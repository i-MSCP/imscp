# i-MSCP preseed.pl template file for installer preseeding feature
#
# See documentation at http://wiki.i-mscp.net/doku.php?id=start:preseeding
#
# Author: Laurent Declercq <l.declercq@nuxwin.com>
# Last update: 2017.10.11

%main::questions = (
    # Mandatory parameters
    #
    # Parameters below must be filled. There is no default value for them.

    # SQL user root password
    SQL_ROOT_PASSWORD                   => '',

    # Panel administrator password
    #
    # Only ASCII alphabet characters and numbers are allowed in password.
    ADMIN_PASSWORD                      => '',

    # System administrator email address
    #
    # Possible value: A valid email address.
    #
    # Bear mind that mails sent to local root user will be forwarded to that
    # email.
    DEFAULT_ADMIN_ADDRESS               => '',

    # Optional parameters
    #
    # Parameters below are optional. If they are not filled, default value
    # will be used.

    # Server hostname
    #
    # Possible values: A fully qualified hostname name
    #
    # Leave empty for autodetection: $(hostname --fqdn)
    SERVER_HOSTNAME                     => '',

    # Server primary IP
    #
    # Possible values: An already configured IPv4, IPv6 or `None'
    #
    # The `None' option is more suitable for Cloud computing services such as
    # Scaleway and Amazon EC2, or when using a Vagrant box where the public IP
    # address that is assigned through DHCP can changes over the time.
    #
    # Selecting the `None' option means that i-MSCP will configure the
    # services to listen on all interfaces.
    #
    # Leave empty for default: None
    BASE_SERVER_IP                      => '',

    # WAN IP (only relevant if your primary IP is in private range)
    #
    # Possible values: Ipv4 or IPv4
    #
    # You can force the use of the private IP address by using the same value as
    # the BASE_SERVER_IP parameter.
    #
    # Leave empty for public IP address automatic detection.
    BASE_SERVER_PUBLIC_IP               => '',

    # Timezone
    #
    # Possible values: A valid timezone such as Europe/Paris
    # (see http://php.net/manual/en/timezones.php)
    #
    # Leave empty for autodetection.
    TIMEZONE                            => '',

    # i-MSCP backup feature (database and configuration files)
    #
    # Possible values: yes, no
    #
    # Leave empty for default: yes
    BACKUP_IMSCP                        => '',

    # Enable backup feature for customers
    #
    # Possible values: yes, no
    #
    # Leave empty for default: yes
    BACKUP_DOMAINS                      => '',

    # SQL server implementation
    #
    # Please consult the ../autoinstaller/Packages/<distro>-<codename>.xml file
    # for available options.
    #
    # Leave empty for default: Depend on distribution and codename.
    SQL_SERVER                          => '',

    # SQL root user (mandatory)
    #
    # This SQL user must have full privileges on the SQL server.
    # Note that this user used only while i-MSCP installation/reconfiguration.
    #
    # Leave empty for default (root).
    SQL_ROOT_USER                       => '',

    # Database name
    #
    # Leave empty for default: imscp
    DATABASE_NAME                       => '',

    # Databas hostname
    #
    # Possible values: A valid hostname or IP address
    #
    # Leave empty for default: localhost
    DATABASE_HOST                       => '',

    # Database port
    #
    # Note that this port is used only for connections through TCP.
    #
    # Possible values: A valid port
    #
    # Leave empty for default: 3306
    DATABASE_PORT                       => '',

    # i-MSCP Master SQL user
    #
    # That is the primary SQL user for i-MSCP. It is used to connect to database
    # and create/delete SQL users for your customers.
    #
    # Note that the debian-sys-maint, imscp_srv_user, mysql.user, root and
    # vlogger_user SQL users are not allowed.
    #
    # Leave empty for default: imscp_user
    DATABASE_USER                       => '',
    # Only ASCII alphabet characters and numbers are allowed in password.
    #
    # Leave empty for autogeneration.
    DATABASE_PASSWORD                   => '',

    # Database user host (only relevant for remote SQL server)
    #
    # Host from which SQL users created by i-MSCP are allowed to connect to the
    # SQL server.
    #
    # Possible values: A valid hostname or IP address
    #
    # Leave empty for default: BASE_SERVER_PUBLIC_IP
    DATABASE_USER_HOST                  => '',

    # Enable or disable prefix/suffix for customer SQL database names
    #
    # Possible values: behind, infront, none
    #
    # Leave empty for default: none.
    MYSQL_PREFIX                        => '',

    # Control panel hostname
    #
    # This is the hostname from which the control panel will be reachable
    #
    # Possible values: A fully qualified hostname name
    #
    # Leave empty for default: panel.SERVER_HOSTNAME[domain part]
    BASE_SERVER_VHOST                   => '',

    # Control panel http port
    #
    # Possible values: A port in range 1025-65535
    #
    # Leave empty for default: 8880
    BASE_SERVER_VHOST_HTTP_PORT         => '',

    # Control panel https port (only relevant if SSL is enabled for the control
    # panel (see below))
    #
    # Possible values: A port in range 1025-65535
    #
    # Leave empty for default: 8443
    BASE_SERVER_VHOST_HTTPS_PORT        => '',

    # Enable or disable SSL
    #
    # Possible values: yes, no
    #
    # Leave empty for default: yes
    PANEL_SSL_ENABLED                   => '',

    # Whether or not a self-signed SSL cettificate must be used
    #
    # Possible values: yes, no
    #
    # Leave empty for default: yes
    PANEL_SSL_SELFSIGNED_CERTIFICATE    => '',

    # SSL private key path (only relevant for trusted SSL certificate)
    PANEL_SSL_PRIVATE_KEY_PATH          => '',

    # SSL private key passphrase (only if the private key is encrypted)
    PANEL_SSL_PRIVATE_KEY_PASSPHRASE    => '',

    # SSL CA Bundle path (only relevant for trusted SSL certificate)
    PANEL_SSL_CA_BUNDLE_PATH            => '',

    # SSL certificate path (only relevant for trusted SSL certificate)
    PANEL_SSL_CERTIFICATE_PATH          => '',

    # Alternative URLs feature for client domains
    #
    # Possible values: 1 for enabling, 0 for disabling
    #
    # Leave empty for default: 1
    CLIENT_DOMAIN_ALT_URLS              => '',

    # Control panel default access mode (only relevant if SSL is enabled)
    #
    # Possible values: http://, https://
    #
    # Leave empty for default: http://
    BASE_SERVER_VHOST_PREFIX            => '',

    # Master administrator login
    #
    # Leave empty for default: admin
    ADMIN_LOGIN_NAME                    => '',

    # DNS server implementation
    #
    # Possible values: bind, external_server
    #
    # Leave empty for default: bind
    NAMED_SERVER                        => '',

    # DNS server mode (Only relevant with 'bind')
    #
    # Possible values: master, slave
    #
    # Leave empty for default: master
    BIND_MODE                           => '',

    # Primary DNS server IP addresses (only relevant with slave mode)
    #
    # Possible value: 'no' or a list of IPv4/IPv6 each separated by semicolon
    # or space
    #
    # Leave empty for default: no
    PRIMARY_DNS                         => '',

    # Slave DNS server IP addresses (only relevant with master mode)
    #
    # Possible value: 'no' or a list of IPv4/IPv6 each separated by semicolon
    # or space
    #
    # Leave empty for default: no
    SECONDARY_DNS                       => '',

    # IPv6 support (only relevant with 'bind')
    #
    # Possible values: yes, no
    #
    # Leave empty for default: no
    BIND_IPV6                           => '',

    # Local DNS resolver (only relevant with 'bind')
    #
    # Possible values: yes, no
    #
    # Leave empty for default: yes
    LOCAL_DNS_RESOLVER                  => '',

    # HTTPd server implementation
    #
    # Possible values: apache_itk, apache_fcgid, apache_php_fpm
    #
    # Leave empty for default: apache_php_fpm
    HTTPD_SERVER                        => '',

    # PHP version to use
    #
    # Popssible values: php5.6, php7.0, php7.1
    #
    # Leave empty for default: php5.6
    PHP_SERVER                          => '',

    # PHP configuration level
    #
    # Possible values: per_user, per_domain, per_site
    #
    # Leave empty for default: per_site
    PHP_CONFIG_LEVEL                    => '',

    # PHP-FPM listen socket type (only relevant with 'apache_php_fpm')
    #
    # Possible values: uds, tcp
    #
    # Leave empty for default: uds
    PHP_FPM_LISTEN_MODE                 => '',

    # FTPd server implementation
    #
    # Possible values: proftpd, vsftpd
    #
    # Leave empty for default: proftpd
    FTPD_SERVER                         => '',

    # FTP SQL user
    #
    # Only ASCII alphabet characters and numbers are allowed in password.
    #
    # Leave empty for default: imscp_srv_user
    FTPD_SQL_USER                       => '',
    # Only ASCII alphabet characters and numbers are allowed in password.
    #
    # Leave empty for autogeneration.
    FTPD_SQL_PASSWORD                   => '',

    # Passive port range
    #
    # Possible values: A valid port range in range 32768-60999
    #
    # Don't forgot to forward TCP traffic on those ports on your server if you're behind a firewall
    #
    # Leave empty for default: 32800 33800
    FTPD_PASSIVE_PORT_RANGE             => '',

    # MTA server implementation
    #
    # Possible values: postfix
    #
    # Leave empty for default: postfix
    MTA_SERVER                          => '',

    # POP/IMAP servers implementation
    #
    # Possible values: courier, dovecot
    #
    # Leave empty for default: dovecot
    PO_SERVER                           => '',

    # Authdaemon SQL user (only relevant with 'courier')
    #
    # Only ASCII alphabet characters and numbers are allowed in password.
    #
    # Leave empty for default: imscp_srv_user
    AUTHDAEMON_SQL_USER                 => '',
    # Only ASCII alphabet characters and numbers are allowed in password.
    #
    # Leave empty for autogeneration.
    AUTHDAEMON_SQL_PASSWORD             => '',

    # Dovecot SQL user (only relevant with 'dovecot')
    #
    # Only ASCII alphabet characters and numbers are allowed in password.
    #
    # Leave empty for default: imscp_srv_user
    DOVECOT_SQL_USER                    => '',
    # Only ASCII alphabet characters and numbers are allowed in password.
    #
    # Leave empty for autogeneration.
    DOVECOT_SQL_PASSWORD                => '',

    # Enable or disable SSL for FTP/Mail services
    #
    # Possible values: yes, no
    #
    # Leave empty for default: yes
    SERVICES_SSL_ENABLED                => '',

    # Whether or not a self-signed SSL certificate must be used
    # Only relevant if SSL is enabled for the FTP/Mail services
    #
    # Possible values: yes, no
    #
    # Leave empty for default: yes
    SERVICES_SSL_SELFSIGNED_CERTIFICATE => '',

    # SSL private key path (only relevant for trusted SSL certificate)
    #
    # Possible values: Path to SSL private key
    SERVICES_SSL_PRIVATE_KEY_PATH       => '',

    # SSL private key passphrase (only if the private key is encrypted)
    #
    # Possible values: SSL private key passphrase
    SERVICES_SSL_PRIVATE_KEY_PASSPHRASE => '',

    # SSL CA Bundle path (only relevant for trusted SSL certificate)
    #
    # Possible values: Path to the SSL CA Bundle
    SERVICES_SSL_CA_BUNDLE_PATH         => '',

    # SSL certificate path (only relevant for trusted SSL certificate)
    #
    # Possible values: Path to SSL certificate
    SERVICES_SSL_CERTIFICATE_PATH       => '',

    # Webstats package
    #
    # Possible values: Awstats, No
    #
    # Leave empty for default: Awstats
    WEBSTATS_PACKAGES                   => '',

    # FTP Web file manager packages
    #
    # Possible values: Pydio (only if PHP < 7.0), Net2ftp and MonstaFTP
    #
    # Leave empty for default: MonstaFTP
    FILEMANAGER_PACKAGE                 => '',

    # SQL user for PhpMyAdmin
    #
    # Leave empty for default: imscp_srv_user
    PHPMYADMIN_SQL_USER                 => '',
    # Only ASCII alphabet characters and numbers are allowed in password.
    #
    # Leave empty for autogeneration.
    PHPMYADMIN_SQL_PASSWORD             => '',

    # Webmmail packages
    #
    # Possible values: 'No' or a list of comma separated packages.
    #
    # Leave empty for default: RainLoop,Roundcube
    WEBMAIL_PACKAGES                    => '',

    # SQL user for Roundcube package (only if you use Roundcube)
    #
    # Leave empty for default: imscp_srv_user
    ROUNDCUBE_SQL_USER                  => '',
    # Only ASCII alphabet characters and numbers are allowed in password.
    #
    # Leave empty for autogeneration.
    ROUNDCUBE_SQL_PASSWORD              => '',

    # SQL user for Rainloop package (only relevant if you use Rainloop)
    #
    # Leave empty for default: imscp_srv_user
    RAINLOOP_SQL_USER                   => '',
    # Only ASCII alphabet characters and numbers are allowed in password.
    #
    # Leave empty for autogeneration.
    RAINLOOP_SQL_PASSWORD               => '',

    # Anti-rootkits packages
    #
    # Possible values: 'No' or a list of comma separated packages.
    #
    # Leave empty for default: Chkrootkit,Rkhunter
    ANTI_ROOTKITS_PACKAGES              => ''
);

1;
