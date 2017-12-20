=head1 NAME

 Servers::sqld::remote::installer - i-MSCP Remote SQL server installer implementation

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

package Servers::sqld::remote::installer;

use strict;
use warnings;
use iMSCP::Database;
use iMSCP::Debug qw/ error /;
use iMSCP::Dir;
use iMSCP::File;
use version;
use parent 'Servers::sqld::mysql::installer';

=head1 DESCRIPTION

 i-MSCP Remote SQL server installer implementation.

=head1 PRIVATE METHODS

=over 4

=item _buildConf( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
    my ($self) = @_;

    my $rs = $self->{'sqld'}->{'eventManager'}->trigger( 'beforeRemoteSqldBuildConf' );
    return $rs if $rs;

    eval {
        # Make sure that the conf.d directory exists
        iMSCP::Dir->new( dirname => "$self->{'sqld'}->{'config'}->{'SQLD_CONF_DIR'}/conf.d" )->make( {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => 0755
        } );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # Create the /etc/mysql/my.cnf file if missing
    unless ( -f "$self->{'sqld'}->{'config'}->{'SQLD_CONF_DIR'}/my.cnf" ) {
        $rs = $self->{'sqld'}->{'eventManager'}->trigger( 'onLoadTemplate', 'remote_sqld', 'my.cnf', \ my $cfgTpl, {} );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $cfgTpl = "!includedir $self->{'sqld'}->{'config'}->{'SQLD_CONF_DIR'}/conf.d/\n";
        } elsif ( $cfgTpl !~ m%^!includedir\s+$self->{'sqld'}->{'config'}->{'SQLD_CONF_DIR'}/conf.d/\n%m ) {
            $cfgTpl .= "!includedir $self->{'sqld'}->{'config'}->{'SQLD_CONF_DIR'}/conf.d/\n";
        }

        my $file = iMSCP::File->new( filename => "$self->{'sqld'}->{'config'}->{'SQLD_CONF_DIR'}/my.cnf" );
        $file->set( $cfgTpl );
        $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    $rs ||= $self->{'sqld'}->{'eventManager'}->trigger( 'onLoadTemplate', 'remote_sqld', 'imscp.cnf', \ my $cfgTpl, {} );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'sqld'}->{'cfgDir'}/imscp.cnf" )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read the %s file", "$self->{'sqld'}->{'cfgDir'}/imscp.cnf" ));
            return 1;
        }
    }

    my $file = iMSCP::File->new( filename => "$self->{'sqld'}->{'config'}->{'SQLD_CONF_DIR'}/conf.d/imscp.cnf" );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $file->mode( 0644 );
    $rs ||= $self->{'sqld'}->{'eventManager'}->trigger( 'afterRemoteSqldBuildConf' );
}

=item _updateServerConfig( )

 Update server configuration

  - Upgrade MySQL system tables if necessary
  - Disable unwanted plugins

 Return 0 on success, other on failure

=cut

sub _updateServerConfig
{
    my ($self) = @_;

    if ( $main::imscpConfig{'SQL_PACKAGE'} eq 'Servers::sqld::mariadb' ) {
        return 0 if version->parse( "$self->{'sqld'}->{'config'}->{'SQLD_VERSION'}" ) < version->parse( '10.0' );
    } elsif ( version->parse( "$self->{'sqld'}->{'config'}->{'SQLD_VERSION'}" ) < version->parse( '5.6.6' ) ) {
        return 0;
    }

    eval {
        my $dbh = iMSCP::Database->getInstance()->getRawDb();
        local $dbh->{'RaiseError'};

        # Disable unwanted plugins (bc reasons)
        for ( qw/ cracklib_password_check simple_password_check validate_password / ) {
            $dbh->do( "UNINSTALL PLUGIN $_" ) if $dbh->selectrow_hashref( "SELECT name FROM mysql.plugin WHERE name = '$_'" );
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
