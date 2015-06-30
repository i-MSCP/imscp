=head1 NAME

 Servers::mta::postfix::installer - i-MSCP Postfix MTA server installer implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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

package Servers::mta::postfix::installer;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::EventManager;
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

@main::sqlUsers = () unless @main::sqlUsers;

=head1 DESCRIPTION

 i-MSCP Postfix MTA server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
	my ($self, $eventManager) = @_;

	$eventManager->register('beforeSetupDialog', sub { push @{$_[0]}, sub { $self->showDialog(@_) }; 0; });
}

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub showDialog
{
	my ($self, $dialog) = @_;

	my $dbUser = main::setupGetQuestion('SASL_SQL_USER') || $self->{'config'}->{'DATABASE_USER'} || 'sasl_user';
	my $dbPass = main::setupGetQuestion('SASL_SQL_PASSWORD') || $self->{'config'}->{'DATABASE_PASSWORD'} || '';

	my ($rs, $msg) = (0, '');

	if(
		$main::reconfigure ~~ [ 'mta', 'servers', 'all', 'forced' ] ||
		(length $dbUser < 6 || length $dbUser > 16 || $dbUser !~ /^[\x21-\x5b\x5d-\x7e]+$/) ||
		(length $dbPass < 6 || $dbPass !~ /^[\x21-\x5b\x5d-\x7e]+$/)
	) {
		# Ask for the SASL restricted SQL username
		do{
			($rs, $dbUser) = $dialog->inputbox(
				"\nPlease enter an username for the Postfix SASL SQL user:$msg", $dbUser
			);

			if($dbUser eq $main::imscpConfig{'DATABASE_USER'}) {
				$msg = "\n\n\\Z1You cannot reuse the i-MSCP SQL user '$dbUser'.\\Zn\n\nPlease try again:";
				$dbUser = '';
			} elsif(length $dbUser > 16) {
				$msg = "\n\n\\Username can be up to 16 characters long.\\Zn\n\nPlease try again:";
				$dbUser = '';
			} elsif(length $dbUser < 6) {
				$msg = "\n\n\\Z1Username must be at least 6 characters long.\\Zn\n\nPlease try again:";
				$dbUser = '';
			} elsif($dbUser !~ /^[\x21-\x5b\x5d-\x7e]+$/) {
				$msg = "\n\n\\Z1Only printable ASCII characters (excepted space and backslash) are allowed.\\Zn\n\nPlease try again:";
				$dbUser = '';
			}
		} while ($rs != 30 && ! $dbUser);

		if($rs != 30) {
			$msg = '';

			do {
				# Ask for the SASL restricted SQL user password
				($rs, $dbPass) = $dialog->passwordbox(
					"\nPlease, enter a password for the restricted sasl SQL user (blank for autogenerate):$msg", $dbPass
				);

				if($dbPass ne '') {
					if(length $dbPass < 6) {
						$msg = "\n\n\\Z1Password must be at least 6 characters long.\\Zn\n\nPlease try again:";
						$dbPass = '';
					} elsif($dbPass !~ /^[\x21-\x5b\x5d-\x7e]+$/) {
						$msg = "\n\n\\Z1Only printable ASCII characters (excepted space and backslash) are allowed.\\Zn\n\nPlease try again:";
						$dbPass = '';
					} else {
						$msg = '';
					}
				} else {
					$msg = '';
				}
			} while($rs != 30 && $msg);

			if($rs != 30) {
				if(! $dbPass) {
					my @allowedChr = map { chr } (0x21..0x5b, 0x5d..0x7e);
					$dbPass = '';
					$dbPass .= $allowedChr[rand @allowedChr] for 1..16;
				}

				$dialog->msgbox("\nPassword for the restricted SASL SQL user set to: $dbPass");
			}
		}
	}

	if($rs != 30) {
		main::setupSetQuestion('SASL_SQL_USER', $dbUser);
		main::setupSetQuestion('SASL_SQL_PASSWORD', $dbPass);
	}

	$rs;
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	my $rs = $self->_addUsersAndGroups();
	return $rs if $rs;

	$self->_makeDirs();
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->_setupSqlUser();
	return $rs if $rs;

	$rs = $self->_buildConf();
	return $rs if $rs;

	$rs = $self->_buildLookupTables();
	return $rs if $rs;

	$rs = $self->_buildAliasesDb();
	return $rs if $rs;

	$rs = $self->_oldEngineCompatibility();
	return $rs if $rs;

	$self->_saveConf();
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = shift;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $imscpGName = $main::imscpConfig{'IMSCP_GROUP'};
	my $mtaUName = $self->{'config'}->{'MTA_MAILBOX_UID_NAME'};
	my $mtaGName = $self->{'config'}->{'MTA_MAILBOX_GID_NAME'};

	# eg. /etc/postfix/imscp
	my $rs = setRights(
		$self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}, {
		user => $rootUName, group => $rootGName, dirmode => '0755', filemode => '0644', recursive => 1
	});
	return $rs if $rs;

	# eg. /etc/postfix/sasl (since 1.1.12)
	$rs = setRights(
		$self->{'config'}->{'MTA_SASL_CONF_DIR'}, {
		user => $rootUName, group => $rootGName, dirmode => '0755', filemode => '0640', recursive => 1
	});
	return $rs if $rs;

	# eg. /var/www/imscp/engine/messenger
	$rs = setRights(
		"$main::imscpConfig{'ENGINE_ROOT_DIR'}/messenger", {
		user => $rootUName, group => $imscpGName, dirmode => '0750', filemode => '0750', recursive => 1
	});
	return $rs if $rs;

	# eg. /var/log/imscp/imscp-arpl-msgr
	$rs = setRights(
		"$main::imscpConfig{'LOG_DIR'}/imscp-arpl-msgr", {
		user => $mtaUName, group => $imscpGName, dirmode => '0750', filemode => '0600', recursive => 1
	});
	return $rs if $rs;

	# eg. /var/mail/virtual
	$rs = setRights(
		$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}, {
		user => $mtaUName, group => $mtaGName, dirmode => '0750', filemode => '0640', recursive => 1
	});
	return $rs if $rs;

	# eg. /usr/sbin/maillogconvert.pl
	setRights('/usr/sbin/maillogconvert.pl', { user => $rootUName, group => $rootGName, mode => '0750' });
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::mta::postfix::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();
	$self->{'mta'} = Servers::mta::postfix->getInstance();

	$self->{'eventManager'}->trigger(
		'beforeMtaInitInstaller', $self, 'postfix'
	) and fatal('postfix - beforeMtaInitInstaller has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/postfix";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'lkptsDir'} = "$self->{'cfgDir'}/imscp";
	$self->{'config'} = $self->{'mta'}->{'config'};

	# Merge old config file with new config file
	my $oldConf = "$self->{'cfgDir'}/postfix.old.data";
	if(-f $oldConf) {
		tie my %oldConfig, 'iMSCP::Config', fileName => $oldConf;

		for my $param(keys %oldConfig) {
			if(exists $self->{'config'}->{$param}) {
				$self->{'config'}->{$param} = $oldConfig{$param};
			}
		}
	}

	$self->{'eventManager'}->trigger(
		'afterMtaInitInstaller', $self, 'postfix'
	) and fatal('postfix - afterMtaInitInstaller has failed');

	$self;
}

=item _addUsersAndGroups()

 Add users and groups

 Return int 0 on success, other on failure

=cut

sub _addUsersAndGroups
{
	my $self = shift;

	my @groups = ([
		$self->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # Group name
		'yes' # Whether it's a system group
	]);

	my @users = ([
		$self->{'config'}->{'MTA_MAILBOX_UID_NAME'}, # User name
		$self->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # User primary group name
		'vmail_user', # Comment
		$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}, # User homedir
		'yes', # Whether it's a system user
		[$main::imscpConfig{'IMSCP_GROUP'}] # Additional user group(s)
	]);

	my @userToGroups = ();

	my $rs = $self->{'eventManager'}->trigger('beforeMtaAddUsersAndGroups', \@groups, \@users, \@userToGroups);
	return $rs if $rs;

	# Create groups
	my $systemGroup = iMSCP::SystemGroup->getInstance();

	for my $group(@groups) {
		$rs = $systemGroup->addSystemGroup($group->[0], ($group->[1] eq 'yes') ? 1 : 0);
		return $rs if $rs;
	}

	# Create users
	for my $user(@users) {
		my $systemUser = iMSCP::SystemUser->new();
		$systemUser->{'group'} = $user->[1];
		$systemUser->{'comment'} = $user->[2];
		$systemUser->{'home'} = $user->[3];
		$systemUser->{'system'} = 'yes' if $user->[4] eq 'yes';

		$rs = $systemUser->addSystemUser($user->[0]);
		return $rs if $rs;

		if(defined $user->[5]) {
			for my $group(@{$user->[5]}) {
				$rs = $systemUser->addToGroup($group) ;
				return $rs if $rs;
			}
		}
	}

	# User to groups
	for my $entry(@userToGroups) {
		my $systemUser = iMSCP::SystemUser->new();
		my $user = $entry->[0];

		for my $group(@{$entry->[1]}) {
			$rs = $systemUser->addToGroup($group, $user);
			return $rs if $rs;
		}
	}

	$self->{'eventManager'}->trigger('afterMtaAddUsersAndGroups');
}

=item _makeDirs()

 Create directories

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
	my $self = shift;

	my @directories = ([
		$self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}, # eg. /etc/postfix/imscp
		$main::imscpConfig{'ROOT_USER'},
		$main::imscpConfig{'ROOT_GROUP'},
		0755
	],
	# Since 1.1.12
	[
		$self->{'config'}->{'MTA_SASL_CONF_DIR'}, # eg. /etc/postfix/sasl
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
		$main::imscpConfig{'IMSCP_GROUP'},
		0750
	]);

	my $rs = $self->{'eventManager'}->trigger('beforeMtaMakeDirs', \@directories);
	return $rs if $rs;

	for my $dir(@directories) {
		$rs = iMSCP::Dir->new( dirname => $dir->[0] )->make({
			user => $dir->[1], group => $dir->[2], mode => $dir->[3]
		});
		return $rs if $rs;
	}

	$self->{'eventManager'}->trigger('afterMtaMakeDirs');
}

=item _setupSqlUser()

 Setup SASL SQL user

 Return int 0 on success, other on failure

=cut

sub _setupSqlUser
{
	my $self = shift;

	my $dbName = main::setupGetQuestion('DATABASE_NAME');
	my $dbUser = main::setupGetQuestion('SASL_SQL_USER');
	my $dbUserHost = main::setupGetQuestion('DATABASE_USER_HOST');
    $dbUserHost = '127.0.0.1' if $dbUserHost eq 'localhost';
	my $dbPass = main::setupGetQuestion('SASL_SQL_PASSWORD');
	my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

	my $rs = $self->{'eventManager'}->trigger('beforeMtaSetupDb', $dbUser, $dbOldUser, $dbPass, $dbUserHost);
	return $rs if $rs;

	for my $sqlUser ($dbOldUser, $dbUser) {
		next unless $sqlUser || $sqlUser ~~ @main::sqlUsers;

		for my $host(
			$dbUserHost, $main::imscpOldConfig{'DATABASE_USER_HOST'}, $main::imscpOldConfig{'DATABASE_HOST'},
			$main::imscpOldConfig{'BASE_SERVER_IP'}
		) {
			next unless $host;

			if(main::setupDeleteSqlUser($sqlUser, $host)) {
				error(sprintf('Unable to remove %s@%s SQL user or one of its privileges', $sqlUser, $host));
				return 1;
			}
		}
	}

	my ($db, $errStr) = main::setupGetSqlConnect();
	fatal(sprintf('Unable to connect to SQL server: %s', $errStr)) unless $db;

	# Create SQL user if not already created by another server/package installer
	unless($dbUser ~~ @main::sqlUsers) {
		$rs = $db->doQuery('c', 'CREATE USER ?@? IDENTIFIED BY ?', $dbUser, $dbUserHost, $dbPass);
		unless(ref $rs eq 'HASH') {
			error(sprintf('Unable to create %s@%s SQL user: %s', $dbUser, $dbUserHost, $rs));
			return 1;
		}
	} else { # Make any other installer aware of that SQL user
		push @main::sqlUsers, $dbUser unless $dbUser ~~ @main::sqlUsers;
	}

	my $quotedDbName = $db->quoteIdentifier($dbName);

	$rs = $db->doQuery('g', "GRANT SELECT ON $quotedDbName.mail_users TO ?@?", $dbUser, $dbUserHost,);
	unless(ref $rs eq 'HASH') {
		error(sprintf('Unable to add SQL privileges: %s', $rs));
		return 1;
	}

	$self->{'config'}->{'DATABASE_USER'} = $dbUser;
	$self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;

	$self->{'eventManager'}->trigger('afterMtaSetupDb');
}

=item _buildConf()

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeMtaBuildConf');
	return $rs if $rs;

	$rs = $self->_buildMainCfFile();
	return $rs if $rs;

	$rs = $self->_buildMasterCfFile();
	return $rs if $rs;

	$rs = $self->_buildSaslConfFile();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterMtaBuildConf');
}

=item _buildLookupTables()

 Build lookup tables

 Return int 0 on success, other on failure

=cut

sub _buildLookupTables
{
	my $self = shift;

	my $dir = iMSCP::Dir->new( dirname => $self->{'lkptsDir'});
	my @lookupTables = $dir->getFiles();

	my $rs = $self->{'eventManager'}->trigger('beforeMtaBuildLookupTables', \@lookupTables);
	return $rs if $rs;

	for my $table(@lookupTables) {
		my $file = iMSCP::File->new( filename => "$self->{'lkptsDir'}/$table" );

		$rs = $file->copyFile($self->{'wrkDir'});
		return $rs if $rs;

		$rs = $file->copyFile("$self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}");
		return $rs if $rs;

		$self->{'mta'}->{'postmap'}->{"$self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}/$table"} = 1;
	}

	$self->{'eventManager'}->trigger('afterMtaBuildLookupTables', \@lookupTables);
}

=item _buildAliasesDb()

 Build aliases database

 Return int 0 on success, other on failure

=cut

sub _buildAliasesDb
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeMtaBuildAliases');
	return $rs if $rs;

	my ($stdout, $stderr);
	$rs = execute('newaliases', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Error while executing newaliases command") if ! $stderr && $rs;
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterMtaBuildAliases');
}

=item _saveConf()

 Save configuration file

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = shift;

	iMSCP::File->new( filename => "$self->{'cfgDir'}/postfix.data" )->copyFile("$self->{'cfgDir'}/postfix.old.data");
}

=item _bkpConfFile($cfgFile)

 Backup configuration file

 Param string $cfgFile Configuration file path
 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
	my ($self, $cfgFile) = @_;

	my $rs = $self->{'eventManager'}->trigger('beforeMtaBkpConfFile', $cfgFile);
	return $rs if $rs;

	if(-f $cfgFile) {
		my $file = iMSCP::File->new( filename => $cfgFile );
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

	$self->{'eventManager'}->trigger('afterMtaBkpConfFile', $cfgFile);
}

=item _buildMainCfFile()

 Build main.cf file

 Return int 0 on success, other on failure

=cut

sub _buildMainCfFile
{
	my $self = shift;

	my $rs = $self->_bkpConfFile("self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}/main.cf");
	return $rs if $rs;

	my $baseServerIpType = iMSCP::Net->getInstance->getAddrVersion($main::imscpConfig{'BASE_SERVER_IP'});
	my $gid = getgrnam($self->{'config'}->{'MTA_MAILBOX_GID_NAME'});
	my $uid = getpwnam($self->{'config'}->{'MTA_MAILBOX_UID_NAME'});
	my $hostname = $main::imscpConfig{'SERVER_HOSTNAME'};

	my $data = {
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
		CONF_DIR => $main::imscpConfig{'CONF_DIR'},
		SSL => ($main::imscpConfig{'SERVICES_SSL_ENABLED'} eq 'yes') ? '' : '#',
		CERTIFICATE => 'imscp_services'
	};

	my $cfgTpl;
	$rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'postfix', 'main.cf', \$cfgTpl, $data);
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/main.cf" )->get();
		unless(defined $cfgTpl) {
			error("Unable to read $self->{'cfgDir'}/main.cf");
			return 1;
		}
	}

	$rs = $self->{'eventManager'}->trigger('beforeMtaBuildMainCfFile', \$cfgTpl, 'main.cf');
	return $rs if $rs;

	$cfgTpl = process($data, $cfgTpl);

	# Fix for #790
	my ($stdout, $stderr);
	execute("postconf -h mail_version", \$stdout, \$stderr);
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
		$cfgTpl =~ s/smtpd_recipient_restrictions/smtpd_relay_restrictions =\n\nsmtpd_recipient_restrictions/;
	}

	$rs = $self->{'eventManager'}->trigger('afterMtaBuildMainCfFile', \$cfgTpl, 'main.cf');
	return $rs if $rs;

	my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/main.cf" );

	$rs = $file->set($cfgTpl);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$file->copyFile($self->{'config'}->{'POSTFIX_CONF_FILE'});
}

=item _buildMasterCfFile()

 Build master.cf file

 Return int 0 on success, other on failure

=cut

sub _buildMasterCfFile
{
	my $self = shift;

	my $rs = $self->_bkpConfFile("self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}/master.cf");
	return $rs if $rs;

	my $data = {
		MTA_MAILBOX_UID_NAME => $self->{'config'}->{'MTA_MAILBOX_UID_NAME'},
		IMSCP_GROUP => $main::imscpConfig{'IMSCP_GROUP'},
		ARPL_PATH => $main::imscpConfig{'ROOT_DIR'}."/engine/messenger/imscp-arpl-msgr"
	};

	my $cfgTpl;
	$rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'postfix', 'master.cf', \$cfgTpl, $data);
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/master.cf" )->get();
		unless(defined $cfgTpl) {
			error("Unable to read $self->{'cfgDir'}/master.cf");
			return 1;
		}
	}

	$rs = $self->{'eventManager'}->trigger('beforeMtaBuildMasterCfFile', \$cfgTpl, 'master.cf');
	return $rs if $rs;

	$cfgTpl = process($data, $cfgTpl);

	$rs = $self->{'eventManager'}->trigger('afterMtaBuildMasterCfFile', \$cfgTpl, 'master.cf');
	return $rs if $rs;

	my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/master.cf" );

	$rs = $file->set($cfgTpl);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$file->copyFile($self->{'config'}->{'POSTFIX_MASTER_CONF_FILE'});
}

=item _buildSaslConfFile()

 Build SASL configuration file

 Return int 0 on success, other on failure

=cut

sub _buildSaslConfFile
{
	my $self = shift;

	my $rs = $self->_bkpConfFile("self->{'config'}->{'MTA_SASL_CONF_DIR'}/smtpd.conf");
	return $rs if $rs;

	my $dbHost = $main::imscpConfig{'DATABASE_HOST'};

	my $data = {
		DATABASE_HOST => ($dbHost eq 'localhost') ? '127.0.0.1' : $dbHost, # Force TCP connection
		DATABASE_PORT => $main::imscpConfig{'DATABASE_PORT'},
		DATABASE_NAME => $main::imscpConfig{'DATABASE_NAME'},
		DATABASE_USER => $self->{'config'}->{'DATABASE_USER'},
		DATABASE_PASSWORD => $self->{'config'}->{'DATABASE_PASSWORD'}
	};

	my $cfgTpl;
	$rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'postfix', 'smtpd.conf', \$cfgTpl, $data);
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/sasl/smtpd.conf")->get();
		unless(defined $cfgTpl) {
			error("Unable to read $self->{'cfgDir'}/sasl/smtpd.conf");
			return 1;
		}
	}

	$rs = $self->{'eventManager'}->trigger('beforeMtaBuildSaslConfFile', \$cfgTpl, 'smtpd.conf');
	return $rs if $rs;

	$cfgTpl = process($data, $cfgTpl);

	$rs = $self->{'eventManager'}->trigger('afterMtaBuildaslConfFil', \$cfgTpl, 'smtpd.conf');
	return $rs if $rs;

	my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/smtpd.conf");

	$rs = $file->set($cfgTpl);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$file->copyFile("$self->{'config'}->{'MTA_SASL_CONF_DIR'}/smtpd.conf");
}

=item _oldEngineCompatibility()

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeMtaOldEngineCompatibility');
	return $rs if $rs;

	if(-f '/etc/sasldb2') {
		$rs = iMSCP::File->new( filename => '/etc/sasldb2' )->delFile();
		return $rs if $rs;
	}

	if(-f '/var/spool/postfix/etc/sasldb2') {
		$rs = iMSCP::File->new( filename => '/var/spool/postfix/etc/sasldb2' )->delFile();
		return $rs if $rs;
	}

	$self->{'eventManager'}->trigger('afterMtadOldEngineCompatibility');
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
