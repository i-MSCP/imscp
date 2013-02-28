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
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::po::courier;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::File;
use iMSCP::Execute;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	iMSCP::HooksManager->getInstance()->trigger('beforePoInit', $self, 'courier');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/courier";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	my $conf = "$self->{'cfgDir'}/courier.data";
	tie %self::courierConfig, 'iMSCP::Config', 'fileName' => $conf;

	$self->{$_} = $self::courierConfig{$_} for keys %self::courierConfig;

	iMSCP::HooksManager->getInstance()->trigger('afterPoInit', $self, 'courier');

	$self;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;
	my $rs = 0;

	$rs = $hooksManager->trigger('beforePoRegisterSetupHooks', $hooksManager, 'courier');

	require Servers::po::courier::installer;

	$rs |= Servers::po::courier::installer->new()->registerSetupHooks($hooksManager);

	$rs |= $hooksManager->trigger('afterPoRegisterSetupHooks', $hooksManager, 'courier');

	$rs;
}

sub preinstall
{
	my $self = shift;
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforePoPreinstall', 'courier');

	$rs |= $self->stop();

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterPoPreinstall', 'courier');

	$rs;
}

sub install
{
	my $self = shift;
	my $rs = 0;

	$rs |= iMSCP::HooksManager->getInstance()->trigger('beforePoInstall', 'courier');

	require Servers::po::courier::installer;

	$rs |= Servers::po::courier::installer->new()->install();

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterPoInstall', 'courier');

	$rs;
}

sub uninstall
{
	my $self = shift;
	my $rs = 0;

	$rs |= iMSCP::HooksManager->getInstance()->trigger('beforePoUninstall', 'courier');

	require Servers::po::courier::uninstaller;

	$rs |= Servers::po::courier::uninstaller->new()->uninstall();
	$rs |= $self->restart();

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterPoUninstall', 'courier');

	$rs;
}

sub postinstall
{
	my $self = shift;
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforePoPostinstall', 'courier');

	$rs |= $self->start();

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterPoPostinstall', 'courier');

	$rs;
}

sub start
{
	my $self = shift;
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforePoStart');

	my ($stdout, $stderr);

	for('CMD_AUTHD', 'CMD_POP', 'CMD_IMAP', 'CMD_POP_SSL', 'CMD_IMAP_SSL') {
		$rs |= execute("$self::courierConfig{$_} start", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr;
		last if $rs;
	}

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterPoStart');

	$rs;
}

sub stop
{
	my $self = shift;
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforePoStop');

	my ($stdout, $stderr);

	for('CMD_AUTHD', 'CMD_POP', 'CMD_IMAP', 'CMD_POP_SSL', 'CMD_IMAP_SSL') {
		$rs |= execute("$self::courierConfig{$_} stop", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr;
		last if $rs;
	}

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterPoStop');

	$rs;
}

sub restart
{
	my $self = shift;
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforePoRestart');

	my ($stdout, $stderr);

	for('CMD_AUTHD', 'CMD_POP', 'CMD_IMAP', 'CMD_POP_SSL', 'CMD_IMAP_SSL') {
		$rs |= execute("$self::courierConfig{$_} restart", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr;
		last if $rs;
	}

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterPoRestart');

	$rs;
}

sub addMail
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $errmsg = {
		'MAIL_ADDR'	=> 'You must supply mail address!',
		'MAIL_PASS'	=> 'You must supply account password!'
	};

	for(keys %{$errmsg}) {
		error($errmsg->{$_}) unless $data->{$_};
		return 1 unless $data->{$_};
	}

	if($data->{'MAIL_TYPE'} =~ /_mail/) {

		$rs = iMSCP::HooksManager->getInstance()->trigger('beforePoAddMail');

		# Backup current working file if any
		if(-f "$self->{'wrkDir'}/userdb"){
			$rs |= iMSCP::File->new(
				'filename' => "$self->{'wrkDir'}/userdb"
			)->copyFile(
				"$self->{'bkpDir'}/userdb." . time
			);
		}

		my $userdbWrkFile = -f "$self->{'wrkDir'}/userdb" ? "$self->{'wrkDir'}/userdb" : "$self->{'cfgDir'}/userdb";

		# Getting userdb working file content
		my $userdbWrkFile = iMSCP::File->new('filename' => $userdbWrkFile);
		my $userdbWrkFileContent = $userdbWrkFile->get();
		return 1 unless defined $userdbWrkFileContent;

		# Ensuring that the new entry doesn't already exists
		my $mailbox = $data->{'MAIL_ADDR'};
		$mailbox =~ s/\./\\\./g;
		$userdbWrkFileContent =~ s/^$mailbox\t[^\n]*\n//gmi;

		# Encrypt password
		require Crypt::PasswdMD5;
		Crypt::PasswdMD5->import();
		my @rand_data = ('A'..'Z', 'a'..'z', '0'..'9', '.', '/');
		my $rand;
		$rand .= $rand_data[rand()*($#rand_data + 1)] for('1'..'8');
		my $password = unix_md5_crypt($data->{'MAIL_PASS'}, $rand);

		# Retrieve needed data from MTA
		require Servers::mta;
		my $mta = Servers::mta->factory();
		my $uid = scalar getpwnam($mta->{'MTA_MAILBOX_UID_NAME'});
		my $gid = scalar getgrnam($mta->{'MTA_MAILBOX_GID_NAME'});
		my $mailDir = $mta->{'MTA_VIRTUAL_MAIL_DIR'};

		# Adding new entry in userdb file
		$userdbWrkFileContent .=
			"$data->{'MAIL_ADDR'}\tuid=$uid|gid=$gid|home=$mailDir/$data->{'DMN_NAME'}/$data->{'MAIL_ACC'}|" .
			"shell=/bin/false|systempw=$password|mail=$mailDir/$data->{'DMN_NAME'}/$data->{'MAIL_ACC'}\n";

		# Writing the new userdb working file
		$userdbWrkFile->{'filename'} = "$self->{'wrkDir'}/userdb";
		$userdbWrkFile->set($userdbWrkFileContent);
		$rs |= $userdbWrkFile->save();

		# Setting permissions on userdb working file
		$rs |= $userdbWrkFile->mode(0600);
		$rs |= $userdbWrkFile->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

		# Copying new file in production directory (permissions are preserved)
		$rs |= $userdbWrkFile->copyFile("$self->{'AUTHLIB_CONF_DIR'}/userdb");

		# Updating userdb.dat file from the contents of the new userdb file
		my ($stdout, $stderr);
		$rs |= execute($self->{'CMD_MAKEUSERDB'}, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr;

		$rs |= iMSCP::HooksManager->getInstance()->trigger('afterPoAddMail');
	}

	$rs;
}

sub delMail
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	my $errmsg = {
		'MAIL_ADDR'	=> 'You must supply mail address!',
		'MAIL_PASS'	=> 'You must supply account password!'
	};

	for(keys %{$errmsg}){
		error("$errmsg->{$_}") unless $data->{$_};
		return 1 unless $data->{$_};
	}

	if($data->{'MAIL_TYPE'} =~ /_mail/) {

		$rs = iMSCP::HooksManager->getInstance()->trigger('beforePoDelMail');
		return $rs if $rs;

		if(-f "$self->{'wrkDir'}/userdb"){
			$rs |= iMSCP::File->new(
				'filename' => "$self->{'wrkDir'}/userdb"
			)->copyFile(
				"$self->{'bkpDir'}/userdb." . time
			);
		}

		my $userdbWrkFile = -f "$self->{'wrkDir'}/userdb" ? "$self->{'wrkDir'}/userdb" : "$self->{'cfgDir'}/userdb";

		# Getting userdb working file content
		my $userdbWrkFile = iMSCP::File->new('filename' => $userdbWrkFile);
		my $userdbWrkFileContent = $userdbWrkFile->get();
		return 1 unless defined $userdbWrkFileContent;

		# Removing entry in userdb working file
		my $mailbox = $data->{'MAIL_ADDR'};
		$mailbox =~ s/\./\\\./g;
		$userdbWrkFileContent =~ s/^$mailbox\t[^\n]*\n//gmi;

		# Writing the new userdb working file
		$userdbWrkFile->{'filename'} = "$self->{'wrkDir'}/userdb";
		$userdbWrkFile->set($userdbWrkFileContent);
		$rs |= $userdbWrkFile->save();

		# Setting permissions on userdb working file
		$rs |= $userdbWrkFile->mode(0600);
		$rs |= $userdbWrkFile->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

		# Copying new file in production directory (permissions are preserved)
		$rs |= $userdbWrkFile->copyFile("$self->{'AUTHLIB_CONF_DIR'}/userdb");

		# Updating userdb.dat file from the contents of the new userdb file
		my ($stdout, $stderr);
		$rs |= execute($self->{'CMD_MAKEUSERDB'}, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr;

		$rs |= iMSCP::HooksManager->getInstance()->trigger('afterPoDelMail');
	}

	$rs;
}

sub getTraffic
{
	my $self = shift;
	my $who = shift;
	my $dbName = "$self->{'wrkDir'}/log.db";
	my $logFile = "$main::imscpConfig{'TRAFF_LOG_DIR'}/mail.log";
	my $wrkLogFile = "$main::imscpConfig{'LOG_DIR'}/mail.po.log";
	my ($rv, $rs, $stdout, $stderr);

	iMSCP::HooksManager->getInstance()->trigger('beforePoGetTraffic') and return 0;

	# only if files was not aleady parsed this session
	unless($self->{'logDb'}) {
		# use a small conf file to memorize last line readed and his content
		tie %{$self->{'logDb'}}, 'iMSCP::Config','fileName' => $dbName, noerrors => 1;

		## first use? we zero line and content
		$self->{'logDb'}->{'line'} = 0 unless $self->{'logDb'}->{'line'};
		$self->{'logDb'}->{'content'} = '' unless $self->{'logDb'}->{'content'};
		my $lastLineNo = $self->{'logDb'}->{'line'};
		my $lastLine = $self->{'logDb'}->{'content'};

		# copy log file
		$rs = iMSCP::File->new(filename => $logFile)->copyFile($wrkLogFile) if -f $logFile;
		# return 0 traffic if we fail
		return 0 if $rs;

		# link log file to array
		require Tie::File;
		tie my @content, 'Tie::File', $wrkLogFile or return 0;

		# save last line
		$self->{'logDb'}->{'line'} = $#content;
		$self->{'logDb'}->{'content'} = @content[$#content];

		# test for logratation
		if(@content[$lastLineNo] && @content[$lastLineNo] eq $lastLine){
			## No logratation ocure. We zero already readed files
			(tied @content)->defer;
			@content = @content[$lastLineNo + 1 .. $#content];
			(tied @content)->flush;
		}

		# Read log file
		my $content = iMSCP::File->new(filename => $wrkLogFile)->get() || '';

		#IMAP
		# Oct 15 12:56:42 daniel imapd: LOGOUT, user=ndmn@test1.eu.bogus, ip=[::ffff:192.168.1.2], headers=0, body=0, rcvd=172, sent=310, time=205
		# 1   2     3      4      5      6         7                              8                  9         10       11        12        13
		while($content =~ m/^.*(?:imapd|imapd\-ssl).*user=[^\@]*\@([^,]*),\sip=\[([^\]]+)\],\sheaders=(\d+),\sbody=(\d+),\srcvd=(\d+),\ssent=(\d+),.*$/mg){
						# date time imap(-ssl)         mailfrom @ domain       ip             headers size      body size  received size   send size      etc
						#                                             1         2                     3              4         5              6
			if($2 !~ /localhost|127.0.0.1/) {
					# $self->{traff}->{$1} += $3 + $4 + $5 + $6;
					# Why we count only headers and body, not all traffic?!! to be checked
					$self->{'traff'}->{$1} += $3 + $4
						if $1 && defined $3 && defined $4 && ($3+$4);
					debug("Traffic for $1 is $self->{traff}->{$1} (added IMAP traffic: ". ($3 + $4).")")
						if $1 && defined $3 && defined $4 && ($3+$4);
			}
		}

		# POP
		# courierpop3login is for Debian. pop3d for Fedora.
		# Oct 15 14:54:06 daniel pop3d:     LOGOUT, user=ndmn@test1.eu.bogus, ip=[::ffff:192.168.1.2], port=[41477], top=0, retr=0, rcvd=32, sent=147, time=0, stls=1
		# Oct 15 14:51:12 daniel pop3d-ssl: LOGOUT, user=ndmn@test1.eu.bogus, ip=[::ffff:192.168.1.2], port=[41254], top=0, retr=496, rcvd=32, sent=672, time=0, stls=1
		# 1   2     3      4      5           6         7                              8                  9          10       11        12        13
		while($content =~ m/^.*(?:courierpop3login|pop3d|pop3d-ssl).*user=[^\@]*\@([^,]*),\sip=\[([^\]]+)\].*\stop=(\d+),\sretr=(\d+),\srcvd=(\d+),\ssent=(\d+),.*$/mg){
						# date time imap(-ssl)                mailfrom @ domain                  ip           top size    retr size   received size   send size      etc
						#                                              1                         2                3           4            5              6
			if($2 !~ /localhost|127.0.0.1/) {
					# $self->{traff}->{$1} += $3 + $4 + $5 + $6;
					# Why we count some of fields, not all traffic?!! to be checked
					$self->{'traff'}->{$1} += $4 + $5 + $6
						if $1 && defined $4 && defined $5 && defined $6 && ($4+$5+$6);
					debug("Traffic for $1 is $self->{'traff'}->{$1} (added POP traffic: ". ($4 + $5 + $6).")")
						if $1 && defined $4 && defined $5 && defined $6 && ($4+$5+$6);
			}
		}
	}

	iMSCP::HooksManager->getInstance()->trigger('afterPoGetTraffic') and return 0;

	$self->{'traff'}->{$who} ? $self->{'traff'}->{$who} : 0;
}

END
{
	my $endCode = $?;
	my $self = Servers::po::courier->new();
	my $wrkLogFile = "$main::imscpConfig{LOG_DIR}/mail.po.log";
	my $rs = 0;

	$rs |= $self->restart() if $self->{'restart'} && $self->{'restart'} eq 'yes';
	$rs |= iMSCP::File->new('filename' => $wrkLogFile)->delFile() if -f $wrkLogFile;
	$? = $endCode || $rs;
}

1;
