=head1 NAME

 Servers::httpd::apache_php_fpm::installer - i-MSCP Apache2/PHP-FPM Server implementation

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

package Servers::httpd::apache_php_fpm::installer;

use strict;
use warnings;
use File::Basename 'dirname';
use iMSCP::Boolean;
use iMSCP::Crypt qw/ ALPHA64 decryptRijndaelCBC encryptRijndaelCBC randomStr /;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::EventManager;
use iMSCP::Execute qw/ execute escapeShell /;
use iMSCP::Getopt;
use iMSCP::ProgramFinder;
use iMSCP::Service;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use Module::Load::Conditional 'check_install';
use Servers::httpd::apache_php_fpm;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Apache2/PHP-FPM Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%events )

 Register setup event listeners

 Param iMSCP::events \%events
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $events ) = @_;

    $events->registerOne( 'beforeSetupDialog', sub {
        push @{ $_[0] },
            sub { $self->_dialogForPhpConfLevel( @_ ); },
            sub { $self->_dialogForPhpListenMode( @_ ); };
        0;
    } );
}

=item install( )

 Pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    local $@;
    eval { $self->_setPhpVariables(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    for my $confVar ( qw/ PHP_CONFIG_LEVEL PHP_FPM_LISTEN_MODE / ) {
        $self->{'phpConfig'}->{$confVar} = ::setupGetQuestion( $confVar );
    }

    #$self->{'httpd'}->stop();
    0;
}

=item install( )

 Installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->_setApacheVersion();
    $rs ||= $self->_makeDirs();
    $rs ||= $self->_copyDomainDisablePages;
    $rs ||= $self->_buildFastCgiConfFiles();
    $rs ||= $self->_buildPhpConfFiles();
    $rs ||= $self->_buildApacheConfFiles();
    $rs ||= $self->_installLogrotate();
    $rs ||= $self->_setupVlogger();
    $rs ||= $self->_cleanup();
}

=item postinstall( )

 Uninstallation tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    local $@;
    eval {
        my $service = iMSCP::Service->getInstance();
        $service->enable( sprintf(
            'php%s-fpm', $self->{'phpConfig'}->{'PHP_VERSION'}
        ));
        $service->enable( $self->{'config'}->{'HTTPD_SNAME'} );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{ $_[0] }, [
                sub {
                    # Set forceRestart flag, else server will be reloaded only
                    $self->{'httpd'}->forceRestart();
                    $self->{'httpd'}->restart();
                },
                'Httpd (Apache2/php-fpm)'
            ];
            0;
        },
        3
    );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::httpd::apache_php_fpm::installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'events'} = iMSCP::EventManager->getInstance();
    $self->{'httpd'} = Servers::httpd::apache_php_fpm->getInstance();
    $self->{'apacheCfgDir'} = $self->{'httpd'}->{'apacheCfgDir'};
    $self->{'config'} = $self->{'httpd'}->{'config'};
    $self->{'phpCfgDir'} = $self->{'httpd'}->{'phpCfgDir'};
    $self->{'phpConfig'} = $self->{'httpd'}->{'phpConfig'};
    $self;
}

=item _dialogForPhpConfLevel( \%dialog )

 Dialog for PHP configuration level

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForPhpConfLevel
{
    my ( $self, $dialog ) = @_;

    my $value = ::setupGetQuestion(
        'PHP_CONFIG_LEVEL',
        length $self->{'phpConfig'}->{'PHP_CONFIG_LEVEL'}
            ? $self->{'phpConfig'}->{'PHP_CONFIG_LEVEL'}
            : 'per_site'
    );

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ php servers all / )
        && grep ( $value eq $_, qw/ per_site per_domain per_user / )
    ) {
        ::setupSetQuestion( 'PHP_CONFIG_LEVEL', $value );
        return 20;
    }

    my %choices = (
        'Per site'   => 'per_site',
        'Per domain' => 'per_domain',
        'Per user'   => 'per_user'
    );

    ( my $ret, $value ) = $dialog->select(
        <<"EOF", \%choices, ( grep ( $value eq $_, qw/ per_site per_domain per_user /) )[0] // 'per_site' );
Please choose the PHP configuration level you want use. Available levels are:

\\Z4Per domain:\\Zn One pool configuration file per domain (including subdomains)
\\Z4Per user:\\Zn One pool configuration file per user
\\Z4Per site:\\Zn One pool configuration per domain

If you make use of the PhpSwitcher plugin, you \\ZbMUST\\ZB choose the 'per site' option.
EOF
    return 30 if $ret == 30;

    ::setupSetQuestion( 'PHP_CONFIG_LEVEL', $value );
    0;
}

=item _dialogForPhpListenMode( )

 Dialog for PHP listen mode

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForPhpListenMode
{
    my ( $self, $dialog ) = @_;

    my $value = ::setupGetQuestion(
        'PHP_FPM_LISTEN_MODE',
        length $self->{'phpConfig'}->{'PHP_FPM_LISTEN_MODE'}
            ? $self->{'phpConfig'}->{'PHP_FPM_LISTEN_MODE'}
            : 'uds'
    );

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ php servers all / )
        && grep ( $value eq $_, qw/ tcp uds /)
    ) {
        ::setupSetQuestion( 'PHP_FPM_LISTEN_MODE', $value );
        return 20;
    }

    my %choices = (
        'TCP/IP socket'            => 'tcp',
        'UDS (Unix Domain Socket)' => 'uds'
    );

    ( my $ret, $value ) = $dialog->select(
        <<"EOF", \%choices, ( grep ( $value eq $_ ) )[0] // 'uds' );
Please choose the FastCGI address type that you want use. Available types are:

\\Z4tcp:\\Zn TCP/IP (e.g. 127.0.0.1:9000)
\\Z4uds:\\Zn Unix domain socket (e.g. /run/php/php<version>-fpm-domain.tld.sock)

The UDS choice is highly recommended. For high traffic sites, TCP/IP can require a tweaking of your kernel parameters (sysctl).
EOF
    return 30 if $ret == 30;

    ::setupSetQuestion( 'PHP_FPM_LISTEN_MODE', $value );
    0;
}

=item _setPhpVariables

 Set PHP Variables

 Return int 0 on success, die on failure

=cut

sub _setPhpVariables
{
    my ( $self ) = @_;

    ( $self->{'phpConfig'}->{'PHP_VERSION'} ) = ::setupGetQuestion( 'PHP_SERVER' )
        =~ /(\d+.\d+)$/ or die( "Couldn't guess system PHP version" );

    $self->{'phpConfig'}->{'PHP_CLI_BIN_PATH'} = iMSCP::ProgramFinder::find(
        "php$self->{'phpConfig'}->{'PHP_VERSION'}"
    ) or die( "Couldn't find system php (cli) binary" );

    my ( $phpCliConfDir ) = `$self->{'phpConfig'}->{'PHP_CLI_BIN_PATH'} -ni 2> /dev/null | grep '(php.ini) Path'`
        =~ /([^\s]+)$/ or die( "Couldn't guess system PHP configuration root directory" );

    $self->{'phpConfig'}->{'PHP_CONF_DIR_PATH'} = dirname( $phpCliConfDir );

    if ( -d "$self->{'phpConfig'}->{'PHP_CONF_DIR_PATH'}/fpm/pool.d" ) {
        $self->{'phpConfig'}->{'PHP_FPM_POOL_DIR_PATH'} = "$self->{'phpConfig'}->{'PHP_CONF_DIR_PATH'}/fpm/pool.d";
    } else {
        die( sprintf(
            "Couldn't guess php (fpm) pool configuration directory: Directory %s doesn't exist.",
            "$self->{'phpConfig'}->{'PHP_CONF_DIR_PATH'}/fpm/pool.d"
        ));
    }

    $self->{'phpConfig'}->{'PHP_FCGI_BIN_PATH'} = iMSCP::ProgramFinder::find(
        "php-cgi$self->{'phpConfig'}->{'PHP_VERSION'}"
    ) or die( "Couldn't find system php (cgi-fcgi) binary" );

    $self->{'phpConfig'}->{'PHP_FPM_BIN_PATH'} = iMSCP::ProgramFinder::find(
        "php-fpm$self->{'phpConfig'}->{'PHP_VERSION'}"
    ) or die( "Couldn't find system php (fpm-fcgi) binary" );

    0;
}

=item _setApacheVersion

 Set Apache version

 Return int 0 on success, other on failure

=cut

sub _setApacheVersion
{
    my ( $self ) = @_;

    my $rs = execute( 'apache2ctl -v', \my $stdout, \my $stderr );
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    if ( $stdout !~ m%Apache/([\d.]+)% ) {
        error( "Couldn't guess Apache version" );
        return 1;
    }

    $self->{'config'}->{'HTTPD_VERSION'} = $1;
    debug( sprintf( 'Apache version set to: %s', $1 ));
    0;
}

=item _makeDirs( )

 Create directories

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeHttpdMakeDirs' );
    return $rs if $rs;

    local $@;
    eval {
        iMSCP::Dir->new(
            dirname => $self->{'config'}->{'HTTPD_LOG_DIR'}
        )->make( {
            user  => $::imscpConfig{'ROOT_USER'},
            group => $::imscpConfig{'ADM_GROUP'},
            mode  => 0750
        } );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # Cleanup pools directory (prevent possible orphaned pool file when
    # changing PHP configuration level)
    unlink grep !/www\.conf$/, glob "$self->{'phpConfig'}->{'PHP_FPM_POOL_DIR_PATH'}/*.conf";
    $self->{'events'}->trigger( 'afterHttpdMakeDirs' );
}

=item _copyDomainDisablePages( )

 Copy pages for disabled domains

 Return int 0 on success, other on failure

=cut

sub _copyDomainDisablePages
{
    local $@;
    eval {
        iMSCP::Dir->new(
            dirname => "$::imscpConfig{'CONF_DIR'}/skel/domain_disabled_pages"
        )->rcopy(
            "$::imscpConfig{'USER_WEB_DIR'}/domain_disabled_pages",
            { preserve => 'no' }
        );
    };
    if ( $@ ) {
        error( $@ );
        return;
    }

    0;
}

=item _buildFastCgiConfFiles( )

 Build FastCGI configuration files

 Return int 0 on success, other on failure

=cut

sub _buildFastCgiConfFiles
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeHttpdBuildFastCgiConfFiles' );
    return $rs if $rs;

    $self->{'httpd'}->setData( {
        PHP_VERSION => $self->{'phpConfig'}->{'PHP_VERSION'}
    } );

    $rs = $self->{'httpd'}->disableModules(
        'actions', 'fastcgi', 'fcgid', 'fcgid_imscp', 'suexec',
        'php5', 'php5_cgi', 'php5filter',
        'php5.6', 'php7.0', 'php7.1', 'php7.2', 'php7.3', 'php7.4', 'php8.0',
        'proxy_fcgi', 'proxy_handler', 'mpm_itk', 'mpm_event', 'mpm_prefork',
        'mpm_worker'
    );
    $rs ||= $self->{'httpd'}->enableModules(
        'authz_groupfile', 'mpm_event', 'proxy_fcgi', 'suexec', 'version'
    );
    $rs ||= $self->{'events'}->trigger( 'afterHttpdBuildFastCgiConfFiles' );
}

=item _buildPhpConfFiles( )

 Build PHP configuration files

 Return int 0 on success, other on failure

=cut

sub _buildPhpConfFiles
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger(
        'beforeHttpdBuildPhpConfFiles'
    );
    return $rs if $rs;

    $self->{'httpd'}->setData( {
        HTTPD_USER                          => $self->{'config'}->{'HTTPD_USER'},
        HTTPD_GROUP                         => $self->{'config'}->{'HTTPD_GROUP'},
        PEAR_DIR                            => $self->{'phpConfig'}->{'PHP_PEAR_DIR'},
        PHP_CONF_DIR_PATH                   => $self->{'phpConfig'}->{'PHP_CONF_DIR_PATH'},
        PHP_FPM_POOL_DIR_PATH               => $self->{'phpConfig'}->{'PHP_FPM_POOL_DIR_PATH'},
        PHP_FPM_LOG_LEVEL                   => $self->{'phpConfig'}->{'PHP_FPM_LOG_LEVEL'} || 'error',
        PHP_FPM_EMERGENCY_RESTART_THRESHOLD => $self->{'phpConfig'}->{'PHP_FPM_EMERGENCY_RESTART_THRESHOLD'} || 10,
        PHP_FPM_EMERGENCY_RESTART_INTERVAL  => $self->{'phpConfig'}->{'PHP_FPM_EMERGENCY_RESTART_INTERVAL'} || '1m',
        PHP_FPM_PROCESS_CONTROL_TIMEOUT     => $self->{'phpConfig'}->{'PHP_FPM_PROCESS_CONTROL_TIMEOUT'} || '60s',
        PHP_FPM_PROCESS_MAX                 => $self->{'phpConfig'}->{'PHP_FPM_PROCESS_MAX'} // 0,
        PHP_FPM_RLIMIT_FILES                => $self->{'phpConfig'}->{'PHP_FPM_RLIMIT_FILES'} // 4096,
        PHP_VERSION                         => $self->{'phpConfig'}->{'PHP_VERSION'},
        TIMEZONE                            => ::setupGetQuestion( 'TIMEZONE' ),
        PHP_OPCODE_CACHE_ENABLED            => $self->{'phpConfig'}->{'PHP_OPCODE_CACHE_ENABLED'},
        PHP_OPCODE_CACHE_MAX_MEMORY         => $self->{'phpConfig'}->{'PHP_OPCODE_CACHE_MAX_MEMORY'}
    } );

    $rs = $self->{'httpd'}->buildConfFile( "$self->{'phpCfgDir'}/fpm/php.ini", {}, {
        destination => "$self->{'phpConfig'}->{'PHP_CONF_DIR_PATH'}/fpm/php.ini"
    } );
    $rs ||= $self->{'httpd'}->buildConfFile( "$self->{'phpCfgDir'}/fpm/php-fpm.conf", {}, {
        destination => "$self->{'phpConfig'}->{'PHP_CONF_DIR_PATH'}/fpm/php-fpm.conf"
    } );
    $rs ||= $self->{'httpd'}->buildConfFile( "$self->{'phpCfgDir'}/fpm/pool.conf.default", {}, {
        destination => "$self->{'phpConfig'}->{'PHP_FPM_POOL_DIR_PATH'}/www.conf"
    } );
    $rs ||= $self->{'events'}->trigger( 'afterHttpdBuildPhpConfFiles' );
}

=item _buildApacheConfFiles

 Build main Apache configuration files

 Return int 0 on success, other on failure

=cut

sub _buildApacheConfFiles
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeHttpdBuildApacheConfFiles' );
    return $rs if $rs;

    if ( -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf" ) {
        $rs = $self->{'events'}->trigger(
            'onLoadTemplate', 'apache_php_fpm', 'ports.conf', \my $cfgTpl, {}
        );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            return 1 unless defined(
                $cfgTpl = iMSCP::File->new(
                    filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf"
                )->get()
            );
        }

        $rs = $self->{'events'}->trigger(
            'beforeHttpdBuildConfFile', \$cfgTpl, 'ports.conf'
        );
        return $rs if $rs;

        $cfgTpl =~ s/^NameVirtualHost[^\n]+\n//gim;

        $rs = $self->{'events'}->trigger(
            'afterHttpdBuildConfFile', \$cfgTpl, 'ports.conf'
        );
        return $rs if $rs;

        my $file = iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf"
        );
        $file->set( $cfgTpl );

        $rs = $file->save();
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    # Turn off default access log provided by Debian package
    $rs = $self->{'httpd'}->disableConfs( 'other-vhosts-access-log.conf' );
    return $rs if $rs;

    # Remove default access log file provided by Debian package
    if ( -f "$self->{'config'}->{'HTTPD_LOG_DIR'}/other_vhosts_access.log" ) {
        $rs = iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_LOG_DIR'}/other_vhosts_access.log"
        )->delFile();
        return $rs if $rs;
    }

    $self->{'httpd'}->setData( {
        HTTPD_CUSTOM_SITES_DIR => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'},
        HTTPD_LOG_DIR          => $self->{'config'}->{'HTTPD_LOG_DIR'},
        HTTPD_ROOT_DIR         => $self->{'config'}->{'HTTPD_ROOT_DIR'},
        VLOGGER_CONF           => "$self->{'apacheCfgDir'}/vlogger.conf"
    } );

    $rs ||= $self->{'httpd'}->buildConfFile( '00_nameserver.conf' );
    $rs ||= $self->{'httpd'}->buildConfFile( '00_imscp.conf', {}, {
        destination => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available/00_imscp.conf"
    } );
    $rs ||= $self->{'httpd'}->enableModules(
        'cgid', 'headers', 'proxy', 'proxy_http', 'rewrite', 'setenvif', 'ssl'
    );
    $rs ||= $self->{'httpd'}->enableSites( '00_nameserver.conf' );
    $rs ||= $self->{'httpd'}->enableConfs( '00_imscp.conf' );
    $rs ||= $self->{'httpd'}->disableConfs(
        'php5.6-cgi.conf', 'php5.6-fpm.conf',
        'php7.0-cgi.conf', 'php7.0-fpm.conf',
        'php7.1-cgi.conf', 'php7.1-fpm.conf',
        'php7.2-cgi.conf', 'php7.2-fpm.conf',
        'php7.3-cgi.conf', 'php7.3-fpm.conf',
        'php7.4-cgi.conf', 'php7.4-fpm.conf',
        'php8.0-cgi.conf', 'php8.0-fpm.conf',
        'serve-cgi-bin.conf'
    );
    $rs ||= $self->{'httpd'}->disableSites(
        'default', 'default-ssl', '000-default.conf', 'default-ssl.conf'
    );
    $rs ||= $self->{'events'}->trigger( 'afterHttpdBuildApacheConfFiles' );
}

=item _installLogrotate( )

 Install logrotate files

 Return int 0 on success, other on failure

=cut

sub _installLogrotate
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeHttpdInstallLogrotate', 'apache2' );

    $self->{'httpd'}->setData( {
        ROOT_USER     => $::imscpConfig{'ROOT_USER'},
        ADM_GROUP     => $::imscpConfig{'ADM_GROUP'},
        HTTPD_LOG_DIR => $self->{'config'}->{'HTTPD_LOG_DIR'},
        PHP_VERSION   => $self->{'phpConfig'}->{'PHP_VERSION'}
    } );

    $rs ||= $self->{'httpd'}->buildConfFile( 'logrotate.conf', {}, {
        destination => "$::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2"
    } );
    $rs ||= $self->{'events'}->trigger( 'afterHttpdInstallLogrotate', 'apache2' );

    if ( !$rs && version->parse( "$self->{'phpConfig'}->{'PHP_VERSION'}" ) < version->parse( '7.0' ) ) {
        $rs ||= $self->{'events'}->trigger( 'beforeHttpdInstallLogrotate', 'php5-fpm' );
        $rs ||= $self->{'httpd'}->buildConfFile( "$self->{'phpCfgDir'}/fpm/logrotate.tpl", {}, {
            destination => "$::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm"
        } );
        $rs ||= $self->{'events'}->trigger( 'afterHttpdInstallLogrotate', 'php5-fpm' );
    }

    $rs;
}

=item _setupVlogger( )

 Setup vlogger

 Return int 0 on success, other on failure

=cut

sub _setupVlogger
{
    my ( $self ) = @_;

    my $rs = eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        my %config = @{ $dbh->selectcol_arrayref(
            "
                SELECT `name`, `value`
                FROM `config`
                WHERE `name` LIKE 'APACHE_VLOGGER_SQL_%'
            ",
            { Columns => [ 1, 2 ] }
        ) };

        if ( length $config{'APACHE_VLOGGER_SQL_USER'} ) {
            $config{'APACHE_VLOGGER_SQL_USER'} = decryptRijndaelCBC(
                $::imscpDBKey, $::imscpDBiv, $config{'APACHE_VLOGGER_SQL_USER'}
            );
        } else {
            $config{'APACHE_VLOGGER_SQL_USER'} = 'vlogger_' . randomStr(
                8, ALPHA64
            );
        }

        if ( length $config{'APACHE_VLOGGER_SQL_USER_PASSWD'} ) {
            $config{'APACHE_VLOGGER_SQL_USER_PASSWD'} = decryptRijndaelCBC(
                $::imscpDBKey,
                $::imscpDBiv,
                $config{'APACHE_VLOGGER_SQL_USER_PASSWD'}
            );
        } else {
            $config{'APACHE_VLOGGER_SQL_USER_PASSWD'} = randomStr(
                16, ALPHA64
            );
        }

        $dbh->do(
            '
                INSERT INTO `config` (`name`,`value`)
                VALUES (?,?),(?,?)
                ON DUPLICATE KEY UPDATE `name` = `name`
            ',
            undef,
            'APACHE_VLOGGER_SQL_USER',
            encryptRijndaelCBC(
                $::imscpDBKey,
                $::imscpDBiv,
                $config{'APACHE_VLOGGER_SQL_USER'}
            ),
            'APACHE_VLOGGER_SQL_USER_PASSWD',
            encryptRijndaelCBC(
                $::imscpDBKey,
                $::imscpDBiv,
                $config{'APACHE_VLOGGER_SQL_USER_PASSWD'}
            )
        );

        my $rs = execute(
            '/usr/bin/mysql '
                . escapeShell( ::setupGetQuestion( 'DATABASE_NAME' ))
                . ' < ' . escapeShell( "$self->{'apacheCfgDir'}/vlogger.sql" ),
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;

        my $sqlServer = Servers::sqld->factory();

        for my $host (
            $::imscpOldConfig{'DATABASE_USER_HOST'},
            ::setupGetQuestion( 'DATABASE_USER_HOST' ),
        ) {
            next unless length $host;
            for my $user (
                $config{'APACHE_VLOGGER_SQL_USER'},
                'vlogger_user' # Transitional
            ) {
                $sqlServer->dropUser( $user, $host );
            }
        }

        $sqlServer->createUser(
            $config{'APACHE_VLOGGER_SQL_USER'},
            ::setupGetQuestion( 'DATABASE_USER_HOST' ),
            $config{'APACHE_VLOGGER_SQL_USER_PASSWD'},
        );

        $dbh->do(
            "
                GRANT SELECT, INSERT, UPDATE
                ON `@{ [ ::setupGetQuestion( 'DATABASE_NAME' ) ] }`.`httpd_vlogger`
                TO ?\@?
            ",
            undef,
            $config{'APACHE_VLOGGER_SQL_USER'},
            ::setupGetQuestion( 'DATABASE_USER_HOST' )
        );

        $self->{'httpd'}->setData( {
            DSN               => "DBI:" . (
                !!check_install( module => 'DBD::MariaDB', verbose => FALSE )
                    ? 'MariaDB' : 'mysql'
            ) . ":database=@{ [ ::setupGetQuestion( 'DATABASE_NAME' ) ] };"
                . $::imscpConfig{'DATABASE_HOST'}
                . ( $::imscpConfig{'DATABASE_HOST'} ne 'localhost'
                ? ";$::imscpConfig{'DATABASE_PORT'}" : '' ),
            DATABASE_USER     => $config{'APACHE_VLOGGER_SQL_USER'},
            DATABASE_PASSWORD => $config{'APACHE_VLOGGER_SQL_USER_PASSWD'}
        } );
        $self->{'httpd'}->buildConfFile(
            "$self->{'apacheCfgDir'}/vlogger.conf.tpl",
            { SKIP_TEMPLATE_CLEANER => TRUE },
            { destination => "$self->{'apacheCfgDir'}/vlogger.conf" }
        );
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs;
}

=item _cleanup( )

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ( $self ) = @_;

    local $@;
    my $rs = eval {
        my $rs = $self->{'events'}->trigger( 'beforeHttpdCleanup' );
        $rs ||= $self->{'httpd'}->disableSites(
            'imscp.conf',
            '00_modcband.conf',
            '00_master.conf',
            '00_master_ssl.conf'
        );
        return $rs if $rs;

        if ( -f "$self->{'apacheCfgDir'}/apache.old.data" ) {
            $rs = iMSCP::File->new(
                filename => "$self->{'apacheCfgDir'}/apache.old.data"
            )->delFile();
            return $rs if $rs;
        }

        if ( -f "$self->{'phpCfgDir'}/php.old.data" ) {
            $rs = iMSCP::File->new(
                filename => "$self->{'phpCfgDir'}/php.old.data"
            )->delFile();
            return $rs if $rs;
        }

        for my $file (
            'imscp.conf',
            '00_modcband.conf',
            '00_master.conf',
            '00_master_ssl.conf'
        ) {
            next unless -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$file";
            $rs = iMSCP::File->new(
                filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$file"
            )->delFile();
            return $rs if $rs;
        }

        $rs = $self->{'httpd'}->disableModules( 'php_fpm_imscp', 'fastcgi_imscp' );
        return $rs if $rs;

        for my $file (
            'fastcgi_imscp.conf',
            'fastcgi_imscp.load',
            'php_fpm_imscp.conf',
            'php_fpm_imscp.load'
        ) {
            next unless -f "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$file";
            $rs = iMSCP::File->new(
                filename => "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$file"
            )->delFile();
            return $rs if $rs;
        }

        if ( -d $self->{'phpConfig'}->{'PHP_FCGI_STARTER_DIR'} ) {
            $rs = execute(
                "rm -f $self->{'phpConfig'}->{'PHP_FCGI_STARTER_DIR'}/*/php5-fastcgi-starter",
                \my $stdout,
                \my $stderr
            );
            debug( $stdout ) if $stdout;
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;
        }

        for my $dir (
            '/var/log/apache2/backup',
            '/var/log/apache2/users',
            '/var/www/scoreboards'
        ) {
            iMSCP::Dir->new( dirname => $dir )->remove();
        }

        if ( -f "$self->{'phpConfig'}->{'PHP_FPM_POOL_DIR_PATH'}/master.conf" ) {
            $rs = iMSCP::File->new(
                filename => "$self->{'phpConfig'}->{'PHP_FPM_POOL_DIR_PATH'}/master.conf"
            )->delFile();
            return $rs if $rs;
        }

        $rs = execute(
            "rm -f $::imscpConfig{'USER_WEB_DIR'}/*/logs/*.log",
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;

        #
        ## Cleanup and disable unused PHP versions/SAPIs
        #

        if ( -f "$::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm" ) {
            $rs = iMSCP::File->new(
                filename => "$::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm"
            )->delFile();
            return $rs if $rs;
        }

        iMSCP::Dir->new( dirname => '/etc/php5' )->remove();
        iMSCP::Dir->new(
            dirname => $self->{'phpConfig'}->{'PHP_FCGI_STARTER_DIR'}
        )->remove();

        $self->{'events'}->trigger( 'afterHttpdCleanup' );
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
