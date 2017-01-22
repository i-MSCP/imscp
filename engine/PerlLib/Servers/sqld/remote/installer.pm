=head1 NAME

 Servers::sqld::remote::installer - i-MSCP Remote MySQL server installer implementation

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

package Servers::sqld::remote::installer;

use strict;
use warnings;
use iMSCP::Config;
use iMSCP::Crypt qw/ decryptRijndaelCBC /;
use iMSCP::Database;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::TemplateParser;
use iMSCP::Umask;
use Servers::sqld::remote;
use version;
use parent 'Servers::sqld::mysql::installer';

=head1 DESCRIPTION

 i-MSCP Remote MySQL server installer implementation.

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Servers::sqld::remote:installer

=cut

sub _init
{
    my $self = shift;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'sqld'} = Servers::sqld::remote->getInstance();
    $self->{'cfgDir'} = $self->{'sqld'}->{'cfgDir'};
    $self->{'config'} = $self->{'sqld'}->{'config'};

    # Be sure to work with newest conffile
    # Cover case where the conffile has been loaded prior installation of new files (even if discouraged)
    untie(%{$self->{'config'}});
    tie %{$self->{'config'}}, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/mysql.data";

    my $oldConf = "$self->{'cfgDir'}/mysql.old.data";
    if (-f $oldConf) {
        tie my %oldConfig, 'iMSCP::Config', fileName => $oldConf, readonly => 1;
        while(my ($key, $value) = each(%oldConfig)) {
            next unless exists $self->{'config'}->{$key};
            $self->{'config'}->{$key} = $value;
        }
    }

    $self;
}

=item _buildConf()

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConf
{
    my $self = shift;

    my $rs = $self->{'eventManager'}->trigger( 'beforeSqldBuildConf' );
    return $rs if $rs;

    my $rootUName = $main::imscpConfig{'ROOT_USER'};
    my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
    my $mysqlGName = $self->{'config'}->{'SQLD_GROUP'};
    my $confDir = $self->{'config'}->{'SQLD_CONF_DIR'};

    # Make sure that the conf.d directory exists
    $rs = iMSCP::Dir->new( dirname => "$confDir/conf.d" )->make(
        {
            user  => $rootUName,
            group => $rootGName,
            mode  => 0755
        }
    );
    return $rs if $rs;

    # Create the /etc/mysql/my.cnf file if missing
    unless (-f "$confDir/my.cnf") {
        $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'mysql', 'my.cnf', \my $cfgTpl, { } );
        return $rs if $rs;

        unless (defined $cfgTpl) {
            $cfgTpl = "!includedir $confDir/conf.d/\n";
        } elsif ($cfgTpl !~ m%^!includedir\s+$confDir/conf.d/\n%m) {
            $cfgTpl .= "!includedir $confDir/conf.d/\n";
        }

        my $file = iMSCP::File->new( filename => "$confDir/my.cnf" );
        $rs = $file->set( $cfgTpl );
        $rs ||= $file->save();
        $rs ||= $file->owner( $rootUName, $rootGName );
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'onLoadTemplate', 'mysql', 'imscp.cnf', \my $cfgTpl, { } );
    return $rs if $rs;

    unless (defined $cfgTpl) {
        $cfgTpl = iMSCP::File->new( filename => "$self->{'cfgDir'}/imscp.cnf" )->get();
        unless (defined $cfgTpl) {
            error( sprintf( 'Could not read %s', "$self->{'cfgDir'}/imscp.cnf" ) );
            return 1;
        }
    }

    (my $user = main::setupGetQuestion( 'DATABASE_USER' ) ) =~ s/"/\\"/g;
    (my $pwd = decryptRijndaelCBC( $main::imscpDBKey, $main::imscpDBiv, main::setupGetQuestion( 'DATABASE_PASSWORD' ) ) ) =~ s/"/\\"/g;

    $cfgTpl = process(
        {
            DATABASE_HOST     => main::setupGetQuestion( 'DATABASE_HOST' ),
            DATABASE_PORT     => main::setupGetQuestion( 'DATABASE_PORT' ),
            DATABASE_PASSWORD => $pwd,
            DATABASE_USER     => $user
        },
        $cfgTpl
    );

    local $UMASK = 027; # imscp.cnf file must not be created world-readable

    my $file = iMSCP::File->new( filename => "$confDir/conf.d/imscp.cnf" );
    $rs ||= $file->set( $cfgTpl );
    $rs ||= $file->save();
    $rs ||= $file->owner( $rootUName, $mysqlGName );
    $rs ||= $file->mode( 0640 );
    return $rs if $rs;

    $self->{'eventManager'}->trigger( 'afterSqldBuildConf' );
}

=item _updateServerConfig()

 Update server configuration

  - Upgrade MySQL system tables if necessary
  - Disable unwanted plugins

 Return 0 on success, other on failure

=cut

sub _updateServerConfig
{
    my $self = shift;

    my $db = iMSCP::Database->factory();

    # Set SQL mode (BC reasons)
    my $qrs = $db->doQuery( 's', "SET GLOBAL sql_mode = 'NO_AUTO_CREATE_USER'" );
    unless (ref $qrs eq 'HASH') {
        error( $qrs );
        return 1;
    }

    # Disable unwanted plugins (bc reasons)
    if (($main::imscpConfig{'SQL_SERVER'} =~ /^mariadb/
        && version->parse( "$self->{'config'}->{'SQLD_VERSION'}" ) >= version->parse( '10.0' ))
        || (version->parse( "$self->{'config'}->{'SQLD_VERSION'}" ) >= version->parse( '5.6.6' ))
    ) {
        for my $plugin(qw/ cracklib_password_check simple_password_check validate_password /) {
            $qrs = $db->doQuery( 'name', "SELECT name FROM mysql.plugin WHERE name = '$plugin'" );
            unless (ref $qrs eq 'HASH') {
                error( $qrs );
                return 1;
            }

            if (%{$qrs}) {
                $qrs = $db->doQuery( 'u', "UNINSTALL PLUGIN $plugin" );
                unless (ref $qrs eq 'HASH') {
                    error( $qrs );
                    return 1;
                }
            }
        }
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
