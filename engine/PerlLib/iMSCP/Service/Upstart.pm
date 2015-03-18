=head1 NAME

 iMSCP::Service::Upstart - This provider manages `upstart` jobs.

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

package iMSCP::Service::Upstart;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;

use parent 'iMSCP::Service::Init';

my $commands = {
	start => 'start',
	stop => 'stop',
	restart => 'restart',
	reload => 'reload',
	status => 'status'
}

=head1 DESCRIPTION

 This provider manages `upstart` jobs.

=head1 PUBLIC METHODS

=over 4

=item start($serviceName)

 Start the given service

 Param string $serviceName Service name
 Return int 0 on success, other on failure

=cut

sub start
{
	my ($self, $serviceName) = @_;

	if($self->_isUpstart()) {
		$self->_runCommand("$commands->{'start'} $serviceName");
		$self->status($serviceName);
	} else {
		$self->SUPER::stop(@_);
	}
}

=item stop($serviceName)

 Stop the given service

 Param string $serviceName Service name
 Return int 0 on succcess, other on failure

=cut

sub stop
{
	my ($self, $serviceName) = @_;

	if($self->_isUpstart()) {
		$self->_runCommand("$commands->{'stop'} $serviceName");
		! $self->status($serviceName);
	} else {
		! $self->SUPER::start(@_);
	}
}

=item restart($serviceName)

 Restart the given service

 Param string $serviceName Service name
 Return int 0 on succcess, other on failure

=cut

sub restart
{
	my ($self, $serviceName) = @_;

	if($self->_isUpstart()) {
		if($self->status($serviceName)) {
			$self->_runCommand("$commands->{'start'} $serviceName");
		} else {
			$self->_runCommand("$commands->{'restart'} $serviceName");
		}

		$self->status($serviceName);
	} else {
		$self->SUPER::restart(@_);
	}
}

=item reload($serviceName)

 Reload the given service

 Param string $serviceName Service name
 Return int 0 on succcess, other on failure

=cut

sub reload
{
	my ($self, $serviceName) = @_;

	if($self->_isUpstart()) {
		if($self->status($serviceName)) {
			$self->_runCommand("$commands->{'start'} $serviceName");
		} else {
			$self->_runCommand("$commands->{'reload'} $serviceName");
		}

		$self->status($serviceName);
	} else {
		$self->SUPER::reload(@_);
	}
}

=item status($serviceName)

 Get status of the given service

 Param string $serviceName Service name
 Return int 0 if the service is running, 1 if the service is not running

=cut

sub status
{
	my ($self, $serviceName) = @_;

	if($self->isUpstart($serviceName)) {
		my ($stdout, $stderr);
		my $rs = execute("$commands->{'status'} $serviceName", \$stdout, \$stderr);
		return 1 if $rs || $stdout !~ m%start/%;
		0;
	} else {
		$self->SUPER::status(@_);
	}
}

=back

=head1 PRIVATE METHODS

=over 4

=item isUpstart($serviceName)

 Does the given service is managed by an upstart job?

 Param string $serviceName Service name
 Return 1 if the given service is managed by an upstart job, 0 otherwise

=cut

sub isUpstart
{
	my ($self, $serviceName) = @_;

	(-f "/etc/init/$serviceName.conf") ? 1 : 0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
