# i-MSCP Listener::Packages::Override listener file
# Copyright (C) 2010-2019 Laurent Declercq <l.declercq@nuxwin.com>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301 USA

#
## This listener file make it possible to override default distribution
# packages file.
#

package Listener::Packages::Override;

use strict;
use warnings;
use iMSCP::EventManager;

# Path to your own package file
my $DISTRO_PACKAGES_FILE = '';

# Please don't edit anything below this line

iMSCP::EventManager->getInstance()->register(
    'onLoadPackagesFile',
    sub {
        my $packagesFile = shift;
        ${ $packagesFile } = $DISTRO_PACKAGES_FILE;
        0;
    }
) if length $DISTRO_PACKAGES_FILE;

1;
__END__
