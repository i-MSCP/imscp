#!/usr/bin/make -f
#
# ispCP ? (OMEGA) a Virtual Hosting Control Panel
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
# The ispCP ? Home Page is at:
#
#    http://isp-control.net
#

export

DAEMON_DIR=$(SYSTEM_ROOT)/daemon


build: daemon/ispcp_daemon

daemon/ispcp_daemon:
	(cd ./daemon; \
	$(MAKE) ispcp_daemon;)

install: build
	$(SYSTEM_MAKE_DIRS) $(DAEMON_DIR)
	cp ./daemon/ispcp_daemon $(DAEMON_DIR)

uninstall:

	(cd ./daemon; \
	$(MAKE) clean; )
	rm -rf $(DAEMON_DIR)
	rm -rf build

.PHONY: install uninstall
