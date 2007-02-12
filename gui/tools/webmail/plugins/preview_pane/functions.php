<?php

/**
  * SquirrelMail Preview Pane Plugin
  * Copyright (C) 2004 Paul Lesneiwski <pdontthink@angrynerds.com>
  * This program is licensed under GPL. See COPYING for details
  *
  */


/**
  * Build user options for display on "Display Preferences" page
  *
  */
  
function preview_pane_show_options_do() 
{

   global $data_dir, $username;
   $use_previewPane = getPref($data_dir, $username, 'use_previewPane', 0);
   $previewPane_vertical_split = getPref($data_dir, $username, 'previewPane_vertical_split', 0);
   $previewPane_size = getPref($data_dir, $username, 'previewPane_size', 300);
   $pp_refresh_message_list = getPref($data_dir, $username, 'pp_refresh_message_list', 0);

   bindtextdomain('preview_pane', SM_PATH . 'plugins/preview_pane/locale');
   textdomain('preview_pane');

   global $optpage_data;
   $optpage_data['vals'][1][] = array(
      'name'          => 'use_previewPane',
      'caption'       => _("Show Message Preview Pane"),
      'type'          => SMOPT_TYPE_BOOLEAN,
      'initial_value' => $use_previewPane,
      'refresh'       => SMOPT_REFRESH_ALL,
   );
   $optpage_data['vals'][1][] = array(
      'name'          => 'previewPane_vertical_split',
      'caption'       => _("Split Preview Pane Vertically"),
      'type'          => SMOPT_TYPE_BOOLEAN,
      'initial_value' => $previewPane_vertical_split,
      'refresh'       => SMOPT_REFRESH_ALL,
   );
   $optpage_data['vals'][1][] = array(
      'name'          => 'previewPane_size',
      'caption'       => _("Message Preview Pane Size"),
      'type'          => SMOPT_TYPE_INTEGER,
      'initial_value' => $previewPane_size,
      'refresh'       => SMOPT_REFRESH_ALL,
      'size'          => SMOPT_SIZE_TINY,
   );
   $optpage_data['vals'][1][] = array(
      'name'          => 'pp_refresh_message_list',
      'caption'       => _("Always Refresh Message List<br />When Using Preview Pane"),
      'type'          => SMOPT_TYPE_BOOLEAN,
      'initial_value' => $pp_refresh_message_list,
      'refresh'       => SMOPT_REFRESH_ALL,
   );

   bindtextdomain('squirrelmail', SM_PATH . 'locale');
   textdomain('squirrelmail');

}


/**
  * Construct button that clears out any preview pane contents.
  *
  */
function preview_pane_clear_pp_button_do()
{

   global $plugins, $archive_mail_button_has_been_printed,
          $username, $data_dir;


   if (!in_array('archive_mail', $plugins))
      $fixed_archive_mail_plugin_is_in_use_in_SM_1_4_x = 0;
   else 
   {

/* doesn't work due to archive mail functionality not being in a proper function body
      // trick the archive mail plugin just long enough
      // to figure out if it has been patched
      //
      global $SQM_INTERNAL_VERSION, $archiveMailIsPatchedForPreviewPanePlugin;
      $tempSMversion = $SQM_INTERNAL_VERSION;
      $SQM_INTERNAL_VERSION[1] = 5;
      include_once(SM_PATH . 'plugins/archive_mail/includes/archive_mail_bottom.php');
      $SQM_INTERNAL_VERSION = $tempSMversion;

      if ($archiveMailIsPatchedForPreviewPanePlugin)
         $fixed_archive_mail_plugin_is_in_use_in_SM_1_4_x = 1;
      else
         $fixed_archive_mail_plugin_is_in_use_in_SM_1_4_x = 0;
*/
      $fixed_archive_mail_plugin_is_in_use_in_SM_1_4_x = 1;
   }


   if (getPref($data_dir, $username, 'use_previewPane', 0) == 1) 
   {

      if ($fixed_archive_mail_plugin_is_in_use_in_SM_1_4_x 
       && !$archive_mail_button_has_been_printed)
         include_once(SM_PATH . 'plugins/archive_mail/includes/archive_mail_bottom.php');


      if ($fixed_archive_mail_plugin_is_in_use_in_SM_1_4_x
       && $archive_mail_button_has_been_printed)
         echo '&nbsp;';
      else
         echo '<tr width="100%"><td align="right">';


      bindtextdomain('preview_pane', SM_PATH . 'plugins/preview_pane/locale');
      textdomain('preview_pane');

      echo '<input type="button" value="' . _("Clear Preview") 
         . '" onClick="parent.bottom.document.location=\'' . SM_PATH 
         . 'plugins/preview_pane/empty_frame.php\'">'
         . '</td></tr>';

      bindtextdomain('squirrelmail', SM_PATH . 'locale');
      textdomain('squirrelmail');

   }

}


/**
  * Points message targets to open in the preview pane
  * (and possibly refresh message list as well)
  *
  */
function preview_pane_change_message_target_do($args)
{

   global $data_dir, $username;
   sqgetGlobalVar('REQUEST_URI', $request_uri, SQ_SERVER);

   if (getPref($data_dir, $username, 'use_previewPane', 0) == 1) 
   {
      $pp_refresh_message_list = getPref($data_dir, $username, 'pp_refresh_message_list', 0);
      $ret = ' TARGET="bottom" ';
      if ($pp_refresh_message_list)
         $ret .= ' onClick="document.location=\'' . $request_uri . '\'" ';
      return $ret;
   }

}


/**
  * Helper function that allows preview_pane_check_frames_do() to
  * know what kind of send action was actually occuring.
  *
  */
function preview_pane_compose_send_do()
{
   global $action, $pp_action;
   sqsession_register($action, 'pp_action');
}


/**
  * This function disallows the message list to be loaded
  * into the bottom frame.  JavaScript checks for the bottom
  * frame name, if it matches, just redirect to the empty
  * frame provided with the plugin.
  *
  */
function preview_pane_check_frames_do()
{

   // globalize $pp_forceTopURL and $pp_noPageHeader to synch 
   // with sent_confirmation
   //
   global $data_dir, $username, $plugins, $pp_forceTopURL, 
          $pp_noPageHeader, $pp, $compose_new_win, $pp_action;


   $pp_forceTopURL = "no";
   $pp_noPageHeader = FALSE;
   sqgetGlobalVar('pp', $pp, SQ_GET);
   sqgetGlobalVar('pp_action', $pp_action, SQ_SESSION);


   // bail if composing in new window - none of this applies
   //
   if ($compose_new_win) 
      return;


   // same goes if this is not a reply or a forward
   //
   if (!(strpos($pp_action, 'reply') !== FALSE || strpos($pp_action, 'forward') !== FALSE))
      return;


   // only need to bother if using preview pane (and haven't
   // already been redirected ala below)
   //
   if (getPref($data_dir, $username, 'use_previewPane', 0) == 1 && $pp != "yes") 
   {


      // check if sent_confirmation is installed and currently active
      // in which case we will allow the page to be loaded (only styles
      // 3 and 4; 1 and 2 force URL into top frame)
      //
      if (in_array('sent_confirmation', $plugins)) 
      {

         global $sent_conf_message_style, $sent_conf_allow_user_override,
                $sent_conf_message_sent_status;
         include_once (SM_PATH . 'plugins/sent_confirmation/config.php');
         if ($sent_conf_allow_user_override)
         {
            $sent_conf_style 
               = getPref($data_dir, $username, 'sent_conf_style', $sent_conf_message_style);
         }
         sqgetGlobalVar('sent_conf_message_sent_status', 
                        $sent_conf_message_sent_status, SQ_SESSION);


         if (isset($sent_conf_message_sent_status)
                && !empty($sent_conf_message_sent_status)
                && $sent_conf_message_sent_status != 'not_sent'
                && $sent_conf_message_style != 'off')
         {

            if ($sent_conf_message_style == 1 || $sent_conf_message_style == 2)
               $pp_forceTopURL = "yes";
            if ($sent_conf_message_style == 3 || $sent_conf_message_style == 4)
            {
               $pp_noPageHeader = TRUE;
               return;
            }

         }

      }


      echo "<script language='javascript' type='text/javascript'>\n"
         . "<!--\n"
         . "\n"
         . "   if (self.name == 'bottom')\n"
         . "   {\n";


// NOTE: we can also force the top frame to the URL that was being
//       loaded in the bottom, but this is usually overkill...
//       unless sent_confirmation style 1 or 2 is being used
      if ($pp_forceTopURL == "yes")
         echo "      parent.right.document.location = '" . $_SERVER['REQUEST_URI'] . "&pp=yes';\n";


      echo "      document.location = '" . SM_PATH . "plugins/preview_pane/empty_frame.php'\n"
         . "   }\n"
         . "//-->\n"
         . "</script>\n";

   }

}


/**
  * Change targets of message list and delete links at top of message display
  *
  * @param string $source The HTML text containing the message navigation links.
  *
  * @return string The same HTML text, but with the appropriate target changes in place.
  *
  */
function preview_pane_change_message_display_do($source) 
{

   global $data_dir, $username;

   if (getPref($data_dir, $username, 'use_previewPane', 0) == 1) 
   {

      // as of SM version 1.5.0, this works a little differently
      //
      if (check_sm_version(1, 5, 0))
      {
         $nav_row = $source[0];
         $menu_row = $source[1];

         // retarget message list link to top frame (in $nav_row)
         // and recode the target of the delete links to delete
         // message in top frame and move to next/prev messge in 
         // bottom frame (or clear out the bottom frame if the
         // delete button was pressed) 
         //
         // (the delete & prev, delete & next, and the message list
         // links are in $nav_row, the delete button is in $menu_row)
         //
         $nav_row = preg_replace(array('/&amp;delete_id=(\d+)"/', '/(href="[^"]*?right_main\.php\?.*?)>/'),

// WHY WHY WHY can't I access form widgets in the deleteMessageForm by using 
// "document.deleteMessageForm" instead of "document.forms[1]"????  Something
// is fishy here
//
                                 array('" onClick="if (parent.right) { document.forms[1].submit(); }"', '$1 target="right">'),

                                 $nav_row);


         $menu_row = preg_replace(array('/deleteMessageForm"(.*?)>/', '/(SUBMIT.*?name)="delete"/'),
                                  array('deleteMessageForm" target="right" onSubmit="document.location=\'' . SM_PATH . 'plugins/preview_pane/empty_frame.php\'" $1><input type="hidden" name="delete" value="1" />', '$1="delBtn"'),
                                  $menu_row);

         return array($nav_row, $menu_row);
      }


      // SM version 1.4.x...
      //
      else
      {
         // retarget message list link to top frame and recode
         // the target of the delete link to delete message in top frame
         // and clear out the bottom frame
         //
         $source = preg_replace(
            '/(<a.*?right_main.*?)>(.*?)href="(.*?delete_message.*?)"(.*?)<\/a>/', 

            '$1 target="right">$2href="$3'     // was:  ' . rand() 
            . '" onClick="if (parent.right) {parent.right.document.location=\'$3\'; document.location=\'' 
            . SM_PATH . 'plugins/preview_pane/empty_frame.php\'; return false;}"$4</a>',

            $source);

         return $source;
      }

   }

   return '';

}


/**
  * This function actually builds the third pane (frame)
  *
  * @param $source string The HTML source code containing the main SquirrelMail
  *                       frameset code.
  *
  * @return string The same HTML code, with the additional message pane frame inserted.
  *
  */
function preview_pane_build_frames_do($source) 
{

   global $data_dir, $username, $location_of_bar, $left_size, $right_frame_url;

   $use_previewPane = getPref($data_dir, $username, 'use_previewPane', 0);

   if ($use_previewPane)
   {

      $previewPane_size = getPref($data_dir, $username, 'previewPane_size', 300);
      $previewPane_vertical_split = getPref($data_dir, $username, 'previewPane_vertical_split', 0);

      if ($previewPane_vertical_split)
         $split = 'cols';
      else
         $split = 'rows';


      bindtextdomain('preview_pane', SM_PATH . 'plugins/preview_pane/locale');
      textdomain('preview_pane');

      if ($location_of_bar == 'right') 
      {

         $source = preg_replace('/<frameset.*/is', 
                             "<frameset cols=\"*, $left_size\" id=\"fs1\">\n"
                           . "<frameset $split=\"*, $previewPane_size\" id=\"fs2\">\n"
                           . "<frame src=\"$right_frame_url\" name=\"right\" title=\"" . _("Message List") . "\" frameborder=\"1\" />\n"
                           . "<frame src=\"" . SM_PATH . "plugins/preview_pane/empty_frame.php\" name=\"bottom\" title=\"" . _("Message Preview") . "\" frameborder=\"1\" />\n"
                           . "</frameset>" 
                           . "<frame src=\"left_main.php\" name=\"left\" title=\"" . _("Folder List") . "\" frameborder=\"1\" />\n",
                             $source);   

      }


      else
      {

         $source = preg_replace('/<frameset.*/is', 
                             "<frameset cols=\"$left_size, *\" id=\"fs1\">\n"
                           . "<frame src=\"left_main.php\" name=\"left\" title=\"" . _("Folder List") . "\" frameborder=\"1\" />\n"
                           . "<frameset $split=\"*, $previewPane_size\" id=\"fs2\">\n"
                           . "<frame src=\"$right_frame_url\" name=\"right\" title=\"" . _("Message List") . "\" frameborder=\"1\" />\n"
                           . "<frame src=\"" . SM_PATH . "plugins/preview_pane/empty_frame.php\" name=\"bottom\" title=\"" . _("Message Preview") . "\" frameborder=\"1\" />\n"
                           . "</frameset>\n",
                             $source);   

      }

      bindtextdomain('squirrelmail', SM_PATH . 'locale');
      textdomain('squirrelmail');

   }

   return $source;

}


/**
 * Stolen from functions/pageHeader.php
 * Modified for use with this plugin - sends page header stuff w/out menu bar
 *
 * @param array color the array of theme colors
 * @param string mailbox the current mailbox name to display
 * @param string xtra extra html code to add 
 * @param bool session
 * @return void
 */
function pp_displayPageHeader($color, $mailbox, $xtra='', $session=false) {

    global $hide_sm_attributions, $PHP_SELF, $frame_top,
           $compose_new_win, $compose_width, $compose_height,
           $attachemessages, $provider_name, $provider_uri,
           $javascript_on, $default_use_mdn, $mdn_user_support,
           $startMessage, $theme_css;

    sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION );
    sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION );
    $module = substr( $PHP_SELF, ( strlen( $PHP_SELF ) - strlen( $base_uri ) ) * -1 );
    if ($qmark = strpos($module, '?')) {
        $module = substr($module, 0, $qmark);
    }
    if (!isset($frame_top)) {
        $frame_top = '_top';
    }

    if ($session) {
        $compose_uri = $base_uri.'src/compose.php?mailbox='.urlencode($mailbox).'&amp;attachedmessages=true&amp;session='."$session";
    } else {
        $compose_uri = $base_uri.'src/compose.php?newmessage=1';
        $session = 0;
    }

    if($javascript_on) {

                $js ='';

                // compose in new window code
                if ($compose_new_win == '1') {
                    if (!preg_match("/^[0-9]{3,4}$/", $compose_width)) {
                        $compose_width = '640';
                    }
                    if (!preg_match("/^[0-9]{3,4}$/", $compose_height)) {
                        $compose_height = '550';
                    }
                    $js .= "function comp_in_new(comp_uri) {\n".
                     "       if (!comp_uri) {\n".
                     '           comp_uri = "'.$compose_uri."\";\n".
                     '       }'. "\n".
                         '    var newwin = window.open(comp_uri' .
                         ', "_blank",'.
                         '"width='.$compose_width. ',height='.$compose_height.
                         ',scrollbars=yes,resizable=yes");'."\n".
                         "}\n\n";
                }

                // javascript for sending read receipts
                if($default_use_mdn && $mdn_user_support) {
                    $js .= 'function sendMDN() {'."\n".
                           "    mdnuri=window.location+'&sendreceipt=1'; ".
                           "var newwin = window.open(mdnuri,'right');".
                       "\n}\n\n";
                }

                // if any of the above passes, add the JS tags too.
                if($js) {
                    $js = "\n".'<script language="JavaScript" type="text/javascript">' .
                        "\n<!--\n" . $js . "// -->\n</script>\n";
                }

                displayHtmlHeader ('SquirrelMail', $js);
                $onload = $xtra;

    } else {
        /* do not use JavaScript */
        displayHtmlHeader ('SquirrelMail');
        $onload = '';
    }

    echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\" $onload>\n\n";
	//echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"../plugins/preview_pane/".$theme_css."\" />"; //Insert stylesheet here
    echo "<a name=\"pagetop\"></a>\n";
    echo "\n\n";
}


?>
