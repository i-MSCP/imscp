=head1 NAME

 iMSCP::Provider::Service::Debian::Upstart - Service provider for Debian `upstart` jobs.

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

package iMSCP::Provider::Service::Debian::Upstart;

use strict;
use warnings;

use parent qw(
	iMSCP::Provider::Service::Upstart
	iMSCP::Provider::Service::Debian::Sysvinit
);

=head1 DESCRIPTION

 Service provider for Debian `upstart` jobs.

 The only differences with the base `upstart` provider are support for enabling, disabling and removing underlying
 sysvinit scripts if any.

 See:
  https://wiki.debian.org/Upstart

=head1 PUBLIC METHODS

=over 4

=item isEnabled($service)

 Does the given service is enabled?

 Return bool TRUE if the given service is enabled, FALSE otherwise

=cut

sub isEnabled
{
	my ($self, $service) = @_;

	if($self->_isUpstart($service)) {
		$self->SUPER::isEnabled($service);
	} else {
		$self->iMSCP::Provider::Service::Debian::Sysvinit::isEnabled($service);
	}
}

=item enable($service)

 Enable the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub enable
{
	my ($self, $service) = @_;

	if($self->_isUpstart($service)) {
		return unless $self->SUPER::enable($service);
	}

	# Also enable the underlying sysvinit script if any
	if($self->_isSysvinit($service)) {
		$self->iMSCP::Provider::Service::Debian::Sysvinit::enable($service);
	} else {
		1;
	}
}

=item disable($service)

 Disable the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub disable
{
	my ($self, $service) = @_;

	if($self->_isUpstart($service)) {
		return unless $self->SUPER::disable($service);
	}

	# Also disable the underlying sysvinit script if any
	if($self->_isSysvinit($service)) {
		$self->iMSCP::Provider::Service::Debian::Sysvinit::disable($service);
	} else {
		1;
	}
}

=item remove($service)

 Remove the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub remove
{
	my ($self, $service) = @_;

	if($self->_isUpstart($service)) {
		return unless $self->SUPER::remove($service);
	}

	# Also remove the underlying sysvinit script if any
	if($self->_isSysvinit($service)) {
		$self->iMSCP::Provider::Service::Debian::Sysvinit::remove($service);
	} else {
		1;
	}
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return iMSCP::Provider::Service::Debian::Systemd

=cut

sub _init
{
	my $self = shift;

	$self->iMSCP::Provider::Service::Debian::Sysvinit::_init();
	$self->SUPER::_init();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
