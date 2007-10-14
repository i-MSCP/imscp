
export

KEYS_DIR=$(SYSTEM_ROOT)/keys

install:
	$(SYSTEM_MAKE_DIRS) $(KEYS_DIR)
	cp ./rpl.pl $(KEYS_DIR)

uninstall:
	$(RM) $(KEYS_DIR)/rpl.pl $(KEYS_DIR)

.PHONY: install uninstall
