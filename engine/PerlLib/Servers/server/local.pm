=head1 NAME

 Servers::server::local - i-MSCP local server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::server::local;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Debug 'debug';
use iMSCP::LsbRelease;
use Class::Autouse qw/ :nostat Servers::server::local::installer /;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP local server implementation

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%events )

 Register setup event listeners

 Param iMSCP::EventManager \%events
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( undef, $events ) = @_;

    Servers::server::local::installer->getInstance()->registerSetupListeners(
        $events
    );
}

=item preinstall( )

 Pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeServerPreInstall', 'local' );
    $rs ||= Servers::server::local::installer->getInstance()->preinstall();
    $rs ||= $self->{'events'}->trigger( 'afterServerPreInstall', 'local' );
}

=item install( )

 Installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeServerInstall', 'local' );
    $rs ||= Servers::server::local::installer->getInstance()->install();
    $rs ||= $self->{'events'}->trigger( 'afterServerInstall', 'local' );
}

=item dpkgPostInvokeTasks( )

 dpkg(1) post-invoke tasks
 
 - Update LSB info in the master configuration file

 Return int 0 on success, other on failure

=cut

sub dpkgPostInvokeTasks
{
    my ( $self ) = @_;

    my $lsb = iMSCP::LsbRelease->getInstance();

    return 0 if lc $lsb->getId( TRUE ) eq $::imscpConfig{'DISTRO_ID'}
        && lc $lsb->getCodename( TRUE ) eq $::imscpConfig{'DISTRO_CODENAME'}
        && $lsb->getRelease( TRUE, TRUE ) eq $::imscpConfig{'DISTRO_RELEASE'};

    debug( 'Updating LSB info in master configuration file' );
    @{main::imscpConfig}{qw/ DISTRO_ID DISTRO_CODENAME DISTRO_RELEASE /} = (
        lc $lsb->getId( TRUE ),
        lc $lsb->getCodename( TRUE ),
        $lsb->getRelease( TRUE, TRUE )
    );

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::server::local::installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'events'} = iMSCP::EventManager->getInstance();
    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut



1;
__END__
