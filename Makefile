#!/usr/bin/make -f

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
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
# The Initial Developer of the Original Code is moleSoftware GmbH.
# Portions created by Initial Developer are Copyright (C) 2001-2006
# by moleSoftware GmbH. All Rights Reserved.
# Portions created by the ispCP Team are Copyright (C) 2006-2010 by
# isp Control Panel. All Rights Reserved.
#
# The ispCP ω Home Page is:
#
#    http://isp-control.net
#

ifndef INST_PREF
        INST_PREF=/tmp/ispcp
endif

HOST_OS=debian

ROOT_CONF=$(INST_PREF)/etc

SYSTEM_ROOT=$(INST_PREF)/var/www/ispcp
SYSTEM_CONF=$(INST_PREF)/etc/ispcp
SYSTEM_LOG=$(INST_PREF)/var/log/ispcp
SYSTEM_APACHE_BACK_LOG=$(INST_PREF)/var/log/apache2/backup
SYSTEM_VIRTUAL=$(INST_PREF)/var/www/virtual
SYSTEM_AWSTATS=$(INST_PREF)/var/www/awstats
SYSTEM_FCGI=$(INST_PREF)/var/www/fcgi
SYSTEM_SCOREBOARDS=$(INST_PREF)/var/www/scoreboards
SYSTEM_MAIL_VIRTUAL=$(INST_PREF)/var/mail/virtual
SYSTEM_MAKE_DIRS=/bin/mkdir -p
SYSTEM_MAKE_FILE=/bin/touch

export


install:

	cd ./tools && $(MAKE) install

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_ROOT)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_LOG)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_LOG)/ispcp-arpl-msgr
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_VIRTUAL)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_FCGI)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_SCOREBOARDS)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_AWSTATS)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_MAIL_VIRTUAL)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_APACHE_BACK_LOG)

	cd ./configs && $(MAKE) install &
	cd ./engine && $(MAKE) install
	cd ./gui && $(MAKE) install
	cd ./keys && $(MAKE) install
	cd ./database && $(MAKE) install


uninstall:

	cd ./tools && $(MAKE) uninstall &
	cd ./configs && $(MAKE) uninstall &
	cd ./engine && $(MAKE) uninstall &
	cd ./gui && $(MAKE) uninstall &
	cd ./keys && $(MAKE) uninstall &
	cd ./database && $(MAKE) uninstall &

	rm -rf $(SYSTEM_CONF)
	rm -rf $(SYSTEM_ROOT)
	rm -rf $(SYSTEM_LOG)
	rm -rf $(SYSTEM_VIRTUAL)
	rm -rf $(SYSTEM_FCGI)
	rm -rf $(SYSTEM_SCOREBOARDS)
	rm -rf $(SYSTEM_MAIL_VIRTUAL)
	rm -rf $(SYSTEM_APACHE_BACK_LOG)
	#rm -rf ./*~


clean:

	cd ./tools/daemon && $(MAKE) clean
	rm -rf $(INST_PREF)

.PHONY: install uninstall clean
