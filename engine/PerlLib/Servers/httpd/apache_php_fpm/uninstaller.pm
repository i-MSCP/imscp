#!/usr/bin/perl

=head1 NAME

 Servers::httpd::apache_php_fpm::uninstaller - i-MSCP Apache PHP-FPM Server uninstaller

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_php_fpm::uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use Servers::httpd::apache_php_fpm;
use Modules::SystemUser;
use Modules::SystemGroup;
use iMSCP::Dir;
use iMSCP::File;
use File::Basename;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Apache PHP FPM Server uninstaller.

=head1 PUBLIC METHODS

=item uninstall

 Process uninstall tasks.

 Return int 0 on success, 1 on failure

=cut

sub uninstall
{
	my $self = shift;

	$self->_removeUserAndGroup() and return 1;
	$self->_restoreApacheConfig() and return 1;
	$self->_restorePhpfpmConfig();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by new(). Initialize instance.

 Return Servers::httpd::apache_php_fpm::uninstaller

=cut

sub _init
{
	my $self = shift;

	$self->{'httpd'} = Servers::httpd::apache_php_fpm->new();

	$self->{'apacheCfgDir'} = "$main::imscpConfig{'CONF_DIR'}/apache";
	$self->{'apacheBkpDir'} = "$self->{'apacheCfgDir'}/backup";
	$self->{'apacheWrkDir'} = "$self->{'apacheCfgDir'}/working";

	tie %self::apacheConfig, 'iMSCP::Config','fileName' => "$self->{'apacheCfgDir'}/apache.data";

	$self->{'phpfpmCfgDir'} = "$main::imscpConfig{'CONF_DIR'}/php-fpm";
	$self->{'apacheBkpDir'} = "$self->{'phpfpmCfgDir'}/backup";
	$self->{'apacheWrkDir'} = "$self->{'phpfpmCfgDir'}/working";

	tie %self::phpfpmConfig, 'iMSCP::Config','fileName' => "$self->{'phpfpmCfgDir'}/phpfpm.data";

	$self;
}

=item _removeUserAndGroup()

 Remove Panel user and group.

 Return int 0 on success, 1 on failure
=cut

sub _removeUserAndGroup
{
	my $self = shift;

	# Remove panel user
	my $panelUName = Modules::SystemUser->new();
	$panelUName->{'force'} = 'yes';
	$panelUName->delSystemUser(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	) and return 1;

	# Remove panel group
	my $panelGName = Modules::SystemGroup->new();
	$panelGName->delSystemGroup(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	) and return 1;

	0;
}

=item _restoreApacheConfig()

 Restore Apache configuration.

 Return int 0 on success, 1 on failure

=cut

sub _restoreApacheConfig
{
	my $self = shift;

	$self->{'httpd'}->disableMod('php_fpm_imscp') and return 1
		if -e "$self::apacheConfig{'APACHE_MODS_DIR'}/php_fpm_imscp.load";

	for ('php_fpm_imscp.conf', 'php_fpm_imscp.load') {
		iMSCP::File->new(
			'filename' => "$self::apacheConfig{'APACHE_MODS_DIR'}/$_"
		)->delFile() and return 1 if -e "$self::apacheConfig{'APACHE_MODS_DIR'}/$_";
	}

	for('00_nameserver.conf', '00_master_ssl.conf', '00_master.conf', '00_modcband.conf', '01_awstats.conf') {
		if( -e "$self::apacheConfig{'APACHE_SITES_DIR'}/$_") {
			$self->{'httpd'}->disableSite($_) and return 1;
			iMSCP::File->new('filename' => "$self::apacheConfig{'APACHE_SITES_DIR'}/$_")->delFile() and return 1;
		}
	}

	for (
		"$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2", "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache",
		"$self::apacheConfig{'APACHE_CONF_DIR'}/ports.conf"
	) {
		my ($filename, $directories, $suffix) = fileparse($_);

		iMSCP::File->new(
			filename => "$self->{'apacheBkpDir'}/$filename$suffix.system"
		)->copyFile($_) and return 1 if -e "$self->{'apacheBkpDir'}/$filename$suffix.system";
	}

	for (
		$self::apacheConfig{'APACHE_USERS_LOG_DIR'}, $self::apacheConfig{'APACHE_BACKUP_LOG_DIR'},
		$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'}
	) {
		iMSCP::Dir->new(dirname => $_)->remove() and return 1 if -d $_;
	}

	$self->{'httpd'}->enableSite('default') and return 1 if -e "$self::apacheConfig{'APACHE_SITES_DIR'}/default";

	0;
}

=item restorePhpfpmConfig()

 Restore PHP FPM configuration.

 Return int 0 on success, 1 on failure

=cut

sub _restorePhpfpmConfig
{
	my $self = shift;

	iMSCP::File->new(
		'filename' => "$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/master.conf"
	)->delFile() and return 1 if -e "$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/master.conf";

	my ($filename, $directories, $suffix) = fileparse("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm");

	if("$self->{'apacheBkpDir'}/logrotate.$filename$suffix.system") {
		iMSCP::File->new(
    		'filename' => "$self->{'apacheBkpDir'}/logrotate.$filename$suffix.system"
    	)->copyFile("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/$filename$suffix") and return 1
	}

	($filename, $directories, $suffix) = fileparse($self::phpfpmConfig{'CMD_PHP_FPM'});

	if("$self->{'apacheBkpDir'}/init.$filename$suffix.system") {
		iMSCP::File->new(
    		'filename' => "$self->{'phpfpmBkpDir'}/init.$filename$suffix.system"
    	)->copyFile($self::phpfpmConfig{'CMD_PHP_FPM'}) and return 1
	}

	for (
		"$self::phpfpmConfig{'PHP_FPM_CONF_DIR'}/php-fpm.conf"
		"$self::phpfpmConfig{'PHP_FPM_CONF_DIR'}/php.ini"
	) {
		($filename, $directories, $suffix) = fileparse($_);

		iMSCP::File->new(
			'filename' => "$self->{'phpfpmBkpDir'}/$filename$suffix.system"
		)->copyFile($_) and return 1 if -e "$self->{'phpfpmBkpDir'}/$filename$suffix.system";
	}

	iMSCP::File->new(
		'filename' => "$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/www.conf.disabled"
	)->moveFile(
		"$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/www.conf"
	) if -e "$self::phpfpmConfig{'PHP_FPM_POOLS_CONF_DIR'}/www.conf.disabled";

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
