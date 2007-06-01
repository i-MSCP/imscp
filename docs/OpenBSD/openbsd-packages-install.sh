#!/bin/sh

export PKG_PATH=ftp://rt.fm/pub/OpenBSD/`uname -r`/packages/`uname -m`/

for a in `cat ./openbsd-packages.txt`
do

	pkg_add -v $a

done
	wget ftp://ftp.proftpd.org/distrib/source/proftpd-1.3.0a.tar.bz2
        bunzip2 proftpd-1.3.0a.tar.bz2
        tar -xvf proftpd-1.3.0a.tar
        cd proftpd-1.3.0a 
	./configure --sysconfdir=/etc --enable-ctrls --enable-ipv6 --enable-endfile --enable-facl --with-modules=mod_sql:mod_sql_mysql:mod_tls:mod_rewrite:mod_ratio:mod_readme:mod_ifsession:mod_ctrls_admin:mod_quotatab:mod_quotatab_file:mod_quotatab_sql --with-includes=/usr/local/include/mysql --with-libraries=/usr/local/lib --localstatedir=/var/run
        cd ./proftpd-1.3.0a i
        make
	make install
        rm -rf ./proftpd-1.3.0a.tar
        rm -rf ./proftpd-1.3.0a

