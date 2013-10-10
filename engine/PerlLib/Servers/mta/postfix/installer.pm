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
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

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
use iMSCP::SystemUser;
use iMSCP::SystemGroup;
use File::Basename;
use Servers::mta::postfix;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'mta'} = Servers::mta::postfix->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforeMtaInitInstaller', $self, 'postfix'
	) and fatal('postfix - beforeMtaInitInstaller hook has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/postfix";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'vrlDir'} = "$self->{'cfgDir'}/imscp";

	$self->{'config'} = $self->{'mta'}->{'config'};

	my $oldConf = "$self->{'cfgDir'}/postfix.old.data";

	if(-f $oldConf) {
		tie my %oldConfig, 'iMSCP::Config', 'fileName' => $oldConf, 'noerrors' => 1;

		for(keys %oldConfig) {
			if(exists $self->{'config'}->{$_}) {
				$self->{'config'}->{$_} = $oldConfig{$_};
			}
		}
	}

	$self->{'hooksManager'}->trigger(
		'afterMtaInitInstaller', $self, 'postfix'
	) and fatal('postfix - afterMtaInitInstaller hook has failed');

	$self;
}

# Process Mta preinstall tasks
sub preinstall
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaPreInstall', 'postfix');

	$rs = $self->addUsersAndGroups();
	return $rs if $rs;

	$rs = $self->makeDirs();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterMtaPreInstall', 'postfix');
}

# Process install Mta install tasks
sub install
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaInstall', 'postfix');
	return $rs if $rs;

	for (
		$self->{'config'}->{'POSTFIX_CONF_FILE'},
		$self->{'config'}->{'POSTFIX_MASTER_CONF_FILE'},
		$self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'} . '/aliases',
		$self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'} . '/domains',
		$self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'} . '/mailboxes',
		$self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'} . '/transport',
		$self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'} . '/sender-access',
		$self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'} . '/relay_domains'
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

	$self->{'hooksManager'}->trigger('afterMtaInstall', 'postfix');
}

# Set Mta files and directories permissions
sub setEnginePermissions
{
	my $self = shift;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $imscpGName = $main::imscpConfig{'IMSCP_GROUP'};
	my $mtaUName = $self->{'config'}->{'MTA_MAILBOX_UID_NAME'};
	my $mtaGName = $self->{'config'}->{'MTA_MAILBOX_GID_NAME'};
	my $mtaCfg = $self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'};
	my $mtaFolder = $self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'};
	my $imscpRootDir = $main::imscpConfig{'ROOT_DIR'};
	my $logDir = $main::imscpConfig{'LOG_DIR'};

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaSetEnginePermissions');
	return $rs if $rs;

	# eg. /etc/postfix/imscp
	$rs = setRights(
		$mtaCfg,
		{ 'user' => $rootUName, 'group' => $rootGName, 'dirmode' => '0755', 'filemode' => '0644', 'recursive' => 1 }
	);
	return $rs if $rs;

	# eg. /var/www/imscp/engine/messenger
	$rs = setRights(
		"$imscpRootDir/engine/messenger",
		{ 'user' => $rootUName, 'group' => $imscpGName, 'dirmode' => '0750', 'filemode' => '0750', 'recursive' => 1 }
	);
	return $rs if $rs;

	# eg. /var/log/imscp/imscp-arpl-msgr
	$rs = setRights(
		"$logDir/imscp-arpl-msgr",
		{ 'user' => $mtaUName, 'group' => $mtaGName, 'dirmode' => '0750', 'filemode' => '0640', 'recursive' => 1 }
	);
	return $rs if $rs;

	# eg. /var/mail/virtual
	$rs = setRights(
		$mtaFolder,
		{ 'user' => $mtaUName, 'group' => $mtaGName, 'dirmode' => '0750', 'filemode' => '0640', 'recursive' => 1 }
	);
	return $rs if $rs;

	# eg. /usr/sbin/maillogconvert.pl
	$rs = setRights(
		$self->{'config'}->{'CMD_PFLOGSUM'},
		'user' => $rootUName, 'group' => $rootGName, 'mode' => 0750
	);

	$self->{'hooksManager'}->trigger('afterMtaSetEnginePermissions');
}

# Create needed directories
sub makeDirs
{
	my $self = shift;
	my $rs = 0;

	my @directories = (
		[
			$self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}, # eg. /etc/postfix/imscp
			$main::imscpConfig{'ROOT_USER'},
			$main::imscpConfig{'ROOT_GROUP'},
			0755
		],
		[
			$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}, # eg. /var/mail/virtual
			$self->{'config'}->{'MTA_MAILBOX_UID_NAME'},
			$self->{'config'}->{'MTA_MAILBOX_GID_NAME'},
			0750
		],
		[
			$main::imscpConfig{'LOG_DIR'} . '/imscp-arpl-msgr', # eg /var/log/imscp/imscp-arpl-msgr
			$self->{'config'}->{'MTA_MAILBOX_UID_NAME'},
			$self->{'config'}->{'MTA_MAILBOX_GID_NAME'},
			0750
		]
	);

	$rs = $self->{'hooksManager'}->trigger('beforeMtaMakeDirs', \@directories);
	return $rs if $rs;

	for(@directories) {
		$rs = iMSCP::Dir->new(
			'dirname' => $_->[0]
		)->make(
			{ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3] }
		);
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterMtaMakeDirs');
}

# Add needed users and groups for MTA
sub addUsersAndGroups
{
	my $self = shift;
	my $rs = 0;

	my @groups = (
		[
			$self->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # Group name
			'yes' # Whether it's a system group
		]
	);

	my @users = (
		[
			$self->{'config'}->{'MTA_MAILBOX_UID_NAME'}, # User name
			$self->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # User primary group name
			'vmail_user', # Comment
			$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}, # User homedir
			'yes', # Whether it's a system user
			[$main::imscpConfig{'IMSCP_GROUP'}] # Additional user group(s)
		]
	);

	my @userToGroups = (
		[
			$self->{'config'}->{'POSTFIX_USER'}, # User to add into group
			[$self->{'config'}->{'SASLDB_GROUP'}] # Group(s) to which add user
		]
	);

	$rs = $self->{'hooksManager'}->trigger('beforeMtaAddUsersAndGroups', \@groups, \@users, \@userToGroups);
	return $rs if $rs;

	# Create groups
	my $systemGroup = iMSCP::SystemGroup->getInstance();

	for(@groups) {
		$rs = $systemGroup->addSystemGroup($_->[0], ($_->[1] eq 'yes') ? 1 : 0);
		return $rs if $rs;
	}

	# Create users
	for(@users) {
		my $systemUser = iMSCP::SystemUser->new();

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
		my $systemUser = iMSCP::SystemUser->new();
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
	$rs = execute("$self->{'config'}->{'CMD_NEWALIASES'}", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Error while executing $self->{'config'}->{'CMD_NEWALIASES'}") if ! $stderr && $rs;
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

	$rs = $file->owner(
		$self->{'config'}->{'MTA_MAILBOX_UID_NAME'}, $self->{'config'}->{'MTA_MAILBOX_GID_NAME'}
	);
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
		$rs = $file->copyFile("$self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}");
		return $rs if $rs;

		# Creating/updating databases for all lookup tables
		my $rs = execute(
			"$self->{'config'}->{'CMD_POSTMAP'} $self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}/$_",
			\$stdout,
			\$stderr
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
	my $cfgFile = shift;

	my $timestamp = time;

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaBkpConfFile', $cfgFile);
	return $rs if $rs;

	if(-f $cfgFile) {
		my $file = iMSCP::File->new('filename' => $cfgFile);
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

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/postfix.data");

	my $rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
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

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaBuildConf');
	return $rs if $rs;

	$rs = $self->buildMainCfFile();
	return $rs if $rs;

	$rs = $self->buildMasterCfFile();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterMtaBuildConf');
}

# Build and install Postfix main.cf configuration file
sub buildMainCfFile
{
	my $self = shift;

	# Loading the template from /etc/imscp/postfix/
	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/main.cf");
	my $cfgTpl = $file->get();
	return 1 if ! defined $cfgTpl;

	# Building the file
	my $hostname = $main::imscpConfig{'SERVER_HOSTNAME'};
	my $gid = getgrnam($self->{'config'}->{'MTA_MAILBOX_GID_NAME'});
	my $uid = getpwnam($self->{'config'}->{'MTA_MAILBOX_UID_NAME'});

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaBuildMainCfFile', \$cfgTpl, 'main.cf');
	return $rs if $rs;

	$cfgTpl = iMSCP::Templator::process(
		{
			MTA_HOSTNAME => $hostname,
			MTA_LOCAL_DOMAIN => "$hostname.local",
			MTA_VERSION => $main::imscpConfig{'Version'},
			MTA_TRANSPORT_HASH => $self->{'config'}->{'MTA_TRANSPORT_HASH'},
			MTA_LOCAL_MAIL_DIR => $self->{'config'}->{'MTA_LOCAL_MAIL_DIR'},
			MTA_LOCAL_ALIAS_HASH => $self->{'config'}->{'MTA_LOCAL_ALIAS_HASH'},
			MTA_VIRTUAL_MAIL_DIR => $self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'},
			MTA_VIRTUAL_DMN_HASH => $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'},
			MTA_VIRTUAL_MAILBOX_HASH => $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'},
			MTA_VIRTUAL_ALIAS_HASH => $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'},
			MTA_RELAY_HASH => $self->{'config'}->{'MTA_RELAY_HASH'},
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

	# Fix for #790
	my ($stdout, $stderr);
	execute("$self->{'config'}->{'CMD_POSTCONF'} -h mail_version", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	return 1 if $rs;

	if(defined $stdout) {
		chomp($stdout);
		require version;

		if(version->parse($stdout) >= version->parse('2.10.0')) {
			$cfgTpl =~ s/smtpd_recipient_restrictions/smtpd_relay_restrictions =\n\nsmtpd_recipient_restrictions/;
		}
	} else {
		error('Unable to find Postfix version');
		return 1;
	}

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
	$file->copyFile($self->{'config'}->{'POSTFIX_CONF_FILE'});
}

# Build and install Postfix master.cf configuration file
sub buildMasterCfFile
{
	my $self = shift;

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/master.cf");
	my $cfgTpl = $file->get();
	return 1 if ! defined $cfgTpl;

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaBuildMasterCfFile', \$cfgTpl, 'master.cf');
	return $rs if $rs;

	$cfgTpl = iMSCP::Templator::process(
		{
			MTA_MAILBOX_UID_NAME => $self->{'config'}->{'MTA_MAILBOX_UID_NAME'},
			IMSCP_GROUP => $main::imscpConfig{'IMSCP_GROUP'},
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
	$file->copyFile($self->{'config'}->{'POSTFIX_MASTER_CONF_FILE'});
}

1;
