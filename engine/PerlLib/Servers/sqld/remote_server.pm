=head1 NAME

 Servers::sqld::remote_server - i-MSCP remote SQL server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Servers::sqld::remote_server;

use strict;
use warnings;
use parent 'Servers::sqld::mysql';

=head1 DESCRIPTION

 i-MSCP remote SQL server implementation.

=head1 PUBLIC METHODS

=over 4

=item postinstall()

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
	my $self = shift;

	my $rs = $self->{'eventManager'}->trigger('beforeSqldPostInstall', 'mysql');
	return $rs if $rs;

	$self->{'eventManager'}->trigger('afterSqldPostInstall', 'mysql');
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
