=head1 NAME

Package::Webstats::Awstats::Installer - i-MSCP AWStats package installer

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

package Package::Webstats::Awstats::Installer;

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

 AWStats package installer.

 See Package::Webstats::Awstats::Awstats for more information.

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
			"\nPlease select the AWStats mode you want use:", ['Dynamic', 'Static'], $awstatsMode ? 'Static' : 'Dynamic'
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
		"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats/Scripts/awstats_buildstaticpages.pl",
		{
			'user' => $main::imscpConfig{'ROOT_USER'},
			'group' => $main::imscpConfig{'ROOT_USER'},
			'mode' => '0700'
		}
	);

	$rs = setRights(
		"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats/Scripts/awstats_updateall.pl",
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

 Return Package::Webstats::Awstats::Installer

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
	iMSCP::Dir->new(
		dirname => $main::imscpConfig{'AWSTATS_CACHE_DIR'}
	)->make(
		{ 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $_[0]->{'httpd'}->getRunningGroup(), 'mode' => 02750 }
	);
}

=item _createGlobalAwstatsVhost()

 Create global vhost file

 Return int 0 on success, other on failure

=cut

sub _createGlobalAwstatsVhost
{
	my $self = $_[0];

	my $version = $self->{'httpd'}->{'config'}->{'HTTPD_VERSION'};;
	my $apache24 = (version->parse($version) >= version->parse('2.4.0'));

	$self->{'httpd'}->setData(
		{
			NAMEVIRTUALHOST => ($apache24) ? '' : 'NameVirtualHost 127.0.0.1:80',
			AWSTATS_ENGINE_DIR => $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
			AWSTATS_WEB_DIR => $main::imscpConfig{'AWSTATS_WEB_DIR'},
			AUTHZ_ALLOW_ALL => ($apache24) ? 'Require all granted' : 'Allow from all'
		}
	);

	my $rs = $self->{'httpd'}->buildConfFile(
		"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats/Config/01_awstats.conf"
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->installConfFile('01_awstats.conf');
	return $rs if $rs;

	$self->{'httpd'}->enableSites('01_awstats.conf');
}

=item _disableDefaultConfig()

 Disable default configuration

 Return int 0 on success, other on failure

=cut

sub _disableDefaultConfig
{
	my $rs = 0;

	if(-f "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf") {
		$rs = iMSCP::File->new(
			filename => "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf"
		)->moveFile(
			"$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled"
		);
		return $rs if $rs;
	}

	my $cronDir = Servers::cron->factory()->{'config'}->{'CRON_D_DIR'};
	if(-f "$cronDir/awstats") {
		$rs = iMSCP::File->new( filename => "$cronDir/awstats" )->moveFile("$cronDir/awstats.disable");
	}

	$rs;
}

=item _addAwstatsCronTask()

 Add AWStats cron task for dynamic mode

 Return int 0 on success, other on failure

=cut

sub _addAwstatsCronTask
{
	Servers::cron->factory()->addTask(
		{
			TASKID => 'Package::Webstats::Awstats',
			MINUTE => '15',
			HOUR => '3-21/6',
			DAY => '*',
			MONTH => '*',
			DWEEK => '*',
			USER => $main::imscpConfig{'ROOT_USER'},
			COMMAND => 'nice -n 15 ionice -c2 -n5 perl ' .
				"$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/Webstats/Awstats/Scripts/awstats_updateall.pl now " .
				"-awstatsprog=$main::imscpConfig{'AWSTATS_ENGINE_DIR'}/awstats.pl > /dev/null 2>&1"
		}
	);
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
