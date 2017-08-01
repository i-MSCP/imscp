=head1 NAME

iMSCP::Dialog::InputValidation

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

package iMSCP::Dialog::InputValidation;

use strict;
use warnings;
use Data::Validate::Domain qw/ is_domain is_hostname /;
use DateTime::TimeZone;
use Email::Valid;
use iMSCP::Database;
use iMSCP::Net;
use Net::LibIDN qw/ idn_to_ascii /;
use parent 'Exporter';

our @EXPORT = qw/isValidUsername isValidPassword isValidEmail isValidHostname isValidDomain isValidIpAddr
isValidTimezone isValidDbName isNumber isNumberInRange isStringNotInList isValidNumberRange isNotEmpty
isAvailableSqlUser/;

our $lastValidationError = '';

=head1 DESCRIPTION

 Provides set of routines for ease of user inputs validation.

=head1 PUBLIC METHODS

=over 4

=item isValidUsername( $username )

 Is the given username valid?

 Param string $username Username
 Return bool TRUE if the given username is valid, FALSE otherwise

=cut

sub isValidUsername( $ )
{
    my ($username) = @_;

    defined $username or die( 'Missing $username parameter' );
    my $length = length $username;

    return 1 if $length >= 3 && $length <= 16 && $username =~ /^[\x30-\x39\x41-\x5a\x61-\x7a\x5f]+$/;

    $lastValidationError = <<"EOF";


\\Z1Invalid or unauthorized username.\\Zn

 - Username must be between 3 and 16 characters long.
 - Only ASCII alphabet, number and underscore characters are allowed.

Please try again:
EOF

    0;
}

=item isValidPassword( $password )

 Is the given password valid?
 
 Param string $password Password
 Return bool TRUE if the given password is valid, FALSE otherwise

=cut

sub isValidPassword( $ )
{
    my ($password) = @_;

    defined $password or die( 'Missing $password parameter' );
    my $length = length $password;

    return 1 if $length >= 6 && $length <= 32 && $password =~ /^[\x30-\x39\x41-\x5a\x61-\x7a]+$/;

    $lastValidationError = <<"EOF";


\\Z1Invalid password.\\Zn

 - Password must be between 6 and 32 characters long.
 - Only ASCII alphabet and number characters are allowed.

Please try again:
EOF

    0;
}

=item isValidEmail( $email )

 Is the given email valid?

 Param string $email Email
 Return bool TRUE if the given email is valid, FALSE otherwise

=cut

sub isValidEmail( $ )
{
    my ($email) = @_;

    defined $email or die( 'Missing $email parameter' );

    return 1 if Email::Valid->address( $email );

    $lastValidationError = <<"EOF";


\\Z1Invalid email address.\\Zn

Please try again:
EOF

    0;
}

=item isValidHostname( $hostname )

 Is the given hostname valid?
 
 Param string $hostname Hostname
 Return bool TRUE if the given hostname is valid, FALSE otherwise

=cut

sub isValidHostname( $ )
{
    my ($hostname) = @_;

    defined $hostname or die( 'Missing $hostname parameter' );

    return 1 if $hostname !~ /\.$/ && ( $hostname =~ tr/.// ) >= 2 && is_hostname( idn_to_ascii( $hostname, 'utf-8' ));

    $lastValidationError = <<"EOF";


\\Z1Invalid hostname.\\Zn

 - Hostname must comply to RFC 1123 and 5890
 - The hostname must be a fully qualified hostname (FQHN).

Please try again:
EOF

    0;
}

=item isValidDomain( $domainName )

 Is the given domain name valid?

 Param string $domain Domain name
 Return bool TRUE if the given domain name is valid, FALSE otherwise

=cut

sub isValidDomain( $ )
{
    my ($domainName) = @_;

    defined $domainName or die( 'Missing $domainName parameter' );

    return 1 if $domainName !~ /\.$/ && is_domain(
        idn_to_ascii( $domainName, 'utf-8' ),
        {
            domain_disable_tld_validation => 1
        }
    );

    $lastValidationError = <<"EOF";


\\Z1Invalid domain name.\\Zn

 - Domain name must comply to RFC 1123 and 5890

Please try again:
EOF

    0;
}

=item isValidIpAddr( $ipAddr [, $typeReg = ANY ] )

 Is the given IP address valid?

 Param string $ipAddr IP address
 Param regexp|undef typeReg Regexp defining allowed IP type
 Return bool TRUE if the given IP address is valid, FALSE otherwise

=cut

sub isValidIpAddr( $;$ )
{
    my ($ipAddr, $typeReg) = @_;

    defined $ipAddr or die( 'Missing $ipAddr parameter' );

    my $net = iMSCP::Net->getInstance();
    return 1 if $net->isValidAddr( $ipAddr ) && ( !defined $typeReg || $net->getAddrType( $ipAddr ) =~ /^$typeReg$/ );

    $lastValidationError = <<"EOF";


\\Z1Invalid or unauthorized IP address.\\Zn

Please try again:
EOF

    0;
}

=item isValidDbName( $dbName )

 Is the given database name valid?

 Param string $email Email
 Return bool TRUE if the given email is valid, FALSE otherwise

=cut

sub isValidDbName($)
{
    my ($dbName) = @_;

    defined $dbName or die( 'Missing $dbName parameter' );
    my $length = length $dbName;

    return 1 if $length >= 3 && $length <= 16 && $dbName =~ /^[\x30-\x39\x41-\x5a\x61-\x7a\x5f]+$/;

    $lastValidationError = <<"EOF";


\\Z1Invalid or unauthorized database name.\\Zn

 - Database name must be between 3 and 16 characters long.
 - Only ASCII alphabet, number and underscore characters are allowed.

Please try again:
EOF

    0;
}

=item isValidTimezone( $timezone )

 Is the given timzone name valid?

 Param string timezone Timezone
 Return bool TRUE if the given timezone is valid, FALSE otherwise

=cut

sub isValidTimezone
{
    my ($timezone) = @_;

    defined $timezone or die( 'Missing $timezone parameter' );

    return 1 if DateTime::TimeZone->is_valid_name( $timezone );

    $lastValidationError = <<"EOF";


\\Z1Invalid timezone.\\Zn

 - Consult http://php.net/manual/en/timezones.php for a list of valid timezones.

Please try again:
EOF

    0;
}

=item isNumber( $number )

 Is the given number valid?

 Param int $number Number
 Return bool TRUE if the given number is valid, FALSE otherwise

=cut

sub isNumber($)
{
    my ($number) = @_;

    defined $number or die( 'Missing $timezone parameter' );

    return 1 if $number =~ /^[\x30-\x39]+$/;

    $lastValidationError = <<"EOF";


\\Z1Invalid number.\\Zn

Please try again:
EOF

    0;
}

=item isValidNumberRange( $numberRange, \$n1, \$n2 )

 Is the given number range a valid number range?

 Param string $numberRange Number range
 Param scalarref \$n1 First number in range
 Param scalarref \$n2 Last number in range
 Return bool TRUE if the given number range is valid, FALSE otherwise

=cut

sub isValidNumberRange( $$$ )
{
    my ($numberRange, $n1, $n2) = @_;

    defined $numberRange or die( 'Missing $numberRange parameter' );
    defined $n1 or die( 'Missing $n1 parameter' );
    defined $n2 or die( 'Missing $n2 parameter' );

    return 1 if ( ${$n1}, ${$n2} ) = $numberRange =~ /^([\x30-\x39]+)\s+([\x30-\x39]+)$/;

    $lastValidationError = <<"EOF";


\\Z1Invalid number range.\\Zn

- Number range must be two numbers separated by a space.

Please try again:
EOF

    0;
}

=item isNumberInRange( $number, $start, $end )

 Is the given number in the given range?

 Param int $number Number
 Param int $start Start of range
 Param int $end End of range
 Return bool TRUE if the given number is under the given range, FALSE otherwise

=cut

sub isNumberInRange( $$$ )
{
    my ($number, $start, $end) = @_;

    defined $number or die( 'Missing $number parameter' );
    defined $start or die( 'Missing $start parameter' );
    defined $end or die( 'Missing $end parameter' );

    no warnings;
    return 1 if defined $number && $number >= $start && $number <= $end;

    $lastValidationError = <<"EOF";


\\Z1Invalid number.\\Zn

 - Number $number must be in range from $start to $end.

Please try again:
EOF

    0;
}

=item isStringNotInList( $string, @stringList )

 Is the given string not in the given string list?

 Note: Comparison is case-insensitive

 Param string string String
 Param list @stringList String list
 Return bool TRUE if the given string is the given list, FALSE otherwise

=cut

sub isStringNotInList( $@ )
{
    my ($string, @stringList) = @_;

    defined $string or die( 'Missing $string parameter' );

    return 1 unless grep { lc $string eq lc $_ } @stringList;

    my $entries = join ', ', @stringList;
    $lastValidationError = <<"EOF";


\\Z1Invalid entry.\\Zn

 - Following entries are not allowed: $entries

Please try again:
EOF

    0;
}

=item isNotEmpty( $string )

 Is the given string not an empty string?

 Param string $string String
 Return bool TRUE if the given string is not empty, FALSE otherwise

=cut

sub isNotEmpty( $ )
{
    my ($string) = @_;

    defined $string or die( 'Missing $string parameter' );

    return 1 if !$string || $string =~ /[^\s]/;

    $lastValidationError = <<"EOF";


\\Z1Entry cannot be empty.\\Zn

Please try again:
EOF

    0;
}

=item isAvailableSqlUser( $username )

 Is the given SQL user available?

 This routine make sure that the given SQL user is not already used by a customer.

 Param string $username SQL username
 Return bool TRUE if the given SQL user is available, FALSE otherwise

=cut

sub isAvailableSqlUser ( $ )
{
    my ($username) = @_;

    defined $username or die( 'Missing $username parameter' );

    my $db = iMSCP::Database->factory();

    local $@;
    my $oldDbName = eval { $db->useDatabase( main::setupGetQuestion( 'DATABASE_NAME' )); };
    if ( $@ ) {
        return 1 if $@ =~ /unknown database/i; # On fresh installation, there is no database yet
        die;
    }

    my $dbh = $db->getRawDb();
    $dbh->{'RaiseError'} = 1;
    my $row = $dbh->selectrow_hashref( 'SELECT 1 FROM sql_user WHERE sqlu_name = ? LIMIT 1', undef, $username );

    $db->useDatabase( $oldDbName ) if $oldDbName;

    return 1 unless $row;

    $lastValidationError = <<"EOF";


\\Z1Invalid SQL username.\\Zn

 - The given SQL user is already used by one of your customers.

Please try again:
EOF

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
