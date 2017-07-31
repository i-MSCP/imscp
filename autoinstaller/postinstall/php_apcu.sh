#!/bin/sh
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

set -e

# Make sure that apcu.ini file is available for installed PHP variant
# Enable APCU extension
# See https://github.com/oerdnj/deb.sury.org/issues/660

if [ ! -f "/etc/php/$1/mods-available/apcu.ini" ]; then
    cat <<EOF > /etc/php/$1/mods-available/apcu.ini
extension=apc.so
EOF
fi

phpenmod apcu
