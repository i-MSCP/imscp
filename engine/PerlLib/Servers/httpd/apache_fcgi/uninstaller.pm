#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::httpd::apache_fcgi::uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use Data::Dumper;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{

	my $self		= shift;

	$self->{cfgDir}	= "$main::imscpConfig{'CONF_DIR'}/apache";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";

	my $conf		= "$self->{cfgDir}/apache.data";

	tie %self::apacheConfig, 'iMSCP::Config','fileName' => $conf;

	0;
}

sub uninstall{

	my $self = shift;
	my $rs = 0;

	$rs |= $self->removeUsers();
	$rs |= $self->removeDirs();
	$rs |= $self->fastcgiConf();
	$rs |= $self->vHostConf();
	$rs |= $self->restoreConf();

	$rs;
}

sub removeUsers{

	my $self = shift;
	my $rs = 0;
	my ($panelGName, $panelUName);

	## Panel user
	use Modules::SystemUser;
	$panelUName = Modules::SystemUser->new();
	$panelUName->{force} = 'yes';
	$rs |= $panelUName->delSystemUser($main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'});

	# Panel group
	use Modules::SystemGroup;
	$panelGName = Modules::SystemGroup->new();
	$rs |= $panelGName->delSystemGroup($main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'});

	$rs;
}

sub removeDirs{

	use iMSCP::Dir;

	my $rs			= 0;
	my $self		= shift;

	for (
		$self::apacheConfig{'APACHE_USERS_LOG_DIR'},
		$self::apacheConfig{'APACHE_BACKUP_LOG_DIR'},
		$self::apacheConfig{'APACHE_CUSTOM_SITES_CONFIG_DIR'},
		$self::apacheConfig{'PHP_STARTER_DIR'}
	) {
		$rs |= iMSCP::Dir->new(dirname => $_)->remove() if -d $_;
	}

	$rs;
}

sub restoreConf{

	use File::Basename;

	my $self		= shift;
	my $rs			= 0;

	for ((
		"$main::imscpConfig{LOGROTATE_CONF_DIR}/apache2",
		"$main::imscpConfig{LOGROTATE_CONF_DIR}/apache",
		"$self::apacheConfig{APACHE_CONF_DIR}/ports.conf"
	)) {
		my ($filename, $directories, $suffix) = fileparse($_);
		$rs	=	iMSCP::File->new(
					filename => "$self->{bkpDir}/$filename$suffix.system"
				)->copyFile($_)
				if(-f "$self->{bkpDir}/$filename$suffix.system");
	}

	$rs;
}

sub fastcgiConf {

	use iMSCP::File;
	use Servers::httpd::apache_fcgi;

	my $self	= shift;
	my $httpd	= Servers::httpd::apache_fcgi->new();
	my $rs		= 0;

	$rs |= $httpd->disableMod("fastcgi_imscp fcgid_imscp");

	for (qw/fastcgi_imscp.conf fastcgi_imscp.load fcgid_imscp.conf fcgid_imscp.load/) {
		$rs |= iMSCP::File->new(filename => "$self::apacheConfig{'APACHE_MODS_DIR'}/$_")->delFile();
	}

	$rs;
}

sub vHostConf {

	use iMSCP::File;
	use Servers::httpd::apache_fcgi;

	my $self	= shift;
	my $httpd	= Servers::httpd::apache_fcgi->new();
	my $rs		= 0;

	for("00_nameserver.conf", "00_master_ssl.conf", "00_master.conf", "00_modcband.conf", "01_awstats.conf"){

		$rs |= $httpd->disableSite($_);

		if(-f "$self::apacheConfig{'APACHE_SITES_DIR'}/$_") {
			$rs |= iMSCP::File->new(
				filename => "$self::apacheConfig{'APACHE_SITES_DIR'}/$_"
			)->delFile();
		}
	}

	$rs |= $httpd->enableSite("default");

	$rs;
}

1;
