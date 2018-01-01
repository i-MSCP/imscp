=head1 NAME

 iMSCP::Servers::Ftpd::Vsftpd::Debian - i-MSCP (Debian) Vsftpd server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Servers::Ftpd::Vsftpd::Debian;

use strict;
use warnings;
use iMSCP::Service;
use parent 'iMSCP::Servers::Ftpd::Vsftpd::Abstract';

=head1 DESCRIPTION

 i-MSCP (Debian) Vsftpd server implementation.

=head1 SHUTDOWN TASKS

=over 4

=item shutdown( $priority )

 Restart, reload or start the Vsftpd server when needed

 This method is called automatically before the program exit.

 Param int $priority Server priority
 Return void

=cut

sub shutdown
{
    my ($self, $priority) = @_;

    return unless my $action = $self->{'restart'} ? 'restart' : ( $self->{'reload'} ? 'reload' : ( $self->{'start'} ? ' start' : undef ) );

    iMSCP::Service->getInstance()->registerDelayedAction( 'vsftpd', [ $action, sub { $self->$action(); } ], $priority );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
