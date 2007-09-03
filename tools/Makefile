
export

DAEMON_DIR=$(SYSTEM_ROOT)/daemon


build: daemon/ispcp_daemon

daemon/ispcp_daemon:
	(cd ./daemon; \
	$(MAKE) ispcp_daemon;)

install: build
	$(SYSTEM_MAKE_DIRS) $(DAEMON_DIR)
	cp ./daemon/ispcp_daemon $(DAEMON_DIR)

uninstall:

	(cd ./daemon; \
	$(MAKE) clean; )
	rm -rf $(DAEMON_DIR)
	rm -rf build

.PHONY: install uninstall
