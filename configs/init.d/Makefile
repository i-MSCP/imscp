#
#
#

export

install:

	$(SYSTEM_MAKE_DIRS) $(ROOT_CONF)/init.d
        
	cp vhcs2_daemon $(ROOT_CONF)/init.d
	cp vhcs2_network $(ROOT_CONF)/init.d

	chown root:root $(ROOT_CONF)/init.d/vhcs2_network $(ROOT_CONF)/init.d/vhcs2_daemon
	chmod 0755 $(ROOT_CONF)/init.d/vhcs2_network $(ROOT_CONF)/init.d/vhcs2_daemon
        
uninstall:

	rm -f $(ROOT_CONF)/init.d/vhcs2_daemon
	rm -f $(ROOT_CONF)/init.d/vhcs2_network

