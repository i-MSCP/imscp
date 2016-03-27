#!/usr/bin/perl

# i-MSCP preseed.pl template file for installer preseeding
#
# See documentation at http://wiki.i-mscp.net/doku.php?id=start:preseeding
#
# Author: Laurent Declercq <l.declercq@nuxwin.com>
# Last update: 2016.03.28

%main::questions = (

    #
    ## i-MSCP server implementations
    #

    # FTP server
    # Possible values: proftpd, vsftpd
    FTPD_SERVER                         => 'proftpd',

    # Web server
    # Possible values: apache_itk, apache_fcgid, apache_php_fpm
    HTTPD_SERVER                        => 'apache_php_fpm',

    # DNS server
    # Possible values: bind, external_server
    NAMED_SERVER                        => 'bind',

    # SMTP server
    # Possible values: postfix
    MTA_SERVER                          => 'postfix',

    # POP/IMAP servers
    # Possible values: courier, dovecot
    PO_SERVER                           => 'dovecot',

    # SQL server
    # Please consult the docs/<distro>/packages-<distro>.xml file for available options.
    SQL_SERVER                          => 'mysql_5.5',

    #
    # System configuration
    #

    # Server hostname
    # Possible values: A fully qualified hostname name
    SERVER_HOSTNAME                     => 'host.domain.tld',

    # Primary server IP
    # Possible values: An already configured IPv4 or IPv6
    BASE_SERVER_IP                      => '192.168.1.110',

    # WAN IP
    # Only relevant if the primary server IP is in private range (e.g. when your server is behind NAT).
    # You can force usage of private IP by leaving this blanc
    # Possible values: Ipv4 or IPv6
    BASE_SERVER_PUBLIC_IP               => '',

    # Secondary IPs to configure
    # These IPs, if not already configured, will be added to the first network card (e.g. eth0)
    # Possible values: an array of IPv4/IPv6 such as [ '192.168.1.111', '192.168.1.230' ]
    SERVER_IPS                          => [ ],

    #
    ## Control panel configuration
    #

    # Control panel domain
    # This is the domain name from which the control panel will be reachable
    # Possible values: A fully qualified domain name
    BASE_SERVER_VHOST                   => 'panel.domain.tld',

    # Control panel http port
    # Possible values: A port in range 1025-65535
    BASE_SERVER_VHOST_HTTP_PORT         => '8080',

    # Control panel https port
    # Only relevant if SSL is enabled for the control panel (see below)
    # Possible values: A port in range 1025-65535
    BASE_SERVER_VHOST_HTTPS_PORT        => '4443',

    # Enable or disable SSL
    # Possible values: yes, no
    PANEL_SSL_ENABLED                   => 'yes',

    # Whether or not a self-signed SSL cettificate must be used
    # Possible values: yes, no
    PANEL_SSL_SELFSIGNED_CERTIFICATE    => 'yes',

    # SSL private key path
    # Only relevant if you don't use a self-signed certificate
    PANEL_SSL_PRIVATE_KEY_PATH          => '',

    # SSL private key passphrase
    # Only relevant if your SSL private key is encrypted
    PANEL_SSL_PRIVATE_KEY_PASSPHRASE    => '',

    # SSL CA Bundle path
    # Only relevant if you don't use a self-signed certificate
    PANEL_SSL_CA_BUNDLE_PATH            => '',

    # SSL certificate path
    # Only relevant if you don't use a self-signed certificate
    PANEL_SSL_CERTIFICATE_PATH          => '',

    # Control panel default access mode
    # Only relevant if SSL is enabled
    # Possible values: http://, https://
    BASE_SERVER_VHOST_PREFIX            => 'http://',

    # Master administrator login
    ADMIN_LOGIN_NAME                    => 'admin',
    ADMIN_PASSWORD                      => '',

    # Master administrator email address
    # Possible value: A valid email address. Be aware that mails sent to local root user will be forwarded to that email.
    DEFAULT_ADMIN_ADDRESS               => '',

    # Database name
    DATABASE_NAME                       => 'imscp',

    # SQL user
    # Note that this SQL user must have full privileges on the SQL server. It is used to to connect to the i-MSCP
    # database and also to create/delete SQL users for your customers
    DATABASE_USER                       => 'root',
    DATABASE_PASSWORD                   => '',

    # Database user host for SQL user created by i-MSCP
    # That is the host from which SQL users created by i-MSCP are allowed to connect to the SQL server
    # Possible values: A valid hostname or IP address
    DATABASE_USER_HOST                  => 'localhost',

    # Enable or disable prefix/suffix for SQL database names
    # Possible values: behind, infront, none
    MYSQL_PREFIX                        => 'none',

    #
    ## DNS server configuration
    #

    # DNS server mode
    # Only relevant with 'bind' server implementation
    # Possible values: master, slave
    BIND_MODE                           => 'master',

    # Allow to specify IP addresses of your primary DNS servers - Only relevant if you set BIND_MODE to 'slave'
    # Primary DNS server IP addresses
    # Only relevant with master mode
    # Possible value: 'no' or a list of IPv4/IPv6 each separated by semicolon or space
    PRIMARY_DNS                         => 'no',

    # Slave DNS server IP addresses
    # Only relevant with slave mode
    # Possible value: 'no' or a list of IPv4/IPv6 each separated by semicolon or space
    SECONDARY_DNS                       => 'no',

    # IPv6 support
    # Only relevant with 'bind' server implementation
    # Possible values: yes, no
    BIND_IPV6                           => 'no',

    # Local DNS resolver
    # Only relevant with 'bind' server implementation
    # Possible values: yes, no
    LOCAL_DNS_RESOLVER                  => 'yes',

    #
    ## FTP server configuration parameters
    #

    # FTP SQL user
    FTPD_SQL_USER                       => 'vftp_user',
    FTPD_SQL_PASSWORD                   => '',

    # Passive port range
    # Possible values: A valid port range in range 32768-60999
    FTPD_PASSIVE_PORT_RANGE             => '32768 60999',

    #
    ## SQL server configuration
    #

    # Database type
    # Possible values: mysql
    DATABASE_TYPE                       => 'mysql',

    # Databas hostname
    # Possible values: A valid hostname or IP address
    DATABASE_HOST                       => 'localhost',

    # Database port
    # Note that port is used only for connections through TCP
    # Possible values: A valid port
    DATABASE_PORT                       => '3306',

    #
    ## Courier, POP server configuration
    #

    # Authdaemon SQL user
    AUTHDAEMON_SQL_USER                 => 'authdaemon_user',
    AUTHDAEMON_SQL_PASSWORD             => '',

    # SASL SQL user
    # Only relevant with 'courier' server implementation
    SASL_SQL_USER                       => 'sasl_user',
    SASL_SQL_PASSWORD                   => '',

    # Dovecot SQL user
    # Only relevant with 'dovecot' server implementation
    DOVECOT_SQL_USER                    => 'dovecot_user',
    DOVECOT_SQL_PASSWORD                => '',

    #
    ## PHP configuration parameters
    #

    # PHP configuration level
    # Only relevant with 'apache_fgcid' server implementation
    # Possible values: per_user, per_domain, per_site
    INI_LEVEL                           => 'per_site',

    # PHP configuration level
    # Only relevant with 'apache_php_fpm' server implementation
    # Possible values: per_user, per_domain, per_site
    PHP_FPM_POOLS_LEVEL                 => 'per_site',

    # PHP-FPM listen socket type
    # Only relevant with 'apache_php_fpm' sever implementation
    # Possible values: uds, tcp
    PHP_FPM_LISTEN_MODE                 => 'uds',

    # Timezone
    # Possible values: A valid timezone (see http://php.net/manual/en/timezones.php)
    TIMEZONE                            => 'UTC',

    #
    ## Backup configuration
    #

    # i-MSCP backup feature (database and configuration files)
    # Enable backup for i-MSCP
    # Possible values: yes, no
    BACKUP_IMSCP                        => 'yes',

    # Enable backup feature for customers
    # Possible values: yes, no
    BACKUP_DOMAINS                      => 'yes',

    #
    ## SSL configuration for FTP, IMAP/POP and SMTP services
    #

    # Enable or disable SSL
    # Possible values: yes, no
    SERVICES_SSL_ENABLED                => 'yes',

    # Whether or not a self-signed SSL certificate must be used
    # Only relevant if SSL is enabled
    # Possible values: yes, no
    SERVICES_SSL_SELFSIGNED_CERTIFICATE => 'yes',

    # SSL private key path
    # Only relevant if you don't use a self-signed SSL certificate
    # Possible values: Path to SSL private key
    SERVICES_SSL_PRIVATE_KEY_PATH       => '',

    # SSL private key passphrase
    # Only relevant if your SSL private key is encrypted
    # Possible values: SSL private key passphrase
    SERVICES_SSL_PRIVATE_KEY_PASSPHRASE => '',

    # SSL CA Bundle path
    # Only relevant if you don't use a self-signed certificate
    # Possible values: Path to the SSL CA Bundle
    SERVICES_SSL_CA_BUNDLE_PATH         => '',

    # SSL certificate path
    # Only relevant if you don't use a self-signed certificate
    # Possible values: Path to SSL certificate
    SERVICES_SSL_CERTIFICATE_PATH       => '',

    #
    ## i-MSCP packages configuration
    #

    # Webstats package
    # Enable or disable webstats package
    # Possible values: Awstats, No
    WEBSTATS_PACKAGES                   => 'Awstats',

    # Awstats mode
    # Only relevant if you use webstats Awstats package
    # Possible values: 0 for dynamic, 1 for statis
    AWSTATS_MODE                        => '0',

    # FTP Web file manager packages
    # Possible values: Pydio, Net2ftp
    FILEMANAGER_PACKAGE                 => 'Pydio',

    # SQL user for PhpMyAdmin
    PHPMYADMIN_SQL_USER                 => 'pma_user',
    PHPMYADMIN_SQL_PASSWORD             => '',

    # Webmmail packages
    # Possible values: 'No' or a list of packages, each comma separated
    WEBMAIL_PACKAGES                    => 'RainLoop,Roundcube',

    # SQL user for Roundcube package
    # Only relevant if you use the Roundcube webmail package
    ROUNDCUBE_SQL_USER                  => 'roundcube_user',
    ROUNDCUBE_SQL_PASSWORD              => '',

    # SQL user for Rainloop package
    # Only relevant if you use the Rainloop webmail package
    RAINLOOP_SQL_USER                   => 'rainloop_user',
    RAINLOOP_SQL_PASSWORD               => '',

    # Anti-rootkits packages
    # Possible values: 'No' or a list of packages, each comma separated
    ANTI_ROOTKITS_PACKAGES              => 'Chkrootkit,Rkhunter'
);

1;
