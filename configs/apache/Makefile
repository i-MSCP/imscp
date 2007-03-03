ROOT_APACHE_CONF=$(ROOT_CONF)/apache2

RELEASE=`cat /etc/debian_version | awk '{print $1}'`

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/apache
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)/sites-available
	$(SYSTEM_MAKE_DIRS) $(ROOT_APACHE_CONF)/mods-available

	cp ./httpd.conf ./working/vhcs2.conf
	# Debian 4.0 "Etch"?
	if [[ $(RELEASE) == '4.0' ]] ; then \
		cp ./fastcgi2.conf ./working/fastcgi.conf \
	else \
		cp ./fastcgi.conf ./working/fastcgi.conf \
	fi
	cp ./httpd.conf $(SYSTEM_CONF)/apache
	cp -R ./backup $(SYSTEM_CONF)/apache
	cp -R ./parts $(SYSTEM_CONF)/apache
	cp -R ./working $(SYSTEM_CONF)/apache

	cp -R ./httpd.conf $(ROOT_APACHE_CONF)/sites-available/vhcs2.conf
	cp -R ./fastcgi.conf ${ROOT_APACHE_CONF}/mods-available/fastcgi.conf

uninstall:

	rm -rf $(SYSTEM_CONF)/apache
	rm -rf $(ROOT_APACHE_CONF)/vhcs2.conf
