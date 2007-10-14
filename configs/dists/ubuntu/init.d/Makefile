#
#
#

export

install:

	$(SYSTEM_MAKE_DIRS) $(ROOT_CONF)/init.d
	$(SYSTEM_MAKE_DIRS) $(ROOT_CONF)/default
        
	cp ispcp_daemon $(ROOT_CONF)/init.d
	cp ispcp_network $(ROOT_CONF)/init.d
	[ -f ispcp_daemon.default ] && cp ispcp_daemon.default $(ROOT_CONF)/default/ispcp_daemon
	[ -f ispcp_network.default ] && cp ispcp_network.default $(ROOT_CONF)/default/ispcp_network

	chown root:root $(ROOT_CONF)/init.d/ispcp_network $(ROOT_CONF)/init.d/ispcp_daemon $(ROOT_CONF)/default/ispcp_daemon
	chmod 0755 $(ROOT_CONF)/init.d/ispcp_network $(ROOT_CONF)/init.d/ispcp_daemon $(ROOT_CONF)/default/ispcp_daemon
        
uninstall:

	rm -f $(ROOT_CONF)/init.d/ispcp_daemon
	rm -f $(ROOT_CONF)/init.d/ispcp_network
	rm -f $(ROOT_CONF)/default/ispcp_daemon
	rm -f $(ROOT_CONF)/default/ispcp_network

