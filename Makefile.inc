
ifndef INST_PREF
        INST_PREF=/tmp/ispcp
endif

HOST_OS=openbsd

ROOT_CONF=$(INST_PREF)/etc

SYSTEM_ROOT=$(INST_PREF)/var/www/ispcp

SYSTEM_CONF=$(INST_PREF)/etc/ispcp

SYSTEM_LOG=$(INST_PREF)/var/log/ispcp

SYSTEM_APACHE_BACK_LOG=$(INST_PREF)/var/www/log/backup

SYSTEM_VIRTUAL=$(INST_PREF)/var/www/virtual

SYSTEM_AWSTATS=$(INST_PREF)/var/www/awstats

SYSTEM_FCGI=$(INST_PREF)/var/www/fcgi

SYSTEM_MAIL_VIRTUAL=$(INST_PREF)/var/mail/virtual

SYSTEM_MAKE_DIRS=/bin/mkdir -p

.PHONY: install uninstall clean
