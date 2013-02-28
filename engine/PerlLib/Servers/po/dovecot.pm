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

package Servers::po::dovecot;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::File;
use Tie::File;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	iMSCP::HooksManager->getInstance()->trigger('beforePoInit', $self, 'dovecot');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/dovecot";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	my $conf = "$self->{'cfgDir'}/dovecot.data";

	tie %self::dovecotConfig, 'iMSCP::Config','fileName' => $conf;

	iMSCP::HooksManager->getInstance()->trigger('afterPoInit', $self, 'dovecot');

	$self;
}

sub registerSetupHooks
{
	my $self = shift;
	my $hooksManager = shift;
	my $rs = 0;

	$rs = $hooksManager->trigger('beforePoRegisterSetupHooks', $hooksManager, 'dovecot');

	require Servers::po::dovecot::installer;

	$rs |= Servers::po::dovecot::installer->new()->registerSetupHooks($hooksManager);

	$rs |= $hooksManager->trigger('afterPoRegisterSetupHooks', $hooksManager, 'dovecot');

	$rs;
}

sub install
{
	my $self = shift;
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforePoInstall', 'dovecot');

	require Servers::po::dovecot::installer;

	$rs |= Servers::po::dovecot::installer->new()->install();

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterPoInstall', 'dovecot');

	$rs;
}

sub uninstall
{
	my $self = shift;
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforePoUninstall', 'dovecot');

	require Servers::po::dovecot::uninstaller;

	$rs |= Servers::po::dovecot::uninstaller->new()->uninstall();
	$rs |= $self->restart();

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterPoUninstall', 'dovecot');

	$rs;
}

sub postinstall
{
	my $self = shift;
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforePoPostinstall', 'dovecot');

	$self->{'restart'} = 'yes';

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterPoPostinstall', 'dovecot');

	$rs;
}

sub restart
{
	my $self = shift;
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforePoRestart');

	my ($stdout, $stderr);
	$rs |= execute("$self::dovecotConfig{'CMD_DOVECOT'} restart", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr && !$rs;
	error($stderr) if $stderr && $rs;

	$rs |= iMSCP::HooksManager->getInstance()->trigger('afterPoRestart');

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

	iMSCP::HooksManager->getInstance()->trigger('beforePoGetTraffic');

	# only if files was not already parsed this session
	unless($self->{'logDb'}){
		# use a small conf file to memorize last line readed and his content
		tie %{$self->{'logDb'}}, 'iMSCP::Config','fileName' => $dbName, noerrors => 1;

		# first use? we zero line and content
		$self->{'logDb'}->{'line'} = 0 unless $self->{'logDb'}->{'line'};
		$self->{'logDb'}->{'content'} = '' unless $self->{'logDb'}->{'content'};
		my $lastLineNo = $self->{'logDb'}->{'line'};
		my $lastLine = $self->{'logDb'}->{'content'};

		# copy log file
		$rs = iMSCP::File->new(filename => $logFile)->copyFile($wrkLogFile) if -f $logFile;
		# return 0 traffic if we fail
		return 0 if $rs;

		# link log file to array
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

		# IMAP
		# Oct 15 13:50:31 daniel dovecot: imap(ndmn@test1.eu.bogus): Disconnected: Logged out bytes=235/1032
		# 1   2     3      4      5                6                      7          8    9      10
		while($content =~ m/^.*imap\([^\@]+\@([^\)]+).*Logged out bytes=(\d+)\/(\d+)$/mg){
					# date time   mailfrom @ domain                    size / size
					#                           1                       2       3
			$self->{'traff'}->{$1} += $2 + $3 if $1 && defined $2 && defined $3 && ($2+$3);
			debug("Traffic for $1 is $self->{'traff'}->{$1} (added IMAP traffic: ". ($2 + $3).")") if $1 && defined $2 && defined $3 && ($2+$3);
		}

		# POP
		# Oct 15 14:23:39 daniel dovecot: pop3(ndmn@test1.eu.bogus): Disconnected: Logged out top=0/0, retr=1/533, del=0/1, size=517
		# 1   2     3      4      5                6                      7          8    9      10       11        12       13
		while($content =~ m/^.*pop3\([^\@]+\@([^\)]+).*Logged out .* retr=(\d+)\/(\d+).*$/mg){
					# date time   mailfrom @ domain                    size / size
					#                           1                       2       3
			$self->{'traff'}->{$1} += $2 + $3 if $1 && defined $2 && defined $3 && ($2+$3);
			debug("Traffic for $1 is $self->{'traff'}->{$1} (added POP traffic: ". ($2 + $3).")") if $1 && defined $2 && defined $3 && ($2+$3);
		}
	}

	iMSCP::HooksManager->getInstance()->trigger('afterPoGetTraffic');

	$self->{'traff'}->{$who} ? $self->{'traff'}->{$who} : 0;
}

END
{
	my $endCode	= $?;
	my $self = Servers::po::dovecot->new();
	my $wrkLogFile = "$main::imscpConfig{'LOG_DIR'}/mail.po.log";
	my $rs = 0;

	$rs |= $self->restart() if $self->{'restart'} && $self->{'restart'} eq 'yes';
	$rs |= iMSCP::File->new(filename => $wrkLogFile)->delFile() if -f $wrkLogFile;

	$? = $endCode || $rs;
}

1;
