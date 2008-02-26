#!/bin/bash

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (c) 2001-2006 by moleSoftware GmbH
# http://www.molesoftware.com
# Copyright (c) 2006-2008 by isp Control Panel
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

# for activating debug, please set to 1;
DEBUG=0

# read needed entries from ispcp.conf
for a in `cat /etc/ispcp/ispcp.conf | grep -E '(APACHE_|ROOT_DIR)' | sed -e 's/ //g'`; do
    export $a
done

# for spacing
echo "";
echo "";
echo -n "\tSetting GUI Permissions: ";

if [ $DEBUG -eq 1 ]; then
    echo	"";
fi

#
# fixing gui permissions;
#

for i in `find $ROOT_DIR/gui/`; do

	if [[ -f $i ]]; then

		if [ $DEBUG -eq 1 ]; then
			echo -e "\t0444 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i";
		fi

		chmod 0444 $i;
		chown $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i;

	elif [[ -d $i ]]; then

		if [ $DEBUG -eq 1 ]; then
			echo "0555 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";
		else
			echo -n ".";
		fi

		chmod 0555 $i;
		chown $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i;
	fi

done

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

echo "done";
