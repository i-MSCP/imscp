<?php

   /*
    *  Login:Auto Plugin
    *  By Jay Guerette <JayGuerette@pobox.com>
    *  (c) 2001 (GNU GPL - see ../../COPYING)
    *
    *  If you need help with this, or see improvements that can be made, please
    *  email the SquirrelMail Plugins mailing list or try contacting me at
    *  the address above (note as of May 2003, Jay does not seem to be maintaining
    *  this plugin).  I definately welcome suggestions and comments.  This plugin, 
    *  as is the case with all SquirrelMail plugins, is not directly supported 
    *  by the developers.  
    *
    *  View the INSTALL document for information on installing this.  Also view
    *  the README document and plugins/README.plugins for more information.
    *
    */

   function squirrelmail_plugin_init_login_auto() {

      global $squirrelmail_plugin_hooks;
      
      $squirrelmail_plugin_hooks['login_cookie']['login_auto'] = 'login_auto_get_user';
      $squirrelmail_plugin_hooks['login_form']['login_auto'] = 'login_auto_set_login';
      $squirrelmail_plugin_hooks['login_before']['login_auto'] = 'login_auto_get_pass';
      $squirrelmail_plugin_hooks['login_verified']['login_auto'] = 'login_auto_set_cookies';
      $squirrelmail_plugin_hooks['logout']['login_auto'] = 'login_auto_clear_cookies';
   }

   
   function login_auto_get_user() {

      if (defined('SM_PATH'))
         include_once(SM_PATH . 'plugins/login_auto/functions.php');
      else
         include_once('../plugins/login_auto/functions.php');

      login_auto_get_user_do();

   }


   function login_auto_set_login() {

      if (defined('SM_PATH'))
         include_once(SM_PATH . 'plugins/login_auto/functions.php');
      else
         include_once('../plugins/login_auto/functions.php');

      login_auto_set_login_do();

   }


   function login_auto_get_pass() {

      if (defined('SM_PATH'))
         include_once(SM_PATH . 'plugins/login_auto/functions.php');
      else
         include_once('../plugins/login_auto/functions.php');

      login_auto_get_pass_do();

   }


   function login_auto_set_cookies() {

      if (defined('SM_PATH'))
         include_once(SM_PATH . 'plugins/login_auto/functions.php');
      else
         include_once('../plugins/login_auto/functions.php');

      login_auto_set_cookies_do();

   }


   function login_auto_clear_cookies() {

      if (defined('SM_PATH'))
         include_once(SM_PATH . 'plugins/login_auto/functions.php');
      else
         include_once('../plugins/login_auto/functions.php');

      login_auto_clear_cookies_do();

   }


   function login_auto_version() {

      return '1.1';

   }


?>
