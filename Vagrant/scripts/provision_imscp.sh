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

export DEBIAN_FRONTEND=noninteractive
export LANG=C.UTF-8

# Install pre-required packages
apt-get --assume-yes --no-install-recommends install ca-certificates \
libnet-ip-perl libdata-validate-ip-perl perl

# Make sure that /vagrant/preseed.pl is owned by root user
# and that it is not world-readable
chown root:root /vagrant/preseed.pl
chmod 0640 /vagrant/preseed.pl

# Run i-MSCP installer using preconfiguration file
perl /usr/local/src/imscp/imscp-autoinstall --debug --verbose --preseed /vagrant/preseed.pl
