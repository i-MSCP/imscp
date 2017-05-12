=head1 NAME

 Servers::mta::postfix - i-MSCP Postfix MTA server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
use Class::Autouse qw/ :nostat File::Temp Servers::mta::postfix::installer Servers::mta::postfix::uninstaller /;
use File::Basename;
use iMSCP::Config;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Rights;
use iMSCP::Service;
use Tie::File;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Postfix server implementation.

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaPreInstall', 'postfix' );
    $rs ||= $self->stop( );
    $rs ||= $rs = Servers::mta::postfix::installer->getInstance( )->preinstall( );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaPreInstall', 'postfix' );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaInstall', 'postfix' );
    $rs ||= Servers::mta::postfix::installer->getInstance( )->install( );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaInstall', 'postfix' );
}

=item postinstall( )

 Process postintall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaPostinstall', 'postfix' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance( )->enable( $self->{'config'}->{'MTA_SNAME'} ); };
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
                        $rs = 0;
                        while(my ($mapPath, $mapFileObject) = each(%{$self->{'_maps'}})) {
                            $rs ||= $mapFileObject->mode( 0640 );
                            $rs ||= $self->postmap( $mapPath );
                            last if $rs;
                        }

                        $rs ||= $self->start( );
                    },
                    'Postfix'
                ];
            0;
        },
        6
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaPostinstall', 'postfix' );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaUninstall', 'postfix' );
    $rs ||= Servers::mta::postfix::uninstaller->getInstance( )->uninstall( );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaUninstall', 'postfix' );

    unless ($rs || !iMSCP::Service->getInstance( )->hasService( $self->{'config'}->{'MTA_SNAME'} )) {
        $self->{'restart'} = 1;
    } else {
        $self->{'restart'} = 0;
        $self->{'reload'} = 0;
    }

    $rs;
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaSetEnginePermissions' );
    return $rs if $rs;

    my $rootUName = $main::imscpConfig{'ROOT_USER'};
    my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
    my $imscpGName = $main::imscpConfig{'IMSCP_GROUP'};
    my $mtaUName = $self->{'config'}->{'MTA_MAILBOX_UID_NAME'};
    my $mtaGName = $self->{'config'}->{'MTA_MAILBOX_GID_NAME'};

    # eg. /etc/postfix/main.cf
    $rs = setRights(
        $self->{'config'}->{'POSTFIX_CONF_FILE'},
        {
            user  => $rootUName,
            group => $rootGName,
            mode  => '0644'
        }
    );
    # eg. /etc/postfix/master.cf
    $rs ||= setRights(
        $self->{'config'}->{'POSTFIX_MASTER_CONF_FILE'},
        {
            user  => $rootUName,
            group => $rootGName,
            mode  => '0644'
        }
    );
    # eg. /etc/aliases
    $rs ||= setRights(
        $self->{'config'}->{'MTA_LOCAL_ALIAS_HASH'},
        {
            user  => $rootUName,
            group => $rootGName,
            mode  => '0644'
        }
    );
    # eg. /etc/postfix/imscp
    $rs ||= setRights(
        $self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'},
        {
            user      => $rootUName,
            group     => $rootGName,
            dirmode   => '0750',
            filemode  => '0640',
            recursive => 1
        }
    );
    # eg. /var/www/imscp/engine/messenger
    $rs ||= setRights(
        "$main::imscpConfig{'ENGINE_ROOT_DIR'}/messenger",
        {
            user      => $rootUName,
            group     => $imscpGName,
            dirmode   => '0750',
            filemode  => '0750',
            recursive => 1
        }
    );
    # eg. /var/mail/virtual
    $rs ||= setRights(
        $self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'},
        {
            user      => $mtaUName,
            group     => $mtaGName,
            dirmode   => '0750',
            filemode  => '0640',
            recursive => iMSCP::Getopt->fixPermissions
        }
    );
    # eg. /usr/sbin/maillogconvert.pl
    $rs ||= setRights(
        $self->{'config'}->{'MAIL_LOG_CONVERT_PATH'},
        {
            user  => $rootUName,
            group => $rootGName,
            mode  => '0750'
        }
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaSetEnginePermissions' );
}

=item start( )

 Start Postfix server

 Return int 0 on success, other on failure

=cut

sub start
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaStart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance( )->start( $self->{'config'}->{'MTA_SNAME'} ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterMtaStart' );
}

=item stop( )

 Stop Postfix server

 Return int 0 on success, other on failure

=cut

sub stop
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaStop' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance( )->stop( $self->{'config'}->{'MTA_SNAME'} ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterMtaStop' );
}

=item restart( )

 Restart Postfix server

 Return int 0 on success, other on failure

=cut

sub restart
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaRestart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance( )->restart( $self->{'config'}->{'MTA_SNAME'} ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterMtaRestart' );
}

=item reload( )

 Reload Postfix server

 Return int 0 on success, other on failure

=cut

sub reload
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaReload' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance( )->reload( $self->{'config'}->{'MTA_SNAME'} ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterMtaReload' );
}

=item addDmn( \%data )

 Process addDmn tasks

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddDmn', $data );
    $rs ||= $self->deleteMapEntry(
        $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/
    );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_RELAY_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/ );

    if ($data->{'MAIL_ENABLED'}) { # Mail is managed by this server
        $rs ||= $self->addMapEntry( $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, "$data->{'DOMAIN_NAME'}\tOK" );
    } elsif ($data->{'EXTERNAL_MAIL'} eq 'on') { # Mail is managed by external server
        $rs ||= $self->addMapEntry( $self->{'config'}->{'MTA_RELAY_HASH'}, "$data->{'DOMAIN_NAME'}\tOK" );
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaAddDmn', $data );
}

=item disableDmn( \%data )

 Process disableDmn tasks

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDisableDmn', $data );
    $rs ||= $self->deleteMapEntry(
        $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/
    );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_RELAY_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/ );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDisableDmn', $data );
}

=item deleteDmn( \%data )

 Process deleteDmn tasks

 Param hashref \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDelDmn', $data );
    $rs ||= $self->deleteMapEntry(
        $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/
    );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_RELAY_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/ );
    $rs ||= iMSCP::Dir->new( dirname =>
        "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}" )->remove( );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDelDmn', $data );
}

=item addSub( \%data )

 Process addSub tasks

 Param hashref \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddSub', $data );
    $rs ||= $self->deleteMapEntry(
        $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/
    );

    if ($data->{'MAIL_ENABLED'}) {
        $rs ||= $self->addMapEntry( $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, "$data->{'DOMAIN_NAME'}\tOK" );
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaAddSub', $data );
}

=item disableSub( \%data )

 Process disableSub tasks

 Param hashref \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub disableSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDisableSub', $data );
    $rs ||= $self->deleteMapEntry(
        $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDisableSub', $data );
}

=item deleteSub( \%data )

 Process deleteSub tasks

 Param hashref \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDelSub', $data );
    $rs ||= $self->deleteMapEntry(
        $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/
    );
    $rs ||= iMSCP::Dir->new(
        dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}"
    )->remove( );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDelSub', $data );
}

=item addMail( \%data )

 Process addMail tasks

 Param hashref \%data Mail data
 Return int 0 on success, other on failure

=cut

sub addMail
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddMail', $data );
    return $rs if $rs;

    if ($data->{'MAIL_CATCHALL'} ne '') {
        $rs = $self->addMapEntry(
            $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}, "$data->{'MAIL_ADDR'}\t$data->{'MAIL_CATCHALL'}"
        );
        return $rs if $rs;
    } else {
        $rs = $self->deleteMapEntry(
            $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}, qr/\Q$data->{'MAIL_ADDR'}\E\s+[^\n]*/
        );
        $rs ||= $self->deleteMapEntry(
            $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}, qr/\Q$data->{'MAIL_ADDR'}\E\s+[^\n]*/
        );
        return $rs if $rs;

        my $responderEntry = "$data->{'MAIL_ACC'}\@imscp-arpl.$data->{'DOMAIN_NAME'}";
        $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_TRANSPORT_HASH'}, qr/\Q$responderEntry\E\s+[^\n]*/ );
        return $rs if $rs;

        my $isMailAccount = index( $data->{'MAIL_TYPE'}, '_mail' ) != -1;
        my $isForwardAccount = index( $data->{'MAIL_TYPE'}, '_forward' ) != -1;

        if ($isMailAccount) {
            # Create mailbox
            for ('cur', 'new', 'tmp') {
                $rs = iMSCP::Dir->new(
                    dirname =>
                    "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}/$_"
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

            # Add virtual mailbox map entry
            $rs = $self->addMapEntry(
                $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'},
                "$data->{'MAIL_ADDR'}\t$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}/"
            );
            return $rs if $rs;
        }

        if ($isForwardAccount || $data->{'MAIL_HAS_AUTO_RESPONDER'}) {
            # Add virtual alias map entry
            $rs = $self->addMapEntry(
                $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'},
                $data->{'MAIL_ADDR'} # Recipient
                    ."\t" # Separator
                    .join ',', (
                        # We add the recipient itself in case of a mixed account (normal + forward).
                        # we want keep local copy of inbound mails
                        ($isMailAccount ? $data->{'MAIL_ADDR'} : ()),
                        # Add forward addresses in case of forward account
                        ($isForwardAccount ? $data->{'MAIL_FORWARD'} : ()),
                        # Add autoresponder entry if it is enabled for this account
                        ($data->{'MAIL_HAS_AUTO_RESPONDER'} ? $responderEntry : ())
                    )
            );
            return $rs if $rs;
        }

        if ($data->{'MAIL_HAS_AUTO_RESPONDER'}) {
            # Add transport map entry
            $rs = $self->addMapEntry( $self->{'config'}->{'MTA_TRANSPORT_HASH'}, "$responderEntry\timscp-arpl:" );
            return $rs if $rs;
        }
    }

    $self->{'eventManager'}->trigger( 'afterMtaAddMail', $data );
}

=item disableMail( \%data )

 Process disableMail tasks

 Param hashref \%data Mail data
 Return int 0 on success, other on failure

=cut

sub disableMail
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDisableMail', $data );
    return $rs if $rs;

    if ($data->{'MAIL_CATCHALL'} ne '') {
        $rs ||= $self->deleteMapEntry(
            $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}, qr/\Q$data->{'MAIL_ADDR'}\E\s+\Q$data->{'MAIL_CATCHALL'}/
        );
    } else {
        $rs ||= $self->deleteMapEntry(
            $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}, qr/\Q$data->{'MAIL_ADDR'}\E\s+[^\n]*/
        );
        $rs ||= $self->deleteMapEntry(
            $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}, qr/\Q$data->{'MAIL_ADDR'}\E\s+[^\n]*/
        );
        return $rs if $rs;

        my $responderEntry = "$data->{'MAIL_ACC'}\@imscp-arpl.$data->{'DOMAIN_NAME'}";
        $rs = $self->deleteMapEntry( $self->{'config'}->{'MTA_TRANSPORT_HASH'}, qr/\Q$responderEntry\E\s+[^\n]*/ );
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDisableMail', $data );
}

=item deleteMail( \%data )

 Process deleteMail tasks

 Param hashref \%data Mail data
 Return int 0 on success, other on failure

=cut

sub deleteMail
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaDelMail', $data );
    return $rs if $rs;

    if ($data->{'MAIL_CATCHALL'} ne '') {
        $rs ||= $self->deleteMapEntry(
            $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}, qr/\Q$data->{'MAIL_ADDR'}\E\s+\Q$data->{'MAIL_CATCHALL'}/
        );
    } else {
        $rs ||= $self->deleteMapEntry(
            $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}, qr/\Q$data->{'MAIL_ADDR'}\E\s+[^\n]*/
        );
        $rs ||= $self->deleteMapEntry(
            $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}, qr/\Q$data->{'MAIL_ADDR'}\E\s+[^\n]*/
        );
        return $rs if $rs;

        my $responderEntry = "$data->{'MAIL_ACC'}\@imscp-arpl.$data->{'DOMAIN_NAME'}";
        $rs = $self->deleteMapEntry( $self->{'config'}->{'MTA_TRANSPORT_HASH'}, qr/\Q$responderEntry\E\s+[^\n]*/ );
        $rs ||= iMSCP::Dir->new(
            dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}"
        )->remove( );
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaDelMail', $data );
}

=item getTraffic( $trafficDb [, $trafficDataSrc, $indexDb ] )

 Get SMTP traffic

 Param hashref \%trafficDb Traffic database
 Param string $logFile Path to SMTP log file from which traffic data must be extracted (only when self-called)
 Param hashref $trafficIndexDb Traffic index database (only when self-called)
 Die on failure

=cut

sub getTraffic
{
    my ($self, $trafficDb, $logFile, $trafficIndexDb) = @_;

    $logFile ||= "$main::imscpConfig{'TRAFF_LOG_DIR'}/$main::imscpConfig{'MAIL_TRAFF_LOG'}";

    # The log file exists and is not empty
    if (-f -s $logFile) {
        # We use an index database file to keep trace of the last processed log
        $trafficIndexDb or tie %{$trafficIndexDb},
            'iMSCP::Config', fileName => "$main::imscpConfig{'IMSCP_HOMEDIR'}/traffic_index.db", nodie => 1;

        my ($idx, $idxContent) = ($trafficIndexDb->{'smtp_lineNo'} || 0, $trafficIndexDb->{'smtp_lineContent'});

        # Create a snapshot of current log file state
        my $snapshotFH = File::Temp->new( UNLINK => 1 );
        iMSCP::File->new( filename => $logFile )->copyFile( $snapshotFH, { preserve => 'no' } ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        # Tie the snapshot for easy handling
        tie my @snapshot, 'Tie::File', $snapshotFH, memory => 10_485_760 or die(
            sprintf( "Couldn't tie %s file", $snapshotFH )
        );

        # We keep trace of the index for the live log file only
        unless ($logFile =~ /\.1$/) {
            $trafficIndexDb->{'smtp_lineNo'} = $#snapshot;
            $trafficIndexDb->{'smtp_lineContent'} = $snapshot[$#snapshot];
        }

        debug( sprintf( 'Processing SMTP logs from the %s file', $logFile ) );

        # We have already seen the log file in the past. We must skip logs that were already processed
        if ($snapshot[$idx] && $snapshot[$idx] eq $idxContent) {
            debug( sprintf( 'Skipping logs that were already processed (lines %d to %d)', 1, ++$idx ) );

            my $logsFound = (@snapshot = @snapshot[$idx .. $#snapshot]) > 0;
            untie(@snapshot);
            $snapshotFH->close();

            unless ($logsFound) {
                debug( sprintf( 'No new SMTP logs found in %s file for processing', $logFile ) );
                return;
            }
        } elsif ($logFile !~ /\.1$/) {
            debug( 'Log rotation has been detected. Processing last rotated log file first' );
            untie(@snapshot);
            $snapshotFH->close();
            $self->getTraffic(  $trafficDb, $logFile.'.1', $trafficIndexDb );
        } else {
            untie(@snapshot);
            $snapshotFH->close();
        }

        # Extract and standardize SMTP logs using maillogconvert.pl script
        open my $fh, '-|', "maillogconvert.pl standard < $snapshotFH 2>/dev/null" or die(
            sprintf( "Couldn't pipe to maillogconvert.pl command for reading: %s", $! )
        );

        while(<$fh>) {
            # Extract SMTP traffic data
            #
            # Log line example
            # date       hour     from            to            relay_s            relay_r            proto  extinfo code size
            # 2017-04-17 13:31:50 from@domain.tld to@domain.tld relay_s.domain.tld relay_r.domain.tld SMTP   -       1    1001
            next unless /\@(?<from>[^\s]+)[^\@]+\@(?<to>[^\s]+)\s+(?<relay_s>[^\s]+)\s+(?<relay_r>[^\s]+).*?(?<size>\d+)$/o;

            $trafficDb->{$+{'from'}} += $+{'size'} if exists $trafficDb->{$+{'from'}};
            $trafficDb->{$+{'to'}} += $+{'size'} if exists $trafficDb->{$+{'to'}};
        }

        close( $fh );
    }

    # The log file is empty. We need to check the last rotated log file
    # to extract traffic from possible unprocessed logs
    elsif ($logFile !~ /\.1$/ && -f -s $logFile.'.1') {
        debug( 'The %s log file is empty. Processing last rotated log file', $logFile );
        $self->getTraffic(  $trafficDb, $logFile.'.1', $trafficIndexDb );
    }

    # There are no new logs found for processing
    else {
        debug( sprintf( 'No new SMTP logs found in %s file for processing', $logFile ) );
    }
}

=item addMapEntry( $mapPath [, $entry ] )

 Create the given Postfix map or add the given entry into the given Postfix map

 Note: without any $entry passed-in, the map will be simply created or updated.

 Param string $mapPath Map file path
 Param string $entry OPTIONAL Map entry to add if any
 Return int 0 on success, other on failure

=cut

sub addMapEntry
{
    my ($self, $mapPath, $entry) = @_;

    local $@;
    my $file = eval { $self->_getMapFileObject( $mapPath ); };
    if ($@) {
        error($@);
        return 1;
    }

    my $mapFileContent = $file->get( );
    unless (defined $mapFileContent) {
        error(sprintf("Couldn't read %s file", $file->{'filename'}));
        return 1;
    }

    return 0 unless defined $entry;

    my $rs = $self->{'eventManager'}->trigger( 'beforeAddPostfixMapEntry', $mapPath, $entry );
    return $rs if $rs;

    $mapFileContent =~ s/^\Q$entry\E\n//gim;
    $mapFileContent .= "$entry\n";

    $rs = $file->set( $mapFileContent );
    $rs ||= $file->save( );
    $rs ||= $self->{'eventManager'}->trigger( 'afterAddPostfixMapEntry', $mapPath, $entry );
}

=item deleteMapEntry( $mapPath, $entry )

 Delete the given entry from the given Postfix map

 Param string $mapPath Map file path
 Param Regexp $entry Regexp matching map entry to delete
 Return int 0 on success, other on failure

=cut

sub deleteMapEntry
{
    my ($self, $mapPath, $entry) = @_;

    local $@;
    my $file = eval { $self->_getMapFileObject( $mapPath ); };
    if ($@) {
        error($@);
        return 1;
    }

    my $mapFileContent = $file->get( );
    unless (defined $mapFileContent) {
        error(sprintf("Couldn't read %s file", $file->{'filename'}));
        return 1;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeDeletePostfixMapEntry', $mapPath, $entry );
    return $rs if $rs;

    $mapFileContent =~ s/^$entry\n//gim;

    $rs = $file->set( $mapFileContent );
    $rs ||= $file->save( );
    $rs ||= $self->{'eventManager'}->trigger( 'afterDeletePostfixMapEntry', $mapPath, $entry );
}

=item postmap( $mapPath [, $mapType = 'hash' ] )

 Postmap the given map

 Param string $mapPath Map path
 Param string $hashtype Map type (default: hash)
 Return int 0 on success, other on failure

=cut

sub postmap
{
    my (undef, $mapPath, $mapType) = @_;
    $mapType ||= 'hash';

    my $rs = execute( "postmap $mapType:$mapPath", \ my $stdout, \ my $stderr );
    debug($stdout) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item postconf( $conffile, %params )

 Provides an interface to POSTCONF(1) for editing parameters in Postfix main.cf configuration file

 Param hash %params A hash where each key is a Postfix parameter name and associated value, a hashes describing in order:
  - action     : The action to be performed (add|replace|remove)
  - values     : An array containing parameter value(s) to add, replace or remove. Default add.
  - empty      : Force adding parameter with an empty value instead of removing it
  - before     : OPTIONAL option allowing to add values before the given value (expressed as a Regexp)
  - after      : OPTIONAL option allowing to add values after the given value (expressed as a Regexp)

  Note that the `before' and `after' options are only relevant for the `add' action. Note also that the `before'
  option has a highter precedence than the `after' option.

  For instance, let's assume that we want add both, the `check_client_access <table>' value and the
  `check_recipient_access <table>' value to the `smtpd_recipient_restrictions' Postfix parameter, before the
  `check_policy_service ...' value. The following would do the job:

  Servers::mta::postfix->getInstance(
    (
        smtpd_recipient_restrictions => {
            action => 'add',
            values => [ 'check_client_access <table>', 'check_recipient_access <table>' ],
            before => qr/check_policy_service\s+.*/,
        }
    )
  );

 Return int 0 on success, other on failure

=cut

sub postconf
{
    my ($self, %params) = @_;

    my @paramsToRemove = ( );
    my $time = time( );

    # Avoid POSTCONF(1) being slow by waiting 2 seconds before next processing
    # See https://groups.google.com/forum/#!topic/list.postfix.users/MkhEqTR6yRM
    my $rs = 1 unless utime $time, $time - 2, $self->{'config'}->{'POSTFIX_CONF_FILE'};
    error(sprintf( "Couldn't touch %s file: %s", $self->{'config'}->{'POSTFIX_CONF_FILE'} ) ) if $rs;
    return $rs if $rs;

    local $@;
    $rs = eval {
        my $stderr;
        $rs = executeNoWait(
            [ 'postconf', '-c', $self->{'config'}->{'POSTFIX_CONF_DIR'}, keys %params ],
            sub {
                return unless (my $pName, my $pValue) = (shift) =~ /^([^=]+)\s+=\s*(.*)/;

                my ($forceEmpty, @values, @replace) = (0, split(/,\s*/, $pValue), ());

                for my $value(@{$params{$pName}->{'values'}}) {
                    $forceEmpty = 1 if $params{$pName}->{'empty'};

                    if (!defined $params{$pName}->{'action'} || $params{$pName}->{'action'} eq 'add') {
                        next if grep { $_ eq $value } @values;

                        if (defined $params{$pName}->{'before'} || defined $params{$pName}->{'after'}) {
                            my $regexp = $params{$pName}->{'before'} || $params{$pName}->{'after'};
                            my ($index) = grep { $values[$_] =~ /^$regexp$/ } (0 .. (@values - 1));
                            next unless defined $index;

                            splice( @values, (defined $params{$pName}->{'before'} ? $index : ++$index), 0, $value );
                        } else {
                            push @values, $value;
                        }
                    } elsif ($params{$pName}->{'action'} eq 'replace') {
                        push @replace, $value;
                    } elsif ($params{$pName}->{'action'} eq 'remove') {
                        @values = grep { $_ !~ /^$value$/ } @values;
                    }
                }

                $params{$pName} = join ', ', @replace ? @replace : @values;
                if (!$forceEmpty && $params{$pName} eq '') {
                    push @paramsToRemove, $pName;
                    delete $params{$pName};
                    $forceEmpty = 0;
                }
            },
            sub { $stderr .= shift }
        );

        debug( $stderr ) if $stderr;
        $rs;
    };
    if ($@) {
        error( $@ );
        return 1;
    }
    return $rs if $rs;

    if (!$rs && %params) {
        my $cmd = [ 'postconf', '-e', '-c', $self->{'config'}->{'POSTFIX_CONF_DIR'} ];
        while(my ($param, $value) = each( %params )) {
            next if ref $value eq 'HASH';
            push @{$cmd}, "$param=$value";
        }

        $rs = execute( $cmd, \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;

        # Avoid POSTCONF(1) being slow by waiting 2 seconds before next processing
        # See https://groups.google.com/forum/#!topic/list.postfix.users/MkhEqTR6yRM
        $time = time( );
        $rs = 1 unless utime $time, $time - 2, $self->{'config'}->{'POSTFIX_CONF_FILE'};
        error(sprintf( "Couldn't touch %s file: %s", $self->{'config'}->{'POSTFIX_CONF_FILE'} ) ) if $rs;
    }

    return $rs if $rs || !@paramsToRemove;

    # postconf -X command that allows to remove parameter is not available prior Postfix 2.10. Thus, we must
    # edit the file manually.
    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'POSTFIX_CONF_DIR'}/main.cf" );
    my $fileContent = $file->get( );
    unless (defined $fileContent) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
        return 1;
    }

    $fileContent =~ s/^\Q$_\E\s*=[^\n]+\n//gim for @paramsToRemove;
    $rs = $file->set( $fileContent );
    $rs ||= $file->save;

    # Avoid POSTCONF(1) being slow by waiting 2 seconds before next processing
    # See https://groups.google.com/forum/#!topic/list.postfix.users/MkhEqTR6yRM
    $time = time( );
    $rs = 1 unless utime $time, $time - 2, $self->{'config'}->{'POSTFIX_CONF_FILE'};
    error(sprintf( "Couldn't touch %s file: %s", $self->{'config'}->{'POSTFIX_CONF_FILE'} ) ) if $rs;

    $self->{'reload'} = 1 unless $rs;
    $rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::mta::postfix

=cut

sub _init
{
    my $self = shift;

    $self->{'restart'} = 0;
    $self->{'reload'} = 0;
    $self->{'eventManager'} = iMSCP::EventManager->getInstance( );
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/postfix";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    tie %{$self->{'config'}}, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/postfix.data", readonly => 1;
    $self->{'_maps'} = { };
    $self;
}

=item _getMapFileObject( mapPath )

 Get iMSCP::File object for the given postfix map

 Param string $mapPath Postfix map path
 Return iMSCP::File, die on failure

=cut

sub _getMapFileObject
{
    my ($self, $mapPath) = @_;

    return $self->{'_maps'}->{$mapPath} if exists $self->{'_maps'}->{$mapPath};

    $self->{'_maps'}->{$mapPath} = iMSCP::File->new( filename => $mapPath );

    unless (-f $mapPath) {
        my $basename = basename($mapPath);
        $self->{'_maps'}->{$mapPath}->set( <<"EOF"
# Postfix $basename - auto-generated by i-MSCP
#     DO NOT EDIT THIS FILE BY HAND -- YOUR CHANGES WILL BE OVERWRITTEN
EOF
        );

        $self->{'_maps'}->{$mapPath}->save( ) == 0 && $self->{'_maps'}->{$mapPath}->mode( 0640 ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
    }

    $self->{'postmap'}->{$mapPath} = 1;
    $self->{'_maps'}->{$mapPath};
}

=item END

 Save all Postfix maps

=cut

END
    {
        return if defined $main::execmode && $main::execmode eq 'setup';

        my $self = __PACKAGE__->getInstance( );
        my $ret = 0;

        while(my ($mapPath, $mapFileObject) = each(%{$self->{'_maps'}})) {
            my $rs = $mapFileObject->mode( 0640 );
            $rs ||= $self->postmap( $mapPath );
            $ret ||= $rs;
        }

        $? ||= $ret;
    }

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
