ROOT_APACHE_CONF=$(ROOT_CONF)/apache2

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/apache
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)/sites-available
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)/mods-available
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)/ispcp

	cp ./httpd.conf $(SYSTEM_CONF)/apache
	cp ./00_master.conf $(SYSTEM_CONF)/apache
	cp ./01_awstats.conf $(SYSTEM_CONF)/apache
	cp -R ./backup $(SYSTEM_CONF)/apache
	cp -R ./parts $(SYSTEM_CONF)/apache
	cp -R ./working $(SYSTEM_CONF)/apache
	cp ./httpd.conf $(SYSTEM_CONF)/apache/working/ispcp.conf
	cp ./httpd.conf $(SYSTEM_CONF)/apache/parts/ispcp_base.tpl
	cp -R ./working/fastcgi_ispcp.conf ${ROOT_APACHE_CONF}/mods-available/fastcgi_ispcp.conf

uninstall:

	rm -rf $(SYSTEM_CONF)/apache
	rm -rf $(APACHE_SITES_DIR)/ispcp.conf
	rm -rf $(APACHE_SITES_DIR)/00_master.conf
	rm -rf $(APACHE_SITES_DIR)/01_awstats.conf
	rm -rf $(APACHE_MODS_DIR)/fastcgi_ispcp.conf
