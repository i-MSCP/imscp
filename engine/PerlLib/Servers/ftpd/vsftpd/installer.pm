=head1 NAME

 Servers::ftpd::vsftpd::installer - i-MSCP VsFTPd Server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2015-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::ftpd::vsftpd::installer;

use strict;
use warnings;
use Cwd;
use iMSCP::Config;
use iMSCP::Database;
use iMSCP::Crypt 'randomStr';
use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Stepper;
use iMSCP::TemplateParser;
use File::Basename;
use Servers::ftpd::vsftpd;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

%main::sqlUsers = () unless %main::sqlUsers;
@main::createdSqlUsers = () unless @main::createdSqlUsers;

=head1 DESCRIPTION

 Installer for the i-MSCP VsFTPd Server implementation.

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

    $eventManager->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->sqlUserDialog( @_ ) }, sub { $self->passivePortRangeDialog( @_ ) };
            0;
        }
    );
}

=item sqlUserDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other on failure

=cut

sub sqlUserDialog
{
    my ($self, $dialog) = @_;

    my $dbUser = main::setupGetQuestion( 'FTPD_SQL_USER', $self->{'config'}->{'DATABASE_USER'} || 'vftp_user' );
    my $dbPass = main::setupGetQuestion( 'FTPD_SQL_PASSWORD', $self->{'config'}->{'DATABASE_PASSWORD'} );

    my ($rs, $msg) = (0, '');

    if ($main::reconfigure =~ /^ftpd|servers|all|forced$/
        || length $dbUser < 6 || length $dbUser > 16 || $dbUser !~ /^[\x21-\x22\x24-\x5b\x5d-\x7e]+$/
        || length $dbPass < 6 || $dbPass !~ /^[\x21-\x22\x24-\x5b\x5d-\x7e]+$/
    ) {
        do {
            ($rs, $dbUser) = $dialog->inputbox( <<"EOF", $dbUser );

Please enter an username for the VsFTPd SQL user:$msg
EOF
            if (lc( $dbUser ) eq lc( $main::imscpConfig{'DATABASE_USER'} )) {
                $msg = "\n\n\\Z1You cannot reuse the i-MSCP SQL user '$dbUser'.\\Zn\n\nPlease try again:";
                $dbUser = '';
            } elsif (lc( $dbUser ) eq 'root') {
                $msg = "\n\n\\Z1Usage of SQL root user is prohibited.\\Zn\n\nPlease try again:";
                $dbUser = '';
            } elsif (length $dbUser > 16) {
                $msg = "\n\n\\Username can be up to 16 characters long.\\Zn\n\nPlease try again:";
                $dbUser = '';
            } elsif (length $dbUser < 6) {
                $msg = "\n\n\\Z1Username must be at least 6 characters long.\\Zn\n\nPlease try again:";
                $dbUser = '';
            } elsif ($dbUser !~ /^[\x21-\x22\x24-\x5b\x5d-\x7e]+$/) {
                $msg = "\n\n\\Z1Only printable ASCII characters (excepted space and number sign and backslash) are allowed.\\Zn\n\nPlease try again:";
                $dbUser = '';
            }
        } while ($rs < 30 && !$dbUser);

        if ($rs < 30) {
            $msg = '';

            # Ask for the VsFTPd SQL user password unless we reuses existent SQL user
            unless (grep($_ eq $dbUser, ( keys %main::sqlUsers ))) {
                do {
                    ($rs, $dbPass) = $dialog->passwordbox( <<"EOF", $dbPass );

Please, enter a password for the VsFTPd SQL user (blank for autogenerate):$msg
EOF


                    if ($dbPass ne '') {
                        if (length $dbPass < 6) {
                            $msg = "\n\n\\Z1Password must be at least 6 characters long.\\Zn\n\nPlease try again:";
                            $dbPass = '';
                        } elsif ($dbPass !~ /^[\x21-\x22\x24-\x5b\x5d-\x7e]+$/) {
                            $msg = "\n\n\\Z1Only printable ASCII characters (excepted space and number sign and backslash) are allowed.\\Zn\n\nPlease try again:";
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
                $dbPass = randomStr( 16 ) unless $dbPass;
                $dialog->msgbox( <<"EOF" );

Password for the VsFTPd SQL user set to: $dbPass"
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

 Ask for VsFTPd port range to use for passive data transfers

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

\\Z4\\Zb\\ZuVsFTPd passive port range\\Zn

Please, choose the passive port range for VsFTPd.

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

    my $rs = $self->_setVersion();
    $rs ||= $self->_setupDatabase();
    $rs ||= $self->_buildConfigFile();
    $rs ||= $self->_saveConf();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::ftpd::vsftpd::installer

=cut

sub _init
{
    my $self = shift;

    $self->{'ftpd'} = Servers::ftpd::vsftpd->getInstance();
    $self->{'eventManager'} = $self->{'ftpd'}->{'eventManager'};
    $self->{'cfgDir'} = $self->{'ftpd'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'config'} = $self->{'ftpd'}->{'config'};

    my $oldConf = "$self->{'cfgDir'}/vsftpd.old.data";
    return $self unless -f $oldConf;

    tie my %oldConfig, 'iMSCP::Config', fileName => $oldConf;
    for my $param(keys %oldConfig) {
        next unless exists $self->{'config'}->{$param};
        $self->{'config'}->{$param} = $oldConfig{$param};
    }

    $self;
}

=item _setVersion

 Set version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
    my $self = shift;

    # Version is print through STDIN (see: strace vsftpd -v)
    my $rs = execute( 'vsftpd -v 0>&1', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    return $rs if $rs;

    if ($stdout !~ m%([\d.]+)%) {
        error( 'Could not find VsFTPd version from `vsftpd -v 0>&1` command output.' );
        return 1;
    }

    $self->{'config'}->{'VSFTPD_VERSION'} = $1;
    debug( sprintf( 'VsFTPd version set to: %s', $1 ) );
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

    $self->{'eventManager'}->trigger( 'beforeFtpdSetupDb', $dbUser, $dbPass );

    for my $sqlUser ($dbOldUser, $dbUser) {
        next if !$sqlUser || grep($_ eq "$sqlUser\@$dbUserHost", @main::createdSqlUsers);

        for my $host($dbUserHost, $main::imscpOldConfig{'DATABASE_USER_HOST'}) {
            next unless $host;
            $sqlServer->dropUser( $sqlUser, $host );
        }
    }

    # Create SQL user if not already created by another server/package installer
    unless (grep($_ eq "$dbUser\@$dbUserHost", @main::createdSqlUsers)) {
        debug( sprintf( 'Creating %s@%s SQL user', $dbUser, $dbUserHost ) );
        $sqlServer->createUser( $dbUser, $dbUserHost, $dbPass );
        push @main::createdSqlUsers, "$dbUser\@$dbUserHost";
    }

    my $db = iMSCP::Database->factory();

    # Give needed privileges to this SQL user

    # No need to escape wildcard characters. See https://bugs.mysql.com/bug.php?id=18660
    my $quotedDbName = $db->quoteIdentifier( $dbName );
    my $quotedTableName = $db->quoteIdentifier( 'ftp_users' );
    my $rs = $db->doQuery( 'g', "GRANT SELECT ON $quotedDbName.$quotedTableName TO ?@?", $dbUser, $dbUserHost );
    unless (ref $rs eq 'HASH') {
        error( sprintf( 'Could not add SQL privileges: %s', $rs ) );
        return 1;
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

    # Make sure to start with clean user configuration directory
    unlink glob "$self->{'config'}->{'FTPD_USER_CONF_DIR'}/*";

    my ($passvMinPort, $passvMaxPort) = split( /\s+/, $self->{'config'}->{'FTPD_PASSIVE_PORT_RANGE'} );
    my $data = {
        DATABASE_NAME          => $main::imscpConfig{'DATABASE_NAME'},
        DATABASE_HOST          => $main::imscpConfig{'DATABASE_HOST'},
        DATABASE_PORT          => $main::imscpConfig{'DATABASE_PORT'},
        DATABASE_USER          => $self->{'config'}->{'DATABASE_USER'},
        DATABASE_PASS          => $self->{'config'}->{'DATABASE_PASSWORD'},
        FTPD_BANNER            => $self->{'config'}->{'FTPD_BANNER'},
        FRONTEND_USER_SYS_NAME => $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'},
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

    unless (defined $cfgTpl) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/vsftpd.conf" )->get();
        unless (defined $cfgTpl) {
            error( sprintf( 'Could not read %s file', "$self->{'cfgDir'}/vsftpd.conf" ) );
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeFtpdBuildConf', \$cfgTpl, 'vsftpd.conf' );
    return $rs if $rs;

    if ($self->_isVsFTPdInsideCt()) {
        $cfgTpl .= <<EOF;

# VsFTPd run inside unprivileged VE
# See http://youtrack.i-mscp.net/issue/IP-1503
seccomp_sandbox=NO
EOF
    }

    if ($main::imscpConfig{'BASE_SERVER_IP'} ne $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'}) {
        $cfgTpl .= <<EOF;

# VsFTPd behind NAT - Use public IP address
pasv_address=$main::imscpConfig{'BASE_SERVER_PUBLIC_IP'}
pasv_promiscuous=YES
EOF
    }

    if (main::setupGetQuestion( 'SERVICES_SSL_ENABLED' ) eq 'yes') {
        $cfgTpl .= <<EOF;

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

    $cfgTpl = iMSCP::TemplateParser::process( $data, $cfgTpl );

    $rs = $self->{'eventManager'}->trigger( 'afterFtpdBuildConf', \$cfgTpl, 'vsftpd.conf' );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $self->{'config'}->{'FTPD_CONF_FILE'} );
    $rs = $file->set( $cfgTpl );
    $rs ||= $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0640 );
    return $rs if $rs;

    # VsFTPd pam-mysql configuration file
    undef $cfgTpl;

    $rs = $self->_bkpConfFile( $self->{'config'}->{'FTPD_PAM_CONF_FILE'} );
    $rs ||= $self->{'eventManager'}->trigger( 'onLoadTemplate', 'vsftpd', 'vsftpd.pam', \$cfgTpl, $data );
    return $rs if $rs;

    unless (defined $cfgTpl) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/vsftpd.pam" )->get();
        unless (defined $cfgTpl) {
            error( sprintf( 'Could not read %s file', "$self->{'cfgDir'}/vsftpd.pam" ) );
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeFtpdBuildConf', \$cfgTpl, 'vsftpd.pam' );
    return $rs if $rs;

    $cfgTpl = iMSCP::TemplateParser::process( $data, $cfgTpl );

    $rs = $self->{'eventManager'}->trigger( 'afterFtpdBuildConf', \$cfgTpl, 'vsftpd.pam' );
    return $rs if $rs;

    $file = iMSCP::File->new( filename => $self->{'config'}->{'FTPD_PAM_CONF_FILE'} );
    $rs ||= $file->set( $cfgTpl );
    $rs ||= $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0640 );
}

=item _saveConf()

 Save configuration file

 Return int 0 on success, other on failure

=cut

sub _saveConf
{
    my $self = shift;

    iMSCP::File->new( filename => "$self->{'cfgDir'}/vsftpd.data" )->copyFile( "$self->{'cfgDir'}/vsftpd.old.data" );
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
        my $basename = basename( $cfgFile );

        unless (-f "$self->{'bkpDir'}/$basename.system") {
            $rs = $file->copyFile( "$self->{'bkpDir'}/$basename.system" );
            return $rs if $rs;
        } else {
            $rs = $file->copyFile( "$self->{'bkpDir'}/$basename.".time );
            return $rs if $rs;
        }
    }

    $self->{'eventManager'}->trigger( 'afterFtpdBkpConfFile', $cfgFile );
}

=item _isVsFTPdInsideCt()

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

    if ($stdout =~ /envID:\s+(\d+)/) {
        return ($1 > 0) ? 1 : 0;
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
