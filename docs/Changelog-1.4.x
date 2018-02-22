i-MSCP ChangeLog

------------------------------------------------------------------------------------------------------------------------
1.4.7
------------------------------------------------------------------------------------------------------------------------

2017-07-10: Laurent Declercq
    RELEASE i-MSCP 1.4.7

BACKEND
    Added: iMSCP::EventManager::registerOne() method -- Allows to register a listener that will be executed at most once
    Added: `nodeferring' option to iMSCP::Config -- Allows to disable deferred writing
    Fixed: CRON(8), AT(1) and LPQ(1) jobs that belong to a user being deleted must be also deleted (iMSCP::SystemUser)
    Fixed: Don't execute system USERMOD(8) command when not necessary
    Fixed: Make sure that immutable bit is preserved when moving user' homedir (iMSCP::SystemUser)
    Fixed: Make sure that immutable bit is removed when removing user (iMSCP::SystemUser)
    Fixed: Prevent releasing locks in child processes (iMSCP::LockFile)
    Rewritten: iMSCP::SystemUser and iMSCP::SystemGroup libraries
    Rewritten: Module::User module
    Security: Prevent modification/deletion of root user/group (iMSCP::SystemUser, iMSCP::SystemGroup)

CONFIG
    Added: `BIND_DB_ROOT_DIR' configuration parameter (Bind9)
    Added: `DOVECOT_VERSION' configuration parameter (Dovecot)
    Enhancement: Alternative URLs for client domains can now be disabled - see the errata file for further details
    Fixed: Prefer IPv4 to prevent timeout issues (/etc/gai.conf)
    Updated: Vhost template files (Apache2)
    Updated: Zone template files (Bind9)

BACKEND
    Fixed: Avoid triggering change of all subdomains that belong to a parent domain that is being changed (historical issue)
    Fixed: Don't load File::Temp module through Class::Autouse module as this can lead to compile time errors
    Removed: Unix user prefix from alternative URLs

DISTRIBUTIONS
    Added: dirmngr package in list of pre-required packages as it is needed by gnupg2 for network access (Debian 9/Stretch)
    Fixed: libmysqlcient18 package not available for MySQL 5.7 server community (Debian 9/Stretch)

FRONTEND
    Fixed: Couldn't add BASE_SERVER_VHOST as customer domain
    Fixed: Invalid parameter number: number of bound variables does not match number of tokens (Software Installer)
    Fixed: Trailing slash added twice in URI path when creating subdomain with redirect feature enabled
    Removed: Unix user prefix from alternative URLs

PACKAGES
    Fixed: Event listener responsible to add the Apache2 configuration snippet is registered too late (AWStats)

SCRIPTS
    Fixed: Make sure that scripts are run by root user when needed

SERVERS
    Changed: DNS records for alternative URLs are now added as subdomain DNS record without www and ftp records (Bind9)
    Fixed: Calling createUser()/dropUser() with `RaiseError' flag set to 1 raise an error (SQL server impl.)
    Fixed: Couldn't switch from external to local DNS server
    Fixed: Invalid ssl_protocols setting: Unknown protocol 'SSLv2' when Dovecot is built against libssl ≥ 1.1.x
    Fixed: Make use of POSTCONF(1) to remove unwanted parameters (Postfix)
    Fixed: Never process the same zone twice for SOA addition (Bind9)
    Review: Engine permissions (Bind9)

PLUGINS
    Fixed: Exception not throw when executing multiple SQL statements in single query (Database migrations - PHP: #61613)

INSTALLER
    Added: Setup dialog for alternative URLs feature
    Fixed: Avoid piping WGET(1) output to APT-KEY(8); Make use of temporary file to store APT key for better error handling
    Fixed: apache2_postinst.sh: Raised error if there is a version mismatch between installed package and source package
    Fixed: apt-key output should not be parsed (stdout is not a terminal)
    Fixed: gpg: failed to start the dirmngr '/usr/bin/dirmngr': No such file or directory
    Fixed: Lose of data in configuration files due to deferred writing (all servers/packages)
    Fixed: Make sure that LOGROTATE(8) configuration files are copied with expected ownership and permissions
    Fixed: Make sure that none of package being installed/updated is in `hold' state
    Fixed: The `IPV6_SUPPORT' configuration parameter is never set (Servers::server::local::installer)
    Fixed: The `IPV6_SUPPORT' configuration parameter must be set early (Servers::server::local::installer)
    Fixed: The master configuration file (imscp.conf) must stay writable during all setup process

VENDOR
    Updated: IDNA Convert library to version 1.1.0 (with feww modifications for PSR-0 compatibility)

------------------------------------------------------------------------------------------------------------------------
1.4.6
------------------------------------------------------------------------------------------------------------------------

2017-06-16: Laurent Declercq
    RELEASE i-MSCP 1.4.6

BACKEND
    Added: iMSCP::LockFile class - Implements file locks for locking files in UNIX
    Review: Store lock files in /var/lock instead of /tmp

DISTRIBUTIONS
    Added: Support for MySQL community server 5.6/5.7 (Debian 9/Stretch)

SERVERS
    Fixed: Possible "File not found" error with Seafile (Apache < 2.4.11 with mod_proxy_fcgi)
    Fixed: Usage of undefined $conffile variable in vsftpd server uninstaller (vsftpd)

INSTALLER
    Fixed: Dialog gauges overlapping

YOUTRACK
    IP-1723 Race condition in i-MSCP' file locking strategy leading to concurrent processes when that is not expected

------------------------------------------------------------------------------------------------------------------------
1.4.5
------------------------------------------------------------------------------------------------------------------------

2017-06-13: Laurent Declercq
    RELEASE i-MSCP 1.4.5

BACKEND
    Added: iMSCP::Cwd package - Allows to restrict scope of chdir() to enclosing block
    Review: Prioritize servers and packages to avoid overuse of events

CONFIG
    Reverted: Merging of Nginx site configuration files (Regression fix)

DISTRIBUTIONS
    Fixed: Erroneous MariaDB repository (Ubuntu 14.04, 16.04)
    Fixed: libmysqlclient20 package must be installed in any case (Ubuntu 16.04; Regression fix)
    Fixed: Missing switch for MariaDB 10.2 server in distribution packages files

FRONTEND
    Fixed: Cannot create catch-all email account (Forward mail) due to erroneous SQL query (Regression fix)

INSTALLER
    Added: Support for package preinstall tasks
    Added: `local_server` configuration parameter; Allows reconfiguration of system hostname, primary IP and timezone
    Fixed: Couldn't get APT key from keys.gnupg.net keys server due to DNS failure
    Fixed: Possible missing /etc/mysql/mariadb.conf.d directory when upgrading MariaDB server
    Moved: SQL server related routines into SQL server installer

LISTENERS
    Fixed: 10_nginx_hsts.pl listener file: nginx: [emerg] "add_header" directive is not allowed (Regression fix)
    Updated: 10_named_slave_provisioning.pl listener file: Add configuration in SSL server too

PACKAGES
    Added: Package for backup feature (Package::Backup)
    Added: Package for services SSL (Package::ServicesSSL)
    Fixed: Create relative links instead of absolute links for Nginx site configuration files (Package::FrontEnd)

SCRIPTS
    Fixed: imscp-backup-all backup script: Missing domain name in web backup archive names
    Fixed: imscp-backup-imscp backup script: Global symbol "$dbName" requires explicit package name (Regression fix)

SERVERS
    Added: System local server implementation (Servers::server::local)
    
YOUTRACK
    IP-1722 Missing IP addresses in event logs

------------------------------------------------------------------------------------------------------------------------
1.4.4
------------------------------------------------------------------------------------------------------------------------

2017-06-08: Laurent Declercq
    RELEASE i-MSCP 1.4.4

BACKEND
    Added: Event listener priority queue; Allows to set priority for event listeners in range [-1000 .. 1000]
    Added: iMSCP::Dir::clear() method - Allows to clear full or specific content of a directory
    Added: Modules::ServerIP module (replace both Modules::Ips and Modules::NetworkInterfaces modules)
    Enhancement: Objects can now be registered as event listener (iMSCP::EventManager)
    Fixed: Can't use an undefined value as a HASH reference; DBI::selectrow_hashref() return undef on empty results
    Fixed: Fallback to INADDR_ANY address IP if a domain IP is not found in database
    Fixed: Make use of Full Stop unicode character to encode dots in database names (iMSCP::Database::mysql::dumpdb)
    Fixed: Make use of SOLIDUS unicode character to encode slashes in database names (iMSCP::Database::mysql::dumpdb)
    Fixed: Ownership and permissions not set (iMSCP::Dir::make)
    Removed: Modules::Ips and Modules::NetworkInterfaces modules
    Removed: Support for systemd older than 204-3 (iMSCP::Provider::Service::Debian::Systemd)
    Removed: Support for sysv-rc older than 2.88 (iMSCP::Provider::Service::Debian::Sysvinit)

CONFIG
    Added: `BIND_DB_FORMAT' configuration parameter (Bind9)
    Added: `BIND_DB_MASTER_DIR' configuration parameter (Bind9)
    Added: `BIND_DB_SLAVE_DIR' configuration parameter (Bind9)
    Added: 'CMD_SYSCTL` and 'SYSCTL_CONF_DIR` parameters for sysctl(8) (master)
    Added: `PluginApi' configuration parameter (master)
    Fixed: Debian httpredir.debian.org service is discontinued (See https://wiki.debian.org/DebianGeoMirror)
    Fixed: Make sure that the root directory is marked as shared in regards to mount propagation (at least for i-MSCP mounts)
    Fixed: Prevent upgrade of Percona server while processing disttribution upgrade (all distributions; APT pinning)
    Merged: Nginx site configuration files (FrontEnd)
    Removed: *.old.data configuration files (servers/packages)
    Removed: `BIND_DB_DIR' configuration parameter (replaced by `BIND_DB_MASTER_DIR' and `BIND_DB_SLAVE_DIR' configuration parameters)
    Renamed: All Nginx configuration template files (changed extension from .conf to .nginx for IDE syntax highlighting) (FrontEnd)

DATABASE
    Updated: domain_dns.domain_dns_status column length (VARCHAR to TEXT)

CRON
    Fixed: Cron tasks for traffic accounting are never executed due to wrong command in /etc/cron.d/imscp cron file

DISTRIBUTIONS
    Added: Support for Devuan jessie 1.0 - See https://devuan.org/
    Added: Support for MariaDB 10.2 (all distributions)
    Changed: Install nginx-full package instead of nginx-light package (Nginx)
    Fixed: invoke-rc.d: action rotate is unknown, but proceeding anyway. (See Debian bug report #728682)
    Fixed: Wrong GPG key for MariaDB repository (Ubuntu 16.04)

FRONTEND
    Added: 502 (Bad Gateway) error page
    Added: Include directive for loading of dynamic modules (Nginx)
    Fixed: Allow auto-completion for login form (regression fix)
    Fixed: Allow lower value than `post_max_size' for `memory_limit' (PHP Editor)
    Fixed: Couldn't redirect full domains, subdomains to non-standard port
    Fixed: Creation of mailboxes for Postfix canonical domains through the control panel must be prohibited
    Fixed: Integrity constraint violation: 1052 Column 'fname' in WHERE clause is ambiguous when searching user by name
    Fixed: mail_users.po_active database column must be set to 'no' for forward only and catch-all accounts
    Fixed: Many location blocks match unwanted resources (eg: /pma will also matches with /pmailman and that is unwanted) (Nginx)
    Fixed: Non well formed numeric value encountered in utils_getPhpValueInBytes() function
    Fixed: Nginx is now able to determines number of available CPU cores (Nginx)
    Fixed: Stop matching process as soon as possible (Nginx)
    Removed: Check for CNAME conflict (Custom DNS records)

LISTENERS
    Added: 10_backup_storage_outsourcing.pl: Allows storage of customer backup directories elsewhere on the file system
    Added: 10_proftpd_auth_unix.pl listener file: Enable unix authentication
    Added: 10_proftpd_serverident.pl file: Set custom server identification message (replace 10_proftpd_tuning.pl)
    Added: 10_proftpd_tls.pl file: Enforce TLS (replace 10_proftpd_tuning.pl)
    Fixed: 10_named_override_default_rr.pl: Cannot override default www CNAME record
    Fixed: 20_named_dualstack.pl listener file: Missing DNS records for subdomains
    Fixed: Listeners from listener files that listen on the `afterMtaBuildConf' event must be registered with low priority
    Updated: 10_nginx_hsts.pl listener file: Nginx configuration template filenames were updated +++
    Updated: 10_named_slave_provisioning.pl listener file: Nginx configuration template filenames were renamed +++
    Updated: 70_postfix_submission_tls.pl file: Fully redefines submission service and disable SSLv3

INSTALLER
    Added: `None' option as possible choice for server primary IP address. Fixes networking issues on Scaleway and Amazon EC2 servers
    Added: Routine to setup kernel (loading of kernel parameters from imscp.conf sysctl configuration file)
    Fixed: Don't check for source archive when binary archive has not been found for a specific section (APT repositories)
    Fixed: Usage of ZG-POLICY-RC.D(8) to provide local policy-rc.d and avoid violation of Debian policy (Debian/Ubuntu)
    Fixed: SQL server must be restarted prior cron, ftp, http, mail and panel services
    Removed: `beforeInstallPreRequiredPackages' event (Event cannot be triggered before installation of pre-required packages)

PACKAGES
    Fixed: Cron tasks are added too early (AWStats, Chkrootkit, Rkhunter)
    Fixed: Don't instantiate packages when not really needed
    Fixed: RainLoop entries are not removed from database when a mail account is deleted (RainLoop)
    Updated: PhpMyAdmin package installer for use of PhpMyAdmin 4.7.0
    Updated: RainLoop package installer for use of RainLoop 1.11.0.203

PLUGINS
    Added: iMSCP::Plugins::getClass() method for easy plugin loading and class retrieval (backend)
    Changed: Plugin errors, outside those raised by the Modules::Plugins, are no longer returned to the caller (backend)
    Fixed: Avoid to instantiate plugins twice when an action involve a sub-action (backend Modules::Plugin)
    Fixed: Plugin API version not available in plugin package instances (backend)
    Fixed: Wrong default value for the `plugin_lockers' database field
    Fixed: Wrong status set for un-installable plugins after their uninstallation (`uninstalled' while `disabled' is expected)
    Removed: PLUGIN_API_VERSION constant from iMSCP_Plugin_Manager class (replaced by master `PluginApi' configuration parameter)
    Updated: API version to 1.4.1

SCRIPTS
    Fixed: Don't automatically mount file systems that have the `noauto' option (imscp-mountall)
    Rewritten: imscp-backup-all backup script
    Rewritten: imscp-dsk-quota quota script
    Rewritten: imscp-vrl-traff traffic accounting script

SERVERS
    Added: Explicite IMAP/POP3 log format (Dovecot)
    Added: In/Out bytes variables in POP3 logs for better traffic accounting (Dovecot)
    Changed: Directory for master zone files moved from /var/cache/bind to /var/cache/bind/imscp/master (Bind9)
    Changed: Directory for slave zone files moved from /var/cache/bind/slave to /var/cache/bind/imscp/slave (Bind9)
    Changed: Make use of relative paths for zone files instead of absolute paths in named.conf.local file (Bind9)
    Changed: Zone files are now dumped in binary format for rapid loading by named (Bind9)
    Fixed: Argument "" isn't numeric in addition (+)... in getTraffic() method of all servers implementing it
    Fixed: Don't instantiate servers when that is not necessary
    Fixed: Don't POSTMAP(1) when not necessary (Postfix)
    Fixed: Error: quota: Unknown namespace: Trash (Dovecot/IMAP)
    Fixed: Maildir not removed when turning normal mail account into forward only mail account (Postfix)
    Fixed: Make use of pristine suexec for better performances (Apache2/Suexec)
    Fixed: Recipient address rejected: User unknown in local recipient table (Postfix)
    Fixed: Skip write operation in Servers::Postfix::deleteMapEntry() when not necessary (Postfix)
    Fixed: Unwanted directory listings (Apache2 - Regression fix)
    Fixed: warning: do not list domain xxxxxx in BOTH `mydestination' and `virtual_mailbox_domains' (Postfix)
    Fixed: Wrong POP3 traffic accounting (Dovecot)
    Removed: Backup and working directories (Cron)
    Removed: `beforeHttpdAddIps' and `afterHttpdAddIps' events (Httpd server impl.)
    Removed: Useless addIps() routine; The `NameVirtualHost' directive is no longer needed with Apache 2.4 (Httpd server impl.)
    Rewritten: getTraffic() routine for all servers that implement it

SYSTEM
    Fixed: Set swapiness to lower value (10) than default (60) for better memory management

VENDOR
    Updated: maillogconvert.pl script to version 1.2 (revision 20140126)

YOUTRACK
    IP-1698 Restoring a backup causes domain status "unexpected error"
    IP-1715 Changing directory in protected area results in obsolete .htaccess file in old directory
    IP-1716 PHP editor - Caret is jumping to the end of the input field each time validation is triggered
    IP-1717 Wrong GPG key for MariaDB repository (Ubuntu 16.04)
    IP-1721 Couldn't backup databases named with slashes

------------------------------------------------------------------------------------------------------------------------
1.4.3
------------------------------------------------------------------------------------------------------------------------

2017-04-17: Laurent Declercq
    RELEASE i-MSCP 1.4.3

CONFIG
    Added: `class', `default' and `description' attributes in distribution packages files for service alternatives
    Added: /etc/apt/apt/conf.d/01norecommend to avoid installation of recommended/suggested distribution packages
    Added: `*._PACKAGE' variables in master conffile. Variables holding i-MSCP server class names
    Removed: FastCGI section in Apache2 domain.tpl vhost template (mod_proxy_fcgi is now available for all distributions)
    Removed: SSLCertificateChainFile Apache2 directive in domain.tpl vhost template file (no longer needed since Apache 2.4.8)

BACKEND
    Added: iMSCP::File::getAsRef() method - Method allowing to get scalar reference to file content
    Added: Servers::php class (doesn't do anything yet - currently a child of Servers::noserver)
    Fixed: Couldn't umount mounts that are in deleted state due to wrong regexp in iMSCP::Mount library
    Review: Mitigate IO operations by reading full fstab-like file in memory and by reusing same file object (iMSCP::Mount)

DISTRIBUTIONS
    Fixed: Install Apache2 (2.4.10) from backports repository (Ubuntu Trusty Thar)
    Fixed: Make sure that sendmail* packages are pre-uninstalled

FRONTEND
    Added: Button allowing customers to force syncing of mailboxes quota info (Client interface)
    Added: Percent value for mailboxes quota usage (Client interface)
    Fixed: Datepicker is positioned below the location bar
    Fixed: Disabled htaccess, htgroup and htusers items wrongly reported as error in debugger interface (admin interface)
    Enhancement: Display a static warning to customer when at least one of his mailboxes is over quota (Client interface)
    Readded: Mailboxes quota usage information (Client interface)

INSTALLER
    Changed: Default SQL user for servers/packages is now set to `imscp_srv_user'
    Fixed: `Access denied...' SQL error when reusing same SQL user for all i-MSCP servers/packages
    Fixed: Allow for automatic generation of the passwords (whenever possible) in preseeding mode
    Fixed: Allow for automatic detection of the server public IP address in preseeding mode
    Fixed: Allow for automatic detection of the timezone in preseeding mode
    Fixed: composer.phar archive is not updated due to wrong working directory
    Fixed: Configures getaddrinfo(3) to prefer IPv4 during setup and update phases
    Fixed: Enforce usage of values from preseed.pl file in preseeding mode, even on reconfiguration
    Fixed: Make sure that SQL users set for i-MSCP servers/packages are not already used by customers
    Fixed: When switching to another FTPD server implementation, uninstallation of old FTP server must be triggered
    Fixed: When switching to another PO server implementation, uninstallation of old PO server must be triggered 
    Readded: Courier alternative for IMAP/POP servers (Debian Stretch)

LISTENERS
    Updated: Add Referrer-Policy header to 40_apache2_security_headers.pl listener file

PACKAGES
    Fixed: `Accessing a non-existing parameter: FILEMANAGER_PACKAGE` error when upgrading from 1.1.x Serie (Filemanager)
    Fixed: Can't locate object method "getInstance" via package "MSCP::Service" (FrontEnd)
    Fixed: Package uninstallers must be idempotent

SCRIPTS
    Fixed: All in/out traffic not computed (imscp-srv-traff)

SERVERS
    Fixed: Disable control engine by default (ProFTPD)
    Fixed: `postfix' user is still part of authdaemon group after switching to Dovecot (Courier)
    Fixed: Run ProFTPD server under `proftpd' user instead of `nobody' user (ProFTPD)
    Fixed: SQL user not removed after uninstallation (PO and FTPD server impl.)

UNINSTALLER
    Fixed: Ask administrator to uninstall plugins prior running i-MSCP uninstaller
    Fixed: Authdaemond socket directory is still mounted in Postfix chroot after switching to Dovecot (Courier)
    Fixed: General uninstallation failure due to wrong SQL query in imscp-uninstall script
    
YOUTRACK
    IP-1707 Getting Error when trying to change e-mail forwarder due to wrong quota value
    IP-1709 Protected area users/groups management - Groups list is truncated to first group
    IP-1710 Password field of client prepopulated when called from reseller
    IP-1711 Error while using preseed file with empty BASE_SERVER_PUBLIC_IP parameter

------------------------------------------------------------------------------------------------------------------------
1.4.2
------------------------------------------------------------------------------------------------------------------------

2017-04-04: Laurent Declercq
    RELEASE i-MSCP 1.4.2

CRONJOBS
    Changed: Niceness from 0 to 10 for the imscp-srv-traff and imscp-vrl-traff i-MSCP core cron tasks
    Changed: Niceness from 15 to 10 for i-MSCP core cron tasks

FRONTEND
    Fixed: Couldn't create email account with unlimited quota, even if email quota for customer is unlimited
    Fixed: Couldn't create forward email account if hidden quota field from normal email account is not valid
    Review: Moved links for alternative URLs on the line of the main URLs for a cleaner overview (Client interface)

LISTENERS
    Fixed: Missing test for SSL in the 30_apache2_tools_proxy.pl listener file

SERVERS
    Fixed: FastCgiExternalServer: redefinition of previously defined class (apache_php_fpm httpd server impl.)
    Fixed: Modules::Plugin::_call: Can't locate object method "getInstance" via package "Servers::mta::postfix" (po servers impl.)

SERVICES
    Changed: Niceness from 0 to 10 for imscp_daemon and imscp_panel services 

YOUTRACK
    IP-1670 Customer must be still able to create forward email account, even when email quota is full assigned

------------------------------------------------------------------------------------------------------------------------
1.4.1
------------------------------------------------------------------------------------------------------------------------

2017-03-28: Laurent Declercq
    RELEASE i-MSCP 1.4.1

DATABASE
    Fixed: Delete invalid default email accounts

DISTRIBUTION
    Fixed: Couldn't install unauthenticated distribution packages - Wrong attribute name in packages file

INSTALLER
    Fixed: Don't allows switching to other SGBD vendor (most of the time, there are pre-required manual tasks to be done)

LISTENERS
    Updated: All Dovecot listener files according latest changes

PACKAGES
    Fixed: AWStats interface must not be reachable for redirected or proxied sites (revert)
    Fixed: Couldn't create /run/imscp service directory in some environments - systemd-tmpfiles command fails (FrontEnd)

SERVERS
    Changed: Usage of `mpm_event' in place of `mpm_worker' (Apache_fcgid httpd server impl.)
    Fixed: Couldn't dump database with Percona server 5.7.x - MySQL 5.6.x compatibility mode must be enabled
    Fixed: Usage of new syntax for piped logs (Apache2)
    Fixed: Vhost files for disabled domains are badly generated when SSL is enabled (Httpd server impl.)
    Fixed: Vhost files for forwarded SSL domains are badly generated (Httpd server impl.)
    Fixed: vlogger process not terminated (unpredictable context), leading to high CPU load (Httpd server impl.)
    Fixed: Wrong dovecot configuration used due to wrong conditional test (Dovecot PO server impl.)
    Removed: Configuration file for Dovecot older than version 2.1 (no longer needed)

------------------------------------------------------------------------------------------------------------------------
1.4.0
------------------------------------------------------------------------------------------------------------------------

2017-03-26: Laurent Declercq
    RELEASE i-MSCP 1.4.0

ARPL
    Fixed: ARPL is failing due to unexpected encoding
    Removed: imscp-arpl-msgr log directory (ARPL error logs goes now into /var/log/mail.log)

BACKEND
    Added: isRoutableAddr() method to check whether a given IP address is world-routable (iMSCP::Net)
    Added: Support for prefix length (iMSCP::Net::addAddr())
    Changed: Event logging is now done on a per module basis - see the errata file for further details
    Changed: Listener files from deprecated /etc/imscp/hooks.d directory are now ignored (iMSCP::EventManager)
    Fixed: Couldn't add IP address without label (iMSCP::Net::addAddr())
    Fixed: Couldn't set user/group on dangling symlinks (iMSCP::Rights::setRights())
    Fixed: Don't change permissions on symlink targets (iMSCP::File & MSCP::Rights)
    Fixed: Don't connect to SQL server when that is not needed (iMSCP::Database::mysql)
    Fixed: Error `net.ipv6.conf.eth0:0.autoconf is an unknown key' (iMSCP::Provider::NetworkInterface::Debian)
    Fixed: Force addition of `CREATE DATABASE' statement in SQL dumps, even for empty databases
    Fixed: Make sure that ownership is fixed recursively when restoring a Web backup
    Fixed: Restore database using temporary SQL user in place of customer SQL user (Modules::Domain)
    Fixed: Several encoding issues (regression fix)
    Fixed: Usage of lchown(2) system call to avoid dereference of symlinks (iMSCP::Rights::setRights())
    Review: Read line by line to avoid opening in-memory file in STDOUT|STDERR routines (iMSCP::Execute::executeNoWait())
    Fixed: Read mount entries from /proc/self/mounts file to cover case where /etc/mtab is not a symlink (iMSCP::Mount)

CONFIG
    Changed: Usage of Courier authdaemon as password verifier (Cyrus SASL) - see the errata file for further details
    Merged: domain_redirect.tpl, domain_redirect_ssl.tpl and domain_ssl.tpl templates in domain.tpl template (Apache2)
    Merged: domain_disabled_ssl.tpl template in domain_disabled.tpl template (Apache2)
    Removed: domain_ssl.tpl, domain_redirect.tpl, domain_redirect_ssl.tpl and domain_disabled_ssl template files (Apache2)
    Updated: Courier configuration for use of new password scheme (SHA512-CRYPT)
    Updated: Cyrus SALS configuration for use of new password scheme (SHA512-CRYPT)
    Updated: Dovecot configuration for use of new password scheme (SHA512-CRYPT)

SCRIPTS
    Added: Support for IPv6 traffic data (imscp-srv-traff)
    Fixed: Missing iptables chains/rules for IPv6 traffic logging (imscp-net-traffic-logger)

DAEMON
    Fixed: Default Makefile target must not involves the `clean' target

DATABASE
    Removed: `ftp_users.rawpasswd' column (i-MSCP database)
    Removed: `sql_user.sqlu_pass' column (i-MSCP database)
    Updated: `server_ips.ip_number` column length (i-MSCP database)

DISTRIBUTIONS
    Added: MariaDB 10.1 for Ubuntu Xenial Xerus
    Added: Percona SQL Server (5.5, 5.6, 5.7) for Ubuntu Xenial Xerus
    Added: PHP 5.6, 7.0, 7.1 alternatives for Debian Jessie/Stretch through Ondřej Surý repository
    Added: PHP 5.6, 7.0, 7.1 alternatives for Ubuntu Trusty/Xenial through Ondřej Surý PPA
    Dropped: Support for Debian Wheezy - Many softwares and library are really too old
    Dropped: Support for PHP versions that are considered EOL by upstream PHP team (Ubuntu/Debian)
    Dropped: Support for Ubuntu Precise Pangolin (12.04) - Will be EOL on April 2017
    Updated: Debian Stretch packages file according last state of repository (full freeze since 20170205)

DOCUMENTATION
    Added: CGI script sample for Perl, Python and Ruby

FRONTEND
    Added: Function for overriding of native JS alert() function
    Added: jQuery.imscp.confirm() and jQuery.imscp.confirmOnclick() global jQuery functions for confirmation dialogs
    Added: Method to get IP address version (iMSCP::Net)
    Added: Method to get IP prefix length (iMSCP::Net)
    Added: Methods to compress/expand IPv6 addresses (iMSCP::Net)
    Changed: Defer loading of NIC and IP data (iMSCP::Net)
    Changed: Restricts character range for password generator to ASCII alphabet characters and numbers
    Fixed: {CUSTOMER} template variable is not replaced in reseller alias order email notification
    Fixed: Administrators cannot switch onto reseller/customer interface when database update is available
    Fixed: Don't list software that require database for customers that have not SQL feature enabled
    Fixed: `iMSCP_Exception_Production' class not compatible with PHP ≥ 7.0
    Fixed: Infobox for new alias orders must be static (reseller/index.php)
    Fixed: IP address input field is too small (admin/ip_manage.php)
    Fixed: Store compressed IPv6 (ip_manage.php)
    Fixed: Try to guess the prefix length whenever possible (ip_manage.php)
    Fixed: Usage of non-numeric values (iMSCP_pTemplate)
    Fixed: When IP address is pasted, netmask input field is not updated (admin/ip_manage.php)
    Removed: PhpMyAdmin auto-login feature (password for SQL database are no longer stored plaintext in database)
    Removed: Pydio auto-login feature (password for FTP users are no longer stored plaintext in database)
    Review: Increased value for the PHP `post_max_size' and `upload_max_filesize' directives

INSTALLER
    Fixed: APT GPG keys not updated when required
    Fixed: Missing `mysql' group; the `mysql' group is only created by the mysql-server package (SQL remote server impl.)
    Fixed: Patch for Apache 2 mod_proxy_fcgi module not required if Apache version is ≥ 2.4.24
    Fixed: Patches for libpam-mysql not required if libpam-mysql version is ≥ 0.8.0
    Fixed: Several files containing critical data are created world-readable, giving time to other processes to read them
    Fixed: `W: Download is performed unsandboxed as root as file...' warning with newest APT versions
    Moved: Distribution package files from ./docs directory to ./autoinstaller/Packages directory
    Review: Forbid usage of `debian-sys-maint' SQL user
    Updated: ./docs/preseed.pl preseeding template file

LISTENERS
    Added: 10_postfix_transport_table.pl listener file (Allows to add entries in Postfix transport(5) table)
    Fixed: Default hostname must be overridden to prevent hostname mismatches (10_roundcube_tls.pl)
    Updated: 10_apache2_dualstack.pl listener file for i-MSCP Serie 1.4.x
    Updated: 20_apache2_serveralias_override.pl.pl listener file for i-MSCP Serie 1.4.x
    Updated: 30_apache2_tools_proxy.pl.pl listener file for i-MSCP Serie 1.4.x
    Updated: 40_apache2_security_headers.pl.pl listener for i-MSCP Serie 1.4.x
    Updated: 50_dovecot_plaintext.pl listener file according for i-MSCP Serie 1.4.x

PACKAGES
    Added: `beforeUpdateRoundCubeMailHostEntries' event listener (RoundCube package installer)
    Fixed: Apache2 needs to be reloaded on password update (AWStats)
    Fixed: AWStats interface is not reachable for redirected or proxied sites (AWStats)
    Fixed: Couldn't access symlinked icons (AWStats)
    Fixed: Password not updated on customer password recovery (AWStats)

PLUGINS
    Updated: API version to 1.4.0

SERVERS
    Added: LAN IP address in virtualhost for local access (Servers::ftpd::proftpd::installer)
    Added: Support for Python and Ruby CGI scripts (Httpd server impl.)
    Added: `/.well-known' directory to site skeletons (Httpd server impl.)
    Changed: Usage of mpm_event in place of mpm_worker (PHP-FPM httpd server impl.)
    Dropped: Compatibility for Apache2 < 2.4.x (Httpd server impl.)
    Fixed: Any site must have a document root, even when redirected or proxied (Httpd server impl.)
    Fixed: apache2: Could not reliably determine the server's fully qualified domain name, using ::1 for ServerName
    Fixed: Cleanup and disable unused PHP SAPIs
    Fixed: Even when a site is redirected or proxied, its Web folder must be created (Httpd server impl.)
    Fixed: Forward Secrecy not supported with reference browsers (Apache2)
    Fixed: Make sure that PHP Apache2 SAPI is disabled when needed (httpd server impl.)
    Fixed: Possible `NameVirtualHost <ip>:<port> has no VirtualHosts' warning (Apache2)
    Fixed: Possible `nginx: [emerg] bind() to <ip>:<port> failed (98: Address already in use)' error
    Fixed: POSTCONF(1) is being slow when called multiple-times, slowing down i-MSCP installer (Postfix server impl.)
    Fixed: Set HSTS `max-age' value to zero when HSTS is disabled (See RFC 6797)
    Fixed: The `/.well-known' directory is not reacheable when a site is redirected or proxied (httpd server impl.)
    Fixed: Wrong events triggered (Servers::mta::postfix)
    Fixed: Wrong permissions set on Courier Authdaemon socket dir, making maildrop MDA unable to connect
    Fixed: Wrong permissions set on Dovecot configuration files

SERVICES
    Added: Upstart job override files for PHP-FPM 5.6, 7.0 and 7.1 (reload with SIGUSR2)
    Added: --nodaemonize option in imscp_panel Upstart job configuration file
    Added: systemd-tmpfiles for creation of the /run/imscp directory
    Changed: run directory for imscp_panel service (/var/run to /run/imscp)
    Changed: run directory for PHP-FPM (/var/run to /run/php)
    Fixed: Make sure that the /run/imscp directory is created by imscp_panel.conf Upstart job configuration file
    Fixed: Make sure that the /run/imscp directory is created by imscp_panel sysvinit script
    Fixed: The imscp_mountall service must be started as late as possible on server boot
    Removed: imscp_panel_checkconf as FPM often ends with zend_mm_heap corrupted, preventing service to be (re)started

YOUTRACK
    IP-0826 Any password should be encrypted
    IP-1383 Security - Remove auto-login feature to remove plaintext passwords
    IP-1686 Fields beginning or ending with braced tags are corrupted by the clean_input function.
    IP-1688 /etc/postfix/domains.db entry not added if mail value was changed from -1 (disabled) to enabled (0 or a value)
    IP-1689 Password reset conflict with PanelRedirect
    IP-1694 Administrator: Order / Filter by Reseller
    IP-1700 The /etc/mtab file get overwritten by the /etc/init.d/vzquota sysvinit script (Strato vServer) on reboot

------------------------------------------------------------------------------------------------------------------------
Older release series
------------------------------------------------------------------------------------------------------------------------

See ./docs/Changelog-x.x.x files
