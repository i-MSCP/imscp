<?php

if ( !defined('SM_PATH') ) {
    define('SM_PATH', '../../');
}

include_once(SM_PATH . "plugins/check_quota/config.php");

function squirrelmail_plugin_init_check_quota() {
  global $squirrelmail_plugin_hooks, $cq_show_above_folders_list;
  if ( isset($cq_show_above_folders_list) && $cq_show_above_folders_list )
    $squirrelmail_plugin_hooks['quota_plugin_left']['check_quota'] = 'check_quota_left_do';
  else
    $squirrelmail_plugin_hooks['quota_plugin_left']['check_quota'] = 'check_quota_left_do';
  $squirrelmail_plugin_hooks['right_main_after_header']['check_quota'] = 'check_quota_MOTD_do';
}

function check_quota_version() {
    return '1.4';
}

include_once(SM_PATH . "plugins/check_quota/functions.php");

?>
