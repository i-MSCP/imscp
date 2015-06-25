#!/bin/bash
#
#  Copyright (C) 2014 by Red Hat
#
#  This program is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; either version 3 of the License, or
#  (at your option) any later version.
#
# Script which allow to test services for poodle vulnerability
#
# Usage example for a single port:
#
# sh ./poodle.sh <ip> 21
#
# Usage example for many ports:
#
# for i in `echo 21 587 443 465 993 995 `; do sh ./poodle.sh <ip> $i; done
#

host=${1:-127.0.0.1}
port=${2:-443}
protocol=${3:-ssl3}
timeout_bin=`which timeout 2>/dev/null`

echo -n "$host:$port - "
if [ $port -eq 21 ]; then
	opt="-starttls ftp"
fi
if [ $port -eq 587 ]; then
	opt="-starttls smtp"
fi

out="`echo 'Q' | ${timeout_bin:+$timeout_bin 5} openssl s_client -$protocol $opt -connect "${host}:${port}" 2>/dev/null`"

if [ $? -eq 124 ]; then
	echo "error: Timeout connecting to host!"
	exit 1
fi

if ! echo "$out" | grep -q 'Cipher is' ; then
	echo 'Not vulnerable.  Failed to establish SSL connection.'
	exit 0
fi

proto=`echo "$out" | grep '^ *Protocol *:' | awk '{ print $3 }'`
cipher=`echo "$out" | grep '^ *Cipher *:' | awk '{ print $3 }'`

if [ "$cipher" = '0000'  -o  "$cipher" = '(NONE)' ]; then
	echo 'Not vulnerable.  Failed to establish SSLv3 connection.'
	exit 0
else
	echo "Vulnerable!  SSLv3 connection established using $proto/$cipher"
	exit 1
fi
