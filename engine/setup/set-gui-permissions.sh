#!/bin/bash

# ISPCP ω (OMEGA) - Virtual Hosting Control System | Omega Version
# Copyright (c) 2006-2007 by ispCP | http://isp-control.net
#
#
# License:
#    This program is free software; you can redistribute it and/or
#    modify it under the terms of the GPL General Public License
#    as published by the Free Software Foundation; either version 2.0
#    of the License, or (at your option) any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GPL General Public License for more details.
#
#    You may have received a copy of the GPL General Public License
#    along with this program.
#
#    An on-line copy of the GPL General Public License can be found
#    http://www.fsf.org/licensing/licenses/gpl.txt
#
########################################################################

# read needed entries from ispcp.conf
for a in `cat /etc/ispcp/ispcp.conf | grep -E '(APACHE_|ROOT_DIR)' | sed -e 's/ //g'`
do
export $a
done

#
# fixing gui permissions;
#

for i in `find $ROOT_DIR/gui/`; do

	if [[ -f $i ]]; then

		echo -e "\t0444 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i";

		chmod 0444 $i;
		chown $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i;

	elif [[ -d $i ]]; then

		echo "0555 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";

		chmod 0555 $i;
		chown $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i;
	fi

done

#
# fixing webmail's database permissions;
#

i="$ROOT_DIR/gui/tools/webmail/data"

echo "0755 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";

chmod -R 0755 $i;
chown -R $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i;

#
# fixing filemanager permissions
#

i="$ROOT_DIR/gui/tools/filemanager/temp"

echo "0777 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";
chmod -R 0777 $i;

#
# fixing user_logo folder permissions;
#

i="$ROOT_DIR/gui/themes/user_logos"

echo "0755 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";

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

echo "0755 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";

chmod -R 0755 $i;
chown -R $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i;
