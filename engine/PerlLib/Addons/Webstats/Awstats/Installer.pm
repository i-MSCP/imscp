#!/usr/bin/perl

=head1 NAME

Addons::Webstats::Awstats::Installer - i-MSCP AWStats addon installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::Webstats::Awstats::Installer;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::TemplateParser;
use iMSCP::Dir;
use iMSCP::File;
use Servers::httpd;
use Servers::cron;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 AWStats addon installer.

 See Addons::Webstats::Awstats::Awstats for more information.

=head1 PUBLIC METHODS

=over 4

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
	my (undef, $dialog) =  @_;

	my $rs = 0;
	my $awstatsMode = main::setupGetQuestion('AWSTATS_MODE');

	if($main::reconfigure ~~ ['webstats', 'all', 'forced'] || not $awstatsMode ~~ ['0','1']) {
		($rs, $awstatsMode) = $dialog->radiolist(
			"\nPlease, select the AWStats mode you want use:", ['Dynamic', 'Static'], $awstatsMode ? 'Static' : 'Dynamic'
		);

		$awstatsMode = $awstatsMode eq 'Dynamic' ? 0 : 1 if $rs != 30;
	}

	main::setupSetQuestion('AWSTATS_MODE', $awstatsMode) if $rs != 30;

	$rs;
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->_disableDefaultConfig();
	return $rs if $rs;

	$rs = $self->_createCacheDir();
	return $rs if $rs;

	$rs = $self->_createGlobalAwstatsVhost();
	return $rs if $rs;

	if(main::setupGetQuestion('AWSTATS_MODE') eq '0') {
		# Add cron task for dynamic mode
		$rs = $self->_addAwstatsCronTask();
	}

	$rs;
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	require iMSCP::Rights;
	iMSCP::Rights->import();

	my $rs = setRights(
		"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Addons/Webstats/Awstats/Scripts/awstats_buildstaticpages.pl",
		{
			'user' => $main::imscpConfig{'ROOT_USER'},
			'group' => $main::imscpConfig{'ROOT_USER'},
			'mode' => '0700'
		}
	);

	$rs = setRights(
		"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Addons/Webstats/Awstats/Scripts/awstats_updateall.pl",
		{
			'user' => $main::imscpConfig{'ROOT_USER'},
			'group' => $main::imscpConfig{'ROOT_USER'},
			'mode' => '0700'
		}
	);

	$rs = setRights(
		$main::imscpConfig{'AWSTATS_CACHE_DIR'},
		{
			'user' => $main::imscpConfig{'ROOT_USER'},
			'group' => Servers::httpd->factory()->getRunningGroup(),
			'dirmode' => '02750',
			'filemode' => '0640',
			'recursive' => 1
		}
	);

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Addons::Webstats::Awstats::Installer

=cut

sub _init
{
	my $self = $_[0];

	$self->{'httpd'} = Servers::httpd->factory();

	$self;
}

=item _createCacheDir()

 Create cache directory

 Return int 0 on success, other on failure

=cut

sub _createCacheDir
{
	my $self = $_[0];

	iMSCP::Dir->new(
		'dirname' => $main::imscpConfig{'AWSTATS_CACHE_DIR'}
	)->make(
		{ 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $self->{'httpd'}->getRunningGroup(), 'mode' => 02750 }
	);
}

=item _createGlobalAwstatsVhost()

 Create global vhost file

 Return int 0 on success, other on failure

=cut

sub _createGlobalAwstatsVhost
{
	my $self = $_[0];

	my $apache24 = (qv("v$self->{'httpd'}->{'config'}->{'APACHE_VERSION'}") >= qv('v2.4.0'));

	$self->{'httpd'}->setData(
		{
			NAMEVIRTUALHOST => $apache24 ? '' : 'NameVirtualHost 127.0.0.1:80',
			AWSTATS_ENGINE_DIR => $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
			AWSTATS_WEB_DIR => $main::imscpConfig{'AWSTATS_WEB_DIR'},
			WEBSTATS_RPATH => $main::imscpConfig{'WEBSTATS_RPATH'},
			AUTHZ_ALLOW_ALL => $apache24 ? 'Require all granted' : 'Allow from all'
		}
	);

	my $rs = $self->{'httpd'}->buildConfFile(
		"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Addons/Webstats/Awstats/Config/01_awstats.conf", { }
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile('01_awstats.conf');
	return $rs if $rs;

	$self->{'httpd'}->enableSite('01_awstats.conf');
}

=item _disableDefaultConfig()

 Disable default cron task and configuration file

 Return int 0 on success, other on failure

=cut

sub _disableDefaultConfig
{
	my $rs = 0;

	if(-f "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf") {
		$rs = iMSCP::File->new(
			'filename' => "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf"
		)->moveFile(
			"$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled"
		);
		return $rs if $rs;
	}

	if(-f "$main::imscpConfig{'CRON_D_DIR'}/awstats") {
		$rs = iMSCP::File->new(
			'filename' => "$main::imscpConfig{'CRON_D_DIR'}/awstats"
		)->moveFile(
			"$main::imscpConfig{'CRON_D_DIR'}/awstats.disable"
		);
	}

	$rs;
}

=item _addAwstatsCronTask()

 Add cron task for dynamic mode

 Return int 0 on success, other on failure

=cut

sub _addAwstatsCronTask
{
	my $self = $_[0];

	Servers::cron->factory()->addTask(
		{
			TASKID => 'Addons::Webstats::Awstats',
			MINUTE => '15',
			HOUR => '3-21/6',
			DAY => '*',
			MONTH => '*',
			DWEEK => '*',
			USER => $main::imscpConfig{'ROOT_USER'},
			COMMAND => "$main::imscpConfig{'CMD_PERL'} " .
				"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Addons/Webstats/Awstats/Scripts/awstats_updateall.pl now " .
				"-awstatsprog=$main::imscpConfig{'AWSTATS_ENGINE_DIR'}/awstats.pl >/dev/null 2>&1"
		}
	);
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
