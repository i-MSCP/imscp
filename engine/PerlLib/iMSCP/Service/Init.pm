=head1 NAME

 iMSCP::Service::Init - This provider manages `sysvinit` scripts.

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

package iMSCP::Service::Init;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use parent 'Common::SingletonClass';

my $commands = {
	pkill => 'pkill',
	pgrep => 'pgrep',
	service => 'service'
}

=head1 DESCRIPTION

 This provider manages `sysvinit` scripts.

=head1 PUBLIC METHODS

=over 4

=item start($serviceName [, $pattern = $serviceName ])

 Start the given service

 Param string $serviceName Service name
 Param string $pattern OPTIONAL Pattern as expected by the pgrep/pkill commands or 'retval' (default to service name)
 Return int 0 on succcess, other on failure

=cut

sub start
{
	my ($self, $serviceName, $pattern) = @_;

	$pattern ||= $serviceName;

	my $ret = $self->_runCommand("$commands->{'service'} $serviceName start");

	unless($pattern eq 'retval') {
		my $loopCount = 0;

		do {
			return 0 unless $self->status($serviceName, $pattern);
			sleep 1;
			$loopCount++;
		} while ($loopCount < 30);

		$self->status($serviceName, $pattern);
	} else {
		$ret;
	}
}

=item stop($serviceName [, $pattern = $serviceName ])

 Stop the given service

 Param string $serviceName Service name
 Param string $pattern OPTIONAL Pattern as expected by the pgrep/pkill commands or 'retval' (default to service name)
 Return int 0 on succcess, other on failure

=cut

sub stop
{
	my ($self, $serviceName, $pattern) = @_;

	$pattern ||= $serviceName;

	my $ret = $self->_runCommand("$commands->{'service'} $serviceName stop");

	unless($pattern eq 'retval') {
		my $loopCount = 0;

		do {
			return 0 if $self->status($serviceName, $pattern);
			sleep 1;
			$loopCount++;
		} while ($loopCount < 30);

		# Try by sending TERM signal ( soft way )
		$self->_runCommand("$commands->{'pkill'} -TERM $pattern");

		do {
			return 0 if $self->status($serviceName, $pattern);
			sleep 1;
			$loopCount++;
		} while ($loopCount < 5);

		# Try by sending KILL signal ( hard way )
		$self->_runCommand("$commands->{'pkill'} -KILL $pattern");

		do {
			return 0 if $self->status($serviceName, $pattern);
			sleep 1;
			$loopCount++;
		} while ($loopCount < 3);

		! $self->status($serviceName, $pattern);
	} else {
		$ret;
	}
}

=item restart($serviceName [, $pattern = $serviceName ])

 Restart the given service

 Param string $serviceName Service name
 Param string $pattern OPTIONAL Pattern as expected by the pgrep/pkill commands or 'retval' (default to service name)
 Return int 0 on succcess, other on failure

=cut

sub restart
{
	my ($self, $serviceName, $pattern) = @_;

	$pattern ||= $serviceName;

	unless($pattern eq 'retval') {
		if($self->status($pattern)) { # In case the service is not running, we start it
			$self->_runCommand("$commands->{'service'} $serviceName start");
		} else {
			$self->_runCommand("$commands->{'service'} $serviceName restart");
		}

		my $loopCount = 0;

		do {
			return 0 unless $self->status($serviceName, $pattern);
			sleep 1;
			$loopCount++;
		} while ($loopCount < 30);

		$self->status($serviceName, $pattern);
	} else {
		$self->_runCommand("$commands->{'service'} $serviceName restart");
	}
}

=item reload($serviceName [, $pattern = $serviceName ])

 Reload the given service

 Param string $serviceName Service name
 Param string $pattern OPTIONAL Pattern as expected by the pgrep/pkill commands or 'retval' (default to service name)
 Return int 0 on succcess, other on failure

=cut

sub reload
{
	my ($self, $serviceName, $pattern) = @_;

	$pattern ||= $serviceName;

	unless($pattern eq 'retval') {
		if($self->status($pattern)) { # In case the service is not running, we start it
			$self->_runCommand("$commands->{'service'} $serviceName start");
		} else {
			$self->_runCommand("$commands->{'service'} $serviceName reload");
		}

		my $loopCount = 0;

		do {
			return 0 unless $self->status($serviceName, $pattern);
			sleep 1;
			$loopCount++;
		} while ($loopCount < 30);

		$self->status($serviceName, $pattern);
	} else {
		$self->_runCommand("$commands->{'service'} $serviceName reload");
	}
}

=item status($serviceName [, $pattern = $serviceName ])

 Get status of the given service

 Param string $serviceName Service name
 Param string $pattern OPTIONAL Pattern as expected by the pgrep/pkill commands or 'retval' (default to service name)
 Return int 0 if the service is running, 1 if the service is not running

=cut

sub status
{
	my ($self, $serviceName, $pattern) = @_;

	$pattern ||= $serviceName;

	if($pattern eq 'retval') {
		$self->_runCommand("$commands->{'service'} status");
	} else {
		$self->_runCommand("$commands->{'pgrep'} $pattern");
	}
}

=back

=head1 PRIVATE METHODS

=over 4

=item _runCommand($command)

 Run the given command

 Return int 0 on success, other on failure

=cut

sub _runCommand
{
	my ($self, $command) = @_;

	my ($stdout, $stderr);
	my $rs = execute($command, \$stdout, \$stderr);
	debug($stderr) if $stderr;

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
