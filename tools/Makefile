#!/usr/bin/make -f

# i-MSCP internet Multi Server Control Panel
# Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
# Copyright (C) 2010 by internet Multi Server Control Panel - http://i-mscp.sf.net
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
# Portions created by the i-msCP Team are Copyright (C) 2010 by
# internet Multi Server Control Panel. All Rights Reserved.
#
# The i-MSCP Home Page is:
#
#    http://i-mscp.sf.net
#

export

DAEMON_DIR=$(SYSTEM_ROOT)/daemon


build: daemon/imscp_daemon

daemon/imscp_daemon:
	(cd ./daemon; \
	$(MAKE) imscp_daemon;)

install: build
	$(SYSTEM_MAKE_DIRS) $(DAEMON_DIR)
	cp ./daemon/imscp_daemon $(DAEMON_DIR)

uninstall:

	(cd ./daemon; \
	$(MAKE) clean; )
	rm -rf $(DAEMON_DIR)
	rm -rf build

.PHONY: install uninstall
