=head1 NAME

 Servers::httpd::Apache2::Event - i-MSCP Apache2 (MPM Event) server implementation

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

package Servers::httpd::Apache2::Event;

use strict;
use warnings;
use parent 'Servers::httpd::Apache2::Prefork';

=head1 DESCRIPTION

 i-MSCP Apache2 (MPM Event) server implementation.

=head1 PRIVATE METHODS

=over 4

=item _setupModules( )

 See Servers::httpd::Apache2::Abstract::_setupModules()

=cut

sub _setupModules
{
    my ($self) = @_;

    my $rs = $self->disableModules( qw/ mpm_itk mpm_prefork mpm_worker cgi / );
    $rs ||= $self->enableModules(
        qw/mpm_event access_compat alias auth_basic auth_digest authn_core authn_file authz_core authz_groupfile authz_host authz_user autoindex cgid
        deflate dir env expires headers mime mime_magic negotiation proxy proxy_http rewrite ssl suexec version/
    );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
