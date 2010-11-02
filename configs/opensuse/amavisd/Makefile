#!/usr/bin/make -f

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
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
# The Original Code is "ispCP ω (OMEGA) - Virtual Hosting Control Panel".
#
# The Initial Developer of the Original Code is moleSoftware GmbH.
# Portions created by Initial Developer are Copyright (C) 2001-2006
# by moleSoftware GmbH. All Rights Reserved.
# Portions created by the ispCP Team are Copyright (C) 2006-2010 by
# isp Control Panel. All Rights Reserved.
#
# The ispCP ω Home Page is:
#
#    http://isp-control.net
#

export

install:
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/amavisd

	cp -R ./backup $(SYSTEM_CONF)/amavisd
	cp -R ./working $(SYSTEM_CONF)/amavisd
	cp ./amavisd.tpl $(SYSTEM_CONF)/amavisd
	cp ./amavisd.domains $(SYSTEM_CONF)/amavisd/amavisd.domains
	cp ./sender_scores_sitewide $(SYSTEM_CONF)/amavisd/sender_scores_sitewide
	cp ./spamassassin.tpl $(SYSTEM_CONF)/amavisd/spamassassin.tpl

uninstall:

	rm -rf $(SYSTEM_CONF)/amavisd
