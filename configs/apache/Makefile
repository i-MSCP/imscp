
ROOT_APACHE_CONF=$(ROOT_CONF)/apache2/sites-available

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/apache
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)

	cp ./httpd.conf ./working/vhcs2.conf

	cp ./httpd.conf $(SYSTEM_CONF)/apache
	cp -R ./backup $(SYSTEM_CONF)/apache
	cp -R ./parts $(SYSTEM_CONF)/apache
	cp -R ./working $(SYSTEM_CONF)/apache

	cp -R ./httpd.conf $(ROOT_APACHE_CONF)/vhcs2.conf

uninstall:

	rm -rf $(SYSTEM_CONF)/apache
	rm -rf $(ROOT_APACHE_CONF)/vhcs2.conf

