#!/usr/bin/perl

=head1 NAME

 Servers::sqld::mysql::installer - i-MSCP MySQL server installer implementation

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
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::sqld::mysql::installer;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use iMSCP::Rights;
use iMSCP::Crypt;
use File::HomeDir;
use Servers::sqld::mysql;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP MySQL server installer implementation.

=head1 PUBLIC METHODS

=over 4

=item install()

 Process install tasks

 Return in 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeSqldInstall', 'mysql');
	return $rs if $rs;

	$rs = $self->_createGlobalConfFile();
	return $rs if $rs;

	$rs = $self->_createRootUserConfFile();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterSqldInstall', 'mysql');
}

=item setEnginePermissions()

 Set engine permissions

 Return in 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeSqldSetEnginePermissions');
	return $rs if $rs;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $homeDir = File::HomeDir->users_home($rootUName);
	my $confDir = '/etc/mysql/conf.d';

	if(defined $homeDir) {
		if(-f "$homeDir/.my.cnf") {
			$rs = setRights("$homeDir/.my.cnf", { 'user' => $rootUName, 'group' => $rootGName, 'mode' => '0600' });
			return $rs if $rs;
		}
	} else {
		error('Unable to find root user homedir');
		return 1;
	}

	if(-f "$confDir/imscp.cnf") {
		$rs = setRights("$confDir/imscp.cnf", { 'user' => $rootUName, 'group' => $rootGName, 'mode' => '0644' });
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterSqldSetEnginePermissions');
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance of this class.

 Return Servers::sqld::mysql:installer

=cut

sub _init
{
	my $self = $_[0];

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'sqld'} = Servers::sqld::mysql->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforeSqldInitInstaller', $self, 'mysql'
	) and fatal('postfix - beforeSqldInitInstaller hook has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/mysql";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'hooksManager'}->trigger(
		'afterSqldInitInstaller', $self, 'mysql'
	) and fatal('postfix - afterSqldInitInstaller hook has failed');

	$self;
}

=item _createGlobalConfFile()

 Create global configuration file

 Return in 0 on success, other on failure

=cut

sub _createGlobalConfFile
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeMysqlCreateGlobalConfFile');
	return $rs if $rs;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $confDir = '/etc/mysql/conf.d';

	if(-d $confDir) {
		# Load template
		my $cfgTpl;
		$rs = $self->{'hooksManager'}->trigger(
			'onLoadTemplate',
			'mysql',
			'imscp.conf',
			\$cfgTpl,
			{ 'USER' => $rootUName, 'GROUP' => $rootGName, 'CONFDIR' => $confDir }
		);
		return $rs if $rs;

		unless(defined $cfgTpl) {
			$cfgTpl = iMSCP::File->new('filename' => "$self->{'cfgDir'}/imscp.cnf")->get();
			unless(defined $cfgTpl) {
				error("Unable to read $self->{'cfgDir'}/imscp.cnf");
				return 1;
			}
		}

		# Build file

		$cfgTpl = process(
			{
				INNODB_USE_NATIVE_AIO => ($self->_isMysqldInsideCt()) ? 0 : 1
			},
			$cfgTpl
		);

		# Store file

		my $file = iMSCP::File->new('filename' => "$confDir/imscp.cnf");

		$rs = $file->set($cfgTpl);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterMysqlCreateGlobalConfFile');
}

=item _createRootUserConfFile()

 Create root user configuration file

 Return in 0 on success, other on failure

=cut

sub _createRootUserConfFile
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforeMysqlCreateRootUserConfFile');
	return $rs if $rs;

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $homeDir = File::HomeDir->users_home($rootUName);

	if(defined $homeDir) {
		# Load template
		my $cfgTpl;
		$rs = $self->{'hooksManager'}->trigger(
			'onLoadTemplate',
			'mysql',
			'.my.cnf',
			\$cfgTpl,
			{ 'USER' => $rootUName, 'GROUP' => $rootGName, 'HOMEDIR' => $homeDir }
		);
		return $rs if $rs;

		unless(defined $cfgTpl) {
			$cfgTpl = iMSCP::File->new('filename' => "$self->{'cfgDir'}/.my.cnf")->get();
			unless(defined $cfgTpl) {
				error("Unable to read $self->{'cfgDir'}/.my.cnf");
				return 1;
			}
		}

		# Build file

		$cfgTpl = process(
			{
				DATABASE_HOST => $main::imscpConfig{'DATABASE_HOST'},
				DATABASE_PORT => $main::imscpConfig{'DATABASE_PORT'},
				DATABASE_PASSWORD => escapeShell(
					iMSCP::Crypt->getInstance()->decrypt_db_password($main::imscpConfig{'DATABASE_PASSWORD'})
				),
				DATABASE_USER => $main::imscpConfig{'DATABASE_USER'},
			}
			,
			$cfgTpl
		);

		# Store file

		my $file = iMSCP::File->new('filename' => "$homeDir/.my.cnf");

		$rs = $file->set($cfgTpl);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0600);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;
	} else {
		error('Unable to find root user homedir');
		return 1;
	}

	$self->{'hooksManager'}->trigger('afterMysqlCreateRootUserConfFile');
}

=item _isMysqldInsideCt()

 Does the Mysql server is run inside an unprivileged VE (OpenVZ container)

 Return int 1 if the Mysql server is run inside an OpenVZ container, 0 otherwise

=cut

sub _isMysqldInsideCt
{
	my $rs = 0;

	if(-f '/proc/user_beancounters') {
		my ($stdout, $stderr);
		$rs = execute(
			"$main::imscpConfig{'CMD_CAT'} /proc/1/status | $main::imscpConfig{'CMD_GREP'} --color=never envID",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		warning($stderr) if $rs && $stderr;
		return $rs if $rs;

		if($stdout =~ /envID:\s+(\d+)/) {
			$rs = ($1 > 0) ? 1 : 0;
		}
	}

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
