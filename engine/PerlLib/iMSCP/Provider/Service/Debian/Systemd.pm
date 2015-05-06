=head1 NAME

 iMSCP::Provider::Service::Debian::Systemd - Service provider for Debian `systemd` service units

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

package iMSCP::Provider::Service::Debian::Systemd;

use strict;
use warnings;
use iMSCP::Execute;
use iMSCP::File;
use Scalar::Defer;
use parent qw(
	iMSCP::Provider::Service::Systemd
	iMSCP::Provider::Service::Debian::Sysvinit
);

# Commands used in that package
my %commands = (
	'dpkg' => '/usr/bin/dpkg',
	'systemctl' => '/bin/systemctl'
);

# Compatibility mode
my $compat = 0;

=head1 DESCRIPTION

 Service provider for Debian `systemd` service units.

 The only differences with the base `systemd` provider are support for enabling, disabling and removing underlying
 sysvinit scripts. This provider also provides backware compatibility mode for older Debian systemd package versions.

 See:
  https://wiki.debian.org/systemd
  https://wiki.debian.org/systemd/Packaging
  https://wiki.debian.org/systemd/Integration

=head1 PUBLIC METHODS

=over 4

=item isEnabled($service)

 Does the given service is enabled?

 Param string $service Service name
 Return bool TRUE if the given service is enabled, FALSE otherwise

=cut

sub isEnabled
{
	my ($self, $service) = @_;

	if($self->_isSystemd($service)) {
		$self->SUPER::isEnabled($service);
	} else {
		# is-enabled API call is not available for sysvinit scripts. Thus, we must use the Debian sysvinit provider to
		# known whether or not the sysvinit script is enabled.
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

	# compat mode is forced here due to https://bugs.launchpad.net/ubuntu/wily/+source/systemd/+bug/1447807

	#if($compat) {
		if($self->_isSystemd($service)) {
			return unless $self->SUPER::enable($service);
		}

		# Backward compatibility operations
		# We must manually enable the underlying sysvinit script if any. This is needed because `systemctl` as provided
		# in systemd packages older than version 204-3, doesn't make call of `the update-rc-d <service> disable`. Thus,
		# the sysvinit script is not enabled. We must also make call of `systemctl daemon-reload` to make systemd aware
		# of changes.
		if($self->_isSysvinit($service)) {
			(
				$self->iMSCP::Provider::Service::Debian::Sysvinit::enable($service) &&
				$self->_exec($commands{'systemctl'}, 'daemon-reload') == 0
			);
		} else {
			1;
		}
	#} else {
	#	(
	#		$self->SUPER::enable($service) &&
	#		( $self->_isSystemd($service) || $self->_exec($commands{'systemctl'}, 'daemon-reload') == 0)
	#	);
	#}
}

=item disable($service)

 Disable the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub disable
{
	my ($self, $service) = @_;

	if($compat) {
		if($self->_isSystemd($service)) {
			return unless $self->SUPER::disable($service);
		}

		# Backward compatibility operations
		# We must manually disable the underlying sysvinit script if any. This is needed because `systemctl` as provided
		# in systemd packages older than version 204-3, doesn't make call of `the update-rc-d <service> disable`. Thus,
		# the sysvinit script is not disabled. We must also make call of `systemctl daemon-reload` to make systemd aware
		# of changes.
		if($self->_isSysvinit($service)) {
			(
				$self->iMSCP::Provider::Service::Debian::Sysvinit::disable($service) &&
				$self->_exec($commands{'systemctl'}, 'daemon-reload') == 0
			);
		} else {
			1;
		}
	} else {
		(
			$self->SUPER::disable($service) &&
			( $self->_isSystemd($service) || $self->_exec($commands{'systemctl'}, 'daemon-reload') == 0)
		);
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

	if($self->_isSystemd($service)) {
		return unless $self->SUPER::remove($service);
	}

	if($self->_isSysvinit($service)) {
		# Remove the underlying sysvinit script if any and make systemd aware of changes
		(
			$self->iMSCP::Provider::Service::Debian::Sysvinit::remove($service) &&
			$self->_exec($commands{'systemctl'}, 'daemon-reload')
		);
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
	my $self = $_[0];

	# Sets compatibility mode according systemd version in use
	$compat = lazy {
		$self->_exec(
			$commands{'dpkg'}, '--compare-versions', '$(dpkg-query -W --showformat \'${Version}\' systemd)', 'ge', '204-3'
		);
	};

	$self->SUPER::_init();
}

=item _isSystemd($service)

 Does the given service is managed by a native systemd service unit?

 Param string $service Service name
 Return bool TRUE if the given service is managed by a systemd unit, FALSE otherwise

=cut

sub _isSystemd
{
	my ($self, $service) = @_;

	local $@;
	eval { $self->getUnitFilePath($service); };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
