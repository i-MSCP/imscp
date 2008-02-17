
export

install:

	if test $(HOST_OS) = debian  ; then \
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
	elif test $(HOST_OS) = centos ; then \
		cd ./dists/centos && $(MAKE) install ; \
	elif test $(HOST_OS) = fedora ; then \
		cd ./dists/fedora && $(MAKE) install ; \
	elif test $(HOST_OS) = gentoo ; then \
		cd ./dists/gentoo && $(MAKE) install ; \
	elif test $(HOST_OS) = redhat ; then \
		cd ./dists/redhat && $(MAKE) install ; \
	elif test $(HOST_OS) = suse10 ; then \
		cd ./dists/suse10 && $(MAKE) install ; \
	elif test $(HOST_OS) = opensuse10 ; then \
		cd ./dists/opensuse10 && $(MAKE) install ; \
	elif test $(HOST_OS) = ubuntu ; then \
		cd ./dists/ubuntu && $(MAKE) install ; \
	fi

uninstall:

	if test $(HOST_OS) = debian ; then \
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
	elif test $(HOST_OS) = centos ; then \
		cd ./dists/centos && $(MAKE) uninstall ; \
	elif test $(HOST_OS) = fedora ; then \
		cd ./dists/fedora && $(MAKE) uninstall ; \
	elif test $(HOST_OS) = gentoo ; then \
		cd ./dists/gentoo && $(MAKE) uninstall ; \
	elif test $(HOST_OS) = redhat ; then \
		cd ./dists/redhat && $(MAKE) uninstall ; \
	elif test $(HOST_OS) = suse10 ; then \
		cd ./dists/suse10 && $(MAKE) uninstall ; \
	elif test $(HOST_OS) = opensuse10 ; then \
		cd ./dists/opensuse10 && $(MAKE) uninstall ; \
	elif test $(HOST_OS) = ubuntu ; then \
		cd ./dists/ubuntu && $(MAKE) uninstall ; \
	fi
