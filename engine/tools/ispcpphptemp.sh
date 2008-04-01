#!/bin/bash

# read needed entries from ispcp.conf
if [ -f /usr/local/etc/ispcp/ispcp.conf ]
then
	for a in `cat /usr/local/etc/ispcp/ispcp.conf  | grep -E '(^APACHE_WWW_DIR|^PHP_STARTER_DIR)' | sed -e 's/ //g'`; do
		export $a
	done
else
	for a in `cat /etc/ispcp/ispcp.conf | grep -E '(^APACHE_WWW_DIR|^PHP_STARTER_DIR)' | sed -e 's/ //g'`; do
		export $a
	done
fi


WEBDIRS=`ls -d ${APACHE_WWW_DIR}/* `

for wdir in ${WEBDIRS}; do
	tmpdir="${wdir}/phptmp"
	fcgidir=${PHP_STARTER_DIR}`echo ${wdir} | awk "-F${APACHE_WWW_DIR}" '{print $2}'`

	if [ ! -f "${fcgidir}/php4/php.ini" ] && [ ! -f "${fcgidir}/php4/php.ini" ]; then
		continue;
	fi

	cur=0
	max=1440

	for ini in "${fcgidir}/php4/php.ini ${fcgidir}/php5/php.ini"; do
		if [ ! -f "$ini" ]; then
			continue;
		fi

		cur=$(sed -n -e 's/^[[:space:]]*session.gc_maxlifetime[[:space:]]*=[[:space:]]*\([0-9]\+\).*$/\1/p' ${ini} 2>/dev/null || true);

		if [ -z "$cur" ]; then
			cur=0
		fi

		if [ "$cur" -gt "$max" ]; then
			max=$cur
		fi
	done

	max=$(($max/60))

	nice -n 19 find ${tmpdir} -type f -cmin +${max} -print0 | xargs -r -0 rm

	# Per-domain php.ini should be used instead of this:
	#if [ -f "/usr/lib/php4/maxlifetime" ] ; then
	#	nice -n 19 find ${tmpdir} -type f -cmin +$(/usr/lib/php4/maxlifetime) -print0 | xargs -r -0 rm
	#else
	#	nice -n 19 find ${tmpdir} -type f -cmin +24 -print0 | xargs -r -0 rm
	#fi
done