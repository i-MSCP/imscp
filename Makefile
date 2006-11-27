
INST_PREF=/tmp/vhcs-2.4.8

HOST_OS=debian

ROOT_CONF=$(INST_PREF)/etc

SYSTEM_ROOT=$(INST_PREF)/var/www/vhcs2

SYSTEM_CONF=$(INST_PREF)/etc/vhcs2

SYSTEM_LOG=$(INST_PREF)/var/log/vhcs2

SYSTEM_APACHE_BACK_LOG=$(INST_PREF)/var/log/apache2/backup

SYSTEM_VIRTUAL=$(INST_PREF)/var/www/virtual

SYSTEM_FCGI=$(INST_PREF)/var/www/fcgi

SYSTEM_MAIL_VIRTUAL=$(INST_PREF)/var/mail/virtual

SYSTEM_MAKE_DIRS=/usr/sbin/vhcs2-mkdirs.pl

CODE_PHP=/usr/sbin/encode-file

export

install:

	cd ./tools && $(MAKE) install

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_ROOT)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_LOG)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_LOG)/vhcs2-arpl-msgr
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_VIRTUAL)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_FCGI)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_MAIL_VIRTUAL)
	$(SYSTEM_MAKE_DIRS) $(SYSTEM_APACHE_BACK_LOG)

	cd ./configs && $(MAKE) install
	cd ./engine && $(MAKE) install
	cd ./gui && $(MAKE) install
	cd ./keys && $(MAKE) install

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
