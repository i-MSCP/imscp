=head1 NAME

 Servers::ftpd::vsftpd - i-MSCP VsFTPd Server implementation

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Servers::ftpd::vsftpd;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Servers::ftpd::vsftpd::installer Servers::ftpd::vsftpd::uninstaller /;
use File::Basename;
use File::Temp;
use iMSCP::Config;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Rights;
use iMSCP::Service;
use iMSCP::TemplateParser;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP VsFTPd Server implementation.

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

    Servers::ftpd::vsftpd::installer->getInstance()->registerSetupListeners( $eventManager );
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdPreinstall' );
    $rs ||= $self->stop();
    $rs ||= $self->{'eventManager'}->trigger( 'afterFtpdPreinstall' );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdInstall', 'vsftpd' );
    $rs ||= Servers::ftpd::vsftpd::installer->getInstance()->install();
    $rs ||= $self->{'eventManager'}->trigger( 'afterFtpdInstall', 'vsftpd' );
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, die on failure

=cut

sub postinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdPostInstall', 'vsftpd' );

    local $@;
    eval { iMSCP::Service->getInstance()->enable( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs = $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            # We must do a restart here because vsftpd from local package is started while installation
            push @{$_[0]}, [ sub { $self->restart() }, 'VsFTPd server' ];
            0
        },
        4
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterFtpdPostInstall', 'vsftpd' );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, die on failure

=cut

sub uninstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdUninstall', 'vsftpd' );
    $rs ||= Servers::ftpd::vsftpd::uninstaller->getInstance()->uninstall();

    unless ( $rs || !iMSCP::Service->getInstance()->hasService( $self->{'config'}->{'FTPD_SNAME'} ) ) {
        $self->{'restart'} = 1;
    } else {
        $self->{'start'} = 0;
        $self->{'restart'} = 0;
        $self->{'reload'} = 0;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterFtpdUninstall', 'vsftpd' );
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdSetEnginePermissions' );
    $rs ||= setRights(
        $self->{'config'}->{'FTPD_USER_CONF_DIR'},
        {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $main::imscpConfig{'ROOT_GROUP'},
            dirmode   => '0750',
            filemode  => '0640',
            recursive => 1
        }
    );
    $rs ||= setRights(
        $self->{'config'}->{'FTPD_CONF_FILE'},
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0640'
        }
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterFtpdSetEnginePermissions' );
}

=item addUser( \%data )

 Process addUser tasks

 Param hash \%data user data as provided by Modules::User module
 Return int 0 on success, other on failure

=cut

sub addUser
{
    my ($self, $data) = @_;

    return 0 if $data->{'STATUS'} eq 'tochangepwd';

    $self->{'eventManager'}->trigger( 'beforeFtpdAddUser', $data );

    my $dbh = iMSCP::Database->factory()->getRawDb();

    local $@;
    eval {
        local $dbh->{'RaiseError'} = 1;

        $dbh->begin_work();
        $dbh->do(
            'UPDATE ftp_users SET uid = ?, gid = ? WHERE admin_id = ?',
            undef, $data->{'USER_SYS_UID'}, $data->{'USER_SYS_GID'}, $data->{'USER_ID'}
        );
        $dbh->do(
            'UPDATE ftp_group SET gid = ? WHERE groupname = ?', undef, $data->{'USER_SYS_GID'}, $data->{'USERNAME'}
        );
        $dbh->commit();
    };
    if ( $@ ) {
        $dbh->rollback();
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'AfterFtpdAddUser', $data );
}

=item addFtpUser( \%data )

 Add FTP user

 Param hash \%data Ftp user as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub addFtpUser
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdAddFtpUser', $data );
    $rs ||= $self->_createFtpUserConffile( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterFtpdAddFtpUser', $data );
}

=item disableFtpUser( \%data )

 Disable FTP user

 Param hash \%data Ftp user data as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub disableFtpUser
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdDisableFtpUser', $data );
    $rs ||= $self->_deleteFtpUserConffile( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterFtpdDisableFtpUser', $data );
}

=item deleteFtpUser( \%data )

 Delete FTP user

 Param hash \%data Ftp user data as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub deleteFtpUser
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdDeleteFtpUser', $data );
    $rs ||= $self->_deleteFtpUserConffile( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterFtpdDeleteFtpUser', $data );
}

=item start( )

 Start vsftpd

 Return int 0, other on failure

=cut

sub start
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdStart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->start( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterFtpdStart' );
}

=item stop( )

 Stop vsftpd

 Return int 0, other on failure

=cut

sub stop
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdStop' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->stop( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterFtpdStop' );
}

=item restart( )

 Restart vsftpd

 Return int 0, other on failure

=cut

sub restart
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdRestart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterFtpdRestart' );
}

=item reload( )

 Reload vsftpd

 Return int 0, other on failure

=cut

sub reload
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdReload' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->reload( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterFtpdReload' );
}

=item getTraffic( $trafficDb )

 Get VsFTPd traffic data

 Param hashref \%trafficDb Traffic database
 Die on failure

=cut

sub getTraffic
{
    my ($self, $trafficDb) = @_;

    my $logFile = $self->{'config'}->{'FTPD_TRAFF_LOG_PATH'};

    # The log file exists and is not empty
    unless ( -f -s $logFile ) {
        debug( sprintf( 'No new FTP logs found in %s file for processing', $logFile ));
        return;
    }

    debug( sprintf( 'Processing FTP logs from the %s file', $logFile ));

    # Create snapshot of traffic data source file
    my $snapshotFH = File::Temp->new( UNLINK => 1 );
    iMSCP::File->new( filename => $logFile )->copyFile( $snapshotFH->filename, { preserve => 'no' } ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );

    # Reset log file
    # FIXME: We should really avoid truncating. Instead, we should use logrotate.
    truncate( $logFile, 0 ) or die( sprintf( "Couldn't truncate %s file: %s", $logFile, $! ));

    # Extract FTP traffic data
    while ( <$snapshotFH> ) {
        next unless /^(?:[^\s]+\s){7}(?<bytes>\d+)\s(?:[^\s]+\s){5}[^\s]+\@([^\s]+)/o
            && exists $trafficDb->{$+{'domain'}};
        $trafficDb->{$+{'domain'}} += $+{'bytes'};
    }

    $snapshotFH->close();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::ftpd::vsftpd

=cut

sub _init
{
    my ($self) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'start'} = 0;
    $self->{'restart'} = 0;
    $self->{'reload'} = 0;
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/vsftpd";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->_mergeConfig() if -f "$self->{'cfgDir'}/vsftpd.data.dist";
    tie %{$self->{'config'}},
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/vsftpd.data",
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

    if ( -f "$self->{'cfgDir'}/vsftpd.data" ) {
        tie my %newConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/vsftpd.data.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/vsftpd.data", readonly => 1;

        debug( 'Merging old configuration with new configuration...' );

        while ( my ($key, $value) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/vsftpd.data.dist" )->moveFile(
        "$self->{'cfgDir'}/vsftpd.data"
    ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
}

=item _createFtpUserConffile( \%data )

 Create user vsftpd configuration file

 Param hash \%data Ftp user data as provided by Modules::FtpUser module
 Return int 0, other on failure

=cut

sub _createFtpUserConffile
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'vsftpd', 'vsftpd_user.conf', \ my $cfgTpl, $data );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/vsftpd_user.conf" )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read %s file", "$self->{'cfgDir'}/vsftpd_user.conf" ));
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeFtpdBuildConf', \$cfgTpl, 'vsftpd_user.conf' );
    return $rs if $rs;

    $cfgTpl = process( $data, $cfgTpl );

    $rs = $self->{'eventManager'}->trigger( 'afterFtpdBuildConf', \$cfgTpl, 'vsftpd_user.conf' );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'FTPD_USER_CONF_DIR'}/$data->{'USERNAME'}" );
    $file->set( $cfgTpl );
    $file->save();
}

=item _deleteFtpUserConffile(\%data)

 Delete user vsftpd configuration file

 Param hash \%data Ftp user data as provided by Modules::FtpUser module
 Return int 0, other on failure

=cut

sub _deleteFtpUserConffile
{
    my ($self, $data) = @_;

    return 0 unless -f "$self->{'config'}->{'FTPD_USER_CONF_DIR'}/$data->{'USERNAME'}";

    iMSCP::File->new( filename => "$self->{'config'}->{'FTPD_USER_CONF_DIR'}/$data->{'USERNAME'}" )->delFile();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
