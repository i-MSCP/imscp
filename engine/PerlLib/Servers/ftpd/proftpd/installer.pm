#!/usr/bin/perl

=head1 NAME

 Servers::ftpd::proftpd::installer - i-MSCP Proftpd Server implementation

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
#
# @category    i-MSCP
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::ftpd::proftpd::installer;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::TemplateParser;
use iMSCP::EventManager;
use File::Basename;
use Servers::ftpd::proftpd;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Poftpd Server implementation.

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

	 $eventManager->register('beforeSetupDialog', sub {push@{$_[0]}, sub { $self->askProftpd(@_) }; 0; });
}

=item askProftpd(\%dialog)

 Setup questions

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub askProftpd
{
	my ($self, $dialog) = @_;

	my $dbUser = main::setupGetQuestion('FTPD_SQL_USER') || $self->{'config'}->{'DATABASE_USER'} || 'vftp_user';
	my $dbPass = main::setupGetQuestion('FTPD_SQL_PASSWORD') || $self->{'config'}->{'DATABASE_PASSWORD'};

	my ($rs, $msg) = (0, '');

	if(
		$main::reconfigure ~~ ['ftpd', 'servers', 'all', 'forced'] ||
		(length $dbUser < 6 || length $dbUser > 16 || $dbUser !~ /^[\x21-\x5b\x5d-\x7e]+$/) ||
		(length $dbPass < 6 || $dbPass !~ /^[\x21-\x5b\x5d-\x7e]+$/)
	) {
		# Ask for the proftpd restricted SQL username
		do{
			($rs, $dbUser) = $dialog->inputbox(
				"\nPlease enter an username for the ProFTPD SQL user:$msg", $dbUser
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
				# Ask for the proftpd restricted SQL user password
				($rs, $dbPass) = $dialog->passwordbox(
					"\nPlease, enter a password for the restricted proftpd SQL user (blank for autogenerate):$msg", $dbPass
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

				$dialog->msgbox("\nPassword for the restricted proftpd SQL user set to: $dbPass");
			}
		}
	}

	if($rs != 30) {
		main::setupSetQuestion('FTPD_SQL_USER', $dbUser);
		main::setupSetQuestion('FTPD_SQL_PASSWORD', $dbPass);
	}

	$rs;
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdInstall', 'proftpd');
	return $rs if $rs;

	$rs = $self->_bkpConfFile($self->{'config'}->{'FTPD_CONF_FILE'});
	return $rs if $rs;

	$rs = $self->_setVersion();
	return $rs if $rs;

	$rs = $self->_setupDatabase();
	return $rs if $rs;

	$rs = $self->_buildConfigFile();
	return $rs if $rs;

	$rs = $self->_createTrafficLogFile();
	return $rs if $rs;

	$rs = $self->_saveConf();
	return $rs if $rs;

	$rs = $self->_oldEngineCompatibility();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterFtpdInstall', 'proftpd');
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::ftpd::proftpd::installer

=cut

sub _init
{
	my $self = $_[0];

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();

	$self->{'ftpd'} = Servers::ftpd::proftpd->getInstance();

	$self->{'eventManager'}->trigger(
		'beforeFtpdInitInstaller', $self, 'proftpd'
	) and fatal('proftpd - beforeFtpdInitInstaller has failed');

	$self->{'cfgDir'} = $self->{'ftpd'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'config'} = $self->{'ftpd'}->{'config'};

	# Merge old config file with new config file
	my $oldConf = "$self->{'cfgDir'}/proftpd.old.data";
	if(-f $oldConf) {
		tie my %oldConfig, 'iMSCP::Config', 'fileName' => $oldConf;

		for(keys %oldConfig) {
			if(exists $self->{'config'}->{$_}) {
				$self->{'config'}->{$_} = $oldConfig{$_};
			}
		}
	}

	$self->{'eventManager'}->trigger(
		'afterFtpdInitInstaller', $self, 'proftpd'
	) and fatal('proftpd - afterFtpdInitInstaller has failed');

	$self;
}

=item _bkpConfFile()

 Backup file

 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
	my ($self, $cfgFile) = @_;

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdBkpConfFile', $cfgFile);
	return $rs if $rs;

	if(-f $cfgFile){
		my $file = iMSCP::File->new('filename' => $cfgFile );
		my ($filename, $directories, $suffix) = fileparse($cfgFile);

		if(! -f "$self->{'bkpDir'}/$filename$suffix.system") {
			$rs = $file->copyFile("$self->{'bkpDir'}/$filename$suffix.system");
			return $rs if $rs;
		} else {
			$rs = $file->copyFile("$self->{'bkpDir'}/$filename$suffix." . time);
			return $rs if $rs;
		}
	}

	$self->{'eventManager'}->trigger('afterFtpdBkpConfFile', $cfgFile);
}

=item _setVersion

 Set version

 Return in 0 on success, other on failure

=cut

sub _setVersion
{
	my $self = $_[0];

	my ($stdout, $stderr);
	my $rs = execute("proftpd -v", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to find ProFTPD version') if $rs && ! $stderr;
	return $rs if $rs;

	if($stdout =~ m%([\d.]+)%) {
		$self->{'config'}->{'PROFTPD_VERSION'} = $1;
		debug("ProFTPD version set to: $1");
	} else {
		error('Unable to parse ProFTPD version from ProFTPD version string');
		return 1;
	}

	0;
}

=item _setupDatabase()

 Setup database

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
	my $self = $_[0];

	my $dbUser = main::setupGetQuestion('FTPD_SQL_USER');
	my $dbUserHost = main::setupGetQuestion('DATABASE_USER_HOST');
	my $dbPass = main::setupGetQuestion('FTPD_SQL_PASSWORD');

	my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdSetupDb', $dbUser, $dbPass);
	return $rs if $rs;

	# Removing any old SQL user (including privileges)
	for my $sqlUser ($dbOldUser, $dbUser) {
		next unless $sqlUser;

		for my $host(
			$dbUserHost, $main::imscpOldConfig{'DATABASE_USER_HOST'}, $main::imscpOldConfig{'DATABASE_HOST'},
			$main::imscpOldConfig{'BASE_SERVER_IP'}
		) {
			next unless $host;

			if(main::setupDeleteSqlUser($sqlUser, $host)) {
				error('Unable to remove SQL user or one of its privileges');
				return 1;
			}
		}
	}

	# Getting SQL connection with full privileges
	my ($db, $errStr) = main::setupGetSqlConnect();
	fatal("Unable to connect to SQL server: $errStr") unless $db;

	# Adding new SQL user with needed privileges
	for('ftp_users', 'ftp_group') {
		$rs = $db->doQuery(
			'dummy',
			"GRANT SELECT ON `$main::imscpConfig{'DATABASE_NAME'}`.`$_` TO ?@? IDENTIFIED BY ?",
			$dbUser,
			$dbUserHost,
			$dbPass
		);
		unless(ref $rs eq 'HASH') {
			error("Unable to add privileges: $rs");
			return 1;
		}
	}

	for( 'quotalimits', 'quotatallies') {
		$rs = $db->doQuery(
			'dummy',
			"GRANT SELECT, INSERT, UPDATE ON `$main::imscpConfig{'DATABASE_NAME'}`.`$_` TO ?@? IDENTIFIED BY ?",
			$dbUser,
			$dbUserHost,
			$dbPass
		);
		unless(ref $rs eq 'HASH') {
			error("Unable to add privileges: $rs");
			return 1;
		}
	}

	# Store database user and password in config file
	$self->{'config'}->{'DATABASE_USER'} = $dbUser;
	$self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;

	$self->{'eventManager'}->trigger('afterFtpSetupDb', $dbUser, $dbPass);
}

=item _buildConfigFile()

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfigFile
{
	my $self = $_[0];

	# Define data

	my $data = {
		HOSTNAME => $main::imscpConfig{'SERVER_HOSTNAME'},
		DATABASE_NAME => $main::imscpConfig{'DATABASE_NAME'},
		DATABASE_HOST => $main::imscpConfig{'DATABASE_HOST'},
		DATABASE_PORT => $main::imscpConfig{'DATABASE_PORT'},
		DATABASE_USER => $self->{'config'}->{'DATABASE_USER'},
		DATABASE_PASS => $self->{'config'}->{'DATABASE_PASSWORD'},
		FTPD_MIN_UID => $self->{'config'}->{'MIN_UID'},
		FTPD_MIN_GID => $self->{'config'}->{'MIN_GID'},
		CONF_DIR => $main::imscpConfig{'CONF_DIR'},
		SSL => (main::setupGetQuestion('SERVICES_SSL_ENABLED') eq 'yes') ? '' : '#',
		CERTIFICATE => 'imscp_services',
		TLSOPTIONS => (qv("v$self->{'config'}->{'PROFTPD_VERSION'}") >= qv('v1.3.3'))
			? 'NoCertRequest NoSessionReuseRequired' : 'NoCertRequest'
	};

	# Load template

	my $cfgTpl;
	my $rs = $self->{'eventManager'}->trigger('onLoadTemplate', 'proftpd', 'proftpd.conf', \$cfgTpl, $data);
	return $rs if $rs;

	unless(defined $cfgTpl) {
		$cfgTpl = iMSCP::File->new('filename' => "$self->{'cfgDir'}/proftpd.conf")->get();
		unless(defined $cfgTpl) {
			error("Unable to read $self->{'cfgDir'}/proftpd.conf");
			return 1;
		}
	}

	# Build file

	$rs = $self->{'eventManager'}->trigger('beforeFtpdBuildConf', \$cfgTpl, 'proftpd.conf');
	return $rs if $rs;

	$cfgTpl = process($data, $cfgTpl);

	$rs = $self->{'eventManager'}->trigger('afterFtpdBuildConf', \$cfgTpl, 'proftpd.conf');
	return $rs if $rs;

	# Store file

	my $file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/proftpd.conf");

	$rs = $file->set($cfgTpl);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$file->copyFile($self->{'config'}->{'FTPD_CONF_FILE'});
}

=item _createTrafficLogFile()

 Create traffic log file

 Return int 0 on success, other on failure

=cut

sub _createTrafficLogFile
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeFtpdCreateTrafficLogFile');
	return $rs if $rs;

	# Creating proftpd traffic log directory if it doesn't already exists
	if (! -d "$main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd") {
		$rs = iMSCP::Dir->new(
			'dirname' => "$main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd"
		)->make(
			{ 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $main::imscpConfig{'ROOT_GROUP'}, 'mode' => 0755 }
		);
		return $rs if $rs;
	}

	if(! -f "$main::imscpConfig{'TRAFF_LOG_DIR'}/$self->{'config'}->{'FTP_TRAFF_LOG_PATH'}") {
		my $file = iMSCP::File->new(
			'filename' => "$main::imscpConfig{'TRAFF_LOG_DIR'}/$self->{'config'}->{'FTP_TRAFF_LOG_PATH'}"
		);

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;
	}

	$self->{'eventManager'}->trigger('afterFtpdCreateTrafficLogFile');
}

=item _saveConf()

 Save configuration

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = $_[0];

	iMSCP::File->new(
		'filename' => "$self->{'cfgDir'}/proftpd.data"
	)->copyFile(
		"$self->{'cfgDir'}/proftpd.old.data"
	);
}

=item _oldEngineCompatibility()

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforeNamedOldEngineCompatibility');
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterNameddOldEngineCompatibility');
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
