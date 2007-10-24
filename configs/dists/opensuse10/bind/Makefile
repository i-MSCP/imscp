export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/named.d
	cp ./named.conf ./working
	cp ./named.conf $(SYSTEM_CONF)/named.d
	cp -R ./backup $(SYSTEM_CONF)/named.d
	cp -R ./parts $(SYSTEM_CONF)/named.d
	cp -R ./working $(SYSTEM_CONF)/named.d

uninstall:

	rm -rf $(SYSTEM_CONF)/named.d

