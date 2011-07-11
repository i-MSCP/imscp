<?php

function squirrelmail_plugin_init_disk_quota() {
  global $squirrelmail_plugin_hooks;
  $squirrelmail_plugin_hooks['left_main_after']['disk_quota'] = 'disk_quota_graph_and_details';
}

function disk_quota_version() {
    return '3.1.1';
}

if ( !defined('SM_PATH') ) {
    define('SM_PATH', '../../');
}

function disk_quota_graph_and_details() {

  include_once(SM_PATH . 'plugins/disk_quota/functions.php');
  disk_quota_check();

}

?>
