
export

DAEMON_DIR=$(SYSTEM_ROOT)/daemon

install:
	cd ./build && $(MAKE) install 
	cd ./daemon && $(MAKE) vhcs2_daemon
	$(SYSTEM_MAKE_DIRS) $(DAEMON_DIR)
	cp ./daemon/vhcs2_daemon $(DAEMON_DIR) 

uninstall:

	cd ./build && $(MAKE) uninstall 
	cd ./daemon && $(MAKE) uninstall
	rm -rf $(DAEMON_DIR)

