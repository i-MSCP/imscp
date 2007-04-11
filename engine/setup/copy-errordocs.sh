#!/bin/sh

for a in `cat /etc/ispcp/ispcp.conf | grep -E '(APACHE_WWW_DIR|GUI_ROOT_DIR|APACHE_USER|APACHE_GROUP)'| sed -e 's/ //g'`
do
export $a
done


chown ${APACHE_USER}:${APACHE_GROUP} ${GUI_ROOT_DIR}/errordocs/index.php
chmod 400 ${GUI_ROOT_DIR}/errordocs/index.php

for domain in `ls -1A ${APACHE_WWW_DIR}`; do
	if [ -d "${APACHE_WWW_DIR}/${domain}" ] ; then
		echo "${domain}"
		cp -p ${GUI_ROOT_DIR}/errordocs/index.php ${APACHE_WWW_DIR}/${domain}/errors/404/
		cp -p ${GUI_ROOT_DIR}/errordocs/index.php ${APACHE_WWW_DIR}/${domain}/errors/403/
		cp -p ${GUI_ROOT_DIR}/errordocs/index.php ${APACHE_WWW_DIR}/${domain}/errors/401/
		cp -p ${GUI_ROOT_DIR}/errordocs/index.php ${APACHE_WWW_DIR}/${domain}/errors/500/
	fi
done
