=head1 NAME

 Servers::mta::postfix - i-MSCP Postfix MTA server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::mta::postfix;

use strict;
no strict 'refs';
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Service;
use FileCache maxopen => 70;
use Scalar::Defer;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Postfix MTA server implementation.

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

	require Servers::mta::postfix::installer;
	Servers::mta::postfix::installer->getInstance()->registerSetupListeners($eventManager);
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other or die on failure

=cut

sub preinstall
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeMtaPreInstall', 'postfix');
	$self->stop();

	require Servers::mta::postfix::installer;
	my $rs = Servers::mta::postfix::installer->getInstance()->preinstall();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterMtaPreInstall', 'postfix');
}

=item install()

 Process install tasks

 Return int 0 on success, other or die on failure

=cut

sub install
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeMtaInstall', 'postfix');

	require Servers::mta::postfix::installer;
	my $rs = Servers::mta::postfix::installer->getInstance()->install();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterMtaInstall', 'postfix');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other or die on failure

=cut

sub uninstall
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeMtaUninstall', 'postfix');

	require Servers::mta::postfix::uninstaller;
	my $rs = Servers::mta::postfix::uninstaller->getInstance()->uninstall();
	return $rs if $rs;

	$self->restart();
	$self->{'eventManager'}->trigger('afterMtaUninstall', 'postfix');
}

=item postinstall()

 Process postintall tasks

 Return int 0 on success, die on failure

=cut

sub postinstall
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeMtaPostinstall', 'postfix');
	iMSCP::Service->getInstance()->enable($self->{'config'}->{'MTA_SNAME'});

	$self->{'eventManager'}->register('beforeSetupRestartServices', sub { push @{$_[0]}, [
		sub {
			while(my($table, $type) = each(%{$self->{'postmap'}})) {
				$self->postmap($table, $type);
			}

			$self->restart();
		}
		,
		'Postfix mail server'
	]; 0; });

	$self->{'eventManager'}->trigger('afterMtaPostinstall', 'postfix');
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeMtaSetEnginePermissions');

	require Servers::mta::postfix::installer;
	my $rs = Servers::mta::postfix::installer->getInstance()->setEnginePermissions();
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterMtaSetEnginePermissions');
}

=item start()

 Start server

 Return int 0 on success, die on failure

=cut

sub start
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeMtaStart');
	iMSCP::Service->getInstance()->start($self->{'config'}->{'MTA_SNAME'});
	$self->{'eventManager'}->trigger('afterMtaStart');
}

=item restart()

 Restart server

 Return int 0 on success, die on failure

=cut

sub restart
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeMtaRestart');
	iMSCP::Service->getInstance()->restart($self->{'config'}->{'MTA_SNAME'});
	$self->{'eventManager'}->trigger('afterMtaRestart');
}

=item stop()

 Stop server

 Return int 0 on success, die on failure

=cut

sub stop
{
	my $self = shift;

	$self->{'eventManager'}->trigger('beforeMtaStop');
	iMSCP::Service->getInstance()->stop($self->{'config'}->{'MTA_SNAME'});
	$self->{'eventManager'}->trigger('afterMtaStop');
}

=item addDmn(\%data)

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, die on failure

=cut

sub addDmn
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeMtaAddDmn', $data);

	if ($data->{'EXTERNAL_MAIL'} eq 'domain') {
		$self->deleteTableEntry(qr/\Q$data->{'DOMAIN_NAME'}\E/, $self->{'config'}->{'MTA_VIRTUAL_DMN_MAP'});

		if ($data->{'DOMAIN_TYPE'} ~~ [ 'dmn', 'als' ]) {
			$self->addTableEntry(
				($data->{'EXTERNAL_MAIL'} eq 'wildcard') ? ".$data->{'DOMAIN_NAME'}" : $data->{'DOMAIN_NAME'},
				'OK',
				$self->{'config'}->{'MTA_RELAY_MAP'}
			);
		}
	} elsif ($data->{'EXTERNAL_MAIL'} eq 'wildcard') {
		if ($data->{'MAIL_ENABLED'}) {
			$self->addTableEntry(
				$data->{'DOMAIN_NAME'}, $data->{'DOMAIN_TYPE'}, $self->{'config'}->{'MTA_VIRTUAL_DMN_MAP'}
			);
		}

		if ($data->{'DOMAIN_TYPE'} ~~ [ 'dmn', 'als' ]) {
			$self->addTableEntry(
				($data->{'EXTERNAL_MAIL'} eq 'wildcard') ? ".$data->{'DOMAIN_NAME'}" : $data->{'DOMAIN_NAME'},
				'OK',
				$self->{'config'}->{'MTA_RELAY_MAP'}
			);
		}
	} elsif ($data->{'MAIL_ENABLED'}) {
		$self->addTableEntry(
			$data->{'DOMAIN_NAME'}, $data->{'DOMAIN_TYPE'}, $self->{'config'}->{'MTA_VIRTUAL_DMN_MAP'}
		);

		if ($data->{'DOMAIN_TYPE'} ~~ [ 'dmn', 'als' ]) {
			$self->deleteTableEntry(qr/.?\Q$data->{'DOMAIN_NAME'}\E/, $self->{'config'}->{'MTA_RELAY_MAP'});
		}
	} else {
		$self->disableDmn($data);
	}

	$self->{'eventManager'}->trigger('afterMtaAddDmn', $data);
}

=item disableDmn(\%data)

 Process disableDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, die on failure

=cut

sub disableDmn
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeMtaDisableDmn', $data);
	$self->deleteTableEntry(qr/\Q$data->{'DOMAIN_NAME'}\E/, $self->{'config'}->{'MTA_VIRTUAL_DMN_MAP'});

	if ($data->{'DOMAIN_TYPE'} ~~ [ 'dmn', 'als' ]) {
		$self->deleteTableEntry(qr/.?\Q$data->{'DOMAIN_NAME'}\E/, $self->{'config'}->{'MTA_RELAY_MAP'});
	}

	$self->{'eventManager'}->trigger('afterMtaDisableDmn', $data);
}

=item deleteDmn(\%data)

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, die on failure

=cut

sub deleteDmn
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeMtaDelDmn', $data);
	$self->deleteTableEntry(qr/\Q$data->{'DOMAIN_NAME'}\E/, $self->{'config'}->{'MTA_VIRTUAL_DMN_MAP'});

	if ($data->{'DOMAIN_TYPE'} ~~ [ 'dmn', 'als' ]) {
		$self->deleteTableEntry(qr/.?\Q$data->{'DOMAIN_NAME'}\E/, $self->{'config'}->{'MTA_RELAY_MAP'});
	}

	iMSCP::Dir->new( dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}" )->remove();
	$self->{'eventManager'}->trigger('afterMtaDelDmn', $data);
}

=item addSub(\%data)

 Process addSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, die on failure

=cut

sub addSub
{
	(shift)->addDmn(@_);
}

=item disableSub(\%data)

 Process disableSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, die on failure

=cut

sub disableSub
{
	(shift)->disableDmn(@_);
}

=item deleteSub(\%data)

 Process deleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, die on failure

=cut

sub deleteSub
{
	(shift)->deleteDmn(@_);
}

=item addMail(\%data)

 Process addMail tasks

 Param hash \%data Mail data
 Return int 0 on success, die on failure

=cut

sub addMail
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeMtaAddMail', $data);

	if (index($data->{'MAIL_TYPE'}, '_mail') != -1) { # Normal mail account
		my $mailDir = "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}";
		my $mailUidName = $self->{'config'}->{'MTA_MAILBOX_UID_NAME'};
		my $mailGidName = $self->{'config'}->{'MTA_MAILBOX_GID_NAME'};

		iMSCP::Dir->new( dirname => $mailDir )->make({
			user => $self->{'config'}->{'MTA_MAILBOX_UID_NAME'},
			group => $self->{'config'}->{'MTA_MAILBOX_GID_NAME'},
			mode => 0750
		});

		for my $dir(qw/cur new tmp/) {
			iMSCP::Dir->new( dirname => "$mailDir/$dir" )->make({
				user => $mailUidName, group => $mailGidName, mode => 0750
			});
		}

		$self->addTableEntry(
			$data->{'MAIL_ADDR'},
			"$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}/",
			$self->{'config'}->{'MTA_VIRTUAL_MAILBOX_MAP'}
		);
	} else {
		$self->deleteTableEntry(qr/\Q$data->{'MAIL_ADDR'}\E/, $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_MAP'});
		iMSCP::Dir->new(
			dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}"
		)->remove();
	}

	if (index($data->{'MAIL_TYPE'}, '_forward') != -1) {
		$self->_addAliasEntry($data);
	} else {
		$self->_deleteAliasEntry($data);
	}

#	if ($data->{'MAIL_DOMAIN_HAS_AUTO_RESPONDER'}) {
#		$self->addTableEntry("imscp-arpl.$data->{'DOMAIN_NAME'}", 'imscp-arpl:', $self->{'config'}->{'MTA_TRANSPORT_MAP'});
#	} else {
#		$self->deleteTableEntry(qr/\Qimscp-arpl.$data->{'DOMAIN_NAME'}\E/, $self->{'config'}->{'MTA_TRANSPORT_MAP'});
#	}

#	if ($data->{'MAIL_HAS_CATCH_ALL'}) {
#		$rs = $self->_addCatchAll($data);
#		return $rs if $rs;
#	} else {
#		$rs = $self->_deleteCatchAll($data);
#		return $rs if $rs;
#	}

	$self->{'eventManager'}->trigger('afterMtaAddMail', $data);
}

=item disableMail(\%data)

 Process disableMail tasks

 Param hash \%data Mail data
 Return int 0 on success, die on failure

=cut

sub disableMail
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeMtaDisableMail', $data);
	$self->deleteTableEntry(qr/\Q$data->{'MAIL_ADDR'}\E/, $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_MAP'});
	$self->deleteAliasEntry($data);

#	$self->deleteTableEntry(qr/\Qimscp-arpl.$data->{'DOMAIN_NAME'}\E/, $self->{'config'}->{'MTA_TRANSPORT_MAP'});
#	$rs = $self->_deleteCatchAll($data);
#	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterMtaDisableMail', $data);
}

=item deleteMail(\%data)

 Process deleteMail tasks

 Param hash \%data Mail data
 Return int 0 on success, die on failure

=cut

sub deleteMail
{
	my ($self, $data) = @_;

	$self->{'eventManager'}->trigger('beforeMtaDelMail', $data);

	if (index($data->{'MAIL_TYPE'}, '_mail') != -1) {
		$self->deleteTableEntry(qr/\Q$data->{'MAIL_ADDR'}\E/, $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_MAP'});
		iMSCP::Dir->new(
			dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}"
		)->remove();
	}

	$self->_deleteAliasEntry($data);

#	$self->deleteTableEntry(qr/\Qimscp-arpl.$data->{'DOMAIN_NAME'}\E/, $self->{'config'}->{'MTA_TRANSPORT_MAP'});
#	$rs = $self->_deleteCatchAll($data);
#	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterMtaDelMail', $data);
}

=item getTraffic()

 Get SMTP traffic

 Return hash Traffic data or die on failure

=cut

sub getTraffic
{
	my ($self, $trafficDataSrc, $trafficDb) = @_;

	my $variableDataDir = $main::imscpConfig{'VARIABLE_DATA_DIR'};
	my $trafficDbPath = "$variableDataDir/smtp_traffic.db";
	my $selfCall = 1;
	my %trafficDb;

	# Load traffic database
	unless (ref $trafficDb eq 'HASH') {
		tie %trafficDb, 'iMSCP::Config', fileName => $trafficDbPath, nowarn => 1;
		$selfCall = 0;
	} else {
		%trafficDb = %{$trafficDb};
	}

	# Data source file
	$trafficDataSrc ||= "$main::imscpConfig{'TRAFF_LOG_DIR'}/$main::imscpConfig{'MAIL_TRAFF_LOG'}";

	if (-f $trafficDataSrc) {
		# We are using a small file to memorize the number of the last line that has been read and his content
		tie my %indexDb, 'iMSCP::Config', fileName => "$variableDataDir/traffic_index.db", nowarn => 1;

		my $lastParsedLineNo = $indexDb{'smtp_lineNo'} || 0;
		my $lastParsedLineContent = $indexDb{'smtp_lineContent'} || '';

		# Create a snapshot of log file to process
		require File::Temp;
		my $tpmFile1 = File::Temp->new();
		iMSCP::File->new( filename => $trafficDataSrc )->copyFile( $tpmFile1, { preserve => 'no' } );

		require Tie::File;
		tie my @content, 'Tie::File', $tpmFile1 or die("Unable to tie $tpmFile1");

		unless ($selfCall) {
			# Saving last processed line number and line content
			$indexDb{'smtp_lineNo'} = $#content;
			$indexDb{'smtp_lineContent'} = $content[$#content];
		}

		if ($content[$lastParsedLineNo] && $content[$lastParsedLineNo] eq $lastParsedLineContent) {
			# Skip lines which were already processed
			(tied @content)->defer;
			@content = @content[$lastParsedLineNo + 1 .. $#content];
			(tied @content)->flush;
		} elsif (!$selfCall) {
			debug(sprintf('Log rotation has been detected. Processing %s first...', "$trafficDataSrc.1"));
			%trafficDb = %{$self->getTraffic("$trafficDataSrc.1", \%trafficDb)};
			$lastParsedLineNo = 0;
		}

		debug(sprintf('Processing lines from %s, starting at line %d', $trafficDataSrc, $lastParsedLineNo));

		if (@content) {
			untie @content;

			# Extract postfix data
			my $tpmFile2 = File::Temp->new();

			my $rs = execute("grep postfix $tpmFile1 | maillogconvert.pl standard 1> $tpmFile2", undef, \my $stderr);
			die(sprintf('Could not extract postfix data: %s', $stderr)) if $rs;

			# Read and parse SMTP traffic source file (line by line)
			while(<$tpmFile2>) {
				if (/^[^\s]+\s[^\s]+\s[^\s\@]+\@([^\s]+)\s[^\s\@]+\@([^\s]+)\s([^\s]+)\s([^\s]+)\s[^\s]+\s[^\s]+\s[^\s]+\s(\d+)$/gimo) {
					if ($4 !~ /virtual/ && !($3 =~ /localhost|127.0.0.1/ && $4 =~ /localhost|127.0.0.1/)) {
						$trafficDb{$1} += $5;
						$trafficDb{$2} += $5;
					}
				}
			}
		} else {
			debug(sprintf('No new content found in %s - Skipping', $trafficDataSrc));
			untie @content;
		}
	} elsif (!$selfCall) {
		debug(sprintf('Log rotation has been detected. Processing %s...', "$trafficDataSrc.1"));
		%trafficDb = %{$self->getTraffic("$trafficDataSrc.1", \%trafficDb)};
	}

	# Schedule deletion of traffic database. This is only done on success. On failure, the traffic database is kept
	# in place for later processing. In such case, data already processed are zeroed by the traffic processor script.
	$self->{'eventManager'}->register('afterVrlTraffic', sub {
		-f $trafficDbPath ? iMSCP::File->new( filename => $trafficDbPath )->delFile() : 0
	}) unless $selfCall;

	\%trafficDb;
}

=item postmap($filename [, $filetype = cdb ])

 Postmap the given file

 Param string $filename Filename
 Param string $filetype OPTIONAL Filetype (btree, cdb, cidr, hash...)
 Return int 0 on success, die on failure

=cut

sub postmap
{
	my ($self, $filename, $filetype) = @_;

	$filetype ||= 'cdb';

	$self->{'eventManager'}->trigger('beforeMtaPostmap', \$filename, \$filetype);

	eval { cacheout '+<', $filename } or die(sprintf('Could not open %s: %s', $filename, $!));

	my $fh = __PACKAGE__ . "::${filename}";
	$fh->flush() or die(sprintf('Could not flush %s filehandle: %s', $filename, $!));

	my ($stdout, $stderr);
	!execute("postmap $filetype:$filename", \$stdout, \$stderr) or die(sprintf(
		'Could not postmap the %s map: %s', $filename, $stderr || 'Unknown error'
	));

	$self->{'eventManager'}->trigger('afterMtaPostmap', $filename, $filetype);
}

=item addTableEntry($key, $value, $table [, $tableType = 'cdb' ])

 Add the given entry to the given lookup table

 Param string $key Entry key
 Param string $value Entry value
 Param string $table Path of the table to operate on
 Päram string $tableType OPTIONAL Type of the table
 Return int 0 on success, die on failure

=cut

sub addTableEntry
{
	my ($self, $key, $value, $table, $tableType) = @_;

	eval { cacheout '+<', $table } or die(sprintf('Could not open %s: %s', $table, $!));
	my $fh = __PACKAGE__ . "::${table}";
	my $content = do { $fh->seek(0, 0); local $/; <$fh> };

	$content =~ s/^$key\t+[^\n]*\n//gm if defined $content;
	$content .= "$key\t$value\n";

	$fh->seek(0, 0);
	print $fh $content;
	$fh->truncate($fh->tell());

	$self->{'postmap'}->{$table} ||= $tableType || 'cdb';
	0;
}

=item deleteTableEntry($entry, $table [, $tableType = 'cdb' ])

 Delete the given entry from the given lookup table

 Param regexp $entry Regexp matching entry key
 Param string $table Path of the table to operate on
 Päram string $tableType OPTIONAL Type of the table
 Return int 0  on success, die on failure

=cut

sub deleteTableEntry
{
	my ($self, $entry, $table, $tableType) = @_;

	unless(defined $main::execmode && $main::execmode eq 'setup') {
		cacheout '+<', $table or die(sprintf('Could not open %s: %s', $table, $!));
		my $fh = __PACKAGE__ . "::${table}";
		my $content = do { $fh->seek(0, 0); local $/; <$fh> };

		if (defined $content && $content =~ s/^$entry\t+[^\n]*\n//gm) {
			$fh->seek(0, 0);
			print $fh $content;
			$fh->truncate($fh->tell());
			$self->{'postmap'}->{$table} ||= $tableType || 'cdb';
		}
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::mta::postfix

=cut

sub _init
{
	my $self = shift;

	$self->{'eventManager'} = iMSCP::EventManager->getInstance();
	$self->{'restart'} = 0;
	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/postfix";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'config'} = lazy { tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/postfix.data"; \%c };
	$self;
}

=item _addAliasEntry(\%data)

 Add entry in aliases lookup table

 Param hash \%data Mail data
 Return int 0 on success, die on failure

=cut

sub _addAliasEntry
{
	my ($self, $data) = @_;

	$self->addTableEntry($data->{'MAIL_ADDR'}, $data->{'MAIL_FORWARD'}, $self->{'config'}->{'MTA_VIRTUAL_ALIAS_MAP'});

	#my @entry = ($data->{'MAIL_FORWARD'});

	# For a normal+foward mail account, we must also add the recipient as address to keep local copy of forwarded mails
	#if (index($data->{'MAIL_TYPE'}, '_mail') != -1) {
	#	push(@entry, $data->{'MAIL_ADDR'});
	#}

	# If the auto-responder is activated, we must also add an address such as user@imscp-arpl.domain.tld
	#if ($data->{'MAIL_HAS_AUTO_RESPONDER'}) {
	#	push(@entry, "$data->{'MAIL_ACC'}\@imscp-arpl.$data->{'DOMAIN_NAME'}");
	#}

	#$self->addTableEntry("$data->{'MAIL_ADDR'}\t" . join(',', @entry), $self->{'config'}->{'MTA_VIRTUAL_ALIAS_MAP'});
}

=item _deleteAliasEntry(\%data)

 Delete entry from aliases lookup table

 Param hash \%data Mail data
 Return int 0 on success, die on failure

=cut

sub _deleteAliasEntry
{
	my ($self, $data) = @_;

	$self->deleteTableEntry(qr/\Q$data->{'MAIL_ADDR'}\E/, $self->{'config'}->{'MTA_VIRTUAL_ALIAS_MAP'});

#	if ($data->{'MAIL_STATUS'} ne 'todelete') {
#		# Handle normal mail accounts entries for which auto-responder is active
#		my @entry;
#
#		# If auto-responder is activated, we must also add the recipient as address to keep local copy of forwarded mails
#		if ($data->{'MAIL_HAS_AUTO_RESPONDER'} && index($data->{'MAIL_TYPE'}, '_mail') != -1) {
#			push(@entry, $data->{'MAIL_ADDR'});
#		}
#
#		# If auto-responder is activated, we need an address such as user@imscp-arpl.domain.tld
#		if ($data->{'MAIL_HAS_AUTO_RESPONDER'} && index($data->{'MAIL_TYPE'}, '_mail') != -1) {
#			push(@entry, "$data->{'MAIL_ACC'}\@imscp-arpl.$data->{'DOMAIN_NAME'}");
#		}
#
#		if (@entry) {
#			$self->addTableEntry(
#				"$data->{'MAIL_ADDR'}\t" . join(',', @entry), $self->{'config'}->{'MTA_VIRTUAL_ALIAS_MAP'}
#			);
#		}
#	}
}

=item _addCatchAll(\%data)

 Add catchall

 Param hash \%data Mail data
 Return int 0 on success, die on failure

=cut

sub _addCatchAll
{
	my ($self, $data) = @_;

#	for my $entry(@{$data->{'MAIL_ON_CATCHALL'}}) {
#		my $mailbox = quotemeta($entry);
#		$content =~ s/^$mailbox\s+$mailbox\n//gim;
#		$content .= "$entry\t$entry\n";
#	}
#
#	if (index($data->{'MAIL_TYPE'}, '_catchall') != -1) {
#		$self->deleteTableEntry(qr%@\Q$data->{'DOMAIN_NAME'}\E%, $self->{'config'}->{'MTA_VIRTUAL_ALIAS_MAP'});
#
#		$self->addTableEntry(
#			#"\@$data->{'DOMAIN_NAME'}\t$data->{'MAIL_CATCHALL'}", $self->{'config'}->{'MTA_VIRTUAL_ALIAS_MAP'}
#			"\@$data->{'DOMAIN_NAME'}\t$data->{'MAIL_CATCHALL'}", $self->{'config'}->{'MTA_VIRTUAL_ALIAS_MAP'}
#		);
#	}

	0;
}

=item _deleteCatchAll(\%data)

 Delete catchall

 Param hash \%data Mail data
 Return int 0 on success, die on failure

=cut

sub _deleteCatchAll
{
	my ($self, $data) = @_;

#	for my $entry(@{$data->{'MAIL_ON_CATCHALL'}}) {
#		my $mailbox = quotemeta($entry);
#		$content =~ s/^$mailbox\s+$mailbox\n//gim;
#	}
#
#	my $catchAll = quotemeta("\@$data->{'DOMAIN_NAME'}");
#	$content =~ s/^$catchAll\s+[^\n]*\n//gim;

#	$self->deleteTableEntry(qr%@\Q$data->{'DOMAIN_NAME'}\E%, $self->{'config'}->{'MTA_VIRTUAL_ALIAS_MAP'});

	0;
}

=item END

 Process end tasks

=cut

END
{
	unless(defined $main::execmode && $main::execmode eq 'setup') {
		my $self = __PACKAGE__->getInstance();
		while(my($table, $type) = each(%{$self->{'postmap'}})) {
			$self->postmap($table, $type);
		}
	}
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
