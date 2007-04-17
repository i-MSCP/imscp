RELEASE=`cat /etc/debian_version | awk '{print $1}'`

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/proftpd
	cp -R ./backup $(SYSTEM_CONF)/proftpd

	# Ubuntu 7.04?
	if [[ $(RELEASE) == 'testing/unstable' ]] ; then \
		if [[ -e /etc/proftpd.conf ]] ; then \
			mv /etc/proftpd.conf /etc/proftpd.conf.bak ; \
		fi ; \
		$(SYSTEM_MAKE_DIRS) $(ROOT_CONF)/proftpd ; \
		cp ./proftpd1.3.conf $(SYSTEM_CONF)/proftpd/proftpd.conf ; \
		cp ./proftpd1.3.conf $(ROOT_CONF)/proftpd/proftpd.conf ; \
	else \
		cp ./proftpd.conf $(ROOT_CONF) ; \
		cp ./proftpd.conf $(SYSTEM_CONF)/proftpd ; \
	fi \

uninstall:

	rm -rf $(SYSTEM_CONF)/proftpd
