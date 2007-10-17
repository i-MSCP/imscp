#!/bin/sh

for a in `cat /etc/ispcp/ispcp.conf | grep -E '(APACHE_WWW_DIR|GUI_ROOT_DIR|APACHE_USER|APACHE_GROUP)'| sed -e 's/ //g'`
do
export $a
done


chown ${APACHE_USER}:${APACHE_GROUP} ${GUI_ROOT_DIR}/errordocs/*.html
chmod 400 ${GUI_ROOT_DIR}/errordocs/*.html

for domain in `ls -1A ${APACHE_WWW_DIR}`; do
	if [ -d "${APACHE_WWW_DIR}/${domain}" ] ; then
		echo "${domain}"
		cp -p ${GUI_ROOT_DIR}/errordocs/404.html ${APACHE_WWW_DIR}/${domain}/errors/
		cp -p ${GUI_ROOT_DIR}/errordocs/403.html ${APACHE_WWW_DIR}/${domain}/errors/
		cp -p ${GUI_ROOT_DIR}/errordocs/401.html ${APACHE_WWW_DIR}/${domain}/errors/
		cp -p ${GUI_ROOT_DIR}/errordocs/500.html ${APACHE_WWW_DIR}/${domain}/errors/
	fi
done
