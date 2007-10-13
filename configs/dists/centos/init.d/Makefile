#
#
#

export

install:

	$(SYSTEM_MAKE_DIRS) $(ROOT_CONF)/init.d
        
	cp ispcp_daemon $(ROOT_CONF)/init.d
	cp ispcp_network $(ROOT_CONF)/init.d

	chown root:root $(ROOT_CONF)/init.d/ispcp_network $(ROOT_CONF)/init.d/ispcp_daemon
	chmod 0755 $(ROOT_CONF)/init.d/ispcp_network $(ROOT_CONF)/init.d/ispcp_daemon
        
uninstall:

	rm -f $(ROOT_CONF)/init.d/ispcp_daemon
	rm -f $(ROOT_CONF)/init.d/ispcp_network

