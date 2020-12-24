#!/usr/bin/perl

use strict;
use warnings;

# Preseeding template file for i-MSCP unattended installation
#
# See the documentation at http://wiki.i-mscp.net/doku.php?id=start:preseeding
#
# Author: Laurent Declercq <l.declercq@nuxwin.com>
# Last update: 2019.08.20

%::questions = (
    ###
    ### Mandatory parameters
    ### Unless otherwise stated, these parameters can't be left blank.
    ###

    # Server primary IP
    #
    # If you make use of a Cloud computing service such as Scaleway or Amazon
    # EC2, you should set the value to '0.0.0.0' which means that i-MSCP will
    # configures the services to listen on all interfaces rather than a 
    # specific interface.
    #
    # Possible values: A configured IPv4/IPv6 address, or 0.0.0.0 to make the
    #                  services listen on all interfaces.
    BASE_SERVER_IP                      => '',

    # Master administrator (control panel) password
    #
    # Only ASCII alphabet characters and digits are allowed in password.
    ADMIN_PASSWORD                      => '',

    # Master administrator email address
    #
    # Be aware that mails sent to local root user will be forwarded to this
    # email address.
    #
    # This email address is very important as this is the one to which i-MSCP
    # will send all system notifications such as errors. Furthermore, If you
    # make use of The i-MSCP LetsEncrypt plugin, this email will be also  used
    # for the account registration. 
    #
    # Possible value: A valid email address.
    DEFAULT_ADMIN_ADDRESS               => '',

    # SQL root username/password
    #
    # If you make use of a local SQL server, and if the unix_socket
    # authentication plugin is enabled for the SQL root user, you can leave
    # these parameters blank.
    #
    # The installer only make use of that SQL user account while installation.
    SQL_ROOT_USER                       => '',
    SQL_ROOT_PASSWORD                   => '',

    ###
    ### Parameters with default values
    ### All parameters below can be left 'AS THIS' if the default values fit
    ## your needs.
    ###

    #
    ## System configuration
    #

    # Server hostname
    #
    # Possible values: A fully qualified hostname name (FQHN)
    #
    # Leave this parameter blank for use of default value: server hostname.
    SERVER_HOSTNAME                     => '',

    # WAN IP
    #
    # You can force usage of a private IP by setting this parameter to the
    # value of the 'BASE_SERVER_IP' parameter instead of a public IP.
    #
    # If you have set the 'BASE_SERVER_IP' parameter value to '0.0.0.0', you
    # should leave this parameter blank.
    #
    # Possible values: an Ipv4 or IPv6 address
    #
    # Leave this parameter blank for use of default value: WAN IP
    BASE_SERVER_PUBLIC_IP               => '',

    # Server timezone
    #
    # Possible values: A valid timezone such as 'Europe/Berlin'
    # (see http://php.net/manual/en/timezones.php)
    #
    # Leave this parameter blank for use of default value: server timezone.
    TIMEZONE                            => '',

    #
    ## Backup configuration parameters
    #

    # Enable/Disable backup feature for the control panel database and
    # configuration files
    #
    # Possible values: yes, no
    #
    # Leave this parameter blank for use of default value: yes
    BACKUP_IMSCP                        => '',

    # Enable/Disable the backup feature for client data (Web data, SQL data,
    # and mail data)
    #
    # Enabling this feature will make the resellers able to enable/disable the
    # backup feature on a per client basis.
    #
    # Possible values: yes, no
    #
    # Leave this parameter blank for use of default value: yes
    BACKUP_DOMAINS                      => '',

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
    # Leave this parameter blank for use of default value: default SQL server
    # as set in distribution packages file.
    SQL_SERVER                          => '',

    # Keep the local SQL server installed regardless of the selected SQL server
    # implementation
    #
    # If there is a local SQL server installed locally, and if you choose the
    # remote SQl server alternative, this flag tells  the installer whether or
    # not the local server must be kept installed.
    #
    # Possible value: yes, no
    #
    # Leave this parameter blank for use of default value: yes
    KEEP_LOCAL_SQL_SERVER               => '',

    # SQL server hostname
    #
    # For a local SQL server, 'localhost' is the recommended value. Setting an
    # IP address such as '127.0.0.1' in place of 'localhost' would force
    # connections through TCP/IP rather than local socket connections.
    # 
    # For a remote SQL server, the 'localhost', '127.0.0.1' and '::1' entries
    # are irrelevant, and therefore, prohibited.
    #
    # Possible values: A valid hostname or IP address
    #
    # If you make use of a local SQL server, you can leave this parameter blank
    # for use of default value: localhost
    DATABASE_HOST                       => '',

    # SQL server port (only relevant for TCP/IP connection)
    #
    # Possible values: A port in range 1025 to 65535
    #
    # Leave this parameter blank for use of default value: 3306
    DATABASE_PORT                       => '',

    # i-MSCP Master SQL user
    #
    # Master SQL user for i-MSCP. That SQL user is used by both the i-MSCP
    # frontEnd and backend.
    #
    # Note that the debian-sys-maint, mysql.user, root  SQL users are not
    # allowed.
    #
    # Leave this parameter blank for use of default value: imscp_user
    DATABASE_USER                       => '',
    # Only ASCII alphabet characters and digits are allowed in password.
    #
    # Leave this parameter blank for use of default value: random password.
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
    #
    # Leave this parameter blank for use of default value which depending on
    # context is:
    # - Local SQL server: localhost
    # - Remote SQL server: WAN IP
    DATABASE_USER_HOST                  => '',

    # Database name (database for the control panel)
    #
    # Leave this parameter blank for use of default value: imscp
    DATABASE_NAME                       => '',

    # Enable/disable prefix/suffix for SQL databases/usernames
    #
    # Possible values: behind, infront, none
    #
    # Leave this parameter blank for use of default value: none
    MYSQL_PREFIX                        => '',

    #
    ## Control panel configuration parameters
    #

    # FrontEnd httpd server
    #
    # Possible value: nginx
    #
    # Leave this parameter blank for us of default value: default Http server
    # for the frontend (control panel) as set in distribution packages file.
    FRONTEND_SERVER                     => '',

    # Control panel hostname
    #
    # Hostname from which the control panel must be reachable.
    #
    # Possible values: A fully qualified hostname name (FQHN).
    #
    # Leave this parameter blank for use of default value:
    # panel.<SERVER_HOSTNAME>
    BASE_SERVER_VHOST                   => '',

    # Control panel http port
    #
    # Possible values: A port in range 1025-65535
    #
    # Leave this parameter blank for use of default value: 8880
    BASE_SERVER_VHOST_HTTP_PORT         => '',

    # Control panel https port (only relevant if SSL is enabled for the control
    # panel)
    #
    # Possible values: A port in range 1025-65535
    #
    # Leave this parameter blank for use of default value: 8443
    BASE_SERVER_VHOST_HTTPS_PORT        => '',

    # Enable/disable SSL for the control panel
    #
    # Possible values: yes, no
    #
    # Leave this parameter blank for use of default value: yes
    PANEL_SSL_ENABLED                   => '',

    # Whether or not a self-signed SSL certificate must be generated for the
    # control panel
    #
    # Possible values: yes, no
    #
    # Leave this parameter blank for use of default value: yes
    #
    # Warning: This parameter has a higher precedence than other SSL related
    # parameters. If you want provide your own SSL certificate, be sure to set
    # the value of this parameter to 'no'.
    PANEL_SSL_SELFSIGNED_CERTIFICATE    => '',

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
    #
    # Leave this parameter blank for use of default value: yes
    CLIENT_WEBSITES_ALT_URLS            => '',

    # Control panel access mode (only relevant if SSL is enabled for the control
    # panel)
    #
    # Possible values: http://, https://
    #
    # Leave this parameter blank for use of default value: http:// or https//,
    # depending whether or not SSL is enabled for the control panel. Note that
    # if SSL is disabled for the control panel, this parameter will be set to
    # http:// regardless of the value set.
    BASE_SERVER_VHOST_PREFIX            => '',

    # Master administrator account credentials (control panel)
    #
    # Leave this parameter blank for use of default value: admin
    ADMIN_LOGIN_NAME                    => '',

    #
    ## DNS server configuration
    #

    # DNS server implementation
    #
    # Possible values: bind, external_server
    #
    # Leave this parameter blank for us of default value: default DNS server
    # as set in distribution packages file.
    NAMED_SERVER                        => '',

    #
    # Bind server implementation configuration parameters
    #

    # DNS server mode
    #
    # Possible values: master, slave
    #
    # Leave this parameter blank for use of default value: master
    BIND_MODE                           => '',

    # Master DNS IP addresses (Only relevant when the value of the 'BIND_MODE'
    # parameter is set to 'master')
    #
    # Possible value: 'no', or a list of IPv4/IPv6 addresses, each separated by
    # semicolon or space.
    #
    # Leave this parameter blank for use of default value: no
    PRIMARY_DNS                         => '',

    # Slave DNS IP addresses (Only relevant when the value of the 'BIND_MODE'
    # parameter is set to 'slave')
    #
    # Possible value: 'no', or a list of IPv4/IPv6 addresses, each separated by
    # semicolon or space
    #
    # Leave this parameter blank for use of default value: no
    SECONDARY_DNS                       => '',

    # IPv6 support
    #
    # Possible values: yes, no
    #
    # Leave this parameter blank for use of default value: no
    BIND_IPV6                           => '',

    # Local DNS resolver
    #
    # Make use of the local DNS server (bind9) for the local DNS resolution.
    #
    # Possible values: yes, no
    #
    # Leave this parameter blank for use of default value: yes
    LOCAL_DNS_RESOLVER                  => '',

    #
    ## Httpd server configuration parameters
    #

    # Httpd server implementation
    #
    # Possible values: apache_itk, apache_fcgid or apache_php_fpm (recommended)
    #
    # Leave this parameter blank for us of default value: Default Httpd server
    # as set in distribution packages file.
    HTTPD_SERVER                        => '',

    #
    ## PHP configuration parameters
    #

    # PHP version for customers
    #
    # Possible values: php5.6, php7.0, php7.1, php7.2, php7.3
    #                  php7.4 (recommended) or php8.0
    #
    # Leave this parameter blank for us of default value: Default PHP version
    # as set in distribution packages file.
    PHP_SERVER                          => '',

    # PHP configuration level
    #
    # If you make use of the PhpSwitcher plugin, you need set the value to
    # 'per_site'.
    #
    # Possible values: per_user, per_domain, per_site
    #
    # Leave this parameter blank for use of default value: per_site
    PHP_CONFIG_LEVEL                    => '',

    # PHP-FPM listen socket type (Only relevant with the 'apache_php_fpm'
    # server implementation)
    #
    # Possible values: uds (recommended), tcp
    #
    # Leave this parameter blank for use of default value: uds
    PHP_FPM_LISTEN_MODE                 => '',

    #
    ## FTPd server configuration parameters
    #

    # FTPd server implementation
    #
    # Possible values: proftpd, vsftpd
    #
    # Leave this parameter blank for us of default value: default FTP server
    # as set in distribution packages file.
    FTPD_SERVER                         => '',

    # Passive TCP port range
    #
    # If your server is behind a NAT router, you MUST not forget
    # to forward those TCP ports.
    #
    # Possible values: A port range in range 32768-60999
    #
    # Leave this parameter blank for use of default value: 32800 33800
    FTPD_PASSIVE_PORT_RANGE             => '',

    #
    ## MTA server configuration parameters
    #

    # MTA server implementation
    #
    # Possible values: postfix
    #
    # Leave this parameter blank for us of default value: default MTA server
    # as set in distribution packages file.
    MTA_SERVER                          => '',

    #
    ## IMAP, POP server configuration parameters
    #

    # POP/IMAP servers implementation
    #
    # Possible values: courier, dovecot
    #
    # Leave this parameter blank for us of default value: default IMAP/POP
    # server  as set in distribution packages file.
    PO_SERVER                           => '',

    #
    ## SSL configuration for FTP, IMAP/POP and SMTP services
    #

    # Enable/disable SSL for various services (FTP, IMAP/POP, SMTP)
    #
    # Possible values: yes, no
    #
    # Leave this parameter blank for use of default value: yes
    SERVICES_SSL_ENABLED                => '',

    # Whether or not a self-signed SSL certificate must be generated
    #
    # Possible values: yes, no
    #
    # Leave this parameter blank for use of default value: yes
    #
    # Warning: This parameter has a higher precedence than other SSL related
    # parameters. If you want provide your own SSL certificate, be sure to set
    # the value of this parameter to 'no'.
    SERVICES_SSL_SELFSIGNED_CERTIFICATE => '',

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
    #
    # Leave this parameter blank for use of default value: AWStats
    WEB_STATISTIC_PACKAGES              => '',

    # Web FTP clients
    #
    # Possible values: 'No', or a list of packages, each comma separated.
    # Available packages are: MonstaFTP
    #
    # Leave this parameter blank for use of default value: MonstaFTP
    WEB_FTP_CLIENT_PACKAGES             => '',

    # SQL administrator tool packages
    #
    # Possible values: 'No', or a list of packages, each comma separated.
    # Available packages are: PhpMyAdmin
    #
    # Leave this parameter blank for use of default value: PhpMyAdmin
    SQL_ADMIN_TOOL_PACKAGES             => '',

    # Webmail client packages
    #
    # Possible values: 'No', or a list of packages, each comma separated.
    # Available packages are: RainLoop, Roundcube
    #
    # Leave this parameter blank for use of default value: RainLoop,Roundcube
    WEB_MAIL_CLIENT_PACKAGES            => '',

    # Antirootkits packages
    #
    # Possible values: 'No', or a list of packages, each comma separated.
    # Available packages are: Chkrootkit, Rkhunter
    #
    # Leave this parameter blank for use of default value: Chkrootkit,Rkhunter
    ANTI_ROOTKIT_PACKAGES               => ''
);

1;
__END__
