=head1 NAME

 iMSCP::OpenSSL - Library for validation and generation of SSL certificates

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
use iMSCP::Boolean;
use iMSCP::Debug qw/ error debug /;
use iMSCP::Execute qw/ execute escapeShell /;
use iMSCP::File;
use iMSCP::TemplateParser 'process';
use parent 'Common::Object';

=head1 DESCRIPTION

 Library for validation and generation of SSL certificates

=head1 PUBLIC METHODS

=over 4

=item validatePrivateKey( )

 Validate private key
 
 On error, an error message isset through iMSCP::Debug::error()

 Return bool TRUE if the private key is valid, FALSE otherwise

=cut

sub validatePrivateKey
{
    my ( $self ) = @_;

    unless ( length $self->{'private_key_container_path'}
        && -f $self->{'private_key_container_path'}
    ) {
        error( 'Invalid SSL private key path.', FALSE );
        return FALSE;
    }

    my $passphraseFile;
    if ( $self->{'private_key_passphrase'} ) {
        # Write SSL private key passphrase into temporary file, which is only
        # readable by root
        $passphraseFile = File::Temp->new();
        print $passphraseFile $self->{'private_key_passphrase'};
        $passphraseFile->close();
    }

    my $cmd = [
        '/usr/bin/openssl',
        'pkey',
        '-in', $self->{'private_key_container_path'},
        '-noout',
        ( defined $passphraseFile
            ? ( '-passin', 'file:' . $passphraseFile->filename() ) : ()
        )
    ];

    my $rs = execute( $cmd, \my $stdout, \my $stderr );
    if ( $rs ) {
        debug( $stdout ) if length $stdout;
        debug( $stderr ) if length $stderr;
        error( 'Invalid SSL private key or passphrase.', FALSE );
        return FALSE;
    }

    TRUE;
}

=item validateCertificate( )

 Validate certificate
 
 On error, an error message isset through iMSCP::Debug::error()

 Return bool TRUE if the SSL certificate is valid, FALSE otherwise

=cut

sub validateCertificate
{
    my ( $self ) = @_;

    unless ( length $self->{'certificate_container_path'}
        && -f $self->{'certificate_container_path'}
    ) {
        error( 'Invalid SSL certificate path.', FALSE );
        return FALSE;
    }

    my $caBundle = FALSE;

    if ( length $self->{'ca_bundle_container_path'}
        && -f $self->{'ca_bundle_container_path'}
    ) {
        $caBundle = TRUE;
    } elsif ( !-f _ ) {
        error( 'Invalid SSL CA bundle path.', FALSE );
        return FALSE;
    } else {
        # We assume a self-signed SSL certificate.
        # We need trust the self-signed SSL certificate for validation time,
        # else the 18 at 0 depth lookup: self signed certificate' error is
        # raised (openssl >= 1.1.0)
        $self->{'ca_bundle_container_path'}
            = $self->{'certificate_container_path'};
    }

    my $cmd = [
        '/usr/bin/openssl',
        'verify',
        ( length $self->{'ca_bundle_container_path'}
            ? ( '-CAfile', $self->{'ca_bundle_container_path'} ) : ()
        ),
        '-purpose', 'sslserver',
        $self->{'certificate_container_path'}
    ];

    my $rs = execute( $cmd, \my $stdout, \my $stderr );
    if ( $rs ) {
        debug( $stdout ) if length $stdout;
        debug( $stderr ) if length $stderr;
        error( 'Invalid SSL certificate.', FALSE );
        return FALSE;
    }

    $self->{'ca_bundle_container_path'} = undef $caBundle;
    TRUE;
}

=item validateCertKeyMatching( )

 Validate certificate key matching
 
 On error, an error message isset through iMSCP::Debug::error()

 Return bool TRUE if the SSL certificate private key matches the SSL
        certificate, FALSE otherwise
=cut

sub validateCertKeyMatching
{
    my ( $self ) = @_;

    unless ( length $self->{'certificate_container_path'}
        && -f $self->{'certificate_container_path'}
    ) {
        error( 'Invalid SSL certificate path.', FALSE );
        return FALSE;
    }

    unless ( length $self->{'private_key_container_path'}
        && -f $self->{'private_key_container_path'}
    ) {
        error( 'Invalid SSL private key path.', FALSE );
        return FALSE;
    }

    my @cmd = (
        '/usr/bin/openssl',
        'pkey',
        '-in', escapeShell( $self->{'private_key_container_path'} ),
        '-pubout',
        '-outform', 'pem'
    );

    my $rs = execute( "@cmd", \my $pkey, \my $stderr );
    if ( $rs ) {
        debug( $stderr ) if length $stderr;
        error( "Couldn't export public key from SSL private key.", FALSE );
        return FALSE;
    }

    @cmd = (
        '/usr/bin/openssl',
        'x509',
        '-in', escapeShell( $self->{'certificate_container_path'} ),
        '-pubkey',
        '-noout',
        '-outform', 'pem'
    );
    $rs = execute( "@cmd", \my $x509, \$stderr );
    if ( $rs ) {
        debug( $stderr ) if length $stderr;
        error( "Couldn't export public key from SSL certificate.", FALSE );
        return FALSE;
    }

    return TRUE if $pkey eq $x509;

    error( "The SSL certificate private key doesn't match with the SSL certificate.", FALSE );
    FALSE;
}

=item validateCertificateChain( )

 Validate the certificate chain
 
 On error, an error message isset through iMSCP::Debug::error()

 Return bool TRUE if the certificate chain is valid, FALSE otherwise

=cut

sub validateCertificateChain
{
    my ( $self ) = @_;

    $self->validatePrivateKey()
        && $self->validateCertificate()
        && $self->validateCertKeyMatching();
}

=item importPrivateKey( )

 Import private key in certificate chain container

 On error, an error message isset through iMSCP::Debug::error()

 Return bool TRUE on success, FALSE on error

=cut

sub importPrivateKey
{
    my ( $self ) = @_;

    unless ( length $self->{'private_key_container_path'}
        && -f $self->{'private_key_container_path'}
    ) {
        error( 'Invalid SSL private key path.', FALSE );
        return FALSE;
    }

    my $passphraseFile;
    if ( length $self->{'private_key_passphrase'} ) {
        # Write SSL private key passphrase into temporary file which is only
        # readable by root user
        $passphraseFile = File::Temp->new();
        print $passphraseFile $self->{'private_key_passphrase'};
        $passphraseFile->close();
    }

    my $cmd = [
        '/usr/bin/openssl',
        'pkey',
        '-in', $self->{'private_key_container_path'},
        '-out', "$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem",
        ( defined $passphraseFile
            ? ( '-passin', 'file:' . $passphraseFile->filename ) : ()
        )
    ];

    my $rs = execute( $cmd, \my $stdout, \my $stderr );
    if ( $rs ) {
        debug( $stdout ) if length $stdout;
        debug( $stderr ) if length $stderr;
        error( "Couldn't import SSL private key in SSL certificate chain.", FALSE );
        return FALSE;
    }

    TRUE;
}

=item importCertificate( )

 Import certificate in certificate chain container

 On error, an error message isset through iMSCP::Debug::error()

 Return bool TRUE on success, FALSE on error

=cut

sub importCertificate
{
    my ( $self ) = @_;

    unless ( length $self->{'certificate_container_path'}
        && -f $self->{'certificate_container_path'}
    ) {
        error( 'Invalid SSL certificate path.', FALSE );
        return FALSE;
    }

    my $file = iMSCP::File->new(
        filename => $self->{'certificate_container_path'}
    );
    return FALSE unless defined( my $certificateRef = $file->getAsRef());

    ${ $certificateRef } =~ s/^(?:\015?\012)+|(?:\015?\012)+$//g;
    ${ $certificateRef } .= "\n";

    return FALSE if $file->save();

    my @cmd = (
        '/usr/bin/cat',
        escapeShell( $self->{'certificate_container_path'} ),
        '>>', escapeShell( "$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem" )
    );

    my $rs = execute( "@cmd", \my $stdout, \my $stderr );
    if ( $rs ) {
        debug( $stdout ) if length $stdout;
        debug( $stderr ) if length $stderr;
        error( "Couldn't import SSL certificate in SSL certificate chain.", FALSE );
        return FALSE;
    }

    TRUE;
}

=item importCaBundle( )

 Import the CA Bundle in certificate chain container if any

 On error, an error message isset through iMSCP::Debug::error()

 Return bool TRUE on success, FALSE on error

=cut

sub importCaBundle
{
    my ( $self ) = @_;

    return TRUE unless length $self->{'ca_bundle_container_path'};

    unless ( -f $self->{'certificate_container_path'} ) {
        error( 'Invalid SSL CA bundle.', FALSE );
        return FALSE;
    }

    my $file = iMSCP::File->new(
        filename => $self->{'ca_bundle_container_path'}
    );
    return FALSE unless defined( my $caBundleRef = $file->getAsRef());

    ${ $caBundleRef } =~ s/^(?:\015?\012)+|(?:\015?\012)+$//g;
    ${ $caBundleRef } .= "\n";

    return FALSE if $file->save();

    my @cmd = (
        '/usr/bin/cat',
        escapeShell( $self->{'ca_bundle_container_path'} ),
        '>>', escapeShell( "$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem" )
    );

    my $rs = execute( "@cmd", \my $stdout, \my $stderr );
    if ( $rs ) {
        debug( $stdout ) if length $stdout;
        debug( $stderr ) if length $stderr;
        error( "Couldn't import SSL CA bundle in SSL certificate chain.", FALSE );
        return FALSE;
    }

    TRUE;
}

=item createSelfSignedCertificate( \%data )

 Generate a self-signed SSL certificate
 
 On error, an error message isset through iMSCP::Debug::error()

 Param hash \%data Certificate data:
  - common_name: SSL certificate common name
  - email: SSL certificate email address
  - wildcard Flag indicating whether or not a wildcard SSL certificate must be
             generated
 Return bool TRUE on success, FALSE on error, die on failure

=cut

sub createSelfSignedCertificate
{
    my ( $self, $data ) = @_;

    ref $data eq 'HASH' or die( 'Wrong $data parameter. Hash expected' );
    $data->{'common_name'} or die( 'Missing common_name parameter' );
    $data->{'email'} or die( 'Missing email parameter' );

    my $openSSLConffileTpl = "$::imscpConfig{'CONF_DIR'}/openssl/openssl.cnf.tpl";
    my $commonName = $data->{'wildcard'}
        ? '*.' . $data->{'common_name'}
        : $data->{'common_name'};

    # Load openssl configuration template file for self-signed SSL certificates
    return FALSE unless defined(
        my $openSSLConffileTplContent = iMSCP::File->new(
            filename => $openSSLConffileTpl
        )->get()
    );

    # Write openssl configuration file into temporary file
    my $openSSLConffile = File::Temp->new();
    print $openSSLConffile process(
        {
            COMMON_NAME   => $commonName,
            EMAIL_ADDRESS => $data->{'email'},
            ALT_NAMES     => $data->{'wildcard'}
                ? "DNS.1 = $commonName\n"
                : "DNS.1 = $commonName\nDNS.2 = www.$commonName\n"
        },
        $openSSLConffileTplContent
    );
    $openSSLConffile->close();

    my $cmd = [
        'openssl',
        'req',
        '-x509',
        '-nodes',
        '-days', '365',
        '-config', $openSSLConffile->filename(),
        '-newkey', 'rsa',
        '-keyout', "$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem",
        '-out', "$self->{'certificate_chains_storage_dir'}/$self->{'certificate_chain_name'}.pem"
    ];

    my $rs = execute( $cmd, \my $stdout, \my $stderr );
    if ( $rs ) {
        debug( $stdout ) if length $stdout;
        debug( $stderr ) if length $stderr;
        error( "Couldn't generate self-signed SSL certificate.", FALSE );
        return FALSE;
    }

    TRUE;
}

=item createCertificateChain( )

 Create certificate chain (import private key, certificate and CA Bundle)

 On error, an error message isset through iMSCP::Debug::error()

 Return bool TRUE on succes, FALSE on error

=cut

sub createCertificateChain
{
    my ( $self ) = @_;

    $self->importPrivateKey() && $self->importCertificate()
        && $self->importCaBundle();
}

=item getCertificateExpiryTime( [ certificatePath = $self->{'certificate_container_path'} ] )

 Get SSL certificate expiry time
 
 On error, an error message isset through iMSCP::Debug::error()

 Param string certificatePath Path to SSL certificate
 Return timestamp on success, undef on error

=cut

sub getCertificateExpiryTime
{
    my ( $self, $certificatePath ) = @_;
    $certificatePath //= $self->{'certificate_container_path'};

    unless ( ref \$certificatePath eq 'SCALAR' && length $certificatePath ) {
        error( 'Invalid SSL certificate path provided.', FALSE );
        return undef;
    }

    my $rs = execute(
        [
            '/usr/bin/openssl',
            'x509',
            '-enddate',
            '-noout',
            '-in', $certificatePath
        ],
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;

    unless ( $rs == 0 && $stdout =~ /^notAfter=(.*)/i ) {
        error( sprintf(
            "Couldn't get SSL certificate expiry time: %s",
            $stderr || 'unknown error'
        ), FALSE );
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
    my ( $self ) = @_;

    # Full path to the certificate chains storage directory
    $self->{'certificate_chains_storage_dir'} //= '';
    # Certificate chain name
    $self->{'certificate_chain_name'} //= undef;
    # Full path to the private key container
    $self->{'private_key_container_path'} //= undef;
    # Private key passphrase if any
    $self->{'private_key_passphrase'} //= undef;
    # Full path to the SSL certificate container
    $self->{'certificate_container_path'} //= undef;
    # Full path to the CA Bundle container
    $self->{'ca_bundle_container_path'} //= undef;

    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
