=head1 NAME

 Servers::po::dovecot - i-MSCP Dovecot IMAP/POP3 Server implementation

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

package Servers::po::dovecot;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Service;
use Servers::mta;
use Tie::File;
use Scalar::Defer;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Dovecot IMAP/POP3 Server implementation.

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

	require Servers::po::dovecot::installer;
	Servers::po::dovecot::installer->getInstance()->registerSetupListeners($eventManager);
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoPreinstall', 'dovecot');
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterPoPreinstall', 'dovecot');
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoInstall', 'dovecot');
	return $rs if $rs;

	require Servers::po::dovecot::installer;
	$rs = Servers::po::dovecot::installer->getInstance()->install();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterPoInstall', 'dovecot');
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoPostinstall', 'dovecot');
	return $rs if $rs;

	local $@;
	eval { iMSCP::Service->getInstance()->enable($self->{'config'}->{'DOVECOT_SNAME'}); };
	if($@) {
		error($@);
		return 1;
	}

	$self->{'eventManager'}->register(
		'beforeSetupRestartServices', sub { push @{$_[0]}, [ sub { $self->restart(); }, 'Dovecot' ]; 0; }
	);

	$self->{'eventManager'}->trigger('afterPoPostinstall', 'dovecot');
}

=item postaddMail(\%data)

 Process postaddMail tasks

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub postaddMail
{
	my ($self, $data) = @_;

	my $rs = 0;

	if($data->{'MAIL_TYPE'} =~ /_mail/) {
		my $mta = Servers::mta->factory();
		my $mailDir = "$mta->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}";
		my $mailUidName =  $mta->{'config'}->{'MTA_MAILBOX_UID_NAME'};
		my $mailGidName = $mta->{'config'}->{'MTA_MAILBOX_GID_NAME'};

		for my $dir("$mailDir/.Drafts", "$mailDir/.Junk", "$mailDir/.Sent", "$mailDir/.Trash") {
			my $rs = iMSCP::Dir->new( dirname => $dir)->make({
				user => $mailUidName, group => $mailGidName , mode => 0750
			});
			return $rs if $rs;

			for my $subdir ('cur', 'new', 'tmp') {
				$rs = iMSCP::Dir->new( dirname => "$dir/$subdir" )->make({
					user => $mailUidName, group => $mailGidName, mode => 0750
				});
				return $rs if $rs;
			}
		}

		my @subscribedFolders = ('Drafts', 'Junk', 'Sent', 'Trash');
		my $subscriptionsFile = iMSCP::File->new( filename => "$mailDir/subscriptions" );

		if(-f "$mailDir/subscriptions") {
			my $subscriptionsFileContent = $subscriptionsFile->get();
			unless(defined $subscriptionsFileContent) {
				error('Unable to read dovecot subscriptions file');
				return 1;
			}

			if($subscriptionsFileContent ne '') {
				@subscribedFolders = (@subscribedFolders, split("\n", $subscriptionsFileContent));
				require List::MoreUtils;
				@subscribedFolders = sort(List::MoreUtils::uniq(@subscribedFolders));
			}
		}

		my $rs = $subscriptionsFile->set((join "\n", @subscribedFolders) . "\n");
		return $rs if $rs;

		$rs = $subscriptionsFile->save();
		return $rs if $rs;

		$rs = $subscriptionsFile->mode(0640);
		return $rs if $rs;

		$rs = $subscriptionsFile->owner($mailUidName, $mailGidName);
		return $rs if $rs;

		if(-f "$mailDir/maildirsize") {
			$rs = iMSCP::File->new( filename => "$mailDir/maildirsize" )->delFile();
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

	my $rs = $self->{'eventManager'}->trigger('beforePoUninstall', 'dovecot');
	return $rs if $rs;

	require Servers::po::dovecot::uninstaller;

	$rs = Servers::po::dovecot::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$rs = $self->restart();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterPoUninstall', 'dovecot');
}

=item restart()

 Restart dovecot

 Return int 0 on success, other on failure

=cut

sub restart
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforePoRestart');
	return $rs if $rs;

	iMSCP::Service->getInstance()->restart($self->{'config'}->{'DOVECOT_SNAME'});

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
				# IMAP traffic (< Dovecot 1.2.1)
				# Sep 13 20:11:27 imscp dovecot: IMAP(user@domain.tld): Disconnected: Logged out bytes=244/850
				#
				# IMAP traffic (>= Dovecot 1.2.1)
				# Sep 13 22:06:09 imscp dovecot: imap(user@domain.tld): Disconnected: Logged out in=244 out=858
				if(/^.*imap\([^\@]+\@([^\)]+)\):\sDisconnected:.*(?:bytes|in)=(\d+)(?:\/|\sout=)(\d+)$/gimo) {
					$trafficDb{$1} += $2 + $3;
					next;
				}

				# POP traffic
				# Sep 13 20:14:16 imscp dovecot: POP3(user@domain.tld): Disconnected: Logged out top=1/3214, retr=0/0, del=0/1, size=27510
				$trafficDb{$1} += $2 + $3 if /^.*pop3\([^\@]+\@([^\)]+)\):\sDisconnected:.*retr=(\d+)\/(\d+).*$/gimo;
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
	) unless $selfCall;;

	\%trafficDb;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::po::dovecot

=cut

sub _init
{
	my $self = shift;

	$self->{'restart'} = 0;
	$self->{'eventManager'} = iMSCP::EventManager->getInstance();
	$self->{'eventManager'}->trigger('beforePoInit', $self, 'dovecot') and fatal('dovecot - beforePoInit has failed');
	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/dovecot";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'config'} = lazy { tie my %c, 'iMSCP::Config',fileName => "$self->{'cfgDir'}/dovecot.data"; \%c; };
	$self->{'eventManager'}->trigger('afterPoInit', $self, 'dovecot') and fatal('dovecot - afterPoInit has failed');

	$self;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
