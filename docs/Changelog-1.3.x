i-MSCP ChangeLog

------------------------------------------------------------------------------------------------------------------------
1.3.16
------------------------------------------------------------------------------------------------------------------------

2017-01-07: Laurent Declercq
    RELEASE i-MSCP 1.3.16

INSTALLER
    Fixed: Default value for the `DATABASE_USER_HOST' parameter cannot be a local host when using SQL remote server
    Fixed: iMSCP::Dialog::InputValidation::isNotEmpty() validation method always return true, causing unexpected results
    Fixed: iMSCP::Dialog::InputValidation::isValidPassword() validation method always return true, causing security hole
           due to acceptance of empty passwords

PACKAGES
    Review: Configuration files are no longer lazy-loaded

SERVERS
    Fixed: Could not create SQL user (MariaDB, Percona and remote server implementations)
    Review: Configuration files are no longer lazy-loaded

------------------------------------------------------------------------------------------------------------------------
1.3.15
------------------------------------------------------------------------------------------------------------------------

2017-01-05: Laurent Declercq
    RELEASE i-MSCP 1.3.15

CONFIGS
    Fixed: Unexpected wide character in 00_nameserver.conf Apache2 conffile, causing memory leak

BACKEND
    Added: iMSCP::Mount::getMounts() function
    Added: Missing iMSCP::Config::DESTROY() method
    Fixed: Could not delete domain due to duplicate mounts (See #IP-1679)
    Fixed: Don't attempt to flush() when tied file is readonly or when deferred writing is disabled (iMSCP::Config)
    Fixed: Recursion must be optional in iMSCP::Mount::umount() (iMSCP::Mount)
    Removed: Unwanted `nofail' option in iMSCP::Bootstrapper library
    Removed: Unwanted `nofail' option in iMSCP::Config library

DISTRIBUTIONS
    Fixed: `/etc/mtab' file not a symlink to `/proc/mounts', leading to duplicate mounts (See #IP-1679)
    Removed: libdata-validate-domain-perl package from package files (provided module version 0.10 is too old)

FRONTEND
    Fixed: Discard forwarded domains from list of shareable mount points
    Fixed: Unexpected error `VirtualFileSystem: Could not list directory' when editing a forwarded domain, alias or subdomain

INSTALLER
    Added: `admin_credentials' and `admin_email' reconfiguration items
    Added: iMSCP::Dialog::InputValidation library for ease of user input validation
    Added: preInstall() and postInstall() methods (autoinstaller::Adapter::AbstractAdapter)
    Enhancement: Try to guess WAN IP using ipinfo.io Web service
    Removed: `afterPreBuild', `beforeBuild' and `beforePostBuild' events
    Renamed: `beforeInstall' event to `preInstall` and `afterInstall' event to `postInstall`
    Renamed: `beforePreBuild' event to `preBuild', and `afterBuild' event to `postBuild'

SERVERS
    Fixed: Path must be cleaned before use in regexes (HTTPD servers)
    Fixed: Skip cleanup of custom.conf.tpl, index.html, vlogger.conf.tpl and php-fcgi-starter conffiles (HTTPD servers)

VENDOR
    Added: Data::Validate::Domain Perl module version 0.14
    Updated: Capture::Tiny Perl module from version 0.40 to version 0.44
    Updated: Net::Domain::TLD Perl module from version 1.74 to version 1.75

YOUTRACK
    #IP-1679 Duplicate bind mounts leading to un-deletable Web folders (Ubuntu < 16.04)
    #IP-1680 UTF-8 characters get corrupted on template processing (on web folder skeleton files)

------------------------------------------------------------------------------------------------------------------------
1.3.14
------------------------------------------------------------------------------------------------------------------------

2016-12-27: Laurent Declercq
    RELEASE i-MSCP 1.3.14

FRONTEND
    Review: Database connection initialization

SERVERS
    Fixed: Missing shebang in php-fcgi starter scripts, leading to 500 errors (apache_fcgid)

------------------------------------------------------------------------------------------------------------------------
1.3.13
------------------------------------------------------------------------------------------------------------------------

2016-12-26: Laurent Declercq
    RELEASE i-MSCP 1.3.13

BACKEND
    Fixed: Strings with code points over 0xFF may not be mapped into in-memory file handles

------------------------------------------------------------------------------------------------------------------------
1.3.12
------------------------------------------------------------------------------------------------------------------------

2016-12-25: Laurent Declercq
    RELEASE i-MSCP 1.3.12

BACKEND
    Fixed: Could not write log files for the Modules::Htaccess module due to possible slash in names
    Fixed: Missing flush() method to return underlying tied array to immediate-write mode (iMSCP::Config)
    Fixed: Several encoding issues (wide characters)
    Fixed: Unless otherwise stated, config object must die when accessing a non-existent parameter (iMSCP::Config)

INSTALLER
    Fixed: Failure when there are wide characters outputted to a filehandle for which utf8 layer is not set
    Fixed: Possible corruption of server/package configuration files

LISTENERS
    Fixed: Servers::httpd config is loaded too early in 10_apache2_dualstack.pl

SERVERS
    Fixed: Usage of rewrite engine for setting up FCGI handlers leads to many troubles (apache2_php_fpm server)
    Review: Remove any comment and empty lines in production configuration files (Http server impl.)

------------------------------------------------------------------------------------------------------------------------
1.3.11
------------------------------------------------------------------------------------------------------------------------

2016-12-22: Laurent Declercq
    RELEASE i-MSCP 1.3.11

FRONTEND
    Fixed: Users cannot make new login attempts after a blocking timeout (BruteForce core plugin)
    Fixed: Waiting time between login attempts is never put off, even when expected to be (BruteForce core plugin)
    Fixed: Wrong parameter passed-in to authentication result (Authentication service class)

INSTALLER
    Added: `IMSCP_SETUP' environment variable to make any sub-process aware of i-MSCP setup process
    Fixed: Installation fail on first attempt due to dpkg(1) post-invoke-tasks that are run too early

PACKAGES
    Fixed: Blowfish secret is too short (Package::PhpMyAdmin::Installer)

SERVERS
    Fixed: PHP scripts are outputted without even being interpreted when the rewrite engine is disabled in .htaccess files (apache2_php_fpm server)

------------------------------------------------------------------------------------------------------------------------
1.3.10
------------------------------------------------------------------------------------------------------------------------

2016-12-19: Laurent Declercq
    RELEASE i-MSCP 1.3.10

BACKEND
    Fixed: Bad cipher used in `imscp_common_methods.pl' for decrypting  database password, breaking software installer

FRONTEND
    Added: Minified version of imscp.js file
    Fixed: Could not assign/un-assign htgroup (Protected area feature)
    Fixed: When creating/editing a htuser, credentials are not written in .htpasswd file (Protected area feature)
    Fixed: When deleting htuser, credentials are not removed from the .htpasswd file (Protected area feature)
    Fixed: Wrong SQL statement leading to 400 error page (Protected area feature)

PLUGINS
    Fixed: Do not register listeners provided by a plugin that is not yet known by the plugin manager

SERVERS
    Fixed: Rewrite rules put in .htaccess files are never applied in some contexts (apache2_php_fpm server impl.)

YOUTRACK
    #IP-1678 Protected area feature - Several issues with htusers/htgroups

------------------------------------------------------------------------------------------------------------------------
1.3.9
------------------------------------------------------------------------------------------------------------------------

2016-12-16: Laurent Declercq
    RELEASE i-MSCP 1.3.9

BACKEND
    Fixed: Prevent replacement of Apache2 variables in vhost files (iMSCP::TemplateParser)
    Fixed: Wrong substitution for deleted mount points (iMSCP::Mount)

FRONTEND
    Added: iMSCP_Authentication_AuthEvent class (Represent authentication event passed-in to listeners)
    Added: `onSharedScriptStart' and `onSharedScriptEnd' events
    Added: Static info in custom DNS management interface explaining rules for substitution with $ORIGIN (Custom DNS)
    Added: Support for DNS NS resource record - only allowed for subzone delegation (Custom DNS)
    Changed: Authentication service class now expects an authentication result object pulled from authentication event
    Changed: FTP chooser script (ftp_choose_dir.php) is now a shared script (previously, it was a client script)
    Fixed: A backend request must be triggered when a customer password is being updated by the administrator (Admin level)
    Fixed: Authentication data (htuser(s)/htgroup(s)) must remain selected upon errors (Protected area form)
    Fixed: Could not install a new software instance due to a bad check on installation path (Software installer)
    Fixed: Default (credentials) authentication handler must not stop propagation of authentication event on failure
    Fixed: Favicon not loaded in some browsers, specially MSIE
    Fixed: Installation of a new software instance on forwarded domains must be prohibited (Software installer)
    Fixed: Installation of a new software instance outside of the document root must be prohibited (Software installer)
    Fixed: MCRYPT extension is being deprecated in PHP 7.1.x and will be removed in PHP 7.2.x (replaced by openssl)
    Fixed: Missing check on FTP user owner while editing (Ftp user edit form - Security flaw)
    Fixed: Missing check on Htuser(s)/Htgroup(s) owner while editing (Protected area form - Security flaw)
    Fixed: Password hash is not updated on customer password change (Reseller level)
    Fixed: Reseller cannot edit DocumentRoot of domain aliases (Reseller alias edit form)
    Fixed: Several layout issues in admin/software_rights.tpl (Software installer)
    Fixed: Several layout issues in client/software_view.tpl (Software installer)
    Fixed: Wrong SQL query leading to an exception (Software installer)
    Removed: decryptBlowfishCbcPassword() function (replaced by \iMSCP\Crypt library)

INSTALLER
    Fixed: Default timezone badly detected - DateTime::TimeZone object isn't stringifiable

LISTENERS
    Added: 40_apache2_security_headers.pl listener file for Apache2 security headers - https://securityheaders.io
    Fixed: 10_proftpd_tuning.pl listener file is broken

MODULES
    Renamed: Modules::Htusers package to Modules::Htpasswd package

PLUGINS
    Fixed: Don't load unused data from plugin table (iMSCP_Plugin_Manager)
    Updated: API version to 1.0.7 (due to changes made in Authentication service class)

SCRIPTS
    Fixed: Hide DEBUG messages from the imscp-dpkg-post-invoke.pl script when running APT
    Fixed: Missing `--debug' command line option in several scripts

SECURITY
    Changed: Usage of AES-256 (Rijndael) algorithm to encrypt data in place of the Blowfish algorithm (see the errata)

SERVERS
    Fixed: Several issues with ProxyErrorOverride directive. See https://i-mscp.net/index.php/Thread/15502

UPGRADE
    Dropped: Upgrade support for i-MSCP versions older than 1.1.0 (See the errata)

YOUTRACK
    #IP-1639 When editing a hosting plan, some PHP INI values are not always those that were set while creation
    #IP-1640 When editing reseller properties, customers's PHP INI values are updated with incorrect values
    #IP-1665 Allow underscore in CNAME-record
    #IP-1666 Could not dump domain.tld when two DNS entries have same name
    #IP-1667 ITK httpd server implementation - Variables not replaced in vhost template
    #IP-1671 Backup script - literal error in sql query without visible or negative effect
    #IP-1672 DocumentRoot no longer editable for domains with shared mount point feature enabled
    #IP-1676 Input mask during installation when confirmation of password is wrong

------------------------------------------------------------------------------------------------------------------------
1.3.8
------------------------------------------------------------------------------------------------------------------------

2016-11-23: Laurent Declercq
    RELEASE i-MSCP 1.3.8

BACKEND
    Fixed: Boot mode not set when it is expected to be (iMSCP::Bootstrapper)
    Fixed: Configuration files must be opened read-only outside of setup context
    Fixed: Customer SSL certificates are validated twice per add/change actions
    Fixed: Don't display error messages related to invalid customer SSL certificates while i-MSCP update/reconfiguration
    Fixed: Lock files must be released before processing of debug messages (dump)
    Fixed: Wrong provider used while removing services (iMSCP::Service)
    Review: Merged module data provider methods (All servers/packages are now receiving identical set of data)

FRONTEND
    Added: Hour and minute fields in message headers (Support Tickets)
    Changed: Htuser passwords are now hashed using APR-1 algorithm
    Changed: Panel user passwords are now hashed using APR-1 algorithm (See the errata file for further details)
    Changed: Usage of HTML textarea tag instead of input tag for data field (Custom DNS interface)
    Fixed: Any printable ASCII character must be allowed inside TXT record data field (Custom DNS interface)
    Fixed: Could not list directory when domain alias or subdomain is forwarded (Client side)
    Fixed: Double-quotes inside a character string of a TXT/SPF data field must be escaped (Custom DNS interface)
    Fixed: HTML <br> tags not rendered in messages (Support Tickets)
    Fixed: Ignore user abort while listing directory (VirtualFileSystem)
    Fixed: It is too hard to differentiate messages (Support Tickets)
    Fixed: Leading and trailing double-quotes from TXT/SPF data field must be removed before rendering (Custom DNS interface)
    Fixed: Messages are hard to read because they are greyed (Support Tickets)
    Fixed: Messages should be displayed in LIFO order (Support Tickets)
    Fixed: Resellers cannot change customer passwords due to useless verification on the current password
    Fixed: Tooltips not rendered correctly in some contexts (UI)
    Removed: cryptPasswordWithSalt(), generateRandomSalt() and _passgen() functions (replaced by \iMSCP\Crypt library)

INSTALLER
    Fixed: Could not set host value to `%' (host from which SQL users created by i-MSCP must be allowed to connect)
    Review: Prefer IPv4 family (Wget)

LISTENERS
    Added: 10_frontend_templates_override.pl (Allows to override default frontEnd template files)
    Added: 10_named_global_ns.pl (Allows to set identical NS entries in all zones)
    Fixed: DNS entries are always overridden after custom DNS processing (20_named_dualstack.pl)
    Fixed: Entries not added in Postfix main.cf file (20_postfix_policy_whitelist.pl)
    Fixed: Typo in package name, leading to compilation failure (20_postfix_policy_whitelist.pl)
    Fixed: Wrong namespace used for many listeners (Named namespace)
    Removed: 10_named_override_default_ns_rr.pl (replaced by the all-in-one 10_named_global_ns.pl listener file)

PACKAGES
    Added: dpkg post-invoke task for updating the `imscp_panel' PHP binary when the system PHP binary is updated (FrontEnd)
    Changed: Authentication provider for Awstats (Apache mod_authn_file instead of mod_dbd) (AWStats)
    Fixed: AH01102: error reading status line from remote server 127.0.0.1:8889 (AWStats)

PLUGINS
    Added: Support for optional build field (versioning)
    Fixed: Update plugin data when date or build fields are increased
    Fixed: Ignore user abort while migrating database

SERVERS
    Added: `PHP_FPM_RLIMIT_FILES" configuration parameter in php.data configuration file (PHP-FPM)
    Changed: Calculate SOA serial numbers using GMT timezone (Bind9)
    Changed: PHP-FPM `rlimit_files' value to avoid the `Too many open files (24)' error on start-up (PHP-FPM)
    Fixed: Duplicate bind mounts due to unwanted iMSCP::Mount::mount() call (Apache2 FCGID)
    Fixed: Name server names are badly generated in dual-stack context (Bind9)
    Fixed: Name server names should be configurable, at least through event listeners (Bind9)
    Fixed: Only double quotes must be escaped in master SQL user password (Remote SQL server)
    Fixed: Postfix maps not written in some contexts (especially on error) (Postfix)

SERVICES
    Fixed: All customer sites running a PHP application get a 503 error when restarting the `imscp_panel service' (related to #IP-1641)
    Reverted: Changes made regarding #IP-1641 issue (See the issue for further details)

UNINSTALLER
    Fixed: Global symbol "$package" requires explicit package name at imscp-uninstall line 216

YOUTRACK
    #IP-1641 PHP binary for the imscp_panel service must be updated when the system PHP binary is updated
    #IP-1649 Domain traffic data missing
    #IP-1650 When a catchall is deleted, the mailbox used with the catchall is also deleted
    #IP-1651 Forward URL - The new document root must pre-exists inside the /htdocs directory
    #IP-1654 Support - Several issues in view ticket interface
    #IP-1656 Debian Stretch - DBD::mysql - libdbd-mysql-perl requirements
    #IP-1663 Custom DNS - Could not add TXT-DATA when the character string is longer than 255 characters

------------------------------------------------------------------------------------------------------------------------
1.3.7
------------------------------------------------------------------------------------------------------------------------

2016-10-21: Laurent Declercq
    RELEASE i-MSCP 1.3.7

BACKEND
    Fixed: Could not remove service (iMSCP::Service)

PACKAGES
    Fixed: Bareword "JSON::true" not allowed while "strict subs" in use (MonstaFTP)
    Fixed: Permissions are not set (any package)

SERVERS
    Fixed: `TLSProtocol' directive not allowed in global context (ProFTPD)

YOUTRACK
    #IP-1644 Pydio - Could not write into the AJXP_DATA_PATH folder

------------------------------------------------------------------------------------------------------------------------
1.3.6
------------------------------------------------------------------------------------------------------------------------

2016-10-18: Laurent Declercq
    RELEASE i-MSCP 1.3.6

BACKEND
    Fixed: Several shadowed and unused variables

FEATURES
    Added: Editable DocumentRoot (see the errata file for further details)

FRONTEND
    Added: Events for custom DNS records
    Added: Show document roots (relative to mount points) on domains_manage.php page (client side)
    Fixed: Avoid attaching event handler when not necessary (FTP directory chooser)
    Fixed: Connection must be made through SSL when SSL is enabled for the services (VirtualFileSystem)
    Fixed: Forbid direct access to the `/tools' directory
    Fixed: FTP passive mode should be enabled whenever possible (VirtualFileSystem)
    Fixed: Hide URLs on hover event (FTP directory chooser)
    Fixed: Lets the applications choose the charset they want use (panel, pma, net2ftp...)
    Fixed: Make parent directory selectable (FTP directory chooser)
    Fixed: PhpMyAdmin auto-login doesn't works when connecting through SSL with a self-signed certificate
    Fixed: Tooltips not initialized in dialog box (FTP directory chooser)
    Review: Better error handling (VirtualFileSystem)

INSTALLER
    Added: fix_apache2_mod_proxy_fcgi.sh apache2 post install task (patch and rebuild mod_proxy_fcgi module)
    Added: Support for package post installation tasks (autoinstaller::Adapter::DebianAdapter)
    Changed: Usage of iMSCP::Crypt::randomStr() to generate passwords
    Fixed: Errors from updDB.php script not properly captured
    Fixed: Making sure that all required PHP modules are enabled by executing php5enmod/phpenmod (regression fix)
    Fixed: Migration script is always run while reconfiguration, forcing clients to re-download all mails (PO servers)
    Fixed: Packages such as `Rainloop' remain selected even when they are unchecked in setup dialog
    Fixed: Preseeding feature is broken - Unable to make unattended installations
    Fixed: Some PHP modules such as `mcrypt' and `imap' are not enabled after a fresh installation (Ubuntu 14.04)
    Fixed: The setupSystemDirectories() subroutine is called too early

LISTENERS
    Added: 10_phpfpm_settings_override.pl (Allows to override PHP-FPM settings in pool configuration files)
    Fixed: User web directory must be mounted as slave subtree (10_system_mount_userwebdir.pl)

PACKAGES
    Updated: MonstaFTP package installer for use of Monsta FTP 2.1.x serie

SERVERS
    Added: SQL query for user iteration through `doveadm' (Dovecot)
    Fixed: Authorization problems - You don't have permission to access / on this server (Apache2)
    Fixed: Could not access error documents on error (redirect vhosts) (Apache2)
    Fixed: Could not list directory when accessing FTP server via localhost, and when passive mode is enabled (ProFTPD)
    Fixed: Proxy loop caused by the `ProxyErrorOverride' directive (See the errata file for further details) (Apache2)
    Rewritten: Configuration file (ProFTPD)

SERVICES
    Changed: Create a symlink instead of copying the system PHP binary for the imscp_panel service (Partially closes: #IP-1641)

YOUTRACK
    #IP-1633 Disabled domains - All sub-pages should be redirected to the root page instead of sending a 404 HTTP error
    #IP-1637 Duplicate mount entries in /etc/imscp/mount/mounts.conf
    #IP-1638 Apache workers not closed on mis-configured vlogger

------------------------------------------------------------------------------------------------------------------------
1.3.5
------------------------------------------------------------------------------------------------------------------------

2016-10-01: Laurent Declercq
    RELEASE i-MSCP 1.3.5

BACKEND
    Fixed: Changes in version 1.3.4, regarding the modules data immutability, are breaking compatibility with plugins

------------------------------------------------------------------------------------------------------------------------
1.3.4
------------------------------------------------------------------------------------------------------------------------

2016-10-01: Laurent Declercq
    RELEASE i-MSCP 1.3.4

BACKEND
    Changed: Save CPU time and memory consumption by returning a ref to a readonly hash instead of a hash copy (Modules)
    Fixed: Avoid to reverse mount entries each time umount() subroutine is called (iMSCP::Mount)
    Fixed: Don't remove a mount entry if the mount has not been really unmounted (iMSCP::Mount)
    Fixed: Data provided by modules must be immutable

FRONTEND
    Fixed: Prevent CloudFlare to obfuscate SQL users by enclosing the select element in <!--email_off--> comment tags
    Fixed: Previous value for the `forward_type' field not retained on error (reseller/user_add1.php)
    Fixed: Unknown `START_ID_POS_CHECKED' and `END_ID_POS_CHECKED' tags in sql_user_add.tpl

INSTALLER
    Added: Documentation link for installer modes
    Fixed: Error not catched on mount failure
    Fixed: Avoid usage of rsync command to copy distribution files

LISTENERS
    Added: 10_mount_userwebdir.pl listener file (Allows to mount USER_WEB_DIR into /var/www/virtual from another place)

PLUGINS
    Updated: API version to 1.0.6

SERVERS
    Fixed: Redirects and HSTS sections are not removed in SSL vhost files when they have to be (Apache2)
    Fixed: Tells the mail clients what are the mailboxes they must use and subscribe by implementing RFC 6154 (Dovecot)
    Fixed: Unexpected behavior with `disablereuse' parameter set to `Off' (Apache2 mod_proxy)
    Re-added: ProxyErrorOverride directive in 00_nameserver.conf (Closes: #IP-1406 which has been reopened) (Apache2)

SERVICES
    Fixed: Mount entries processed in wrong order while stopping service (imscp_mountall)

YOUTRACK
    #IP-1628 Could not remount /var/www/virtual as shared subtree when /var/www is a separated fs
    #IP-1630 Proxy Redirect - Restict ports to > 1024 (no system reserved ports)
    #IP-1632 Email quota accounting is wrong

------------------------------------------------------------------------------------------------------------------------
1.3.3
------------------------------------------------------------------------------------------------------------------------

2016-09-24: Laurent Declercq
    RELEASE i-MSCP 1.3.3

SERVERS
    Fixed: Can't locate Servers/named/external_server.pm

------------------------------------------------------------------------------------------------------------------------
1.3.2
------------------------------------------------------------------------------------------------------------------------

2016-09-24: Laurent Declercq
    RELEASE i-MSCP 1.3.2

BACKEND
    Fixed: Allow detection of bind mounts (iMSCP::Mount)
    Fixed: An IP address must not be added in the interfaces file if already present, even if auto mode is set
    Fixed: Lock files are never deleted
    Fixed: Remove any lock on shutdown (iMSCP::Bootstrapper)
    Fixed: Read mount entries once per run (iMSCP::Mount)

CONFIGS
    Updated: Request headers in redirect vhosts (Apache2)
    Removed: /etc/imscp/imscp.old.conf configuration file

CRONJOBS
    Fixed: run() subroutine is run twice per process (imscp-disable-accounts)

FRONTEND
    Added: showForbiddenErrorPage() function
    Fixed: Error on reseller/domain_details.php page; Unsupported operand types
    Fixed: The `onBeforeAddDomain' event is triggered too early
    Fixed: Too many redirects when attempting to login from an external host (when external login is disallowed)

LISTENERS
    Added: 10_packages_override.pl listener file (Allows to override package files)
    Added: debian_jessie_php7.0.xml package file (for use with the 10_packages_override.pl listener file)
    Fixed: Can't use string ("all") as an ARRAY ref while "strict refs" in use (10_postfix_tuning.pl)

PLUGINS
    Fixed: Could not delete plugins which implement the install() method but not the uninstall method()
    Removed: Support for ZIP plugin archives (only tar.gz and tar.bz2 archives are now supported)

SERVERS
    Fixed: Installation fail if main.cf is missing or misconfigured (Servers::mta::postfix::installer)
    
SERVICES
    Fixed: Empty lines are not ignored in the /etc/imscp/mounts/mounts.conf configuration file (imscp_mountall)

YOUTRACK
    #IP-0815 Dovecot LDA - Enable support for address extensions
    #IP-1616 ProxyPass entries not removed in vhost file if HSTS is enabled
    #IP-1617 Reverse proxy: Localhost is not allowed as address
    #IP-1618 OpenVZ - Could not remount root directory as shared subtree
    #IP-1621 Parameters from the imscp.conf file are reseted to their default values on upgrade (only in some contexts)
    #IP-1623 SQL root user is deleted when upgrading from a version older than 1.3.0
    #IP-1624 Default SPF DNS record not removed when adding custom SPF DNS record

------------------------------------------------------------------------------------------------------------------------
1.3.1
------------------------------------------------------------------------------------------------------------------------

2016-09-14: Laurent Declercq
    RELEASE i-MSCP 1.3.1

BACKEND
    Added: iMSCP::Mount::isMountpoint() function (iMSCP::Mount library)
    Added: iMSCP::Syscall library
    Added: Regexp support for begin and ending tags (iMSCP::TemplateParser library)
    Added: Support for `rbind' (iMSCP::Mount library)
    Fixed: Could not bind mount socket files (iMSCP::Mount library)
    Rewritten: iMSCP::Mount library - Make direct syscalls instead of calling mount(8)/umount(8)

CONFIG
    Added: index.xhtml and index.htm to DirectoryIndex directive (Apache2 vhosts)
    Added: `localhost' to `mydestination' parameter (Postfix)
    Added: `PHP_OPCODE_CACHE_ENABLED' and `PHP_OPCODE_CACHE_MAX_MEMORY' in php/php.data conffile
    Fixed: FastCGI handler name for PHP must be static
    Fixed: PHP opcode cache should be enabled by default (OPcache, APC)
    Moved: DocumentRoot for pages of disabled domains now live outside of the customer home directories

CONTRIB
    Added: 10_named_override_default_ns_rr.pl
    Added: 10_named_override_default_rr.pl listener
    Removed: 10_named_tuning.pl listener (replaced by 10_named_override_default_rr.pl listener)
    Removed: 10_named_tuning2.pl listener (replaced by 10_named_override_default_ns_rr.pl listener)

DATABASE
    Added: Compound unique index on the domain_traffic table to avoid slow query and duplicate entries
    Added: Compound unique key on the `php_ini' table
    Fixed: Missing primary key on httpd_vlogger table
    Changed: Default value for the `dtraff_web', `dtraff_ftp', `dtraff_mail' and `domain_traffic' columns (NULL to 0)
    Fixed: Disallow NULL value on `domain_traffic.domain_id' and `domain_traffic.dtraff_time' columns
    Fixed: Make queries compatible with `only_full_group_by' SQL mode which is par of default modes in MySQL > 5.7

FRONTEND
    Added: `onAddIpAddr', `onChangeIpConfigMode' and `onDeleteIpAddr' events (admin level)
    Changed: Listening ports are now 8880 (http) and 8443 (https). (Required by CloudFlare)
    Changed: Frontend is now run through an isolated PHP-FPM Daemon (see errata file)
    Fixed: Authentication failure when accessing panel through IP (PhpMyAdmin/Pydio auto-login feature)
    Fixed: Could not upload new plugin version (Bad version check)
    Fixed: Possible authentication failure when accessing panel through proxy (PhpMyAdmin/Pydio auto-login feature)
    Fixed: Customer PHP permissions are not synced with reseller PHP permissions in some contexts
    Fixed: General error: 1366 Incorrect integer value: '_no_' for column 'mail_auto_respond'
    Fixed: IPv6 - Possible duplicate entries - It is possible to add an IPv6 twice (uncompressed and compressed)
    Fixed: Make sure that `Reply-To' header is set, even for system notification emails
    Fixed: PHP INI entry for main domains must be created even if PHP editor is not enabled
    Fixed: Possible NULL values when creating default email accounts
    Fixed: Prevent rollback attempts when not in transaction (iMSCP_Update_Database)
    Fixed: `SELECT' placeholder is not parsed in ip_manage.php (admin level)
    Fixed: Running the frontEnd through PHP CGI using spawn-fcgi lead to problems with opcode cache (cache not shared)
    Fixed: Server IP addresses are missing in the debugger interface
    Removed: Support for FTP URL (Redirect feature)

INSTALLER
    Added: `primary_ip' item to the list of reconfigurable items (--reconfigure command line option)
    Added: Requirements check for PHP modules
    Fixed: Cannot create pbuilder environment - Wrong repository (pbuilder use first entry from sources.list)
    Fixed: Could not install pre-required packages error on first run
    Fixed: Could not remove deprecated `phptmp' directories on update (Httpd servers)
    Fixed: Ensure that DEFAULT_ADMIN_ADDRESS parameter is set
    Fixed: Prefer IPv4 address family on IPv6 capable hosts (Wget)
    Fixed: SQL server data directory must not be hardcoded
    Fixed: Update and Security repositories are missing in pbuilder environments
    Removed: Additional IP configuration dialog (see the errata file for more details)
    Removed: `ips' item from the list of reconfigurable items (--reconfigure command line option)

LISTENERS
    Fixed: Script for slave DNS provisioning won't work if HTML compression is disabled in panel

PACKAGES
    Changed: AWStats is now accessible through dedicated port (8889) (AWStats)
    Changed: Usage for Apache2 mod_dbd to authenticate AWStats users (AWStats)
    Fixed: Data can be accessed through localhost without any authentication (AWStats)
    Fixed: Configuration files are not generated (regression fix) (AWStats)
    Fixed: Distribution package (awstats) is not installed (regression fix) (AWStats)
    Fixed: Could not authenticate to AWStats interface of domain aliases and subdomains (AWStats)
    Removed: Support for AWStats static mode (AWStats)

PLUGINS
    Fixed: A plugin must be lockable by more than one plugin
    Updated: API version to 1.0.5 (needed due to changes in iMSCP::Mount library)

PRESEEDING
    Removed: SERVER_IPS parameter according changes made in installer

SERVERS
    Fixed: Could not access httpd log files through FTP (permissions on log files are too restrictives)
    Fixed: Could not start PHP FPM service when using TCP FastCGI address type (apache_php_fpm)
    Fixed: Dotfiles not listed (ProFTPD, VsFTPD)
    Fixed: Enable opportunistic TLS for Postfix client side (smtp)
    Fixed: Enforce installation of our own sysvinit script for ProFTPD to prevent restart failure on log rotation
    Fixed: HSTS - Follow RFC 6797 recommendations for non-secure-to-secure redirects (status code 301 instead of 307)
    Fixed: HSTS - Wrong redirect when domain is forwarded (Apache server impl.)
    Fixed: mysql-client-5.6 package not available in Ubuntu 16.04 repositories (Remote server)
    Fixed: Permissions on courier-authdaemon rundir are resetted on reboot (systemd tmpfiles.d)
    Fixed: Process $ORIGIN substitutions before triggering the `afterNamedAddCustomDNS' event (Bind9 server impl.)
    Fixed: Wrong argument passed to Servers::named::bind::_updateSOAserialNumber

SKELETON
    Added: Dedicated `domain_disabled_pages' directory for pages of disabled domains

SYSTEM
    Fixed: Make sure that / is marked as shared in regards to mount propagation, even when not using systemd

YOUTRACK
    #IP-0135 IPs management - The NIC and netmask should be editable for each IP
    #IP-1316 Could not add CNAME with underscore in name for DKIM
    #IP-1429 Make main domains forwardable
    #IP-1508 Revise acceptable password characters
    #IP-1526 Alternate URL for subdomains and domain aliases not shown in interface
    #IP-1534 Cannot save PHP `post_max_size' and `upload_max_filesize' for a domain
    #IP-1575 When editing forwarding URL, scheme field is not set with current value
    #IP-1576 Feature - Proxy support through Apache2 mod_proxy for customers
    #IP-1577 i-MSCP installer - Dialog exit with code 255 when console size is too small
    #IP-1579 APT (≥ 1.1) - The `--force-yes' option has been replaced by various options starting with `--allow'
    #IP-1581 Allow to disable auto-configuration of network interfaces (IP addresses managed by i-MSCP)
    #IP-1587 Slow query on domain_traffic table when admin or reseller want to login into customer's area
    #IP-1588 Alternative URL vHosts tagged on default IP of I-MSCP
    #IP-1594 Users assigned to protected area not pre-selected on edition
    #IP-1595 courier-authdaemon service not started on reboot
    #IP-1596 Previous DNS zone is not removed when changing BASE_SERVER_VHOST
    #IP-1600 All domain names must be lowercased using mb_strtolower() PHP function
    #IP-1604 Any customer can access AWStats interface of other customers
    #IP-1609 Quota script shouldn't include apache log files
    #IP-1611 Could not authenticate to PMA through auto-login feature when PanelRedirect plugin is installed (Proxy mode)
    #IP-1613 PhpMyAdmin auto-login feature doesn't support backslashes in passwords

------------------------------------------------------------------------------------------------------------------------
1.3.0
------------------------------------------------------------------------------------------------------------------------

2016-06-26: Laurent Declercq
    RELEASE i-MSCP 1.3.0

BACKEND
    Added; (before|after)AddIps events
    Added: (before|after)MountLogsFolder and (before|after)UnmountLogsFolder events
    Added: Caller info for __DIE__ and __WARN__ signal handlers
    Added: Debug info for loading of listener files (Event::Manager)
    Added: Fallback method for sysvinit scripts that don't provide status command (iMSCP::Provider::Service::Sysvinit)
    Added: `fixpermissions' option for the iMSCP::Dir::make() method
    Added: iMSCP::Database::mysql::useDatabase() method - Allow to select database on which we want operate on
    Added: iMSCP::DbTasksProcessor - Allows to process db tasks without spawning new process
    Added: imscp master system user (homedir is /var/local/imscp)
    Added: imscp-mountall script - Mount file systems by reading entries from i-MSCP fstab-like file
    Added: iMSCP::Mount library - Library for mounting/unmounting file systems
    Added: iMSCP::OpenSSL::getCertificateExpiryTime() method - Allow to get SSL certificate expiry time (UNIX timestamp)
    Added: onBoot event in iMSCP::Bootstrapper (triggered at end of bootstrapping process)
    Added: Servers::mta::postfix::postconf method() - Allows editing of Postfix main.cf parameters
    Added: Support for PHP 7.0 (httpd servers implementations)
    Added: Support for prime256v1 (ECDSA) keys (iMSCP::OpenSSL)
    Added: Support for systemd socket units (systemd init provider)
    Enhancement: Allow passing command through arrayref (iMSCP::Execute::execute, iMSCP::Execute::executeNoWait)
    Fixed: composer.phar must not be run with root user privileges (it is now run with i-MSCP system master user)
    Fixed: Could not disable custom DNS resource records; DNS resource records are always re-enabled on domain change
    Fixed: Database handle is destroyed when calling fork(), leading to unexpected failure (iMSCP::Database::mysql)
    Fixed: eth0 is hardcoded in i-MSCP network interface provider
    Fixed: Ignore user-specific options if any ~/.my.cnf file when running mysqldump (iMSCP::Database::mysql::dumpdb)
    Fixed: iMSCP::SystemUser::addSystemUser must allow empty value for user comment field
    Fixed: Mails sent from Backend could be rejected if sender email domain is hosted externally
    Fixed: Make sure that the `root' user HOME directory environment variable is defined
    Fixed: Many inconsistencies in iMSCP::Getopt
    Fixed: Upstart jobs not always enabled (iMSCP::Provider::Service::Debian::Upstart)
    Improvement: Allow registering same listener on many events at once (Event::Manager)
    Introduced: HSTS (HTTP Strict Transport Security) feature
    Removed: (before|adter)DispatchRequest events
    Removed: cache data directory (/var/cache/imscp directory)
    Removed: imscp-httpd-logs-mngr script (Apache2 logs dirctories are now mounted via the iMSCP::Mount library)
    Removed: installConfFile() method from httpd server packages
    Rewritten: iMSCP::Mail library
    Rewritten: iMSCP::SetRights library (usage of built-in functions to avoid call of system chown/chmod commands)
    Rewritten: Postfix Server implementation (Servers::mta::postfix)

CONFIG
    Added: php configuration directory (directory which holds all PHP template and configuration parameters)
    Added: `DISTRO_ID', `DISTRO_CODENAME' and `DISTRO_RELEASE' parameters in imscp.conf file
    Added: `IMSCP_USER' parameter (name of i-MSCP master system user)
    Added: `IPV6_SUPPORT' parameter (tells whether or not IPv6 support is enabled for the server)
    Added: `MAX_INSTANCES', `MAX_CLIENT_PER_HOST' and `FTPD_CONF_DIR' parameters in proftpd/proftpd.data file
    Added: `MOUNT_CUSTOMER_LOGS' parameter in apache/apache.data file (allows to disable logs bind mounts)
    Changed: Increased default limits for ProFTPD/VsFTPd
    Changed: Moved DirectoryIndex directive in vhosts (Apache2)
    Depreciated: `CACHE_DATA_DIR' parameter
    Fixed: Allow access to `.well-known' folder in any case if exists (Apache2)
    Moved: Nginx configuration directory to frontend configuration directory
    Removed: Redundant `MYSQL_PREFIX_TYPE' parameter
    Removed: `VARIABLE_DATA_DIR' parameter
    Removed: i-MSCP intermediate working files, including working directory (Postfix)
    Removed: i-MSCP intermediate working files, including working directory (Apache)
    Removed: fcgi configuration directory (merged into php configuration directory)
    Removed: php-fpm configuration directory (merged into php configuration directory)

CONTRIB
    Added: 10_bind9_rrl listener file (Listener implementing RRL (Response-Rate-Limiting))
    Added: PhpMyAdmin/10_phpmyadmin_conffile.pl listener file (Allows to override default configuration template file)
    Updated: All listener files for i-MSCP 1.3.x Serie

DAEMON
    Added: notify() function allowing to notify parent process when daemon has been fully initialized
    Added: Added help for command line options
    Bumped: Version to 1.3.0
    Fixed: Ensures that the daemon process is re-parented to init/PID 1 (double fork())
    Fixed: Exits from the parent process only after daemon full initialization
    Fixed: Possible buffer overflow
    Fixed: Segfault when client issue bad `helo` command
    Merged: signal handlers

DATABASE
    Added: SPF type to the list of allowed DNS resource records
    Changed: `domain_dns.domain_dns' and `domain_dns.domain_text' column types from varchar to text
    Changed: i-MSCP now uses its own SQL user (master SQL user). See errata file for more details.
    Updated: `domain_id' index on `domain_dns' table; added index length for `domain_dns' and `domain_text' columns

DISTRIBUTIONS
    Fixed: libpam-mysql package - undefined symbol: make_scrambled_password (bugs affecting all distributions)
    Fixed: libpam-mysql package - possible segfault (bugs affecting all distributions)

FRONTEND
    Added: Datatable for domains, subdomains, domain aliases and DNS resource records (client level)
    Added: Datatable for the file system information table (admin level)
    Added: iMSCP\Crypt library
    Added: iMSCP\Net class - Allows to get list of network devices and IP addresses (stdlib)
    Added: \iMSCP\Json\LazyDecoder class for lazy-decoding of JSON data
    Added: Exclamation icon
    Added: MX DNS resource record type (client level)
    Added: `onParseTemplate' event (event triggered when a template is parsed)
    Added: `onSendMail' event (event triggered each time a mail is sent)
    Added: SPF DNS resource record type (validated as a TXT record) (client level)
    Added: Support for .phtml template files (stdlib)
    Added: torul() function for escaping a string for the URI or parameter contexts (stdlib)
    Changed: DNS resource record are no longer created automatically (client level - See  errata for more details)
    Changed: exec() PHP function is no longer disabled for the control panel (master php.ini file)
    Changed: The `type' field cannot longer be changed when editing a DNS resource record (client level)
    Changed: Support for nested MySQL transactions is now emulated using named transaction savepoints (stdlib)
    Fixed: Bruteforce feature still acts even when disabled (lostpassword.php)
    Fixed: Could not add DKIM/DMARC DNS resource records (client level)
    Fixed: Could not retrieve network devices list under Debian Stretch (testing) - Related to #IP-1525 (admin level)
    Fixed: Customer must be allowed to set custom TTL value for any DNS resource record (client level)
    Fixed: Customer must be allowed to set `name' field of any DNS resource record (client level)
    Fixed: Customer PHP permissions not saved when editing customer account (regression fix)
    Fixed: Could not remove database; There is no such grant defined for user... (stdlib)
    Fixed: Could not set URL redirect when adding domain alias; Bad request error page (client level)
    Fixed: Email template inconsistencies
    Fixed: IDNs are shown in ACE form in several places
    Fixed: iMSCP_Update_Database::addIndex() doesn't allows to set index length (stdlib)
    Fixed: Lostpasword feature is broken
    Fixed: Mails sent from FrontEnd could be rejected if sender email domain is hosted externally
    Fixed: Privileges on database that contains wildcards are not removed when SQL user is assigned to many databases
    Fixed: Reseller PHP permissions not saved when editing reseller account (regression fix)
    Fixed: Uncaught ReferenceError: `ajaxStop' is not defined (admin level)
    Fixed: Wrong behavior with exception handler when database connection is not available (stdlib)
    Fixed: Wrong value for `LOG_LEVEL' configuration parameter
    Introduced: HSTS (HTTP Strict Transport Security) feature
    Removed: User deletion confirmation pages (admin and reseller levels)
    Removed: iMSCP_NetworkCard class; replaced by iMSCP\Net class (stdlib)
    Rewritten: FTP chooser interface to avoid usage of iframe and allows fine-grained filtering (client level)
    Rewritten: Protected area interface (client level)

INSTALLER
    Added: --fix-permissions command line option. See errata file for more details.
    Added: (before|after)SetupRegisterPluginListeners events
    Added: Check for maximum supported PHP/Perl versions
    Added: Check to prevent downgrade attempts (when DB schema change is involved)
    Added: Conditional statement feature for install.xml files (copy_config items only)
    Added: Layer allowing to rebuild Debian/Ubuntu source packages using pbuilder after applying a set patches on them
    Added: `onBuildPackageList' event (Allows to override default distribution packages file)
    Fixed: `_' and `%' wildcards must be escaped in GRANT statements
    Fixed: Debug and verbose modes must be set early
    Fixed: Disable Dovecot systemd socket activation unit if any (not needed for Dovecot as configured by i-MSCP)
    Fixed: Don't die on misconfigured server hostname. Just ask for a valid hostname instead
    Fixed: Invalid regexes for SQL user/password (Package::PhpMyAdmin::Installer)
    Fixed: libmariadbclient18 package not provided in Debian Stretch (testing) repository
    Fixed: /etc/mailname file not updated when reconfiguring system hostname
    Fixed: Ignore SIGNINT to prevent user aborting installer in wrong way
    Fixed: Master DNS zone is always deleted on i-MSCP update/reconfiguration (Package::FrontEnd::Installer)
    Fixed: MySQL server 5.6 no longer available for Ubuntu 16.04 (replaced by MySQL server 5.7)
    Fixed: Plugin setup listeners are no longer registered (regression fix)
    Fixed: SQL user and password not correctly escaped in imscp.cnf file (Servers::sqld::mysql::installer)
    Moved: Master administrator questions and setup routines into Package::FrontEnd::Installer
    Removed: Update notices (link to errata file is sufficient)
    Renamed: imscp-setup script to imscp-reconfigure

PACKAGES
    Added: Monsta FTP package
    Disabled: Pydio in case PHP ≥ 7.0.0 is detected (Pydio)
    Fixed: As of 2012-6-7, the md5 algorithm is "no longer considered safe" (FrontEnd)
    Fixed: `ERR_INCOMPLETE_CHUNKED_ENCODING' error due to wrong permissions on nginx fastCGI cache directory (FrontEnd)
    Fixed: Missing IP version check (FrontEnd)
    Fixed: White page due to `net::ERR_INCOMPLETE_CHUNKED_ENCODING' errors (related to #IP-1530) (PhpMyAdmin)
    Updated: PhpMyAdmin version to 4.6.0 - Only for servers with PHP/MySQL ≥ 5.5 (PhpMyAdmin)

PLUGINS
    Enhancement: Plugin backend packages can now be simple objects
    Fixed: Plugin configuration not synced when triggering plugin list update (when a plugin is in error state)
    Fixed: Plugin manager must load plugins by respecting their priority
    Fixed: The `change' action must be automatically triggered on plugin list update, even for plugins with error state
    Fixed: Wrong events triggered when a plugin is being locked/unlocked
    Updated: API version to 1.0.4

SERVERS
    Added: i-MSCP own logrorate configuration file for ProFTPD (xferlog file don't need to be rotated)
    Added: i-MSCP own sysvinit script for ProFTPD - solve #IP-1402
    Changed: HTTPD log directories are now bind-mounted as read-only files system
    Changed: setenvif module is now always required ((Httpd servers)
    Fixed: AH01265: attempt to invoke directory as script: /var/www/virtual/<domain.tld>/cgi-bin/
    Fixed: Bad regexp in getTraffic() method leading to unmatching of traffic data (VsFTPd)
    Fixed: Could not build local vsftpd package when gpg has been initialized; .changes file cannot be signed
    Fixed: Courier sysvinit script must not be copied when selected PO server is Dovecot
    Fixed: Disabled mod_tls_memcache.c module to avoid weird notice on restart (ProFTPD)
    Fixed: Do not fix user/group and mode when not required (Httpd server implementations)
    Fixed: Do not remove default DNS records that are not redefined (Servers::named::bind)
    Fixed: Enable support for UTF-8 file systems (VsFTPd)
    Fixed: Hide real server identity (ProFTPD)
    Fixed: Invalid version format (non-numeric data) when installing VsFTPd on Debian Stretch (testing)
    Fixed: IP addresses are added multiple time in Apache 00_nameserver.conf file (Httpd servers)
    Fixed: libmysqlclient18 package not available for Ubuntu 16.04
    Fixed: maillogconvert.pl must not be installed in /usr/sbin (moved to /usr/local/sbin)
    Fixed: maillogconvert.pl not removed on uninstallation
    Fixed: maillogconvert.pl path must not be hardcoded
    Fixed: ON|OFF strings not recognized as boolean value in imscp.cnf file (MySQL)
    Fixed: Postfix (≥ 3.0) is showing many warnings about backwards-compatible settings (default setting chroot=y)
    Fixed: Postfix maps must not be world-readable (Postfix)
    Fixed: The SOA serial number must be incremented only once when a DNS zone is updated (Servers::named::bind)
    Fixed: Traffic accounting routines must not remove log files (ProFTPD/VsFTPd). Files are now truncated instead
    Fixed: Useless reload of VsFTPd when adding/removing an FTP user (privileges are given by session)
    Fixed: VsFTPd doesn't reopen xferlog file automatically when the file has been removed, leading to traffic data loss
    Fixed: VsFTPD doesn't support non-ascii characters in FTP usernames
    Fixed: When the --skip-distro-packages installer option is set, the vsftpd local package shouldn't be rebuilt
    Removed: /var/log/proftpd/ftp_traff.log custom log. Traffic data are now pulled from /var/log/proftpd/xferlog

SERVICES
    Added: imscp_mountall service - Mount filesystems on server reboot

VENDOR
    Patched: phpseclib library (deprecated constructor method - PHP ≥ 7.0)
    Updated: phpseclib library to version 1.0.2

VLOGGER
    Fixed: Avoid SELECT queries while dump of traffic data
    Fixed: SQL statement must be prepared once per dump process
    Fixed: Wrong SQL user host (localhost instead of 127.0.0.1)

YOUTRACK
    IP-0931 Unexpected error on protected area creation
    IP-1231 Default SPF DNS resource records should be overrideable
    IP-1365 Check for IPv6 support when installing services
    IP-1395 Domain redirect feature - Missing URL path separator
    IP-1402 ProFTPD doesn't restart properly - killed (signal 15) - Wheezy/Precise/Trusty
    IP-1410 Feature - Possibility to change redirection type
    IP-1509 Could not generate SSL certificate through the frontEnd - Invalide SSL certificate
    IP-1510 VsFTPd - Use of uninitialized value $tmpFile
    IP-1511 Use of uninitialized value $imscpOldConfig{"BASE_SERVER_VHOST"} on fresh install
    IP-1513 Autoreply - Do not autoreply to messages marked as SPAM
    IP-1514 Could not install i-MSCP on Ubuntu Trusty Thar in some contexts
    IP-1525 ifconfig output format has changed in latest versions
    IP-1527 Wrong syntax in the VsFTPd uninstaller, leading to an i-MSCP uninstallation failure
    IP-1530 Nginx - Failed to load resource: net::ERR_INCOMPLETE_CHUNKED_ENCODING
    IP-1533 i-MSCP is not compatible with servers on which IPv6 stack is disabled
    IP-1536 systemd - imscp_daemon service can fail to start/restart
    IP-1539 Default behaviour when adding a MySQL user is counter-intuitive
    IP-1549 RRL implementation for BIND/Named config
    IP-1555 Syntax error in imscp-disable-accounts script
    IP-1557 vsftpd - request failed due to PAM unable to dlopen(pam_mysql.so)...
    IP-1563 Default MX record not removed when adding external mailserver (domain type)
    IP-1564 Debian pam-mysql source package - Could not satisfy build dependencies
    IP-1565 Broken catch-all after deletion of a mailbox
    IP-1566 Exception thrown when requesting password for reseller with deleted admin
    IP-1567 "Unknown" status in users overview
    IP-1572 Upstart Provider - Possible precedence issue with control flow operator
    IP-1573 On i-MSCP update, some disabled customer items are still processed which is unexpected

------------------------------------------------------------------------------------------------------------------------
Older release series
------------------------------------------------------------------------------------------------------------------------

See ./docs/Changelog-x.x.x files
