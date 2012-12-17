#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2012 by internet Multi Server Control Panel
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
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::ftpd::proftpd::installer;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Templator;
use iMSCP::HooksManager;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	iMSCP::HooksManager->getInstance()->trigger('beforeFtpdInitInstaller', $self, 'proftpd');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/proftpd";
	$self->{'bkpDir'} = "$self->{cfgDir}/backup";
	$self->{'wrkDir'} = "$self->{cfgDir}/working";

	my $conf = "$self->{cfgDir}/proftpd.data";
	my $oldConf = "$self->{cfgDir}/proftpd.old.data";

	tie %self::proftpdConfig, 'iMSCP::Config','fileName' => $conf, noerrors => 1;

	if($oldConf) {
		tie %self::proftpdOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1;
		%self::proftpdConfig = (%self::proftpdConfig, %self::proftpdOldConfig);
	}

	iMSCP::HooksManager->getInstance()->trigger('afterFtpdInitInstaller', $self, 'proftpd');

	$self;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;
	my $rs = 0;

	$hooksManager->trigger('beforeFtpdRegisterSetupHooks', $hooksManager, 'proftpd') and return 1;

	# Add proftpd installer dialog in setup dialog stack
	$hooksManager->register(
		'beforeSetupDialog',
		sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askProftpd(@_) }); 0; }
	) and return 1;

	$hooksManager->trigger('afterFtpdRegisterSetupHooks', $hooksManager, 'proftpd');
}

sub askProftpd
{
	my $self = shift;
	my $dialog = shift;

	my $dbType = main::setupGetQuestion('DATABASE_TYPE');
	my $dbHost = main::setupGetQuestion('DATABASE_HOST');
	my $dbPort = main::setupGetQuestion('DATABASE_PORT');
	my $dbName = main::setupGetQuestion('DATABASE_NAME');

	my $dbUser = $main::preseed{'FTPD_SQL_USER'} || $self::proftpdConfig{'DATABASE_USER'} ||
		$self::proftpdOldConfig{'DATABASE_USER'} || 'vftp';

	my $dbPass = $main::preseed{'FTPD_SQL_PASSWORD'} || $self::proftpdConfig{'DATABASE_PASSWORD'} ||
		$self::proftpdOldConfig{'DATABASE_PASSWORD'} || '';

	my ($rs, $msg) = (0, '');

	if($main::reconfigure || main::setupCheckSqlConnect($dbType, '', $dbHost, $dbPort, $dbUser, $dbPass)) {
		# Ask for the proftpd restricted SQL username
		do{
			($rs, $dbUser) = iMSCP::Dialog->factory()->inputbox(
				"\nPlease enter an username for the restricted proftpd SQL user:", $dbUser
			);

			# i-MSCP SQL user cannot be reused
			if($dbUser eq main::setupGetQuestion('DATABASE_USER')){
				$msg = "\n\n\\Z1You cannot reuse the i-MSCP SQL user '$dbUser'.\\Zn\n\nPlease, try again:";
				$dbUser = '';
			}
		} while ($rs != 30 && ! $dbUser);

		if($rs != 30) {
			# Ask for the proftpd restricted SQL user password
			($rs, $dbPass) = $dialog->inputbox(
				'\nPlease, enter a password for the restricted proftpd SQL user (blank for autogenerate):', $dbPass
			);

			if($rs != 30) {
				if(! $dbPass) {
					$dbPass = '';
					my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_');
					$dbPass .= $allowedChars[rand()*($#allowedChars + 1)]for (1..16);
				}

				$dbPass =~ s/('|"|`|#|;|\/|\s|\||<|\?|\\)/_/g;
				$dialog->msgbox("\nPassword for the restricted proftpd SQL user set to: $dbPass");
				$dialog->set('cancel-label');
			}
		}
	}

	if($rs != 30) {
		$self::proftpdConfig{'DATABASE_USER'} = $dbUser;
        $self::proftpdConfig{'DATABASE_PASSWORD'} = $dbPass;
	}

	$rs;
}

sub install
{
	my $self = shift;
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforeFtpdInstall', 'proftpd');

	$rs |= $self->bkpConfFile($self::proftpdConfig{'FTPD_CONF_FILE'});
	$rs |= $self->setupDB();
	$rs |= $self->buildConf();
	$rs |= $self->saveConf();
	$rs |= $self->createLogFiles();
	$rs |= $self->removeOldFiles();

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterFtpdInstall', 'proftpd');

	$rs;
}

sub removeOldFiles
{
	my $self = shift;
	my ($stdout, $stderr);
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforeFtpdRemoveOldFiles');

	use iMSCP::Execute;

	$rs |= execute("$main::imscpConfig{'CMD_RM'} -f $self::proftpdConfig{'FTPD_CONF_DIR'}/*", \$stdout, \$stderr);
	debug("$stdout") if $stdout;

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterFtpdRemoveOldFiles');

	$rs;
}

sub saveConf
{
	use iMSCP::File;

	my $self = shift;
	my $rs = 0;

	my $file = iMSCP::File->new(filename => "$self->{cfgDir}/proftpd.data");
	my $cfg = $file->get() or return 1;

	$rs |= iMSCP::HooksManager->getInstance()->trigger('beforeFtpdSaveConf', \$cfg, 'proftpd.old.data');

	$rs |= $file->mode(0640);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$file = iMSCP::File->new(filename => "$self->{cfgDir}/proftpd.old.data");

	$rs |= $file->set($cfg);
	$rs |= $file->save();
	$rs |= $file->mode(0640);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterFtpdSaveConf', 'proftpd.old.data');

	$rs;
}

sub createLogFiles
{
	my $self = shift;
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforeFtpdCreateLogFiles');

	## To fill ftp_traff.log file with something
	if (! -d "$main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd") {
		debug("Create dir $main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd");

		$rs |= iMSCP::Dir->new(
			dirname => "$main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd"
		)->make({ user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_GROUP'}, mode => 0755 });
	}

	if(! -f "$main::imscpConfig{'TRAFF_LOG_DIR'}$self::proftpdConfig{'FTP_TRAFF_LOG'}") {
		my $file = iMSCP::File->new(
			filename => "$main::imscpConfig{'TRAFF_LOG_DIR'}$self::proftpdConfig{'FTP_TRAFF_LOG'}"
		);

		$rs |= $file->save();
		$rs |= $file->mode(0644);
		$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	}

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterFtpdCreateLogFiles');

	$rs;
}

sub buildConf
{
	my $self = shift;
	my $rs = 0;

	my $cfg = {
		HOST_NAME => $main::imscpConfig{'SERVER_HOSTNAME'},
		DATABASE_NAME => $main::imscpConfig{'DATABASE_NAME'},
		DATABASE_HOST => $main::imscpConfig{'DATABASE_HOST'},
		DATABASE_PORT => $main::imscpConfig{'DATABASE_PORT'},
		DATABASE_USER => $self::proftpdConfig{'DATABASE_USER'},
		DATABASE_PASS => $self::proftpdConfig{'DATABASE_PASSWORD'},
		FTPD_MIN_UID => $self::proftpdConfig{'MIN_UID'},
		FTPD_MIN_GID => $self::proftpdConfig{'MIN_GID'},
		GUI_CERT_DIR => $main::imscpConfig{'GUI_CERT_DIR'},
		SSL => ($main::imscpConfig{'SSL_ENABLED'} eq 'yes' ? '' : '#')
	};

	my $file = iMSCP::File->new(filename => "$self->{cfgDir}/proftpd.conf");
	my $cfgTpl = $file->get();
	return 1 if  ! $cfgTpl;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforeFtpdBuildConf', \$cfgTpl, 'proftpd.conf');

	$cfgTpl = iMSCP::Templator::process($cfg, $cfgTpl);
	return 1 if ! $cfgTpl;

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterFtpdBuildConf', \$cfgTpl, 'proftpd.conf');

	$file = iMSCP::File->new(filename => "$self->{wrkDir}/proftpd.conf");

	$rs |= $file->set($cfgTpl);
	$rs |= $file->save();
	$rs |= $file->mode(0640);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$rs |= $file->copyFile($self::proftpdConfig{'FTPD_CONF_FILE'});

	$rs;
}

sub setupDB
{
	my $self = shift;

	my $dbUser = $self::proftpdConfig{'DATABASE_USER'};
	my $dbOldUser = $self::proftpdOldConfig{'DATABASE_USER'} || '';
	my $dbPass = $self::proftpdConfig{'DATABASE_PASSWORD'};
	my $dbOldPass = $self::proftpdOldConfig{'DATABASE_PASSWORD'} || '';
	my $rs = 0;

	iMSCP::HooksManager->getInstance()->trigger(
		'beforeFtpdSetupDb', $dbUser, $dbOldUser, $dbPass, $dbOldPass
	) and return 1;

	if($dbUser ne $dbOldUser || $dbPass ne $dbOldPass) {

		# Remove old proftpd restricted SQL user and all it privileges (if any)
		$rs = main::setupDeleteSqlUser($dbOldUser);
		error("Unable to remove the old proftpd '$dbOldUser' restricted SQL user: $rs") if $rs;
		return 1 if $rs;

		# Ensure new proftpd user do not already exists by removing it
		$rs = main::setupDeleteSqlUser($dbUser);
		error("Unable to delete the proftpd '$dbUser' restricted SQL user: $rs") if $rs;
		return 1 if $rs;

		# Get SQL connection with full privileges
		my $database = main::setupGetSqlConnect();

		# Add new proftpd restricted SQL user with needed privilegess
		for (qw/ ftp_group ftp_users quotalimits quotatallies /) {
			$rs = $database->doQuery(
				'dummy',
				"
					GRANT SELECT,INSERT,UPDATE,DELETE ON `$main::imscpConfig{'DATABASE_NAME'}`.`$_`
					TO ?@?
					IDENTIFIED BY ?;
				",
				$dbUser,
				$main::imscpConfig{'DATABASE_HOST'},
				$dbPass
			);

			if(ref $rs ne 'HASH') {
				error(
					"Unable to add privileges on the '$main::imscpConfig{'DATABASE_NAME'}.$_' table for the '$dbUser'" .
					" SQL user: $rs"
				);
				return 1;
			}
		}
	}

	iMSCP::HooksManager->getInstance()->trigger('afterFtpSetupDb');
}

sub bkpConfFile
{
	use File::Basename;

	my $self = shift;
	my $cfgFile = shift;
	my $timestamp = time;

	iMSCP::HooksManager->getInstance()->trigger('beforeFtpdBkpConfFile', $cfgFile);

	if(-f $cfgFile){
		my $file = iMSCP::File->new( filename => $cfgFile );
		my ($filename, $directories, $suffix) = fileparse($cfgFile);

		if(! -f "$self->{bkpDir}/$filename$suffix.system") {
			$file->copyFile("$self->{bkpDir}/$filename$suffix.system") and return 1;
		} else {
			$file->copyFile("$self->{bkpDir}/$filename$suffix.$timestamp") and return 1;
		}
	}

	iMSCP::HooksManager->getInstance()->trigger('afterFtpdBkpConfFile', $cfgFile);
}

1;
