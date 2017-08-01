=head1 NAME

 Servers::sqld::percona - i-MSCP Percona server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::sqld::percona;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Servers::sqld::percona::installer Servers::sqld::percona::uninstaller /;
use iMSCP::Service;
use parent 'Servers::sqld::mysql';

=head1 DESCRIPTION

 i-MSCP Percona server implementation.

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldPreinstall', 'percona' );
    $rs ||= Servers::sqld::percona::installer->getInstance()->preinstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterSqldPreinstall', 'percona' )
}

=item postinstall( )

 Process postinstall tasks

 Return int 0

=cut

sub postinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldPostInstall', 'percona' );

    local $@;
    eval { iMSCP::Service->getInstance()->enable( 'mysql' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs = $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]}, [ sub { $self->restart(); }, 'Percona' ];
            0;
        },
        7
    );

    $rs ||= $self->{'eventManager'}->trigger( 'afterSqldPostInstall', 'percona' );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldUninstall', 'percona' );
    $rs ||= Servers::sqld::percona::uninstaller->getInstance()->uninstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterSqldUninstall', 'percona' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
