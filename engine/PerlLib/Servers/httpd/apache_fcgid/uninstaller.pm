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

package Servers::httpd::apache_fcgid::uninstaller;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Crypt 'decryptRijndaelCBC';
use iMSCP::Database;
use iMSCP::Debug 'error';
use iMSCP::Dir;
use iMSCP::File;
use Servers::httpd::apache_fcgid;
use Servers::sqld;
use parent 'Common::SingletonClass';

sub uninstall
{
    my ( $self ) = @_;

    my $rs = eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        my ( $vloggerSqlUser ) = @{ $dbh->selectcol_arrayref(
            "
                SELECT `value`
                FROM `config`
                WHERE `name` = 'APACHE_VLOGGER_SQL_USER'
            "
        ) };

        if ( defined $vloggerSqlUser ) {
            $vloggerSqlUser = decryptRijndaelCBC(
                $::imscpDBKey, $::imscpDBiv, $vloggerSqlUser
            );

            for my $host (
                $::imscpOldConfig{'DATABASE_USER_HOST'},
                $::imscpConfig{'DATABASE_USER_HOST'},
            ) {
                next unless length $host;
                Servers::sqld->factory()->dropUser( $vloggerSqlUser, $host );
            }
        }

        $dbh->do(
            "DELETE FROM `config` WHERE `name` LIKE 'APACHE_VLOGGER_SQL_%'"
        );

        for my $dir (
            $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'},
            $self->{'phpConfig'}->{'PHP_FCGI_STARTER_DIR'}
        ) {
            iMSCP::Dir->new( dirname => $dir )->remove();
        }

        my $rs = $self->{'httpd'}->disableModules( 'fcgid_imscp' );
        return $rs if $rs;

        for my $file ( 'fcgid_imscp.conf', 'fcgid_imscp.load' ) {
            next unless -f "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$file";
            $rs = iMSCP::File->new(
                filename => "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$file"
            )->delFile();
            return $rs if $rs;
        }

        if ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_nameserver.conf" ) {
            $rs = $self->{'httpd'}->disableSites( '00_nameserver.conf' );
            $rs ||= iMSCP::File->new(
                filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_nameserver.conf"
            )->delFile();
            return $rs if $rs;
        }

        my $confDir = -d "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available"
            ? "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available"
            : "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d";

        if ( -f "$confDir/00_imscp.conf" ) {
            $rs = $self->{'httpd'}->disableConfs( '00_imscp.conf' );
            $rs ||= iMSCP::File->new(
                filename => "$confDir/00_imscp.conf"
            )->delFile();
            return $rs if $rs;
        }

        for my $site ( '000-default', 'default' ) {
            next unless -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site";
            $rs = $self->{'httpd'}->enableSites( $site );
            return $rs if $rs;
        }

        0;
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs;
}

sub _init
{
    my ( $self ) = @_;

    $self->{'httpd'} = Servers::httpd::apache_fcgid->getInstance();
    $self->{'apacheCfgDir'} = $self->{'httpd'}->{'apacheCfgDir'};
    $self->{'config'} = $self->{'httpd'}->{'config'};
    $self->{'phpConfig'} = $self->{'httpd'}->{'phpConfig'};
    $self;
}

1;
__END__
