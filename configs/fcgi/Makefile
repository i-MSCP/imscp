export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/fcgi
	cp ./parts/php4-fcgi-starter.tpl $(SYSTEM_CONF)/fcgi
	cp ./parts/php5-fcgi-starter.tpl $(SYSTEM_CONF)/fcgi
	cp ./parts/php4.ini $(SYSTEM_CONF)/fcgi
	cp ./parts/php5.ini $(SYSTEM_CONF)/fcgi
	cp -R ./backup $(SYSTEM_CONF)/fcgi
	cp -R ./parts $(SYSTEM_CONF)/fcgi
	cp -R ./working $(SYSTEM_CONF)/fcgi

uninstall:

	rm -rf $(SYSTEM_CONF)/fcgi

