#!/bin/bash

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
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
# The Original Code is "VHCS - Virtual Hosting Control System".
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

. $(dirname "$0")/ispcp-permission-functions.sh

# for spacing
echo -en "	Setting GUI Permissions: ";

if [ $DEBUG -eq 1 ]; then
    echo	"";
fi

# By default, gui files must be readable by both the panel user (php files are
# run under this user) and apache (static files are served by it).
recursive_set_permissions "$ROOT_DIR/gui/" \
	$PANEL_USER $APACHE_GROUP 0550 0440

# But the following folders must be writable by the panel user, because
# php-generated or uploaded files will be stored there.
recursive_set_permissions "$ROOT_DIR/gui/phptmp" \
	$PANEL_USER $APACHE_GROUP 0750 0640
recursive_set_permissions "$ROOT_DIR/gui/themes/user_logos" \
	$PANEL_USER $APACHE_GROUP 0750 0640
recursive_set_permissions "$ROOT_DIR/gui/tools/filemanager/temp" \
	$PANEL_USER $APACHE_GROUP 0750 0640
recursive_set_permissions "$ROOT_DIR/gui/tools/webmail/data" \
	$PANEL_USER $APACHE_GROUP 0750 0640
recursive_set_permissions \
	"$ROOT_DIR/gui/include/htmlpurifier/HTMLPurifier/DefinitionCache/Serializer" \
	$PANEL_USER $APACHE_GROUP 0750 0640

# Decryption keys allow root access to the database, so they must only be
# accessible by the panel user.
set_permissions "$ROOT_DIR/gui/include/ispcp-db-keys.php" \
	$PANEL_USER $PANEL_GROUP 0400

# Main virtual webhosts directory must be owned by root and readable by all
# the domain-specific users.
set_permissions $APACHE_WWW_DIR $ROOT_USER $ROOT_GROUP 0555

# Main fcgid directory must be world-readable, because all the domain-specific
# users must be able to access its contents.
set_permissions "$PHP_STARTER_DIR" $ROOT_USER $ROOT_GROUP 0555

# Required on centos
set_permissions "$PHP_STARTER_DIR/master" $PANEL_USER $PANEL_GROUP 0755

echo " done";

exit 0
