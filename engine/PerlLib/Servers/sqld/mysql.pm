=head1 NAME

 Servers::sqld::mysql - i-MSCP MySQL server implementation

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

package Servers::sqld::mysql;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Servers::sqld::mysql::installer Servers::sqld::mysql::uninstaller /;
use iMSCP::Boolean;
use iMSCP::Config;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Rights 'setRights';
use iMSCP::Service;
use Try::Tiny;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP MySQL server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%em )

 Register setup event listeners

 Param iMSCP::EventManager \%em
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( undef, $em ) = @_;

    Servers::sqld::mysql::installer->getInstance()->registerSetupListeners( $em );
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldPreinstall', 'mysql' );
    $rs ||= Servers::sqld::mysql::installer->getInstance()->preinstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterSqldPreinstall', 'mysql' );
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeSqldPostInstall', 'mysql' );
        return $rs if $rs;

        iMSCP::Service->getInstance()->enable( 'mysql' );

        $rs = $self->{'eventManager'}->register(
            'beforeSetupRestartServices',
            sub {
                push @{ $_[0] }, [ sub { $self->restart(); }, 'MySQL' ];
                0;
            },
            7
        );
        $rs ||= $self->{'eventManager'}->trigger( 'afterSqldPostInstall', 'mysql' );
    } catch {
        error( $_ );
        1;
    };
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldUninstall', 'mysql' );
    $rs ||= Servers::sqld::mysql::uninstaller->getInstance()->uninstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterSqldUninstall', 'mysql' );
    $rs ||= $self->restart() unless $rs;
    $rs;
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ( $self ) = @_;

    my $rs = setRights( "$self->{'config'}->{'SQLD_CONF_DIR'}/my.cnf", {
        user  => $::imscpConfig{'ROOT_USER'},
        group => $::imscpConfig{'ROOT_GROUP'},
        mode  => '0644'
    } );
    $rs ||= setRights( "$self->{'config'}->{'SQLD_CONF_DIR'}/conf.d/imscp.cnf", {
        user  => $::imscpConfig{'ROOT_USER'},
        group => $self->{'config'}->{'SQLD_GROUP'},
        mode  => '0640'
    } );
}

=item restart( )

 Restart server

 Return int 0 on success, other on failure

=cut

sub restart
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeSqldRestart' );
        return $rs if $rs;

        iMSCP::Service->getInstance()->restart( 'mysql' );
        $self->{'eventManager'}->trigger( 'afterSqldRestart' );
    } catch {
        error( $_ );
        1;
    };
}

=item createUser( $user, $host, $password )

 Create the given SQL user

 Param $string $user SQL username
 Param string $host SQL user host
 Param $string $password SQL user password
 Return int 0 on success, die on failure

=cut

sub createUser
{
    my ( $self, $user, $host, $password ) = @_;

    length $user or die( 'Missing or invalid $user parameter' );
    length $host or die( 'Missing or invalid $host parameter' );
    length $password or die( 'Missing or invalid $password parameter' );

    iMSCP::Database->factory()->getConnector()->run( fixup => sub {
        if ( $_->selectrow_hashref( 'SELECT 1 FROM mysql.user WHERE user = ? AND host = ?', undef, $user, $host ) ) {
            debug( sprintf( 'Updating %s@%s SQL user password', $user, $host ));

            if ( version->parse( $self->getVersion()) < version->parse( '5.7.6' ) ) {
                $_->do( 'SET PASSWORD FOR ?@? = PASSWORD(?)', undef, $user, $host, $password );
                return;
            }

            $_->do( 'ALTER USER ?@? IDENTIFIED BY ? PASSWORD EXPIRE NEVER', undef, $user, $host, $password );
            return;
        }

        debug( sprintf( 'Creating %s@%s SQL user', $user, $host ));
        $_->do(
            'CREATE USER ?@? IDENTIFIED BY ?' . ( version->parse( $self->getVersion()) >= version->parse( '5.7.6' ) ? ' PASSWORD EXPIRE NEVER' : '' ),
            undef, $user, $host, $password
        );
    } );
    0;
}

=item dropUser( $user, $host )

 Drop the given SQL user if exists

 Param $string $user SQL username
 Param string $host SQL user host
 Return int 0 on success, die on failure

=cut

sub dropUser
{
    my ( undef, $user, $host ) = @_;

    length $user or die( 'Missing or invalid $user parameter' );
    length $host or die( 'Missing or invalid $host parameter' );

    return 0 if $user =~ /^(?:debian-sys-maint|mysql\.sys|root)$/; # Prevent deletion of system SQL users

    iMSCP::Database->factory()->getConnector()->run( fixup => sub {
        return unless $_->selectrow_hashref( 'SELECT 1 FROM mysql.user WHERE user = ? AND host = ?', undef, $user, $host );
        debug( sprintf( 'Dropping %s@%s SQL user', $user, $host ));
        $_->do( 'DROP USER ?@?', undef, $user, $host );
    } );

    0;
}

=item getType( )

 Get SQL server type

 Return string MySQL server type

=cut

sub getType
{
    my ( $self ) = @_;

    $self->{'config'}->{'SQLD_TYPE'};
}

=item getVersion( )

 Get SQL server version

 Return string MySQL server version

=cut

sub getVersion
{
    my ( $self ) = @_;

    $self->{'config'}->{'SQLD_VERSION'};
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::sqld::mysql

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = "$::imscpConfig{'CONF_DIR'}/mysql";
    $self->_mergeConfig() if -f "$self->{'cfgDir'}/mysql.data.dist";
    tie %{ $self->{'config'} },
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/mysql.data",
        readonly    => $::execmode ne 'setup',
        nodeferring => $::execmode eq 'setup';
    $self;
}

=item _mergeConfig( )

 Merge distribution configuration with production configuration

 Die on failure

=cut

sub _mergeConfig
{
    my ( $self ) = @_;

    if ( -f "$self->{'cfgDir'}/mysql.data" ) {
        tie my %newConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/mysql.data.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/mysql.data", readonly => TRUE;
        debug( 'Merging old configuration with new configuration...' );
        while ( my ( $key, $value ) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }
        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/mysql.data.dist" )->moveFile( "$self->{'cfgDir'}/mysql.data" ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error'
    );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
