=head1 NAME

 Servers::ftpd::proftpd - i-MSCP ProFTPD Server implementation

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

package Servers::ftpd::proftpd;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Service;
use File::Basename;
use Scalar::Defer;
use Class::Autouse qw/Servers::ftpd::proftpd::installer Servers::ftpd::proftpd::uninstaller/;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Proftpd Server implementation.

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

    Servers::ftpd::proftpd::installer->getInstance()->registerSetupListeners( $eventManager );
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdPreinstall' );
    $rs ||= $self->stop();
    $rs ||= $self->{'eventManager'}->trigger( 'afterFtpdPreinstall' );
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdInstall', 'proftpd' );
    $rs ||= Servers::ftpd::proftpd::installer->getInstance()->install();
    $rs ||= $self->{'eventManager'}->trigger( 'afterFtpdInstall', 'proftpd' );
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdPostInstall', 'proftpd' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->enable( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->register(
        'beforeSetupRestartServices', sub {
            push @{$_[0]}, [ sub { $self->start(); }, 'ProFTPD' ];
            0;
        }
    );

    $self->{'eventManager'}->trigger( 'afterFtpdPostInstall', 'proftpd' );
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdUninstall', 'proftpd' );
    $rs ||= Servers::ftpd::proftpd::uninstaller->getInstance()->uninstall();
    $self->{'restart'} = 1 unless $rs;
    $rs ||= $self->{'eventManager'}->trigger( 'afterFtpdUninstall', 'proftpd' );
}

=item addUser(\%data)

 Process addUser tasks

 Param hash \%data User data
 Return int 0 on success, other on failure

=cut

sub addUser
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdAddUser', $data );
    return $rs if $rs;

    my $db = iMSCP::Database->factory();

    my $rdata = $db->doQuery(
        'u',
        'UPDATE ftp_users SET uid = ?, gid = ? WHERE admin_id = ?',
        $data->{'USER_SYS_UID'},
        $data->{'USER_SYS_GID'},
        $data->{'USER_ID'}
    );
    unless (ref $rdata eq 'HASH') {
        error( $rdata );
        return 1;
    }

    $rdata = $db->doQuery(
        'u', 'UPDATE ftp_group SET gid = ? WHERE groupname = ?', $data->{'USER_SYS_GID'}, $data->{'USERNAME'}
    );
    unless (ref $rdata eq 'HASH') {
        error( $rdata );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'AfterFtpdAddUser', $data );
}

=item addFtpUser(\%data)

 Add FTP user

 Param hash \%data Ftp user as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub addFtpUser
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdAddFtpUser', $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterFtpdAddFtpUser', $data );
}

=item disableFtpUser(\%data)

 Disable FTP user

 Param hash \%data Ftp user data as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub disableFtpUser
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdDisableFtpUser', $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterFtpdDisableFtpUser', $data );
}

=item deleteFtpUser(\%data)

 Delete FTP user

 Param hash \%data Ftp user data as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub deleteFtpUser
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdDeleteFtpUser', $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterFtpdDeleteFtpUser', $data );
}

=item start()

 Start ProFTPD

 Return int 0, other on failure

=cut

sub start
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdStart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->start( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterFtpdStart' );
}

=item stop()

 Stop ProFTPD

 Return int 0, other on failure

=cut

sub stop
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdStop' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->stop( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterFtpdStop' );
}

=item restart()

 Restart ProFTPD

 Return int 0, other on failure

=cut

sub restart
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdRestart' );
    return $rs if $rs;

    local $@;
    eval {
        my $serviceMngr = iMSCP::Service->getInstance();

        # Mitigate restart problems by waiting a bit before start
        # For instance on Ubuntu Trusty, ProFTPD stay in not running state when using restart command
        $serviceMngr->stop( $self->{'config'}->{'FTPD_SNAME'} );

        # Give ProFTPD sufficient time for stopping
        sleep 2;

        $serviceMngr->start( $self->{'config'}->{'FTPD_SNAME'} );
    };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterFtpdRestart' );
}

=item reload()

 Reload ProFTPD

 Return int 0, other on failure

=cut

sub reload
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdReload' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->reload( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterFtpdReload' );
}

=item getTraffic()

 Get ProFTPD traffic data

 Return hash Traffic database or die on failure

=cut

sub getTraffic
{
    my $self = shift;

    my $trafficDbPath = "$main::imscpConfig{'VARIABLE_DATA_DIR'}/ftp_traffic.db";

    # Load traffic database (create it if needed)
    tie my %trafficDb, 'iMSCP::Config', fileName => $trafficDbPath, nowarn => 1;

    # Traffic data source file
    my $trafficDataSrc = $self->{'config'}->{'FTPD_TRAFF_LOG_PATH'};

    if (-f -s $trafficDataSrc) {
        # Process only if the file exists and is not empty
        require File::Temp;

        # Create snapshot of traffic data source file
        my $tmpFile = File::Temp->new( UNLINK => 1 );
        iMSCP::File->new( filename =>
            $trafficDataSrc )->copyFile( $tmpFile ) == 0 or die( iMSCP::Debug::getLastError() );

        # Reset traffic data source file
        truncate( $trafficDataSrc, 0 ) or die( sprintf( 'Could not truncate %s file: %s', $trafficDataSrc, $! ) );

        # Extract traffic data from snapshot and add them in traffic database
        open my $fh, '<', $tmpFile or die( sprintf( 'Could not open file: %s', $! ) );
        while(<$fh>) {
            $trafficDb{$2} += $1 if /^(?:[^\s]+\s){7}(\d+)\s(?:[^\s]+\s){5}[^\s]+\@([^\s]+)/gm;
        }
        close( $fh );
    } else {
        debug( sprintf( 'No traffic data found in %s - Skipping', $trafficDataSrc ) );
    }

    # Schedule deletion of full traffic database. This is only done on success. On failure, the traffic database is kept
    # in place for later processing. In such case, data already processed are zeroed by the traffic processor script.
    $self->{'eventManager'}->register( 'afterVrlTraffic', sub {
            -f $trafficDbPath ? iMSCP::File->new( filename => $trafficDbPath )->delFile() : 0;
        } );

    \%trafficDb;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::ftpd::proftpd

=cut

sub _init
{
    my $self = shift;

    $self->{'start'} = 0;
    $self->{'restart'} = 0;
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'eventManager'}->trigger( 'beforeFtpdInit', $self,
        'proftpd' ) and fatal( 'proftpd - beforeFtpdInit has failed' );
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/proftpd";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'commentChar'} = '#';
    $self->{'config'} = lazy {
            tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/proftpd.data";
            \%c;
        };
    $self->{'eventManager'}->trigger( 'afterFtpdInit', $self,
        'proftpd' ) and fatal( 'proftpd - afterFtpdInit has failed' );
    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
