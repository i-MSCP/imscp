<?php

/*
 * This is the configuration file for Check Quota Plugin.
 * You can edit the options here to suit your needs.
 * Please make sure you read the INSTALL file before
 * configuring these options.
*/

global $cq_check_quota_type, $cq_quota_binary,
       $cq_do_not_use_flash, $cq_use_gd_graphs,
       $cq_show_above_folders_list, $cq_font_size,
       $cq_graph_align, $cq_show_used_instead_of_percent,
       $cq_show_count_instead_of_percent, $cq_red_alert_percent,
       $cq_yellow_alert_percent, $cq_show_quota_intro_text,
       $cq_intro_text, $cq_show_count_quota_intro_text,
       $cq_count_intro_text, $cq_show_info_above_bar,
       $cq_show_filesystems, $cq_fs_alias,
       $cq_show_yellow_alert_MOTD, $cq_show_red_alert_MOTD,
       $cq_yellow_alert_MOTD, $cq_red_alert_MOTD,
       $cq_use_1000KB_per_MB, $cq_check_quota_debug,
       $cq_total, $cq_maxcount;

/*******************************
 * Quota Configuration Options *
 *******************************/


/*
 * Use IMAP or UNIX quotas?
 * If you use IMAP quotas, then you should set this to 0,
 * if you use UNIX quotas, then you should set this to 1.
 *
 * Default: IMAP Quotas
 * 0: IMAP / 1: UNIX
*/

$cq_check_quota_type = 0;


/*
 * If you are using UNIX quotas, set the quota binary
 * in this variable. See INSTALL for instructions.
*/

$cq_quota_binary = "/usr/bin/sudo /usr/bin/quota";


/***************************
 * General Display Options *
 ***************************/


/*
 * Use Flash or HTML quota graphics?
 * You can use Flash quota graphics for better looking
 * graphics, but as SquirrelMail is a pure PHP-driven
 * webmail program, Flash is not enabled by default.
 *
 * Default: Use HTML
 * 0: Use Flash / 1: Use HTML
*/

$cq_do_not_use_flash = 1;


/*
 * Take advantage of gd graphics library?
 * If you are not using Flash graphics, you can have
 * php gd library draw the quota bar for you.
 * For this to work PHP must be compiled with
 * --with-gd option so that GD functionality will
 * be enabled in PHP. See:
 * http://www.php.net/manual/ref.image.php
 *
 * NOTE that although the plugin attempts to prevent the
 * browser from caching these images, they are
 * unintentionally cached sometimes, and as such, using
 * this option is not recommended.
 *
 * Default: Use pure HTML
 * 0: Use pure HTML / 1: Use gd
*/

$cq_use_gd_graphs = 0;


/*
 * Will the quota information be shown above or below
 * the folders list?
 *
 * Default: Below
 * 0: Below / 1: Above
*/

$cq_show_above_folders_list = 0;


/*
 * Font size for quota information displayed on the
 * folders bar.
 *
 * Default: -2
*/

$cq_font_size = "-2";


/*
 * Alignment for the graph and text information on
 * the left pane. It can be "left", "center" or "right".
 *
 * Default: "center"
*/

$cq_graph_align = "center";  


/*
 * Whether to show used space (in KB, MB or GB depending
 * on usage or used percent of quota in the information
 * displayed on the folders bar. This is only relevant if
 * the user has size quota.
 * 
 * Default: Show percent   
 * 0: Show percent / 1: Show MB
*/

$cq_show_used_instead_of_percent = 0;


/*
 * Whether to show count or percent in the information
 * displayed on the folders bar. This is only relevant if
 * the user has file count quota.
 *
 * Default: Show percent
 * 0: Show percent / 1: Show file count
*/

$cq_show_count_instead_of_percent = 0;


/*
 * Quota percent thresholds. If the user's usage is more
 * than these, graphs will have different colors and also
 * you have the option to show a warning in MOTD.
 *
 * Defaults: Yellow: 70% / Red: 90%
*/
 
$cq_yellow_alert_percent = 70;
$cq_red_alert_percent = 90;


/*
 * Option to turn the introductory "Quota Usage"
 * text displayed on the folders bar, on or off.
 *
 * Default: On
 * 0: Off / 1: On
*/

$cq_show_quota_intro_text = 1;


/*
 * Option to override the "Quota Usage" text displayed
 * on the folders bar.
 * 
 * NOTE: If you override this text, internationalization
 * will be broken for this text, so this is not
 * recommended.
*/

//$cq_intro_text = _("Quota Usage:");

$cq_intro_text = '';


/*
 * Option to turn the introductory "File Usage"
 * text displayed on the folders bar, on or off.
 *
 * Default: On
 * 0: Off / 1: On
*/

$cq_show_count_quota_intro_text = 1;


/*
 * Option to override the "File Usage" text displayed
 * on the folders bar.
 * 
 * NOTE: If you override this text, internationalization
 * will be broken for this text, so this is not
 * recommended.
*/

//$cq_count_intro_text = _("File Usage:");

$cq_count_intro_text = '';


/*
 * Option to show the quota information above or below
 * the quota bar.
 * If $cq_show_quota_intro_text or $cq_show_filesystems
 * are turned on, this setting will have no effect for
 * "Quota Usage" text.
 * If $cq_show_count_quota_intro_text or $cq_show_filesystems
 * are turned on, this setting will have no effect for
 * "File Usage" text.
 *
 * Default: Below
 * 0: Below / 1: Above
*/

$cq_show_info_above_bar = 0;


/*
 *
 * Option to show filesystem names instead of "Quota Usage"
 * and "File Usage" texts. This is only relevant when
 * UNIX quotas are used.
 *
 * This is useful when you have more than one filesystem
 * which have quotas for your users.                  
 * 
 * If you have set up aliases for your filesystems below, you
 * can set this variable to show only the aliases or both the 
 * aliases and filesystem names. 
 * 
 * When set to "1" filesystem aliases override filesystem names.
 * 
 * Default: Off
 * 0: Off
 * 1: Show fs names OR aliases if an alias is set for that fs       
 * 2: Show fs names AND aliases if an alias is set for that fs   
*/                  
 
$cq_show_filesystems = 0;


/*
 * You can use this array to map different names to your          
 * filesystems. For example you can map "Mail Folder" for a 
 * filesystem named "/dev/hda4" etc. Leave it empty if you don't 
 * want to set up any aliases. [That is, simply comment out                
 * the lines between "(" and ")"]. If $cq_show_filesystems is
 * set to "0" this array will have no meaning.
*/

$cq_fs_alias = array (
        "/dev/md3" => "Mail",
        "/dev/hda5" => "Data",
        );


/*
 * Yellow alert login warning option. When turned on
 * and when the user exceeds the yellow alert
 * threshold, a warning message will be displayed as
 * MOTD when she logs on.
 *
 * Default: On
 * 0: Off / 1: On
*/

$cq_show_yellow_alert_MOTD = 1;


/*
 * Red alert login warning option. When turned on
 * and when the user exceeds the red alert
 * threshold, a warning message will be displayed as
 * MOTD when she logs on.
 *
 * Default: On
 * 0: Off / 1: On
*/

$cq_show_red_alert_MOTD = 1;


/*
 * Specify an override for warning text to be 
 * shown at login when quota is in yellow alert 
 * status, or leave blank to use the default
 * warning message.  This only has an effect 
 * if $cq_show_yellow_alert_MOTD is turned on.
 *
 * The special string "###PERCENT_USED###" can
 * be included, and will be replaced with the
 * actual percent of the user's quota when displayed.
 * 
 * HTML formatting is acceptable in this string.
 *
 * NOTE: If you override this text, internationalization
 * will be broken for this text, so this is not
 * recommended.
*/

//$cq_yellow_alert_MOTD = '<strong>WARNING:</strong> Your quota usage is currently <strong>###PERCENT_USED###%</strong>.  You may want to make sure you empty out your Trash and clean your Sent folder.';

$cq_yellow_alert_MOTD = '';


/*
 * Specify an override for warning text to be 
 * shown at login when quota is in red alert (critical)
 * status, or leave blank to use the default
 * warning message.  This only has an effect 
 * if $cq_show_red_alert_MOTD is turned on.
 *
 * The special string "###PERCENT_USED###" can
 * be included, and will be replaced with the
 * actual percent of the user's quota when displayed.
 *
 * HTML formatting is acceptable in this string.
 *
 * NOTE: If you override this text, internationalization
 * will be broken for this text, so this is not
 * recommended.
*/

//$cq_red_alert_MOTD = '<strong>WARNING:</strong> Your quota usage is currently <strong>###PERCENT_USED###%</strong>.  To avoid losing any email, you should immediately empty out your Trash and Sent folders and delete any emails with large attachments.';

$cq_red_alert_MOTD = '';


/*
 * If your quotas are set based on 1MB = 1000KB, you may
 * want to turn this option on. Otherwise, 1MB = 1024KB
 * will be used for calculations, and this is the generally
 * accepted conversion.
 *
 * Default: Off
 * 0: Off / 1: On
*/

$cq_use_1000KB_per_MB = 0;


/*
 * Turn on debugging for troubleshooting to see output from
 * server when IMAP quotas are used. This has no effect for
 * UNIX quotas.
 *
 * Default: Off
 * 0: Off / 1: On
*/

$cq_check_quota_debug = 0;

/********************
 * Override Options *
*********************/
 
/*
 * Some IMAP servers use dynamic quotas, so the users may
 * not be able to see their true quotas. In this situation
 * you can override the max quota information returned by
 * the plugin with these variables. For size quotas, change
 * $cq_total, for file count quotas, change $cq_maxcount.
 * Leave blank for server returned quota information to be
 * displayed.
 * 
 * NOTE: Values must be entered in Kilobytes.
*/
 
$cq_total = '';
$cq_maxcount = '';

?>
