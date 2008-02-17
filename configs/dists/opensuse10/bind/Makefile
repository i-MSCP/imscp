export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/bind
	cp ./named.conf ./working
	cp ./named.conf $(SYSTEM_CONF)/bind
	cp -R ./backup $(SYSTEM_CONF)/bind
	cp -R ./parts $(SYSTEM_CONF)/bind
	cp -R ./working $(SYSTEM_CONF)/bind

uninstall:

	rm -rf $(SYSTEM_CONF)/bind

