<?php

/*
 * This is the configuration file for Check Quota Plugin.
 * You can edit the options here to suit your needs.
 * Please make sure you read the INSTALL file before configuring 
 * these options.
*/

global $settings;
$settings = array();


/*******************************
 * Quota Configuration Options *
 *******************************/

/*
 * Use UNIX (filesystem), IMAP-based or cPanel (experimental) quotas?
 * If you use UNIX (filesystem) quotas, you should set this to 0.
 * If you use IMAP-based quotas, you should set this to 1.
 * If you use cPanel quotas, you should set this to 2.
 *
 * Default: UNIX (filesystem) quotas
 * 0: UNIX / 1: IMAP / 2: cPanel
*/

$settings['quota_type'] = 0;


/*
 * If you are using UNIX (filesystem) quotas, set the path 
 * to the "sudo" binary here.  See INSTALL for instructions.
*/

$settings['sudo_binary'] = '/usr/bin/sudo';


/*
 * If you are using UNIX quotas, set the path to the "quota"
 *  binary here.  See INSTALL for instructions.
*/

$settings['quota_binary'] = '/usr/bin/quota';


/*
 * If you are using UNIX quotas and your IMAP server and web server
 * are on different physical machines (i.e. you want to check UNIX
 * quotas remotely) set this to 1.  See INSTALL for instructions.
 *
 * This setting is only used for UNIX quotas.
 *
 * Default: Check on local server
 * 0: Check on local server / 1: Check on remote server
*/

$settings['check_unix_on_remote_server'] = 0;


/*
 * If you are using UNIX quotas and your IMAP server and web server 
 * are on different physical machines (i.e. you want to check UNIX 
 * quotas remotely) set the path to the "ssh" binary here.  See INSTALL 
 * for instructions.
 *
 * This setting is only used for remote UNIX quotas.
*/

$settings['ssh_binary'] = '/usr/bin/ssh';


/*
 * If you are using UNIX quotas and your IMAP server and web server 
 * are on different physical machines (i.e. you want to check UNIX
 * quotas remotely) set the username that will check quotas on your 
 * IMAP server here.  See INSTALL for instructions.
 *
 * This setting is only used for remote UNIX quotas.
*/

$settings['remote_unix_user'] = 'cquotauser';


/*
 * If you are using UNIX quotas and your IMAP server and web server
 * are on different physical machines (i.e. you want to check UNIX
 * quotas remotely) set the IP address or hostname of your IMAP 
 * server here.  See INSTALL for instructions.
 *
 * This setting is only used for remote UNIX quotas.
*/

$settings['remote_unix_server'] = '192.168.1.1';


/*
 * If you are using cPanel quotas, set the path to the "du" binary 
 * here.  See INSTALL for instructions.
*/

$settings['du_binary'] = '/usr/bin/du';


/*
 * If you are using cPanel quotas, set the cPanel root path here.
 * See INSTALL for instructions.
*/

$settings['cpanel_root'] = '/path/to/cpanel/root';


/*
 * If you want to use a separate IMAP connection when checking your
 * quota, set this variable to 1, else, set it to 0
 *
 * This will be a waste of resources if there already is an open
 * IMAP connection to your server. So, most probably you will not need
 * to adjust this variable in most occasions.
 *
 * Default: Do not use separate IMAP connection
 * 0: Do not use separate connection / 1: Use separate connection
*/

$settings['use_separate_imap_connection'] = 0;

/***************************
 * General Display Options *
 ***************************/

/*
 * Which graph type do you prefer?
 *
 * You have three options here.  You can use HTML tables, GD images, 
 * or Flash graphics.
 *
 * GD images can only be generated if you have GD support built into
 * PHP (--with-gd).  For more information on how to install GD support
 * in PHP, see: 
 *
 * http://www.php.net/manual/ref.image.php
 *
 * If your server doesn't have GD png, gif or jpg support but you enable GD
 * graphics, the plugin will revert to HTML tables.
 *
 * As SquirrelMail is a pure PHP-driven webmail program, and some people do
 * not have GD support in PHP, default is HTML tables.
 *
 * You will get the most satisfying graph output with GD or Flash graphs.
 *
 * Default: Use HTML
 * 0: Use HTML / 1: Use GD / 2: Use Flash 
*/

$settings['graph_type'] = 0;


/*
 * Will the quota information be shown above or below the folders list?
 *
 * Default: Above
 * 0: Below / 1: Above
*/

$settings['info_above_folders_list'] = 1;


/*
 * Font size for quota information displayed on the folder pane.
 * Must be a positive or negative integer value.
 *
 * Default: -2
*/

$settings['font_size'] = -2;


/*
 * Alignment for the graph and text information on the folder pane.  
 * This can be "left", "center" or "right".
 *
 * Default: "left"
*/

$settings['graph_alignment'] = 'left';  


/*
 * Whether or not to use horizontal rules to seperate quota information
 * from other information on the folder pane.
 *
 * Default: No
 * 0: No / 1: Yes
*/

$settings['use_hr'] = 0;


/*
 * Whether or not to show the introductory "Quota Usage" text for 
 * size-based quotas and the "File Usage" text for count-based quotas 
 * displayed on the folder pane.
 *
 * Default: On
 * 0: Off / 1: On
*/

$settings['show_intro_texts'] = 1;


/*
 * Show the quota details above or below the quota graph?
 *
 * Default: Below
 * 0: Below / 1: Above
*/

$settings['details_above_graph'] = 0;


/*
 * Whether to show used space (in KB, MB or GB depending on usage) for
 * size-based quotas and file count for count-based quotas instead of
 * percent usage in the information displayed on the folder pane.
 * 
 * Default: Show percent   
 * 0: Show percent / 1: Show size/count
*/

$settings['show_quantity_instead_of_percent'] = 0;


/*
 * Quota percent thresholds.  If the user's usage is more than one 
 * of these, the graph will use a different color and you also have 
 * the option to show a warning in the SquirrelMail "MOTD" display 
 * area when the user logs in.
 *
 * These must be positive integers.
 *
 * Defaults: Yellow: 70% / Red: 90%
*/
 
$settings['yellow_alert_limit'] = 70;
$settings['red_alert_limit'] = 90;


/*
 * Yellow alert login warning option.  When turned on and when the 
 * user's usage exceeds the yellow alert threshold (see above), a 
 * warning message will be displayed in the SquirrelMail "MOTD" area
 * when he/she logs on.
 *
 * Default: On
 * 0: Off / 1: On
*/

$settings['show_yellow_alert_motd'] = 1;


/*
 * Red alert login warning option.  When turned on and when the 
 * user's usage exceeds the red alert threshold (see above), a 
 * warning message will be displayed in the SquirrelMail "MOTD" area
 * when he/she logs on.
 *
 * Default: On
 * 0: Off / 1: On
*/

$settings['show_red_alert_motd'] = 1;


/*
 * If your quotas are set based on 1 MB = 1000 KB, you may want to 
 * turn this option on.  Otherwise, 1 MB = 1024 KB will be used for 
 * all quota calculations, which is the generally accepted convention.
 *
 * Default: Off
 * 0: Off (1MB = 1024KB) / 1: On (1MB = 1000KB)
*/

$settings['use_1000KB_per_MB'] = 0;


/*
 * Whether or not to show filesystem names.  This setting 
 * is only used for UNIX (filesystem) quotas.
 *
 * This is useful when you have more than one filesystem 
 * which have quotas for your users.                  
 * 
 * When set to "1" filesystem aliases override filesystem names.
 * 
 * Default: Off
 * 0: Off
 * 1: Show filesystem name or alias if an alias is set for that filesystem   
*/                  
 
$settings['show_filesystems'] = 0;


/********************
 * Override Options *
 ********************/
 
/*
 * You can use this list to map different names to your filesystems.  For
 * example, you can display "Mail Folder" instead of the filesystem name 
 * "/dev/hda4".  Leave this empty if you don't want to set up any such 
 * aliases.  If "show_filesystems" is set to "0" this list will have no 
 * meaning.
 *
 * This setting is only used for UNIX quotas.
 *
 * If you use this setting, make sure to replace "/dev/hda8" and "/dev/sda1"
 * below with the filesystems that are appropriate on your system.
*/

$settings['fs_alias'] = array (
                               '/dev/hda8' => 'Mail',
                               '/dev/sda6' => 'Data',
                              );


/*
 * You can use this list to exclude some filesystems in your quota output.
 * This allows you to only show quota information for the filesystem that
 * your mail accounts reside on, to avoid confusion.
 *
 * You don't need to add filesystems which do not have any
 * quota support here.
 *
 * You will need to write the exact filesystem name and, if you want to
 * hide that filesystem, you will need to set its value to 1.  If you want
 * to show that filesystem, you can remove its entry from this list, or
 * you can set its value to 0.
 *
 * This setting is only used for UNIX quotas.
 *
 * If you use this setting, make sure to replace "/dev/hda8" and "/dev/sda1"
 * below with the filesystems that are appropriate on your system.
*/

$settings['exclude_fs'] = array (
                                 '/dev/hda8' => 1,
                                 '/dev/sda6' => 0,
                                );


/*
 * Some IMAP servers use dynamic quotas, so the users may not be able to
 * see their true quotas.  In this situation you can override the max quota
 * information returned by the plugin with these variables.  For size-based
 * quotas, change "imap_size_quota", for count-based quotas, change
 * "imap_count_quota".
 *
 * Leave blank for server returned quota information to be displayed.
 *
 * This setting is only used for IMAP quotas.
 * 
 * NOTE: Values must be entered in Kilobytes.
*/
 
$settings['imap_size_quota']  = '';
$settings['imap_count_quota'] = '';


/*
 * As cPanel quotas work differently from UNIX or IMAP quotas, we have to
 * use "du" to calculate these type of quotas.  Using "du" can be very
 * exhaustive for a slow or loaded server.  So as a default, we calculate
 * cPanel quotas only once for a logged in user.  To refresh the quota
 * information the user has to log out and log in again.  This option
 * overrides this behavior and refreshes cPanel quota information on every
 * refresh of the folders list.
 *
 * CAUTION: enabling this may place unnecessarily high load on your server.
 *
 * cPanel quota information is always refreshed on the troubleshooting page,
 * regardless of this option.
 *
 * This setting is only used for cPanel quotas.
 *
 * Default: Off
 * 0: Off; calculate quota once at time of login
 * 1: Always refresh cPanel information
*/

$settings['always_refresh_cpanel'] = 0;

 
/********************
 * Troubleshooting  *
 ********************/

/*
 * Turn this on to see troubleshooting output from server for help
 * debugging your Check Quota settings.  This will add a link to 
 * the "Options" page with the name "Check Quota Troubleshooting".  
 * You should always turn this off when Check Quota is working as 
 * intended.
 *
 * Default: Off
 * 0: Off / 1: On
*/

$settings['troubleshoot'] = 0;

