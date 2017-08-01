=head1 NAME

 Modules::Abstract - Base class for i-MSCP modules

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Modules::Abstract;

use strict;
use warnings;
use iMSCP::Database;
use iMSCP::Debug qw/ debug fatal /;
use iMSCP::EventManager;
use iMSCP::Packages;
use iMSCP::Servers;
use parent 'Common::Object';

=head1 DESCRIPTION

 i-MSCP modules abstract class.

=head1 PUBLIC METHODS

=over 4

=item getType( )

 Get module type

 Return string Module type

=cut

sub getType
{
    fatal( ref( $_[0] ) . ' module must implements the getType( ) method' );
}

=item process( )

 Process add|delete|restore|disable action according item status.

 Return int 0 on success, other on failure

=cut

sub process
{
    fatal( ref( $_[0] ) . ' module must implements the process( ) method' );
}

=item add( )

 Execute the `add' action on servers, packages

 Should be executed for items with 'toadd|tochange|toenable' status.

 Return int 0 on success, other on failure

=cut

sub add
{
    $_[0]->_execAllActions( 'add' );
}

=item delete( )

 Execute the `delete' action on servers, packages

 Should be executed for items with 'todelete' status.

 Return int 0 on success, other on failure

=cut

sub delete
{
    $_[0]->_execAllActions( 'delete' );
}

=item restore( )

 Execute the `restore' action on servers, packages

 Should be executed for items with 'torestore' status.

 Return int 0 on success, other on failure

=cut

sub restore
{
    $_[0]->_execAllActions( 'restore' );
}

=item disable( )

 Execute the `disable' action on servers, packages

 Should be executed for items with 'todisable' status.

 Return int 0 on success, other on failure

=cut

sub disable
{
    $_[0]->_execAllActions( 'disable' );
}

=back

=head1 PRIVATES METHODS

=over 4

=item _init( )

 Initialize instance

 Return Modules::Abstract

=cut

sub _init
{
    my ($self) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'_dbh'} = iMSCP::Database->factory()->getRawDb();
    $self->{'_data'} = {};
    $self;
}

=item _execAction( $action, $pkgType )

 Execute the given $action on all $pkgType that implement it

 Param string $action Action to execute on servers, packages (<pre|post><action><moduleType>)
 Param string $pkgType Package type (server|package)
 Return int 0 on success, other on failure

=cut

sub _execAction
{
    my ($self, $action, $pkgType) = @_;

    if ( $pkgType eq 'server' ) {
        for  ( iMSCP::Servers->getInstance()->getListWithFullNames() ) {
            ( my $subref = $_->can( $action ) ) or next;
            debug( sprintf( "Executing `%s' action on %s", $action, $_ ));
            my $rs = $subref->( $_->factory(), $self->_getData( $action ));
            return $rs if $rs;
        }

        return 0;
    }

    for ( iMSCP::Packages->getInstance()->getListWithFullNames() ) {
        ( my $subref = $_->can( $action ) ) or next;
        debug( sprintf( "Executing `%s' action on %s", $action, $_ ));
        my $rs = $subref->( $_->getInstance(), $self->_getData( $action ));
        return $rs if $rs;
    }

    0;
}

=item _execAllActions( $action )

 Execute pre$action, $action, post$action on servers, packages

 Param string $action Action to execute on servers, packages (add|delete|restore|disable)
 Return int 0 on success, other on failure

=cut

sub _execAllActions
{
    my ($self, $action) = @_;

    my $moduleType = $self->getType();

    if ( $action =~ /^(?:add|restore)$/ ) {
        for( 'pre', '', 'post' ) {
            my $rs = $self->_execAction( "$_$action$moduleType", 'server' );
            $rs ||= $self->_execAction( "$_$action$moduleType", 'package' );
            return $rs if $rs;
        }

        return 0;
    }

    for( 'pre', '', 'post' ) {
        my $rs = $self->_execAction( "$_$action$moduleType", 'package' );
        $rs ||= $self->_execAction( "$_$action$moduleType", 'server' );
        return $rs if $rs;
    }

    0;
}

=item _getData( $action )

 Data provider method for i-MSCP servers and packages

 Param string $action Action being executed (<pre|post><action><moduleType>) on servers, packages
 Return hashref Reference to a hash containing data, die on failure

=cut

sub _getData
{
    $_[0]->{'_data'};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
