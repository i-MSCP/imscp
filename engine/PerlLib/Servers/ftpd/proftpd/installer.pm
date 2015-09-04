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

package Servers::ftpd::proftpd::installer;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Crypt 'randomStr';
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::TemplateParser;
use File::Basename;
use Servers::ftpd;
use version;
use parent 'Common::SingletonClass';

%main::sqlUsers = () unless %main::sqlUsers;
@main::createdSqlUsers = () unless @main::createdSqlUsers;

=head1 DESCRIPTION

 Installer for the i-MSCP Poftpd Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners(\%eventManager)

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, die on failure

=cut

sub registerSetupListeners
{
	my ($self, $eventManager) = @_;

	$eventManager->register('beforeSetupDialog', sub { push @{$_[0]}, sub { $self->showDialog(@_) }; 0 });
}

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub showDialog
{
	my ($self, $dialog) = @_;

	my $dbUser = main::setupGetQuestion('FTPD_SQL_USER') || $self->{'config'}->{'DATABASE_USER'} || 'vftp_user';
	my $dbPass = main::setupGetQuestion('FTPD_SQL_PASSWORD') || $self->{'config'}->{'DATABASE_PASSWORD'};

	my ($rs, $msg) = (0, '');

	if(
		$main::reconfigure ~~ [ 'ftpd', 'servers', 'all', 'forced' ] ||
		(length $dbUser < 6 || length $dbUser > 16 || $dbUser !~ /^[\x21-\x7e]+$/) ||
		(length $dbPass < 6 || $dbPass !~ /^[\x21-\x7e]+$/)
	) {
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
			} elsif($dbUser !~ /^[\x21-\x7e]+$/) {
				$msg = "\n\n\\Z1Only printable ASCII characters (excepted space) are allowed.\\Zn\n\nPlease try again:";
				$dbUser = '';
			}
		} while ($rs != 30 && ! $dbUser);

		if($rs != 30) {
			$msg = '';

			# Ask for the proftpd SQL user password unless we reuses existent SQL user
			unless($dbUser ~~ [ keys %main::sqlUsers ]) {
				do {
					($rs, $dbPass) = $dialog->passwordbox(
						"\nPlease, enter a password for the ProFTPD SQL user (blank for autogenerate):$msg", $dbPass
					);

					if($dbPass ne '') {
						if(length $dbPass < 6) {
							$msg = "\n\n\\Z1Password must be at least 6 characters long.\\Zn\n\nPlease try again:";
							$dbPass = '';
						} elsif($dbPass !~ /^[\x21-\x7e]+$/) {
							$msg = "\n\n\\Z1Only printable ASCII characters (excepted space) are allowed.\\Zn\n\nPlease try again:";
							$dbPass = '';
						} else {
							$msg = '';
						}
					} else {
						$msg = '';
					}
				} while($rs != 30 && $msg);
			} else {
				$dbPass = $main::sqlUsers{$dbUser};
			}

			if($rs != 30) {
				$dbPass = randomStr(16) unless $dbPass;
				$dialog->msgbox("\nPassword for the ProFTPD SQL user set to: $dbPass");
			}
		}
	}

	if($rs != 30) {
		main::setupSetQuestion('FTPD_SQL_USER', $dbUser);
		main::setupSetQuestion('FTPD_SQL_PASSWORD', $dbPass);
		$main::sqlUsers{$dbUser} = $dbPass;
	}

	$rs;
}

=item install()

 Process install tasks

 Return int 0 on success, other or die on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->_bkpConfFile($self->{'config'}->{'FTPD_CONF_FILE'});
	return $rs if $rs;

	$rs = $self->_setVersion();
	return $rs if $rs;

	$rs = $self->_setupDatabase();
	return $rs if $rs;

	$rs = $self->_buildConfigFile();
	return $rs if $rs;

	$rs = $self->_createTrafficLogFile();
	return $rs if $rs;

	$self->_saveConf();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::ftpd::proftpd::installer, die on failure

=cut

sub _init
{
	my $self = shift;

	$self->{'ftpd'} = Servers::ftpd->factory();
	$self->{'eventManager'} = $self->{'ftpd'} ->{'eventManager'};
	$self->{'cfgDir'} = $self->{'ftpd'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'config'} = $self->{'ftpd'}->{'config'};

	my $oldConf = "$self->{'cfgDir'}/proftpd.old.data";
	if(-f $oldConf) {
		tie my %oldConfig, 'iMSCP::Config', fileName => $oldConf;
		for my $param(keys %oldConfig) {
			if(exists $self->{'config'}->{$param}) {
				$self->{'config'}->{$param} = $oldConfig{$param};
			}
		}
	}

	$self;
}

=item _bkpConfFile()

 Backup file

 Return int 0 on success, die on failure

=cut

sub _bkpConfFile
{
	my ($self, $cfgFile) = @_;

	$self->{'eventManager'}->trigger('beforeFtpdBkpConfFile', $cfgFile);

	if(-f $cfgFile){
		my $file = iMSCP::File->new( filename => $cfgFile );
		my $basename = basename($cfgFile);

		unless(-f "$self->{'bkpDir'}/$basename.system") {
			$file->copyFile("$self->{'bkpDir'}/$basename.system");
		} else {
			$file->copyFile("$self->{'bkpDir'}/$basename." . time);
		}
	}

	$self->{'eventManager'}->trigger('afterFtpdBkpConfFile', $cfgFile);
}

=item _setVersion

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
	my $self = shift;

	my $rs = execute('proftpd -v', \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to find ProFTPD version') if $rs && ! $stderr;
	return $rs if $rs;

	if($stdout =~ m%([\d.]+)%) {
		$self->{'config'}->{'PROFTPD_VERSION'} = $1;
		debug("ProFTPD version set to: $1");
	} else {
		error('Unable to parse ProFTPD version');
		return 1;
	}

	0;
}

=item _setupDatabase()

 Setup database

 Return int 0 on success, die on failure

=cut

sub _setupDatabase
{
	my $self = shift;

	my $dbName = main::setupGetQuestion('DATABASE_NAME');
	my $dbUser = main::setupGetQuestion('FTPD_SQL_USER');
	my $dbUserHost = main::setupGetQuestion('DATABASE_USER_HOST');
	my $dbPass = main::setupGetQuestion('FTPD_SQL_PASSWORD');
	my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

	$self->{'eventManager'}->trigger('beforeFtpdSetupDb', $dbUser, $dbPass);

	for my $sqlUser ($dbOldUser, $dbUser) {
		next if ! $sqlUser || "$sqlUser\@$dbUserHost" ~~ @main::createdSqlUsers;

		for my $host(
			$dbUserHost, $main::imscpOldConfig{'DATABASE_USER_HOST'}, $main::imscpOldConfig{'DATABASE_HOST'},
			$main::imscpOldConfig{'BASE_SERVER_IP'}
		) {
			next unless $host;

			if(main::setupDeleteSqlUser($sqlUser, $host)) {
				die(sprintf('Could not remove %s@%s SQL user or one of its privileges', $sqlUser, $host));
			}
		}
	}

	my ($db, $errStr) = main::setupGetSqlConnect();
	$db or die(sprintf('Could not connect to SQL server: %s', $errStr));

	# Create SQL user if not already created by another server/package installer
	unless("$dbUser\@$dbUserHost" ~~ @main::createdSqlUsers) {
		debug(sprintf('Creating %s@%s SQL user', $dbUser, $dbUserHost));
		my $rs = $db->doQuery('c', 'CREATE USER ?@? IDENTIFIED BY ?', $dbUser, $dbUserHost, $dbPass);
		ref $rs eq 'HASH' or die(sprintf('Could not create the %s@%s SQL user: %s', $dbUser, $dbUserHost, $rs));
		push @main::createdSqlUsers, "$dbUser\@$dbUserHost";
	}

	# Give needed privileges to this SQL user

	my $quotedDbName = $db->quoteIdentifier($dbName);

	for my $tableName('ftp_users', 'ftp_group') {
		my $quotedTableName = $db->quoteIdentifier($tableName);
		my $rs = $db->doQuery('g', "GRANT SELECT ON $quotedDbName.$quotedTableName TO ?@?", $dbUser, $dbUserHost);
		ref $rs eq 'HASH' or die(sprintf('Could not add SQL privileges: %s', $rs));
	}

	for my $tableName('quotalimits', 'quotatallies') {
		my $quotedTableName = $db->quoteIdentifier($tableName);
		my $rs = $db->doQuery(
			'g', "GRANT SELECT, INSERT, UPDATE ON $quotedDbName.$quotedTableName TO ?@?", $dbUser, $dbUserHost
		);
		ref $rs eq 'HASH' or die(sprintf('Could not to add SQL privileges: %s', $rs));
	}

	$self->{'config'}->{'DATABASE_USER'} = $dbUser;
	$self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
	$self->{'eventManager'}->trigger('afterFtpSetupDb', $dbUser, $dbPass);
}

=item _buildConfigFile()

 Build configuration file

 Return int 0 on success, die on failure

=cut

sub _buildConfigFile
{
	my $self = shift;

	my $version = $self->{'config'}->{'PROFTPD_VERSION'};

	# Escape any double-quotes and backslash in password ( see #IP-1330 )
	(my $dbUser = $self->{'config'}->{'DATABASE_USER'}) =~ s%("|\\)%\\$1%g;
	(my $dbPass = $self->{'config'}->{'DATABASE_PASSWORD'}) =~ s%("|\\)%\\$1%g;

	my $data = {
		HOSTNAME => $main::imscpConfig{'SERVER_HOSTNAME'},
		DATABASE_NAME => $main::imscpConfig{'DATABASE_NAME'},
		DATABASE_HOST => $main::imscpConfig{'DATABASE_HOST'},
		DATABASE_PORT => $main::imscpConfig{'DATABASE_PORT'},
		DATABASE_USER => '"' . $dbUser . '"',
		DATABASE_PASS => '"' . $dbPass . '"',
		FTPD_MIN_UID => $self->{'config'}->{'MIN_UID'},
		FTPD_MIN_GID => $self->{'config'}->{'MIN_GID'},
		CONF_DIR => $main::imscpConfig{'CONF_DIR'},
		SSL => (main::setupGetQuestion('SERVICES_SSL_ENABLED') eq 'yes') ? '' : '#',
		CERTIFICATE => 'imscp_services',
		TLSOPTIONS => (version->parse($version) >= version->parse('1.3.3'))
			? 'NoCertRequest NoSessionReuseRequired' : 'NoCertRequest'
	};

	$self->{'eventManager'}->trigger('onLoadTemplate', 'proftpd', 'proftpd.conf', \my $cfgTpl, $data);
	$cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/proftpd.conf" )->get() unless defined $cfgTpl;
	$self->{'eventManager'}->trigger('beforeFtpdBuildConf', \$cfgTpl, 'proftpd.conf');
	$cfgTpl = process($data, $cfgTpl);
	$self->{'eventManager'}->trigger('afterFtpdBuildConf', \$cfgTpl, 'proftpd.conf');

	my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/proftpd.conf" );
	$file->set($cfgTpl);
	$file->save();
	$file->mode(0640);
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	$file->copyFile($self->{'config'}->{'FTPD_CONF_FILE'});
}

=item _createTrafficLogFile()

 Create traffic log file

 Return int 0 on success, die on failure

=cut

sub _createTrafficLogFile
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeFtpdCreateTrafficLogFile');

	iMSCP::Dir->new( dirname => "$main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd" )->make({
		user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_GROUP'}, mode => 0755
	});

	unless(-f "$main::imscpConfig{'TRAFF_LOG_DIR'}/$self->{'config'}->{'FTP_TRAFF_LOG_PATH'}") {
		my $file = iMSCP::File->new(
			filename => "$main::imscpConfig{'TRAFF_LOG_DIR'}/$self->{'config'}->{'FTP_TRAFF_LOG_PATH'}"
		);
		$file->save();
		$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		$file->mode(0644);
	}

	$self->{'eventManager'}->trigger('afterFtpdCreateTrafficLogFile');
}

=item _saveConf()

 Save configuration file

 Return int 0 on success, die on failure

=cut

sub _saveConf
{
	my $self = shift;

	iMSCP::File->new( filename => "$self->{'cfgDir'}/proftpd.data" )->copyFile("$self->{'cfgDir'}/proftpd.old.data");
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
