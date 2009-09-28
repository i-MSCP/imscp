#!/usr/bin/make -f
#
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
# The ISPCP ω Home Page is at:
#
#    http://isp-control.net
#

# This is a TODO list:
# CLOSE:	there is no /etc/cron.d/ispcp is freebsd
# CLOSE: 	wrong path and format awstats cronjob
# OPEN:		scoreboard dir not created
# OPEN:		Under jail, system still in heavy testing

.ifdef $(OSTYPE)==FreeBSD
.include <Makefile.fbsd>
.else
.include <Makefile.inc>
.endif

install:
	#
	# Preparing ISPCP System Directory and files
	#
	cd ./tools && $(MAKE) install
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_ROOT)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_LOG)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_LOG)/ispcp-arpl-msgr
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_VIRTUAL)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_FCGI)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_MAIL_VIRTUAL)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_APACHE_BACK_LOG)
	cd ./configs && $(MAKE) install
	cd ./engine && $(MAKE) install
	cd ./gui && $(MAKE) install
	cd ./keys && $(MAKE) install

	#
	# Patch some variable
	#
	/usr/bin/sed s/"\/etc\/ispcp\/ispcp.conf"/"\/usr\/local\/etc\/ispcp\/ispcp.conf"/g ./engine/ispcp_common_code.pl > $(SYSTEM_ROOT)/engine/ispcp_common_code.pl
	/usr/bin/sed s/"\/etc\/awstats"/"\/usr\/local\/etc\/awstats"/g ./engine/awstats/awstats_updateall.pl > $(SYSTEM_ROOT)/engine/awstats/awstats_updateall.pl

.if exists ($(SYSTEM_WEB)/ispcp/engine/ispcp-db-keys.pl)
	#
	# Previous database key detected, assuming being perform Upgrade Procedure
	#
	cp $(SYSTEM_WEB)/ispcp/engine/ispcp-db-keys.pl $(SYSTEM_ROOT)/engine/
	cp $(SYSTEM_WEB)/ispcp/engine/messenger/ispcp-db-keys.pl $(SYSTEM_ROOT)/engine/messenger/
	cp $(SYSTEM_WEB)/ispcp/gui/include/ispcp-db-keys.php $(SYSTEM_ROOT)/gui/include/
	cp $(SYSTEM_WEB)/ispcp/gui/themes/user_logos/* $(SYSTEM_ROOT)/gui/themes/user_logos/
	cp $(SYSTEM_WEB)/ispcp/gui/tools/pma/config.inc.php $(SYSTEM_ROOT)/gui/tools/pma/

	# Delete old files to avoid security risks
	rm -rf $(SYSTEM_WEB)/ispcp/gui/admin
	rm -rf $(SYSTEM_WEB)/ispcp/gui/client
	rm -rf $(SYSTEM_WEB)/ispcp/gui/include
	rm -rf $(SYSTEM_WEB)/ispcp/gui/orderpanel
	rm -rf $(SYSTEM_WEB)/ispcp/gui/themes
	rm -rf $(SYSTEM_WEB)/ispcp/gui/reseller
	rm -rf $(SYSTEM_WEB)/ispcp/gui/*.php

        # Backup ispcp.conf and copy the /etc directory into your system (you may make backups):
	mv -v /usr/local/etc/ispcp/ispcp.conf /usr/local/etc/ispcp/ispcp.old.conf
	mv -v /usr/local/etc/proftpd.conf /usr/local/etc/proftpd.old.conf

	# Copy /usr and /var directories into your system (you may make backups)
	cp -R $(INST_PREF)/usr/* /usr/
	cp -R $(INST_PREF)/var/* /var/
.else
	cd ${INST_PREF} && cp -R * /
.endif

	mkdir -p /usr/local/www/data/scoreboards
	#
	#
	# If Some error occured please read FAQ first and search at forum in http://www.isp-control.net
	# Go to $(SYSTEM_WEB)/ispcp/engine/setup and type "ispcp-setup" to configure or "ispcp-upgrade"
	# to complete upgrade process
	rm -rf ${INST_PREF}

uninstall:
	cd ./tools && $(MAKE) uninstall
	cd ./configs && $(MAKE) uninstall
	cd ./engine && $(MAKE) uninstall
	cd ./gui && $(MAKE) uninstall
	cd ./keys && $(MAKE) uninstall
	rm -rf $(SYSTEM_CONF)
	rm -rf $(SYSTEM_ROOT)
	rm -rf $(SYSTEM_LOG)
	rm -rf $(SYSTEM_VIRTUAL)
	rm -rf $(SYSTEM_FCGI)
	rm -rf $(SYSTEM_MAIL_VIRTUAL)
	rm -rf $(SYSTEM_APACHE_BACK_LOG)
	rm -rf ./*~


clean:
	cd ./tools/daemon && $(MAKE) clean

