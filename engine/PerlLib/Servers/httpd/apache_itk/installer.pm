=head1 NAME

 Servers::httpd::apache_itk::installer - i-MSCP Apache2/ITK Server implementation

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

package Servers::httpd::apache_itk::installer;

use strict;
use warnings;
use File::Basename;
use iMSCP::Boolean;
use iMSCP::Crypt qw/ ALNUM randomStr /;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute 'execute';
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::ProgramFinder;
use iMSCP::Service;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use Servers::httpd::apache_itk;
use Servers::sqld;
use Try::Tiny;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Apache2/ITK Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%em)

 Register setup event listeners

 Param iMSCP::EventManager \%em
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $em ) = @_;

    $em->register( 'beforeSetupDialog', sub {
        push @{ $_[0] }, sub { $self->showDialog( @_ ) };
        0;
    } );
}

=item showDialog( \%dialog )

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub showDialog
{
    my ( $self, $dialog ) = @_;

    my $confLevel = ::setupGetQuestion( 'PHP_CONFIG_LEVEL', $self->{'phpConfig'}->{'PHP_CONFIG_LEVEL'} );

    if ( iMSCP::Getopt->reconfigure =~ /^(?:httpd|php|servers|all|forced)$/ || $confLevel !~ /^per_(?:site|domain|user)$/ ) {
        $confLevel =~ s/_/ /;
        ( my $rs, $confLevel ) = $dialog->radiolist(
            <<"EOF", [ 'per_site', 'per_domain', 'per_user' ], $confLevel =~ /^per (?:user|domain)$/ ? $confLevel : 'per site' );

\\Z4\\Zb\\ZuPHP configuration level\\Zn

Please choose the PHP configuration level you want use. Available levels are:

\\Z4Per domain:\\Zn Changes made through the PHP editor apply to selected domain, including its subdomains
\\Z4Per user:\\Zn Changes made through the PHP Editor apply to all domains
\\Z4Per site:\\Zn Change made through the PHP editor apply to selected domain only
EOF
        return $rs if $rs >= 30;
    }

    ( $self->{'phpConfig'}->{'PHP_CONFIG_LEVEL'} = $confLevel ) =~ s/ /_/;
    0;
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->_setApacheVersion();
    $rs ||= $self->_makeDirs();
    $rs ||= $self->_copyDomainDisablePages();
    $rs ||= $self->_buildPhpConfFiles();
    $rs ||= $self->_buildApacheConfFiles();
    $rs ||= $self->_installLogrotate();
    $rs ||= $self->_setupVlogger();
    $rs ||= $self->_cleanup();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::httpd::apache_itk::installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'httpd'} = Servers::httpd::apache_itk->getInstance();
    $self->{'apacheCfgDir'} = $self->{'httpd'}->{'apacheCfgDir'};
    $self->{'config'} = $self->{'httpd'}->{'config'};
    $self->{'phpCfgDir'} = $self->{'httpd'}->{'phpCfgDir'};
    $self->{'phpConfig'} = $self->{'httpd'}->{'phpConfig'};
    $self->_guessSystemPhpVariables();
    $self;
}

=item _guessSystemPhpVariables( )

 Guess system PHP Variables

 Return int 0 on success, die on failure

=cut

sub _guessSystemPhpVariables
{
    my ( $self ) = @_;

    my ( $phpVersion ) = `php -nv 2> /dev/null` =~ /^PHP\s+(\d+.\d+)/ or die( "Couldn't guess system PHP version" );

    $self->{'phpConfig'}->{'PHP_VERSION'} = $phpVersion;

    my ( $phpConfDir ) = `php -ni 2> /dev/null | grep '(php.ini) Path'` =~ /([^\s]+)$/ or die(
        "Couldn't guess system PHP configuration directory path"
    );

    my $phpConfBaseDir = dirname( $phpConfDir );
    $self->{'phpConfig'}->{'PHP_CONF_DIR_PATH'} = $phpConfBaseDir;
    $self->{'phpConfig'}->{'PHP_FPM_POOL_DIR_PATH'} = "$phpConfBaseDir/fpm/pool.d";

    unless ( -d $self->{'phpConfig'}->{'PHP_FPM_POOL_DIR_PATH'} ) {
        $self->{'phpConfig'}->{'PHP_FPM_POOL_DIR_PATH'} = '';
        die( sprintf( "Couldn't guess '%s' PHP configuration parameter value: directory doesn't exists.", 'PHP_FPM_POOL_DIR_PATH' ));
    }

    $self->{'phpConfig'}->{'PHP_CLI_BIN_PATH'} = iMSCP::ProgramFinder::find( "php$phpVersion" );
    $self->{'phpConfig'}->{'PHP_FCGI_BIN_PATH'} = iMSCP::ProgramFinder::find( "php-cgi$phpVersion" );
    $self->{'phpConfig'}->{'PHP_FPM_BIN_PATH'} = iMSCP::ProgramFinder::find( "php-fpm$phpVersion" );

    for my $param ( qw/ PHP_CLI_BIN_PATH PHP_FCGI_BIN_PATH PHP_FPM_BIN_PATH / ) {
        next if $self->{'phpConfig'}->{$param};
        die( sprintf( "Couldn't guess '%s' PHP configuration parameter value.", $param ));
    }

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

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdMakeDirs' );
        return $rs if $rs;

        for my $dir (
            [ $self->{'config'}->{'HTTPD_LOG_DIR'}, $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ADM_GROUP'}, 0750 ],
            [
                "$self->{'config'}->{'HTTPD_LOG_DIR'}/" . ::setupGetQuestion( 'BASE_SERVER_VHOST' ),
                $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'}, 0750
            ]
        ) {
            iMSCP::Dir->new( dirname => $dir->[0] )->make( {
                user  => $dir->[1],
                group => $dir->[2],
                mode  => $dir->[3]
            } );
        }

        $self->{'eventManager'}->trigger( 'afterHttpdMakeDirs' );
    } catch {
        error( $_ );
        1;
    };
}

=item _copyDomainDisablePages( )

 Copy pages for disabled domains

 Return int 0 on success, other on failure

=cut

sub _copyDomainDisablePages
{
    try {
        iMSCP::Dir->new( dirname => "$::imscpConfig{'CONF_DIR'}/skel/domain_disabled_pages" )->rcopy(
            "$::imscpConfig{'USER_WEB_DIR'}/domain_disabled_pages", { preserve => 'no' }
        );
    } catch {
        error( $_ );
        1;
    };
}

=item _buildPhpConfFiles( )

 Build PHP configuration files

 Return int 0 on success, other on failure

=cut

sub _buildPhpConfFiles
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBuildPhpConfFiles' );
    return $rs if $rs;

    $self->{'httpd'}->setData( {
        PEAR_DIR                    => $self->{'phpConfig'}->{'PHP_PEAR_DIR'},
        TIMEZONE                    => ::setupGetQuestion( 'TIMEZONE' ),
        PHP_OPCODE_CACHE_ENABLED    => $self->{'phpConfig'}->{'PHP_OPCODE_CACHE_ENABLED'},
        PHP_OPCODE_CACHE_MAX_MEMORY => $self->{'phpConfig'}->{'PHP_OPCODE_CACHE_MAX_MEMORY'}
    } );
    $rs = $self->{'httpd'}->buildConfFile( "$self->{'phpCfgDir'}/apache/php.ini", {}, {
        destination => "$self->{'phpConfig'}->{'PHP_CONF_DIR_PATH'}/apache2/php.ini"
    } );
    $rs = $self->{'httpd'}->disableModules(
        'actions', 'fastcgi', 'fcgid', 'fcgid_imscp', 'suexec', 'php5', 'php5_cgi', 'php5filter', 'php5.6', 'php7.0', 'php7.1', 'proxy_fcgi',
        'proxy_handler', 'mpm_itk', 'mpm_event', 'mpm_prefork', 'mpm_worker'
    );
    $rs ||= $self->{'httpd'}->enableModules( 'authz_groupfile', "php$self->{'phpConfig'}->{'PHP_VERSION'}", 'mpm_itk', 'version' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdBuildPhpConfFiles' );
}

=item _buildApacheConfFiles( )

 Build Apache configuration files

 Return int 0 on success, other on failure

=cut

sub _buildApacheConfFiles
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBuildApacheConfFiles' );
    return $rs if $rs;

    if ( -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf" ) {
        $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'apache_itk', 'ports.conf', \my $cfgTpl, {} );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $cfgTpl = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf" )->get();
            return 1 unless defined $cfgTpl;
        }

        $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBuildConfFile', \$cfgTpl, 'ports.conf' );
        return $rs if $rs;

        $cfgTpl =~ s/^NameVirtualHost[^\n]+\n//gim;

        $rs = $self->{'eventManager'}->trigger( 'afterHttpdBuildConfFile', \$cfgTpl, 'ports.conf' );
        return $rs if $rs;

        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf" );
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
        $rs = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_LOG_DIR'}/other_vhosts_access.log" )->delFile();
        return $rs if $rs;
    }

    $self->{'httpd'}->setData( {
        ENGINE_ROOT_DIR        => $::imscpConfig{'ENGINE_ROOT_DIR'},
        HTTPD_CUSTOM_SITES_DIR => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'},
        HTTPD_LOG_DIR          => $self->{'config'}->{'HTTPD_LOG_DIR'},
        HTTPD_ROOT_DIR         => $self->{'config'}->{'HTTPD_ROOT_DIR'},
        VLOGGER_CONF           => "$self->{'apacheCfgDir'}/imscp-vlogger.conf"
    } );
    $rs ||= $self->{'httpd'}->buildConfFile( '00_nameserver.conf', );
    $rs ||= $self->{'httpd'}->buildConfFile( '00_imscp.conf', {}, {
        destination => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available/00_imscp.conf"
    } );
    $rs ||= $self->{'httpd'}->enableModules( 'cgid', 'headers', 'proxy', 'proxy_http', 'rewrite', 'setenvif', 'ssl' );
    $rs ||= $self->{'httpd'}->enableSites( '00_nameserver.conf' );
    $rs ||= $self->{'httpd'}->enableConfs( '00_imscp.conf' );
    $rs ||= $self->{'httpd'}->disableConfs(
        'php5.6-cgi.conf', 'php7.0-cgi.conf', 'php7.1-cgi.conf', 'php5.6-fpm.conf', 'php7.0-fpm.conf', 'php7.1-fpm.conf', 'serve-cgi-bin.conf'
    );
    $rs ||= $self->{'httpd'}->disableSites( 'default', 'default-ssl', '000-default.conf', 'default-ssl.conf' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdBuildApacheConfFiles' );
}

=item _installLogrotate( )

 Install Apache logrotate file

 Return int 0 on success, other on failure

=cut

sub _installLogrotate
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdInstallLogrotate', 'apache2' );
    $self->{'httpd'}->setData( {
        ROOT_USER     => $::imscpConfig{'ROOT_USER'},
        ADM_GROUP     => $::imscpConfig{'ADM_GROUP'},
        HTTPD_LOG_DIR => $self->{'config'}->{'HTTPD_LOG_DIR'},
        PHP_VERSION   => $self->{'CONFIG'}->{'PHP_VERSION'}
    } );
    $rs ||= $self->{'httpd'}->buildConfFile( 'logrotate.conf', {}, {
        destination => "$::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2"
    } );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdInstallLogrotate', 'apache2' );
}

=item _setupVlogger( )

 Setup vlogger

 Return int 0 on success, other on failure

=cut

sub _setupVlogger
{
    my ( $self ) = @_;

    try {
        my $dbHost = ::setupGetQuestion( 'DATABASE_HOST' );
        my $dbPort = ::setupGetQuestion( 'DATABASE_PORT' );
        my $dbName = ::setupGetQuestion( 'DATABASE_NAME' );
        my $dbUser = 'vlogger_user';
        my $dbUserHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
        my $dbPass = randomStr( 16, ALNUM );

        # Because imscp-vlogger script call CHROOT(2), it cannot access MySQL server through UDS
        # Thus, we need force connection through TCP
        $dbHost = '127.0.0.1' if $dbHost eq 'localhost';
        $dbUserHost = '127.0.0.1' if $dbUserHost eq 'localhost';

        my $rs = ::setupImportSqlSchema( iMSCP::Database->factory(), "$self->{'apacheCfgDir'}/imscp-vlogger.sql" );
        return $rs if $rs;

        if ( length $::imscpOldConfig{'DATABASE_USER_HOST'} && $dbUserHost ne $::imscpOldConfig{'DATABASE_USER_HOST'} ) {
            Servers::sqld->factory()->dropUser( $dbUser, $::imscpOldConfig{'DATABASE_USER_HOST'} );
        }

        Servers::sqld->factory()->createUser( $dbUser, $dbUserHost, $dbPass );

        iMSCP::Database->factory()->getConnector()->run( fixup => sub {
            # No need to escape wildcard characters. See https://bugs.mysql.com/bug.php?id=18660
            $_->do(
                "GRANT SELECT, INSERT, UPDATE ON @{ [ $_->quote_identifier( $dbName ) ] }.httpd_vlogger TO ?\@?", undef, $dbUser, $dbUserHost
            );
        } );

        $self->{'httpd'}->setData( {
            DATABASE_HOST     => $dbHost,
            DATABASE_PORT     => $dbPort,
            DATABASE_NAME     => $dbName,
            DATABASE_USER     => $dbUser,
            DATABASE_PASSWORD => $dbPass
        } );
        $self->{'httpd'}->buildConfFile( "$self->{'apacheCfgDir'}/imscp-vlogger.conf.tpl", { SKIP_TEMPLATE_CLEANER => TRUE }, {
            destination => "$self->{'apacheCfgDir'}/imscp-vlogger.conf"
        } );
    } catch {
        error( $_ );
        1;
    };
}

=item _cleanup( )

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ( $self ) = @_;

    try {
        my $rs ||= $self->{'httpd'}->disableSites( 'imscp.conf', '00_modcband.conf', '00_master.conf', '00_master_ssl.conf' );
        return $rs if $rs;

        if ( -f "$self->{'apacheCfgDir'}/apache.old.data" ) {
            $rs = iMSCP::File->new( filename => "$self->{'apacheCfgDir'}/apache.old.data" )->delFile();
            return $rs if $rs;
        }

        if ( -f "$self->{'phpCfgDir'}/php.old.data" ) {
            $rs = iMSCP::File->new( filename => "$self->{'phpCfgDir'}/php.old.data" )->delFile();
            return $rs if $rs;
        }

        for my $file ( 'imscp.conf', '00_modcband.conf', '00_master.conf', '00_master_ssl.conf' ) {
            next unless -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$file";
            $rs = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$file" )->delFile();
            return $rs if $rs;
        }

        $rs = $self->{'httpd'}->disableModules( 'php_fpm_imscp', 'fastcgi_imscp' );
        return $rs if $rs;

        for my $file ( 'fastcgi_imscp.conf', 'fastcgi_imscp.load', 'php_fpm_imscp.conf', 'php_fpm_imscp.load' ) {
            next unless -f "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$file";
            $rs = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$file" )->delFile();
            return $rs if $rs;
        }

        if ( -d $self->{'phpConfig'}->{'PHP_FCGI_STARTER_DIR'} ) {
            $rs = execute( "rm -f $self->{'phpConfig'}->{'PHP_FCGI_STARTER_DIR'}/*/php5-fastcgi-starter", \my $stdout, \my $stderr );
            debug( $stdout ) if $stdout;
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;
        }

        for my $dir ( '/var/log/apache2/backup', '/var/log/apache2/users', '/var/www/scoreboards' ) {
            iMSCP::Dir->new( dirname => $dir )->remove();
        }

        # Remove customer's logs file if any (no longer needed since we are now use bind mount)
        $rs = execute( "rm -f $::imscpConfig{'USER_WEB_DIR'}/*/logs/*.log", \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;

        #
        ## Cleanup and disable unused PHP versions/SAPIs
        #

        if ( -f "$::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm" ) {
            $rs = iMSCP::File->new( filename => "$::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm" )->delFile();
            return $rs if $rs;
        }

        iMSCP::Dir->new( dirname => '/etc/php5' )->remove();
        # CGI
        iMSCP::Dir->new( dirname => $self->{'phpConfig'}->{'PHP_FCGI_STARTER_DIR'} )->remove();

        # FPM
        unlink grep !/www\.conf$/, glob "$self->{'phpConfig'}->{'PHP_FPM_POOL_DIR_PATH'}/*.conf";

        my $serviceMngr = iMSCP::Service->getInstance();
        $serviceMngr->stop( sprintf( 'php%s-fpm', $self->{'phpConfig'}->{'PHP_VERSION'} ));
        $serviceMngr->disable( sprintf( 'php%s-fpm', $self->{'phpConfig'}->{'PHP_VERSION'} ));
        0;
    } catch {
        error( $_ );
        1;
    };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
