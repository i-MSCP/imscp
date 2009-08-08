<?php

/**
 * Local config overrides.
 *
 * You can override the config.php settings here.
 * Don't do it unless you know what you're doing.
 * Use standard PHP syntax, see config.php for examples.
 *
 * @copyright &copy; 2002-2009 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: config_local.php 13549 2009-04-15 22:00:49Z jervfors $
 * @package squirrelmail
 * @subpackage config
 *
 * @modified by ispCP Omega Team http://isp-control.net
 */

/**
 * Possible IMAP servers are:
 * bincimap
 * courier
 * cyrus
 * dovecot
 * exchange
 * hmailserver
 * macosx
 * mercury32
 * uw
 *
 * others (for default)
 */

$imap_server_type = "courier";


/**
 * 'cyrus' settings
 */
if ($imap_server_type == "cyrus") {
	$default_folder_prefix 			= "";
	$trash_folder 					= "INBOX.Trash";
	$sent_folder 					= "INBOX.Sent";
	$draft_folder 					= "INBOX.Drafts";
	$show_prefix_option 			= false;
	$default_sub_of_inbox 			= true;
	$show_contain_subfolders_option = false;
	$optional_delimiter 			= ".";
	$delete_folder 					= true;
	$force_username_lowercase 		= false;
}
/**
 * 'courier' settings
 */
else if ($imap_server_type == "courier") {
	$default_folder_prefix 			= "INBOX.";
	$trash_folder 					= "Trash";
	$sent_folder 					= "Sent";
	$draft_folder 					= "Drafts";
	$show_prefix_option 			= false;
	$default_sub_of_inbox 			= false;
	$show_contain_subfolders_option = false;
	$optional_delimiter 			= ".";
	$delete_folder 					= true;
	$force_username_lowercase 		= false;
}
/**
 * 'dovecot' settings
 */
else if ($imap_server_type == "dovecot") {
	$default_folder_prefix 			= "";
	$trash_folder 					= "Trash";
	$sent_folder 					= "Sent";
	$draft_folder 					= "Drafts";
	$show_prefix_option 			= false;
	$default_sub_of_inbox 			= false;
	$show_contain_subfolders_option = false;
	$optional_delimiter 			= "detect";
	$delete_folder 					= false;
	$force_username_lowercase 		= true;
}
