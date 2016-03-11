#!/usr/bin/perl

# i-MSCP preseed.pl template file for installer preseeding
#
# See documentation at http://wiki.i-mscp.net/doku.php?id=start:preseeding
#
# Author: Laurent Declercq <l.declercq@nuxwin.com>
# Last update: 2016.03.11

%main::questions = (
    # Server to use for the HTTP service (apache_itk|apache_fcgid|apache_php_fpm)
    HTTPD_SERVER => 'apache_php_fpm',

    # PHP configuration level to use - Only relevant if the server for the http service is set to 'apache_fgcid'
    INI_LEVEL => 'per_site', # 'per_user', 'per_domain' or 'per_site'

    # PHP configuration level to use - Only relevant if the server for the http server is set to 'apache_php_fpm'
    PHP_FPM_POOLS_LEVEL => 'per_site', # 'per_user', 'per_domain' or 'per_site'

    # apache_php_fpm - Only relevant if the server for the http server is set to 'apache_php_fpm'
    # FastCGI addresse type to use (Unix domain socket or TCP/IP)
    # Be aware that TCP/IP can require modification of your kernel parameters (sysctl)
    PHP_FPM_LISTEN_MODE => 'uds', # 'uds', 'tcp'

    # Server to use for the POP/IMAP services (courier|dovecot)
    PO_SERVER => 'dovecot',

    # Authdaemon restricted SQL user - only relevant if you set PO_SERVER to 'courier'
    AUTHDAEMON_SQL_USER => 'authdaemon_user',
    AUTHDAEMON_SQL_PASSWORD => '<password>', # Password must be at least 6 characters long

    # SASL restricted SQL user - only relevant if you set PO_SERVER to 'courier'
    SASL_SQL_USER => 'sasl_user',
    SASL_SQL_PASSWORD => '<password>', # Password must not be empty

    # Dovecot restricted SQL user - only relevant if you set PO_SERVER to 'dovecot'
    DOVECOT_SQL_USER => 'dovecot_user',
    DOVECOT_SQL_PASSWORD => '<password>', # Password must be at least 6 characters long

    # Server to use for the Ftp service (proftpd|vsftpd)
    FTPD_SERVER => 'proftpd',

    # ProFTPD/VsFTPd SQL user
    FTPD_SQL_USER => 'vftp_user',
    FTPD_SQL_PASSWORD => '<password>', # Password must not empty

    # ProFTPD/VsFTPd passive port range
    FTPD_PASSIVE_PORT_RANGE => '32768 60999',

    # Server to use for the Mail service (postfix)
    MTA_SERVER => 'postfix',

    # Server to use for the DNS service (bind or external_server)
    NAMED_SERVER => 'bind',

    # Mode in which the DNS server should acts - Only relevant if you set NAMED_SERVER to 'bind'
    BIND_MODE => 'master', # 'master' or 'slave'

    # Allow to specify IP addresses of your primary DNS servers - Only relevant if you set BIND_MODE to 'slave'
    PRIMARY_DNS => 'no', # 'no' or list of IP addresses, each separated by semicolon or space

    # Allow to indicate IP addresses of your slave DNS server(s) - Only relevant if you set BIND_MODE to 'master'
    SECONDARY_DNS => 'no', # 'no' or list of IP addresses, each separated by semicolon or space

    # IPv6 support for DNS server - Only relevant if you set NAMED_SERVER to 'bind'
    BIND_IPV6 => 'no', # 'yes' or 'no'

    # Local DNS resolver - Only relevant if you set NAMED_SERVER to 'bind'
    LOCAL_DNS_RESOLVER => 'yes', # 'yes' or 'no'

    # Server to use for the SQL service
    # Please consult the docs/<distro>/packages-<distro>.xml file for available options.
    SQL_SERVER => 'mysql_5.5',

    # Server hostname
    SERVER_HOSTNAME => 'host.domain.tld', # Fully qualified hostname name

    # Domain name from which the i-MSCP control panel must be reachable
    BASE_SERVER_VHOST => 'panel.domain.tld', # Fully qualified domain name

    # HTTP port from which the control panel must be reachable - Must be a valid port greater than 1023
    BASE_SERVER_VHOST_HTTP_PORT => '8080',

    # HTTPs port from which the control panel must be reachable - Must be a valid port greater than 1023
    # Only relevant if PANEL_SSL_ENABLED is set to 'yes' (see below)
    BASE_SERVER_VHOST_HTTPS_PORT => '4443',

    # Base server IP - Accept both IPv4 and IPv6 - IP must be already configured (see ifconfig)
    BASE_SERVER_IP => '192.168.5.110',

    # Base server public IP - Only relevant if the base server IP is in private range
    BASE_SERVER_PUBLIC_IP => '192.168.5.110',

    # IPs to add in the i-MSCP database - Accept both IPv4 and IPv6
    # Any unconfigured IPs will be added to the first network device found (eg: eth0, p2p1 ...)
    SERVER_IPS => [], # ['192.168.5.115', '192.168.5.120']

    # SQL DSN
    DATABASE_TYPE => 'mysql', # Database type (for now, only 'mysql' is supported)
    DATABASE_HOST => 'localhost', # Accept both hostname and IP
    DATABASE_PORT => '3306', # Only relevant for TCP (e.g: when DATABASE_HOST is not set to 'localhost')
    DATABASE_NAME => 'imscp', # Database name

    # i-MSCP SQL user
    DATABASE_USER => 'root', # SQL user
    DATABASE_PASSWORD => '<password>', # Password must not empty

    # Host from which SQL users created by i-MSCP are allowed to connect to the MySQL server
    DATABASE_USER_HOST => 'localhost',

    # MySQL prefix/sufix
    MYSQL_PREFIX => 'no', # 'yes' or 'no'
    MYSQL_PREFIX_TYPE => 'none', # 'none' if MYSQL_PREFIX question is set to 'no' or 'infront' or 'behind'

    # Default administrator data
    # Be aware that email address is added as alias for the local root user into the /etc/aliases file
    ADMIN_LOGIN_NAME => 'admin', # Default admin name
    ADMIN_PASSWORD => '<password>', # Default admin password
    DEFAULT_ADMIN_ADDRESS => 'l.declercq@nuxwin.com', # Default admin email address (must be a valid email)

    # Timzone
    TIMEZONE => 'UTC', # A valid timezone (see http://php.net/manual/en/timezones.php)

    # SSL for i-MSCP control panel
    PANEL_SSL_ENABLED => 'yes', # 'yes' or 'no'

    # Only relevant if PANEL_SSL_ENABLED is set to 'yes'
    PANEL_SSL_SELFSIGNED_CERTIFICATE => 'yes', # 'yes' for selfsigned, 'no' for own certificate

    # Only relevant if PANEL_SSL_ENABLED is set to 'yes' and  PANEL_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
    PANEL_SSL_PRIVATE_KEY_PATH => '', # Path to private key

    # Only relevant if PANEL_SSL_ENABLED is set to 'yes' and PANEL_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
    PANEL_SSL_PRIVATE_KEY_PASSPHRASE => '', # Leave blank if your private key is not protected by a passphrase

    # Only relevant if PANEL_SSL_ENABLED is set to 'yes' and PANEL_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
    PANEL_SSL_CA_BUNDLE_PATH => '', # Leave blank if you do not have CA bundle

    # Only relevant if PANEL_SSL_ENABLED is set to 'yes' and PANEL_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
    PANEL_SSL_CERTIFICATE_PATH => '', # Path to SSL certificate

    # Only relevant if PANEL_SSL_ENABLED is set to 'yes'
    # Let's value set to 'http://' if you set PANEL_SSL_ENABLED to 'no'
    BASE_SERVER_VHOST_PREFIX => 'http://', # Default panel access mode 'http://' or 'https://'

    # SSL for i-MSCP services (proftpd, courier, dovecot...)
    SERVICES_SSL_ENABLED => 'yes', # 'yes' or 'no'

    # Only relevant if SERVICES_SSL_ENABLED is set to 'yes'
    SERVICES_SSL_SELFSIGNED_CERTIFICATE => 'yes', # 'yes' for selfsigned certificate, 'no' for own certificate

    # Only relevant if SERVICES_SSL_ENABLED is set to 'yes' and  SERVICES_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
    SERVICES_SSL_PRIVATE_KEY_PATH => '', # Path to private key

    # Only relevant if SERVICES_SSL_ENABLED is set to 'yes' and SERVICES_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
    SERVICES_SSL_PRIVATE_KEY_PASSPHRASE => '', # Leave blank if your private key is not protected by a passphrase

    # Only relevant if SERVICES_SSL_ENABLED is set to 'yes' and SERVICES_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
    SERVICES_SSL_CA_BUNDLE_PATH => '', # Leave blank if you do not have CA bundle

    # Only relevant if SERVICES_SSL_ENABLED is set to 'yes' and SERVICES_SSL_SELFSIGNED_CERTIFICATE is set to 'no'
    SERVICES_SSL_CERTIFICATE_PATH => '', # Path to SSL certificate

    # iMSCP backup feature (database and configuration files)
    BACKUP_IMSCP => 'yes', # 'yes' or 'no' - It's recommended to set this question to 'yes'

    # Customers backup feature - Allows resellers to enable/disable backup feature for their customers
    BACKUP_DOMAINS => 'yes', # 'yes' or 'no'

    # Webstats packages
    WEBSTATS_PACKAGES => 'Awstats', # 'Awstats' or 'No'

    # Only relevant if WEBSTATS_PACKAGES is set to 'Awstats'
    AWSTATS_MODE => '0', # Empty value if WEBSTATS_PACKAGES is set to 'No', 0 for dynamic or 1 for static

    # Ftp Web file manager
    FILEMANAGER_PACKAGE => 'Pydio', # Pydio or Net2ftp

    # Phpmyadmin package restricted SQL user
    PHPMYADMIN_SQL_USER => 'pma_user',
    PHPMYADMIN_SQL_PASSWORD => '<password>', # Password must not be at least 6 characters long

    # Webmmail packages
    # List of webmail packages to install such as (RainLoop,Roundcube)
    # Set the value to 'No' if you do not want install any webmail
    WEBMAIL_PACKAGES => 'RainLoop,Roundcube',

    # Roundcube package restricted SQL user
    # Only relevant if the Roundcube package is listed in the WEBMAIL_PACKAGES parameter
    ROUNDCUBE_SQL_USER => 'roundcube_user',
    ROUNDCUBE_SQL_PASSWORD => '<password>', # Password must not be at least 6 characters long

    # Rainloop package restricted SQL user
    # Only relevant if the RainLoop package is listed in the WEBMAIL_PACKAGES parameter
    RAINLOOP_SQL_USER => 'rainloop_user',
    RAINLOOP_SQL_PASSWORD => '<password>', # Password must be at least 6 characters long

    # Anti-Rootkits packages - List of Anti-Rootkits packages to install such as (Chkrootkit,Rkhunter)
    # Set the value to 'No' if you do not want install any Anti-Rootkit
    ANTI_ROOTKITS_PACKAGES => 'Chkrootkit,Rkhunter'
);

1;
