=head1 NAME

 iMSCP::Cwd - Allows to restrict scope of chdir() calls to enclosing block

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Cwd;

use strict;
use warnings;
use Exporter qw/ import /;

our $CWD;

our @EXPORT = qw/ $CWD /;

tie $CWD, 'iMSCP::Cwd::SCALAR' or die "Can't tie \$CWD";

{
    package iMSCP::Cwd::SCALAR;

    use Cwd;

    sub TIESCALAR
    {
        bless [], $_[0];
    }

    sub FETCH
    {
        getcwd();
    }

    sub STORE
    {
        return unless defined $_[1];
        chdir( $_[1] ) or die( sprintf( "Couldn't change directory to %s: %s:", $_[1], $! ));
    }
}

1;
__END__
