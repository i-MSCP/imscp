
SYSTEM_GUI_ROOT=$(SYSTEM_ROOT)/gui

export

install:

	$(SYSTEM_MAKE_DIRS) $(SYSTEM_GUI_ROOT)

	cp ./index.php $(SYSTEM_GUI_ROOT)/index.php
	cp ./lostpassword.php $(SYSTEM_GUI_ROOT)/lostpassword.php
	cp ./imagecode.php $(SYSTEM_GUI_ROOT)/imagecode.php
	cp ./robots.txt $(SYSTEM_GUI_ROOT)/robots.txt
	cp ./favicon.ico $(SYSTEM_GUI_ROOT)/favicon.ico

	cp -dR ./admin $(SYSTEM_GUI_ROOT)
	cp -dR ./reseller $(SYSTEM_GUI_ROOT)
	cp -dR ./client $(SYSTEM_GUI_ROOT)
	cp -dR ./include $(SYSTEM_GUI_ROOT)

	cp -dR ./domain_default_page $(SYSTEM_GUI_ROOT)
	cp -dR ./errordocs $(SYSTEM_GUI_ROOT)
	cp -dR ./themes $(SYSTEM_GUI_ROOT)
	cp -dR ./tools $(SYSTEM_GUI_ROOT)
	cp -dR ./orderpanel $(SYSTEM_GUI_ROOT)

	cp -dR ./phptmp $(SYSTEM_GUI_ROOT)

uninstall:

	rm -rf $(SYSTEM_GUI_ROOT)

.PHONY: install uninstall
