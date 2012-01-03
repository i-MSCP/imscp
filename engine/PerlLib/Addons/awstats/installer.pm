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

package Addons::awstats::installer;

use strict;
use warnings;
use iMSCP::Debug;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;


sub askAwstats{

	use iMSCP::Dialog;

	my ($rs, $force);

	if(!$main::imscpConfig{'AWSTATS_ACTIVE'}){
		if($main::imscpConfigOld{'AWSTATS_ACTIVE'} && $main::imscpConfigOld{'AWSTATS_ACTIVE'} =~ /yes|no/){
			$main::imscpConfig{'AWSTATS_ACTIVE'}	= $main::imscpConfigOld{'AWSTATS_ACTIVE'};
		} else {
			while (! ($rs = iMSCP::Dialog->factory()->radiolist("Do you want to enable Awstats?", 'yes', 'no'))){}
			if($rs ne $main::imscpConfig{'AWSTATS_ACTIVE'}){
				$main::imscpConfig{'AWSTATS_ACTIVE'} = $rs;
				$force = 'yes';
			}
		}
	}

	if($main::imscpConfig{'AWSTATS_ACTIVE'} eq 'yes'){
		if($force){
			while (! ($rs = iMSCP::Dialog->factory()->radiolist("Select Awstats mode?", 'dynamic', 'static'))){}
			$rs = $rs eq 'dynamic' ? 0 : 1;
			$main::imscpConfig{'AWSTATS_MODE'} = $rs;
		}
		if(!defined $main::imscpConfig{'AWSTATS_MODE'} || $main::imscpConfig{'AWSTATS_MODE'} !~ /0|1/){
			if(defined $main::imscpConfigOld{'AWSTATS_MODE'} && $main::imscpConfigOld{'AWSTATS_MODE'} =~ /0|1/){
				$main::imscpConfig{'AWSTATS_MODE'}	= $main::imscpConfigOld{'AWSTATS_MODE'};
			} else {
				while (! ($rs = iMSCP::Dialog->factory()->radiolist("Select Awstats mode?", 'dynamic', 'static'))){}
				$rs = $rs eq 'dynamic' ? 0 : 1;
				$main::imscpConfig{'AWSTATS_MODE'} = $rs;
			}
		}
	} else {
		$main::imscpConfig{'AWSTATS_MODE'} = '' if $main::imscpConfig{'AWSTATS_MODE'} ne '';
	}

	0;
}

sub registerHooks{
	my $self = shift;

	use Servers::httpd;

	my $httpd = Servers::httpd->factory();

	$httpd->registerPreHook(
		'buildConf', sub { return $self->installLogrotate(@_); }
	);

	0;
}

sub install{

	my $self	= shift;
	my $rs		= 0;
	$self->{httpd} = Servers::httpd->factory() unless $self->{httpd} ;

	$self->{user} = $self->{httpd}->can('getRunningUser') ? $self->{httpd}->getRunningUser() : $main::imscpConfig{ROOT_USER};
	$self->{group} = $self->{httpd}->can('getRunningUser') ? $self->{httpd}->getRunningGroup() : $main::imscpConfig{ROOT_GROUP};

	$self->askAwstats() and return 1;
	if ($main::imscpConfig{'AWSTATS_ACTIVE'} eq 'yes') {
		$self->makeDirs() and return 1;
		$self->vhost() and return 1;
	}
	$self->disableConf() and return 1;
	$self->disableCron() and return 1;

	$rs;
}

sub makeDirs{

	use iMSCP::Dir;

	my $self		= shift;

	iMSCP::Dir->new(
		dirname => $main::imscpConfig{'AWSTATS_CACHE_DIR'}
	)->make({
		user => $self->{user},
		group => $self->{group},
		mode => 0755
	}) and return 1;

	0;
}


################################################################################
# i-MSCP awstats vhost - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Built, store and install Awstats vhost configuration file (01_awstats.conf)
#  - Disable default awstats.conf file
#  - Remove default debian cron task for Awstats
#
# @return int 0 on success, other on failure

sub vhost {

	use Servers::httpd;

	my $rs		= 0;
	my $httpd	= Servers::httpd->factory();

	$httpd->setData({
		AWSTATS_ENGINE_DIR	=> $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
		AWSTATS_WEB_DIR		=> $main::imscpConfig{'AWSTATS_WEB_DIR'}
	});

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
sub disableConf{

	use iMSCP::File;

	my $self	= shift;

	if(-f "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf") {
		iMSCP::File->new(
			filename => "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf"
		)->moveFile(
			"$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled"
		) and return 1;
	}

	0;
}

sub disableCron{

	use iMSCP::File;

	my $self	= shift;

	# Removing default Debian Package cron task for awstats
	if(-f "$main::imscpConfig{'CRON_D_DIR'}/awstats") {
		iMSCP::File->new(
			filename => "$main::imscpConfig{'CRON_D_DIR'}/awstats"
		)->moveFile(
			"$main::imscpConfig{'CONF_DIR'}/cron.d/backup/awstats.system"
		) and return 1;
	}

	0;
}

sub installLogrotate{

	use iMSCP::Templator;

	my $self	= shift;
	my $content	= shift || '';
	my $file	= shift || '';

	if ($file eq 'logrotate.conf') {
		$content = replaceBloc(
			'# AWSTATS SECTION BEGIN',
			'# AWSTATS SECTION END',
			(
				$main::imscpConfig{'AWSTATS_ACTIVE'} eq 'yes'
				?
				"\tprerotate\n".
				"\t\t$main::imscpConfig{'AWSTATS_ROOT_DIR'}\/awstats_updateall.pl ".
				"now -awstatsprog=$main::imscpConfig{'AWSTATS_ENGINE_DIR'}\/awstats.pl &> \/dev\/null\n".
				"\tendscript"
				:
				''
			),
			$content,
			undef
		);
	} else {
		# Not file we expect, register again
		my $httpd = Servers::httpd->factory();

		$httpd->registerPreHook(
			'buildConf', sub { return $self->installLogrotate(@_); }
		);
	}

	$content;
}

1;
