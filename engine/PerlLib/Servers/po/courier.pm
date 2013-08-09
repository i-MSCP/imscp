#!/usr/bin/perl

=head1 NAME

 Servers::po::courier - i-MSCP Courier IMAP/POP3 Server implementation

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
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Daniel Andreca <sci2tech@gmail.com>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::po::courier;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Courier IMAP/POP3 Server implementation.

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

	my $rs = $hooksManager->trigger('beforePoRegisterSetupHooks', $hooksManager, 'courier');
	return $rs if $rs;

	$hooksManager->trigger('afterPoRegisterSetupHooks', $hooksManager, 'courier');
}

=item preinstall()

 Process preinstall tasks.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoPreinstall', 'courier');
	return $rs if $rs;

	$rs = $self->stop();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterPoPreinstall', 'courier');
}

=item install()

 Process install tasks.

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	require Servers::po::courier::installer;
	Servers::po::courier::installer->getInstance(config => \%self::config)->install();
}

=item postinstall()

 Process postinstall tasks.

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoPostinstall', 'courier');
	return $rs if $rs;

	$rs = $self->start();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterPoPostinstall', 'courier');
}

=item uninstall()

 Process uninstall tasks.

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoUninstall', 'courier');
	return $rs if $rs;

	require Servers::po::courier::uninstaller;

	$rs = Servers::po::courier::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$rs = $self->restart();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterPoUninstall', 'courier');
}

=item addMail()

 Add mail account.

 Return int 0 on success, other on failure

=cut

sub addMail
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	if($data->{'MAIL_TYPE'} =~ /_mail/) {
		$rs = $self->{'hooksManager'}->trigger('beforePoAddMail');
		return $rs if $rs;

		# Backup current working file if any
		if(-f "$self->{'wrkDir'}/userdb"){
			$rs = iMSCP::File->new(
				'filename' => "$self->{'wrkDir'}/userdb"
			)->copyFile(
				"$self->{'bkpDir'}/userdb." . time
			);
			return $rs if $rs;
		}

		my $userdbWrkFile = -f "$self->{'wrkDir'}/userdb" ? "$self->{'wrkDir'}/userdb" : "$self->{'cfgDir'}/userdb";

		# Getting userdb working file content
		$userdbWrkFile = iMSCP::File->new('filename' => $userdbWrkFile);
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
		my $uid = scalar getpwnam($mta->{'config'}->{'MTA_MAILBOX_UID_NAME'});
		my $gid = scalar getgrnam($mta->{'config'}->{'MTA_MAILBOX_GID_NAME'});
		my $mailDir = $mta->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'};

		# Adding new entry in userdb file
		$userdbWrkFileContent .=
			"$data->{'MAIL_ADDR'}\tuid=$uid|gid=$gid|home=$mailDir/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}|" .
			"shell=/bin/false|systempw=$password|mail=$mailDir/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}\n";

		# Writing the new userdb working file
		$userdbWrkFile->{'filename'} = "$self->{'wrkDir'}/userdb";

		$rs = $userdbWrkFile->set($userdbWrkFileContent);
		return $rs if $rs;

		$rs = $userdbWrkFile->save();
		return $rs if $rs;

		# Setting permissions on userdb working file
		$rs = $userdbWrkFile->mode(0600);
		return $rs if $rs;

		$rs = $userdbWrkFile->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		# Copying new file in production directory (permissions are preserved)
		$rs = $userdbWrkFile->copyFile("$self->{'AUTHLIB_CONF_DIR'}/userdb");
		return $rs if $rs;

		# Updating userdb.dat file from the contents of the new userdb file
		my ($stdout, $stderr);
		$rs = execute($self->{'CMD_MAKEUSERDB'}, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = $self->{'hooksManager'}->trigger('afterPoAddMail');
		return $rs if $rs;
	}
}

=item postaddMail()

 Create maildir folders and subscription file.

 Return int 0 on success, other on failure

=cut

sub postaddMail
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	if($data->{'MAIL_TYPE'} =~ /_mail/) {

		# Getting i-MSCP MTA server implementation instance
		require Servers::mta;
		my $mta = Servers::mta->factory();

		my $mailDir = "$mta->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}";
		my $mailUidName =  $mta->{'config'}->{'MTA_MAILBOX_UID_NAME'};
		my $mailGidName = $mta->{'config'}->{'MTA_MAILBOX_GID_NAME'};

		for ("$mailDir/.Drafts", "$mailDir/.Junk", "$mailDir/.Sent", "$mailDir/.Trash") {

			# Creating maildir directory or only set its permissions if already exists
			$rs = iMSCP::Dir->new('dirname' => $_)->make(
				{ 'user' => $mailUidName, 'group' => $mailGidName , 'mode' => 0750 }
			);
			return $rs if $rs;

			# Creating maildir sub folders (cur, new, tmp) or only set there permissions if they already exists
			for my $subdir ('cur', 'new', 'tmp') {
				$rs = iMSCP::Dir->new('dirname' => "$_/$subdir")->make(
					{ 'user' => $mailUidName, 'group' => $mailGidName, 'mode' => 0750 }
				);
				return $rs if $rs;
			}
		}

		# Creating/updating courierimapsubscribed file

		my @subscribedFolders = ('INBOX.Drafts', 'INBOX.Junk', 'INBOX.Sent', 'INBOX.Trash');
		my $courierimapsubscribedFile = iMSCP::File->new('filename' => "$mailDir/courierimapsubscribed");

		if(-f "$mailDir/courierimapsubscribed") {
			my $courierimapsubscribedFileContent = $courierimapsubscribedFile->get();

			if(! defined $courierimapsubscribedFileContent) {
				error('Unable to read courier courierimapsubscribed file');
				return 1;
			}

			if($courierimapsubscribedFileContent ne '') {
				@subscribedFolders = (@subscribedFolders, split("\n", $courierimapsubscribedFileContent));
				require List::MoreUtils;
				@subscribedFolders = sort(List::MoreUtils::uniq(@subscribedFolders));
			}
		}

		$rs = $courierimapsubscribedFile->set((join "\n", @subscribedFolders) . "\n");
		return $rs if $rs;

		$rs = $courierimapsubscribedFile->save();
		return $rs if $rs;

		$rs = $courierimapsubscribedFile->mode(0640);
		return $rs if $rs;

		$rs = $courierimapsubscribedFile->owner($mailUidName, $mailGidName);
		return $rs if $rs;
	}

	0;
}

=item deleteMail()

 Delete mail account.

 Return int 0 on success, other on failure

=cut

sub deleteMail
{
	my $self = shift;
	my $data = shift;
	my $rs = 0;

	if($data->{'MAIL_TYPE'} =~ /_mail/) {

		$rs = $self->{'hooksManager'}->trigger('beforePoDelMail');
		return $rs if $rs;

		if(-f "$self->{'wrkDir'}/userdb"){
			$rs = iMSCP::File->new(
				'filename' => "$self->{'wrkDir'}/userdb"
			)->copyFile(
				"$self->{'bkpDir'}/userdb." . time
			);
			return $rs if $rs;
		}

		my $userdbWrkFile = -f "$self->{'wrkDir'}/userdb" ? "$self->{'wrkDir'}/userdb" : "$self->{'cfgDir'}/userdb";

		# Getting userdb working file content
		$userdbWrkFile = iMSCP::File->new('filename' => $userdbWrkFile);
		my $userdbWrkFileContent = $userdbWrkFile->get();
		return 1 unless defined $userdbWrkFileContent;

		# Removing entry in userdb working file
		my $mailbox = $data->{'MAIL_ADDR'};
		$mailbox =~ s/\./\\\./g;
		$userdbWrkFileContent =~ s/^$mailbox\t[^\n]*\n//gmi;

		# Writing the new userdb working file
		$userdbWrkFile->{'filename'} = "$self->{'wrkDir'}/userdb";

		$rs = $userdbWrkFile->set($userdbWrkFileContent);
		return $rs if $rs;

		$rs = $userdbWrkFile->save();
		return $rs if $rs;

		# Setting permissions on userdb working file
		$rs = $userdbWrkFile->mode(0600);
		return $rs if $rs;

		$rs = $userdbWrkFile->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		# Copying new file in production directory (permissions are preserved)
		$rs = $userdbWrkFile->copyFile("$self->{'AUTHLIB_CONF_DIR'}/userdb");
		return $rs if $rs;

		# Updating userdb.dat file from the contents of the new userdb file
		my ($stdout, $stderr);
		$rs = execute($self->{'CMD_MAKEUSERDB'}, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = $self->{'hooksManager'}->trigger('afterPoDelMail');
		return $rs if $rs;
	}

	0;
}

=item start()

 Start courier servers.

 Return int 0 on success, other on failure

=cut

sub start
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoStart');
	return $rs if $rs;

	my ($stdout, $stderr);

	for('CMD_AUTHD', 'CMD_POP', 'CMD_IMAP', 'CMD_POP_SSL', 'CMD_IMAP_SSL') {
		$rs = execute("$self->{'config'}->{$_} start", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterPoStart');
}

=item stop()

 Stop courier servers.

 Return int 0 on success, other on failure

=cut

sub stop
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoStop');
	return $rs if $rs;

	my ($stdout, $stderr);

	for('CMD_AUTHD', 'CMD_POP', 'CMD_IMAP', 'CMD_POP_SSL', 'CMD_IMAP_SSL') {
		$rs = execute("$self->{'config'}->{$_} stop", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;;
	}

	$self->{'hooksManager'}->trigger('afterPoStop');
}

=item restart()

 Restart courier servers.

 Return int 0 on success, other on failure

=cut

sub restart
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoRestart');
	return $rs if $rs;

	my ($stdout, $stderr);

	for('CMD_AUTHD', 'CMD_POP', 'CMD_IMAP', 'CMD_POP_SSL', 'CMD_IMAP_SSL') {
		$rs = execute("$self->{'config'}->{$_} restart", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	$self->{'hooksManager'}->trigger('afterPoRestart');
}

=item getTraffic()

 Get server traffic.

 Return int Server traffic, 0 on failure

=cut

sub getTraffic
{
	my $self = shift;
	my $who = shift;
	my $dbName = "$self->{'wrkDir'}/log.db";
	my $logFile = "$main::imscpConfig{'TRAFF_LOG_DIR'}/mail.log";
	my $wrkLogFile = "$main::imscpConfig{'LOG_DIR'}/mail.po.log";
	my ($rv, $rs, $stdout, $stderr);

	$self->{'hooksManager'}->trigger('beforePoGetTraffic') and return 0;

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
		$self->{'logDb'}->{'content'} = $content[$#content];

		# test for logratation
		if($content[$lastLineNo] && $content[$lastLineNo] eq $lastLine){
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

	$self->{'hooksManager'}->trigger('afterPoGetTraffic') and return 0;

	$self->{'traff'}->{$who} ? $self->{'traff'}->{$who} : 0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance.

 Return Servers::po::courier

=cut

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforePoInit', $self, 'courier'
	) and fatal('courier - beforePoInit hook has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/courier";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	tie %{$self->{'config'}}, 'iMSCP::Config', 'fileName' => "$self->{'cfgDir'}/courier.data";

	$self->{'hooksManager'}->trigger(
		'afterPoInit', $self, 'courier'
	) and fatal('courier - afterPoInit hook has failed');

	$self;
}

=item END

 Code triggered at the very end of script execution.

-  Start or restart server if needed
 - Remove old traffic logs file if exists

 Return int Exit code

=cut

END
{
	my $exitCode = $?;
	my $self = Servers::po::courier->getInstance();
	my $wrkLogFile = "$main::imscpConfig{'LOG_DIR'}/mail.po.log";
	my $rs = 0;

	$rs = $self->restart() if $self->{'restart'} && $self->{'restart'} eq 'yes';
	$rs |= iMSCP::File->new('filename' => $wrkLogFile)->delFile() if -f $wrkLogFile;

	$? = $exitCode || $rs;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
