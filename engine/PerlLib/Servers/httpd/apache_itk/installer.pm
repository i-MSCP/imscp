=head1 NAME

 Servers::httpd::apache_itk::installer - i-MSCP Apache2/ITK Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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
use iMSCP::Config;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::Rights;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::TemplateParser;
use iMSCP::ProgramFinder;
use File::Basename;
use Servers::httpd::apache_itk;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Apache2/ITK Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self, $eventManager) = @_;

    $eventManager->register( 'beforeSetupDialog', sub {
            push @{$_[0]}, sub { $self->showDialog( @_ ) };
            0;
        } );
}

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub showDialog
{
    my ($self, $dialog) = @_;

    my $rs = 0;
    my $confLevel = main::setupGetQuestion( 'INI_LEVEL', $self->{'config'}->{'INI_LEVEL'} );

    if (grep($_ eq $main::reconfigure, ( 'httpd', 'php', 'servers', 'all', 'forced' ))
        || !grep($_ eq $confLevel, ( 'per_site', 'per_domain', 'per_user' ))
    ) {
        $confLevel =~ s/_/ /;

        ($rs, $confLevel) = $dialog->radiolist(
            <<"EOF", [ 'per_site', 'per_domain', 'per_user' ], grep($_ eq $confLevel, ( 'per user', 'per domain' )) ? $confLevel : 'per site' );

\\Z4\\Zb\\ZuPHP configuration level\\Zn

Please, choose the PHP configuration level you want use. Available levels are:

\\Z4Per user:\\Zn Changes made through the PHP Editor apply to all domains
\\Z4Per domain:\\Zn Changes made through the PHP editor apply to selected domain, including its subdomains
\\Z4Per site:\\Zn Change made through PHP the editor apply to selected domain only
EOF
    }

    ($self->{'config'}->{'INI_LEVEL'} = $confLevel) =~ s/ /_/ if $rs < 30;
    $rs;
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    for my $file ("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2",
        "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf") {
        my $rs = $self->_bkpConfFile( $file );
        return $rs if $rs;
    }

    my $rs = $self->_setApacheVersion();
    $rs ||= $self->_makeDirs();
    $rs ||= $self->_buildPhpConfFiles();
    $rs ||= $self->_buildApacheConfFiles();
    $rs ||= $self->_installLogrotate();
    $rs ||= $self->_setupVlogger();
    $rs ||= $self->_saveConf();
    $rs ||= $self->_oldEngineCompatibility();
}

=item setEnginePermissions

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my $self = shift;

    my $rs = setRights( '/usr/local/sbin/vlogger', {
            user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_GROUP'}, mode => '0750' }
    );
    $rs ||= setRights( $self->{'config'}->{'HTTPD_LOG_DIR'}, {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $main::imscpConfig{'ADM_GROUP'},
            dirmode   => '0755',
            filemode  => '0644',
            recursive => 1
        } );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::httpd::apache_itk::installer

=cut

sub _init
{
    my $self = shift;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'httpd'} = Servers::httpd::apache_itk->getInstance();
    $self->{'eventManager'}->trigger( 'beforeHttpdInitInstaller', $self, 'apache_itk' ) and fatal(
        'apache_itk - beforeHttpdInitInstaller has failed'
    );
    $self->{'apacheCfgDir'} = $self->{'httpd'}->{'apacheCfgDir'};
    $self->{'apacheBkpDir'} = "$self->{'apacheCfgDir'}/backup";
    $self->{'apacheWrkDir'} = "$self->{'apacheCfgDir'}/working";
    $self->{'config'} = $self->{'httpd'}->{'config'};

    my $oldConf = "$self->{'apacheCfgDir'}/apache.old.data";
    if (-f $oldConf) {
        tie my %oldConfig, 'iMSCP::Config', fileName => $oldConf;

        for my $param(keys %oldConfig) {
            if (exists $self->{'config'}->{$param}) {
                $self->{'config'}->{$param} = $oldConfig{$param};
            }
        }
    }

    $self->{'eventManager'}->trigger( 'afterHttpdInitInstaller', $self, 'apache_itk' ) and fatal(
        'apache_itk - afterHttpdInitInstaller has failed'
    );
    $self;
}

=item _bkpConfFile($cfgFile)

 Backup the given file

 Param string $cfgFile File to backup
 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
    my ($self, $cfgFile) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBkpConfFile', $cfgFile );
    return $rs if $rs;

    my $timestamp = time;

    if (-f $cfgFile) {
        my $file = iMSCP::File->new( filename => $cfgFile );
        my $filename = fileparse( $cfgFile );

        unless (-f "$self->{'apacheBkpDir'}/$filename.system") {
            $rs = $file->copyFile( "$self->{'apacheBkpDir'}/$filename.system" );
            return $rs if $rs;
        } else {
            $rs = $file->copyFile( "$self->{'apacheBkpDir'}/$filename.$timestamp" );
            return $rs if $rs;
        }
    }

    $self->{'eventManager'}->trigger( 'afterHttpdBkpConfFile', $cfgFile );
}

=item _setApacheVersion

 Set Apache version

 Return int 0 on success, other on failure

=cut

sub _setApacheVersion
{
    my $self = shift;

    my $rs = execute( 'apache2ctl -v', \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    return $rs if $rs;

    if ($stdout !~ m%Apache/([\d.]+)%) {
        error( 'Could not find Apache version from `apache2ctl -v` command output.' );
        return 1;
    }

    $self->{'config'}->{'HTTPD_VERSION'} = $1;
    debug( sprintf( 'Apache version set to: %s', $1 ) );
    0;
}

=item _makeDirs()

 Create directories

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdMakeDirs' );
    return $rs if $rs;

    for my $dir(
        [
            $self->{'config'}->{'HTTPD_LOG_DIR'},
            $main::imscpConfig{'ROOT_USER'},
            $$main::imscpConfig{'ROOT_GROUP'},
            0755
        ],
        [
            "$self->{'config'}->{'HTTPD_LOG_DIR'}/$main::imscpConfig{'BASE_SERVER_VHOST'}",
            $$main::imscpConfig{'ROOT_USER'},
            $$main::imscpConfig{'ROOT_GROUP'},
            0750
        ]
    ) {
        $rs = iMSCP::Dir->new( dirname => $dir->[0] )->make( { user => $dir->[1], group => $dir->[2], mode =>
                $dir->[3] } );
        return $rs if $rs;
    }

    # Todo move this statement into the httpd apache_fcgid server implementation (uninstaller) when it will be ready for
    # call when switching to another httpd server implementation.
    $rs = iMSCP::Dir->new( dirname => $self->{'config'}->{'PHP_STARTER_DIR'} )->remove();
    return $rs if $rs;

    $self->{'eventManager'}->trigger( 'afterHttpdMakeDirs' );
}

=item _buildPhpConfFiles()

 Build PHP configuration files

 Return int 0 on success, other on failure

=cut

sub _buildPhpConfFiles
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBuildPhpConfFiles' );
    return $rs if $rs;

    $self->{'httpd'}->setData( {
            PEAR_DIR => $main::imscpConfig{'PEAR_DIR'},
            TIMEZONE => $main::imscpConfig{'TIMEZONE'}
        } );

    $rs = $self->{'httpd'}->buildConfFile( $self->{'apacheCfgDir'}.'/parts/php5.itk.ini', { }, {
            destination => "$self->{'apacheWrkDir'}/php.ini",
            mode        => 0644,
            user        => $main::imscpConfig{'ROOT_USER'},
            group       => $main::imscpConfig{'ROOT_GROUP'}
        } );
    return $rs if $rs;

    $rs = iMSCP::File->new( filename => "$self->{'apacheWrkDir'}/php.ini" )->copyFile(
        $self->{'config'}->{"ITK_PHP5_PATH"}
    );
    return $rs if $rs;

    # Transitional: fastcgi_imscp
    my @modulesOff = ('fastcgi', 'fcgid', 'fastcgi_imscp', 'fcgid_imscp', 'php_fpm_imscp', 'php4', 'php5_cgi', 'suexec');
    my @modulesOn = ('php5', 'version');
    my $version = $self->{'config'}->{'HTTPD_VERSION'};

    if (version->parse( $version ) >= version->parse( '2.4.0' )) {
        # MPM management is a mess in Jessie. We so disable all and re-enable only needed MPM
        push @modulesOff, 'mpm_itk', 'mpm_prefork', 'mpm_event', 'mpm_prefork', 'mpm_worker';
        push @modulesOn, 'mpm_itk', 'authz_groupfile';
    }

    $rs = $self->{'httpd'}->disableModules( @modulesOff );
    $rs ||= $self->{'httpd'}->enableModules( @modulesOn );
    return $rs if $rs;

    if (iMSCP::ProgramFinder::find( 'php5enmod' )) {
        for my $extension(
            'apc', 'curl', 'gd', 'imap', 'intl', 'json', 'mcrypt', 'mysqlnd/10', 'mysqli', 'mysql', 'opcache', 'pdo/10',
            'pdo_mysql'
        ) {
            $rs = execute( "php5enmod $extension", \my $stdout, \my $stderr );
            debug( $stdout ) if $stdout;
            unless (grep($_ eq $rs, ( 0, 2 ))) {
                error( $stderr ) if $stderr;
                return $rs;
            }
        }
    }

    $self->{'eventManager'}->trigger( 'afterHttpdBuildPhpConfFiles' );
}

=item _buildApacheConfFiles()

 Build Apache configuration files

 Return int 0 on success, other on failure

=cut

sub _buildApacheConfFiles
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBuildApacheConfFiles' );
    return $rs if $rs;

    if (-f "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf") {
        $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'apache_itk', 'ports.conf', \my $cfgTpl, { } );
        return $rs if $rs;

        unless (defined $cfgTpl) {
            $cfgTpl = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf" )->get();
            unless (defined $cfgTpl) {
                error( sprintf( 'Could not read %s file', "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf" ) );
                return 1;
            }
        }

        $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBuildConfFile', \$cfgTpl, 'ports.conf' );
        return $rs if $rs;

        $cfgTpl =~ s/^(NameVirtualHost\s+\*:80)/#$1/gmi;

        $rs = $self->{'eventManager'}->trigger( 'afterHttpdBuildConfFile', \$cfgTpl, 'ports.conf' );
        return $rs if $rs;

        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf" );
        $rs = $file->set( $cfgTpl );
        $rs ||= $file->mode( 0644 );
        $rs ||= $file->save();
        return $rs if $rs;
    }

    # Turn off default access log provided by Debian package
    if (-d "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available") {
        $rs = $self->{'httpd'}->disableConfs( 'other-vhosts-access-log.conf' );
        return $rs if $rs;
    } elsif (-f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/other-vhosts-access-log") {
        $rs = iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/other-vhosts-access-log"
        )->delFile();
        return $rs if $rs;
    }

    # Remove default access log file provided by Debian package
    if (-f "$self->{'config'}->{'HTTPD_LOG_DIR'}/other_vhosts_access.log") {
        $rs = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_LOG_DIR'}/other_vhosts_access.log" )->delFile();
        return $rs if $rs;
    }

    my $apache24 = version->parse( "$self->{'config'}->{'HTTPD_VERSION'}" ) >= version->parse( '2.4.0' );

    $rs = $self->{'httpd'}->setData( {
            HTTPD_LOG_DIR   => $self->{'config'}->{'HTTPD_LOG_DIR'},
            HTTPD_ROOT_DIR  => $self->{'config'}->{'HTTPD_ROOT_DIR'},
            AUTHZ_DENY_ALL  => $apache24 ? 'Require all denied' : 'Deny from all',
            AUTHZ_ALLOW_ALL => $apache24 ? 'Require all granted' : 'Allow from all',
            PIPE            =>
                version->parse( "$self->{'config'}->{'HTTPD_VERSION'}" ) >= version->parse( '2.2.12' ) ? '||' : '|',
            VLOGGER_CONF    => "$self->{'apacheWrkDir'}/vlogger.conf"
        } );
    $rs ||= $self->{'httpd'}->buildConfFile( '00_nameserver.conf' );
    $rs ||= $self->{'httpd'}->installConfFile( '00_nameserver.conf' );
    $rs ||= $self->{'httpd'}->setData( { HTTPD_CUSTOM_SITES_DIR => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'} } );
    $rs ||= $self->{'httpd'}->buildConfFile( '00_imscp.conf' );
    $rs ||= $self->{'httpd'}->installConfFile( '00_imscp.conf', {
            destination => -d "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available"
                ? "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available" : "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d"
        } );
    $rs ||= $self->{'httpd'}->enableModules( 'cgid', 'proxy', 'proxy_http', 'rewrite', 'setenvif', 'ssl' );
    $rs ||= $self->{'httpd'}->enableSites( '00_nameserver.conf' );
    $rs ||= $self->{'httpd'}->enableConfs( '00_imscp.conf' );
    $rs ||= $self->{'httpd'}->disableSites( 'default', 'default-ssl', '000-default.conf', 'default-ssl.conf' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdBuildApacheConfFiles' );
}

=item _installLogrotate()

 Install Apache logrotate file

 Return int 0 on success, other on failure

=cut

sub _installLogrotate
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdInstallLogrotate', 'apache2' );
    $rs ||= $self->{'httpd'}->setData( {
            ROOT_USER     => $main::imscpConfig{'ROOT_USER'},
            ADM_GROUP     => $main::imscpConfig{'ADM_GROUP'},
            HTTPD_LOG_DIR => $self->{'config'}->{'HTTPD_LOG_DIR'}
        } );
    $rs ||= $self->{'httpd'}->buildConfFile( 'logrotate.conf' );
    $rs ||= $self->{'httpd'}->installConfFile( 'logrotate.conf', {
            destination => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2"
        } );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdInstallLogrotate', 'apache2' );
}

=item _setupVlogger()

 Setup vlogger

 Return int 0 on success, other on failure

=cut

sub _setupVlogger
{
    my $self = shift;

    my $sqld = Servers::sqld->factory();
    my $host = main::setupGetQuestion( 'DATABASE_HOST' );
    $host = $host eq 'localhost' ? '127.0.0.1' : $host;
    my $port = main::setupGetQuestion( 'DATABASE_PORT' );
    my $dbName = main::setupGetQuestion( 'DATABASE_NAME' );
    my $user = 'vlogger_user';
    my $userHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    $userHost = '127.0.0.1' if $userHost eq 'localhost';

    my @allowedChr = map { chr } (0x21 .. 0x5b, 0x5d .. 0x7e);
    my $pass = '';
    $pass .= $allowedChr[ rand @allowedChr ] for 1 .. 16;

    my $db = iMSCP::Database->factory();
    my $rs = main::setupImportSqlSchema( $db, "$self->{'apacheCfgDir'}/vlogger.sql" );
    return $rs if $rs;

    for my $host($userHost, $main::imscpOldConfig{'DATABASE_USER_HOST'}, 'localhost') {
        next unless $host;
        $sqld->dropUser( $user, $host );
    }

    my $qDbName = $db->quoteIdentifier( $dbName );
    $sqld->createUser( $user, $userHost, $pass );
    $rs = $db->doQuery( 'g', "GRANT SELECT, INSERT, UPDATE ON $qDbName.httpd_vlogger TO ?@?", $user, $userHost );
    unless (ref $rs eq 'HASH') {
        error( sprintf( 'Could not add SQL privileges: %s', $rs ) );
        return 1;
    }

    $rs = $self->{'httpd'}->setData(
        {
            DATABASE_NAME     => $dbName,
            DATABASE_HOST     => $host,
            DATABASE_PORT     => $port,
            DATABASE_USER     => $user,
            DATABASE_PASSWORD => $pass
        }
    );
    $rs ||= $self->{'httpd'}->buildConfFile(
        "$self->{'apacheCfgDir'}/vlogger.conf.tpl", { }, { destination => "$self->{'apacheWrkDir'}/vlogger.conf" }
    );
}

=item _saveConf()

 Save configuration file

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
    my $self = shift;

    iMSCP::File->new( filename => "$self->{'apacheCfgDir'}/apache.data" )->copyFile(
        "$self->{'apacheCfgDir'}/apache.old.data"
    );
}

=item _oldEngineCompatibility()

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdOldEngineCompatibility' );
    $rs ||= $self->{'httpd'}->disableSites( 'imscp.conf', '00_modcband.conf', '00_master.conf', '00_master_ssl.conf' );
    return $rs if $rs;

    for my $site('imscp.conf', '00_modcband.conf', '00_master.conf', '00_master_ssl.conf') {
        next unless -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site";
        $rs ||= iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site" )->delFile();
        return $rs if $rs;
    }

    for my $dir($self->{'config'}->{'APACHE_BACKUP_LOG_DIR'}, $self->{'config'}->{'HTTPD_USERS_LOG_DIR'},
        $self->{'config'}->{'APACHE_SCOREBOARDS_DIR'}
    ) {
        $rs = iMSCP::Dir->new( dirname => $dir )->remove();
        return $rs if $rs;
    }

    # Remove customer's logs file if any (no longer needed since we are now use bind mount)
    $rs = execute( "rm -f $main::imscpConfig{'USER_WEB_DIR'}/*/logs/*.log", \my $stdout, \my $stderr );
    error( $stderr ) if $rs && $stderr;
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdOldEngineCompatibility' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
