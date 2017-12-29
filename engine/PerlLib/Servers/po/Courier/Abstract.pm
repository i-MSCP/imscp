=head1 NAME

 Servers::po::Courier::Abstract - i-MSCP Courier IMAP/POP3 server abstract implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package Servers::po::Courier::Abstract;

use strict;
use warnings;
use Array::Utils qw/ unique /;
use autouse Fcntl => qw/ O_RDONLY /;
use autouse 'iMSCP::Crypt' => qw/ ALNUM randomStr /;
use autouse 'iMSCP::Rights' => qw/ setRights /;
use autouse 'iMSCP::Dialog::InputValidation' => qw/ isAvailableSqlUser isOneOfStringsInList isStringNotInList isValidPassword isValidUsername /;
use File::Basename;
use File::Spec;
use File::Temp;
use iMSCP::Config;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::Execute qw/ execute executeNoWait /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Mount qw/ addMountEntry removeMountEntry isMountpoint mount umount /;
use iMSCP::ProgramFinder;
use iMSCP::Stepper qw/ endDetail startDetail step /;
use iMSCP::SystemUser;
use iMSCP::TemplateParser qw/ processByRef replaceBlocByRef /;
use iMSCP::Umask;
use iMSCP::Service;
use Servers::mta;
use Servers::sqld;
use Sort::Naturally;
use Tie::File;
use parent 'Common::SingletonClass';

%main::sqlUsers = () unless %main::sqlUsers;

=head1 DESCRIPTION

 i-MSCP Courier IMAP/POP3 server abstract implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners()

 Register setup event listeners

 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->authdaemonSqlUserDialog( @_ ) };
            0;
        }
    );
    $rs ||= $self->{'eventManager'}->register( 'beforePostfixBuildMainCfFile', sub { $self->configurePostfix( @_ ); } );
    $rs ||= $self->{'eventManager'}->register( 'beforePostfixBuildMasterCfFile', sub { $self->configurePostfix( @_ ); } );
}

=item authdaemonSqlUserDialog(\%dialog)

 Authdaemon SQL user dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub authdaemonSqlUserDialog
{
    my ($self, $dialog) = @_;

    my $masterSqlUser = main::setupGetQuestion( 'DATABASE_USER' );
    my $dbUser = main::setupGetQuestion(
        'AUTHDAEMON_SQL_USER', $self->{'config'}->{'AUTHDAEMON_DATABASE_USER'} || ( iMSCP::Getopt->preseed ? 'imscp_srv_user' : '' )
    );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $dbPass = main::setupGetQuestion(
        'AUTHDAEMON_SQL_PASSWORD', ( iMSCP::Getopt->preseed ? randomStr( 16, ALNUM ) : $self->{'config'}->{'AUTHDAEMON_DATABASE_PASSWORD'} )
    );

    $iMSCP::Dialog::InputValidation::lastValidationError = '';

    if ( isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ 'po', 'servers', 'all', 'forced' ] )
        || !isValidUsername( $dbUser )
        || !isStringNotInList( lc $dbUser, 'root', 'debian-sys-maint', lc $masterSqlUser, 'vlogger_user' )
        || !isAvailableSqlUser( $dbUser )
    ) {
        my $rs = 0;

        do {
            if ( $dbUser eq '' ) {
                $iMSCP::Dialog::InputValidation::lastValidationError = '';
                $dbUser = 'imscp_srv_user';
            }

            ( $rs, $dbUser ) = $dialog->inputbox( <<"EOF", $dbUser );
$iMSCP::Dialog::InputValidation::lastValidationError
Please enter a username for the Courier Authdaemon SQL user (leave empty for default):
\\Z \\Zn
EOF
        } while $rs < 30
            && ( !isValidUsername( $dbUser )
            || !isStringNotInList( lc $dbUser, 'root', 'debian-sys-maint', lc $masterSqlUser, 'vlogger_user' )
            || !isAvailableSqlUser( $dbUser )
        );

        return $rs unless $rs < 30;
    }

    main::setupSetQuestion( 'AUTHDAEMON_SQL_USER', $dbUser );

    if ( isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ 'po', 'servers', 'all', 'forced' ] ) || !isValidPassword( $dbPass ) ) {
        unless ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
            my $rs = 0;

            do {
                if ( $dbPass eq '' ) {
                    $iMSCP::Dialog::InputValidation::lastValidationError = '';
                    $dbPass = randomStr( 16, ALNUM );
                }

                ( $rs, $dbPass ) = $dialog->inputbox( <<"EOF", $dbPass );
$iMSCP::Dialog::InputValidation::lastValidationError
Please enter a password for the Courier Authdaemon user (leave empty for autogeneration):
\\Z \\Zn
EOF
            } while $rs < 30 && !isValidPassword( $dbPass );

            return $rs unless $rs < 30;

            $main::sqlUsers{$dbUser . '@' . $dbUserHost} = $dbPass;
        } else {
            $dbPass = $main::sqlUsers{$dbUser . '@' . $dbUserHost};
        }
    } elsif ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
        $dbPass = $main::sqlUsers{$dbUser . '@' . $dbUserHost};
    } else {
        $main::sqlUsers{$dbUser . '@' . $dbUserHost} = $dbPass;
    }

    main::setupSetQuestion( 'AUTHDAEMON_SQL_PASSWORD', $dbPass );
    0;
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    $self->stop();
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->_setupAuthdaemonSqlUser();
    $rs ||= $self->_buildConf();
    $rs ||= $self->_setupSASL();
    $rs ||= $self->_migrateFromDovecot();
    $rs ||= $self->_cleanup();
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    eval {
        my @toEnableServices = ( 'AUTHDAEMON_SNAME', 'POPD_SNAME', 'IMAPD_SNAME' );
        my @toDisableServices = ();

        if ( $main::imscpConfig{'SERVICES_SSL_ENABLED'} eq 'yes' ) {
            push @toEnableServices, 'POPD_SSL_SNAME', 'IMAPD_SSL_SNAME';
        } else {
            push @toDisableServices, 'POPD_SSL_SNAME', 'IMAPD_SSL_SNAME';
        }

        my $serviceMngr = iMSCP::Service->getInstance();
        $serviceMngr->enable( $self->{'config'}->{$_} ) for @toEnableServices;

        for ( @toDisableServices ) {
            $serviceMngr->stop( $self->{'config'}->{$_} );
            $serviceMngr->disable( $self->{'config'}->{$_} );
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]}, [ sub { $self->start(); }, 'Courier IMAP/POP, Courier Authdaemon' ];
            0;
        },
        5
    );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    # In setup context, processing must be delayed, else we won't be able to connect to SQL server
    if ( $main::execmode eq 'setup' ) {
        return $self->{'eventManager'}->register(
            'afterSqldPreinstall',
            sub {
                my $rs ||= $self->_dropSqlUser();
                $rs ||= $self->_removeConfig();
            }
        );
    }

    my $rs = $self->_dropSqlUser();
    $rs ||= $self->_removeConfig();

    unless ( $rs || !iMSCP::Service->getInstance()->hasService( $self->{'config'}->{'AUTHDAEMON_SNAME'} ) ) {
        $self->{'restart'} ||= 1;
    } else {
        $self->{'restart'} ||= 0;
    }

    $rs;
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ($self) = @_;

    if ( -d $self->{'config'}->{'AUTHLIB_SOCKET_DIR'} ) {
        my $rs ||= setRights( $self->{'config'}->{'AUTHLIB_SOCKET_DIR'},
            {
                user  => $self->{'config'}->{'AUTHDAEMON_USER'},
                group => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
                mode  => '0750'
            }
        );
        return $rs if $rs;
    }

    my $rs = setRights( "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authmysqlrc",
        {
            user  => $self->{'config'}->{'AUTHDAEMON_USER'},
            group => $self->{'config'}->{'AUTHDAEMON_GROUP'},
            mode  => '0660'
        }
    );
    $rs ||= setRights( $self->{'config'}->{'QUOTA_WARN_MSG_PATH'},
        {
            user  => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0640'
        }
    );
    return $rs if $rs;

    if ( -f "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/dhparams.pem" ) {
        $rs = setRights( "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/dhparams.pem",
            {
                user  => $self->{'config'}->{'AUTHDAEMON_USER'},
                group => $self->{'config'}->{'AUTHDAEMON_GROUP'},
                mode  => '0600'
            }
        );
        return $rs if $rs;
    }

    0;
}

=item addMail( \%data )

 Process addMail tasks

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub addMail
{
    my ($self, $data) = @_;

    return 0 unless index( $data->{'MAIL_TYPE'}, '_mail' ) != -1;

    my $mailDir = "$self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}";
    my $mailUidName = $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'};
    my $mailGidName = $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'};

    eval {
        for my $mailbox( '.Drafts', '.Junk', '.Sent', '.Trash' ) {
            iMSCP::Dir->new( dirname => "$mailDir/$mailbox" )->make( {
                user           => $mailUidName,
                group          => $mailGidName,
                mode           => 0750,
                fixpermissions => iMSCP::Getopt->fixPermissions
            } );

            for ( 'cur', 'new', 'tmp' ) {
                iMSCP::Dir->new( dirname => "$mailDir/$mailbox/$_" )->make( {
                    user           => $mailUidName,
                    group          => $mailGidName,
                    mode           => 0750,
                    fixpermissions => iMSCP::Getopt->fixPermissions
                } );
            }
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    my @subscribedFolders = ( 'INBOX.Drafts', 'INBOX.Junk', 'INBOX.Sent', 'INBOX.Trash' );
    my $subscriptionsFile = iMSCP::File->new( filename => "$mailDir/courierimapsubscribed" );

    if ( -f "$mailDir/courierimapsubscribed" ) {
        my $subscriptionsFileContent = $subscriptionsFile->get();
        unless ( defined $subscriptionsFile ) {
            error( "Couldn't read Courier subscriptions file" );
            return 1;
        }

        if ( $subscriptionsFileContent ne '' ) {
            @subscribedFolders = nsort unique ( @subscribedFolders, split( /\n/, $subscriptionsFileContent ));
        }
    }

    my $rs = $subscriptionsFile->set( ( join "\n", @subscribedFolders ) . "\n" );
    $rs = $subscriptionsFile->save();
    $rs ||= $subscriptionsFile->owner( $mailUidName, $mailGidName );
    $rs ||= $subscriptionsFile->mode( 0640 );
    return $rs if $rs;

    if ( $data->{'MAIL_QUOTA'} ) {
        if ( $self->{'forceMailboxesQuotaRecalc'}
            || ( defined $main::execmode && $main::execmode eq 'backend' && $data->{'STATUS'} eq 'tochange' )
            || !-f "$mailDir/maildirsize"
        ) {
            $rs = execute( [ 'maildirmake', '-q', "$data->{'MAIL_QUOTA'}S", $mailDir ], \ my $stdout, \ my $stderr );
            debug( $stdout ) if $stdout;
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;

            my $file = iMSCP::File->new( filename => "$mailDir/maildirsize" );
            $rs ||= $file->owner( $mailUidName, $mailGidName );
            $rs = $file->mode( 0640 );
            return $rs if $rs;
        }

        return 0;
    }

    if ( -f "$mailDir/maildirsize" ) {
        $rs = iMSCP::File->new( filename => "$mailDir/maildirsize" )->delFile();
        return $rs if $rs;
    }

    0;
}

=item start( )

 Start courier servers

 Return int 0 on success, other on failure

=cut

sub start
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeCourierStart' );
    return $rs if $rs;

    eval {
        my $serviceMngr = iMSCP::Service->getInstance();

        for my $service( 'AUTHDAEMON_SNAME', 'POPD_SNAME', 'IMAPD_SNAME' ) {
            $serviceMngr->start( $self->{'config'}->{$service} );
        }

        if ( $main::imscpConfig{'SERVICES_SSL_ENABLED'} eq 'yes' ) {
            for my $service( 'POPD_SSL_SNAME', 'IMAPD_SSL_SNAME' ) {
                $serviceMngr->start( $self->{'config'}->{$service} );
            }
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterCourierStart' );
}

=item stop( )

 Stop courier servers

 Return int 0 on success, other on failure

=cut

sub stop
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeCourierStop' );
    return $rs if $rs;

    eval {
        my $serviceMngr = iMSCP::Service->getInstance();
        for my $service( 'AUTHDAEMON_SNAME', 'POPD_SNAME', 'POPD_SSL_SNAME', 'IMAPD_SNAME', 'IMAPD_SSL_SNAME' ) {
            $serviceMngr->stop( $self->{'config'}->{$service} );
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterCourierStop' );
}

=item restart( )

 Restart courier servers

 Return int 0 on success, other on failure

=cut

sub restart
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeCourierRestart' );
    return $rs if $rs;

    eval {
        my @toRestartServices = ( 'AUTHDAEMON_SNAME', 'POPD_SNAME', 'IMAPD_SNAME' );
        if ( $main::imscpConfig{'SERVICES_SSL_ENABLED'} eq 'yes' ) {
            push @toRestartServices, 'POPD_SSL_SNAME', 'IMAPD_SSL_SNAME';
        }

        my $serviceMngr = iMSCP::Service->getInstance();
        $serviceMngr->restart( $self->{'config'}->{$_} ) for @toRestartServices;
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterCourierRestart' );
}

=item getTraffic( $trafficDb [, $logFile, $trafficIndexDb ] )

 Get IMAP/POP3 traffic data

 Param hashref \%trafficDb Traffic database
 Param string $logFile Path to SMTP log file (only when self-called)
 Param hashref $trafficIndexDb Traffic index database (only when self-called)
 Return void, die on failure

=cut

sub getTraffic
{
    my ($self, $trafficDb, $logFile, $trafficIndexDb) = @_;
    $logFile ||= "$main::imscpConfig{'TRAFF_LOG_DIR'}/$main::imscpConfig{'MAIL_TRAFF_LOG'}";

    unless ( -f $logFile ) {
        debug( sprintf( "IMAP/POP3 %s log file doesn't exist. Skipping ...", $logFile ));
        return;
    }

    debug( sprintf( 'Processing IMAP/POP3 %s log file', $logFile ));

    # We use an index database to keep trace of the last processed logs
    $trafficIndexDb or tie %{$trafficIndexDb}, 'iMSCP::Config', fileName => "$main::imscpConfig{'IMSCP_HOMEDIR'}/traffic_index.db", nodie => 1;
    my ($idx, $idxContent) = ( $trafficIndexDb->{'po_lineNo'} || 0, $trafficIndexDb->{'po_lineContent'} );

    tie my @logs, 'Tie::File', $logFile, mode => O_RDONLY, memory => 0 or die( sprintf( "Couldn't tie %s file in read-only mode", $logFile ));

    # Retain index of the last log (log file can continue growing)
    my $lastLogIdx = $#logs;

    if ( exists $logs[$idx] && $logs[$idx] eq $idxContent ) {
        debug( sprintf( 'Skipping IMAP/POP3 logs that were already processed (lines %d to %d)', 1, ++$idx ));
    } elsif ( $idxContent ne '' && substr( $logFile, -2 ) ne '.1' ) {
        debug( 'Log rotation has been detected. Processing last rotated log file first' );
        $self->getTraffic( $trafficDb, $logFile . '.1', $trafficIndexDb );
        $idx = 0;
    }

    if ( $lastLogIdx < $idx ) {
        debug( 'No new IMAP/POP3 logs found for processing' );
        return;
    }

    debug( sprintf( 'Processing IMAP/POP3 logs (lines %d to %d)', $idx+1, $lastLogIdx+1 ));

    # Extract IMAP/POP3 traffic data
    #
    # Log line examples
    # Apr 21 15:14:44 www pop3d: LOGOUT, user=user@domain.tld, ip=[::ffff:192.168.1.1], port=[36852], top=0, retr=0, rcvd=6, sent=30, time=0, stls=1
    # Apr 21 15:14:55 www imapd: LOGOUT, user=user@domain.tld, ip=[::ffff:192.168.1.1], headers=0, body=0, rcvd=635, sent=1872, time=4477, starttls=1
    # Apr 21 15:23:12 www pop3d-ssl: LOGOUT, user=user@domain.tld, ip=[::ffff:192.168.1.1], port=[59556], top=0, retr=0, rcvd=12, sent=39, time=0, stls=1
    # Apr 21 15:24:36 www imapd-ssl: LOGOUT, user=user@domain.tld, ip=[::ffff:192.168.1.1], headers=0, body=0, rcvd=50, sent=374, time=10, starttls=1
    my $regexp = qr/(?:imapd|pop3d)(?:-ssl)?:.*user=[^\@]+\@(?<domain>[^,]+).*rcvd=(?<rcvd>\d+).*sent=(?<sent>\d+)/;

    # In term of memory usage, C-Style loop provide better results than using 
    # range operator in Perl-Style loop: for( @logs[$idx .. $lastLogIdx] ) ...
    for ( my $i = $idx; $i <= $lastLogIdx; $i++ ) {
        next unless $logs[$i] =~ /$regexp/ && exists $trafficDb->{$+{'domain'}};
        $trafficDb->{$+{'domain'}} += ( $+{'in'}+$+{'out'} );
    }

    return if substr( $logFile, -2 ) eq '.1';

    $trafficIndexDb->{'po_lineNo'} = $lastLogIdx;
    $trafficIndexDb->{'po_lineContent'} = $logs[$lastLogIdx];
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::po::Courier::Abstract

=cut

sub _init
{
    my ($self) = @_;

    @{$self}{qw/ restart forceMailboxesQuotaRecalc mta /} = ( 0, 0, Servers::mta->factory() );
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/courier";
    $self->_mergeConfig() if defined $main::execmode && $main::execmode eq 'setup' && -f "$self->{'cfgDir'}/courier.data.dist";
    tie %{$self->{'config'}},
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/courier.data",
        readonly    => !( defined $main::execmode && $main::execmode eq 'setup' ),
        nodeferring => defined $main::execmode && $main::execmode eq 'setup';
    $self;
}

=item _mergeConfig( )

 Merge distribution configuration with production configuration

 Die on failure

=cut

sub _mergeConfig
{
    my ($self) = @_;

    if ( -f "$self->{'cfgDir'}/courier.data" ) {
        tie my %newConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/courier.data.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/courier.data", readonly => 1;

        debug( 'Merging old configuration with new configuration ...' );

        while ( my ($key, $value) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/courier.data.dist" )->moveFile( "$self->{'cfgDir'}/courier.data" ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
}

=item _setupAuthdaemonSqlUser( )

 Setup authdaemon SQL user

 Return int 0 on success, other on failure

=cut

sub _setupAuthdaemonSqlUser
{
    my ($self) = @_;

    my $dbName = main::setupGetQuestion( 'DATABASE_NAME' );
    my $dbUser = main::setupGetQuestion( 'AUTHDAEMON_SQL_USER' );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $oldDbUserHost = $main::imscpOldConfig{'DATABASE_USER_HOST'};
    my $dbPass = main::setupGetQuestion( 'AUTHDAEMON_SQL_PASSWORD' );
    my $dbOldUser = $self->{'config'}->{'AUTHDAEMON_DATABASE_USER'};

    my $rs = $self->{'eventManager'}->trigger( 'beforeCourierSetupAuthdaemonSqlUser', $dbUser, $dbOldUser, $dbPass, $dbUserHost );
    return $rs if $rs;

    eval {
        my $sqlServer = Servers::sqld->factory();

        # Drop old SQL user if required
        for my $sqlUser ( $dbOldUser, $dbUser ) {
            next unless $sqlUser;

            for my $host( $dbUserHost, $oldDbUserHost ) {
                next if !$host || exists $main::sqlUsers{$sqlUser . '@' . $host} && !defined $main::sqlUsers{$sqlUser . '@' . $host};
                $sqlServer->dropUser( $sqlUser, $host );
            }
        }

        # Create SQL user if required
        if ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
            debug( sprintf( 'Creating %s@%s SQL user', $dbUser, $dbUserHost ));
            $sqlServer->createUser( $dbUser, $dbUserHost, $dbPass );
            $main::sqlUsers{$dbUser . '@' . $dbUserHost} = undef;
        }

        my $dbh = iMSCP::Database->getInstance()->getRawDb();
        local $dbh->{'RaiseError'} = 1;

        # Give required privileges to this SQL user
        # No need to escape wildcard characters. See https://bugs.mysql.com/bug.php?id=18660
        my $quotedDbName = $dbh->quote_identifier( $dbName );
        $dbh->do( "GRANT SELECT ON $quotedDbName.mail_users TO ?\@?", undef, $dbUser, $dbUserHost );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'config'}->{'AUTHDAEMON_DATABASE_USER'} = $dbUser;
    $self->{'config'}->{'AUTHDAEMON_DATABASE_PASSWORD'} = $dbPass;
    $self->{'eventManager'}->trigger( 'afterCourierSetupAuthdaemonSqlUser' );
}

=item _buildConf( )

 Build courier configuration files

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
    my ($self) = @_;

    my $rs = $self->_buildDHparametersFile();
    $rs ||= $self->_buildAuthdaemonrcFile();
    $rs ||= $self->_buildSslConfFiles();
    return $rs if $rs;

    my $data = {
        DATABASE_HOST        => main::setupGetQuestion( 'DATABASE_HOST' ),
        DATABASE_PORT        => main::setupGetQuestion( 'DATABASE_PORT' ),
        DATABASE_USER        => $self->{'config'}->{'AUTHDAEMON_DATABASE_USER'},
        DATABASE_PASSWORD    => $self->{'config'}->{'AUTHDAEMON_DATABASE_PASSWORD'},
        DATABASE_NAME        => main::setupGetQuestion( 'DATABASE_NAME' ),
        HOST_NAME            => main::setupGetQuestion( 'SERVER_HOSTNAME' ),
        MTA_MAILBOX_UID      => ( scalar getpwnam( $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'} ) ),
        MTA_MAILBOX_GID      => ( scalar getgrnam( $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'} ) ),
        MTA_VIRTUAL_MAIL_DIR => $self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}
    };

    my %cfgFiles = (
        authmysqlrc     => [
            "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authmysqlrc", # Destpath
            $self->{'config'}->{'AUTHDAEMON_USER'}, # Owner
            $self->{'config'}->{'AUTHDAEMON_GROUP'}, # Group
            0640 # Permissions
        ],
        'quota-warning' => [
            $self->{'config'}->{'QUOTA_WARN_MSG_PATH'}, # Destpath
            $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'}, # Owner
            $main::imscpConfig{'ROOT_GROUP'}, # Group
            0640 # Permissions
        ]
    );

    {
        local $UMASK = 027; # authmysqlrc file must not be created/copied world-readable

        for my $conffile( keys %cfgFiles ) {
            $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'courier', $conffile, \ my $cfgTpl, $data );
            return $rs if $rs;

            unless ( defined $cfgTpl ) {
                $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/$conffile" )->get();
                unless ( defined $cfgTpl ) {
                    error( sprintf( "Couldn't read the %s file", "$self->{'cfgDir'}/$conffile" ));
                    return 1;
                }
            }

            $rs = $self->{'eventManager'}->trigger( 'beforeCourierBuildConf', \ $cfgTpl, $conffile );
            return $rs if $rs;

            processByRef( $data, \$cfgTpl );

            $rs = $self->{'eventManager'}->trigger( 'afterCourierBuildConf', \ $cfgTpl, $conffile );
            return $rs if $rs;

            my $file = iMSCP::File->new( filename => $cfgFiles{$conffile}->[0] );
            $file->set( $cfgTpl );

            $rs = $file->save();
            $rs ||= $file->owner( $cfgFiles{$conffile}->[1], $cfgFiles{$conffile}->[2] );
            $rs ||= $file->mode( $cfgFiles{$conffile}->[3] );
            return $rs if $rs;
        }
    }

    if ( -f "$self->{'cfgDir'}/imapd.local" ) {
        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'COURIER_CONF_DIR'}/imapd" );
        my $fileContentRef = $file->getAsRef();
        unless ( defined $fileContentRef ) {
            error( sprintf( "Couldn't read the %s file", $file->{'filename'} ));
            return 1;
        }

        replaceBlocByRef(
            qr/(?:^\n)?# Servers::po::Courier::Abstract::installer - BEGIN\n/m, qr/# Servers::po::Courier::Abstract::installer - ENDING\n/, '',
            $fileContentRef
        );

        ${$fileContentRef} .= <<"EOF";

# Servers::po::Courier::Abstract::installer - BEGIN
. $self->{'cfgDir'}/imapd.local
# Servers::po::Courier::Abstract::installer - ENDING
EOF
        $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    0;
}

=item _setupSASL( )

 Setup SASL for Postfix

 Return int 0 on success, other on failure

=cut

sub _setupSASL
{
    my ($self) = @_;

    # Add postfix user in `mail' group to make it able to access
    # authdaemon rundir
    my $rs = iMSCP::SystemUser->new()->addToGroup(
        $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}, $self->{'mta'}->{'config'}->{'POSTFIX_USER'}
    );
    return $rs if $rs;

    # Mount authdaemond socket directory in Postfix chroot
    # Postfix won't be able to connect to socket located outside of its chroot
    my $fsSpec = File::Spec->canonpath( $self->{'config'}->{'AUTHLIB_SOCKET_DIR'} );
    my $fsFile = File::Spec->canonpath(
        "$self->{'mta'}->{'config'}->{'POSTFIX_QUEUE_DIR'}/$self->{'config'}->{'AUTHLIB_SOCKET_DIR'}"
    );
    my $fields = { fs_spec => $fsSpec, fs_file => $fsFile, fs_vfstype => 'none', fs_mntops => 'bind,slave' };

    eval { iMSCP::Dir->new( dirname => $fsFile )->make(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs = addMountEntry( "$fields->{'fs_spec'} $fields->{'fs_file'} $fields->{'fs_vfstype'} $fields->{'fs_mntops'}" );
    $rs ||= mount( $fields ) unless isMountpoint( $fields->{'fs_file'} );

    # Build Cyrus SASL smtpd.conf configuration file

    $rs ||= $self->{'eventManager'}->trigger( 'onLoadTemplate', 'courier', 'smtpd.conf', \ my $cfgTpl );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/sasl/smtpd.conf" )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read the %s file", "$self->{'cfgDir'}/sasl/smtpd.conf" ));
            return 1;
        }
    }

    processByRef(
        {
            PWCHECK_METHOD  => $self->{'config'}->{'PWCHECK_METHOD'},
            LOG_LEVEL       => $self->{'config'}->{'LOG_LEVEL'},
            MECH_LIST       => $self->{'config'}->{'MECH_LIST'},
            AUTHDAEMON_PATH => $self->{'config'}->{'AUTHDAEMON_PATH'}
        },
        \$cfgTpl
    );

    local $UMASK = 027; # smtpd.conf file must not be created/copied world-readable
    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'SASL_CONF_DIR'}/smtpd.conf" );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0640 );
}

=item _buildDHparametersFile( )

 Build the DH parameters file with a stronger size (2048 instead of 768)

 Fix: #IP-1401
 Return int 0 on success, other on failure

=cut

sub _buildDHparametersFile
{
    my ($self) = @_;

    return 0 unless iMSCP::ProgramFinder::find( 'certtool' ) || iMSCP::ProgramFinder::find( 'mkdhparams' );

    if ( -f "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/dhparams.pem" ) {
        my $rs = execute(
            [ 'openssl', 'dhparam', '-in', "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/dhparams.pem", '-text', '-noout' ],
            \ my $stdout,
            \ my $stderr
        );
        debug( $stderr || 'Unknown error' ) if $rs;
        if ( $rs == 0 && $stdout =~ /\((\d+)\s+bit\)/ && $1 >= 2048 ) {
            return 0; # Don't regenerate file if not needed
        }

        $rs = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/dhparams.pem" )->delFile();
        return $rs if $rs;
    }

    startDetail();

    my $rs = step(
        sub {
            my ($tmpFile, $cmd);

            if ( iMSCP::ProgramFinder::find( 'certtool' ) ) {
                $tmpFile = File::Temp->new( UNLINK => 0 );
                $cmd = "certtool --generate-dh-params --sec-param medium > $tmpFile";
            } else {
                $cmd = 'DH_BITS=2048 mkdhparams';
            }

            my $output = '';
            my $outputHandler = sub {
                next if $_[0] =~ /^[.+]/;
                $output .= $_[0];
                step( undef, "Generating DH parameter file\n\n$output", 1, 1 );
            };

            my $rs = executeNoWait( $cmd, ( iMSCP::Getopt->noprompt && !iMSCP::Getopt->verbose ? sub {} : $outputHandler ), $outputHandler );
            error( $output || 'Unknown error' ) if $rs;
            $rs ||= iMSCP::File->new(
                filename => $tmpFile->filename )->moveFile( "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/dhparams.pem"
            ) if $tmpFile;
            $rs;
        }, 'Generating DH parameter file', 1, 1
    );
    endDetail();
    $rs;
}

=item _buildAuthdaemonrcFile( )

 Build the authdaemonrc file

 Return int 0 on success, other on failure

=cut

sub _buildAuthdaemonrcFile
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'courier', 'authdaemonrc', \ my $cfgTpl, {} );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authdaemonrc" )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read the %s file", "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authdaemonrc" ));
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeCourierBuildAuthdaemonrcFile', \ $cfgTpl, 'authdaemonrc' );
    return $rs if $rs;

    $cfgTpl =~ s/authmodulelist=".*"/authmodulelist="authmysql"/;

    $rs = $self->{'eventManager'}->trigger( 'afterCourierBuildAuthdaemonrcFile', \ $cfgTpl, 'authdaemonrc' );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authdaemonrc" );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $self->{'config'}->{'AUTHDAEMON_USER'}, $self->{'config'}->{'AUTHDAEMON_GROUP'} );
    $rs ||= $file->mode( 0660 );
}

=item _buildSslConfFiles( )

 Build ssl configuration file

 Return int 0 on success, other on failure

=cut

sub _buildSslConfFiles
{
    my ($self) = @_;

    return 0 unless main::setupGetQuestion( 'SERVICES_SSL_ENABLED', 'no' ) eq 'yes';

    for ( $self->{'config'}->{'COURIER_IMAP_SSL'}, $self->{'config'}->{'COURIER_POP_SSL'} ) {
        my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'courier', $_, \ my $cfgTpl, {} );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $cfgTpl = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/$_" )->get();
            unless ( defined $cfgTpl ) {
                error( sprintf( "Couldn't read the %s file", "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/$_" ));
                return 1;
            }
        }

        $rs = $self->{'eventManager'}->trigger( 'beforeCourierBuildSslConfFile', \ $cfgTpl, $_ );
        return $rs if $rs;

        if ( $cfgTpl =~ /^TLS_CERTFILE=/gm ) {
            $cfgTpl =~ s!^(TLS_CERTFILE=).*!$1$main::imscpConfig{'CONF_DIR'}/imscp_services.pem!gm;
        } else {
            $cfgTpl .= "TLS_CERTFILE=$main::imscpConfig{'CONF_DIR'}/imscp_services.pem\n";
        }

        $rs = $self->{'eventManager'}->trigger( 'afterCourierBuildSslConfFile', \ $cfgTpl, $_ );
        return $rs if $rs;

        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/$_" );
        $file->set( $cfgTpl );
        $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    0;
}

=item _migrateFromDovecot( )

 Migrate mailboxes from Dovecot

 Return int 0 on success, other on failure

=cut

sub _migrateFromDovecot
{
    my ($self) = @_;

    return 0 unless index( $main::imscpOldConfig{'Servers::po'}, 'Dovecot' ) != -1;

    my $rs = execute(
        [
            '/usr/bin/perl', "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlVendor/courier-dovecot-migrate.pl", '--to-courier', '--quiet', '--convert',
            '--overwrite', '--recursive', $self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}
        ],
        \ my $stdout,
        \ my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;

    unless ( $rs ) {
        $self->{'forceMailboxesQuotaRecalc'} = 1;
        $main::imscpOldConfig{'Servers::po'} = $main::imscpConfig{'Servers::po'};
    }

    $rs;
}

=item _cleanup( )

 Processc cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ($self) = @_;

    if ( -f "$self->{'cfgDir'}/courier.old.data" ) {
        my $rs = iMSCP::File->new( filename => "$self->{'cfgDir'}/courier.old.data" )->delFile();
        return $rs if $rs;
    }

    if ( -f "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/userdb" ) {
        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/userdb" );
        $file->set( '' );
        my $rs = $file->save();
        $rs ||= $file->mode( 0600 );
        return $rs if $rs;

        $rs = execute( [ 'makeuserdb', '-f', "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/userdb" ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
    }

    # Remove postfix user from authdaemon group.
    # It is now added in mail group (since 1.5.0)
    my $rs = iMSCP::SystemUser->new()->removeFromGroup(
        $self->{'config'}->{'AUTHDAEMON_GROUP'}, $self->{'mta'}->{'config'}->{'POSTFIX_USER'}
    );
    return $rs if $rs;

    # Remove old authdaemon socket private/authdaemon mount directory.
    # Replaced by var/run/courier/authdaemon (since 1.5.0)
    my $fsFile = File::Spec->canonpath( "$self->{'mta'}->{'config'}->{'POSTFIX_QUEUE_DIR'}/private/authdaemon" );
    $rs ||= umount( $fsFile );
    return $rs if $rs;

    eval { iMSCP::Dir->new( dirname => $fsFile )->remove(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _dropSqlUser( )

 Drop SQL user

 Return int 0 on success, other on failure

=cut

sub _dropSqlUser
{
    my ($self) = @_;

    # In setup context, take value from old conffile, else take value from current conffile
    my $dbUserHost = ( $main::execmode eq 'setup' ) ? $main::imscpOldConfig{'DATABASE_USER_HOST'} : $main::imscpConfig{'DATABASE_USER_HOST'};

    return 0 unless $self->{'config'}->{'AUTHDAEMON_DATABASE_USER'} && $dbUserHost;

    eval { Servers::sqld->factory()->dropUser( $self->{'config'}->{'AUTHDAEMON_DATABASE_USER'}, $dbUserHost ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _removeConfig( )

 Remove configuration

 Return int 0 on success, other on failure

=cut

sub _removeConfig
{
    my ($self) = @_;

    # Umount the courier-authdaemond rundir from the Postfix chroot
    my $fsFile = File::Spec->canonpath( "$self->{'mta'}->{'config'}->{'POSTFIX_QUEUE_DIR'}/$self->{'config'}->{'AUTHLIB_SOCKET_DIR'}" );
    my $rs = removeMountEntry( qr%.*?[ \t]+\Q$fsFile\E(?:/|[ \t]+)[^\n]+% );
    $rs ||= umount( $fsFile );
    return $rs if $rs;

    eval { iMSCP::Dir->new( dirname => $fsFile )->remove(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # Remove the `postfix' user from the `mail' group
    $rs = iMSCP::SystemUser->new()->removeFromGroup(
        $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}, $self->{'mta'}->{'config'}->{'POSTFIX_USER'}
    );
    return $rs if $rs;

    # Remove i-MSCP configuration stanza from the courier-imap daemon configuration file
    if ( -f "$self->{'config'}->{'COURIER_CONF_DIR'}/imapd" ) {
        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'COURIER_CONF_DIR'}/imapd" );
        my $fileContentRef = $file->getAsRef();
        unless ( defined $fileContentRef ) {
            error( sprintf( "Couldn't read the %s file", $file->{'filename'} ));
            return 1;
        }

        replaceBlocByRef(
            qr/(?:^\n)?# Servers::po::Courier::Abstract::installer - BEGIN\n/m, qr/# Servers::po::Courier::Abstract::installer - ENDING\n/, '',
            $fileContentRef
        );

        $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    # Remove the configuration file for SASL
    if ( -f "$self->{'config'}->{'SASL_CONF_DIR'}/smtpd.conf" ) {
        $rs = iMSCP::File->new( filename => "$self->{'config'}->{'SASL_CONF_DIR'}/smtpd.conf" )->delFile();
        return $rs if $rs;
    }

    # Remove the systemd-tmpfiles file
    if ( -f '/etc/tmpfiles.d/courier-authdaemon.conf' ) {
        $rs = iMSCP::File->new( filename => '/etc/tmpfiles.d/courier-authdaemon.conf' )->delFile();
        return $rs if $rs;
    }

    # Remove the quota warning script
    if ( -f $self->{'config'}->{'QUOTA_WARN_MSG_PATH'} ) {
        $rs = iMSCP::File->new( filename => $self->{'config'}->{'QUOTA_WARN_MSG_PATH'} )->delFile();
        return $rs if $rs;
    }

    0;
}

=back

=head1 EVENT LISTENERS

=over 4

=item configurePostfix( \$fileContent, $fileName )

 Injects configuration for both, maildrop MDA and Cyrus SASL in Postfix configuration files.

 Listener that listen on the following events:
  - beforePostfixBuildMainCfFile
  - beforePostfixBuildMasterCfFile

 Param string \$fileContent Configuration file content
 Param string $fileName Configuration filename
 Return int 0 on success, other on failure

=cut

sub configurePostfix
{
    my ($self, $fileContent, $fileName) = @_;

    if ( $fileName eq 'main.cf' ) {
        return $self->{'eventManager'}->register(
            'afterPostfixBuildConf',
            sub {
                $self->{'mta'}->postconf( (
                    # Maildrop MDA parameters
                    virtual_transport                      => {
                        action => 'replace',
                        values => [ 'maildrop' ]
                    },
                    maildrop_destination_concurrency_limit => {
                        action => 'replace',
                        values => [ '2' ]
                    },
                    maildrop_destination_recipient_limit   => {
                        action => 'replace',
                        values => [ '1' ]
                    },
                    # Cyrus SASL parameters
                    smtpd_sasl_type                        => {
                        action => 'replace',
                        values => [ 'cyrus' ]
                    },
                    smtpd_sasl_path                        => {
                        action => 'replace',
                        values => [ 'smtpd' ]
                    },
                    smtpd_sasl_auth_enable                 => {
                        action => 'replace',
                        values => [ 'yes' ]
                    },
                    smtpd_sasl_security_options            => {
                        action => 'replace',
                        values => [ 'noanonymous' ]
                    },
                    smtpd_sasl_authenticated_header        => {
                        action => 'replace',
                        values => [ 'yes' ]
                    },
                    broken_sasl_auth_clients               => {
                        action => 'replace',
                        values => [ 'yes' ]
                    },
                    # SMTP restrictions
                    smtpd_helo_restrictions                => {
                        action => 'add',
                        values => [ 'permit_sasl_authenticated' ],
                        after  => qr/permit_mynetworks/
                    },
                    smtpd_sender_restrictions              => {
                        action => 'add',
                        values => [ 'permit_sasl_authenticated' ],
                        after  => qr/permit_mynetworks/
                    },
                    smtpd_recipient_restrictions           => {
                        action => 'add',
                        values => [ 'permit_sasl_authenticated' ],
                        after  => qr/permit_mynetworks/
                    }
                ));
            }
        );
    }

    if ( $fileName eq 'master.cf' ) {
        ${$fileContent} .= <<"EOF"
maildrop  unix  -       n       n       -       -       pipe
 flags=DRhu user=$self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'}:$self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'} argv=maildrop -w 90 -d \${user}\@\${nexthop} \${extension} \${recipient} \${user} \${nexthop} \${sender}
EOF
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
