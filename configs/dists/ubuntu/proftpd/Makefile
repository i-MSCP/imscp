RELEASE=`cat /etc/lsb-release | awk '{print $1}'`

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/proftpd
	cp -R ./backup $(SYSTEM_CONF)/proftpd

	# if: Ubuntu 6.06 (dapper)
	# else: Ubuntu >= 6.10
	if test $(RELEASE) = 'dapper' ; then \
		cp ./proftpd.conf $(ROOT_CONF) ; \
		cp ./proftpd.conf $(SYSTEM_CONF)/proftpd ; \
	else \
		if test -e /etc/proftpd.conf; then \
			mv /etc/proftpd.conf /etc/proftpd.conf.bak ; \
		fi ; \
		$(SYSTEM_MAKE_DIRS) $(ROOT_CONF)/proftpd ; \
		cp ./proftpd1.3.conf $(SYSTEM_CONF)/proftpd/proftpd.conf ; \
		cp ./proftpd1.3.conf $(ROOT_CONF)/proftpd/proftpd.conf ; \
	fi \

uninstall:

	rm -rf $(SYSTEM_CONF)/proftpd
