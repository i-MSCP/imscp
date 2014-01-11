#!/usr/bin/perl

=head1 NAME

 Servers::ftpd::proftpd::installer - i-MSCP Proftpd Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
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
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
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
use iMSCP::HooksManager;
use File::Basename;
use Servers::ftpd::proftpd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Installer for the i-MSCP Poftpd Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks(\%$hooksManager)

 Register setup hook functions

 Param iMSCP::HooksManager $hooksManager Hooks manager instance
 Return int 0 on success, other on failure

=cut

sub registerSetupHooks
{
	my ($self, $hooksManager) = @_;

	my $rs = $hooksManager->trigger('beforeFtpdRegisterSetupHooks', $hooksManager, 'proftpd');
	return $rs if $rs;

	# Add proftpd installer dialog in setup dialog stack
	$rs = $hooksManager->register(
		'beforeSetupDialog', sub { my $dialogStack = shift; push(@$dialogStack, sub { $self->askProftpd(@_) }); 0; }
	);
	return $rs if $rs;

	$hooksManager->trigger('afterFtpdRegisterSetupHooks', $hooksManager, 'proftpd');
}

=item askProftpd(\%dialog)

 Setup questions

 Param iMSCP::Dialog::Dialog $dialog Dialog instance
 Return int 0 on success, other on failure

=cut

sub askProftpd
{
	my ($self, $dialog) = @_;

	my $dbType = main::setupGetQuestion('DATABASE_TYPE');
	my $dbHost = main::setupGetQuestion('DATABASE_HOST');
	my $dbPort = main::setupGetQuestion('DATABASE_PORT');
	my $dbName = main::setupGetQuestion('DATABASE_NAME');

	my $dbUser = main::setupGetQuestion('FTPD_SQL_USER') || $self->{'config'}->{'DATABASE_USER'} || 'vftp';
	my $dbPass = main::setupGetQuestion('FTPD_SQL_PASSWORD') || $self->{'config'}->{'DATABASE_PASSWORD'} || '';

	my ($rs, $msg) = (0, '');

	if($main::reconfigure ~~ ['ftpd', 'servers', 'all', 'forced'] || ! ($dbUser && $dbPass)) {
		# Ask for the proftpd restricted SQL username
		do{
			($rs, $dbUser) = iMSCP::Dialog->factory()->inputbox(
				"\nPlease enter an username for the restricted proftpd SQL user:$msg", $dbUser
			);

			if($dbUser eq $main::imscpConfig{'DATABASE_USER'}) {
				$msg = "\n\n\\Z1You cannot reuse the i-MSCP SQL user '$dbUser'.\\Zn\n\nPlease, try again:";
				$dbUser = '';
			} elsif(length $dbUser > 16) {
				$msg = "\n\n\\Z1MySQL username can be up to 16 characters long.\\Zn\n\nPlease, try again:";
				$dbUser = '';
			}
		} while ($rs != 30 && ! $dbUser);

		if($rs != 30) {
			# Ask for the proftpd restricted SQL user password
			($rs, $dbPass) = $dialog->passwordbox(
				'\nPlease, enter a password for the restricted proftpd SQL user (blank for autogenerate):', $dbPass
			);

			if($rs != 30) {
				if(! $dbPass) {
					my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_');

					$dbPass = '';
					$dbPass .= $allowedChars[rand @allowedChars] for 1..16;
				}

				$dbPass =~ s/('|"|`|#|;|\/|\s|\||<|\?|\\)/_/g;
				$dialog->msgbox("\nPassword for the restricted proftpd SQL user set to: $dbPass");
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

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeFtpdInstall', 'proftpd');
	return $rs if $rs;

	$rs = $self->_bkpConfFile($self->{'config'}->{'FTPD_CONF_FILE'});
	return $rs if $rs;

	$rs = $self->_setupDatabase();
	return $rs if $rs;

	$rs = $self->_buildConfigFile();
	return $rs if $rs;

	$rs = $self->_createTrafficLogFile();
	return $rs if $rs;

	$rs = $self->_saveConf();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterFtpdInstall', 'proftpd');
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance

 Return Servers::ftpd::proftpd::installer

=cut

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'ftpd'} = Servers::ftpd::proftpd->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforeFtpdInitInstaller', $self, 'proftpd'
	) and fatal('proftpd - beforeFtpdInitInstaller hook has failed');

	$self->{'cfgDir'} = $self->{'ftpd'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'config'} = $self->{'ftpd'}->{'config'};

	my $oldConf = "$self->{'cfgDir'}/proftpd.old.data";

	if(-f $oldConf) {
		tie %{$self->{'oldConfig'}}, 'iMSCP::Config', 'fileName' => $oldConf, 'noerrors' => 1;

		for(keys %{$self->{'oldConfig'}}) {
			if(exists $self->{'config'}->{$_}) {
				$self->{'config'}->{$_} = $self->{'oldConfig'}->{$_};
			}
		}
	}

	$self->{'hooksManager'}->trigger(
		'afterFtpdInitInstaller', $self, 'proftpd'
	) and fatal('proftpd - afterFtpdInitInstaller hook has failed');

	$self;
}

=item _bkpConfFile()

 Backup file

 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
	my ($self, $cfgFile) = @_;

	my $rs = $self->{'hooksManager'}->trigger('beforeFtpdBkpConfFile', $cfgFile);
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

	$self->{'hooksManager'}->trigger('afterFtpdBkpConfFile', $cfgFile);
}

=item _setupDatabase()

 Setup database

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
	my $self = shift;

	my $dbUser = $self->{'config'}->{'DATABASE_USER'};
	my $dbUserHost = main::setupGetQuestion('DATABASE_USER_HOST');
	my $dbPass = $self->{'config'}->{'DATABASE_PASSWORD'};

	my $dbOldUser = $self->{'oldConfig'}->{'DATABASE_USER'} || '';

	my $rs = $self->{'hooksManager'}->trigger('beforeFtpdSetupDb', $dbUser, $dbPass);
	return $rs if $rs;

	# Remove any old proftpd SQL user (including privileges)
	for my $sqlUser ($dbOldUser, $dbUser) {
		next if ! $sqlUser;

		for($dbUserHost, $main::imscpOldConfig{'DATABASE_HOST'}, $main::imscpOldConfig{'BASE_SERVER_IP'}) {
			next if ! $_;

			$rs = main::setupDeleteSqlUser($sqlUser, $_);
			error("Unable to remove $sqlUser\@$_' SQL user or one of its privileges") if $rs;
			return 1 if $rs;
		}
	}

	# Get SQL connection with full privileges
	my $database = main::setupGetSqlConnect();

	# Add new proftpd restricted SQL user with needed privileges
	for('ftp_users', 'ftp_group') {
		$rs = $database->doQuery(
			'dummy',
			"GRANT SELECT ON `$main::imscpConfig{'DATABASE_NAME'}`.`$_` TO ?@? IDENTIFIED BY ?",
			$dbUser,
			$dbUserHost,
			$dbPass
		);
		unless(ref $rs eq 'HASH') {
			error(
				"Unable to add privileges on the '$main::imscpConfig{'DATABASE_NAME'}.$_' table for the Proftpd " .
				"'$dbUser\@$dbUserHost' SQL user: $rs"
			);
			return 1;
		}
	}

	for( 'quotalimits', 'quotatallies') {
		$rs = $database->doQuery(
			'dummy',
			"GRANT SELECT, INSERT, UPDATE ON `$main::imscpConfig{'DATABASE_NAME'}`.`$_` TO ?@? IDENTIFIED BY ?",
			$dbUser,
			$dbUserHost,
			$dbPass
		);
		unless(ref $rs eq 'HASH') {
			error(
				"Unable to add privileges on the '$main::imscpConfig{'DATABASE_NAME'}.$_' table for the Proftpd " .
				"'$dbUser\@$dbUserHost' SQL user: $rs"
			);
			return 1;
		}
	}

	$self->{'hooksManager'}->trigger('afterFtpSetupDb', $dbUser, $dbPass);
}

=item _buildConfigFile()

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfigFile
{
	my $self = shift;

	my $cfg = {
		HOST_NAME => $main::imscpConfig{'SERVER_HOSTNAME'},
		DATABASE_NAME => $main::imscpConfig{'DATABASE_NAME'},
		DATABASE_HOST => $main::imscpConfig{'DATABASE_HOST'},
		DATABASE_PORT => $main::imscpConfig{'DATABASE_PORT'},
		DATABASE_USER => $self->{'config'}->{'DATABASE_USER'},
		DATABASE_PASS => $self->{'config'}->{'DATABASE_PASSWORD'},
		FTPD_MIN_UID => $self->{'config'}->{'MIN_UID'},
		FTPD_MIN_GID => $self->{'config'}->{'MIN_GID'},
		GUI_CERT_DIR => $main::imscpConfig{'GUI_CERT_DIR'},
		SSL => main::setupGetQuestion('SSL_ENABLED') eq 'yes' ? '' : '#'
	};

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/proftpd.conf");
	my $cfgTpl = $file->get();
	unless(defined $cfgTpl) {
		error("Unable to read $file->{'filename'}");
		return 1;
	}

	my $rs = $self->{'hooksManager'}->trigger('beforeFtpdBuildConf', \$cfgTpl, 'proftpd.conf');
	return $rs if $rs;

	$cfgTpl = process($cfg, $cfgTpl);
	return 1 unless defined $cfgTpl;

	$rs = $self->{'hooksManager'}->trigger('afterFtpdBuildConf', \$cfgTpl, 'proftpd.conf');
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$self->{'wrkDir'}/proftpd.conf");

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
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeFtpdCreateTrafficLogFile');
	return $rs if $rs;

	# Creating proftpd traffic log directory if it doesn't already exists
	if (! -d "$main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd") {
		debug("Creating $main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd directory");

		$rs = iMSCP::Dir->new(
			'dirname' => "$main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd"
		)->make(
			{ 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $main::imscpConfig{'ROOT_GROUP'}, 'mode' => 0755 }
		);
		return $rs if $rs;
	}

	if(! -f "$main::imscpConfig{'TRAFF_LOG_DIR'}$self->{'config'}->{'FTP_TRAFF_LOG'}") {
		my $file = iMSCP::File->new(
			'filename' => "$main::imscpConfig{'TRAFF_LOG_DIR'}$self->{'config'}->{'FTP_TRAFF_LOG'}"
		);

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterFtpdCreateTrafficLogFile');
}

=item _saveConf()

 Save configuration

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
	my $self = shift;

	my $rootUname = $main::imscpConfig{'ROOT_USER'};
	my $rootGname = $main::imscpConfig{'ROOT_GROUP'};

	my $file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/proftpd.data");

	my $rs = $file->owner($rootUname, $rootGname);
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	my $cfg = $file->get();
	unless(defined $cfg) {
		error("Unable to read $self->{'cfgDir'}/proftpd.data");
		return 1;
	}

	$rs = $self->{'hooksManager'}->trigger('beforeFtpdSaveConf', \$cfg, 'proftpd.old.data');
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$self->{'cfgDir'}/proftpd.old.data");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->owner($rootUname, $rootGname);
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterFtpdSaveConf', 'proftpd.old.data');
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
