=head1 NAME

 Servers::mta::postfix - i-MSCP Postfix MTA server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::Config;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Service;
use Tie::File;
use Scalar::Defer;
use Class::Autouse qw/Servers::mta::postfix::installer Servers::mta::postfix::uninstaller/;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Postfix MTA server implementation.

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

    $self->{'eventManager'}->register(
        'beforeSetupRestartServices', sub {
            push @{$_[0]}, [
                    sub {
                        my $rs = 0;
                        for my $map(keys %{$self->{'postmap'}}) {
                            $rs ||= $self->postmap( $map );
                        }

                        $rs ||= $self->restart();
                    },
                    'Postfix'
                ];
            0;
        } );

    $self->{'eventManager'}->trigger( 'afterMtaPostinstall', 'postfix' );
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

 Restart server

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

=item postmap($filename [, $filetype = 'hash' ])

 Postmap the given file

 Param string $filename Filename
 Param string $filetype Filetype
 Return int 0 on success, other on failure

=cut

sub postmap
{
    my ($self, $filename, $filetype) = @_;

    $filetype ||= 'hash';

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaPostmap', \$filename, \$filetype );
    return $rs if $rs;

    $rs = execute( "postmap $filetype:$filename", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaPostmap', $filename, $filetype );
}

=item addDmn(\%data)

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddDmn', $data );
    return $rs if $rs;

    if ($data->{'EXTERNAL_MAIL'} eq 'domain') {
        # Mail for both domain and subdomains is managed by external server
        # Remove entry from the Postfix virtual_mailbox_domains map
        $rs = $self->disableDmn( $data );
        return $rs if $rs;

        if ($data->{'DOMAIN_TYPE'} eq 'Dmn') {
            # Remove any previous entry of this domain from the Postfix relay_domains map
            $rs = $self->_deleteFromRelayHash( $data );

            # Add the domain entry to the Postfix relay_domain map
            $rs ||= $self->_addToRelayHash( $data );
            return $rs if $rs;
        }
    } elsif ($data->{'EXTERNAL_MAIL'} eq 'wildcard') {
        # Only mail for in-existent subdomains is managed by external server
        if ($data->{'MAIL_ENABLED'}) {
            # Add the domain or subdomain entry to the Postfix virtual_mailbox_domains map
            $rs = $self->_addToDomainsHash( $data );
            return $rs if $rs;
        }

        if ($data->{'DOMAIN_TYPE'} eq 'Dmn') {
            # Remove any previous entry of this domain from the Postfix relay_domains map
            $rs = $self->_deleteFromRelayHash( $data );

            # Add the wildcard entry for in-existent subdomains to the Postfix relay_domain map
            $rs ||= $self->_addToRelayHash( $data );
            return $rs if $rs;
        }
    } elsif ($data->{'MAIL_ENABLED'}) {
        # Mail for domain and subdomains is managed by i-MSCP mail host
        # Add domain or subdomain entry to the Postfix virtual_mailbox_domains map
        $rs = $self->_addToDomainsHash( $data );
        return $rs if $rs;

        if ($data->{'DOMAIN_TYPE'} eq 'Dmn') {
            # Remove any previous entry of this domain from the Postfix relay_domains map
            $rs = $self->_deleteFromRelayHash( $data );
            return $rs if $rs;
        }
    } else {
        # Remove entry from the Postfix virtual_mailbox_domains map
        $rs = $self->disableDmn( $data );

        # Remove any previous entry of this domain from the Postfix relay_domains map
        $rs ||= $self->_deleteFromRelayHash( $data );
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterMtaAddDmn', $data );
}

=item disableDmn(\%data)

 Process disableDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDisableDmn', $data );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'} );
    my $content = $file->get();
    unless (defined $content) {
        error( sprintf( 'Could not read %s file', $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'} ) );
        return 1;
    }

    my $entry = "$data->{'DOMAIN_NAME'}\t\t\t$data->{'TYPE'}\n";
    $content =~ s/^$entry//gim;

    $rs = $file->set( $content );
    $rs ||= $file->save();
    return $rs if $rs;

    $self->{'postmap'}->{$self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}} = 1;

    if ($data->{'DOMAIN_TYPE'} eq 'Dmn') {
        $rs = $self->_deleteFromRelayHash( $data );
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterMtaDisableDmn', $data );
}

=item deleteDmn(\%data)

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDelDmn', $data );
    $rs ||= $self->disableDmn( $data );
    $rs ||= iMSCP::Dir->new(
        dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}"
    )->remove();
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDelDmn', $data );
}

=item addSub(\%data)

 Process addSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddSub', $data );
    $rs ||= $self->addDmn( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaAddSub', $data );
}

=item disableSub(\%data)

 Process disableSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub disableSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDisableSub', $data );
    $rs ||= $self->disableDmn( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDisableSub', $data );
}

=item deleteSub(\%data)

 Process deleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDelSub', $data );
    $rs ||= $self->deleteDmn( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDelSub', $data );
}

=item addMail(\%data)

 Process addMail tasks

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub addMail
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddMail', $data );
    return $rs if $rs;

    if ($data->{'MAIL_TYPE'} =~ /_mail/) {
        $rs = $self->_addMailBox( $data );
        return $rs if $rs;
    } else {
        $rs = $self->_deleteMailBox( $data );
        return $rs if $rs;
    }

    if ($data->{'MAIL_HAS_AUTO_RSPND'} eq 'yes') {
        $rs = $self->_addAutoRspnd( $data );
        return $rs if $rs;
    } else {
        $rs = $self->_deleteAutoRspnd( $data );
        return $rs if $rs;
    }

    if ($data->{'MAIL_TYPE'} =~ /_forward/) {
        $rs = $self->_addMailForward( $data );
        return $rs if $rs;
    } else {
        $rs = $self->_deleteMailForward( $data );
        return $rs if $rs;
    }

    if ($data->{'MAIL_HAS_CATCH_ALL'} eq 'yes') {
        $rs = $self->_addCatchAll( $data );
        return $rs if $rs;
    } else {
        $rs = $self->_deleteCatchAll( $data );
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterMtaAddMail', $data );
}

=item deleteMail(\%data)

 Process deleteMail tasks

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub deleteMail
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDelMail', $data );
    $rs ||= $self->_deleteMailBox( $data );
    $rs ||= $self->_deleteMailForward( $data );
    $rs ||= $self->_deleteAutoRspnd( $data );
    $rs ||= $self->_deleteCatchAll( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDelMail', $data );
}

=item disableMail(\%data)

 Process disableMail tasks

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub disableMail
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDisableMail', $data );
    $rs ||= $self->_disableMailBox( $data );
    $rs ||= $self->_deleteMailForward( $data );
    $rs ||= $self->_deleteAutoRspnd( $data );
    $rs ||= $self->_deleteCatchAll( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDisableMail', $data );
}

=item getTraffic()

 Get SMTP traffic

 Return hash Traffic data or die on failure

=cut

sub getTraffic
{
    my ($self, $trafficDataSrc, $trafficDb) = @_;

    require File::Temp;

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
        # We are using a small file to memorize the number of the last line that has been read and his content
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

            # Read and parse SMTP traffic source file (line by line)
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
    $self->{'eventManager'}->register( 'afterVrlTraffic', sub {
            -f $trafficDbPath ? iMSCP::File->new( filename => $trafficDbPath )->delFile() : 0;
        } ) unless $selfCall;

    \%trafficDb;
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
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'eventManager'}->trigger( 'beforeMtaInit', $self,
        'postfix' ) and fatal( 'postfix - beforeMtaInit has failed' );
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/postfix";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'commentChar'} = '#';
    $self->{'config'} = lazy {
            tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/postfix.data";
            \%c;
        };
    $self->{'eventManager'}->trigger( 'afterMtaInit', $self,
        'postfix' ) and fatal( 'postfix - afterMtaInit has failed' );
    $self;
}

=item _addToRelayHash(\%data)

 Add entry to relay hash file

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub _addToRelayHash
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddToRelayHash', $data );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'MTA_RELAY_HASH'} );
    my $content = $file->get();
    unless (defined $content) {
        error( sprintf( 'Could not read %s file', $self->{'config'}->{'MTA_RELAY_HASH'} ) );
        return 1;
    }

    my $entry = "$data->{'DOMAIN_NAME'}\t\t\tOK\n";

    if ($data->{'EXTERNAL_MAIL'} eq 'wildcard') { # For wildcard MX, we add entry such as ".domain.tld"
        $entry = '.'.$entry;
    }

    $content .= $entry unless $content =~ /^$entry/gim;

    $rs = $file->set( $content );
    $rs ||= $file->save();
    return $rs if $rs;

    $self->{'postmap'}->{$self->{'config'}->{'MTA_RELAY_HASH'}} = 1;
    $self->{'eventManager'}->trigger( 'afterMtaAddToRelayHash', $data );
}

=item _deleteFromRelayHash(\%data)

 Delete entry from relay hash file

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub _deleteFromRelayHash
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDelFromRelayHash', $data );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'MTA_RELAY_HASH'} );
    my $content = $file->get();
    unless (defined $content) {
        error( sprintf( 'Could not read %s file', $self->{'config'}->{'MTA_RELAY_HASH'} ) );
        return 1;
    }

    my $entry = "\\.?$data->{'DOMAIN_NAME'}\t\t\tOK\n"; # Match both "domain.tld" and ".domain.tld" entries
    $content =~ s/^$entry//gim;

    $rs = $file->set( $content );
    $rs ||= $file->save();
    return $rs if $rs;

    $self->{'postmap'}->{$self->{'config'}->{'MTA_RELAY_HASH'}} = 1;
    $self->{'eventManager'}->trigger( 'afterMtaDelFromRelayHash', $data );
}

=item _addToDomainsHash(\%data)

 Add entry to domains hash file

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub _addToDomainsHash
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddToDomainsHash', $data );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'} );
    my $content = $file->get();
    unless (defined $content) {
        error( sprintf( 'Could not read %s file', $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'} ) );
        return 1;
    }

    my $entry = "$data->{'DOMAIN_NAME'}\t\t\t$data->{'TYPE'}\n";
    $content .= $entry unless $content =~ /^$entry/gim;

    $rs = $file->set( $content );
    $rs ||= $file->save();
    return $rs if $rs;

    $self->{'postmap'}->{$self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}} = 1;

    $rs = iMSCP::Dir->new( dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}" )->make(
        {
            user  => $self->{'config'}->{'MTA_MAILBOX_UID_NAME'},
            group => $self->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            mode  => 0750,
            fixpermissions => iMSCP::Getopt->fixPermissions
        }
    );
    return $rs if $rs;

    $self->{'eventManager'}->trigger( 'afterMtaAddToDomainsHash', $data );
}

=item _addMailBox(\%data)

 Add mailbox

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub _addMailBox
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddMailbox', $data );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'} );
    my $content = $file->get();
    unless (defined $content) {
        error( sprintf( 'Could not read %s file', $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'} ) );
        return 1;
    }

    my $mailbox = quotemeta( $data->{'MAIL_ADDR'} );
    $content =~ s/^$mailbox\s+[^\n]*\n//gim;
    $content .= "$data->{'MAIL_ADDR'}\t$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}/\n";

    $rs = $file->set( $content );
    $rs ||= $file->save();
    return $rs if $rs;

    $self->{'postmap'}->{$self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}} = 1;

    my $mailDir = "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}";
    my $mailUidName = $self->{'config'}->{'MTA_MAILBOX_UID_NAME'};
    my $mailGidName = $self->{'config'}->{'MTA_MAILBOX_GID_NAME'};

    # Creating maildir directory or only set its permissions if already exists
    $rs = iMSCP::Dir->new( dirname => $mailDir )->make(
        {
            user  => $self->{'config'}->{'MTA_MAILBOX_UID_NAME'},
            group => $self->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            mode  => 0750,
            fixpermissions => iMSCP::Getopt->fixPermissions
        }
    );
    return $rs if $rs;

    # Creating maildir sub folders (cur, new, tmp) or only set there permissions if they already exists
    for my $dir('cur', 'new', 'tmp') {
        $rs = iMSCP::Dir->new( dirname => "$mailDir/$dir" )->make(
            {
                user => $mailUidName,
                group => $mailGidName,
                mode => 0750,
                fixpermissions => iMSCP::Getopt->fixPermissions
            }
        );
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterMtaAddMailbox', $data );
}

=item _disableMailBox(\%data)

 Disable mailbox

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub _disableMailBox
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDisableMailbox', $data );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'} );
    my $content = $file->get();
    unless (defined $content) {
        error( sprintf( 'Could not read %s file', $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'} ) );
        return 1;
    }

    my $mailbox = quotemeta( $data->{'MAIL_ADDR'} );
    $content =~ s/^$mailbox\s+[^\n]*\n//gim;

    $rs ||= $file->set( $content );
    $rs ||= $file->save();
    return $rs if $rs;

    $self->{'postmap'}->{$self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}} = 1;
    $self->{'eventManager'}->trigger( 'afterMtaDisableMailbox', $data );
}

=item _deleteMailBox(\%data)

 Delete mailbox

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub _deleteMailBox
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDelMailbox', $data );
    $rs ||= $self->_disableMailBox( $data );

    return $rs unless $data->{'MAIL_ACC'};

    my $mailDir = "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}";
    $rs = iMSCP::Dir->new( dirname => $mailDir )->remove();
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDelMailbox', $data );
}

=item _addMailForward(\%data)

 Add forward mail

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub _addMailForward
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddMailForward', $data );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'} );
    my $content = $file->get();
    unless (defined $content) {
        error( sprintf( 'Could not read %s file', $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'} ) );
        return 1;
    }

    my $forwardEntry = quotemeta( $data->{'MAIL_ADDR'} );
    $content =~ s/^$forwardEntry\s+[^\n]*\n//gim;

    my @line;

    # For a normal+foward mail account, we must add the recipient as address to keep local copy of any forwarded mail
    push( @line, $data->{'MAIL_ADDR'} ) if $data->{'MAIL_TYPE'} =~ /_mail/;
    # Add address(s) to which mail will be forwarded
    push( @line, $data->{'MAIL_FORWARD'} );
    # If the auto-responder is activated, we must add an address such as user@imscp-arpl.domain.tld
    push( @line, "$data->{'MAIL_ACC'}\@imscp-arpl.$data->{'DOMAIN_NAME'}" ) if $data->{'MAIL_AUTO_RSPND'};

    $content .= "$data->{'MAIL_ADDR'}\t".join( ',', @line )."\n" if scalar @line;

    $rs = $file->set( $content );
    $rs ||= $file->save();
    return $rs if $rs;

    $self->{'postmap'}->{$self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}} = 1;
    $self->{'eventManager'}->trigger( 'afterMtaAddMailForward', $data );
}

=item _deleteMailForward(\%data)

 Delete forward mail

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub _deleteMailForward
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDelMailForward', $data );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'} );
    my $content = $file->get();
    unless (defined $content) {
        error( sprintf( 'Could not read %s file', $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'} ) );
        return 1;
    }

    my $forwardEntry = quotemeta( $data->{'MAIL_ADDR'} );
    $content =~ s/^$forwardEntry\s+[^\n]+\n//gim;

    # Handle normal mail accounts entries for which auto-responder is active
    if ($data->{'MAIL_STATUS'} ne 'todelete') {
        my @line;
        # If auto-responder is activated, we must add the recipient as address to keep local copy of any forwarded mail
        push( @line, $data->{'MAIL_ADDR'} ) if $data->{'MAIL_AUTO_RSPND'} && $data->{'MAIL_TYPE'} =~ /_mail/;
        # If auto-responder is activated, we need an address such as user@imscp-arpl.domain.tld
        push( @line, "$data->{'MAIL_ACC'}\@imscp-arpl.$data->{'DOMAIN_NAME'}" )
            if $data->{'MAIL_AUTO_RSPND'} && $data->{'MAIL_TYPE'} =~ /_mail/;

        $content .= "$data->{'MAIL_ADDR'}\t".join( ',', @line )."\n" if scalar @line;
    }

    $rs = $file->set( $content );
    $rs ||= $file->save();
    return $rs if $rs;

    $self->{'postmap'}->{$self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}} = 1;
    $self->{'eventManager'}->trigger( 'afterMtaDelMailForward', $data );
}

=item _addAutoRspnd(\%data)

 Add auto-responder

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub _addAutoRspnd
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddAutoRspnd', $data );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'MTA_TRANSPORT_HASH'} );
    my $content = $file->get();
    unless (defined $content) {
        error( sprintf( 'Could not read %s file', $self->{'config'}->{'MTA_TRANSPORT_HASH'} ) );
        return 1;
    }

    my $transportEntry = quotemeta( "imscp-arpl.$data->{'DOMAIN_NAME'}" );
    $content =~ s/^$transportEntry\s+[^\n]*\n//gmi;
    $content .= "imscp-arpl.$data->{'DOMAIN_NAME'}\timscp-arpl:\n";

    $rs = $file->set( $content );
    $rs ||= $file->save();
    return $rs if $rs;

    $self->{'postmap'}->{$self->{'config'}->{'MTA_TRANSPORT_HASH'}} = 1;
    $self->{'eventManager'}->trigger( 'afterMtaAddAutoRspnd', $data );
}

=item _deleteAutoRspnd(\%data)

 Delete auto-responder

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub _deleteAutoRspnd
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDelAutoRspnd', $data );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'MTA_TRANSPORT_HASH'} );
    my $content = $file->get();
    unless (defined $content) {
        error( sprintf( 'Could not read %s file', $self->{'config'}->{'MTA_TRANSPORT_HASH'} ) );
        return 1;
    }

    my $transportEntry = quotemeta( "imscp-arpl.$data->{'DOMAIN_NAME'}" );
    $content =~ s/^$transportEntry\s+[^\n]*\n//gmi;

    $rs = $file->set( $content );
    $rs ||= $file->save();
    return $rs if $rs;

    $self->{'postmap'}->{$self->{'config'}->{'MTA_TRANSPORT_HASH'}} = 1;
    $self->{'eventManager'}->trigger( 'afterMtaDelAutoRspnd', $data );
}

=item _addCatchAll(\%data)

 Add catchall

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub _addCatchAll
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddCatchAll', $data );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'} );
    my $content = $file->get();
    unless (defined $content) {
        error( sprintf( 'Could not read %s file', $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'} ) );
        return 1;
    }

    for my $entry(@{$data->{'MAIL_ON_CATCHALL'}}) {
        my $mailbox = quotemeta( $entry );
        $content =~ s/^$mailbox\s+$mailbox\n//gim;
        $content .= "$entry\t$entry\n";
    }

    if ($data->{'MAIL_TYPE'} =~ /_catchall/) {
        my $catchAll = quotemeta( "\@$data->{'DOMAIN_NAME'}" );
        $content =~ s/^$catchAll\s+[^\n]*\n//gim;
        $content .= "\@$data->{'DOMAIN_NAME'}\t$data->{'MAIL_CATCHALL'}\n";
    }

    $rs = $file->set( $content );
    $rs ||= $file->save();
    return $rs if $rs;

    $self->{'postmap'}->{$self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}} = 1;
    $self->{'eventManager'}->trigger( 'afterMtaAddCatchAll', $data );
}

=item _deleteCatchAll(\%data)

 Delete catchall

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub _deleteCatchAll
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDelCatchAll', $data );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'} );
    my $content = $file->get();
    unless (defined $content) {
        error( sprintf( 'Could not read %s file', $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'} ) );
        return 1;
    }

    for my $entry(@{$data->{'MAIL_ON_CATCHALL'}}) {
        my $mailbox = quotemeta( $entry );
        $content =~ s/^$mailbox\s+$mailbox\n//gim;
    }

    my $catchAll = quotemeta( "\@$data->{'DOMAIN_NAME'}" );
    $content =~ s/^$catchAll\s+[^\n]*\n//gim;

    $rs = $file->set( $content );
    $rs ||= $file->save();
    return $rs if $rs;

    $self->{'postmap'}->{$self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}} = 1;
    $self->{'eventManager'}->trigger( 'afterMtaDelCatchAll', $data );
}

=item END

 Process end tasks

=cut

END
    {
        my $self = __PACKAGE__->getInstance();
        my $rs = $?;

        unless (defined $main::execmode && $main::execmode eq 'setup') {
            for my $map(keys %{$self->{'postmap'}}) {
                $rs ||= $self->postmap( $map );
            }
        }

        $? = $rs;
    }

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
