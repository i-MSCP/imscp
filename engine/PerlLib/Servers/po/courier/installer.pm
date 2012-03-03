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

package Servers::po::courier::installer;

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
	$self->{cfgDir}	= "$main::imscpConfig{'CONF_DIR'}/courier";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";

	my $conf		= "$self->{cfgDir}/courier.data";
	my $oldConf		= "$self->{cfgDir}/courier.old.data";

	tie %self::courierConfig, 'iMSCP::Config','fileName' => $conf;
	tie %self::courierOldConfig, 'iMSCP::Config','fileName' => $oldConf if -f $oldConf;

	0;
}

sub migrateMailboxes{

	if(
		$main::imscpConfigOld{PO_SERVER}
		&&
		$main::imscpConfigOld{PO_SERVER} eq 'dovecot'
		&&
		$main::imscpConfig{PO_SERVER}  eq 'courier'
	){
		use iMSCP::Execute;
		use FindBin;
		use Servers::mta;

		my $mta	= Servers::mta->factory($main::imscpConfig{MTA_SERVER});
		my ($rs, $stdout, $stderr);
		my $binPath = "perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlVendor/courier-dovecot-migrate.pl";
		my $mailPath = "$mta->{'MTA_VIRTUAL_MAIL_DIR'}";

		$rs = execute("$binPath --to-courier --convert --recursive $mailPath", \$stdout, \$stderr);
		debug("$stdout...") if $stdout;
		warning("$stderr") if $stderr && !$rs;
		error("$stderr") if $stderr && $rs;
		error("Error while converting mails") if !$stderr && $rs;
	}

	0;
}

sub install{

	my $self	= shift;
	my $rs		= 0;

	# Saving all system configuration files if they exists
	for ((
		'authdaemonrc',
		'userdb',
		"$self::courierConfig{COURIER_IMAP_SSL}",
		"$self::courierConfig{COURIER_POP_SSL}"
	)) {
		$rs |= $self->bkpConfFile($_);
	}

	# authdaemonrc file
	$rs |= $self->authDaemon();

	# userdb file
	$rs |= $self->userDB();

	# SSL Conf files
	$rs |= $self->sslConf();

	$rs |= $self->saveConf();

	$rs |= $self->migrateMailboxes();

	$rs;
}

sub saveConf{

	use iMSCP::File;

	my $self	= shift;
	my $rs		= 0;
	my$file		= iMSCP::File->new(filename => "$self->{cfgDir}/courier.data");
	my $cfg		= $file->get() or return 1;

	$file = iMSCP::File->new(filename => "$self->{cfgDir}/courier.old.data");
	$rs |= $file->set($cfg);
	$rs |= $file->save();
	$rs |= $file->mode(0640);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs;
}

sub bkpConfFile{

	my $self		= shift;
	my $cfgFile		= shift;
	my $timestamp	= time;

	if(-f "$self::courierConfig{'AUTHLIB_CONF_DIR'}/$cfgFile"){
		my $file	= iMSCP::File->new(
						filename => "$self::courierConfig{'AUTHLIB_CONF_DIR'}/$cfgFile"
					);
		if(!-f "$self->{bkpDir}/$cfgFile.system") {
			$file->copyFile("$self->{bkpDir}/$cfgFile.system") and return 1;
		} else {
			$file->copyFile("$self->{bkpDir}/$cfgFile.$timestamp") and return 1;
		}
	}

	0;
}

sub authDaemon{

	my $self = shift;
	my ($rdata, $file);

	# Loading the system file from /etc/imscp/backup
	$file = iMSCP::File->new(filename => "$self->{bkpDir}/authdaemonrc.system");
	$rdata = $file->get();
	if (!$rdata){
		error("Error while reading $self->{bkpDir}/authdaemonrc.system");
		return 1 ;
	}

	# Building the new file (Adding the authuserdb module if needed)
	if($rdata !~ /^\s*authmodulelist="(?:.*)?authuserdb.*"$/gm) {
		$rdata =~ s/(authmodulelist=")/$1authuserdb /gm;
	}

	# Storing the new file in the working directory
	$file = iMSCP::File->new(filename => "$self->{wrkDir}/authdaemonrc");
	$file->set($rdata) and return 1;
	$file->save() and return 1;
	$file->mode(0660) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Installing the new file in the production directory
	$file->copyFile("$self::courierConfig{'AUTHLIB_CONF_DIR'}") and return 1;

	0;
}

sub userDB{

	my $self = shift;
	my ($rdata, $file);

	# Storing the new file in the working directory
	iMSCP::File->new(filename => "$self->{cfgDir}/userdb")->copyFile("$self->{wrkDir}") and return 1;

	# After build this file is world readable which is is bad
	# Permissions are inherited by production file
	$file = iMSCP::File->new(filename => "$self->{wrkDir}/userdb");
	$file->mode(0600) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Installing the new file in the production directory
	$file->copyFile("$self::courierConfig{'AUTHLIB_CONF_DIR'}") and return 1;

	$file = iMSCP::File->new(filename => "$self::courierConfig{'AUTHLIB_CONF_DIR'}/userdb");
	$file->mode(0600) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Creating/Updating userdb.dat file from the contents of the userdb file
	my ($rs, $stdout, $stderr);
	$rs = execute($self::courierConfig{'CMD_MAKEUSERDB'}, \$stdout, \$stderr);
	debug("$stdout") if ($stdout);
	if($rs){
		error("$stderr") if $stderr;
		error("Error while executing $self::courierConfig{CMD_MAKEUSERDB} returned status $rs") unless $stderr;
		return $rs;
	}

	0;
}

sub sslConf{

	my $self	= shift;
	my $rs		= 0;
	my ($rdata, $file);

	for (($self::courierConfig{'COURIER_IMAP_SSL'}, $self::courierConfig{'COURIER_POP_SSL'})) {

		#if ssl is not enabled
		last if lc($main::imscpConfig{'SSL_ENABLED'}) ne 'yes';


		$file = iMSCP::File->new(filename => "$self::courierConfig{'AUTHLIB_CONF_DIR'}/$_");
		#read file exit if can not read
		$rdata = $file->get();
		if (!$rdata){
			$rs |= 1;
			error("Error while reading $self::courierConfig{'AUTHLIB_CONF_DIR'}/$_");
			next;
		}

		#if ssl conf not in place we add if
		if($rdata =~ m/^TLS_CERTFILE=/msg){
			$rdata =~ s!^TLS_CERTFILE=.*$!TLS_CERTFILE=$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem!mg;
		} else {
			$rdata .= "TLS_CERTFILE=$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem";
		}

		$file = iMSCP::File->new(filename => "$self->{wrkDir}/$_");
		$rs |= $file->set($rdata);
		$rs |= $file->save();
		$rs |= $file->mode(0644);
		$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		# Installing the new file in the production directory
		$rs |= $file->copyFile("$self::courierConfig{'AUTHLIB_CONF_DIR'}");
	}

	$rs;
}

sub registerHooks{
	my $self = shift;

	use Servers::mta;

	my $mta = Servers::mta->factory($main::imscpConfig{MTA_SERVER});

	$mta->registerPostHook(
		'buildConf', sub { return $self->mtaConf(@_); }
	) if $mta->can('registerPostHook');

	0;
}

sub mtaConf{

	my $self	= shift;
	my $content	= shift || '';

	use iMSCP::Templator;
	use Servers::mta;

	my $mta	= Servers::mta->factory($main::imscpConfig{MTA_SERVER});

	my $poBloc = getBloc(
		"$mta->{commentChar} courier begin",
		"$mta->{commentChar} courier end",
		$content
	);

	$content = replaceBloc(
		"$mta->{commentChar} po setup begin",
		"$mta->{commentChar} po setup end",
		$poBloc,
		$content,
		undef
	);

	#register again wait next config file
	$mta->registerPostHook(
		'buildConf', sub { return $self->mtaConf(@_); }
	) if $mta->can('registerPostHook');

	$content;
}

1;
