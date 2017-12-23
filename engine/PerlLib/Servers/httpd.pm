=head1 NAME

 Servers::httpd - i-MSCP httpd server implementation

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

package Servers::httpd;

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::Service;

# Servers::httpd::Interface server package name
my $PACKAGE;

=head1 DESCRIPTION

 i-MSCP httpd server implementation.

=head1 CLASS METHODS

=over 4

=item getPriority( )

 Get server priority

 Return int Server priority

=cut

sub getPriority
{
    60;
}

=item factory( )

 Create and return an Servers::httpd::Interface instance

 Return Servers::httpd::Interface

=cut

sub factory
{
    return $PACKAGE->getInstance() if $PACKAGE;

    my $sname = ( split '::', $main::imscpConfig{'HTTPD_PACKAGE'} )[2] or die( "Couldn't guess httpd server implementation" );
    $PACKAGE = "Servers::httpd::${sname}::@{[ ucfirst $main::imscpConfig{'DISTRO_ID'} ] }";
    eval "require $PACKAGE; 1" or die( $@ );
    $PACKAGE->getInstance( eventManager => iMSCP::EventManager->getInstance());
}

=item can( $method )

 Checks if the httpd server package provides the given method

 Param string $method Method name
 Return subref|undef

=cut

sub can
{
    my (undef, $method) = @_;

    return $PACKAGE->can( $method ) if $PACKAGE;

    my $sname = ( split'::', $main::imscpConfig{'HTTPD_PACKAGE'} )[2] or die( "Couldn't guess httpd server implementation" );
    my $package = "Servers::httpd::${sname}::@{[ ucfirst $main::imscpConfig{'DISTRO_ID'} ] }";
    eval "require $package; 1" or die( $@ );
    $package->can( $method );
}

=item AUTOLOAD()

 Implement autoloading for inexistent methods

=cut

sub AUTOLOAD
{
    ( my $method = our $AUTOLOAD ) =~ s/.*:://;

    __PACKAGE__->factory()->$method( @_ );
}

=back

=head1 SHUTDOWN TASKS

=over 4

=item END

 Schedule restart, reload or start of httpd server when needed

=cut

END
    {
        return if $? || !$PACKAGE || ( defined $main::execmode && $main::execmode eq 'setup' );

        my $instance = $PACKAGE->hasInstance();

        return 0 unless $instance && ( my $action = $instance->{'restart'}
            ? 'restart' : ( $instance->{'reload'} ? 'reload' : ( $instance->{'start'} ? ' start' : undef ) ) );

        iMSCP::Service->getInstance()->registerDelayedAction(
            $instance->{'config'}->{'HTTPD_SNAME'}, [ $action, sub { $instance->$action(); } ], __PACKAGE__->getPriority()
        );
    }

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
