#!/bin/bash

COURIER_CONF_DIR=/etc/courier

CMD_CP=/bin/cp

CMD_MAKEUSERDB=/usr/sbin/makeuserdb

CMD_MKDIR=/bin/mkdir


${CMD_CP} ./authdaemonrc ${COURIER_CONF_DIR}

${CMD_CP} ./authmodulelist ${COURIER_CONF_DIR}

${CMD_CP} ./imapd ${COURIER_CONF_DIR}

${CMD_CP} ./pop3d ${COURIER_CONF_DIR}

${CMD_CP} ./working/userdb ${COURIER_CONF_DIR}

${CMD_MAKEUSERDB}

