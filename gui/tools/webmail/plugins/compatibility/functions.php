<?php

/**
  * SquirrelMail Compatibility Plugin
  * Copyright (c) 2004-2008 Paul Lesniewski <paul@squirrelmail.org>
  * Licensed under the GNU GPL. For full terms see the file COPYING.
  *
  * SquirrelMail developers, see below under "SQUIRRELMAIL DEVELOPER 
  * NOTES" for information about how the include files are maintained
  *
  * @package plugins
  * @subpackage compatibility
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



   // now include everything that current SM version is missing
   //
   load_legacy_support();




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

      if (defined('SM_VERSION'))
         $version = SM_VERSION;
      else
         global $version;

      list($aa, $bb, $cc) = preg_split('/\./', $version, 3);
      $cc = intval($cc);

      if(!is_numeric($cc))
         list($cc, $info) = explode(' ', $cc, 2);

      return ($aa > $a)
          || (($aa == $a) && ($bb > $b))
          || (($aa == $a) && ($bb == $b) && ($cc >= $c));
   }



   /**
     * Includes needed files with updated API for legacy
     * SquirrelMail installations
     *
     */
   function load_legacy_support()
   {

      global $compatibility_sm_path;

      // SQUIRRELMAIL DEVELOPER NOTES
      //
      // The array below should be updated with every release of 
      // SquirrelMail, along with adding needed directories in the 
      // includes for this plugin.
      //
      // Code that was added as of a certain version should be put 
      // in the include file in the directory corresponding to that 
      // same version number in order to make it available to older
      // versions.  When including files here, that file will be
      // included for all versions prior.
      //
      // If some code was added to the newest codebase for two different
      // release series, for example, a function called "sq_new_function()"
      // that was added in both 1.4.10 as well as 1.5.2, please add 
      // the function to only the higher version (1.5.2 in this case),
      // and add additional logic to the way it is included:
      //
      //    if ((!compatibility_check_sm_version(1, 4, 10)
      //      || (compatibility_check_sm_version(1, 5, 0) 
      //     && !compatibility_check_sm_version(1, 5, 2)))
      //     && !function_exists('sq_new_function'))
      //    {
      //    function sq_new_function()
      //    {
      //       echo "HELLO WORLD";
      //    }
      //    }
      //
      // NOTE: In-between version includes should simply be put in the
      //       lowest version include file for the new release series
      //       (e.g., anything in the 1.5.0 include file will be loaded
      //       for all 1.4.x versions, and of course, not for 1.5.0).
      //       The following in-between include file system is thus not 
      //       needed, but leaving code and documentation about how it 
      //       works just for edification:
      //
      // In-between include directories should be named as such:
      // "1.4.x-to-1.5.0" where the latter version string must 
      // correspond to a real SquirrelMail version and the versions 
      // must be separated by the string "-to-"
      //
      // Order in this array is significant, please keep newest releases
      // at top of this list, ordered downward from there...
      //
      // These have to be hard-coded since there is no way to know if this
      // plugin is being used in series 1.2.x, 1.3.x, 1.4.x, 1.5.x, etc.
      //
      $compatibility_versions = array(
         '1.5.2', '1.5.1', '1.5.0',
         // in-between includes not needed:
         //'1.4.x-to-1.5.0',
         '1.4.16', '1.4.15', '1.4.14',
         '1.4.13', '1.4.12', '1.4.11', '1.4.10', '1.4.9', '1.4.8', '1.4.7', 
         '1.4.6', '1.4.5', '1.4.4', '1.4.3', '1.4.2', '1.4.1', '1.4.0',
         // skipping 1.3.x, not supported for now
         // in-between includes not needed:
         //'1.2.x-to-1.4.0',
         '1.2.11', '1.2.10', '1.2.9', '1.2.8', '1.2.7',
         // if you are running anything older than this, I feel really, really sorry for you
                                     );


      // loop through all versions in our list, including files
      // for all versions newer than the one being run here and now
      //
      $last_csv_version_string = '';
      $last_version_string = '';
      for ($count = 0; !empty($compatibility_versions[$count]); $count++)
      {

         $version_string = $compatibility_versions[$count];


/* ----- see above; in-between includes not needed...
         // in-between version files: set version string to the higher
         // version so that all versions in the release series below
         // it will see it
         //
         if (($pos = strpos($version_string, '-to-')) !== FALSE)
            $csv_version_string = str_replace('.', ', ', substr($version_string, $pos + 4));


         // normal conversion to CSV values
         //
         else
----- */
            // note that we could split $version_string by '.' instead and not use eval below
            $csv_version_string = str_replace('.', ', ', $version_string);


         if ($count == 0)
         {
            if (eval('if (compatibility_check_sm_version(' . $csv_version_string . ')) return TRUE; else return FALSE;'))
               return;
            $last_csv_version_string = $csv_version_string;
            $last_version_string = $version_string;
            continue;
         }


         if (eval('if (file_exists($compatibility_sm_path . \'plugins/compatibility/includes/'
              . $last_version_string . '/global.php\')) include_once($compatibility_sm_path . \'plugins/compatibility/includes/'
              . $last_version_string . '/global.php\'); if (compatibility_check_sm_version('
              . $csv_version_string . ')) return TRUE;'))
            return;

         $last_csv_version_string = $csv_version_string;
         $last_version_string = $version_string;

      }

   }



/**
  * Allows a plugin to push itself (or another plugin) to the top 
  * or bottom of the plugin order for a certain hook.  It also
  * allows for positioning immediately before or after another 
  * plugin.
  *
  * NOTE that this function will only be useful when called from
  * certain points in the code context, such as from a very early
  * hook like 'config_override' (which has been changed to
  * 'prefs_backend'), and may not work reliably for reordering
  * hooks that are already in execution.
  *
  * @param string  $plugin_name     The name of the plugin to reposition.
  * @param string  $hook_name       The name of the hook wherein the 
  *                                 repositioning happens.
  * @param boolean $before          If the repositioning should be at the
  *                                 top of the plugin list (or before the
  *                                 $relative_plugin plugin).  When FALSE, 
  *                                 repositioning goes to the bottom of 
  *                                 the plugin list or after $relative_plugin
  *                                 (OPTIONAL; default is TRUE).
  * @param string  $relative_plugin The name of a plugin that the repositioning
  *                                 should be relative to.  If not given,
  *                                 the target plugin is just moved to the
  *                                 extreme front or back of the whole plugin
  *                                 list (OPTIONAL; default not used).
  *
  * @return boolean TRUE when repositioning succeeds, FALSE otherwise
  *                 (for instance, it might fail if the target plugin 
  *                 is not already registered on the target hook, or
  *                 $relative_plugin is not also found on the target hook).
  *
  */
function reposition_plugin_on_hook($plugin_name, $hook_name, $before=TRUE,
                                   $relative_plugin='')
{

   global $squirrelmail_plugin_hooks, $plugins;


   // make sure plugin is already registered on the target hook
   //
   if (is_array($squirrelmail_plugin_hooks[$hook_name]) 
    && !empty($squirrelmail_plugin_hooks[$hook_name][$plugin_name]))
   {

      // move relative to another plugin?
      //
      $relative_plugin_function = FALSE;
      if (!empty($relative_plugin))
      {
         if (empty($squirrelmail_plugin_hooks[$hook_name][$relative_plugin]))
            return FALSE;

         $relative_plugin_function = $squirrelmail_plugin_hooks[$hook_name][$relative_plugin];
      }


      // grab target plugin's function callback for this hook
      //
      $plugin_function = $squirrelmail_plugin_hooks[$hook_name][$plugin_name];


      // reordering an associative array can only be done
      // by rebuilding by hand as far as I know
      //
      $new_hook_array = array();
      if ($before && !$relative_plugin_function) 
         $new_hook_array[$plugin_name] = $plugin_function;
      foreach ($squirrelmail_plugin_hooks[$hook_name] as $plugin => $function)
      {

         if ($plugin == $plugin_name)
            continue;

         // move relative to another plugin?
         //
         if ($plugin == $relative_plugin && !empty($relative_plugin))
         {
            if ($before)
            {
               $new_hook_array[$plugin_name] = $plugin_function;
               $new_hook_array[$relative_plugin] = $relative_plugin_function;
            }
            else
            {
               $new_hook_array[$relative_plugin] = $relative_plugin_function;
               $new_hook_array[$plugin_name] = $plugin_function;
            }
            continue;
         }

         $new_hook_array[$plugin] = $function;

      }
      if (!$before && !$relative_plugin_function) 
         $new_hook_array[$plugin_name] = $plugin_function;



      // now replace the plugins for the target hook
      //
      $squirrelmail_plugin_hooks[$hook_name] = $new_hook_array;

      return TRUE;

   }


   // plugin not found on target hook
   //
   return FALSE;

}



/**
  * Dynamically enables a plugin to the SquirrelMail environment.
  *
  * @param string  $plugin_name  The name of the plugin to add.
  * @param mixed   $args         The current plugin function argument,
  *                              which must be exactly as received by
  *                              the plugin function that is calling
  *                              this code (OPTIONAL; default empty).
  * @param boolean $dont_execute When adding plugins that are registered
  *                              on the same hook that is currently being
  *                              executed, the registered function for
  *                              the new plugins will be manually run,
  *                              however, setting this flag to TRUE will
  *                              prevent that from happening (plugins
  *                              will be registered, but never executed)
  *                              (OPTIONAL; default is FALSE).
  *
  */
function add_plugin($plugin_name, $args='', $dont_execute=FALSE)
{

   global $squirrelmail_plugin_hooks, $plugins;

   // changing the hook function array whilst in the
   // middle of iterating thru it for the same hook
   // doesn't always work, so we'll see if the hook
   // currently being executed has had its function
   // list changed; if so, we will execute the added
   // hook functions ourselves
   //
   $hook_name = get_current_hook_name($args);


   // used below for determining if any plugins
   // were added to currently running hook
   //
   if (!empty($hook_name) 
    && !empty($squirrelmail_plugin_hooks[$hook_name])
    && is_array($squirrelmail_plugin_hooks[$hook_name]))
      $original_hook_functions = $squirrelmail_plugin_hooks[$hook_name];
   else
      $original_hook_functions = array();


   // add plugin to global plugin array
   //
   $plugins[] = $plugin_name;


   // enable plugin -- emulate code from use_plugin() function
   // in SquirrelMail core, because in 1.5.2, it no longer
   // called "squirrelmail_plugin_init_<plugin_name>", which 
   // NEEDS to be called here.
   //
   if (file_exists(SM_PATH . "plugins/$plugin_name/setup.php")) 
   {

      include_once(SM_PATH . "plugins/$plugin_name/setup.php");

      $function = "squirrelmail_plugin_init_$plugin_name";
      if (function_exists($function))
         $function();

   }


   // now get any new plugins for the current hook
   // and run their hooks
   //
   if (!$dont_execute && !empty($hook_name))
   {

      if (!empty($squirrelmail_plugin_hooks[$hook_name])
       && is_array($squirrelmail_plugin_hooks[$hook_name]))
         $new_hook_functions = array_diff($squirrelmail_plugin_hooks[$hook_name],
                                          $original_hook_functions);
      else
         $new_hook_functions = array();

      foreach ($new_hook_functions as $function)
         if (function_exists($function))
//FIXME: is $args always how plugins are called, even in 1.4.x?
            $function($args);

   }

}



/**
  * Dynamically disables a plugin from the SquirrelMail environment.
  *
  * @param string $plugin_name The name of the plugin to remove.
  *
  */
function remove_plugin($plugin_name)
{

   global $squirrelmail_plugin_hooks, $plugins;

   $plugin_key = array_search($plugin_name, $plugins);
   if (!is_null($plugin_key) && $plugin_key !== FALSE)
   {
      unset($plugins[$plugin_key]);
      if (is_array($squirrelmail_plugin_hooks))
         foreach (array_keys($squirrelmail_plugin_hooks) as $hookName)
         {
            unset($squirrelmail_plugin_hooks[$hookName][$plugin_name]);
         }
   }

}



/**
  * Determines what plugin hook is currently executing,
  * if any, in a SquirrelMail version-independent fashion.
  *
  * @param mixed $args The current plugin function argument,
  *                    which must be exactly as received by
  *                    the plugin function that is calling
  *                    this code.
  *
  * @return string The name of the currently executing plugin
  *                hook, or an empty string if either no hook
  *                is running or the hook name could not be
  *                determined.
  *
  */
function get_current_hook_name($args='')
{

   if (check_sm_version(1, 5, 1))
   {
      global $currentHookName;
      if (!empty($currentHookName))
         return $currentHookName;
   }


//TODO: should we ALWAYS backtrace instead of assuming that $args[0] is the hook name?
   if (!empty($args[0]) && is_string($args[0]))
      return $args[0];
   else
   {

      // plugin args not given or didn't have a hook
      // name in them, so try backtracing instead
      //
      $backtrace = debug_backtrace();
      foreach ($backtrace as $trace)
         if ($trace['function'] == 'do_hook'
          || $trace['function'] == 'do_hook_function'
          || $trace['function'] == 'concat_hook_function'
          || $trace['function'] == 'boolean_hook_function')
            return $trace['args'][0];

      // nothing found at all
      //
      return '';

   }

}



/**
  * Load configuration file
  *
  * Convenience function for plugins that loads a configuration
  * file (or files).  If the file(s) is(are) not found, an error
  * is displayed and execution stops (function won't return).  
  * If multiple configuration files are given, ALL of them are 
  * included (unless $load_only_one is TRUE), if they exist, in
  * the order given.  Only one of them needs to be found to avert
  * triggering an error.
  *
  * Note that configuration files are loaded in the order given,
  * so the caller should place the file that should have the
  * final overrides as the LAST in the given list, unless using
  * $load_only_one, in which case the most important configuration
  * file should probably come first.
  *
  * Non-functional on login_before hook.
TODO - re-verify that the above is true
  *
  * @param string $plugin_name    The name of the plugin as
  *                               it is known to SquirrelMail, 
  *                               that is, it is the name
  *                               of the plugin directory
  * @param mixed $config_files    An array of files that will
  *                               be included IN THE ORDER that 
  *                               they are given in the
  *                               array.  Can also be given as
  *                               a string if only one config
  *                               file is being loaded.  Files
  *                               should be specified relative
  *                               to the calling plugin's
  *                               directory, such as:
  *                                 'config.php'
  *                               or:
  *                                 '../../config/my_plugin_config.php'
  *                               or:
  *                                 array('data/config.php', 'data/admins.php')
  *                               It is also possible to give a
  *                               full/direct path to a
  *                               configuration file by placing
  *                               a forward slash at the
  *                               beginning of the file:
  *                               array('/var/lib/squirrelmail/config/myplugin.conf')
  * @param boolean $return_errors When TRUE, any errors encountered
  *                               will cause this function to return
  *                               FALSE; otherwise, errors are
  *                               handled herein by showing an error 
  *                               to the user and exiting (OPTIONAL; 
  *                               default is FALSE).
  * @param boolean $load_only_one When TRUE, this function will stop
  *                               after it has successfully loaded
  *                               one configuration file, starting
  *                               with the first one given for
  *                               $config_files.  When FALSE, all
  *                               configuration files will be loaded
  *                               such that the last one can override
  *                               all others ("cascading") (OPTIONAL;
  *                               default is FALSE).
  *
  * @return mixed If no errors are found, TRUE is returned; if an error
  *               was found and $return_errors is TRUE, FALSE is returned.  
  *               If $return_errors is FALSE and an error is found, this 
  *               function will never return.
  *
  */
function load_config($plugin_name, $config_files,
                     $return_errors=FALSE, $load_only_one=FALSE)
{

   global $compatibility_sm_path;


   // if only one config file given as string
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

      // if we only need one configuration file, stop
      // here if we successfully loaded this config file
      //
      if ($load_only_one && ${'config' . $file_count})
         return TRUE;

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

      if ($return_errors) return FALSE;

// TODO: when used in configtest hook (and others??), this function (sq_change_text_domain()) is not known yet... but usually $return_errors should be turned on for that hook
      sq_change_text_domain('compatibility');
      $error_msg = _("Administrative error:") . '<br />' 
                 . sprintf(_("The plugin %s has not been set up correctly."),
                           '&quot;<strong>' . $plugin_name . '</strong>&quot;') 
                 . '<br />' 
                 . _("Please read the README or INSTALL files that came with the plugin.");
      sq_change_text_domain('squirrelmail');
      include_once($compatibility_sm_path . 'functions/display_messages.php');
      global $color;
      plain_error_message($error_msg, $color);
      exit;
   }

   return TRUE;

}



/**
  * Validate Plugin Setup Utility
  *
  * Checks a plugin to see if the user has installed it 
  * correctly by checking for the existence of the given
  * files (all relative from the plugin's directory)
  *
  * @param string $pluginName The name of the plugin as
  *                           it is known to SquirrelMail, 
  *                           that is, it is the name
  *                           of the plugin directory.
  * @param array $configFiles An array of any files that the
  *                           user should have set up for 
  *                           this plugin, for example:
  *                             array('config.php') 
  *                           or: 
  *                             array('data/config.php', 'data/admins.php')
  *                           where all files will be 
  *                           referenced from the plugin's
  *                           main directory.
  *                           It is also possible to give a 
  *                           full/direct path to a 
  *                           configuration file by placing 
  *                           a forward slash at the 
  *                           beginning of the file:
  *                             array('/var/lib/squirrelmail/config/myplugin.conf')
  * @param boolean $return_errors When true, any errors encountered
  *                               will cause this function to return
  *                               either FALSE or a string describing
  *                               the error; otherwise, errors are
  *                               handled herein by showing an error 
  *                               to the user and exiting (OPTIONAL; 
  *                               default is FALSE).
  *
  * @return mixed If no errors are found, TRUE is returned; if an error
  *               was found and $return_errors is TRUE, either FALSE or 
  *               a string describing the error is returned.  If $return_errors
  *               is FALSE and an error is found, this function will never
  *               return.
  *
  */
function check_plugin_setup($pluginName, $configFiles, $return_errors=FALSE)
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

         //if ($return_errors) return FALSE;

         sq_change_text_domain('compatibility');

         if ($return_errors) 
         {
            $error_msg = sprintf(_("The file %s is missing from plugin %s."), 
                                 '&quot;<strong>' . $configFile . '</strong>&quot;',
                                 '&quot;<strong>' . $pluginName . '</strong>&quot;');
            sq_change_text_domain('squirrelmail');
            return $error_msg;
         }

         $error_msg = _("Administrative error:") . '<br />' 
                    . sprintf(_("The plugin %s has not been set up correctly."),
                              '&quot;<strong>' . $pluginName . '</strong>&quot;') 
                    . '<br />' 
                    . sprintf(_("The file %s is missing."),
                              '&quot;<strong>' . $configFile . '</strong>&quot;') 
                    . '<br />'
                    . _("Please read the README or INSTALL files that came with the plugin.");
         sq_change_text_domain('squirrelmail');
         include_once($compatibility_sm_path . 'functions/display_messages.php');
         global $color;
         plain_error_message($error_msg, $color);
         exit;

      }

   }

   return TRUE;

}



/**
  * Test if a given file contains a given string.
  *
  * This can be used, for example, by plugins during the configtest hook
  * that want to verify if a certain patch to the SquirrelMail core has
  * been applied or not.
  *
  * The string is searched for using a regular expression to allow for
  * more complex searches than direct string comparision.  The $string
  * parameter may contain regular expression syntax if desired.
  *
  * @param string  $file   The file to search (usually a full path).
  * @param string  $string The string to search for (can include regular
  *                        expression syntax if desired - any special
  *                        regular expression meta characters need to
  *                        be escaped unless they are being used as such).
  * @param boolean $quiet  When TRUE and $file cannot be found or opened,
  *                        this function returns FALSE, otherwise it 
  *                        will complain and exit (never return) (OPTIONAL;
  *                        default is FALSE).
  *
  * @return boolean TRUE when $string was found or FALSE when it was not.
  *                 When $quiet is TRUE, FALSE is also returned if the
  *                 target file could not be located, opened or read.
  *
  */
function check_file_contents($file, $string, $quiet=FALSE)
{

   if (check_php_version(4, 3, 0))
   {
      $contents = file_get_contents($file);
      if (!$contents)
         if ($quiet) return FALSE;
         else
         {
            echo 'FATAL ERROR: ' . $file . ' cannot be found or opened!';
            exit;
         }
   }
   else
   {
      $temp_contents = file($file);
      if (!$temp_contents || !is_array($temp_contents))
         if ($quiet) return FALSE;
         else
         {
            echo 'FATAL ERROR: ' . $file . ' cannot be found or opened!';
            exit;
         }
      $contents = '';
      foreach ($temp_contents as $line)
         $contents .= $line;
   }


   return preg_match('/' . $string . '/', $contents);

}



/**
  * Returns the difference in times.  Some small
  * amount of precision might be lost in the +
  * operations, but nothing serious.  
  *
  * This is only a convenience function for plugin
  * authors fine tuning performance and may not
  * work on some operating systems.
  *
  * @param string $start The microtime() results for
  *                      the start point.
  * @param string $end   The microtime() results for
  *                      the end point (OPTIONAL;
  *                      if empty defaults to the
  *                      time NOW).
  *
  * @return float The difference between start and
  *               end points.
  *
  */
function sm_microtime_diff($start, $end=0)
{

   if (empty($end)) $end = microtime();

   return (substr($end, 11) - substr($start, 11))
        + (substr($end, 0, 9) - substr($start, 0, 9));

}



