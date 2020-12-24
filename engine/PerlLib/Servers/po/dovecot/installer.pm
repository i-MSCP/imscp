=head1 NAME

 Servers::po::dovecot::installer - i-MSCP Dovecot IMAP/POP3 Server installer implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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
use File::Basename 'fileparse';
use iMSCP::Crypt qw/ ALPHA64 decryptRijndaelCBC encryptRijndaelCBC randomStr /;
use iMSCP::Boolean;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute 'execute';
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Service;
use iMSCP::TemplateParser 'process';
use iMSCP::Umask '$UMASK';
use Servers::mta::postfix;
use Servers::po::dovecot;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Dovecot IMAP/POP3 Server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%events )

 Register setup event listeners

 Param iMSCP::EventManager \%events
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $events ) = @_;

    my $rs = $events->register(
        'beforeMtaBuildMainCfFile', sub { $self->configurePostfix( @_ ); }
    );
    $rs ||= $events->register(
        'beforeMtaBuildMasterCfFile', sub { $self->configurePostfix( @_ ); }
    );
}

=item install( )

 Pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    my $rs = $self->_setDovecotVersion();
    return $rs if $rs;

    local $@;
    $rs = eval {
        my $service = iMSCP::Service->getInstance();

        # Disable dovecot.socket unit if any
        # Dovecot as configured by i-MSCP doesn't rely on systemd activation
        # socket.  This also solve problem on boxes where IPv6 is not
        # available; default dovecot.socket unit file make  assumption that
        # IPv6 is available without further checks...
        # See also: https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=814999
        if ( $service->isSystemd()
            && $service->hasService( 'dovecot.socket' )
        ) {
            $service->stop( 'dovecot.socket' );
            $service->disable( 'dovecot.socket' );
        }

        #$self->{'po'}->stop();
        0;
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs;
}

=item install( )

 Installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    for my $file ( 'dovecot.conf', 'dovecot-sql.conf' ) {
        my $rs = $self->_bkpConfFile( $file );
        return $rs if $rs;
    }

    my $rs = $self->_makeDirs();
    $rs ||= $self->_setupSqlUser();
    $rs ||= $self->_buildConf();
    $rs ||= $self->_migrateFromCourier();
    $rs ||= $self->_oldEngineCompatibility();
}

=item postinstall( )

 Post-installation tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    local $@;
    eval { iMSCP::Service->getInstance()->enable(
        $self->{'config'}->{'DOVECOT_SNAME'}
    ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{ $_[0] }, [ sub { $self->{'po'}->restart(); }, 'Dovecot' ];
            0;
        },
        5
    );
}

=back

=head1 EVENT LISTENERS

=over 4

=item configurePostfix( $fileContent, $fileName )

 Injects configuration for both, Dovecot LDA and Dovecot SASL in Postfix
 configuration files.

 Listener that listen on the following events:
  - beforeMtaBuildMainCfFile
  - beforeMtaBuildMasterCfFile

 Param string \$fileContent Configuration file content
 Param string $fileName Configuration file name
 Return int 0 on success, other on failure

=cut

sub configurePostfix
{
    my ( $self, $fileContent, $fileName ) = @_;

    if ( $fileName eq 'main.cf' ) {
        return $self->{'events'}->register( 'afterMtaBuildConf', sub {
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
        } );
    }

    if ( $fileName eq 'master.cf' ) {
        my $configSnippet = <<'EOF';
dovecot   unix  -       n       n       -       -       pipe
 flags=DRhu user={MTA_MAILBOX_UID_NAME}:{MTA_MAILBOX_GID_NAME} argv={DOVECOT_DELIVER_PATH} -f ${sender} -d ${user}@${nexthop} -m INBOX.${extension}
EOF
        ${ $fileContent } .= process(
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
    my ( $self ) = @_;

    $self->{'events'} = iMSCP::EventManager->getInstance();
    $self->{'po'} = Servers::po::dovecot->getInstance();
    $self->{'mta'} = Servers::mta::postfix->getInstance();
    $self->{'cfgDir'} = $self->{'po'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'config'} = $self->{'po'}->{'config'};
    $self;
}

=item _makeDirs( )

 Create required directories

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
    my ( $self ) = @_;

    local $@;
    eval {
        iMSCP::Dir->new(
            dirname => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/imscp.d"
        )->make( {
            user  => $::imscpConfig{'ROOT_USER'},
            group => $::imscpConfig{'ROOT_GROUP'},
            mode  => 0755
        } );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _setDovecotVersion( )

 Set Dovecot version

 Return int 0 on success, other on failure

=cut

sub _setDovecotVersion
{
    my ( $self ) = @_;

    my $rs = execute( [ 'dovecot', '--version' ], \my $stdout, \my $stderr );
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
    my ( $self, $cfgFile ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforePoBkpConfFile', $cfgFile );
    return $rs if $rs;

    if ( -f "$self->{'config'}->{'DOVECOT_CONF_DIR'}/$cfgFile" ) {
        my $file = iMSCP::File->new(
            filename => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/$cfgFile"
        );

        unless ( -f "$self->{'bkpDir'}/$cfgFile.system" ) {
            $rs = $file->copyFile(
                "$self->{'bkpDir'}/$cfgFile.system", { preserve => 'no' }
            );
            return $rs if $rs;
        } else {
            $rs = $file->copyFile(
                "$self->{'bkpDir'}/$cfgFile." . time, { preserve => 'no' }
            );
            return $rs if $rs;
        }
    }

    $self->{'events'}->trigger( 'afterPoBkpConfFile', $cfgFile );
}

=item _setupSqlUser( )

 Setup SQL user

 Return int 0 on success, other on failure

=cut

sub _setupSqlUser
{
    my ( $self ) = @_;

    my $rs = eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        my %config = @{ $dbh->selectcol_arrayref(
            "
                SELECT `name`, `value`
                FROM `config`
                WHERE `name` LIKE 'DOVECOT_SQL_%'
            ",
            { Columns => [ 1, 2 ] }
        ) };

        if ( length $config{'DOVECOT_SQL_USER'} ) {
            $config{'DOVECOT_SQL_USER'} = decryptRijndaelCBC(
                $::imscpDBKey, $::imscpDBiv, $config{'DOVECOT_SQL_USER'}
            );
        } else {
            $config{'DOVECOT_SQL_USER'} = 'dovecot_' . randomStr(
                8, ALPHA64
            );
        }

        if ( length $config{'DOVECOT_SQL_USER_PASSWD'} ) {
            $config{'DOVECOT_SQL_USER_PASSWD'} = decryptRijndaelCBC(
                $::imscpDBKey, $::imscpDBiv, $config{'DOVECOT_SQL_USER_PASSWD'}
            );
        } else {
            $config{'DOVECOT_SQL_USER_PASSWD'} = randomStr( 16, ALPHA64 );
        }

        (
            $self->{'_dovecot_sql_user'}, $self->{'_dovecot_sql_user_passwd'}
        ) = (
            $config{'DOVECOT_SQL_USER'}, $config{'DOVECOT_SQL_USER_PASSWD'}
        );

        $dbh->do(
            '
                INSERT INTO `config` (`name`,`value`)
                VALUES (?,?),(?,?)
                ON DUPLICATE KEY UPDATE `name` = `name`
            ',
            undef,
            'DOVECOT_SQL_USER',
            encryptRijndaelCBC(
                $::imscpDBKey,
                $::imscpDBiv,
                $config{'DOVECOT_SQL_USER'}
            ),
            'DOVECOT_SQL_USER_PASSWD',
            encryptRijndaelCBC(
                $::imscpDBKey,
                $::imscpDBiv,
                $config{'DOVECOT_SQL_USER_PASSWD'}
            )
        );

        my $sqlServer = Servers::sqld->factory();

        for my $host (
            $::imscpOldConfig{'DATABASE_USER_HOST'},
            ::setupGetQuestion( 'DATABASE_USER_HOST' )
        ) {
            next unless length $host;
            for my $user (
                $config{'DOVECOT_SQL_USER'},
                $self->{'dovecot'}->{'oldConfig'}->{'DATABASE_USER'} # Transitional
            ) {
                next unless length $user;
                $sqlServer->dropUser( $user, $host );
            }
        }

        $sqlServer->createUser(
            $config{'DOVECOT_SQL_USER'},
            ::setupGetQuestion( 'DATABASE_USER_HOST' ),
            $config{'DOVECOT_SQL_USER_PASSWD'},
        );

        $dbh->do(
            "
                GRANT SELECT
                ON `@{ [ ::setupGetQuestion( 'DATABASE_NAME' ) ] }`.`mail_users`
                TO ?\@?
            ",
            undef,
            $config{'DOVECOT_SQL_USER'},
            ::setupGetQuestion( 'DATABASE_USER_HOST' )
        );

        0;
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs;
}

=item _buildConf( )

 Build dovecot configuration files

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
    my ( $self ) = @_;

    ( my $dbName = ::setupGetQuestion( 'DATABASE_NAME' ) ) =~ s%('|"|\\)%\\$1%g;
    ( my $dbUser = $self->{'_dovecot_sql_user'} ) =~ s%('|"|\\)%\\$1%g;
    ( my $dbPass = $self->{'_dovecot_sql_user_passwd'} ) =~ s%('|"|\\)%\\$1%g;

    my $data = {
        DATABASE_HOST                            => ::setupGetQuestion( 'DATABASE_HOST' ),
        DATABASE_PORT                            => ::setupGetQuestion( 'DATABASE_PORT' ),
        DATABASE_NAME                            => $dbName,
        DATABASE_USER                            => $dbUser,
        DATABASE_PASSWORD                        => $dbPass,
        HOSTNAME                                 => ::setupGetQuestion( 'SERVER_HOSTNAME' ),
        IMSCP_GROUP                              => $::imscpConfig{'IMSCP_GROUP'},
        MTA_VIRTUAL_MAIL_DIR                     => $self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'},
        MTA_MAILBOX_UID_NAME                     => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'},
        MTA_MAILBOX_GID_NAME                     => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
        MTA_MAILBOX_UID                          => scalar getpwnam( $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'} ),
        MTA_MAILBOX_GID                          => scalar getgrnam( $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'} ),
        NETWORK_PROTOCOLS                        => ::setupGetQuestion( 'IPV6_SUPPORT' ) ? '*, [::]' : '*',
        POSTFIX_SENDMAIL_PATH                    => $self->{'mta'}->{'config'}->{'POSTFIX_SENDMAIL_PATH'},
        DOVECOT_CONF_DIR                         => $self->{'config'}->{'DOVECOT_CONF_DIR'},
        DOVECOT_DELIVER_PATH                     => $self->{'config'}->{'DOVECOT_DELIVER_PATH'},
        DOVECOT_LDA_AUTH_SOCKET_PATH             => $self->{'config'}->{'DOVECOT_LDA_AUTH_SOCKET_PATH'},
        DOVECOT_SASL_AUTH_SOCKET_PATH            => $self->{'config'}->{'DOVECOT_SASL_AUTH_SOCKET_PATH'},
        DOVECOT_DEFAULT_CLIENT_LIMIT             => $self->{'config'}->{'DOVECOT_DEFAULT_CLIENT_LIMIT'},
        DOVECOT_IMAP_LOGIN_PROCESS_LIMIT         => $self->{'config'}->{'DOVECOT_IMAP_LOGIN_PROCESS_LIMIT'},
        DOVECOT_IMAP_LOGIN_PROCESS_MIN_AVAIL     => $self->{'config'}->{'DOVECOT_IMAP_LOGIN_PROCESS_MIN_AVAIL'},
        DOVECOT_IMAP_LOGIN_PROCESS_SERVICE_COUNT => $self->{'config'}->{'DOVECOT_IMAP_LOGIN_PROCESS_SERVICE_COUNT'},
        DOVECOT_IMAP_PROCESS_LIMIT               => $self->{'config'}->{'DOVECOT_IMAP_PROCESS_LIMIT'},
        DOVECOT_IMAP_PROCESS_MIN_AVAIL           => $self->{'config'}->{'DOVECOT_IMAP_PROCESS_MIN_AVAIL'},
        DOVECOT_IMAP_PROCESS_SERVICE_COUNT       => $self->{'config'}->{'DOVECOT_IMAP_PROCESS_SERVICE_COUNT'},
        DOVECOT_IMAP_VSZ_LIMIT                   => $self->{'config'}->{'DOVECOT_IMAP_VSZ_LIMIT'},
        DOVECOT_POP3_LOGIN_PROCESS_LIMIT         => $self->{'config'}->{'DOVECOT_POP3_LOGIN_PROCESS_LIMIT'},
        DOVECOT_POP3_LOGIN_PROCESS_MIN_AVAIL     => $self->{'config'}->{'DOVECOT_POP3_LOGIN_PROCESS_MIN_AVAIL'},
        DOVECOT_POP3_LOGIN_PROCESS_SERVICE_COUNT => $self->{'config'}->{'DOVECOT_POP3_LOGIN_PROCESS_SERVICE_COUNT'},
        DOVECOT_POP3_PROCESS_LIMIT               => $self->{'config'}->{'DOVECOT_POP3_PROCESS_LIMIT'},
        DOVECOT_POP3_PROCESS_MIN_AVAIL           => $self->{'config'}->{'DOVECOT_POP3_PROCESS_MIN_AVAIL'},
        DOVECOT_POP3_PROCESS_SERVICE_COUNT       => $self->{'config'}->{'DOVECOT_POP3_PROCESS_SERVICE_COUNT'},
        DOVECOT_MAX_USERIP_CONNECTIONS           => $self->{'config'}->{'DOVECOT_MAX_USERIP_CONNECTIONS'},
        ENGINE_ROOT_DIR                          => $::imscpConfig{'ENGINE_ROOT_DIR'},
        POSTFIX_USER                             => $self->{'mta'}->{'config'}->{'POSTFIX_USER'},
        POSTFIX_GROUP                            => $self->{'mta'}->{'config'}->{'POSTFIX_GROUP'}
    };

    # Transitional code (should be removed in later version)
    if ( -f "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-dict-sql.conf" ) {
        iMSCP::File->new(
            filename => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-dict-sql.conf"
        )->delFile();
    }

    my %cfgFiles = (
        'dovecot.conf'             => [
            "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot.conf",
            $::imscpConfig{'ROOT_USER'},
            $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            0640
        ],
        'dovecot-sql.conf'         => [
            "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf",
            $::imscpConfig{'ROOT_USER'},
            $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            0640
        ],
        'dovecot-managesieve.conf' => [
            "$self->{'config'}->{'DOVECOT_CONF_DIR'}/imscp.d/dovecot-managesieve.conf",
            $::imscpConfig{'ROOT_USER'},
            $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            0640
        ],
        'quota-warning'            => [
            "$::imscpConfig{'ENGINE_ROOT_DIR'}/quota/imscp-dovecot-quota.sh",
            $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'},
            $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            0750
        ]
    );

    {
        # dovecot-sql.conf file must not be created/copied world-readable
        local $UMASK = 027;

        for my $conffile ( keys %cfgFiles ) {
            my $rs = $self->{'events'}->trigger(
                'onLoadTemplate', 'dovecot', $conffile, \my $cfgTpl, $data
            );
            return $rs if $rs;

            unless ( defined $cfgTpl ) {
                return 1 unless defined(
                    $cfgTpl = iMSCP::File->new(
                        filename => "$self->{'cfgDir'}/$conffile"
                    )->get()
                );
            }

            if ( $conffile eq 'dovecot.conf' ) {
                my $ssl = ::setupGetQuestion( 'SERVICES_SSL_ENABLED' );
                $cfgTpl .= "\nssl = $ssl\n";

                # Fixme: Find a better way to guess libssl version
                if ( $ssl eq 'yes' ) {
                    if ( -e '/usr/lib/dovecot/libdcrypt_openssl.so' ) {
                      unless ( `ldd /usr/lib/dovecot/libdcrypt_openssl.so | grep libssl.so` =~ /libssl.so.(\d.\d)/ ) {
                          error( "Couldn't guess libssl version against which Dovecot has been built" );
                          return 1;
                      }
                    } else {
                      unless ( `ldd /usr/lib/dovecot/libdovecot-login.so | grep libssl.so` =~ /libssl.so.(\d.\d)/ ) {
                          error( "Couldn't guess libssl version against which Dovecot has been built" );
                          return 1;
                      }
                    }

                    $cfgTpl .= <<"EOF";
ssl_protocols = @{[ version->parse( $1 ) >= version->parse( '1.1' ) ? '!SSLv3' : '!SSLv2 !SSLv3' ]}
ssl_cert = <$::imscpConfig{'CONF_DIR'}/imscp_services.pem
ssl_key = <$::imscpConfig{'CONF_DIR'}/imscp_services.pem
EOF
                }
            }

            $rs = $self->{'events'}->trigger(
                'beforePoBuildConf', \$cfgTpl, $conffile
            );
            return $rs if $rs;

            $cfgTpl = process( $data, $cfgTpl );

            $rs = $self->{'events'}->trigger(
                'afterPoBuildConf', \$cfgTpl, $conffile
            );
            return $rs if $rs;

            my $filename = fileparse( $cfgFiles{$conffile}->[0] );
            my $file = iMSCP::File->new(
                filename => "$self->{'wrkDir'}/$filename"
            );
            $file->set( $cfgTpl );

            $rs = $file->save();
            $rs ||= $file->owner(
                $cfgFiles{$conffile}->[1], $cfgFiles{$conffile}->[2]
            );
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
    my ( $self ) = @_;

    return 0 unless $::imscpOldConfig{'PO_SERVER'} eq 'courier';

    my $rs = $self->{'events'}->trigger( 'beforePoMigrateFromCourier' );
    return $rs if $rs;

    $rs = execute(
        [
            'perl', "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlVendor/courier-dovecot-migrate.pl",
            '--to-dovecot', '--quiet', '--convert', '--overwrite',
            '--recursive', $self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}
        ],
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    error( $stderr || 'Error while migrating from Courier to Dovecot' ) if $rs;

    unless ( $rs ) {
        $self->{'po'}->{'forceMailboxesQuotaRecalc'} = TRUE;
        $::imscpOldConfig{'PO_SERVER'} = 'dovecot';
        $::imscpOldConfig{'PO_PACKAGE'} = 'Servers::po::dovecot';
    }

    $rs ||= $self->{'events'}->trigger( 'afterPoMigrateFromCourier' );
}

=item _oldEngineCompatibility( )

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger(
        'beforePoOldEngineCompatibility'
    );
    return $rs if $rs;

    if ( -f "$self->{'cfgDir'}/dovecot.old.data" ) {
        $rs = iMSCP::File->new(
            filename => "$self->{'cfgDir'}/dovecot.old.data"
        )->delFile();
        return $rs if $rs;
    }

    $self->{'events'}->trigger( 'afterPodOldEngineCompatibility' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
