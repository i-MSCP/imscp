<?php
   /*
    *  Small Calendar
    *  By Nick Rosenberg <nick@dolphins-angels.com>
    *  This is an implementation of the calendar script
    *  at http://www.cascade.org.uk/software/php/calendar
    *  by David Wilkinson with a few mods to make it work
    *  with Squirrelmail.
    *
    *  NOTE: This script requires the calendar module to work
    *  properly as the days link directly to it.
    *
    */
   function squirrelmail_plugin_init_smallcal()
   {

      global $squirrelmail_plugin_hooks;

      $squirrelmail_plugin_hooks['options_display_inside']['smallcal'] = 'smallcal_options';
      $squirrelmail_plugin_hooks['options_display_save']['smallcal'] = 'smallcal_save';
      $squirrelmail_plugin_hooks['loading_prefs']['smallcal'] = 'smallcal_pref';
      $squirrelmail_plugin_hooks["smallcal_plugin"]["smallcal"] = "addsmallcal_left";

   }


   function smallcal_save() 
   {

      if (defined('SM_PATH'))
         include_once(SM_PATH . 'plugins/smallcal/functions.php');
      else
         include_once('../plugins/smallcal/functions.php');

      smallcal_save_do();
    
   }


   function smallcal_pref() 
   {

      if (defined('SM_PATH'))
         include_once(SM_PATH . 'plugins/smallcal/functions.php');
      else
         include_once('../plugins/smallcal/functions.php');

      smallcal_pref_do();
    
   }


   function smallcal_options() 
   {

      if (defined('SM_PATH'))
         include_once(SM_PATH . 'plugins/smallcal/functions.php');
      else
         include_once('../plugins/smallcal/functions.php');

      smallcal_options_do();
    
   }


   function addsmallcal_left()
   {

      if (defined('SM_PATH'))
         include_once(SM_PATH . 'plugins/smallcal/functions.php');
      else
         include_once('../plugins/smallcal/functions.php');

      addsmallcal_left_do();
    
   }


   function smallcal_version()
   {

      return '0.8';

   }


?>
