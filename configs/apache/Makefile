ROOT_APACHE_CONF=$(ROOT_CONF)/apache2

RELEASE=`cat /etc/debian_version | awk '{print $1}'`

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/apache
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)/sites-available
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)/mods-available

	cp ./httpd.conf $(SYSTEM_CONF)/apache
	cp ./00_master.conf $(SYSTEM_CONF)/apache
	cp ./01_awstats.conf $(SYSTEM_CONF)/apache
	cp -R ./backup $(SYSTEM_CONF)/apache
	cp -R ./parts $(SYSTEM_CONF)/apache
	cp -R ./working $(SYSTEM_CONF)/apache

	cp ./httpd.conf $(SYSTEM_CONF)/apache/working/ispcp.conf
	cp ./httpd.conf $(SYSTEM_CONF)/apache/parts/ispcp_base.tpl

	# Debian 4.0 "Etch"?
	if [[ $(RELEASE) = '4.0' ]] || [[ $(RELEASE) = 'lenny/sid' ]]; then \
		cp ./fastcgi2.conf $(SYSTEM_CONF)/apache/working/fastcgi_ispcp.conf ; \
	else \
		cp ./fastcgi.conf $(SYSTEM_CONF)/apache/working/fastcgi_ispcp.conf ; \
	fi

	cp -R $(SYSTEM_CONF)/apache/working/fastcgi_ispcp.conf $(ROOT_APACHE_CONF)/mods-available/fastcgi_ispcp.conf

uninstall:

	rm -rf $(SYSTEM_CONF)/apache
	rm -rf $(ROOT_APACHE_CONF)/ispcp.conf
	rm -rf ${ROOT_APACHE_CONF}/mods-available/fastcgi_ispcp.conf
