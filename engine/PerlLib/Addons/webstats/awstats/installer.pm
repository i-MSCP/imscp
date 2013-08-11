#!/usr/bin/perl

=head1 NAME

Addons::webstats::awstats::installer - i-MSCP Awstats addon installer

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

package Addons::webstats::awstats::installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Templator;
use iMSCP::Dir;
use iMSCP::File;
use Servers::httpd;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Awstats addon installer.

 See Addons::webstats::awstats::awstats for more information.

=head1 PUBLIC METHODS

=over 4

=item askAwstats()

 Show installer questions.

 Return int 0 or 30

=cut

sub askAwstats
{
	my ($self, $dialog, $rs) = (shift, shift, 0);
	my $awstatsMode =  main::setupGetQuestion('AWSTATS_MODE');

	if($main::reconfigure ~~ ['webstats', 'all', 'forced'] || $awstatsMode !~ /^0|1$/) {
		($rs, $awstatsMode) = $dialog->radiolist(
			"\nPlease, select the Awstats mode you want use:",
			['Dynamic', 'Static'],
			$awstatsMode ? 'Static' : 'Dynamic'
		);

		$awstatsMode = $awstatsMode eq 'Dynamic' ? 0 : 1 if $rs != 30;
	}

	main::setupSetQuestion('AWSTATS_MODE', $awstatsMode) if $rs != 30;

	$rs;
}

=item preinstall()

 Process preinstall tasks.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	# Register the _installLogrotate() filter hook function to add the Awstats logrotate configuration snippet in the
	# Apache logrotate file
	iMSCP::HooksManager->getInstance()->register('beforeHttpdBuildConf', sub { $self->_installLogrotate(@_); });
}

=item install()

 Process install tasks.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;
	my $rs = 0;

	if (main::setupGetQuestion('WEBSTATS_ADDON') eq 'Awstats') {
		$rs = $self->_makeCacheDir();
		return $rs if $rs;

		$rs = $self->_createGlobalAwstatsVhost();
		return $rs if $rs;
	} else {
		$rs = $self->_removeGlobalAwstatsVhost();
		return $rs if $rs;
	}

	$rs = $self->_disableDefaultConfig();
	return $rs if $rs;

	$self->_disableDefaultCronTask();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _installLogrotate(\$content, $filename)

 Add or remove Awstats logrotate configuration snippet in the Apache logrotate file.

 Filter hook function responsible to add or remove the Awstats logrotate configuration snippet in the Apache logrotate
file. If the file received is not the one expected, this function will auto-register itself to act on the next file.

 Param SCALAR reference - A reference to a scalar containing file content
 Param Param SCALAR Filename
 Return int 0 on success, other on failure

=cut

sub _installLogrotate
{
	my $self = shift;
	my $content = shift;
	my $filename = shift;

	if ($filename eq 'logrotate.conf') {
		$$content = replaceBloc(
			"# SECTION awstats BEGIN.\n",
			"# SECTION awstats END.\n",
			(
				main::setupGetQuestion('WEBSTATS_ADDON') eq 'Awstats'
				?
					"\tprerotate\n".
					"\t\t$main::imscpConfig{'AWSTATS_ROOT_DIR'}/awstats_updateall.pl now " .
					"-awstatsprog=$main::imscpConfig{'AWSTATS_ENGINE_DIR'}/awstats.pl &> /dev/null\n" .
					"\tendscript\n"
				:
					''
			),
			$$content
		);
	} else {
		my $rs = iMSCP::HooksManager->getInstance()->register(
			'beforeHttpdBuildConf', sub { return $self->_installLogrotate(@_); }
		);
		return $rs if $rs;
	}

	0;
}

=item _makeCacheDir()

 Create cache directory for Awstats.

 Return int 0 on success, other on failure

=cut

sub _makeCacheDir
{
	my $self = shift;

	my $httpd = Servers::httpd->factory();

	iMSCP::Dir->new(
		'dirname' => $main::imscpConfig{'AWSTATS_CACHE_DIR'}
	)->make(
		{ 'user' => $httpd->getRunningUser(), 'group' => $httpd->getRunningGroup(), 'mode' => 0750 }
	);
}

=item _createGlobalAwstatsVhost()

 Create and install global awstats Apache vhost file.

 Return int 0 on success, other on failure

=cut

sub _createGlobalAwstatsVhost
{
	my $self = shift;
	my $rs = 0;

	my $httpd = Servers::httpd->factory();
	my $apache24 = (version->new("v$httpd->{'config'}->{'APACHE_VERSION'}") >= version->new('v2.4.0'));

	$httpd->setData(
		{
			AWSTATS_ENGINE_DIR => $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
			AWSTATS_WEB_DIR => $main::imscpConfig{'AWSTATS_WEB_DIR'},
			WEBSTATS_RPATH => $main::imscpConfig{'WEBSTATS_RPATH'},
			AUTHZ_ALLOW_ALL => $apache24 ? 'Require all granted' : "Order allow,deny\n    Allow from all",
			AUTHZ_DENY_ALL => $apache24 ? 'Require all denied' : "Order deny,allow\n    Deny from all"
		}
	);

	if($apache24) {
		$rs = iMSCP::HooksManager->getInstance()->register(
			'beforeHttpdBuildConfFile', sub { my $content = shift; $$content =~ s/NameVirtualHost[^\n]+\n//gi; 0; }
		);
		return $rs if $rs;
	}

	$rs = $httpd->buildConfFile('01_awstats.conf');
	return $rs if $rs;

	$rs = $httpd->installConfFile('01_awstats.conf');
	return $rs if $rs;

	$httpd->enableSite('01_awstats.conf');
}

=item _removeGlobalAwstatsVhost()

 Disable and remove global Apache vhost file for Awstats.

 Return int 0 on success, other on failure

=cut

sub _removeGlobalAwstatsVhost
{
	my $self = shift;

	my $httpd = Servers::httpd->factory();
	my $apacheSiteDir = $httpd->{'config'}->{'APACHE_SITES_DIR'};
	my $awstatsVhostFile = '01_awstats.conf';
	my $rs = 0;

	if (-f "$apacheSiteDir/$awstatsVhostFile") {
		$rs = $httpd->disableSite('01_awstats.conf');
		return $rs if $rs;

		$rs = iMSCP::File->new('filename' => "$apacheSiteDir/$awstatsVhostFile")->delFile();
	}

	$rs;
}

=item _disableDefaultConfig()

 Disable default Awstats configuration file provided by Awstats Debian package.

 Return int 0 on success, other on failure

=cut

sub _disableDefaultConfig
{
	my $self = shift;
	my $rs = 0;

	if(-f "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf") {
		$rs = iMSCP::File->new(
			'filename' => "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf"
		)->moveFile(
			"$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled"
		);
		return $rs if $rs;
	}

	0;
}

=item _disableDefaultCronTask()

 Disable default Awstats cron task provided by Awstats Debian package.

 Return int 0 on success, other on failure

=cut

sub _disableDefaultCronTask
{
	my $self = shift;
	my $rs = 0;

	if(-f "$main::imscpConfig{'CRON_D_DIR'}/awstats") {
		$rs = iMSCP::File->new(
			'filename' => "$main::imscpConfig{'CRON_D_DIR'}/awstats"
		)->moveFile(
			"$main::imscpConfig{'CONF_DIR'}/cron.d/backup/awstats.system"
		);
	}

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
