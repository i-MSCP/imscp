
INST_PREF=/tmp/vhcs-2.4.8

HOST_OS=openbsd

ROOT_CONF=$(INST_PREF)/etc

SYSTEM_ROOT=$(INST_PREF)/var/www/vhcs2

SYSTEM_CONF=$(INST_PREF)/etc/vhcs2

SYSTEM_LOG=$(INST_PREF)/var/log/vhcs2

SYSTEM_APACHE_BACK_LOG=$(INST_PREF)/var/www/log/backup

SYSTEM_VIRTUAL=$(INST_PREF)/var/www/virtual

SYSTEM_FCGI=$(INST_PREF)/var/www/fcgi

SYSTEM_MAIL_VIRTUAL=$(INST_PREF)/var/mail/virtual

SYSTEM_MAKE_DIRS=/usr/sbin/vhcs2-mkdirs.pl

CODE_PHP=/usr/sbin/encode-file
