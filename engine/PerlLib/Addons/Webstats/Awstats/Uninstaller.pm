#!/usr/bin/perl

=head1 NAME

Addons::Webstats::Awstats::Uninstaller - i-MSCP AWStats addon uninstaller

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

package Addons::Webstats::Awstats::Uninstaller;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use Servers::httpd;
use iMSCP::Ext2Attributes qw(setImmutable clearImmutable isImmutable);
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This is the uninstaller for the i-MSCP Awstats addon

=head1 PUBLIC METHODS

=over 4

=item uninstall()

 Process AWStats addon uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->_deleteFiles();
	return $rs if $rs;

	$rs = $self->_removeVhost();
	return $rs if $rs;

	$self->_restoreDebianConfig();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _deleteFiles()

 Delete any AWStats file created by this addon

 Return int 0 on success other on failure

=cut

sub _deleteFiles
{
	my ($rs, $stdout, $stderr);

	if(-d $main::imscpConfig{'USER_WEB_DIR'}) {
		my @homeDirs = iMSCP::Dir->new('dirname' => $main::imscpConfig{'USER_WEB_DIR'})->getDirs();

		if(@homeDirs) {
			for(@homeDirs) {
				my $isImmutableHomeDir = isImmutable("$main::imscpConfig{'USER_WEB_DIR'}/$_");

				$rs = clearImmutable("$main::imscpConfig{'USER_WEB_DIR'}/$_") if $isImmutableHomeDir;
				return $rs if $rs;

				$rs = iMSCP::Dir->new('dirname' => "$main::imscpConfig{'USER_WEB_DIR'}/$_/statistics")->remove();
				return $rs if $rs;

				$rs = setImmutable("$main::imscpConfig{'USER_WEB_DIR'}/$_") if $isImmutableHomeDir;
				return $rs if $rs;
			}
		}
	}

	# Remove cache directory content
	if(-d $main::imscpConfig{'AWSTATS_CACHE_DIR'}) {
		$rs = execute("$main::imscpConfig{'CMD_RM'} -f $main::imscpConfig{'AWSTATS_CACHE_DIR'}/*",  \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# Remove configuration files created by i-MSCP
	if(-d $main::imscpConfig{'AWSTATS_CONFIG_DIR'}) {
		$rs = execute(
			"$main::imscpConfig{'CMD_RM'} -fR $main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.*.conf",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
	}

	$rs;
}

=item _removeVhost()

 Disable and remove global Apache vhost file for AWStats

 Return int 0 on success, other on failure

=cut

sub _removeVhost
{
	my $httpd = Servers::httpd->factory();
	my $rs = 0;

	if(-f "$httpd->{'apacheWrkDir'}/01_awstats.conf") {
		$rs = iMSCP::File->new('filename' => "$httpd->{'apacheWrkDir'}/01_awstats.conf")->delFile();
		return $rs if $rs;
	}

	if (-f "$httpd->{'config'}->{'APACHE_SITES_DIR'}/01_awstats.conf") {
		$rs = $httpd->disableSite('01_awstats.conf');
		return $rs if $rs;

		$rs = iMSCP::File->new('filename' => "$httpd->{'config'}->{'APACHE_SITES_DIR'}/01_awstats.conf")->delFile();
	}

	$rs;
}

=item _restoreDebianConfig()

 Restore default configuration as provided by the awstats Debian package

 Return int 0 on success, other on failure

=cut

sub _restoreDebianConfig
{
	my $rs = 0;

	if(-f "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled") {
		$rs = iMSCP::File->new(
			'filename' => "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled"
		)->moveFile(
			"$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf"
		);
		return $rs if $rs;
	}

	if(-f "$main::imscpConfig{'CRON_D_DIR'}/awstats.disable") {
		$rs = iMSCP::File->new(
			'filename' => "$main::imscpConfig{'CRON_D_DIR'}/awstats.disable"
		)->moveFile(
			"$main::imscpConfig{'CRON_D_DIR'}/awstats"
		);
	}

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
