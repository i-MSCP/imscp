# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2016-2017 by Laurent Declercq
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

#
## Allows to override default PhpMyAdmin configuration template file
#

package Listener::PhpMyAdmin::Conffile;

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::File;

#
## Configuration variables
#

# Path to PhpMyAdmin configuration template file
my $tplFilePath = '/root/imscp.config.inc.php';

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'onLoadTemplate',
    sub {
        my ($pkgName, $tplName, $tplContent) = @_;

        return 0 unless $pkgName eq 'phpmyadmin' && $tplName eq 'imscp.config.inc.php' && -f $tplFilePath;

        $$tplContent = iMSCP::File->new( filename => $tplFilePath )->get();
        0;
    }
);

1;
__END__
