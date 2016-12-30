=head1 NAME

 Modules::Abstract - Base class for i-MSCP modules

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by internet Multi Server Control Panel
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
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Packages;
use iMSCP::Servers;
use parent 'Common::Object';

=head1 DESCRIPTION

 i-MSCP modules abstract class.

=head1 PUBLIC METHODS

=over 4

=item getType()

 Get module type

 Return string Module type

=cut

sub getType
{
    fatal( ref( $_[0] ).' module must implement the getType() method' );
}

=item process()

 Process action (add|delete|restore|disable) according item status.

 Return int 0 on success, other on failure

=cut

sub process
{
    fatal( ref( $_[0] ).' module must implement the process() method' );
}

=item add()

 Add item

 Called for items with 'toadd|tochange|toenable' status.

 Return int 0 on success, other on failure

=cut

sub add
{
    $_[0]->_runAllActions( 'add' );
}

=item delete()

 Delete item

 Called for items with 'todelete' status.

 Return int 0 on success, other on failure

=cut

sub delete
{
    $_[0]->_runAllActions( 'delete' );
}

=item restore()

 Restore item

 Called for items with 'torestore' status.

 Return int 0 on success, other on failure

=cut

sub restore
{
    $_[0]->_runAllActions( 'restore' );
}

=item disable()

 Disable item

 Called for items with 'todisable' status.

 Return int 0 on success, other on failure

=cut

sub disable
{
    $_[0]->_runAllActions( 'disable' );
}

=back

=head1 PRIVATES METHODS

=over 4

=item _init()

 Initialize instance

 Return Modules::User

=cut

sub _init
{
    my $self = shift;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'_data'} = { };
    $self;
}

=item _runAction($action, $itemType)

 Run the given action on all servers or packages that implement it

 Param string $action Action to run
 Param string $itemType Item type (server|package)
 Return int 0 on success, other or die on failure

=cut

sub _runAction
{
    my ($self, $action, $itemType) = @_;

    if ($itemType eq 'server') {
        for my $server (iMSCP::Servers->getInstance()->getListWithFullNames()) {
            eval "require $server";

            my $instance = $server->factory();
            if (my $subref = $instance->can( $action )) {
                debug( "Calling action $action on $server" );
                my $rs = $subref->( $instance, $self->_getData( $action ) );
                return $rs if $rs;
            }
        }
        return 0;
    }

    for my $package (iMSCP::Packages->getInstance()->getListWithFullNames()) {
        eval "require $package";
        my $instance = $package->getInstance();
        if (my $subref = $instance->can( $action )) {
            debug( "Calling action $action on $package" );
            my $rs = $subref->( $instance, $self->_getData( $action ) );
            return $rs if $rs;
        }
    }

    0;
}

=item _runAllActions()

 Run actions (pre<Action>, <Action>, post<Action>) on each servers and packages

 Return int 0 on success, other on failure

=cut

sub _runAllActions
{
    my ($self, $action) = @_;

    my $moduleType = $self->getType();

    if ($action =~ /^(?:add|restore)$/) {
        for('pre', '', 'post') {
            my $rs = $self->_runAction( "$_$action$moduleType", 'server' );
            $rs ||= $self->_runAction( "$_$action$moduleType", 'package' );
            return $rs if $rs;
        }

        return 0;
    }

    for('pre', '', 'post') {
        my $rs = $self->_runAction( "$_$action$moduleType", 'package' );
        $rs ||= $self->_runAction( "$_$action$moduleType", 'server' );
        return $rs if $rs;
    }

    0;
}

=item _getData($action)

 Data provider method for i-MSCP servers and packages

 Param string $action Action
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
