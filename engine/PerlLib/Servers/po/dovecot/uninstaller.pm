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
# @version		SVN: $Id: installer.pm 5417 2011-10-05 20:17:21Z sci2tech $
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::po::dovecot::uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Execute;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{

	my $self		= shift;
	$self->{cfgDir}	= "$main::imscpConfig{'CONF_DIR'}/dovecot";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";

	my $conf		= "$self->{cfgDir}/dovecot.data";

	tie %self::dovecotConfig, 'iMSCP::Config','fileName' => $conf;

	0;
}

sub uninstall{

	my $self	= shift;
	my $rs		= 0;

	$rs |= $self->restoreConfFile();
	$rs |= $self->removeSQL();

	$rs;
}

sub restoreConfFile{

	my $self	= shift;
	my $rs		= 0;
	my $file;

	for ((
		'dovecot.conf',
		'dovecot-sql.conf'
	)) {
		$rs	|=	iMSCP::File->new(
					filename => "$self->{bkpDir}/$_.system"
				)->copyFile(
					"$self::dovecotConfig{'DOVECOT_CONF_DIR'}/$_"
				)
				if -f "$self->{bkpDir}/$_.system"
		;
	}

	use Servers::mta;
	my $mta	= Servers::mta->factory();

	for ('dovecot-sql.conf', 'dovecot-dict-sql.conf') {
		$file = iMSCP::File->new(filename => "$self::dovecotConfig{'DOVECOT_CONF_DIR'}/$_");
		$rs |= $file->mode(0640);
		$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $mta->{'MTA_MAILBOX_GID_NAME'});
	}

	$file	= iMSCP::File->new(filename => "$self::dovecotConfig{'DOVECOT_CONF_DIR'}/dovecot.conf");
	$rs |= $file->mode(0644);

	$rs;
}

sub removeSQL{

	my $self	= shift;
	my $rs		= 0;

	if($self::dovecotConfig{'DATABASE_USER'}) {

		my $database = iMSCP::Database->new()->factory();

		$database->doQuery( 'delete', "DROP USER ?@?", $self::dovecotConfig{'DATABASE_USER'}, 'localhost');
		$database->doQuery( 'delete', "DROP USER ?@?", $self::dovecotConfig{'DATABASE_USER'}, '%');
		$database->doQuery('dummy', 'FLUSH PRIVILEGES');

	}

	0;
}

1;
