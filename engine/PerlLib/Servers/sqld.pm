=head1 NAME

 Servers::sqld - i-MSCP Sqld Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::sqld;

use strict;
use warnings;
use iMSCP::Debug;

our $instance;

=head1 DESCRIPTION

 i-MSCP sqld server implementation.

=head1 PUBLIC METHODS

=over 4

=item factory()

 Create and return sqld server instance

 Return Sqld server instance

=cut

sub factory
{
    return $instance if defined $instance;

    (my $sName = $main::imscpConfig{'SQL_SERVER'}) =~ s/_\d+\.\d+$//;
    my $package = "Servers::sqld::$sName";
    eval "require $package";
    fatal($@) if $@;
    $instance = $package->getInstance();
}

=item can($method)

 Checks if the sqld server class provide the given method

 Param string $method Method name
 Return subref|undef

=cut

sub can
{
    my ($self, $method) = @_;
    $self->factory()->can($method);
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
