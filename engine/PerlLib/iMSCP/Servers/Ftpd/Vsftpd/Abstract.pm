=head1 NAME

 iMSCP::Servers::Ftpd::vsftpd - i-MSCP VsFTPd Server abstract implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Servers::Ftpd::Vsftpd::Abstract;

use strict;
use warnings;
use Cwd;
use File::Basename;
use autouse Fcntl => qw/ O_RDONLY /;
use autouse 'iMSCP::Crypt' => qw/ ALNUM randomStr /;
use autouse 'iMSCP::Dialog::InputValidation' => qw/ isAvailableSqlUser isNumberInRange isOneOfStringsInList isStringNotInList isValidNumberRange
        isValidPassword isValidUsername /;
use autouse 'iMSCP::Execute' => qw/ execute /;
use autouse 'iMSCP::Rights' => qw/ setRights /;
use Class::Autouse qw/ :nostat iMSCP::Database iMSCP::Getopt iMSCP::Servers::Sqld /;
use File::Temp;
use iMSCP::Config;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::File;
use iMSCP::Service;
use iMSCP::TemplateParser qw/ processByRef /;
use iMSCP::Umask;
use version;
use parent 'iMSCP::Common::SingletonClass';

%main::sqlUsers = () unless %main::sqlUsers;

=head1 DESCRIPTION

 i-MSCP VsFTPd Server abstract implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( )

 Register setup event listeners

 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self) = @_;

    $self->{'eventManager'}->registerOne(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->sqlUserDialog( @_ ) }, sub { $self->passivePortRangeDialog( @_ ) };
            0;
        },
        # Same priority as the factory
        150
    );
}

=item sqlUserDialog( \%dialog )

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub sqlUserDialog
{
    my ($self, $dialog) = @_;

    my $masterSqlUser = main::setupGetQuestion( 'DATABASE_USER' );
    my $dbUser = main::setupGetQuestion( 'FTPD_SQL_USER', $self->{'config'}->{'DATABASE_USER'} || ( iMSCP::Getopt->preseed ? 'imscp_srv_user' : '' ));
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $dbPass = main::setupGetQuestion(
        'FTPD_SQL_PASSWORD', ( iMSCP::Getopt->preseed ? randomStr( 16, ALNUM ) : $self->{'config'}->{'DATABASE_PASSWORD'} )
    );

    $iMSCP::Dialog::InputValidation::lastValidationError = '';

    if ( isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ 'ftpd', 'servers', 'all', 'forced' ] )
        || !isValidUsername( $dbUser )
        || !isStringNotInList( lc $dbUser, 'root', 'debian-sys-maint', lc $masterSqlUser, 'vlogger_user' )
        || !isAvailableSqlUser( $dbUser )
    ) {
        my $rs = 0;

        do {
            if ( $dbUser eq '' ) {
                $iMSCP::Dialog::InputValidation::lastValidationError = '';
                $dbUser = 'imscp_srv_user';
            }

            ( $rs, $dbUser ) = $dialog->inputbox( <<"EOF", $dbUser );
$iMSCP::Dialog::InputValidation::lastValidationError
Please enter a username for the VsFTPd SQL user (leave empty for default):
\\Z \\Zn
EOF
        } while $rs < 30
            && ( !isValidUsername( $dbUser )
            || !isStringNotInList( lc $dbUser, 'root', 'debian-sys-maint', lc $masterSqlUser, 'vlogger_user' )
            || !isAvailableSqlUser( $dbUser )
        );

        return $rs unless $rs < 30;
    }

    main::setupSetQuestion( 'FTPD_SQL_USER', $dbUser );

    if ( isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ 'ftpd', 'servers', 'all', 'forced' ] ) || !isValidPassword( $dbPass ) ) {
        unless ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
            my $rs = 0;

            do {
                if ( $dbPass eq '' ) {
                    $iMSCP::Dialog::InputValidation::lastValidationError = '';
                    $dbPass = randomStr( 16, ALNUM );
                }

                ( $rs, $dbPass ) = $dialog->inputbox( <<"EOF", $dbPass );
$iMSCP::Dialog::InputValidation::lastValidationError
Please enter a password for the VsFTPd SQL user (leave empty for autogeneration):
\\Z \\Zn
EOF
            } while $rs < 30 && !isValidPassword( $dbPass );

            return $rs unless $rs < 30;

            $main::sqlUsers{$dbUser . '@' . $dbUserHost} = $dbPass;
        } else {
            $dbPass = $main::sqlUsers{$dbUser . '@' . $dbUserHost};
        }
    } elsif ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
        $dbPass = $main::sqlUsers{$dbUser . '@' . $dbUserHost};
    } else {
        $main::sqlUsers{$dbUser . '@' . $dbUserHost} = $dbPass;
    }

    main::setupSetQuestion( 'FTPD_SQL_PASSWORD', $dbPass );
    0;
}

=item passivePortRangeDialog( \%dialog )

 Ask for VsFTPd port range to use for passive data transfers

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub passivePortRangeDialog
{
    my ($self, $dialog) = @_;

    my $passivePortRange = main::setupGetQuestion(
        'FTPD_PASSIVE_PORT_RANGE', $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'} || ( iMSCP::Getopt->preseed ? '32768 60999' : '' )
    );
    my ($startOfRange, $endOfRange);

    $iMSCP::Dialog::InputValidation::lastValidationError = '';

    if ( !isValidNumberRange( $passivePortRange, \$startOfRange, \$endOfRange )
        || !isNumberInRange( $startOfRange, 32768, 60999 )
        || !isNumberInRange( $endOfRange, $startOfRange, 60999 )
        || isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ 'ftpd', 'servers', 'all', 'forced' ] )
    ) {
        my $rs = 0;

        do {
            if ( $passivePortRange eq '' ) {
                $iMSCP::Dialog::InputValidation::lastValidationError = '';
                $passivePortRange = '32768 60999';
            }

            ( $rs, $passivePortRange ) = $dialog->inputbox( <<"EOF", $passivePortRange );
$iMSCP::Dialog::InputValidation::lastValidationError
\\Z4\\Zb\\ZuVsFTPd passive port range\\Zn

Please enter the passive port range for VsFTPd.

Note that if you're behind a NAT, you must forward those ports to this server.
\\Z \\Zn
EOF
        } while $rs < 30
            && ( !isValidNumberRange( $passivePortRange, \$startOfRange, \$endOfRange )
            || !isNumberInRange( $startOfRange, 32768, 60999 )
            || !isNumberInRange( $endOfRange, $startOfRange, 60999 )
        );

        return $rs unless $rs < 30;
    }

    $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'} = $passivePortRange;
    0;
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    $rs ||= $self->stop();
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->_setVersion();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_buildConfigFile();
    $rs ||= $self->_cleanup();
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, die on failure

=cut

sub postinstall
{
    my ($self) = @_;

    eval { iMSCP::Service->getInstance()->enable( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->registerOne(
        'beforeSetupRestartServices',
        sub {
            # We must do a restart here because vsftpd from local package is started while installation
            push @{$_[0]}, [ sub { $self->restart() }, 'VsFTPd server' ];
            0
        },
        4
    );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, die on failure

=cut

sub uninstall
{
    my ($self) = @_;

    # In setup context, processing must be delayed, else we won't be able to connect to SQL server
    if ( $main::execmode eq 'setup' ) {
        return iMSCP::EventManager->getInstance()->register(
            'afterSqldPreinstall',
            sub {
                my $rs = $self->_dropSqlUser();
                $rs ||= $self->_removeConfig();
            }
        );
    }

    my $rs = $self->_dropSqlUser();
    $rs ||= $self->_removeConfig();

    unless ( $rs || !iMSCP::Service->getInstance()->hasService( $self->{'config'}->{'FTPD_SNAME'} ) ) {
        $self->{'restart'} ||= 1;
    } else {
        @{$self}{ qw/ start restart reload / } = ( 0, 0, 0 );
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterVsftpdUninstall' );
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ($self) = @_;

    my $rs = setRights( $self->{'config'}->{'FTPD_USER_CONF_DIR'},
        {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $main::imscpConfig{'ROOT_GROUP'},
            dirmode   => '0750',
            filemode  => '0640',
            recursive => 1
        }
    );
    $rs ||= setRights( $self->{'config'}->{'FTPD_CONF_FILE'},
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0640'
        }
    );
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

    $self->{'eventManager'}->trigger( 'beforeVsftpdAddUser', $data );

    my $dbh = iMSCP::Database->getInstance()->getRawDb();

    eval {
        local $dbh->{'RaiseError'} = 1;

        $dbh->begin_work();
        $dbh->do(
            'UPDATE ftp_users SET uid = ?, gid = ? WHERE admin_id = ?', undef, $data->{'USER_SYS_UID'}, $data->{'USER_SYS_GID'}, $data->{'USER_ID'}
        );
        $dbh->do( 'UPDATE ftp_group SET gid = ? WHERE groupname = ?', undef, $data->{'USER_SYS_GID'}, $data->{'USERNAME'} );
        $dbh->commit();
    };
    if ( $@ ) {
        $dbh->rollback();
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'AfterVsftpdAddUser', $data );
}

=item addFtpUser( \%data )

 Add FTP user

 Param hash \%data Ftp user as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub addFtpUser
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeVsftpdAddFtpUser', $data );
    $rs ||= $self->_createFtpUserConffile( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterVsftpdAddFtpUser', $data );
}

=item disableFtpUser( \%data )

 Disable FTP user

 Param hash \%data Ftp user data as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub disableFtpUser
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeVsftpdDisableFtpUser', $data );
    $rs ||= $self->_deleteFtpUserConffile( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterVsftpdDisableFtpUser', $data );
}

=item deleteFtpUser( \%data )

 Delete FTP user

 Param hash \%data Ftp user data as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub deleteFtpUser
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeVsftpdDeleteFtpUser', $data );
    $rs ||= $self->_deleteFtpUserConffile( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterVsftpdDeleteFtpUser', $data );
}

=item start( )

 Start vsftpd

 Return int 0, other on failure

=cut

sub start
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeVsftpdStart' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->start( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterVsftpdStart' );
}

=item stop( )

 Stop vsftpd

 Return int 0, other on failure

=cut

sub stop
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeVsftpdStop' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->stop( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterVsftpdStop' );
}

=item restart( )

 Restart vsftpd

 Return int 0, other on failure

=cut

sub restart
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeVsftpdRestart' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->restart( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterVsftpdRestart' );
}

=item reload( )

 Reload vsftpd

 Return int 0, other on failure

=cut

sub reload
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeVsftpdReload' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->reload( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterVsftpdReload' );
}

=item getTraffic( $trafficDb [, $logFile, $trafficIndexDb ] )

 Get VsFTPd traffic data

 Param hashref \%trafficDb Traffic database
 Param string $logFile Path to VsFTPd traffic log file (only when self-called)
 Param hashref $trafficIndexDb Traffic index database (only when self-called)
 Die on failure

=cut

sub getTraffic
{
    my ($self, $trafficDb, $logFile, $trafficIndexDb) = @_;
    $logFile ||= $self->{'config'}->{'FTPD_TRAFF_LOG_PATH'};

    unless ( -f $logFile ) {
        debug( sprintf( "VsFTPd traffic %s log file doesn't exist. Skipping ...", $logFile ));
        return;
    }

    debug( sprintf( 'Processing VsFTPd traffic %s log file', $logFile ));

    # We use an index database to keep trace of the last processed logs
    $trafficIndexDb or tie %{$trafficIndexDb}, 'iMSCP::Config', fileName => "$main::imscpConfig{'IMSCP_HOMEDIR'}/traffic_index.db", nodie => 1;
    my ($idx, $idxContent) = ( $trafficIndexDb->{'vsftpd_lineNo'} || 0, $trafficIndexDb->{'vsftpd_lineContent'} );

    tie my @logs, 'Tie::File', $logFile, mode => O_RDONLY, memory => 0 or die( sprintf( "Couldn't tie %s file in read-only mode", $logFile ));

    # Retain index of the last log (log file can continue growing)
    my $lastLogIdx = $#logs;

    if ( exists $logs[$idx] && $logs[$idx] eq $idxContent ) {
        debug( sprintf( 'Skipping VsFTPd traffic logs that were already processed (lines %d to %d)', 1, ++$idx ));
    } elsif ( $idxContent ne '' && substr( $logFile, -2 ) ne '.1' ) {
        debug( 'Log rotation has been detected. Processing last rotated log file first' );
        $self->getTraffic( $trafficDb, $logFile . '.1', $trafficIndexDb );
        $idx = 0;
    }

    if ( $lastLogIdx < $idx ) {
        debug( 'No new VsFTPd traffic logs found for processing' );
        return;
    }

    debug( sprintf( 'Processing VsFTPd traffic logs (lines %d to %d)', $idx+1, $lastLogIdx+1 ));

    my $regexp = qr/^(?:[^\s]+\s){7}(?<bytes>\d+)\s(?:[^\s]+\s){5}[^\s]+\@(?<domain>[^\s]+)/;

    # In term of memory usage, C-Style loop provide better results than using 
    # range operator in Perl-Style loop: for( @logs[$idx .. $lastLogIdx] ) ...
    for ( my $i = $idx; $i <= $lastLogIdx; $i++ ) {
        next unless $logs[$i] =~ /$regexp/ && exists $trafficDb->{$+{'domain'}};
        $trafficDb->{$+{'domain'}} += $+{'bytes'};
    }

    return if substr( $logFile, -2 ) eq '.1';

    $trafficIndexDb->{'vsftpd_lineNo'} = $lastLogIdx;
    $trafficIndexDb->{'vsftpd_lineContent'} = $logs[$lastLogIdx];
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Servers::Ftpd::Vsftpd::Abstract

=cut

sub _init
{
    my ($self) = @_;

    @{$self}{qw/ start restart reload /} = ( 0, 0, 0 );
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/vsftpd";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->_mergeConfig() if defined $main::execmode && $main::execmode eq 'setup' && -f "$self->{'cfgDir'}/vsftpd.data.dist";
    tie %{$self->{'config'}},
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/vsftpd.data",
        readonly    => !( defined $main::execmode && $main::execmode eq 'setup' ),
        nodeferring => defined $main::execmode && $main::execmode eq 'setup';
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

        debug( 'Merging old configuration with new configuration ...' );

        while ( my ($key, $value) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/vsftpd.data.dist" )->moveFile( "$self->{'cfgDir'}/vsftpd.data" ) == 0 or die(
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
            error( sprintf( "Couldn't read the %s file", "$self->{'cfgDir'}/vsftpd_user.conf" ));
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeVsftpdBuildConf', \$cfgTpl, 'vsftpd_user.conf' );
    return $rs if $rs;

    processByRef( $data, \$cfgTpl );

    $rs = $self->{'eventManager'}->trigger( 'afterVsftpdBuildConf', \$cfgTpl, 'vsftpd_user.conf' );
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

=item _setVersion

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
    my ($self) = @_;

    # Version is print through STDIN (see: strace vsftpd -v)
    my $rs = execute( 'vsftpd -v 0>&1', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    if ( $stdout !~ m%([\d.]+)% ) {
        error( "Couldn't find VsFTPd version from `vsftpd -v 0>&1` command output." );
        return 1;
    }

    $self->{'config'}->{'VSFTPD_VERSION'} = $1;
    debug( sprintf( 'VsFTPd version set to: %s', $1 ));
    0;
}

=item _setupDatabase( )

 Setup database

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
    my ($self) = @_;

    my $dbName = main::setupGetQuestion( 'DATABASE_NAME' );
    my $dbUser = main::setupGetQuestion( 'FTPD_SQL_USER' );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $oldDbUserHost = $main::imscpOldConfig{'DATABASE_USER_HOST'};
    my $dbPass = main::setupGetQuestion( 'FTPD_SQL_PASSWORD' );
    my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

    $self->{'eventManager'}->trigger( 'beforeVsftpdSetupDb', $dbUser, $dbPass );

    eval {
        my $sqlServer = iMSCP::Servers::Sqld->factory();

        # Drop old SQL user if required
        for my $sqlUser ( $dbOldUser, $dbUser ) {
            next unless $sqlUser;

            for my $host( $dbUserHost, $oldDbUserHost ) {
                next if !$host || exists $main::sqlUsers{$sqlUser . '@' . $host} && !defined $main::sqlUsers{$sqlUser . '@' . $host};
                $sqlServer->dropUser( $sqlUser, $host );
            }
        }

        # Create SQL user if required
        if ( defined $main::sqlUsers{$dbUser . '@' . $dbUserHost} ) {
            debug( sprintf( 'Creating %s@%s SQL user', $dbUser, $dbUserHost ));
            $sqlServer->createUser( $dbUser, $dbUserHost, $dbPass );
            $main::sqlUsers{$dbUser . '@' . $dbUserHost} = undef;
        }

        my $dbh = iMSCP::Database->getInstance()->getRawDb();
        local $dbh->{'RaiseError'} = 1;

        # Give required privileges to this SQL user
        # No need to escape wildcard characters. See https://bugs.mysql.com/bug.php?id=18660
        my $quotedDbName = $dbh->quote_identifier( $dbName );
        $dbh->do( "GRANT SELECT ON $quotedDbName.ftp_users TO ?\@?", undef, $dbUser, $dbUserHost );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'config'}->{'DATABASE_USER'} = $dbUser;
    $self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
    $self->{'eventManager'}->trigger( 'afterVsftpdSetupDb', $dbUser, $dbPass );
}

=item _buildConfigFile( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfigFile
{
    my ($self) = @_;

    # Make sure to start with clean user configuration directory
    unlink glob "$self->{'config'}->{'FTPD_USER_CONF_DIR'}/*";

    my ($passvMinPort, $passvMaxPort) = split( /\s+/, $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'} );
    my $data = {
        IPV4_ONLY              => main::setupGetQuestion( 'IPV6_SUPPORT' ) ? 'NO' : 'YES',
        IPV6_SUPPORT           => main::setupGetQuestion( 'IPV6_SUPPORT' ) ? 'YES' : 'NO',
        DATABASE_NAME          => main::setupGetQuestion( 'DATABASE_NAME' ),
        DATABASE_HOST          => main::setupGetQuestion( 'DATABASE_HOST' ),
        DATABASE_PORT          => main::setupGetQuestion( 'DATABASE_PORT' ),
        DATABASE_USER          => $self->{'config'}->{'DATABASE_USER'},
        DATABASE_PASS          => $self->{'config'}->{'DATABASE_PASSWORD'},
        FTPD_BANNER            => $self->{'config'}->{'FTPD_BANNER'},
        FRONTEND_USER_SYS_NAME => $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'},
        PASSV_ENABLE           => $self->{'config'}->{'PASSV_ENABLE'},
        PASSV_MIN_PORT         => $passvMinPort,
        PASSV_MAX_PORT         => $passvMaxPort,
        FTP_MAX_CLIENTS        => $self->{'config'}->{'FTP_MAX_CLIENTS'},
        MAX_PER_IP             => $self->{'config'}->{'MAX_PER_IP'},
        LOCAL_MAX_RATE         => $self->{'config'}->{'LOCAL_MAX_RATE'},
        USER_WEB_DIR           => $main::imscpConfig{'USER_WEB_DIR'},
        FTPD_USER_CONF_DIR     => $self->{'config'}->{'FTPD_USER_CONF_DIR'}
    };

    # vsftpd main configuration file

    my $rs = $self->_bkpConfFile( $self->{'config'}->{'FTPD_CONF_FILE'} );
    $rs ||= $self->{'eventManager'}->trigger( 'onLoadTemplate', 'vsftpd', 'vsftpd.conf', \ my $cfgTpl, $data );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/vsftpd.conf" )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read the %s file", "$self->{'cfgDir'}/vsftpd.conf" ));
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeVsftpdBuildConf', \$cfgTpl, 'vsftpd.conf' );
    return $rs if $rs;

    if ( $self->_isVsFTPdInsideCt() ) {
        $cfgTpl .= <<'EOF';

# VsFTPd run inside unprivileged VE
# See http://youtrack.i-mscp.net/issue/IP-1503
seccomp_sandbox=NO
EOF
    }

    my $baseServerPublicIp = main::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' );
    if ( $main::imscpConfig{'BASE_SERVER_IP'} ne $baseServerPublicIp ) {
        $cfgTpl .= <<"EOF";

# Server behind NAT - Advertise public IP address
pasv_address=$baseServerPublicIp
pasv_promiscuous=YES
EOF
    }

    if ( main::setupGetQuestion( 'SERVICES_SSL_ENABLED' ) eq 'yes' ) {
        $cfgTpl .= <<"EOF";

# SSL support
ssl_enable=YES
force_local_data_ssl=NO
force_local_logins_ssl=NO
ssl_sslv2=NO
ssl_sslv3=NO
ssl_tlsv1=YES
require_ssl_reuse=NO
ssl_ciphers=HIGH
rsa_cert_file=$main::imscpConfig{'CONF_DIR'}/imscp_services.pem
rsa_private_key_file=$main::imscpConfig{'CONF_DIR'}/imscp_services.pem
EOF
    }

    processByRef( $data, \$cfgTpl );

    $rs = $self->{'eventManager'}->trigger( 'afterVsftpdBuildConf', \$cfgTpl, 'vsftpd.conf' );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'FTPD_CONF_FILE'} );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0640 );
    return $rs if $rs;

    # VsFTPd pam-mysql configuration file
    undef $cfgTpl;

    local $UMASK = 027; # vsftpd.pam file must not be created/copied world-readable
    $rs = $self->_bkpConfFile( $self->{'config'}->{'FTPD_PAM_CONF_FILE'} );
    $rs ||= $self->{'eventManager'}->trigger( 'onLoadTemplate', 'vsftpd', 'vsftpd.pam', \$cfgTpl, $data );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/vsftpd.pam" )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read the %s file", "$self->{'cfgDir'}/vsftpd.pam" ));
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeVsftpdBuildConf', \$cfgTpl, 'vsftpd.pam' );
    return $rs if $rs;

    processByRef( $data, \$cfgTpl );

    $rs = $self->{'eventManager'}->trigger( 'afterVsftpdBuildConf', \$cfgTpl, 'vsftpd.pam' );
    return $rs if $rs;

    $file = iMSCP::File->new( filename => $self->{'config'}->{'FTPD_PAM_CONF_FILE'} );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0640 );
}

=item _bkpConfFile( )

 Backup file

 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
    my ($self, $cfgFile) = @_;

    return 0 unless -f $cfgFile;

    my $file = iMSCP::File->new( filename => $cfgFile );
    my $basename = basename( $cfgFile );

    unless ( -f "$self->{'bkpDir'}/$basename.system" ) {
        my $rs = $file->copyFile( "$self->{'bkpDir'}/$basename.system", { preserve => 'no' } );
        return $rs if $rs;
    } else {
        my $rs = $file->copyFile( "$self->{'bkpDir'}/$basename." . time, { preserve => 'no' } );
        return $rs if $rs;
    }

    0;
}

=item _isVsFTPdInsideCt( )

 Does the VsFTPd server is run inside an unprivileged VE (OpenVZ container)

 Return bool TRUE if the VsFTPd server is run inside an OpenVZ container, FALSE otherwise

=cut

sub _isVsFTPdInsideCt
{
    return 0 unless -f '/proc/user_beancounters';

    my $rs = execute( 'cat /proc/1/status | grep --color=never envID', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    debug( $stderr ) if $rs && $stderr;
    return $rs if $rs;

    if ( $stdout =~ /envID:\s+(\d+)/ ) {
        return ( $1 > 0 ) ? 1 : 0;
    }

    0;
}

=item _cleanup( )

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ($self) = @_;

    return 0 unless -f "$self->{'cfgDir'}/vsftpd.old.data";

    iMSCP::File->new( filename => "$self->{'cfgDir'}/vsftpd.old.data" )->delFile();
}

=item _dropSqlUser( )

 Drop SQL user

 Return int 0 on success, 1 on failure

=cut

sub _dropSqlUser
{
    my ($self) = @_;

    # In setup context, take value from old conffile, else take value from current conffile
    my $dbUserHost = ( $main::execmode eq 'setup' ) ? $main::imscpOldConfig{'DATABASE_USER_HOST'} : $main::imscpConfig{'DATABASE_USER_HOST'};

    return 0 unless $self->{'config'}->{'DATABASE_USER'} && $dbUserHost;

    eval { iMSCP::Servers::Sqld->factory()->dropUser( $self->{'config'}->{'DATABASE_USER'}, $dbUserHost ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _removeConfig( )

 Remove configuration

 Return int 0 on success, other on failure

=cut

sub _removeConfig
{
    my ($self) = @_;

    for ( $self->{'config'}->{'FTPD_CONF_FILE'}, $self->{'config'}->{'FTPD_PAM_CONF_FILE'} ) {
        # Setup context means switching to another FTP server. In such case, we simply delete the files
        if ( $main::execmode eq 'setup' ) {
            if ( -f $self->{'config'}->{'FTPD_CONF_FILE'} ) {
                my $rs = iMSCP::File->new( filename => $self->{'config'}->{'FTPD_CONF_FILE'} )->delFile();
                return $rs if $rs;
            }

            my $filename = basename( $_ );

            if ( -f "$self->{'bkpDir'}/$filename.system" ) {
                my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$filename.system" )->delFile();
                return $rs if $rs;
            }

            next;
        }

        my $dirname = dirname( $self->{'config'}->{'FTPD_CONF_FILE'} );
        my $filename = basename( $_ );

        if ( -d $dirname && -f "$self->{'bkpDir'}/$filename.system" ) {
            my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$filename.system" )->copyFile(
                $self->{'config'}->{'FTPD_CONF_FILE'}, { preserve => 'no' }
            );
            return $rs if $rs;
        }
    }

    eval { iMSCP::Dir->new( dirname => $self->{'config'}->{'FTPD_USER_CONF_DIR'} )->remove(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
