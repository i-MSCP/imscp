export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/cron.d
	cp ./ispcp ./working
	cp ./ispcp.phptemp ./working
	cp ./ispcp $(SYSTEM_CONF)/cron.d
	cp ./ispcp.phptemp $(SYSTEM_CONF)/cron.d
	cp -R ./backup $(SYSTEM_CONF)/cron.d
	cp -R ./parts $(SYSTEM_CONF)/cron.d
	cp -R ./working $(SYSTEM_CONF)/cron.d

uninstall:

	rm -rf $(ROOT_CONF)/cron.d
