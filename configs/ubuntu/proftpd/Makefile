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

RELEASE=`lsb_release -c | awk '{print $$2}' | tr -d "\n"`

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/proftpd

	cp -R ./backup $(SYSTEM_CONF)/proftpd
	cp -R ./parts $(SYSTEM_CONF)/proftpd
	cp -R ./working $(SYSTEM_CONF)/proftpd

	if [ ! -d $(ROOT_CONF)/proftpd ]; then \
		$(SYSTEM_MAKE_DIRS) $(ROOT_CONF)/proftpd ; \
	fi

	$(SYSTEM_MAKE_DIRS) $(ROOT_CONF)/proftpd/ispcp;
	cp ./root_domain.conf $(ROOT_CONF)/proftpd/ispcp;

	if [ $(RELEASE) = 'dapper' ] ; then \
		cp ./proftpd.conf $(SYSTEM_CONF)/proftpd ; \
	elif [ $(RELEASE) = 'intrepid' ] || [ $(RELEASE) = 'jaunty' ] || [ $(RELEASE) = 'karmic' ]; then \
		cp ./proftpd1.3.intrepid.conf $(SYSTEM_CONF)/proftpd/proftpd.conf ; \
	else \
		if test -e /etc/proftpd.conf; then \
			mv /etc/proftpd.conf /etc/proftpd.conf.bak ; \
		fi ; \
		cp ./proftpd1.3.conf $(SYSTEM_CONF)/proftpd/proftpd.conf ; \
	fi

uninstall:

	rm -rf $(SYSTEM_CONF)/proftpd
