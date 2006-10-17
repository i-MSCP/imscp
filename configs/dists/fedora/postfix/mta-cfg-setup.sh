#!/bin/bash

MTA_CONF_DIR=/etc/postfix

MTA_SYSTEM_CONF_DIR=/etc/postfix/vhcs2



CMD_CP=/bin/cp

CMD_POSTMAP=/usr/sbin/postmap

CMD_NEWALIASES=/usr/bin/newaliases

CMD_MKDIR=/bin/mkdir



${CMD_CP} ./main.cf ${MTA_CONF_DIR}

${CMD_CP} ./master.cf ${MTA_CONF_DIR}

${CMD_MKDIR} -p ${MTA_SYSTEM_CONF_DIR}

${CMD_CP} ./working/{aliases,domains,mailboxes,transport} ${MTA_SYSTEM_CONF_DIR}

${CMD_POSTMAP} ${MTA_SYSTEM_CONF_DIR}/{aliases,domains,mailboxes,transport}

${CMD_NEWALIASES}

