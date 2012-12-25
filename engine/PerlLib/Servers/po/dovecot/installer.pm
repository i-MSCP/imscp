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

package Servers::po::dovecot::installer;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Execute;
use Data::Dumper;
use iMSCP::HooksManager;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/dovecot";
	$self->{'bkpDir'} = "$self->{cfgDir}/backup";
	$self->{'wrkDir'} = "$self->{cfgDir}/working";

	my $conf = "$self->{cfgDir}/dovecot.data";
	my $oldConf = "$self->{cfgDir}/dovecot.old.data";

	tie %self::dovecotConfig, 'iMSCP::Config','fileName' => $conf, noerrors => 1;

	if(-f $oldConf) {
		tie %self::dovecotOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1;
		%self::dovecotConfig = (%self::dovecotConfig, %self::dovecotOldConfig);
	}

	$self->getVersion() and return 1;

	0;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	$hooksManager->trigger('beforePoRegisterSetupHooks', $hooksManager, 'dovecot') and return 1;

	# Add installer dialog in setup dialog stack
	$hooksManager->register(
		'beforeSetupDialog',
		sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askDovecot(@_) }); 0; }
	) and return 1;

	$hooksManager->register('afterMtaBuildMasterCfFile', sub { $self->buildMtaConf(@_); }) and return 1;
	$hooksManager->register('afterMtaBuildMainCfFile', sub { $self->buildMtaConf(@_); }) and return 1;

	$hooksManager->trigger('afterPoRegisterSetupHooks', $hooksManager, 'dovecot');
}

sub askDovecot
{
	my $self = shift;
	my $dialog = shift;

	my $dbType = main::setupGetQuestion('DATABASE_TYPE');
	my $dbHost = main::setupGetQuestion('DATABASE_HOST');
	my $dbPort = main::setupGetQuestion('DATABASE_PORT');
	my $dbName = main::setupGetQuestion('DATABASE_NAME');

	my $dbUser = $main::preseed{'DOVECOT_SQL_USER'} || $self::dovecotConfig{'DATABASE_USER'} ||
		$self::dovecotOldConfig{'DATABASE_USER'} || 'dovecot_user';

	my $dbPass = $main::preseed{'DOVECOT_SQL_PASSWORD'} || $self::dovecotConfig{'DATABASE_PASSWORD'} ||
		$self::dovecotOldConfig{'DATABASE_PASSWORD'} || '';

	my ($rs, $msg) = (0, '');

	if($main::reconfigure || main::setupCheckSqlConnect($dbType, '', $dbHost, $dbPort, $dbUser, $dbPass)) {
		# Ask for the dovecot restricted SQL username
		do{
			($rs, $dbUser) = iMSCP::Dialog->factory()->inputbox(
				"\nPlease enter an username for the restricted dovecot SQL user:", $dbUser
			);

			# i-MSCP SQL user cannot be reused
			if($dbUser eq main::setupGetQuestion('DATABASE_USER')){
				$msg = "\n\n\\Z1You cannot reuse the i-MSCP SQL user '$dbUser'.\\Zn\n\nPlease, try again:";
				$dbUser = '';
			}
		} while ($rs != 30 && ! $dbUser);

		if($rs != 30) {
			# Ask for the dovecot restricted SQL user password
			($rs, $dbPass) = $dialog->inputbox(
				'\nPlease, enter a password for the restricted dovecot SQL user (blank for autogenerate):', $dbPass
			);

			if($rs != 30) {
				if(! $dbPass) {
					$dbPass = '';
					my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_');
					$dbPass .= $allowedChars[rand()*($#allowedChars + 1)]for (1..16);
				}

				$dbPass =~ s/('|"|`|#|;|\/|\s|\||<|\?|\\)/_/g;
				$dialog->msgbox("\nPassword for the restricted dovecot SQL user set to: $dbPass");
				$dialog->set('cancel-label');
			}
		}
	}

	if($rs != 30) {
		$self::dovecotConfig{'DATABASE_USER'} = $dbUser;
        $self::dovecotConfig{'DATABASE_PASSWORD'} = $dbPass;
	}

	$rs;
}

sub install
{
	my $self = shift;
	my $rs;

	iMSCP::HooksManager->getInstance()->trigger('beforePoInstall', 'dovecot') and return 1;

	# Save all system configuration files if they exists
	$rs |= $self->bkpConfFile($_) for ('dovecot.conf', 'dovecot-sql.conf');

	$rs |= $self->setupDb();
	$rs |= $self->buildConf();
	$rs |= $self->saveConf();
	$rs |= $self->migrateMailboxes();

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterPoInstall', 'dovecot');

	$rs;
}

sub migrateMailboxes
{
	my $self = shift;

	iMSCP::HooksManager->getInstance()->trigger('beforePoMigrateMailboxes') and return 1;

	if($main::imscpOldConfig{'PO_SERVER'} && $main::imscpOldConfig{'PO_SERVER'} eq 'courier' &&
		$main::imscpConfig{'PO_SERVER'} eq 'dovecot'
	) {
		use iMSCP::Execute;
		use FindBin;
		use Servers::mta;

		my $mta	= Servers::mta->factory();
		my ($rs, $stdout, $stderr);
		my $binPath = "perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlVendor/courier-dovecot-migrate.pl";
		my $mailPath = "$mta->{'MTA_VIRTUAL_MAIL_DIR'}";

		$rs = execute("$binPath --to-dovecot --convert --recursive $mailPath", \$stdout, \$stderr);
		debug("$stdout...") if $stdout;
		warning("$stderr") if $stderr && !$rs;
		error("$stderr") if $stderr && $rs;
		error("Error while converting mails") if !$stderr && $rs;
	}

	iMSCP::HooksManager->getInstance()->trigger('afterPoMigrateMailboxes');
}

sub getVersion
{
	my $self = shift;
	my ($rs, $stdout, $stderr);

	iMSCP::HooksManager->getInstance()->trigger('beforePoGetVersion');

	$rs = execute('dovecot --version', \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	error("Can't get dovecot version") if !$stderr and $rs;
	return $rs if $rs;

	chomp($stdout);
	$stdout =~ m/^([0-9\.]+)\s*/;

	if($1) {
		$self->{'version'} = $1;
	} else {
		error("Can't get dovecot version");
		return 1;
	}

	iMSCP::HooksManager->getInstance()->trigger('afterPoGetVersion');
}

sub saveConf
{
	my $self = shift;

	use iMSCP::File;

	my $file = iMSCP::File->new(filename => "$self->{cfgDir}/dovecot.data");

	my $cfg = $file->get() or return 1;

	iMSCP::HooksManager->getInstance()->trigger('beforePoSaveConf', \$cfg, 'dovecot.old.data') and return 1;

	$file->mode(0640) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	$file = iMSCP::File->new(filename => "$self->{cfgDir}/dovecot.old.data");
	$file->set($cfg) and return 1;
	$file->save and return 1;
	$file->mode(0640) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	iMSCP::HooksManager->getInstance()->trigger('afterPoSaveConf', 'dovecot.old.data');
}


sub bkpConfFile
{
	my $self = shift;
	my $cfgFile = shift;
	my $timestamp = time;

	iMSCP::HooksManager->getInstance()->trigger('beforePoBkpConfFile', $cfgFile) and return 1;

	if(-f "$self::dovecotConfig{'DOVECOT_CONF_DIR'}/$cfgFile"){
		my $file = iMSCP::File->new(filename => "$self::dovecotConfig{'DOVECOT_CONF_DIR'}/$cfgFile");

		if(!-f "$self->{bkpDir}/$cfgFile.system") {
			$file->copyFile("$self->{bkpDir}/$cfgFile.system") and return 1;
		} else {
			$file->copyFile("$self->{bkpDir}/$cfgFile.$timestamp") and return 1;
		}
	}

	iMSCP::HooksManager->getInstance()->trigger('afterPoBkpConfFile', $cfgFile);
}

sub buildConf
{
	my $self = shift;

	use Servers::mta;

	my $mta	= Servers::mta->factory($main::imscpConfig{'MTA_SERVER'});

	my $cfg = {
		DATABASE_TYPE => $main::imscpConfig{'DATABASE_TYPE'},
		DATABASE_HOST => (
			$main::imscpConfig{'DATABASE_PORT'}
				? "$main::imscpConfig{DATABASE_HOST} port=$main::imscpConfig{DATABASE_PORT}"
				: $main::imscpConfig{'DATABASE_HOST'}
		),
		DATABASE_USER => $self::dovecotConfig{'DATABASE_USER'},
		DATABASE_PASSWORD => $self::dovecotConfig{'DATABASE_PASSWORD'},
		DATABASE_NAME => $main::imscpConfig{'DATABASE_NAME'},
		GUI_CERT_DIR => $main::imscpConfig{'GUI_CERT_DIR'},
		HOST_NAME => $main::imscpConfig{'SERVER_HOSTNAME'},
		DOVECOT_SSL => ($main::imscpConfig{'SSL_ENABLED'} eq 'yes' ? 'yes' : 'no'),
		COMMENT_SSL => ($main::imscpConfig{'SSL_ENABLED'} eq 'yes' ? '' : '#'),
		MAIL_USER => $mta->{'MTA_MAILBOX_UID_NAME'},
		MAIL_GROUP => $mta->{'MTA_MAILBOX_GID_NAME'},
		vmailUID => scalar getpwnam($mta->{'MTA_MAILBOX_UID_NAME'}),
		mailGID => scalar getgrnam($mta->{'MTA_MAILBOX_GID_NAME'}),
		DOVECOT_CONF_DIR => $self::dovecotConfig{'DOVECOT_CONF_DIR'}
	};

	use version;
	my $cfgFiles = {
		'dovecot.conf' =>(
			version->new($self->{'version'}) < version->new('2.0.0') ? 'dovecot.conf.1' : 'dovecot.conf.2'
		),
		'dovecot-sql.conf' => 'dovecot-sql.conf',
		'dovecot-dict-sql.conf' => 'dovecot-dict-sql.conf'
	};

	for (keys %{$cfgFiles}) {
		my $file = iMSCP::File->new(filename => "$self->{cfgDir}/$cfgFiles->{$_}");
		my $cfgTpl = $file->get();
		return 1 if ! $cfgTpl;

		iMSCP::HooksManager->getInstance()->trigger('beforePoBuildConf', \$cfgTpl, $_) and return 1;

		$cfgTpl = iMSCP::Templator::process($cfg, $cfgTpl);
		return 1 if ! $cfgTpl;

		iMSCP::HooksManager->getInstance()->trigger('afterPoBuildConf', \$cfgTpl, $_) and return 1;

		$file = iMSCP::File->new(filename => "$self->{wrkDir}/$_");
		$file->set($cfgTpl) and return 1;
		$file->save() and return 1;
		$file->mode(0640) and return 1;
		$file->owner($main::imscpConfig{'ROOT_USER'}, $mta->{'MTA_MAILBOX_GID_NAME'}) and return 1;
		$file->copyFile($self::dovecotConfig{'DOVECOT_CONF_DIR'}) and return 1;
	}

	my $file = iMSCP::File->new(filename => "$self::dovecotConfig{'DOVECOT_CONF_DIR'}/dovecot.conf");
	$file->mode(0644) and return 1;

	0;
}

sub setupDb
{
	my $self = shift;

	my $dbUser = $self::dovecotConfig{'DATABASE_USER'};
	my $dbOldUser = $self::dovecotOldConfig{'DATABASE_USER'} || '';
	my $dbPass = $self::dovecotConfig{'DATABASE_PASSWORD'};
	my $dbOldPass = $self::dovecotOldConfig{'DATABASE_PASSWORD'} || '';
	my $rs;

	iMSCP::HooksManager->getInstance()->trigger(
		'beforePoSetupDb', $dbUser, $dbOldUser, $dbPass, $dbOldPass
	) and return 1;

	if($dbUser ne $dbOldUser || $dbPass ne $dbOldPass) {

		# Remove old dovecot restricted SQL user and all it privileges (if any)
		$rs = main::setupDeleteSqlUser($dbOldUser);
		error("Unable to remove the old dovecot '$dbOldUser' restricted SQL user: $rs") if $rs;
		return 1 if $rs;

		# Ensure new dovecot user do not already exists by removing it
		$rs = main::setupDeleteSqlUser($dbUser);
		error("Unable to delete the dovecot '$dbUser' restricted SQL user: $rs") if $rs;
		return 1 if $rs;

		# Get SQL connection with full privileges
		my $database = main::setupGetSqlConnect();

		# Add new dovecot restricted SQL user with needed privilegess
		$rs = $database->doQuery(
			'dummy',
			"GRANT SELECT ON `$main::imscpConfig{'DATABASE_NAME'}`.* TO ?@? IDENTIFIED BY ?",
			$dbUser,
			$main::imscpConfig{'DATABASE_HOST'},
			$dbPass
		);
		if(ref $rs ne 'HASH') {
        	error(
        		"Unable to add privileges on the '$main::imscpConfig{'DATABASE_NAME'}' tables for the '$dbUser'" .
        		" SQL user: $rs"
        	);
        	return 1;
        }

		$rs = $database->doQuery(
			'dummy',
			"GRANT SELECT, INSERT, UPDATE, DELETE ON `$main::imscpConfig{'DATABASE_NAME'}`.`quota_dovecot` TO ?@?",
			$dbUser,
			$main::imscpConfig{'DATABASE_HOST'}
		);
		if(ref $rs ne 'HASH') {
        	error(
        		"Unable to add privileges on the '$main::imscpConfig{'DATABASE_NAME'}.quota_dovecot' table for the " .
        		" '$dbUser' SQL user: $rs"
        	);
        	return 1;
        }
	}

	iMSCP::HooksManager->getInstance()->trigger('afterPoSetupDb');
}

# Hook function acting on the afterMtaBuildConf hook
sub buildMtaConf
{
	my $self = shift;
	my $content	= shift || '';

	use iMSCP::Templator;

	my $mta	= Servers::mta->factory($main::imscpConfig{'MTA_SERVER'});

	my $poBloc = getBloc(
		"$mta->{'commentChar'} dovecot begin",
		"$mta->{'commentChar'} dovecot end",
		$$content
	);

	my $tpl = { SFLAG =>(version->new($self->{'version'}) < version->new('2.0.0') ? '-s' : '') };

	$poBloc = iMSCP::Templator::process($tpl, $poBloc);

	$$content = replaceBloc(
		"$mta->{'commentChar'} po setup begin",
		"$mta->{'commentChar'} po setup end",
		$poBloc,
		$$content,
		undef
	);

	# self register again and wait for next configuration file
	iMSCP::HooksManager->getInstance()->register(
		'afterMtaBuildMasterCfFile', sub { $self->buildMtaConf(@_); }
	) and return 1;

	iMSCP::HooksManager->getInstance()->register('afterMtaBuildMainCfFile', sub { $self->buildMtaConf(@_); });
}

1;
