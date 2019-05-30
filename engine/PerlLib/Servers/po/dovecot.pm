=head1 NAME

 Servers::po::dovecot - i-MSCP Dovecot IMAP/POP3 Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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
use File::Spec;
use File::Temp;
use iMSCP::Boolean;
use iMSCP::Config;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Rights 'setRights';
use iMSCP::Service;
use List::MoreUtils 'uniq';
use Servers::mta;
use Sort::Naturally;
use Tie::File;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Dovecot IMAP/POP3 Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%events )

 Register setup event listeners

 Param iMSCP::EventManager \%events
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( undef, $events ) = @_;

    Servers::po::dovecot::installer->getInstance()->registerSetupListeners(
        $events
    );
}

=item preinstall( )

 Pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforePoPreinstall', 'dovecot' );
    $rs ||= Servers::po::dovecot::installer->getInstance()->preinstall();
    $rs ||= $self->{'events'}->trigger( 'afterPoPreinstall', 'dovecot' );
}

=item install( )

 Installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforePoInstall', 'dovecot' );
    $rs ||= Servers::po::dovecot::installer->getInstance()->install();
    $rs ||= $self->{'events'}->trigger( 'afterPoInstall', 'dovecot' );
}

=item postinstall( )

 Post-installation tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforePoPostinstall', 'dovecot' );
    $rs ||= Servers::po::dovecot::installer->getInstance()->postinstall();
    $rs ||= $self->{'events'}->trigger( 'afterPoPostinstall', 'dovecot' );
}

=item uninstall( )

 Uninstallation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforePoUninstall', 'dovecot' );
    $rs ||= Servers::po::dovecot::uninstaller->getInstance()->uninstall();
    $rs ||= $self->{'events'}->trigger( 'afterPoUninstall', 'dovecot' );
    return $rs if $rs;

    if ( iMSCP::Service->getInstance()->hasService(
        $self->{'config'}->{'DOVECOT_SNAME'}
    ) ) {
        $self->{'restart'} = TRUE;
    } else {
        $self->{'restart'} = FALSE;
    }

    0;
}

=item addMail( \%data )

 Process addMail tasks

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub addMail
{
    my ( $self, $data ) = @_;

    return 0 unless index( $data->{'MAIL_TYPE'}, '_mail' ) != -1;

    my $mailDir = "$self->{'mta'}->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}/$data->{'DOMAIN_NAME'}/$data->{'MAIL_ACC'}";
    my $mailUidName = $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'};
    my $mailGidName = $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'};

    # Mailboxes

    # Note: We also create the sieve directory, even if sieve isn't
    # available. See the comments about sieve below for further explanation.
    local $@;
    eval {
        for my $mailbox ( qw/ .Drafts .Junk .Sent .Trash sieve / ) {
            iMSCP::Dir->new( dirname => "$mailDir/$mailbox" )->make( {
                user           => $mailUidName,
                group          => $mailGidName,
                mode           => 0750,
                fixpermissions => iMSCP::Getopt->fixPermissions
            } );

            next if $mailbox eq 'sieve';

            for my $dir ( qw/ cur new tmp / ) {
                iMSCP::Dir->new( dirname => "$mailDir/$mailbox/$dir" )->make( {
                    user           => $mailUidName,
                    group          => $mailGidName,
                    mode           => 0750,
                    fixpermissions => iMSCP::Getopt->fixPermissions
                } );
            }
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # Subscriptions

    my @subscribedFolders = qw/ Drafts Junk Sent Trash /;
    my $subscriptionsFile = iMSCP::File->new(
        filename => "$mailDir/subscriptions"
    );

    if ( -f "$mailDir/subscriptions" ) {
        return 1 unless defined(
            my $subscriptionsFileContent = $subscriptionsFile->get()
        );

        if ( $subscriptionsFileContent ne '' ) {
            @subscribedFolders = nsort uniq(
                @subscribedFolders, split( /\n/, $subscriptionsFileContent
            ));
        }
    }

    $subscriptionsFile->set( ( join "\n", @subscribedFolders ) . "\n" );
    my $rs = $subscriptionsFile->save();
    $rs ||= $subscriptionsFile->owner( $mailUidName, $mailGidName );
    $rs ||= $subscriptionsFile->mode( 0640 );
    return $rs if $rs;

    # Sieve filters

    # Unless a sieve script (filters) is already present, we provide a default
    # one that define two rules: one for moving SPAM to Junk folder, and another
    # one for vacation. However, by default, only the 'spam' rule need to be
    # enabled because this doesn't make sense to enable vacation for new email
    # accounts.
    #
    # If we don't do that, users will have to enable sieve through the Roundcube
    # managesieve plugin and that's not acceptable for beginners, mostly for SPAM
    # filtering...
    unless ( -f "$mailDir/sieve/managesieve.sieve" ) {
        my $file = iMSCP::File->new( filename => "$self->{'cfgDir'}/sieve.default" );
        $rs = $file->copyFile( "$mailDir/sieve/managesieve.sieve", { preserve => 'no' } );
        $rs ||= $file->owner( $mailUidName, $mailGidName );
        $rs ||= $file->mode( 0600 );
        return $rs if $rs;
    }

    # We need also create the symlink to the sieve filters script
    # Note: The 'dovecot.sieve' symlink was the one created by the old
    # RoundcubePlugins plugin (managesieve plugin configuration). It is now
    # '.dovecot.sieve'. We need remove it if present...
    for my $lnk ( qw/ .dovecot.sieve dovecot.sieve / ) {
        next unless -l "$mailDir/$lnk";
        $rs = iMSCP::File->new( filename => "$mailDir/$lnk" )->delFile();
        return $rs if $rs;
    }

    unless ( symlink(
        File::Spec->abs2rel( "$mailDir/sieve/managesieve.sieve", $mailDir ),
        "$mailDir/.dovecot.sieve"
    ) ) {
        error( sprintf( "Couldn't create symlink for managesieve" ));
        return 1;
    }

    $rs = iMSCP::File->new( filename => "$mailDir/.dovecot.sieve" )->owner(
        $mailUidName, $mailGidName
    );
    return $rs if $rs;

    # Quota

    if ( $data->{'MAIL_QUOTA'} ) {
        if ( $self->{'forceMailboxesQuotaRecalc'}
            || ( $self->{'execMode'} eq 'backend' && $data->{'STATUS'} eq 'tochange' )
            || !-f "$mailDir/maildirsize"
        ) {
            # TODO create maildirsize file manually (set quota definition and
            # recalculate byte and file counts)
            if ( -f "$mailDir/maildirsize" ) {
                $rs = iMSCP::File->new(
                    filename => "$mailDir/maildirsize"
                )->delFile();
                return $rs if $rs;
            }
        }

        return 0;
    }

    if ( -f "$mailDir/maildirsize" ) {
        $rs = iMSCP::File->new(
            filename => "$mailDir/maildirsize"
        )->delFile();
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
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforePoSetEnginePermissions' );
    $rs ||= setRights( $self->{'config'}->{'DOVECOT_CONF_DIR'}, {
        user  => $::imscpConfig{'ROOT_USER'},
        group => $::imscpConfig{'ROOT_GROUP'},
        mode  => '0755'
    } );
    $rs ||= setRights(
        "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot.conf",
        {
            user  => $::imscpConfig{'ROOT_USER'},
            group => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            mode  => '0640'
        }
    );
    $rs ||= setRights(
        "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf",
        {
            user  => $::imscpConfig{'ROOT_USER'},
            group => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            mode  => '0640'
        }
    );
    $rs ||= setRights(
        "$::imscpConfig{'ENGINE_ROOT_DIR'}/quota/imscp-dovecot-quota.sh",
        {
            user  => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_UID_NAME'},
            group => $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'},
            mode  => '0750'
        }
    );
    $rs ||= $self->{'events'}->trigger( 'afterPoSetEnginePermissions' );
}

=item start( )

 Start Dovecot

 Return int 0 on success, other on failure

=cut

sub start
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforePoStart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->start(
        $self->{'config'}->{'DOVECOT_SNAME'}
    ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterPoStart' );
}

=item stop( )

 Stop Dovecot

 Return int 0 on success, other on failure

=cut

sub stop
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforePoStop' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->stop(
        $self->{'config'}->{'DOVECOT_SNAME'}
    ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterPoStop' );
}

=item restart( )

 Restart Dovecot

 Return int 0 on success, other on failure

=cut

sub restart
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforePoRestart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart(
        $self->{'config'}->{'DOVECOT_SNAME'}
    ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterPoRestart' );
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
    my ( $self, $trafficDb, $logFile, $trafficIndexDb ) = @_;

    $logFile ||= "$::imscpConfig{'TRAFF_LOG_DIR'}/$::imscpConfig{'MAIL_TRAFF_LOG'}";

    if ( -f -s $logFile ) {
        # We use an index database file to keep trace of the last processed log
        $trafficIndexDb or tie %{ $trafficIndexDb },
            'iMSCP::Config',
            fileName => "$::imscpConfig{'IMSCP_HOMEDIR'}/traffic_index.db",
            nodie    => TRUE;

        my ( $idx, $idxContent ) = (
            $trafficIndexDb->{'po_lineNo'} || 0,
            $trafficIndexDb->{'po_lineContent'}
        );

        # Create a snapshot of current log file state
        my $snapshotFH = File::Temp->new();
        iMSCP::File->new(
            filename => $logFile
        )->copyFile(
            $snapshotFH->filename(), { preserve => 'no' }
        ) == 0 or die( getMessageByType(
            'error', { amount => 1, remove => TRUE }
        ) || 'Unknown error' );

        # Tie the snapshot for easy handling
        tie my @snapshot, 'Tie::File', $snapshotFH,
            memory => 10_485_760 or die( sprintf(
            "Couldn't tie %s file", $snapshotFH->filename()
        ));

        # We keep trace of the index for the live log file only
        unless ( $logFile =~ /\.1$/ ) {
            $trafficIndexDb->{'po_lineNo'} = $#snapshot;
            $trafficIndexDb->{'po_lineContent'} = $snapshot[$#snapshot];
        }

        debug( sprintf( 'Processing IMAP/POP3 logs from the %s file', $logFile ));

        # We have already seen the log file in the past. We must skip logs that
        # were already processed
        if ( $snapshot[$idx] && $snapshot[$idx] eq $idxContent ) {
            debug( sprintf(
                'Skipping logs that were already processed (lines %d to %d)',
                1,
                ++$idx
            ));

            my $logsFound = ( @snapshot = @snapshot[$idx .. $#snapshot] ) > 0;
            untie( @snapshot );

            unless ( $logsFound ) {
                debug( sprintf(
                    'No new IMAP/POP3 logs found in %s file for processing', $logFile
                ));
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
        debug(
            'The %s log file is empty. Processing last rotated log file',
            $logFile
        );
        $self->getTraffic( $trafficDb, $logFile . '.1', $trafficIndexDb );
    } else {
        # There are no new logs found for processing
        debug( sprintf(
            'No new IMAP/POP3 logs found in %s file for processing', $logFile
        ));
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
    my ( $self ) = @_;

    $self->{'restart'} = FALSE;
    $self->{'forceMailboxesQuotaRecalc'} = FALSE;
    $self->{'execMode'} = ( defined $::execmode && $::execmode eq 'setup' )
        ? 'setup' : 'backend';
    $self->{'events'} = iMSCP::EventManager->getInstance();
    $self->{'mta'} = Servers::mta->factory();
    $self->{'cfgDir'} = "$::imscpConfig{'CONF_DIR'}/dovecot";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->_mergeConfig() if -f "$self->{'cfgDir'}/dovecot.data.dist";
    tie %{ $self->{'config'} },
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/dovecot.data",
        readonly    => !( defined $::execmode && $::execmode eq 'setup' ),
        nodeferring => ( defined $::execmode && $::execmode eq 'setup' );
    $self;
}

=item _mergeConfig( )

 Merge distribution configuration with production configuration

 Die on failure

=cut

sub _mergeConfig
{
    my ( $self ) = @_;

    if ( -f "$self->{'cfgDir'}/dovecot.data" ) {
        tie my %newConfig, 'iMSCP::Config',
            fileName => "$self->{'cfgDir'}/dovecot.data.dist";
        tie my %oldConfig, 'iMSCP::Config',
            fileName => "$self->{'cfgDir'}/dovecot.data", readonly => TRUE;

        debug( 'Merging old configuration with new configuration...' );

        while ( my ( $key, $value ) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        %{ $self->{'oldConfig'} } = ( %oldConfig );

        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new(
        filename => "$self->{'cfgDir'}/dovecot.data.dist"
    )->moveFile(
        "$self->{'cfgDir'}/dovecot.data"
    ) == 0 or die( getMessageByType(
        'error', { amount => 1, remove => TRUE }
    ) || 'Unknown error' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
