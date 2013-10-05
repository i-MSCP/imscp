#!/usr/bin/perl

=head1 NAME

 Servers::po::courier::installer - i-MSCP Courier IMAP/POP3 Server installer implementation

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

package Servers::po::courier::installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::Rights;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::Templator;
use File::Basename;
use Servers::po::courier;
use Servers::mta::postfix;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Courier IMAP/POP3 Server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks($hooksManager)

 Register setup hooks.

 Param iMSCP::HooksManager $hooksManager Hooks manager instance
 Return int 0 on success, other on failure

=cut

sub registerSetupHooks($$)
{
	my $self = shift;
	my $hooksManager = shift;

	if(defined $main::imscpConfig{'MTA_SERVER'} && lc($main::imscpConfig{'MTA_SERVER'}) eq 'postfix') {
		my $rs = $hooksManager->trigger('beforePoRegisterSetupHooks', $hooksManager, 'courier');
		return $rs if $rs;

		$rs = $hooksManager->register(
			'beforeSetupDialog', sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askCourier(@_) }); 0; }
		);
		return $rs if $rs;

		$rs = $hooksManager->register('beforeMtaBuildMainCfFile', sub { $self->buildPostfixConf(@_); });
		return $rs if $rs;

		$rs = $hooksManager->register('beforeMtaBuildMasterCfFile', sub { $self->buildPostfixConf(@_); });
		return $rs if $rs;

		$hooksManager->trigger('afterPoRegisterSetupHooks', $hooksManager, 'courier');
	} else {
		$main::imscpConfig{'PO_SERVER'} = 'no';
		warning('i-MSCP Courier PO server require the Postfix MTA. Installation skipped...');

		0;
	}
}

=item askCourier($dialog)

 Ask user for authdaemon restricted SQL user.

 Param iMSCP::Dialog::Dialog $dialog Dialog instance
 Return int 0 on success, other on failure

=cut

sub askCourier($$)
{
	my $self = shift;
	my $dialog = shift;

	my $dbUser = main::setupGetQuestion('AUTHDAEMON_SQL_USER') || $self->{'config'}->{'DATABASE_USER'} || 'authdaemon_user';
	my $dbPass = main::setupGetQuestion('AUTHDAEMON_SQL_PASSWORD') || $self->{'config'}->{'DATABASE_PASSWORD'} || '';

	my ($rs, $msg) = (0, '');

	if($main::reconfigure ~~ ['po', 'servers', 'all', 'forced'] || ! ($dbUser && $dbPass)) {
		# Ask for the authdaemon restricted SQL username
		do{
			($rs, $dbUser) = iMSCP::Dialog->factory()->inputbox(
				"\nPlease enter a username for the restricted authdaemon SQL user:", $dbUser
			);

			# i-MSCP SQL user cannot be reused
			if($dbUser eq main::setupGetQuestion('DATABASE_USER')) {
				$msg = "\n\n\\Z1You cannot reuse the i-MSCP SQL user '$dbUser'.\\Zn\n\nPlease, try again:";
				$dbUser = '';
			}
		} while ($rs != 30 && ! $dbUser);

		if($rs != 30) {
			# Ask for the authdaemon restricted SQL user password
			($rs, $dbPass) = $dialog->inputbox(
				'\nPlease, enter a password for the restricted authdaemon SQL user (blank for autogenerate):', $dbPass
			);

			if($rs != 30) {
				if(! $dbPass) {
					my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_');

					$dbPass = '';
					$dbPass .= $allowedChars[rand @allowedChars] for 1..16;
				}

				$dbPass =~ s/('|"|`|#|;|\/|\s|\||<|\?|\\)/_/g;
				$dialog->msgbox("\nPassword for the restricted authdaemon SQL user set to: $dbPass");
				$dialog->set('cancel-label');
			}
		}
	}

	if($rs != 30) {
		$self->{'config'}->{'DATABASE_USER'} = $dbUser;
		$self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
	}

	$rs;
}

=item install()

 Process installation.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoInstall', 'courier');
	return $rs if $rs;

	for(
		$self->{'config'}->{'CMD_AUTHDAEMON'},
		"$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authdaemonrc",
		"$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authmysqlrc",
		"$self->{'config'}->{'AUTHLIB_CONF_DIR'}/self->{'config'}->{'COURIER_IMAP_SSL'}",
		"$self->{'config'}->{'AUTHLIB_CONF_DIR'}/$self->{'config'}->{'COURIER_POP_SSL'}"
	) {
		$rs = $self->_bkpConfFile($_);
		return $rs if $rs;
	}

	$rs = $self->_setupSqlUser();
	return $rs if $rs;

	$rs = $self->_overrideAuthdaemonInitScript();
	return $rs if $rs;

	$rs = $self->_buildConf();
	return $rs if $rs;

	$rs = $self->_saveConf();
	return $rs if $rs;

	# Migrate from dovecot if needed
	if(defined $main::imscpOldConfig{'PO_SERVER'} && $main::imscpOldConfig{'PO_SERVER'} eq 'dovecot') {
		$rs = $self->_migrateFromDovecot();
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterPoInstall', 'courier');
}

=item setEnginePermissions()

 Set permissions.

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoSetEnginePermissions');
	return $rs if $rs;

	$rs = setRights(
		$self->{'config'}->{'AUTHLIB_SOCKET_DIR'},
		{
			'user' => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'},
			'group' => $self->{'config'}->{'AUTHDAEMON_GROUP'},
			'mode' => '0750'
		}
	);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterPoSetEnginePermissions');
}

=head1 HOOK FUNCTIONS

=over 4

=item buildPostfixConf($fileContent, $fileName)

 Add maildrop MDA in Postfix configuration files.

 Filter hook function acting on the following hooks
  - beforeMtaBuildMainCfFile
  - beforeMtaBuildMasterCfFile

 This filter hook function is reponsible to add the maildrop deliver in Postfix configuration files.

 Param string $fileContent Configuration file content
 Param string $fileName Configuration file name
 Return int 0 on success, other on failure

=cut

sub buildPostfixConf($$$)
{
	my $self = shift;
	my $fileContent = shift;
	my $fileName = shift;

	if($fileName eq 'main.cf') {
		$$fileContent .= <<EOF

virtual_transport = maildrop
maildrop_destination_concurrency_limit = 2
maildrop_destination_recipient_limit = 1
EOF

	} elsif($fileName eq 'master.cf') {
		my $configSnippet = <<EOF;

maildrop  unix  -       n       n       -       -       pipe
 flags=DRhu user={MTA_MAILBOX_UID_NAME}:{MTA_MAILBOX_GID_NAME} argv={MAILDROP_MDA_PATH} -w 90 -d \${user}@\${nexthop} \${extension} \${recipient}
 \${user} \${nexthop} \${sender}
EOF

		$$fileContent .= iMSCP::Templator::process(
			{
				MTA_MAILBOX_UID_NAME => $self->{'mta'}->{'config'}-> {'MTA_MAILBOX_UID_NAME'},
				MTA_MAILBOX_GID_NAME => $self->{'mta'}->{'config'}-> {'MTA_MAILBOX_GID_NAME'},
				MAILDROP_MDA_PATH => $self->{'config'}->{'MAILDROP_MDA_PATH'}
			},
			$configSnippet
		);
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance.

 Return Servers::po::courier::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'po'} = Servers::po::courier->getInstance();
	$self->{'mta'} = Servers::mta::postfix->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforePodInitInstaller', $self, 'courier'
	) and fatal('courier - beforePoInitInstaller hook has failed');

	$self->{'cfgDir'} = $self->{'po'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'config'}= $self->{'po'}->{'config'};

	my $oldConf = "$self->{'cfgDir'}/courier.old.data";

	if(-f $oldConf) {
		tie my %oldConfig, 'iMSCP::Config', 'fileName' => $oldConf, 'noerrors' => 1;

		for(keys %oldConfig) {
			if(exists $self->{'config'}->{$_}) {
				$self->{'config'}->{$_} = $oldConfig{$_};
			}
		}
	}

	$self->{'hooksManager'}->trigger(
		'afterPodInitInstaller', $self, 'courier'
	) and fatal('courier - afterPoInitInstaller hook has failed');

	$self;
}

=item _bkpConfFile($filePath)

 Backup the given file.

 Param string $filePath File path
 Return int 0 on success, other on failure

=cut

sub _bkpConfFile($$)
{
	my $self = shift;
	my $filePath = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoBkpConfFile', $filePath);
	return $rs if $rs;

	if(-f $filePath) {
		my $fileName = fileparse($filePath);
		my $file = iMSCP::File->new('filename' => $filePath);

		if(! -f "$self->{'bkpDir'}/$fileName.system") {
			$rs = $file->copyFile("$self->{'bkpDir'}/$fileName.system");
			return $rs if $rs;
		} else {
			my $timestamp = time;
			$rs = $file->copyFile("$self->{'bkpDir'}/$fileName.$timestamp");
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterPoBkpConfFile', $filePath);
}

=item _setupSqlUser()

 Setup SQL user.

 Return int 0 on success, other on failure

=cut

sub _setupSqlUser
{
	my $self = shift;

	my $dbUser = $self->{'config'}->{'DATABASE_USER'};
	my $dbUserHost = main::setupGetQuestion('DATABASE_USER_HOST');
	my $dbPass = $self->{'config'}->{'DATABASE_PASSWORD'};
	my $dbOldUser = $self->{'oldConfig'}->{'DATABASE_USER'} || '';

	my $rs = $self->{'hooksManager'}->trigger('beforePoSetupDb', $dbUser, $dbOldUser, $dbPass, $dbUserHost);
	return $rs if $rs;

	# Remove any old authdaemon SQL user (including privileges)
	for my $sqlUser ($dbOldUser, $dbUser) {
		next if ! $sqlUser;

		for($dbUserHost, $main::imscpOldConfig{'DATABASE_HOST'}, $main::imscpOldConfig{'BASE_SERVER_IP'}) {
			next if ! $_;

			$rs = main::setupDeleteSqlUser($sqlUser, $_);
			error("Unable to remove '$sqlUser\@$_' SQL user or one of its privileges") if $rs;
			return 1 if $rs;
		}
	}

	# Get SQL connection with full privileges
	my ($database, $errStr) = main::setupGetSqlConnect();
	fatal("Unable to connect to SQL Server: $errStr") if ! $database;

	# Add new authdaemon restricted SQL user with needed privileges

	$rs = $database->doQuery(
		'dummy',
		"GRANT SELECT ON `$main::imscpConfig{'DATABASE_NAME'}`.`mail_users` TO ?@? IDENTIFIED BY ?",
		$dbUser,
		$dbUserHost,
		$dbPass
	);
	unless(ref $rs eq 'HASH') {
		error(
			"Unable to add privileges on the `$main::imscpConfig{'DATABASE_NAME'}`.`mail_users` table for the '$dbUser'" .
			" SQL user: $rs"
		);
		return 1;
	}

	$self->{'hooksManager'}->trigger('afterPoSetupDb');
}

=item _overrideAuthdaemonInitScript()

 Override courier-authdaemon init script

 Return int 0 on success, other on failure

=cut

sub _overrideAuthdaemonInitScript
{
	my $self = shift;

	my $file = iMSCP::File->new('filename' => $self->{'config'}->{'CMD_AUTHDAEMON'});

	my $fileContent = $file->get();
	unless($fileContent) {
		error("Unable to read $self->{'config'}->{'CMD_AUTHDAEMON'} file");
		return 1;
	}

	my $mailUser = $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'};
	my $authdaemonUser = $self->{'config'}->{'AUTHDAEMON_USER'};
	my $authdaemonGroup = $self->{'config'}->{'AUTHDAEMON_GROUP'};

	$fileContent =~ s/$authdaemonUser:$authdaemonGroup\s+\$rundir$/$mailUser:$authdaemonGroup \$rundir/m;

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0755);
	return $rs if $rs;

	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
}

=item _buildConf()

 Build courier configuration files.

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
	my $self = shift;

	my $rs = $self->_buildAuthdaemonrcFile();
	return $rs if $rs;

	$rs = $self->_buildSslConfFiles();
	return $rs if $rs;

	my $cfg = {
		DATABASE_HOST => $main::imscpConfig{'DATABASE_HOST'},
		DATABASE_PORT => $main::imscpConfig{'DATABASE_PORT'},
		DATABASE_USER => $self->{'config'}->{'DATABASE_USER'},
		DATABASE_PASSWORD => $self->{'config'}->{'DATABASE_PASSWORD'},
		DATABASE_NAME => $main::imscpConfig{'DATABASE_NAME'},
		HOST_NAME => $main::imscpConfig{'SERVER_HOSTNAME'},
		MTA_MAILBOX_UID => scalar getpwnam($self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'}),
		MTA_MAILBOX_GID => scalar getgrnam($self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}),
		MTA_VIRTUAL_MAIL_DIR => $self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}
	};

	my %cfgFiles = (
		'authmysqlrc' => [
			"$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authmysqlrc", # Destpath
			$self->{'config'}->{'AUTHDAEMON_USER'}, # Owner
			$self->{'config'}->{'AUTHDAEMON_GROUP'}, # Group
			0660 # Permissions
		],
		'quota-warning' => [
			$self->{'config'}->{'QUOTA_WARN_MSG_PATH'}, # Destpath
			$self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'}, # Owner
			$main::imscpConfig{'ROOT_GROUP'}, # Group
			0640 # Permissions
		]
	);

	for (keys %cfgFiles) {
		# Get configuration template content
		my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/$_");
		my $cfgTpl = $file->get();
		return 1 if ! defined $cfgTpl;

		my $rs = $self->{'hooksManager'}->trigger('beforePoBuildConf', \$cfgTpl, $_);
		return $rs if $rs;

		# Replace placeholders
		$cfgTpl = iMSCP::Templator::process($cfg, $cfgTpl);
		return 1 if ! defined $cfgTpl;

		$rs = $self->{'hooksManager'}->trigger('afterPoBuildConf', \$cfgTpl, $_);
		return $rs if $rs;

		# Retrieve filename
		my $filename = fileparse($cfgFiles{$_}->[0]);

		# Store file in working directory
		$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$filename");

		$rs = $file->set($cfgTpl);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode($cfgFiles{$_}->[3]);
		return $rs if $rs;

		$rs = $file->owner($cfgFiles{$_}->[1], $cfgFiles{$_}->[2]);
		return $rs if $rs;

		# Install file in production directory
		$rs = $file->copyFile($cfgFiles{$_}->[0]);
		return $rs if $rs;
	}
}

=item _buildAuthdaemonrcFile()

 Build the authdaemonrc file.

 Return int 0 on success, other on failure

=cut

sub _buildAuthdaemonrcFile
{
	my $self = shift;

	# Loading the system file from /etc/imscp/backup
	my $file = iMSCP::File->new('filename' => "$self->{'bkpDir'}/authdaemonrc.system");
	my $rdata = $file->get();

	unless (defined $rdata) {
		error("Unable to read $self->{'bkpDir'}/authdaemonrc.system file");
		return 1;
	}

	my $rs = $self->{'hooksManager'}->trigger('beforePoBuildAuthdaemonrcFile', \$rdata, 'authdaemonrc');
	return $rs if $rs;

	# Building new file (Adding the authmysql module if needed)
	if($rdata !~ /^\s*authmodulelist="(?:.*)?authmysql.*"$/gm) {
		$rdata =~ s/(authmodulelist=")/$1authmysql /gm;
	}

	$rs = $self->{'hooksManager'}->trigger('afterPoBuildAuthdaemonrcFile', \$rdata, 'authdaemonrc');
	return $rs if $rs;

	# Storing new file in the working directory
	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/authdaemonrc");

	$rs = $file->set($rdata);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0660);
	return $rs if $rs;

	$rs = $file->owner($self->{'config'}->{'AUTHDAEMON_USER'}, $self->{'config'}->{'AUTHDAEMON_GROUP'});
	return $rs if $rs;

	# Installing the new file in the production directory
	$file->copyFile("$self->{'config'}->{'AUTHLIB_CONF_DIR'}");
}

=item buildAuthmysqlrcFile()

 Build the authmysqlrc file.

 Return int 0 on success, other on failure

=cut

sub buildAuthmysqlrcFile
{
	0;
}

=item _buildSslConfFiles()

 Build ssl configuration file.

 Return int 0 on success, other on failure

=cut

sub _buildSslConfFiles
{
	my $self = shift;

	for ($self->{'config'}->{'COURIER_IMAP_SSL'}, $self->{'config'}->{'COURIER_POP_SSL'}) {
		last if lc($main::imscpConfig{'SSL_ENABLED'}) ne 'yes';

		my $rs = $self->{'hooksManager'}->trigger('beforePoBuildSslConfFiles', $_);
		return $rs if $rs;

		my $file = iMSCP::File->new('filename' => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/$_");

		my $rdata = $file->get();
		unless (defined $rdata) {
			error("Unable to read $self->{'config'}->{'AUTHLIB_CONF_DIR'}/$_");
			return 1;
		}

		if($rdata =~ m/^TLS_CERTFILE=/msg) {
			$rdata =~ s!^TLS_CERTFILE=.*$!TLS_CERTFILE=$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem!gm;
		} else {
			$rdata .= "TLS_CERTFILE=$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem";
		}

		# Store file in working directory
		$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$_");

		$rs = $file->set($rdata);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		# Install file in production directory
		$rs = $file->copyFile("$self->{'config'}->{'AUTHLIB_CONF_DIR'}");
		return $rs if $rs;

		$rs = $self->{'hooksManager'}->trigger('afterPoBuildSslConfFiles', $_);
		return $rs if $rs;
	}

	0;
}

=item _saveConf()

 Save Courier configuration.

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = shift;

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/courier.data");

	my $rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	my $cfg = $file->get();
	unless(defined $cfg) {
		error("Unable to read $self->{'cfgDir'}/courier.data");
		return 1;
	}

	$rs = $self->{'hooksManager'}->trigger('beforePoSaveConf', \$cfg, 'courier.old.data');
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/courier.old.data");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterPoSaveConf', 'courier.old.data');
}

=item _migrateFromDovecot()

 Migrate mailboxes from Dovecot.

 Return int 0 on success, other on failure

=cut

sub _migrateFromDovecot
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoMigrateFromDovecot');
	return $rs if $rs;

	my $binPath = "$main::imscpConfig{'CMD_PERL'} $main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlVendor/courier-dovecot-migrate.pl";
	my $mailPath = "$self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}";

	# Converting all mailboxes to courier format

	my ($stdout, $stderr);
	$rs = execute("$binPath --to-courier --convert --recursive $mailPath", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	error('Error while converting mails') if ! $stderr && $rs;
	return $rs if $rs;

	# Converting dovecot subscriptions files to courier format

	my $domainDirs = iMSCP::Dir->new('dirname' => $mailPath);

	for($domainDirs->getDirs()) {
		my $mailboxesDirs = iMSCP::Dir->new('dirname' => "$mailPath/$_");

		for my $mailDir($mailboxesDirs->getDirs()) {
			if(-f "$mailPath/$_/$mailDir/subscriptions") {
				my $subscriptionsFile = iMSCP::File->new('filename' => "$mailPath/$_/$mailDir/subscriptions");

				$rs = $subscriptionsFile->copyFile("$mailPath/$_/$mailDir/courierimapsubscribed");
				return $rs if $rs;

				my $courierimapsubscribedFile = iMSCP::File->new(
					'filename' => "$mailPath/$_/$mailDir/courierimapsubscribed"
				);

				my $courierimapsubscribedFileContent = $courierimapsubscribedFile->get();

				unless(defined $courierimapsubscribedFileContent) {
					error('Unable to read courier courierimapsubscribed file newly created');
					return 1;
				}

				# Converting any subscription entry to courier format
				$courierimapsubscribedFileContent =~ s/^(.*)/INBOX.$1/gm;

				# Writing new courier courierimapsubscribed file
				$rs = $courierimapsubscribedFile->set($courierimapsubscribedFileContent);
				return $rs if $rs;

				$rs = $courierimapsubscribedFile->save();
				return $rs if $rs;

				# Removing no longer needed file
				$rs = $subscriptionsFile->delFile();
				return $rs if $rs;
			}
		}
	}

	$self->{'hooksManager'}->trigger('afterPoMigrateFromDovecot');
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
