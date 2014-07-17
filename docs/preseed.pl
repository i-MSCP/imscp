#!/usr/bin/perl

# i-MSCP preseed.pl template file for installer preseeding
#
# See documentation at http://wiki.i-mscp.net/doku.php?id=start:preseeding
#
# Author: Laurent Declercq <l.declercq@nuxwin.com>
# Last update: 2014.06.20

# Server to use for the HTTP service
# (apache_itk|apache_fcgid|apache_php_fpm)
$main::questions{'HTTPD_SERVER'} = 'apache_php_fpm';

# apache_fcgid - Only relevant if the server for the http service is set to 'apache_fgcid'
$main::questions{'INI_LEVEL'} = 'per_user'; # 'per_user' or 'per_domain' or 'per_vhost'

# apache_php_fpm - Only relevant if the server for the http server is set to 'apache_php_fpm'
$main::questions{'PHP_FPM_POOLS_LEVEL'} = 'per_user'; # 'per_user' or 'per_domain' or 'per_site'

# Server to use for the Pop/Imap services
# (courier|dovecot)
$main::questions{'PO_SERVER'} = 'courier';

# Authdaemon restricted SQL user -only relevant if you set PO_SERVER to 'courier'
$main::questions{'AUTHDAEMON_SQL_USER'} = 'authdaemon_user';
$main::questions{'AUTHDAEMON_SQL_PASSWORD'} = '<password>'; # Password must not be empty

# SASL restricted SQL user
$main::questions{'SASL_SQL_USER'} = 'sasl_user';
$main::questions{'SASL_SQL_PASSWORD'} = '<password>'; # Password must not be empty

# Dovecot restricted SQL user - only relevant if you set PO_SERVER to 'dovecot'
$main::questions{'DOVECOT_SQL_USER'} = 'dovecot_user';
$main::questions{'DOVECOT_SQL_PASSWORD'} = '<password>'; # Password must not be empty

# Server to use for the Ftp service
# (proftpd)
$main::questions{'FTPD_SERVER'} = 'proftpd';

# Proftpd restricted SQL user
$main::questions{'FTPD_SQL_USER'} = 'vftp';
$main::questions{'FTPD_SQL_PASSWORD'} = '<password>'; # Password must not empty

# Server to use for the Mail service
# (postfix)
$main::questions{'MTA_SERVER'} = 'postfix';

# Policyd Weight package
$main::questions{'DNSBL_CHECKS_ONLY'} = 'no'; # 'yes' or 'no'

# Server to use for the Dns service
# (bind or external_server)
$main::questions{'NAMED_SERVER'} = 'bind';

# Mode in which the DNS server should acts
# Only relevant if you set NAMED_SERVER to 'bind'
$main::questions{'BIND_MODE'} = 'master'; # 'master' or 'slave'

# Allow to indicate IP addresses of your primary DNS servers
# Only relevant if you set BIND_MODE to 'slave'
$main::questions{'PRIMARY_DNS'} = 'no'; # 'no' or list of IP addresses, each separated by semicolon or space

# Allow to indicate IP addresses of your slave DNS server(s)
# Only relevant if you set BIND_MODE to 'master'
$main::questions{'SECONDARY_DNS'} = 'no'; # 'no' or list of IP addresses, each separated by semicolon or space

# IPv6 support for DNS server
# Only relevant if you set NAMED_SERVER to 'bind'
$main::questions{'BIND_IPV6'} = 'no'; # 'yes' or 'no'

# Local DNS resolver
# Only relevant if you set NAMED_SERVER to 'bind'
$main::questions{'LOCAL_DNS_RESOLVER'} = 'yes'; # 'yes' or 'no'

# Server to use for the SQL service
# Depending of your distro:
#  - mysql_5.1 or mysql_5.5
#  - mariadb_5.3 or mariadb_5.5 or mariadb_10.0
#  - percona_5.5 or percona_5.6
#  - remote_server
$main::questions{'SQL_SERVER'} = 'mysql_5.5';

# Server hostname
$main::questions{'SERVER_HOSTNAME'} = 'host.domain.tld'; # Fully qualified hostname name

# Domain name from which the i-MSCP frontEnd should be reachable
$main::questions{'BASE_SERVER_VHOST'} = 'panel.domain.tld'; # Fully qualified domain name

# Base server IP - Accept both IPv4 and IPv6
# IP must be already configured (see ifconfig)
$main::questions{'BASE_SERVER_IP'} = '192.168.5.110';

# Base server public IP
# Only relevant if the Base server IP is in private range
$main::questions{'BASE_SERVER_PUBLIC_IP'} = '';

# IPs to add in the i-MSCP database - Accept both IPv4 and IPv6
# Any unconfigured IPs will be added as alias to the first network card found (eg: eth0, p2p1 ...)
$main::questions{'SERVER_IPS'} = []; # ['192.168.5.115', '192.168.5.120']

# SQL DSN
$main::questions{'DATABASE_TYPE'} = 'mysql'; # Database type (for now, only 'mysql' is supported)
$main::questions{'DATABASE_HOST'} = 'localhost'; # Accept both hostname and IP
$main::questions{'DATABASE_PORT'} = '3306'; # Only relevant for TCP (eg: when DATABASE_HOST is not set to 'localhost')
$main::questions{'DATABASE_NAME'} = 'imscp'; # Database name

# i-MSCP SQL user
$main::questions{'DATABASE_USER'} = 'root'; # SQL user  - User must exist and have full privileges on SQL server
$main::questions{'DATABASE_USER_HOST'} = 'localhost'; # Host from which SQL users created by i-MSCP are allowed to connect to the MySQL server
$main::questions{'DATABASE_PASSWORD'} = '<password>'; # Password must not empty

# MySQL prefix/sufix
$main::questions{'MYSQL_PREFIX'} = 'no'; # 'yes' or 'no'
$main::questions{'MYSQL_PREFIX_TYPE'} = 'none'; # 'none' if MYSQL_PREFIX question is set to 'no' or 'infront' or 'behind'

# Default admin
$main::questions{'ADMIN_LOGIN_NAME'} = 'admin'; # Default admin name
$main::questions{'ADMIN_PASSWORD'} = '<password>'; # Default admin password (A least 6 characters long)
$main::questions{'DEFAULT_ADMIN_ADDRESS'} = 'user@domain.tld'; # Default admin email address (must be a valid email)

# PHP Timzone
$main::questions{'PHP_TIMEZONE'} = 'Europe/London'; # A valid PHP timezone (see http://php.net/manual/en/timezones.php)

# SSL for i-MSCP control panel
$main::questions{'PANEL_SSL_ENABLED'} = 'no'; # 'yes' or 'no'

# Only relevant if PANEL_SSL_ENABLED is set to 'yes'
$main::questions{'PANEL_SSL_SELFSIGNED_CERTIFICATE'} = 'no'; # 'yes' for selfsigned, 'no' for own certificate

# Only relevant if PANEL_SSL_ENABLED is set to 'yes' and  PANEL_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
$main::questions{'PANEL_SSL_PRIVATE_KEY_PATH'} = ''; # Path to private key

# Only relevant if PANEL_SSL_ENABLED is set to 'yes' and PANEL_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
$main::questions{'PANEL_SSL_PRIVATE_KEY_PASSPHRASE'} = ''; # Leave blank if your private key is not protected by a passphrase

# Only relevant if PANEL_SSL_ENABLED is set to 'yes' and PANEL_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
$main::questions{'PANEL_SSL_CA_BUNDLE_PATH'} = ''; # Leave blank if you do not have CA bundle

# Only relevant if PANEL_SSL_ENABLED is set to 'yes' and PANEL_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
$main::questions{'PANEL_SSL_CERTIFICATE_PATH'} = ''; # Path to SSL certificate

# Only relevant if PANEL_SSL_ENABLED is set to 'yes' ;
# Let's value set to 'http://' if you set PANEL_SSL_ENABLED to 'no'
$main::questions{'BASE_SERVER_VHOST_PREFIX'} = 'http://'; # Default panel access mode 'http://' or 'https://'

# SSL for i-MSCP services (proftpd, courier, dovecot...)
$main::questions{'SERVICES_SSL_ENABLED'} = 'no'; # 'yes' or 'no'

# Only relevant if SERVICES_SSL_ENABLED is set to 'yes'
$main::questions{'SERVICES_SSL_SELFSIGNED_CERTIFICATE'} = 'no'; # 'yes' for selfsigned, 'no' for own certificate

# Only relevant if SERVICES_SSL_ENABLED is set to 'yes' and  SERVICES_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
$main::questions{'SERVICES_SSL_PRIVATE_KEY_PATH'} = ''; # Path to private key

# Only relevant if SERVICES_SSL_ENABLED is set to 'yes' and SERVICES_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
$main::questions{'SERVICES_SSL_PRIVATE_KEY_PASSPHRASE'} = ''; # Leave blank if your private key is not protected by a passphrase

# Only relevant if SERVICES_SSL_ENABLED is set to 'yes' and SERVICES_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
$main::questions{'SERVICES_SSL_CA_BUNDLE_PATH'} = ''; # Leave blank if you do not have CA bundle

# Only relevant if SERVICES_SSL_ENABLED is set to 'yes' and SERVICES_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
$main::questions{'SERVICES_SSL_CERTIFICATE_PATH'} = ''; # Path to SSL certificate

# iMSCP backup feature (database and configuration files)
$main::questions{'BACKUP_IMSCP'} = 'yes'; # 'yes' or 'no' - It's recommended to set this question to 'yes'

# Customers backup feature - Allows resellers to enable/disable backup feature for their customers
$main::questions{'BACKUP_DOMAINS'} = 'yes'; # 'yes' or 'no'

# Webstats packages
$main::questions{'WEBSTATS_PACKAGES'} = 'Awstats'; # 'Awstats' or 'No'

# Only relevant if WEBSTATS_PACKAGES is set to 'Awstats'
$main::questions{'AWSTATS_MODE'} = '0'; # Empty value if WEBSTATS_PACKAGES is set to 'No', 0 for dynamic or 1 for static

# Ftp Web file manager
$main::questions{'FILEMANAGER_PACKAGE'} = 'AjaXplorer'; # AjaXplorer or Net2ftp

# Phpmyadmin package restricted SQL user
$main::questions{'PHPMYADMIN_SQL_USER'} = 'pma';
$main::questions{'PHPMYADMIN_SQL_PASSWORD'} = '<password>'; # Password must not be empty

# Roundcube package restricted SQL user
$main::questions{'ROUNDCUBE_SQL_USER'} = 'roundcube_user';
$main::questions{'ROUNDCUBE_SQL_PASSWORD'} = '<password>'; # Password must not be empty

# Anti-Rootkits packages
$main::questions{'ANTI_ROOTKITS_PACKAGES'} = 'Chkrootkit,Rkhunter'; # 'Chkrootkit' and/or 'Rkhunter' or 'No', each value comma separated

1;
