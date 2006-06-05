
ROOT_POSTFIX_CONF=$(ROOT_CONF)/postfix

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_CONF)/postfix
	$(SYSTEM_MAKE_DIRS) $(ROOT_POSTFIX_CONF)
#
#       MTA Configuration Files;
#
	cp ./main.cf $(SYSTEM_CONF)/postfix/main.cf
	cp ./master.cf $(SYSTEM_CONF)/postfix/master.cf
#	cp ./mta-cfg-setup.sh $(SYSTEM_CONF)/postfix

#
#       MTA Configuration Directories;
#
	cp -R ./backup $(SYSTEM_CONF)/postfix
	cp -R ./parts $(SYSTEM_CONF)/postfix
	cp -R ./working $(SYSTEM_CONF)/postfix
	cp -R ./vhcs2 $(SYSTEM_CONF)/postfix
	
	cp -R ./vhcs2 $(ROOT_POSTFIX_CONF)
	cp ./main.cf $(ROOT_POSTFIX_CONF)
	cp ./master.cf $(ROOT_POSTFIX_CONF)

uninstall:

	rm -rf $(SYSTEM_CONF)/postfix
	rm -rf $(ROOT_POSTFIX_CONF)/vhcs2

