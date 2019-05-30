# i-MSCP Listener::System::Hosts listener file
# Copyright (C) 2010-2017 Laurent Declercq <l.declercq@nuxwin.com>
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
## Allows to add host entries in the system hosts file (eg. /etc/hosts).
#

package Listener::System::Hosts;

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::File;

#
## Configuration variables
#

# Path to system hosts file
my $HOST_FILE_PATH = '/etc/hosts';

# Parameter which allow to add one or many host entries in the system hosts file
# Please replace the entries below by your own entries
my @HOST_ENTRIES = (
    '192.168.1.10	foo.mydomain.org	foo',
    '192.168.1.13	bar.mydomain.org	bar'
);

#
## Please, don't edit anything below this line
#

# Listener responsible to add host entries in the system hosts file, once it was built by i-MSCP
iMSCP::EventManager->getInstance()->register(
    'afterSetupServerHostname',
    sub
    {
        return 0 unless -f $HOST_FILE_PATH;

        my $file = iMSCP::File->new( filename => $HOST_FILE_PATH );
        return 1 unless defined( my $fileContent = $file->get());

        $file->set( $fileContent . ( join "\n", @HOST_ENTRIES ) . "\n" );
        $file->save();
    }
);

1;
__END__
