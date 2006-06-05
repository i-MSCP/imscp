export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/proftpd
	cp -R ./backup $(SYSTEM_CONF)/proftpd
	cp ./proftpd.conf $(SYSTEM_CONF)/proftpd
	cp ./proftpd.conf $(ROOT_CONF)

uninstall:

	rm -rf $(SYSTEM_CONF)/proftpd
