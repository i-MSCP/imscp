
export

install:

	if [ $(HOST_OS) == debian ] ; then \
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
		(cd ./logrotate && $(MAKE) install); \
	elif [ $(HOST_OS) == fedora ] ; then \
		cd ./dists/fedora && $(MAKE) install ; \
	elif [ $(HOST_OS) == gentoo ] ; then \
		cd ./dists/gentoo && $(MAKE) install ; \
	elif [ $(HOST_OS) == redhat ] ; then \
		cd ./dists/redhat && $(MAKE) install ; \
	elif [ $(HOST_OS) == sles9 ] ; then \
		cd ./dists/sles9 && $(MAKE) install ; \
	elif [ $(HOST_OS) == suse100 ] ; then \
		cd ./dists/suse100 && $(MAKE) install ; \
	elif [ $(HOST_OS) == suse93 ] ; then \
		cd ./dists/suse93 && $(MAKE) install ; \
	elif [ $(HOST_OS) == ubuntu ] ; then \
		cd ./dists/ubuntu && $(MAKE) install ; \
	fi

uninstall:

	if [ $(HOST_OS) == debian ] ; then \
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
	elif [ $(HOST_OS) == fedora ] ; then \
		cd ./dists/fedora && $(MAKE) uninstall ; \
	elif [ $(HOST_OS) == gentoo ] ; then \
		cd ./dists/gentoo && $(MAKE) uninstall ; \
	elif [ $(HOST_OS) == redhat ] ; then \
		cd ./dists/redhat && $(MAKE) uninstall ; \
	elif [ $(HOST_OS) == sles9 ] ; then \
		cd ./dists/sles9 && $(MAKE) uninstall ; \
	elif [ $(HOST_OS) == suse93 ] ; then \
		cd ./dists/suse93 && $(MAKE) uninstall ; \
	elif [ $(HOST_OS) == suse100 ] ; then \
		cd ./dists/suse100 && $(MAKE) uninstall ; \
	elif [ $(HOST_OS) == ubuntu ] ; then \
		cd ./dists/ubuntu && $(MAKE) uninstall ; \
	fi

