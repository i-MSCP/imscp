
export

install:

	if test $(HOST_OS) = fedora ; then \
		cp ./ispcp.conf $(SYSTEM_CONF) ; \
		(cd ./apache && $(MAKE) install &); \
		(cd ./awstats && $(MAKE) install &); \
		(cd ./bind && $(MAKE) install &); \
		(cd ./cron.d && $(MAKE) install &); \
		(cd ./database && $(MAKE) install &);  \
		(cd ./fcgi && $(MAKE) install &); \
		(cd ./init.d && $(MAKE) install &); \
		(cd ./postfix && $(MAKE) install &); \
		(cd ./courier && $(MAKE) install &); \
		(cd ./proftpd && $(MAKE) install &); \
		(cd ./logrotate && $(MAKE) install); 
	fi

uninstall:

	if test $(HOST_OS) = fedora ; then \
		rm -rf $(SYSTEM_CONF)/ispcp.conf ;  \
		(cd ./apache && $(MAKE) uninstall &); \
		(cd ./awstats && $(MAKE) uninstall &); \
		(cd ./bind && $(MAKE) uninstall &); \
		(cd ./cron.d && $(MAKE) uninstall &); \
		(cd ./database && $(MAKE) uninstall &); \
		(cd ./init.d && $(MAKE) uninstall &); \
		(cd ./postfix && $(MAKE) uninstall &); \
		(cd ./courier && $(MAKE) uninstall &); \
		(cd ./proftpd && $(MAKE) uninstall &); \
		(cd ./logrotate && $(MAKE) uninstall &); \

	fi
