=head1 NAME

 Servers::server::local - i-MSCP local server implementation

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

package Servers::server::local;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Servers::server::local::installer /;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP local server implementation

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( )

 Register setup event listeners

 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self) = @_;

    Servers::server::local::installer->getInstance( server => $self )->registerSetupListeners();
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeLocalServerPreInstall' );
    $rs ||= Servers::server::local::installer->getInstance( server => $self )->preinstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterLocalServerPreInstall' );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeLocalServerInstall' );
    $rs ||= Servers::server::local::installer->getInstance( server => $self )->install();
    $rs ||= $self->{'eventManager'}->trigger( 'afterLocalServerInstall' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut



1;
__END__
