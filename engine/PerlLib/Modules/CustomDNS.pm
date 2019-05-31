=head1 NAME

 Modules::CustomDNS - i-MSCP CustomDNS module

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

package Modules::CustomDNS;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Debug qw/ error getMessageByType /;
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

=item process( \%data )

 Process module

 Even if a DNS resource record isn't valid, we always return 0 (success). It is
 the responsability of the customers to fix their DNS resource records.

 Param hashref \%data Custom DNS record data
 Return int 0 on success, other on failure

=cut

sub process
{
    my ( $self, $data ) = @_;

    my $rs = $self->_loadData( $data->{'id'}, $data->{'type'} );
    return $rs if $rs;

    $self->_normalizeRRs();

    if ( $self->add() ) {
        local $@;
        eval {
            $self->{'_dbh'}->do(
                "
                    UPDATE domain_dns
                    SET domain_dns_status = ?
                    WHERE @{ [ $data->{'type'} eq 'domain'
                        ? 'domain_id = ? AND alias_id = 0' : 'alias_id = ?'
                    ] }
                    AND domain_dns_status <> 'disabled'
                ",
                undef,
                getMessageByType( 'error', { amount => 1, remove => TRUE } )
                    || 'Invalid DNS resource record',
                $data->{'id'}
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
        $self->{'_dbh'}->begin_work();
        $self->{'_dbh'}->do(
            "
                UPDATE domain_dns
                SET domain_dns_status = IF(
                    domain_dns_status = 'todisable',
                    'disabled',
                    IF(domain_dns_status NOT IN('todelete', 'disabled'),
                        'ok', domain_dns_status
                    )
                )
                WHERE @{ [ $data->{'type'} eq 'domain'
                    ? 'domain_id = ? AND alias_id = 0' : 'alias_id = ?'
                ] }
            ",
            undef,
            $data->{'id'}
        );
        $self->{'_dbh'}->do(
            "
                DELETE FROM domain_dns
                WHERE @{ [ $data->{'type'} eq 'domain'
                    ? 'domain_id = ? AND alias_id = 0' : 'alias_id = ?'
                ] }
                AND domain_dns_status = 'todelete'
            ",
            undef,
            $data->{'id'}
        );
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
    my ( $self ) = @_;

    @{ $self }{qw/ dns_zone dns_rr /} = ( undef, [] );
    $self->SUPER::_init();
}

=item _loadData( $domainID, $domainType )

 Load all DNS resource records that belong to the given domain

 Param string $domainID Domain unique identifier
 Param string $domainType Domain type (alias|domain)
 Return int 0 on success, other on failure

=cut

sub _loadData
{
    my ( $self, $domainID, $domainType ) = @_;

    eval {
        $self->{'dns_rr'} = $self->{'_dbh'}->selectall_arrayref(
            "
                SELECT
                    CASE
                        WHEN LOCATE('\t', domain_dns) THEN SUBSTRING_INDEX(domain_dns, '\t', 1)
                        WHEN LOCATE(' ', domain_dns) THEN SUBSTRING_INDEX(domain_dns, ' ', 1)
                    ELSE
                        domain_dns
                    END AS `name`,
                    CASE
                        WHEN LOCATE('\t', domain_dns) THEN SUBSTRING_INDEX(domain_dns, '\t', -1)
                        WHEN LOCATE(' ', domain_dns) THEN SUBSTRING_INDEX(domain_dns, ' ', -1)
                    ELSE
                        10800
                    END AS `ttl`,
                    domain_class AS `class`,
                    domain_type AS `type`,
                    domain_text AS `rdata`
                FROM domain_dns
                WHERE @{ [ $domainType eq 'domain'
                    ? 'domain_id = ? AND alias_id = 0' : 'alias_id = ?'
                ] }
                AND domain_dns_status NOT IN ('todisable','todelete','disabled')
            ",
            { Slice => {} },
            $domainID
        );

        if ( $domainType eq 'domain' ) {
            $self->{'dns_zone'} = $self->{'_dbh'}->selectcol_arrayref(
                'SELECT domain_name FROM domain WHERE domain_id = ?',
                undef,
                $domainID
            )->[0];
        } else {
            $self->{'dns_zone'} = $self->{'_dbh'}->selectcol_arrayref(
                'SELECT alias_name FROM domain_aliasses WHERE alias_id = ?',
                undef, $domainID
            )->[0];
        }

        defined $self->{'dns_zone'} or die( sprintf(
            'DNS zone not found for custom DNS RR group (%s/%d)',
            $domainType,
            $domainID
        ));
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _normalizeRRs( )

 Normalize all DNS resource records

 Return void

=cut

sub _normalizeRRs
{
    my ( $self ) = @_;

    # Normalize TXT and SPF RRs
    for my $rr ( @{ $self->{'dns_rr'} } ) {
        next unless grep ( $_ eq $rr->{'type'}, qw/ TXT SPF /);

        # Turn line-breaks into whitespaces
        $rr->{'rdata'} =~ s/\R+/ /go;
        # Remove leading and trailing whitespaces if any
        $rr->{'rdata'} =~ s/^\s+|\s+$//o;
        # Make sure to work with quoted <character-string>
        $rr->{'rdata'} = qq/"$rr->{'rdata'}"/ unless $rr->{'rdata'} =~ /^".*"$/o;

        # Split data field into several <character-string>s when
        # <character-string> is longer than 255 bytes, excluding delimiters.
        # See: https://tools.ietf.org/html/rfc4408#section-3.1.3
        if ( length $rr->{'rdata'} > 257 ) {
            # Extract all quoted <character-string>s, excluding delimiters
            $_ =~ s/^"(.*)"$/$1/o for my @chunks = extract_multiple(
                $rr->{'rdata'},
                [ sub { extract_delimited( $_[0], '"' ) } ],
                undef,
                TRUE
            );
            $rr->{'rdata'} = join '', @chunks if @chunks;
            undef @chunks;

            for ( my $i = 0, my $length = length $rr->{'rdata'};
                $i < $length; $i += 255
            ) {
                push( @chunks, substr( $rr->{'rdata'}, $i, 255 ));
            }

            $rr->{'rdata'} = join ' ', map ( qq/"$_"/, @chunks );
        }
    }
}

=item _getData( $action )

 Data provider method for servers and packages

 Param string $action Action
 Return hashref Reference to a hash containing data

=cut

sub _getData
{
    my ( $self, $action ) = @_;

    $self->{'_data'} = do { {
        ACTION   => $action,
        DNS_ZONE => $self->{'dns_zone'},
        DNS_RR   => $self->{'dns_rr'}
    } } unless %{ $self->{'_data'} };

    $self->{'_data'};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
