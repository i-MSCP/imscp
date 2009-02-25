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

export

install:

	$(SYSTEM_MAKE_DIRS) $(ROOT_CONF)/init.d
	$(SYSTEM_MAKE_DIRS) $(ROOT_CONF)/default

	cp ispcp_daemon $(ROOT_CONF)/init.d
	cp ispcp_network $(ROOT_CONF)/init.d

	chown root:root $(ROOT_CONF)/init.d/ispcp_network $(ROOT_CONF)/init.d/ispcp_daemon
	chmod 0755 $(ROOT_CONF)/init.d/ispcp_network $(ROOT_CONF)/init.d/ispcp_daemon

	if test -f ispcp_daemon.default ; then \
		cp -p ispcp_daemon.default $(ROOT_CONF)/default/ispcp_daemon ; \
	fi
	if test -f ispcp_network.default ; then \
		cp -p ispcp_network.default $(ROOT_CONF)/default/ispcp_network ; \
	fi

uninstall:

	rm -f $(ROOT_CONF)/init.d/ispcp_daemon
	rm -f $(ROOT_CONF)/init.d/ispcp_network
	rm -f $(ROOT_CONF)/default/ispcp_daemon
	rm -f $(ROOT_CONF)/default/ispcp_network
