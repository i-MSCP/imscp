#!/usr/bin/perl

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
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_fcgi::uninstaller;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::SystemUser;
use iMSCP::SystemGroup;
use iMSCP::Dir;
use File::Basename;
use iMSCP::File;
use Servers::httpd::apache_fcgi;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'httpd'} = Servers::httpd::apache_fcgi->getInstance();

	$self->{'cfgDir'} = $self->{'httpd'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'config'} = $self->{'httpd'}->{'config'};

	$self;
}

sub uninstall
{
	my $self = shift;

	my $rs = $self->removeUsers();
	return $rs if $rs;

	$rs = $self->removeDirs();
	return $rs if $rs;

	$rs = $self->fastcgiConf();
	return $rs if $rs;

	$rs = $self->vHostConf();
	return $rs if $rs;

	$self->restoreConf();
}

sub removeUsers
{
	my $self = shift;

	my $rs = 0;
	my ($panelGName, $panelUName);

	# Panel user
	$panelUName = iMSCP::SystemUser->new();
	$panelUName->{'force'} = 'yes';

	$rs = $panelUName->delSystemUser(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	);
	return $rs if $rs;

	# Panel group
	$panelGName = iMSCP::SystemGroup->new();

	$panelGName->delSystemGroup(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	);
}

sub removeDirs
{
	my $self = shift;
	my $rs = 0;

	for (
		$self->{'config'}->{'APACHE_USERS_LOG_DIR'}, $self->{'config'}->{'APACHE_BACKUP_LOG_DIR'},
		$self->{'config'}->{'APACHE_CUSTOM_SITES_CONFIG_DIR'}, $self->{'config'}->{'PHP_STARTER_DIR'}
	) {
		$rs = iMSCP::Dir->new(dirname => $_)->remove() if -d $_;
		return $rs if $rs;
	}

	0;
}

sub restoreConf
{
	my $self = shift;
	my $rs = 0;

	for (
		"$main::imscpConfig{LOGROTATE_CONF_DIR}/apache2", "$main::imscpConfig{LOGROTATE_CONF_DIR}/apache",
		"$self->{'config'}->{APACHE_CONF_DIR}/ports.conf"
	) {
		my ($filename, $directories, $suffix) = fileparse($_);
		$rs	= iMSCP::File->new(
			'filename' => "$self->{bkpDir}/$filename$suffix.system"
		)->copyFile($_) if(-f "$self->{bkpDir}/$filename$suffix.system");
		return $rs if $rs;
	}

	0;
}

sub fastcgiConf
{
	my $self = shift;

	my $httpd = Servers::httpd::apache_fcgi->getInstance();

	# try to disable but do not fail if do not exists
	my $rs = 0;
	for('fastcgi_imscp', 'fcgid_imscp') {
		$rs = $httpd->disableMod($_) if -f "$self->{'config'}->{'APACHE_MODS_DIR'}/$_.load";
		return $rs if $rs;
	}
	
	for ('fastcgi_imscp.conf', 'fastcgi_imscp.load', 'fcgid_imscp.conf', 'fcgid_imscp.load') {
		$rs = iMSCP::File->new('filename' => "$self->{'config'}->{'APACHE_MODS_DIR'}/$_")->delFile() if -f "$self->{'config'}->{'APACHE_MODS_DIR'}/$_";
		return $rs if $rs;
	}

	0;
}

sub vHostConf
{
	my $self = shift;

	my $httpd = Servers::httpd::apache_fcgi->getInstance();
	my $rs = 0;

	for('00_nameserver.conf', '00_master_ssl.conf', '00_master.conf', '00_modcband.conf', '01_awstats.conf') {

		$rs = $httpd->disableSite($_);
		return $rs if $rs;

		if(-f "$self->{'config'}->{'APACHE_SITES_DIR'}/$_") {
			$rs = iMSCP::File->new(
				'filename' => "$self->{'config'}->{'APACHE_SITES_DIR'}/$_"
			)->delFile();
			return $rs if $rs;
		}
	}

	$httpd->enableSite('default');
}

1;
