=head1 NAME

 Servers::po::courier::installer - i-MSCP Courier IMAP/POP3 Server installer implementation

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

package Servers::po::courier::installer;

use strict;
use warnings;
use File::Basename;
use File::Spec;
use File::Temp;
use iMSCP::Crypt qw/ ALNUM randomStr /;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dialog::InputValidation qw/ isAvailableSqlUser isOneOfStringsInList isStringNotInList isValidPassword isValidUsername /;
use iMSCP::Dir;
use iMSCP::Execute qw/ execute executeNoWait /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Mount qw/ addMountEntry isMountpoint mount umount /;
use iMSCP::ProgramFinder;
use iMSCP::Stepper qw/ endDetail startDetail step /;
use iMSCP::SystemUser;
use iMSCP::TemplateParser qw/ processByRef replaceBlocByRef /;
use iMSCP::Umask;
use Servers::sqld;
use parent 'Common::SingletonClass';

%main::sqlUsers = () unless %main::sqlUsers;

=head1 DESCRIPTION

 i-MSCP Courier IMAP/POP3 Server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( )

 Register setup event listeners

 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self) = @_;

    my $rs = $self->{'po'}->{'eventManager'}->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->authdaemonSqlUserDialog( @_ ) };
            0;
        }
    );
    $rs ||= $self->{'po'}->{'eventManager'}->register( 'beforePostfixBuildMainCfFile', sub { $self->configurePostfix( @_ ); } );
    $rs ||= $self->{'po'}->{'eventManager'}->register( 'beforePostfixBuildMasterCfFile', sub { $self->configurePostfix( @_ ); } );
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
        'AUTHDAEMON_SQL_USER', $self->{'po'}->{'config'}->{'AUTHDAEMON_DATABASE_USER'} || ( iMSCP::Getopt->preseed ? 'imscp_srv_user' : '' )
    );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $dbPass = main::setupGetQuestion(
        'AUTHDAEMON_SQL_PASSWORD',
        ( iMSCP::Getopt->preseed ? randomStr( 16, ALNUM ) : $self->{'po'}->{'config'}->{'AUTHDAEMON_DATABASE_PASSWORD'} )
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
        return $self->{'po'}->{'eventManager'}->register(
            'afterPostfixBuildConf',
            sub {
                $self->{'po'}->{'mta'}->postconf( (
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
 flags=DRhu user=$self->{'po'}->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'}:$self->{'po'}->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'} argv=maildrop -w 90 -d \${user}\@\${nexthop} \${extension} \${recipient} \${user} \${nexthop} \${sender}
EOF
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

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
    my $dbOldUser = $self->{'po'}->{'config'}->{'AUTHDAEMON_DATABASE_USER'};

    my $rs = $self->{'po'}->{'eventManager'}->trigger( 'beforeCourierSetupAuthdaemonSqlUser', $dbUser, $dbOldUser, $dbPass, $dbUserHost );
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

    $self->{'po'}->{'config'}->{'AUTHDAEMON_DATABASE_USER'} = $dbUser;
    $self->{'po'}->{'config'}->{'AUTHDAEMON_DATABASE_PASSWORD'} = $dbPass;
    $self->{'po'}->{'eventManager'}->trigger( 'afterCourierSetupAuthdaemonSqlUser' );
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
        DATABASE_USER        => $self->{'po'}->{'config'}->{'AUTHDAEMON_DATABASE_USER'},
        DATABASE_PASSWORD    => $self->{'po'}->{'config'}->{'AUTHDAEMON_DATABASE_PASSWORD'},
        DATABASE_NAME        => main::setupGetQuestion( 'DATABASE_NAME' ),
        HOST_NAME            => main::setupGetQuestion( 'SERVER_HOSTNAME' ),
        MTA_MAILBOX_UID      => ( scalar getpwnam( $self->{'po'}->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'} ) ),
        MTA_MAILBOX_GID      => ( scalar getgrnam( $self->{'po'}->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'} ) ),
        MTA_VIRTUAL_MAIL_DIR => $self->{'po'}->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}
    };

    my %cfgFiles = (
        authmysqlrc     => [
            "$self->{'po'}->{'config'}->{'AUTHLIB_CONF_DIR'}/authmysqlrc", # Destpath
            $self->{'po'}->{'config'}->{'AUTHDAEMON_USER'}, # Owner
            $self->{'po'}->{'config'}->{'AUTHDAEMON_GROUP'}, # Group
            0640 # Permissions
        ],
        'quota-warning' => [
            $self->{'po'}->{'config'}->{'QUOTA_WARN_MSG_PATH'}, # Destpath
            $self->{'po'}->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'}, # Owner
            $main::imscpConfig{'ROOT_GROUP'}, # Group
            0640 # Permissions
        ]
    );

    {
        local $UMASK = 027; # authmysqlrc file must not be created/copied world-readable

        for my $conffile( keys %cfgFiles ) {
            $rs = $self->{'po'}->{'eventManager'}->trigger( 'onLoadTemplate', 'courier', $conffile, \ my $cfgTpl, $data );
            return $rs if $rs;

            unless ( defined $cfgTpl ) {
                $cfgTpl = iMSCP::File->new( filename => "$self->{'po'}->{'cfgDir'}/$conffile" )->get();
                unless ( defined $cfgTpl ) {
                    error( sprintf( "Couldn't read the %s file", "$self->{'po'}->{'cfgDir'}/$conffile" ));
                    return 1;
                }
            }

            $rs = $self->{'po'}->{'eventManager'}->trigger( 'beforeCourierBuildConf', \ $cfgTpl, $conffile );
            return $rs if $rs;

            processByRef( $data, \$cfgTpl );

            $rs = $self->{'po'}->{'eventManager'}->trigger( 'afterCourierBuildConf', \ $cfgTpl, $conffile );
            return $rs if $rs;

            my $file = iMSCP::File->new( filename => $cfgFiles{$conffile}->[0] );
            $file->set( $cfgTpl );

            $rs = $file->save();
            $rs ||= $file->owner( $cfgFiles{$conffile}->[1], $cfgFiles{$conffile}->[2] );
            $rs ||= $file->mode( $cfgFiles{$conffile}->[3] );
            return $rs if $rs;
        }
    }

    if ( -f "$self->{'po'}->{'cfgDir'}/imapd.local" ) {
        my $file = iMSCP::File->new( filename => "$self->{'po'}->{'config'}->{'COURIER_CONF_DIR'}/imapd" );
        my $fileContentRef = $file->getAsRef();
        unless ( defined $fileContentRef ) {
            error( sprintf( "Couldn't read the %s file", $file->{'filename'} ));
            return 1;
        }

        replaceBlocByRef(
            qr/(?:^\n)?# Servers::po::courier::installer - BEGIN\n/m, qr/# Servers::po::courier::installer - ENDING\n/, '', $fileContentRef
        );

        ${$fileContentRef} .= <<"EOF";

# Servers::po::courier::installer - BEGIN
. $self->{'po'}->{'cfgDir'}/imapd.local
# Servers::po::courier::installer - ENDING
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
        $self->{'po'}->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}, $self->{'po'}->{'mta'}->{'config'}->{'POSTFIX_USER'}
    );
    return $rs if $rs;

    # Mount authdaemond socket directory in Postfix chroot
    # Postfix won't be able to connect to socket located outside of its chroot
    my $fsSpec = File::Spec->canonpath( $self->{'po'}->{'config'}->{'AUTHLIB_SOCKET_DIR'} );
    my $fsFile = File::Spec->canonpath(
        "$self->{'po'}->{'mta'}->{'config'}->{'POSTFIX_QUEUE_DIR'}/$self->{'po'}->{'config'}->{'AUTHLIB_SOCKET_DIR'}"
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

    $rs ||= $self->{'po'}->{'eventManager'}->trigger( 'onLoadTemplate', 'courier', 'smtpd.conf', \ my $cfgTpl );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'po'}->{'cfgDir'}/sasl/smtpd.conf" )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read the %s file", "$self->{'po'}->{'cfgDir'}/sasl/smtpd.conf" ));
            return 1;
        }
    }

    processByRef(
        {
            PWCHECK_METHOD  => $self->{'po'}->{'config'}->{'PWCHECK_METHOD'},
            LOG_LEVEL       => $self->{'po'}->{'config'}->{'LOG_LEVEL'},
            MECH_LIST       => $self->{'po'}->{'config'}->{'MECH_LIST'},
            AUTHDAEMON_PATH => $self->{'po'}->{'config'}->{'AUTHDAEMON_PATH'}
        },
        \$cfgTpl
    );

    local $UMASK = 027; # smtpd.conf file must not be created/copied world-readable
    my $file = iMSCP::File->new( filename => "$self->{'po'}->{'config'}->{'SASL_CONF_DIR'}/smtpd.conf" );
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

    if ( -f "$self->{'po'}->{'config'}->{'AUTHLIB_CONF_DIR'}/dhparams.pem" ) {
        my $rs = execute(
            [ 'openssl', 'dhparam', '-in', "$self->{'po'}->{'config'}->{'AUTHLIB_CONF_DIR'}/dhparams.pem", '-text', '-noout' ],
            \ my $stdout,
            \ my $stderr
        );
        debug( $stderr || 'Unknown error' ) if $rs;
        if ( $rs == 0 && $stdout =~ /\((\d+)\s+bit\)/ && $1 >= 2048 ) {
            return 0; # Don't regenerate file if not needed
        }

        $rs = iMSCP::File->new( filename => "$self->{'po'}->{'config'}->{'AUTHLIB_CONF_DIR'}/dhparams.pem" )->delFile();
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
                filename => $tmpFile->filename )->moveFile( "$self->{'po'}->{'config'}->{'AUTHLIB_CONF_DIR'}/dhparams.pem"
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

    my $rs = $self->{'po'}->{'eventManager'}->trigger( 'onLoadTemplate', 'courier', 'authdaemonrc', \ my $cfgTpl, {} );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'po'}->{'config'}->{'AUTHLIB_CONF_DIR'}/authdaemonrc" )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read the %s file", "$self->{'po'}->{'config'}->{'AUTHLIB_CONF_DIR'}/authdaemonrc" ));
            return 1;
        }
    }

    $rs = $self->{'po'}->{'eventManager'}->trigger( 'beforeCourierBuildAuthdaemonrcFile', \ $cfgTpl, 'authdaemonrc' );
    return $rs if $rs;

    $cfgTpl =~ s/authmodulelist=".*"/authmodulelist="authmysql"/;

    $rs = $self->{'po'}->{'eventManager'}->trigger( 'afterCourierBuildAuthdaemonrcFile', \ $cfgTpl, 'authdaemonrc' );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => "$self->{'po'}->{'config'}->{'AUTHLIB_CONF_DIR'}/authdaemonrc" );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $self->{'po'}->{'config'}->{'AUTHDAEMON_USER'}, $self->{'po'}->{'config'}->{'AUTHDAEMON_GROUP'} );
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

    for ( $self->{'po'}->{'config'}->{'COURIER_IMAP_SSL'}, $self->{'po'}->{'config'}->{'COURIER_POP_SSL'} ) {
        my $rs = $self->{'po'}->{'eventManager'}->trigger( 'onLoadTemplate', 'courier', $_, \ my $cfgTpl, {} );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $cfgTpl = iMSCP::File->new( filename => "$self->{'po'}->{'config'}->{'AUTHLIB_CONF_DIR'}/$_" )->get();
            unless ( defined $cfgTpl ) {
                error( sprintf( "Couldn't read the %s file", "$self->{'po'}->{'config'}->{'AUTHLIB_CONF_DIR'}/$_" ));
                return 1;
            }
        }

        $rs = $self->{'po'}->{'eventManager'}->trigger( 'beforeCourierBuildSslConfFile', \ $cfgTpl, $_ );
        return $rs if $rs;

        if ( $cfgTpl =~ /^TLS_CERTFILE=/gm ) {
            $cfgTpl =~ s!^(TLS_CERTFILE=).*!$1$main::imscpConfig{'CONF_DIR'}/imscp_services.pem!gm;
        } else {
            $cfgTpl .= "TLS_CERTFILE=$main::imscpConfig{'CONF_DIR'}/imscp_services.pem\n";
        }

        $rs = $self->{'po'}->{'eventManager'}->trigger( 'afterCourierBuildSslConfFile', \ $cfgTpl, $_ );
        return $rs if $rs;

        my $file = iMSCP::File->new( filename => "$self->{'po'}->{'config'}->{'AUTHLIB_CONF_DIR'}/$_" );
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

    return 0 unless $main::imscpOldConfig{'PO_SERVER'} eq 'dovecot';

    my $rs = execute(
        [
            '/usr/bin/perl', "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlVendor/courier-dovecot-migrate.pl", '--to-courier', '--quiet', '--convert',
            '--overwrite', '--recursive', $self->{'po'}->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}
        ],
        \ my $stdout,
        \ my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;

    unless ( $rs ) {
        $self->{'po'}->{'forceMailboxesQuotaRecalc'} = 1;
        $main::imscpOldConfig{'PO_SERVER'} = 'courier';
        $main::imscpOldConfig{'PO_PACKAGE'} = 'Servers::po::courier';
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

    if ( -f "$self->{'po'}->{'cfgDir'}/courier.old.data" ) {
        my $rs = iMSCP::File->new( filename => "$self->{'po'}->{'cfgDir'}/courier.old.data" )->delFile();
        return $rs if $rs;
    }

    if ( -f "$self->{'po'}->{'config'}->{'AUTHLIB_CONF_DIR'}/userdb" ) {
        my $file = iMSCP::File->new( filename => "$self->{'po'}->{'config'}->{'AUTHLIB_CONF_DIR'}/userdb" );
        $file->set( '' );
        my $rs = $file->save();
        $rs ||= $file->mode( 0600 );
        return $rs if $rs;

        $rs = execute( [ 'makeuserdb', '-f', "$self->{'po'}->{'config'}->{'AUTHLIB_CONF_DIR'}/userdb" ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
    }

    # Remove postfix user from authdaemon group.
    # It is now added in mail group (since 1.5.0)
    my $rs = iMSCP::SystemUser->new()->removeFromGroup(
        $self->{'po'}->{'config'}->{'AUTHDAEMON_GROUP'}, $self->{'po'}->{'mta'}->{'config'}->{'POSTFIX_USER'}
    );
    return $rs if $rs;

    # Remove old authdaemon socket private/authdaemon mount directory.
    # Replaced by var/run/courier/authdaemon (since 1.5.0)
    my $fsFile = File::Spec->canonpath( "$self->{'po'}->{'mta'}->{'config'}->{'POSTFIX_QUEUE_DIR'}/private/authdaemon" );
    $rs ||= umount( $fsFile );
    return $rs if $rs;

    eval { iMSCP::Dir->new( dirname => $fsFile )->remove(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
