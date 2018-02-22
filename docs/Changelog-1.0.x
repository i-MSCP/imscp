i-MSCP ChangeLog

-------------------------------------------------------------------------------------
i-MSCP 1.0.3.0
-------------------------------------------------------------------------------------
2012-03-23: Torsten Widmann
	- RELEASE i-MSCP 1.0.3.0 (Stable)

Tickets:
	- Fixed #388: Cosmetics - Typo Error
	- Fixed #389: Defect - Plugins installed by admin are removed during update
	- Fixed #391: Bug - HELO-Mismatch (policyd-weight)
	- Fixed #393: Defect - Apache cannot restart after a libapach2-mod* update
	- Fixed #394: Defect - Wrong permissions for /etc/policyd-weight.conf

-------------------------------------------------------------------------------------
i-MSCP 1.0.2.2 (RC3)
-------------------------------------------------------------------------------------
2012-03-16: Torsten Widmann
	- RELEASE i-MSCP 1.0.2.2 (RC3)

Distributions:
	Ubuntu:
		- Added support for Oneiric Ocelot

Features / Enhancements:

	GUI:
		Tools:
			- Replaced net2ftp by ajaxplorer

Tickets:
	- Fixed #296: Defect - Some issues with dovecot migration script
	- Fixed #366: Enhancement - Move menu label show/disable option at user profile level
	- Fixed #370: Enhancement - Admin/Reseller must be able to edit "Open_Base_Dir" for a domain
	- Fixed #372: Cosmetic - Change colour of enable/disabled Features in PHP Editor
	- Fixed #375: Defect - GUI should allow shared mount points
	- Fixed #377: Defect - Some issues with shared mount point
	- Fixed #380: Bug - Order Panel - An exception have been thrown Hosting
	- Fixed #381: Defect - Wrong query in setup_imscp_database - Drop user - Condition
	- Fixed #382: Defect - Notifications system for authentication is broken
	- Fixed #384: Defect - Deleting FTP-Account: An exception have been thrown
	- Fixed #385: Enhancement - Unable to install correctry - Installer: fqdn & hostname errors
	- Fixed #386: Defect - The gui cache directory must be purged during update

-------------------------------------------------------------------------------------
i-MSCP 1.0.2.1 (RC2)
-------------------------------------------------------------------------------------
2012-02-28: Torsten Widmann
	- RELEASE i-MSCP 1.0.2.1 (RC2)

Features / Enhancements:

	GUI:
		Core:
			- Added plugins management interface
			- Added action script (rest.php) to handle REST requests (The REST server will comming soon)
			- Updated action scripts and core library to fit with events system changes
		Components:
			- Authentication
				- Added authentication base class to handle authentication process
				- Added bruteforce class for bruteforce detection
			- Events system:
				- Added new events (See iMSCP_Events class)
				- Added support for listeners return values
				- Finished implementation of iMSCP_Events_Manager_interface
				- Updated Events documentation (iMSCP_Events, iMSCP_Database_Events, iMSCP_pTemplate_Events)
			- DebugBar:
				- Updated to fit with events system changes
			- Plugins:
				- Added plugin manager
				- Updated demo plugin to fit with events system changes
	Tools:
		- Updated PhpMyAdmin to version 3.4.10.1

Tickets:
	- Fixed #68: Defect - Hide disabled features
	- Fixed #201: Cosmetics - An exception have been thrown at Login with wrong password
	- Fixed #254: Defect - Error raised for default PHP directives values
	- Fixed #325: Defect - Missing PHP sendmail parameter in vhost file for ITK
	- Fixed #329: Bug - Custom DNS record - Syntax issue
	- Fixed #343: Defect - Disabled Mail should remove DNS entries
	- Fixed #345: Bug - admin - statistics
	- Fixed #346: Cosmetics - SquirrelMail - Warning: mime.php on line 36
	- Fixed #353: Bug - An exception have been thrown, when deleting a DB
	- Fixed #354: Bug - Timezone is not set in ITK configuration
	- Fixed #355: Defect - PHP Editor - When using ITK disabled functions must be hidden
	- Fixed #358: Cosmetics - OrderPanel - Disk and Traffic limits
	- Fixed #361: Bug - main::setup_imscp_database: HASH(0xb0e18fc)
	- Fixed #373: Defect - i18n issues
	- Fixed #374: Defect - system php include dirs should be openbasedir allowed paths

-------------------------------------------------------------------------------------
i-MSCP 1.0.2.0 (RC1)
-------------------------------------------------------------------------------------
2012-02-10: Torsten Widmann
	- RELEASE i-MSCP 1.0.2.0 (RC1)

Tickets:
	- Fixed #309: Review - User profile icon must be revisited
	- Fixed #323: Defect - Password for customer not sent via mail
	- Fixed #325: Defect - Missing PHP sendmail parameter in vhost file for ITK
	- Fixed #328: Defect - Pagination is broken (reseller/domain_alias.tpl)
	- Fixed #330: Defect - The cron task for the anti-rootkit (chkrootkit) doesn't work
	- Fixed #332: Bug - Autoresponder - alias missing
	- Fixed #336: Bug - Domain alias deletion
	- Fixed #327: Bug - Fcgi process should be killed during setup/update
	- Fixed #344: Bug - PHP session garbage collector not implemented for the panel (GUI)

-------------------------------------------------------------------------------------
i-MSCP 1.0.1.6 (beta 6)
-------------------------------------------------------------------------------------
2012-01-19: Torsten Widmann
	- RELEASE i-MSCP 1.0.1.6 (Beta 6)

Features / Enhancements:

	ENGINE:
		Core:
			- Added support for Apache2 MPM ITK
			- Added SSL support for customers
	GUI:
		Components:
			- DebugBar:
				- Updated layout
				- Files plugin: Count and shows the loaded templates files
		Core:
			- Added layout color switcher
			- Added layout colors (black, green, red, yellow)
			- Added admin setting to allow to show/hide main menu labels
			- Events system:
				- Allow to pass arbitrary data to the events listeners methods
				- Allow to use closures
			- Integrated Zend_Navigation component (without view helper)
			- Reseller level - Added flash message for new orders (Only for confirmed orders)
			- Reseller level - Added flash message for new domain alias orders
			- PhpMyAdmin on-click logon - Set language according user panel language
			- pTemplate: Added a set of events (see the iMSCP_pTemplate_Events class)
		Javascript
			- Integrated jQuery DataTables plugin (v.1.8.2)
			- Updated jQuery to version 1.7.1
			- Updated jQuery UI to version 1.8.16 (minified version)
			- Rewritten iMSCP jQuery tooltip plugin
		Tools:
			- Updated PhpMyAdmin to version 3.4.9

Tickets:
	- Fixed #47:  Feature - Theme color chooser
	- Fixed #228: Enhancement - Multiple HTTPS domains on same IP + wildcard SSL
	- Fixed #240: Defect - Next page function on reseller statistics page doesn't work
	- Fixed #242: Security Faillure - Directories are created with group write permissions
	- Fixed #244: Bug - admin/domain_edit.php can cause several issues and must be rewritten
	- Fixed #245: Defect - rkhunter log file is not created
	- Fixed #246: Defect - Traffic accounting seem to be broken
	- Fixed #248: Defect - Bind Slave Mode
	- Fixed #250: Defect - Wrong units in system tools overview
	- Fixed #251: Defect - OldConfig uninitialised on fresh install
	- Fixed #253: Defect - When domain backups are disable there should not be warning emails
	- Fixed #255: Defect - Hide support feature menu item at user level
	- Fixed #257: Defect - Uninstall error
	- Fixed #259: Cosmetics - Different style for input and select elements
	- Fixed #260: Cosmetics - Move app installer menu items to different section
	- Fixed #262: Defect - AWStats not included in apache logrotate config
	- Fixed #263: Defect - Unable to delete htusers && htgroups
	- Fixed #264: Defect - PHP Editor not available
	- Fixed #265: Defect - Disabled feature can not be enabled
	- Fixed #267: Defect - mod_cband configurations are not removed
	- Fixed #269: Defect - Reseller can´t edit php.ini settings for costumer
	- Fixed #270: Defect - Users Can´t acces domain overview
	- Fixed #271: Enhancement - Do not enforce session reuse for FTP over SSL (Tx Cube)
	- Fixed #272: Task - Update postfix's master.cf
	- Fixed #274: Defect - proftpd limits do not work
	- Fixed #275: Defect - Service status always down when using ipv6 as base ip
	- Fixed #276: Defect - proftpd can´t resolv hostname when using ipv6 as base ip
	- Fixed #278: Defect - Possible corruption of postfix's domain configuration
	- Fixed #279: Defect - Installer should not fail when we are using another webmail
	- Fixed #286: Defect - Error by update
	- Fixed #287: Update - phpMyAdmin 3.4.8 released
	- Fixed #289: Defect - Ubuntu update error without end
	- Fixed #292: Feature - Layout color chooser
	- Fixed #294: Defect - Notice: Undefined index: user_id...
	- Fixed #297: Defect - Wrong data type in PerlLib/Addons/awstats.pm line 196
	- Fixed #298: Defect - Configuration variable `CLIENT_TEMPLATE_PATH` is missing.
	- Fixed #299: Defect - Error while installing apache itk
	- Fixed #300: Defect - Can't locate Servers/ftpd/proftpd/uninstaller.pm
	- Fixed #301: Bug - Error while Inserting a SSL-Certificate
	- Fixed #302: Defect - Notice: Undefined index: t_software_menu
	- Fixed #303: Defect - Double tooltip for software description on admin and reseller level
	- Fixed #304: Enhancement - Postfix configuration should log user authentication
	- Fixed #305: Enhancement - lostpw-login screen
	- Fixed #307: Defect - Software installer - Many pages are missing in xml menu files
	- Fixed #310: Defect - Error after inserting a SSL-certificate
	- Fixed #311: Defect - autoinstaller cleans squirrel data folder
	- Fixed #313: Enhancement - Some improvements on postfix's master.cf (tx aseques)
	- Fixed #314: Defect: Software package installation failed
	- Fixed #317: Malfunction - Bruteforce detects successful connections as attacks
	- Fixed #318: Malfunction - Changes in imscp.conf are lost after upgrading

-------------------------------------------------------------------------------------
i-MSCP 1.0.1.5 (beta 5)
-------------------------------------------------------------------------------------
2011-10-25: Torsten Widmann
	- RELEASE i-MSCP 1.0.1.5 (Beta 5)

Features / Enhancements:

	ENGINE:
		Core:
			- Added PHP directives editor
			- Added support for Dovecot
			- Added IPv6 support
			- Engine rewrite including the following new features:
				- Ability to turn off one or many services/servers such as Apache,
				  Bind9, Postfix...
				- Ability to setup DNS service (Bind9) as Master or Slave server
				- New logger (one file per operation)
				- php.ini per user account (default), or per domain entity (vhost)
	GUI:
		Core:
			- Added IPv6 support
			- Added PHP directives editor
		Javascript:
			- Updated jQuery to version 1.6.4
			- Updated jQuery UI to version 1.8.16
		Tools:
			- Updated PhpMyAdmin to version 3.4.7
			- Updated Squirrelmail to version 1.4.22
	SETUP:
		- Added Auto-installer (including both tree builder and setup based on Dialog)

Tickets:
	- Fixed #15:  Feature - PHP directives editor
	- Fixed #16:  Feature - Mail Quota Support
	- Fixed #57:  Enhancement - Adapt the postfix master.cf to be compatible with dovecot
	- Fixed #58:  Enhancement - Default mail quota should be increased up to 100MB
	- Fixed #77:  Task - Setup - Empty files must be removed
	- Fixed #79:  Feature - Support for IPv6
	- Fixed #94:  Enhancement in imscp-vrl-traff
	- Fixed #111: Feature - Add multiple secondary DNS server-wide
	- Fixed #144: Bug - When disabling an account sub-domains/aliases are still working
	- Fixed #147: Squirrelmail 1.4.22 released
	- Fixed #151: Enhancement - Messages system
	- Fixed #161: Defect - Warnings on Webmail
	- Fixed #164: Bug - Customer not set to status "change" if update email limit
	- Fixed #165: Bug - Wrong level for page message
	- Fixed #166: Enhancement - Add Dovecot support
	- Fixed #171: Bug - Auto-installer is broken
	- Fixed #172: Bug - Bad template and variable name
	- Fixed #173: Update - phpMyAdmin 3.4.4 released
	- Fixed #174: Bug - no session directory
	- Fixed #175: Nice To Have - mail_addr saved in mail_type_forward too
	- Fixed #177: Cosmetics - Postfix alias file
	- Fixed #178: Enhancement - dovecot managed sieve missing
	- Fixed #179: Bug - Unexpected T_STRING
	- Fixed #183: Bug - client/subdomain_edit.php shows a white page
	- Fixed #182: Defect - set_page_message is not fully integrated
	- Fixed #185: Bug - reseller/ip_usage.php doesn't show any statistics
	- Fixed #186: Defect - Typo error
	- Fixed #187: Update - phpMyAdmin 3.4.5 released
	- Fixed #188: Defect - Table quota_dovecot is still myisam than innoDB
	- Fixed #189: Bug - Undefined template replacement data in repl_var...
	- Fixed #193: Bug - Adding reseller throws exception (phpini... var is missing)
	- Fixed #194: Bug - client/domain_manage.php doesn't show php.ini menu entries
	- Fixed #195: Bug - syntax error, unexpected '('...
	- Fixed #196: Defect - squirrelmail required for upgrade
	- Fixed #197: Bug - network card management don't work as expected
	- Fixed #198: Defect Zend Uri validation do not obey TLD_STRICT_VALIDATION
	- Fixed #200: Malfunction - False error on update - 00_master_ssl.conf does not exist!
	- Fixed #202: Bug - Unknown column php_ini_al_disable_functions in reseller_props table
	- Fixed #203: Bug - Apache needs manual restart after fresh installation
	- Fixed #207: Bug - Bug in postfix alias file if it a mail copy
	- Fixed #209: Bug - Autoresponser no permission (filepermission)
	- Fixed #210: Bug - Some issues with new engine (Apache vhost files)
	- Fixed #211: Bug - Placeholder not parsed for the GUI php5-fcgi-starter file
	- Fixed #212: Bug - Some issues with installer
	- Fixed #215: Bug - Subdomains is not accessible to edit phpini
	- Fixed #218: Bug - Update trunk don't work because of dovecot 2.0.15-1
	- Fixed #220: Bug - Webspace Display show's wrong Values
	- Fixed #222: Defect - The script to migrate from ispcp should take care of killing the ispcp-daemon
	- Fixed #223: Defect - Security issue
	- Fixed #224: Enhancement - There is a confusing message in the imscp-setup
	- Fixed #225: Defect - Webspace indicator shows Values from Main Domain in Subdomains
	- Fixed #226: Update - PMA 3.4.6 released
	- Fixed #229: Cosmetics - i18n issue
	- Fixed #230: Defect - Build step - Skeleton directory for disabled pages not saved for versions prior 1.0.1.4
	- Fixed #233: Defect - Some Words not Translated [German]
	- Fixed #234: Defect - PostGrey misspelled in imscp.conf
	- Fixed #238: Bug - After deleting a customer, the entries belong to the PHP directives editor still exist
	- Fixed #239: Update - PMA 3.4.7 released

-------------------------------------------------------------------------------------
i-MSCP 1.0.1.4 (beta 4)
-------------------------------------------------------------------------------------

2011-07-24: Torsten Widmann
	- RELEASE i-MSCP 1.0.1.4 beta 4

Distributions:
	- Removed 'configs' directories for distributions that are no longer supported:
	  CentOS, Fedora, FreeBSD, Gentoo, OpenBSD, OpenSuse

Features / Enhancements:

	GUI:
		Core:
			- Improved security by moving some files outside documentRoot
		i18n:
			- Migrated to gettext (Machine object files), included:
				- Database is no longer used to store translation tables
				- It's no longer possible to delete languages
				- Importing languages files for install/update is still supported but
				  only for Machine Object files
		Javascript:
			- Updated jQuery to version 1.6.2
			- Updated jQuery UI (core and datepicker) to version 1.8.14
		Tools:
			- Updated PhpMyAdmin to version 3.4.3.2

Tickets:
	- Fixed #43:  Enhancement - You are blocked for 30 minutes
	- Fixed #65:  Good practices - Software Installer - All shared functions must be put in specific file
	- Fixed #90:  Defect - Custom logo feature is broken
	- Fixed #101: Defect - Missing messages in Software Installer
	- Fixed #103: Enhancement - Admin should have the rights to delete packages
	- Fixed #113: Bug - Tree Builder only works with lowercase dirs
	- Fixed #118: Update - PMA 3.4.3 released
	- Fixed #119: Defect - Error when adding IP's
	- Fixed #121: Bug - Users with domain set not to expire can not login
	- Fixed #122: Bug - Lost password do not work
	- Fixed #123: Cosmetics - Tabs's links are not showed properly on login page
	- Fixed #124: Enhancement - Switch to PHP gettext
	- Fixed #125: Defect - Database Upgrade Required on Clean Install
	- Fixed #126: Bug - Enter key on login form redirect on lost password page
	- Fixed #127: Update - jQuery v1.6.2 available
	- Fixed #128: Update - phpMyAdmin 3.4.3.1 available
	- Fixed #129: Bug - Default ISP logo not showed - wrong path generated
	- Fixed #130: Defect - PHP gettext native support - Files cache issue
	- Fixed #131: Bug - Awstats in dynamic mode do not work
	- Fixed #132: Bug - ACTIVATION_LINK placeholder not replaced in mail
	- Fixed #133: Bug - SQL users are not deleted on domain deletion
	- Fixed #139: Bug - Unable to disable domain account
	- Fixed #141: Bug - View Aliases link doesn't work (admin level)
	- Fixed #142: Task - PMA config File have to be updated to new Dir Structure
	- Fixed #145: Defect - Some items are not deleted on user deletion
	- Fixed #150: Bug - Tickets feature is broken
	- Fixed #153: BUG - Some pages lack footer
	- Fixed #154: Update - phpMyAdmin 3.4.3.2 available
	- Fixed #156: Bug - Users's properties must be loaded in session before i18n initialization
	- Fixed #159: Defect - 404 after update a customer (Hosting plans available for admin)
	- Fixed #160: Defect - Variable {YOU_ARE_LOGGED_AS} on edit domain (Hosting plans available for admin)

-------------------------------------------------------------------------------------
i-MSCP 1.0.1.3 (beta 3)
-------------------------------------------------------------------------------------

2011-06-24: Laurent Declercq
	- RELEASE i-MSCP 1.0.1.3 beta 3

Features / Enhancements:

	ENGINE:
		Core:
			- Added SSL support for Postfix, courier (imap/pop)
			- Added SSL support for Proftpd
	GUI:
		Components:
			- DebugBar: Added $_SERVER variable (plugin variables)
		Core:
			- Added SSL support for master (panel) vhost
			- Update (database): Improved both execution time and memory consumption

Tickets:
	- Fixed #2:   Feature - SSL support for master vhost
	- Fixed #93:  Defect - Login page doesn't look good on IE7
	- Fixed #96:  Bug - Notice raised when clicking on link from the domain' default page
	- Fixed #97:  Good practices - Throw an exception if the phptmp directory is unwritable
	- Fixed #98:  Bug - Encoding issue in include/i18n.php
	- Fixed #99:  Bug - Undefined variable: DB in reseller/software_upload.php on line 457
	- Fixed #100: Bug - Default language overrides user language at admin level
	- Fixed #102: Bug - Unable to install more than one language file
	- Fixed #104: Task - autoreplies_log database table still use MYISAM engine)
	- Fixed #105: Bug - Database update 58 is faulty
	- Fixed #107: Defect - Unable to import languages files pulled from transifex
	- Fixed #108: Bug - Reseller's properties set to NULL when editing a domain

-------------------------------------------------------------------------------------
i-MSCP 1.0.0-beta2
-------------------------------------------------------------------------------------

2011-06-20: Laurent Declercq
	- RELEASE i-MSCP 1.0.0 beta 2

	GUI:
		- Removed support for PHP version older than 5.3.2

Tickets:
	- Fixed #92: Bug - Installation of Language File not possible

-------------------------------------------------------------------------------------
i-MSCP 1.0.0-beta1
-------------------------------------------------------------------------------------

2011-06-18: Torsten Widmann
	- RELEASE i-MSCP 1.0.0 beta 1

Distributions:
	Debian:
		- Added support for testing (wheezy)
		- Removed support for oldstable (lenny) - Perl ≥ v5.10.1 required

Features / Enhancements:

	DATABASE:
		- Switched to InnoDB engine
	ENGINE:
		Config:
			- Moved keys file into /etc
		Core:
			- Improved logging system
		Migration:
			- Added migration script for ispCP version ≥ 1.0.7
		Setup/Update:
			- New i-mscp tree builder
			- New installer (Dialog - merged setup and update scripts)
	GUI:
		Core:
			- Added DebugBar component
			- Added Events system component
			- Added on-click-logon ftp-user - Thanks to William Lightning
			- Added Software installer
			- Dropped password encryption for Ftp, Mail accounts...
			- New theme (XHTML strict)
			- Rewritten Update component
			- Removed support for importation of languages from text files
		Tools:
			- Updated PhpMyAdmin to version 3.4.2
		Vendor:
			- Updated idna_convert.class.php to version 0.8.0
			- Updated Net_DNS to version 1.0.7

Tickets:
	- Fixed #3:   Defect - Wrong DNS entry when using multiple IP for alternative
	- Fixed #5:   Update - jQuery - version 1.4.4 available
	- Fixed #6:   Update - idna_convert class - version 0.6.9 available
	- Fixed #7:   Task - Import i-MSCP theme
	- Fixed #10:  Enhancement - IP-Overview for admin and reseller
	- Fixed #11:  Defect - Wrong used variables
	- Fixed #12:  Security Failure - Some CSRF issues in admin log
	- Fixed #14:  Feature - Software Installer
	- Fixed #23:  Merge Config files if not Distribution specific
	- Fixed #24:  Missing Program "Dialog" in package list
	- Fixed #25:  Little Error in INSTALL
	- Fixed #26:  Warnings in build script being installed via serial console
	- Fixed #27:  Add logger to build script
	- Fixed #28:  Defect - [FreeBSD] imscp-build use wrong path
	- Fixed #29:  Wrong link during Setup
	- Fixed #30:  Domainname Error during setup
	- Fixed #31:  Malfunction - [FreeBSD] An error occurred during setup process
	- Fixed #32:  Task - Needed package to install CPAN XML::Parser
	- Fixed #34:  Task - Default and Disabled Headers picture mismatch
	- Fixed #35:  Defect - Missing back stick in SQL
	- Fixed #36:  Deprecated: Call-time pass
	- Fixed #37:  Cosmetics - Hosting plan Header Color
	- Fixed #38:  Defect - Missing checkbox for hostingplan Terms of Service
	- Fixed #39:  Cosmetics - Missing background for the datepicker header
	- Fixed #40:  Updated phpMyAdmin to version 3.3.8.1
	- Fixed #42:  Cosmetics - Wrong design in phpMyAdmin
	- Fixed #44:  Update - Net::DNS v1.0.7 is available
	- Fixed #48:  Task - Licence header for create_release.sh
	- Fixed #49:  Cosmetics - Some improvements on multilanguage.php
	- Fixed #50:  Malfunction - Patch to fix the exception when switching users
	- Fixed #51:  Enhancement - Change Package actions if software is installed
	- Fixed #52:  Cosmetics - Occurences of ispcp in Sources
	- Fixed #53:  Cosmetics - Wrong link in docs/Ubuntu/INSTALL
	- Fixed #54:  Cosmetics - Wrong header in gui/admin/ip_usage.php
	- Fixed #56:  Cosmetics - Wrong logo path
	- Fixed #59:  Bug - Wrong path in debian and ubuntu config
	- Fixed #66:  Migration script
	- Fixed #67:  Database PDO::MYSQL_ATTR_USE_BUFFERED_QUERY
	- Fixed #73:  Bug - Problem with Mail (No Login possible)
	- Fixed #74:  Task - Template not yet updated
	- Fixed #75:  Bug - Can't change Admin password
	- Fixed #76:  Bug - Typo in SQL query
	- Fixed #78:  Bug - Typo error in HTML
	- Fixed #80:  Bug - Wrong referenced links
	- Fixed #81:  Bug - reseller can not edit user from view details
	- Fixed #82:  Cosmetics - client/webtools.php lookout lack picture
	- Fixed #83:  Bug - client/ftp_choose_dir.php result in blank page
	- Fixed #84:  Bug - Unable to edit custom errors pages (client)
	- Fixed #85:  Defect - http://app-pkg.i-mscp.net is not reachable
	- Fixed #86:  Bug - Administrator can not change default language for panel
	- Fixed #87:  Bug - Can not delete database
	- Fixed #88:  Bug - Reseller can not delete orders
	- Fixed #89:  Cosmetics - Traffic display in client/index.php
	- Fixed #95:  Bug - Version update check is broken
	- Fixed #104: Task - autoreplies_log database table still use MYISAM engine
	- Fixed #105: Bug - Database update 58 is faulty
