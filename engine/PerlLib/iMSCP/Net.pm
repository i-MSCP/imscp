#!/usr/bin/perl

=head1 NAME

 iMSCP::Net - Package allowing to manage network devices and IP addresses

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

# TODO: Handle input prefix lenght (CIDR)

package iMSCP::Net;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use Net::IP qw(:PROC);
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Package allowing to manage network devices and IP addresses.

=head1 PUBLIC METHODS

=over 4

=item getAddresses()

 Get addresses list

 Return array|string List of IP addresses

=cut

sub getAddresses()
{
	my $self = $_[0];

	wantarray ? keys %{$self->{'addresses'}} : join(' ', keys %{$self->{'addresses'}});
}

=item addAddr($addr, $dev)

 Add the given IP to the given network device

 Param string $addr IP address
 Param string $dev Network device name
 Return int 0 on success, other on failure

=cut

sub addAddr($$$)
{
	my ($self, $addr, $dev) = @_;

	if($self->isValidAddr($addr)) {
		unless($self->isKnownAddr($addr)) {
			if($self->isKnownDevice($dev)) {
				$addr = $self->normalizeAddr($addr);

				my $cidr = (ip_is_ipv4($addr)) ? 32 : 64; # TODO should be configurable

				my ($stdout, $stderr);
				my $rs = execute("$main::imscpConfig{'CMD_IP'} addr add $addr/$cidr dev $dev", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				error("Unable to add IP $addr to network device $dev") if $rs && ! $stderr;
				return $rs if $rs;

				# This class must be aware of this new IP
				$self->{'addresses'}->{$addr} = {
					'prefix_length' => $cidr,
					'version' => $self->getAddrVersion($addr),
					'device' => $dev
				};
			}
		} else {
			error("Unknown network device: $dev");
			return 1;
		}
	} else {
		error("Invalid IP: $addr");
		return 1;
	}

	0;
}

=item delAddr($addr)

 Delete the given IP

 Param string $addr IP address
 Return int 0 on success, other on failure

=cut

sub delAddr($$)
{
	my ($self, $addr) = @_;

	if($self->isValidAddr($addr)) {
		if($self->isKnownAddr($addr)) {
			$addr = $self->normalizeAddr($addr);

			my $dev = $self->{'addresses'}->{$addr}->{'device'};
			my $cidr = $self->{'addresses'}->{$addr}->{'prefix_length'};

			my ($stdout, $stderr);
			my $rs = execute("$main::imscpConfig{'CMD_IP'} addr del $addr/$cidr dev $dev", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			error("Unable to delete IP $addr from network device $dev") if $rs && ! $stderr;
			return $rs if $rs;

			# This class must be aware of this deletion
			delete $self->{'addresses'}->{$addr};
		}
	} else {
		error("Invalid IP: $addr");
		return 1;
	}

	0;
}

=item getAddrVersion($addr)

 Get version of the given IP (ipv4|ipv6)

 Param string $addr IP address
 Return string|undef IP version or undef in case the given IP is invalid

=cut

sub getAddrVersion($$)
{
	my ($self, $addr) = @_;

	my $version = ip_get_version($addr);

	if($version) {
		($version == 4) ? 'ipv4' : 'ipv6';
	} else {
		error("Invalid IP: $addr");
		undef;
	}
}

=item getAddrDevice($addr)

 Return the network device name to which the given IP belong to

 Param string $addr IP address
 Return string|undef Network device name or undef if the given IP is either invalid or not known by this module

=cut

sub getAddrDevice($$)
{
	my ($self, $addr) = @_;

	if($self->isValidAddr($addr)) {
		if($self->isKnownAddr($addr)) {;
			return $self->{'addresses'}->{$addr}->{'device'};
		} else {
			error("Unknown IP: $addr");
		}
	} else {
		error("Invalid IP: $addr");
	}

	undef;
}

=item isKnownAddr($addr)

 Is the given IP known?

 Param string $addr IP address
 Return int 1 if the given IP is known, 0 otherwise

=cut

sub isKnownAddr($$)
{
	my ($self, $addr) = @_;

	(exists($self->{'addresses'}->{$self->normalizeAddr($addr)})) ? 1 : 0;
}

=item isValidAddr($addr)

 Check whether or not the given IP is valid

 Param string $addr IP address
 Return int 1 if valid, 0 otherwise

=cut

sub isValidAddr($$)
{
	my ($self, $addr) = @_;

	(ip_get_version($addr)) ? 1 : 0;
}

=item normalizeAddr($addr)

 Normalize the given IP

 Param string $addr IP address
 Return string Normalized IP on success, undef on failure

=cut

sub normalizeAddr($$)
{
	my ($self, $addr) = @_;

	if($self->getAddrVersion($addr) eq 'ipv6') {
		ip_compress_address($addr, 6);
	} else {
		$addr;
	}
}

=item getDevices()

 Get network devices list

 Return array|string List of devices

=cut

sub getDevices()
{
	my $self = $_[0];

	wantarray ? keys %{$self->{'devices'}} : join(' ', keys %{$self->{'devices'}});
}

=item isKnownDevice($dev)

 Is the given network device known?

 Param string $dev Network device name
 Return int 1 if the network device is known, 0 otherwise

=cut

sub isKnownDevice($$)
{
	my ($self, $dev) = @_;

	(exists($self->{'devices'}->{$dev})) ? 1 : 0;
}

=item upDevice($dev)

 Bring the the given network device up

 Param string $dev Network device name
 Return int 0 on success, other on failure

=cut

sub upDevice($$)
{
	my ($self, $dev) = @_;

	my $rs = 0;

	if($self->isKnownDevice($dev)) {
		my ($stdout, $stderr);
		my $rs = execute("$main::imscpConfig{'CMD_IP'} link set dev $dev up", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error("Unable to bring the network device up: $dev") if $rs && ! $stderr;
	} else {
		error("Unknown network device: $dev");
	 	$rs = 1;
	}

	$rs;
}

=item downDevice($dev)

 Bring the given network device down

 Param string $dev Network device name
 Return int 0 on success, other on failure

=cut

sub downDevice($$)
{
	my ($self, $dev) = @_;

	my $rs = 0;

	if($self->isKnownDevice) {
		my ($stdout, $stderr);
		my $rs = execute("$main::imscpConfig{'CMD_IP'} link set dev $dev down", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		error("Unable to bring the network device down: $dev") if $rs && ! $stderr;
	} else {
		error("Unknown network device: $dev");
		$rs = 1;
	}

	$rs;
}

=item isDeviceUp($dev)

 Is the given network device up?

 Param string $dev Network device name
 Return int 1 if the given device is known and  up, 0 otherwise

=cut

sub isDeviceUp($$)
{
	my ($self, $dev) = @_;

	($self->{'devices'}->{$dev}->{'flags'} =~ /^(?:.*,)?UP(?:,.*)?$/) ? 1 : 0;
}

=item isDeviceDown($dev)

 Is the given device down?

 Param string $dev Network device name
 Return int 1 if the given device is known and down, 0 otherwise

=cut

sub isDeviceDown($$)
{
	my ($self, $dev) = @_;

	($self->{'devices'}->{$dev}->{'flags'} =~ /^(?:.*,)?UP(?:,.*)?$/) ? 0 : 1;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return iMSCP::Net

=cut

sub _init
{
	my $self = $_[0];

	$self->{'devices'} = $self->_extractDevices();
	$self->{'addresses'} = $self->_extractAddresses();

	$self;
}

=item _extractDevices()

 Extract network devices data

 Return hash|undef A hash describing each device found or undef on failure

=cut

sub _extractDevices()
{
	my $self = $_[0];

	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'CMD_IP'} -o link show", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	fatal('Unable to get network devices data') if $rs;

	my $devices = {};

	$devices->{$1}->{'flags'} = $2 while($stdout =~ /^[^\s]+\s+(.*?):\s+<(.*)>/gm);

	$devices;
}

=item _extractAddresses()

 Extract addresses data (scope global only)

 Return hash|undef A hash describing each IP found or undef on failure

=cut

sub _extractAddresses()
{
	my $self = $_[0];

	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'CMD_IP'} -o addr show scope global", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	fatal('Unable to get network devices data') if $rs;

	my $addresses = {};

	while($stdout =~ m%^[^\s]+\s+([^\s]+)\s+([^\s]+)\s+([^/\s]+).*?/(\d+)%gm) {
		$addresses->{$self->normalizeAddr($3)} = {
			'prefix_length' => $4,
			'version' => ($2 eq 'inet') ? 'ipv4' : 'ipv6',
			'device' => $1
		} ;
	}

	$addresses;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
