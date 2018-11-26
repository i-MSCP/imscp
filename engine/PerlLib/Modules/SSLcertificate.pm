=head1 NAME

 Modules::SSLcertificate - i-MSCP SSLcertificate module

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

package Modules::SSLcertificate;

use strict;
use warnings;
use iMSCP::Debug qw/ error getLastError getMessageByType warning /;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::OpenSSL;
use File::Temp;
use parent 'Modules::Abstract';

=head1 DESCRIPTION

 i-MSCP SSLcertificate module.

=head1 PUBLIC METHODS

=over 4

=item getType( )

 Get module type

 Return string Module type

=cut

sub getType
{
    'SSLcertificate';
}

=item process( $certificateId )

 Process module

 Param int $certificateId SSL certificate unique identifier
 Return int 0 on success, other on failure

=cut

sub process
{
    my ($self, $certificateId) = @_;

    my $rs = $self->_loadData( $certificateId );
    return $rs if $rs || !$self->{'domain_name'};

    my @sql;
    if ( $self->{'status'} =~ /^to(?:add|change)$/ ) {
        $rs = $self->add();
        @sql = ( 'UPDATE ssl_certs SET status = ? WHERE cert_id = ?', undef,
            ( $rs
                ? ( getMessageByType( 'error', { amount => 1, remove => 1 } )
                    || 'Unknown error' ) =~ s/iMSCP::OpenSSL::validateCertificate:\s+//r
                : 'ok'
            ),
            $certificateId );
    } elsif ( $self->{'status'} eq 'todelete' ) {
        $rs = $self->delete();
        @sql = $rs
            ? ( 'UPDATE ssl_certs SET status = ? WHERE cert_id = ?', undef,
                getLastError( 'error' ) || 'Unknown error', $certificateId )
            : ( 'DELETE FROM ssl_certs WHERE cert_id = ?', undef, $certificateId );
    } else {
        warning( sprintf( 'Unknown action (%s) for SSL certificate (ID %d)', $self->{'status'}, $certificateId ));
        return 0;
    }

    local $@;
    eval {
        local $self->{'_dbh'}->{'RaiseError'} = 1;
        $self->{'_dbh'}->do( @sql );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # (since 1.2.16 - See #IP-1500)
    # On toadd and to change actions, return 0 to avoid any failure on update when a customer's SSL certificate is
    # expired or invalid. It is the customer responsability to update the certificate throught his interface
    ( $self->{'status'} =~ /^to(?:add|change)$/ ) ? 0 : $rs;
}

=item add( )

 Add SSL certifcate

 Return int 0 on success, other on failure

=cut

sub add
{
    my ($self) = @_;

    # Remove previous SSL certificate if any
    my $rs = $self->delete();
    return $rs if $rs;

    # Private key
    my $privateKeyContainer = File::Temp->new( UNLINK => 1 );
    print $privateKeyContainer $self->{'private_key'};
    $privateKeyContainer->flush();
    $privateKeyContainer->close();

    # Certificate
    my $certificateContainer = File::Temp->new( UNLINK => 1 );
    print $certificateContainer $self->{'certificate'};
    $certificateContainer->flush();
    $certificateContainer->close();

    # CA Bundle (intermediate certificate(s))
    my $caBundleContainer;
    if ( $self->{'ca_bundle'} ) {
        $caBundleContainer = File::Temp->new( UNLINK => 1 );
        print $caBundleContainer $self->{'ca_bundle'};
        $caBundleContainer->flush();
        $caBundleContainer->close();
    }

    # Create OpenSSL object
    my $openSSL = iMSCP::OpenSSL->new(
        certificate_chains_storage_dir => $self->{'certsDir'},
        certificate_chain_name         => $self->{'domain_name'},
        private_key_container_path     => $privateKeyContainer->filename,
        certificate_container_path     => $certificateContainer->filename,
        ca_bundle_container_path       => $caBundleContainer ? $caBundleContainer->filename : ''
    );

    # Check certificate chain
    $rs = $openSSL->validateCertificateChain();

    # Create certificate chain (private key, certificate and CA bundle)
    $rs ||= $openSSL->createCertificateChain();
}

=item delete( )

 Delete SSL certificate

 Return int 0 on success, other on failyre

=cut

sub delete
{
    my ($self) = @_;

    return 0 unless -f "$self->{'certsDir'}/$self->{'domain_name'}.pem";
    iMSCP::File->new( filename => "$self->{'certsDir'}/$self->{'domain_name'}.pem" )->delFile();
}

=item _init( )

 Initialize instance

 Return Modules::SSLcertificate

=cut

sub _init
{
    my ($self) = @_;

    $self->{'certsDir'} = "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs";
    iMSCP::Dir->new( dirname => $self->{'certsDir'} )->make(
        {

            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => 0750
        }
    );
    $self->SUPER::_init();
}

=item _loadData( $certificateId )

 Load data

 Param int $certificateId SSL certificate unique identifier
 Return int 0 on success, other on failure

=cut

sub _loadData
{
    my ($self, $certificateId) = @_;

    local $@;
    eval {
        local $self->{'_dbh'}->{'RaiseError'} = 1;
        my $row = $self->{'_dbh'}->selectrow_hashref(
            'SELECT * FROM ssl_certs WHERE cert_id = ?', undef, $certificateId
        );
        $row or die( sprintf( 'Data not found for SSL certificate (ID %d)', $certificateId ));
        %{$self} = ( %{$self}, %{$row} );

        if ( $self->{'domain_type'} eq 'dmn' ) {
            $row = $self->{'_dbh'}->selectrow_hashref(
                'SELECT domain_name FROM domain WHERE domain_id = ?', undef, $self->{'domain_id'}
            );
        } elsif ( $self->{'domain_type'} eq 'als' ) {
            $row = $self->{'_dbh'}->selectrow_hashref(
                'SELECT alias_name AS domain_name FROM domain_aliasses WHERE alias_id = ?', undef, $self->{'domain_id'}
            );
        } elsif ( $self->{'domain_type'} eq 'sub' ) {
            $row = $self->{'_dbh'}->selectrow_hashref(
                "
                    SELECT CONCAT(subdomain_name, '.', domain_name) AS domain_name
                    FROM subdomain
                    JOIN domain USING(domain_id)
                    WHERE subdomain_id = ?
                ",
                undef, $self->{'domain_id'}
            );
        } else {
            $row = $self->{'_dbh'}->selectrow_hashref(
                "
                    SELECT CONCAT(subdomain_alias_name, '.', alias_name) AS domain_name
                    FROM subdomain_alias
                    JOIN domain_aliasses USING(alias_id)
                    WHERE subdomain_alias_id = ?
                ",
                undef, $self->{'domain_id'}
            );
        }

        unless ( $row ) {
            # Delete orphaned SSL certificate
            $self->{'_dbh'}->do( 'DELETE FROM ssl_certs WHERE cert_id = ?', undef, $certificateId );
        } else {
            %{$self} = ( %{$self}, %{$row} );
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
