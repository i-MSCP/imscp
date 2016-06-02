=head1 NAME

 Servers::sqld::mysql - i-MSCP MySQL server implementation

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

package Servers::sqld::mysql;

use strict;
use warnings;
use iMSCP::Config;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::Service;
use Scalar::Defer;
use version;
use Class::Autouse qw/Servers::sqld::mysql::installer Servers::sqld::mysql::uninstaller/;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP MySQL server implementation.

=head1 PUBLIC METHODS

=over 4

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldPreinstall' );
    $rs ||= Servers::sqld::mysql::installer->getInstance()->preinstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterSqldPreinstall' );
}

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldPostInstall', 'mysql' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->enable( 'mysql' ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $rs = $self->{'eventManager'}->register(
        'beforeSetupRestartServices', sub {
            push @{$_[0]}, [ sub { $self->restart(); }, 'SQL' ];
            0;
        }
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterSqldPostInstall', 'mysql' );
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldUninstall', 'mysql' );
    $rs ||= Servers::sqld::mysql::uninstaller->getInstance()->uninstall();
    $rs ||= $self->restart();
    $rs ||= $self->{'eventManager'}->trigger( 'afterSqldUninstall', 'mysql' );
}

=item setEnginePermissions()

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldSetEnginePermissions' );
    $rs ||= Servers::sqld::mysql::installer->getInstance()->setEnginePermissions();
    $rs ||= $self->{'eventManager'}->trigger( 'afterSqldSetEnginePermissions' );
}

=item restart()

 Restart server

 Return int 0 on success, other on failure

=cut

sub restart
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldRestart' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart( 'mysql' ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterSqldRestart' );
}

=item createUser($user, $host, $password)

 Create the given SQL user

 Param $string $user SQL username
 Param string $host SQL user host
 Param $string $password SQL user password
 Return int 0 on success, die on failure

=cut

sub createUser
{
    my ($self, $user, $host, $password) = @_;

    defined $user or die( '$user parameter is not defined' );
    defined $host or die( '$host parameter is not defined' );
    defined $password or die( '$password parameter is not defined' );

    my $db = iMSCP::Database->factory();
    my $qrs = $db->doQuery(
        'c', 'CREATE USER ?@? IDENTIFIED BY ?'.(
                version->parse( $self->getVersion() ) >= version->parse( '5.7.6' ) ? ' PASSWORD EXPIRE NEVER' : ''
        ),
        $user, $host, $password
    );
    ref $qrs eq 'HASH' or die( sprintf( 'Could not create the %s@%s SQL user: %s', $user, $host, $qrs ) );
    0;
}

=item dropUser($user, $host)

 Drop the given SQL user if exists

 Param $string $user SQL username
 Param string $host SQL user host
 Return int 0 on success, die on failure

=cut

sub dropUser
{
    my ($self, $user, $host) = @_;

    defined $user or die( '$user parameter not defined' );
    defined $host or die( '$host parameter not defined' );

    my $db = iMSCP::Database->factory();
    my $qrs = $db->doQuery( 1, 'SELECT 1 FROM mysql.user WHERE user = ? AND host = ?', $user, $host );
    ref $qrs eq 'HASH' or die( $qrs );

    return 0 unless %{$qrs};

    $qrs = $db->doQuery( 'd', 'DROP USER ?@?', $user, $host );
    ref $qrs eq 'HASH' or die( sprintf( 'Could not drop the %s@%s SQL user: %s', $user, $host, $qrs ) );
    0;
}

=item getType()

 Get SQL server type

 Return string MySQL server type

=cut

sub getType
{
    my $self = shift;

    $self->{'config'}->{'SQLD_TYPE'};
}

=item getVersion()

 Get SQL server version

 Return string MySQL server version

=cut

sub getVersion
{
    my $self = shift;

    $self->{'config'}->{'SQLD_VERSION'};
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::sqld::mysql

=cut

sub _init
{
    my $self = shift;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/mysql";
    $self->{'config'} = $self->{'mysql'}->{'config'};
    $self->{'config'} = lazy {
            tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/mysql.data";
            \%c;
        };
    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
