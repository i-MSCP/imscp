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
for a in `grep -E '(APACHE_|ROOT_|MTA_MAILBOX_|^LOG_DIR|^DEBUG|^PHP_STARTER_DIR|^MR_LOCK|^CMD_)' ${CONF_FILE} | sed -e 's/ //g'`; do
    export $a
done

#
# fixing engine permissions;
#

echo -n "	Setting Engine Permissions: ";

if [ $DEBUG -eq 1 ]; then
    echo	"";
fi

# Fix ispcp.conf perms
if [ $DEBUG -eq 1 ]; then
    echo -e "	ug+r,u+w,o-r $ROOT_USER:$APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID /etc/ispcp/ispcp.conf";
else
    echo -n ".";
fi
${CMD_CHOWN} $ROOT_USER:$APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID ${CONF_FILE}

# Fix rkhunter.log perms
if [ $DEBUG -eq 1 ]; then
    echo -e "	ug+r,u+w,o-r $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$ROOT_USER /var/log/rkhunter.log";
else
    echo -n ".";
fi

#chmod ug+r,u+w,o-r rkhunter.log
if [ -f /var/log/rkhunter.log ]
then
	${CMD_CHOWN} $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$ROOT_GROUP /var/log/rkhunter.log
fi

#
# fixing engine permissions
#
if [ $DEBUG -eq 1 ]; then
    find $ROOT_DIR/engine/ -print0 | xargs -0 ${CMD_CHMOD} -v 0700
    find $ROOT_DIR/engine/ -print0 | xargs -0 ${CMD_CHOWN} -v $ROOT_USER:$ROOT_GROUP
else
    find $ROOT_DIR/engine/ -print0 | xargs -0 ${CMD_CHMOD} 0700
    find $ROOT_DIR/engine/ -print0 | xargs -0 ${CMD_CHOWN} $ROOT_USER:$ROOT_GROUP
fi

#
# fixing engine folder permissions;
#
${CMD_CHMOD} 0755 $ROOT_DIR/engine;
${CMD_CHOWN} $ROOT_USER:$ROOT_GROUP $ROOT_DIR/engine;

#
# fixing fcgi folder permissions in Centos;
#
${CMD_CHMOD} 0755 $PHP_STARTER_DIR/master;
${CMD_CHOWN} $APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID:$APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID $PHP_STARTER_DIR/master;

#
# fixing messager permissions;
#

i="$ROOT_DIR/engine/messenger"

if [ $DEBUG -eq 1 ]; then
	echo "0700 $MTA_MAILBOX_UID_NAME:$MTA_MAILBOX_GID_NAME [$i]";
else
	echo -n ".";
fi
${CMD_CHMOD} -R 0700 $i;
${CMD_CHOWN} -R $MTA_MAILBOX_UID_NAME:$MTA_MAILBOX_GID_NAME $i;


#
# fixing messager folder permissions;
#
i="$ROOT_DIR/engine/messenger"

if [ $DEBUG -eq 1 ]; then
	echo "0755 $ROOT_USER:$ROOT_GROUP folder [$i]";
else
	echo -n ".";
fi

${CMD_CHMOD} 0755 $i;
${CMD_CHOWN} $ROOT_USER:$ROOT_GROUP $i;


#
# fixing messager log folder permissions;
#
i="${LOG_DIR}/ispcp-arpl-msgr"

if [ $DEBUG -eq 1 ]; then
	echo "0755 $MTA_MAILBOX_UID_NAME:$MTA_MAILBOX_GID_NAME folder [$i]";
else
	echo -n ".";
fi

${CMD_CHMOD} 0755 $i;
${CMD_CHOWN} -R $MTA_MAILBOX_UID_NAME:$MTA_MAILBOX_GID_NAME $i;


echo "done";