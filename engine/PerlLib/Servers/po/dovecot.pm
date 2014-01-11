#!/usr/bin/perl

=head1 NAME

 Servers::po::dovecot - i-MSCP Dovecot IMAP/POP3 Server implementation

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

package Servers::po::dovecot;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Dir;
use Tie::File;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Dovecot IMAP/POP3 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupHooks($hooksManager)

 Register setup hooks.

 Param iMSCP::HooksManager $hooksManager Hooks manager instance
 Return int 0 on success, other on failure

=cut

sub registerSetupHooks($$)
{
	my ($self, $hooksManager) = @_;

	require Servers::po::dovecot::installer;
	Servers::po::dovecot::installer->getInstance()->registerSetupHooks($hooksManager);
}

=item install()

 Process install tasks.

 Return int 0 on success, other on failure

=cut

sub install
{
	require Servers::po::dovecot::installer;
	Servers::po::dovecot::installer->getInstance()->install();
}

=item postinstall()

 Process postinstall tasks.

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoPostinstall', 'dovecot');
	return $rs if $rs;

	$self->{'restart'} = 'yes';

	$self->{'hooksManager'}->trigger('afterPoPostinstall', 'dovecot');
}

=item postaddMail()

 Create maildir folders and subscription file.

 Return int 0 on success, other on failure

=cut

sub postaddMail($$)
{
	my ($self, $data) = @_;

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

		# Creating/updating subscriptions file

		my @subscribedFolders = ('Drafts', 'Junk', 'Sent', 'Trash');
		my $subscriptionsFile = iMSCP::File->new('filename' => "$mailDir/subscriptions");

		if(-f "$mailDir/subscriptions") {
			my $subscriptionsFileContent = $subscriptionsFile->get();

			if(! defined $subscriptionsFileContent) {
				error('Unable to read dovecot subscriptions file');
				return 1;
			}

			if($subscriptionsFileContent ne '') {
				@subscribedFolders = (@subscribedFolders, split("\n", $subscriptionsFileContent));
				require List::MoreUtils;
				@subscribedFolders = sort(List::MoreUtils::uniq(@subscribedFolders));
			}
		}

		$rs = $subscriptionsFile->set((join "\n", @subscribedFolders) . "\n");
		return $rs if $rs;

		$rs = $subscriptionsFile->save();
		return $rs if $rs;

		$rs = $subscriptionsFile->mode(0640);
		return $rs if $rs;

		$rs = $subscriptionsFile->owner($mailUidName, $mailGidName);
		return $rs if $rs;

		if(-f "$mailDir/maildirsize") {
			$rs = iMSCP::File->new('filename' => "$mailDir/maildirsize")->delFile();
			return $rs if $rs;
		}
	}

	0;
}

=item uninstall()

 Process uninstall tasks.

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoUninstall', 'dovecot');
	return $rs if $rs;

	require Servers::po::dovecot::uninstaller;

	$rs = Servers::po::dovecot::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$rs = $self->restart();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterPoUninstall', 'dovecot');
}

=item restart()

 Restart the server.

 Return int 0, other on failure

=cut

sub restart
{
	my $self = shift;

	my $rs = $self->{'hooksManager'}->trigger('beforePoRestart');
	return $rs if $rs;

	my $stdout;
	$rs = execute(
		"$main::imscpConfig{'SERVICE_MNGR'} $self->{'config'}->{'DOVECOT_SNAME'} restart 2>/dev/null", \$stdout
	);
	debug($stdout) if $stdout;
	error('Unable to restart Dovecot') if $rs > 1;
	return $rs if $rs > 1;

	$self->{'hooksManager'}->trigger('afterPoRestart');
}

=item getTraffic($domainName)

 Get IMAP/POP traffic for the given domain name.

 Param string $domainName Domain name for which traffic must be returned
 Return int Traffic amount (BytesIn+BytesOut) in bytes, 0 on failure

=cut

sub getTraffic($$)
{
	my ($self, $domainName) = @_;

	my $dbName = "$self->{'wrkDir'}/log.db";
	my $logFile = "$main::imscpConfig{'TRAFF_LOG_DIR'}/mail.log";
	my $wrkLogFile = "$main::imscpConfig{'LOG_DIR'}/mail.po.log";
	my ($rv, $rs, $stdout, $stderr);

	$self->{'hooksManager'}->trigger('beforePoGetTraffic', $domainName) and return 0;

	# We process only if the file has not been aleady parsed during this session
	unless($self->{'logDb'}) {
		# We are using a small file to memorize the number of the last line that has been read and his content
		tie %{$self->{'logDb'}}, 'iMSCP::Config','fileName' => $dbName, noerrors => 1;

		$self->{'logDb'}->{'line'} = 0 unless $self->{'logDb'}->{'line'};
		$self->{'logDb'}->{'content'} = '' unless $self->{'logDb'}->{'content'};

		my $lastLineNo = $self->{'logDb'}->{'line'};
		my $lastLine = $self->{'logDb'}->{'content'};

		$rs = iMSCP::File->new('filename' => $logFile)->copyFile($wrkLogFile) if -f $logFile;
		return 1 if $rs;

		tie my @content, 'Tie::File', $wrkLogFile or return 0;

		# Saving last line number and content from the current working file
		$self->{'logDb'}->{'line'} = $#content;
		$self->{'logDb'}->{'content'} = $content[$#content];

		# Test for logrotation
		if($content[$lastLineNo] && $content[$lastLineNo] eq $lastLine){
			# No logrotation occured. We want parse only new lines so we skip those already processed
			(tied @content)->defer;
			@content = @content[$lastLineNo + 1 .. $#content];
			(tied @content)->flush;
		}

		# Reading log file
		my $content = iMSCP::File->new('filename' => $wrkLogFile)->get() || '';

		# Getting IMAP traffic from working log file
		#
		# IMAP traffic line sample (< Dovecot 1.2.1)
		#
		# Sep 13 20:11:27 imscp dovecot: IMAP(user@domain.tld): Disconnected: Logged out bytes=244/850
		#
		# IMAP traffuc line sample (>= Dovecot 1.2.1)
		#
		# Sep 13 22:06:09 imscp dovecot: imap(user@domain.tld): Disconnected: Logged out in=244 out=858
		#
		while($content =~ /^.*imap\([^\@]+\@([^\)]+)\):\sDisconnected:.*(?:bytes|in)=(\d+)(?:\/|\sout=)(\d+)$/gmi) {
			$self->{'traffic'}->{$1} += $2 + $3;
		}

		# Getting POP traffic from working log file
		#
		# POP traffic Line sample
		#
		# Sep 13 20:14:16 imscp dovecot: POP3(user@domain.tld): Disconnected: Logged out top=1/3214, retr=0/0, del=0/1, size=27510
		#
		while($content =~ /^.*pop3\([^\@]+\@([^\)]+)\):\sDisconnected:.*retr=(\d+)\/(\d+).*$/gmi) {
			$self->{'traffic'}->{$1} += $2 + $3;
		}
	}

	$self->{'hooksManager'}->trigger('afterPoGetTraffic', $domainName) and return 0;

	(exists $self->{'traffic'}->{$domainName}) ? $self->{'traffic'}->{$domainName} : 0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Called by getInstance(). Initialize instance.

 Return Servers::po::dovecot

=cut

sub _init
{
	my $self = shift;

	$self->{'hooksManager'} = iMSCP::HooksManager->getInstance();

	$self->{'hooksManager'}->trigger(
		'beforePoInit', $self, 'dovecot'
	) and fatal('dovecot - beforePoInit hook has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/dovecot";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	tie %{$self->{'config'}}, 'iMSCP::Config','fileName' => "$self->{'cfgDir'}/dovecot.data";

	$self->{'hooksManager'}->trigger(
		'afterPoInit', $self, 'dovecot'
	) and fatal('dovecot - afterPoInit hook has failed');

	$self;
}

=item END

 Code triggered at the very end of script execution.

-  Restart server if needed
 - Remove old traffic logs file if exists

 Return int Exit code

=cut

END
{
	my $exitCode = $?;
	my $self = Servers::po::dovecot->getInstance();
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
