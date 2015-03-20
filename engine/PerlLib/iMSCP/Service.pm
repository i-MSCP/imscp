=head1 NAME

 iMSCP::Service - Package providing a set of functions for service management

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

package iMSCP::Service;

use strict;
use warnings;
use iMSCP::Execute;
use parent 'Common::SingletonClass';

my $initSystem = _detectInitSystem();

=head1 DESCRIPTION

 Package providing a set of functions for service management.

=head1 PUBLIC METHODS

=over 4

=item start($serviceName [, $pattern = $serviceName ])

 Start the given service

 Param string $serviceName Service name
 Param string $pattern OPTIONAL Pattern as expected by the pgrep/pkill commands or 'retval' (default to service name)
 Return int 0 on success, other on failure

=cut

sub start
{
	my ($self, $serviceName, $pattern) = @_;

	$self->{'provider'}->start($serviceName, $pattern);
}

=item stop($serviceName [, $pattern = $serviceName ])

 Stop the given service

 Param string $serviceName Service name
 Param string $pattern OPTIONAL Pattern as expected by the pgrep/pkill commands or 'retval' (default to service name)
 Return int 0 on success, other on failure

=cut

sub stop
{
	my ($self, $serviceName, $pattern) = @_;

	$self->{'provider'}->stop($serviceName, $pattern);
}

=item restart($serviceName [, $pattern = $serviceName ])

 Restart the given service

 Param string $serviceName Service name
 Param string $pattern OPTIONAL Pattern as expected by the pgrep/pkill commands or 'retval' (default to service name)
 Return int 0 on success, other on failure

=cut

sub restart
{
	my ($self, $serviceName, $pattern) = @_;

	$self->{'provider'}->restart($serviceName, $pattern);
}

=item reload($serviceName [, $pattern = $serviceName ])

 Reload the given service

 Param string $serviceName Service name
 Param string $pattern OPTIONAL Pattern as expected by the pgrep/pkill commands or 'retval' (default to service name)
 Return int 0 on success, other on failure

=cut

sub reload
{
	my ($self, $serviceName, $pattern) = @_;

	$self->{'provider'}->reload($serviceName, $pattern);
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

	$self->{'provider'}->status($serviceName, $pattern);
}

=item isSysvinit()

 Does sysvinit is used as init system?

 Return bool TRUE if sysvinit is used as init system, FALSE otherwise

=cut

sub isSysvinit
{
	($initSystem eq 'sysvinit');
}

=item isUpstart()

 Does upstart is used as init system?

 Return bool TRUE if upstart is used as init system, FALSE otherwise

=cut

sub isUpstart
{
	($initSystem eq 'upstart');
}

=item isSystemd()

 Does systemd is used as init system?

 Return bool TRUE if systemd is used as init system, FALSE otherwise

=cut

sub isSystemd
{
	($initSystem eq 'systemd');
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return iMSCP::Service

=cut

sub _init
{
	my $self = $_[0];

	if($self->isSystemd()) {
		require iMSCP::Service::Systemd;
		$self->{'provider'} = iMSCP::Service::Systemd->getInstance();
	} elsif($self->isUpstart()) {
		require iMSCP::Service::Upstart;
		$self->{'provider'} = iMSCP::Service::Upstart->getInstance();
	} else {
		require iMSCP::Service::Sysvinit;
		$self->{'provider'} = iMSCP::Service::Sysvinit->getInstance();
	}

	$self;
}

=item _detectInitSystem()

 Detect init system in use

 Return string init system in use

=cut

sub _detectInitSystem
{
	my $initSystem = 'sysvinit';

	my %initSystems = (
		upstart => { command => '/sbin/init --version', regexp => qr/upstart/ },
		systemd => { command => 'systemctl', regexp => qr/-\.mount/ }
	);

	local $@;

	for(keys %initSystems) {
		eval {
			my ($stdout, $stderr);
			execute($initSystems{$_}->{'command'}, \$stdout, \$stderr);

			if($stdout =~ /$initSystems{$_}->{'regexp'}/) {
				$initSystem = $_;
			}
		};

		last if $initSystem ne 'sysvinit';
	}

	$initSystem;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
