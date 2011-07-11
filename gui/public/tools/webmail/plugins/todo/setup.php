<?php

function squirrelmail_plugin_init_todo() {
	global $squirrelmail_plugin_hooks;
	$squirrelmail_plugin_hooks['menuline']['todo'] = 'todo_menuline';
	$squirrelmail_plugin_hooks['left_main_after']['todo'] = 'todo_show_reminders';
	$squirrelmail_plugin_hooks['right_main_after_header']['todo'] = 'todo_show_has_reminders';
	$squirrelmail_plugin_hooks['loading_prefs']['todo'] = 'todo_load_prefs';
	$squirrelmail_plugin_hooks['options_display_inside']['todo'] = 'todo_option_display';
	$squirrelmail_plugin_hooks['options_display_save']['todo'] = 'todo_option_save';
	$squirrelmail_plugin_hooks['logout']['todo'] = 'todo_reset_first_login';
}

function todo_version() {
	return 0.5;
}

function todo_menuline() {
   //Add 'To Do' link to upper menu
	displayInternalLink('plugins/todo/todo.php',_("To Do"),'right');
	echo "&nbsp;&nbsp\n";
}

function todo_show_reminders() {
	if (defined('SM_PATH'))
	  include_once(SM_PATH . 'plugins/todo/setup_functions.php');
	else
	  include_once('../plugins/todo/setup_functions.php');
	todo_show_reminders_do();
}

function todo_show_has_reminders() {
	if (defined('SM_PATH'))
	  include_once(SM_PATH . 'plugins/todo/setup_functions.php');
	else
	  include_once('../plugins/todo/setup_functions.php');
	todo_show_has_reminders_do();
}

function todo_load_prefs() {
	if (defined('SM_PATH'))
	  include_once(SM_PATH . 'plugins/todo/setup_functions.php');
	else
	  include_once('../plugins/todo/setup_functions.php');
	todo_load_prefs_do();
}


function todo_reset_first_login() {
	global $username, $data_dir, $my_has_todo;
    setPref($data_dir, $username, "todo_first_login", "1");
	$hashed_dir = getHashedDir($username, $data_dir);
	if(file_exists("$hashed_dir/$username.todo")) {
		if(filesize("$hashed_dir/$username.todo") == 0) {
			@unlink("$hashed_dir/$username.todo");
		}
	}
}

function todo_option_display()
{
	if (defined('SM_PATH'))
	  include_once(SM_PATH . 'plugins/todo/setup_functions.php');
	else
	  include_once('../plugins/todo/setup_functions.php');
	todo_option_display_do();
}

function todo_option_save()
{
	if (defined('SM_PATH'))
	  include_once(SM_PATH . 'plugins/todo/setup_functions.php');
	else
	  include_once('../plugins/todo/setup_functions.php');
	todo_option_save_do();
}

?>
