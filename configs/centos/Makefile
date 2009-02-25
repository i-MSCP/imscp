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
	if test $(HOST_OS) = centos ; then \
		cp ./ispcp.conf $(SYSTEM_CONF) ; \
		(cd ./apache && $(MAKE) install &); \
		(cd ./awstats && $(MAKE) install &); \
		(cd ./bind && $(MAKE) install &); \
		(cd ./cron.d && $(MAKE) install &); \
		(cd ./database && $(MAKE) install &);  \
		(cd ./fcgi && $(MAKE) install &); \
		(cd ./init.d && $(MAKE) install &); \
		(cd ./postfix && $(MAKE) install &); \
		(cd ./courier && $(MAKE) install &); \
		(cd ./proftpd && $(MAKE) install &); \
		(cd ./logrotate && $(MAKE) install); \
	fi

uninstall:
	if test $(HOST_OS) = centos ; then \
		rm -rf $(SYSTEM_CONF)/ispcp.conf ;  \
		(cd ./apache && $(MAKE) uninstall &); \
		(cd ./awstats && $(MAKE) uninstall &); \
		(cd ./bind && $(MAKE) uninstall &); \
		(cd ./cron.d && $(MAKE) uninstall &); \
		(cd ./database && $(MAKE) uninstall &); \
		(cd ./init.d && $(MAKE) uninstall &); \
		(cd ./postfix && $(MAKE) uninstall &); \
		(cd ./courier && $(MAKE) uninstall &); \
		(cd ./proftpd && $(MAKE) uninstall &); \
		(cd ./logrotate && $(MAKE) uninstall &); \
	fi
