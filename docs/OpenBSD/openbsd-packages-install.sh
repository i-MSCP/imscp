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
cd ./proftpd-1.3.0a
make
make install
cd ..
rm -rf ./proftpd-1.3.0a.tar
rm -rf ./proftpd-1.3.0a
groupadd -o -g 0 root
ln -s /usr/local/libexec/makedatprog /usr/local/bin/makedatprog
	/usr/local/share/mysql/mysql.server start
	/usr/local/bin/mysql_install_db
	mysqladmin password 'your-new-password'
	/var/www/ispcp/engine/setup/ispcp-setup
	echo "mkdir -p /var/run/courier-imap" >> /etc/rc.local
	echo "/usr/local/libexec/authlib/authdaemond start" >> /etc/rc.local
	echo "/usr/local/libexec/imapd.rc start" >> /etc/rc.local
	echo "/usr/local/libexec/imapd-ssl.rc start" >> /etc/rc.local
	echo "/usr/local/libexec/pop3d.rc start" >> /etc/rc.local
	echo "/usr/local/libexec/pop3d-ssl.rc start" >> /etc/rc.local
	echo "/usr/local/share/mysql/mysql.server start" >> /etc/rc.local
	echo "mkdir -p /var/run/proftpd" >> /etc/rc.local
	echo "/etc/proftpd.rc start" >> /etc/rc.local
	echo "/etc/ispcp_daemon.rc start" >> /etc/rc.local
	echo "ntpd_flags=" >> /etc/rc.conf.local
	echo "httpd_flags=-u" >> /etc/rc.conf.local
	echo "inetd=NO" >> /etc/rc.conf.local
	echo "syslogd_flags=" >> /etc/rc.conf.local
	echo "sendmail_flags=\"-bd -q30m\"" >> /etc/rc.conf.local
	echo "named_flags=" >> /etc/rc.conf.local
	echo "AddType application/x-httpd-php .php" >> /var/www/conf/httpd.conf
	echo "Include /var/www/conf/ispcp.conf" >> /var/www/conf/httpd.conf
	cp /usr/local/share/examples/php4/php.ini-recommended /var/www/conf/php.ini
	/usr/local/sbin/phpxs -s
	/usr/local/sbin/phpxs -a curl
	/usr/local/sbin/phpxs -a dbx
	/usr/local/sbin/phpxs -a domxml
	/usr/local/sbin/phpxs -a filepro
	/usr/local/sbin/phpxs -a gmp
	/usr/local/sbin/phpxs -a imap
	/usr/local/sbin/phpxs -a mcrypt
	/usr/local/sbin/phpxs -a mhash
	/usr/local/sbin/phpxs -a mysql
	/usr/local/sbin/phpxs -a pdf
	/usr/local/sbin/phpxs -a shmop
	/usr/local/sbin/phpxs -a xmlrpc
	/usr/local/sbin/phpxs -a xslt
	/usr/local/sbin/postfix-enable
	apachectl restart
	mkimapdcert
	mkpop3dcert