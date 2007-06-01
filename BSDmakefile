
.include <Makefile.inc>

install:
	groupadd -o -g 0 root
	ln -s /usr/local/libexec/makedatprog /usr/local/bin/makedatprog
	cd ./tools && $(MAKE) install
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_ROOT)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_LOG)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_LOG)/ispcp-arpl-msgr
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_VIRTUAL)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_FCGI)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_MAIL_VIRTUAL)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_APACHE_BACK_LOG)
	cd ./configs && $(MAKE) install
	cd ./engine && $(MAKE) install
	cd ./gui && $(MAKE) install
	cd ./keys && $(MAKE) install
	cd ${INST_PREF} && cp -R * /
	rm -rf ${INST_PREF}
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

uninstall:
	cd ./tools && $(MAKE) uninstall
	cd ./configs && $(MAKE) uninstall
	cd ./engine && $(MAKE) uninstall
	cd ./gui && $(MAKE) uninstall
	cd ./keys && $(MAKE) uninstall
	rm -rf $(SYSTEM_CONF)
	rm -rf $(SYSTEM_ROOT)
	rm -rf $(SYSTEM_LOG)
	rm -rf $(SYSTEM_VIRTUAL)
	rm -rf $(SYSTEM_FCGI)
	rm -rf $(SYSTEM_MAIL_VIRTUAL)
	rm -rf $(SYSTEM_APACHE_BACK_LOG)
	rm -rf ./*~
