=head1 NAME

 iMSCP::Database Database adapter factory

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by internet Multi Server Control Panel
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
use iMSCP::Boolean;
use Module::Load::Conditional 'check_install';

=head1 DESCRIPTION

 Database adapter factory.

=cut

=head1 FUNCTIONS

=over 4

=item factory( )

 Create and return a database adapter instance.

 Return iMSCP::Database::MariaDB|iMSCP::Database::MySQL

=cut

sub factory
{
    CORE::state $adapter;

    $adapter //= do {
        # The DBD::MariaDB driver is only available in recent distributions
        # such as Debian Buster (10.x)
        $adapter = !!check_install(
            module => 'DBD::MariaDB', verbose => FALSE
        ) ? 'iMSCP::Database::MariaDB' : 'iMSCP::Database::MySQL';

        eval "require $adapter" or die( sprintf(
            "Couldn't load the '%s' database adapter: %s", $adapter, $@
        ));

        $adapter->getInstance();
    };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
