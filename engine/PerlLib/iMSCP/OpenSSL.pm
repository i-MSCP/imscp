=head1 NAME

iMSCP::OpenSSL - i-MSCP OpenSSL library

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

package iMSCP::OpenSSL;

use strict;
use warnings;
use File::Temp;
use Date::Parse;
use iMSCP::Debug qw/ error debug /;
use iMSCP::Execute qw/ execute escapeShell /;
use iMSCP::File;
use iMSCP::TemplateParser;
use parent 'Common::Object';

=head1 DESCRIPTION

 Library allowing to check and import SSL certificates in single container (PEM).

=head1 PUBLIC METHODS

=over 4

=item validatePrivateKey( )

 Validate private key

 Return int 0 on success, other on failure

=cut

sub validatePrivateKey
{
    my ($self) = @_;

    unless ( $self->{'private_key_container_path'} ) {
        error( 'Path to SSL private key is not set' );
        return 1;
    }

    unless ( -f $self->{'private_key_container_path'} ) {
        error( sprintf( "%s SSL private key doesn't exists", $self->{'private_key_container_path'} ));
        return 1;
    }

    my $passphraseFile;
    if ( $self->{'private_key_passphrase'} ) {
        # Write SSL private key passphrase into temporary file, which is only readable by root
        $passphraseFile = File::Temp->new( UNLINK => 1 );
        print $passphraseFile $self->{'private_key_passphrase'};
        $passphraseFile->flush();
        $passphraseFile->close();
    }

    my $cmd = [
        'openssl', 'pkey', '-in', $self->{'private_key_container_path'}, '-noout',
        ( ( $passphraseFile ) ? ( '-passin', 'file:' . $passphraseFile->filename ) : () )
    ];

    my $rs = execute( $cmd, \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error(
        sprintf(
            "Couldn't import SSL private key from %s file: %s",
            $self->{'private_key_container_path'},
            $stderr || 'unknown error'
        )
    ) if $rs;
    $rs;
}

=item validateCertificate( )

 Validate certificate

 If a CA Bundle (intermediate certificate(s)) is set, the whole certificate chain will be checked

 Return int 0 on success, other on failure

=cut

sub validateCertificate
{
    my ($self) = @_;

    unless ( $self->{'certificate_container_path'} ) {
        error( 'Path to SSL certificate is not set' );
        return 1;
    }

    unless ( -f $self->{'certificate_container_path'} ) {
        error( sprintf( "%s SSL certificate doesn't exists", $self->{'certificate_container_path'} ));
        return 1;
    }

    my $caBundle = 0;

    if ( $self->{'ca_bundle_container_path'} ) {
        unless ( -f $self->{'ca_bundle_container_path'} ) {
            error( sprintf( "%s SSL CA Bundle doesn't exists", $self->{'ca_bundle_container_path'} ));
            return 1;
        }

        $caBundle = 1;
    } else {
        # We asssume a self-signed SSL certificate.
        # We need trust the self-signed SSL certificate for validation time, else
        # the 18 at 0 depth lookup: self signed certificate' error is raised (openssl >= 1.1.0)
        $self->{'ca_bundle_container_path'} = $self->{'certificate_container_path'};
    }

    my $cmd = [
        'openssl', 'verify',
        ( ( $self->{'ca_bundle_container_path'} ne '' ) ? ( '-CAfile', $self->{'ca_bundle_container_path'} ) : () ),
        '-purpose', 'sslserver', $self->{'certificate_container_path'}
    ];

    my $rs = execute( $cmd, \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( sprintf(
        "SSL certificate is not valid: %s",
        ( $stderr || $stdout || 'Unknown error' ) =~ s/$self->{'certificate_container_path'}:\s+//r
    )) if $rs;

    $self->{'ca_bundle_container_path'} = '' unless $caBundle;
    $rs;
}

=item validateCertificateChain( )

 Validate certificate chain

 Return int 0 on success, other on failure

=cut

sub validateCertificateChain
{
    my ($self) = @_;

    my $rs = $self->validatePrivateKey();
    $rs ||= $self->validateCertificate();
}

=item importPrivateKey( )

 Import private key in certificate chain container

 Return int 0 on success, other on failure

=cut

sub importPrivateKey
{
    my ($self) = @_;

    my $passphraseFile;
    if ( $self->{'private_key_passphrase'} ) {
        # Write SSL private key passphrase into temporary file, which is only readable by root
        $passphraseFile = File::Temp->new( UNLINK => 1 );
        print $passphraseFile $self->{'private_key_passphrase'};
        $passphraseFile->flush();
        $passphraseFile->close();
    }

    my $cmd = [
        'openssl', 'pkey', '-in', $self->{'private_key_container_path'},
        '-out', "$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem",
        ( ( $passphraseFile ) ? ( '-passin', 'file:' . $passphraseFile->filename ) : () )
    ];

    my $rs = execute( $cmd, \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( sprintf( "Couldn't import SSL private key: %s", $stderr || 'unknown error' )) if $rs;
    $rs;
}

=item importCertificate( )

 Import certificate in certificate chain container

 Return int 0 on success, other on failure

=cut

sub importCertificate
{
    my ($self) = @_;

    my $file = iMSCP::File->new( filename => $self->{'certificate_container_path'} );
    my $certificateRef = $file->getAsRef();
    unless ( defined $certificateRef ) {
        error( sprintf( "Couldn't read %s file", $self->{'certificate_container_path'} ));
        return 1;
    }

    ${$certificateRef} =~ s/^(?:\015?\012)+|(?:\015?\012)+$//g;
    ${$certificateRef} .= "\n";

    my $rs = $file->save();
    return $rs if $rs;

    my @cmd = (
        'cat', escapeShell( $self->{'certificate_container_path'} ),
        '>>', escapeShell( "$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem" )
    );

    $rs = execute( "@cmd", \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( sprintf( "Couldn't import SSL certificate: %s", $stderr || 'unknown error' )) if $rs;
    $rs;
}

=item importCaBundle( )

 Import the CA Bundle in certificate chain container if any

 Return int 0 on success, other on failure

=cut

sub importCaBundle
{
    my ($self) = @_;

    return 0 unless $self->{'ca_bundle_container_path'};

    my $file = iMSCP::File->new( filename => $self->{'ca_bundle_container_path'} );
    my $caBundleRef = $file->getAsRef();
    unless ( defined $caBundleRef ) {
        error( sprintf( "Couldn't read %s file", $self->{'ca_bundle_container_path'} ));
        return 1;
    }

    ${$caBundleRef} =~ s/^(?:\015?\012)+|(?:\015?\012)+$//g;
    ${$caBundleRef} .= "\n";

    my $rs = $file->save();
    return $rs if $rs;

    my @cmd = (
        'cat', escapeShell( $self->{'ca_bundle_container_path'} ),
        '>>', escapeShell( "$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem" )
    );

    $rs = execute( "@cmd", \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( sprintf( "Couldn't import SSL CA Bundle: %s", $stderr || 'unknown error' )) if $rs;
    $rs;
}

=item createSelfSignedCertificate( \%data )

 Generate a self-signed SSL certificate

 Param hash \%data Certificate data (common_name, email, wildcard = false)
 Param bool $wildcardSSL OPTIONAL Does a wildcard SSL certificate must be generated (default FALSE)
 Return int 0 on success, other on failure

=cut

sub createSelfSignedCertificate
{
    my ($self, $data) = @_;

    ref $data eq 'HASH' or die( 'Wrong $data parameter. Hash expected' );
    $data->{'common_name'} or die( 'Missing common_name parameter' );
    $data->{'email'} or die( 'Missing email parameter' );

    my $openSSLConffileTpl = "$main::imscpConfig{'CONF_DIR'}/openssl/openssl.cnf.tpl";
    my $commonName = $data->{'wildcard'} ? '*.' . $data->{'common_name'} : $data->{'common_name'};

    # Load openssl configuration template file for self-signed SSL certificates
    my $openSSLConffileTplContent = iMSCP::File->new( filename => $openSSLConffileTpl )->get();
    unless ( defined $openSSLConffileTplContent ) {
        error( sprintf( "Couldn't load %s openssl configuration template file", $openSSLConffileTpl ));
        return 1;
    }

    # Write openssl configuration file into temporary file
    my $openSSLConffile = File::Temp->new( UNLINK => 1 );
    print $openSSLConffile process(
        {
            COMMON_NAME   => $commonName,
            EMAIL_ADDRESS => $data->{'email'},
            ALT_NAMES     => ( $data->{'wildcard'}
                ? "DNS.1 = $commonName\n" : "DNS.1 = $commonName\nDNS.2 = www.$commonName\n"
            )
        },
        $openSSLConffileTplContent
    );
    $openSSLConffile->flush();
    $openSSLConffile->close();

    my $cmd = [
        'openssl', 'req', '-x509', '-nodes', '-days', '365', '-config', $openSSLConffile->filename, '-newkey', 'rsa',
        '-keyout', "$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem",
        '-out', "$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem"
    ];

    my $rs = execute( $cmd, \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( sprintf( "Couldn't generate self-signed certificate: %s", $stderr || 'unknown error' )) if $rs;
    $rs
}

=item createCertificateChain( )

 Create certificate chain (import private key, certificate and CA Bundle)

 Return 0 on success, other on failure

=cut

sub createCertificateChain
{
    my ($self) = @_;

    my $rs = $self->importPrivateKey();
    $rs ||= $self->importCertificate();
    $rs ||= $self->importCaBundle();
}

=item getCertificateExpiryTime( [ certificatePath = $self->{'certificate_container_path'} ] )

 Get SSL certificate expiry time

 Param string certificatePath Path to SSL certificate (default: $self->{'certificate_container_path'})
 Return timestamp on success, undef

=cut

sub getCertificateExpiryTime
{
    my ($self, $certificatePath) = @_;
    $certificatePath ||= $self->{'certificate_container_path'};

    unless ( $certificatePath ) {
        error( 'Invalide SSL certificate path provided' );
        return undef;
    }

    my $rs = execute(
        [ 'openssl', 'x509', '-enddate', '-noout', '-in', $certificatePath ], \ my $stdout, \ my $stderr
    );
    debug( $stdout ) if $stdout;

    unless ( $rs == 0 && $stdout =~ /^notAfter=(.*)/i ) {
        error( sprintf( "Couldn't get SSL certificate expiry time: %s", $stderr || 'unknown error' ));
        return undef;
    }

    str2time( $1 );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::OpenSSL

=cut

sub _init
{
    my ($self) = @_;

    # Full path to the certificate chains storage directory
    $self->{'certificate_chains_storage_dir'} = '' unless $self->{'certificate_chains_storage_dir'};

    # Certificate chain name
    $self->{'certificate_chain_name'} = '' unless $self->{'certificate_chain_name'};

    # Full path to the private key container
    $self->{'private_key_container_path'} = '' unless $self->{'private_key_container_path'};

    # Private key passphrase if any
    $self->{'private_key_passphrase'} = '' unless $self->{'private_key_passphrase'};

    # Full path to the SSL certificate container
    $self->{'certificate_container_path'} = '' unless $self->{'certificate_container_path'};

    # Full path to the CA Bundle container (Container which contain one or many intermediate certificates)
    $self->{'ca_bundle_container_path'} = '' unless $self->{'ca_bundle_container_path'};

    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
