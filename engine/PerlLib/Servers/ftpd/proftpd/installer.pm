=head1 NAME

 Servers::ftpd::proftpd::installer - i-MSCP Proftpd Server implementation

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

package Servers::ftpd::proftpd::installer;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::TemplateParser;
use iMSCP::EventManager;
use File::Basename;
use Servers::ftpd::proftpd;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

%main::sqlUsers = () unless %main::sqlUsers;
@main::createdSqlUsers = () unless @main::createdSqlUsers;

=head1 DESCRIPTION

 Installer for the i-MSCP Poftpd Server implementation.

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

    $eventManager->register( 'beforeSetupDialog', sub {
            push @{$_[0]}, sub { $self->sqlUserDialog( @_ ) }, sub { $self->passivePortRangeDialog( @_ ) };
            0;
        } );
}

=item sqlUserDialog(\%dialog)

 Ask for ProFTPD SQL user

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub sqlUserDialog
{
    my ($self, $dialog) = @_;

    my $dbUser = main::setupGetQuestion( 'FTPD_SQL_USER' ) || $self->{'config'}->{'DATABASE_USER'} || 'vftp_user';
    my $dbPass = main::setupGetQuestion( 'FTPD_SQL_PASSWORD' ) || $self->{'config'}->{'DATABASE_PASSWORD'};

    my ($rs, $msg) = (0, '');

    if (grep($_ eq $main::reconfigure, ( 'ftpd', 'servers', 'all', 'forced' ))
        || length $dbUser < 6 || length $dbUser > 16 || $dbUser !~ /^[\x21-\x7e]+$/
        || length $dbPass < 6 || $dbPass !~ /^[\x21-\x7e]+$/
    ) {
        # Ensure no special chars are present in password. If we don't, dialog will not let user set new password
        $dbPass = '';

        do {
            ($rs, $dbUser) = $dialog->inputbox( <<"EOF", $dbUser );

Please enter an username for the ProFTPD SQL user:$msg
EOF
            if ($dbUser eq $main::imscpConfig{'DATABASE_USER'}) {
                $msg = "\n\n\\Z1You cannot reuse the i-MSCP SQL user '$dbUser'.\\Zn\n\nPlease try again:";
                $dbUser = '';
            } elsif (length $dbUser > 16) {
                $msg = "\n\n\\Username can be up to 16 characters long.\\Zn\n\nPlease try again:";
                $dbUser = '';
            } elsif (length $dbUser < 6) {
                $msg = "\n\n\\Z1Username must be at least 6 characters long.\\Zn\n\nPlease try again:";
                $dbUser = '';
            } elsif ($dbUser !~ /^[\x21-\x7e]+$/) {
                $msg = "\n\n\\Z1Only printable ASCII characters (excepted space) are allowed.\\Zn\n\nPlease try again:";
                $dbUser = '';
            }
        } while ($rs < 30 && !$dbUser);

        if ($rs < 30) {
            $msg = '';

            # Ask for the proftpd SQL user password unless we reuses existent SQL user
            unless (grep($_ eq $dbUser, (keys %main::sqlUsers))) {
                do {
                    ($rs, $dbPass) = $dialog->passwordbox( <<"EOF", $dbPass );

Please, enter a password for the proftpd SQL user (blank for autogenerate):$msg
EOF


                    if ($dbPass ne '') {
                        if (length $dbPass < 6) {
                            $msg = "\n\n\\Z1Password must be at least 6 characters long.\\Zn\n\nPlease try again:";
                            $dbPass = '';
                        } elsif ($dbPass !~ /^[\x21-\x7e]+$/) {
                            $msg = "\n\n\\Z1Only printable ASCII characters (excepted space) are allowed.\\Zn\n\nPlease try again:";
                            $dbPass = '';
                        } else {
                            $msg = '';
                        }
                    } else {
                        $msg = '';
                    }
                } while ($rs < 30 && $msg);
            } else {
                $dbPass = $main::sqlUsers{$dbUser};
            }

            if ($rs < 30) {
                unless ($dbPass) {
                    my @allowedChr = map { chr } (0x21 .. 0x7e);
                    $dbPass = '';
                    $dbPass .= $allowedChr[rand @allowedChr] for 1 .. 16;
                }

                $dialog->msgbox( <<"EOF" );

Password for the proftpd SQL user set to: $dbPass
EOF
            }
        }
    }

    if ($rs < 30) {
        main::setupSetQuestion( 'FTPD_SQL_USER', $dbUser );
        main::setupSetQuestion( 'FTPD_SQL_PASSWORD', $dbPass );
        $main::sqlUsers{$dbUser} = $dbPass;
    }

    $rs;
}

=item passivePortRangeDialog(\%dialog)

 Ask for ProtFTPD port range to use for passive data transfers

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub passivePortRangeDialog
{
    my ($self, $dialog) = @_;

    my ($rs, $msg) = (0, '');
    my $passivePortRange = main::setupGetQuestion( 'FTPD_PASSIVE_PORT_RANGE' ) || $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'};

    if (grep($_ eq $main::reconfigure, ( 'ftpd', 'servers', 'all', 'forced' ))
        || $passivePortRange !~ /^(\d+)\s+(\d+)$/
        || $1 < 32768 || $1 >= 60999 || $1 >= $2
    ) {
        $passivePortRange = '32768 60999' unless $1 && $2;

        do {
            ($rs, $passivePortRange) = $dialog->inputbox( <<EOF

\\Z4\\Zb\\ZuProFTPD passive port range\\Zn

Please, choose the passive port range for ProFTPD.

Be aware that if you're behind a NAT, you must forward those ports to this server.$msg
EOF
                ,
                $passivePortRange
            );

            if ($passivePortRange !~ /^(\d+)\s+(\d+)$/ || $1 < 32768 || $1 >= 60999 || $1 >= $2) {
                $passivePortRange = '32768 60999';
                $msg = "\n\n\\Z1Invalid port range.\\Zn\n\nPlease try again:"
            } else {
                $passivePortRange = "$1 $2";
                $msg = '';
            }
        } while ($rs < 30 && $msg);
    }

    $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'} = $passivePortRange if $rs < 30;
    $rs;
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    my $rs = $self->_bkpConfFile( $self->{'config'}->{'FTPD_CONF_FILE'} );
    $rs ||= $self->_setVersion();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_buildConfigFile();
    $rs ||= $self->_saveConf();
    $rs ||= $self->_oldEngineCompatibility();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::ftpd::proftpd::installer

=cut

sub _init
{
    my $self = shift;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'ftpd'} = Servers::ftpd::proftpd->getInstance();
    $self->{'eventManager'}->trigger( 'beforeFtpdInitInstaller', $self, 'proftpd' ) and fatal(
        'proftpd - beforeFtpdInitInstaller has failed'
    );
    $self->{'cfgDir'} = $self->{'ftpd'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";
    $self->{'config'} = $self->{'ftpd'}->{'config'};

    my $oldConf = "$self->{'cfgDir'}/proftpd.old.data";
    if (-f $oldConf) {
        tie my %oldConfig, 'iMSCP::Config', fileName => $oldConf;

        for my $param(keys %oldConfig) {
            if (exists $self->{'config'}->{$param}) {
                $self->{'config'}->{$param} = $oldConfig{$param};
            }
        }
    }

    $self->{'eventManager'}->trigger( 'afterFtpdInitInstaller', $self, 'proftpd' ) and fatal(
        'proftpd - afterFtpdInitInstaller has failed'
    );
    $self;
}

=item _bkpConfFile()

 Backup file

 Return int 0 on success, other on failure

=cut

sub _bkpConfFile
{
    my ($self, $cfgFile) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdBkpConfFile', $cfgFile );
    return $rs if $rs;

    if (-f $cfgFile) {
        my $file = iMSCP::File->new( filename => $cfgFile );
        my ($filename, $directories, $suffix) = fileparse( $cfgFile );

        unless (-f "$self->{'bkpDir'}/$filename$suffix.system") {
            $rs = $file->copyFile( "$self->{'bkpDir'}/$filename$suffix.system" );
            return $rs if $rs;
        } else {
            $rs = $file->copyFile( "$self->{'bkpDir'}/$filename$suffix.".time );
            return $rs if $rs;
        }
    }

    $self->{'eventManager'}->trigger( 'afterFtpdBkpConfFile', $cfgFile );
}

=item _setVersion

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
    my $self = shift;

    my $rs = execute( 'proftpd -v', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    return $rs if $rs;

    if ($stdout !~ m%([\d.]+)%) {
        error( 'Could not find ProFTPD version from `proftpd -v` command output.' );
        return 1;
    }

    $self->{'config'}->{'PROFTPD_VERSION'} = $1;
    debug( "ProFTPD version set to: $1" );
    0;
}

=item _setupDatabase()

 Setup database

 Return int 0 on success, other on failure

=cut

sub _setupDatabase
{
    my $self = shift;

    my $sqlServer = Servers::sqld->factory();
    my $dbName = main::setupGetQuestion( 'DATABASE_NAME' );
    my $dbUser = main::setupGetQuestion( 'FTPD_SQL_USER' );
    my $dbUserHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    my $dbPass = main::setupGetQuestion( 'FTPD_SQL_PASSWORD' );
    my $dbOldUser = $self->{'config'}->{'DATABASE_USER'};

    my $rs = $self->{'eventManager'}->trigger( 'beforeFtpdSetupDb', $dbUser, $dbPass );
    return $rs if $rs;

    for my $sqlUser ($dbOldUser, $dbUser) {
        next if !$sqlUser || grep($_ eq "$sqlUser\@$dbUserHost", @main::createdSqlUsers);

        for my $host($dbUserHost, $main::imscpOldConfig{'DATABASE_USER_HOST'}) {
            next unless $host;
            $sqlServer->dropUser( $sqlUser, $host );
        }
    }

    my ($db, $errStr) = main::setupGetSqlConnect();
    fatal( sprintf( 'Unable to connect to SQL server: %s', $errStr ) ) unless $db;

    # Create SQL user if not already created by another server/package installer
    unless (grep($_ eq "$dbUser\@$dbUserHost", @main::createdSqlUsers)) {
        debug( sprintf( 'Creating %s@%s SQL user', $dbUser, $dbUserHost ) );
        $sqlServer->createUser( $dbUser, $dbUserHost, $dbPass );
        push @main::createdSqlUsers, "$dbUser\@$dbUserHost";
    }

    # Give needed privileges to this SQL user

    my $quotedDbName = $db->quoteIdentifier( $dbName );

    for my $tableName('ftp_users', 'ftp_group') {
        my $quotedTableName = $db->quoteIdentifier( $tableName );

        $rs = $db->doQuery( 'g', "GRANT SELECT ON $quotedDbName.$quotedTableName TO ?@?", $dbUser, $dbUserHost );
        unless (ref $rs eq 'HASH') {
            error( sprintf( 'Unable to add SQL privileges: %s', $rs ) );
            return 1;
        }
    }

    for my $tableName('quotalimits', 'quotatallies') {
        my $quotedTableName = $db->quoteIdentifier( $tableName );

        $rs = $db->doQuery(
            'g', "GRANT SELECT, INSERT, UPDATE ON $quotedDbName.$quotedTableName TO ?@?", $dbUser, $dbUserHost
        );
        unless (ref $rs eq 'HASH') {
            error( sprintf( 'Unable to add SQL privileges: %s', $rs ) );
            return 1;
        }
    }

    $self->{'config'}->{'DATABASE_USER'} = $dbUser;
    $self->{'config'}->{'DATABASE_PASSWORD'} = $dbPass;
    $self->{'eventManager'}->trigger( 'afterFtpSetupDb', $dbUser, $dbPass );
}

=item _buildConfigFile()

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfigFile
{
    my $self = shift;

    # Escape any double-quotes and backslash in password (see #IP-1330)
    (my $dbUser = $self->{'config'}->{'DATABASE_USER'}) =~ s%("|\\)%\\$1%g;
    (my $dbPass = $self->{'config'}->{'DATABASE_PASSWORD'}) =~ s%("|\\)%\\$1%g;

    my $data = {
        HOSTNAME                => $main::imscpConfig{'SERVER_HOSTNAME'},
        DATABASE_NAME           => $main::imscpConfig{'DATABASE_NAME'},
        DATABASE_HOST           => $main::imscpConfig{'DATABASE_HOST'},
        DATABASE_PORT           => $main::imscpConfig{'DATABASE_PORT'},
        DATABASE_USER           => '"'.$dbUser.'"',
        DATABASE_PASS           => '"'.$dbPass.'"',
        FTPD_MIN_UID            => $self->{'config'}->{'MIN_UID'},
        FTPD_MIN_GID            => $self->{'config'}->{'MIN_GID'},
        FTPD_PASSIVE_PORT_RANGE => $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'},
        CONF_DIR                => $main::imscpConfig{'CONF_DIR'},
        CERTIFICATE             => 'imscp_services',
        TLSOPTIONS              =>
            version->parse( "$self->{'config'}->{'PROFTPD_VERSION'}" ) >= version->parse( '1.3.3' )
            ? 'NoCertRequest NoSessionReuseRequired' : 'NoCertRequest',
        MAX_INSTANCES           => $self->{'config'}->{'MAX_INSTANCES'},
        MAX_CLIENT_PER_HOST     => $self->{'config'}->{'MAX_CLIENT_PER_HOST'}
    };

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'proftpd', 'proftpd.conf', \ my $cfgTpl, $data );
    return $rs if $rs;

    unless (defined $cfgTpl) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/proftpd.conf" )->get();
        unless (defined $cfgTpl) {
            error( sprintf( 'Could not read to read the %s file', "$self->{'cfgDir'}/proftpd.conf" ) );
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeFtpdBuildConf', \$cfgTpl, 'proftpd.conf' );
    return $rs if $rs;

    if ($main::imscpConfig{'BASE_SERVER_IP'} ne $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'}) {
        $cfgTpl .= <<EOF;

# ProFTPD behind NAT - Use public IP address
MasqueradeAddress $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'}
EOF
    }

    if (main::setupGetQuestion( 'SERVICES_SSL_ENABLED' ) eq 'yes') {
        $cfgTpl .= <<'EOF';

# SSL configuration
<IfModule mod_tls.c>
  TLSEngine                on
  TLSRequired              off
  TLSLog                   /var/log/proftpd/ftp_ssl.log
  TLSProtocol              TLSv1
  TLSOptions               {TLSOPTIONS}
  TLSRSACertificateFile    {CONF_DIR}/{CERTIFICATE}.pem
  TLSRSACertificateKeyFile {CONF_DIR}/{CERTIFICATE}.pem
  TLSVerifyClient          off
</IfModule>
EOF
    }

    $cfgTpl = process( $data, $cfgTpl );

    $rs = $self->{'eventManager'}->trigger( 'afterFtpdBuildConf', \$cfgTpl, 'proftpd.conf' );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => "$self->{'wrkDir'}/proftpd.conf" );
    $rs = $file->set( $cfgTpl );
    $rs ||= $file->save();
    $rs ||= $file->mode( 0640 );
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->copyFile( $self->{'config'}->{'FTPD_CONF_FILE'} );
    return $rs if $rs;

    if (-f "$self->{'config'}->{'FTPD_CONF_DIR'}/modules.conf") {
        $file = iMSCP::File->new( filename => "$self->{'config'}->{'FTPD_CONF_DIR'}/modules.conf" );
        $cfgTpl = $file->get();
        unless (defined $cfgTpl) {
            error( sprintf( 'Could not read %s file', "$self->{'config'}->{'FTPD_CONF_DIR'}/modules.conf" ) );
            return 1;
        }

        $cfgTpl =~ s/^(LoadModule\s+mod_tls_memcache.c)/#$1/m;
        $rs = $file->set( $cfgTpl );
        $rs ||= $file->save();
    }

    $rs;
}

=item _saveConf()

 Save configuration file

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
    my $self = shift;

    iMSCP::File->new( filename => "$self->{'cfgDir'}/proftpd.data" )->copyFile( "$self->{'cfgDir'}/proftpd.old.data" );
}

=item _oldEngineCompatibility()

 Remove old files

 Return int 0 on success, other on failure

=cut

sub _oldEngineCompatibility
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeNamedOldEngineCompatibility' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterNameddOldEngineCompatibility' );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
