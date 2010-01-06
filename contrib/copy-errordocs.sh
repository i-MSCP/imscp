#!/bin/sh

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
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
# The Initial Developer of the Original Code is ispCP Team.
# Portions created by Initial Developer are Copyright (C) 2006-2010 by
# isp Control Panel. All Rights Reserved.
#
# The ispCP ω Home Page is:
#
#    http://isp-control.net
#

for a in `cat /etc/ispcp/ispcp.conf | grep -E '(APACHE_WWW_DIR|GUI_ROOT_DIR|APACHE_USER|APACHE_GROUP)'| sed -e 's/ //g'`
do
export $a
done


chown ${APACHE_USER}:${APACHE_GROUP} ${GUI_ROOT_DIR}/errordocs/*.html
chmod 400 ${GUI_ROOT_DIR}/errordocs/*.html

for domain in `ls -1A ${APACHE_WWW_DIR}`; do
	if [ -d "${APACHE_WWW_DIR}/${domain}" ] ; then
		echo "${domain}"
		cp -p ${GUI_ROOT_DIR}/errordocs/404.html ${APACHE_WWW_DIR}/${domain}/errors/
		cp -p ${GUI_ROOT_DIR}/errordocs/403.html ${APACHE_WWW_DIR}/${domain}/errors/
		cp -p ${GUI_ROOT_DIR}/errordocs/401.html ${APACHE_WWW_DIR}/${domain}/errors/
		cp -p ${GUI_ROOT_DIR}/errordocs/500.html ${APACHE_WWW_DIR}/${domain}/errors/
	fi
done
