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

package Servers::ftpd::proftpd::installer;

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
	my $oldConf		= "$self->{cfgDir}/proftpd.old.data";

	tie %self::proftpdConfig, 'iMSCP::Config','fileName' => $conf;
	tie %self::proftpdOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1 if -f $oldConf;

	0;
}

sub install{

	my $self	= shift;
	my $rs		= 0;

	# Saving all system configuration files if they exists
	for ((
		$self::proftpdConfig{'FTPD_CONF_FILE'},
	)) {
		$rs |= $self->bkpConfFile($_);
	}

	$rs |= $self->setupDB();
	$rs |= $self->buildConf();
	$rs |= $self->saveConf();
	$rs |= $self->logFiles();
	$rs |= $self->removeOldFile();

	$rs;
}

sub removeOldFile{

	use iMSCP::Execute;

	my $self	= shift;
	my $rs		= 0;
	my ($stdout, $stderr);

	$rs = execute("rm $self::proftpdConfig{'FTPD_CONF_DIR'}/*", \$stdout, \$stderr);
	debug("$stdout") if $stdout;

	0;
}

sub saveConf{

	use iMSCP::File;

	my $self	= shift;
	my $rs		= 0;
	my $file	= iMSCP::File->new(filename => "$self->{cfgDir}/proftpd.data");
	my $cfg		= $file->get() or return 1;

	$rs |= $file->mode(0640);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$file = iMSCP::File->new(filename => "$self->{cfgDir}/proftpd.old.data");
	$rs |= $file->set($cfg);
	$rs |= $file->save();
	$rs |= $file->mode(0640);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs;
}

sub logFiles{

	my $self	= shift;
	my $rs		= 0;

	## To fill ftp_traff.log file with something
	if (! -d "$main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd") {
		debug("Create dir $main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd");
		$rs |= iMSCP::Dir->new(
			dirname => "$main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd"
		)->make({
			user	=> $main::imscpConfig{'ROOT_USER'},
			group	=> $main::imscpConfig{'ROOT_GROUP'},
			mode	=> 0755
		});
	}

	if(! -f "$main::imscpConfig{'TRAFF_LOG_DIR'}$self::proftpdConfig{'FTP_TRAFF_LOG'}") {
		my $file = iMSCP::File->new(
			filename => "$main::imscpConfig{'TRAFF_LOG_DIR'}$self::proftpdConfig{'FTP_TRAFF_LOG'}"
		);
		$rs |= $file->save();
		$rs |= $file->mode(0644);
		$rs |= $file->owner(
			$main::imscpConfig{'ROOT_USER'},
			$main::imscpConfig{'ROOT_GROUP'}
		);
	}

	$rs;
}

sub buildConf{

	my $self	= shift;
	my $rs		= 0;

	my $cfg = {
		HOST_NAME		=> $main::imscpConfig{'SERVER_HOSTNAME'},
		DATABASE_NAME	=> $main::imscpConfig{'DATABASE_NAME'},
		DATABASE_HOST	=> $main::imscpConfig{'DATABASE_HOST'},
		DATABASE_PORT	=> $main::imscpConfig{'DATABASE_PORT'},
		DATABASE_USER	=> $self::proftpdConfig{'DATABASE_USER'},
		DATABASE_PASS	=> $self::proftpdConfig{'DATABASE_PASSWORD'},
		FTPD_MIN_UID	=> $self::proftpdConfig{'MIN_UID'},
		FTPD_MIN_GID	=> $self::proftpdConfig{'MIN_GID'},
		GUI_CERT_DIR	=> $main::imscpConfig{'GUI_CERT_DIR'},
		SSL				=> ($main::imscpConfig{'SSL_ENABLED'} eq 'yes' ? '' : '#')
	};

	my $file	= iMSCP::File->new(filename => "$self->{cfgDir}/proftpd.conf");
	my $cfgTpl	= $file->get();
	return 1 if (!$cfgTpl);

	$cfgTpl = iMSCP::Templator::process($cfg, $cfgTpl);
	return 1 if (!$cfgTpl);

	$file = iMSCP::File->new(filename => "$self->{wrkDir}/proftpd.conf");
	$rs |= $file->set($cfgTpl);
	$rs |= $file->save();
	$rs |= $file->mode(0640);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $file->copyFile($self::proftpdConfig{'FTPD_CONF_FILE'});

	$rs;
}

sub setupDB{

	my $self	= shift;
	my $connData;

	if(!$self->check_sql_connection
		(
			$self::proftpdConfig{'DATABASE_USER'} || '',
			$self::proftpdConfig{'DATABASE_PASSWORD'} || ''
		)
	){
		$connData = 'yes';
	}elsif($self::proftpdOldConfig{'DATABASE_USER'} && !$self->check_sql_connection
		(
			$self::proftpdOldConfig{'DATABASE_USER'} || '',
			$self::proftpdOldConfig{'DATABASE_PASSWORD'} || ''
		)
	){
		$self::proftpdConfig{'DATABASE_USER'}		= $self::proftpdOldConfig{'DATABASE_USER'};
		$self::proftpdConfig{'DATABASE_PASSWORD'}	= $self::proftpdOldConfig{'DATABASE_PASSWORD'};
		$connData = 'yes';
	} else {
		my $dbUser = 'vftp';

		do{
			$dbUser = iMSCP::Dialog->factory()->inputbox("Please enter database user name for the restricted proftpd user (default vftp)", $dbUser);
			#we will not allow root user to be used as database user for proftpd since account will be restricted
			if($dbUser eq $main::imscpConfig{DATABASE_USER}){
				iMSCP::Dialog->factory()->msgbox("You can not use $main::imscpConfig{DATABASE_USER} as restricted user");
				$dbUser = undef;
			}
		} while (!$dbUser);

		iMSCP::Dialog->factory()->set('cancel-label','Autogenerate');
		my $dbPass;
		$dbPass = iMSCP::Dialog->factory()->inputbox("Please enter database password (leave blank for autogenerate)", $dbPass);
		if(!$dbPass){
			$dbPass = '';
			my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_');
			$dbPass .= $allowedChars[rand()*($#allowedChars + 1)] for (1..16);
		}
		$dbPass =~ s/('|"|`|#|;|\/|\s|\||<|\?|\\)/_/g;
		iMSCP::Dialog->factory()->msgbox("Your password is '".$dbPass."' (we have stripped not allowed chars)");
		iMSCP::Dialog->factory()->set('cancel-label');
		$self::proftpdConfig{'DATABASE_USER'}		= $dbUser;
		$self::proftpdConfig{'DATABASE_PASSWORD'}	= $dbPass;
	}

	#restore db connection
	my $crypt = iMSCP::Crypt->new();
	my $err = $self->check_sql_connection(
			$main::imscpConfig{'DATABASE_USER'},
			$main::imscpConfig{'DATABASE_PASSWORD'} ? $crypt->decrypt_db_password($main::imscpConfig{'DATABASE_PASSWORD'}) : ''
	);
	if ($err){
		error("$err");
		return 1;
	}

	if(!$connData) {
		my $database = iMSCP::Database->new(db => $main::imscpConfig{DATABASE_TYPE})->factory();

		## We ensure that new data doesn't exist in database

		$err = $database->doQuery(
			'dummy',
			"
				DELETE FROM
					`mysql`.`tables_priv`
				WHERE
					`Host` = ?
				AND
					`Db` = ?
				AND
					`User` = ?;
			", $main::imscpConfig{'DATABASE_HOST'}, $main::imscpConfig{'DATABASE_NAME'}, $self::proftpdConfig{'DATABASE_USER'}
		);
		return $err if (ref $err ne 'HASH');

		$err = $database->doQuery(
			'dummy',
			"
				DELETE FROM
					`mysql`.`user`
				WHERE
					`Host` = ?
				AND
					`User` = ?;
			", $main::imscpConfig{'DATABASE_HOST'}, $self::proftpdConfig{'DATABASE_USER'}
		);
		return $err if (ref $err ne 'HASH');

		$err = $database->doQuery('dummy', 'FLUSH PRIVILEGES');
		return $err if (ref $err ne 'HASH');

		## Inserting new data into the database
		for (qw/ftp_group ftp_users quotalimits quotatallies/) {
			$err = $database->doQuery(
				'dummy',
				"
					GRANT SELECT,INSERT,UPDATE,DELETE ON `$main::imscpConfig{'DATABASE_NAME'}`.`$_`
					TO ?@?
					IDENTIFIED BY ?;
				",
				$self::proftpdConfig{DATABASE_USER},
				$main::imscpConfig{DATABASE_HOST},
				$self::proftpdConfig{DATABASE_PASSWORD}
			);
			return $err if (ref $err ne 'HASH');
		}
	}

	0;
}

sub check_sql_connection{

	use iMSCP::Database;

	my ($self, $dbUser, $dbPass) = (@_);
	my $database = iMSCP::Database->new(db => $main::imscpConfig{DATABASE_TYPE})->factory();
	$database->set('DATABASE_USER',		$dbUser);
	$database->set('DATABASE_PASSWORD',	$dbPass);

	return $database->connect();
}

sub bkpConfFile{

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

	0;
}

1;
