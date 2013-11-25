#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
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

sub _init
{
	my $self = shift;

	$self->{'ftpd'} = Servers::ftpd::proftpd->getInstance();

	$self->{'cfgDir'} = $self->{'ftpd'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'config'} = $self->{'ftpd'}->{'config'};

	$self;
}

sub uninstall
{
	my $self = shift;

	my $rs = $self->restoreConfFile();
	return $rs if $rs;

	$rs = $self->removeDB();
	return $rs if $rs;

	$self->removeDirs();
}

sub removeDirs
{
	my $self = shift;
	my $rs = 0;

	# TODO: if this is directory referenced in the restored conf file, it must not be removed. Otherwise proftpd WILL fail. For the time beeing, this is disabled
	#for("$main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd"){
	#	$rs = iMSCP::Dir->new('dirname' => $_)->remove();
	#	return $rs if $rs;
	#}

	0;
}

sub removeDB
{
	my $self = shift;
	my $rs = 0;
	my $err = 0;
	my $database = iMSCP::Database->new()->factory();

	if($self->{'config'}->{'DATABASE_USER'}){
		$err = $database->doQuery('delete', "DROP USER ?@?", $self->{'config'}->{'DATABASE_USER'}, 'localhost');
		$err = $database->doQuery('delete', "DROP USER ?@?", $self->{'config'}->{'DATABASE_USER'}, '%');
		$err = $database->doQuery('dummy', 'FLUSH PRIVILEGES');

		unless (ref $err eq 'HASH'){
			error($err);
			$rs = 1;
		}
	}

	$rs;
}

sub restoreConfFile
{
	my $self = shift;
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

1;
