<?php

function squirrelmail_plugin_init_show_user_and_ip() {
   global $squirrelmail_plugin_hooks;
   $squirrelmail_plugin_hooks['welcome']['show_user_and_ip'] = 'sui_show_user_and_ip';
   $squirrelmail_plugin_hooks['right_main_after_header']['show_user_and_ip'] = 'sui_show_last_date_and_ip';
   $squirrelmail_plugin_hooks['loading_prefs']['show_user_and_ip'] = 'sui_last_date_and_ip_load';
   $squirrelmail_plugin_hooks['right_main_bottom']['show_user_and_ip'] = 'sui_last_date_and_ip_save';
   $squirrelmail_plugin_hooks['options_display_inside']['show_user_and_ip'] = 'sui_left_option';
   $squirrelmail_plugin_hooks['options_display_save']['show_user_and_ip'] = 'sui_left_option_save';
}

function show_user_and_ip_version() {
	return '3.3';
}

if (!defined('SM_PATH'))
	define('SM_PATH','../../');

include_once(SM_PATH . 'plugins/show_user_and_ip/functions.php');

function sui_show_user_and_ip()
{
  sui_show_ui();
}

function sui_last_date_and_ip_load()
{
  sui_last_load();
}

function sui_show_last_date_and_ip()
{
  sui_show_last();
}

function sui_left_option()
{
  sui_left_opt();
}

function sui_left_option_save()
{
  sui_left_opt_save();
}

function sui_last_date_and_ip_save()
{
  sui_last_save();
}

?>
