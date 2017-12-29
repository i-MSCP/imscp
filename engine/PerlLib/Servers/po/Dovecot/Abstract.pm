=head1 NAME

 Servers::po::Dovecot::Abstract - i-MSCP Dovecot IMAP/POP3 Server implementation

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

package Servers::po::Dovecot::Abstract;

use strict;
use warnings;
use autouse Fcntl => qw/ O_RDONLY /;
use autouse 'iMSCP::Crypt' => qw/ ALNUM randomStr /;
use autouse 'iMSCP::Dialog::InputValidation' => qw/ isAvailableSqlUser isOneOfStringsInList isStringNotInList isValidPassword isValidUsername /;
use autouse 'iMSCP::Execute' => qw/ execute /;
use autouse 'iMSCP::Rights' => qw/ setRights /;
use autouse 'iMSCP::TemplateParser' => qw/ processByRef /;
use Array::Utils qw/ unique /;
use File::Temp;
use Class::Autouse qw/ :nostat iMSCP::Database /;
use iMSCP::Config;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Service;
use iMSCP::Umask;
use Servers::mta;
use Servers::sqld;
use Sort::Naturally;
use Tie::File;
use parent 'Common::SingletonClass';

%main::sqlUsers = () unless %main::sqlUsers;

=head1 DESCRIPTION

 i-MSCP Dovecot IMAP/POP3 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( )

 Register setup event listeners

 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->showDialog( @_ ) };
            0;
        }
    );
    $rs ||= $self->{'eventManager'}->register( 'beforePostfixBuildMainCfFile', sub { $self->configurePostfix( @_ ); } );
    $rs ||= $self->{'eventManager'}->register( 'beforePostfixBuildMasterCfFile', sub { $self->configurePostfix( @_ ); } );
}

=item showDialog( \%dialog )

 Ask user for Dovecot restricted SQL user

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub showDialog
{
    my ($self, $dialog) = @_;

    my $masterSqlUser = main::setupGetQuestion( 'DATABASE_USER' );
    my $dbUser = main::setupGetQuestion(
        'DOVECOT_SQL_USER', $self->{'config'}->{'DATABASE_USER'} || ( iMSCP::Getopt->preseed ? 'imscp_srv_user' : '' )
    );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $dbPass = main::setupGetQuestion(
        'DOVECOT_SQL_PASSWORD', ( iMSCP::Getopt->preseed ? randomStr( 16, ALNUM ) : $self->{'config'}->{'DATABASE_PASSWORD'} )
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
Please enter a username for the Dovecot SQL user (leave empty for default):
\\Z \\Zn
EOF
        } while $rs < 30
            && ( !isValidUsername( $dbUser )
            || !isStringNotInList( lc $dbUser, 'root', 'debian-sys-maint', lc $masterSqlUser, 'vlogger_user' )
            || !isAvailableSqlUser( $dbUser )
        );

        return $rs unless $rs < 30;
    }

    main::setupSetQuestion( 'DOVECOT_SQL_USER', $dbUser );

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
Please enter a password for the Dovecot SQL user (leave empty for autogeneration):
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

    main::setupSetQuestion( 'DOVECOT_SQL_PASSWORD', $dbPass );
    0;
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    eval {
        my $serviceMngr = iMSCP::Service->getInstance();

        # Disable dovecot.socket unit if any
        # Dovecot as configured by i-MSCP doesn't rely on systemd activation socket
        # This also solve problem on boxes where IPv6 is not available; default dovecot.socket unit file make
        # assumption that IPv6 is available without further checks...
        # See also: https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=814999
        if ( $serviceMngr->isSystemd() && $serviceMngr->hasService( 'dovecot.socket' ) ) {
            $serviceMngr->stop( 'dovecot.socket' );
            $serviceMngr->disable( 'dovecot.socket' );
        }

        $self->stop();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    for ( 'dovecot.conf', 'dovecot-sql.conf' ) {
        my $rs = $self->_bkpConfFile( $_ );
        return $rs if $rs;
    }

    my $rs = $self->_setDovecotVersion();
    $rs ||= $self->_setupSqlUser();
    $rs ||= $self->_buildConf();
    $rs ||= $self->_migrateFromCourier();
    $rs ||= $self->_cleanup();
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeDovecotPostinstall' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->enable( $self->{'config'}->{'DOVECOT_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs = $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]}, [ sub { $self->start(); }, 'Dovecot' ];
            0;
        },
        5
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterDovecotPostinstall' );
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
        return $self->{'eventManager'}->getInstance()->register(
            'afterSqldPreinstall',
            sub {
                my $rs ||= $self->_dropSqlUser();
                $rs ||= $self->_removeConfig();
            }
        );
    }

    my $rs = $self->_dropSqlUser();
    $rs ||= $self->_removeConfig();

    unless ( $rs || !iMSCP::Service->getInstance()->hasService( $self->{'config'}->{'DOVECOT_SNAME'} ) ) {
        $self->{'restart'} ||= 1;
    } else {
        $self->{'restart'} ||= 0;
    }

    $rs;
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

    my @subscribedFolders = ( 'Drafts', 'Junk', 'Sent', 'Trash' );
    my $subscriptionsFile = iMSCP::File->new( filename => "$mailDir/subscriptions" );

    if ( -f "$mailDir/subscriptions" ) {
        my $subscriptionsFileContent = $subscriptionsFile->get();
        unless ( defined $subscriptionsFileContent ) {
            error( "Couldn't read Dovecot subscriptions file" );
            return 1;
        }

        if ( $subscriptionsFileContent ne '' ) {
            @subscribedFolders = nsort unique ( @subscribedFolders, split( /\n/, $subscriptionsFileContent ));
        }
    }

    my $rs = $subscriptionsFile->set( ( join "\n", @subscribedFolders ) . "\n" );
    $rs ||= $subscriptionsFile->save();
    $rs ||= $subscriptionsFile->owner( $mailUidName, $mailGidName );
    $rs ||= $subscriptionsFile->mode( 0640 );
    return $rs if $rs;

    if ( $data->{'MAIL_QUOTA'} ) {
        if ( $self->{'forceMailboxesQuotaRecalc'}
            || ( defined $main::execmode && $main::execmode && $data->{'STATUS'} eq 'tochange' )
            || !-f "$mailDir/maildirsize"
        ) {
            # TODO create maildirsize file manually (set quota definition and recalculate byte and file counts)
            if ( -f "$mailDir/maildirsize" ) {
                $rs = iMSCP::File->new( filename => "$mailDir/maildirsize" )->delFile();
                return $rs if $rs;
            }
        }

        return 0;
    }

    if ( -f "$mailDir/maildirsize" ) {
        $rs = iMSCP::File->new( filename => "$mailDir/maildirsize" )->delFile();
        return $rs if $rs;
    }

    0;
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ($self) = @_;

    my $rs = setRights( $self->{'config'}->{'DOVECOT_CONF_DIR'},
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0755'
        }
    );
    $rs ||= setRights( "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot.conf",
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            mode  => '0640'
        }
    );
    $rs ||= setRights( "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf",
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            mode  => '0640'
        }
    );
    $rs ||= setRights( "$main::imscpConfig{'ENGINE_ROOT_DIR'}/quota/imscp-dovecot-quota.sh",
        {
            user  => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'},
            group => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            mode  => '0750'
        }
    );
}

=item start( )

 Start Dovecot

 Return int 0 on success, other on failure

=cut

sub start
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeDovecotStart' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->start( $self->{'config'}->{'DOVECOT_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterDovecotStart' );
}

=item stop( )

 Stop Dovecot

 Return int 0 on success, other on failure

=cut

sub stop
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeDovecotStop' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->stop( $self->{'config'}->{'DOVECOT_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterDovecotStop' );
}

=item restart( )

 Restart Dovecot

 Return int 0 on success, other on failure

=cut

sub restart
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeDovecotRestart' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->restart( $self->{'config'}->{'DOVECOT_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterDovecotRestart' );
}

=item getTraffic( $trafficDb [, $logFile, $trafficIndexDb ] )

 Get IMAP/POP3 traffic data

 Param hashref \%trafficDb Traffic database
 Param string $logFile Path to SMTP log file (only when self-called)
 Param hashref $trafficIndexDb Traffic index database (only when self-called)
 Die on failure

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

    tie my @logs, 'Tie::File', $logFile, mode => O_RDONLY, memory => 0 or die(
        sprintf( "Couldn't tie %s file in read-only mode", $logFile )
    );

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
    # Apr 18 23:41:48 jessie dovecot: imap(user@domain.tld): Disconnected: Logged out in=244 out=858
    # Apr 18 23:41:48 jessie dovecot: pop3(user@domain.tld): Disconnected: Logged out top=0/0, retr=0/0, del=0/0, size=0, in=12, out=43
    my $regexp = qr/(?:imap|pop3)\([^\@]+\@(?<domain>[^\)]+)\):.*in=(?<in>\d+).*out=(?<out>\d+)$/;

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

 Return Servers::po::Dovecot::Abstract

=cut

sub _init
{
    my ($self) = @_;

    @{$self}{qw/ restart forceMailboxesQuotaRecalc mta /} = ( 0, 0, Servers::mta->factory() );
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/dovecot";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->_mergeConfig() if defined $main::execmode && $main::execmode eq 'setup' && -f "$self->{'cfgDir'}/dovecot.data.dist";
    tie %{$self->{'config'}},
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/dovecot.data",
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

    if ( -f "$self->{'cfgDir'}/dovecot.data" ) {
        tie my %newConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/dovecot.data.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/dovecot.data", readonly => 1;

        debug( 'Merging old configuration with new configuration ...' );

        while ( my ($key, $value) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/dovecot.data.dist" )->moveFile( "$self->{'cfgDir'}/dovecot.data" ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
}

=item _setDovecotVersion( )

 Set Dovecot version

 Return int 0 on success, other on failure

=cut

sub _setDovecotVersion
{
    my ($self) = @_;

    my $rs = execute( [ 'dovecot', '--version' ], \ my $stdout, \ my $stderr );
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    if ( $stdout !~ m/^([\d.]+)/ ) {
        error( "Couldn't guess Dovecot version" );
        return 1;
    }

    $self->{'config'}->{'DOVECOT_VERSION'} = $1;
    debug( sprintf( 'Dovecot version set to: %s', $1 ));
    0;
}

=item _bkpConfFile( $cfgFile )

 Backup the given file

 Param string $cfgFile Configuration file name
 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
    my ($self, $cfgFile) = @_;

    return 0 unless -f "$self->{'config'}->{'DOVECOT_CONF_DIR'}/$cfgFile";

    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/$cfgFile" );
    unless ( -f "$self->{'bkpDir'}/$cfgFile.system" ) {
        my $rs = $file->copyFile( "$self->{'bkpDir'}/$cfgFile.system", { preserve => 'no' } );
        return $rs if $rs;
    } else {
        my $rs = $file->copyFile( "$self->{'bkpDir'}/$cfgFile." . time, { preserve => 'no' } );
        return $rs if $rs;
    }

    0;
}

=item _setupSqlUser( )

 Setup SQL user

 Return int 0 on success, other on failure

=cut

sub _setupSqlUser
{
    my ($self) = @_;

    my $dbName = main::setupGetQuestion( 'DATABASE_NAME' );
    my $dbUser = main::setupGetQuestion( 'DOVECOT_SQL_USER' );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $oldDbUserHost = $main::imscpOldConfig{'DATABASE_USER_HOST'};
    my $dbPass = main::setupGetQuestion( 'DOVECOT_SQL_PASSWORD' );
    my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

    my $rs = $self->{'eventManager'}->trigger( 'beforeDovecotSetupDb', $dbUser, $dbOldUser, $dbPass, $dbUserHost );
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

    $self->{'config'}->{'DATABASE_USER'} = $dbUser;
    $self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
    $self->{'eventManager'}->trigger( 'afterDovecotSetupDb' );
}

=item _buildConf( )

 Build dovecot configuration files

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
    my ($self) = @_;

    eval {
        # Make the /etc/dovecot/imscp.d direcetory free of any file that were
        # installed by i-MSCP listener files.
        iMSCP::Dir->new( dirname => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/imscp.d" )->clear( undef, qr/_listener\.conf$/ )
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    ( my $dbName = main::setupGetQuestion( 'DATABASE_NAME' ) ) =~ s%('|"|\\)%\\$1%g;
    ( my $dbUser = $self->{'config'}->{'DATABASE_USER'} ) =~ s%('|"|\\)%\\$1%g;
    ( my $dbPass = $self->{'config'}->{'DATABASE_PASSWORD'} ) =~ s%('|"|\\)%\\$1%g;

    my $data = {
        DATABASE_HOST                 => main::setupGetQuestion( 'DATABASE_HOST' ),
        DATABASE_PORT                 => main::setupGetQuestion( 'DATABASE_PORT' ),
        DATABASE_NAME                 => $dbName,
        DATABASE_USER                 => $dbUser,
        DATABASE_PASSWORD             => $dbPass,
        HOSTNAME                      => main::setupGetQuestion( 'SERVER_HOSTNAME' ),
        IMSCP_GROUP                   => $main::imscpConfig{'IMSCP_GROUP'},
        MTA_VIRTUAL_MAIL_DIR          => $self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'},
        MTA_MAILBOX_UID_NAME          => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'},
        MTA_MAILBOX_GID_NAME          => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
        MTA_MAILBOX_UID               => ( scalar getpwnam( $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'} ) ),
        MTA_MAILBOX_GID               => ( scalar getgrnam( $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'} ) ),
        NETWORK_PROTOCOLS             => main::setupGetQuestion( 'IPV6_SUPPORT' ) ? '*, [::]' : '*',
        POSTFIX_SENDMAIL_PATH         => $self->{'mta'}->{'config'}->{'POSTFIX_SENDMAIL_PATH'},
        DOVECOT_CONF_DIR              => $self->{'config'}->{'DOVECOT_CONF_DIR'},
        DOVECOT_DELIVER_PATH          => $self->{'config'}->{'DOVECOT_DELIVER_PATH'},
        DOVECOT_SASL_AUTH_SOCKET_PATH => $self->{'config'}->{'DOVECOT_SASL_AUTH_SOCKET_PATH'},
        ENGINE_ROOT_DIR               => $main::imscpConfig{'ENGINE_ROOT_DIR'},
        POSTFIX_USER                  => $self->{'mta'}->{'config'}->{'POSTFIX_USER'},
        POSTFIX_GROUP                 => $self->{'mta'}->{'config'}->{'POSTFIX_GROUP'},
    };

    # Transitional code (should be removed in later version)
    if ( -f "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-dict-sql.conf" ) {
        my $rs = iMSCP::File->new( filename => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-dict-sql.conf" )->delFile();
        return $rs if $rs;
    }

    my %cfgFiles = (
        'dovecot.conf'     => [
            "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot.conf", # Destpath
            $main::imscpConfig{'ROOT_USER'}, # Owner
            $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # Group
            0640 # Permissions
        ],
        'dovecot-sql.conf' => [
            "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf", # Destpath
            $main::imscpConfig{'ROOT_USER'}, # owner
            $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # Group
            0640 # Permissions
        ],
        'quota-warning'    => [
            "$main::imscpConfig{'ENGINE_ROOT_DIR'}/quota/imscp-dovecot-quota.sh", # Destpath
            $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'}, # Owner
            $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # Group
            0750 # Permissions
        ]
    );

    {
        local $UMASK = 027; # dovecot-sql.conf file must not be created/copied world-readable

        for my $conffile( keys %cfgFiles ) {
            my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'dovecot', $conffile, \ my $cfgTpl, $data );
            return $rs if $rs;

            unless ( defined $cfgTpl ) {
                $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/$conffile" )->get();
                unless ( defined $cfgTpl ) {
                    error( sprintf( "Couldn't read the %s file", "$self->{'cfgDir'}/$conffile" ));
                    return 1;
                }
            }

            if ( $conffile eq 'dovecot.conf' ) {
                my $ssl = main::setupGetQuestion( 'SERVICES_SSL_ENABLED' );
                $cfgTpl .= <<"EOF";

# SSL

ssl = $ssl
EOF
                # Fixme: Find a better way to guess libssl version
                if ( $ssl eq 'yes' ) {
                    unless ( `ldd /usr/lib/dovecot/libdovecot-login.so | grep libssl.so` =~ /libssl.so.(\d.\d)/ ) {
                        error( "Couldn't guess libssl version against which Dovecot has been built" );
                        return 1;
                    }

                    $cfgTpl .= <<"EOF";
ssl_protocols = @{[ version->parse( $1 ) >= version->parse( '1.1' ) ? '!SSLv3' : '!SSLv2 !SSLv3' ]}
ssl_cert = <$main::imscpConfig{'CONF_DIR'}/imscp_services.pem
ssl_key = <$main::imscpConfig{'CONF_DIR'}/imscp_services.pem
EOF
                }
            }

            $rs = $self->{'eventManager'}->trigger( 'beforeDovecotBuildConf', \$cfgTpl, $conffile );
            return $rs if $rs;

            processByRef( $data, \$cfgTpl );

            $rs = $self->{'eventManager'}->trigger( 'afterDovecotBuildConf', \$cfgTpl, $conffile );
            return $rs if $rs;

            my $filename = fileparse( $cfgFiles{$conffile}->[0] );
            my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$filename" );
            $file->set( $cfgTpl );
            $rs = $file->save();
            $rs ||= $file->owner( $cfgFiles{$conffile}->[1], $cfgFiles{$conffile}->[2] );
            $rs ||= $file->mode( $cfgFiles{$conffile}->[3] );
            $rs ||= $file->copyFile( $cfgFiles{$conffile}->[0] );
            return $rs if $rs;
        }
    }

    0;
}

=item _migrateFromCourier( )

 Migrate mailboxes from Courier

 Return int 0 on success, other on failure

=cut

sub _migrateFromCourier
{
    my ($self) = @_;

    return 0 unless index( $main::imscpOldConfig{'Servers::po'}, 'Courier' ) != -1;

    my $rs = execute(
        [
            'perl', "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlVendor/courier-dovecot-migrate.pl", '--to-dovecot',
            '--quiet', '--convert', '--overwrite', '--recursive', $self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}
        ],
        \ my $stdout,
        \ my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    error( $stderr || 'Error while migrating from Courier to Dovecot' ) if $rs;

    unless ( $rs ) {
        $self->{'forceMailboxesQuotaRecalc'} = 1;
        $main::imscpOldConfig{'Servers::po'} = $main::imscpConfig{'Servers::po'};
    }

    $rs;
}

=item _cleanup( )

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ($self) = @_;

    return 0 unless -f "$self->{'cfgDir'}/dovecot.old.data";

    iMSCP::File->new( filename => "$self->{'cfgDir'}/dovecot.old.data" )->delFile();
}

=item _dropSqlUser( )

 Drop SQL user

 Return int 0 on success, 1 on failure

=cut

sub _dropSqlUser
{
    my ($self) = @_;

    # In setup context, take value from old conffile, else take value from current conffile
    my $dbUserHost = ( $main::execmode eq 'setup' ) ? $main::imscpOldConfig{'DATABASE_USER_HOST'} : $main::imscpConfig{'DATABASE_USER_HOST'};

    return 0 unless $self->{'config'}->{'DATABASE_USER'} && $dbUserHost;

    eval { Servers::sqld->factory()->dropUser( $self->{'config'}->{'DATABASE_USER'}, $dbUserHost ); };
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

    return 0 unless -d $self->{'config'}->{'DOVECOT_CONF_DIR'};

    for ( 'dovecot.conf', 'dovecot-sql.conf' ) {
        next unless -f "$self->{'bkpDir'}/$_.system";

        my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$_.system" )->copyFile(
            "$self->{'config'}->{'DOVECOT_CONF_DIR'}/$_", { preserve => 'no' }
        );
        return $rs if $rs;
    }

    if ( -f "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf" ) {
        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf" );
        my $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'} );
        $rs ||= $file->mode( 0644 );
    }

    eval { iMSCP::Dir->new( dirnname => '/etc/dovecot/imscp.d' )->remove(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=back

=head1 EVENT LISTENERS

=over 4

=item configurePostfix( $fileContent, $fileName )

 Injects configuration for both, Dovecot LDA and Dovecot SASL in Postfix configuration files.

 Listener that listen on the following events:
  - beforePostfixBuildMainCfFile
  - beforePostfixBuildMasterCfFile

 Param string \$fileContent Configuration file content
 Param string $fileName Configuration file name
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
                    # Dovecot LDA parameters
                    virtual_transport                     => {
                        action => 'replace',
                        values => [ 'dovecot' ]
                    },
                    dovecot_destination_concurrency_limit => {
                        action => 'replace',
                        values => [ '2' ]
                    },
                    dovecot_destination_recipient_limit   => {
                        action => 'replace',
                        values => [ '1' ]
                    },
                    # Dovecot SASL parameters
                    smtpd_sasl_type                       => {
                        action => 'replace',
                        values => [ 'dovecot' ]
                    },
                    smtpd_sasl_path                       => {
                        action => 'replace',
                        values => [ 'private/auth' ]
                    },
                    smtpd_sasl_auth_enable                => {
                        action => 'replace',
                        values => [ 'yes' ]
                    },
                    smtpd_sasl_security_options           => {
                        action => 'replace',
                        values => [ 'noanonymous' ]
                    },
                    smtpd_sasl_authenticated_header       => {
                        action => 'replace',
                        values => [ 'yes' ]
                    },
                    broken_sasl_auth_clients              => {
                        action => 'replace',
                        values => [ 'yes' ]
                    },
                    # SMTP restrictions
                    smtpd_helo_restrictions               => {
                        action => 'add',
                        values => [ 'permit_sasl_authenticated' ],
                        after  => qr/permit_mynetworks/
                    },
                    smtpd_sender_restrictions             => {
                        action => 'add',
                        values => [ 'permit_sasl_authenticated' ],
                        after  => qr/permit_mynetworks/
                    },
                    smtpd_recipient_restrictions          => {
                        action => 'add',
                        values => [ 'permit_sasl_authenticated' ],
                        after  => qr/permit_mynetworks/
                    }
                ));
            }
        );
    }

    if ( $fileName eq 'master.cf' ) {
        ${$fileContent} .= <<"EOF";
dovecot   unix  -       n       n       -       -       pipe
 flags=DRhu user=$self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'}:$self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'} argv=$self->{'config'}->{'DOVECOT_DELIVER_PATH'} -f \${sender} -d \${user}\@\${nexthop} -m INBOX.\${extension}
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
