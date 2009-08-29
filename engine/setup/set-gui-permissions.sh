#!/bin/bash

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (c) 2001-2006 by moleSoftware GmbH
# http://www.molesoftware.com
# Copyright (c) 2006-2009 by isp Control Panel
# http://isp-control.net
#
#
# License:
#    This program is free software; you can redistribute it and/or
#    modify it under the terms of the MPL Mozilla Public License
#    as published by the Free Software Foundation; either version 1.1
#    of the License, or (at your option) any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    MPL Mozilla Public License for more details.
#
#    You may have received a copy of the MPL Mozilla Public License
#    along with this program.
#
#    An on-line copy of the MPL Mozilla Public License can be found
#    http://www.mozilla.org/MPL/MPL-1.1.html
#
#
# The ispCP ω Home Page is at:
#
#    http://isp-control.net
#

# read needed entries from ispcp.conf
CONF_FILE="/etc/ispcp/ispcp.conf"
if [ -f /usr/local/etc/ispcp/ispcp.conf ]
then
    CONF_FILE="/usr/local/etc/ispcp/ispcp.conf"
fi
for a in `grep -E '(APACHE_|ROOT_|MTA_MAILBOX_|^LOG_DIR|^DEBUG)' ${CONF_FILE} | sed -e 's/ //g'`; do
    export $a
done

# for spacing
echo "";
echo "";
echo -n "	Setting GUI Permissions: ";

if [ $DEBUG -eq 1 ]; then
    echo	"";
fi

#
# fixing gui permissions;
#
if [ $DEBUG -eq 1 ]; then
    find $ROOT_DIR/gui/ -print0 -type f| xargs -0 chmod -v 0444
    find $ROOT_DIR/gui/ -print0 -type d| xargs -0 chmod -v 0555
    find $ROOT_DIR/gui/ -print0 | xargs -0 \
	chown -v $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP
else
    find $ROOT_DIR/gui/ -print0 -type f| xargs -0 chmod 0444
    find $ROOT_DIR/gui/ -print0 -type d| xargs -0 chmod 0555
    find $ROOT_DIR/gui/ -print0 | xargs -0 \
	chown $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP
fi

#
# fixing webmail's database permissions;
#

i="$ROOT_DIR/gui/tools/webmail/data"

if [ $DEBUG -eq 1 ]; then
	echo "0755 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";
else
	echo -n ".";
fi

chmod -R 0755 $i;
chown -R $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i;

#
# fixing filemanager permissions
#

i="$ROOT_DIR/gui/tools/filemanager/temp"

if [ $DEBUG -eq 1 ]; then
	echo "0777 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";
else
	echo -n ".";
fi

chmod -R 0777 $i;

#
# fixing user_logo folder permissions;
#

i="$ROOT_DIR/gui/themes/user_logos"

if [ $DEBUG -eq 1 ]; then
	echo "0755 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";
else
	echo -n ".";
fi

chmod -R 0644 $i;
chmod 0755 $i;
chown -R $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i;


#
# fixing db keys permissions;
#

chmod 0444 $ROOT_DIR/gui/include/ispcp-db-keys.php

#
# Setting correct permission for virtual root directory
#

chmod  0755 $APACHE_WWW_DIR;
chown  $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $APACHE_WWW_DIR;

#
# Set correct permission for phptmp gui directory
#

i="$ROOT_DIR/gui/phptmp"

if [ $DEBUG -eq 1 ]; then
	echo "0755 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";
else
	echo -n ".";
fi

chmod -R 0755 $i;
chown -R $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i;

#
# Set correct permission for HTMLPurifier/DefinitionCache/Serializer gui directory
#

i="$ROOT_DIR/gui/include/htmlpurifier/HTMLPurifier/DefinitionCache/Serializer"
chmod -R 0755 $i;

echo "done";
