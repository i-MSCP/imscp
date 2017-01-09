=head1 NAME

 Servers::named - i-MSCP Named Server implementation

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

package Servers::named;

use strict;
use warnings;
use iMSCP::Debug;

our $instance;

=head1 DESCRIPTION

 i-MSCP named server implementation.

=head1 PUBLIC METHODS

=over 4

=item factory()

 Create and return named server instance

 Also trigger uninstallation of old named server when needed.

 Return Named server instance

=cut

sub factory
{
    return $instance if defined $instance;

    my $sName = $main::imscpConfig{'NAMED_SERVER'} || 'external_server';

    if (defined $main::execmode && $main::execmode eq 'setup') {
        if ($sName eq 'external_server'
            && $main::imscpOldConfig{'NAMED_SERVER'} ne ''
            && $main::imscpOldConfig{'NAMED_SERVER'} ne $sName
        ) {
            my $package = "Servers::named::$main::imscpOldConfig{'NAMED_SERVER'}";
            eval "require $package";
            fatal( $@ ) if $@;

            my $rs = $package->getInstance()->uninstall();
            fatal( sprintf( "Could not uninstall the `%s' server", $main::imscpOldConfig{'NAMED_SERVER'} ) ) if $rs;
        }
    }

    my $package = ($sName eq 'external_server') ? 'Servers::noserver' : "Servers::named::$sName";
    eval "require $package";
    fatal( $@ ) if $@;
    $instance = $package->getInstance();
}

=item can($method)

 Checks if the named server class provide the given method

 Param string $method Method name
 Return subref|undef

=cut

sub can
{
    my ($self, $method) = @_;
    $self->factory()->can( $method );
}

END
    {
        return if $? || (defined $main::execmode && $main::execmode eq 'setup');

        if ($Servers::named::instance->{'restart'}) {
            $? = $Servers::named::instance->restart();
        } elsif ($Servers::named::instance->{'reload'}) {
            $? = $Servers::named::instance->reload();
        }
    }

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
