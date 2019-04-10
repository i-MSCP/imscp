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
use Class::Autouse qw/ :nostat iMSCP::Composer /;
use iMSCP::Boolean;
use iMSCP::Debug 'error';
use iMSCP::EventManager;
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

=item preinstall( )

 Process pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    unless ( -f "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/phpmyadmin/src/Handler.pm" ) {
        error( "Couldn't find the PhpMyAdmin package handler in the $::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/phpmyadmin/src directory" );
        return 1;
    }

    my $rs = iMSCP::File->new(
        filename => "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/phpmyadmin/src/Handler.pm"
    )->copyFile( "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/SqlAdminTools/PhpMyAdmin/Handler.pm" );
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

    return 0 unless -f "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/SqlAdminTools/PhpMyAdmin/Handler.pm";

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
            ->remove( 'imscp/phpmyadmin' )
            ->dumpComposerJson();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    iMSCP::File->new( filename => "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/SqlAdminTools/PhpMyAdmin/Handler.pm" )->delFile();
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

 Get PhpMyAdmin package handler instance

 Return Package::SqlAdminTools::PhpMyAdmin::Handler, die on failure

=cut

sub _getHandler
{
    my ( $self ) = @_;

    $self->{'_handler'} //= do {
        require "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/SqlAdminTools/PhpMyAdmin/Handler.pm";
        Package::SqlAdminTools::PhpMyAdmin::Handler->new();
    };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
