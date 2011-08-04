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
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::named::bind::installer;

use strict;
use warnings;
use iMSCP::Debug;


use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{
	debug((caller(0))[3].': Starting...');

	my $self		= shift;

	$self->{cfgDir}	= "$main::imscpConfig{'CONF_DIR'}/bind";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";

	my $conf		= "$self->{cfgDir}/bind.data";
	my $oldConf		= "$self->{cfgDir}/bind.old.data";

	tie %self::bindConfig, 'iMSCP::Config','fileName' => $conf;
	tie %self::bindOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1 if -f $oldConf;

	debug((caller(0))[3].': Ending...');
	0;
}

sub buildConf{
	debug((caller(0))[3].': Starting...');

	use iMSCP::File;

	my $self		= shift;
	my ($rs, $rdata, $cfgTpl, $cfg, $err);

	## Building new configuration file

	# Loading the system main configuration file named.conf.system if it exists
	if(-f "$self->{bkpDir}/named.conf.system") {
		$cfg = iMSCP::File->new(filename => "$self->{bkpDir}/named.conf.system")->get();
		return 1 if(!$cfg);

		# Adjusting the configuration if needed
		$cfg =~ s/listen-on ((.*) )?{ 127.0.0.1; };/listen-on $1 { any; };/;
		$cfg .= "\n";
	} else {
		warning((caller(0))[3].": Can't find the default distribution file for named...");
		$cfg = '';
	}

	# Loading the template from /etc/imscp/bind/named.conf
	$cfgTpl = iMSCP::File->new(filename => "$self->{cfgDir}/named.conf")->get();
	return 1 if(!$cfgTpl);

	# Building new file
	$cfg .= $cfgTpl;

	## Storage and installation of new file

	# Storing new file in the working directory
	my $file = iMSCP::File->new(filename => "$self->{wrkDir}/named.conf");
	$file->set($cfg) and return 1;
	$file->save() and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
	$file->mode(0644) and return 1;

	# Install the new file in the production directory
	$file->copyFile($self::bindConfig{'BIND_CONF_FILE'}) and return 1;

	debug((caller(0))[3].': Ending...');
	0;
}

sub install{
	debug((caller(0))[3].': Starting...');

	my $self = shift;

	# Saving all system configuration files if they exists
	for ((
		$self::bindConfig{'BIND_CONF_FILE'}
	)) {
		$self->bkpConfFile($_) and return 1;
	}

	$self->buildConf() and return 1;

	$self->askMode() and return 1;

	$self->saveConf() and return 1;

	$self->oldEngineCompatibility() and return 1;

	debug((caller(0))[3].': Ending...');
	0;
}

sub askMode{
	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;

	my $self = shift;

	if($self::bindConfig{'BIND_MODE'}){
		debug((caller(0))[3].': Ending...');
		return 0;
	}

	if($self::bindOldConfig{'BIND_MODE'}){
		$self::bindConfig{'BIND_MODE'} = $self::bindOldConfig{'BIND_MODE'};
		debug((caller(0))[3].': Ending...');
		return 0;
	}

	my $out;
	while (! ($out = iMSCP::Dialog->factory()->radiolist("Select bind mode", 'primary', 'secondary'))){}
	$self::bindConfig{'BIND_MODE'} = $out;

	$self->askOtherDNS();

	debug((caller(0))[3].': Ending...');
	0;
}

sub askOtherDNS{

	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;

	my $self = shift;
	my $out;

	if($self::bindConfig{'BIND_MODE'} eq 'primary'){
		while (! ($out = iMSCP::Dialog->factory()->radiolist("Enable secondary DNS server address IP?", 'no', 'yes'))){}
		if($out eq 'no'){
			$self::bindConfig{'SECONDARY_DNS'} = 'no';
			debug((caller(0))[3].': Ending...');
			return 0;
		}
	}

	use Data::Validate::IP qw/is_ipv4/;

	my $mode = $self::bindConfig{'BIND_MODE'} eq 'primary' ? 'secondary' : 'primary';

	do{
		while (! ($out = iMSCP::Dialog->factory()->inputbox("Please enter $mode DNS server address IP"))){}
	}while(! (is_ipv4($out) && $out ne '127.0.0.1') );

	$self::bindConfig{
			$self::bindConfig{'BIND_MODE'} eq 'primary'
			?
			'SECONDARY_DNS'
			:
			'PRIMARY_DNS'
	} = $out;

	debug((caller(0))[3].': Ending...');
	0;
}

sub bkpConfFile{
	debug((caller(0))[3].': Starting...');

	use File::Basename;

	my $self		= shift;
	my $cfgFile		= shift;
	my $timestamp	= time;

	if(-f $cfgFile){
		my $file	= iMSCP::File->new( filename => $cfgFile );
		my ($filename, $directories, $suffix) = fileparse($cfgFile);
		if(!-f "$self->{bkpDir}/$filename$suffix.system") {
			$file->copyFile("$self->{bkpDir}/$filename$suffix.system") and return 1;
		} else {
			$file->copyFile("$self->{bkpDir}/$filename$suffix.$timestamp") and return 1;
		}
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub saveConf{
	debug((caller(0))[3].': Starting...');

	use iMSCP::File;

	my $self		= shift;
	my $file = iMSCP::File->new(filename => "$self->{cfgDir}/bind.data");
	my $cfg = $file->get() or return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	$file = iMSCP::File->new(filename => "$self->{cfgDir}/bind.old.data");
	$file->set($cfg) and return 1;
	$file->save and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	debug((caller(0))[3].': Ending...');
	0;
}

sub oldEngineCompatibility{
	debug((caller(0))[3].': Starting...');

	for((
		'BIND_MODE',
		'BIND_CONF_FILE',
		'BIND_DB_DIR',
		'CMD_NAMED',
		'SECONDARY_DNS'
	)){
		$main::imscpConfig{$_} = $self::bindConfig{$_} if !$main::imscpConfig{$_} || $main::imscpConfig{$_} ne $self::bindConfig{$_};
	}

	debug((caller(0))[3].': Ending...');
	0;
}

1;
