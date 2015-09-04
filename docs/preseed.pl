#!/usr/bin/perl

# i-MSCP preseed.pl template file for installer preseeding
#
# See documentation at http://wiki.i-mscp.net/doku.php?id=start:preseeding
#
# Author: Laurent Declercq <l.declercq@nuxwin.com>
# Last update: 2015.09.04

# Httpd server to use for the frontEnd
# (nginx)
$main::questions{'FRONTEND_SERVER'} = 'nginx';

# Server to use for the HTTP service
# (apache_itk|apache_fcgid|apache_php_fpm)
$main::questions{'HTTPD_SERVER'} = 'apache_php_fpm';

# Server to use for the FTP service
# (proftpd|vsftpd)
$main::questions{'FTPD_SERVER'} = 'proftpd';

# apache_fcgid - Only relevant if the server for the http service is set to 'apache_fgcid'
$main::questions{'INI_LEVEL'} = 'per_user'; # 'per_user', 'per_domain' or 'per_site'

# apache_php_fpm - Only relevant if the server for the http server is set to 'apache_php_fpm'
$main::questions{'PHP_FPM_POOLS_LEVEL'} = 'per_user'; # 'per_user', 'per_domain' or 'per_site'

# Server to use for the Pop/Imap services
# (courier|dovecot)
$main::questions{'PO_SERVER'} = 'dovecot';

# Authdaemon restricted SQL user -only relevant if you set PO_SERVER to 'courier'
$main::questions{'AUTHDAEMON_SQL_USER'} = 'authdaemon_user';
$main::questions{'AUTHDAEMON_SQL_PASSWORD'} = 'password'; # Password must not be empty and at least 6 characters long

# SASL restricted SQL user
$main::questions{'SASL_SQL_USER'} = 'sasl_user';
$main::questions{'SASL_SQL_PASSWORD'} = 'password'; # Password must not be empty

# Dovecot restricted SQL user - only relevant if you set PO_SERVER to 'dovecot'
$main::questions{'DOVECOT_SQL_USER'} = 'dovecot_user';
$main::questions{'DOVECOT_SQL_PASSWORD'} = 'password'; # Password must not be empty and at least 6 characters long

# Server to use for the Ftp service
# (proftpd)
$main::questions{'FTPD_SERVER'} = 'proftpd';

# Proftpd SQL user
$main::questions{'FTPD_SQL_USER'} = 'vftp_user';
$main::questions{'FTPD_SQL_PASSWORD'} = 'password'; # Password must not empty

# Server to use for the Mail service
# (postfix)
$main::questions{'MTA_SERVER'} = 'postfix';

# Server to use for the DNS service
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
#  - mysql_5.1, mysql_5.5, mariadb_5.3, mariadb_5.5, mariadb_10.0, percona_5.5 or percona_5.6, remote_server
$main::questions{'SQL_SERVER'} = 'mysql_5.5';

# Server hostname
$main::questions{'SERVER_HOSTNAME'} = 'host.domain.tld'; # Fully qualified hostname name

# Domain name from which the i-MSCP control panel must be reachable
$main::questions{'BASE_SERVER_VHOST'} = 'panel.domain.tld'; # Fully qualified domain name

# Http port from which the control panel must be reachable
# Must be a valid port greater than 1023
$main::questions{'BASE_SERVER_VHOST_HTTP_PORT'} = '8080';

# Https port from which the control panel must be reachable (Only relevant if PANEL_SSL_ENABLED is set to 'yes')
# Must be a valid port greater than 1023
$main::questions{'BASE_SERVER_VHOST_HTTPS_PORT'} = '4443';

# Base server IP - Accept both IPv4 and IPv6
# IP must be already configured (see ifconfig)
$main::questions{'BASE_SERVER_IP'} = '192.168.5.110';

# Base server public IP (Only relevant if the base server IP is in private range)
$main::questions{'BASE_SERVER_PUBLIC_IP'} = '192.168.5.110';

# IPs to add in the i-MSCP database - Accept both IPv4 and IPv6
# Any unconfigured IPs will be added to the first network device found (eg: eth0, p2p1 ...)
$main::questions{'SERVER_IPS'} = []; # [ '192.168.5.115', '192.168.5.120' ]

# SQL DSN
$main::questions{'DATABASE_TYPE'} = 'mysql'; # Database type (for now, only 'mysql' is supported)
$main::questions{'DATABASE_HOST'} = 'localhost'; # Accept both hostname and IP
$main::questions{'DATABASE_PORT'} = '3306'; # Only relevant for TCP (e.g: when DATABASE_HOST is not set to 'localhost')
$main::questions{'DATABASE_NAME'} = 'imscp'; # Database name

# i-MSCP SQL user
$main::questions{'DATABASE_USER'} = 'root'; # SQL user
$main::questions{'DATABASE_PASSWORD'} = 'password'; # Password must not empty

# Host from which SQL users created by i-MSCP are allowed to connect to the MySQL server
$main::questions{'DATABASE_USER_HOST'} = 'localhost';

# MySQL prefix/sufix
$main::questions{'MYSQL_PREFIX'} = 'no'; # 'yes' or 'no'
$main::questions{'MYSQL_PREFIX_TYPE'} = 'none'; # 'none' if MYSQL_PREFIX question is set to 'no' or 'infront' or 'behind'

# Default admin
$main::questions{'ADMIN_LOGIN_NAME'} = 'admin'; # Default admin name
$main::questions{'ADMIN_PASSWORD'} = 'password'; # Default admin password
$main::questions{'DEFAULT_ADMIN_ADDRESS'} = 'user@domain.tld'; # Default admin email address (must be a valid email)

# Timzone
$main::questions{'TIMEZONE'} = 'UTC'; # A valid timezone (see http://php.net/manual/en/timezones.php)

# SSL for i-MSCP control panel
$main::questions{'PANEL_SSL_ENABLED'} = 'no'; # 'yes' or 'no'

# Only relevant if PANEL_SSL_ENABLED is set to 'yes'
$main::questions{'PANEL_SSL_SELFSIGNED_CERTIFICATE'} = 'no'; # 'yes' for selfsigned, 'no' for own certificate

# Only relevant if PANEL_SSL_ENABLED is set to 'yes' and PANEL_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
$main::questions{'PANEL_SSL_PRIVATE_KEY_PATH'} = ''; # Path to private key

# Only relevant if PANEL_SSL_ENABLED is set to 'yes' and PANEL_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
$main::questions{'PANEL_SSL_PRIVATE_KEY_PASSPHRASE'} = ''; # Leave blank if your private key is not protected by a passphrase

# Only relevant if PANEL_SSL_ENABLED is set to 'yes' and PANEL_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
$main::questions{'PANEL_SSL_CA_BUNDLE_PATH'} = ''; # Leave blank if you do not have CA bundle

# Only relevant if PANEL_SSL_ENABLED is set to 'yes' and PANEL_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
$main::questions{'PANEL_SSL_CERTIFICATE_PATH'} = ''; # Path to SSL certificate

# Only relevant if PANEL_SSL_ENABLED is set to 'yes'
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
$main::questions{'FILEMANAGER_PACKAGE'} = 'Pydio'; # Pydio or Net2ftp

# Phpmyadmin package restricted SQL user
$main::questions{'PHPMYADMIN_SQL_USER'} = 'pma_user';
$main::questions{'PHPMYADMIN_SQL_PASSWORD'} = 'password'; # Password must not be empty and at least 6 characters long

# Webmmail packages
# List of webmail packages to install such as (RainLoop,Roundcube)
# Set the value to 'No' if you do not want install any webmail
$main::questions{'WEBMAIL_PACKAGES'} = 'RainLoop,Roundcube';

# Roundcube package restricted SQL user
# Only relevant if the Roundcube package is listed in the WEBMAIL_PACKAGES parameter
$main::questions{'ROUNDCUBE_SQL_USER'} = 'roundcube_user';
$main::questions{'ROUNDCUBE_SQL_PASSWORD'} = 'password'; # Password must not be empty and at least 6 characters long

# Rainloop package restricted SQL user (Only relevant if the RainLoop package is listed in the WEBMAIL_PACKAGES parameter)
$main::questions{'RAINLOOP_SQL_USER'} = 'rainloop_user';
$main::questions{'RAINLOOP_SQL_PASSWORD'} = 'password'; # Password must not be empty and at least 6 characters long

# Anti-Rootkits packages
# List of Anti-Rootkits packages to install such as (Chkrootkit,Rkhunter)
# Set the value to 'No' if you do not want install any Anti-Rootkit
$main::questions{'ANTI_ROOTKITS_PACKAGES'} = 'Chkrootkit,Rkhunter';

1;
