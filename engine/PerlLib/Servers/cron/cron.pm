#!/usr/bin/perl

=head1 NAME

 Servers::cron::cron - i-MSCP Cron server implementation

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::cron::cron;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::TemplateParser;
use File::Basename;
use Switch;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Cron server implementation.

=head1 PUBLIC METHODS

=over 4

=item addTask(\%data, $filepath)

 Add a new cron task

 Param hash \%data Cron task data:
  - TASKID Cron task unique identifier
  - OPTIONAL MINUTE field: Minute time or shortcut such as @daily, @monthly... ( Default to @daily )
  - OPTIONAL HOUR field: HOUR Hour time ( ignored if the MINUTE field define a shortcut ) ( Default to  emtpy)
  - OPTIONAL DAY field: DAY Day of month date ( ignored if the MINUTE field define a shortcut ) ( Default to  emtpy)
  - OPTIONAL MONTH field: MONTH Month date ( ignored if the MINUTE field define a shortcut ) ( Default to emtpy)
  - OPTIONAL DWEEK field: DWEEK Day of week date ( ignored if the MINUTE field define a shortcut ) ( Default to  emtpy)
  - USER user under which the command must be run
  - COMMAND Command
  See crontab(5) for more information about allowed values
  Param string $file OPTIONAL Absolute path to cron file (default: imscp cron file)
  Return int 0 on success, other on failure

=cut

sub addTask
{
	my ($self, $data, $file) = @_;

	$data = { } unless ref $data eq 'HASH';

	unless(exists $data->{'COMMAND'} && exists $data->{'TASKID'}) {
		error('Missing command or task ID');
		return 1;
	}

	$file ||= "$main::imscpConfig{'CRON_D_DIR'}/imscp";

	if(-f $file) {
		$data->{'MINUTE'} = '@daily' unless exists $data->{'MINUTE'};
		$data->{'HOUR'} = '' unless exists $data->{'HOUR'};
		$data->{'DAY'} = '' unless exists $data->{'DAY'};
		$data->{'MONTH'} = '' unless exists $data->{'MONTH'};
		$data->{'DWEEK'} = '' unless exists $data->{'DWEEK'};
		$data->{'USER'} = $main::imscpConfig{'ROOT_USER'} unless exists $data->{'USER'};

		# Validate cron task
		eval { $self->_validateCronTask($data) };
		if($@) {
			error("Invalid cron tasks: $@");
			return 1;
		}

		my $filename = fileparse($file);

		my $wrkFile = iMSCP::File->new( filename => $file );

		# Backup current file
		my $rs = $wrkFile->copyFile("$self->{'bkpDir'}/$filename." . time);
		return $rs if $rs;

		# Getting current working file content
		my $wrkFileContent = $wrkFile->get();
		unless(defined $wrkFileContent) {
			error("Unable to read $file");
			return 1;
		}

		$rs = $self->{'eventManager'}->trigger('beforeCronAddTask', \$wrkFileContent, $data);
		return $rs if $rs;

		my $cronEntryBegin = "# imscp [$data->{'TASKID'}] entry BEGIN\n";
		my $cronEntryEnding = "# imscp [$data->{'TASKID'}] entry ENDING\n";

		my $cronEntry = sprintf(
			"%s %s %s %s %s %s %s\n",
			$data->{'MINUTE'}, $data->{'HOUR'}, $data->{'DAY'}, $data->{'MONTH'}, $data->{'DWEEK'}, $data->{'USER'},
			$data->{'COMMAND'}
		);

		$cronEntry =~ s/ +/ /;

		# Remove previous task with same id if any
		$wrkFileContent = replaceBloc($cronEntryBegin, $cronEntryEnding, '', $wrkFileContent);

		# Adding new entry
		$wrkFileContent = replaceBloc(
			"# imscp [{ENTRY_ID}] entry BEGIN\n",
			"# imscp [{ENTRY_ID}] entry ENDING\n",
			"$cronEntryBegin$cronEntry$cronEntryEnding",
			$wrkFileContent,
			'preserve'
		);

		$self->{'eventManager'}->trigger('afterCronAddTask', \$wrkFileContent, $data);

		# Store file in working directory
		my $fileH = iMSCP::File->new( filename => "$self->{'wrkDir'}/$filename" );

		$rs = $fileH->set($wrkFileContent);
		return $rs if $rs;

		$rs = $fileH->save();
		return $rs if $rs;

		$rs = $fileH->mode(0640);
		return $rs if $rs;

		$rs = $fileH->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		# Install file in production directory
		$fileH->copyFile($file);
	} else {
		error("Unable to add cron task: File $file not found.");
		1;
	}
}

=item deleteTask(\%data, $file)

 Delete a cron task

 Param hash \%data Cron task data
  - TASKID Cron task unique identifier
 Param string $file OPTIONAL Absolute path to cron file (default: imscp cron file)
 Return int 0 on success, other on failure

=cut

sub deleteTask
{
	my ($self, $data, $file) = @_;

	$data = { } unless ref $data eq 'HASH';

	unless(exists $data->{'TASKID'}) {
		error('Missing task ID');
		return 1;
	}

	$file ||= "$main::imscpConfig{'CRON_D_DIR'}/imscp";

	if(-f $file) {
		my $filename = fileparse($file);

		my $wrkFile = iMSCP::File->new( filename => $file );

		# Backup current file
		my $rs = $wrkFile->copyFile("$self->{'bkpDir'}/$filename." . time);
		return $rs if $rs;

		# Getting current working file content
		my $wrkFileContent = $wrkFile->get();
		unless(defined $wrkFileContent) {
			error("Unable to read $file}");
			return 1;
		}

		$rs = $self->{'eventManager'}->trigger('beforeCronDelTask', \$wrkFileContent, $data);
		return $rs if $rs;

		$wrkFileContent = replaceBloc(
			"# imscp [$data->{'TASKID'}] entry BEGIN\n",
			"# imscp [$data->{'TASKID'}] entry ENDING\n",
			'',
			$wrkFileContent
		);

		$rs = $self->{'eventManager'}->trigger('afterCronDelTask', \$wrkFileContent, $data);
		return $rs if $rs;

		# Store file in working directory
		my $fileH = iMSCP::File->new( filename => "$self->{'wrkDir'}/$filename" );

		$rs = $fileH->set($wrkFileContent);
		return $rs if $rs;

		$rs = $fileH->save();
		return $rs if $rs;

		$rs = $fileH->mode(0640);
		return $rs if $rs;

		$rs = $fileH->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		# Install file in production directory
		$fileH->copyFile($file);
	} else {
		error("Unable to remove cron task: File $file not found.");
		1;
	}
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	if(-f "$main::imscpConfig{'CRON_D_DIR'}/imscp") {
		require iMSCP::Rights;
		iMSCP::Rights->import();

		setRights(
			"$main::imscpConfig{'CRON_D_DIR'}/imscp",
			{
				'user' => $main::imscpConfig{'ROOT_USER'},
				'group' => $main::imscpConfig{'ROOT_GROUP'},
				'mode' => '0640'
			}
		);
	} else {
		0;
	}
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::cron::cron

=cut

sub _init
{
	my $self = $_[0];

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();

	$self->{'eventManager'}->trigger('beforeCronInit', $self, 'cron') and fatal('cron - beforeCronInit has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/cron.d";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'tplDir'} = "$self->{'cfgDir'}/parts";

	$self->{'eventManager'}->trigger('afterCronInit', $self, 'cron') and fatal('cron - afterCronInit has failed');

	$self;
}

=item _validateCronTask()

 Validate cron task attributes

 Return undef ( die on failure )

=cut

sub _validateCronTask
{
	my ($self, $data) = @_;

	if(
		$data->{'MINUTE'} ~~ ['@reboot', '@yearly', '@annually', '@monthly', '@weekly', '@daily', '@midnight', '@hourly']
	) {
		$data->{'HOUR'} = $data->{'DAY'} = $data->{'MONTH'} = $data->{'MONTH'} = $data->{'DWEEK'} = '';
	} else {
		$self->_validateAttribute('minute', $data->{'MINUTE'});
		$self->_validateAttribute('hour', $data->{'HOUR'});
		$self->_validateAttribute('dmonth', $data->{'DAY'});
		$self->_validateAttribute('month', $data->{'MONTH'});
		$self->_validateAttribute('dweek', $data->{'DWEEK'});
	}

	undef;
}

=item _validateAttribute()

 Validate the given cron task attribute value

 Return undef ( die on failure )

=cut

sub _validateAttribute
{
	my ($self, $name, $value) = @_;

	$name ||= 'undefined';

	die(sprintf("Value for the '%s' cron task attribute cannot be empty", $name)) if $value eq '';

	if($value ne '*') {
		my $pattern = '';
		my $step = '[1-9]?[0-9]';
		my $months = 'jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';
		my $days = 'mon|tue|wed|thu|fri|sat|sun';
		my @namesArr = ();

		switch ($name) {
			case 'minute' { $pattern = '[ ]*(\b[0-5]?[0-9]\b)[ ]*'; }
			case 'hour' { $pattern = '[ ]*(\b[01]?[0-9]\b|\b2[0-3]\b)[ ]*'; }
			case 'dmonth' { $pattern = '[ ]*(\b[01]?[1-9]\b|\b2[0-9]\b|\b3[01]\b)[ ]*'; }
			case 'month' {
				@namesArr = split '|', $months;
				$pattern = "([ ]*(\b[0-1]?[0-9]\b)[ ]*)|([ ]*($months)[ ]*)";
			}
			case 'dweek' {
				@namesArr = split '|', $days;
				$pattern = "([ ]*(\b[0]?[0-7]\b)[ ]*)|([ ]*($days)[ ]*)";
			}
			else { die(sprintf("Unknown '%s' cron task attribute", $name)); }
		}

		my $range = "((($pattern)|(\\*\\/$step)?)|((($pattern)-($pattern))(\\/$step)?))";
		my $longPattern = "$range(,$range)*";

		if ($value !~ /^$longPattern$/i) {
			die(sprintf("Invalid value '%s' given for the '%s' cron task attribute", $value, $name));
		} else {
			my @testArr = split ',', $value;

			for my $testField (@testArr) {
				if ($pattern && $testField =~ /^((($pattern)-($pattern))(\/$step)?)+$/) {
					my @compare = split '-', $testField;
					my @compareSlash = split '/', $compare['1'];

					$compare[1] = $compareSlash[0] if scalar @compareSlash == 2;

					my ($left) = grep { $namesArr[$_] eq lc($compare[0]) } 0..$#namesArr;
					my ($right) = grep { $namesArr[$_] eq lc($compare[1]) } 0..$#namesArr;

					$left = $compare[0] unless $left;
					$right = $compare[1] unless $right;

					if (int($left) > int($right)) {
						die(sprintf("Invalid value '%s' given for the '%s' cron task attribute", $value, $name));
					}
				}
			}
		}
	}

	undef;
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
