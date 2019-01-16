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
use Try::Tiny;
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

 Note: Even if a DNS resource record is invalid, we always return 0 (success).
 It is the responsability of customers to fix their DNS resource records.

 Param hashref \%data Custom DNS record data
 Return int 0 on success, die on failure

=cut

sub process
{
    my ( $self, $data ) = @_;

    $self->_loadData( $data->{'id'}, $data->{'type'} );

    if ( $self->add() ) {
        $self->{'_conn'}->run( fixup => sub {
            $_->do(
                "
                    UPDATE domain_dns
                    SET domain_dns_status = ?
                    WHERE @{ [ $data->{'type'} eq 'domain' ? 'domain_id = ? AND alias_id = 0' : 'alias_id = ?' ] }
                    AND domain_dns_status <> 'disabled'
                ",
                undef, getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error', $data->{'id'}
            );
        } );

        return 0;
    }

    $self->{'_conn'}->txn( fixup => sub {
        $_->do(
            "
                UPDATE domain_dns
                SET domain_dns_status = IF(
                    domain_dns_status = 'todisable', 'disabled', IF(domain_dns_status NOT IN('todelete', 'disabled'), 'ok', domain_dns_status)
                )
                WHERE @{ [ $data->{'type'} eq 'domain' ? 'domain_id = ? AND alias_id = 0' : 'alias_id = ?' ] }
            ",
            undef, $data->{'id'}
        );
        $_->do(
            "
                DELETE FROM domain_dns
                WHERE @{ [ $data->{'type'} eq 'domain' ? 'domain_id = ? AND alias_id = 0' : 'alias_id = ?' ] }
                AND domain_dns_status = 'todelete'
            ",
            undef, $data->{'id'}
        );
    } );
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

    @{ $self }{qw/ zone_name dns_records /} = ( undef, [] );
    $self->SUPER::_init();
}

=item _loadData( $domainId,  $domainType )

 Load data

 Param int $domainId Domain unique identifier
 Param string $domainType Domain Type (alias|domain)
 Return void, die on failure

=cut

sub _loadData
{
    my ( $self, $domainId, $domainType ) = @_;

    my $rows = $self->{'_conn'}->run( fixup => sub {
        $_->selectall_arrayref(
            "
                SELECT SUBSTRING_INDEX(domain_dns, '\t', 1), SUBSTRING_INDEX(domain_dns, '\t', -1), domain_class, domain_type, domain_text,
                    domain_dns_status
                FROM domain_dns
                WHERE @{ [ $domainType eq 'domain' ? 'domain_id = ? AND alias_id = 0' : 'alias_id = ?' ] }
                AND domain_dns_status <> 'disabled'
            ",
            undef, $domainId
        );
    } );
    @{ $rows } or die( sprintf( 'Data not found for custom DNS records (%s/%d)', $domainType, $domainId ));

    ( $self->{'zone'} ) = $self->{'_conn'}->run( fixup => sub {
        return @{ $_->selectcol_arrayref( 'SELECT domain_name FROM domain WHERE domain_id = ?', undef, $domainId ) } if $domainType eq 'domain';
        @{ $_->selectcol_arrayref( 'SELECT alias_name FROM domain_aliasses WHERE alias_id = ?', undef, $domainId ) };
    } );

    defined $self->{'zone'} or die( sprintf( 'Zone not found for custom DNS records (%s/%d)', $domainType, $domainId ));

    # 1. Filter DNS records that must be disabled or deleted
    # 2. For TXT/SPF records, split data field into several
    #    <character-string>s when <character-string> is longer than 255
    #    bytes. See: https://tools.ietf.org/html/rfc4408#section-3.1.3
    for my $rr ( @{ $rows } ) {
        # Skip DNS RR that must be disabled or deleted
        next if grep ( $_ eq $rr->[5], 'todisable', 'todelete' );

        if ( $rr->[3] eq 'TXT' || $rr->[3] eq 'SPF' ) {
            # Turn line-breaks into whitespaces
            $rr->[4] =~ s/\R+/ /g;
            # Remove leading and trailing whitespaces
            $rr->[4] =~ s/^\s+|\s+$//;
            # Make sure to work with quoted <character-string>
            $rr->[4] = qq/"$rr->[4]"/ unless $rr->[4] =~ /^".*"$/;

            # Split data field into several <character-string>s when
            # <character-string> is longer than 255 bytes, excluding delimiters.
            # See: https://tools.ietf.org/html/rfc4408#section-3.1.3
            if ( length $rr->[4] > 257 ) {
                # Extract all quoted <character-string>s, excluding delimiters
                $rr =~ s/^"(.*)"$/$1/ for my @chunks = extract_multiple( $rr->[4], [ sub { extract_delimited( $_[0], '"' ) } ], undef, 1 );
                $rr->[4] = join '', @chunks if @chunks;
                undef @chunks;

                for ( my $i = 0, my $length = length $_->[4]; $i < $length; $i += 255 ) {
                    push( @chunks, substr( $rr->[4], $i, 255 ));
                }

                $rr->[4] = join ' ', map ( qq/"$_"/, @chunks );
            }
        }

        push @{ $self->{'dns_records'} }, [ ( @{ $rr } )[0 .. 4] ];
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
        DNS_RECORDS => $self->{'dns_records'},
        ZONE_NAME   => $self->{'zone'}
    } } unless %{ $self->{'_data'} };

    $self->{'_data'}->{'ACTION'} = $action;
    $self->{'_data'};
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
