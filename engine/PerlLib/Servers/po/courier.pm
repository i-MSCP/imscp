=head1 NAME

 Servers::po::courier - i-MSCP Courier IMAP/POP3 Server implementation

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

package Servers::po::courier;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Config;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::Service;
use Servers::mta;
use Tie::File;
use Scalar::Defer;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Courier IMAP/POP3 Server implementation.

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

	require Servers::po::courier::installer;
	Servers::po::courier::installer->getInstance()->registerSetupListeners($eventManager);
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoPreinstall', 'courier');
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterPoPreinstall', 'courier');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoInstall', 'courier');
	return $rs if $rs;

	require Servers::po::courier::installer;
	$rs = Servers::po::courier::installer->getInstance()->install();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterPoInstall', 'courier');
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoPostinstall', 'courier');
	return $rs if $rs;

	local $@;
	eval {
		my $serviceMngr = iMSCP::Service->getInstance();

		for my $service('AUTHDAEMON_SNAME', 'POPD_SNAME', 'IMAPD_SNAME') {
			$serviceMngr->restart($self->{'config'}->{$service});
		}

		if($main::imscpConfig{'SERVICES_SSL_ENABLED'} eq 'yes') {
			for my $service('POPD_SSL_SNAME', 'IMAPD_SSL_SNAME') {
				$serviceMngr->enable($self->{'config'}->{$service});
			}
		} else {
			for my $service('POPD_SSL_SNAME', 'IMAPD_SSL_SNAME') {
				$serviceMngr->stop($self->{'config'}->{$service});
				$serviceMngr->disable($self->{'config'}->{$service});
			}
		}
	};
	if($@) {
		error($@);
		return 1;
	}

	$self->{'eventManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->restart(); }, 'Courier' ]; 0; }
	);

	$self->{'eventManager'}->trigger('afterPoPostinstall', 'courier');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoUninstall', 'courier');
	return $rs if $rs;

	require Servers::po::courier::uninstaller;

	$rs = Servers::po::courier::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$rs = $self->restart();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterPoUninstall', 'courier');
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoSetEnginePermissions');
	return $rs if $rs;

	require Servers::po::courier::installer;
	$rs = Servers::po::courier::installer->getInstance()->setEnginePermissions();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterPoSetEnginePermissions');
}

=item postaddMail(\%data)

 Process postaddMail tasks

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub postaddMail
{
	my ($self, $data) = @_;

	if($data->{'MAIL_TYPE'} =~ /_mail/) {
		my $mta = Servers::mta->factory();

		my $mailDir = "$mta->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}";
		my $mailUidName =  $mta->{'config'}->{'MTA_MAILBOX_UID_NAME'};
		my $mailGidName = $mta->{'config'}->{'MTA_MAILBOX_GID_NAME'};

		for my $dir("$mailDir/.Drafts", "$mailDir/.Junk", "$mailDir/.Sent", "$mailDir/.Trash") {
			my $rs = iMSCP::Dir->new( dirname => $dir )->make({
				user => $mailUidName, group => $mailGidName , mode => 0750
			});
			return $rs if $rs;

			for my $subdir ('cur', 'new', 'tmp') {
				my $rs = iMSCP::Dir->new( dirname => "$dir/$subdir" )->make({
					user => $mailUidName, group => $mailGidName, mode => 0750
				});
				return $rs if $rs;
			}
		}

		my @subscribedFolders = ('INBOX.Drafts', 'INBOX.Junk', 'INBOX.Sent', 'INBOX.Trash');
		my $courierimapsubscribedFile = iMSCP::File->new( filename => "$mailDir/courierimapsubscribed" );

		if(-f "$mailDir/courierimapsubscribed") {
			my $courierimapsubscribedFileContent = $courierimapsubscribedFile->get();

			unless(defined $courierimapsubscribedFileContent) {
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
			$rs = execute("maildirmake -q @maildirmakeCmdArgs", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;

			if(-f "$mailDir/maildirsize") {
				my $file = iMSCP::File->new( filename => "$mailDir/maildirsize" );

				$rs = $file->owner($mailUidName, $mailGidName);
				return $rs if $rs;

				$rs = $file->mode(0640);
				return $rs if $rs;
			}
		} elsif(-f "$mailDir/maildirsize") {
			$rs = iMSCP::File->new( filename => "$mailDir/maildirsize" )->delFile();
			return $rs if $rs;
		}
	}

	0;
}

=item start()

 Start courier servers

 Return int 0 on success, other on failure

=cut

sub start
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoStart');
	return $rs if $rs;

	local $@;
	eval {
		my $serviceMngr = iMSCP::Service->getInstance();

		for my $service('AUTHDAEMON_SNAME', 'POPD_SNAME', 'IMAPD_SNAME') {
			$serviceMngr->restart($self->{'config'}->{$service});
		}

		if($main::imscpConfig{'SERVICES_SSL_ENABLED'} eq 'yes') {
			for my $service('POPD_SSL_SNAME', 'IMAPD_SSL_SNAME') {
				$serviceMngr->start($self->{'config'}->{$service});
			}
		}
	};
	if($@) {
		error($@);
		return 1;
	}

	$self->{'eventManager'}->trigger('afterPoStart');
}

=item stop()

 Stop courier servers

 Return int 0 on success, other on failure

=cut

sub stop
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoStop');
	return $rs if $rs;

	local $@;
	eval {
		my $serviceMngr = iMSCP::Service->getInstance();
		for my $service('AUTHDAEMON_SNAME', 'POPD_SNAME', 'POPD_SSL_SNAME', 'IMAPD_SNAME', 'IMAPD_SSL_SNAME') {
			$serviceMngr->stop($self->{'config'}->{$service});
		}
	};
	if($@) {
		error($@);
		return 1;
	}

	$self->{'eventManager'}->trigger('afterPoStop');
}

=item restart()

 Restart courier servers

 Return int 0 on success, other on failure

=cut

sub restart
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoRestart');
	return $rs if $rs;

	local $@;
	eval {
		my $serviceMngr = iMSCP::Service->getInstance();

		for my $service('AUTHDAEMON_SNAME', 'POPD_SNAME', 'IMAPD_SNAME') {
			$serviceMngr->restart($self->{'config'}->{$service});
		}

		if($main::imscpConfig{'SERVICES_SSL_ENABLED'} eq 'yes') {
			for my $service('POPD_SSL_SNAME', 'IMAPD_SSL_SNAME') {
				$serviceMngr->restart($self->{'config'}->{$service});
			}
		}
	};
	if($@) {
		error($@);
		return 1;
	}

	$self->{'eventManager'}->trigger('afterPoRestart');
}

=item getTraffic()

 Get IMAP/POP traffic data

 Return hash Traffic data or die on failure

=cut

sub getTraffic
{
	my ($self, $trafficDataSrc, $trafficDb) = @_;

	require File::Temp;

	my $variableDataDir = $main::imscpConfig{'VARIABLE_DATA_DIR'};
	my $trafficDbPath = "$variableDataDir/po_traffic.db";
	my $selfCall = 1;
	my %trafficDb;

	# Load traffic database
	unless(ref $trafficDb eq 'HASH') {
		tie %trafficDb, 'iMSCP::Config', fileName => $trafficDbPath, nowarn => 1;
		$selfCall = 0;
	} else {
		%trafficDb = %{$trafficDb};
	}

	# Data source file
	$trafficDataSrc ||= "$main::imscpConfig{'TRAFF_LOG_DIR'}/$main::imscpConfig{'MAIL_TRAFF_LOG'}";

	if(-f $trafficDataSrc) {
		# We are using a small file to memorize the number of the last line that has been read and his content
		tie my %indexDb, 'iMSCP::Config', fileName => "$variableDataDir/traffic_index.db", nowarn => 1;

		my $lastParsedLineNo = $indexDb{'po_lineNo'} || 0;
		my $lastParsedLineContent = $indexDb{'po_lineContent'} || '';

		# Create a snapshot of log file to process
		my $tpmFile1 = File::Temp->new();
		my $rs = iMSCP::File->new( filename => $trafficDataSrc )->copyFile( $tpmFile1, { preserve => 'no' } );
		die(iMSCP::Debug::getLastError()) if $rs;

		tie my @content, 'Tie::File', $tpmFile1 or die("Unable to tie $tpmFile1");

		unless($selfCall) {
			# Saving last processed line number and line content
			$indexDb{'po_lineNo'} = $#content;
			$indexDb{'po_lineContent'} = $content[$#content];
		}

		if($content[$lastParsedLineNo] && $content[$lastParsedLineNo] eq $lastParsedLineContent) {
			# Skip lines which were already processed
			(tied @content)->defer;
			@content = @content[$lastParsedLineNo + 1 .. $#content];
			(tied @content)->flush;
		} elsif(!$selfCall) {
			debug(sprintf('Log rotation has been detected. Processing %s first...', "$trafficDataSrc.1"));
			%trafficDb = %{$self->getTraffic("$trafficDataSrc.1", \%trafficDb)};
			$lastParsedLineNo = 0;
		}

		debug(sprintf('Processing lines from %s, starting at line %d', $trafficDataSrc, $lastParsedLineNo));

		if(@content) {
			untie @content;

			# Read and parse IMAP/POP traffic source file (line by line)
			while(<$tpmFile1>) {
				# Extract traffic data ( IMAP )
				#
				# Important consideration for both IMAP and POP traffic accounting with courier
				#
				# Courier distinguishes header, body, received and sent bytes fields. Clearly, header and body fields can be zero
				# while there is still some traffic. But more importantly, body gives only the bytes of messages sent.
				#
				# Here, we want count all traffic so we take sum of the received and sent bytes only.
				#
				# IMAP traffic line sample
				# Oct 15 12:56:42 imscp imapd: LOGOUT, user=user@domain.tld, ip=[::ffff:192.168.1.2], headers=0, body=0, rcvd=172, sent=310, time=205
				if(m/^.*(?:imapd|imapd\-ssl).*user=[^\@]*\@([^,]*),\sip=\[([^\]]+)\],\sheaders=\d+,\sbody=\d+,\srcvd=(\d+),\ssent=(\d+),.*$/gimo && not $2 ~~ ['localhost', '127.0.0.1', '::ffff:127.0.0.1']) {
					$trafficDb{$1} += $3 + $4;
					next;
				}

				# Extract traffic data ( POP3 )
				#
				# POP traffic line sample
				#
				# Oct 15 14:54:06 imscp pop3d: LOGOUT, user=user@domain.tld, ip=[::ffff:192.168.1.2], port=[41477], top=0, retr=0, rcvd=32, sent=147, time=0, stls=1
				# Oct 15 14:51:12 imscp pop3d-ssl: LOGOUT, user=user@domain.tld, ip=[::ffff:192.168.1.2], port=[41254], top=0, retr=496, rcvd=32, sent=672, time=0, stls=1
				#
				# Note: courierpop3login is for Debian. pop3d for Fedora.
				$trafficDb{$1} += $3 + $4 if m/^.*(?:courierpop3login|pop3d|pop3d-ssl).*user=[^\@]*\@([^,]*),\sip=\[([^\]]+)\].*\stop=\d+,\sretr=\d+,\srcvd=(\d+),\ssent=(\d+),.*$/gimo && not $2 ~~ ['localhost', '127.0.0.1', '::ffff:127.0.0.1'];
			}
		} else {
			debug(sprintf('No new content found in %s - Skipping', $trafficDataSrc));
			untie @content;
		}
	} elsif(!$selfCall) {
		debug(sprintf('Log rotation has been detected. Processing %s...', "$trafficDataSrc.1"));
		%trafficDb = %{$self->getTraffic("$trafficDataSrc.1", \%trafficDb)};
	}

	# Schedule deletion of traffic database. This is only done on success. On failure, the traffic database is kept
	# in place for later processing. In such case, data already processed are zeroed by the traffic processor script.
	$self->{'eventManager'}->register(
		'afterVrlTraffic', sub { (-f $trafficDbPath) ? iMSCP::File->new( filename => $trafficDbPath )->delFile() : 0; }
	) unless $selfCall;

	\%trafficDb;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::po::courier

=cut

sub _init
{
	my $self = shift;

	$self->{'restart'} = 0;
	$self->{'eventManager'} = iMSCP::EventManager->getInstance();
	$self->{'eventManager'}->trigger('beforePoInit', $self, 'courier') and fatal('courier - beforePoInit has failed');
	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/courier";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'config'} = lazy { tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/courier.data"; \%c; };
	$self->{'eventManager'}->trigger('afterPoInit', $self, 'courier') and fatal('courier - afterPoInit has failed');

	$self;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
