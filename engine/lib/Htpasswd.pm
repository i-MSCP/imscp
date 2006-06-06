package Apache::Htpasswd;

use vars qw(@ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $VERSION);
use warnings;
use strict;    # Restrict unsafe variables, references, barewords
use Carp;

use POSIX qw ( SEEK_SET SEEK_END );
use Fcntl qw ( LOCK_EX LOCK_UN );

@ISA = qw(Exporter);

@EXPORT = qw();

@EXPORT_OK =
  qw(htpasswd htDelete fetchPass fetchInfo writeInfo htCheckPassword error Version);

%EXPORT_TAGS = ( all => [@EXPORT_OK] );

$VERSION = '1.8';

sub Version {
    return $VERSION;
}

#-----------------------------------------------------------#
# Public Methods
#-----------------------------------------------------------#

sub new {
    my $proto = shift;
    my $args  = shift;
    my $passwdFile;

    if ( ref $args eq 'HASH' ) {
        $passwdFile = $args->{'passwdFile'};
    }
    else {
        $passwdFile = $args;
    }

    my $class = ref($proto) || $proto;
    my ($self) = {};
    bless( $self, $class );

    $self->{'PASSWD'}   = $passwdFile;
    $self->{'ERROR'}    = "";
    $self->{'LOCK'}     = 0;
    $self->{'OPEN'}     = 0;
    $self->{'READONLY'} = $args->{'ReadOnly'} if ref $args eq 'HASH';
    $self->{'USEMD5'} = $args->{'UseMD5'} if ref $args eq 'HASH';
    $self->{'USEPLAIN'} = $args->{'UsePlain'} if ref $args eq 'HASH';

    return $self;
}

#-----------------------------------------------------------#

sub error {
    my $self = shift;
    return $self->{'ERROR'};
}

#-----------------------------------------------------------#

sub htCheckPassword {
    my $self = shift;
    my $Id   = shift;
    my $pass = shift;
    my $MD5Magic = '$apr1$';
    my $SHA1Magic = '{SHA}';

    my $cryptPass = $self->fetchPass($Id);
    if ( !$cryptPass ) { return undef; }

    if (index($cryptPass, $MD5Magic) == 0) {
        # This is an MD5 password
        require Crypt::PasswdMD5;
        my $salt = $cryptPass;
        $salt =~ s/^\Q$MD5Magic//;      # Take care of the magic string if present
        $salt =~ s/^(.*)\$/$1/;         # Salt can have up to 8 chars...
        $salt = substr( $salt, 0, 8 );  # That means no more than 8 chars too.
        return 1 if Crypt::PasswdMD5::apache_md5_crypt( $pass, $salt ) eq $cryptPass;
    }
    elsif (index($cryptPass, $SHA1Magic) == 0) {
        # This is an SHA1 password
        require Digest::SHA1;
        require MIME::Base64;
        return 1 if '{SHA}'.MIME::Base64::encode_base64( Digest::SHA1::sha1( $pass ), '' ) eq $cryptPass;
    }

    # See if it is encrypted using crypt
    return 1 if crypt($pass, $cryptPass) eq $cryptPass;

    # See if it is a plain, unencrypted password
    return 1 if $self->{USEPLAIN} && $pass eq $cryptPass;
    
    $self->{'ERROR'} =
        __PACKAGE__ . "::htCheckPassword - Passwords do not match.";
    carp $self->error() unless caller ne $self;
    return 0;
}

#-----------------------------------------------------------#

sub htpasswd {
    my $self    = shift;
    my $Id      = shift;
    my $newPass = shift;
    my $oldPass = shift;
    my $noOld = 0;

    if ( $self->{READONLY} ) {
        $self->{'ERROR'} =
          __PACKAGE__ . "::htpasswd - Can't change passwords in ReadOnly mode";
        carp $self->error();
        return undef;
    }

    if ( !defined($oldPass) ) { 
			$noOld = 1; 
		}

    if ( defined($oldPass) && ref $oldPass eq 'HASH' ) {
        if ($oldPass->{'overwrite'}) {
            $newPass = $Id unless $newPass;
            my $newEncrypted = $self->CryptPasswd($newPass);
            return $self->writePassword( $Id, $newEncrypted );
        }
    }

    # New Entry
    if ($noOld) {
        my $passwdFile = $self->{'PASSWD'};

        # Encrypt new password string

        my $passwordCrypted = $self->CryptPasswd($newPass);

        $self->_open();

        if ( $self->fetchPass($Id) ) {

            # User already has a password in the file.
            $self->{'ERROR'} =
              __PACKAGE__ . "::htpasswd - $Id already exists in $passwdFile";
            carp $self->error();
            $self->_close();
            return undef;
        }
        else {

            # If we can add the user.
            seek( FH, 0, SEEK_END );
            print FH "$Id\:$passwordCrypted\n";

            $self->_close();
            return 1;
        }

        $self->_close();

    }
    else {
        $self->_open();

        my $exists = $self->htCheckPassword( $Id, $oldPass );

        if ($exists) {
            my ($newCrypted) = $self->CryptPasswd($newPass);
            return $self->writePassword( $Id, $newCrypted );
        }
        else {

            # ERROR returned from htCheckPass
            $self->{'ERROR'} =
              __PACKAGE__ . "::htpasswd - Password not changed.";
            carp $self->error();
            return undef;
        }

        $self->_close();
    }
}    # end htpasswd

#-----------------------------------------------------------#

sub htDelete {
    my $self       = shift;
    my $Id         = shift;
    my $passwdFile = $self->{'PASSWD'};
    my @cache;
    my $return;

    # Loop through the file, building a cache of exising records
    # which don't match the Id.

    $self->_open();

    seek( FH, 0, SEEK_SET );
    while (<FH>) {

        if (/^$Id\:/) {
            $return = 1;
        }
        else {
            push ( @cache, $_ );
        }
    }

    # Write out the @cache if needed.

    if ($return) {

        # Return to beginning of file
        seek( FH, 0, SEEK_SET );

        while (@cache) {
            print FH shift (@cache);
        }

        # Cut everything beyond current position
        truncate( FH, tell(FH) );

    }
    else {
        $self->{'ERROR'} =
          __PACKAGE__ . "::htDelete - User $Id not found in $passwdFile: $!";
        carp $self->error();
    }

    $self->_close();

    return $return;
}

#-----------------------------------------------------------#

sub fetchPass {
    my $self       = shift;
    my $Id         = shift;
    my $passwdFile = $self->{'PASSWD'};

    my $passwd = 0;

    $self->_open();

    while (<FH>) {
        chop;
        my @tmp = split ( /:/, $_, 3 );
        if ( $tmp[0] eq $Id ) {
            $passwd = $tmp[1];
            last;
        }
    }

    $self->_close();

    return $passwd;
}

#-----------------------------------------------------------#

sub writePassword {
    my $self    = shift;
    my $Id      = shift;
    my $newPass = shift;

    my $passwdFile = $self->{'PASSWD'};
    my @cache;
    my $return;

    $self->_open();
    seek( FH, 0, SEEK_SET );

    while (<FH>) {

        my @tmp = split ( /:/, $_, 3 );
        if ( $tmp[0] eq $Id ) {
            my $info = $tmp[2] ? $tmp[2] : "";
            chomp $info;
            push ( @cache, "$Id\:$newPass\:$info\n" );
            $return = 1;

        }
        else {
            push ( @cache, $_ );
        }
    }

    # Write out the @cache, if needed.

    if ($return) {

        # Return to beginning of file
        seek( FH, 0, SEEK_SET );

        while (@cache) {
            print FH shift (@cache);
        }

        # Cut everything beyond current position
        truncate( FH, tell(FH) );

    }
    else {
        $self->{'ERROR'} = __PACKAGE__
          . "::writePassword - User $Id not found in $passwdFile: $!";
        carp $self->error() . "\n";
    }

    $self->_close();

    return $return;
}

#-----------------------------------------------------------#

sub fetchInfo {
    my $self       = shift;
    my $Id         = shift;
    my $passwdFile = $self->{'PASSWD'};

    my $info = 0;

    $self->_open();

    while (<FH>) {
        chop;
        my @tmp = split ( /:/, $_, 3 );
        if ( $tmp[0] eq $Id ) {
            $info = $tmp[2];
            last;
        }
    }

    $self->_close();

    return $info;
}

#-----------------------------------------------------------#

sub fetchUsers {
    my $self       = shift;
    my $passwdFile = $self->{'PASSWD'};
    my $count      = 0;
    my @users;

    $self->_open();

    while (<FH>) {
        chop;
        my @tmp = split ( /:/, $_, 3 );
        push ( @users, $tmp[0] ) unless !$tmp[0];
    }

    $self->_close();

    return wantarray() ? @users : scalar @users;
}

#-----------------------------------------------------------#

sub writeInfo {
    my $self    = shift;
    my $Id      = shift;
    my $newInfo = shift;

    my ($passwdFile) = $self->{'PASSWD'};
    my (@cache);

    my ($return);

    $self->_open();
    seek( FH, 0, SEEK_SET );

    while (<FH>) {

        my @tmp = split ( /:/, $_, 3 );

        if ( $tmp[0] eq $Id ) {
            chomp $tmp[1] if ( @tmp == 2 );   # Cut out EOL if there was no info
            push ( @cache, "$Id\:$tmp[1]\:$newInfo\n" );
            $return = 1;

        }
        else {
            push ( @cache, $_ );
        }
    }

    # Write out the @cache, if needed.

    if ($return) {

        # Return to beginning of file
        seek( FH, 0, SEEK_SET );

        while (@cache) {
            print FH shift (@cache);
        }

        # Cut everything beyond current position
        truncate( FH, tell(FH) );

    }
    else {
        $self->{'ERROR'} =
          __PACKAGE__ . "::writeInfo - User $Id not found in $passwdFile: $!";
        carp $self->error() . "\n";
    }

    $self->_close();

    return $return;
}

#-----------------------------------------------------------#

sub CryptPasswd {
    my $self   = shift;
    my $passwd = shift;
    my $salt   = shift;
    my @chars  = ( '.', '/', 0 .. 9, 'A' .. 'Z', 'a' .. 'z' );
    my $Magic = '$apr1$';    # Apache specific Magic chars
    my $cryptType = (  $^O =~ /^MSWin/i || $self->{'USEMD5'} ) ? "MD5" : "crypt";

    if ( $salt && $cryptType =~ /MD5/i && $salt =~ /^\Q$Magic/ ) {

        # Borrowed from Crypt::PasswdMD5
        $salt =~ s/^\Q$Magic//;       # Take care of the magic string if present
        $salt =~ s/^(.*)\$.*$/$1/;    # Salt can have up to 8 chars...
        $salt = substr( $salt, 0, 8 );    # That means no more than 8 chars too.
                                          # For old crypt only
    }
    elsif ( $salt && $cryptType =~ /crypt/i ) {
        if ($salt =~ /\$2a\$\d+\$(.{23})/) {
            $salt = $1;
        } else {
            # Make sure only use 2 chars
            $salt = substr( $salt, 0, 2 );
        }  
    }
    else {

# If we use MD5, create apache MD5 with 8 char salt: 3 randoms, 5 dots
        if ( $cryptType =~ /MD5/i ) {
            $salt =
              join ( '', map { $chars[ int rand @chars ] } ( 0 .. 2 ) )
              . "." x 5;

            # Otherwise fallback to standard archaic crypt
        }
        else {
            $salt = join ( '', map { $chars[ int rand @chars ] } ( 0 .. 1 ) );
        }
    }

    if ( $cryptType =~ /MD5/i ) {
				require Crypt::PasswdMD5;
        return Crypt::PasswdMD5::apache_md5_crypt( $passwd, $salt );
    }
    else {
        return crypt( $passwd, $salt );
    }
}

#-----------------------------------------------------------#

sub DESTROY { close(FH); };

#-----------------------------------------------------------#

sub _lock {
    my $self = shift;

    # Lock if we don't have the lock
    flock( FH, LOCK_EX ) if ( $self->{'LOCK'} == 0 );

    # We have the lock
    $self->{'LOCK'} = 1;

    # Seek to head
    seek( FH, 0, SEEK_SET );
}

#-----------------------------------------------------------#

sub _unlock {
    my $self = shift;

    flock( FH, LOCK_UN );

    $self->{'LOCK'} = 0;
}

#-----------------------------------------------------------#

sub _open {
    my $self = shift;

    if ( $self->{'OPEN'} > 0 ) {
        $self->{'OPEN'}++;
        $self->_lock();
        return;
    }

    my $passwdFile = $self->{'PASSWD'};

    if ( $self->{READONLY} ) {
        if ( !open( FH, $passwdFile ) ) {
            $self->{'ERROR'} =
              __PACKAGE__ . "::fetchPass - Cannot open $passwdFile: $!";
            croak $self->error();
        }
    }
    else {
        if ( !open( FH, "+<$passwdFile" ) ) {
            $self->{'ERROR'} =
              __PACKAGE__ . "::fetchPass - Cannot open $passwdFile: $!";
            croak $self->error();
        }
    }

    binmode(FH);
    $self->{'OPEN'}++;
    $self->_lock() unless $self->{READONLY};    # No lock on r/o
}

#-----------------------------------------------------------#

sub _close {
    my $self = shift;
    $self->_unlock() unless $self->{READONLY};

    $self->{'OPEN'}--;

    if ( $self->{'OPEN'} > 0 ) { return; }

    if ( !close(FH) ) {
        my $passwdFile = $self->{'PASSWD'};
        $self->{'ERROR'} =
          __PACKAGE__ . "::htDelete - Cannot close $passwdFile: $!";
        carp $self->error();
        return undef;
    }
}

#-----------------------------------------------------------#

1;

__END__

=head1 NAME

Apache::Htpasswd - Manage Unix crypt-style password file.

=head1 SYNOPSIS

    use Apache::Htpasswd;

    $foo = new Apache::Htpasswd("path-to-file");

    $foo = new Apache::Htpasswd({passwdFile => "path-to-file",
				 ReadOnly   => 1}
				);

    # Add an entry
    $foo->htpasswd("zog", "password");

    # Change a password
    $foo->htpasswd("zog", "new-password", "old-password");

    # Change a password without checking against old password

    $foo->htpasswd("zog", "new-password", {'overwrite' => 1});

    # Check that a password is correct
    $foo->htCheckPassword("zog", "password");

    # Fetch an encrypted password
    $foo->fetchPass("foo");

    # Delete entry
    $foo->htDelete("foo");

    # If something fails, check error
    $foo->error;

    # Write in the extra info field
    $foo->writeInfo("login", "info");

    # Get extra info field for a user
    $foo->fetchInfo("login");

=head1 DESCRIPTION

This module comes with a set of methods to use with htaccess password
files. These files (and htaccess) are used to do Basic Authentication
on a web server.

The passwords file is a flat-file with login name and their associated
crypted password. You can use this for non-Apache files if you wish, but
it was written specifically for .htaccess style files.

=head2 FUNCTIONS

=over 4

=item Apache::Htpasswd->new(...);

As of version 1.5.4 named params have been added, and it is suggested that
you use them from here on out.

	Apache::Htpasswd->new("path-to-file");

"path-to-file" should be the path and name of the file containing
the login/password information.

	Apache::Htpasswd->new({passwdFile => "path-to-file",
			       ReadOnly   => 1,
			       UseMD5     => 1,
			     });

This is the prefered way to instantiate an object. The 'ReadOnly' param
is optional, and will open the file in read-only mode if used. The 'UseMD5'
is also optional: it will force MD5 password under Unix.

If you want to support plain un-encrypted passwords, then you need to set
the UsePlain option (this is NOT recommended, but might be necesary in some
situations)

=item error;

If a method returns an error, or a method fails, the error can
be retrieved by calling error()


=item htCheckPassword("login", "password");

Finds if the password is valid for the given login.

Returns 1 if passes.
Returns 0 if fails.


=item htpasswd("login", "password");

This will add a new user to the password file.
Returns 1 if succeeds.
Returns undef on failure.


=item htDelete("login")

Delete users entry in password file.

Returns 1 on success
Returns undef on failure.


=item htpasswd("login", "new-password", "old-password");

If the I<old-password> matches the I<login's> password, then
it will replace it with I<new-password>. If the I<old-password>
is not correct, will return 0.


=item htpasswd("login", "new-password", {'overwrite' => 1});

Will replace the password for the login. This will force the password
to be changed. It does no verification of old-passwords.

Returns 1 if succeeds
Returns undef if fails

=item fetchPass("login");

Returns I<encrypted> password if succeeds.
Returns 0 if login is invalid.
Returns undef otherwise.

=item fetchInfo("login");

Returns additional information if succeeds.
Returns 0 if login is invalid.
Returns undef otherwise.

=item fetchUsers();

Will return either a list of all the user names, or a count of all the
users.

The following will return a list:
my @users = $Htpasswd->fetchUsers();

The following will return the count:
my $user_count = $Htpasswd->fetchUsers();

=item writeInfo("login", "info");

Will replace the additional information for the login.
Returns 0 if login is invalid.
Returns undef otherwise.


=item CryptPasswd("password", "salt");

Will return an encrypted password using 'crypt'. If I<salt> is
ommitted, a salt will be created.

=back

=head1 INSTALLATION

You install Apache::Htpasswd, as you would install any perl module library,
by running these commands:

   perl Makefile.PL
   make
   make test
   make install
   make clean

If you are going to use MD5 encrypted passwords, you need to install L<Crypt::PasswdMD5>.

If you need to support SHA1 encrypted passwords, you need to install L<Digest::SHA1> and L<MIME::Base64>.

=head1 DOCUMENTATION

POD style documentation is included in the module.
These are normally converted to manual pages and installed as part
of the "make install" process.  You should also be able to use
the 'perldoc' utility to extract and read documentation from the
module files directly.


=head1 AVAILABILITY

The latest version of Apache::Htpasswd should always be available from:

    $CPAN/modules/by-authors/id/K/KM/KMELTZ/

Visit <URL:http://www.perl.com/CPAN/> to find a CPAN
site near you.

=head1 CHANGES

Revision 1.8.0  Added proper PREREQ_PM

Revision 1.7.0  Handle SHA1 and plaintext. Also change the interface
for allowing change of password without first checking old password. IF
YOU DON'T READ THE DOCS AND SEE I DID THIS DON'T EMAIL ME!

Revision 1.6.0  Handle Blowfish hashes when that's the mechanism crypt() uses.

Revision 1.5.9  MD5 for *nix with new UseMD5 arg for new()

Revision 1.5.8  Bugfix to htpasswd().

Revision 1.5.7  MD5 for Windows, and other minor changes.

Revision 1.5.6  Minor enhancements.

Revision 1.5.5  2002/08/14 11:27:05 Newline issue fixed for certain conditions.

Revision 1.5.4  2002/07/26 12:17:43 kevin doc fixes, new fetchUsers method,
new ReadOnly option, named params for new(), various others

Revision 1.5.3  2001/05/02 08:21:18 kevin
Minor bugfix

Revision 1.5.2  2001/04/03 09:14:57 kevin
Really fixed newline problem :)

Revision 1.5.1  2001/03/26 08:25:38 kevin
Fixed another newline problem

Revision 1.5  2001/03/15 01:50:12 kevin
Fixed bug to remove newlines

Revision 1.4  2001/02/23 08:23:46 kevin
Added support for extra info fields

Revision 1.3  2000/04/04 15:00:15 meltzek
Made file locking safer to avoid race conditions. Fixed
typo in docs.

Revision 1.2  1999/01/28 22:43:45  meltzek
Added slightly more verbose error croaks. Made sure error from htCheckPassword is only called when called directly, and not by $self.

Revision 1.1  1998/10/22 03:12:08  meltzek
Slightly changed how files lock.
Made more use out of carp and croak.
Made sure there were no ^M's as per Randal Schwartz's request.


=head1 BUGS

None known at time of writting.

=head1 AUTHOR INFORMATION

Copyright 1998..2005, Kevin Meltzer.  All rights reserved.  It may
be used and modified freely, but I do request that this copyright
notice remain attached to the file.  You may modify this module as you
wish, but if you redistribute a modified version, please attach a note
listing the modifications you have made.

This is released under the same terms as Perl itself.

Address bug reports and comments to:
kmeltz@cpan.org

The author makes no warranties, promises, or gaurentees of this software. As with all
software, use at your own risk.

=head1 SEE ALSO

L<Apache::Htgroup>, L<Crypt::PasswdMD5>, L<Digest::SHA1>, L<MIME::Base64>

=cut

