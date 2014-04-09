#!/usr/bin/perl

=head1 NAME

 Servers::httpd::apache_php_fpm::uninstaller - i-MSCP Apache2/PHP-FPM Server uninstaller

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
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
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_php_fpm::uninstaller;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::SystemUser;
use iMSCP::SystemGroup;
use iMSCP::File;
use iMSCP::Dir;
use File::Basename;
use Servers::httpd::apache_php_fpm;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Apache2/PHP-FPM Server uninstaller

=head1 PUBLIC METHODS

=item uninstall

 Process uninstall tasks

 Return int 0 on success, 1 on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->_removeUserAndGroup();
	return $rs if $rs;

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

 Called by getInstance(). Initialize instance

 Return Servers::httpd::apache_php_fpm::uninstaller

=cut

sub _init
{
	my $self = $_[0];

	$self->{'httpd'} = Servers::httpd::apache_php_fpm->getInstance();

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

=item _removeUserAndGroup()

 Remove Panel user and group

 Return int 0 on success, 1 on failure

=cut

sub _removeUserAndGroup
{
	my $self = $_[0];

	# Panel user
	my $rs  = iMSCP::SystemUser->new('force' => 'yes')->delSystemUser(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	);
	return $rs if $rs;

	# Remove panel group
	iMSCP::SystemGroup->getInstance()->delSystemGroup(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	);
}

=item _removeVloggerSqlUser()

 Remove vlogger SQL user

 Return int 0

=cut

sub _removeVloggerSqlUser
{
	my $self = $_[0];

	my $db = iMSCP::Database->factory();

	$db->doQuery('dummy', 'DROP USER ?@?', 'vlogger_user', $main::imscpConfig{'DATABASE_USER_HOST'});
	$db->doQuery('dummy', 'FLUSH PRIVILEGES');

	0;
}

=item _removeDirs()

 Remove Apache directories

 Return int 0 on success, 1 on failure

=cut

sub _removeDirs
{
	my $self = $_[0];

	iMSCP::Dir->new('dirname' => $self->{'config'}->{'APACHE_CUSTOM_SITES_CONFIG_DIR'})->remove();
}

=item _restoreApacheConfig()

 Restore Apache configuration

 Return int 0 on success, 1 on failure

=cut

sub _restoreApacheConfig
{
	my $self = $_[0];

	my $rs = $self->{'httpd'}->disableMod('php_fpm_imscp')
		if -f "$self->{'config'}->{'APACHE_MODS_DIR'}/php_fpm_imscp.load";
	return $rs if $rs;

	for ('php_fpm_imscp.conf', 'php_fpm_imscp.load') {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'config'}->{'APACHE_MODS_DIR'}/$_"
		)->delFile() if -f "$self->{'config'}->{'APACHE_MODS_DIR'}/$_";
		return $rs if $rs;
	}

	for('00_nameserver.conf', '00_master_ssl.conf', '00_master.conf') {
		if(-f "$self->{'config'}->{'APACHE_SITES_DIR'}/$_") {
			$rs = $self->{'httpd'}->disableSite($_);
			return $rs if $rs;

			$rs = iMSCP::File->new('filename' => "$self->{'config'}->{'APACHE_SITES_DIR'}/$_")->delFile();
			return $rs if $rs;
		}
	}

	for ("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2", "$self->{'config'}->{'APACHE_CONF_DIR'}/ports.conf") {
		my $filename = fileparse($_);

		$rs = iMSCP::File->new(
			'filename' => "$self->{'apacheBkpDir'}/$filename.system"
		)->copyFile($_) if -f "$self->{'apacheBkpDir'}/$filename.system";
		return $rs if $rs;
	}


	$rs = iMSCP::Dir->new('dirname' => $self->{'config'}->{'APACHE_CUSTOM_SITES_CONFIG_DIR'})->remove();
	return $rs if $rs;

	for('000-default', 'default') {
		$rs = $self->{'httpd'}->enableSite($_) if -f "$self->{'config'}->{'APACHE_SITES_DIR'}/$_";
		return $rs if $rs;
	}

	0;
}

=item restorePhpfpmConfig()

 Restore PHP FPM configuration

 Return int 0 on success, 1 on failure

=cut

sub _restorePhpfpmConfig
{
	my $self = $_[0];

	my $rs = iMSCP::File->new(
		'filename' => "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/master.conf"
	)->delFile() if -f "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/master.conf";
	return $rs if $rs;

	my $filename = fileparse("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm");

	if(-f "$self->{'phpfpmBkpDir'}/logrotate.$filename.system") {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'phpfpmBkpDir'}/logrotate.$filename.system"
		)->copyFile("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/$filename");
		return $rs if $rs;
	}

	for (
		"$self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php-fpm.conf",
		"$self->{'phpfpmConfig'}->{'PHP_FPM_CONF_DIR'}/php.ini"
	) {
		$filename = fileparse($_);

		$rs = iMSCP::File->new(
			'filename' => "$self->{'phpfpmBkpDir'}/$filename.system"
		)->copyFile($_) if -f "$self->{'phpfpmBkpDir'}/$filename.system";
		return $rs if $rs;
	}

	$rs = iMSCP::File->new(
		'filename' => "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/www.conf.disabled"
	)->moveFile(
		"$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/www.conf"
	) if -f "$self->{'phpfpmConfig'}->{'PHP_FPM_POOLS_CONF_DIR'}/www.conf.disabled";
	return $rs if $rs;

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
