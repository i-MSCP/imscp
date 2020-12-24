#!/bin/bash
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

export DEBIAN_FRONTEND=noninteractive
export LANG=C.UTF-8

# Remove unwanted foreign i386 architecture which is enabled in
# some Vagrant boxes
dpkg --remove-architecture i386 2>/dev/null

. /etc/os-release

if [ "$ID" == 'debian' ] ; then
  # Fix problem with grub installation
  echo "set grub-pc/install_devices $(grub-probe -t device /boot/grub)" | debconf-communicate
fi

apt-get update
apt-get --assume-yes dist-upgrade
apt-get --assume-yes install \
  ca-certificates            \
  perl                       \
  virt-what

# Fix problem with Debian Buster (Grub not cleanly installed)
if virt-what | grep virtualbox &> /dev/null ; then
  grub-install /dev/sda
fi
