
SYSTEM_ENGINE_ROOT=$(SYSTEM_ROOT)/engine

CMD_INSTALL=/usr/bin/install -m 0700 -o root -g root

CMD_CHOWN=/bin/chown

export

install:

	$(SYSTEM_MAKE_DIRS) $(INST_PREF)/usr/sbin
	$(CMD_INSTALL) ./maillogconvert/maillogconvert.pl $(INST_PREF)/usr/sbin
        
uninstall:

	rm -rf $(INST_PREF)/usr/sbin/maillogconvert.pl
