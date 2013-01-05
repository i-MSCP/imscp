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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::cron;

use strict;
use warnings;
use iMSCP::Debug;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	iMSCP::HooksManager->getInstance()->trigger('beforeCronInit', $self, 'cron');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/cron.d";
	$self->{'bkpDir'} = "$self->{cfgDir}/backup";
	$self->{'wrkDir'} = "$self->{cfgDir}/working";
	$self->{'tplDir'} = "$self->{cfgDir}/parts";

	iMSCP::HooksManager->getInstance()->trigger('afterCronInit', $self, 'cron');

	$self;
}

sub factory
{
	Servers::cron->new();
}

sub addTask
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	use iMSCP::File;
	use iMSCP::Templator;

	$data = {} if (ref $data ne 'HASH');

	my $errmsg = {
		USER	=> 'You must provide running user!',
		C0MMAND	=> 'You must provide cron command!',
		TASKID	=> 'You must provide a unique task id!',
	};

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	$data->{'MINUTE'} = 1 unless exists $data->{'MINUTE'};
	$data->{'HOUR'} = 1 unless exists $data->{'HOUR'};
	$data->{'DAY'} = 1 unless exists $data->{'DAY'};
	$data->{'MONTH'} = 1 unless exists $data->{'MONTH'};
	$data->{'DWEEK'} = 1 unless exists $data->{'DWEEK'};
	$data->{'LOG_DIR'} = $main::imscpConfig{'LOG_DIR'};

	# Backup production file
	$rs |=	iMSCP::File->new(
		filename => "$main::imscpConfig{CRON_D_DIR}/imscp"
	)->copyFile(
		"$self->{bkpDir}/imscp." . time
	) if(-f "$main::imscpConfig{CRON_D_DIR}/imscp");

	my $file = iMSCP::File->new(filename => "$self->{wrkDir}/imscp");
	my $wrkFileContent = $file->get();

	unless($wrkFileContent){
		error("Can not read $self->{wrkDir}/imscp");
		$rs = 1;
	} else {
		my $cleanBTag = iMSCP::File->new(filename => "$self->{tplDir}/task_b.tpl")->get();
		my $cleanTag = iMSCP::File->new(filename => "$self->{tplDir}/task_entry.tpl")->get();
		my $cleanETag = iMSCP::File->new(filename => "$self->{tplDir}/task_e.tpl")->get();
		my $bTag = process({TASKID => $data->{'TASKID'}}, $cleanBTag);
		my $eTag = process({TASKID => $data->{'TASKID'}}, $cleanETag);
		my $tag = process($data, $cleanTag);

		$wrkFileContent = replaceBloc($bTag, $eTag, '', $wrkFileContent, undef);
		$wrkFileContent = replaceBloc($cleanBTag, $cleanETag, "$bTag$tag$eTag", $wrkFileContent, 'keep');

		# Store the file in the working directory
		my $file = iMSCP::File->new(filename =>"$self->{wrkDir}/imscp");
		$rs |= $file->set($wrkFileContent);
		$rs |= $file->save();
		$rs |= $file->mode(0644);
		$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

		# Install the file in the production directory
		$rs |= $file->copyFile("$main::imscpConfig{CRON_D_DIR}/imscp");
	}

	$rs;
}

sub delTask
{
	my $self = shift;
	my $data = shift;
	my $rs;

	use iMSCP::File;
	use iMSCP::Templator;

	$data = {} if (ref $data ne 'HASH');

	my $errmsg = {
		TASKID	=> 'You must provide a unique task id!'
	};

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	# BACKUP PRODUCTION FILE
	$rs |= iMSCP::File->new(
		filename => "$main::imscpConfig{CRON_D_DIR}/imscp"
	)->copyFile(
		"$self->{bkpDir}/imscp." . time
	) if(-f "$main::imscpConfig{CRON_D_DIR}/imscp");

	my $file = iMSCP::File->new(filename => "$self->{wrkDir}/imscp");
	my $wrkFileContent = $file->get();

	unless($wrkFileContent){
		error("Can not read $self->{wrkDir}/imscp");
		$rs = 1;
	} else {
		my $cleanBTag = iMSCP::File->new(filename => "$self->{tplDir}/task_b.tpl")->get();
		my $cleanETag = iMSCP::File->new(filename => "$self->{tplDir}/task_e.tpl")->get();
		my $bTag = process({TASKID => $data->{'TASKID'}}, $cleanBTag);
		my $eTag = process({TASKID => $data->{'TASKID'}}, $cleanETag);

		$wrkFileContent = replaceBloc($bTag, $eTag, '', $wrkFileContent, undef);

		# Store the file in the working directory
		my $file = iMSCP::File->new(filename =>"$self->{wrkDir}/imscp");
		$rs |= $file->set($wrkFileContent);
		$rs |= $file->save();
		$rs |= $file->mode(0644);
		$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

		# Install the file in the production directory
		$rs |= $file->copyFile("$main::imscpConfig{'CRON_D_DIR'}/imscp");
	}

	$rs;
}

1;
