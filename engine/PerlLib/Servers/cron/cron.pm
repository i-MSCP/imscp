=head1 NAME

 Servers::cron::cron - i-MSCP Cron server implementation

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

package Servers::cron::cron;

use strict;
use warnings;
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
	$rs||= $self->{'eventManager'}->trigger('afterCronPreinstall', 'cron');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeCronInstall', 'cron');
	$rs ||= $self->{'eventManager'}->trigger('onLoadTemplate', 'cron', 'imscp', \ my $cfgTpl, { });
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/imscp" )->get();
		unless(defined $cfgTpl) {
			error(sprintf('Could not read %s', "$self->{'cfgDir'}/imscp"));
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
	$rs ||= $file->save();
	$rs ||= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs ||= $file->mode(0644);
	$rs ||= $file->copyFile("$self->{'config'}->{'CRON_D_DIR'}/imscp");
	$rs ||= $self->{'eventManager'}->trigger('afterCronInstall', 'cron');
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

	local $@;
	eval { $srvMngr->enable($self->{'config'}->{'CRON_SNAME'}); };
	if($@) {
		error($@);
		return 1;
	}

	$rs ||= $self->{'eventManager'}->register('beforeSetupRestartServices', sub {
		push @{$_[0]}, [ sub { $srvMngr->restart($self->{'config'}->{'CRON_SNAME'}); 0; }, 'Cron' ]; 0;
	});
	$rs ||= $self->{'eventManager'}->trigger('afterCronPostInstall', 'cron');
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
	unless(-f $filepath) {
		error(sprintf('Could not add cron task: File %s not found.', $filepath));
		return 1;
	}

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
		error(sprintf('Could not read %s file', $filepath));
		return 1;
	}

	my $rs = $self->{'eventManager'}->trigger('beforeCronAddTask', \$wrkFileContent, $data);
	return $rs if $rs;

	# Remove entry with same ID if any
	$wrkFileContent = replaceBloc(
		"# imscp [$data->{'TASKID'}] entry BEGIN\n", "# imscp [$data->{'TASKID'}] entry ENDING\n", '', $wrkFileContent
	);

	($wrkFileContent .= <<EOF) =~ s/^(@[^\s]+)\s+/$1 /gm;

# imscp [$data->{'TASKID'}] entry BEGIN
$data->{'MINUTE'} $data->{'HOUR'} $data->{'DAY'} $data->{'MONTH'} $data->{'DWEEK'} $data->{'USER'} $data->{'COMMAND'}
# imscp [$data->{'TASKID'}] entry ENDING
EOF

	$rs = $self->{'eventManager'}->trigger('afterCronAddTask', \$wrkFileContent, $data);
	return $rs if $rs;

	my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$filename" );
	$rs = $file->set($wrkFileContent);
	$rs ||= $file->save();
	$rs ||= $file->mode(0640);
	$rs ||= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs ||= $file->copyFile($filepath);
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
	unless(-f $filepath) {
		error(sprintf('Could not remove cron task: File %s not found.', $filepath));
		return 1;
	}

	my $filename = fileparse($filepath);
	my $wrkFileContent = iMSCP::File->new( filename => $filepath )->get;
	unless(defined $wrkFileContent) {
		error(sprintf('Could not read %s file', $filepath));
		return 1;
	}

	my $rs = $self->{'eventManager'}->trigger('beforeCronDelTask', \$wrkFileContent, $data);
	return $rs if $rs;

	$wrkFileContent = replaceBloc(
		"# imscp [$data->{'TASKID'}] entry BEGIN\n", "# imscp [$data->{'TASKID'}] entry ENDING\n", '', $wrkFileContent
	);

	$rs = $self->{'eventManager'}->trigger('afterCronDelTask', \$wrkFileContent, $data);
	return $rs if $rs;

	my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$filename" );
	$rs ||= $file->set($wrkFileContent);
	$rs ||= $file->save();
	$rs ||= $file->mode(0640);
	$rs ||= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs ||= $file->copyFile($filepath);
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = shift;

	return 0 unless -f "$self->{'config'}->{'CRON_D_DIR'}/imscp";

	require iMSCP::Rights;
	iMSCP::Rights->import();

	setRights("$self->{'config'}->{'CRON_D_DIR'}/imscp", {
		user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_GROUP'}, mode => '0640'
	});
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

	if($data->{'MINUTE'} =~ /^@(reboot|yearly|annually|monthly|weekly|daily|midnight|hourly)$/) {
		$data->{'HOUR'} = $data->{'DAY'} = $data->{'MONTH'} = $data->{'DWEEK'} = '';
		return undef;
	}

	for my $attribute(qw/minute hour day month dweek/) {
		$self->_validateAttribute($attribute, $data->{ uc($attribute) });
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

	defined $name or die('$name is undefined');
	defined $value or die('$value is undefined');
	$value ne '' or die(sprintf("Value for the '%s' cron task attribute cannot be empty", $name));
	return undef unless $value ne '*';

	my $step = '[1-9]?[0-9]';
	my $months = 'jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';
	my $days = 'mon|tue|wed|thu|fri|sat|sun';
	my @namesArr = ();
	my $pattern;

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
	}

	defined $pattern or die(sprintf("Unknown '%s' cron task attribute", $name));

	my $range = "((($pattern)|(\\*\\/$step)?)|((($pattern)-($pattern))(\\/$step)?))";
	my $longPattern = "$range(,$range)*";

	$value =~ /^$longPattern$/i or die(sprintf(
		"Invalid value '%s' given for the '%s' cron task attribute", $value, $name
	));

	for my $testField (split ',', $value) {
		unless ($testField =~ /^((($pattern)-($pattern))(\/$step)?)+$/) {
			next;
		}

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

	undef;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
