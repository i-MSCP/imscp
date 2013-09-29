#!/usr/bin/perl

=head1 NAME

 Servers::po::dovecot::installer - i-MSCP Dovecot IMAP/POP3 Server installer implementation

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

package Servers::po::dovecot::installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::Templator;
use File::Basename;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Dovecot IMAP/POP3 Server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks($hooksManager)

 Register setup hooks.

 Param iMSCP::HooksManager $hooksManager Hooks manager instance
 Return int 0 on success, other on failure

=cut

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	if(defined $main::imscpConfig{'MTA_SERVER'} && lc($main::imscpConfig{'MTA_SERVER'}) eq 'postfix') {
		my $rs = $hooksManager->trigger('beforePoRegisterSetupHooks', $hooksManager, 'dovecot');
    	return $rs if $rs;

		$rs = $hooksManager->register(
			'beforeSetupDialog', sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askDovecot(@_) }); 0; }
		);
		return $rs if $rs;

		$rs = $hooksManager->register('beforeMtaBuildMainCfFile', sub { $self->buildPostfixConf(@_); });
		return $rs if $rs;

		$rs = $hooksManager->register('beforeMtaBuildMasterCfFile', sub { $self->buildPostfixConf(@_); });
		return $rs if $rs;

		$hooksManager->trigger('afterPoRegisterSetupHooks', $hooksManager, 'dovecot');
	} else {
		$main::imscpConfig{'PO_SERVER'} = 'no';
		warning('i-MSCP Dovecot PO server require the Postfix MTA. Installation skipped...');

		0;
	}
}

=item askDovecot($dialog)

 Ask user for Dovecot restricted SQL user.

 Param iMSCP::Dialog::Dialog $dialog Dialog instance
 Return int 0 on success, other on failure

=cut

sub askDovecot
{
	my $self = shift;
	my $dialog = shift;

	my $dbUser = main::setupGetQuestion('DOVECOT_SQL_USER') || $self->{'config'}->{'DATABASE_USER'} || 'dovecot_user';
	my $dbPass = main::setupGetQuestion('DOVECOT_SQL_PASSWORD') || $self->{'config'}->{'DATABASE_PASSWORD'} || '';

	my ($rs, $msg) = (0, '');

	if($main::reconfigure ~~ ['po', 'servers', 'all', 'forced'] || ! ($dbUser && $dbPass)) {
		# Ask for the dovecot restricted SQL username
		do{
			($rs, $dbUser) = iMSCP::Dialog->factory()->inputbox(
				"\nPlease enter a username for the restricted dovecot SQL user:", $dbUser
			);

			# i-MSCP SQL user cannot be reused
			if($dbUser eq main::setupGetQuestion('DATABASE_USER')) {
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
					my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_');

					$dbPass = '';
					$dbPass .= $allowedChars[rand @allowedChars] for 1..16;
				}

				$dbPass =~ s/('|"|`|#|;|\/|\s|\||<|\?|\\)/_/g;
				$dialog->msgbox("\nPassword for the restricted dovecot SQL user set to: $dbPass");
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

	my $rs = $self->{'hooksManager'}->trigger('beforePoInstall', 'dovecot');
	return $rs if $rs;

	$rs = $self->_bkpConfFile($_) for ('dovecot.conf', 'dovecot-sql.conf');
	return $rs if $rs;

	$rs = $self->_setupSqlUser();
	return $rs if $rs;

	$rs = $self->_buildConf();
	return $rs if $rs;

	$rs = $self->_saveConf();
	return $rs if $rs;

	# Migrate from Courier if needed
	if(defined $main::imscpOldConfig{'PO_SERVER'} && $main::imscpOldConfig{'PO_SERVER'} eq 'courier') {
		$rs = $self->_migrateFromCourier();
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterPoInstall', 'dovecot');
}

=back

=head1 HOOK FUNCTIONS

=over 4

=item buildPostfixConf()

 Add Dovecot SASL and LDA parameters for Postfix.

 Filter hook function acting on the following hooks
  - beforeMtaBuildMainCfFile
  - beforeMtaBuildMasterCfFile

 This filter hook function is reponsible to add Dovecot SASL and LDA parameters in Postfix configuration files.

 Return int 0 on success, other on failure

=cut

sub buildPostfixConf
{
	my $self = shift;
	my $content	= shift;
	my $filename = shift;

	if($filename eq 'main.cf') {
		# SASL part
		my $configSnippet = <<EOF;
smtpd_sasl_type = dovecot
smtpd_sasl_path = private/auth
EOF

	$$content =~ s/(# SASL parameters\n)/$1$configSnippet/;

	# LDA part
	$$content .= <<EOF

virtual_transport = dovecot
dovecot_destination_recipient_limit = 1
EOF

	} elsif($filename eq 'master.cf') {
		# LDA part
		my $configSnippet = <<EOF;

dovecot   unix  -       n       n       -       -       pipe
  flags=DRhu user={MTA_MAILBOX_UID_NAME}:{MTA_MAILBOX_GID_NAME} argv={DOVECOT_LDA_PATH} -f \${sender} -d \${recipient} {SFLAG}
EOF

		require Servers::mta;
		my $mta = Servers::mta->factory();

		$$content .= iMSCP::Templator::process(
			{
				MTA_MAILBOX_UID_NAME => $mta->{'config'}-> {'MTA_MAILBOX_UID_NAME'},
				MTA_MAILBOX_GID_NAME => $mta->{'config'}-> {'MTA_MAILBOX_GID_NAME'},
				DOVECOT_LDA_PATH => $self->{'config'}->{'DOVECOT_LDA_PATH'},
				SFLAG => (version->new($self->{'version'}) < version->new('2.0.0') ? '-s' : '')
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

 Return Servers::po::dovecot::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'po'} = Servers::po::dovecot->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforePodInitInstaller', $self, 'dovecot'
	) and fatal('dovecot - beforePoInitInstaller hook has failed');

	$self->{'cfgDir'} = $self->{'po'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'config'} = $self->{'po'}->{'config'};

	my $oldConf = "$self->{'cfgDir'}/dovecot.old.data";

	if(-f $oldConf) {
		tie %{$self->{'oldConfig'}}, 'iMSCP::Config', 'fileName' => $oldConf, 'noerrors' => 1;

		for(keys %{$self->{'oldConfig'}}) {
			if(exists $self->{'config'}->{$_}) {
				$self->{'config'}->{$_} = $self->{'oldConfig'}->{$_};
			}
		}
	}

	$self->_getVersion() and fatal('Unable to get Dovecot version');

	$self->{'hooksManager'}->trigger(
		'afterPodInitInstaller', $self, 'dovecot'
	) and fatal('dovecot - afterPoInitInstaller hook has failed');

	$self;
}

=item _getVersion()

 Get Dovecot version.

 Return int 0 on success, other on failure

=cut

sub _getVersion
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoGetVersion');
	return $rs if $rs;

	my ($stdout, $stderr);
	$rs = execute('/usr/sbin/dovecot --version', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;
	error('Unable to get dovecot version') if $rs && ! $stderr;
	return $rs if $rs;

	chomp($stdout);
	$stdout =~ m/^([0-9\.]+)\s*/;

	if($1) {
		$self->{'version'} = $1;
	} else {
		error("Unable to find Dovecot version");
		return 1;
	}

	$self->{'hooksManager'}->trigger('afterPoGetVersion');
}

=item _bkpConfFile()

 Backup the given file.

 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
	my $self = shift;
	my $cfgFile = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoBkpConfFile', $cfgFile);
	return $rs if $rs;

	if(-f "$self->{'config'}->{'DOVECOT_CONF_DIR'}/$cfgFile") {
		my $file = iMSCP::File->new('filename' => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/$cfgFile");

		if(! -f "$self->{'bkpDir'}/$cfgFile.system") {
			$rs = $file->copyFile("$self->{'bkpDir'}/$cfgFile.system");
			return $rs if $rs;
		} else {
			my $timestamp = time;
			$rs = $file->copyFile("$self->{'bkpDir'}/$cfgFile.$timestamp");
			return $rs if $rs;
		}
	}

	$self->{'hooksManager'}->trigger('afterPoBkpConfFile', $cfgFile);
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

	# Remove any old dovecot SQL user (including privileges)
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

	# Add new dovecot restricted SQL user with needed privileges

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

=item _buildConf()

 Build dovecot configuration files.

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
	my $self = shift;

	require Servers::mta;
	my $mta = Servers::mta->factory();

	my $cfg = {
		DATABASE_TYPE => $main::imscpConfig{'DATABASE_TYPE'},
		DATABASE_HOST =>
			($main::imscpConfig{'DATABASE_PORT'} && $main::imscpConfig{'DATABASE_PORT'} ne 'localhost')
				? "$main::imscpConfig{'DATABASE_HOST'} port=$main::imscpConfig{'DATABASE_PORT'}"
				: $main::imscpConfig{'DATABASE_HOST'}
		,
		DATABASE_USER => $self->{'config'}->{'DATABASE_USER'},
		DATABASE_PASSWORD => $self->{'config'}->{'DATABASE_PASSWORD'},
		DATABASE_NAME => $main::imscpConfig{'DATABASE_NAME'},
		GUI_CERT_DIR => $main::imscpConfig{'GUI_CERT_DIR'},
		HOST_NAME => $main::imscpConfig{'SERVER_HOSTNAME'},
		DOVECOT_SSL => ($main::imscpConfig{'SSL_ENABLED'} eq 'yes' ? 'yes' : 'no'),
		COMMENT_SSL => ($main::imscpConfig{'SSL_ENABLED'} eq 'yes' ? '' : '#'),
		MASTER_GROUP => $main::imscpConfig{'MASTER_GROUP'},
		MTA_MAILBOX_UID_NAME => $mta->{'config'}->{'MTA_MAILBOX_UID_NAME'},
		MTA_MAILBOX_GID_NAME => $mta->{'config'}->{'MTA_MAILBOX_GID_NAME'},
		MTA_MAILBOX_UID => scalar getpwnam($mta->{'config'}->{'MTA_MAILBOX_UID_NAME'}),
		MTA_MAILBOX_GID => scalar getgrnam($mta->{'config'}->{'MTA_MAILBOX_GID_NAME'}),
		POSTFIX_USER => $mta->{'config'}->{'POSTFIX_USER'},
		POSTFIX_GROUP => $mta->{'config'}->{'POSTFIX_GROUP'},
		POSTFIX_SENDMAIL_PATH => $mta->{'config'}->{'POSTFIX_SENDMAIL_PATH'},
		DOVECOT_CONF_DIR => $self->{'config'}->{'DOVECOT_CONF_DIR'},
		DOVECOT_LDA_PATH => $self->{'config'}->{'DOVECOT_LDA_PATH'},
		DOVECOT_SASL_SOCKET_PATH => $self->{'config'}->{'DOVECOT_SASL_SOCKET_PATH'},
		DOVECOT_AUTH_SOCKET_PATH => $self->{'config'}->{'DOVECOT_AUTH_SOCKET_PATH'},
		ENGINE_ROOT_DIR => $main::imscpConfig{'ENGINE_ROOT_DIR'}
	};

	# Transitional code (should be removed in later version
	if(-f "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-dict-sql.conf") {
		iMSCP::File->new('filename' => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-dict-sql.conf")->delFile();
	}

	my %cfgFiles = (
		((version->new($self->{'version'}) < version->new('2.0.0')) ? 'dovecot.conf.1' : 'dovecot.conf.2') => [
			"$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot.conf", # Destpath
			$main::imscpConfig{'ROOT_USER'}, # Owner
			$mta->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # Group
			0640 # Permissions
		],
		'dovecot-sql.conf' => [
		"$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf", # Destpath
			$main::imscpConfig{'ROOT_USER'}, # owner
			$mta->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # Group
			0644 # Permissions
		],
		((version->new($self->{'version'}) < version->new('2.0.0')) ? 'quota-warning.1' : 'quota-warning.2') => [
			"$main::imscpConfig{'ENGINE_ROOT_DIR'}/quota/imscp-dovecot-quota.sh", # Destpath
			$mta->{'config'}->{'MTA_MAILBOX_UID_NAME'}, # Owner
			$mta->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # Group
			0750 # Permissions
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

		$rs = $file->owner($cfgFiles{$_}->[1], $cfgFiles{$_}->[2]);
		return $rs if $rs;

		$rs = $file->mode($cfgFiles{$_}->[3]);
		return $rs if $rs;

		# Install file in production directory
		$rs = $file->copyFile($cfgFiles{$_}->[0]);
		return $rs if $rs;
	}
}

=item _saveConf()

 Save Dovecot configuration.

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = shift;

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/dovecot.data");

	my $rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	my $cfg = $file->get();
	unless (defined $cfg) {
		error("Unable to read $self->{'cfgDir'}/dovecot.data");
		return 1;
	}

	$rs = $self->{'hooksManager'}->trigger('beforePoSaveConf', \$cfg, 'dovecot.old.data');
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/dovecot.old.data");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save;
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterPoSaveConf', 'dovecot.old.data');
}

=item _migrateFromCourier()

 Migrate mailboxes from Courier.

 Return int 0 on success, other on failure

=cut

sub _migrateFromCourier
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoMigrateFromCourier');
	return $rs if $rs;

	# Getting i-MSCP MTA server implementation instance
	require Servers::mta;
	my $mta	= Servers::mta->factory();

	my $binPath = "$main::imscpConfig{'CMD_PERL'} $main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlVendor/courier-dovecot-migrate.pl";
	my $mailPath = "$mta->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}";

	# Converting all mailboxes to dovecot format

	my ($stdout, $stderr);
	$rs = execute("$binPath --to-dovecot --convert --recursive $mailPath", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	error('Error while converting mailboxes to devecot format') if ! $stderr && $rs;
	return $rs if $rs;

	# Converting courier subscription files to dovecot format

	my $domainDirs = iMSCP::Dir->new('dirname' => $mailPath);

	for($domainDirs->getDirs()) {
		my $mailboxesDirs = iMSCP::Dir->new('dirname' => "$mailPath/$_");

		for my $mailDir($mailboxesDirs->getDirs()) {
			if(-f "$mailPath/$_/$mailDir/courierimapsubscribed") {

				my $courierimapsubscribedFile = iMSCP::File->new(
					'filename' => "$mailPath/$_/$mailDir/courierimapsubscribed"
				);

				$rs = $courierimapsubscribedFile->copyFile("$mailPath/$_/$mailDir/subscriptions");
				return $rs if $rs;

				my $subscriptionsFile = iMSCP::File->new('filename' => "$mailPath/$_/$mailDir/subscriptions");
				my $subscriptionsFileContent = $subscriptionsFile->get();

				unless(defined $subscriptionsFileContent) {
					error('Unable to read dovecot subscriptions file newly created');
					return 1;
				}

				# Converting any subscription entry to dovecot format
				$subscriptionsFileContent =~ s/^INBOX\.//gm;

				# Writing new dovecot subscriptions file
				$rs = $subscriptionsFile->set($subscriptionsFileContent);
				return $rs if $rs;

				$rs = $subscriptionsFile->save();
				return $rs if $rs;

				# Removing no longer needed file
				$rs = $courierimapsubscribedFile->delFile();
				return $rs if $rs;
			}
		}
	}

	$self->{'hooksManager'}->trigger('afterPoMigrateFromCourier');
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
