#!/bin/sh
#
# ispCP repositories install script for OpenSuse 11.3
#
# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2006-2009 by isp Control Panel - http://ispcp.net
#
# author Laurent Declercq <laurent.declercq@ispcp.net>
# version 1.0.0
# SVN: $Id$
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
# The Original Code is "ispCP ω (OMEGA) a Virtual Hosting Control Panel".
#
# The Initial Developer of the Original Code is ispCP Team.
# Portions created by Initial Developer are Copyright (C) 2006-2009 by
# isp Control Panel. All Rights Reserved.
#
# The ispCP ω Home Page is:
#
#    http://isp-control.net


ZYPPER_BIN=/usr/bin/zypper
URI_BASE="http://download.opensuse.org"

case "$1" in
	add)
		# Apache
		$ZYPPER_BIN ar -f $URI_BASE/repositories/Apache/openSUSE_11.3/ ispcp-apache2
		$ZYPPER_BIN ar -f $URI_BASE/repositories/Apache:/Modules/openSUSE_11.3/ ispcp-apache2-modules

		# Awstats
		$ZYPPER_BIN ar -f $URI_BASE/repositories/network:/utilities/openSUSE_11.3/ ispcp-awstats

		# Proftpd, lha, rkhunter
		$ZYPPER_BIN ar -f $URI_BASE/repositories/openSUSE:/11.3:/Contrib/standard/ ispcp-contrib

		# Courier, Postgrey
		$ZYPPER_BIN ar -f $URI_BASE/repositories/server:/mail/openSUSE_11.3 ispcp-mail

		# policyd-weight
		$ZYPPER_BIN ar -f $URI_BASE/repositories/home:/pheinlein/openSUSE_11.3/ ispcp-pweight

		# Refresh all repositories
		$ZYPPER_BIN ref
	;;
	rm)
		REPO="apache2 apache2-modules awstats contrib mail pweight"

		for i in $REPO
			do $ZYPPER_BIN rr ispcp-$i
		done
	;;
	*)
		echo "Usage: sh ./repo {add|rm}"

	exit 1
esac

exit 0
