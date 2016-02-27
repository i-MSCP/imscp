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

package Servers::httpd::apache_fcgid::uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Database;
use File::Basename;
use Servers::httpd::apache_fcgid;
use parent 'Common::SingletonClass';

sub uninstall
{
	my $self = shift;

	my $rs = $self->_removeVloggerSqlUser();
	$rs ||= $self->_removeDirs();
	$rs ||= $self->_fastcgiConf();
	$rs ||= $self->_vHostConf();
	$rs ||= $self->_restoreConf();
}

sub _init
{
	my $self = shift;

	$self->{'httpd'} = Servers::httpd::apache_fcgid->getInstance();
	$self->{'apacheCfgDir'} = $self->{'httpd'}->{'apacheCfgDir'};
	$self->{'apacheBkpDir'} = "$self->{'apacheCfgDir'}/backup";
	$self->{'apacheWrkDir'} = "$self->{'apacheCfgDir'}/working";
	$self->{'config'} = $self->{'httpd'}->{'config'};
	$self;
}

sub _removeVloggerSqlUser
{
	my $self = shift;

	my $db = iMSCP::Database->factory();
	$db->doQuery('d', 'DROP USER ?@?', 'vlogger_user', $main::imscpConfig{'DATABASE_USER_HOST'});
	$db->doQuery('f', 'FLUSH PRIVILEGES');
	0;
}

sub _removeDirs
{
	my $self = shift;

	for my $dir($self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}, $self->{'config'}->{'PHP_STARTER_DIR'}) {
		my $rs = iMSCP::Dir->new( dirname => $dir )->remove();
		return $rs if $rs;
	}

	0;
}

sub _restoreConf
{
	my $self = shift;

	for my $file("$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2", "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf") {
		my $filename = fileparse($file);

		if(-f "$self->{bkpDir}/$filename.system") {
			my $rs = iMSCP::File->new( filename => "$self->{bkpDir}/$filename.system")->copyFile($file) ;
			return $rs if $rs;
		}
	}

	0;
}

sub _fastcgiConf
{
	my $self = shift;

	if (-l "$self->{'config'}->{'HTTPD_MODS_ENABLED_DIR'}/fcgid_imscp.load") {
		my $rs = $self->{'httpd'}->disableModules('fcgid_imscp');
		return $rs if $rs;
	}

	for my $conffile('fcgid_imscp.conf', 'fcgid_imscp.load') {
		if(-f "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$conffile") {
			my $rs = iMSCP::File->new(
				filename => "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$conffile"
			)->delFile();
			return $rs if $rs;
		}
	}

	0;
}

sub _vHostConf
{
	my $self = shift;

	if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_nameserver.conf") {
		my $rs = $self->{'httpd'}->disableSites('00_nameserver.conf');
		return $rs if $rs;

		$rs = iMSCP::File->new(
			filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_nameserver.conf"
		)->delFile();
		return $rs if $rs;
	}

	my $confDir = (-d "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available")
		? "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available" : "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d";

    if(-f "$confDir/00_imscp.conf") {
		my $rs = $self->{'httpd'}->disableConfs('00_imscp.conf');
		return $rs if $rs;

		$rs = iMSCP::File->new( filename => "$confDir/00_imscp.conf" )->delFile();
		return $rs if $rs;
	}

	for my $site('000-default', 'default') {
		if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_") {
			my $rs = $self->{'httpd'}->enableSites($site);
			return $rs if $rs;
		}
	}

	0;
}

1;
__END__
