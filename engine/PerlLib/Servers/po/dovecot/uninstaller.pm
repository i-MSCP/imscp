#!/usr/bin/perl

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

package Servers::po::dovecot::uninstaller;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Database;
use Servers::po::dovecot;
use Servers::mta::postfix;
use parent 'Common::SingletonClass';

sub uninstall
{
	my $self = shift;

	my $rs = $self->_restoreConfFile();
	return $rs if $rs;

	$self->_dropSqlUser();
}

sub _init
{
	my $self = shift;

	$self->{'po'} = Servers::po::dovecot->getInstance();
	$self->{'mta'} = Servers::mta::postfix->getInstance();

	$self->{'cfgDir'} = $self->{'po'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'config'} = $self->{'po'}->{'config'};

	$self;
}

sub _restoreConfFile
{
	my $self = shift;

	my $rs = 0;

	for ('dovecot.conf', 'dovecot-sql.conf') {
		$rs = iMSCP::File->new(
			'filename' => "$self->{bkpDir}/$_.system"
		)->copyFile(
			"$self->{'config'}->{'DOVECOT_CONF_DIR'}/$_"
		) if -f "$self->{bkpDir}/$_.system";
		return $rs if $rs;
	}

	my $file = iMSCP::File->new('filename' => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf");

	$rs = $file->mode(0644);
	return $rs if $rs;

	$file->owner($main::imscpConfig{'ROOT_USER'}, $self->{'mta'}->{'MTA_MAILBOX_GID_NAME'});
}

sub _dropSqlUser
{
	my $self = shift;

	if($self->{'config'}->{'DATABASE_USER'}) {
		my $database = iMSCP::Database->factory();

		$database->doQuery('delete', 'DROP USER ?@?', $self->{'config'}->{'DATABASE_USER'}, 'localhost');
		$database->doQuery('delete', 'DROP USER ?@?', $self->{'config'}->{'DATABASE_USER'}, '%');
		$database->doQuery(
			'delete', 'DROP USER ?@?', $self->{'config'}->{'DATABASE_USER'}, $main::imscpConfig{'DATABASE_USER_HOST'}
		);
		$database->doQuery('dummy', 'FLUSH PRIVILEGES');

	}

	0;
}

1;
