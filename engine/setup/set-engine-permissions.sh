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

. $(dirname "$0")/ispcp-permission-functions.sh

echo -n "	Setting Engine Permissions: ";
if [ $DEBUG -eq 1 ]; then
    echo	"";
fi

# ispcp.conf must be world readable because user "vmail" needs to access it.
set_permissions "/etc/ispcp/ispcp.conf" $ROOT_USER $ROOT_GROUP 0644
if [ -f /usr/local/etc/ispcp/ispcp.conf ]; then
	set_permissions "/usr/local/etc/ispcp/ispcp.conf" \
		$ROOT_USER $ROOT_GROUP 0644
fi

# The panel must be able to read rkhunter log to display it.
if [ -f /var/log/rkhunter.log ]; then
	set_permissions "/var/log/rkhunter.log" $PANEL_USER $ROOT_GROUP 640
fi

# Only root can run engine scripts
recursive_set_permissions "$ROOT_DIR/engine" $ROOT_USER $ROOT_GROUP 0700 0700

# Engine folder must be world-readable because "vmail" user must be able
# to access its "messenger" subfolder.
set_permissions "$ROOT_DIR/engine" $ROOT_USER $ROOT_GROUP 0755

# Messenger script is run by user "vmail.
recursive_set_permissions "$ROOT_DIR/engine/messenger" \
	$MTA_MAILBOX_UID_NAME $MTA_MAILBOX_GID_NAME 0750 0550
recursive_set_permissions "$LOG_DIR/ispcp-arpl-msgr" \
	$MTA_MAILBOX_UID_NAME $MTA_MAILBOX_GID_NAME 0750 0640

echo " done";

exit 0
