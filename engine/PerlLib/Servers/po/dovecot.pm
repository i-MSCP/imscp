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

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	require Servers::po::dovecot::installer;
	Servers::po::dovecot::installer->getInstance()->install();
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = $_[0];

	my $rs = $self->{'eventManager'}->trigger('beforePoPostinstall', 'dovecot');
	return $rs if $rs;

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
	my $self = $_[0];

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
	my $self = $_[0];

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
	my $self = $_[0];

	my $variableDataDir = $main::imscpConfig{'VARIABLE_DATA_DIR'};

	# Load traffic database
	tie my %trafficDb, 'iMSCP::Config', fileName => "$variableDataDir/po_traffic.db", nowarn => 1;

	# Data source file
	my $trafficDataSrc = "$main::imscpConfig{'TRAFF_LOG_DIR'}/$main::imscpConfig{'MAIL_TRAFF_LOG'}";

	if(-f $trafficDataSrc && -s _) {
		my $wrkLogFile = "$main::imscpConfig{'LOG_DIR'}/mail.po.log";

		# We are using a small file to memorize the number of the last line that has been read and his content
		tie my %indexDb, 'iMSCP::Config', fileName => "$variableDataDir/traffic_index.db", nowarn => 1;

		$indexDb{'po_lineNo'} = 0 unless $indexDb{'po_lineNo'};
		$indexDb{'po_lineContent'} = '' unless $indexDb{'po_lineContent'};

		my $lastLineNo = $indexDb{'po_lineNo'};
		my $lastlineContent = $indexDb{'po_lineContent'};

		# Creating working file from current state of upstream data source
		my $rs = iMSCP::File->new( filename => $trafficDataSrc )->copyFile( $wrkLogFile, { 'preserve' => 'no' } );
		die(iMSCP::Debug::getLastError()) if $rs;

		tie my @content, 'Tie::File', $wrkLogFile or die("Unable to tie file $wrkLogFile: $!");

		# Saving last line number and line date content from the current working file
		$indexDb{'po_lineNo'} = $#content;
		$indexDb{'po_lineContent'} = $content[$#content];

		# Test for logrotation
		if($content[$lastLineNo] && $content[$lastLineNo] eq $lastlineContent) {
			# No logrotation occurred. We want parse only new lines so we skip those already processed
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
		# IMAP traffic line sample (< Dovecot 1.2.1)
		#
		# Sep 13 20:11:27 imscp dovecot: IMAP(user@domain.tld): Disconnected: Logged out bytes=244/850
		#
		# IMAP traffic line sample (>= Dovecot 1.2.1)
		#
		# Sep 13 22:06:09 imscp dovecot: imap(user@domain.tld): Disconnected: Logged out in=244 out=858
		#
		$trafficDb{$1} += $2 + $3 while(
			$wrkLogContent =~ /^.*imap\([^\@]+\@([^\)]+)\):\sDisconnected:.*(?:bytes|in)=(\d+)(?:\/|\sout=)(\d+)$/gimo
		);

		# Getting POP traffic
		#
		# POP traffic Line sample
		#
		# Sep 13 20:14:16 imscp dovecot: POP3(user@domain.tld): Disconnected: Logged out top=1/3214, retr=0/0, del=0/1, size=27510
		#
		$trafficDb{$1} += $2 + $3 while(
			$wrkLogContent =~ /^.*pop3\([^\@]+\@([^\)]+)\):\sDisconnected:.*retr=(\d+)\/(\d+).*$/gimo
		);
	}

	# Schedule deletion of traffic database. This is only done on success. On failure, the traffic database is kept
	# in place for later processing. In such case, data already processed (put in database) are zeroed by the
	# traffic processor script.
	$self->{'eventManager'}->register(
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

 Initialize instance

 Return Servers::po::dovecot

=cut

sub _init
{
	my $self = $_[0];

	$self->{'restart'} = 0;

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();

	$self->{'eventManager'}->trigger(
		'beforePoInit', $self, 'dovecot'
	) and fatal('dovecot - beforePoInit has failed');

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/dovecot";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'config'} = lazy { tie my %c, 'iMSCP::Config',fileName => "$self->{'cfgDir'}/dovecot.data"; \%c; };

	$self->{'eventManager'}->trigger(
		'afterPoInit', $self, 'dovecot'
	) and fatal('dovecot - afterPoInit has failed');

	$self;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
