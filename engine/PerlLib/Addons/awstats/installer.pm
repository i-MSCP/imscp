#!/usr/bin/perl

=head1 NAME

Addons::awstats::installer - i-MSCP Awstats addon installer

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
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Addons::awstats::installer;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Templator;
use iMSCP::Dir;
use iMSCP::File;
use Servers::httpd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Awstats addon installer.

 See Addons::awstats for more information.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks(HooksManager)

 Register setup hook functions

 Param iMSCP::HooksManager instance
 Return int 0 on success, 1 on failure

=cut

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	my $rs = 0;

	# Register add awstats dialog in setup dialog stack to show awstats addon questions on install
	$rs = $hooksManager->register(
		'beforeSetupDialog', sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askAwstats(@_) }); 0; }
	);
	return $rs if $rs;

	# Register installLogrotate filter hook function to process logrotate awstats section on install
	$hooksManager->register('beforeHttpdBuildConf', sub { $self->installLogrotate(@_); });
}

=item install()

 Process install tasks.

 Return int 0 on success, 1 on failure

=cut

sub install
{
	my $self = shift;
	my $rs = 0;

	$self->{'httpd'} = Servers::httpd->factory() unless $self->{'httpd'} ;

	$self->{'user'} = $self->{'httpd'}->can('getRunningUser')
		? $self->{'httpd'}->getRunningUser() : $main::imscpConfig{'ROOT_USER'};

	$self->{'group'} = $self->{'httpd'}->can('getRunningUser') ?
		$self->{'httpd'}->getRunningGroup() : $main::imscpConfig{'ROOT_GROUP'};

	if (main::setupGetQuestion('AWSTATS_ACTIVE') =~ /^yes$/i) {
		$rs = $self->_makeCacheDir();
		return $rs if $rs;

		$rs = $self->_createVhost();
		return $rs if $rs;
	}

	$rs = $self->_disableDefaultConfig();
	return $rs if $rs;

	$self->_disableDefaultCronTask();
}

=back

=head1 HOOK FUNCTIONS

=over 4

=item installLogrotate(\$content, $filename)

 Add or remove awstats logrotate section in the Apache logrotate file.

 Filter hook function responsible to add or remove the logrotate awstats section in the Apache logrotate file. If the
file received is not the one expected, this function will auto-register itself to act on the next file.

 Param SCALAR reference - A reference to a scalar containing file content
 Param Param SCALAR Filename
 Return int 0 on success, 1 on failure

=cut

sub installLogrotate
{
	my $self = shift;
	my $content = shift;
	my $filename = shift;

	if ($filename eq 'logrotate.conf') {
		$$content = replaceBloc(
			'# SECTION awstats BEGIN.',
			'# SECTION awstats END.',
			(
				main::setupGetQuestion('AWSTATS_ACTIVE') =~ /^yes$/i
				?
				"\tprerotate\n".
				"\t\t$main::imscpConfig{'AWSTATS_ROOT_DIR'}\/awstats_updateall.pl ".
				"now -awstatsprog=$main::imscpConfig{'AWSTATS_ENGINE_DIR'}\/awstats.pl &> \/dev\/null\n".
				"\tendscript"
				:
				''
			),
			$$content
		);
	} else {
		my $rs = iMSCP::HooksManager->getInstance()->register('beforeHttpdBuildConf', sub { return $self->installLogrotate(@_); });
		return $rs if $rs;
	}

	0;
}

=item askAwstats()

 Show awstats installer questions.

 Hook function responsible to show awstats installer questions.

 Return int 0 or 30

=cut

sub askAwstats
{
	my ($self, $dialog, $rs) = (shift, shift, 0);
	my $awstatsActive = main::setupGetQuestion('AWSTATS_ACTIVE');
	my $awstatsMode =  main::setupGetQuestion('AWSTATS_MODE');

	$awstatsActive = lc($awstatsActive);

	if(
		$main::reconfigure ~~ ['webstats', 'all', 'forced'] || $awstatsActive !~ /^yes|no$/ ||
		($awstatsActive eq 'yes' && $awstatsMode !~ /^0|1$/) || ($awstatsActive eq 'no' && $awstatsMode ne '')
	) {
		($rs, $awstatsActive)  = $dialog->radiolist(
"
\\Z4\\Zb\\Zui-MSCP Awstats Addon\\Zn

Do you want activate the Awstats addon?
",
			['yes', 'no'],
			$awstatsActive ne 'yes' ? 'no' : 'yes'
		);

		if($rs != 30) {
			if($awstatsActive eq 'yes') {
           		($rs, $awstatsMode) = $dialog->radiolist(
           			"\nPlease, select the Awstats mode you want use:",
           			['dynamic', 'static'],
           			$awstatsMode ? 'static' : 'dynamic'
           		);

				$awstatsMode = $awstatsMode eq 'dynamic' ? 0 : 1 if $rs != 30;
       		} else {
        		$awstatsMode = '';
        	}
        }
	}

    if($rs != 30) {
    	$main::questions{'AWSTATS_ACTIVE'} = $awstatsActive;
    	$main::questions{'AWSTATS_MODE'} = $awstatsMode;
    }

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _makeCacheDir()

 Create awstats cache directory.

 Return int 0 on success, 1 on failure

=cut

sub _makeCacheDir
{
	my $self = shift;


	iMSCP::Dir->new(
		'dirname' => $main::imscpConfig{'AWSTATS_CACHE_DIR'}
	)->make(
		{ 'user' => $self->{'user'},'group' => $self->{'group'}, 'mode' => 0755 }
	);
}

=item _createVhost()

 Create and install global awstats Apache vhost file.

 Return int 0 on success, 1 on failure

=cut

sub _createVhost {

	my $self = shift;
	my $rs = 0;

	my $httpd = Servers::httpd->factory();

	$httpd->setData(
		{
			AWSTATS_ENGINE_DIR => $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
			AWSTATS_WEB_DIR => $main::imscpConfig{'AWSTATS_WEB_DIR'}
		}
	);

	if($httpd->can('buildConfFile')){
		$rs = $httpd->buildConfFile('01_awstats.conf');
		return $rs if $rs;
	}

	if($httpd->can('installConfFile')){
		$rs = $httpd->installConfFile('01_awstats.conf');
		return $rs if $rs;
	}

	if($httpd->can('enableSite')){
		$rs = $httpd->enableSite('01_awstats.conf');
		return $rs if $rs;
	}

	0;
}

=item _disableDefaultConfig()

 Disable default awstats configuration file provided by awstats Debian package.

 Return int 0 on success, 1 on failure

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

 Disable default awstats cron task provided by awstats Debian package.

 Return int 0 on success, 1 on failure

=cut

sub _disableDefaultCronTask
{
	my $self = shift;
	my $rs = 0;

	if(-f "$main::imscpConfig{'CRON_D_DIR'}/awstats") {
		iMSCP::File->new(
			'filename' => "$main::imscpConfig{'CRON_D_DIR'}/awstats"
		)->moveFile(
			"$main::imscpConfig{'CONF_DIR'}/cron.d/backup/awstats.system"
		);
		return $rs if $rs;
	}

	0;
}

=back

=head1 AUTHORS

 - Daniel Andreca <sci2tech@gmail.com>
 - Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
