=head1 NAME

 iMSCP::Provider::Service::Sysvinit - Base service provider for `sysvinit` scripts

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

package iMSCP::Provider::Service::Sysvinit;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use File::Spec;
use iMSCP::Debug 'error';
use iMSCP::Execute;
use iMSCP::LsbRelease;
use Hash::Util::FieldHash 'fieldhash';

# Paths where sysvinit script must be searched
fieldhash my %paths;

=head1 DESCRIPTION

 Base service provider for `sysvinit` scripts.

=head1 PUBLIC METHODS

=over 4

=item getInstance()

 Get instance

 Return iMSCP::Provider::Service::Sysvinit

=cut

sub getInstance
{
	my $self = $_[0];

	no strict 'refs';
	my $instance = \${"${self}::_instance"};

	unless(defined $$instance) {
		$$instance = bless (\ my $this, $self);
		$$instance->_init();
	}

	$$instance;
}

=item isEnabled($service)

 Does the given service is enabled?

 Param string $service Service name
 Return bool TRUE

=cut

sub isEnabled
{
	1; # Not implemented
}

=item enable($service)

 Enable the given service

 Param string $service Service name
 Return bool TRUE

=cut

sub enable
{
	1; # Not implemented
}

=item disable($service)

 Disable the given service

 Param string $service Service name
 Return bool TRUE

=cut

sub disable
{
	1; # Not implemented
}

=item remove($service)

 Remove the given service

 Param string $service Service name
 Return bool TRUE

=cut

sub remove
{
	1; # Not implemented
}

=item start($service)

 Start the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub start
{
	my ($self, $service) = @_;

	($self->_exec($self->getInitscriptPath($service), 'start') == 0);
}

=item stop($service)

 Stop the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub stop
{
	my ($self, $service) = @_;

	($self->_exec($self->getInitscriptPath($service), 'stop') == 0);
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
		($self->_exec($self->getInitscriptPath($service), 'restart') == 0);
	} else {
		($self->_exec($self->getInitscriptPath($service), 'start') == 0);
	}
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
		($self->_exec($self->getInitscriptPath($service), 'reload') == 0);
	} else {
		($self->_exec($self->getInitscriptPath($service), 'start') == 0);
	}
}

=item isRunning($service)

 Does the given service is running?

 Param string $service Service name
 Return bool TRUE if the given service is running, FALSE otherwise

=cut

sub isRunning
{
	my ($self, $service) = @_;

	# FIXME: Assumption is made that any init script is providing status command which is bad...
	# TODO: Fallback using processes table output should be implemented
	($self->_exec($self->getInitscriptPath($service), 'status') == 0);
}

=item getInitscriptPath($service)

 Get full path of init script which belongs to the given service

 Param string $service Service name
 Return string Init script path on success, die on failure

=cut

sub getInitscriptPath
{
	my ($self, $service) = @_;

	$self->_searchInitScript($service);
}

=back

=head1 PRIVATE METHODS

=over 4

=item

 Initialize instance

 Return iMSCP::Provider::Service::Sysvinit

=cut

sub _init
{
	my $self = $_[0];

	# TODO (v2.0.0) Replace LsbRelease call by pfacter since LSB is something Linux specific
	# http://search.cpan.org/dist/pfacter/
	# http://search.cpan.org/~dozzie/Sys-Facter-1.01/
	my $id = iMSCP::LsbRelease->getInstance()->getId('short');

	if($id ~~ [ 'FreeBSD', 'DragonFly' ]) {
		$paths{$self} = [ '/etc/rc.d', '/usr/local/etc/rc.d' ];
	} elsif ($id eq 'HP-UX') {
		$paths{$self} = [ '/sbin/init.d' ];
	} elsif($id eq 'Archlinux') {
		$paths{$self} = [ '/etc/rc.d' ];
	} else {
		$paths{$self} =  [ '/etc/init.d' ];
	}

	$self;
}

=item searchInitScript($service)

 Search the init script which belongs to the given service in all available paths

 Param string $service Service name
 Return string Init script path on success, die on failure

=cut

sub _searchInitScript
{
	my ($self, $service, $flush) = @_;

	for my $path(@{$paths{$self}}) {
		my $filepath = File::Spec->join($path, $service);
		return $filepath if -f $filepath;

		$filepath .= '.sh';
		return $filepath if -f $filepath;
	}

	die(sprintf('Could not find sysvinit script for the %s service', $service));
}

=item _exec($command)

 Execute the given command

 Return int Command exit status

=cut

sub _exec
{
	my ($self, @command) = @_;

	my ($stdout, $stderr);
	my $ret = execute("@command", \$stdout, \$stderr);
	error($stderr) if $ret && $stderr;
	$ret;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
