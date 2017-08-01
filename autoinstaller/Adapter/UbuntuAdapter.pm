=head1 NAME

 autoinstaller::Adapter::UbuntuAdapter - Ubuntu autoinstaller adapter

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

package autoinstaller::Adapter::UbuntuAdapter;

use strict;
use warnings;
use parent 'autoinstaller::Adapter::DebianAdapter';

=head1 DESCRIPTION

 i-MSCP autoinstaller adapter implementation for Ubuntu.

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return autoinstaller::Adapter::UbuntuAdapter

=cut

sub _init
{
    my $self = shift;

    $self->SUPER::_init();
    $self->{'repositorySections'} = [ 'main', 'universe', 'multiverse' ];
    $self;
}

=back

=head1 Author

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
