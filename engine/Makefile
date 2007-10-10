
SYSTEM_ENGINE_ROOT=$(SYSTEM_ROOT)/engine

CMD_CHOWN=/bin/chown
CMD_RM=/bin/rm -rf

CMD_INSTALL=/usr/bin/install -m 0700 -o root -g root
CMD_MAIL_INSTALL=/usr/bin/install -m 0700 -o vmail -g mail

CMD_DIR_INSTALL=/usr/bin/install -d -o root -g root

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_ENGINE_ROOT)
	$(CMD_DIR_INSTALL) -m 0755 $(SYSTEM_ENGINE_ROOT)

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_ENGINE_ROOT)/awstats
	$(CMD_DIR_INSTALL) -m 0700 $(SYSTEM_ENGINE_ROOT)/awstats

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_ENGINE_ROOT)/backup
	$(CMD_DIR_INSTALL) -m 0700 $(SYSTEM_ENGINE_ROOT)/backup

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_ENGINE_ROOT)/quota
	$(CMD_DIR_INSTALL) -m 0700 $(SYSTEM_ENGINE_ROOT)/quota

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_ENGINE_ROOT)/traffic
	$(CMD_DIR_INSTALL) -m 0700 $(SYSTEM_ENGINE_ROOT)/traffic

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_ENGINE_ROOT)/messager
	$(CMD_DIR_INSTALL) -m 0755 $(SYSTEM_ENGINE_ROOT)/messager

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_ENGINE_ROOT)/setup
	$(CMD_DIR_INSTALL) -m 0700 $(SYSTEM_ENGINE_ROOT)/setup

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_ENGINE_ROOT)/tools
	$(CMD_DIR_INSTALL) -m 0700 $(SYSTEM_ENGINE_ROOT)/tools

	cd ./traffic && $(MAKE) install

	$(CMD_INSTALL) ./ispcp_common_code.pl $(SYSTEM_ENGINE_ROOT)

	$(CMD_INSTALL) ./ispcp_common_methods.pl $(SYSTEM_ENGINE_ROOT)

	# keys needed for ISPCP engine;
	$(CMD_INSTALL) ./ispcp-db-keys.pl $(SYSTEM_ENGINE_ROOT)

	# one more copy for auto replay system;
	$(CMD_INSTALL) ./ispcp-db-keys.pl $(SYSTEM_ENGINE_ROOT)/messager

	$(CMD_INSTALL) ./ispcp-rqst-mngr $(SYSTEM_ENGINE_ROOT)
	$(CMD_INSTALL) ./ispcp-dmn-mngr $(SYSTEM_ENGINE_ROOT)
	$(CMD_INSTALL) ./ispcp-sub-mngr $(SYSTEM_ENGINE_ROOT)
	$(CMD_INSTALL) ./ispcp-als-mngr $(SYSTEM_ENGINE_ROOT)
	$(CMD_INSTALL) ./ispcp-htuser-mngr $(SYSTEM_ENGINE_ROOT)
	$(CMD_INSTALL) ./ispcp-mbox-mngr $(SYSTEM_ENGINE_ROOT)
	$(CMD_INSTALL) ./ispcp-serv-mngr $(SYSTEM_ENGINE_ROOT)
	$(CMD_INSTALL) ./ispcp-db-passwd $(SYSTEM_ENGINE_ROOT)

	$(CMD_INSTALL) ./backup/ispcp-bk-task $(SYSTEM_ENGINE_ROOT)/backup
	$(CMD_INSTALL) ./backup/ispcp-backup-all $(SYSTEM_ENGINE_ROOT)/backup

	$(CMD_INSTALL) ./quota/ispcp-dsk-quota $(SYSTEM_ENGINE_ROOT)/quota

	$(CMD_INSTALL) ./messager/ispcp-arpl-msgr $(SYSTEM_ENGINE_ROOT)/messager

	$(CMD_INSTALL) ./setup/set-engine-permissions.sh $(SYSTEM_ENGINE_ROOT)/setup
	$(CMD_INSTALL) ./setup/set-gui-permissions.sh $(SYSTEM_ENGINE_ROOT)/setup
	$(CMD_INSTALL) ./setup/reset-setup $(SYSTEM_ENGINE_ROOT)/setup
	$(CMD_INSTALL) ./setup/ispcp-setup $(SYSTEM_ENGINE_ROOT)/setup
	$(CMD_INSTALL) ./setup/ispcp-uninstall $(SYSTEM_ENGINE_ROOT)/setup
	$(CMD_INSTALL) ./setup/vhcs2ispcp.pl $(SYSTEM_ENGINE_ROOT)/setup
	$(CMD_INSTALL) ./setup/vhcs2ispcp.sql $(SYSTEM_ENGINE_ROOT)/setup
	$(CMD_INSTALL) ./setup/vhcs2-remover.pl $(SYSTEM_ENGINE_ROOT)/setup
	$(CMD_INSTALL) ./setup/copy-errordocs.sh $(SYSTEM_ENGINE_ROOT)/setup

	$(CMD_INSTALL) ./tools/ispcp-httpd-logs-mngr $(SYSTEM_ENGINE_ROOT)/tools/ispcp-httpd-logs-mngr
	$(CMD_INSTALL) ./tools/ispcpphptemp.sh $(SYSTEM_ENGINE_ROOT)/tools/ispcpphptemp.sh

	$(CMD_INSTALL) ./traffic/ispcp-srv-traff $(SYSTEM_ENGINE_ROOT)/traffic
	$(CMD_INSTALL) ./traffic/ispcp-vrl-traff $(SYSTEM_ENGINE_ROOT)/traffic
	$(CMD_INSTALL) ./traffic/ispcp-vrl-traff-SUSE $(SYSTEM_ENGINE_ROOT)/traffic
	$(CMD_INSTALL) ./traffic/ispcp-vrl-traff-correction $(SYSTEM_ENGINE_ROOT)/traffic

uninstall:

	rm -rf $(SYSTEM_ENGINE_ROOT)
	rm -rf ./*~

