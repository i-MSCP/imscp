=head1 NAME

 iMSCP::Provider::Service::Sysvinit - Service provider for Debian `sysvinit' scripts

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

package iMSCP::Provider::Service::Debian::Sysvinit;

use strict;
use warnings;
use iMSCP::File;
use Scalar::Defer;
use parent 'iMSCP::Provider::Service::Sysvinit';

# Commands used in that package
my %COMMANDS = (
    dpkg          => '/usr/bin/dpkg',
    'invoke-rc.d' => '/usr/sbin/invoke-rc.d',
    'update-rc.d' => '/usr/sbin/update-rc.d'
);

# Enable compatibility mode if sysv-rc package version is lower than version 2.88
my $SYSVRC_COMPAT_MODE = lazy
    {
        __PACKAGE__->_exec(
            $COMMANDS{'dpkg'}, '--compare-versions', '$(dpkg-query -W -f \'${Version}\' sysv-rc)', 'lt', '2.88'
        ) == 0;
    };

=head1 DESCRIPTION

 Service provider for Debian `sysvinit' scripts.

 The only differences with the base sysvinit provider are support for enabling, disabling and removing services
 via `update-rc.d' and the ability to determine enabled status via `invoke-rc.d'.

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

    defined $service or die( 'parameter $service is not defined' );
    my $ret = $self->_exec( $COMMANDS{'invoke-rc.d'}, '--quiet', '--query', $service, 'start' );

    # 104 is the exit status when you query start an enabled service.
    # 106 is the exit status when the policy layer supplies a fallback action
    if ($ret =~ /^10(?:4|6)$/) {
        return 1;
    }

    if ($ret =~ /^10(?:1|5)$/) {
        # 101 is action not allowed, which means we have to do the check manually.
        # 105 is unknown, which generally means the iniscript does not support query
        # The debian policy states that the initscript should support methods of query
        # For those that do not, peform the checks manually
        # http://www.debian.org/doc/debian-policy/ch-opersys.html
        return (my @count = glob( "/etc/rc*.d/S??$service" )) >= 4;
    }

    0;
}

=item enable($service)

 Enable the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub enable
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );

    #if ($SYSVRC_COMPAT_MODE) {
    return $self->_exec( $COMMANDS{'update-rc.d'}, '-f', $service, 'remove' ) == 0
        && $self->_exec( $COMMANDS{'update-rc.d'}, $service, 'defaults' ) == 0;
    #}

    #$self->_exec( $COMMANDS{'update-rc.d'}, $service, 'defaults' ) == 0
    #    && $self->_exec( $COMMANDS{'update-rc.d'}, $service, 'enable' ) == 0;
}

=item disable($service)

 Disable the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub disable
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );

    if ($SYSVRC_COMPAT_MODE) {
        return $self->_exec( $COMMANDS{'update-rc.d'}, '-f', $service, 'remove' ) == 0
            && $self->_exec( $COMMANDS{'update-rc.d'}, $service, 'stop', '00', '1', '2', '3', '4', '5', '6', '.' ) == 0;
    }

    #$self->_exec( $COMMANDS{'update-rc.d'}, $service, 'defaults' ) == 0
    #&& $self->_exec( $COMMANDS{'update-rc.d'}, $service, 'disable' ) == 0;
    $self->_exec( $COMMANDS{'update-rc.d'}, $service, 'disable' ) == 0;
}

=item remove($service)

 Remove the given service

 Param string $service Service name
 Return bool TRUE on success, FALSE on failure

=cut

sub remove
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    $self->stop( $service ) && $self->_exec( $COMMANDS{'update-rc.d'}, '-f', $service, 'remove' ) == 0
        && $self->SUPER::remove( $service );
}

=item hasService($service)

 Does the given service exists?

 Return bool TRUE if the given service exits, FALSE otherwise

=cut

sub hasService
{
    my ($self, $service) = @_;

    defined $service or die( 'parameter $service is not defined' );
    $self->_isSysvinit( $service );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
