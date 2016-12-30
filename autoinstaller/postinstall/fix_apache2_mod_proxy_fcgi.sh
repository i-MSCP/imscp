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

# Fix for https://bz.apache.org/bugzilla/show_bug.cgi?id=55415
# diff -Naur mod_proxy_fcgi.c imscp_mod_proxy_fcgi.c
#
# Howto remove divert:
#  service apache2 stop
#  rm /usr/lib/apache2/modules/mod_proxy_fcgi.so
#  dpkg-divert --rename --remove /usr/lib/apache2/modules/mod_proxy_fcgi.so

SRC_DIR=/usr/local/src/imscp-apache_src
APACHE_VERSION=`dpkg-query --show --showformat '${Version}' apache2`

# Don't process if the module has been already patched or if apache2 version is
# lower than 2.4.10
if [ -f /usr/lib/apache2/modules/mod_proxy_fcgi.so-DIST ] \
   || dpkg --compare-versions "$APACHE_VERSION" lt "2.4.10" ; then
    exit;
fi

rm -fR ${SRC_DIR}
mkdir -p ${SRC_DIR}
cd ${SRC_DIR}
service apache2 stop
apt-get -y install apache2-dev patch
apt-get -y source apache2
cd apache2*/modules/proxy
patch -p0 <<EOF
--- mod_proxy_fcgi.c	2016-10-09 01:05:17.000000000 +0200
+++ imscp_mod_proxy_fcgi.c	2016-10-09 01:20:38.711978843 +0200
@@ -591,7 +593,8 @@
                             }
 
                             if (conf->error_override &&
-                                ap_is_HTTP_ERROR(r->status)) {
+                                ap_is_HTTP_ERROR(r->status) &&
+                                ap_is_initial_req(r)) {
                                 /*
                                  * set script_error_status to discard
                                  * everything after the headers
EOF
apxs -c mod_proxy_fcgi.c
dpkg-divert --divert /usr/lib/apache2/modules/mod_proxy_fcgi.so-DIST --rename /usr/lib/apache2/modules/mod_proxy_fcgi.so
apxs -i mod_proxy_fcgi.la
cd /
rm -fR ${SRC_DIR}
