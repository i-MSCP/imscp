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


package Servers::mta::postfix::installer;

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

	iMSCP::HooksManager->getInstance()->trigger('beforeMtaInitInstaller', $self, 'postfix');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/postfix";
	$self->{'bkpDir'} = "$self->{cfgDir}/backup";
	$self->{'wrkDir'} = "$self->{cfgDir}/working";
	$self->{'vrlDir'} = "$self->{cfgDir}/imscp";

	my $conf = "$self->{cfgDir}/postfix.data";
	my $oldConf = "$self->{cfgDir}/postfix.old.data";

	tie %self::postfixConfig, 'iMSCP::Config','fileName' => $conf, noerrors => 1;

	if($oldConf) {
		tie %self::postfixOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1;
		%self::postfixConfig = (%self::postfixConfig, %self::postfixOldConfig);
	}

	iMSCP::HooksManager->getInstance()->trigger('afterMtaInitInstaller', $self, 'postfix');

	0;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	$hooksManager->trigger('beforeMtaRegisterSetupHooks', $hooksManager, 'postfix') and return 1;

	$hooksManager->trigger('afterMtaRegisterSetupHooks', $hooksManager, 'postfix');
}

# Process Mta preinstall tasks
sub preinstall
{
	my $self = shift;

	iMSCP::HooksManager->getInstance()->trigger('beforeMtaPreInstall', 'postfix') and return 1;

	$self->addUsersAndGroups() and return 1;
	$self->makeDirs() and return 1;

	iMSCP::HooksManager->getInstance()->trigger('afterMtaPreInstall', 'postfix');
}

# Process install Mta install tasks
sub install
{
	my $self = shift;
	my $rs = 0;

	my @mtaConffiles = (
		$self::postfixConfig{'POSTFIX_CONF_FILE'},
		$self::postfixConfig{'POSTFIX_MASTER_CONF_FILE'},
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'} . '/aliases',
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'} . '/domains',
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'} . '/mailboxes',
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'} . '/transport',
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'} . '/sender-access',
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'} . '/relay_domains'
	);

	iMSCP::HooksManager->getInstance()->trigger('beforeMtaInstall', 'postfix') and return 1;

	# Saving all system configuration files if they exists
	$rs |= $self->bkpConfFile($_) for @mtaConffiles;
	$rs |= $self->buildConf();
	$rs |= $self->buildLookupTables();
	$rs |= $self->buildAliasesDb();
	$rs |= $self->arplSetup();
	$rs |= $self->saveConf();
	$rs |= $self->setPermissions();

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterMtaInstall', 'postfix');

	$rs;
}

# Set Mta files and directories permissions
sub setPermissions
{
	my $self = shift;
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $mtaUName = $self::postfixConfig{'MTA_MAILBOX_UID_NAME'};
	my $mtaGName = $self::postfixConfig{'MTA_MAILBOX_GID_NAME'};
	my $mtaCfg = $self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'};
	my $mtaFolder = $self::postfixConfig{'MTA_VIRTUAL_MAIL_DIR'};
	my $imscpRootDir = $main::imscpConfig{'ROOT_DIR'};
	my $logDir = $main::imscpConfig{'LOG_DIR'};
	my $rs = 0;

	use iMSCP::Rights;

	iMSCP::HooksManager->getInstance()->trigger('beforeMtaSetPermissions') and return 1;

	$rs |= setRights(
		$mtaCfg,
		{ user => $rootUName, group => $rootGName, dirmode => '0755', filemode => '0644', recursive => 'yes' }
	);

	$rs |= setRights(
		"$imscpRootDir/engine/messenger",
		{ user => $mtaUName, group => $mtaGName, dirmode => '0750', filemode => '0550', recursive => 'yes' }
	);

	$rs |= setRights(
		"$logDir/imscp-arpl-msgr",
		{ user => $mtaUName, group => $mtaGName, dirmode => '0750', filemode => '0640', recursive => 'yes' }
	);

	$rs |= setRights(
		$mtaFolder,
		{ user => $mtaUName, group => $mtaGName, dirmode => '0750', filemode => '0640', recursive => 'yes' }
	);

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterMtaSetPermissions');

	$rs;
}

# Create needed directories
sub makeDirs
{
	my $self = shift;
	my $rs = 0;

	use iMSCP::Dir;

	my @directories = (
		[
			$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'},
			$main::imscpConfig{'ROOT_USER'},
			$main::imscpConfig{'ROOT_GROUP'}
		],
		[
			$self::postfixConfig{'MTA_VIRTUAL_MAIL_DIR'},
			$self::postfixConfig{'MTA_MAILBOX_UID_NAME'},
			$self::postfixConfig{'MTA_MAILBOX_GID_NAME'}
		],
	);

	iMSCP::HooksManager->getInstance()->trigger('beforeMtaMakeDirs', \@directories) and return 1;

	$rs |= iMSCP::Dir->new(
		dirname => $_->[0])->make({ user => $_->[1], group => $_->[2], mode => 0755 }
	) for @directories;

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterMtaMakeDirs');

	$rs;
}

# Add needed users and groups for Mta
sub addUsersAndGroups
{
	my $self = shift;
	my $rs = 0;

	use Modules::SystemUser;
	use Modules::SystemGroup;

	my @groups = (
		[
			$self::postfixConfig{'MTA_MAILBOX_GID_NAME'},	# group name
			'yes'											# Whether it's a system group
		]
	);

	my @users = (
		[
			$self::postfixConfig{'MTA_MAILBOX_UID_NAME'},	# User name
			$self::postfixConfig{'MTA_MAILBOX_GID_NAME'},	# User primary group name
			'vmail_user',									# Comment
			$self::postfixConfig{'MTA_VIRTUAL_MAIL_DIR'},	# User homedir
			'yes',											# Whether it's a system user
			[$main::imscpConfig{'MASTER_GROUP'}]			# Additional user group(s)
		]
	);

	my @userToGroups = (
		[
			$self::postfixConfig{'POSTFIX_USER'},			# User to add into group
			[$self::postfixConfig{'SASLDB_GROUP'}]			# group(s) to which add user
		]
	);

	iMSCP::HooksManager->getInstance()->trigger(
		'beforeMtaAddUsersAndGroups', \@groups, \@users, \@userToGroups
	) and return 1;

	# Create groups
	for(@groups) {
		my $systemGroup = Modules::SystemGroup->new();
		$systemGroup->{'system'} = 'yes' if $_->[1] eq 'yes';
		$rs |= $systemGroup->addSystemGroup($_->[0]);
	}

	# Create users
	for(@users) {
		my $systemUser = Modules::SystemUser->new();

		$systemUser->{'group'} = $_->[1];
		$systemUser->{'comment'} = $_->[2];
		$systemUser->{'home'} = $_->[3];
		$systemUser->{'system'} = 'yes' if $_->[4] eq 'yes';

		$rs |= $systemUser->addSystemUser($_->[0]);

		if(defined $_->[5]) {
			$rs |= $systemUser->addToGroup($_) for @{$_->[5]};
		}
	}

	# User to groups
	for(@userToGroups) {
		my $systemUser = Modules::SystemUser->new();
		my $user = $_->[0];

		$rs |= $systemUser->addToGroup($_, $user) for @{$_->[1]};
	}

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterMtaAddUsersAndGroups');

	$rs;
}

# Build Mta aliases db
sub buildAliasesDb
{
	my $self = shift;
	my ($rs, $stdout, $stderr);

	iMSCP::HooksManager->getInstance()->trigger('beforeMtaBuildAliases') and return 1;

	# Rebuilding the database for the mail aliases file - Begin
	$rs = execute("$self::postfixConfig{'CMD_NEWALIASES'}", \$stdout, \$stderr);
	debug("$stdout");
	error("$stderr") if($stderr);
	error("Error while executing $self::postfixConfig{'CMD_NEWALIASES'}") if !$stderr && $rs;

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterMtaBuildAliases');

	$rs;
}

# Setup auto-responder
sub arplSetup
{
	my $self = shift;

	my $file;
	my $rs = 0;

	iMSCP::HooksManager->getInstance()->trigger('beforeMtaArplSetup') and return 1;

	$file = iMSCP::File->new(filename => "$main::imscpConfig{'ROOT_DIR'}/engine/messenger/imscp-arpl-msgr");
	$rs |= $file->mode(0755);
	$rs |= $file->owner($self::postfixConfig{'MTA_MAILBOX_UID_NAME'}, $self::postfixConfig{'MTA_MAILBOX_GID_NAME'});

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterMtaArplSetup');

	$rs;
}

# Build and install Mta lookup tables
sub buildLookupTables
{
	my $self = shift;
	my $rs = 0;
	my ($stdout, $stderr, $file);

	use iMSCP::File;
	use iMSCP::Execute;

	my @lookupTables = qw/aliases domains mailboxes transport sender-access relay_domains/;

	iMSCP::HooksManager->getInstance()->trigger('beforeMtaBuildLookupTables', \@lookupTables) and return 1;

	for (@lookupTables) {
		# Storing the new files in the working directory
		$file = iMSCP::File->new(filename => "$self->{vrlDir}/$_");
		$rs |= $file->copyFile("$self->{wrkDir}");

		# Install the files in the production directory
		$rs |= $file->copyFile("$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'}");

		# Creating/updating databases for all lookup tables
		my $rv = execute(
			"$self::postfixConfig{'CMD_POSTMAP'} $self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'}/$_", \$stdout, \$stderr
		);
		debug("$stdout");
		error("$stderr") if $rv;
		$rs |= $rv;
	}

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterMtaBuildLookupTables', \@lookupTables);

	$rs;
}

# Backup the given Mta configuration file
sub bkpConfFile
{
	my $self = shift;
	my $rs = 0;
	my $cfgFile = shift;
	my $timestamp = time;

	iMSCP::HooksManager->getInstance()->trigger('beforeMtaBkpConfFile', $cfgFile) and return 1;

	use File::Basename;

	if(-f $cfgFile) {
		my $file = iMSCP::File->new( filename => $cfgFile );
		my ($filename, $directories, $suffix) = fileparse($cfgFile);

		if(!-f "$self->{bkpDir}/$filename$suffix.system") {
			$rs |= $file->copyFile("$self->{bkpDir}/$filename$suffix.system");
		} else {
			$rs |= $file->copyFile("$self->{bkpDir}/$filename$suffix.$timestamp");
		}
	}

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterMtaBkpConfFile', $cfgFile);

	$rs;
}

# Save Postfix data file
sub saveConf
{
	my $self = shift;
	my $rs = 0;

	use iMSCP::File;

	my $file = iMSCP::File->new(filename => "$self->{cfgDir}/postfix.data");
	my $cfg = $file->get() or return 1;

	iMSCP::HooksManager->getInstance()->trigger('beforeMtaSaveConf', \$cfg, 'postfix.old.data') and return 1;

	$file = iMSCP::File->new(filename => "$self->{cfgDir}/postfix.old.data");
	$rs |= $file->set($cfg);
	$rs |= $file->save;
	$rs |= $file->mode(0640);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterMtaSaveConf', 'postfix.old.data');

	$rs;
}

# Build both master.cf and main.cf Postfix configuration files
sub buildConf
{
	my $self = shift;
	my $rs = 0;

	iMSCP::HooksManager->getInstance()->trigger('beforeMtabuildConf') and return 1;

	$rs |= $self->buildMainCfFile();
	$rs |= $self->buildMasterCfFile();

	$rs |=  iMSCP::HooksManager->getInstance()->trigger('afterMtabuildConf');

	$rs;
}

# Build and install Postfix master.cf configuration file
sub buildMasterCfFile
{
	my $self = shift;
	my $rs = 0;

	use iMSCP::File;
	use iMSCP::Templator;

	my $file = iMSCP::File->new(filename => "$self->{cfgDir}/master.cf");
	my $cfgTpl	= $file->get();
	return 1 if ! $cfgTpl;

	iMSCP::HooksManager->getInstance()->trigger('beforeMtaBuildMasterCfFile', \$cfgTpl, 'master.cf') and return 1;

	$cfgTpl = iMSCP::Templator::process(
		{
			ARPL_USER => $self::postfixConfig{'MTA_MAILBOX_UID_NAME'},
			ARPL_GROUP => $main::imscpConfig{'MASTER_GROUP'},
			ARPL_PATH => $main::imscpConfig{'ROOT_DIR'}."/engine/messenger/imscp-arpl-msgr"
		},
		$cfgTpl
	);
	return 1 if ! $cfgTpl;

	iMSCP::HooksManager->getInstance()->trigger('afterMtaBuildMasterCfFile', \$cfgTpl, 'master.cf') and return 1;

	$file = iMSCP::File->new(filename => "$self->{wrkDir}/master.cf");

	$rs |= $file->set($cfgTpl);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	# Installing the new file in the production dir
	$rs |= $file->copyFile($self::postfixConfig{'POSTFIX_MASTER_CONF_FILE'});

	$rs;
}

# Build and install Postfix main.cf configuration file
sub buildMainCfFile
{
	my $self = shift;
	my $rs = 0;

	use iMSCP::File;
	use iMSCP::Templator;

	# Loading the template from /etc/imscp/postfix/
	my $file = iMSCP::File->new(filename => "$self->{cfgDir}/main.cf");
	my $cfgTpl = $file->get();
	return 1 if  ! $cfgTpl;

	# Building the file
	my $hostname = $main::imscpConfig{'SERVER_HOSTNAME'};
	my $gid	= getgrnam($self::postfixConfig{'MTA_MAILBOX_GID_NAME'});
	my $uid	= getpwnam($self::postfixConfig{'MTA_MAILBOX_UID_NAME'});

	iMSCP::HooksManager->getInstance()->trigger('beforeMtaBuildMainCfFile', \$cfgTpl, 'main.cf') and return 1;

	$cfgTpl = iMSCP::Templator::process(
		{
			MTA_HOSTNAME => $hostname,
			MTA_LOCAL_DOMAIN => "$hostname.local",
			MTA_VERSION => $main::imscpConfig{'Version'},
			MTA_TRANSPORT_HASH => $self::postfixConfig{'MTA_TRANSPORT_HASH'},
			MTA_LOCAL_MAIL_DIR => $self::postfixConfig{'MTA_LOCAL_MAIL_DIR'},
			MTA_LOCAL_ALIAS_HASH => $self::postfixConfig{'MTA_LOCAL_ALIAS_HASH'},
			MTA_VIRTUAL_MAIL_DIR => $self::postfixConfig{'MTA_VIRTUAL_MAIL_DIR'},
			MTA_VIRTUAL_DMN_HASH => $self::postfixConfig{'MTA_VIRTUAL_DMN_HASH'},
			MTA_VIRTUAL_MAILBOX_HASH => $self::postfixConfig{'MTA_VIRTUAL_MAILBOX_HASH'},
			MTA_VIRTUAL_ALIAS_HASH => $self::postfixConfig{'MTA_VIRTUAL_ALIAS_HASH'},
			MTA_RELAY_HASH => $self::postfixConfig{'MTA_RELAY_HASH'},
			MTA_MAILBOX_MIN_UID => $uid,
			MTA_MAILBOX_UID => $uid,
			MTA_MAILBOX_GID => $gid,
			PORT_POSTGREY => $main::imscpConfig{'PORT_POSTGREY'},
			GUI_CERT_DIR => $main::imscpConfig{'GUI_CERT_DIR'},
			SSL => ($main::imscpConfig{'SSL_ENABLED'} eq 'yes' ? '' : '#')
		},
		$cfgTpl
	);
	return 1 if ! $cfgTpl;

	iMSCP::HooksManager->getInstance()->trigger('afterMtaBuildMainCfFile', \$cfgTpl, 'main.cf') and return 1;

	# Storing the new file in working directory
	$file = iMSCP::File->new(filename => "$self->{wrkDir}/main.cf");

	$rs |= $file->set($cfgTpl);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	# Installing the new file in production directory
	$rs |= $file->copyFile($self::postfixConfig{'POSTFIX_CONF_FILE'});

	$rs;
}

1;
