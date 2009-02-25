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
# The ispCP ω Home Page is at:
#
#    http://isp-control.net
#

ROOT_AUTHLIB_CONF=$(ROOT_CONF)/courier/authlib
ROOT_COURIER_CONF=$(ROOT_CONF)/courier
ROOT_COURIER_IMAP_CONF=$(ROOT_CONF)/courier-imap

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/courier
	$(SYSTEM_MAKE_DIRS) $(ROOT_AUTHLIB_CONF)
	$(SYSTEM_MAKE_DIRS) $(ROOT_COURIER_IMAP_CONF)
	$(SYSTEM_MAKE_FILE) $(INST_PREF)/var/log/mail.log

#	MTA Configuration Files;
	cp ./userdb $(SYSTEM_CONF)/courier

#	MTA Configuration Directories;
	cp -R ./backup $(SYSTEM_CONF)/courier
	cp -R ./working $(SYSTEM_CONF)/courier
	cp ./userdb $(ROOT_COURIER_CONF)

uninstall:

	rm -rf $(SYSTEM_CONF)/courier
