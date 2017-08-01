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

package Servers::httpd::apache_itk::uninstaller;

use strict;
use warnings;
use iMSCP::Dir;
use iMSCP::File;
use Servers::httpd::apache_itk;
use Servers::sqld;
use parent 'Common::SingletonClass';

sub uninstall
{
    my ($self) = @_;

    my $rs = $self->_removeVloggerSqlUser();
    $rs ||= $self->_removeDirs();
    $rs ||= $self->_vHostConf();
}

sub _init
{
    my ($self) = @_;

    $self->{'httpd'} = Servers::httpd::apache_itk->getInstance();
    $self->{'apacheCfgDir'} = $self->{'httpd'}->{'apacheCfgDir'};
    $self->{'config'} = $self->{'httpd'}->{'config'};
    $self->{'phpConfig'} = $self->{'httpd'}->{'phpConfig'};
    $self;
}

sub _removeVloggerSqlUser
{
    if ( $main::imscpConfig{'DATABASE_USER_HOST'} eq 'localhost' ) {
        return Servers::sqld->factory()->dropUser( 'vlogger_user', '127.0.0.1' );
    }

    Servers::sqld->factory()->dropUser( 'vlogger_user', $main::imscpConfig{'DATABASE_USER_HOST'} );
}

sub _removeDirs
{
    my ($self) = @_;

    iMSCP::Dir->new( dirname => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'} )->remove();
    0;
}

sub _vHostConf
{
    my ($self) = @_;

    if ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_nameserver.conf" ) {
        my $rs = $self->{'httpd'}->disableSites( '00_nameserver.conf' );
        $rs ||= iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_nameserver.conf"
        )->delFile();
        return $rs if $rs;
    }

    my $confDir = ( -d "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available" )
        ? "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available" : "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d";

    if ( -f "$confDir/00_imscp.conf" ) {
        my $rs = $self->{'httpd'}->disableConfs( '00_imscp.conf' );
        $rs ||= iMSCP::File->new( filename => "$confDir/00_imscp.conf" )->delFile();
        return $rs if $rs;
    }

    for ( '000-default', 'default' ) {
        next unless -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_";
        my $rs = $self->{'httpd'}->enableSites( $_ );
        return $rs if $rs;
    }

    0;
}

1;
__END__
