<?php

/**
  * SquirrelMail Compatibility Plugin
  * Copyright (C) 2004-2005 Paul Lesneiwski <paul@openguild.net>
  * This program is licensed under GPL. See COPYING for details
  *
  */



   // change this to TRUE to disable plugin config test functionality
   // which will increase performance (minimally)
   //
   $disable_config_check = FALSE;


   // ---------------------------------------


   global $compatibility_sm_path, $compatibility_disable_config_check;
   $compatibility_disable_config_check = $disable_config_check;


   // we don't want to do any defining constants ourselves to stay
   // as non-intrusive as possible, so just our own variable
   //
   if (defined('SM_PATH'))
      $compatibility_sm_path = SM_PATH;
   else
      $compatibility_sm_path = '../';


   // Some uses of this plugin (such as vlogin) were somehow calling the
   // functions here before having included the functions in global.php,
   // resulting in fatal errors when called below.  Thus, the need for
   // the following includes
   //

   // we also need to include the validate file first
   // thing so we don't lose the ability to display themes,
   // but we cannot include this file unless we are being
   // called from a plugin request, thus this if statement
   //
/*
   if ( strpos(getcwd(), 'plugins') ) 
   {
      if (file_exists($compatibility_sm_path . 'include/validate.php'))
         include_once($compatibility_sm_path . 'include/validate.php');
      else if (file_exists($compatibility_sm_path . 'src/validate.php'))
         include_once($compatibility_sm_path . 'src/validate.php');
   }


   include_once($compatibility_sm_path . 'functions/strings.php');


   if (file_exists($compatibility_sm_path . 'functions/global.php'))
      include_once($compatibility_sm_path . 'functions/global.php');
   else if (file_exists($compatibility_sm_path . 'src/global.php'))
      include_once($compatibility_sm_path . 'src/global.php');
*/



   // legacy support for previous versions of compatibility plugin
   //
//see below
//   function compatibility_check_sm_version ($a = '0', $b = '0', $c = '0')
//   { return check_sm_version($a, $b, $c); }
   function compatibility_check_php_version ($a = '0', $b = '0', $c = '0')
   { return check_php_version($a, $b, $c); }
   function compatibility_sqsession_register ($var, $name)
   { sqsession_register ($var, $name); }
   function compatibility_sqsession_unregister ($name) 
   { sqsession_unregister($name); }
   function compatibility_sqsession_is_active()
   { sqsession_is_active(); }
   function compatibility_sqsession_is_registered ($name)
   { return sqsession_is_registered($name); }
   function compatibility_sqextractGlobalVar ($name)
   { global $$name; sqgetGlobalVar($name, $$name); }




   /**
     * Checks SquirrelMail version, returns TRUE if SquirrelMail
     * version is at least a.b.c.
     *
     * @param $a int Major version number
     * @param $b int Minor version number
     * @param $c int Revision number
     *
     * @return boolean TRUE if SquirrelMail version matches at
     *                 least a.b.c, FALSE otherwise.
     *
     */
   function compatibility_check_sm_version ($a = '0', $b = '0', $c = '0')
   {
      if (function_exists('check_sm_version'))
         return check_sm_version($a, $b, $c);

      global $version;
      list($aa, $bb, $cc) = preg_split('/\./', $version, 3);

      if(!is_numeric($cc))
         list($cc, $info) = explode(' ', $cc, 2);

      return ($aa > $a)
          || (($aa == $a) && ($bb > $b))
          || (($aa == $a) && ($bb == $b) && ($cc >= $c));
   }



   // include the right file for current version of SM
   //
   if (compatibility_check_sm_version(1, 5, 1))
   {
      // do nothing for now
   }
   else if (compatibility_check_sm_version(1, 5, 0))
   {
      include_once($compatibility_sm_path . 'plugins/compatibility/includes/1.5.0/global.php');
   }
   else if (compatibility_check_sm_version(1, 4, 6))
   {
      include_once($compatibility_sm_path . 'plugins/compatibility/includes/1.4.6/global.php');
   }
   else if (compatibility_check_sm_version(1, 4, 5))
   {
      include_once($compatibility_sm_path . 'plugins/compatibility/includes/1.4.5/global.php');
   }
   else if (compatibility_check_sm_version(1, 4, 4))
   {
      include_once($compatibility_sm_path . 'plugins/compatibility/includes/1.4.4/global.php');
   }
   else if (compatibility_check_sm_version(1, 4, 3))
   {
      include_once($compatibility_sm_path . 'plugins/compatibility/includes/1.4.3/global.php');
   }
   else if (compatibility_check_sm_version(1, 4, 2))
   {
      include_once($compatibility_sm_path . 'plugins/compatibility/includes/1.4.2/global.php');
   }
   else if (compatibility_check_sm_version(1, 4, 1))
   {
      include_once($compatibility_sm_path . 'plugins/compatibility/includes/1.4.1/global.php');
   }
   else if (compatibility_check_sm_version(1, 4, 0))
   {
      include_once($compatibility_sm_path . 'plugins/compatibility/includes/1.4.0/global.php');
   }
   else if (compatibility_check_sm_version(1, 2, 11))
   {
      include_once($compatibility_sm_path . 'plugins/compatibility/includes/1.2.11/global.php');
   }
   else if (compatibility_check_sm_version(1, 2, 10))
   {
      include_once($compatibility_sm_path . 'plugins/compatibility/includes/1.2.10/global.php');
   }
   else if (compatibility_check_sm_version(1, 2, 9))
   {
      include_once($compatibility_sm_path . 'plugins/compatibility/includes/1.2.9/global.php');
   }
   else if (compatibility_check_sm_version(1, 2, 8))
   {
      include_once($compatibility_sm_path . 'plugins/compatibility/includes/1.2.8/global.php');
   }
   else if (compatibility_check_sm_version(1, 2, 7))
   {
      include_once($compatibility_sm_path . 'plugins/compatibility/includes/1.2.7/global.php');
   }



/**
  * Load configuration file
  *
  * Convenience function for plugins that loads a configuration
  * file (or files).  If the file(s) is(are) not found, an error
  * is displayed and execution stops (function won't return).  
  * If multiple configuration files are given, ALL of them are 
  * included, if they exist, in the order given.  Only one of 
  * them needs to be found to avert triggering an error.
  *
  * Non-functional on login_before hook.
  *
  * @param string $plugin_name The name of the plugin as
  * it is known to SquirrelMail, that is, it is the name
  * of the plugin directory
  *
  * @param array $config_files An array of files that will
  * be included IN THE ORDER that they are given in the
  * array.  Files should be specified relative to the calling
  * plugin's directory, such as:
  * array('config.php') or array('data/config.php', 'data/admins.php')
  * It is also possible to give a full/direct path to a 
  * configuration file by placing a forward slash at the 
  * beginning of the file:
  * array('/var/lib/squirrelmail/config/myplugin.conf')
  *
  */
function load_config($plugin_name, $config_files)
{

   global $compatibility_sm_path;


   // if only one config file given as string, push 
   // into an array just to be nice
   //
   if (!is_array($config_files)) $config_files = array($config_files);
      

   // loop through files, attempting to include them
   // 
   $file_count = 1;
   foreach ($config_files as $file)
   {

      if (strpos($file, '/') === 0)
         $plugin_path = $file;
      else 
         $plugin_path = $compatibility_sm_path . 'plugins/' . $plugin_name . '/' . $file;

      // store inclusion results to be checked below
      //
      ${'config' . $file_count} = @include_once($plugin_path);

      $file_count++;
      
   }


   // now check to see if we got at least one successful inclusion
   //
   $success = FALSE;
   for ($i = 1; $i < $file_count; $i++)
      if (${'config' . $i})
      {
         $success = TRUE;
         break;
      }


   // error...
   //
   if (!$success)
   {
      global $color;
      bindtextdomain ('compatibility', SM_PATH . 'locale');
      textdomain ('compatibility');
      $error_msg = _("Administrative error:") . '<br />' 
                 . sprintf(_("The plugin %s has not been set up correctly."),
                           '&quot;<b>' . $plugin_name . '</b>&quot;') 
                 . '<br />' 
                 . _("Please read the README or INSTALL files that came with the plugin.");
      bindtextdomain ('squirrelmail', SM_PATH . 'locale');
      textdomain ('squirrelmail');
      plain_error_message($error_msg, $color);
      exit;
   }

}



/**
  * Validate Plugin Setup Utility
  *
  * Checks a plugin to see if the user has installed it 
  * correctly by checking for the existence of the given
  * files (all relative from the plugin's directory)
  *
  * @param string $pluginName The name of the plugin as
  * it is known to SquirrelMail, that is, it is the name
  * of the plugin directory.
  *
  * @param array $configFiles An array of any files that the
  * user should have set up for this plugin, for example:
  * array('config.php') or array('data/config.php', 'data/admins.php')
  * where all files will be referenced from the plugin's
  * main directory.
  * It is also possible to give a full/direct path to a 
  * configuration file by placing a forward slash at the 
  * beginning of the file:
  * array('/var/lib/squirrelmail/config/myplugin.conf')
  *
  */
function check_plugin_setup($pluginName, $configFiles)
{

   global $compatibility_disable_config_check;
   if ($compatibility_disable_config_check) return;


   global $compatibility_sm_path;


   // check one at a time...
   //
   foreach ($configFiles as $configFile)
   {

      if (strpos($configFile, '/') === 0)
         $plugin_path = $configFile;
      else 
         $plugin_path = $compatibility_sm_path . 'plugins/' . $pluginName . '/' . $configFile;

      if (!file_exists($plugin_path))
      {

         global $color;
         bindtextdomain ('compatibility', SM_PATH . 'locale');
         textdomain ('compatibility');
         $error_msg = _("Administrative error:") . '<br />' 
                    . sprintf(_("The plugin %s has not been set up correctly."),
                              '&quot;<b>' . $pluginName . '</b>&quot;') 
                    . '<br />' 
                    . sprintf(_("The file %s is missing."),
                              '&quot;<b>' . $configFile . '</b>&quot;') 
                    . '<br />'
                    . _("Please read the README or INSTALL files that came with the plugin.");
         bindtextdomain ('squirrelmail', SM_PATH . 'locale');
         textdomain ('squirrelmail');
         plain_error_message($error_msg, $color);
         exit;

      }

   }

}



?>
