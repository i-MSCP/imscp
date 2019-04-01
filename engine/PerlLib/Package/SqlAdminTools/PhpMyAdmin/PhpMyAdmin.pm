=head1 NAME

 Package::SqlAdminTools::PhpMyAdmin::PhpMyAdmin - PhpMyAdmin package

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

package Package::SqlAdminTools::PhpMyAdmin::PhpMyAdmin;

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

my $packageVersionConstraint = '^1.0';

=head1 DESCRIPTION

 PhpMyAdmin allows administering of MySQL with a web interface.

 It allows administrators to:
 * browse through databases and tables;
 * create, copy, rename, alter and drop databases;
 * create, copy, rename, alter and drop tables;
 * perform table maintenance;
 * add, edit and drop fields;
 * execute any SQL-statement, even multiple queries;
 * create, alter and drop indexes;
 * load text files into tables;
 * create and read dumps of tables or databases;
 * export data to SQL, CSV, XML, Word, Excel, PDF and LaTeX formats;
 * administer multiple servers;
 * manage MySQL users and privileges;
 * check server settings and runtime information with configuration hints;
 * check referential integrity in MyISAM tables;
 * create complex queries using Query-by-example (QBE), automatically connecting required tables;
 * create PDF graphics of database layout;
 * search globally in a database or a subset of it;
 * transform stored data into any format using a set of predefined functions, such as displaying BLOB-data as image or download-link;
 * manage InnoDB tables and foreign keys;
 and is fully internationalized and localized in dozens of languages.

 Project homepage: http://www.phpmyadmin.net/

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
                ->require( 'imscp/phpmyadmin', $packageVersionConstraint )
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

    if ( -f "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/phpmyadmin/src/Handler.pm" ) {
        my $rs = iMSCP::File->new(
            filename => "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/phpmyadmin/src/Handler.pm"
        )->copyFile( "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/SqlAdminTools/PhpMyAdmin/Handler.pm" );
        return $rs if $rs;
    } else {
        error( "Couldn't find the PhpMyAdmin package handler in the $::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/phpmyadmin/src directory" );
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

    if ( -f "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/SqlAdminTools/PhpMyAdmin/Handler.pm" ) {
        return iMSCP::File->new( filename => "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/SqlAdminTools/PhpMyAdmin/Handler.pm" )->delFile();
    }

    eval {
        iMSCP::Composer->new(
            user          => $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
            composer_home => "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/.composer",
            composer_json => 'composer.json'
        )
            ->remove( 'imscp/phpmyadmin' )
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

 Return Package::SqlAdminTools::PhpMyAdmin::PhpMyAdmin

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
}

=item _getHandler( )

 Get PhpMyAdmin package handler

 Return Package::SqlAdminTools::PhpMyAdmin::Handler|Package::NoHandler

=cut

sub _getHandler
{
    my ( $self ) = @_;

    $self->{'_handler'} //= do {
        local $@;
        # We need process this way because @INC entries are not always identical (setup/reconfiguration vs production)
        # handlers are always installed in production directory (e.g. /var/www/imscp/engine/PerlLib/Package/<PackageType>/<Package>/)
        eval { require "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/SqlAdminTools/PhpMyAdmin/Handler.pm" };
        if ( $@ ) {
            require Package::NoHandler;
            return Package::NoHandler->new();
        }

        Package::SqlAdminTools::PhpMyAdmin::Handler->new();
    };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
