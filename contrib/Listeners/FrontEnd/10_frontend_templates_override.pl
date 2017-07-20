# i-MSCP Listener::FrontEnd::Templates::Override listener file
# Copyright (C) 2016-2017 Laurent Declercq <l.declercq@nuxwin.com>
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
## Allows to override default i-MSCP frontEnd template files by copying your own template files.
##
#

package Listener::FrontEnd::Templates::Override;

use strict;
use warnings;
use iMSCP::Dir;
use iMSCP::EventManager;

# Path to your own template files
my $CUSTOM_THEMES_PATH = '/usr/local/src/imscp-custom/themes/default';

# Please don't edit anything below this line

iMSCP::EventManager->getInstance()->register(
    'afterSetupInstallFiles',
    sub {
        iMSCP::Dir->new( dirname => $CUSTOM_THEMES_PATH )->rcopy(
            "$main::imscpConfig{'GUI_ROOT_DIR'}/themes/default", { preserve => 'no' }
        );
    }
);

1;
__END__
