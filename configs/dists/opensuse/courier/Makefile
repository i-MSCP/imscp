
ROOT_COURIER_CONF=$(ROOT_CONF)/courier
ROOT_AUTHLIB_CONF=$(ROOT_CONF)/authlib
export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/courier
	$(SYSTEM_MAKE_DIRS) $(ROOT_COURIER_CONF)
	$(SYSTEM_MAKE_DIRS) $(ROOT_AUTHLIB_CONF)

#	MTA Configuration Files;
	cp ./userdb $(SYSTEM_CONF)/courier

#	MTA Configuration Directories;
	cp -R ./backup $(SYSTEM_CONF)/courier
	cp -R ./working $(SYSTEM_CONF)/courier
	cp ./userdb $(ROOT_AUTHLIB_CONF)

uninstall:

	rm -rf $(SYSTEM_CONF)/courier
