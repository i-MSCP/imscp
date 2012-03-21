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

package Addons::policyd::installer;

use strict;
use warnings;
use iMSCP::Debug;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{

	my $self		= shift;
	$self->{cfgDir}	= "$main::imscpConfig{'CONF_DIR'}/policyd";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";

	my $conf		= "$self->{cfgDir}/policyd.data";
	my $oldConf		= "$self->{cfgDir}/policyd.old.data";

	tie %self::policydConfig, 'iMSCP::Config','fileName' => $conf, noerrors => 1;
	tie %self::policydOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1 if -f $oldConf;

	0;
}

sub install{

	my $self	= shift;
	my $rs		= 0;

	$rs |= $self->bkpConfFile($self::policydConfig{'POLICYD_CONF_FILE'});
	$rs |= $self->askRBL();
	$rs |= $self->buildConf();
	$rs |= $self->saveConf();

	$rs;
}

sub saveConf{

	use iMSCP::File;

	my $self	= shift;
	my $rootUsr	= $main::imscpConfig{'ROOT_USER'};
	my $rootGrp	= $main::imscpConfig{'ROOT_GROUP'};
	my $rs		= 0;

	my $file	= iMSCP::File->new(filename => "$self->{cfgDir}/policyd.data");
	my $cfg		= $file->get();
	return 1 unless $cfg;
	$rs			|= $file->mode(0640);
	$rs			|= $file->owner($rootUsr, $rootGrp);

	$file	= iMSCP::File->new(filename => "$self->{cfgDir}/policyd.old.data");
	$rs		|= $file->set($cfg);
	$rs		|= $file->save();
	$rs		|= $file->mode(0640);
	$rs		|= $file->owner($rootUsr, $rootGrp);

	$rs;
}

sub bkpConfFile{

	use File::Basename;

	my $self		= shift;
	my $cfgFile		= shift;
	my $timestamp	= time;

	my ($name,$path,$suffix) = fileparse($cfgFile);

	if(-f $cfgFile){
		my $file	= iMSCP::File->new(filename => $cfgFile);
		$file->copyFile("$self->{bkpDir}/$name$suffix.$timestamp") and return 1;
	}

	0;
}

sub askRBL{

	use iMSCP::Dialog;

	my $rs;

	if(!defined $self::policydConfig{'DNSBL_CHECKS_ONLY'} || $self::policydConfig{'DNSBL_CHECKS_ONLY'} !~ /0|1/){
		if(defined $self::policydOldConfig{'DNSBL_CHECKS_ONLY'} && $self::policydOldConfig{'DNSBL_CHECKS_ONLY'} =~ /0|1/){
			$self::policydConfig{'DNSBL_CHECKS_ONLY'} = $self::policydOldConfig{'DNSBL_CHECKS_ONLY'};
		} else {
			while (! ($rs = iMSCP::Dialog->factory()->radiolist(
				"Do you want to disable additional checks for MTA, HELO and domain?\n\n".
				"YES (may cause some spam messages to be accepted).\n\n".
				"NO (default, some misconfigured mail service providers\n\t\t\twill be treat as spam and messages will be rejected).\n",
				'No',
				'Yes'
			))){}
			$rs = $rs eq 'No' ? 0 : 1;
			$self::policydConfig{'DNSBL_CHECKS_ONLY'} = $rs;
		}
	}

	0;
}

sub buildConf{

	use iMSCP::Execute;
	use File::Basename;

	my $self		= shift;
	my $rs			= 0;
	my $uName		= $self::policydConfig{'POLICYD_USER'};
	my $gName		= $self::policydConfig{'POLICYD_GROUP'};

	my ($name,$path,$suffix) = fileparse($self::policydConfig{'POLICYD_CONF_FILE'});

	unless (-f $self::policydConfig{'POLICYD_CONF_FILE'}){
		my ($stdout, $stderr);
		$rs |= execute("$self::policydConfig{POLICYD_BIN_FILE} defaults > $self::policydConfig{POLICYD_CONF_FILE}", \$stdout, \$stderr);
		debug("$stdout") if $stdout;
		warning("$stderr") if !$rs && $stderr;
		error("$stderr") if $rs && $stderr;
		error("Can not create default config file") if $rs && !$stderr;
		return $rs if $rs;
	}

	my $file	= iMSCP::File->new(filename => $self::policydConfig{POLICYD_CONF_FILE});
	my $cfgTpl	= $file->get();
	return 1 unless $cfgTpl;

	$cfgTpl =~ s/^\s{0,}\$dnsbl_checks_only\s{0,}=.*$/\n   \$dnsbl_checks_only = $self::policydConfig{DNSBL_CHECKS_ONLY};          # 1: ON, 0: OFF (default)/mi;

	$file = iMSCP::File->new(filename => "$self->{wrkDir}/$name$suffix");
	$rs |= $file->set($cfgTpl);
	$rs |= $file->save();
	$rs |= $file->mode(0640);
	$rs |= $file->owner($uName, $gName);
	$rs |= $file->copyFile($self::policydConfig{POLICYD_CONF_FILE});

	$rs;
}


1;
