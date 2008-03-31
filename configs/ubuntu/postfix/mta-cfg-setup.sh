#!/bin/sh

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (c) 2001-2006 by moleSoftware GmbH
# http://www.molesoftware.com
# Copyright (c) 2006-2007 by isp Control Panel
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

MTA_CONF_DIR=/etc/postfix

MTA_SYSTEM_CONF_DIR=/etc/postfix/ispcp


CMD_CP=/bin/cp

CMD_POSTMAP=/usr/sbin/postmap

CMD_NEWALIASES=/usr/bin/newaliases

CMD_MKDIR=/bin/mkdir


${CMD_CP} ./main.cf ${MTA_CONF_DIR}

${CMD_CP} ./master.cf ${MTA_CONF_DIR}

${CMD_MKDIR} -p ${MTA_SYSTEM_CONF_DIR}

${CMD_CP} ./working/aliases ${MTA_SYSTEM_CONF_DIR}
${CMD_CP} ./working/domains ${MTA_SYSTEM_CONF_DIR}
${CMD_CP} ./working/mailboxes ${MTA_SYSTEM_CONF_DIR}
${CMD_CP} ./working/sender-access ${MTA_SYSTEM_CONF_DIR}
${CMD_CP} ./working/transport ${MTA_SYSTEM_CONF_DIR}

${CMD_POSTMAP} ${MTA_SYSTEM_CONF_DIR}/aliases
${CMD_POSTMAP} ${MTA_SYSTEM_CONF_DIR}/domains
${CMD_POSTMAP} ${MTA_SYSTEM_CONF_DIR}/mailboxes
${CMD_POSTMAP} ${MTA_SYSTEM_CONF_DIR}/sender-access
${CMD_POSTMAP} ${MTA_SYSTEM_CONF_DIR}/transport

${CMD_NEWALIASES}
