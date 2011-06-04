#!/bin/sh

# i-MSCP a internet Multi Server Control Panel
#
# Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
# Copyright (C) 2010 by internet Multi Server Control Panel - http://i-mscp.net
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
#
# Portions created by the ispCP Team are Copyright (C) 2006-2010 by
# isp Control Panel. All Rights Reserved.
#
# Portions created by the i-MSCP Team are Copyright (C) 2010 by
# internet Multi Server Control Panel. All Rights Reserved.
#
# The i-MSCP Home Page is:
#
#    http://i-mscp.net
#

# read needed entries from imscp.conf
if [ -f "/etc/imscp/imscp.conf" ] ; then
	CONF_FILE="/etc/imscp/imscp.conf"
		CMD_SED=`which sed`
elif [ -f /usr/local/etc/imscp/imscp.conf ] ; then
    CONF_FILE="/usr/local/etc/imscp/imscp.conf"
		CMD_SED=`which gsed`
fi

OLD_IFS=$IFS
IFS=$

# Reading needed entries from imscp.conf
for a in $(grep -E '^(APACHE_|CMD_|DEBUG|CONF_DIR|LOG_DIR|MR_LOCK|MTA_MAILBOX_|ROOT_|PHP_STARTER_DIR)' \
${CONF_FILE} | $CMD_SED 's/\s*=\s*\(.*\)/="\1"/'); do
	 eval $a
done

IFS=$OLD_IFS

# Detect xargs version:
# - BSD has no "-r" argument (always acts as if it was specified)
# - GNU has "-r" argument, and we need it!
if echo 'test' | xargs -r >/dev/null 2>&1; then
	XARGS="xargs -r"
else
	XARGS="xargs"
fi

# for readability
PANEL_USER="$APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_UID"
PANEL_GROUP="$APACHE_SUEXEC_USER_PREF$APACHE_SUEXEC_MIN_GID"

# Helper function to recursively set owner and permissions to a folder. Args:
# (1) Path to the folder
# (2) User that will own the folder and all its contents
# (3) Group of the folder and all its contents
# (4) Directory and subdirectories permissions
# (5) File permissions
recursive_set_permissions() {
	if [ $DEBUG -eq 1 ]; then
		find $1 -type d -print0 | ${XARGS} -0 ${CMD_CHMOD} -v $4
		find $1 -type f -print0 | ${XARGS} -0 ${CMD_CHMOD} -v $5
		find $1 -print0 | ${XARGS} -0 ${CMD_CHOWN} -v $2:$3
	else
		find $1 -type d -print0 | ${XARGS} -0 ${CMD_CHMOD} $4
		find $1 -type f -print0 | ${XARGS} -0 ${CMD_CHMOD} $5
		find $1 -print0 | ${XARGS} -0 ${CMD_CHOWN} $2:$3
	fi
}

# Helper function to set owner and permissions to a file/folder. Args:
# (1) Path to the file/folder
# (2) User that will own the folder and all its contents
# (3) Group of the folder and all its contents
# (4) Permissions
set_permissions() {
	if [ $DEBUG -eq 1 ]; then
		echo "$4 $2:$3 [$1]";
	else
		echo -n ".";
	fi
	${CMD_CHMOD} $4 $1;
	${CMD_CHOWN} $2:$3 $1;
}
