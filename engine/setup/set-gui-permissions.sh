#!/bin/sh

# i-MSCP - internet Multi Server Control Panel
#
# Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
# Copyright (C) 2010-2011 by internet Multi Server Control Panel - http://i-mscp.net
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
#
# Portions created by the ispCP Team are Copyright (C) 2006-2010 by
# isp Control Panel. All Rights Reserved.
#
# Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
# internet Multi Server Control Panel. All Rights Reserved.
#
# The i-MSCP Home Page is:
#
#    http://i-mscp.net
#

SELFDIR=$(dirname "$0")
. $SELFDIR/imscp-permission-functions.sh
# for spacing
echo -n "	Setting GUI Permissions: ";

if [ $DEBUG -eq 1 ]; then
    echo	"";
fi

set_permissions "$ROOT_DIR/gui" \
    $PANEL_USER $APACHE_GROUP 0550

recursive_set_permissions "$ROOT_DIR/gui/public" \
	$PANEL_USER $APACHE_GROUP 0550 0440

recursive_set_permissions "$ROOT_DIR/gui/library" \
	$PANEL_USER $PANEL_USER 0500 0400

recursive_set_permissions "$ROOT_DIR/gui/data" \
	$PANEL_USER $PANEL_USER 0700 0600

set_permissions "$ROOT_DIR/gui/data" \
    $PANEL_USER $APACHE_GROUP 0550

recursive_set_permissions "$ROOT_DIR/gui/data/ispLogos" \
	$PANEL_USER $APACHE_GROUP 0750 0640

recursive_set_permissions "$ROOT_DIR/gui/i18n/locales" \
	$PANEL_USER $PANEL_USER 0700 0600

recursive_set_permissions "$ROOT_DIR/gui/public/tools/filemanager/temp" \
	$PANEL_USER $PANEL_USER 0700 0600

recursive_set_permissions "$ROOT_DIR/gui/public/tools/webmail/data" \
	$PANEL_USER $PANEL_USER 0700 0600

# Main virtual webhosts directory must be owned by root and readable by all
# the domain-specific users.
set_permissions "$APACHE_WWW_DIR" \
    $ROOT_USER $ROOT_GROUP 0555

# Main fcgid directory must be world-readable, because all the domain-specific
# users must be able to access its contents.
set_permissions "$PHP_STARTER_DIR" \
    $ROOT_USER $ROOT_GROUP 0555

echo " done";

exit 0
