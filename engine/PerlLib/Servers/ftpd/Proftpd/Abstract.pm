=head1 NAME

 Servers::ftpd::proftpd - i-MSCP ProFTPD Server abstract implementation

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package Servers::ftpd::Proftpd::Abstract;

use strict;
use warnings;
use autouse Fcntl => qw/ O_RDONLY /;
use autouse 'iMSCP::Crypt' => qw/ ALNUM randomStr /;
use autouse 'iMSCP::Dialog::InputValidation' => qw/ isAvailableSqlUser isNumberInRange isOneOfStringsInList isStringNotInList isValidNumberRange
        isValidPassword isValidUsername /;
use autouse 'iMSCP::Execute' => qw / execute /;
use autouse 'iMSCP::Rights' => qw/ setRights /;
use autouse 'iMSCP::TemplateParser' => qw/ processByRef /;
use Class::Autouse qw/ :nostat iMSCP::Database iMSCP::File iMSCP::Getopt Servers::sqld /;
use File::Basename;
use File::Temp;
use iMSCP::Config;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Umask;
use iMSCP::Service;
use parent 'Common::SingletonClass';

%main::sqlUsers = () unless %main::sqlUsers;

=head1 DESCRIPTION

 i-MSCP Proftpd Server abstract implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( )

 Register setup event listeners

 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self) = @_;

    $self->{'eventManager'}->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->sqlUserDialog( @_ ) }, sub { $self->passivePortRangeDialog( @_ ) };
            0;
        }
    );
}

=item sqlUserDialog( \%dialog )

 Ask for ProFTPD SQL user

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
Please enter a username for the ProFTPD SQL user (leave empty for default):
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
Please enter a password for the ProFTPD SQL user (leave empty for autogeneration):
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

 Ask for ProtFTPD port range to use for passive data transfers

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

    if ( isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ 'ftpd', 'servers', 'all', 'forced' ] )
        || !isValidNumberRange( $passivePortRange, \$startOfRange, \$endOfRange )
        || !isNumberInRange( $startOfRange, 32768, 60999 )
        || !isNumberInRange( $endOfRange, $startOfRange, 60999 )
    ) {
        my $rs = 0;

        do {
            if ( $passivePortRange eq '' ) {
                $iMSCP::Dialog::InputValidation::lastValidationError = '';
                $passivePortRange = '32768 60999';
            }

            ( $rs, $passivePortRange ) = $dialog->inputbox( <<"EOF", $passivePortRange );
$iMSCP::Dialog::InputValidation::lastValidationError
\\Z4\\Zb\\ZuProFTPD passive port range\\Zn

Please enter the passive port range for ProFTPD (leave empty for default).

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

    $self->stop();
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->_bkpConfFile( $self->{'config'}->{'FTPD_CONF_FILE'} );
    $rs ||= $self->_setVersion();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_buildConfigFile();
    $rs ||= $self->_cleanup();
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    eval { iMSCP::Service->getInstance()->enable( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]}, [ sub { $self->start(); }, 'ProFTPD' ];
            0;
        },
        4
    );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

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

    $rs;
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ($self) = @_;

    setRights( $self->{'config'}->{'FTPD_CONF_FILE'},
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0640'
        }
    );
}

=item addUser( \%data )

 Process addUser tasks

 Param hash \%data Data as provided by Modules::User module
 Return int 0 on success, other on failure

=cut

sub addUser
{
    my ($self, $data) = @_;

    return 0 if $data->{'STATUS'} eq 'tochangepwd';

    my $rs = $self->{'eventManager'}->trigger( 'beforeProftpdAddUser', $data );
    return $rs if $rs;

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

    $self->{'eventManager'}->trigger( 'AfterProftpdAddUser', $data );
}

=item addFtpUser( \%data )

 Add FTP user

 Param hash \%data Ftp user as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub addFtpUser
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeProftpdAddFtpUser', $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterProftpdAddFtpUser', $data );
}

=item disableFtpUser( \%data )

 Disable FTP user

 Param hash \%data Ftp user data as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub disableFtpUser
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeProftpdDisableFtpUser', $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterProftpdDisableFtpUser', $data );
}

=item deleteFtpUser( \%data )

 Delete FTP user

 Param hash \%data Ftp user data as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub deleteFtpUser
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeProftpdDeleteFtpUser', $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterProftpdDeleteFtpUser', $data );
}

=item start( )

 Start ProFTPD

 Return int 0, other on failure

=cut

sub start
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeProftpdStart' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->start( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterProftpdStart' );
}

=item stop( )

 Stop ProFTPD

 Return int 0, other on failure

=cut

sub stop
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeProftpdStop' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->stop( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterProftpdStop' );
}

=item restart( )

 Restart ProFTPD

 Return int 0, other on failure

=cut

sub restart
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeProftpdRestart' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->restart( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterProftpdRestart' );
}

=item reload( )

 Reload ProFTPD

 Return int 0, other on failure

=cut

sub reload
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeProftpdReload' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->reload( $self->{'config'}->{'FTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterProftpdReload' );
}

=item getTraffic( $trafficDb [, $logFile, $trafficIndexDb ] )

 Get ProFTPD traffic data

 Param hashref \%trafficDb Traffic database
 Param string $logFile Path to ProFTPD traffic log file (only when self-called)
 Param hashref $trafficIndexDb Traffic index database (only when self-called)
 Die on failure

=cut

sub getTraffic
{
    my ($self, $trafficDb, $logFile, $trafficIndexDb) = @_;
    $logFile ||= $self->{'config'}->{'FTPD_TRAFF_LOG_PATH'};

    unless ( -f $logFile ) {
        debug( sprintf( "ProFTPD traffic %s log file doesn't exist. Skipping ...", $logFile ));
        return;
    }

    debug( sprintf( 'Processing ProFTPD traffic %s log file', $logFile ));

    # We use an index database to keep trace of the last processed logs
    $trafficIndexDb or tie %{$trafficIndexDb},
        'iMSCP::Config', fileName => "$main::imscpConfig{'IMSCP_HOMEDIR'}/traffic_index.db", nodie => 1;
    my ($idx, $idxContent) = ( $trafficIndexDb->{'proftpd_lineNo'} || 0, $trafficIndexDb->{'proftpd_lineContent'} );

    tie my @logs, 'Tie::File', $logFile, mode => O_RDONLY, memory => 0 or die( sprintf( "Couldn't tie %s file in read-only mode", $logFile ));

    # Retain index of the last log (log file can continue growing)
    my $lastLogIdx = $#logs;

    if ( exists $logs[$idx] && $logs[$idx] eq $idxContent ) {
        debug( sprintf( 'Skipping ProFTPD traffic logs that were already processed (lines %d to %d)', 1, ++$idx ));
    } elsif ( $idxContent ne '' && substr( $logFile, -2 ) ne '.1' ) {
        debug( 'Log rotation has been detected. Processing last rotated log file first' );
        $self->getTraffic( $trafficDb, $logFile . '.1', $trafficIndexDb );
        $idx = 0;
    }

    if ( $lastLogIdx < $idx ) {
        debug( 'No new ProFTPD traffic logs found for processing' );
        return;
    }

    debug( sprintf( 'Processing ProFTPD traffic logs (lines %d to %d)', $idx+1, $lastLogIdx+1 ));

    my $regexp = qr/^(?:[^\s]+\s){7}(?<bytes>\d+)\s(?:[^\s]+\s){5}[^\s]+\@(?<domain>[^\s]+)/;

    # In term of memory usage, C-Style loop provide better results than using 
    # range operator in Perl-Style loop: for( @logs[$idx .. $lastLogIdx] ) ...
    for ( my $i = $idx; $i <= $lastLogIdx; $i++ ) {
        next unless $logs[$i] =~ /$regexp/ && exists $trafficDb->{$+{'domain'}};
        $trafficDb->{$+{'domain'}} += $+{'bytes'};
    }

    return if substr( $logFile, -2 ) eq '.1';

    $trafficIndexDb->{'proftpd_lineNo'} = $lastLogIdx;
    $trafficIndexDb->{'proftpd_lineContent'} = $logs[$lastLogIdx];
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::ftpd::Proftpd::Abstract

=cut

sub _init
{
    my ($self) = @_;

    @{$self}{qw/ start restart reload /} = ( 0, 0, 0 );
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/proftpd";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->_mergeConfig() if defined $main::execmode && $main::execmode eq 'setup' && -f "$self->{'cfgDir'}/proftpd.data.dist";
    tie %{$self->{'config'}},
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/proftpd.data",
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

    if ( -f "$self->{'cfgDir'}/proftpd.data" ) {
        tie my %newConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/proftpd.data.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/proftpd.data", readonly => 1;

        debug( 'Merging old configuration with new configuration ...' );

        while ( my ($key, $value) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        untie( %newConfig );
        untie( %oldConfig );

        iMSCP::File->new( filename => "$self->{'cfgDir'}/proftpd.data" )->delFile();
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/proftpd.data.dist" )->moveFile( "$self->{'cfgDir'}/proftpd.data" ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
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
    my ($filename, undef, $suffix) = fileparse( $cfgFile );

    unless ( -f "$self->{'bkpDir'}/$filename$suffix.system" ) {
        my $rs = $file->copyFile( "$self->{'bkpDir'}/$filename$suffix.system", { preserve => 'no' } );
        return $rs if $rs;
    } else {
        my $rs = $file->copyFile( "$self->{'bkpDir'}/$filename$suffix." . time, { preserve => 'no' } );
        return $rs if $rs;
    }

    0;
}

=item _setVersion

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
    my ($self) = @_;

    my $rs = execute( 'proftpd -v', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    if ( $stdout !~ m%([\d.]+)% ) {
        error( "Couldn't find ProFTPD version from `proftpd -v` command output." );
        return 1;
    }

    $self->{'config'}->{'PROFTPD_VERSION'} = $1;
    debug( "ProFTPD version set to: $1" );
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

    my $rs = $self->{'eventManager'}->trigger( 'beforeProftpdSetupDb', $dbUser, $dbPass );
    return $rs if $rs;

    eval {
        my $sqlServer = Servers::sqld->factory();

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
        for ( 'ftp_users', 'ftp_group' ) {
            $dbh->do( "GRANT SELECT ON $quotedDbName.$_ TO ?\@?", undef, $dbUser, $dbUserHost );
        }

        for ( 'quotalimits', 'quotatallies' ) {
            $dbh->do( "GRANT SELECT, INSERT, UPDATE ON $quotedDbName.$_ TO ?\@?", undef, $dbUser, $dbUserHost );
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'config'}->{'DATABASE_USER'} = $dbUser;
    $self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
    $self->{'eventManager'}->trigger( 'afterProftpdSetupDb', $dbUser, $dbPass );
}

=item _buildConfigFile( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfigFile
{
    my ($self) = @_;

    # Escape any double-quotes and backslash (see #IP-1330)
    ( my $dbUser = $self->{'config'}->{'DATABASE_USER'} ) =~ s%("|\\)%\\$1%g;
    ( my $dbPass = $self->{'config'}->{'DATABASE_PASSWORD'} ) =~ s%("|\\)%\\$1%g;

    my $data = {
        IPV6_SUPPORT            => main::setupGetQuestion( 'IPV6_SUPPORT' ) ? 'on' : 'off',
        HOSTNAME                => main::setupGetQuestion( 'SERVER_HOSTNAME' ),
        DATABASE_NAME           => main::setupGetQuestion( 'DATABASE_NAME' ),
        DATABASE_HOST           => main::setupGetQuestion( 'DATABASE_HOST' ),
        DATABASE_PORT           => main::setupGetQuestion( 'DATABASE_PORT' ),
        DATABASE_USER           => qq/"$dbUser"/,
        DATABASE_PASS           => qq/"$dbPass"/,
        FTPD_MIN_UID            => $self->{'config'}->{'MIN_UID'},
        FTPD_MIN_GID            => $self->{'config'}->{'MIN_GID'},
        FTPD_PASSIVE_PORT_RANGE => $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'},
        CONF_DIR                => $main::imscpConfig{'CONF_DIR'},
        CERTIFICATE             => 'imscp_services',
        SERVER_IDENT_MESSAGE    => '"[' . main::setupGetQuestion( 'SERVER_HOSTNAME' ) . '] i-MSCP FTP server."',
        TLSOPTIONS              => 'NoCertRequest NoSessionReuseRequired',
        MAX_INSTANCES           => $self->{'config'}->{'MAX_INSTANCES'},
        MAX_CLIENT_PER_HOST     => $self->{'config'}->{'MAX_CLIENT_PER_HOST'}
    };

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'proftpd', 'proftpd.conf', \ my $cfgTpl, $data );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/proftpd.conf" )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read to read the %s file", "$self->{'cfgDir'}/proftpd.conf" ));
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeProftpdBuildConf', \$cfgTpl, 'proftpd.conf' );
    return $rs if $rs;

    if ( main::setupGetQuestion( 'SERVICES_SSL_ENABLED' ) eq 'yes' ) {
        $cfgTpl .= <<'EOF';

# SSL configuration
<Global>
<IfModule mod_tls.c>
  TLSEngine                on
  TLSRequired              off
  TLSLog                   /var/log/proftpd/ftp_ssl.log
  TLSOptions               {TLSOPTIONS}
  TLSRSACertificateFile    {CONF_DIR}/{CERTIFICATE}.pem
  TLSRSACertificateKeyFile {CONF_DIR}/{CERTIFICATE}.pem
  TLSVerifyClient          off
</IfModule>
</Global>
<IfModule mod_tls.c>
  TLSProtocol TLSv1
</IfModule>
EOF
    }

    my $baseServerIp = main::setupGetQuestion( 'BASE_SERVER_IP' );
    my $baseServerPublicIp = main::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' );

    if ( $baseServerIp ne $baseServerPublicIp ) {
        my @virtualHostIps = grep($_ ne '0.0.0.0', ( '127.0.0.1', ( main::setupGetQuestion( 'IPV6_SUPPORT' ) ? '::1' : () ), $baseServerIp ));
        $cfgTpl .= <<"EOF";

# Server behind NAT - Advertise public IP address
MasqueradeAddress $baseServerPublicIp

# VirtualHost for local access (No IP masquerading)
<VirtualHost @virtualHostIps>
    ServerName "{HOSTNAME}.local"
</VirtualHost>
EOF
    }

    processByRef( $data, \$cfgTpl );

    $rs = $self->{'eventManager'}->trigger( 'afterProftpdBuildConf', \$cfgTpl, 'proftpd.conf' );
    return $rs if $rs;

    local $UMASK = 027; # proftpd.conf file must not be created/copied world-readable
    my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/proftpd.conf" );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0640 );
    $rs ||= $file->copyFile( $self->{'config'}->{'FTPD_CONF_FILE'} );
    return $rs if $rs;

    if ( -f "$self->{'config'}->{'FTPD_CONF_DIR'}/modules.conf" ) {
        $file = iMSCP::File->new( filename => "$self->{'config'}->{'FTPD_CONF_DIR'}/modules.conf" );
        my $cfgTplRef = $file->getAsRef();
        unless ( defined $cfgTplRef ) {
            error( sprintf( "Couldn't read the %s file", "$self->{'config'}->{'FTPD_CONF_DIR'}/modules.conf" ));
            return 1;
        }

        ${$cfgTplRef} =~ s/^(LoadModule\s+mod_tls_memcache.c)/#$1/m;
        $rs ||= $file->save();
    }

    $rs;
}

=item _cleanup( )

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ($self) = @_;

    return 0 unless -f "$self->{'cfgDir'}/proftpd.old.data";

    iMSCP::File->new( filename => "$self->{'cfgDir'}/proftpd.old.data" )->delFile();
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

    eval { Servers::sqld->factory()->dropUser( $self->{'config'}->{'DATABASE_USER'}, $dbUserHost ); };
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

    # Setup context means switching to another FTP server. In such case, we simply delete the files
    if ( $main::execmode eq 'setup' ) {
        if ( -f $self->{'config'}->{'FTPD_CONF_FILE'} ) {
            my $rs = iMSCP::File->new( filename => $self->{'config'}->{'FTPD_CONF_FILE'} )->delFile();
            return $rs if $rs;
        }

        my $filename = basename( $self->{'config'}->{'FTPD_CONF_FILE'} );

        if ( -f "$self->{'bkpDir'}/$filename.system" ) {
            my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$filename.system" )->delFile();
            return $rs if $rs;
        }

        return 0;
    }

    my $dirname = dirname( $self->{'config'}->{'FTPD_CONF_FILE'} );
    my $filename = basename( $self->{'config'}->{'FTPD_CONF_FILE'} );

    return 0 unless -d $dirname && -f "$self->{'bkpDir'}/$filename.system";

    iMSCP::File->new( filename => "$self->{'bkpDir'}/$filename.system" )->copyFile(
        $self->{'config'}->{'FTPD_CONF_FILE'}, { preserve => 'no' }
    );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
