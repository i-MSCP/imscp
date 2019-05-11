#!/usr/bin/perl

# i-MSCP preseed.pl template file for installer preseeding feature
#
# See documentation at http://wiki.i-mscp.net/doku.php?id=start:preseeding
#
# Author: Laurent Declercq <l.declercq@nuxwin.com>
# Last update: 2019.05.11

%::questions = (
    #
    ## System configuration
    #

    # Server hostname
    #
    # Possible values: A fully qualified hostname name (FQHN)
    #
    # Leave this parameter empty for use of the configured server hostname.
    SERVER_HOSTNAME                     => '',

    # Server primary IP
    #
    # Note: If you make use of a Cloud computing service such as Scaleway or
    # Amazon EC2, you should set the value to '0.0.0.0'. Setting the value to
    # '0.0.0.0' means that i-MSCP will configures the services to listen on all
    # interfaces rather than a specific interface.
    #
    # Possible values: A configured IPv4 or IPv6 address
    BASE_SERVER_IP                      => '',

    # WAN IP
    #
    # You can force usage of a private IP by setting this parameter to the
    # value of the 'BASE_SERVER_IP' parameter instead of a public IP. You
    # can also leave this parameter empty for automatic detection of the
    # public (WAN) IP.
    #
    # If you have set the 'BASE_SERVER_IP' parameter value to '0.0.0.0', you
    # should leave this parameter empty.
    #
    # Possible values: an Ipv4 or IPv6 address
    BASE_SERVER_PUBLIC_IP               => '',

    # Server timezone
    #
    # Possible values: A valid timezone such as 'Europe/Berlin'
    # (see http://php.net/manual/en/timezones.php)
    #
    # Leave this parameter empty for automatic timezone detection.
    TIMEZONE                            => '',

    #
    ## Backup configuration parameters
    #

    # Enable/Disable backup feature for the control panel database and
    # configuration files
    #
    # Possible values: yes, no
    BACKUP_IMSCP                        => 'yes',

    # Enable/Disable the backup feature for client data (Web data, SQL data,
    # and mail data)
    #
    # Enabling this feature will make the resellers able to enable/disable the
    # backup feature on a per client basis.
    #
    # Possible values: yes, no
    BACKUP_DOMAINS                      => 'yes',

    #
    ## SQL server configuration parameters
    #

    # SQL server implementation
    #
    # Available SQL server vendors/versions depend on your distribution.
    # Please consult the autoinstaller/Packages/<distro>-<codename>.xml file.
    # Accepted values are the XML node names that describe SQL servers. For
    # instance: 'remote_server', 'mysql_5.7', 'mariadb_10.1', 'mariadb_10.2'
    #
    # Leave this parameter empty to automatically select the default SQL server
    # implementation, as set in the distribution packages file.
    SQL_SERVER                          => '',

    # Keep the local SQL server installed regardless of the selected SQL server
    # implementation
    #
    # If there is a local SQL server installed, and when selecting the
    # 'remote server' SQL implementation, this flag tells whether or not
    # the local server must be kept.
    #
    # Possible value: yes, no
    KEEP_LOCAL_SQL_SERVER               => 'yes',

    # Database hostname
    #
    # Possible values: A valid hostname or IP address
    DATABASE_HOST                       => 'localhost',

    # Database port
    #
    # This port is used only for connections through TCP.
    #
    # Possible values: A valid port
    DATABASE_PORT                       => '3306',

    # SQL root user (User with full privileges, including GRANT option)
    #
    # If you make use of local MySQL server and if the unix_socket
    # authentication plugin is enabled for the SQL root user, you can leave
    # those parameter empty.
    #
    # The installer only make use of that user while installation.
    SQL_ROOT_USER                       => '',
    # SQL root user password
    SQL_ROOT_PASSWORD                   => '',

    # i-MSCP Master SQL user
    #
    # Master SQL user for i-MSCP. That SQL user is used by both the i-MSCP
    # frontEnd and backend.
    #
    # Note that the debian-sys-maint, mysql.user, root  SQL users are not
    # allowed.
    DATABASE_USER                       => 'imscp_user',
    # Only ASCII alphabet characters and numbers are allowed in password.
    # Leave this parameter empty for automatic password generation.
    DATABASE_PASSWORD                   => '',

    # Hostname for SQL users created by i-MSCP
    # 
    # This is the hostname from which SQL users created by i-MSCP can connect
    # to the SQL server. Generally speaking, that hostname should be
    # 'localhost', excepted when using a remote SQL server . In such a case,
    # the hostname should be set to the i-MSCP server hostname, or its WAN IP.
    # However, if both servers can communicate (are linked) together through an
    # internal network (LAN), it is best recommended to choose an IP address
    # from the LAN (private IP range) rather than relying on the WAN IP. Doing
    # so would leverage security, and prevent any NAT or resolving issue.
    # 
    # Finally, when using a remote SQL server, usage of an hostname consisting
    # only of a wildcard ('%') character should be avoided. Doing so  would
    # make the SQL users able to connect from any location. This practice is
    # best avoided to mitigate unwanted connections from the outside-world,
    # from attackers which would have get your SQL credentials. Nowadays, local
    # SQL servers are setup to listen on the loopback interface, that is,
    # locally only, but that's not the case of remote SQL servers which must be
    # reachable through TCP/IP.
    #
    # Possible values: A valid SQL user hostname.
    # See https://dev.mysql.com/doc/refman/5.7/en/account-names.html
    DATABASE_USER_HOST                  => 'localhost',

    # Database name (database for the control panel)
    DATABASE_NAME                       => 'imscp',

    # Enable/disable prefix/suffix for SQL databases and users names
    #
    # Possible values: behind, infront, none
    MYSQL_PREFIX                        => 'none',

    #
    ## Control panel configuration parameters
    #

    # FrontEnd httpd server
    #
    # Possible value: nginx
    #
    # Leave this parameter empty to automatically select the default DNS
    # server implementation, as set in the distribution packages file.
    FRONTEND_SERVER                     => '',

    # Control panel hostname
    #
    # Hostname from which the control panel must be reachable.
    #
    # Possible values: A fully qualified hostname name (FQHN)
    #
    #
    # Leave this parameter empty for use of default value:
    #    panel.<SERVER_HOSTNAME>
    BASE_SERVER_VHOST                   => '',

    # Control panel http port
    #
    # Possible values: A port in range 1025-65535
    BASE_SERVER_VHOST_HTTP_PORT         => '8880',

    # Control panel https port (only relevant if SSL is enabled for the control
    # panel)
    #
    # Possible values: A port in range 1025-65535
    BASE_SERVER_VHOST_HTTPS_PORT        => '8443',

    # Enable/disable SSL for the control panel
    #
    # Possible values: yes, no
    PANEL_SSL_ENABLED                   => 'yes',

    # Whether or not a self-signed SSL certificate must be generated for the
    # control panel
    #
    # Possible values: yes, no
    PANEL_SSL_SELFSIGNED_CERTIFICATE    => 'yes',

    # Control panel SSL certificate private key (only relevant for a trusted
    # SSL certificate)
    #
    # Possible value: SSL certificate private key path
    PANEL_SSL_PRIVATE_KEY_PATH          => '',

    # Passphrase for the control panel SSL certificate private key (only if the
    # private key is encrypted)
    #
    # Possible value: Passphrase for the SSL certificate private key
    PANEL_SSL_PRIVATE_KEY_PASSPHRASE    => '',

    # Control panel SSL certificate CA bundle (only relevant for a trusted SSL
    # certificate)
    #
    # Possible value: SSL certificate CA bundle path
    PANEL_SSL_CA_BUNDLE_PATH            => '',

    # Control panel SSL certificate (only relevant for a trusted SSL
    # certificate)
    #
    # Possible value: SSL certificate path
    PANEL_SSL_CERTIFICATE_PATH          => '',

    # Alternative URLs feature for the client websites
    #
    # When this feature is enabled, clients can access their Websites through
    # an alternative URL which is a subdomain from the control panel domain.
    #
    # If you make use of an external DNS server, you must not forgot to add a
    # wildcard DNS in the control panel domain zone such as *.<cp_domain>.tld.
    #
    # Possible values: yes, no
    CLIENT_WEBSITES_ALT_URLS            => 'yes',

    # Control panel access mode (only relevant if SSL is enabled for the control
    # panel)
    #
    # Possible values: http://, https://
    BASE_SERVER_VHOST_PREFIX            => 'http://',

    # Master administrator account credentials
    ADMIN_LOGIN_NAME                    => 'admin',
    # Only ASCII alphabet characters and numbers are allowed in password.
    ADMIN_PASSWORD                      => '',

    # Master administrator email address
    #
    # Be aware that mails sent to local root user will be forwarded to that
    # email.
    #
    # Possible value: A valid email address.
    DEFAULT_ADMIN_ADDRESS               => '',

    #
    ## DNS server configuration
    #

    # DNS server implementation
    #
    # Possible values: bind, external_server
    #
    # Leave this parameter empty to automatically select the default DNS
    # server implementation, as set in the distribution packages file.
    NAMED_SERVER                        => '',

    #
    # Bind server implementation configuration parameters
    #

    # DNS server mode
    #
    # Possible values: master, slave
    BIND_MODE                           => 'master',

    # Master DNS IP addresses (Only relevant when the value of the 'BIND_MODE'
    # parameter is set to 'master')
    #
    # Possible value: 'no', or a list of IPv4/IPv6 addresses, each separated by
    # semicolon or space
    PRIMARY_DNS                         => 'no',

    # Slave DNS IP addresses (Only relevant when the value of the 'BIND_MODE'
    # parameter is set to 'slave')
    #
    # Possible value: 'no', or a list of IPv4/IPv6 addresses, each separated by
    # semicolon or space
    SECONDARY_DNS                       => 'no',

    # IPv6 support
    #
    # Possible values: yes, no
    BIND_IPV6                           => 'no',

    # Local DNS resolver
    #
    # Make use of the local DNS server (bind9) for the local DNS resolution.
    #
    # Possible values: yes, no
    LOCAL_DNS_RESOLVER                  => 'yes',

    #
    ## Httpd server configuration parameters
    #

    # Httpd server implementation
    #
    # Possible values: apache_itk, apache_fcgid or apache_php_fpm (recommended)
    #
    # Leave this parameter empty to automatically select the default httpd
    # server implementation, as set in the distribution packages file.
    HTTPD_SERVER                        => '',

    #
    ## PHP configuration parameters
    #

    # PHP version for customers
    #
    # Possible values: php5.6, php7.0, php7.1, php7.2, or php7.3 (recommended)
    #
    # Leave this parameter empty to automatically select the default PHP
    # version, as set in the distribution packages file.
    PHP_SERVER                          => '',

    # PHP configuration level
    #
    # If you make use of the PhpSwitcher plugin, you need set the value to
    # 'per_site'.
    #
    # Possible values: per_user, per_domain, per_site
    PHP_CONFIG_LEVEL                    => 'per_site',

    # PHP-FPM listen socket type (Only relevant with the 'apache_php_fpm'
    # server implementation)
    #
    # Possible values: uds (recommended), tcp
    PHP_FPM_LISTEN_MODE                 => 'uds',

    #
    ## FTPd server configuration parameters
    #

    # FTPd server implementation
    #
    # Possible values: proftpd, vsftpd
    #
    # Leave this parameter empty to automatically select the default FTP server
    # implementation, as set in the distribution packages file.
    FTPD_SERVER                         => '',

    # Passive port range
    #
    # If your server is behind a NAT router, you MUST not forget
    # to forward those TCP port.
    #
    # Possible values: A valid port range in range 32768-60999
    FTPD_PASSIVE_PORT_RANGE             => '32800 33800',

    #
    ## MTA server configuration parameters
    #

    # MTA server implementation
    #
    # Possible values: postfix
    #
    # Leave this parameter empty to automatically select the default MTA server
    # implementation, as set in the distribution packages file.
    MTA_SERVER                          => '',

    #
    ## IMAP, POP server configuration parameters
    #

    # POP/IMAP servers implementation
    #
    # Possible values: courier, dovecot
    # Leave this parameter empty to automatically select the default IMAP/POP
    # server implementation, as set in the distribution packages file.
    PO_SERVER                           => '',

    #
    ## SSL configuration for FTP, IMAP/POP and SMTP services
    #

    # Enable/disable SSL for various services (FTP, IMAP/POP, SMTP)
    #
    # Possible values: yes, no
    SERVICES_SSL_ENABLED                => 'yes',

    # Whether or not a self-signed SSL certificate must be generated
    #
    # Possible values: yes, no
    SERVICES_SSL_SELFSIGNED_CERTIFICATE => 'yes',

    # Services SSL certificate private key (only relevant for a trusted SSL
    # certificate)
    #
    # Possible values: SSL certificate private key path
    SERVICES_SSL_PRIVATE_KEY_PATH       => '',

    # Passphrase for the services SSL certificate private key (only relevant if
    # the private key is encrypted)
    #
    # Possible values: passphrase for the SSL certificate private key
    SERVICES_SSL_PRIVATE_KEY_PASSPHRASE => '',

    # Services SSL certificate CA Bundle (only relevant for a trusted SSL
    # certificate)
    #
    # Possible values: SSL certificate CA bundle path
    SERVICES_SSL_CA_BUNDLE_PATH         => '',

    # Services SSL certificate (only relevant for trusted SSL certificate)
    #
    # Possible values: SSL certificate path
    SERVICES_SSL_CERTIFICATE_PATH       => '',

    #
    ## Packages (addons)
    #

    # Web statistic packages
    #
    # Possible values: 'No', or a list of packages, each comma separated.
    # Available packages are: AWStats
    WEB_STATISTIC_PACKAGES              => 'AWStats',

    # Web FTP clients
    #
    # Possible values: 'No', or a list of packages, each comma separated.
    # Available packages are: MonstaFTP
    WEB_FTP_CLIENT_PACKAGES             => 'MonstaFTP',

    # SQL administrator tool packages
    #
    # Possible values: 'No', or a list of packages, each comma separated.
    # Available packages are: PhpMyAdmin
    SQL_ADMIN_TOOL_PACKAGES             => 'PhpMyAdmin',

    # Webmail client packages
    #
    # Possible values: 'No', or a list of packages, each comma separated.
    # Available packages are: RainLoop, Roundcube
    WEB_MAIL_CLIENT_PACKAGES            => 'RainLoop,Roundcube',

    # Antirootkits packages
    #
    # Possible values: 'No', or a list of packages, each comma separated.
    # Available packages are: Chkrootkit, Rkhunter
    ANTI_ROOTKIT_PACKAGES               => 'Chkrootkit,Rkhunter'
);

1;
