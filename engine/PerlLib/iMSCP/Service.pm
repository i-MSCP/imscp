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
use iMSCP::ProgramFinder;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Package providing a set of functions for service management.

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

	$self->{'provider'}->start($serviceName, $pattern);
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

	$self->{'provider'}->stop($serviceName, $pattern);
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

	$self->{'provider'}->restart($serviceName, $pattern);
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

	if(iMSCP::ProgramFinder::find('systemctl')) {
		require iMSCP::Service::Systemd;
		$self->{'provider'} = iMSCP::Service::Systemd->getInstance();
	} elsif(iMSCP::ProgramFinder::find('initctl')) {
		require iMSCP::Service::Upstart;
		$self->{'provider'} = iMSCP::Service::Upstart->getInstance();
	} else {
		require iMSCP::Service::Init;
		$self->{'provider'} = iMSCP::Service::Init->getInstance();
	}

	$self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
