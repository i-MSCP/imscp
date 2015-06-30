=head1 NAME

 Servers::po::dovecot::installer - i-MSCP Dovecot IMAP/POP3 Server installer implementation

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

package Servers::po::dovecot::installer;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Config;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use File::Basename;
use version;
use Servers::po::dovecot;
use Servers::mta::postfix;
use parent 'Common::SingletonClass';

@main::sqlUsers = () unless @main::sqlUsers;

=head1 DESCRIPTION

 i-MSCP Dovecot IMAP/POP3 Server installer implementation.

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

	if(defined $main::imscpConfig{'MTA_SERVER'} && lc($main::imscpConfig{'MTA_SERVER'}) eq 'postfix') {
		my $rs = $eventManager->register('beforeSetupDialog', sub { push @{$_[0]}, sub { $self->showDialog(@_) }; 0; });
		return $rs if $rs;

		$rs = $eventManager->register('beforeMtaBuildMainCfFile', sub { $self->buildPostfixConf(@_); });
		return $rs if $rs;

		$eventManager->register('beforeMtaBuildMasterCfFile', sub { $self->buildPostfixConf(@_); });
	} else {
		$main::imscpConfig{'PO_SERVER'} = 'no';
		warning('i-MSCP Dovecot PO server require the Postfix MTA. Installation skipped...');

		0;
	}
}

=item showDialog(\%dialog)

 Ask user for Dovecot restricted SQL user

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub showDialog
{
	my ($self, $dialog) = @_;

	my $dbUser = main::setupGetQuestion('DOVECOT_SQL_USER') || $self->{'config'}->{'DATABASE_USER'} || 'dovecot_user';
	my $dbPass = main::setupGetQuestion('DOVECOT_SQL_PASSWORD') || $self->{'config'}->{'DATABASE_PASSWORD'};

	my ($rs, $msg) = (0, '');

	if(
		$main::reconfigure ~~ [ 'po', 'servers', 'all', 'forced' ] ||
		(length $dbUser < 6 || length $dbUser > 16 || $dbUser =~ /[^\x21-\x7e]+/) ||
		(length $dbPass < 6 || $dbPass =~ /[^\x21-\x7e]+/)
	) {
		# Ask for the dovecot restricted SQL username
		do{
			($rs, $dbUser) = $dialog->inputbox(
				"\nPlease enter an username for the Dovecot SQL user:$msg", $dbUser
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
			} elsif($dbUser =~ /[^\x21-\x7e]+/) {
				$msg = "\n\n\\Z1Only printable ASCII characters (excluding space) are allowed.\\Zn\n\nPlease try again:";
				$dbUser = '';
			}
		} while ($rs != 30 && ! $dbUser);

		if($rs != 30) {
			$msg = '';

			do {
				# Ask for the dovecot restricted SQL user password
				($rs, $dbPass) = $dialog->passwordbox(
					"\nPlease, enter a password for the restricted dovecot SQL user (blank for autogenerate):$msg", $dbPass
				);

				if($dbPass ne '') {
					if(length $dbPass < 6) {
						$msg = "\n\n\\Z1Password must be at least 6 characters long.\\Zn\n\nPlease try again:";
						$dbPass = '';
					} elsif($dbPass =~ /[^\x21-\x7e]+/) {
						$msg = "\n\n\\Z1Only printable ASCII characters (excluding space) are allowed.\\Zn\n\nPlease try again:";
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
					my @allowedChr = map { chr } 0x21..0x7e;
					$dbPass = '';
					$dbPass .= $allowedChr[rand @allowedChr] for 1..16;
				}

				$dialog->msgbox("\nPassword for the restricted dovecot SQL user set to: $dbPass");
			}
		}
	}

	if($rs != 30) {
		main::setupSetQuestion('DOVECOT_SQL_USER', $dbUser);
		main::setupSetQuestion('DOVECOT_SQL_PASSWORD', $dbPass);
	}

	$rs;
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	for my $filename('dovecot.conf', 'dovecot-sql.conf') {
		my $rs = $self->_bkpConfFile($filename);
		return $rs if $rs;
	}

	my $rs = $self->_setupSqlUser();
	return $rs if $rs;

	$rs = $self->_buildConf();
	return $rs if $rs;

	$rs = $self->_saveConf();
	return $rs if $rs;

	if(defined $main::imscpOldConfig{'PO_SERVER'} && $main::imscpOldConfig{'PO_SERVER'} eq 'courier') {
		$rs = $self->_migrateFromCourier();
		return $rs if $rs;
	}

	$self->_oldEngineCompatibility();
}

=back

=head1 EVENT LISTENERS

=over 4

=item buildPostfixConf($fileContent, $fileName)

 Add Dovecot SASL and LDA parameters for Postfix

 Listener which listen on the following events:
  - beforeMtaBuildMainCfFile
  - beforeMtaBuildMasterCfFile

 This listener is reponsible to add Dovecot SASL and LDA parameters in Postfix configuration files.

 Param string \$fileContent Configuration file content
 Param string $fileName Configuration file name
 Return int 0 on success, other on failure

=cut

sub buildPostfixConf
{
	my ($self, $fileContent, $fileName) = @_;

	if($fileName eq 'main.cf') {
		$$fileContent .= <<EOF

virtual_transport = dovecot
dovecot_destination_recipient_limit = 1
EOF

	} elsif($fileName eq 'master.cf') {
		my $configSnippet = <<EOF;

dovecot   unix  -       n       n       -       -       pipe
  flags=DRhu user={MTA_MAILBOX_UID_NAME}:{MTA_MAILBOX_GID_NAME} argv={DOVECOT_DELIVER_PATH} -f \${sender} -d \${recipient} {SFLAG}
EOF

		$$fileContent .= iMSCP::TemplateParser::process(
			{
				MTA_MAILBOX_UID_NAME => $self->{'mta'}->{'config'}-> {'MTA_MAILBOX_UID_NAME'},
				MTA_MAILBOX_GID_NAME => $self->{'mta'}->{'config'}-> {'MTA_MAILBOX_GID_NAME'},
				DOVECOT_DELIVER_PATH => $self->{'config'}->{'DOVECOT_DELIVER_PATH'},
				SFLAG => (version->parse($self->{'version'}) < version->parse('2.0.0') ? '-s' : '')
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

 Initialize instance

 Return Servers::po::dovecot::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();

	$self->{'po'} = Servers::po::dovecot->getInstance();
	$self->{'mta'} = Servers::mta::postfix->getInstance();

	$self->{'eventManager'}->trigger(
		'beforePodInitInstaller', $self, 'dovecot'
	) and fatal('dovecot - beforePoInitInstaller has failed');

	$self->{'cfgDir'} = $self->{'po'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'config'} = $self->{'po'}->{'config'};

	my $oldConf = "$self->{'cfgDir'}/dovecot.old.data";
	if(-f $oldConf) {
		tie my %oldConfig, 'iMSCP::Config', fileName => $oldConf;

		for my $param(keys %oldConfig) {
			if(exists $self->{'config'}->{$param}) {
				$self->{'config'}->{$param} = $oldConfig{$param};
			}
		}
	}

	$self->_getVersion() and fatal('Unable to get Dovecot version');

	$self->{'eventManager'}->trigger(
		'afterPodInitInstaller', $self, 'dovecot'
	) and fatal('dovecot - afterPoInitInstaller has failed');

	$self;
}

=item _getVersion()

 Get Dovecot version

 Return int 0 on success, other on failure

=cut

sub _getVersion
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoGetVersion');
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

	$self->{'eventManager'}->trigger('afterPoGetVersion');
}

=item _bkpConfFile($cfgFile)

 Backup the given file

 Param string $cfgFile Configuration file name
 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
	my ($self, $cfgFile) = @_;

	my $rs = $self->{'eventManager'}->trigger('beforePoBkpConfFile', $cfgFile);
	return $rs if $rs;

	if(-f "$self->{'config'}->{'DOVECOT_CONF_DIR'}/$cfgFile") {
		my $file = iMSCP::File->new( filename => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/$cfgFile" );

		unless(-f "$self->{'bkpDir'}/$cfgFile.system") {
			$rs = $file->copyFile("$self->{'bkpDir'}/$cfgFile.system");
			return $rs if $rs;
		} else {
			my $timestamp = time;
			$rs = $file->copyFile("$self->{'bkpDir'}/$cfgFile.$timestamp");
			return $rs if $rs;
		}
	}

	$self->{'eventManager'}->trigger('afterPoBkpConfFile', $cfgFile);
}

=item _setupSqlUser()

 Setup SQL user

 Return int 0 on success, other on failure

=cut

sub _setupSqlUser
{
	my $self = shift;

	my $dbName = main::setupGetQuestion('DATABASE_NAME');
	my $dbUser = main::setupGetQuestion('DOVECOT_SQL_USER');
	my $dbUserHost = main::setupGetQuestion('DATABASE_USER_HOST');
	my $dbPass = main::setupGetQuestion('DOVECOT_SQL_PASSWORD');
	my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

	my $rs = $self->{'eventManager'}->trigger('beforePoSetupDb', $dbUser, $dbOldUser, $dbPass, $dbUserHost);
	return $rs if $rs;

	for my $sqlUser ($dbOldUser, $dbUser) {
		next unless $sqlUser || $sqlUser ~~ @main::sqlUsers;

		for my $host($dbUserHost, $main::imscpOldConfig{'DATABASE_HOST'}, $main::imscpOldConfig{'BASE_SERVER_IP'}) {
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

	# Give needed privileges to this SQL user

	my $quotedDbName = $db->quoteIdentifier($dbName);

	$rs = $db->doQuery('g', "GRANT SELECT ON $quotedDbName.mail_users TO ?@?", $dbUser, $dbUserHost);
	unless(ref $rs eq 'HASH') {
		error(sprintf('Unable to add SQL privilege: %s', $rs));
		return 1;
	}

	$self->{'config'}->{'DATABASE_USER'} = $dbUser;
	$self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;

	$self->{'eventManager'}->trigger('afterPoSetupDb');
}

=item _buildConf()

 Build dovecot configuration files

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
	my $self = shift;

	(my $dbName = main::setupGetQuestion('DATABASE_NAME')) =~ s%('|"|\\)%\\$1%g;
	(my $dbUser = $self->{'config'}->{'DATABASE_USER'}) =~ s%('|"|\\)%\\$1%g;
	(my $dbPass = $self->{'config'}->{'DATABASE_PASSWORD'}) =~ s%('|"|\\)%\\$1%g;

	my $data = {
		DATABASE_TYPE => $main::imscpConfig{'DATABASE_TYPE'},
		DATABASE_HOST => $main::imscpConfig{'DATABASE_HOST'},
		DATABASE_PORT => $main::imscpConfig{'DATABASE_PORT'},
		DATABASE_NAME => $dbName,
		DATABASE_USER => $dbUser,
		DATABASE_PASSWORD => $dbPass,
		CONF_DIR => $main::imscpConfig{'CONF_DIR'},
		HOSTNAME => $main::imscpConfig{'SERVER_HOSTNAME'},
		DOVECOT_SSL => ($main::imscpConfig{'SERVICES_SSL_ENABLED'} eq 'yes') ? 'yes' : 'no',
		COMMENT_SSL => ($main::imscpConfig{'SERVICES_SSL_ENABLED'} eq 'yes') ? '' : '#',
		CERTIFICATE => 'imscp_services',
		IMSCP_GROUP => $main::imscpConfig{'IMSCP_GROUP'},
		MTA_VIRTUAL_MAIL_DIR => $self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'},
		MTA_MAILBOX_UID_NAME => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'},
		MTA_MAILBOX_GID_NAME => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
		MTA_MAILBOX_UID => scalar getpwnam($self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'}),
		MTA_MAILBOX_GID => scalar getgrnam($self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}),
		POSTFIX_SENDMAIL_PATH => $self->{'mta'}->{'config'}->{'POSTFIX_SENDMAIL_PATH'},
		DOVECOT_CONF_DIR => $self->{'config'}->{'DOVECOT_CONF_DIR'},
		DOVECOT_DELIVER_PATH => $self->{'config'}->{'DOVECOT_DELIVER_PATH'},
		DOVECOT_AUTH_SOCKET_PATH => $self->{'config'}->{'DOVECOT_AUTH_SOCKET_PATH'},
		ENGINE_ROOT_DIR => $main::imscpConfig{'ENGINE_ROOT_DIR'}
	};

	# Transitional code (should be removed in later version
	if(-f "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-dict-sql.conf") {
		iMSCP::File->new( filename => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-dict-sql.conf")->delFile();
	}

	my %cfgFiles = (
		(
			(version->parse($self->{'version'}) < version->parse('2.0.0'))
				? 'dovecot.conf.1.x'
				: (version->parse($self->{'version'}) < version->parse('2.1.0'))
					? 'dovecot.conf.2.0' : 'dovecot.conf.2.1'
		) => [
			"$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot.conf", # Destpath
			$main::imscpConfig{'ROOT_USER'}, # Owner
			$self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # Group
			0640 # Permissions
		],
		'dovecot-sql.conf' => [
		"$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf", # Destpath
			$main::imscpConfig{'ROOT_USER'}, # owner
			$self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # Group
			0640 # Permissions
		],
		((version->parse($self->{'version'}) < version->parse('2.0.0')) ? 'quota-warning.1' : 'quota-warning.2') => [
			"$main::imscpConfig{'ENGINE_ROOT_DIR'}/quota/imscp-dovecot-quota.sh", # Destpath
			$self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'}, # Owner
			$self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}, # Group
			0750 # Permissions
		]
	);

	for my $conffile(keys %cfgFiles) {
		my $cfgTpl;
		my $rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'dovecot', $conffile, \$cfgTpl, $data);
		return $rs if $rs;

		unless(defined $cfgTpl) {
			$cfgTpl= iMSCP::File->new( filename => "$self->{'cfgDir'}/$conffile" )->get();
			unless(defined $cfgTpl) {
				error("Unable to read $self->{'cfgDir'}/$conffile");
				return 1;
			}
		}

		$rs = $self->{'eventManager'}->trigger('beforePoBuildConf', \$cfgTpl, $conffile);
		return $rs if $rs;

		$cfgTpl = process($data, $cfgTpl);

		$rs = $self->{'eventManager'}->trigger('afterPoBuildConf', \$cfgTpl, $conffile);
		return $rs if $rs;

		my $filename = fileparse($cfgFiles{$conffile}->[0]);

		my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$filename" );

		$rs = $file->set($cfgTpl);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->owner($cfgFiles{$conffile}->[1], $cfgFiles{$conffile}->[2]);
		return $rs if $rs;

		$rs = $file->mode($cfgFiles{$conffile}->[3]);
		return $rs if $rs;

		$rs = $file->copyFile($cfgFiles{$conffile}->[0]);
		return $rs if $rs;
	}

	0;
}

=item _saveConf()

 Save configuration file

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = shift;

	iMSCP::File->new( filename => "$self->{'cfgDir'}/dovecot.data" )->copyFile("$self->{'cfgDir'}/dovecot.old.data");
}

=item _migrateFromCourier()

 Migrate mailboxes from Courier

 Return int 0 on success, other on failure

=cut

sub _migrateFromCourier
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoMigrateFromCourier');
	return $rs if $rs;

	my $mailPath = "$self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}";

	my @cmd = (
		'perl', "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlVendor/courier-dovecot-migrate.pl", '--to-dovecot',
		'--convert', '--overwrite', '--recursive', $mailPath
	);

	my ($stdout, $stderr);
	$rs = execute("@cmd", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	error('Error while converting mailboxes to devecot format') if ! $stderr && $rs;
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterPoMigrateFromCourier');
}

=item _oldEngineCompatibility()

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoOldEngineCompatibility');
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterPodOldEngineCompatibility');
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
