#!/usr/bin/perl

=head1 NAME

 Servers::po::courier - i-MSCP Courier IMAP/POP3 Server implementation

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

package Servers::po::courier;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Config;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::Service;
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

sub registerSetupHooks($$)
{
	my ($self, $hooksManager) = @_;

	require Servers::po::courier::installer;
	Servers::po::courier::installer->getInstance()->registerSetupHooks($hooksManager);
}

=item preinstall()

 Process preinstall tasks.

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = $_[0];

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
	require Servers::po::courier::installer;
	Servers::po::courier::installer->getInstance()->install();
}

=item postinstall()

 Process postinstall tasks.

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforePoPostinstall', 'courier');
	return $rs if $rs;

	$self->{'hooksManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->start(); }, 'IMAP/POP3' ]; 0; }
	);

	$self->{'hooksManager'}->trigger('afterPoPostinstall', 'courier');
}

=item uninstall()

 Process uninstall tasks.

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforePoUninstall', 'courier');
	return $rs if $rs;

	require Servers::po::courier::uninstaller;

	$rs = Servers::po::courier::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$rs = $self->restart();
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterPoUninstall', 'courier');
}

=item setEnginePermissions()

 Set permissions.

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	require Servers::po::courier::installer;
	Servers::po::courier::installer->getInstance()->setEnginePermissions();
}

=item postaddMail()

 Create maildir folders, subscription and maildirsize files.

 Return int 0 on success, other on failure

=cut

sub postaddMail($$)
{
	my ($self, $data) = @_;

	if($data->{'MAIL_TYPE'} =~ /_mail/) {
		# Getting i-MSCP MTA server implementation instance
		require Servers::mta;
		my $mta = Servers::mta->factory();

		my $mailDir = "$mta->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}";
		my $mailUidName =  $mta->{'config'}->{'MTA_MAILBOX_UID_NAME'};
		my $mailGidName = $mta->{'config'}->{'MTA_MAILBOX_GID_NAME'};

		for ("$mailDir/.Drafts", "$mailDir/.Junk", "$mailDir/.Sent", "$mailDir/.Trash") {

			# Creating maildir directory or only set its permissions if already exists
			my $rs = iMSCP::Dir->new('dirname' => $_)->make(
				{ 'user' => $mailUidName, 'group' => $mailGidName , 'mode' => 0750 }
			);
			return $rs if $rs;

			# Creating maildir sub folders (cur, new, tmp) or only set there permissions if they already exists
			for my $subdir ('cur', 'new', 'tmp') {
				my $rs = iMSCP::Dir->new('dirname' => "$_/$subdir")->make(
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

		my $rs = $courierimapsubscribedFile->set((join "\n", @subscribedFolders) . "\n");
		return $rs if $rs;

		$rs = $courierimapsubscribedFile->save();
		return $rs if $rs;

		$rs = $courierimapsubscribedFile->mode(0640);
		return $rs if $rs;

		$rs = $courierimapsubscribedFile->owner($mailUidName, $mailGidName);
		return $rs if $rs;

		if(defined($data->{'MAIL_QUOTA'}) && $data->{'MAIL_QUOTA'} != 0) {
			my @maildirmakeCmdArgs = (escapeShell("$data->{'MAIL_QUOTA'}S"), escapeShell("$mailDir"));

			my($stdout, $stderr);
			$rs = execute("$self->{'config'}->{'CMD_MAILDIRMAKE'} -q @maildirmakeCmdArgs", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;

			if(-f "$mailDir/maildirsize") {
				my $file = iMSCP::File->new('filename' => "$mailDir/maildirsize");

				$rs = $file->owner($mailUidName, $mailGidName);
				return $rs if $rs;

				$rs = $file->mode(0640);
				return $rs if $rs;
			}
		} elsif(-f "$mailDir/maildirsize") {
			$rs = iMSCP::File->new('filename' => "$mailDir/maildirsize")->delFile();
			return $rs if $rs;
		}
	}

	0;
}

=item start()

 Start courier servers.

 Return int 0 on success, other on failure

=cut

sub start
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforePoStart');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->start($self->{'config'}->{'AUTHDAEMON_SNAME'}, 'authdaemon');
	error("Unable to start $self->{'config'}->{'AUTHDAEMON_SNAME'} service") if $rs;
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->start($self->{'config'}->{'POPD_SNAME'}, '-f pop3d.pid');
	error("Unable to start $self->{'config'}->{'POPD_SNAME'} service") if $rs;
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->start($self->{'config'}->{'POPD_SSL_SNAME'}, '-f pop3d-ssl.pid');
	error("Unable to start $self->{'config'}->{'POPD_SSL_SNAME'} service") if $rs;
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->start($self->{'config'}->{'IMAPD_SNAME'}, '-f imapd.pid');
	error("Unable to start $self->{'config'}->{'IMAPD_SNAME'} service") if $rs;
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->start($self->{'config'}->{'IMAPD_SSL_SNAME'}, '-f imapd-ssl.pid');
	error("Unable to start $self->{'config'}->{'IMAPD_SSL_SNAME'} service") if $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterPoStart');
}

=item stop()

 Stop courier servers.

 Return int 0 on success, other on failure

=cut

sub stop
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforePoStop');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->stop($self->{'config'}->{'AUTHDAEMON_SNAME'}, 'authdaemon');
	error("Unable to stop $self->{'config'}->{'AUTHDAEMON_SNAME'} service") if $rs;
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->stop($self->{'config'}->{'POPD_SNAME'}, '-f pop3d.pid');
	error("Unable to stop $self->{'config'}->{'POPD_SNAME'} service") if $rs;
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->stop($self->{'config'}->{'POPD_SSL_SNAME'}, '-f pop3d-ssl.pid');
	error("Unable to stop $self->{'config'}->{'POPD_SSL_SNAME'} service") if $rs;
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->stop($self->{'config'}->{'IMAPD_SNAME'}, '-f imapd.pid');
	error("Unable to stop $self->{'config'}->{'IMAPD_SNAME'} service") if $rs;
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->stop($self->{'config'}->{'IMAPD_SSL_SNAME'}, '-f imapd-ssl.pid');
	error("Unable to stop $self->{'config'}->{'IMAPD_SSL_SNAME'} service") if $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterPoStop');
}

=item restart()

 Restart courier servers.

 Return int 0 on success, other on failure

=cut

sub restart
{
	my $self = $_[0];

	my $rs = $self->{'hooksManager'}->trigger('beforePoRestart');
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->restart($self->{'config'}->{'AUTHDAEMON_SNAME'}, 'authdaemon');
	error("Unable to restart $self->{'config'}->{'AUTHDAEMON_SNAME'} service") if $rs;
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->restart($self->{'config'}->{'POPD_SNAME'}, '-f pop3d.pid');
	error("Unable to restart $self->{'config'}->{'POPD_SNAME'} service") if $rs;
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->restart($self->{'config'}->{'POPD_SSL_SNAME'}, '-f pop3d-ssl.pid');
	error("Unable to restart $self->{'config'}->{'POPD_SSL_SNAME'} service") if $rs;
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->restart($self->{'config'}->{'IMAPD_SNAME'}, '-f imapd.pid');
	error("Unable to restart $self->{'config'}->{'IMAPD_SNAME'} service") if $rs;
	return $rs if $rs;

	$rs = iMSCP::Service->getInstance()->restart($self->{'config'}->{'IMAPD_SSL_SNAME'}, '-f imapd-ssl.pid');
	error("Unable to restart $self->{'config'}->{'IMAPD_SSL_SNAME'} service") if $rs;
	return $rs if $rs;

	$self->{'hooksManager'}->trigger('afterPoRestart');
}

=item getTraffic()

 Get IMAP/POP traffic data

 Return hash_ref Traffic data or die on failure

=cut

sub getTraffic
{
	my $self = $_[0];

	my $variableDataDir = $main::imscpConfig{'VARIABLE_DATA_DIR'};

	# Load traffic database
	tie my %trafficDb, 'iMSCP::Config', 'fileName' => "$variableDataDir/po_traffic.db", 'noerrors' => 1;

	my $trafficLogFile = "$main::imscpConfig{'TRAFF_LOG_DIR'}/$main::imscpConfig{'MAIL_TRAFF_LOG'}";

	if(-f $trafficLogFile && -s _) {
		my $wrkLogFile = "$main::imscpConfig{'LOG_DIR'}/mail.po.log";

		# We are using a small file to memorize the number of the last line that has been read and his content
		tie my %indexDb, 'iMSCP::Config', 'fileName' => "$variableDataDir/traffic_index.db", 'noerrors' => 1;

		$indexDb{'po_lineNo'} = 0 unless $indexDb{'po_lineNo'};
		$indexDb{'po_lineContent'} = '' unless $indexDb{'po_lineContent'};

		my $lastLineNo = $indexDb{'po_lineNo'};
		my $lastlineContent = $indexDb{'po_lineContent'};

		# Creating working file from current state of upstream data source
		my $rs = iMSCP::File->new('filename' => $trafficLogFile)->copyFile($wrkLogFile, { 'preserve' => 'no' });
		die(iMSCP::Debug::getLastError()) if $rs;

		require Tie::File;
		tie my @content, 'Tie::File', $wrkLogFile or die("Unable to tie file $wrkLogFile");

		# Saving last line number and line date content from the current working file
		$indexDb{'po_lineNo'} = $#content;
		$indexDb{'po_lineContent'} = $content[$#content];

		# Test for logrotation
		if($content[$lastLineNo] && $content[$lastLineNo] eq $lastlineContent) {
			# No logrotation occured. We want parse only new lines so we skip those already processed
			(tied @content)->defer;
			@content = @content[$lastLineNo + 1 .. $#content];
			(tied @content)->flush;
		}

		# TODO: Parse the last rotated mail.log (i.e mail.log.1) file to cover the case where a rotation has been made.
		# This should allow to retrieve traffic data logged between the last collect and the log rotation. Those data
		# are currently lost because they are never collected.

		my $wrkLogContent = iMSCP::File->new('filename' => $wrkLogFile)->get();
		die(iMSCP::Debug::getLastError()) unless defined $wrkLogContent;

		# Getting IMAP traffic
		#
		# Important consideration for both IMAP and POP traffic accounting with courier
		#
		# Courier distinguishes header, body, received and sent bytes fields. Clearly, header and body fields can be zero
		# while there is still some traffic. But more importantly, body gives only the bytes of messages sent.
		#
		# Here, we want count all traffic so we take sum of the received and sent bytes only.
		#
		# IMAP traffic line sample
		#
		# Oct 15 12:56:42 imscp imapd: LOGOUT, user=user@domain.tld, ip=[::ffff:192.168.1.2], headers=0, body=0, rcvd=172, sent=310, time=205
		#
		while($wrkLogContent =~ m/^.*(?:imapd|imapd\-ssl).*user=[^\@]*\@([^,]*),\sip=\[([^\]]+)\],\sheaders=\d+,\sbody=\d+,\srcvd=(\d+),\ssent=(\d+),.*$/gimo) {
			if(not $2 ~~ ['localhost', '127.0.0.1', '::ffff:127.0.0.1']) {
				$trafficDb{$1} += $3 + $4;
			}
		}

		# Getting POP traffic
		#
		# POP traffic line sample
		#
		# Oct 15 14:54:06 imscp pop3d: LOGOUT, user=user@domain.tld, ip=[::ffff:192.168.1.2], port=[41477], top=0, retr=0, rcvd=32, sent=147, time=0, stls=1
		# Oct 15 14:51:12 imscp pop3d-ssl: LOGOUT, user=user@domain.tld, ip=[::ffff:192.168.1.2], port=[41254], top=0, retr=496, rcvd=32, sent=672, time=0, stls=1
		#
		# Note: courierpop3login is for Debian. pop3d for Fedora.
		#
		while($wrkLogContent =~ m/^.*(?:courierpop3login|pop3d|pop3d-ssl).*user=[^\@]*\@([^,]*),\sip=\[([^\]]+)\].*\stop=\d+,\sretr=\d+,\srcvd=(\d+),\ssent=(\d+),.*$/gimo) {
			if(not $2 ~~ ['localhost', '127.0.0.1', '::ffff:127.0.0.1']) {
				$trafficDb{$1} += $3 + $4;
			}
		}
	}

	# Schedule deletion of traffic database. This is only done on success. On failure, the traffic database is kept
	# in place for later processing. In such case, data already processed (put in database) are zeroed by the
	# traffic processor script.
	$self->{'hooksManager'}->register(
		'afterVrlTraffic',
		sub {
			if(-f "$variableDataDir/po_traffic.db") {
				iMSCP::File->new('filename' => "$variableDataDir/po_traffic.db")->delFile();
			} else {
				0;
			}
		}
	) and die(iMSCP::Debug::getLastError());

	\%trafficDb;
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
	my $self = $_[0];

	$self->{'restart'} = 0;

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

 - Start or restart server if needed
 - Remove old traffic logs file if exists

 Return int Exit code

=cut

END
{
	unless($main::execmode && $main::execmode eq 'setup') {
		my $exitCode = $?;
		my $self = Servers::po::courier->getInstance();
		my $rs = 0;

		$rs = $self->restart() if $self->{'restart'};

		$? = $exitCode || $rs;
	}
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
