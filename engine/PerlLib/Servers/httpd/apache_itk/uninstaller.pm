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

package Servers::httpd::apache_itk::uninstaller;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::SystemUser;
use iMSCP::SystemGroup;
use iMSCP::File;
use iMSCP::Dir;
use File::Basename;
use Servers::httpd::apache_itk;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'httpd'} = Servers::httpd::apache_fcgi->getInstance();

	$self->{'apacheCfgDir'} = $self->{'httpd'}->{'apacheCfgDir'};
	$self->{'apacheBkpDir'} = "$self->{'apacheCfgDir'}/backup";
	$self->{'apacheWrkDir'} = "$self->{'apacheCfgDir'}/working";

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

	$rs = $self->vHostConf();
	return $rs if $rs;

	$self->restoreConf();
}

sub removeUsers
{
	my $self = shift;

	# Panel user
	my $rs  = iMSCP::SystemUser->new('force' => 'yes')->delSystemUser(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	);
	return $rs if $rs;

	# Panel group
	iMSCP::SystemGroup->getInstance()->delSystemGroup(
		'groupname' => $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	);
}

sub removeDirs
{
	my $self = shift;

	my $rs = 0;

	for (
		$self->{'config'}->{'APACHE_USERS_LOG_DIR'}, $self->{'config'}->{'APACHE_BACKUP_LOG_DIR'},
		$self->{'config'}->{'APACHE_CUSTOM_SITES_CONFIG_DIR'}, $self->{'config'}->{'SCOREBOARDS_DIR'}
	) {
		$rs = iMSCP::Dir->new('dirname' => $_)->remove();
		return $rs if $rs;
	}

	0;
}

sub restoreConf
{
	my $self = shift;

	my $rs = 0;

	for ("$main::imscpConfig{LOGROTATE_CONF_DIR}/apache2", "$self->{'config'}->{APACHE_CONF_DIR}/ports.conf") {
		my $filename = fileparse($_);

		$rs	= iMSCP::File->new(
			'filename' => "$self->{bkpDir}/$filename.system"
		)->copyFile($_) if -f "$self->{bkpDir}/$filename.system";
		return $rs if $rs;
	}

	$rs;
}

sub vHostConf
{
	my $self = shift;

	my $rs = 0;

	for('00_nameserver.conf', '00_master_ssl.conf', '00_master.conf') {
		$rs = $self->{'httpd'}->disableSite($_);
		return $rs if $rs;

		if(-f "$self->{'config'}->{'APACHE_SITES_DIR'}/$_") {
			$rs = iMSCP::File->new(
				'filename' => "$self->{'config'}->{'APACHE_SITES_DIR'}/$_"
			)->delFile();
			return $rs if $rs;
		}
	}

	for('000-default', 'default') {
		$rs = $self->{'httpd'}->enableSite($_) if -f "$self->{'config'}->{'APACHE_SITES_DIR'}/$_";
		return $rs if $rs;
	}

	0;
}

1;
