=head1 NAME

 iMSCP::Service - High-level interface for service providers

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
use iMSCP::Debug qw/debug getMessageByType/;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::LsbRelease;
use iMSCP::ProgramFinder;
use Module::Load::Conditional qw/check_install can_load/;
use Scalar::Defer;
use parent 'Common::SingletonClass';

$Module::Load::Conditional::FIND_VERSION = 0;

my $initSystem = lazy { _detectInitSystem() };

=head1 DESCRIPTION

 High-level interface for service providers.

=head1 PUBLIC METHODS

=over 4

=item isEnabled($service)

 Does the given service is enabled?

 Return TRUE if the given service is enabled, FALSE otherwise

=cut

sub isEnabled
{
	my ($self, $service) = @_;

	$self->{'provider'}->isEnabled($service);
}

=item enable($service)

 Enable the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub enable
{
	my ($self, $service) = @_;

	local $@;

	my $ret = eval {
		(
			$self->{'eventManager'}->trigger('onBeforeEnableService', $service) == 0 &&
			$self->{'provider'}->enable($service) &&
			$self->{'eventManager'}->trigger('onAfterEnableService', $service) == 0
		);
	};

	die(
		sprintf('Could not enable the %s service: %s', $service, ($@) ? $@ : $self->_getLastError())
	) unless $ret && !$@;

	$ret;
}

=item disable($service)

 Disable the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub disable
{
	my ($self, $service) = @_;

	local $@;

	my $ret = eval {
		(
			$self->{'eventManager'}->trigger('onBeforeDisableService', $service) == 0 &&
			$self->{'provider'}->disable($service) &&
			$self->{'eventManager'}->trigger('onAfterDisableService', $service) == 0
		);
	};

	die(
		sprintf('Could not disable the %s service: %s', $service, ($@) ? $@ : $self->_getLastError())
	) unless $ret && !$@;
}

=item remove($service)

 Remove the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub remove
{
	my ($self, $service) = @_;

	local $@;

	my $ret = eval {
		(
			$self->{'eventManager'}->trigger('onBeforeRemoveService', $service) == 0 &&
			$self->{'provider'}->remove($service) &&
			$self->{'eventManager'}->trigger('onAfterRemoveService', $service)
		);
	};

	die(
		sprintf('Could not remove the %s service: %s', $service, ($@) ? $@ : $self->_getLastError())
	) unless $ret && !$@;

	$ret;
}

=item start($service)

 Start the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub start
{
	my ($self, $service) = @_;

	local $@;

	my $ret = eval {
		(
			$self->{'eventManager'}->trigger('onBeforeStartService', $service) == 0 &&
			$self->{'provider'}->start($service) &&
			$self->{'eventManager'}->trigger('onAfterStartService', $service) == 0
		);
	};

	die(
		sprintf('Could not start the %s service: %s', $service, ($@) ? $@ : $self->_getLastError())
	) unless $ret && !$@;

	$ret;
}

=item stop($service)

 Stop the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub stop
{
	my ($self, $service) = @_;

	local $@;

	my $ret = eval {
		(
			$self->{'eventManager'}->trigger('onBeforeStopService', $service) == 0 &&
			$self->{'provider'}->stop($service) &&
			$self->{'eventManager'}->trigger('onAfterStopService', $service) == 0
		);
	};

	die(
		sprintf('Could not stop the %s service: %s', $service, ($@) ? $@ : $self->_getLastError())
	) if !$ret || $@;

	$ret;
}

=item restart($service)

 Restart the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub restart
{
	my ($self, $service) = @_;

	local $@;

	my $ret = eval {
		(
			$self->{'eventManager'}->trigger('onBeforeRestartService', $service) == 0 &&
			$self->{'provider'}->restart($service) &&
			$self->{'eventManager'}->trigger('onAfterRestartService', $service) == 0
		);
	};

	die(
		sprintf('Could not restart the %s service: %s', $service, ($@) ? $@ : $self->_getLastError())
	) unless $ret && !$@;

	$ret;
}

=item reload($service)

 Reload the given service

 Param string $service Service name
 Return bool TRUE on success, die on failure

=cut

sub reload
{
	my ($self, $service) = @_;

	local $@;

	my $ret = eval {
		(
			$self->{'eventManager'}->trigger('onBeforeReloadService', $service) == 0 &&
			$self->{'provider'}->reload($service) &&
			$self->{'eventManager'}->trigger('onAfterReloadService', $service) == 0
		);
	};

	die(
		sprintf('Could not reload the %s service: %s', $service, ($@) ? $@ : $self->_getLastError())
	) unless $ret && !$@;

	$ret;
}

=item isRunning($service)

 Get status of the given service

 Param string $service Service name
 Return bool TRUE if the given service is running, FALSE otherwise

=cut

sub isRunning
{
	my ($self, $service) = @_;

	local $@;

	(eval { $self->{'provider'}->isRunning($service); });
}

=item isSysvinit()

 Does sysvinit is used as init system?

 Return bool TRUE if sysvinit is used as init system, FALSE otherwise

=cut

sub isSysvinit
{
	($initSystem eq 'Sysvinit');
}

=item isUpstart()

 Does upstart is used as init system?

 Return bool TRUE if upstart is used as init system, FALSE otherwise

=cut

sub isUpstart
{
	($initSystem eq 'Upstart');
}

=item isSystemd()

 Does systemd is used as init system?

 Return bool TRUE if systemd is used as init system, FALSE otherwise

=cut

sub isSystemd
{
	($initSystem eq 'Systemd');
}

=item getProvider($providerName)

 Get a particular service provider instance

 Param string Provider name (sysvinit|upstart|systemd)
 Return iMSCP::Provider::Service::Sysvinit

=cut

sub getProvider
{
	my ($self, $providerName) = @_;

	$providerName = ucfirst(lc($providerName));

	my $id = iMSCP::LsbRelease->getInstance->getId('short');
	$id = 'Debian' if $id eq 'Ubuntu';

	my $provider = "iMSCP::Provider::Service::${id}::${providerName}";

	unless(check_install(module => $provider)) {
		$provider = "iMSCP::Provider::Service::${providerName}"; # Fallback to the base provider
	}

	can_load(modules => { $provider => undef}) or die(
		sprintf("Unable to load the %s service provider: %s", $provider, $Module::Load::Conditional::ERROR)
	);

	$provider->getInstance();
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

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();
	$self->{'provider'} = $self->getProvider($initSystem);

	$self;
}

=item _detectInitSystem()

 Detect init system in use

 Return string init system in use

=cut

sub _detectInitSystem
{
	my $initSystem = 'Sysvinit';

	my $initCmd = iMSCP::ProgramFinder::find('init');
	my $systemctlCmd = iMSCP::ProgramFinder::find('systemctl');

	my %initSystems = (
		Upstart => {
			command => (defined $initCmd) ? "$initCmd --version" : undef,
			regexp => qr/upstart/
		},
		Systemd => {
			command => (defined $systemctlCmd) ? "$systemctlCmd list-units --full --no-legend" : undef,
			regexp => qr/-\.mount/
		}
	);

	local $@;

	for(keys %initSystems) {
		eval {
			if(defined $initSystems{$_}->{'command'}) {
				my ($stdout, $stderr);
				execute($initSystems{$_}->{'command'}, \$stdout, \$stderr);

				$initSystem = $_ if $stdout =~ /$initSystems{$_}->{'regexp'}/;
			}
		};

		last if $initSystem ne 'Sysvinit';
	}

	debug(sprintf('%s init system has been detected', $initSystem));

	$initSystem;
}

=item _getLastError()

 Get last error

 Return string

=cut

sub _getLastError
{
	getMessageByType('error', { amount => 1, remove => 1 }) || 'An unexpected error occurred';
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
