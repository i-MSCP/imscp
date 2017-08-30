=head1 NAME

 Modules::CustomDNS - i-MSCP CustomDNS module

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

package Modules::CustomDNS;

use strict;
use warnings;
use iMSCP::Debug qw/ error getLastError /;
use Text::Balanced qw/ extract_multiple extract_delimited /;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP CustomDNS module.

=head1 PUBLIC METHODS

=over 4

=item getType( )

 Get module type

 Return string Module type

=cut

sub getType
{
    'CustomDNS';
}

=item process( $domainId )

 Process module

 Note: Even if a DNS resource record is invalid, we always return 0 (success).
 It is the responsability of customers to fix their DNS resource records.

 Param string $domainId Domain unique identifier (domain type + domain id)
 Return int 0 on success, other on failure

=cut

sub process
{
    my ($self, $domainId) = @_;

    ( my $domainType, $domainId ) = split '_', $domainId;

    unless ( $domainType && $domainId ) {
        error( 'Bad input data...' );
        return 1;
    }

    my $condition = $domainType eq 'domain'
        ? "domain_id = $domainId AND alias_id = 0" : "alias_id = $domainId";

    my $rs = $self->_loadData( $domainType, $domainId );
    return $rs if $rs;

    if ( $self->add() ) {
        local $@;
        eval {
            local $self->{'_dbh'}->{'RaiseError'} = 1;
            $self->{'_dbh'}->do(
                "UPDATE domain_dns SET domain_dns_status = ? WHERE $condition AND domain_dns_status <> 'disabled'",
                undef, ( getLastError( 'error' ) || 'Invalid DNS resource record' )
            );
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        return 0;
    }

    local $@;
    eval {
        local $self->{'_dbh'}->{'RaiseError'} = 1;
        $self->{'_dbh'}->begin_work();

        $self->{'_dbh'}->do(
            "
                UPDATE domain_dns
                SET domain_dns_status = IF(
                    domain_dns_status = 'todisable', 'disabled',
                    IF(domain_dns_status NOT IN('todelete', 'disabled'), 'ok', domain_dns_status)
                )
                WHERE $condition
            "
        );
        $self->{'_dbh'}->do( "DELETE FROM domain_dns WHERE $condition AND domain_dns_status = 'todelete'" );
        $self->{'_dbh'}->commit();
    };
    if ( $@ ) {
        $self->{'_dbh'}->rollback();
        error( $@ );
        return 1;
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item init( )

 Initialize instance

 Return Modules::CustomDNS

=cut

sub _init
{
    my ($self) = @_;

    $self->{'domain_name'} = undef;
    $self->{'dns_records'} = [];
    $self->SUPER::_init();
}

=item _loadData( $domainType, $domainId )

 Load data

 Param string domainType Domain Type (alias|domain)
 Param int $domainId Domain unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
    my ($self, $domainType, $domainId) = @_;

    eval {
        my $condition = $domainType eq 'domain'
            ? "t1.domain_id = $domainId AND t1.alias_id = 0" : "t1.alias_id = $domainId";

        local $self->{'_dbh'}->{'RaiseError'} = 1;
        my $rows = $self->{'_dbh'}->selectall_arrayref(
            "
                SELECT t1.domain_dns, t1.domain_class, t1.domain_type, t1.domain_text, t1.domain_dns_status,
                    IFNULL(t3.alias_name, t2.domain_name) AS domain_name, t4.ip_number
                FROM domain_dns AS t1
                LEFT JOIN domain AS t2 USING(domain_id)
                LEFT JOIN domain_aliasses AS t3 USING(alias_id)
                LEFT JOIN server_ips AS t4 ON (IFNULL(t3.alias_ip_id, t2.domain_ip_id) = t4.ip_id)
                WHERE $condition
                AND domain_dns_status <> 'disabled'
            "
        );

        @{$rows} && defined $rows->[0]->[5] or die(
            sprintf( 'Data not found for custom DNS records (%s/%d)', $domainType, $domainId )
        );

        $self->{'domain_name'} = $rows->[0]->[5];
        $self->{'domain_ip'} = $rows->[0]->[6];

        # 1. Filter DNS records that must be disabled or deleted
        # 2. For TXT/SPF records, split data field into several
        #    <character-string>s when <character-string> is longer than 255
        #    bytes. See: https://tools.ietf.org/html/rfc4408#section-3.1.3
        for ( @{$rows} ) {
            # Filter DNS records that must be disabled or deleted
            next if $_->[4] =~ /^to(?:disable|delete)$/;

            if ( $_->[2] eq 'TXT' || $_->[2] eq 'SPF' ) {
                # Turn line-breaks into whitespaces
                $_->[3] =~ s/\R+/ /g;

                # Remove leading and trailing whitespaces
                $_->[3] =~ s/^\s+|\s+$//;

                # Make sure to work with quoted <character-string>
                $_->[3] = qq/"$_->[3]"/ unless $_->[3] =~ /^".*"$/;

                # Split data field into several <character-string>s when
                # <character-string> is longer than 255 bytes, excluding delimiters.
                # See: https://tools.ietf.org/html/rfc4408#section-3.1.3
                if ( length $_->[3] > 257 ) {
                    # Extract all quoted <character-string>s, excluding delimiters
                    $_ =~ s/^"(.*)"$/$1/ for my @chunks = extract_multiple(
                        $_->[3], [ sub { extract_delimited( $_[0], '"' ) } ], undef, 1
                    );
                    $_->[3] = join '', @chunks if @chunks;
                    undef @chunks;

                    for ( my $i = 0, my $length = length $_->[3]; $i < $length; $i += 255 ) {
                        push( @chunks, substr( $_->[3], $i, 255 ));
                    }

                    $_->[3] = join ' ', map( qq/"$_"/, @chunks );
                }
            }

            push @{$self->{'dns_records'}}, [ ( @{$_} )[0 .. 3] ];
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _getData( $action )

 Data provider method for servers and packages

 Param string $action Action
 Return hashref Reference to a hash containing data

=cut

sub _getData
{
    my ($self, $action) = @_;

    $self->{'_data'} = do {
        {
            ACTION                => $action,
            BASE_SERVER_PUBLIC_IP => $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'},
            DOMAIN_NAME           => $self->{'domain_name'},
            DOMAIN_IP             => $self->{'domain_ip'},
            DNS_RECORDS           => [ @{$self->{'dns_records'}} ]
        }
    } unless %{$self->{'_data'}};

    $self->{'_data'};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
