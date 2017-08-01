=head1 NAME

 Servers::po::dovecot - i-MSCP Dovecot IMAP/POP3 Server implementation

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

package Servers::po::dovecot;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Servers::po::dovecot::installer Servers::po::dovecot::uninstaller /;
use File::Temp;
use iMSCP::Config;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Rights;
use iMSCP::Service;
use List::MoreUtils qw / uniq /;
use Servers::mta;
use Sort::Naturally;
use Tie::File;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Dovecot IMAP/POP3 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%eventManager )

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my (undef, $eventManager) = @_;

    Servers::po::dovecot::installer->getInstance()->registerSetupListeners( $eventManager );
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePoPreinstall', 'dovecot' );
    $rs ||= $self->stop();
    return $rs if $rs;

    local $@;
    $rs = eval {
        my $serviceMngr = iMSCP::Service->getInstance();

        # Disable dovecot.socket unit if any
        # Dovecot as configured by i-MSCP doesn't rely on systemd activation socket
        # This also solve problem on boxes where IPv6 is not available; default dovecot.socket unit file make
        # assumption that IPv6 is available without further checks...
        # See also: https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=814999
        if ( $serviceMngr->isSystemd() && $serviceMngr->hasService( 'dovecot.socket' ) ) {
            $serviceMngr->stop( 'dovecot.socket' );
            $serviceMngr->disable( 'dovecot.socket' );
        }

        $self->stop();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPoPreinstall', 'dovecot' );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePoInstall', 'dovecot' );
    $rs ||= Servers::po::dovecot::installer->getInstance()->install();
    $rs ||= $self->{'eventManager'}->trigger( 'afterPoInstall', 'dovecot' );
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePoPostinstall', 'dovecot' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->enable( $self->{'config'}->{'DOVECOT_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs = $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]}, [ sub { $self->start(); }, 'Dovecot' ];
            0;
        },
        5
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterPoPostinstall', 'dovecot' );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePoUninstall', 'dovecot' );
    $rs ||= Servers::po::dovecot::uninstaller->getInstance()->uninstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterPoUninstall', 'dovecot' );

    unless ( $rs || !iMSCP::Service->getInstance()->hasService( $self->{'config'}->{'DOVECOT_SNAME'} ) ) {
        $self->{'restart'} = 1;
    } else {
        $self->{'restart'} = 0;
    }

    $rs;
}

=item addMail( \%data )

 Process addMail tasks

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub addMail
{
    my ($self, $data) = @_;

    return 0 unless index( $data->{'MAIL_TYPE'}, '_mail' ) != -1;

    my $mailDir = "$self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}";
    my $mailUidName = $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'};
    my $mailGidName = $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'};

    for my $mailbox( '.Drafts', '.Junk', '.Sent', '.Trash' ) {
        iMSCP::Dir->new( dirname => "$mailDir/$mailbox" )->make(
            {
                user           => $mailUidName,
                group          => $mailGidName,
                mode           => 0750,
                fixpermissions => iMSCP::Getopt->fixPermissions
            }
        );

        for ( 'cur', 'new', 'tmp' ) {
            iMSCP::Dir->new( dirname => "$mailDir/$mailbox/$_" )->make(
                {
                    user           => $mailUidName,
                    group          => $mailGidName,
                    mode           => 0750,
                    fixpermissions => iMSCP::Getopt->fixPermissions
                }
            );
        }
    }

    my @subscribedFolders = ( 'Drafts', 'Junk', 'Sent', 'Trash' );
    my $subscriptionsFile = iMSCP::File->new( filename => "$mailDir/subscriptions" );

    if ( -f "$mailDir/subscriptions" ) {
        my $subscriptionsFileContent = $subscriptionsFile->get();
        unless ( defined $subscriptionsFileContent ) {
            error( "Couldn't read Dovecot subscriptions file" );
            return 1;
        }

        if ( $subscriptionsFileContent ne '' ) {
            @subscribedFolders = nsort uniq ( @subscribedFolders, split( /\n/, $subscriptionsFileContent ));
        }
    }

    my $rs = $subscriptionsFile->set( ( join "\n", @subscribedFolders ) . "\n" );
    $rs ||= $subscriptionsFile->save();
    $rs ||= $subscriptionsFile->owner( $mailUidName, $mailGidName );
    $rs ||= $subscriptionsFile->mode( 0640 );
    return $rs if $rs;

    if ( $data->{'MAIL_QUOTA'} ) {
        if ( $self->{'forceMailboxesQuotaRecalc'}
            || ( $self->{'execMode'} eq 'backend' && $data->{'STATUS'} eq 'tochange' )
            || !-f "$mailDir/maildirsize"
        ) {
            # TODO create maildirsize file manually (set quota definition and recalculate byte and file counts)
            if ( -f "$mailDir/maildirsize" ) {
                $rs = iMSCP::File->new( filename => "$mailDir/maildirsize" )->delFile();
                return $rs if $rs;
            }
        }

        return 0;
    }

    if ( -f "$mailDir/maildirsize" ) {
        $rs = iMSCP::File->new( filename => "$mailDir/maildirsize" )->delFile();
        return $rs if $rs;
    }

    0;
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePoSetEnginePermissions' );
    $rs ||= setRights(
        $self->{'config'}->{'DOVECOT_CONF_DIR'},
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0755'
        }
    );
    $rs ||= setRights(
        "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot.conf",
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            mode  => '0640'
        }
    );
    $rs ||= setRights(
        "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf",
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            mode  => '0640'
        }
    );
    $rs ||= setRights(
        "$main::imscpConfig{'ENGINE_ROOT_DIR'}/quota/imscp-dovecot-quota.sh",
        {
            user  => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'},
            group => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            mode  => '0750'
        }
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterPoSetEnginePermissions' );
}

=item start( )

 Start Dovecot

 Return int 0 on success, other on failure

=cut

sub start
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePoStart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->start( $self->{'config'}->{'DOVECOT_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterPoStart' );
}

=item stop( )

 Stop Dovecot

 Return int 0 on success, other on failure

=cut

sub stop
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePoStop' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->stop( $self->{'config'}->{'DOVECOT_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterPoStop' );
}

=item restart( )

 Restart Dovecot

 Return int 0 on success, other on failure

=cut

sub restart
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePoRestart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart( $self->{'config'}->{'DOVECOT_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterPoRestart' );
}

=item getTraffic( $trafficDb [, $trafficDataSrc, $indexDb ] )

 Get IMAP/POP3 traffic data

 Param hashref \%trafficDb Traffic database
 Param string $logFile Path to SMTP log file from which traffic data must be extracted (only when self-called)
 Param hashref $trafficIndexDb Traffic index database (only when self-called)
 Die on failure

=cut

sub getTraffic
{
    my ($self, $trafficDb, $logFile, $trafficIndexDb) = @_;

    $logFile ||= "$main::imscpConfig{'TRAFF_LOG_DIR'}/$main::imscpConfig{'MAIL_TRAFF_LOG'}";

    if ( -f -s $logFile ) {
        # We use an index database file to keep trace of the last processed log
        $trafficIndexDb or tie %{$trafficIndexDb},
            'iMSCP::Config', fileName => "$main::imscpConfig{'IMSCP_HOMEDIR'}/traffic_index.db", nodie => 1;

        my ($idx, $idxContent) = ( $trafficIndexDb->{'po_lineNo'} || 0, $trafficIndexDb->{'po_lineContent'} );

        # Create a snapshot of current log file state
        my $snapshotFH = File::Temp->new( UNLINK => 1 );
        iMSCP::File->new( filename => $logFile )->copyFile( $snapshotFH->filename, { preserve => 'no' } ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        # Tie the snapshot for easy handling
        tie my @snapshot, 'Tie::File', $snapshotFH, memory => 10_485_760 or die(
            sprintf( "Couldn't tie %s file", $snapshotFH->filename )
        );

        # We keep trace of the index for the live log file only
        unless ( $logFile =~ /\.1$/ ) {
            $trafficIndexDb->{'po_lineNo'} = $#snapshot;
            $trafficIndexDb->{'po_lineContent'} = $snapshot[$#snapshot];
        }

        debug( sprintf( 'Processing IMAP/POP3 logs from the %s file', $logFile ));

        # We have already seen the log file in the past. We must skip logs that were already processed
        if ( $snapshot[$idx] && $snapshot[$idx] eq $idxContent ) {
            debug( sprintf( 'Skipping logs that were already processed (lines %d to %d)', 1, ++$idx ));

            my $logsFound = ( @snapshot = @snapshot[$idx .. $#snapshot] ) > 0;
            untie( @snapshot );

            unless ( $logsFound ) {
                debug( sprintf( 'No new IMAP/POP3 logs found in %s file for processing', $logFile ));
                $snapshotFH->close();
                return;
            }
        } elsif ( $logFile !~ /\.1$/ ) {
            debug( 'Log rotation has been detected. Processing last rotated log file first' );
            untie( @snapshot );
            $self->getTraffic( $trafficDb, $logFile . '.1', $trafficIndexDb );
        } else {
            untie( @snapshot );
        }

        while ( <$snapshotFH> ) {
            # Extract IMAP/POP3 traffic data
            #
            # Log line examples
            # Apr 18 23:41:48 jessie dovecot: imap(user@domain.tld): Disconnected: Logged out in=244 out=858
            # Apr 18 23:41:48 jessie dovecot: pop3(user@domain.tld): Disconnected: Logged out top=0/0, retr=0/0, del=0/0, size=0, in=12, out=43
            next unless /(?:imap|pop3)\([^\@]+\@(?<domain>[^\)]+)\):.*in=(?<in>\d+).*out=(?<out>\d+)$/o
                && exists $trafficDb->{$+{'domain'}};

            $trafficDb->{$+{'domain'}} += ( $+{'in'}+$+{'out'} );
        }

        $snapshotFH->close();
    } elsif ( $logFile !~ /\.1$/ && -f -s $logFile . '.1' ) {
        # The log file is empty. We need to check the last rotated log file
        # to extract traffic from possible unprocessed logs
        debug( 'The %s log file is empty. Processing last rotated log file', $logFile );
        $self->getTraffic( $trafficDb, $logFile . '.1', $trafficIndexDb );
    } else {
        # There are no new logs found for processing
        debug( sprintf( 'No new IMAP/POP3 logs found in %s file for processing', $logFile ));
    }
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::po::dovecot

=cut

sub _init
{
    my ($self) = @_;

    $self->{'restart'} = 0;
    $self->{'forceMailboxesQuotaRecalc'} = 0;
    $self->{'execMode'} = ( defined $main::execmode && $main::execmode eq 'setup' ) ? 'setup' : 'backend';
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'mta'} = Servers::mta->factory();
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/dovecot";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->_mergeConfig() if -f "$self->{'cfgDir'}/dovecot.data.dist";
    tie %{$self->{'config'}},
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/dovecot.data",
        readonly    => !( defined $main::execmode && $main::execmode eq 'setup' ),
        nodeferring => ( defined $main::execmode && $main::execmode eq 'setup' );
    $self;
}

=item _mergeConfig( )

 Merge distribution configuration with production configuration

 Die on failure

=cut

sub _mergeConfig
{
    my ($self) = @_;

    if ( -f "$self->{'cfgDir'}/dovecot.data" ) {
        tie my %newConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/dovecot.data.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/dovecot.data", readonly => 1;

        debug( 'Merging old configuration with new configuration...' );

        while ( my ($key, $value) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/dovecot.data.dist" )->moveFile(
        "$self->{'cfgDir'}/dovecot.data"
    ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
