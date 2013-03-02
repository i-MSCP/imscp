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
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l;declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

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
	my $rs = 0;

	# Add installer dialog in setup dialog stack
	$rs = $hooksManager->register(
		'beforeSetupDialog', sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askDovecot(@_) }); 0; }
	);

	if(defined $main::imscpConfig{'MTA_SERVER'} && lc($main::imscpConfig{'MTA_SERVER'}) eq 'postfix') {
		$rs |= $hooksManager->register('beforeMtaBuildMainCfFile', sub { $self->buildPostfixConf(@_); });
		$rs |= $hooksManager->register('beforeMtaBuildMasterCfFile', sub { $self->buildPostfixConf(@_); });
	}

	$rs;
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

	my $dbType = main::setupGetQuestion('DATABASE_TYPE');
	my $dbHost = main::setupGetQuestion('DATABASE_HOST');
	my $dbPort = main::setupGetQuestion('DATABASE_PORT');
	my $dbName = main::setupGetQuestion('DATABASE_NAME');

	my $dbUser = $main::preseed{'DOVECOT_SQL_USER'} || $self::dovecotConfig{'DATABASE_USER'} ||
		$self::dovecotOldConfig{'DATABASE_USER'} || 'dovecot_user';

	my $dbPass = $main::preseed{'DOVECOT_SQL_PASSWORD'} || $self::dovecotConfig{'DATABASE_PASSWORD'} ||
		$self::dovecotOldConfig{'DATABASE_PASSWORD'} || '';

	my ($rs, $msg) = (0, '');

	if(
		$main::reconfigure ~~ ['po', 'servers', 'all', 'forced']
		|| main::setupCheckSqlConnect($dbType, '', $dbHost, $dbPort, $dbUser, $dbPass)
	) {
		# Ask for the dovecot restricted SQL username
		do{
			($rs, $dbUser) = iMSCP::Dialog->factory()->inputbox(
				"\nPlease enter an username for the restricted dovecot SQL user:", $dbUser
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

=item install()

 Process installation.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;
	my $rs = 0;

	$rs |= $self->_bkpConfFile($_) for ('dovecot.conf', 'dovecot-sql.conf');
	$rs |= $self->_setupDb();
	$rs |= $self->_buildConf();
	$rs |= $self->_saveConf();

	# Migrate from Courier if needed
	if(defined $main::imscpOldConfig{'PO_SERVER'} && $main::imscpOldConfig{'PO_SERVER'} eq 'courier') {
		$rs |= $self->_migrateFromCourier();
	}

	$rs;
}

=back

=head1 HOOK FUNCTIONS

=over 4

=item buildPostfixConf()

 Build Dovecot SASL and LDA parameters for Postfix.

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
		my $dovecotConfigSnippet = <<EOF;
smtpd_sasl_type = dovecot
smtpd_sasl_path = private/auth
EOF

	$$content =~ s/(# SASL parameters\n)/$1$dovecotConfigSnippet/;

	# LDA part
	$$content .= <<EOF

virtual_transport = dovecot
dovecot_destination_recipient_limit = 1
EOF

	} elsif($filename eq 'master.cf') {
		# LDA part
		my $dovecotConfigSnippet .= <<EOF;

dovecot   unix  -       n       n       -       -       pipe
  flags=DRhu user={ARPL_USER} argv=/usr/lib/dovecot/deliver -f \${sender} -d \${recipient} {SFLAG}
EOF
		$$content .= iMSCP::Templator::process(
			{ SFLAG => (version->new($self->{'version'}) < version->new('2.0.0') ? '-s' : '') },
			$dovecotConfigSnippet
		);
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by new(). Initialize instance.

 Return Servers::po::dovecot::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger('beforePodInitInstaller', $self, 'dovecot');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/dovecot";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	my $conf = "$self->{'cfgDir'}/dovecot.data";
	my $oldConf = "$self->{'cfgDir'}/dovecot.old.data";

	tie %self::dovecotConfig, 'iMSCP::Config','fileName' => $conf, 'noerrors' => 1;

	if(-f $oldConf) {
		tie %self::dovecotOldConfig, 'iMSCP::Config','fileName' => $oldConf, 'noerrors' => 1;
		%self::dovecotConfig = (%self::dovecotConfig, %self::dovecotOldConfig);
	}

	$self->_getVersion() and fatal('Unable to get dovecot version');

	$self->{'hooksManager'}->trigger('afterPodInitInstaller', $self, 'dovecot');

	$self;
}

=item _getVersion()

 Get Dovecot version.

 Return int 0 on success, other on failure

=cut

sub _getVersion
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforePoGetVersion');
	return $rs if $rs;

	my ($stdout, $stderr);
	$rs = execute('dovecot --version', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;
	error("Cannot get dovecot version") if !$stderr and $rs;
	return $rs if $rs;

	chomp($stdout);
	$stdout =~ m/^([0-9\.]+)\s*/;

	if($1) {
		$self->{'version'} = $1;
	} else {
		error("Can't get dovecot version");
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
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforePoBkpConfFile', $cfgFile);

	if(! $rs && -f "$self::dovecotConfig{'DOVECOT_CONF_DIR'}/$cfgFile"){
		my $file = iMSCP::File->new('filename' => "$self::dovecotConfig{'DOVECOT_CONF_DIR'}/$cfgFile");

		if(!-f "$self->{'bkpDir'}/$cfgFile.system") {
			$rs = $file->copyFile("$self->{'bkpDir'}/$cfgFile.system");
		} else {
			my $timestamp = time;
			$rs = $file->copyFile("$self->{'bkpDir'}/$cfgFile.$timestamp");
		}
	}

	$rs |= $self->{'hooksManager'}->trigger('afterPoBkpConfFile', $cfgFile);

	$rs;
}

=item _getVersion()

 Setup database for dovecot.

 Return int 0 on success, other on failure

=cut

sub _setupDb
{
	my $self = shift;
	my $rs = 0;

	my $dbUser = $self::dovecotConfig{'DATABASE_USER'};
	my $dbOldUser = $self::dovecotOldConfig{'DATABASE_USER'} || '';
	my $dbPass = $self::dovecotConfig{'DATABASE_PASSWORD'};
	my $dbUserHost = $main::imscpConfig{'SQL_SERVER'} ne 'remote_server'
		? $main::imscpConfig{'DATABASE_HOST'} : $main::imscpConfig{'BASE_SERVER_IP'};

	$rs = $self->{'hooksManager'}->trigger('beforePoSetupDb', $dbUser, $dbOldUser, $dbPass, $dbUserHost);
	return $rs if $rs;

	# Remove old dovecot restricted SQL user and all it privileges (if any)
	for($main::imscpOldConfig{'DATABASE_HOST'} || '', $main::imscpOldConfig{'BASE_SERVER_IP'} || '') {
		next if $_ eq '' || $dbOldUser eq '';
		$rs = main::setupDeleteSqlUser($dbOldUser, $_);
		error("Unable to remove the old dovecot '$dbOldUser' restricted SQL user") if $rs;
		return 1 if $rs;
	}

	# Ensure new dovecot restricted SQL user do not already exists by removing it
	$rs = main::setupDeleteSqlUser($dbUserHost, $dbUser);
	error("Unable to delete the dovecot '$dbUser' restricted SQL user") if $rs;
	return 1 if $rs;

	# Get SQL connection with full privileges
	my ($database, $errStr) = main::setupGetSqlConnect();
	fatal('Unable to connect to SQL Server: $errStr') if ! $database;

	# Add new dovecot restricted SQL user with needed privileges

	$rs = $database->doQuery(
		'dummy',
		"GRANT SELECT ON `$main::imscpConfig{'DATABASE_NAME'}`.* TO ?@? IDENTIFIED BY ?",
		$dbUser,
		$dbUserHost,
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
		$dbUserHost
	);
	if(ref $rs ne 'HASH') {
		error(
			"Unable to add privileges on the '$main::imscpConfig{'DATABASE_NAME'}.quota_dovecot' table for the " .
			" '$dbUser' SQL user: $rs"
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
	my $rs = 0;

	require Servers::mta;

	my $mta	= Servers::mta->factory($main::imscpConfig{'MTA_SERVER'});

	my $cfg = {
		DATABASE_TYPE => $main::imscpConfig{'DATABASE_TYPE'},
		DATABASE_HOST => (
			$main::imscpConfig{'DATABASE_PORT'} && $main::imscpConfig{'DATABASE_PORT'} ne 'localhost'
				? "$main::imscpConfig{'DATABASE_HOST'} port=$main::imscpConfig{'DATABASE_PORT'}"
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
		DOVECOT_CONF_DIR => $self::dovecotConfig{'DOVECOT_CONF_DIR'},
		ENGINE_ROOT_DIR => $main::imscpConfig{'ENGINE_ROOT_DIR'}
	};

	my $cfgFiles = {
		'dovecot.conf' =>(version->new($self->{'version'}) < version->new('2.0.0') ? 'dovecot.conf.1' : 'dovecot.conf.2'),
		'dovecot-sql.conf' => 'dovecot-sql.conf',
		'dovecot-dict-sql.conf' => 'dovecot-dict-sql.conf'
	};

	for (keys %{$cfgFiles}) {
		my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/$cfgFiles->{$_}");
		my $cfgTpl = $file->get();
		return 1 if ! $cfgTpl;

		$rs = $self->{'hooksManager'}->trigger('beforePoBuildConf', \$cfgTpl, $_);

		$cfgTpl = iMSCP::Templator::process($cfg, $cfgTpl);
		return 1 if ! $cfgTpl;

		$rs |= $self->{'hooksManager'}->trigger('afterPoBuildConf', \$cfgTpl, $_);

		$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/$_");
		$rs |= $file->set($cfgTpl);
		$rs |= $file->save();
		$rs |= $file->mode(0640);
		$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $mta->{'MTA_MAILBOX_GID_NAME'});
		$rs |= $file->copyFile($self::dovecotConfig{'DOVECOT_CONF_DIR'});

		last if $rs;
	}

	my $file = iMSCP::File->new('filename' => "$self::dovecotConfig{'DOVECOT_CONF_DIR'}/dovecot.conf") if ! $rs;
	$rs |= $file->mode(0644);

	$rs;
}

=item _saveConf()

 Save Dovecot configuration.

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = shift;
	my $rs = 0;

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/dovecot.data");
	my $cfg = $file->get() or return 1;

	$rs = $self->{'hooksManager'}->trigger('beforePoSaveConf', \$cfg, 'dovecot.old.data');

	$rs |= $file->mode(0640);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/dovecot.old.data");
	$file->set($cfg);
	$rs |= $file->save;
	$rs |= $file->mode(0640);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$rs |= $self->{'hooksManager'}->trigger('afterPoSaveConf', 'dovecot.old.data');

	$rs;
}

=item _migrateFromCourier()

 Migrate mailboxes from Courier.

 Return int 0 on success, other on failure

=cut

sub _migrateFromCourier
{
	my $self = shift;
	my $rs = 0;

	$rs = $self->{'hooksManager'}->trigger('beforePoMigrateFromCourier');

	# Getting i-MSCP MTA server implementation instance
	require Servers::mta;
	my $mta	= Servers::mta->factory();

	my $binPath = "perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlVendor/courier-dovecot-migrate.pl";
	my $mailPath = "$mta->{'MTA_VIRTUAL_MAIL_DIR'}";

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
	$rs = $domainDirs->get();
	return $rs if $rs;

	for($domainDirs->getDirs()) {

		my $mailboxesDirs = iMSCP::Dir->new('dirname' => "$mailPath/$_");
		$rs = $mailboxesDirs->get();
		return $rs if $rs;

		for my $mailDir($mailboxesDirs->getDirs()) {

			if(-f "$mailPath/$_/$mailDir/courierimapsubscribed") {

				my $courierimapsubscribedFile = iMSCP::File->new(
					'filename' => "$mailPath/$_/$mailDir/courierimapsubscribed"
				);

				$rs = $courierimapsubscribedFile->copyFile("$mailPath/$_/$mailDir/subscriptions");
				return $rs if $rs;

				my $subscriptionsFile = iMSCP::File->new('filename' => "$mailPath/$_/$mailDir/subscriptions");
				my $subscriptionsFileContent = $subscriptionsFile->get();

				if(!defined $subscriptionsFileContent) {
					error('Unable to read dovecot subscriptions file newly created');
					return 1;
				}

				# Converting any subscription entry to dovecot format
				$subscriptionsFileContent =~ s/^INBOX\.//gm;

				# Writing new dovecot subscriptions file
				$rs = $subscriptionsFile->set($subscriptionsFileContent);
				$rs |= $subscriptionsFile->save();

				# Removing no longer needed file
				$rs |= $courierimapsubscribedFile->delFile();
			}

			last if $rs;
		}
	}

	$rs |= $self->{'hooksManager'}->trigger('afterPoMigrateFromCourier');

	$rs;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
