#!/usr/bin/perl

=head1 NAME

Package::Roundcube::Uninstaller - i-MSCP Roundcube package uninstaller

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

package Package::Roundcube::Uninstaller;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Execute;
use Package::Roundcube;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Roundcube package uninstaller

=head1 PUBLIC METHODS

=over 4

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->_removeSqlUser();
	return $rs if $rs;

	$rs = $self->_removeSqlDatabase();
	return $rs if $rs;

	$self->_removeFiles();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance

 Return Package::Roundcube::Uninstaller

=cut

sub _init
{
	my $self = shift;

	$self->{'roundcube'} = Package::Roundcube->getInstance();

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/roundcube";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'config'} = $self->{'roundcube'}->{'config'};

	$self;
}

=item _removeSqlUser()

 Remove any Roundcube SQL user

 Return int 0

=cut

sub _removeSqlUser
{
	my $self = shift;

	my $database = iMSCP::Database->factory();

	# We do not catch any error here - It's expected
	for($main::imscpConfig{'DATABASE_USER_HOST'}, $main::imscpConfig{'BASE_SERVER_IP'}, 'localhost', '127.0.0.1', '%') {
		next if ! $_;
		$database->doQuery('dummy', "DROP USER ?@?", $self->{'config'}->{'DATABASE_USER'}, $_);
	}

	$database->doQuery('dummy', 'FLUSH PRIVILEGES');

	0;
}

=item _removeSqlDatabase()

 Remove Roundcube SQL database

 Return int 0

=cut

sub _removeSqlDatabase
{
	my $self = shift;

	my $database = iMSCP::Database->factory();

	my $dbName = $database->quoteIdentifier($main::imscpConfig{'DATABASE_NAME'} . '_pma');

	$database->doQuery('delete', "DROP DATABASE IF EXISTS $dbName");

	0;
}

=item _removeFiles()

 Remove Roundcube files

 Return int 0

=cut

sub _removeFiles
{
	my $self = shift;

	my ($stdout, $stderr);

	if(-d "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail") {
		my $rs = execute(
			"$main::imscpConfig{'CMD_RM'} -fR $main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail", \$stdout, \$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	if(-d $self->{'cfgDir'}) {
		my $rs = execute("$main::imscpConfig{'CMD_RM'} -fR $self->{'cfgDir'}", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
