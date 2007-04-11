#!/bin/sh
#
# clear old sessions from user directorys
#
for a in `cat /etc/ispcp/ispcp.conf | grep -E '(^APACHE_WWW_DIR)' | sed -e 's/ //g'`
do
export $a
done

PHPTMPDIRS=`nice -n 19 find ${APACHE_WWW_DIR} -type d -name phptmp`

for tmpdir in ${PHPTMPDIRS}
do

	if [ -d "${tmpdir}" ] ; then
		if [ -f "/usr/lib/php4/maxlifetime" ] ; then
			nice -n 19 find ${tmpdir} -type f -cmin +$(/usr/lib/php4/maxlifetime) -print0 | xargs -r -0 rm
		else
			nice -n 19 find ${tmpdir} -type f -cmin +24 -print0 | xargs -r -0 rm
		fi
	fi
done