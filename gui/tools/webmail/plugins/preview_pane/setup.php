<?php

/**
  * SquirrelMail Preview Pane Plugin
  * Copyright (C) 2004 Paul Lesneiwski <pdontthink@angrynerds.com>
  * This program is licensed under GPL. See COPYING for details
  *
  */


function squirrelmail_plugin_init_preview_pane() 
{

   global $squirrelmail_plugin_hooks;


   $squirrelmail_plugin_hooks['optpage_loadhook_display']['preview_pane'] 
      = 'preview_pane_show_options';
   $squirrelmail_plugin_hooks['webmail_bottom']['preview_pane']
      = 'preview_pane_build_frames';
   $squirrelmail_plugin_hooks['subject_link']['preview_pane'] 
      = 'preview_pane_change_message_target';
   $squirrelmail_plugin_hooks['read_body_menu_top']['preview_pane'] 
      = 'preview_pane_change_message_display';
   $squirrelmail_plugin_hooks['right_main_after_header']['preview_pane'] 
      = 'preview_pane_check_frames';
   $squirrelmail_plugin_hooks['compose_send']['preview_pane']
      = 'preview_pane_compose_send';
   $squirrelmail_plugin_hooks['mailbox_index_after']['preview_pane']
      = 'preview_pane_clear_pp_button';

}


if (!defined('SM_PATH'))
   define('SM_PATH', '../');


function preview_pane_version() 
{

  return '1.2';

}

function preview_pane_show_options() 
{

  include_once(SM_PATH . 'plugins/preview_pane/functions.php');
  preview_pane_show_options_do();

}


function preview_pane_compose_send() 
{

  include_once(SM_PATH . 'plugins/preview_pane/functions.php');
  preview_pane_compose_send_do();

}


function preview_pane_clear_pp_button() 
{

  include_once(SM_PATH . 'plugins/preview_pane/functions.php');
  preview_pane_clear_pp_button_do();

}


function preview_pane_check_frames() 
{

  include_once(SM_PATH . 'plugins/preview_pane/functions.php');
  preview_pane_check_frames_do();

}


function preview_pane_build_frames($msg) 
{

  include_once(SM_PATH . 'plugins/preview_pane/functions.php');
  return preview_pane_build_frames_do($msg);

}


function preview_pane_change_message_target($args) 
{

  include_once(SM_PATH . 'plugins/preview_pane/functions.php');
  return preview_pane_change_message_target_do($args);

}


function preview_pane_change_message_display($source) 
{

  include_once(SM_PATH . 'plugins/preview_pane/functions.php');
  return preview_pane_change_message_display_do($source);

}


?>
