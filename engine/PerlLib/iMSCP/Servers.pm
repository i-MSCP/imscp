=head1 NAME

 iMSCP::Servers - Package that allows to load and get list of available i-MSCP servers

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

package iMSCP::Servers;

use strict;
use warnings;
use File::Basename;
use parent 'iMSCP::Common::SingletonClass';

=head1 DESCRIPTION

 Package that allows to load and get list of available i-MSCP servers

=head1 PUBLIC METHODS

=over 4

=item getList( )

 Get server list, sorted in descending order of priority

 Return server list

=cut

sub getList
{
    @{$_[0]->{'servers'}};
}

=item getListWithFullNames( )

 Get server list with full names, sorted in descending order of priority

 Return server list

=cut

sub getListWithFullNames
{
    @{$_[0]->{'servers_full_names'}};
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Servers

=cut

sub _init
{
    my ($self) = @_;

    $_ = basename( $_, '.pm' ) for @{$self->{'servers'}} = grep { $_ !~ /(?:Abstract|Noserver)\.pm$/ } glob( dirname( __FILE__ ) . '/Servers/*.pm' );

    # Load all server classes
    for ( @{$self->{'servers'}} ) {
        my $server = "iMSCP::Servers::${_}";
        eval "require $server; 1" or die( sprintf( "Couldn't load %s server class: %s", $server, $@ ));
    }

    # Sort servers by priority (descending order)
    @{$self->{'servers'}} = sort { "iMSCP::Servers::${b}"->getPriority() <=> "iMSCP::Servers::${a}"->getPriority() } @{$self->{'servers'}};
    @{$self->{'servers_full_names'}} = map { "iMSCP::Servers::${_}" } @{$self->{'servers'}};
    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
