#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::mta::postfix::installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Templator;
use iMSCP::Rights;
use Modules::SystemUser;
use Modules::SystemGroup;
use File::Basename;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger('beforeMtaInitInstaller', $self, 'postfix');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/postfix";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'vrlDir'} = "$self->{'cfgDir'}/imscp";

	my $conf = "$self->{'cfgDir'}/postfix.data";
	my $oldConf = "$self->{'cfgDir'}/postfix.old.data";

	tie %self::postfixConfig, 'iMSCP::Config','fileName' => $conf, noerrors => 1;

	if(-f $oldConf) {
		tie %self::postfixOldConfig, 'iMSCP::Config','fileName' => $oldConf, noerrors => 1;
		%self::postfixConfig = (%self::postfixConfig, %self::postfixOldConfig);
	}

	$self->{'hooksManager'}->trigger('afterMtaInitInstaller', $self, 'postfix');

	$self;
}

# Process Mta preinstall tasks
sub preinstall
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->addUsersAndGroups();
	return $rs if $rs;

	$self->makeDirs();
}

# Process install Mta install tasks
sub install
{
	my $self = shift;
	my $rs = 0;

	for (
		$self::postfixConfig{'POSTFIX_CONF_FILE'},
		$self::postfixConfig{'POSTFIX_MASTER_CONF_FILE'},
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'} . '/aliases',
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'} . '/domains',
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'} . '/mailboxes',
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'} . '/transport',
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'} . '/sender-access',
		$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'} . '/relay_domains'
	) {
		$rs = $self->bkpConfFile($_);
		return $rs if $rs;
	}

	$rs = $self->buildConf();
	return $rs if $rs;

	$rs = $self->buildLookupTables();
	return $rs if $rs;

	$rs = $self->buildAliasesDb();
	return $rs if $rs;

	$rs = $self->arplSetup();
	return $rs if $rs;

	$rs = $self->saveConf();
	return $rs if $rs;

	$self->setEnginePermissions();
}

# Set Mta files and directories permissions
sub setEnginePermissions
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

	$rs = $self->{'hooksManager'}->trigger('beforeMtaSetEnginePermissions');
	return $rs if $rs;

	$rs = setRights(
		$mtaCfg,
		{ 'user' => $rootUName, 'group' => $rootGName, 'dirmode' => '0755', 'filemode' => '0644', 'recursive' => 'yes' }
	);
	return $rs if $rs;

	$rs = setRights(
		"$imscpRootDir/engine/messenger",
		{ 'user' => $mtaUName, 'group' => $mtaGName, 'dirmode' => '0750', 'filemode' => '0550', 'recursive' => 'yes' }
	);
	return $rs if $rs;

	$rs = setRights(
		"$logDir/imscp-arpl-msgr",
		{ 'user' => $mtaUName, 'group' => $mtaGName, 'dirmode' => '0750', 'filemode' => '0640', 'recursive' => 'yes' }
	);
	return $rs if $rs;

	$rs = setRights(
		$mtaFolder,
		{ 'user' => $mtaUName, 'group' => $mtaGName, 'dirmode' => '0750', 'filemode' => '0640', 'recursive' => 'yes' }
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterMtaSetEnginePermissions');
}

# Create needed directories
sub makeDirs
{
	my $self = shift;
	my $rs = 0;

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

	$rs = $self->{'hooksManager'}->trigger('beforeMtaMakeDirs', \@directories);
	return $rs if $rs;

	for(@directories) {
		$rs = iMSCP::Dir->new('dirname' => $_->[0])->make({ 'user' => $_->[1], 'group' => $_->[2], 'mode' => 0755 });
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterMtaMakeDirs');
}

# Add needed users and groups for Mta
sub addUsersAndGroups
{
	my $self = shift;
	my $rs = 0;

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

	$rs = $self->{'hooksManager'}->trigger('beforeMtaAddUsersAndGroups', \@groups, \@users, \@userToGroups);
	return $rs if $rs;

	# Create groups
	for(@groups) {
		my $systemGroup = Modules::SystemGroup->new();
		$systemGroup->{'system'} = 'yes' if $_->[1] eq 'yes';
		$rs = $systemGroup->addSystemGroup($_->[0]);
		return $rs if $rs;
	}

	# Create users
	for(@users) {
		my $systemUser = Modules::SystemUser->new();

		$systemUser->{'group'} = $_->[1];
		$systemUser->{'comment'} = $_->[2];
		$systemUser->{'home'} = $_->[3];
		$systemUser->{'system'} = 'yes' if $_->[4] eq 'yes';

		$rs = $systemUser->addSystemUser($_->[0]);
		return $rs if $rs;

		if(defined $_->[5]) {

			for(@{$_->[5]}) {
				$rs = $systemUser->addToGroup($_) ;
				return $rs if $rs;
			}
		}
	}

	# User to groups
	for(@userToGroups) {
		my $systemUser = Modules::SystemUser->new();
		my $user = $_->[0];

		for(@{$_->[1]}) {
			$rs = $systemUser->addToGroup($_, $user);
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterMtaAddUsersAndGroups');
}

# Build Mta aliases db
sub buildAliasesDb
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaBuildAliases');
	return $rs if $rs;

	# Rebuilding the database for the mail aliases file - Begin
	my ($stdout, $stderr);
	$rs = execute("$self::postfixConfig{'CMD_NEWALIASES'}", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Error while executing $self::postfixConfig{'CMD_NEWALIASES'}") if ! $stderr && $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterMtaBuildAliases');
}

# Setup auto-responder
sub arplSetup
{
	my $self = shift;

	my $file;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaArplSetup');
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$main::imscpConfig{'ROOT_DIR'}/engine/messenger/imscp-arpl-msgr");

	$rs = $file->mode(0755);
	return $rs if $rs;

	$rs = $file->owner($self::postfixConfig{'MTA_MAILBOX_UID_NAME'}, $self::postfixConfig{'MTA_MAILBOX_GID_NAME'});
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterMtaArplSetup');
}

# Build and install Mta lookup tables
sub buildLookupTables
{
	my $self = shift;
	my $rs = 0;
	my ($stdout, $stderr, $file);

	my @lookupTables = qw/aliases domains mailboxes transport sender-access relay_domains/;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaBuildLookupTables', \@lookupTables);
	return $rs if $rs;

	for (@lookupTables) {
		# Storing the new files in the working directory
		$file = iMSCP::File->new('filename' => "$self->{'vrlDir'}/$_");

		$rs = $file->copyFile("$self->{'wrkDir'}");
		return $rs if $rs;

		# Install the files in the production directory
		$rs = $file->copyFile("$self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'}");
		return $rs if $rs;

		# Creating/updating databases for all lookup tables
		my $rs = execute(
			"$self::postfixConfig{'CMD_POSTMAP'} $self::postfixConfig{'MTA_VIRTUAL_CONF_DIR'}/$_", \$stdout, \$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterMtaBuildLookupTables', \@lookupTables);
}

# Backup the given Mta configuration file
sub bkpConfFile
{
	my $self = shift;
	my $rs = 0;
	my $cfgFile = shift;
	my $timestamp = time;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaBkpConfFile', $cfgFile);
	return $rs if $rs;

	if(-f $cfgFile) {
		my $file = iMSCP::File->new('filename' => $cfgFile );
		my ($filename, $directories, $suffix) = fileparse($cfgFile);

		if(! -f "$self->{'bkpDir'}/$filename$suffix.system") {
			$rs = $file->copyFile("$self->{'bkpDir'}/$filename$suffix.system");
			return $rs if $rs;
		} else {
			$rs = $file->copyFile("$self->{'bkpDir'}/$filename$suffix.$timestamp");
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterMtaBkpConfFile', $cfgFile);
}

# Save Postfix data file
sub saveConf
{
	my $self = shift;
	my $rs = 0;

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/postfix.data");

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	my $cfg = $file->get();
	unless(defined $cfg) {
		error("Unable to read $self->{'cfgDir'}/postfix.data");
		return 1;
	}

	$rs = $self->{'hooksManager'}->trigger('beforeMtaSaveConf', \$cfg, 'postfix.old.data');
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/postfix.old.data");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save;
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterMtaSaveConf', 'postfix.old.data');
}

# Build both master.cf and main.cf Postfix configuration files
sub buildConf
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaBuildConf');
	return $rs if $rs;

	$rs = $self->buildMainCfFile();
	return $rs if $rs;

	$rs = $self->buildMasterCfFile();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterMtaBuildConf');
}

# Build and install Postfix master.cf configuration file
sub buildMasterCfFile
{
	my $self = shift;
	my $rs = 0;

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/master.cf");
	my $cfgTpl = $file->get();
	return 1 if ! defined $cfgTpl;

	$rs = $self->{'hooksManager'}->trigger('beforeMtaBuildMasterCfFile', \$cfgTpl, 'master.cf');
	return $rs if $rs;

	$cfgTpl = iMSCP::Templator::process(
		{
			ARPL_USER => $self::postfixConfig{'MTA_MAILBOX_UID_NAME'},
			ARPL_GROUP => $main::imscpConfig{'MASTER_GROUP'},
			ARPL_PATH => $main::imscpConfig{'ROOT_DIR'}."/engine/messenger/imscp-arpl-msgr"
		},
		$cfgTpl
	);
	return 1 if ! defined $cfgTpl;

	$rs = $self->{'hooksManager'}->trigger('afterMtaBuildMasterCfFile', \$cfgTpl, 'master.cf');
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/master.cf");

	$rs = $file->set($cfgTpl);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	# Installing the new file in the production dir
	$file->copyFile($self::postfixConfig{'POSTFIX_MASTER_CONF_FILE'});
}

# Build and install Postfix main.cf configuration file
sub buildMainCfFile
{
	my $self = shift;
	my $rs = 0;

	# Loading the template from /etc/imscp/postfix/
	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/main.cf");
	my $cfgTpl = $file->get();
	return 1 if  ! defined $cfgTpl;

	# Building the file
	my $hostname = $main::imscpConfig{'SERVER_HOSTNAME'};
	my $gid	= getgrnam($self::postfixConfig{'MTA_MAILBOX_GID_NAME'});
	my $uid	= getpwnam($self::postfixConfig{'MTA_MAILBOX_UID_NAME'});

	$rs = $self->{'hooksManager'}->trigger('beforeMtaBuildMainCfFile', \$cfgTpl, 'main.cf');
	return $rs if $rs;

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
	return 1 if ! defined $cfgTpl;

	$rs = $self->{'hooksManager'}->trigger('afterMtaBuildMainCfFile', \$cfgTpl, 'main.cf');
	return $rs if $rs;

	# Storing the new file in working directory
	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/main.cf");

	$rs = $file->set($cfgTpl);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	# Installing the new file in production directory
	$file->copyFile($self::postfixConfig{'POSTFIX_CONF_FILE'});
}

1;
