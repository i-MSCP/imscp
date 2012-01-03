#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
# @category		i-MSCP
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::ftpd::proftpd::uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Templator;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{

	my $self		= shift;

	$self->{cfgDir}	= "$main::imscpConfig{'CONF_DIR'}/proftpd";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";

	my $conf		= "$self->{cfgDir}/proftpd.data";

	tie %self::proftpdConfig, 'iMSCP::Config','fileName' => $conf;

	0;
}

sub uninstall{

	my $self	= shift;
	my $rs		= 0;

	$rs |= $self->restoreConfFile();
	$rs |= $self->removeDB();
	$rs |= $self->removeDirs();

	$rs;
}

sub removeDirs{

	use iMSCP::Dir;

	my $self	= shift;
	my $rs		= 0;

	for($self::proftpdConfig{FTPD_CONF_DIR}, "$main::imscpConfig{TRAFF_LOG_DIR}/proftpd"){

		$rs |= iMSCP::Dir->new(
			dirname => $_
		)->remove() if -d $_;
	}

	$rs;
}

sub removeDB{

	my $self		= shift;
	my $rs			= 0;
	my $err			= 0;
	my $database	= iMSCP::Database->new()->factory();

	if($self::proftpdConfig{DATABASE_USER}){

		$err = $database->doQuery(
			'delete',
			"DROP USER ?@?",
			$self::proftpdConfig{'DATABASE_USER'},
			'localhost'
		);

		$err = $database->doQuery(
			'delete',
			"DROP USER ?@?",
			$self::proftpdConfig{'DATABASE_USER'},
			'%'
		);

		$err = $database->doQuery('dummy', 'FLUSH PRIVILEGES');
		if (ref $err ne 'HASH'){
			error($err);
			$rs = 1;
		}
	}

	$rs;
}

sub restoreConfFile{

	use File::Basename;
	use iMSCP::File;

	my $self	= shift;
	my $rs		= 0;

	for (
		$self::proftpdConfig{FTPD_CONF_FILE}
	) {
		my ($filename, $directories, $suffix) = fileparse($_);
		if(-f "$self->{bkpDir}/$filename$suffix.system"){
			$rs	|=	iMSCP::File->new(
						filename => "$self->{bkpDir}/$filename$suffix.system"
					)->copyFile(
						$_
					);
		}
	}

	$rs;
}

1;
