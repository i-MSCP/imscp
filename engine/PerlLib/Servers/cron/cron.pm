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
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::cron::cron;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::File;
use iMSCP::TemplateParser;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Cron server implementation.

=head1 PUBLIC METHODS

=over 4

=item addTask(\%data)

 Add a new cron task

 Param hash_ref $data A reference to a hash describing the cron task
  - TASKID Arbitrary string used as unique identifier by i-MSCP for the cron task
  - MINUTE Minute time field
  - HOUR Hour time field
  - DAY Day of month date field
  - MONTH Month date field
  - DWEEK Day of week date field
  - USER user under which the command must be run
  - COMMAND Command

  See crontab(5) for more information about allowed values

  Return int 0 on success, other on failure

=cut

sub addTask($$)
{
	my ($self, $data) = @_;

	$data = {} unless ref $data eq 'HASH';

	if(-f "$self->{'wrkDir'}/imscp") {
		$data->{'MINUTE'} = 1 unless exists $data->{'MINUTE'};
		$data->{'HOUR'} = 1 unless exists $data->{'HOUR'};
		$data->{'DAY'} = 1 unless exists $data->{'DAY'};
		$data->{'MONTH'} = 1 unless exists $data->{'MONTH'};
		$data->{'DWEEK'} = 1 unless exists $data->{'DWEEK'};
		$data->{'USER'} = $main::imscpConfig{'ROOT_USER'} unless exists $data->{'USER'};

		unless(exists $data->{'COMMAND'} && exists $data->{'TASKID'}) {
			error('Missing command or task ID');
			return 1;
		}

		my $wrkFile = iMSCP::File->new('filename' => "$main::imscpConfig{'CRON_D_DIR'}/imscp");

		# Backup current working file
		my $rs = $wrkFile->copyFile("$self->{'bkpDir'}/imscp." . time);
		return $rs if $rs;

		# Getting current working file content
		my $wrkFileContent = $wrkFile->get();
		unless(defined $wrkFileContent){
			error("Unable to read $wrkFile->{'filename'}");
			return 1;
		}

		$rs = $self->{'hooksManager'}->trigger('beforeCronAddTask', \$wrkFileContent, $data);
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

		$self->{'hooksManager'}->trigger('afterCronAddTask', \$wrkFileContent, $data);

		# Store file in working directory
		my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/imscp");

		$rs = $file->set($wrkFileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		# Install file in production directory
		$file->copyFile("$main::imscpConfig{'CRON_D_DIR'}/imscp");
	} else {
		error("File $self->{'wrkDir'}/imscp not found. Please rerun i-MSCP setup.");
		1;
	}
}

=item deleteTask(\%data)

 Delete a cron task

 Param array_ref A reference to a hash containing the TASKID key, which represent the unique identifier of the cron task

 Return int 0 on success, other on failure

=cut

sub deleteTask($$)
{
	my ($self, $data) = @_;

	$data = {} unless ref $data eq 'HASH';

	if(-f "$self->{'wrkDir'}/imscp") {
		unless(exists $data->{'TASKID'}) {
			error('Missing task ID');
			return 1;
		}

		my $wrkFile = iMSCP::File->new('filename' => "$main::imscpConfig{'CRON_D_DIR'}/imscp");

		# Backup current working file
		my $rs = $wrkFile->copyFile("$self->{'bkpDir'}/imscp." . time);
		return $rs if $rs;

		# Getting current working file content
		my $wrkFileContent = $wrkFile->get();
		unless(defined $wrkFileContent){
			error("Unable to read $wrkFile->{'filename'}");
			return 1;
		}

		$rs = $self->{'hooksManager'}->trigger('beforeCronDelTask', \$wrkFileContent, $data);
		return $rs if $rs;

		$wrkFileContent = replaceBloc(
			"# imscp [$data->{'TASKID'}] entry BEGIN\n",
			"# imscp [$data->{'TASKID'}] entry ENDING\n",
			'',
			$wrkFileContent
		);

		$rs = $self->{'hooksManager'}->trigger('afterCronDelTask', \$wrkFileContent, $data);
		return $rs if $rs;

		# Store file in working directory
		my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/imscp");

		$rs = $file->set($wrkFileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		# Install file in production directory
		$file->copyFile("$main::imscpConfig{'CRON_D_DIR'}/imscp");
	} else {
		error("File $self->{'wrkDir'}/imscp not found. Please rerun i-MSCP setup.");
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
				'mode' => '0644'
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

 Called by getInstance(). Initialize instance

 Return Servers::cron::cron

=cut

sub _init
{
	my $self = $_[0];

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforeCronInit', $self, 'cron'
	) and fatal('cron - beforeCronInit hook has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/cron.d";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'tplDir'} = "$self->{'cfgDir'}/parts";

	$self->{'hooksManager'}->trigger(
		'afterCronInit', $self, 'cron'
	) and fatal('cron - afterCronInit hook has failed');

	$self;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
