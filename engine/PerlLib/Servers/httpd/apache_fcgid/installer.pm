=head1 NAME

 Servers::httpd::apache_fcgid::installer - i-MSCP Apache2/FastCGI Server implementation

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

package Servers::httpd::apache_fcgid::installer;

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
use Servers::httpd::apache_fcgid;
use Servers::sqld;
use Try::Tiny;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Apache2/FastCGI Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%em )

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

    if ( $::reconfigure =~ /^(?:httpd|php|servers|all|forced)$/ || $confLevel !~ /^per_(?:site|domain|user)$/ ) {
        $confLevel =~ s/_/ /;
        ( my $rs, $confLevel ) = $dialog->radiolist(
            <<"EOF", [ 'per_site', 'per_domain', 'per_user' ], $confLevel =~ /^per (?:user|domain)$/ ? $confLevel : 'per site' );

\\Z4\\Zb\\ZuPHP configuration level\\Zn

Please choose the PHP configuration level you want use. Available levels are:

\\Z4Per domain:\\Zn One php.ini file per domain (including subdomains)
\\Z4Per user:\\Zn One php.ini file per user
\\Z4Per site:\\Zn One php.ini file per domain
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
    $rs ||= $self->_buildFastCgiConfFiles();
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

 Return Servers::httpd::apache_fcgid::installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'httpd'} = Servers::httpd::apache_fcgid->getInstance();
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

=item _setApacheVersion( )

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
        error( "Couldn't' guess Apache version" );
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

        # Remove any older fcgi starter directory (prevent possible orphaned file when changing PHP configuration level)
        iMSCP::Dir->new( dirname => $self->{'phpConfig'}->{'PHP_FCGI_STARTER_DIR'} )->remove();

        for my $dir (
            [ $self->{'config'}->{'HTTPD_LOG_DIR'}, $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ADM_GROUP'}, 0750 ],
            [ $self->{'phpConfig'}->{'PHP_FCGI_STARTER_DIR'}, $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'}, 0555 ]
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

=item _buildFastCgiConfFiles( )

 Build FastCGI configuration files

 Return int 0 on success, other on failure

=cut

sub _buildFastCgiConfFiles
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBuildFastCgiConfFiles' );

    $self->{'httpd'}->setData( {
        SYSTEM_USER_PREFIX   => $::imscpConfig{'SYSTEM_USER_PREFIX'},
        SYSTEM_USER_MIN_UID  => $::imscpConfig{'SYSTEM_USER_MIN_UID'},
        PHP_FCGI_STARTER_DIR => $self->{'phpConfig'}->{'PHP_FCGI_STARTER_DIR'}
    } );
    $rs = $self->{'httpd'}->buildConfFile( "$self->{'phpCfgDir'}/fcgi/apache_fcgid_module.conf", {}, {
        destination => "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/fcgid_imscp.conf"
    } );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/fcgid.load" );
    my $cfgTpl = $file->getAsRef();
    return 1 unless defined $cfgTpl;

    $file = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/fcgid_imscp.load" );
    ${ $cfgTpl } = "<IfModule !mod_fcgid.c>\n" . ${ $cfgTpl } . "</IfModule>\n";
    $rs = $file->save();
    $rs ||= $file->owner( $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0644 );
    $rs = $self->{'httpd'}->disableModules(
        'actions', 'fastcgi', 'fcgid', 'fcgid_imscp', 'php5', 'php5_cgi', 'php5filter', 'php5.6', 'php7.0', 'php7.1', 'proxy_fcgi', 'proxy_handler',
        'mpm_itk', 'mpm_event', 'mpm_prefork', 'mpm_worker'
    );
    $rs ||= $self->{'httpd'}->enableModules( 'actions', 'authz_groupfile', 'fcgid_imscp', 'mpm_event', 'version' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdBuildFastCgiConfFiles' );
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
        $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'apache_fcgid', 'ports.conf', \my $cfgTpl, {} );
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
        HTTPD_CUSTOM_SITES_DIR => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'},
        HTTPD_LOG_DIR          => $self->{'config'}->{'HTTPD_LOG_DIR'},
        HTTPD_ROOT_DIR         => $self->{'config'}->{'HTTPD_ROOT_DIR'},
        VLOGGER_CONF           => "$self->{'apacheCfgDir'}/vlogger.conf"
    } );
    $rs ||= $self->{'httpd'}->buildConfFile( '00_nameserver.conf' );
    $rs ||= $self->{'httpd'}->buildConfFile( '00_imscp.conf', {}, {
        destination => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available/00_imscp.conf"
    } );
    $rs ||= $self->{'httpd'}->enableModules( 'cgid', 'headers', 'proxy', 'proxy_http', 'rewrite', 'setenvif', 'ssl', 'suexec' );
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
    $rs ||= $self->{'httpd'}->buildConfFile( 'logrotate.conf', {}, { destination => "$::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2" } );
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
        my $host = ::setupGetQuestion( 'DATABASE_HOST' );
        $host = $host eq 'localhost' ? '127.0.0.1' : $host;
        my $port = ::setupGetQuestion( 'DATABASE_PORT' );
        my $dbName = ::setupGetQuestion( 'DATABASE_NAME' );
        my $user = 'vlogger_user';
        my $userHost = ::setupGetQuestion( 'DATABASE_USER_HOST' );
        $userHost = '127.0.0.1' if $userHost eq 'localhost';
        my $oldUserHost = $::imscpOldConfig{'DATABASE_USER_HOST'};
        my $pass = randomStr( 16, ALNUM );

        my $db = iMSCP::Database->factory();
        my $rs = ::setupImportSqlSchema( $db, "$self->{'apacheCfgDir'}/vlogger.sql" );
        return $rs if $rs;

        my $sqld = Servers::sqld->factory();
        for my $oldHost ( $userHost, $oldUserHost, 'localhost' ) {
            next unless length $oldHost;
            $sqld->dropUser( $user, $oldHost );
        }

        $sqld->createUser( $user, $userHost, $pass );

        iMSCP::Database->factory()->getConnector()->run( fixup => sub {
            # No need to escape wildcard characters. See https://bugs.mysql.com/bug.php?id=18660
            $_->do( "GRANT SELECT, INSERT, UPDATE ON @{ [ $_->quote_identifier( $dbName ) ] }.httpd_vlogger TO ?\@?", undef, $user, $userHost );
        } );

        $self->{'httpd'}->setData( {
            DATABASE_NAME     => $dbName,
            DATABASE_HOST     => $host,
            DATABASE_PORT     => $port,
            DATABASE_USER     => $user,
            DATABASE_PASSWORD => $pass
        } );
        $self->{'httpd'}->buildConfFile( "$self->{'apacheCfgDir'}/vlogger.conf.tpl", { SKIP_TEMPLATE_CLEANER => TRUE }, {
            destination => "$self->{'apacheCfgDir'}/vlogger.conf"
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
        ## Cleanup and disable unused PHP Versions/SAPIs
        #

        if ( -f "$::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm" ) {
            $rs = iMSCP::File->new( filename => "$::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm" )->delFile();
            return $rs if $rs;
        }

        iMSCP::Dir->new( dirname => '/etc/php5' )->remove();

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
