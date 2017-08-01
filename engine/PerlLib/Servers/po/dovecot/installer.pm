=head1 NAME

 Servers::po::dovecot::installer - i-MSCP Dovecot IMAP/POP3 Server installer implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::po::dovecot::installer;

use strict;
use warnings;
use File::Basename;
use iMSCP::Crypt qw/ randomStr /;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dialog::InputValidation;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::TemplateParser;
use iMSCP::Umask;
use Servers::mta::postfix;
use Servers::po::dovecot;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

%main::sqlUsers = () unless %main::sqlUsers;

=head1 DESCRIPTION

 i-MSCP Dovecot IMAP/POP3 Server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%eventManager )

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self, $eventManager) = @_;

    my $rs = $eventManager->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->showDialog( @_ ) };
            0;
        }
    );
    $rs ||= $eventManager->register( 'beforeMtaBuildMainCfFile', sub { $self->configurePostfix( @_ ); } );
    $rs ||= $eventManager->register( 'beforeMtaBuildMasterCfFile', sub { $self->configurePostfix( @_ ); } );
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
    my $dbUser = main::setupGetQuestion( 'DOVECOT_SQL_USER', $self->{'config'}->{'DATABASE_USER'} || 'imscp_srv_user' );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $dbPass = main::setupGetQuestion(
        'DOVECOT_SQL_PASSWORD',
        ( ( iMSCP::Getopt->preseed ) ? randomStr( 16, iMSCP::Crypt::ALNUM ) : $self->{'config'}->{'DATABASE_PASSWORD'} )
    );

    if ( $main::reconfigure =~ /^(?:po|servers|all|forced)$/
        || !isValidUsername( $dbUser )
        || !isStringNotInList( $dbUser, 'root', 'debian-sys-maint', $masterSqlUser, 'vlogger_user' )
        || !isValidPassword( $dbPass )
        || !isAvailableSqlUser( $dbUser )
    ) {
        my ($rs, $msg) = ( 0, '' );

        do {
            ( $rs, $dbUser ) = $dialog->inputbox( <<"EOF", $dbUser );

Please enter a username for the Dovecot SQL user:$msg
EOF
            $msg = '';
            if ( !isValidUsername( $dbUser )
                || !isStringNotInList( $dbUser, 'root', 'debian-sys-maint', $masterSqlUser, 'vlogger_user' )
                || !isAvailableSqlUser( $dbUser )
            ) {
                $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            }
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;

        unless ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
            do {
                ( $rs, $dbPass ) = $dialog->inputbox( <<"EOF", $dbPass || randomStr( 16, iMSCP::Crypt::ALNUM ));

Please enter a password for the Dovecot SQL user:$msg
EOF
                $msg = isValidPassword( $dbPass ) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError;
            } while $rs < 30 && $msg;
            return $rs if $rs >= 30;

            $main::sqlUsers{$dbUser . '@' . $dbUserHost} = $dbPass;
        } else {
            $dbPass = $main::sqlUsers{$dbUser . '@' . $dbUserHost};
        }
    } elsif ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
        $dbPass = $main::sqlUsers{$dbUser . '@' . $dbUserHost};
    } else {
        $main::sqlUsers{$dbUser . '@' . $dbUserHost} = $dbPass;
    }

    main::setupSetQuestion( 'DOVECOT_SQL_USER', $dbUser );
    main::setupSetQuestion( 'DOVECOT_SQL_PASSWORD', $dbPass );
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
    $rs ||= $self->_oldEngineCompatibility();
}

=back

=head1 EVENT LISTENERS

=over 4

=item configurePostfix( $fileContent, $fileName )

 Injects configuration for both, Dovecot LDA and Dovecot SASL in Postfix configuration files.

 Listener that listen on the following events:
  - beforeMtaBuildMainCfFile
  - beforeMtaBuildMasterCfFile

 Param string \$fileContent Configuration file content
 Param string $fileName Configuration file name
 Return int 0 on success, other on failure

=cut

sub configurePostfix
{
    my ($self, $fileContent, $fileName) = @_;

    if ( $fileName eq 'main.cf' ) {
        return $self->{'eventManager'}->register(
            'afterMtaBuildConf',
            sub {
                $self->{'mta'}->postconf(
                    (
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
                    )
                );
            }
        );
    }

    if ( $fileName eq 'master.cf' ) {
        my $configSnippet = <<'EOF';
dovecot   unix  -       n       n       -       -       pipe
 flags=DRhu user={MTA_MAILBOX_UID_NAME}:{MTA_MAILBOX_GID_NAME} argv={DOVECOT_DELIVER_PATH} -f ${sender} -d ${user}@${nexthop} -m INBOX.${extension}
EOF
        ${$fileContent} .= process(
            {
                MTA_MAILBOX_UID_NAME => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'},
                MTA_MAILBOX_GID_NAME => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
                DOVECOT_DELIVER_PATH => $self->{'config'}->{'DOVECOT_DELIVER_PATH'}
            },
            $configSnippet
        );
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::po::dovecot::installer

=cut

sub _init
{
    my ($self) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'po'} = Servers::po::dovecot->getInstance();
    $self->{'mta'} = Servers::mta::postfix->getInstance();
    $self->{'cfgDir'} = $self->{'po'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'config'} = $self->{'po'}->{'config'};
    $self;
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

    my $rs = $self->{'eventManager'}->trigger( 'beforePoBkpConfFile', $cfgFile );
    return $rs if $rs;

    if ( -f "$self->{'config'}->{'DOVECOT_CONF_DIR'}/$cfgFile" ) {
        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/$cfgFile" );
        unless ( -f "$self->{'bkpDir'}/$cfgFile.system" ) {
            $rs = $file->copyFile( "$self->{'bkpDir'}/$cfgFile.system", { preserve => 'no' } );
            return $rs if $rs;
        } else {
            $rs = $file->copyFile( "$self->{'bkpDir'}/$cfgFile." . time, { preserve => 'no' } );
            return $rs if $rs;
        }
    }

    $self->{'eventManager'}->trigger( 'afterPoBkpConfFile', $cfgFile );
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

    my $rs = $self->{'eventManager'}->trigger( 'beforePoSetupDb', $dbUser, $dbOldUser, $dbPass, $dbUserHost );
    return $rs if $rs;

    local $@;
    eval {
        my $sqlServer = Servers::sqld->factory();

        # Drop old SQL user if required
        for my $sqlUser ( $dbOldUser, $dbUser ) {
            next unless $sqlUser;

            for my $host( $dbUserHost, $oldDbUserHost ) {
                next if !$host
                    || exists $main::sqlUsers{$sqlUser . '@' . $host} && !defined $main::sqlUsers{$sqlUser . '@' . $host};
                $sqlServer->dropUser( $sqlUser, $host );
            }
        }

        # Create SQL user if required
        if ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
            debug( sprintf( 'Creating %s@%s SQL user', $dbUser, $dbUserHost ));
            $sqlServer->createUser( $dbUser, $dbUserHost, $dbPass );
            $main::sqlUsers{$dbUser . '@' . $dbUserHost} = undef;
        }

        my $dbh = iMSCP::Database->factory()->getRawDb();
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
    $self->{'eventManager'}->trigger( 'afterPoSetupDb' );
}

=item _buildConf( )

 Build dovecot configuration files

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
    my ($self) = @_;

    ( my $dbName = main::setupGetQuestion( 'DATABASE_NAME' ) ) =~ s%('|"|\\)%\\$1%g;
    ( my $dbUser = $self->{'config'}->{'DATABASE_USER'} ) =~ s%('|"|\\)%\\$1%g;
    ( my $dbPass = $self->{'config'}->{'DATABASE_PASSWORD'} ) =~ s%('|"|\\)%\\$1%g;

    my $data = {
        DATABASE_TYPE                 => main::setupGetQuestion( 'DATABASE_TYPE' ),
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
        DOVECOT_LDA_AUTH_SOCKET_PATH  => $self->{'config'}->{'DOVECOT_LDA_AUTH_SOCKET_PATH'},
        DOVECOT_SASL_AUTH_SOCKET_PATH => $self->{'config'}->{'DOVECOT_SASL_AUTH_SOCKET_PATH'},
        ENGINE_ROOT_DIR               => $main::imscpConfig{'ENGINE_ROOT_DIR'},
        POSTFIX_USER                  => $self->{'mta'}->{'config'}->{'POSTFIX_USER'},
        POSTFIX_GROUP                 => $self->{'mta'}->{'config'}->{'POSTFIX_GROUP'},
    };

    # Transitional code (should be removed in later version)
    if ( -f "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-dict-sql.conf" ) {
        iMSCP::File->new( filename => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-dict-sql.conf" )->delFile();
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
                    error( sprintf( "Couldn't read %s file", "$self->{'cfgDir'}/$conffile" ));
                    return 1;
                }
            }

            if ( $conffile eq 'dovecot.conf' ) {
                my $ssl = main::setupGetQuestion( 'SERVICES_SSL_ENABLED' );
                $cfgTpl .= "\nssl = $ssl\n";

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

            $rs = $self->{'eventManager'}->trigger( 'beforePoBuildConf', \$cfgTpl, $conffile );
            return $rs if $rs;

            $cfgTpl = process( $data, $cfgTpl );

            $rs = $self->{'eventManager'}->trigger( 'afterPoBuildConf', \$cfgTpl, $conffile );
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

    return 0 unless $main::imscpOldConfig{'PO_SERVER'} eq 'courier';

    my $rs = $self->{'eventManager'}->trigger( 'beforePoMigrateFromCourier' );
    return $rs if $rs;

    $rs = execute(
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
        $self->{'po'}->{'forceMailboxesQuotaRecalc'} = 1;
        $main::imscpOldConfig{'PO_SERVER'} = 'dovecot';
        $main::imscpOldConfig{'PO_PACKAGE'} = 'Servers::po::dovecot';
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPoMigrateFromCourier' );
}

=item _oldEngineCompatibility( )

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePoOldEngineCompatibility' );
    return $rs if $rs;

    if ( -f "$self->{'cfgDir'}/dovecot.old.data" ) {
        $rs = iMSCP::File->new( filename => "$self->{'cfgDir'}/dovecot.old.data" )->delFile();
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterPodOldEngineCompatibility' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
