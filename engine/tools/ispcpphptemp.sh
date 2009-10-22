#!/bin/bash
#
# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (c) 2006-2009 by isp Control Panel
# http://isp-control.net
#
#
# License:
#	This program is free software;  you can redistribute it and/or modify
#	it under the terms of the GNU General Public License as published by
#	the Free Software Foundation; either version 2 of the License, or
#	(at your option) any later version.
#
#	This program is distributed in the hope that it will be useful,
#	but WITHOUT ANY WARRANTY; without even the implied warranty of
#	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#	GNU General Public License for more details.
#
#	You may have received a copy of theGNU General Public License
#	along with this program; if not, write to the
#	Free Software Foundation, Inc.,
#	59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#
# The ispCP ω Home Page is at:
#
#	http://isp-control.net
#
# @version $Id$

# Load the required entries from ispcp's configuration
if [ -f /usr/local/etc/ispcp/ispcp.conf ]
then
	CONF=/usr/local/etc/ispcp/ispcp.conf
else
	CONF=/etc/ispcp/ispcp.conf
fi
for a in `cat $CONF  | grep -E '(^ROOT_DIR|^APACHE_WWW_DIR|^PHP_STARTER_DIR)' | sed -e 's/ //g'`; do
	export $a
done

# -r is a GNU-xargs option (BSD doesn't have it, behaving always as if it was specified)
export XARGS="xargs$(echo '' |xargs -r 2>/dev/null && echo ' -r')"

# Ensure that apache dir ends with exactly one slash
APACHE_WWW_DIR=${APACHE_WWW_DIR%/}/

# Removes old (according to the php session.gc_maxfiletime directive) files
# from the given temporary directory. Arguments:
#   - Temporary directory.
#   - Fcgi directory containing php{4,5} php.ini files.
function removeOldFiles {
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
	nice -n 19 find $1 -type f -cmin +${max} -print0 | ${XARGS} -0 rm
}

# Remove older files from hosted domain's temporary folders
for wdir in ${APACHE_WWW_DIR}*; do
	removeOldFiles "${wdir}/phptmp" "${PHP_STARTER_DIR}${wdir#$APACHE_WWW_DIR}"
done

# And finally remove older files from panel's temporary folder
removeOldFiles "${ROOT_DIR}/gui/phptmp" "${PHP_STARTER_DIR}/master"