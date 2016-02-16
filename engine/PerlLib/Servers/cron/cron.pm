=head1 NAME

 Servers::cron::cron - i-MSCP Cron server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::cron::cron;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::TemplateParser;
use iMSCP::Service;
use File::Basename;
use Scalar::Defer;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Cron server implementation.

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeCronPreinstall', 'cron');
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterCronPreinstall', 'cron');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeCronInstall', 'cron');
	return $rs if $rs;

	my $cfgTpl;
	$rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'cron', 'imscp', \$cfgTpl, { });
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/imscp" )->get();
		unless(defined $cfgTpl) {
			error(sprintf('Unable to read %s', "$self->{'cfgDir'}/imscp"));
			return 1;
		}
	}

	$cfgTpl = process(
		{
			QUOTA_ROOT_DIR => $main::imscpConfig{'QUOTA_ROOT_DIR'},
			LOG_DIR => $main::imscpConfig{'LOG_DIR'},
			TRAFF_ROOT_DIR => $main::imscpConfig{'TRAFF_ROOT_DIR'},
			TOOLS_ROOT_DIR => $main::imscpConfig{'TOOLS_ROOT_DIR'},
			BACKUP_MINUTE => $main::imscpConfig{'BACKUP_MINUTE'},
			BACKUP_HOUR => $main::imscpConfig{'BACKUP_HOUR'},
			BACKUP_ROOT_DIR => $main::imscpConfig{'BACKUP_ROOT_DIR'},
			CONF_DIR => $main::imscpConfig{'CONF_DIR'},
			BACKUP_FILE_DIR => $main::imscpConfig{'BACKUP_FILE_DIR'}
		},
		$cfgTpl
	);

	my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/imscp" );

	$rs = $file->set($cfgTpl);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->copyFile("$self->{'config'}->{'CRON_D_DIR'}/imscp");
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterCronInstall', 'cron');
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeCronPostInstall', 'cron');
	return $rs if $rs;

	my $srvMngr = iMSCP::Service->getInstance();
	$srvMngr->enable($self->{'config'}->{'CRON_SNAME'});

	unless($srvMngr->isRunning($self->{'config'}->{'CRON_SNAME'})) {
		$srvMngr->start($self->{'config'}->{'CRON_SNAME'});
	}

	$self->{'eventManager'}->trigger('afterCronPostInstall', 'cron');
}

=item addTask(\%data, $filepath)

 Add a new cron task

 Param hash \%data Cron task data:
  - TASKID Cron task unique identifier
  - OPTIONAL MINUTE field: Minute time or shortcut such as @daily, @monthly... - Default to @daily
  - OPTIONAL HOUR field: HOUR Hour time - ignored if the MINUTE field define a shortcut - Default to *
  - OPTIONAL DAY field: DAY Day of month date - ignored if the MINUTE field define a shortcut - Default to *
  - OPTIONAL MONTH field: MONTH Month date - ignored if the MINUTE field define a shortcut - Default to *
  - OPTIONAL DWEEK field: DWEEK Day of week date - ignored if the MINUTE field define a shortcut - Default to *
  - USER user under which the command must be run
  - COMMAND Command
  Param string $file OPTIONAL Absolute path to cron file (default: imscp cron file)
  Return int 0 on success, other on failure

=cut

sub addTask
{
	my ($self, $data, $filepath) = @_;

	$data = { } unless ref $data eq 'HASH';

	unless(exists $data->{'COMMAND'} && exists $data->{'TASKID'}) {
		error('Missing command or task ID');
		return 1;
	}

	$filepath ||= "$self->{'config'}->{'CRON_D_DIR'}/imscp";

	if(-f $filepath) {
		$data->{'MINUTE'} = '@daily' unless exists $data->{'MINUTE'};
		$data->{'HOUR'} = '*' unless exists $data->{'HOUR'};
		$data->{'DAY'} = '*' unless exists $data->{'DAY'};
		$data->{'MONTH'} = '*' unless exists $data->{'MONTH'};
		$data->{'DWEEK'} = '*' unless exists $data->{'DWEEK'};
		$data->{'USER'} = $main::imscpConfig{'ROOT_USER'} unless exists $data->{'USER'};

		eval { $self->_validateCronTask($data); };
		if($@) {
			error(sprintf('Invalid cron tasks: %s', $@));
			return 1;
		}

		my $filename = fileparse($filepath);
		my $wrkFileContent = iMSCP::File->new( filename => $filepath )->get();
		unless(defined $wrkFileContent) {
			error("Unable to read $filepath");
			return 1;
		}

		my $rs = $self->{'eventManager'}->trigger('beforeCronAddTask', \$wrkFileContent, $data);
		return $rs if $rs;

		my $cronEntryBegin = "# imscp [$data->{'TASKID'}] entry BEGIN\n";
		my $cronEntryEnding = "# imscp [$data->{'TASKID'}] entry ENDING\n";

		my $cronEntry = sprintf(
			"%s %s %s %s %s %s %s\n",
			$data->{'MINUTE'}, $data->{'HOUR'}, $data->{'DAY'}, $data->{'MONTH'}, $data->{'DWEEK'}, $data->{'USER'},
			$data->{'COMMAND'}
		);

		$cronEntry =~ s/ +/ /;
		$wrkFileContent = replaceBloc($cronEntryBegin, $cronEntryEnding, '', $wrkFileContent);
		$wrkFileContent = replaceBloc(
			"# imscp [{ENTRY_ID}] entry BEGIN\n",
			"# imscp [{ENTRY_ID}] entry ENDING\n",
			"$cronEntryBegin$cronEntry$cronEntryEnding",
			$wrkFileContent,
			'preserve'
		);

		$self->{'eventManager'}->trigger('afterCronAddTask', \$wrkFileContent, $data);

		my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$filename" );

		$rs = $file->set($wrkFileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0640);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		$file->copyFile($filepath);
	} else {
		error("Unable to add cron task: File $filepath not found.");
		1;
	}
}

=item deleteTask(\%data, $filepath)

 Delete a cron task

 Param hash \%data Cron task data:
  - TASKID Cron task unique identifier
 Param string $filepath OPTIONAL Absolute path to cron file (default: imscp cron file)
 Return int 0 on success, other on failure

=cut

sub deleteTask
{
	my ($self, $data, $filepath) = @_;

	$data = { } unless ref $data eq 'HASH';

	unless(exists $data->{'TASKID'}) {
		error('Missing task ID');
		return 1;
	}

	$filepath ||= "$self->{'config'}->{'CRON_D_DIR'}/imscp";

	if(-f $filepath) {
		my $filename = fileparse($filepath);
		my $wrkFileContent = iMSCP::File->new( filename => $filepath )->get;
		unless(defined $wrkFileContent) {
			error("Unable to read $filepath");
			return 1;
		}

		my $rs = $self->{'eventManager'}->trigger('beforeCronDelTask', \$wrkFileContent, $data);
		return $rs if $rs;

		$wrkFileContent = replaceBloc(
			"# imscp [$data->{'TASKID'}] entry BEGIN\n",
			"# imscp [$data->{'TASKID'}] entry ENDING\n",
			'',
			$wrkFileContent
		);

		$rs = $self->{'eventManager'}->trigger('afterCronDelTask', \$wrkFileContent, $data);
		return $rs if $rs;

		my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$filename" );

		$rs = $file->set($wrkFileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0640);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		$file->copyFile($filepath);
	} else {
		error("Unable to remove cron task: File $filepath not found.");
		1;
	}
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = shift;

	if(-f "$self->{'config'}->{'CRON_D_DIR'}/imscp") {
		require iMSCP::Rights;
		iMSCP::Rights->import();

		setRights("$self->{'config'}->{'CRON_D_DIR'}/imscp", {
			user => $main::imscpConfig{'ROOT_USER'},
			group => $main::imscpConfig{'ROOT_GROUP'},
			mode => '0640'
		});
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
	my $self = shift;

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();
	$self->{'eventManager'}->trigger('beforeCronInit', $self, 'cron') and fatal('cron - beforeCronInit has failed');
	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/cron.d";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'config'} = lazy { tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/cron.data"; \%c; };
	$self->{'eventManager'}->trigger('afterCronInit', $self, 'cron') and fatal('cron - afterCronInit has failed');

	$self;
}

=item _validateCronTask()

 Validate cron task attributes

 Return undef or die if an attribute is not valid

=cut

sub _validateCronTask
{
	my ($self, $data) = @_;

	if(
		$data->{'MINUTE'} ~~ [ '@reboot', '@yearly', '@annually', '@monthly', '@weekly', '@daily', '@midnight', '@hourly' ]
	) {
		$data->{'HOUR'} = $data->{'DAY'} = $data->{'MONTH'} = $data->{'DWEEK'} = '';
	} else {
		for my $attribute('minute', 'hour', 'day', 'month', 'dweek') {
			$self->_validateAttribute($attribute, $data->{ uc($attribute) });
		}
	}

	undef;
}

=item _validateAttribute()

 Validate the given cron task attribute value

 Param string $name Attribute name
 Param string $value Attribute value
 Return undef or die if an attribute is not valid

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

		if($name eq 'minute') {
			$pattern = '[ ]*(\b[0-5]?[0-9]\b)[ ]*';
		} elsif($name eq 'hour') {
			$pattern = '[ ]*(\b[01]?[0-9]\b|\b2[0-3]\b)[ ]*';
		} elsif ($name eq 'day') {
			$pattern = '[ ]*(\b[01]?[1-9]\b|\b2[0-9]\b|\b3[01]\b)[ ]*';
		} elsif ($name eq 'month') {
			@namesArr = split '|', $months;
			$pattern = "([ ]*(\b[0-1]?[0-9]\b)[ ]*)|([ ]*($months)[ ]*)";
		} elsif ($name eq 'dweek') {
			@namesArr = split '|', $days;
			$pattern = "([ ]*(\b[0]?[0-7]\b)[ ]*)|([ ]*($days)[ ]*)";
		} else {
			die(sprintf("Unknown '%s' cron task attribute", $name));
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
__END__
