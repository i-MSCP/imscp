RELEASE=`cat /etc/debian_version | awk '{print $1}'`

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/proftpd
	cp -R ./backup $(SYSTEM_CONF)/proftpd
	cp ./proftpd.conf $(SYSTEM_CONF)/proftpd

	# Debian 4.0 "Etch"?
	if [[ $(RELEASE) == '4.0' ]] ; then \
		$(SYSTEM_MAKE_DIRS) $(ROOT_CONF)/proftpd ; \
		cp ./proftpd3.conf $(ROOT_CONF)/proftpd ; \
	else \
		cp ./proftpd.conf $(ROOT_CONF) ; \
	fi \

uninstall:

	rm -rf $(SYSTEM_CONF)/proftpd
