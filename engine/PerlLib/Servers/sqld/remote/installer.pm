=head1 NAME

 Servers::sqld::remote::installer - i-MSCP Remote SQL server installer implementation

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Servers::sqld::remote::installer;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Crypt 'decryptRijndaelCBC';
use iMSCP::Database;
use iMSCP::Debug 'error';
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::TemplateParser 'process';
use iMSCP::Umask '$UMASK';
use Servers::sqld::remote;
use version;
use parent 'Servers::sqld::mysql::installer';

=head1 DESCRIPTION

 i-MSCP Remote SQL server installer implementation.

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::sqld::remote:installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'sqld'} = Servers::sqld::remote->getInstance();
    $self->{'events'} = $self->{'sqld'}->{'events'};
    $self->{'cfgDir'} = $self->{'sqld'}->{'cfgDir'};
    $self->{'config'} = $self->{'sqld'}->{'config'};
    $self;
}

=item _buildConf( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeSqldBuildConf' );
    return $rs if $rs;

    my $rootUName = $::imscpConfig{'ROOT_USER'};
    my $rootGName = $::imscpConfig{'ROOT_GROUP'};
    my $confDir = $self->{'config'}->{'SQLD_CONF_DIR'};

    local $@;
    eval {
        # Make sure that the conf.d directory exists
        iMSCP::Dir->new( dirname => "$confDir/conf.d" )->make( {
            user  => $rootUName,
            group => $rootGName,
            mode  => 0755
        } );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # Create the /etc/mysql/my.cnf file if missing
    unless ( -f "$confDir/my.cnf" ) {
        $rs = $self->{'events'}->trigger(
            'onLoadTemplate', 'mysql', 'my.cnf', \my $cfgTpl, {}
        );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $cfgTpl = "!includedir $confDir/conf.d/\n";
        } elsif ( $cfgTpl !~ m%^!includedir\s+$confDir/conf.d/\n%m ) {
            $cfgTpl .= "!includedir $confDir/conf.d/\n";
        }

        my $file = iMSCP::File->new( filename => "$confDir/my.cnf" );
        $file->set( $cfgTpl );

        $rs = $file->save();
        $rs ||= $file->owner( $rootUName, $rootGName );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    $rs ||= $self->{'events'}->trigger(
        'onLoadTemplate', 'mysql', 'imscp.cnf', \my $cfgTpl, {}
    );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        return 1 unless defined(
            $cfgTpl = iMSCP::File->new(
                filename => "$self->{'cfgDir'}/imscp.cnf"
            )->get()
        );
    }

    ( my $user = ::setupGetQuestion( 'DATABASE_USER' ) ) =~ s/"/\\"/g;
    ( my $pwd = decryptRijndaelCBC( $::imscpDBKey, $::imscpDBiv,
        ::setupGetQuestion( 'DATABASE_PASSWORD' )) ) =~ s/"/\\"/g;

    $cfgTpl = process(
        {
            DATABASE_HOST     => ::setupGetQuestion( 'DATABASE_HOST' ),
            DATABASE_PORT     => ::setupGetQuestion( 'DATABASE_PORT' ),
            DATABASE_PASSWORD => $pwd,
            DATABASE_USER     => $user
        },
        $cfgTpl
    );

    local $UMASK = 027; # imscp.cnf file must not be created world-readable

    my $file = iMSCP::File->new( filename => "$confDir/conf.d/imscp.cnf" );
    $file->set( $cfgTpl );
    $rs = $file->save();
    # The 'mysql' group is only created by mysql-server package
    $rs ||= $file->owner( $rootUName, $rootGName );
    $rs ||= $file->mode( 0640 );
    $rs ||= $self->{'events'}->trigger( 'afterSqldBuildConf' );
}

=item _updateServerConfig( )

 Update server configuration

  - Upgrade MySQL system tables if necessary
  - Disable unwanted plugins

 Return 0 on success, other on failure

=cut

sub _updateServerConfig
{
    my ( $self ) = @_;

    if ( !( $::imscpConfig{'SQL_PACKAGE'} eq 'Servers::sqld::mariadb'
        && version->parse( "$self->{'config'}->{'SQLD_VERSION'}" ) >= version->parse( '10.0' ) )
        && !( version->parse( "$self->{'config'}->{'SQLD_VERSION'}" ) >= version->parse( '5.6.6' ) )
    ) {
        return 0;
    }

    eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        local $dbh->{'RaiseError'} = TRUE;

        # Disable unwanted plugins (bc reasons)
        for my $plugin ( qw/ cracklib_password_check simple_password_check validate_password / ) {
            next unless $dbh->selectrow_hashref(
                "SELECT name FROM mysql.plugin WHERE name = '$plugin'"
            );
            $dbh->do( "UNINSTALL PLUGIN $plugin" )
        }
    };
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
