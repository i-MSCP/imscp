=head1 NAME

 Package::WebFtpClients::MonstaFTP::MonstaFTP - MonstaFTP package

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

package Package::WebFtpClients::MonstaFTP::MonstaFTP;

use strict;
use warnings;
use Class::Autouse qw/ :nostat iMSCP::Composer /;
use iMSCP::Boolean;
use iMSCP::Debug 'error';
use iMSCP::File;
use iMSCP::Getopt;
use parent 'Common::SingletonClass';
use subs qw/
    registerSetupListeners
    preinstall install postinstall uninstall
    setGuiPermissions setEnginePermissions
/;

my $packageVersionConstraint = '^1.0';

=head1 DESCRIPTION

 i-MSCP MonstaFTP package.

 MonstaFTP is a web-based FTP client written in PHP.

 Project homepage: http://www.monstaftp.com//

=head1 PUBLIC METHODS

=over 4

=item getPriority( )

 Get package priority

 Return int package priority

=cut

sub getPriority
{
    0;
}

=item registerSetupListeners( \%events )

 Register setup event listeners

 Param iMSCP::EventManager \%events
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( undef, $events ) = @_;

    return 0 if iMSCP::Getopt->skipComposerUpdate;

    $events->registerOne( 'beforeSetupPreInstallServers', sub {
        eval {
            iMSCP::Composer->new(
                user          => $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
                composer_home => "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/.composer",
                composer_json => 'composer.json'
            )
                ->require( 'imscp/monsta-ftp', $packageVersionConstraint )
                ->dumpComposerJson();
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        0;
    }, 10 );
}

=item preinstall( )

 Process pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    unless ( -f "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/monsta-ftp/src/Handler.pm" ) {
        error( "Couldn't find the MonstaFTP package handler in the $::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/monsta-ftp/src directory" );
        return 1;
    }

    my $rs = iMSCP::File->new(
        filename => "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/monsta-ftp/src/Handler.pm"
    )->copyFile( "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/WebFtpClients/MonstaFTP/Handler.pm" );
    return $rs if $rs;

    local $@;
    my $handler = eval { $self->_getHandler(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    if ( my $sub = $handler->can( 'preinstall' ) ) {
        return $sub->( $handler );
    }

    0;
}

=item uninstall( )

 Process uninstallation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ( $self ) = @_;

    return 0 unless -f "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/WebFtpClients/MonstaFTP/Handler.pm";

    local $@;
    my $handler = eval { $self->_getHandler(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    if ( my $sub = $handler->can( 'uninstall' ) ) {
        my $rs = $sub->( $handler );
        return $rs if $rs;
    }

    eval {
        iMSCP::Composer->new(
            user          => $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
            composer_home => "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/.composer",
            composer_json => 'composer.json'
        )
            ->remove( 'imscp/monsta-ftp' )
            ->dumpComposerJson();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    iMSCP::File->new( filename => "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/WebFtpClients/MonstaFTP/Handler.pm" )->delFile();
}

=item AUTOLOAD

 Provides autoloading

 Return int 0 on success, other on failure

=cut

sub AUTOLOAD
{
    my $self = shift;
    ( my $method = our $AUTOLOAD ) =~ s/.*:://;

    local $@;
    my $handler = eval { $self->_getHandler(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    if ( my $sub = $handler->can( $method ) ) {
        return $sub->( $handler, @_ );
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _getHandler( )

 Get MonstaFTP package handler instance

 Return Package::WebFtpClients::MonstaFTP::Handler, die on failure

=cut

sub _getHandler
{
    my ( $self ) = @_;

    $self->{'_handler'} //= do {
        require "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/WebFtpClients/MonstaFTP/Handler.pm";
        Package::WebFtpClients::MonstaFTP::Handler->new();
    };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
