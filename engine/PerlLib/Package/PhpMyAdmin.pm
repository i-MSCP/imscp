#!/usr/bin/perl

=head1 NAME

Package::PhpMyAdmin - i-MSCP PhpMyAdmin package

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
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
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Package::PhpMyAdmin;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::HooksManager;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 PhpMyAdmin package for i-MSCP

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

 Project homepage: : http://www.phpmyadmin.net/

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks(\%hooksManager)

 Register setup hook functions

 Param iMSCP::HooksManager instance
 Return int 0 on success, 1 on failure

=cut

sub registerSetupHooks($$)
{
	my ($self, $hooksManager) = @_;

	require Package::PhpMyAdmin::Installer;
	Package::PhpMyAdmin::Installer->getInstance()->registerSetupHooks($hooksManager);
}

=item preinstall()

 Process preinstall tasks

=cut

sub preinstall
{
	require Package::PhpMyAdmin::Installer;
	Package::PhpMyAdmin::Installer->getInstance()->preinstall();
}

=item install()

 Process install tasks

 Return int 0 on success, 1 on failure

=cut

sub install
{
	require Package::PhpMyAdmin::Installer;
	Package::PhpMyAdmin::Installer->getInstance()->install();
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	require Package::PhpMyAdmin::Uninstaller;
	Package::PhpMyAdmin::Uninstaller->getInstance()->uninstall();
}

=item setPermissionsListener()

 Set file permissions

 Return int 0 on success, other on failure

=cut

sub setPermissionsListener
{
	require Package::PhpMyAdmin::Installer;
	Package::PhpMyAdmin::Installer->getInstance()->setGuiPermissions();
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
	my $self = $_[0];

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/pma";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	tie %{$self->{'config'}}, 'iMSCP::Config', 'fileName' => "$self->{'cfgDir'}/phpmyadmin.data";

	# PhpMyAdmin permissions must be set after FrontEnd base permissions
	iMSCP::HooksManager->getInstance()->register(
		'afterFrontEndSetGuiPermissions', sub { $self->setPermissionsListener(@_) }
	);

	$self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
