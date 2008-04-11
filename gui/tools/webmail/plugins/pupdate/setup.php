<?php
/*******************************************************************************

    Author ......... Jimmy Conner
    Contact ........ jimmy@advcs.org
    Home Site ...... http://www.advcs.org/
    Program ........ Plugin Updates
    Version ........ 0.7
    Purpose ........ Check your currently installed plugins for updates

*******************************************************************************/

   if (!defined('SM_PATH'))
      define('SM_PATH','../');

   function squirrelmail_plugin_init_pupdate() {
      global $squirrelmail_plugin_hooks, $plugins;
      if (in_array('admin_options',$plugins))
         $squirrelmail_plugin_hooks['admin_optpage_register_block']['pupdate'] = 'pupdate_optpage';
      else
         $squirrelmail_plugin_hooks['optpage_register_block']['pupdate'] = 'pupdate_optpage';
   }

   function pupdate_optpage() {
      include_once(SM_PATH . 'plugins/pupdate/optblock.php');
   }

   function pupdate_version() {
      return "0.7";
   }

?>