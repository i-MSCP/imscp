#!/usr/bin/perl

# i-MSCP preseed.pl template file for installer preseeding
#
# See documentation at http://wiki.i-mscp.net/doku.php?id=start:preseeding
#
# Author: Laurent Declercq <l.declercq@nuxwin.com>
# Last update: 2013.07.08
# Status: (Experimental)

## Autoinstall questions

# Service to use
$main::preseed{'SERVERS'} = {
	# Server to use for the Http service
	'HTTPD_SERVER' => 'apache_fgci', # (apache_itk|apache_fgci|apache_php_fpm)
	# Server to use for the Pop/Imap services
	'PO_SERVER' => 'courier', # (courier|dovecot)
	# Server to use for the Ftp service
	'FTPD_SERVER' => 'proftpd', # (proftpd)
	# Server to use for the Mail service
	'MTA_SERVER' => 'postfix', # (postfix)
	# Server to use for the Dns service
	'NAMED_SERVER' => 'bind', # (bind)
	# Server to use for the Sql service
	'SQL_SERVER' => 'mysql_5.5' # Depending of your distro (mysql_5.1|mysql_5.5|mariadb_5.3|mariadb_5.5|mariadb_10.0|remote_server)
};

# apache_fcgi - Only relevant if the server for the http service is set to 'apache_fcgi'
$main::preseed{'PHP_FASTCGI'}  = 'fcgid'; # (fcgid|fastcgi)

# apache_php_fpm - Only relevant if the server for the http service is set to 'apache_fgci'
$main::preseed{'FCGI_PHP_INI_LEVEL'} = 'per_user'; # (per_user|per_domain|per_vhost)

# apache_php_fpm - Only relevant if the server for the http server is set to 'apache_php_fpm'
$main::preseed{'PHP_FPM_POOLS_LEVEL'} = 'per_user'; # (per_user|per_domain|per_site)

# Server hostname
$main::preseed{'SERVER_HOSTNAME'} = 'host.domain.tld'; # Fully qualified hostname name

# Domain name from which the i-MSCP frontEnd should be reachable
$main::preseed{'BASE_SERVER_VHOST'} = 'panel.domain.tld'; # Fully qualified domain name

# Local DNS resolver
$main::preseed{'LOCAL_DNS_RESOLVER'} = 'yes'; # (yes|no)

# Base server Ip (primary external IP) - Accept both IPv4 and IPv6
# IP must be already configured (see ifconfig)
$main::preseed{'BASE_SERVER_IP'} = '192.168.5.110';

# IPs to add in the i-MSCP database - Accept both IPv4 and IPv6
# Any unconfigured IPs will be added as alias to the first netcard found (eg: eth0, p2p1 ...)
$main::preseed{'SERVER_IPS'} = []; # ['192.168.5.115', '192.168.5.120']

# SQL DSN
$main::preseed{'DATABASE_TYPE'} = 'mysql'; # Database type (for now, only 'mysql' is supported)
$main::preseed{'DATABASE_HOST'} = 'localhost'; # Accept both hostname and IP
$main::preseed{'DATABASE_PORT'} = '3306'; # Only relevant for TCP (eg: when DATABASE_HOST is not equal to 'localhost')
$main::preseed{'DATABASE_NAME'} = 'imscp';

# i-MSCP SQL user
$main::preseed{'DATABASE_USER'} = 'root'; # Sql user (user must exist and have full privileges on SQL server)
$main::preseed{'DATABASE_USER_HOST'} = 'localhost'; # Host from which SQL users should be allowed to connect to the MySQL server
$main::preseed{'DATABASE_PASSWORD'} = '<password>'; # Password shouldn't be empty

# MySQL prefix/sufix
$main::preseed{'MYSQL_PREFIX'} = 'no'; # (yes|no)
$main::preseed{'MYSQL_PREFIX_TYPE'} = 'none'; # (none if MYSQL_PREFIX question is set to 'no' or 'infront' or 'behind')

# Default admin
$main::preseed{'ADMIN_LOGIN_NAME'} = 'admin'; # Default admin name
$main::preseed{'ADMIN_PASSWORD'} = '<password>'; # Default admin password (A least 6 characters long)
$main::preseed{'DEFAULT_ADMIN_ADDRESS'} = 'user@domain.tld'; # Default admin email address (should be a valid email)

# PHP Timzone
$main::preseed{'PHP_TIMEZONE'} = 'Europe/London'; # A valid timezone (see http://php.net/manual/en/timezones.php)

# SSL for i-MSCP services
$main::preseed{'SSL_ENABLED'} = 'no'; # (yes|no)

# Only relevant if SSL_ENABLED is set to 'yes'
$main::preseed{'SELFSIGNED_CERTIFICATE'} = 1; # (1 for selfsigned, 0 for own certificate)

# Only relevant if SSL_ENABLED is set to 'yes' and  SELFSIGNED_CERTIFICATE is set to 0
$main::preseed{'CERTIFICATE_KEY_PATH'} = ''; # Path to certificate key

# Only relevant if SSL_ENABLED is set to 'yes' and SELFSIGNED_CERTIFICATE is set to 0
$main::preseed{'CERTIFICATE_KEY_PASSWORD'} = ''; # Leave blank if your certificate key is not protected by a passphrase

# Only relevant if SSL_ENABLED is set to 'yes' and SELFSIGNED_CERTIFICATE is set to 0
$main::preseed{'INTERMEDIATE_CERTIFICATE_PATH'} = ''; # Leave blank if you do not have intermediate certificate

# Only relevant if SSL_ENABLED is set to 'yes' and SELFSIGNED_CERTIFICATE is set to 0
$main::preseed{'CERTIFICATE_PATH'} = ''; # Path to SSL certificat

# Only relevant if SSL_ENABLED is set to 'yes' ;
# Let's value set to 'http://' if you set SSL_ENABLED to 'no'
$main::preseed{'BASE_SERVER_VHOST_PREFIX'} = 'http://'; # Default panel access mode (http:// or https://)

# iMSCP backup feature (database and configuration files)
$main::preseed{'BACKUP_IMSCP'} = 'yes'; # (yes|no) - It's recommended to set this question to 'yes'

# Customers backup feature - Allows resellers to enable/disable backup feature for their customers
$main::preseed{'BACKUP_DOMAINS'} = 'no'; # (yes|no)

# Proftpd SQL user
$main::preseed{'FTPD_SQL_USER'} = 'vftp';
$main::preseed{'FTPD_SQL_PASSWORD'} = '<password>'; # Password shouldn't be empty

# Mode in which the DNS server should acts
$main::preseed{'BIND_MODE'} = 'master'; # (master|slave)

# Allow to indicate IP addresses of your primary DNS server(s)
# Only relevant if you set BIND_MODE to 'slave'
$main::preseed{'PRIMARY_DNS'} = ''; # List of IP addresses, each separated by semicolon

# Allow to indicate IP addresses of your slave DNS server(s)
# Only relevant if you set BIND_MODE to 'master'
$main::preseed{'SECONDARY_DNS'} = 'no'; # (no|list of addresses IP, each separated by semicolon)

# Dovecot SQL user (only relevant if you set PO_SERVER to 'dovecot'
$main::preseed{'DOVECOT_SQL_USER'} = 'dovecot_user';

# dovecot SQL user (only relevant if you set  PO_SERVER to 'dovecot'
$main::preseed{'DOVECOT_SQL_PASSWORD'} = '<password>'; # Password shouldn't be empty

# Webstats addon
$main::preseed{'WEBSTATS_ADDON'} = 'No'; # (Awstats|No)

# Only relevant if WEBSTATS_ADDON is set to 'Awstats'
$main::preseed{'AWSTATS_MODE'} = ''; # (empty value if WEBSTATS_ADDON is set to 'No', 0 for dynamic or 1 for static)

# Policyd Weight addon
$main::preseed{'DNSBL_CHECKS_ONLY'} = 'no'; # (yes|no)

# Ftp Web file manager
$main::preseed{'FILEMANAGER_ADDON'} = 'AjaxPlorer'; # Name of the filemanager addon, eg: AjaxPlorer|Net2ftp

# Phpmyadmin addon
$main::preseed{'PHPMYADMIN_SQL_USER'} = 'pma';
$main::preseed{'PHPMYADMIN_SQL_PASSWORD'} = '<password>'; # Password shouldn't be empty

# Roundcube addon
$main::preseed{'ROUNDCUBE_SQL_USER'} = 'roundcube_user';
$main::preseed{'ROUNDCUBE_SQL_PASSWORD'} = '<password>'; # Password shouldn't be empty

1;
