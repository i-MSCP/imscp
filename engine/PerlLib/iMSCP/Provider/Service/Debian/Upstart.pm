=head1 NAME

 iMSCP::Provider::Service::Debian::Upstart - Service provider for Debian `upstart` jobs.

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

package iMSCP::Provider::Service::Debian::Upstart;

use strict;
use warnings;
use parent qw/ iMSCP::Provider::Service::Upstart iMSCP::Provider::Service::Debian::Sysvinit /;

=head1 DESCRIPTION

 Service provider for Debian `upstart` jobs.

 The only differences with the base `upstart` provider are support for enabling, disabling and removing underlying
 sysvinit scripts if any.

 See: https://wiki.debian.org/Upstart

=head1 PUBLIC METHODS

=over 4

=item isEnabled($job)

 Is the given job enabled?

 Return bool TRUE if the given service is enabled, FALSE otherwise

=cut

sub isEnabled
{
    my ($self, $job) = @_;

    defined $job or die( 'parameter $job is not defined' );

    if ($self->_isUpstart( $job )) {
        return $self->SUPER::isEnabled( $job );
    }

    $self->iMSCP::Provider::Service::Debian::Sysvinit::isEnabled( $job );
}

=item enable($job)

 Enable the given job

 Param string $job Job name
 Return bool TRUE on success, FALSE on failure

=cut

sub enable
{
    my ($self, $job) = @_;

    defined $job or die( 'parameter $job is not defined' );

    if ($self->_isUpstart( $job )) {
        # Ensure that sysvinit script if any is not enabled
        my $ret = $self->_isSysvinit( $job ) ? $self->iMSCP::Provider::Service::Debian::Sysvinit::disable( $job ) : 1;
        return $ret && $self->SUPER::enable( $job );
    }

    # Enable sysvinit script if any
    if ($self->_isSysvinit( $job )) {
        return $self->iMSCP::Provider::Service::Debian::Sysvinit::enable( $job );
    }

    1;
}

=item disable($job)

 Disable the given job

 Param string $job Job name
 Return bool TRUE on success, FALSE on failure

=cut

sub disable
{
    my ($self, $job) = @_;

    defined $job or die( 'parameter $job is not defined' );

    if ($self->_isUpstart( $job )) {
        return 0 unless $self->SUPER::disable( $job );
    }

    # Disable the sysvinit script if any
    if ($self->_isSysvinit( $job )) {
        return $self->iMSCP::Provider::Service::Debian::Sysvinit::disable( $job );
    }

    1;
}

=item remove($job)

 Remove the given job

 Param string $job Job name
 Return bool TRUE on success, FALSE on failure

=cut

sub remove
{
    my ($self, $job) = @_;

    defined $job or die( 'parameter $job is not defined' );

    if ($self->_isUpstart( $job )) {
        return 0 unless $self->SUPER::remove( $job );
    }

    # Remove the sysvinit script if any
    if ($self->_isSysvinit( $job )) {
        return $self->iMSCP::Provider::Service::Debian::Sysvinit::remove( $job );
    }

    1;
}

=item hasService($job)

 Does the given job exists?

 Return bool TRUE if the given job exits, FALSE otherwise

=cut

sub hasService
{
    my ($self, $job) = @_;

    defined $job or die( 'parameter $job is not defined' );

    $self->_isUpstart( $job ) || $self->_isSysvinit( $job );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
