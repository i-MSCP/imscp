#!/bin/bash

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
# Copyright (C) 2006-2009 by isp Control Panel - http://ispcp.net
#
# Version: $Id$
#
# The contents of this file are subject to the Mozilla Public License
# Version 1.1 (the "License"); you may not use this file except in
# compliance with the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS"
# basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
# License for the specific language governing rights and limitations
# under the License.
#
# The Original Code is "VHCS - Virtual Hosting Control System".
#
# The Initial Developer of the Original Code is moleSoftware GmbH.
# Portions created by Initial Developer are Copyright (C) 2001-2006
# by moleSoftware GmbH. All Rights Reserved.
# Portions created by the ispCP Team are Copyright (C) 2006-2009 by
# isp Control Panel. All Rights Reserved.
#
# The ispCP ω Home Page is:
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
    find $ROOT_DIR/gui/ -type f -print0 | xargs -0 chmod -v 0440
    find $ROOT_DIR/gui/ -type d -print0 | xargs -0 chmod -v 0550
    find $ROOT_DIR/gui/ -print0 | xargs -0 \
	chown -v $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP
else
    find $ROOT_DIR/gui/ -type f -print0 | xargs -0 chmod 0440
    find $ROOT_DIR/gui/ -type d -print0 | xargs -0 chmod 0550
    find $ROOT_DIR/gui/ -print0 | xargs -0 \
	chown $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP
fi

#
# fixing webmail's database permissions;
#

i="$ROOT_DIR/gui/tools/webmail/data"

if [ $DEBUG -eq 1 ]; then
	echo "0750 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";
else
	echo -n ".";
fi

chmod -R 0750 $i;
chown -R $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i;

#
# fixing filemanager permissions
#

i="$ROOT_DIR/gui/tools/filemanager/temp"

if [ $DEBUG -eq 1 ]; then
	echo "0750 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";
else
	echo -n ".";
fi

chmod -R 0750 $i;

#
# fixing user_logo folder permissions;
#

i="$ROOT_DIR/gui/themes/user_logos"

if [ $DEBUG -eq 1 ]; then
	echo "0750 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";
else
	echo -n ".";
fi

chmod -R 0640 $i;
chmod 0750 $i;
chown -R $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i;


#
# fixing db keys permissions;
#
i="$ROOT_DIR/gui/include/ispcp-db-keys.php"
if [ $DEBUG -eq 1 ]; then
	echo "0400 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_GID [$i]";
else
	echo -n ".";
fi
chmod 0400 $i;
chown $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_GID $i;

#
# Setting correct permission for virtual root directory
#

if [ $DEBUG -eq 1 ]; then
	echo "0555 $APACHE_USER:$APACHE_GROUP [$APACHE_WWW_DIR]";
else
	echo -n ".";
fi
chmod  0555 $APACHE_WWW_DIR;
chown  $APACHE_USER:$APACHE_GROUP $APACHE_WWW_DIR;

#
# Set correct permission for phptmp gui directory
#

i="$ROOT_DIR/gui/phptmp"
if [ $DEBUG -eq 1 ]; then
	echo "0750 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";
else
	echo -n ".";
fi
chmod -R 0750 $i;
chown -R $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i;

#
# Set correct permission for HTMLPurifier/DefinitionCache/Serializer gui directory
#

i="$ROOT_DIR/gui/include/htmlpurifier/HTMLPurifier/DefinitionCache/Serializer"
if [ $DEBUG -eq 1 ]; then
	echo "0750 $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP [$i]";
else
	echo -n ".";
fi
chmod -R 0750 $i;
chown -R $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_GROUP $i;

echo "done";
