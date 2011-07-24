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
# @copyright	2010 - 2011 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id: installer.pm 4856 2011-07-11 08:48:54Z sci2tech $
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::po::dovecot::installer;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Execute;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{
	debug((caller(0))[3].': Starting...');

	my $self		= shift;
	$self->{cfgDir}	= "$main::imscpConfig{'CONF_DIR'}/dovecot";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";

	my $conf		= "$self->{cfgDir}/dovecot.data";

	tie %main::dovecotConfig, 'iMSCP::Config','fileName' => $conf;

	debug((caller(0))[3].': Ending...');
	0;
}

sub install{
	debug((caller(0))[3].': Starting...');

	my $self = shift;

	# Saving all system configuration files if they exists
	for ((
		'dovecot.conf',
		#'userdb',
		#"$main::imscpConfig{COURIER_IMAP_SSL}",
		#"$main::imscpConfig{COURIER_POP_SSL}"
	)) {
		$self->bkpConfFile($_) and return 1;
	}


	debug((caller(0))[3].': Ending...');
	0;
}

sub bkpConfFile{
	debug((caller(0))[3].': Starting...');

	my $self		= shift;
	my $cfgFile		= shift;
	my $timestamp	= time;

	if(-f "$main::imscpConfig{'DOVECOT_CONF_DIR'}/$cfgFile"){
		my $file	= iMSCP::File->new(
						filename => "$main::imscpConfig{'DOVECOT_CONF_DIR'}/$cfgFile"
					);
		if(!-f "$self->{bkpDir}/$cfgFile.system") {
			$file->copyFile("$self->{bkpDir}/$cfgFile.system") and return 1;
		} else {
			$file->copyFile("$self->{bkpDir}/$cfgFile.$timestamp") and return 1;
		}
	}

	debug((caller(0))[3].': Ending...');
	0;
}

1;
