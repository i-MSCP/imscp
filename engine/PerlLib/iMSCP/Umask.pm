package iMSCP::Umask;

use 5.010001;
use strict;
use warnings;

use POSIX qw();
use Exporter qw(import);

our @EXPORT = qw($UMASK);
our $VERSION = '0.01'; # VERSION
our $UMASK;
tie $UMASK, 'iMSCP::Umask::SCALAR' or die "Can't tie \$UMASK";

{
    package iMSCP::Umask::SCALAR;

    sub TIESCALAR {
        bless [ ], $_[0];
    }

    sub FETCH {
        umask();
    }

    sub STORE {
        umask( $_[1] );
    }
}

1;

# ABSTRACT: Get/set umask via (localizeable) variable

__END__

=pod

=encoding UTF-8

=head1 NAME

File::umask - Get/set umask via (localizeable) variable

=head1 VERSION

This document describes version 0.01 of File::umask (from Perl distribution File-umask), released on 2014-05-14.

=head1 SYNOPSIS

 use iMSCP::Umask;
 printf "Current umask is %03o", $UMASK; # -> 022

 {
     local $UMASK = 0;
     open my($fh), ">", "/tmp/foo"; # file created with 666 permission mode
 }

 open my($fh), ">", "/tmp/two"; # file created with normal 644 permission mode

=head1 DESCRIPTION

This module is inspired by L<File::chdir>, using a tied scalar variable to get/set stuffs. One benefit of this is being
able to use Perl's "local" with it, effectively setting something locally.

=head1 EXPORTS

=head2 $UMASK (exported by default)

=head1 SEE ALSO

Perl's umask builtin.

L<Umask::Local>.

Other modules with the same concept: L<File::chdir>, L<Locale::Tie>.

=head1 AUTHOR

Steven Haryanto <stevenharyanto@gmail.com>

=head1 COPYRIGHT AND LICENSE

This software is copyright (c) 2014 by Steven Haryanto.

This is free software; you can redistribute it and/or modify it under the same terms as the Perl 5 programming language
system itself.

=cut
