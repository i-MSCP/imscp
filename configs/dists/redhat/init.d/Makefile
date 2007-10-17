#
#
#

export

install:

	$(SYSTEM_MAKE_DIRS) $(ROOT_CONF)/init.d
	$(SYSTEM_MAKE_DIRS) $(ROOT_CONF)/default

	cp ispcp_daemon $(ROOT_CONF)/init.d
	cp ispcp_network $(ROOT_CONF)/init.d

	chown root:root $(ROOT_CONF)/init.d/ispcp_network $(ROOT_CONF)/init.d/ispcp_daemon
	chmod 0755 $(ROOT_CONF)/init.d/ispcp_network $(ROOT_CONF)/init.d/ispcp_daemon

    if [[ -f ispcp_daemon.default ]]; then \
		cp -p ispcp_daemon.default $(ROOT_CONF)/default/ispcp_daemon ; \
		chown root:root $(ROOT_CONF)/default/ispcp_daemon; 
		chmod 0755 $(ROOT_CONF)/default/ispcp_daemon;
	fi
	if [[ -f ispcp_network.default ]]; then \
		cp -p ispcp_network.default $(ROOT_CONF)/default/ispcp_network ; \
		chown root:root $(ROOT_CONF)/default/ispcp_network; 
	 	chmod 0755 $(ROOT_CONF)/default/ispcp_network;
	fi

uninstall:

	rm -f $(ROOT_CONF)/init.d/ispcp_daemon
	rm -f $(ROOT_CONF)/init.d/ispcp_network
	rm -f $(ROOT_CONF)/default/ispcp_daemon
	rm -f $(ROOT_CONF)/default/ispcp_network

