=head1 NAME

 Servers::mta::postfix - i-MSCP Postfix MTA server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
use warnings;
use Class::Autouse qw/ File::Temp Servers::mta::postfix::installer Servers::mta::postfix::uninstaller /;
use File::Basename;
use iMSCP::Config;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Service;
use Scalar::Defer;
use Tie::File;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Postfix server implementation.

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaPreInstall', 'postfix' );
    $rs ||= $rs = Servers::mta::postfix::installer->getInstance()->preinstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaPreInstall', 'postfix' );
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaInstall', 'postfix' );
    $rs ||= Servers::mta::postfix::installer->getInstance()->install();
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaInstall', 'postfix' );
}

=item postinstall()

 Process postintall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaPostinstall', 'postfix' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->enable( $self->{'config'}->{'MTA_SNAME'} ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $rs = $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]},
                [
                    sub {
                        my $rs = 0;
                        for my $mapPath(keys %{$self->{'_maps'}}) {
                            my $file = iMSCP::File->new( filename => $mapPath );
                            my $rs = $file->set( $self->{'_maps'}->{$mapPath} );
                            $rs ||= $file->save();
                            $rs ||= $file->mode(0640)
                        }

                        unless($rs) {
                            for my $mapPath(keys %{$self->{'postmap'}}) {
                                $rs = $self->postmap( $mapPath );
                                last if $rs;
                            }

                            $rs ||= $self->restart();
                        }
                    },
                    'Postfix'
                ];
            0;
        }
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaPostinstall', 'postfix' );
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaUninstall', 'postfix' );
    $rs ||= Servers::mta::postfix::uninstaller->getInstance()->uninstall();
    $rs ||= $self->restart();
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaUninstall', 'postfix' );
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaSetEnginePermissions' );
    $rs ||= Servers::mta::postfix::installer->getInstance()->setEnginePermissions();
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaSetEnginePermissions' );
}

=item restart()

 Restart Postfix server

 Return int 0 on success, other on failure

=cut

sub restart
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaRestart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart( $self->{'config'}->{'MTA_SNAME'} ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterMtaRestart' );
}

=item reload()

 Reload Postfix server

 Return int 0 on success, other on failure

=cut

sub reload
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaReload' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->reload( $self->{'config'}->{'MTA_SNAME'} ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterMtaReload' );
}

=item addDmn(\%data)

 Process addDmn tasks

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddDmn', $data );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/ );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_RELAY_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/ );

    if ($data->{'MAIL_ENABLED'}) { # Mail is managed by this server
        $rs ||= $self->addMapEntry( $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, "$data->{'DOMAIN_NAME'}\tOK" );
    } elsif ($data->{'EXTERNAL_MAIL'} eq 'on') { # Mail is managed by external server
        $rs ||= $self->addMapEntry( $self->{'config'}->{'MTA_RELAY_HASH'}, "$data->{'DOMAIN_NAME'}\tOK" );
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaAddDmn', $data );
}

=item disableDmn(\%data)

 Process disableDmn tasks

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDisableDmn', $data );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/ );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_RELAY_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/ );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDisableDmn', $data );
}

=item deleteDmn(\%data)

 Process deleteDmn tasks

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDelDmn', $data );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/ );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_RELAY_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/ );
    $rs ||= iMSCP::Dir->new( dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}" )->remove();
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDelDmn', $data );
}

=item addSub(\%data)

 Process addSub tasks

 Param hashref \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddSub', $data );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/ );

    if($data->{'MAIL_ENABLED'}) {
        $rs ||= $self->addMapEntry( $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, "$data->{'DOMAIN_NAME'}\tOK" );
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaAddSub', $data );
}

=item disableSub(\%data)

 Process disableSub tasks

 Param hashref \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub disableSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDisableSub', $data );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/ );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDisableSub', $data );
}

=item deleteSub(\%data)

 Process deleteSub tasks

 Param hashref \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDelSub', $data );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/ );
    $rs ||= iMSCP::Dir->new( dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}" )->remove();
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDelSub', $data );
}

=item addMail(\%data)

 Process addMail tasks

 Param hashref \%data Mail data
 Return int 0 on success, other on failure

=cut

sub addMail
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddMail', $data );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}, qr/\Q$data->{'MAIL_ADDR'}\E\s+[^\n]*/ );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}, qr/\Q$data->{'MAIL_ADDR'}\E\s+[^\n]*/ );
    my $responderEntry = "$data->{'MAIL_ACC'}\@imscp-arpl.$data->{'DOMAIN_NAME'}";
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_TRANSPORT_HASH'}, qr/\Q$responderEntry\E\s+[^\n]*/ );
    return $rs if $rs;

    my $isMailAccount = index( $data->{'MAIL_TYPE'}, '_mail' ) != -1;
    my $isForwardAccount = index( $data->{'MAIL_TYPE'}, '_forward' ) != -1;

    if ($isMailAccount) {
        for ('cur', 'new', 'tmp') {
            $rs = iMSCP::Dir->new(
                dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}/$_"
            )->make(
                {
                    user           => $self->{'config'}->{'MTA_MAILBOX_UID_NAME'},
                    group          => $self->{'config'}->{'MTA_MAILBOX_GID_NAME'},
                    mode           => 0750,
                    fixpermissions => iMSCP::Getopt->fixPermissions
                }
            );
            return $rs if $rs;
        }

        $rs = $self->addMapEntry(
            $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'},
            "$data->{'MAIL_ADDR'}\t$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}/"
        );

        if (!$rs && !$isForwardAccount) {
            # Postfix lookup in `virtual_alias_maps' first. Thus, if there is a catchall defined for the domain, any
            # mail for the mail account will be catched by the catchall. To prevent this behavior, we must also add an
            # entry in the virtual alias map.
            $rs = $self->addMapEntry(
                $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'},
                $data->{'MAIL_ADDR'} # Recipient
                    ."\t"
                    .$data->{'MAIL_ADDR'} # alias
                    # Add autoresponder if autoresponder is enabled for this account
                    .($data->{'MAIL_HAS_AUTO_RESPONDER'} ? ",$responderEntry" : '')
            );
        }

        return $rs if $rs;
    }

    if ($isForwardAccount) {
        $rs = $self->addMapEntry(
            $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'},
            "$data->{'MAIL_ADDR'}" # Recipient
                ."\t"
                # Add recipient itself in case of mailbox + forward account
                .($isMailAccount ? "$data->{'MAIL_ADDR'}," : '')
                # Add list of mail addresses to which mail must be forwarded
                .$data->{'MAIL_FORWARD'}
                # Add autoresponder if autoresponder is enabled for this account
                .($data->{'MAIL_HAS_AUTO_RESPONDER'} ? ",$responderEntry" : '')
        );
    }

    if ($data->{'MAIL_HAS_AUTO_RESPONDER'}) {
        $rs ||= $self->addMapEntry( $self->{'config'}->{'MTA_TRANSPORT_HASH'}, "$responderEntry\timscp-arpl:" );
    }

    if (!$isMailAccount && !$isForwardAccount && index( $data->{'MAIL_TYPE'}, '_catchall' ) != -1) {
        $rs ||= $self->addMapEntry( $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}, "$data->{'MAIL_ADDR'}\t$data->{'MAIL_CATCHALL'}" );
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaAddMail', $data );
}

=item disableMail(\%data)

 Process disableMail tasks

 Param hashref \%data Mail data
 Return int 0 on success, other on failure

=cut

sub disableMail
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDisableMail', $data );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}, qr/\Q$data->{'MAIL_ADDR'}\E\s+[^\n]*/ );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}, qr/\Q$data->{'MAIL_ADDR'}\E\s+[^\n]*/ );
    my $responderEntry = "$data->{'MAIL_ACC'}\@imscp-arpl.$data->{'DOMAIN_NAME'}";
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_TRANSPORT_HASH'}, qr/\Q$responderEntry\E\s+[^\n]*/ );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDisableMail', $data );
}

=item deleteMail(\%data)

 Process deleteMail tasks

 Param hashref \%data Mail data
 Return int 0 on success, other on failure

=cut

sub deleteMail
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDelMail', $data );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}, qr/\Q$data->{'MAIL_ADDR'}\E\s+[^\n]*/ );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}, qr/\Q$data->{'MAIL_ADDR'}\E\s+[^\n]*/ );
    my $responderEntry = "$data->{'MAIL_ACC'}\@imscp-arpl.$data->{'DOMAIN_NAME'}";
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_TRANSPORT_HASH'}, qr/\Q$responderEntry\E\s+[^\n]*/ );
    $rs ||= iMSCP::Dir->new( dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}" )->remove();
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDelMail', $data );
}

=item getTraffic([ $trafficDataSrc [, \%trafficDb ]])

 Get SMTP traffic

 Param string $trafficDataSrc Path to traffic data source file
 Param hashref \%trafficDb Traffic database
 Return hash Traffic data or die on failure

=cut

sub getTraffic
{
    my ($self, $trafficDataSrc, $trafficDb) = @_;

    my $trafficDir = $main::imscpConfig{'IMSCP_HOMEDIR'};
    my $trafficDbPath = "$trafficDir/smtp_traffic.db";
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

    if (-f -s $trafficDataSrc) {
        # We use a small file to memorize number of the last line that has been read and his content
        tie my %indexDb, 'iMSCP::Config', fileName => "$trafficDir/traffic_index.db", nowarn => 1;

        my $lastParsedLineNo = $indexDb{'smtp_lineNo'} || 0;
        my $lastParsedLineContent = $indexDb{'smtp_lineContent'} || '';

        # Create a snapshot of log file to process
        my $tmpFile1 = File::Temp->new( UNLINK => 1 );
        my $rs = iMSCP::File->new( filename => $trafficDataSrc )->copyFile( $tmpFile1, { preserve => 'no' } );
        die( iMSCP::Debug::getLastError() ) if $rs;

        tie my @content, 'Tie::File', $tmpFile1 or die( sprintf( 'Could not tie %s file', $tmpFile1 ) );

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
            debug( sprintf( 'Log rotation has been detected. Processing %s first...', "$trafficDataSrc.1" ) );
            %trafficDb = %{$self->getTraffic( "$trafficDataSrc.1", \%trafficDb )};
            $lastParsedLineNo = 0;
        }

        debug( sprintf( 'Processing lines from %s, starting at line %d', $trafficDataSrc, $lastParsedLineNo ) );

        if (@content) {
            untie @content;

            # Extract postfix data
            my $tmpFile2 = File::Temp->new( UNLINK => 1 );
            my ($stdout, $stderr);
            execute( "grep postfix $tmpFile1 | maillogconvert.pl standard 1> $tmpFile2", undef, \$stderr ) == 0 or die(
                sprintf( 'Could not extract postfix data: %s', $stderr || 'Unknown error' )
            );

            # Read and extract traffic data from SMTP traffic source file
            open my $fh, '<', $tmpFile2 or die( sprintf( 'Could not open file: %s', $! ) );
            while(<$fh>) {
                if (/^[^\s]+\s[^\s]+\s[^\s\@]+\@([^\s]+)\s[^\s\@]+\@([^\s]+)\s([^\s]+)\s([^\s]+)\s[^\s]+\s[^\s]+\s[^\s]+\s(\d+)$/gim) {
                    if ($4 !~ /virtual/ && !($3 =~ /localhost|127.0.0.1/ && $4 =~ /localhost|127.0.0.1/)) {
                        $trafficDb{$1} += $5;
                        $trafficDb{$2} += $5;
                    }
                }
            }
            close( $fh );
        } else {
            debug( sprintf( 'No traffic data found in %s - Skipping', $trafficDataSrc ) );
            untie @content;
        }
    } elsif (!$selfCall) {
        debug( sprintf( 'Log rotation has been detected. Processing %s...', "$trafficDataSrc.1" ) );
        %trafficDb = %{$self->getTraffic( "$trafficDataSrc.1", \%trafficDb )};
    }

    # Schedule deletion of traffic database. This is only done on success. On failure, the traffic database is kept
    # in place for later processing. In such case, data already processed are zeroed by the traffic processor script.
    $self->{'eventManager'}->register(
        'afterVrlTraffic',
        sub { -f $trafficDbPath ? iMSCP::File->new( filename => $trafficDbPath )->delFile() : 0; }
    ) unless $selfCall;

    \%trafficDb;
}

=item addMapEntry($mapPath, $entry)

 Add the given entry into the given Postfix map

 Note: without any $entry passed-in, the map will be simply created or updated.

 Param string $mapPath Map file path
 Param string $entry Map entry to add
 Return int 0 on success, other on failure

=cut

sub addMapEntry
{
    my ($self, $mapPath, $entry) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeAddPostfixMapEntry', $mapPath, $entry );
    return $rs if $rs;

    local $@;
    my $mapContent = eval { $self->_getMapContent( $mapPath ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    if(defined $entry) {
        $$mapContent =~ s/^\Q$entry\E\n//gim;
        $$mapContent .= "$entry\n";
    }

    $self->{'eventManager'}->trigger( 'beforeAddPostfixMapEntry', $mapPath, $entry );
}

=item deleteMapEntry($mapPath, $entry)

 Delete the given entry from the given Postfix map

 Param string $mapPath Map file path
 Param Regexp $entry Regexp representing map entry to delete
 Return int 0 on success, other on failure

=cut

sub deleteMapEntry
{
    my ($self, $mapPath, $entry) = @_;

    local $@;
    my $mapContent = eval { $self->_getMapContent( $mapPath ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $$mapContent =~ s/^$entry\n//gim;
    0;
}

=item postmap($mapPath [, $mapType = 'hash' ])

 Postmap the given map

 Param string $mapPath Map path
 Param string $hashtype Map type (default: hash)
 Return int 0 on success, other on failure

=cut

sub postmap
{
    my ($self, $mapPath, $mapType) = @_;
    $mapType ||= 'hash';

    my $rs = execute( "postmap $mapType:$mapPath", \ my $stdout, \ my $stderr );
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item postconf($conffile, %params)

 Provide interface to postconf for editing parameters from Postfix main.cf configuration file

 Param hash %params A hash where keys are Postfix parameters names and values a hashes describing in order:
  - action          : The action to be performed (add|replace|remove)
  - values          : An array containing parameters values to add, replace or remove
  - values_separator: Values separator
  - before          : OPTIONAL option allowing to add values before the given value (expressed as a Regexp)
  - after           : OPTIONAL option allowing to add values after the given value (expressed as a Regexp)

    Note that the `before' and `after' option are only supported by the `add' action. Note also that the `before'
    option has highter precedence than the `after' option.

  For instance, let's assume that we want add both, the `check_client_access <table>' value and the
  `check_recipient_access <table>' value to the `smtpd_recipient_restrictions' parameter, after the
  `check_policy_service ...' value. The following hash passed as parameter of this method will do the job:

      my %params = (
        smtpd_recipient_restrictions => {
          action           => 'add',
          values           => [ 'check_client_access <table>', 'check_recipient_access <table>' ],
          before           => qr/check_policy_service\s+.*/,
        }
      );

 Return int 0 on success, other on failure

=cut

sub postconf
{
    my ($self, %params) = @_;

    my @paramsToRemove = ();

    local $@;
    my $rs = eval {
        my $stderr;
        my $rs = executeNoWait(
            [ 'postconf', '-c', $self->{'config'}->{'POSTFIX_CONF_DIR'}, keys %params ],
            sub {
                my $buffer = shift;
                open my $stdout, '<', \$buffer or die( sprintf( 'Could not open: %s', $! ) );
                while(<$stdout>) {
                    /^([^=]+)\s+=\s+(.*)/;
                    next unless defined $1 && defined $2;

                    my @replace;
                    my @values = split /,\s*/, $2;
                    for my $value(@{$params{$1}->{'values'}}) {
                        if (!defined $params{$1}->{'action'} || $params{$1}->{'action'} eq 'add') {
                            next if grep { $_ eq $value } @values;

                            if (defined $params{$1}->{'before'} || defined $params{$1}->{'after'}) {
                                my $regexp = $params{$1}->{'before'} || $params{$1}->{'after'};
                                my ($index) = grep { $values[$_] =~ /^$regexp$/ } (0 .. @values - 1);
                                next unless defined $index;
                                splice( @values, (defined $params{$1}->{'before'} ? $index : ++$index), 0, $value );
                            } else {
                                push @values, $value;
                            }
                        } elsif ($params{$1}->{'action'} eq 'replace') {
                            push @replace, $value;
                        } elsif ($params{$1}->{'action'} eq 'remove') {
                            @values = grep { $_ !~ /^$value$/ } @values;
                        }
                    }

                    $params{$1} = join ', ', @replace ? @replace : @values;
                    if ($params{$1} eq '') {
                        push @paramsToRemove, $1;
                        delete $params{$1};
                    }
                }
                close $stdout;
            },
            sub { $stderr .= shift }
        );
        warning( $stderr ) if $stderr;
        $rs;
    };
    if ($@) {
        error( $@ );
        return 1;
    }
    return $rs if $rs;

    if (%params) {
        my $cmd = [ 'postconf', '-e', '-c', $self->{'config'}->{'POSTFIX_CONF_DIR'} ];
        while(my ($param, $value) = each( %params )) {
            next if ref $value eq 'HASH';
            push @{$cmd}, "$param=$value";
        }

        $rs = execute( $cmd, \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
    }

    return 0 unless @paramsToRemove;

    # postconf -X command that allows to remove parameter is not available prior Postfix 2.10. Thus, we must
    # edit the file manually.
    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'POSTFIX_CONF_DIR'}/main.cf" );
    my $fileContent = $file->get();
    unless (defined $fileContent) {
        error( sprintf( 'Could not read %s file', $file->{'filename'} ) );
        return 1;
    }

    $fileContent =~ s/^\Q$_\E\s*=[^\n]+\n//gim for @paramsToRemove;
    $rs = $file->set( $fileContent );
    $rs ||= $file->save;
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

    $self->{'restart'} = 0;
    $self->{'reload'} = 0;
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/postfix";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'config'} = lazy
        {
            tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/postfix.data";
            \%c;
        };
    $self->{'_maps'} = { };
    $self;
}

=item _getMapContent(mapPath)

 Get given postfix map content

 Param string $mapPath Postfix map path
 Return scalarref Reference to map content

=cut

sub _getMapContent
{
    my ($self, $mapPath) = @_;

    unless (defined $self->{'_maps'}->{$mapPath}) {
        if(-f $mapPath) {
            my $file = iMSCP::File->new( filename => $mapPath );
            $self->{'_maps'}->{$mapPath} = $file->get();
            unless (defined $self->{'_maps'}->{$mapPath}) {
                die( sprintf( 'Could not read %s file', $mapPath ) );
            }
        } else {
            my $basename = basename($mapPath);
            $self->{'_maps'}->{$mapPath} = <<EOF;
# Postfix $basename - auto-generated by i-MSCP
#     DO NOT EDIT THIS FILE BY HAND -- YOUR CHANGES WILL BE OVERWRITTEN
EOF
        }

        $self->{'postmap'}->{$mapPath} = 1;
    }

    \$self->{'_maps'}->{$mapPath};
}

=item END

 Save all Postfix maps

=cut

END
    {
        my $self = __PACKAGE__->getInstance();
        my $exitCode = $?;
        my $rs = 0;

        unless (defined $main::execmode && $main::execmode eq 'setup') {
            for my $mapPath(keys %{$self->{'_maps'}}) {
                my $file = iMSCP::File->new( filename => $mapPath );
                my $rs = $file->set( $self->{'_maps'}->{$mapPath} );
                $rs ||= $file->save();
                $rs ||= $file->mode(0640)
            }

            unless($rs) {
                for my $mapPath(keys %{$self->{'postmap'}}) {
                    $rs = $self->postmap( $mapPath );
                    last if $rs;
                }
            }
        }

        $? = $exitCode || $rs;
    }

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
