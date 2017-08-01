=head1 NAME

 iMSCP::Database Database adapter factory

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package iMSCP::Database;

use strict;
use warnings;

my %adapterInstances;

=head1 DESCRIPTION

 Database adapter factory.

=cut

=head1 FUNCTIONS

=over 4

=item factory( $adapterName )

 Create and return a database adapter instance. Instance is created once

 Param string $adapterName Adapter name
 Return an instance of the specified database adapter

=cut

sub factory
{
    my $adapterName = $_[1] || $main::imscpConfig{'DATABASE_TYPE'};

    return $adapterInstances{$adapterName} if $adapterInstances{$adapterName};

    my $adapter = "iMSCP::Database::${adapterName}";
    eval "require $adapter" or die( sprintf( "Couldn't load `%s` database adapter: %s", $adapter, $@ ));
    $adapterInstances{$adapterName} = $adapter->getInstance();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
