
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

	$(CMD_INSTALL) ./vhcs2_common_code.pl $(SYSTEM_ENGINE_ROOT)

	# keys needed for VHCS engine;
	$(CMD_INSTALL) ./vhcs2-db-keys.pl $(SYSTEM_ENGINE_ROOT)
	
	# one more copy for auto replay system;
	$(CMD_INSTALL) ./vhcs2-db-keys.pl $(SYSTEM_ENGINE_ROOT)/messager

	$(CMD_INSTALL) ./vhcs2-rqst-mngr $(SYSTEM_ENGINE_ROOT)
	$(CMD_INSTALL) ./vhcs2-dmn-mngr $(SYSTEM_ENGINE_ROOT)
	$(CMD_INSTALL) ./vhcs2-sub-mngr $(SYSTEM_ENGINE_ROOT)
	$(CMD_INSTALL) ./vhcs2-als-mngr $(SYSTEM_ENGINE_ROOT)
	$(CMD_INSTALL) ./vhcs2-htuser-mngr $(SYSTEM_ENGINE_ROOT)
	$(CMD_INSTALL) ./vhcs2-mbox-mngr $(SYSTEM_ENGINE_ROOT)
	$(CMD_INSTALL) ./vhcs2-serv-mngr $(SYSTEM_ENGINE_ROOT)
	$(CMD_INSTALL) ./vhcs2-db-passwd $(SYSTEM_ENGINE_ROOT)
	
	$(CMD_INSTALL) ./backup/vhcs2-bk-task $(SYSTEM_ENGINE_ROOT)/backup
	$(CMD_INSTALL) ./backup/vhcs2-backup-all $(SYSTEM_ENGINE_ROOT)/backup
	$(CMD_INSTALL) ./quota/vhcs2-dsk-quota $(SYSTEM_ENGINE_ROOT)/quota
	$(CMD_INSTALL) ./traffic/vhcs2-srv-traff $(SYSTEM_ENGINE_ROOT)/traffic
	$(CMD_INSTALL) ./traffic/vhcs2-vrl-traff $(SYSTEM_ENGINE_ROOT)/traffic
	$(CMD_INSTALL) ./traffic/vhcs2-vrl-traff-SUSE $(SYSTEM_ENGINE_ROOT)/traffic
	$(CMD_INSTALL) ./messager/vhcs2-arpl-msgr $(SYSTEM_ENGINE_ROOT)/messager
	
	$(CMD_INSTALL) ./setup/set-engine-permissions.sh $(SYSTEM_ENGINE_ROOT)/setup
	$(CMD_INSTALL) ./setup/set-gui-permissions.sh $(SYSTEM_ENGINE_ROOT)/setup
	$(CMD_INSTALL) ./setup/vhcs2-setup $(SYSTEM_ENGINE_ROOT)/setup
	$(CMD_INSTALL) ./setup/vhcs2-uninstall $(SYSTEM_ENGINE_ROOT)/setup
	$(CMD_INSTALL) ./setup/copy-errordocs.sh $(SYSTEM_ENGINE_ROOT)/setup
	$(CMD_INSTALL) ./tools/vhcs2-httpd-logs-mngr $(SYSTEM_ENGINE_ROOT)/tools/vhcs2-httpd-logs-mngr
	$(CMD_INSTALL) ./tools/vhcs2phptemp.sh $(SYSTEM_ENGINE_ROOT)/tools/vhcs2phptemp.sh

uninstall:

	rm -rf $(SYSTEM_ENGINE_ROOT)
	rm -rf ./*~
	
