#!/usr/bin/make -f
 
# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
# Copyright (C) 2006-2009 by isp Control Panel - http://ispcp.net
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
# Portions created by the ispCP Team are Copyright (C) 2006-2009 by
# isp Control Panel. All Rights Reserved.
#
# The ispCP ω Home Page is:
#
#    http://isp-control.net
#

SYSTEM_GUI_ROOT=$(SYSTEM_ROOT)/gui

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_GUI_ROOT)

	cp ./index.php $(SYSTEM_GUI_ROOT)/index.php
	cp ./lostpassword.php $(SYSTEM_GUI_ROOT)/lostpassword.php
	cp ./imagecode.php $(SYSTEM_GUI_ROOT)/imagecode.php
	cp ./robots.txt $(SYSTEM_GUI_ROOT)/robots.txt
	cp ./favicon.ico $(SYSTEM_GUI_ROOT)/favicon.ico

	cp -dR ./admin $(SYSTEM_GUI_ROOT)
	cp -dR ./reseller $(SYSTEM_GUI_ROOT)
	cp -dR ./client $(SYSTEM_GUI_ROOT)
	cp -dR ./include $(SYSTEM_GUI_ROOT)

	cp -dR ./domain_default_page $(SYSTEM_GUI_ROOT)
	cp -dR ./domain_disable_page $(SYSTEM_GUI_ROOT)
	cp -dR ./errordocs $(SYSTEM_GUI_ROOT)
	cp -dR ./themes $(SYSTEM_GUI_ROOT)
	cp -dR ./tools $(SYSTEM_GUI_ROOT)
	cp -dR ./orderpanel $(SYSTEM_GUI_ROOT)

	cp -dR ./phptmp $(SYSTEM_GUI_ROOT)

uninstall:

	rm -rf $(SYSTEM_GUI_ROOT)

.PHONY: install uninstall
