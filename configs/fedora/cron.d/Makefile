export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/cron.d
	cp ./ispcp $(SYSTEM_CONF)/cron.d
	cp -R ./backup $(SYSTEM_CONF)/cron.d
	cp -R ./parts $(SYSTEM_CONF)/cron.d
	cp -R ./working $(SYSTEM_CONF)/cron.d
	cp $(SYSTEM_CONF)/cron.d/ispcp $(SYSTEM_CONF)/cron.d/working

uninstall:

	rm -rf $(ROOT_CONF)/cron.d
