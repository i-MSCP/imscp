#!/usr/bin/perl

=head1 NAME

 Servers::ftpd::proftpd::uninstaller - i-MSCP Proftpd Server implementation

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
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::ftpd::proftpd::uninstaller;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use File::Basename;
use iMSCP::File;
use Servers::ftpd::proftpd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Uninstaller for the i-MSCP Poftpd Server implementation.

=head1 PUBLIC METHODS

=over 4

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->restoreConfFile();
	return $rs if $rs;

	$rs = $self->removeDB();
	return $rs if $rs;

	$self->removeDirs();
}

=item removeDirs()

 Remove directories

 Return int 0 on success, other on failure

=cut

sub removeDirs
{
	my $self = $_[0];
	my $rs = 0;

	# TODO: if this is directory referenced in the restored conf file, it must not be removed. Otherwise proftpd WILL fail. For the time beeing, this is disabled
	#for("$main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd"){
	#	$rs = iMSCP::Dir->new('dirname' => $_)->remove();
	#	return $rs if $rs;
	#}

	0;
}

=item removeDB()

 Remove Database data

 Return int 0 on success, other on failure

=cut

sub removeDB
{
	my $self = $_[0];

	my $db = iMSCP::Database->factory();

	$db->doQuery('dummy', 'DROP USER ?@?', $self->{'config'}->{'DATABASE_USER'}, $main::imscpConfig{'DATABASE_USER_HOST'});
	$db->doQuery('dummy', 'FLUSH PRIVILEGES');

	0;
}

=item restoreConfFile()

 Restore system configuration file

 Return int 0 on success, other on failure

=cut

sub restoreConfFile
{
	my $self = $_[0];
	my $rs = 0;

	for ($self->{'config'}->{'FTPD_CONF_FILE'}) {
		my ($filename, $directories, $suffix) = fileparse($_);

		if(-f "$self->{bkpDir}/$filename$suffix.system") {
			$rs	= iMSCP::File->new('filename' => "$self->{'bkpDir'}/$filename$suffix.system")->copyFile($_);
			return $rs if $rs;
		}
	}

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance

 Return Servers::ftpd::proftpd::uninstaller

=cut

sub _init
{
	my $self = $_[0];

	$self->{'ftpd'} = Servers::ftpd::proftpd->getInstance();

	$self->{'cfgDir'} = $self->{'ftpd'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'config'} = $self->{'ftpd'}->{'config'};

	$self;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
