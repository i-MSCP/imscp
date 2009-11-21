#!/bin/bash

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
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
# The Original Code is "ispCP ω (OMEGA) a Virtual Hosting Control Panel".
#
# The Initial Developer of the Original Code is ispCP Team.
# Portions created by Initial Developer are Copyright (C) 2006-2009 by
# isp Control Panel. All Rights Reserved.
#
# The ispCP ω Home Page is:
#
#    http://isp-control.net
#

echo "run from folder /var/www/ispcp/engine/setup"

# ispcp.conf
echo "Checking if ispcp.conf is ok"
cnf_check=$(php -r "
                        include('../../gui/include/class.Config.php');
                        include('../../gui/include/ispcp-config.php');"
)

if [ "$cnf_check" != "" ]; then
 echo "An error has occurred while reading /etc/ispcp/ispcp.conf, here comes the HTML code:"
 echo $cnf_check
 echo ""
 exit;
fi
echo "Everything fine until here"
