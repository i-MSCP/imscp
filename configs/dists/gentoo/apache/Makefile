ROOT_APACHE_CONF=$(ROOT_CONF)/apache2

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/apache
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)/sites-available
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)/mods-available
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)/ispcp

	cp ./httpd.conf ./working/ispcp.conf
	cp ./fastcgi.conf ./working/fastcgi_ispcp.conf
	cp ./httpd.conf $(SYSTEM_CONF)/apache
	cp ./00_master.conf $(SYSTEM_CONF)/apache
	cp ./01_awstats.conf $(SYSTEM_CONF)/apache
	cp -R ./backup $(SYSTEM_CONF)/apache
	cp -R ./parts $(SYSTEM_CONF)/apache
	cp -R ./working $(SYSTEM_CONF)/apache

	cp -R ./httpd.conf $(ROOT_APACHE_CONF)/vhost.d/ispcp.conf
	cp -R ./fastcgi.conf $(ROOT_APACHE_CONF)/modules.d/21-mod-fastcgi_ispcp.conf

uninstall:

	rm -rf $(SYSTEM_CONF)/apache
	rm -rf $(ROOT_APACHE_CONF)/ispcp.conf
	rm -rf $(ROOT_APACHE_CONF)/modules.d/21-mod-fastcgi_ispcp.conf
