=head1 NAME

 iMSCP::Provider::Service::Debian::Upstart - Debian Upstart init provider

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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
use Carp 'croak';
use parent qw/ iMSCP::Provider::Service::Upstart iMSCP::Provider::Service::Debian::SysVinit /;

=head1 DESCRIPTION

 Upstart init provider for Debian-like distributions.
 
 Difference with the iMSCP::Provider::Service::Upstart init provider is the
 support for the SysVinit scripts.

 See: https://wiki.debian.org/Upstart

=head1 PUBLIC METHODS

=over 4

=item isEnabled( $job )

 See iMSCP::Provider::Service::Interface::isEnabled()

=cut

sub isEnabled
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'parameter $job is not defined' );

    return $self->SUPER::isEnabled( $job ) if $self->SUPER::hasService( $job );
    $self->iMSCP::Provider::Service::Debian::SysVinit::isEnabled( $job );
}

=item enable( $job )

 See iMSCP::Provider::Service::Interface::enable()

=cut

sub enable
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'parameter $job is not defined' );

    if ( $self->SUPER::hasService( $job ) ) {
        $self->SUPER::enable( $job );
        $self->iMSCP::Provider::Service::Debian::SysVinit::enable( $job ) if $self->iMSCP::Provider::Service::Debian::SysVinit::hasService( $job );
        return;
    }

    $self->iMSCP::Provider::Service::Debian::SysVinit::enable( $job );
}

=item disable( $job )

 See iMSCP::Provider::Service::Interface::disable()

=cut

sub disable
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'parameter $job is not defined' );

    if ( $self->SUPER::hasService( $job ) ) {
        $self->SUPER::disable( $job );
        $self->iMSCP::Provider::Service::Debian::SysVinit::disable( $job ) if $self->iMSCP::Provider::Service::Debian::SysVinit::hasService( $job );
        return;
    }

    $self->iMSCP::Provider::Service::Debian::SysVinit::disable( $job );
}

=item remove( $job )

 See iMSCP::Provider::Service::Interface::remove()

=cut

sub remove
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'parameter $job is not defined' );

    $self->SUPER::remove( $job );
    $self->iMSCP::Provider::Service::Debian::SysVinit::remove( $job );
}

=item start( $job )

 See iMSCP::Provider::Service::Interface::start()

=cut

sub start
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    if ( $self->SUPER::hasService( $job ) ) {
        $self->SUPER::start( $job );
        return;
    }

    $self->iMSCP::Provider::Service::Debian::SysVinit::start( $job );
}

=item stop( $job )

 See iMSCP::Provider::Service::Interface::stop()

=cut

sub stop
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    if ( $self->SUPER::hasService( $job ) ) {
        $self->SUPER::stop( $job );
        return;
    }

    $self->iMSCP::Provider::Service::Debian::SysVinit::stop( $job );
}

=item restart( $job )

 See iMSCP::Provider::Service::Interface::restart()

=cut

sub restart
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    if ( $self->SUPER::hasService( $job ) ) {
        $self->SUPER::restart( $job );
        return;
    }

    $self->iMSCP::Provider::Service::Debian::SysVinit::restart( $job );
}

=item reload( $job )

 See iMSCP::Provider::Service::Interface::reload()

=cut

sub reload
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    if ( $self->SUPER::hasService( $job ) ) {
        $self->SUPER::reload( $job );
        return;
    }

    $self->iMSCP::Provider::Service::Debian::SysVinit::reload( $job );
}

=item isRunning( $job )

 See iMSCP::Provider::Service::Interface::isRunning()

=cut

sub isRunning
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'Missing or undefined $job parameter' );

    if ( $self->SUPER::hasService( $job ) ) {
        return $self->SUPER::isRunning( $job );
    }

    $self->iMSCP::Provider::Service::Debian::SysVinit::isRunning( $job );
}

=item hasService( $job )

 See iMSCP::Provider::Service::Interface::hasService()

=cut

sub hasService
{
    my ( $self, $job ) = @_;

    defined $job or croak( 'parameter $job is not defined' );

    $self->SUPER::hasService( $job ) || $self->iMSCP::Provider::Service::Debian::SysVinit::hasService( $job );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 See iMSCP::Provider::Service::Debian::SysVinit::_init()

=cut

sub _init
{
    my ( $self ) = @_;

    # Make sure to initialize underlying SysVinit init provider (multiple inheritance)
    $self->iMSCP::Provider::Service::Debian::SysVinit::_init();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
