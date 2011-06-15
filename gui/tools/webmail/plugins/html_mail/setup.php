<?php

/**
  * SquirrelMail HTML Mail Plugin
  * Copyright (c) 2004-2005 Paul Lesneiwski <pdontthink@angrynerds.com>
  * Licensed under the GNU GPL. For full terms see the file COPYING.
  *
  * @package plugins
  * @subpackage html_mail
  *
  */


/**
  * Register this plugin with SquirrelMail
  *
  */
function squirrelmail_plugin_init_html_mail() 
{

   global $squirrelmail_plugin_hooks;

   $squirrelmail_plugin_hooks['generic_header']['html_mail']           = 'html_mail_header';
   $squirrelmail_plugin_hooks['compose_bottom']['html_mail']           = 'html_mail_compose_bottom';
   $squirrelmail_plugin_hooks['compose_button_row']['html_mail']       = 'html_mail_compose_button_row';
   $squirrelmail_plugin_hooks['compose_send']['html_mail']             = 'html_mail_alter_type';
   $squirrelmail_plugin_hooks['compose_before_textarea']['html_mail']  = 'html_mail_emoticons';
   $squirrelmail_plugin_hooks['compose_form']['html_mail']             = 'html_mail_disable_squirrelspell';


   // 1.4.x - 1.5.0:  options go on display options page
   // 1.5.1  and up:  options go on compose options page
   //
   $squirrelmail_plugin_hooks['options_display_inside']['html_mail']   = 'html_mail_display_options';
   $squirrelmail_plugin_hooks['options_compose_inside']['html_mail']   = 'html_mail_display_options';
   $squirrelmail_plugin_hooks['options_display_save']['html_mail']     = 'html_mail_display_save';
   $squirrelmail_plugin_hooks['options_compose_save']['html_mail']     = 'html_mail_display_save';

}


/** @ignore */
if (!defined('SM_PATH'))
   define('SM_PATH', '../');



/**
  * Returns version info about this plugin
  *
  */
function html_mail_version()
{
   return '2.3-1.4';
}


/**
  * Changes outgoing message format to include multipart
  * html and text parts if needed
  *
  */
function html_mail_alter_type(&$argv) 
{

   include_once(SM_PATH . 'plugins/html_mail/functions.php');
   return html_mail_alter_type_do($argv);

}


/**
  * Enables display of emoticons on compose screen if possible
  *
  */
function html_mail_emoticons() 
{

   include_once(SM_PATH . 'plugins/html_mail/functions.php');
   html_mail_emoticons_do();

}


/**
  * Turns off squirrelspell when the user is composing   
  * HTML-formatted email, since squirrelspell will
  * choke on the HTML
  *
  */
function html_mail_disable_squirrelspell() 
{

   include_once(SM_PATH . 'plugins/html_mail/functions.php');
   html_mail_disable_squirrelspell_do();

}


/**
  * Inserts extra JavaScript at bottom of compose page
  * that is needed by the enhanced editor
  *
  */
function html_mail_compose_bottom() 
{

   include_once(SM_PATH . 'plugins/html_mail/functions.php');
   html_mail_footer();

}


/**
  * Inserts controls on the compose page that let the user 
  * switch between HTML and text on the fly.
  *
  */
function html_mail_compose_button_row() 
{

   include_once(SM_PATH . 'plugins/html_mail/functions.php');
   html_mail_choose_type_on_the_fly();

}


/**
  * "Turns on" this plugin if the compose page is currently
  * being shown
  *
  */
function html_mail_header() 
{

   include_once(SM_PATH . 'plugins/html_mail/functions.php');
   html_mail_header_do();

}


/**
  * Show user configuration items
  *
  */
function html_mail_display_options($args) 
{

   include_once(SM_PATH . 'plugins/html_mail/functions.php');
   html_mail_display($args);

}


/**
  * Save user configuration items
  *
  */
function html_mail_display_save($args) 
{

   include_once(SM_PATH . 'plugins/html_mail/functions.php');
   html_mail_save($args);

}


?>
