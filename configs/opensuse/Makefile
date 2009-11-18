#!/usr/bin/make -f

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

export

install:

	if test $(HOST_OS) = opensuse ; then \
		cp ./ispcp.conf $(SYSTEM_CONF) ; \
		cd ./apache && $(MAKE) install ; cd .. ; \
		cd ./awstats && $(MAKE) install ; cd .. ; \
		cd ./bind && $(MAKE) install ; cd .. ; \
		cd ./courier && $(MAKE) install ; cd .. ; \
		cd ./cron.d && $(MAKE) install ; cd .. ; \
		cd ./fcgi && $(MAKE) install ; cd .. ; \
		cd ./init.d && $(MAKE) install ; cd .. ; \
		cd ./logrotate && $(MAKE) install ; cd .. ; \
		cd ./postfix && $(MAKE) install ; cd .. ; \
		cd ./proftpd && $(MAKE) install ; cd .. ; \
	fi

uninstall:

	if test $(HOST_OS) = opensuse ; then \
		rm -rf $(SYSTEM_CONF)/ispcp.conf ;  \
		cd ./apache && $(MAKE) uninstall ; cd .. ; \
		cd ./awstats && $(MAKE) uninstall ; cd .. ; \
		cd ./bind && $(MAKE) uninstall ; cd .. ; \
		cd ./courier && $(MAKE) uninstall ; cd .. ; \
		cd ./cron.d && $(MAKE) uninstall ; cd .. ; \
		cd ./fcgi && $(MAKE) uninstall ; cd .. ; \
		cd ./init.d && $(MAKE) uninstall ; cd .. ; \
		cd ./logrotate && $(MAKE) uninstall ; cd .. ; \
		cd ./postfix && $(MAKE) uninstall ; cd .. ; \
		cd ./proftpd && $(MAKE) uninstall ; cd .. ; \
	fi
