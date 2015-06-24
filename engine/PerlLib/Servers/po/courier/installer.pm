=head1 NAME

 Servers::po::courier::installer - i-MSCP Courier IMAP/POP3 Server installer implementation

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

package Servers::po::courier::installer;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Config;
use iMSCP::Rights;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use File::Basename;
use Servers::po::courier;
use Servers::mta::postfix;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Courier IMAP/POP3 Server installer implementation.

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
		my $rs = $eventManager->register(
			'beforeSetupDialog', sub { push @{$_[0]}, sub { $self->showDialog(@_) }; 0; }
		);
		return $rs if $rs;

		$rs = $eventManager->register('beforeMtaBuildMainCfFile', sub { $self->buildPostfixConf(@_); });
		return $rs if $rs;

		$eventManager->register('beforeMtaBuildMasterCfFile', sub { $self->buildPostfixConf(@_); });
	} else {
		$main::imscpConfig{'PO_SERVER'} = 'no';
		warning('i-MSCP Courier PO server require the Postfix MTA. Installation skipped...');

		0;
	}
}

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub showDialog
{
	my ($self, $dialog) = @_;

	my $dbUser = main::setupGetQuestion('AUTHDAEMON_SQL_USER') || $self->{'config'}->{'DATABASE_USER'} || 'authdaemon_user';
	my $dbPass = main::setupGetQuestion('AUTHDAEMON_SQL_PASSWORD') || $self->{'config'}->{'DATABASE_PASSWORD'};

	my ($rs, $msg) = (0, '');

	if(
		$main::reconfigure ~~ [ 'po', 'servers', 'all', 'forced' ] ||
		(length $dbUser < 6 || length $dbUser > 16 || $dbUser !~ /^[\x21-\x5b\x5d-\x7e]+$/) ||
		(length $dbPass < 6 || $dbPass !~ /^[\x21-\x5b\x5d-\x7e]+$/)
	) {
		# Ask for the authdaemon restricted SQL username
		do{
			($rs, $dbUser) = $dialog->inputbox(
				"\nPlease enter an username for the Courier Authdaemon SQL user:$msg", $dbUser
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
				# Ask for the authdaemon restricted SQL user password
				($rs, $dbPass) = $dialog->passwordbox(
					"\nPlease, enter a password for the restricted authdaemon SQL user (blank for autogenerate):$msg", $dbPass
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

				$dialog->msgbox("\nPassword for the restricted authdaemon SQL user set to: $dbPass");
			}
		}
	}

	if($rs != 30) {
		main::setupSetQuestion('AUTHDAEMON_SQL_USER', $dbUser);
		main::setupSetQuestion('AUTHDAEMON_SQL_PASSWORD', $dbPass);
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

	for my $file(
		"/etc/init.d/$self->{'config'}->{'AUTHDAEMON_SNAME'}",
		"$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authdaemonrc",
		"$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authmysqlrc",
		"$self->{'config'}->{'AUTHLIB_CONF_DIR'}/self->{'config'}->{'COURIER_IMAP_SSL'}",
		"$self->{'config'}->{'AUTHLIB_CONF_DIR'}/$self->{'config'}->{'COURIER_POP_SSL'}"
	) {
		my $rs = $self->_bkpConfFile($file);
		return $rs if $rs;
	}

	my $rs = $self->_setupSqlUser();
	return $rs if $rs;

	$rs = $self->_overrideAuthdaemonInitScript();
	return $rs if $rs;

	$rs = $self->_buildConf();
	return $rs if $rs;

	$rs = $self->_saveConf();
	return $rs if $rs;

	if(defined $main::imscpOldConfig{'PO_SERVER'} && $main::imscpOldConfig{'PO_SERVER'} eq 'dovecot') {
		$rs = $self->_migrateFromDovecot();
		return $rs if $rs;
	}

	$self->_oldEngineCompatibility();
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = shift;

	setRights(
		$self->{'config'}->{'AUTHLIB_SOCKET_DIR'}, {
		user => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'},
		group => $self->{'config'}->{'AUTHDAEMON_GROUP'},
		mode => '0750'
	});
}

=head1 EVENT LISTENERS

=over 4

=item buildPostfixConf(\$fileContent, $fileName)

 Add maildrop MDA in Postfix configuration files.

 Listener which listen on the following events:
  - beforeMtaBuildMainCfFile
  - beforeMtaBuildMasterCfFile

 This listener is reponsible to add the maildrop deliver in Postfix configuration files.

 Param string \$fileContent Configuration file content
 Param string $fileName Configuration filename
 Return int 0 on success, other on failure

=cut

sub buildPostfixConf
{
	my ($self, $fileContent, $fileName) = @_;

	if($fileName eq 'main.cf') {
		$$fileContent .= <<EOF

virtual_transport = maildrop
maildrop_destination_concurrency_limit = 2
maildrop_destination_recipient_limit = 1
EOF

	} elsif($fileName eq 'master.cf') {
		my $configSnippet = <<EOF;

maildrop  unix  -       n       n       -       -       pipe
 flags=DRhu user={MTA_MAILBOX_UID_NAME}:{MTA_MAILBOX_GID_NAME} argv=maildrop -w 90 -d \${user}@\${nexthop} \${extension} \${recipient}
 \${user} \${nexthop} \${sender}
EOF

		$$fileContent .= iMSCP::TemplateParser::process(
			{
				MTA_MAILBOX_UID_NAME => $self->{'mta'}->{'config'}-> {'MTA_MAILBOX_UID_NAME'},
				MTA_MAILBOX_GID_NAME => $self->{'mta'}->{'config'}-> {'MTA_MAILBOX_GID_NAME'}
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

 Return Servers::po::courier::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();
	$self->{'po'} = Servers::po::courier->getInstance();
	$self->{'mta'} = Servers::mta::postfix->getInstance();

	$self->{'eventManager'}->trigger(
		'beforePodInitInstaller', $self, 'courier'
	) and fatal('courier - beforePoInitInstaller has failed');

	$self->{'cfgDir'} = $self->{'po'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'config'}= $self->{'po'}->{'config'};

	my $oldConf = "$self->{'cfgDir'}/courier.old.data";
	if(-f $oldConf) {
		tie my %oldConfig, 'iMSCP::Config', fileName => $oldConf;

		for my $param(keys %oldConfig) {
			if(exists $self->{'config'}->{$param}) {
				$self->{'config'}->{$param} = $oldConfig{$param};
			}
		}
	}

	$self->{'eventManager'}->trigger(
		'afterPodInitInstaller', $self, 'courier'
	) and fatal('courier - afterPoInitInstaller has failed');

	$self;
}

=item _bkpConfFile($filePath)

 Backup the given file

 Param string $filePath File path
 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
	my ($self, $filePath) = @_;

	my $rs = $self->{'eventManager'}->trigger('beforePoBkpConfFile', $filePath);
	return $rs if $rs;

	if(-f $filePath) {
		my $fileName = fileparse($filePath);
		my $file = iMSCP::File->new( filename => $filePath );

		unless(-f "$self->{'bkpDir'}/$fileName.system") {
			$rs = $file->copyFile("$self->{'bkpDir'}/$fileName.system");
			return $rs if $rs;
		} else {
			my $timestamp = time;
			$rs = $file->copyFile("$self->{'bkpDir'}/$fileName.$timestamp");
			return $rs if $rs;
		}
	}

	$self->{'eventManager'}->trigger('afterPoBkpConfFile', $filePath);
}

=item _setupSqlUser()

 Setup SQL user

 Return int 0 on success, other on failure

=cut

sub _setupSqlUser
{
	my $self = shift;

	my $dbUser = main::setupGetQuestion('AUTHDAEMON_SQL_USER');
	my $dbUserHost = main::setupGetQuestion('DATABASE_USER_HOST');
	my $dbPass = main::setupGetQuestion('AUTHDAEMON_SQL_PASSWORD');

	my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

	my $rs = $self->{'eventManager'}->trigger('beforePoSetupDb', $dbUser, $dbOldUser, $dbPass, $dbUserHost);
	return $rs if $rs;

	for my $sqlUser ($dbOldUser, $dbUser) {
		next unless $sqlUser;

		for my $host($dbUserHost, $main::imscpOldConfig{'DATABASE_HOST'}, $main::imscpOldConfig{'BASE_SERVER_IP'}) {
			next unless $host;

			if(main::setupDeleteSqlUser($sqlUser, $host)) {
				error("Unable to remove SQL user or one of its privileges");
				return 1;
			}
		}
	}

	my ($db, $errStr) = main::setupGetSqlConnect();
	fatal("Unable to connect to SQL server: $errStr") unless $db;

	$rs = $db->doQuery(
		'dummy',
		"GRANT SELECT ON `$main::imscpConfig{'DATABASE_NAME'}`.`mail_users` TO ?@? IDENTIFIED BY ?",
		$dbUser,
		$dbUserHost,
		$dbPass
	);
	unless(ref $rs eq 'HASH') {
		error("Unable to add privileges: $rs");
		return 1;
	}

	$self->{'config'}->{'DATABASE_USER'} = $dbUser;
	$self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;

	$self->{'eventManager'}->trigger('afterPoSetupDb');
}

=item _overrideAuthdaemonInitScript()

 Override courier-authdaemon init script

 Return int 0 on success, other on failure

=cut

sub _overrideAuthdaemonInitScript
{
	my $self = shift;

	my $file = iMSCP::File->new( filename => "/etc/init.d/$self->{'config'}->{'AUTHDAEMON_SNAME'}");

	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read the $file->{'filename'} file");
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

 Build courier configuration files

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
	my $self = shift;

	my $rs = $self->_buildAuthdaemonrcFile();
	return $rs if $rs;

	$rs = $self->_buildSslConfFiles();
	return $rs if $rs;

	my $data = {
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

	for my $conffile(keys %cfgFiles) {
		my $cfgTpl;
		$rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'courier', $conffile, \$cfgTpl, $data);
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

		$rs = $file->mode($cfgFiles{$conffile}->[3]);
		return $rs if $rs;

		$rs = $file->owner($cfgFiles{$conffile}->[1], $cfgFiles{$conffile}->[2]);
		return $rs if $rs;

		$rs = $file->copyFile($cfgFiles{$conffile}->[0]);
		return $rs if $rs;
	}

	if(-f "$self->{'cfgDir'}/imapd.local") {
		my $file = iMSCP::File->new( filename => "$self->{'config'}->{'COURIER_CONF_DIR'}/imapd" );
		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error("Unable to read $self->{'filename'}");
			return 1;
		}

		$fileContent = replaceBloc(
			"\n# Servers::po::courier::installer - BEGIN\n",
			"# Servers::po::courier::installer - ENDING\n",
			'',
			$fileContent
		);

		$fileContent .=
			"\n# Servers::po::courier::installer - BEGIN\n" .
			". $self->{'cfgDir'}/imapd.local\n" .
			"# Servers::po::courier::installer - ENDING\n";

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;
	}

	0;
}

=item _buildAuthdaemonrcFile()

 Build the authdaemonrc file

 Return int 0 on success, other on failure

=cut

sub _buildAuthdaemonrcFile
{
	my $self = shift;

	my $cfgTpl;
	my $rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'courier', 'authdaemonrc', \$cfgTpl, { });
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$cfgTpl = iMSCP::File->new( filename => "$self->{'bkpDir'}/authdaemonrc.system" )->get();
		unless (defined $cfgTpl) {
			error("Unable to read $self->{'bkpDir'}/authdaemonrc.system file");
			return 1;
		}
	}

	$rs = $self->{'eventManager'}->trigger('beforePoBuildAuthdaemonrcFile', \$cfgTpl, 'authdaemonrc');
	return $rs if $rs;

	$cfgTpl =~ s/authmodulelist=".*"/authmodulelist="authmysql authpam"/;

	$rs = $self->{'eventManager'}->trigger('afterPoBuildAuthdaemonrcFile', \$cfgTpl, 'authdaemonrc');
	return $rs if $rs;

	my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/authdaemonrc" );

	$rs = $file->set($cfgTpl);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0660);
	return $rs if $rs;

	$rs = $file->owner($self->{'config'}->{'AUTHDAEMON_USER'}, $self->{'config'}->{'AUTHDAEMON_GROUP'});
	return $rs if $rs;

	$file->copyFile("$self->{'config'}->{'AUTHLIB_CONF_DIR'}");
}

=item _buildSslConfFiles()

 Build ssl configuration file

 Return int 0 on success, other on failure

=cut

sub _buildSslConfFiles
{
	my $self = shift;

	if($main::imscpConfig{'SERVICES_SSL_ENABLED'} eq 'yes') {
		for my $conffile($self->{'config'}->{'COURIER_IMAP_SSL'}, $self->{'config'}->{'COURIER_POP_SSL'}) {
			my $cfgTpl;
			my $rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'courier', $conffile, \$cfgTpl, { });
			return $rs if $rs;

			unless(defined $cfgTpl) {
				$cfgTpl = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/$conffile" )->get();
				unless (defined $cfgTpl) {
					error("Unable to read $self->{'config'}->{'AUTHLIB_CONF_DIR'}/$conffile file");
					return 1;
				}
			}

			$rs = $self->{'eventManager'}->trigger('beforePoBuildSslConfFile', \$cfgTpl, $conffile);
			return $rs if $rs;

			if($cfgTpl =~ m/^TLS_CERTFILE=/msg) {
				$cfgTpl =~ s!^TLS_CERTFILE=.*$!TLS_CERTFILE=$main::imscpConfig{'CONF_DIR'}/imscp_services.pem!gm;
			} else {
				$cfgTpl .= "TLS_CERTFILE=$main::imscpConfig{'CONF_DIR'}/imscp_services.pem";
			}

			$rs = $self->{'eventManager'}->trigger('afterPoBuildSslConfFile', \$cfgTpl, $conffile);
			return $rs if $rs;

			my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/$conffile" );

			$rs = $file->set($cfgTpl);
			return $rs if $rs;

			$rs = $file->save();
			return $rs if $rs;

			$rs = $file->mode(0644);
			return $rs if $rs;

			$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
			return $rs if $rs;

			$rs = $file->copyFile("$self->{'config'}->{'AUTHLIB_CONF_DIR'}");
			return $rs if $rs;
		}
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

	iMSCP::File->new( filename => "$self->{'cfgDir'}/courier.data" )->copyFile("$self->{'cfgDir'}/courier.old.data");
}

=item _migrateFromDovecot()

 Migrate mailboxes from Dovecot

 Return int 0 on success, other on failure

=cut

sub _migrateFromDovecot
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoMigrateFromDovecot');
	return $rs if $rs;

	my $mailPath = "$self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}";

	my @cmd = (
		'perl', "$main::imscpConfig{'ENGINE_ROOT_DIR'}/PerlVendor/courier-dovecot-migrate.pl", '--to-courier',
		'--convert', '--overwrite', '--recursive', $mailPath
	);

	my ($stdout, $stderr);
	$rs = execute("@cmd", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	error('Error while converting mails') if ! $stderr && $rs;
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterPoMigrateFromDovecot');
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

	if(-f "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/userdb") {
		my $file = iMSCP::File->new( filename => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/userdb" );

		$rs = $file->set('');
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0600);
		return $rs if $rs;

		my ($stdout, $stderr);
		$rs = execute("makeuserdb -f $self->{'config'}->{'AUTHLIB_CONF_DIR'}/userdb", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	$self->{'eventManager'}->trigger('afterPodOldEngineCompatibility');
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
