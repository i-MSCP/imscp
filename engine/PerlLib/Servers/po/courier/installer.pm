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
	debug((caller(0))[3].': Starting...');

	my $self		= shift;
	$self->{cfgDir}	= "$main::imscpConfig{'CONF_DIR'}/courier";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";

	debug((caller(0))[3].': Ending...');
	0;
}

sub migrateMailboxes{
	debug((caller(0))[3].': Starting...');

	if(
		$main::imscpConfigOld{PO_SERVER} eq 'dovecot'
		&&
		$main::imscpConfig{PO_SERVER}  eq 'courier'
	){
		use iMSCP::Execute;
		use FindBin;
		use Servers::mta;

		my $mta	= Servers::mta->factory($main::imscpConfig{MTA_SERVER});
		my ($rs, $stdout, $stderr);
		my $binPath = "$FindBin::Bin/../PerlVendor/courier-dovecot-migrate.pl";
		my $mailPath = "$mta->{'MTA_VIRTUAL_MAIL_DIR'}";

		$rs = execute("$binPath --to-courier --convert --recursive $mailPath", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout...") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		error((caller(0))[3].": Error while converting mails") if !$stderr && $rs;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub install{
	debug((caller(0))[3].': Starting...');

	my $self = shift;

	# Saving all system configuration files if they exists
	for ((
		'authdaemonrc',
		'userdb',
		"$main::imscpConfig{COURIER_IMAP_SSL}",
		"$main::imscpConfig{COURIER_POP_SSL}"
	)) {
		$self->bkpConfFile($_) and return 1;
	}

	# authdaemonrc file
	$self->authDaemon() and return 1;

	# userdb file
	$self->userDB() and return 1;

	# SSL Conf files
	$self->sslConf() and return 1;

	$self->migrateMailboxes() and return 1;

	debug((caller(0))[3].': Ending...');
	0;
}

sub bkpConfFile{
	debug((caller(0))[3].': Starting...');

	my $self		= shift;
	my $cfgFile		= shift;
	my $timestamp	= time;

	if(-f "$main::imscpConfig{'AUTHLIB_CONF_DIR'}/$cfgFile"){
		my $file	= iMSCP::File->new(
						filename => "$main::imscpConfig{'AUTHLIB_CONF_DIR'}/$cfgFile"
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

sub authDaemon{
	debug((caller(0))[3].': Starting...');

	my $self = shift;
	my ($rdata, $file);

	# Loading the system file from /etc/imscp/backup
	$file = iMSCP::File->new(filename => "$self->{bkpDir}/authdaemonrc.system");
	$rdata = $file->get();
	return 1 if (!$rdata);

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
	$file->copyFile("$main::imscpConfig{'AUTHLIB_CONF_DIR'}") and return 1;


	debug((caller(0))[3].': Ending...');
	0;
}

sub userDB{
	debug((caller(0))[3].': Starting...');

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
	$file->copyFile("$main::imscpConfig{'AUTHLIB_CONF_DIR'}") and return 1;

	$file = iMSCP::File->new(filename => "$main::imscpConfig{'AUTHLIB_CONF_DIR'}/userdb");
	$file->mode(0600) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Creating/Updating userdb.dat file from the contents of the userdb file
	my ($rs, $stdout, $stderr);
	$rs = execute($main::imscpConfig{'CMD_MAKEUSERDB'}, \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if ($stdout);
	error((caller(0))[3].": $stderr") if ($stderr && $rs);
	return $rs if $rs;

	debug((caller(0))[3].': Ending...');
	0;
}

sub sslConf{
	debug((caller(0))[3].': Starting...');

	my $self = shift;
	my ($rdata, $file);

	for (($main::imscpConfig{'COURIER_IMAP_SSL'}, $main::imscpConfig{'COURIER_POP_SSL'})) {

		#if ssl is not enabled
		last if lc($main::imscpConfig{'SSL_ENABLED'}) ne 'yes';


		$file = iMSCP::File->new(filename => "$main::imscpConfig{'AUTHLIB_CONF_DIR'}/$_");
		#read file exit if can not read
		$rdata = $file->get();
		return 1 if (!$rdata);

		#if ssl conf not in place we add if
		if($rdata =~ m/^TLS_CERTFILE=/msg){
			$rdata =~ s!^TLS_CERTFILE=.*$!TLS_CERTFILE=$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem!mg;
		} else {
			$rdata .= "TLS_CERTFILE=$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem";
		}

		$file = iMSCP::File->new(filename => "$self->{wrkDir}/$_");
		$file->set($rdata) and return 1;
		$file->save() and return 1;
		$file->mode(0644) and return 1;
		$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
		# Installing the new file in the production directory
		$file->copyFile("$main::imscpConfig{'AUTHLIB_CONF_DIR'}") and return 1;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

sub registerHooks{
	debug((caller(0))[3].': Starting...');
	my $self = shift;

	use Servers::mta;

	my $mta = Servers::mta->factory($main::imscpConfig{MTA_SERVER});

	$mta->registerPostHook('buildConf', sub { return $self->mtaConf(@_); } );

	debug((caller(0))[3].': Ending...');
	0;
}

sub mtaConf{
	debug((caller(0))[3].': Starting...');
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

	debug((caller(0))[3].': Ending...');
	$content;
}

1;
