export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/crontab
	cp ./crontab.conf ./working
	cp ./crontab.conf $(SYSTEM_CONF)/crontab
	cp -R ./backup $(SYSTEM_CONF)/crontab
	cp -R ./parts $(SYSTEM_CONF)/crontab
	cp -R ./working $(SYSTEM_CONF)/crontab

uninstall:

	rm -rf $(SYSTEM_CONF)/crontab
