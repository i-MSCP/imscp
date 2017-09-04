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
use Class::Autouse qw/ :nostat Servers::mta::postfix::installer Servers::mta::postfix::uninstaller /;
use File::Basename;
use File::Temp;
use iMSCP::Config;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute qw/ execute executeNoWait /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Rights;
use iMSCP::Service;
use Tie::File;
use parent 'Common::SingletonClass';

# Selfref for use in END block
my $instance;

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
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaPreInstall', 'postfix' );
    $rs ||= $self->stop();
    $rs ||= $rs = Servers::mta::postfix::installer->getInstance()->preinstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaPreInstall', 'postfix' );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaInstall', 'postfix' );
    $rs ||= Servers::mta::postfix::installer->getInstance()->install();
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaInstall', 'postfix' );
}

=item postinstall( )

 Process postintall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaPostinstall', 'postfix' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->enable( $self->{'config'}->{'MTA_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs = $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]},
                [
                    sub {
                        for( keys %{$self->{'_postmap'}} ) {
                            if ( $self->{'_maps'}->{$_} ) {
                                $rs = $self->{'_maps'}->{$_}->mode( 0640 );
                                last if $rs;
                            }

                            $rs = $self->postmap( $_ );
                            last if $rs;
                        }

                        $rs ||= $self->start();
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
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaUninstall', 'postfix' );
    $rs ||= Servers::mta::postfix::uninstaller->getInstance()->uninstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterMtaUninstall', 'postfix' );

    unless ( $rs || !iMSCP::Service->getInstance()->hasService( $self->{'config'}->{'MTA_SNAME'} ) ) {
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
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaSetEnginePermissions' );
    # eg. /etc/postfix/main.cf
    $rs ||= setRights(
        $self->{'config'}->{'POSTFIX_CONF_FILE'},
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0644'
        }
    );
    # eg. /etc/postfix/master.cf
    $rs ||= setRights(
        $self->{'config'}->{'POSTFIX_MASTER_CONF_FILE'},
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0644'
        }
    );
    # eg. /etc/aliases
    $rs ||= setRights(
        $self->{'config'}->{'MTA_LOCAL_ALIAS_HASH'},
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0644'
        }
    );
    # eg. /etc/postfix/imscp
    $rs ||= setRights(
        $self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'},
        {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $main::imscpConfig{'ROOT_GROUP'},
            dirmode   => '0750',
            filemode  => '0640',
            recursive => 1
        }
    );
    # eg. /var/www/imscp/engine/messenger
    $rs ||= setRights(
        "$main::imscpConfig{'ENGINE_ROOT_DIR'}/messenger",
        {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $main::imscpConfig{'IMSCP_GROUP'},
            dirmode   => '0750',
            filemode  => '0750',
            recursive => 1
        }
    );
    # eg. /var/mail/virtual
    $rs ||= setRights(
        $self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'},
        {
            user      => $self->{'config'}->{'MTA_MAILBOX_UID_NAME'},
            group     => $self->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            dirmode   => '0750',
            filemode  => '0640',
            recursive => iMSCP::Getopt->fixPermissions
        }
    );
    # eg. /usr/sbin/maillogconvert.pl
    $rs ||= setRights(
        $self->{'config'}->{'MAIL_LOG_CONVERT_PATH'},
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
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
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaStart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->start( $self->{'config'}->{'MTA_SNAME'} ); };
    if ( $@ ) {
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
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaStop' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->stop( $self->{'config'}->{'MTA_SNAME'} ); };
    if ( $@ ) {
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
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaRestart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart( $self->{'config'}->{'MTA_SNAME'} ); };
    if ( $@ ) {
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
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaReload' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->reload( $self->{'config'}->{'MTA_SNAME'} ); };
    if ( $@ ) {
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

    # Do not list `SERVER_HOSTNAME' in BOTH `mydestination' and `virtual_mailbox_domains'
    return 0 if $data->{'DOMAIN_NAME'} eq $main::imscpConfig{'SERVER_HOSTNAME'};

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddDmn', $data );
    $rs ||= $self->deleteMapEntry(
        $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/
    );
    $rs ||= $self->deleteMapEntry( $self->{'config'}->{'MTA_RELAY_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/ );

    if ( $data->{'MAIL_ENABLED'} ) { # Mail is managed by this server
        $rs ||= $self->addMapEntry( $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, "$data->{'DOMAIN_NAME'}\tOK" );
    } elsif ( $data->{'EXTERNAL_MAIL'} eq 'on' ) { # Mail is managed by external server
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

    return 0 if $data->{'DOMAIN_NAME'} eq $main::imscpConfig{'SERVER_HOSTNAME'};

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
    return $rs if $rs;

    iMSCP::Dir->new( dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}" )->remove();

    $self->{'eventManager'}->trigger( 'afterMtaDelDmn', $data );
}

=item addSub( \%data )

 Process addSub tasks

 Param hashref \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ($self, $data) = @_;

    # Do not list `SERVER_HOSTNAME' in BOTH `mydestination' and `virtual_mailbox_domains'
    return 0 if $data->{'DOMAIN_NAME'} eq $main::imscpConfig{'SERVER_HOSTNAME'};

    my $rs = $self->{'eventManager'}->trigger( 'beforeMtaAddSub', $data );
    $rs ||= $self->deleteMapEntry(
        $self->{'config'}->{'MTA_VIRTUAL_DMN_HASH'}, qr/\Q$data->{'DOMAIN_NAME'}\E\s+[^\n]*/
    );

    if ( $data->{'MAIL_ENABLED'} ) {
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

    return 0 if $data->{'DOMAIN_NAME'} eq $main::imscpConfig{'SERVER_HOSTNAME'};

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
    return $rs if $rs;

    iMSCP::Dir->new( dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}" )->remove();

    $self->{'eventManager'}->trigger( 'afterMtaDelSub', $data );
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

    if ( $data->{'MAIL_CATCHALL'} ) {
        $rs = $self->addMapEntry(
            $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'}, "$data->{'MAIL_ADDR'}\t$data->{'MAIL_CATCHALL'}"
        );
        return $rs if $rs;
    } else {
        my $isMailAccount = index( $data->{'MAIL_TYPE'}, '_mail' ) != -1
            && $data->{'DOMAIN_NAME'} ne $main::imscpConfig{'SERVER_HOSTNAME'};
        my $isForwardAccount = index( $data->{'MAIL_TYPE'}, '_forward' ) != -1;

        return 0 unless $isMailAccount || $isForwardAccount;

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

        if ( $isMailAccount ) {
            my $maildir = "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}";

            # Create mailbox

            for( $data->{'DOMAIN_NAME'}, "$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}" ) {
                iMSCP::Dir->new( dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$_" )->make(
                    {
                        user           => $self->{'config'}->{'MTA_MAILBOX_UID_NAME'},
                        group          => $self->{'config'}->{'MTA_MAILBOX_GID_NAME'},
                        mode           => 0750,
                        fixpermissions => iMSCP::Getopt->fixPermissions
                    }
                );
            }

            for ( qw/ cur new tmp / ) {
                iMSCP::Dir->new( dirname => "$maildir/$_" )->make(
                    {
                        user           => $self->{'config'}->{'MTA_MAILBOX_UID_NAME'},
                        group          => $self->{'config'}->{'MTA_MAILBOX_GID_NAME'},
                        mode           => 0750,
                        fixpermissions => iMSCP::Getopt->fixPermissions
                    }
                );
            }

            # Add virtual mailbox map entry
            $rs = $self->addMapEntry(
                $self->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'},
                "$data->{'MAIL_ADDR'}\t$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}/"
            );
            return $rs if $rs;
        } else {
            iMSCP::Dir->new(
                dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}"
            )->remove();
        }

        # Add virtual alias map entry
        $rs = $self->addMapEntry(
            $self->{'config'}->{'MTA_VIRTUAL_ALIAS_HASH'},
            $data->{'MAIL_ADDR'} # Recipient
                . "\t" # Separator
                . join ',', (
                    # Mail account only case:
                    #  Postfix lookup in `virtual_alias_maps' first. Thus, if there
                    #  is a catchall defined for the domain, any mail for the mail
                    #  account will be catched by the catchall. To prevent this
                    #  behavior, we must also add an entry in the virtual alias map.
                    #
                    # Forward + mail account case:
                    #  we want keep local copy of inbound mails
                    ( $isMailAccount ? $data->{'MAIL_ADDR'} : () ),
                    # Add forward addresses in case of forward account
                    ( $isForwardAccount ? $data->{'MAIL_FORWARD'} : () ),
                    # Add autoresponder entry if it is enabled for this account
                    ( $data->{'MAIL_HAS_AUTO_RESPONDER'} ? $responderEntry : () )
                )
        );
        return $rs if $rs;

        if ( $data->{'MAIL_HAS_AUTO_RESPONDER'} ) {
            # Add transport map entry for autoresponder
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

    if ( $data->{'MAIL_CATCHALL'} ) {
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

    if ( $data->{'MAIL_CATCHALL'} ) {
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
        return $rs if $rs;

        iMSCP::Dir->new(
            dirname => "$self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}"
        )->remove();
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
    if ( -f -s $logFile ) {
        # We use an index database file to keep trace of the last processed log
        $trafficIndexDb or tie %{$trafficIndexDb},
            'iMSCP::Config', fileName => "$main::imscpConfig{'IMSCP_HOMEDIR'}/traffic_index.db", nodie => 1;

        my ($idx, $idxContent) = ( $trafficIndexDb->{'smtp_lineNo'} || 0, $trafficIndexDb->{'smtp_lineContent'} );

        # Create a snapshot of current log file state
        my $snapshotFH = File::Temp->new( UNLINK => 1 );
        iMSCP::File->new( filename => $logFile )->copyFile( $snapshotFH->filename, { preserve => 'no' } ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        # Tie the snapshot for easy handling
        tie my @snapshot, 'Tie::File', $snapshotFH, memory => 10_485_760 or die(
            sprintf( "Couldn't tie %s file", $snapshotFH )
        );

        # We keep trace of the index for the live log file only
        unless ( $logFile =~ /\.1$/ ) {
            $trafficIndexDb->{'smtp_lineNo'} = $#snapshot;
            $trafficIndexDb->{'smtp_lineContent'} = $snapshot[$#snapshot];
        }

        debug( sprintf( 'Processing SMTP logs from the %s file', $logFile ));

        # We have already seen the log file in the past. We must skip logs that were already processed
        if ( $snapshot[$idx] && $snapshot[$idx] eq $idxContent ) {
            debug( sprintf( 'Skipping logs that were already processed (lines %d to %d)', 1, ++$idx ));

            my $logsFound = ( @snapshot = @snapshot[$idx .. $#snapshot] ) > 0;
            untie( @snapshot );
            $snapshotFH->close();

            unless ( $logsFound ) {
                debug( sprintf( 'No new SMTP logs found in %s file for processing', $logFile ));
                return;
            }
        } elsif ( $logFile !~ /\.1$/ ) {
            debug( 'Log rotation has been detected. Processing last rotated log file first' );
            untie( @snapshot );
            $snapshotFH->close();
            $self->getTraffic( $trafficDb, $logFile . '.1', $trafficIndexDb );
        } else {
            untie( @snapshot );
            $snapshotFH->close();
        }

        # Extract and standardize SMTP logs using maillogconvert.pl script
        open my $fh, '-|', "maillogconvert.pl standard < $snapshotFH 2>/dev/null" or die(
            sprintf( "Couldn't pipe to maillogconvert.pl command for reading: %s", $! )
        );

        while ( <$fh> ) {
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
    } elsif ( $logFile !~ /\.1$/ && -f -s $logFile . '.1' ) {
        # The log file is empty. We need to check the last rotated log file
        # to extract traffic from possible unprocessed logs
        debug( 'The %s log file is empty. Processing last rotated log file', $logFile );
        $self->getTraffic( $trafficDb, $logFile . '.1', $trafficIndexDb );
    } else {
        # There are no new logs found for processing
        debug( sprintf( 'No new SMTP logs found in %s file for processing', $logFile ));
    }
}

=item addMapEntry( $mapPath [, $entry ] )

 Add the given entry into the given Postfix map

 Note: Without any $entry passed-in, the map will be simply created.

 Param string $mapPath Map file path
 Param string $entry OPTIONAL Map entry to add if any
 Return int 0 on success, other on failure

=cut

sub addMapEntry
{
    my ($self, $mapPath, $entry) = @_;

    local $@;
    my $file = eval { $self->_getMapFileObject( $mapPath ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    return 0 unless defined $entry;

    my $mapFileContentRef = $file->getAsRef();
    unless ( defined $mapFileContentRef ) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
        return 1;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeAddPostfixMapEntry', $mapPath, $entry );
    return $rs if $rs;

    ${$mapFileContentRef} =~ s/^\Q$entry\E\n//gim;
    ${$mapFileContentRef} .= "$entry\n";

    $rs ||= $file->save();
    $self->{'_postmap'}->{$mapPath} = 1 unless $rs || $self->{'_postmap'}->{$mapPath};
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
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    my $mapFileContentRef = $file->getAsRef();
    unless ( defined $mapFileContentRef ) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
        return 1;
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeDeletePostfixMapEntry', $mapPath, $entry );
    return $rs if $rs;

    if ( ${$mapFileContentRef} =~ s/^$entry\n//gim ) {
        $rs = $file->save();
        $self->{'_postmap'}->{$mapPath} = 1 unless $rs || $self->{'_postmap'}->{$mapPath};
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterDeletePostfixMapEntry', $mapPath, $entry );
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
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item postconf( $conffile, %params )

 Provides an interface to POSTCONF(1) for editing parameters in Postfix main.cf configuration file

 Param hash %params A hash where each key is a Postfix parameter name and the value, a hashes describing in order:
  - action : Action to be performed (add|replace|remove) -- Default add
  - values : An array containing parameter value(s) to add, replace or remove. For values to be removed, both strings
             and Regexp are supported.
  - empty  : OPTIONAL Flag that allows to force adding of empty parameter
  - before : OPTIONAL Option that allows to add values before the given value (expressed as a Regexp)
  - after  : OPTIONAL Option that allows to add values after the given value (expressed as a Regexp)

  `replace' action versus `remove' action
    The `replace' action replace the full value of the given parameter while the `remove' action only remove the
    specified value portion in the parameter value. Note that when the resulting value is an empty value, the paramerter
    is removed from the configuration file unless the `empty' flag has been specified.

  `before' and `after' options:
    The `before' and `after' options are only relevant for the `add' action. Note also that the `before' option has a
    highter precedence than the `after' option.
  
  Unknown postfix parameters
    Unknown Postfix parameter are silently ignored

  Usage example:

    Adding parameters

    Let's assume that we want add both, the `check_client_access <table>' value and the `check_recipient_access <table>'
    value to the `smtpd_recipient_restrictions' parameter, before the `check_policy_service ...' service.
    The following would do the job:

    Servers::mta::postfix->getInstance(
        (
            smtpd_recipient_restrictions => {
                action => 'add',
                values => [ 'check_client_access <table>', 'check_recipient_access <table>' ],
                before => qr/check_policy_service\s+.*/,
            }
        )
    );
 
    Removing parameters

    Servers::mta::postfix->getInstance(
        (
            smtpd_milters     => {
                action => 'remove',
                values => [ qr%\Qunix:/opendkim/opendkim.sock\E% ] # Using Regexp
            },
            non_smtpd_milters => {
                action => 'remove',
                values => [ 'unix:/opendkim/opendkim.sock' ] # Using string
            }
        )
    )

 Return int 0 on success, other failure

=cut

sub postconf
{
    my ($self, %params) = @_;

    local $@;
    eval {
        %params or die( 'Missing parameters ' );

        my @pToDel = ();
        my $conffile = $self->{'config'}->{'POSTFIX_CONF_DIR'} || '/etc/postfix';
        my $time = time();

        # Avoid POSTCONF(1) being slow by waiting 2 seconds before next processing
        # See https://groups.google.com/forum/#!topic/list.postfix.users/MkhEqTR6yRM
        utime $time, $time-2, $self->{'config'}->{'POSTFIX_CONF_FILE'} or die(
            sprintf( "Couldn't touch %s file: %s", $self->{'config'}->{'POSTFIX_CONF_FILE'} )
        );

        my ($stdout, $stderr);
        executeNoWait(
            [ 'postconf', '-c', $conffile, keys %params ],
            sub {
                return unless ( my $p, my $v ) = $_[0] =~ /^([^=]+)\s+=\s*(.*)/;

                my (@vls, @rpls) = ( split( /,\s*/, $v ), () );

                defined $params{$p}->{'values'} && ref $params{$p}->{'values'} eq 'ARRAY' or die(
                    sprintf( "Missing or invalid `values' for the %s parameter. Expects an array of values", $p )
                );

                for $v( @{$params{$p}->{'values'}} ) {
                    if ( !$params{$p}->{'action'} || $params{$p}->{'action'} eq 'add' ) {
                        unless ( $params{$p}->{'before'} || $params{$p}->{'after'} ) {
                            next if grep( $_ eq $v, @vls );
                            push @vls, $v;
                            next;
                        }

                        # If the parameter already exists, we delete it as someone could want move it
                        @vls = grep( $_ ne $v, @vls );
                        my $regexp = $params{$p}->{'before'} || $params{$p}->{'after'};
                        ref $regexp eq 'Regexp' or die( 'Invalid before|after option. Expects a Regexp' );
                        my ($idx) = grep ( $vls[$_] =~ /^$regexp$/, 0 .. ( @vls-1 ) );
                        defined $idx
                            ? splice( @vls, ( $params{$p}->{'before'} ? $idx : ++$idx ), 0, $v ) : push @vls, $v;
                    } elsif ( $params{$p}->{'action'} eq 'replace' ) {
                        push @rpls, $v;
                    } elsif ( $params{$p}->{'action'} eq 'remove' ) {
                        @vls = ref $v eq 'Regexp' ? grep ($_ !~ $v, @vls) : grep ($_ ne $v, @vls);
                    } else {
                        die( sprintf( 'Unknown action %s for the  %s parameter', $params{$p}->{'action'}, $p ));
                    }
                }

                my $forceEmpty = $params{$p}->{'empty'};
                $params{$p} = join ', ', @rpls ? @rpls : @vls;

                unless ( $forceEmpty || $params{$p} ne '' ) {
                    push @pToDel, $p;
                    delete $params{$p};
                }
            },
            sub { $stderr .= shift }
        ) == 0 or die(
            $stderr || 'Unknown error'
        );

        if ( %params ) {
            my $cmd = [ 'postconf', '-e', '-c', $conffile ];
            while ( my ($param, $value) = each %params ) { push @{$cmd}, "$param=$value" };
            execute( $cmd, \$stdout, \$stderr ) == 0 or die( $stderr || 'Unknown error' );
            debug( $stdout ) if $stdout;
        }

        if ( @pToDel ) {
            execute( [ 'postconf', '-X', '-c', $conffile, @pToDel ], \$stdout, \$stderr ) == 0 or die(
                $stderr || 'Unknown error'
            );
            debug( $stdout ) if $stdout;
        };

        $self->{'reload'} = 1;
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
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
    my ($self) = @_;

    $instance = $self; # Self ref for use in END block

    $self->{'restart'} = 0;
    $self->{'reload'} = 0;
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/postfix";
    $self->_mergeConfig() if -f "$self->{'cfgDir'}/postfix.data.dist";
    tie %{$self->{'config'}},
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/postfix.data",
        readonly    => !( defined $main::execmode && $main::execmode eq 'setup' ),
        nodeferring => ( defined $main::execmode && $main::execmode eq 'setup' );
    $self->{'_maps'} = {};
    $self;
}

=item _mergeConfig( )

 Merge distribution configuration with production configuration

 Die on failure

=cut

sub _mergeConfig
{
    my ($self) = @_;

    if ( -f "$self->{'cfgDir'}/postfix.data" ) {
        tie my %newConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/postfix.data.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/postfix.data", readonly => 1;

        debug( 'Merging old configuration with new configuration...' );

        while ( my ($key, $value) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        untie ( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/postfix.data.dist" )->moveFile(
        "$self->{'cfgDir'}/postfix.data"
    ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
}

=item _getMapFileObject( mapPath )

 Get iMSCP::File object for the given postfix map

 Param string $mapPath Postfix map path
 Return iMSCP::File, die on failure

=cut

sub _getMapFileObject
{
    my ($self, $mapPath) = @_;

    $self->{'_maps'}->{$mapPath} ||= do {
        my $file = iMSCP::File->new( filename => $mapPath );

        unless ( -f $mapPath ) {
            $file->set( <<"EOF"
# Postfix @{ [ basename( $mapPath ) ] } map - auto-generated by i-MSCP
#     DO NOT EDIT THIS FILE BY HAND -- YOUR CHANGES WILL BE OVERWRITTEN

EOF
            );
            $file->save() == 0 && $file->mode( 0640 ) == 0 or die(
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
            $self->{'_postmap'}->{$mapPath} = 1;
        }

        $file;
    }
}

=item END

 Regenerate Postfix maps

=cut

END
    {
        return if $? || !$instance || ( $main::execmode && $main::execmode eq 'setup' );

        my ($ret, $rs) = ( 0, 0 );

        for( keys %{$instance->{'_postmap'}} ) {
            if ( $instance->{'_maps'}->{$_} ) {
                $rs = $instance->{'_maps'}->{$_}->mode( 0640 );
                $ret ||= $rs;
                next if $rs;
            }

            $rs = $instance->postmap( $_ );
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
