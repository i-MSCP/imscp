=head1 NAME

 iMSCP::Plugins - Package which allow to retrieve list of i-MSCP plugins by paths

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Plugins;

use strict;
use warnings;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Package which allow to retrieve list of i-MSCP plugins by paths

=head1 PUBLIC METHODS

=over 4

=item get()

 Get plugin paths list

 Return server list

=cut

sub get
{
    @{$_[0]->{'plugins'}};
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return iMSCP::Plugins

=cut

sub _init
{
    my $self = shift;

    @{$self->{'plugins'}} = glob( "$main::imscpConfig{'PLUGINS_DIR'}/*/backend/*.pm" );
    $self;
}

=back

=head1 AUTHOR

Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
