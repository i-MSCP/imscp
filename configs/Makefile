
export

install:

	if [[ $(HOST_OS) == debian ]] ; then \
		cp ./vhcs2.conf $(SYSTEM_CONF) ; \
		cd ./apache && $(MAKE) install ; cd .. ; \
		cd ./bind && $(MAKE) install ; cd .. ; \
		cd ./crontab && $(MAKE) install ; cd .. ; \
		cd ./cron.d && $(MAKE) install ; cd .. ; \
		cd ./database && $(MAKE) install ; cd .. ;  \
		cd ./init.d && $(MAKE) install ; cd .. ; \
		cd ./postfix && $(MAKE) install ; cd .. ; \
		cd ./courier && $(MAKE) install ; cd .. ; \
		cd ./proftpd && $(MAKE) install ; cd .. ; \
		cd ./logrotate && $(MAKE) install ; cd .. ; \
	elif [[ $(HOST_OS) == suse100 ]] ; then \
		cd ./dists/suse100 && $(MAKE) install ; \
	elif [[ $(HOST_OS) == suse93 ]] ; then \
		cd ./dists/suse93 && $(MAKE) install ; \
	elif [[ $(HOST_OS) == fedora ]] ; then \
		cd ./dists/fedora && $(MAKE) install ; \
	elif [[ $(HOST_OS) == sles9 ]] ; then \
		cd ./dists/sles9 && $(MAKE) install ; \
	fi

uninstall:

	if [[ $(HOST_OS) == debian ]] ; then \
		rm -rf $(SYSTEM_CONF)/vhcs2.conf ;  \
		cd ./apache && $(MAKE) uninstall ; cd .. ; \
		cd ./bind && $(MAKE) uninstall ; cd .. ; \
		cd ./crontab && $(MAKE) uninstall ; cd .. ; \
		cd ./cron.d && $(MAKE) uninstall ; cd .. ; \
		cd ./database && $(MAKE) uninstall ; cd .. ;  \
		cd ./init.d && $(MAKE) uninstall ; cd .. ; \
		cd ./postfix && $(MAKE) uninstall ; cd .. ; \
		cd ./courier && $(MAKE) uninstall ; cd .. ; \
		cd ./proftpd && $(MAKE) uninstall ; cd .. ; \
		cd ./logrotate && $(MAKE) uninstall ; cd .. ; \
	elif [[ $(HOST_OS) == suse ]] ; then \
		cd ./dists/suse && $(MAKE) uninstall ; \
	elif [[ $(HOST_OS) == fedora ]] ; then \
		cd ./dists/fedora && $(MAKE) uninstall ; \
	elif [[ $(HOST_OS) == sles9 ]] ; then \
		cd ./dists/sles9 && $(MAKE) uninstall ; \
	fi

