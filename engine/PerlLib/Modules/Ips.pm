=head1 NAME

 Modules::Ips - i-MSCP Ips module

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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

package Modules::Ips;

use strict;
use warnings;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Net;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP IPs module.

=head1 PUBLIC METHODS

=over 4

=item getType()

 Get module type

 Return string Module type

=cut

sub getType
{
    'Ips';
}

=item process()

 Process module

 Return int 0 on success, other on failure

=cut

sub process
{
    my $self = shift;

    my $rs = $self->_loadData();
    $rs ||= $self->add();
}

=item addIps()

 Add IP addresses

 Return int 0 on success, other on failure

=cut

sub add
{
    my $self = shift;

    my $ips = {
        IPS     => $self->{'ipaddrs'},
        SSL_IPS => $self->{'ssl_ipaddrs'}
    };

    my $rs = iMSCP::EventManager->trigger( 'beforeAddIps', $ips );
    $rs ||= $self->SUPER::add();
    $rs = iMSCP::EventManager->trigger( 'afterAddIps', $ips );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _loadData()

 Load data

 Return int 0 on success, other on failure

=cut

sub _loadData
{
    my $self = shift;

    my $net = iMSCP::Net->getInstance();
    my $db = iMSCP::Database->factory();

    my $rdata = $db->doQuery(
        'ip_number',
        "
            SELECT ip_number FROM domain INNER JOIN server_ips ON (domain.domain_ip_id = server_ips.ip_id)
            WHERE domain_status <> 'todelete'
            UNION
            SELECT ip_number FROM domain_aliasses
            INNER JOIN server_ips ON (domain_aliasses.alias_ip_id = server_ips.ip_id)
            WHERE alias_status NOT IN ('todelete', 'ordered')
        "
    );
    unless (ref $rdata eq 'HASH') {
        error( $rdata );
        return 1;
    }

    $rdata->{$main::imscpConfig{'BASE_SERVER_IP'}} = undef;
    @{$self->{'ipaddrs'}} = map $net->normalizeAddr( $_ ), keys %{$rdata};

    $rdata = $db->doQuery(
        'ip_number',
        "
            SELECT ip_number FROM ssl_certs
            INNER JOIN domain ON (ssl_certs.domain_id = domain.domain_id)
            INNER JOIN server_ips ON (domain.domain_ip_id = server_ips.ip_id)
            WHERE ssl_certs.domain_type = 'dmn'
            UNION
            SELECT ip_number FROM ssl_certs
            INNER JOIN domain_aliasses ON (ssl_certs.domain_id = domain_aliasses.alias_id)
            INNER JOIN server_ips ON (domain_aliasses.alias_ip_id = server_ips.ip_id)
            WHERE ssl_certs.domain_type = 'als'
            UNION
            SELECT ip_number FROM ssl_certs
            INNER JOIN subdomain_alias ON (ssl_certs.domain_id = subdomain_alias.subdomain_alias_id)
            INNER JOIN domain_aliasses ON (subdomain_alias.alias_id = domain_aliasses.alias_id)
            INNER JOIN server_ips ON (domain_aliasses.alias_ip_id = server_ips.ip_id)
            WHERE ssl_certs.domain_type = 'alssub'
            UNION
            SELECT ip_number FROM ssl_certs
            INNER JOIN subdomain ON (ssl_certs.domain_id = subdomain.subdomain_id)
            INNER JOIN domain ON (subdomain.domain_id = domain.domain_id)
            INNER JOIN server_ips ON (domain.domain_ip_id = server_ips.ip_id) WHERE ssl_certs.domain_type = 'sub'
        "
    );
    unless (ref $rdata eq 'HASH') {
        error( $rdata );
        return 1;
    }

    if ($main::imscpConfig{'PANEL_SSL_ENABLED'} eq 'yes') {
        $rdata->{$main::imscpConfig{'BASE_SERVER_IP'}} = undef;
    }

    @{$self->{'ssl_ipaddrs'}} = map $net->normalizeAddr( $_ ), keys %{$rdata};
    0;
}

=item _getHttpdData($action)

 Data provider method for Httpd servers

 Param string $action Action
 Return hash Hash containing module data

=cut

sub _getHttpdData
{
    my ($self, $action) = @_;

    return %{$self->{'httpd'}} if $self->{'httpd'};

    $self->{'httpd'} = {
        IPS     => $self->{'ipaddrs'},
        SSL_IPS => $self->{'ssl_ipaddrs'}
    };
    %{$self->{'httpd'}};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
