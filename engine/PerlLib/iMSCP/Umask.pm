=head1 NAME

 iMSCP::Umask - Allows to restrict scope of umask() calls to enclosing block

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Umask;

use Exporter qw/ import /;

our @EXPORT = qw/ $UMASK /;
our $UMASK;

tie $UMASK, 'iMSCP::Umask::SCALAR' or die "Can't tie \$UMASK";

{
    package iMSCP::Umask::SCALAR;

    sub TIESCALAR
    {
        bless [], $_[0];
    }

    sub FETCH
    {
        umask();
    }

    sub STORE
    {
        return unless defined $_[1];
        umask( $_[1] );
    }
}

1;
__END__
