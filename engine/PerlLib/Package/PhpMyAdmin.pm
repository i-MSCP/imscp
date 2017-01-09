=head1 NAME

Package::PhpMyAdmin - i-MSCP PhpMyAdmin package

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

package Package::PhpMyAdmin;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Package::PhpMyAdmin::Installer Package::PhpMyAdmin::Uninstaller /;
use iMSCP::Config;
use iMSCP::EventManager;
use iMSCP::Rights;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 PhpMyAdmin package for i-MSCP.

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

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my (undef, $eventManager) = @_;

    Package::PhpMyAdmin::Installer->getInstance()->registerSetupListeners( $eventManager );
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    Package::PhpMyAdmin::Uninstaller->getInstance()->uninstall();
}

=item setGuiPermissionsListener()

 Set gui permissions event listener

 Return int 0 on success, other on failure

=cut

sub setGuiPermissionsListener
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpMyAdminSetGuiPermissions' );
    return $rs if $rs;

    return 0 unless -d "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma";

    my $panelUName = my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};

    $rs ||= setRights(
        "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma",
        {
            user      => $panelUName,
            group     => $panelGName,
            dirmode   => '0550',
            filemode  => '0440',
            recursive => 1
        }
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpMyAdminSetGuiPermissions' );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::PhpMyAdmin

=cut

sub _init
{
    my $self = shift;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/pma";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    tie %{$self->{'config'}}, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/phpmyadmin.data", readonly => 1;
    $self->{'eventManager'}->register('afterFrontendSetGuiPermissions', sub { $self->setGuiPermissionsListener(); });
    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
