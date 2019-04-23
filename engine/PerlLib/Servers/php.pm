=head1 NAME

 Servers::noserver - i-MSCP PHP server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::php;

use strict;
use warnings;
use iMSCP::Debug 'error';
use iMSCP::Dir;
use iMSCP::Service;
use Servers::httpd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP PHP server implementation.

=head1 PUBLIC METHODS

=over 4

=item factory( )

 Create and return system server instance

 Return local server instance

=cut

sub factory
{
    my ( $class ) = @_;

    $class->getInstance();
}

=item getPriority( )

 Get server priority

 Return int Server priority

=cut

sub getPriority
{
    60;
}

=item preinstall( )

 Pre-installation tasks
 
 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    eval {
        my $service = iMSCP::Service->getInstance();
        for my $version (
            iMSCP::Dir->new( dirname => '/etc/php' )->getDirs()
        ) {
            next unless $version =~ /^[0-9.]+$/
                || $self->{'config'}->{'PHP_VERSION'} eq $version;

            $service->stop( sprintf( 'php%s-fpm', $version ));
            $service->disable( sprintf( 'php%s-fpm', $version ));
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::httpd::apache_php_fpm

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'config'} = Servers::httpd->factory()->{'phpConfig'};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
