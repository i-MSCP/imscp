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


ROOT_POSTFIX_CONF=$(ROOT_CONF)/postfix

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/postfix
	$(SYSTEM_MAKE_DIRS) $(ROOT_POSTFIX_CONF)
#
#       MTA Configuration Files;
#
	cp ./main.cf $(SYSTEM_CONF)/postfix/main.cf
	cp ./master.cf $(SYSTEM_CONF)/postfix/master.cf

#
#       MTA Configuration Directories;
#
	cp -R ./backup $(SYSTEM_CONF)/postfix
	cp -R ./parts $(SYSTEM_CONF)/postfix
	cp -R ./working $(SYSTEM_CONF)/postfix
	cp -R ./ispcp $(SYSTEM_CONF)/postfix

	cp -R ./ispcp $(ROOT_POSTFIX_CONF)

uninstall:

	rm -rf $(SYSTEM_CONF)/postfix
	rm -rf $(ROOT_POSTFIX_CONF)/ispcp

