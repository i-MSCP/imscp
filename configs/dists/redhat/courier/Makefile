
ROOT_COURIER_CONF=$(ROOT_CONF)/courier

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/courier
	$(SYSTEM_MAKE_DIRS) $(ROOT_COURIER_CONF)
#
#	MTA Configuration Files;
#
	cp ./authdaemonrc $(SYSTEM_CONF)/courier
	cp ./authmodulelist $(SYSTEM_CONF)/courier
	cp ./imapd $(SYSTEM_CONF)/courier
	cp ./pop3d $(SYSTEM_CONF)/courier
	cp ./userdb $(SYSTEM_CONF)/courier
	
#	cp -p -R ./courier-cfg-setup.sh $(SYSTEM_CONF)/courier
#
#	MTA Configuration Directories;
#
	cp -R ./backup $(SYSTEM_CONF)/courier
	cp -R ./working $(SYSTEM_CONF)/courier

	cp ./authdaemonrc $(ROOT_COURIER_CONF)
	cp ./authmodulelist $(ROOT_COURIER_CONF)
	cp ./imapd $(ROOT_COURIER_CONF)
	cp ./pop3d $(ROOT_COURIER_CONF)
	cp ./userdb $(ROOT_COURIER_CONF)

uninstall:

	rm -rf $(SYSTEM_CONF)/courier


