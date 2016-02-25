=head1 NAME

 iMSCP::Provider::Service::Systemd - Base service provider for `systemd` service units

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

package iMSCP::Provider::Service::Systemd;

use strict;
use warnings;
use File::Spec;
use iMSCP::File;
use parent 'iMSCP::Provider::Service::Sysvinit';
use Hash::Util::FieldHash 'fieldhash';

# Paths where system service unit files must be searched
fieldhash my %paths;

# Commands used in that package
my %commands = (
	'systemctl' => '/bin/systemctl'
);

=head1 DESCRIPTION

 Base service provider for `systemd` service units.

 See:
  http://www.freedesktop.org/wiki/Software/systemd/
  http://www.freedesktop.org/software/systemd/man/systemd.service.html

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

	$self->_exec($commands{'systemctl'}, '--quiet', 'is-enabled', "$service.service") == 0;
}

=item enable($service)

 Enable the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub enable
{
	my ($self, $service) = @_;

	# Note: Will automatically call update-rc.d in case of a sysvinit script
	$self->disable($service) && $self->_exec($commands{'systemctl'}, '--quiet', 'enable', "$service.service") == 0;
}

=item disable($service)

 Disable the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub disable
{
	my ($self, $service) = @_;

	# Note: Will automatically call update-rc.d in case of a sysvinit script
	$self->_exec($commands{'systemctl'}, '--quiet', 'disable', "$service.service") == 0;
}

=item remove($service)

 Remove the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub remove
{
	my ($self, $service) = @_;

	$self->stop($service) && $self->disable($service)
		&& iMSCP::File->new( filename => $self->getUnitFilePath($service) )->delFile() == 0;
}

=item start($service)

 Start the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub start
{
	my ($self, $service) = @_;

	$self->_exec($commands{'systemctl'}, 'start', "$service.service") == 0;
}

=item stop($service)

 Stop the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub stop
{
	my ($self, $service) = @_;

	return 1 unless $self->isRunning($service);

	$self->_exec($commands{'systemctl'}, 'stop', "$service.service") == 0;
}

=item restart($service)

 Restart the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub restart
{
	my ($self, $service) = @_;

	if($self->isRunning($service)) {
		return $self->_exec($commands{'systemctl'}, 'restart', "$service.service") == 0;
	}

	$self->_exec($commands{'systemctl'}, 'start', "$service.service") == 0;
}

=item reload($service)

 Reload the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub reload
{
	my ($self, $service) = @_;

	if($self->isRunning($service)) {
		return $self->_exec($commands{'systemctl'}, 'reload', "$service.service") == 0;
	}

	$self->start($service);
}

=item isRunning($service)

 Does the given service is running?

 Param string $service Service name
 Return bool TRUE if the given service is running, FALSE otherwise

=cut

sub isRunning
{
	my ($self, $service) = @_;

	$self->_exec($commands{'systemctl'}, 'is-active', "$service.service") == 0;
}

=item getUnitFilePath($service)

 Get full path of unit file which belongs to the given service

 Param string $service Service name
 Return string Init script path on success, die on failure

=cut

sub getUnitFilePath
{
	my ($self, $service) = @_;

	$self->_searchUnitFile($service);
}

=back

=head1 PRIVATE METHODS

=over 4

=item

 Initialize instance

 Return iMSCP::Provider::Service::Systemd

=cut

sub _init
{
	my $self = shift;

	$paths{$self} = [
		'/etc/systemd/system',
		'/lib/systemd/system',
		'/usr/local/lib/systemd/system',
		'/usr/lib/systemd/system'
	];

	$self->SUPER::_init();
}

=item _isSystemd($service)

 Does the given service is managed by a native systemd service unit file?

 Param string $service Service name
 Return bool TRUE if the given service is managed by a systemd service unit file, FALSE otherwise

=cut

sub _isSystemd
{
	my ($self, $service) = @_;

	local $@;
	eval { $self->getUnitFilePath($service); };
}

=item _searchUnitFile($service)

 Search the unit file which belongs to the given service in all available paths

 Param string $service Service name
 Return string unit file path on success, die on failure

=cut

sub _searchUnitFile
{
	my ($self, $service) = @_;

	for my $path(@{$paths{$self}}) {
		my $filepath = File::Spec->join($path, $service . '.service');
		return $filepath if -f $filepath;
	}

	die(sprintf('Could not find systemd service unit file for the %s service', $service));
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
