=head1 NAME

 Package::FrontEnd::Uninstaller - i-MSCP FrontEnd package Uninstaller

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

package Package::FrontEnd::Uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::SystemUser;
use iMSCP::SystemGroup;
use iMSCP::Service;
use Package::FrontEnd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP FrontEnd package uninstaller.

=head1 PUBLIC METHODS

=over 4

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    my $rs = $self->_deconfigurePHP();
    $rs ||= $self->_deconfigureHTTPD();
    $rs ||= $self->_deleteMasterWebUser();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Package::FrontEnd::Uninstaller

=cut

sub _init
{
    my ($self) = @_;

    $self->{'frontend'} = Package::FrontEnd->getInstance();
    $self->{'config'} = $self->{'frontend'}->{'config'};
    $self;
}

=item _deconfigurePHP( )

 Deconfigure PHP (imscp_panel service)

 Return int 0 on success, other on failure

=cut

sub _deconfigurePHP
{
    local $@;
    eval { iMSCP::Service->getInstance()->remove( 'imscp_panel' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    for( '/etc/default/imscp_panel', '/etc/tmpfiles.d/imscp_panel.conf',
        "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/imscp_panel", '/usr/local/sbin/imscp_panel',
        '/var/log/imscp_panel.log'
    ) {
        next unless -f;
        my $rs = iMSCP::File->new( filename => $_ )->delFile();
        return $rs if $rs;
    }

    iMSCP::Dir->new( dirname => '/usr/local/lib/imscp_panel' )->remove();
    iMSCP::Dir->new( dirname => '/usr/local/etc/imscp_panel' )->remove();
    iMSCP::Dir->new( dirname => '/var/run/imscp' )->remove();
    0;
}

=item _deconfigureHTTPD( )

 Deconfigure HTTPD (nginx)

 Return int 0 on success, other on failure

=cut

sub _deconfigureHTTPD
{
    my ($self) = @_;

    my $rs = $self->{'frontend'}->disableSites( '00_master.conf' );
    return $rs if $rs;

    if ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master.conf" ) {
        $rs = iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master.conf"
        )->delFile();
        return $rs if $rs;
    }

    if ( -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/imscp_fastcgi.conf" ) {
        $rs = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/imscp_fastcgi.conf" )->delFile();
        return $rs if $rs;
    }

    if ( -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/imscp_php.conf" ) {
        $rs = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/imscp_php.conf" )->delFile();
        return $rs if $rs;
    }

    if ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/default" ) {
        # Nginx as provided by Debian
        $rs = $self->{'frontend'}->enableSites( 'default' );
        return $rs if $rs;
    } elsif ( "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf.disabled" ) {
        # Nginx package as provided by Nginx
        $rs = iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf.disabled"
        )->moveFile(
            "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf"
        );
        return $rs if $rs;
    }

    0;
}

=item _deleteMasterWebUser( )

 Delete i-MSCP master Web user

 Return int 0 on success, other on failure

=cut

sub _deleteMasterWebUser
{
    my $rs = iMSCP::SystemUser->new( force => 'yes' )->delSystemUser(
        $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
    );
    $rs ||= iMSCP::SystemGroup->getInstance()->delSystemGroup(
        $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
    );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
