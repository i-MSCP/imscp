#!/bin/sh

# i-MSCP a internet Multi Server Control Panel
#
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
# The Original Code is "ispCP ω (OMEGA) a Virtual Hosting Control Panel".
#
# The Initial Developer of the Original Code is ispCP Team.
# Portions created by Initial Developer are Copyright (C) 2006-2010 by
# isp Control Panel. All Rights Reserved.
#
# Portions created by the i-MSCP Team are Copyright (C) 2010 by
# internet Multi Server Control Panel. All Rights Reserved.
#
# The i-MSCP Home Page is:
#
#    http://i-mscp.net
#

# Load the required entries from i-MSCP's configuration
if [ -f /usr/local/etc/imscp/imscp.conf ]
then
	CONF=/usr/local/etc/imscp/imscp.conf
else
	CONF=/etc/imscp/imscp.conf
fi
for a in `cat $CONF  | grep -E '(^ROOT_DIR|^APACHE_WWW_DIR|^PHP_STARTER_DIR)' | sed -e 's/ //g'`; do
	export $a
done

# -r is a GNU-xargs option (BSD doesn't have it, behaving always as if it was specified)
export XARGS="xargs$(echo '' |xargs -r 2>/dev/null && echo ' -r')"

# Ensure that apache dir ends with exactly one slash
APACHE_WWW_DIR=${APACHE_WWW_DIR%/}/

# Removes old files (according to the php session.gc_maxfiletime directive)
# from the given temporary directory. Arguments:
#   - Temporary directory.
#   - Fcgi directory containing php{4,5} php.ini files.
removeOldFiles() {
	if [ ! -d "$1" ]; then
		return 1;
	fi

	max=0
	for ini in "$2/php4/php.ini" "$2/php5/php.ini"; do
		if [ ! -f "$ini" ]; then
			continue;
		fi

		cur=$(sed -n -e 's/^[[:space:]]*session.gc_maxlifetime[[:space:]]*=[[:space:]]*\([0-9]\+\).*$/\1/p' ${ini} 2>/dev/null || true);
		if [ -z "$cur" ]; then
			cur=0
		fi
		if [ "$cur" -gt "$max" ]; then
			max=$cur
		fi
	done

	if [ "$max" -eq "0" ]; then
		# PHP default max lifetime
		max=1440
	fi
	max=$(($max/60))
	nice -n 19 find $1 -type f -name 'sess_*' -cmin +${max} -print0 | ${XARGS} -0 rm
}

# Remove older files from hosted domain's temporary folders
for wdir in ${APACHE_WWW_DIR}*; do
	removeOldFiles "${wdir}/phptmp" "${PHP_STARTER_DIR}${wdir#$APACHE_WWW_DIR}"
done

# And finally remove older files from panel's temporary folder
removeOldFiles "${ROOT_DIR}/gui/data/sessions" "${PHP_STARTER_DIR}/master"
