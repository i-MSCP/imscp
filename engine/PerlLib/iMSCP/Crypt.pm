=head1 NAME

 iMSCP::Crypt - Library that provides functions for passwords hashing, verification and data encryption.

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

package iMSCP::Crypt;

use strict;
use warnings;
use Carp;
use Crypt::CBC;
use Crypt::Eksblowfish::Bcrypt ();
use Digest::SHA ();
use Digest::MD5 ();
use MIME::Base64;
use parent 'Exporter';

use constant ALNUM => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
use constant ALPHA64 => './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
use constant BASE64 => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

our @EXPORT_OK = qw/
    randomStr md5 sha256 sha512 bcrypt apr1MD5 htpasswd verify hashEqual encryptBlowfishCBC decryptBlowfishCBC
    encryptRijndaelCBC decryptRijndaelCBC
    /;

=head1 DESCRIPTION

 Library that provides functions for passwords hashing, verification and data encryption.

=head1 FUNCTIONS

=over 4

=item randomStr( $length [, $charList = BASE64 ] )

 Generates a secure random string

 Param int $length Expected string length
 Param bool string $charList character list to use for string generation (default is Base 64 character set)
 Return string, croak on failure

=cut

sub randomStr( $;$ )
{
    my ($length, $charList) = ( shift, shift // BASE64 );

    $length =~ /^[\d]+$/ or croak( 'Bad length parameter. Numeric value expected' );
    $length = int( $length );
    $length > 0 or croak( 'Length parameter value must be >= 1' );

    my $listLen = length $charList;
    if ( $listLen == 1 ) {
        return $charList x $length;
    }

    my @bytes = split //, Crypt::CBC->random_bytes( $length );
    my @charList = split //, $charList;
    my $pos = 0;
    my $str = '';
    for ( my $i = 0; $i < $length; $i++ ) {
        $pos = ( $pos+ord( $bytes[$i] ) ) % $listLen;
        $str .= $charList[$pos];
    }

    $str;
}

=item md5( $password [, $salt = randomStr ] )

 Create a hash of the given password using the MD5 algorithm

 Param string $password The password to be hashed
 Param string $salt An optional salt string to base the hashing on
 Returns string, croak on failure
 Deprecated As of 2012-6-7, this algorithm is "no longer considered safe" by its author. Use bcrypt instead.

=cut

sub md5( $;$ )
{
    my ($password, $salt) = @_;

    if ( defined $salt ) {
        length $salt >= 8 or croak( 'The salt length must be at least 8 bytes long' );
    } else {
        $salt = randomStr( 8 );
    }

    crypt( $password, '$1$' . $salt );
}

=item sha256( $password [, $rounds = 5000 [, $salt = randomStr ] ] )

 Create a hash of the given password using the SHA-256 algorithm

 Param string $password Password to be hashed
 Param int $rounds A numeric value used to indicate how many times the hashing loop should be executed
 Param string $salt An optional salt string to base the hashing on
 Returns string, croak on failure

=cut

sub sha256( $;$$ )
{
    my ($password, $rounds, $salt) = @_;

    $rounds //= 5000;
    $rounds =~ /^[\d]+$/ or croak( 'Bad rounds parameter. Numeric value expected.' );
    $rounds = int( $rounds );
    $rounds > 999 && $rounds < 5001 or croak( 'The rounds parameter must be in range 1000-5000' );
    $rounds = sprintf( '%1$04d', $rounds );

    if ( defined $salt ) {
        length $salt >= 16 or croak( 'The salt length must be at least 16 bytes long' );
    } else {
        $salt = randomStr( 16 );
    }

    crypt( $password, '$5$rounds=' . $rounds . '$' . $salt );
}

=item sha512( $password [, $rounds = 5000 [, $salt = randomStr ] ] )

 Create a hash of the given password using the SHA-512 algorithm

 Param string $password Password to be hashed
 Param int $rounds A numeric value used to indicate how many times the hashing loop should be executed
 Param string $salt An optional salt string to base the hashing on
 Returns string, croak on failure

=cut

sub sha512($;$$)
{
    my ($password, $rounds, $salt) = @_;

    $rounds //= 5000;
    $rounds =~ /^[\d]+$/ or croak( 'Bad rounds parameter. Numeric value expected.' );
    $rounds = int( $rounds );
    $rounds > 999 && $rounds < 5001 or croak( 'The rounds parameter must be in range 1000-5000' );
    $rounds = sprintf( '%1$04d', $rounds );

    if ( defined $salt ) {
        length $salt >= 16 or croak( 'The salt length must be at least 16 bytes long' );
    } else {
        $salt = randomStr( 16 );
    }

    crypt( $password, '$6$rounds=' . $rounds . '$' . $salt );
}

=item bcrypt($password [, $cost = 10 [, $salt = randomStr ] ])

 Create a hash of the given password using the bcrypt algorithm

 Param string $password Password to be hashed
 Param int $cost Base-2 logarithm of the iteration count
 Param string $salt An optional salt string to base the hashing on
 Returns string, croak on failure
=cut

sub bcrypt($;$$)
{
    my ($password, $cost, $salt) = @_;

    $cost //= 10;
    $cost =~ /^[\d]+$/ or croak( 'Bad cost parameter. Numeric value expected.' );
    $cost = int( $cost );
    $cost > 3 && $cost < 32 or croak( 'The cost parameter must be in range 04-31' );
    $cost = sprintf( '%1$02d', $cost );

    if ( defined $salt ) {
        length $salt >= 16 or croak( 'The salt length must be at least 16 bytes long' );
    } else {
        $salt = randomStr( 16 );
    }

    # FIXME Add support for new $2y$ prefix by re-implementing bcrypt
    Crypt::Eksblowfish::Bcrypt::bcrypt( $password,
        '$2a$' . $cost . '$' . Crypt::Eksblowfish::Bcrypt::en_base64( $salt ));
}

=item apr1MD5( $password [, $salt = randomStr(8, ALPHA64) ] )

 APR1 MD5 algorithm (see http://svn.apache.org/viewvc/apr/apr/trunk/crypto/apr_md5.c?view=markup)

 Param string $password The password to be hashed
 Param string $salt Salt An optional salt string to base the hashing on
 Return string

=cut

sub apr1MD5( $;$ )
{
    my ($password, $salt) = @_;

    if ( $salt ) {
        length $salt == 8 or croak( 'The salt length for md5 (APR1) algorithm must be 8 bytes long' );
        my $regexp = qr/[^${\( ALPHA64 )}]/;
        $salt !~ /$regexp/ or croak( 'The salt must be a string in the alphabet "./0-9A-Za-z"' );
    } else {
        $salt = randomStr( 8, ALPHA64 );
    }

    my $len = length $password;
    my $context = $password . '$apr1$' . $salt;
    my $bin = pack( 'H32', Digest::MD5::md5_hex( $password . $salt . $password ));

    for ( my $i = $len; $i > 0; $i -= 16 ) {
        $context .= substr( $bin, 0, ( 16, $i )[16 > $i] );
    }

    my @password = split //, $password;
    for ( my $i = $len; $i > 0; $i >>= 1 ) {
        $context .= ( $i & 1 ) ? chr( 0 ) : $password[0];
    }

    $bin = pack( 'H32', Digest::MD5::md5_hex( $context ));

    for ( my $i = 0; $i < 1000; $i++ ) {
        my $new = ( $i & 1 ) ? $password : $bin;
        $new .= $salt if $i % 3;
        $new .= $password if $i % 7;
        $new .= ( $i & 1 ) ? $bin : $password;
        $bin = pack( 'H32', Digest::MD5::md5_hex( $new ));
    }

    my @bin = split //, $bin;
    my $tmp = '';
    for ( my $i = 0; $i < 5; $i++ ) {
        my $k = $i+6;
        my $j = $i+12;
        $j = 5 if $j == 16;
        $tmp = $bin[$i] . $bin[$k] . $bin[$j] . $tmp;
    }

    '$apr1$' . $salt . '$' . _toAlphabet64( chr( 0 ) . chr( 0 ) . $bin[11] . $tmp );
}

=item htpasswd( $password [, $cost = 10 [, $salt = randomStr [, $format = 'md5' ] ] ] )

 Create an htpasswd password hash of the given password using the given algorithm

 See http://httpd.apache.org/docs/2.4/misc/password_encryptions.html

 Param string $password The password to be hashed
 Param int $cost Base-2 logarithm of the iteration count (only relevant for bcrypt format)
 Param string $salt An optional salt string to base the hashing on (only relevant for bcrypt, crypt and md5 formats)
 Param string $format Format in which the password must be hashed (bcrypt|crypt|sha1|md5) -  Default is md5 (APR1)
 Return string, croak on failure

=cut

sub htpasswd( $;$$ )
{
    my ($password, $cost, $salt, $format) = @_;
    $format //= 'md5';

    if ( $format eq 'bcrypt' ) {
        return bcrypt( $password, $cost, $salt );
    }

    if ( $format eq 'crypt' ) {
        if ( $salt ) {
            length $salt == 2 or croak( 'The salt length must be 2 bytes long' );
            my $regexp = qr/[^${\( ALPHA64 )}]/;
            $salt !~ /$regexp/ or croak( 'The salt must be a string in the alphabet "./0-9A-Za-z"' );
        } else {
            $salt = randomStr( 2, ALPHA64 );
        }

        return crypt( $password, $salt );
    }

    if ( $format eq 'sha1' ) {
        return '{SHA}' . encode_base64( Digest::SHA::sha1( $password ), '' );
    }

    if ( $format eq 'md5' ) {
        return apr1MD5( $password, $salt );
    }

    croak(
        sprintf( 'The %s format is not valid. The supported formats are: %s', $format, 'bcrypt, crypt, md5, sha1' )
    );
}

=item verify( $password, $hash )

 Verify the given password against the given hash

 Param string $password The password to be checked
 Param string $hash The hash to be checked against
 Return bool, croak on failure

=cut

sub verify( $$ )
{
    my ($password, $hash) = @_;

    if ( substr( $hash, 0, 5 ) eq '{SHA}' ) { # htpasswd sha1 hashed password
        return hashEqual( $hash, '{SHA}' . encode_base64( Digest::SHA::sha1( $password ), '' ));
    }

    if ( substr( $hash, 0, 6 ) eq '$apr1$' ) {
        # htpasswd md5 (APR1) hashed password
        my @token = split /\$/, $hash;
        $token[2] or croak( 'APR1 password format is not valid' );
        return hashEqual( $hash, apr1MD5( $password, $token[2] ));
    }

    if ( substr( $hash, 0, 4 ) eq '$2a$' ) { # bcrypt hashed password
        return hashEqual( $hash, Crypt::Eksblowfish::Bcrypt::bcrypt( $password, $hash ));
    }

    hashEqual( $hash, crypt( $password, $hash ));
}

=item hashEqual( $knownString, $userString )

 Timing attack safe string comparison

 Param string $knownString The string of known length to compare against
 Param string $userString The user-supplied string
 Return bool

=cut

sub hashEqual( $$ )
{
    my ($knownString, $userString) = @_;

    return unless defined $userString;

    my $lenExpected = length $knownString;
    my $lenActual = length $userString;
    my $len = ( $lenExpected, $lenActual )[$lenExpected > $lenActual];
    my $result = 0;
    my @knownString = split //, $knownString;
    my @userString = split //, $userString;

    for ( my $i = 0; $i < $len; $i++ ) {
        $result |= ord( $knownString[$i] ) ^ ord( $userString[$i] );
    }

    $result |= $lenExpected ^ $lenActual;
    $result == 0;
}

=item encryptBlowfishCBC( $key, $iv, $data )

 Encrypt the given data using the Blowfish algorithm (Cipher) in CBC mode

 Param string $key Encryption key (4 up to 56 bytes long (32 up to 448 bits))
 Param string $iv Initialization vector (8 bytes long (64 bits))
 Param string $data Data to encrypt
 Return string A base64 encoded string representing encrypted data, croak on failure

=cut

sub encryptBlowfishCBC( $$$ )
{
    _encryptCBC( 'Crypt::Blowfish', @_ );
}

=item decryptBlowfishCBC( $key, $iv, $data )

 Decrypt the given data using the Blowfish algorithm (Cipher) in CBC mode

 Note: PKCS#5/PKCS#7 padding is assumed.

 Param string $key Decryption key (4 up to 56 bytes long (32 up to 448 bits))
 Param string $iv Initialization vector (8 bytes long (64 bits))
 Param string $data A base64 encoded string representing encrypted data
 Return string, croak on failure

=cut

sub decryptBlowfishCBC( $$$ )
{
    _decryptCBC( 'Crypt::Blowfish', @_ );
}

=item encryptRijndaelCBC( $key, $iv, $data )

 Encrypt the given data using the AES (Rijndael) algorithm (Cipher) in CBC mode

 Note: PKCS#5/PKCS#7 padding is assumed.

 Param string $key Encryption key (16, 24, 32 or bytes long (128, 192 or 256 bits))
 Param string $iv Initialization vector (16 bytes long (128 bits))
 Param string $data Data to encrypt
 Return A string base64 encoded string representing encrypted data, croak on failure

=cut

sub encryptRijndaelCBC( $$$ )
{
    _encryptCBC( 'Crypt::Rijndael', @_ );
}

=item decryptRijndaelCBC( $key, $iv, $data )

 Decrypt the given data using the AES (Rijndael) algorithm (Cipher) in CBC mode

 Note: PKCS#5/PKCS#7 padding is assumed.

 Param string $key Decryption key (16, 24, 32 or bytes long (128, 192 or 256 bits))
 Param string $iv Initialization vector (16 bytes long (128 bits))
 Param string $data A base64 encoded string representing encrypted data
 Return string, croak on failure

=cut

sub decryptRijndaelCBC( $$$ )
{
    _decryptCBC( 'Crypt::Rijndael', @_ );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _encryptCBC( $algorithm, $key, $iv, $data )

 Encrypt the given data using the given algorithm (Cipher) in CBC mode

 Note: PKCS#5/PKCS#7 padding is assumed.

 Param string $algorithm Algorithm
 Param string $key Encryption key
 Param string $iv Initialization vector
 Param string $data Data to encrypt
 Return string A base64 encoded string representing encrypted data, croak on failure

=cut

sub _encryptCBC( $$$$ )
{
    my ($algorithm, $key, $iv, $data) = @_;

    encode_base64(
        Crypt::CBC->new(
            -cipher      => $algorithm,
            -key         => $key,
            -keysize     => length $key,
            -blocksize   => length $iv,
            -literal_key => 1,
            -iv          => $iv,
            -header      => 'none',
            -padding     => 'standard'
        )->encrypt( $data ),
        ''
    );
}

=item _decryptCBC( $algorithm, $key, $iv, $data )

 Decrypt the given data using the given algorithm (Cipher) in CBC mode

 Note: PKCS#5/PKCS#7 padding is assumed.

 Param string $algorithm Algorithm
 Param string $key Decryption key
 Param string $iv Initialization vector
 Param string $data A base64 encoded string representing encrypted data
 Return string, croak on failure

=cut

sub _decryptCBC( $$$$ )
{
    my ($algorithm, $key, $iv, $data) = @_;

    Crypt::CBC->new(
        -cipher      => $algorithm,
        -key         => $key,
        -keysize     => length $key,
        -blocksize   => length $iv,
        -literal_key => 1,
        -iv          => $iv,
        -header      => 'none',
        -padding     => 'standard'
    )->decrypt(
        decode_base64( $data )
    );
}

=item _toAlphabet64( $string )

 Convert a binary string using the "./0-9A-Za-z" alphabet

 Param string $string String to be converted
 Return string

=cut

sub _toAlphabet64( $ )
{
    my $string = shift;

    $string = reverse( substr( encode_base64( $string, '' ), 2 ));
    eval "\$string =~ tr#${\(BASE64)}#${\(ALPHA64)}#";
    $string;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
