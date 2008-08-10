#!/usr/bin/make -f
#
# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (c) 2001-2006 by moleSoftware GmbH
# http://www.molesoftware.com
# Copyright (c) 2006-2008 by isp Control Panel
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
# The ispCP ω Home Page is at:
#
#    http://isp-control.net
#

ROOT_APACHE_CONF=$(ROOT_CONF)/apache2
RELEASE=`cat /etc/debian_version | awk '{print $1}'`

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/apache
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)/sites-available
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)/mods-available
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)/ispcp

	cp ./httpd.conf $(SYSTEM_CONF)/apache
	cp ./00_master.conf $(SYSTEM_CONF)/apache
	cp ./01_awstats.conf $(SYSTEM_CONF)/apache
	cp -R ./backup $(SYSTEM_CONF)/apache
	cp -R ./parts $(SYSTEM_CONF)/apache
	cp -R ./working $(SYSTEM_CONF)/apache

	cp ./httpd.conf $(SYSTEM_CONF)/apache/working/ispcp.conf
	cp ./httpd.conf $(SYSTEM_CONF)/apache/parts/ispcp_base.tpl

	# Debian 4.0 "Etch"?
	if [[ $(RELEASE) = '4.0' ]] || [[ $(RELEASE) = 'lenny/sid' ]]; then \
		cp ./fastcgi2.conf $(SYSTEM_CONF)/apache/working/fastcgi_ispcp.conf ; \
	else \
		cp ./fastcgi.conf $(SYSTEM_CONF)/apache/working/fastcgi_ispcp.conf ; \
	fi
	cp ./fcgid_ispcp.conf $(SYSTEM_CONF)/apache/working/fcgid_ispcp.conf

	cp $(SYSTEM_CONF)/apache/working/fastcgi_ispcp.conf $(ROOT_APACHE_CONF)/mods-available/fastcgi_ispcp.conf
	cp ./fcgid_ispcp.conf $(ROOT_APACHE_CONF)/mods-available/fcgid_ispcp.conf

uninstall:

	rm -rf $(SYSTEM_CONF)/apache
	rm -f $(APACHE_SITES_DIR)/ispcp.conf
	rm -f $(APACHE_SITES_DIR)/00_master.conf
	rm -f $(APACHE_SITES_DIR)/01_awstats.conf
	rm -f $(APACHE_MODS_DIR)/fastcgi_ispcp.conf
	rm -f $(APACHE_MODS_DIR)/fcgid_ispcp.conf
