#!/bin/sh
# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

# Create i-MSCP preseed file
if [ ! -f /vagrant/preseed.pl ]; then
 echo "The i-MSCP preseed.pl file has not been found. Please create it first."
 exit 1
fi

head -n -2 /vagrant/preseed.pl > /tmp/preseed.pl
cat <<'EOT' >> /tmp/preseed.pl
$::questions{'BASE_SERVER_IP'} = '0.0.0.0';

1;
EOT

# Execute the i-MSCP installer using preseeding file
perl /usr/local/src/imscp/imscp-autoinstall --debug --verbose --preseed /tmp/preseed.pl
