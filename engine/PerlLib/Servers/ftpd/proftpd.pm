#!/usr/bin/perl

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

package Servers::ftpd::proftpd;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Execute;
use iMSCP::File;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforeFtpdInit', $self, 'proftpd'
	) and fatal('proftpd - beforeFtpdInit hook has failed');;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/proftpd";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'commentChar'} = '#';

	tie %self::proftpdConfig, 'iMSCP::Config', 'fileName' => "$self->{'cfgDir'}/proftpd.data";
	$self->{$_} = $self::proftpdConfig{$_} for keys %self::proftpdConfig;

	$self->{'hooksManager'}->trigger(
		'afterFtpdInit', $self, 'proftpd'
	) and fatal('proftpd - afterFtpdInit hook has failed');

	$self;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;

	require Servers::ftpd::proftpd::installer;
	Servers::ftpd::proftpd::installer->getInstance(
		proftpdConfig => \%self::proftpdConfig
	)->registerSetupHooks($hooksManager);
}

sub install
{
	my $self = shift;

	require Servers::ftpd::proftpd::installer;
	Servers::ftpd::proftpd::installer->getInstance(proftpdConfig => \%self::proftpdConfig)->install();
}

sub postinstall
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeFtpdPostInstall', 'proftpd');
	return $rs if $rs;

	$self->{'restart'} = 'yes';

	$self->{'hooksManager'}->trigger('afterFtpdPostInstall', 'proftpd');
}

sub uninstall
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeFtpdUninstall', 'proftpd');
	return $rs if $rs;

	require Servers::ftpd::proftpd::uninstaller;
	$rs = Servers::ftpd::proftpd::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$rs = $self->{'hooksManager'}->trigger('afterFtpdUninstall', 'proftpd');
	return $rs if $rs;

	$self->restart();
}

sub restart
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeFtpdRestart');
	return $rs if $rs;

	my ($stdout, $stderr);
	$rs = execute("$self->{'CMD_FTPD'} restart", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	# Debug target is expected below
	debug($stderr) if $stderr && ! $rs;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterFtpdRestart');
}

sub addUser
{
	my $self = shift;
	my $data = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforeFtpdAddUser', $data);
	return $rs if $rs;

	my $uid = scalar getpwnam($data->{'USER'});
	my $gid = scalar getgrnam($data->{'GROUP'});

	my $database = iMSCP::Database->factory();

	# Updating ftp_users.uid and ftp_users.gid columns
	my @sql = ("UPDATE `ftp_users` SET `uid` = ?, `gid` = ? WHERE `admin_id` = ?", $uid, $gid, $data->{'USER_ID'});
	my $rdata = $database->doQuery('update', @sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	# Updating ftp_group.gid column
	@sql = ('UPDATE `ftp_group` SET `gid` = ? WHERE `groupname` = ?', $gid, $data->{'USERNAME'});
	$rdata = $database->doQuery('update', @sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$self->{'hooksManager'}->trigger('AfterFtpdAddUser', $data);
}

sub getTraffic
{
	my $self = shift;
	my $who = shift;
	my $trfFile	= "$main::imscpConfig{'TRAFF_LOG_DIR'}/$self::proftpdConfig{'FTP_TRAFF_LOG'}";

	unless(exists $self->{'logDb'}) {
		$self->{'logDb'} = {};
		my $rs = iMSCP::File->new('filename' => $trfFile)->moveFile("$trfFile.old") if -f $trfFile;

		if($rs) {
			delete $self->{'logDb'};
			return 0;
		}

		if(-f "$trfFile.old") {
			my $content = iMSCP::File->new('filename' => "$trfFile.old")->get();
			while($content =~ /^(\d+)\s[^\@]+\@(.*)$/mg){
				$self->{'logDb'}->{$2} += $1 if (defined $2 && defined $1);
			}
		}
	}

	$self->{'logDb'}->{$who} ? $self->{'logDb'}->{$who} : 0;
}

END
{
	my $exitCode = $?;
	my $self = Servers::ftpd::proftpd->getInstance();
	my $trfFile	= "$main::imscpConfig{'TRAFF_LOG_DIR'}/$self::proftpdConfig{'FTP_TRAFF_LOG'}";
	my $rs = 0;

	$rs = $self->restart() if defined $self->{'restart'} && $self->{'restart'} eq 'yes';
	$rs |= iMSCP::File->new('filename' => "$trfFile.old")->delFile() if -f "$trfFile.old";

	$? = $exitCode || $rs;
}

1;
