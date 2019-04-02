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
use File::Basename 'dirname';
use Class::Autouse qw/ :nostat iMSCP::Composer /;
use iMSCP::Boolean;
use iMSCP::Debug qw/ debug error /;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Getopt;
use parent 'Common::SingletonClass';

use subs qw/
    registerSetupListeners setupDialog

    preinstall install postinstall uninstall

    setGuiPermissions setEnginePermissions

    preaddDomain preaddCustomDNS preaddFtpUser preaddHtaccess preaddHtgroup preaddHtpasswd preaddMail preaddServerIP preaddSSLcertificate preaddSub preaddUser
    addDomain addCustomDNS addFtpUser addHtaccess addHtgroup addHtpasswd addMail addServerIP addSSLcertificate addSub addUser
    postaddDomain postaddCustomDNS postaddFtpUser postaddHtaccess postaddHtgroup postaddHtpasswd postaddMail postaddServerIP postaddSSLcertificate postaddSub postaddUser

    predeleteDmn predeleteCustomDNS predeleteFtpUser predeleteHtaccess predeleteHtgroup predeleteHtpasswd predeleteMail predeleteServerIP predeleteSSLcertificate predeleteSub predeleteUser
    deleteDmn deleteCustomDNS deleteFtpUser deleteHtaccess deleteHtgroup deleteHtpasswd deleteMail deleteServerIP deleteSSLcertificate deleteSub deleteUser
    postdeleteDmn postdeleteCustomDNS postdeleteFtpUser postdeleteHtaccess postdeleteHtgroup postdeleteHtpasswd postdeleteMail postdeleteServerIP postdeleteSSLcertificate postdeleteSub postdeleteUser

    prerestoreDmn prerestoreCustomDNS prerestoreFtpUser prerestoreHtaccess prerestoreHtgroup prerestoreHtpasswd prerestoreMail prerestoreServerIP prerestoreSSLcertificate prerestoreSub prerestoreUser
    restoreDmn restoreCustomDNS restoreFtpUser restoreHtaccess restoreHtgroup restoreHtpasswd restoreMail restoreServerIP restoreSSLcertificate restoreSub restoreUser
    postrestoreDmn postrestoreCustomDNS postrestoreFtpUser postrestoreHtaccess postrestoreHtgroup postrestoreHtpasswd postrestoreMail postrestoreServerIP postrestoreSSLcertificate postrestoreSub postrestoreUser

    predisableDmn predisableCustomDNS predisableFtpUser predisableHtaccess predisableHtgroup predisableHtpasswd predisableMail predisableServerIP predisableSSLcertificate predisableSub predisableUser
    disableDmn disableCustomDNS disableFtpUser disableHtaccess disableHtgroup disableHtpasswd disableMail disableServerIP disableSSLcertificate disableSub disableUser
    postdisableDmn postdisableCustomDNS postdisableFtpUser postdisableHtaccess postdisableHtgroup postdisableHtpasswd postdisableMail postdisableServerIP dpostisableSSLcertificate postdisableSub postdisableUser
/;

my $packageVersionConstraint = '2.9.x-dev';

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

=item registerSetupListeners( \%em )

 Register setup event listeners

 Param iMSCP::EventManager \%em
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( undef, $em ) = @_;

    return 0 if iMSCP::Getopt->skipComposerUpdate;

    $em->registerOne( 'beforeSetupPreInstallServers', sub {
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

=item setupDialog( \%dialog )

 Setup dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 NEXT, 30 BACKUP, 50 ESC

=cut

sub setupDialog
{
    0;
}

=item preinstall( )

 Process pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    if ( -f "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/monsta-ftp/src/Handler.pm" ) {
        my $rs = iMSCP::File->new(
            filename => "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/monsta-ftp/src/Handler.pm"
        )->copyFile( "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/WebFtpClients/MonstaFTP/Handler.pm" );
        return $rs if $rs;
    } else {
        error( "Couldn't find the MonstaFTP package handler in the $::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/monsta-ftp/src directory" );
        return 1;
    }

    if ( my $sub = $self->_getHandler()->can( 'preinstall' ) ) {
        return $sub->( $self->_getHandler());
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

    if ( my $sub = $self->_getHandler()->can( 'uninstall' ) ) {
        my $rs = $sub->( $self->_getHandler());
        return $rs if $rs;
    }

    if ( -f "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/WebFtpClients/MonstaFTP/Handler.pm" ) {
        return iMSCP::File->new( filename => "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/WebFtpClients/MonstaFTP/Handler.pm" )->delFile();
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

    0;
}

=item AUTOLOAD

 Provide autoloading

 Return int 0 on success, other on failure

=cut

sub AUTOLOAD
{
    my $self = shift;
    ( my $method = our $AUTOLOAD ) =~ s/.*:://;

    my $handlerInstance = $self->_getHandler();

    if ( my $sub = $handlerInstance->can( $method ) ) {
        return $sub->( $handlerInstance, @_ );
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Package::WebFtpClients::MonstaFTP::MonstaFTP

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
}

=item _getHandler( )

 Get MonstaFTP package handler

 Return Package::WebFtpClients::MonstaFTP::Handler|Package::NoHandler

=cut

sub _getHandler
{
    my ( $self ) = @_;

    $self->{'_handler'} //= do {
        local $@;
        # We need process this way because @INC entries are not always identical (setup/reconfiguration vs production)
        # handlers are always installed in production directory (e.g. /var/www/imscp/engine/PerlLib/Package/<PackageType>/<Package>/)
        eval { require "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/WebFtpClients/MonstaFTP/Handler.pm" };
        if ( $@ ) {
            require Package::NoHandler;
            return Package::NoHandler->new();
        }

        Package::WebFtpClients::MonstaFTP::Handler->new();
    };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
