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

package Servers::po::courier;

use strict;
use warnings;
use iMSCP::Debug;
use Data::Dumper;

use vars qw/@ISA/;

@ISA = ('Common::SingletonClass');
use Common::SingletonClass;

sub _init{

	my $self		= shift;
	$self->{cfgDir}	= "$main::imscpConfig{'CONF_DIR'}/courier";
	$self->{bkpDir}	= "$self->{cfgDir}/backup";
	$self->{wrkDir}	= "$self->{cfgDir}/working";

	my $conf		= "$self->{cfgDir}/courier.data";
	tie %self::courierConfig, 'iMSCP::Config','fileName' => $conf;

	$self->{$_} = $self::courierConfig{$_} foreach(keys %self::courierConfig);

	0;
}

sub preinstall{

	use Servers::po::courier::installer;

	my $self	= shift;
	my $rs		= Servers::po::courier::installer->new()->registerHooks();

	$rs;
}

sub install{

	use Servers::po::courier::installer;

	my $self		= shift;
	my $rs			= Servers::po::courier::installer->new()->install();

	$rs;
}

sub postinstall{

	my $self	= shift;
	$self->{restart} = 'yes';

	0;
}

sub restart{

	my $self = shift;
	my ($rs, $stdout, $stderr);

	use iMSCP::Execute;

	# Reload config
	$rs = execute("$self::courierConfig{'CMD_AUTHD'} restart", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	return $rs if $rs;

	$rs = execute("$self::courierConfig{'CMD_POP'} restart", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	return $rs if $rs;

	$rs = execute("$self::courierConfig{'CMD_IMAP'} restart", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	return $rs if $rs;

	$rs = execute("$self::courierConfig{'CMD_POP_SSL'} restart", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	return $rs if $rs;

	$rs = execute("$self::courierConfig{'CMD_IMAP_SSL'} restart", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	return $rs if $rs;

	0;
}

sub addMail{

	use iMSCP::File;
	use iMSCP::Execute;
	use Servers::mta;
	use Crypt::PasswdMD5;

	my $self = shift;
	my $data = shift;
	my $rs = 0;
	my ($stdout, $stderr);

	local $Data::Dumper::Terse = 1;
	debug("Data: ". (Dumper $data));

	my $errmsg = {
		'MAIL_ADDR'	=> 'You must supply mail address!',
		'MAIL_PASS'	=> 'You must supply account password!'
	};

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	if(-f "$self->{AUTHLIB_CONF_DIR}/userdb"){
		$rs |=	iMSCP::File->new(
					filename => "$self->{AUTHLIB_CONF_DIR}/userdb"
				)->copyFile(
					"$self->{bkpDir}/userdb.".time
				)
		;
	}

	if($data->{MAIL_TYPE} =~ /_mail/){
		my $mBoxHashFile	= (
			-f "$self->{wrkDir}/userdb"
			?
			"$self->{wrkDir}/userdb"
			:
			"$self->{cfgDir}/userdb"
		);

		my $wrkFileName	= "$self->{wrkDir}/userdb";
		my $wrkFileH	= iMSCP::File->new(filename => $mBoxHashFile);
		my $wrkContent	= $wrkFileH->get();
		return 1 unless defined $wrkContent;

		my $mailbox		= $data->{MAIL_ADDR};
		$mailbox		=~ s/\./\\\./g;
		$wrkContent		=~ s/^$mailbox\t[^\n]*\n//gmi;
		my @rand_data	= ('A'..'Z', 'a'..'z', '0'..'9', '.', '/');
		my $rand;
		$rand			.= $rand_data[rand()*($#rand_data + 1)] for('1'..'8');
		my $password	= unix_md5_crypt($data->{MAIL_PASS}, $rand);
		my $mta			= Servers::mta->factory();
		my $uid			= scalar getpwnam($mta->{'MTA_MAILBOX_UID_NAME'});
		my $gid			= scalar getgrnam($mta->{'MTA_MAILBOX_GID_NAME'});
		my $mailDir		= $mta->{'MTA_VIRTUAL_MAIL_DIR'};
		$wrkContent		.=	"$data->{MAIL_ADDR}\tuid=$uid|gid=$gid|home=$mailDir/".
							"$data->{DMN_NAME}/$data->{MAIL_ACC}|shell=/bin/false|".
							"systempw=$password|mail=$mailDir/$data->{DMN_NAME}/$data->{MAIL_ACC}\n";
		$wrkFileH	= iMSCP::File->new(filename => $wrkFileName);
		$wrkFileH->set($wrkContent);
		return 1 if $wrkFileH->save();
		$rs |=	$wrkFileH->mode(0600);
		$rs |=	$wrkFileH->owner(
					$main::imscpConfig{ROOT_USER},
					$main::imscpConfig{ROOT_GROUP}
				);
		$rs |= $wrkFileH->copyFile("$self->{AUTHLIB_CONF_DIR}/userdb");

		$rs |= execute($self->{CMD_MAKEUSERDB}, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr;
	}

	$rs;
}

sub delMail{

	use iMSCP::File;
	use iMSCP::Execute;

	my $self = shift;
	my $data = shift;
	my $rs = 0;
	my ($stdout, $stderr);

	local $Data::Dumper::Terse = 1;
	debug("Data: ". (Dumper $data));

	my $errmsg = {
		'MAIL_ADDR'	=> 'You must supply mail address!',
		'MAIL_PASS'	=> 'You must supply account password!'
	};

	foreach(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	if(-f "$self->{AUTHLIB_CONF_DIR}/userdb"){
		$rs |=	iMSCP::File->new(
					filename => "$self->{AUTHLIB_CONF_DIR}/userdb"
				)->copyFile(
					"$self->{bkpDir}/userdb.".time
				)
		;
	}

	my $mBoxHashFile	= (
		-f "$self->{wrkDir}/userdb"
		?
		"$self->{wrkDir}/userdb"
		:
		"$self->{cfgDir}/userdb"
	);

	my $wrkFileName	= "$self->{wrkDir}/userdb";
	my $wrkFileH	= iMSCP::File->new(filename => $mBoxHashFile);
	my $wrkContent	= $wrkFileH->get();
	return 1 unless defined $wrkContent;

	my $mailbox		= $data->{MAIL_ADDR};
	$mailbox		=~ s/\./\\\./g;
	$wrkContent		=~ s/^$mailbox\t[^\n]*\n//gmi;
	$wrkFileH	= iMSCP::File->new(filename => $wrkFileName);
	$wrkFileH->set($wrkContent);
	return 1 if $wrkFileH->save();
	$rs |=	$wrkFileH->mode(0600);
	$rs |=	$wrkFileH->owner(
				$main::imscpConfig{ROOT_USER},
				$main::imscpConfig{ROOT_GROUP}
			);
	$rs |= $wrkFileH->copyFile("$self->{AUTHLIB_CONF_DIR}/userdb");

	$rs |= execute($self->{CMD_MAKEUSERDB}, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;

	$rs;
}

END{

	my $endCode	= $?;
	my $self	= Servers::po::courier->new();
	my $rs		= 0;
	$rs			= $self->restart() if $self->{restart} && $self->{restart} eq 'yes';

	$? = $endCode || $rs;
}

1;
