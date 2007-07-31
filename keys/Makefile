
export

FILES = $(SYSTEM_ROOT)/gui/include/ispcp-db-keys.php $(SYSTEM_ROOT)/engine/ispcp-db-keys.pl $(SYSTEM_ROOT)/engine/messager/ispcp-db-keys.pl

install: $(FILES)
	perl rpl.pl $(FILES)

uninstall:

.PHONY: install uninstall