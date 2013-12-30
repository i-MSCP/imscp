#!/usr/bin/perl

=head1 NAME

 Servers::mta::postfix::installer - i-MSCP Postfix MTA server installer implementation

=cut

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
use iMSCP::TemplateParser;
use iMSCP::Rights;
use iMSCP::SystemUser;
use iMSCP::SystemGroup;
use File::Basename;
use Servers::mta::postfix;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Postfix MTA server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return in 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaPreInstall', 'postfix');

	$rs = $self->_addUsersAndGroups();
	return $rs if $rs;

	$rs = $self->_makeDirs();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterMtaPreInstall', 'postfix');
}

=item install()

 Process install tasks

 Return in 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaInstall', 'postfix');
	return $rs if $rs;

	$rs = $self->_buildConf();
	return $rs if $rs;

	$rs = $self->_buildLookupTables();
	return $rs if $rs;

	$rs = $self->_buildAliasesDb();
	return $rs if $rs;

	$rs = $self->_saveConf();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterMtaInstall', 'postfix');
}

=item setEnginePermissions()

 Set engine permissions

 Return in 0 on success, other on failure

=cut

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

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance of this class.

 Return Servers::mta::postfix::installer

=cut

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
	$self->{'lkptsDir'} = "$self->{'cfgDir'}/imscp";

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

=item _addUsersAndGroups()

 Add users and groups

 Return in 0 on success, other on failure

=cut

sub _addUsersAndGroups
{
	my $self = shift;

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

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaAddUsersAndGroups', \@groups, \@users, \@userToGroups);
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

=item _makeDirs()

 Create directories

 Return in 0 on success, other on failure

=cut

sub _makeDirs
{
	my $self = shift;

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

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaMakeDirs', \@directories);
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

=item _buildConf()

 Build configuration file

 Return in 0 on success, other on failure

=cut

sub _buildConf
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaBuildConf');
	return $rs if $rs;

	$rs = $self->_buildMainCfFile();
	return $rs if $rs;

	$rs = $self->_buildMasterCfFile();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterMtaBuildConf');
}

=item _buildLookupTables()

 Build lookup tables

 Return in 0 on success, other on failure

=cut

sub _buildLookupTables
{
	my $self = shift;

	my $dir = iMSCP::Dir->new('dirname' => $self->{'lkptsDir'});
	my @lookupTables = $dir->getFiles();

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaBuildLookupTables', \@lookupTables);
	return $rs if $rs;

	for(@lookupTables) {
		# Backup current lookup table if any
		$rs = $self->_bkpConfFile("self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}/$_");
		return $rs if $rs;

		my $file = iMSCP::File->new('filename' => "$self->{'lkptsDir'}/$_");

		# Copy lookup table in working directory
		$rs = $file->copyFile($self->{'wrkDir'});
		return $rs if $rs;

		# Copy lookup table in production directory
		$rs = $file->copyFile("$self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}");
		return $rs if $rs;

		# Schedule lookup table postmap
		$self->{'mta'}->{'postmap'}->{"$self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}/$_"} = 'installer';
	}

	$self->{'hooksManager'}->trigger('afterMtaBuildLookupTables', \@lookupTables);
}

=item _buildAliasesDb()

 Build aliases database

 Return in 0 on success, other on failure

=cut

sub _buildAliasesDb
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaBuildAliases');
	return $rs if $rs;

	my ($stdout, $stderr);
	$rs = execute($self->{'config'}->{'CMD_NEWALIASES'}, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Error while executing $self->{'config'}->{'CMD_NEWALIASES'}") if ! $stderr && $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterMtaBuildAliases');
}

=item _saveConf()

 Save main configuration file

 Return in 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = shift;

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/postfix.data");

	my $rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	my $content = $file->get();
	unless(defined $content) {
		error("Unable to read $file->{'filename'}");
		return 1;
	}

	$rs = $self->{'hooksManager'}->trigger('beforeMtaSaveConf', \$content, 'postfix.old.data');
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/postfix.old.data");

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save;
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterMtaSaveConf', 'postfix.old.data');
}

=item _bkpConfFile($cfgFile)

 Backup configuration file

 Return in 0 on success, other on failure

=cut

sub _bkpConfFile($$)
{
	my $self = shift;
	my $cfgFile = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeMtaBkpConfFile', $cfgFile);
	return $rs if $rs;

	if(-f $cfgFile) {
		my $file = iMSCP::File->new('filename' => $cfgFile);
		my $filename = fileparse($cfgFile);
		my $timestamp = time;

		if(! -f "$self->{'bkpDir'}/$filename.system") {
			$rs = $file->copyFile("$self->{'bkpDir'}/$filename.system");
			return $rs if $rs;
		} else {
			$rs = $file->copyFile("$self->{'bkpDir'}/$filename.$timestamp");
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterMtaBkpConfFile', $cfgFile);
}

=item _buildMainCfFile()

 Build main.cf file

 Return in 0 on success, other on failure

=cut

sub _buildMainCfFile
{
	my $self = shift;

	# Backup current file if any
	my $rs = $self->_bkpConfFile("self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}/main.cf");
	return $rs if $rs;

	# Load template
	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/main.cf");

	my $content = $file->get();
	unless(defined $content) {
		error("Unable to read $file->{'filename'}");
		return 1;
	}

	# Build new file
	my $hostname = $main::imscpConfig{'SERVER_HOSTNAME'};
	my $gid = getgrnam($self->{'config'}->{'MTA_MAILBOX_GID_NAME'});
	my $uid = getpwnam($self->{'config'}->{'MTA_MAILBOX_UID_NAME'});

	$rs = $self->{'hooksManager'}->trigger('beforeMtaBuildMainCfFile', \$content, 'main.cf');
	return $rs if $rs;

	my $baseServerIpType = iMSCP::Net->getInstance->getAddrVersion($main::imscpConfig{'BASE_SERVER_IP'});

	$content = process(
		{
			MTA_INET_PROTOCOLS => $baseServerIpType,
			MTA_SMTP_BIND_ADDRESS => ($baseServerIpType eq 'ipv4') ? $main::imscpConfig{'BASE_SERVER_IP'} : '',
			MTA_SMTP_BIND_ADDRESS6 => ($baseServerIpType eq 'ipv6') ? $main::imscpConfig{'BASE_SERVER_IP'} : '',
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
		$content
	);
	unless(defined $content) {
		error('Unable to build main.cf file');
		return 1;
	}

	# Fix for #790
	my ($stdout, $stderr);
	execute("$self->{'config'}->{'CMD_POSTCONF'} -h mail_version", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	return 1 if $rs;

	unless(defined $stdout) {
		error('Unable to find Postfix version');
		return 1;
	}

	chomp($stdout);

	if(version->parse($stdout) >= version->parse('2.10.0')) {
		$content =~ s/smtpd_recipient_restrictions/smtpd_relay_restrictions =\n\nsmtpd_recipient_restrictions/;
	}

	$rs = $self->{'hooksManager'}->trigger('afterMtaBuildMainCfFile', \$content, 'main.cf');
	return $rs if $rs;

	# Store file in working directory
	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/main.cf");

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	# Copy file in production directory
	$file->copyFile($self->{'config'}->{'POSTFIX_CONF_FILE'});
}

=item _buildMasterCfFile()

 Build master.cf file

 Return in 0 on success, other on failure

=cut

sub _buildMasterCfFile
{
	my $self = shift;

	# Backup current file if any
	my $rs = $self->_bkpConfFile("self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}/master.cf");
	return $rs if $rs;

	# Load template
	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/master.cf");

	my $content = $file->get();
	unless(defined $content) {
		error("Unable to read $file->{'filename'}");
		return 1;
	}

	$rs = $self->{'hooksManager'}->trigger('beforeMtaBuildMasterCfFile', \$content, 'master.cf');
	return $rs if $rs;

	$content = process(
		{
			MTA_MAILBOX_UID_NAME => $self->{'config'}->{'MTA_MAILBOX_UID_NAME'},
			IMSCP_GROUP => $main::imscpConfig{'IMSCP_GROUP'},
			ARPL_PATH => $main::imscpConfig{'ROOT_DIR'}."/engine/messenger/imscp-arpl-msgr"
		},
		$content
	);
	unless(defined $content) {
		error('Unable to build master.cf file');
		return 1;
	}

	$rs = $self->{'hooksManager'}->trigger('afterMtaBuildMasterCfFile', \$content, 'master.cf');
	return $rs if $rs;

	# Store file in working directory
	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/master.cf");

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	# Copy file in production directory
	$file->copyFile($self->{'config'}->{'POSTFIX_MASTER_CONF_FILE'});
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
