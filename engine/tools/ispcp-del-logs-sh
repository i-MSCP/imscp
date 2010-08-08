#!/bin/bash

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2010 by isp Control Panel - http://ispcp.net
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
# The Original Code is "ispCP ω (OMEGA) a Virtual Hosting Control Panel".
#
# The Initial Developer of the Original Code is ispCP Team.
# Portions created by Initial Developer are Copyright (C) 2010 by
# isp Control Panel. All Rights Reserved.
#
# The ispCP ω Home Page is:
#
#    http://isp-control.net
#

# Load the required entries from ispcp's configuration
if [ -f /usr/local/etc/ispcp/ispcp.conf ]
then
	CONF=/usr/local/etc/ispcp/ispcp.conf
else
	CONF=/etc/ispcp/ispcp.conf
fi
for a in `cat $CONF  | grep -E '(^APACHE_LOG_DIR|^APACHE_BACKUP_LOG_DIR|^APACHE_USERS_LOG_DIR)' | sed -e 's/ //g'`; do
	export $a
done

# -r is a GNU-xargs option (BSD doesn't have it, behaving always as if it was specified)
export XARGS="xargs$(echo '' |xargs -r 2>/dev/null && echo ' -r')"

# Remove `Apache` logs that are older than 365 days
for i in `ls -1 ${APACHE_LOG_DIR}/*.log*`
	do
		nice -n 19 find $i -type f -mtime +365 -print0 | ${XARGS} -0 rm;
done

# Remove `Users` logs that are older than 365 days
for i in `ls -1 ${APACHE_USERS_LOG_DIR}/*.log*`
	do
		nice -n 19 find $i -type f -mtime +365 -print0 | ${XARGS} -0 rm;
done

# Remove `Backup` logs that are older than 365 days
for i in `ls -1 ${APACHE_BACKUP_LOG_DIR}/*`
	do
		nice -n 19 find $i -type f -mtime +365 -print0 | ${XARGS} -0 rm;
done