#!/usr/bin/perl

# i-MSCP preseed.pl template file for installer preseeding
# See documentation at http://wiki.i-mscp.net/doku.php?id=start:preseeding
# Last update on 2013.02.19
# Status (Experimental)

## Autoinstall questions

# Service to use
$main::preseed{'SERVERS'} = {
	'HTTPD_SERVER' => 'apache_itk',	# Server to use for the Httpd service (apache_itk|apache_fgci|apache_php_fpm)
	'PO_SERVER' => 'courier',		# Server to use for the po service (courier|dovecot)
	'FTPD_SERVER' => 'proftpd',		# No relevant for now since only proftpd is supported
	'MTA_SERVER' => 'postfix',		# No relevant for now since only postfix is supported
	'NAMED_SERVER' => 'bind',		# No relevant for now since only Bind9 is supported
	'SQL_SERVER' => 'mysql_5.5'		# Server to use for the Sql service (mysql_5.1|mysql_5.5|mariadb_5.3|mariadb_5.5)
};

# apache_fcgi - Only relevant if server for the HTTPD server question is set to 'apache_fcgi'
$main::preseed{'PHP_FASTCGI'}  = 'fcgid'; # (fcgid|fastcgi)

# apache_php_fpm - Only relevant if server for the HTTPD server question is set to 'apache_fgci'
#$main::preseed{'FCGI_PHP_INI_LEVEL'} = 'per_user'; # (per_user|per_domain|per_vhost)

# apache_php_fpm - Only relevant if server for the HTTPD server question is set to 'apache_php_fpm'
$main::preseed{'PHP_FPM_POOLS_LEVEL'} = 'per_user'; # (per_user|per_domain|per_site)

# Server hostname
$main::preseed{'SERVER_HOSTNAME'} = 'host.domain.tld'; # Fully qualified hostname name

# Domain name from which the i-MSCP frontEnd should be reachable
$main::preseed{'BASE_SERVER_VHOST'} = 'panel.host.domain.tld'; # Fully qualified domain name

# Local DNS resolver
$main::preseed{'LOCAL_DNS_RESOLVER'} = 'yes';

# Base server Ip (primary external IP) - Accept both IPv4 and IPv6
# IP must be already configured (see ifconfig)
$main::preseed{'BASE_SERVER_IP'} = '192.168.5.110';

# IPs to add in the i-MSCP database - Accept both IPv4 and IPv6
# Any unconfigured IPs will be added to the first netcard found (eg: eth0)
$main::preseed{'SERVER_IPS'} = ['192.168.5.115']; # ['192.168.5.115', '192.168.5.115']

# SQL DSN
$main::preseed{'DATABASE_TYPE'} = 'mysql'; # Database type (for now, only 'mysql' is supported)
$main::preseed{'DATABASE_HOST'} = 'localhost'; # Accept both hostname and IP
$main::preseed{'DATABASE_PORT'} = '3306'; # Only relevant for TCP (eg: when DATABASE_HOST is not equal to 'localhost')
$main::preseed{'DATABASE_NAME'} = 'imscp';

# iMSCP SQL user
$main::preseed{'DATABASE_USER'} = 'root'; # SQL user (user must exist and have full privileges on SQL server)
$main::preseed{'DATABASE_PASSWORD'} = '<password>'; # Password shouldn't be empty

# MySQL prefix/sufix
$main::preseed{'MYSQL_PREFIX'} = 'no'; # (yes|no)
$main::preseed{'MYSQL_PREFIX_TYPE'} = 'none'; # (none if MYSQL_PREFIX question is set to 'no' or 'infront' or 'behind')

# Default admin
$main::preseed{'ADMIN_LOGIN_NAME'} = 'admin'; # Default admin name
$main::preseed{'ADMIN_PASSWORD'} = '<password>'; # Default admin password (A least 6 characters long)
$main::preseed{'DEFAULT_ADMIN_ADDRESS'} = 'user@domain.tld'; # Default admin email address (should be a valid email)

# PHP Timzone
$main::preseed{'PHP_TIMEZONE'} = 'Europe/London'; # A valid PHP timezone (see http://php.net/manual/en/timezones.php)

# SSL for i-MSCP services
$main::preseed{'SSL_ENABLED'} = 'no'; # (yes|no)

# Only relevant if the SSL_ENABLED question is set to 'yes'
$main::preseed{'SELFSIGNED_CERTIFICATE'} = 1; # (1 for selfsigned, 0 for own certificate)

# Only relevant if the SSL_ENABLED question is set to 'yes' and the SELFSIGNED_CERTIFICATE is set to 0
$main::preseed{'CERTIFICATE_KEY_PATH'} = ''; # Path to certificate key

# Only relevant if the SSL_ENABLED question is set to 'yes' and the SELFSIGNED_CERTIFICATE is set to 0
$main::preseed{'CERTIFICATE_KEY_PASSWORD'} = ''; # Leave blank if your certificate key is not protected by a passphrase

# Only relevant if the SSL_ENABLED question is set to 'yes' and the SELFSIGNED_CERTIFICATE is set to 0
$main::preseed{'INTERMEDIATE_CERTIFICATE_PATH'} = ''; # Leave blank if you do not have intermediate certificate

# Only relevant if the SSL_ENABLED question is set to 'yes' and the SELFSIGNED_CERTIFICATE is set to 0
$main::preseed{'CERTIFICATE_PATH'} = ''; # Path to SSL certificat

# Only relevant if the SSL_ENABLED question is set to 'yes' ;
# Let's value set to 'http://' if you set the SSL_ENABLED question to 'no'
$main::preseed{'BASE_SERVER_VHOST_PREFIX'} = 'http://'; # Default panel access mode (http:// or https://)

# PhpMyAdmin restricted SQL user
$main::preseed{'PMA_USER'} = 'pma';
$main::preseed{'PMA_PASSWORD'} = '<password>'; # Password shouldn't be empty

# iMSCP backup feature - Allows resellers to propose backup feature for their customers
$main::preseed{'BACKUP_IMSCP'} = 'yes'; # (yes|no) - It's recommended to set this question to 'yes'

# Customers backup feature
$main::preseed{'BACKUP_DOMAINS'} = 'no'; # (yes|no)

# Proftpd SQL user
$main::preseed['FTPD_SQL_USER'] = 'vftp';
$main::preseed['FTPD_SQL_PASSWORD'] = '<password>'; # Password shouldn't be empty

# bind
$main::preseed{'BIND_MODE'} = 'master'; # (master|slave) - Mode in which the DNS server should acts

# Only relevant if you set the BIND_MODE question to 'slave'
# Allow to indicate IP addresses of your primary DNS server(s)
$main::preseed{'PRIMARY_DNS'} = ''; # (empty value or list of ips, each separated by semicolon)

# Only relevant if you set the BIND_MODE question to 'master' and if you have slave DNS server(s)
$main::preseed{'SECONDARY_DNS'} = 'no'; # (no|list of IPs, each separated by semicolon)

# dovecot SQL user (only relevant if you set the  PO_SERVER question to 'dovecot'
$main::preseed{'DOVECOT_SQL_USER'} = 'dovecot_user';

# dovecot SQL user (only relevant if you set the  PO_SERVER question to 'dovecot'
$main::preseed{'DOVECOT_SQL_PASSWORD'} = '<password>'; # Password shouldn't be empty

# Aswtats addon
$main::preseed{'AWSTATS_ACTIVE'} = 'no'; # (yes|no)

# Only relevant if the AWSTATS_ACTIVE question is set to 'yes'
$main::preseed{'AWSTATS_MODE'} = ''; # (empty value if the AWSTATS_ACTIVE question is set to 'no', 0 for dynamic or 1 for static)

# Policyd Weight addon
$main::preseed{'DNSBL_CHECKS_ONLY'} = 'no'; # (yes|no)

# Ftp Web file manager
$main::preseed{'FILEMANAGER_ADDON'} = 'AjaxPlorer'; # (AjaxPlorer|Net2ftp)

# Phpmyadmin addon
$main::preseed{'PHPMYADMIN_SQL_USER'} = 'pma';
$main::preseed{'PHPMYADMIN_SQL_PASSWORD'} = '<password>'; # Password shouldn't be empty

# Roundcube addon
$main::preseed{'ROUNDCUBE_SQL_USER'} = 'roundcube_user';
$main::preseed{'ROUNDCUBE_SQL_PASSWORD'} = '<password>'; # Password shouldn't be empty

1;
