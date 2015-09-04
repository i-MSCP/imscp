=head1 NAME

 Servers::httpd::apache_php_fpm::uninstaller - i-MSCP Apache2/PHP-FPM Server uninstaller

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::httpd::apache_php_fpm::uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Database;
use File::Basename;
use Servers::httpd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Apache2/PHP-FPM Server uninstaller.

=head1 PUBLIC METHODS

=item uninstall

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	$rs = $self->_removeVloggerSqlUser();
	return $rs if $rs;

	$rs = $self->_removeDirs();
	return $rs if $rs;

	$rs = $self->_restoreApacheConfig();
	return $rs if $rs;

	$self->_restorePhpfpmConfig();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::httpd::apache_php_fpm::uninstaller

=cut

sub _init
{
	my $self = shift;

	$self->{'httpd'} = Servers::httpd->factory();
	$self->{'apacheCfgDir'} = $self->{'httpd'}->{'apacheCfgDir'};
	$self->{'apacheBkpDir'} = "$self->{'apacheCfgDir'}/backup";
	$self->{'apacheWrkDir'} = "$self->{'apacheCfgDir'}/working";
	$self->{'config'} = $self->{'httpd'}->{'config'};
	$self->{'phpfpmCfgDir'} = $self->{'httpd'}->{'phpfpmCfgDir'};
	$self->{'phpfpmBkpDir'} = "$self->{'phpfpmCfgDir'}/backup";
	$self->{'phpfpmWrkDir'} = "$self->{'phpfpmCfgDir'}/working";
	$self->{'phpfpmConfig'} = $self->{'httpd'}->{'phpfpmConfig'};
	$self;
}

=item _removeVloggerSqlUser()

 Remove vlogger SQL user

 Return int 0

=cut

sub _removeVloggerSqlUser
{
	my $self = shift;

	my $db = iMSCP::Database->factory();

	$db->doQuery('d', 'DROP USER ?@?', 'vlogger_user', $main::imscpConfig{'DATABASE_USER_HOST'});
	$db->doQuery('f', 'FLUSH PRIVILEGES');

	0;
}

=item _removeDirs()

 Remove Apache directories

 Return int 0 on success, other on failure

=cut

sub _removeDirs
{
	my $self = shift;

	iMSCP::Dir->new( dirname => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'} )->remove();
}

=item _restoreApacheConfig()

 Restore Apache configuration

 Return int 0 on success, other on failure

=cut

sub _restoreApacheConfig
{
	my $self = shift;

	if (-l "$self->{'config'}->{'HTTPD_MODS_ENABLED_DIR'}/php_fpm_imscp.load") {
		my $rs = $self->{'httpd'}->disableModules('php_fpm_imscp')
		return $rs if $rs;
	}

	for my $filename('php_fpm_imscp.conf', 'php_fpm_imscp.load') {
		if -f "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$filename" {
			iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$filename")->delFile();
		}
	}

	if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_nameserver.conf") {
		my $rs = $self->{'httpd'}->disableSites('00_nameserver.conf');
		return $rs if $rs;

		iMSCP::File->new(filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_nameserver.conf")->delFile();
	}

	my $confDir = (-d "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available")
		? $self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available : "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d";

    if(-f "$confDir/00_imscp.conf") {
		my $rs = $self->{'httpd'}->disableConfs('00_imscp.conf');
		return $rs if $rs;

		iMSCP::File->new( filename => "$confDir/00_imscp.conf" )->delFile();
	}

	for my $file("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2", "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf") {
		my $filename = fileparse($file);

		if (-f "$self->{'apacheBkpDir'}/$filename.system") {
			iMSCP::File->new( filename => "$self->{'apacheBkpDir'}/$filename.system" )->copyFile($file)
		}
	}

	iMSCP::Dir->new( dirname => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'} )->remove();

	for my $site('000-default', 'default') {
		if (-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site") {
			my $rs = $self->{'httpd'}->enableSites($site);
			return $rs if $rs;
		}
	}

	0;
}

=item restorePhpfpmConfig()

 Restore PHP-FPM configuration

 Return int 0 on success, other on failure

=cut

sub _restorePhpfpmConfig
{
	my $self = shift;

	my $basename = basename("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm");

	if(-f "$self->{'phpfpmBkpDir'}/logrotate.$basename.system") {
		iMSCP::File->new( filename => "$self->{'phpfpmBkpDir'}/logrotate.$basename.system")->copyFile(
			"$main::imscpConfig{'LOGROTATE_CONF_DIR'}/$basename"
		);
	}

	for my $file(
		"$self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php-fpm.conf",
		"$self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php.ini"
	) {
		$basename = basename($file);

		if (-f "$self->{'phpfpmBkpDir'}/$basename.system") {
			iMSCP::File->new( filename => "$self->{'phpfpmBkpDir'}/$basename.system" )->copyFile($_)
		}
	}

	if (-f "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/www.conf.disabled") {
		iMSCP::File->new(filename => "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/www.conf.disabled")->moveFile(
			"$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/www.conf"
		);
	}

	if(-f "/etc/init/php5-fpm.override") {
		iMSCP::File->new( filename => "/etc/init/php5-fpm.override" )->delFile();
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
