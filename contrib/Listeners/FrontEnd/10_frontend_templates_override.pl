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
## Allows to override default i-MSCP frontEnd template files by copying your
## own template files.
##
#

package Listener::FrontEnd::Templates::Override;

use strict;
use warnings;
use iMSCP::Debug 'error';
use iMSCP::Dir;
use iMSCP::EventManager;

# Path to your own i-MSCP theme directory
my $CUSTOM_THEME_PATH = '';

# Please don't edit anything below this line

iMSCP::EventManager->getInstance()->register(
    'afterSetupInstallFiles',
    sub
    {
        local $@;
        eval {
            iMSCP::Dir->new(
                dirname => $CUSTOM_THEME_PATH
            )->rcopy(
                "$::imscpConfig{'GUI_ROOT_DIR'}/themes/default",
                { preserve => 'no' }
            );
        };
        if( $@ ) {
            error( $@ );
            return 1;
        }

        0;
    }
);

1;
__END__
