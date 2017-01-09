=head1 NAME

 Servers::sqld::remote - i-MSCP Remote MySQL server implementation

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

package Servers::sqld::remote;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Servers::sqld::remote::installer Servers::sqld::remote::uninstaller /;
use iMSCP::Database;
use version;
use parent 'Servers::sqld::mysql';

=head1 DESCRIPTION

 i-MSCP Remote MySQL server implementation.

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldPreinstall', 'remote' );
    $rs ||= Servers::sqld::remote::installer->getInstance()->preinstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterSqldPreinstall', 'remote' )
}

=item postinstall()

 Process postinstall tasks

 Return int 0

=cut

sub postinstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldPostInstall', 'remote' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterSqldPostInstall', 'remote' );
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldUninstall', 'remote' );
    $rs ||= Servers::sqld::remote::uninstaller->getInstance()->uninstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterSqldUninstall', 'remote' );
}

=item restart()

 Restart server

 Return int 0

=cut

sub restart
{
    0;
}

=item createUser($user, $host, $password)

 Create the given SQL user

 Param $string $user SQL username
 Param string $host SQL user host
 Param $string $password SQL user password
 Return int 0 on success, die on failure

=cut

sub createUser
{
    my ($self, $user, $host, $password) = @_;

    defined $user or die( '$user parameter is not defined' );
    defined $host or die( '$host parameter is not defined' );
    defined $password or die( '$password parameter is not defined' );

    my $db = iMSCP::Database->factory();
    my $qrs = $db->doQuery(
        'c', 'CREATE USER ?@? IDENTIFIED BY ?'.(
                $self->getType() ne 'mariadb' && version->parse( $self->getVersion() ) >= version->parse( '5.7.6' )
            ? ' PASSWORD EXPIRE NEVER' : ''
        ),
        $user, $host, $password
    );
    ref $qrs eq 'HASH' or die( sprintf( 'Could not create the %s@%s SQL user: %s', $user, $host, $qrs ) );
    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
