<?php

/**
  * SquirrelMail Check Quota Plugin
  * Copyright(c) 2001-2002 Bill Shupp <hostmaster@shupp.org>
  * Copyright(c) 2002 Claudio Panichi
  * Copyright(c) 2002-2007 Kerem Erkan <kerem@keremerkan.net>
  * Copyright(c) 2003-2007 Paul Lesneiwski <paul@squirrelmail.org>
  * Licensed under the GNU GPL. For full terms see the file LICENSE.
  *
  * @package plugins
  * @subpackage check_quota
  *
  */


/**
  * Validate that this plugin is configured correctly
  *
  * @return boolean Whether or not there was a
  *                 configuration error for this plugin.
  *
  */
function check_quota_check_configuration()
{
  global $settings;

  // make sure config file has been created
  //
  if (!@include_once(SM_PATH . 'plugins/check_quota/config.php'))
  {
    do_err('Check Quota plugin is missing its main configuration file', FALSE);
    return TRUE;
  }


 // check that the IMAP server has QUOTA support if needed
 //
/* see comments below; have to fix those before we can use this
 if ($settings['quota_type'] == 1)
 {
   if (!sqGetGlobalVar('sqimap_capabilities', $capabilities, SQ_SESSION))
      $capabilities = '';

   if ( !empty($capabilities['QUOTA']) )
      $quota_capability = TRUE;
    else
    {
// FIXME: IMAP server will give you capability string w/out logging in... is there a SM lib command to get a IMAP stream w/out logging in?  or a way to force SM to go ask for the capability string?
//     $stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10);
//     if ( CheckQuota::check_quota_capability($imap_stream) )
//        $quota_capability = TRUE;
//      else
          $quota_capability = FALSE;
     }

     if (!$quota_capability)
     {
        do_err('Check Quota plugin is configured to obtain quota information from the IMAP server, but the IMAP server does not have QUOTA support built in', FALSE);
        return TRUE;
     }
  }
*/

//FIXME: what other basic error checks can be integrated 
//       from the troubleshooting page to here?  Problem
//       with most of the Unix and cPanel code is that it
//       requires a username to start checking quota and
//       see if anything is broken, but the configtest hook
//       of course doesn't have a username associated with it

   // only need to do this pre-1.5.2, as 1.5.2 will make this
   // check for us automatically
   //
  if (!check_sm_version(1, 5, 2))
  {
    // try to find Compatibility, and then that it is v2.0.7+
    //
    if (function_exists('check_plugin_version') && check_plugin_version('compatibility', 2, 0, 7, TRUE))
      return FALSE;

    // something went wrong
    //
    do_err('Check Quota plugin requires the Compatibility plugin version 2.0.7+', FALSE);

    return TRUE;
  }
}


/**
  * Displays quota graph above folder list (SM 1.4.x)
  *
  */
function check_quota_graph_before()
{
  global $check_quota;

  if (empty($check_quota))
    $check_quota = new CheckQuota();

  if ( !empty($check_quota->settings['info_above_folders_list']) )
    $check_quota->start_graph();
}


/**
  * Displays quota graph below folder list (SM 1.4.x)
  *
  */
function check_quota_graph_after()
{
  global $check_quota;

  if (empty($check_quota))
    $check_quota = new CheckQuota();

  if ( empty($check_quota->settings['info_above_folders_list']) )
    $check_quota->start_graph();
}


/**
  * Displays quota graph above/below folder list (SM 1.5.x)
  *
  */
function check_quota_graph()
{
  global $check_quota;

  if (empty($check_quota))
    $check_quota = new CheckQuota();

  return $check_quota->start_graph();
}


/**
  * Displays quota warnings in MOTD
  *
  * @param mixed $args The arguments passed to this plugin hook
  *
  */
function check_quota_motd($args)
{
  if ( !sqgetGlobalVar('just_logged_in', $just_logged_in, SQ_SESSION) || empty($just_logged_in) )
    return FALSE;

  if ( get_current_hook_name($args) != 'right_main_after_header' || !sqGetGlobalVar('check_quota_motd_displayed', $check_quota_motd_displayed, SQ_SESSION) || empty($check_quota_motd_displayed) )
  {
    global $check_quota;

    if ( empty($check_quota) )
      $check_quota = new CheckQuota();

    $ret = $check_quota->start_motd($args);

    sqsession_register(1, 'check_quota_motd_displayed');

    return $ret;
  }
}


/**
  * Displays Check Quota block on Options page
  *
  */
function check_quota_optpage_register_block()
{
  include_once(SM_PATH . 'functions/imap.php');

  global $check_quota;

  if (empty($check_quota))
    $check_quota = new CheckQuota();

  if ( !empty($check_quota->settings['troubleshoot']) )
  {
    global $optpage_blocks;

    sq_change_text_domain('check_quota');

    $optpage_blocks[] = array(
      'name' => _("\"Check Quota\" Troubleshooting"),
      'url'  => '../plugins/check_quota/troubleshoot.php',
      'desc' => _("This page can aid you in troubleshooting the \"Check Quota\" plugin."),
      'js'   => FALSE
    );

    sq_change_text_domain('squirrelmail');
  }
}


/**
  * This class encapsulates all quota functionality
  *
  */
class CheckQuota
{
  var $settings;
  var $quota;
  var $protocol;
  var $width;
  var $motd_appended;
  var $debug_info;
  var $quota_colors;

  /**
    * Constructor
    *
    * Sets up an object instantiation, loads configuration 
    * settings and checks the user's quota.
    *
    */
  function CheckQuota()
  {
    $this->reset_debug();
    $this->load_settings();
    $this->check_quota();
  }

  /**
    * Get configuration settings from config file
    *
    */
  function load_settings()
  {
    global $settings;

    include_once(SM_PATH . 'plugins/check_quota/config.php');

    $this->settings = $settings;
    unset ($settings);
  }  

  /**
    * Quota Checker Switcher function
    *
    * This function will call the appropriate quota checker function
    * for your system.
    */
  function check_quota()
  {
    switch ($this->settings['quota_type'])
    {
      case 0:
        $this->quota = $this->check_unix();
        break;
      case 1:
        $this->quota = $this->check_imap();
        break;
      case 2:
        $this->quota = $this->check_cpanel();
        break;
    }
  }


  /* =========================== UNIX QUOTA =========================== */


  /**
    * UNIX Quota Checker function
    *
    * Obtains the user's quota usage by asking the (local/remote)
    * server's "quota" command.
    *
    * @return array The quota information, if any.  "NOQUOTA" (string)
    *               is returned when no quota information could be 
    *               retrieved for the user.
    *
    */
  function check_unix()
  {
    global $username;

    $quota_array = array();

    if ( empty($this->settings['check_unix_on_remote_server']) )
      exec($this->settings['sudo_binary'] . ' ' . $this->settings['quota_binary'] . ' -v ' . $username, $quota_array);
    else
    {
      exec($this->settings['ssh_binary'] . ' ' . $this->settings['remote_unix_user'] . '@' . $this->settings['remote_unix_server'] . ' ' .
           $this->settings['sudo_binary'] . ' ' . $this->settings['quota_binary'] . ' -v ' . $username, $quota_array);
    }

    if ( $this->settings['troubleshoot'] )
    {
      if ( sizeof($quota_array) > 0 )
        $this->add_debug_info(_("Quota Binary Output:"),$this->print_r_html($quota_array));
      else
        $this->add_debug_info(_("Warning:"),_("Quota binary did not give any output. Quotas may not be enabled."),1);
    }

    $quota = array();
    $quota_data = array();

    if ( sizeof($quota_array) > 2 )
    {
      $c = 0;
      for ( $i = 2; $i < sizeof($quota_array); $i++ )
      {
        $quota_data[$c] = preg_split('/\s+/', trim($quota_array[$i]));

        if ( strpos($quota_data[$c][0], '/') === FALSE )
        {
          if (!empty($quota_data[$c - 1]))
             $quota_data[$c - 1] = array_merge($quota_data[$c - 1], $quota_data[$c]);
          else
             $quota_data[$c - 1] = $quota_data[$c];
          unset($quota_data[$c]);
        }
        $c++;
      }

      if ( sizeof($quota_data) > 0 )
      {
        $i = 0;

        foreach($quota_data as $quota_line)
        {
          if ( empty($this->settings['exclude_fs'][$quota_line[0]]) )
          {
            // not over quota
            //
            if ( sizeof($quota_line) == 7 && ($quota_line[2] > 0 || $quota_line[3] > 0 || $quota_line[5] > 0 || $quota_line[6] > 0) ) 
            {
              $quota[$i]['fs']     = $quota_line[0];
              $quota[$i]['blocks'] = $quota_line[1];
              $quota[$i]['bquota'] = $quota_line[2];
              $quota[$i]['blimit'] = $quota_line[3];
              $quota[$i]['bgrace'] = '-';
              $quota[$i]['files']  = $quota_line[4];
              $quota[$i]['fquota'] = $quota_line[5];
              $quota[$i]['flimit'] = $quota_line[6];
              $quota[$i]['fgrace'] = '-';
            }
            elseif ( sizeof($quota_line) == 8 )
            {
              // over size quota
              //
              if ( $quota_line[2] > 0 && $quota_line[1] > $quota_line[2] && ($quota_line[2] > 0 || $quota_line[3] > 0) ) 
              {
                $quota[$i]['fs']     = $quota_line[0];
                $quota[$i]['blocks'] = str_replace('*','',$quota_line[1]);
                $quota[$i]['bquota'] = $quota_line[2];
                $quota[$i]['blimit'] = $quota_line[3];
                $quota[$i]['bgrace'] = $quota_line[4];
                $quota[$i]['files']  = $quota_line[5];
                $quota[$i]['fquota'] = $quota_line[6];
                $quota[$i]['flimit'] = $quota_line[7];
                $quota[$i]['fgrace'] = '-';
              }

              // over count quota
              //
              elseif ( $quota_line[5] > 0 && $quota_line[4] > $quota_line[5] && ($quota_line[5] > 0 || $quota_line[6] > 0) ) 
              {
                $quota[$i]['fs']     = $quota_line[0];
                $quota[$i]['blocks'] = $quota_line[1];
                $quota[$i]['bquota'] = $quota_line[2];
                $quota[$i]['blimit'] = $quota_line[3];
                $quota[$i]['bgrace'] = '-';
                $quota[$i]['files']  = str_replace('*','',$quota_line[4]);
                $quota[$i]['fquota'] = $quota_line[5];
                $quota[$i]['flimit'] = $quota_line[6];
                $quota[$i]['fgrace'] = $quota_line[7];
              }

            }

            // over both quotas
            //
            elseif ( sizeof($quota_line) == 9 && ($quota_line[2] > 0 || $quota_line[3] > 0 || $quota_line[6] > 0 || $quota_line[7] > 0) ) 
            {
              $quota[$i]['fs']     = $quota_line[0];
              $quota[$i]['blocks'] = str_replace('*','',$quota_line[1]);
              $quota[$i]['bquota'] = $quota_line[2];
              $quota[$i]['blimit'] = $quota_line[3];
              $quota[$i]['bgrace'] = $quota_line[4];
              $quota[$i]['files']  = str_replace('*','',$quota_line[5]);
              $quota[$i]['fquota'] = $quota_line[6];
              $quota[$i]['flimit'] = $quota_line[7];
              $quota[$i]['fgrace'] = $quota_line[8];
            }
            $i++;
          }
        }
      }
    }

    if ( !empty($quota) && sizeof($quota) > 0 )
    {
      return $quota;
    }
    else
    {
      if ( $this->settings['troubleshoot'] )
        $this->add_debug_info(_("Warning:"),_("You do not have quotas enabled for your username, you will not see any informative graphs."),1);

      return 'NOQUOTA';
    }
  }


  /* =========================== IMAP QUOTA =========================== */


  /**
    * Set up IMAP connection stream and pass that to the quota
    * retrieval function to get the needed quota information.
    *
    * @return array The quota information, if any.  "NOQUOTA" (string)
    *               is returned when no quota support was found in the
    *               IMAP server or the user has no quota information.
    *
    */
  function check_imap()
  {
    global $username, $key, $imapServerAddress, $imapPort, $imap_stream, $imapConnection;

    if ( empty($key) )
      sqGetGlobalVar('key', $key, SQ_COOKIE);

    if ( (!isset($imap_stream) && !isset($imapConnection)) || $this->settings['use_separate_imap_connection'] )
    {
      $stream = sqimap_login($username, $key, $imapServerAddress, $imapPort, 10);
      $new_connection = TRUE;
    } 
    elseif ( isset($imapConnection) ) 
    {
      $stream = $imapConnection;
      $new_connection = FALSE;
    } 
    else 
    {
      $stream = $imap_stream;
      $new_connection = FALSE;
    }

    $quota = $this->sqimap_get_quota($stream, 'INBOX');

    if ( $new_connection )
      sqimap_logout($stream);

    return $quota;
  }

  /**
    * This function does the actual work of getting quota information
    * from the IMAP server, given a valid connection to the server.
    *
    * @return array The quota information, if any.  "NOQUOTA" (string)
    *               is returned when no quota support was found in the
    *               IMAP server or the user has no quota information.
    *
    */
  function sqimap_get_quota ($imap_stream, $mailbox) 
  {
    $quota = array();

    if (!sqGetGlobalVar('sqimap_capabilities', $capabilities, SQ_SESSION))
      $capabilities = '';

    if ( !empty($capabilities['QUOTA']) )
      $quota_capability = TRUE;
    elseif ( $this->check_quota_capability($imap_stream) )
      $quota_capability = TRUE;
    else
      $quota_capability = FALSE;

    if ($quota_capability)
    {
      $imap_command = 'a001 GETQUOTAROOT "' . $mailbox . '"' . "\r\n";

      fputs ($imap_stream, $imap_command);
      $read_array = sqimap_read_data ($imap_stream, 'a001', true, $result, $message);

      if ( check_sm_version(1,5,0) )
        $read_array = $read_array['a001'];

      if ($this->settings['troubleshoot']) 
      {
        $this->add_debug_info(_("IMAP Command Sent:"),$imap_command);
        $this->add_debug_info(_("IMAP Response Received:"),$this->print_r_html($read_array));
      }

      foreach ($read_array as $response)
      {
        $storageUsed = '';
        $storageTotal = '';
        $messagesUsed = '';
        $messagesTotal = '';
        if (is_array($response))
        {
          foreach ($response as $resp)
          {
            if (strpos($resp, 'STORAGE') !== FALSE || strpos($resp, 'MESSAGE') !== FALSE)
            {
              preg_match('/[(]([STORAGEMESSAGE0-9 ]+)[)]/', $resp, $matches);
              $usageArray = explode(' ', $matches[1]);
              if ($usageArray[0] == 'STORAGE')
                list($storageUsed, $storageTotal) = array($usageArray[1], $usageArray[2]);
              if (!empty($usageArray[3]) && $usageArray[3] == 'STORAGE')
                list($storageUsed, $storageTotal) = array($usageArray[4], $usageArray[5]);
              if ($usageArray[0] == 'MESSAGE')
                list($messagesUsed, $messagesTotal) = array($usageArray[1], $usageArray[2]);
              if (!empty($usageArray[3]) && $usageArray[3] == 'MESSAGE')
                list($messagesUsed, $messagesTotal) = array($usageArray[4], $usageArray[5]);

              $quota[0]['fs']     = '';
              $quota[0]['blocks'] = $storageUsed;
              $quota[0]['bquota'] = $storageTotal;
              $quota[0]['blimit'] = '';
              $quota[0]['files']  = $messagesUsed;
              $quota[0]['fquota'] = $messagesTotal;
              $quota[0]['flimit'] = '';

              return $quota;
            }
          }
        }
        elseif (strpos($response, 'STORAGE') !== FALSE || strpos($response, 'MESSAGE') !== FALSE)
        {
          preg_match('/[(]([STORAGEMESSAGE0-9 ]+)[)]/', $response, $matches);
          $usageArray = explode(' ', $matches[1]);
          if ($usageArray[0] == 'STORAGE')
            list($storageUsed, $storageTotal) = array($usageArray[1], $usageArray[2]);
          if (!empty($usageArray[3]) && $usageArray[3] == 'STORAGE')
            list($storageUsed, $storageTotal) = array($usageArray[4], $usageArray[5]);
          if ($usageArray[0] == 'MESSAGE')
            list($messagesUsed, $messagesTotal) = array($usageArray[1], $usageArray[2]);
          if (!empty($usageArray[3]) && $usageArray[3] == 'MESSAGE')
            list($messagesUsed, $messagesTotal) = array($usageArray[4], $usageArray[5]);

          $quota[0]['fs']     = '';
          $quota[0]['blocks'] = $storageUsed;
          $quota[0]['bquota'] = $storageTotal;
          $quota[0]['blimit'] = '';
          $quota[0]['files']  = $messagesUsed;
          $quota[0]['fquota'] = $messagesTotal;
          $quota[0]['flimit'] = '';

          return $quota;
        }
      }
    }
    else
      $this->add_debug_info(_("Warning:"),_("Your IMAP server does not have IMAP4 QUOTA extension. Quotas not enabled."),1);

    return 'NOQUOTA';
  }

  /**
    * Asks the IMAP server for its capabilities list, and
    * returns TRUE if QUOTA is included in that list.
    *
    * @return boolean TRUE if the IMAP server has quota support; FALSE otherwise
    *
    */
  function check_quota_capability($imap_stream, $capability='') 
  {
    if ( empty($capability) )
    {
      fputs ($imap_stream, "a001 CAPABILITY\r\n");
      $capability = sqimap_read_data($imap_stream, 'a001', true, $a, $b);

      if ( check_sm_version(1,5,0) )
        $capability = $capability['a001'];
    }

    // parse CAPABILITIES, looking for QUOTA

    foreach ( $capability as $response )
    {
      if ( is_array($response) )
      {
        foreach ($response as $resp)
        {
          if ( strpos($resp, 'QUOTA') !== FALSE )
            return TRUE;
        }
      }
      elseif ( strpos($response, 'QUOTA') !== FALSE )
        return TRUE;
    }

    return FALSE;
  }


  /* =========================== cPANEL QUOTA =========================== */


  /**
    * cPanel Quota Checker function
    *
    * This functionality is experimental and was first created by
    * Rob Thiesfield (php_hacker@samafas.com).  Some controls have
    * since been added and it has been simplified for faster output 
    * and added debug controls.
    *
    * cPanel uses neither UNIX nor IMAP quotas and has its own quota
    * implementation.
    *
    * This was coded mostly without access to a cPanel system to test
    * with, so please help by submitting problem reports.
    *
    */
  function check_cpanel()
  {
    global $username, $cq_cpanel_refresh;

    $cpanel_quota = array();

    if ( $cq_cpanel_refresh || $this->settings['always_refresh_cpanel'] || !sqGetGlobalVar('cpanel_quota', $cpanel_quota, SQ_SESSION) )
    {
      sqsession_unregister('cpanel_quota');

      @list($user,$domain) = explode('@', $username);
      if ( empty($user) || empty($domain) )
      {
        if ( $this->settings['troubleshoot'] )
          $this->add_debug_info(_("Warning:"),_("Your username is not in user@domain format. cPanel quotas only work with that type of usernames for now."),1);
        sqsession_register('NOQUOTA', 'cpanel_quota');
        return 'NOQUOTA';
      }

      elseif ( empty($this->settings['cpanel_root']) )
      {
        sqsession_register('NOQUOTA', 'cpanel_quota');
        return 'NOQUOTA';
      }

      if ( substr($this->settings['cpanel_root'], -1) != '/' )
        $this->settings['cpanel_root'] .= '/';

      $reseller = '';
      $paths = glob($this->settings['cpanel_root'] . '*/etc/' . $domain, GLOB_ONLYDIR);

      if ( is_array($paths) && sizeof($paths) > 0 )
      {
        $path_parts = explode('/', substr($paths[0], strlen($this->settings['cpanel_root'])));

        if ( sizeof($path_parts) > 0 )
          $reseller = $path_parts[0] . '/';
      }

      if ( empty($reseller) )
      {
        if ( $this->settings['troubleshoot'] )
          $this->add_debug_info(_("Warning:"),_("Reseller information for your domain cannot be obtained."),1);
        sqsession_register('NOQUOTA', 'cpanel_quota');
        return 'NOQUOTA';
      }

      $quota_file = $this->settings['cpanel_root'] . $reseller . 'etc/' . $domain . '/quota';
      if ( $this->settings['troubleshoot'] )
        $this->add_debug_info(_("Quota File:"),$quota_file);

      $fp = fopen($quota_file, 'r');

      if ( !$fp )
      {
        if ( $this->settings['troubleshoot'] )
          $this->add_debug_info(_("Warning:"),_("Quota file for your domain does not exist or not readable."),1);
        sqsession_register('NOQUOTA', 'cpanel_quota');
        return 'NOQUOTA';
      }

      while ( $line = fgets($fp, 4096) )
      {
        $tmp = explode(':', trim($line));

        if ( $tmp[0] == $user )
        {
          $cpanel_quota[0]['bquota'] = $tmp[1] / 1024;
          break;
        }
      }	

      fclose ($fp);

      if ( empty($cpanel_quota[0]['bquota']) )
      {
        if ( $this->settings['troubleshoot'] )
          $this->add_debug_info(_("Warning:"),_("You do not have quotas enabled for your username, you will not see any informative graphs."),1);
        sqsession_register('NOQUOTA', 'cpanel_quota');
        return 'NOQUOTA';
      }

      if ( $this->settings['troubleshoot'] )
        $this->add_debug_info(_("Your cPanel Quota:"),round($cpanel_quota[0]['bquota']) . ' KB');
        
      $files_location = $this->settings['cpanel_root'] . $reseller . 'mail/' . $domain . '/' . $user;

      if ( $this->settings['troubleshoot'] )
        $this->add_debug_info(_("Mail Files Location:"),$files_location);

      $quota_array = array();
      exec($this->settings['du_binary'] . ' -ks ' . $files_location, $quota_array);

      if ( sizeof($quota_array) > 0 )
      {
        if ( $this->settings['troubleshoot'] )
          $this->add_debug_info(_("&quot;du&quot; Binary Output:"), $this->print_r_html($quota_array));

        foreach ( $quota_array as $quota_line )
        {
          $quota_data = preg_split('/\s+/', trim($quota_line));

          if ($quota_data[1] == $files_location)
          {
            $cpanel_quota[0]['blocks'] = $quota_data[0];
            $cpanel_quota[0]['blimit'] = '';
            $cpanel_quota[0]['files'] = '';
            $cpanel_quota[0]['fquota'] = '';
            $cpanel_quota[0]['flimit'] = '';
            $cpanel_quota[0]['fs'] = '';
            break;
          }
        }
      }
      else
      {
        if ( $this->settings['troubleshoot'] )
          $this->add_debug_info(_("Warning:"),_("&quot;du&quot; binary did not give any output. There may be some problem with that binary."),1);

        sqsession_register('NOQUOTA', 'cpanel_quota');

        return 'NOQUOTA';
      }
    }

    sqsession_register($cpanel_quota, 'cpanel_quota');

    return $cpanel_quota;
  }


  /* =========================== QUOTA DISPLAY =========================== */


  /**
    * Quota Graph Starter function
    *
    * This function parses the quota information obtained and
    * decides if a graph will be drawn.
    *
    * @return string Only in SquirrelMail 1.5.2+, the output is returned
    *                in template hook format; otherwise, output is sent 
    *                directly to the client herein.
    *
    */
  function start_graph()
  {
    global $left_size, $color, $oTemplate;

    sq_change_text_domain('check_quota');

    $output = '';
    $location = get_location();
    $proto = substr($location, 0, strpos($location, ':'));
    $this->protocol = $proto . '://';

    $this->width = round($left_size * 0.8);

    if ( is_array($this->quota) )
    {

      // 0 = size quota, 1 = count quota
      //
      for ( $type = 0; $type < 2; $type++ ) 
      {
        $intro_displayed = 0;

        foreach ( $this->quota as $quota_line )
        {
          list($used, $quota, $fs) = $this->parse_quota_info($quota_line, $type);

          $header = '';
          $header_text = '';
          $header_spacing = 0;
          $show_hr_before_graph = 0;

          if ( $quota > 0 )
          {

            if ( $this->settings['show_intro_texts'] && !$intro_displayed )
            {

              if ( $this->settings['use_hr'] )
              {
                if ( check_sm_version(1, 5, 2) )
                  $show_hr_before_graph = 1;
                else
                  $output .= '<hr width="90%" />';
              }

              if ( check_sm_version(1, 5, 2) )
              {
                if ($type == 0)
                  $header_text = _("Quota Usage:");
                else
                  $header_text = _("File Usage:"); 

                if (sizeof($this->quota) >= 2)
                  $header_spacing = 1;
              }
              elseif (sizeof($this->quota) < 2 )
              {
                $header = '<div align="' . $this->settings['graph_alignment'] . '">' 
                         . '<font size="' . $this->settings["font_size"] . '"><b>' 
                         . (($type == 0)? _("Quota Usage:"): _("File Usage:")) 
                         . '</b></font></div>' . "\n";
              }
              else
              {
                $output .= '<p><div align="' . $this->settings['graph_alignment'] . '">' 
                         . '<font size="' . $this->settings["font_size"] . '"><b>' 
                         . (($type == 0)? _("Quota Usage:"): _("File Usage:")) 
                         . '</b></font></div></p>' . "\n";
              }

              $intro_displayed = 1;
            }
            elseif ( $this->settings['use_hr'] && !$intro_displayed )
            {
              if ( check_sm_version(1, 5, 2) )
                $show_hr_before_graph = 1;
              else
                $output .= '<hr width="90%" />';
              $intro_displayed = 1;
            }

            list($percent, $alt) = $this->format_quota_numbers($used, $quota, $type);

            if ( check_sm_version(1, 5, 2) )
            {
              $oTemplate->assign('show_hr_before_graph', $show_hr_before_graph);
              $oTemplate->assign('header_text', $header_text);
              $oTemplate->assign('header_spacing', $header_spacing);
              $oTemplate->assign('graph_alignment', $this->settings['graph_alignment']);
              $oTemplate->assign('font_size', $this->settings['font_size']);
              $oTemplate->assign('details_above_graph', $this->settings['details_above_graph']);
              $oTemplate->assign('yellow_alert_limit', $this->settings['yellow_alert_limit']);
              $oTemplate->assign('red_alert_limit', $this->settings['red_alert_limit']);
              $oTemplate->assign('protocol', $this->protocol);
              $oTemplate->assign('width', $this->width);
              $oTemplate->assign($this->draw_graph($percent, $alt, $fs, $header));

              $output .= $oTemplate->fetch('plugins/check_quota/graph.tpl');
            }
            else
            {
              $output .= $this->draw_graph($percent, $alt, $fs, $header);
            }
          }
        }
      }

      if ( check_sm_version(1, 5, 2) )
        $output .= (($this->settings['use_hr']) ? $oTemplate->fetch('plugins/check_quota/horizontal_rule.tpl') : '');
      else
        $output .= (($this->settings['use_hr']) ? '<hr width="90%" />' : '');

    }

    sq_change_text_domain('squirrelmail');

    if ( check_sm_version(1, 5, 2) )
    {
      if ( !empty($this->settings['info_above_folders_list']) )
        return array('left_main_before' => $output);
      else
        return array('left_main_after' => $output);
    }
    else
    {
      echo $output;
    }
  }

  /**
    * This function draws a quota graph.
    *
    * @param int    $percent The percentage used for this quota 
    *                        graph.
    * @param string $alt     A textual description of the quota 
    *                        information.
    * @param string $fs      The name of the filesystem that this
    *                        quota applies to (Unix-quotas only)
    * @param string $header  Any graph header that should be placed
    *                        right before (on top of) the graph
    *                        (OPTIONAL; default is none; empty)
    *
    * @return mixed In SquirrelMail versions before 1.5.2, a string
    *               containing the quota information graph (HTML code) 
    *               to be sent to the client, or for SquirrelMail 
    *               1.5.2 and above, an array is returned:
    *                 'info'    -- Filesystem and textual details of
    *                              the quota (typically should change
    *                              newlines herein into line breaks)
    *                 'gd_uri'  -- URI to the GD image generator script
    *                              to be used as image src attribute   
    *                              (is only populated if using GD images)
    *                 'swf_uri' -- URI to the Flash image generator 
    *                              (is only populated if using Flash images)
    *                 'alt'     -- The textual description of the quota
    *                 'percent' -- The percent used for this graph
    *                 'percent_rounded' -- Same as "percent" but rounded 
    *                                      to an integer
    *                 'colors'  -- An array of the colors to be used 
    *                              for the graph
    *                 'flash_colors' -- An array of the colors to be 
    *                                   used for the graph ready to be 
    *                                   passed to the Flash image generator
    *                 'number_columns' -- The number of columns/graph
    *                                     segments
    *
    */
  function draw_graph($percent, $alt, $fs, $header='')
  {
    // first, set up color scheme
    //
    if ( empty($this->quota_colors) )
    {
      global $color;

      if ( !sqGetGlobalVar('chosen_theme', $theme, SQ_SESSION) )
        $theme = '';
      if ( !sqGetGlobalVar('random_theme_good_theme', $random_theme, SQ_SESSION) )
        $random_theme = '';

      if ( !empty($theme) )
      {
        $theme = basename($theme);

        if ( $theme == 'random.php' )
        {
          if ( !empty($random_theme) )
            $theme = basename($random_theme);
          else
            $theme = 'default_theme.php';
        }
      }
      else
        $theme = 'default_theme.php';

      $theme_file = SM_PATH . '/plugins/check_quota/themes/' . $theme;

      if (!@include_once($theme_file))
      {
        $quota_color[0] = '#FFFFFF'; // Graph background color (white)
        $quota_color[1] = '#008000'; // Default graph color (green)
        $quota_color[2] = '#FF6600'; // Yellow alert color (orange)
        $quota_color[3] = '#D70000'; // Red alert color (red)
      }

      $this->quota_colors = $quota_color;
    }

    // build output
    //
    $info = '';
    $output = '';
    $image_uri = '';

    $round = round($percent);
    if ( $round > 100 )
      $round = 100;

    $c0 = substr($this->quota_colors[0], 1);
    $c1 = substr($this->quota_colors[1], 1);
    $c2 = substr($this->quota_colors[2], 1);
    $c3 = substr($this->quota_colors[3], 1);

    $template_return_array = array('percent' => $percent,
                                   'percent_rounded' => $round,
                                   'gd_uri' => '',
                                   'swf_uri' => '',
                                   'alt' => $alt,
                                   'colors' => $this->quota_colors,
                                   'flash_colors' => array($c0, $c1, $c2, $c3));

    if ( $this->settings['show_filesystems'] && !empty($fs) )
      $info .= chunk_split($fs, 25, "\n");

    if ( $percent < 100 )
      $info .= $alt;
    else
      $info .= _("OVER QUOTA!");

    $template_return_array['info'] = $info;
    $info = nl2br($info);

    $output .= '<p align="' . $this->settings['graph_alignment'] . '">' 
             . $header
             . '<font size="' . $this->settings['font_size'] . '">' . "\n";

    if ( $this->settings['details_above_graph'] )
      $output .= $info;

    // for HTML or GD graphs...
    //
    if ( $this->settings['graph_type'] < 2 )
    {
      $gd_ok = 0;

      if ( $this->settings['graph_type'] == 1 && ( function_exists('imagepng') || function_exists('imagegif') || function_exists('imagejpeg') ) )
      {
        $gd_ok = 1;

        if ( function_exists('imagepng') )
          $image_type = 'png';
        elseif ( function_exists('imagegif') )
          $image_type = 'gif';
        elseif ( function_exists('imagejpeg') )
          $image_type = 'jpeg';

        $image_uri = 'gd_bar.php?w=' . $this->width 
                   . '&t=' . $image_type 
                   . '&p=' . round($percent) 
                   . '&y=' . $this->settings['yellow_alert_limit'] 
                   . '&r=' . $this->settings['red_alert_limit'] 
                   . '&c0=' . $c0 . '&c1=' . $c1 . '&c2=' . $c2 . '&c3=' . $c3;

        $template_return_array['gd_uri'] = sqm_baseuri()
                                         . 'plugins/check_quota/images/' 
                                         . $image_uri;
      }
      elseif ( $percent >= $this->settings['red_alert_limit'] )
        $tcol = 3;
      elseif ( $percent >= $this->settings['yellow_alert_limit'] )
        $tcol = 2;
      else
        $tcol = 1;          

      $template_return_array['number_columns'] = $tcol;

      $output .= '  <table width="' . $this->width 
               . '" border="0" cellpadding="1" cellspacing="' 
               . (($gd_ok)? '0':'1') 
               . '" bgcolor="' . $this->quota_colors[0] . '">' . "\n" 
               . '    <tr>';
      
      if ( $gd_ok ) 
      {
        $output .= '<td bgcolor="' . $this->quota_colors[0] 
                 . '" valign="center"><img src="' . sqm_baseuri() 
                 . 'plugins/check_quota/images/' . $image_uri 
                 . '" width="100%" height="10" alt="' . $alt . '"></td>';
      }

      // build HTML graph
      //
      else
      {
        if ( $round != 0 )
          $output .= '<td bgcolor="' . $this->quota_colors[$tcol] 
                   . '" width="' . $round . '%" height="10">';
        if ( $round < 100 )
          $output .= '</td><td bgcolor="' . $this->quota_colors[0] . '" height="10">';

        $output .= '</td>';
      }

      $output .= '</tr>' . "\n" . '  </table>' . "\n";
    }
    else
    {
      $template_return_array['swf_uri'] = sqm_baseuri() . 'plugins/check_quota/swf/bar.swf';

      $output .= '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"' 
               . ' codebase="' . $this->protocol . 'download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0"' 
               . ' width="' . $this->width . '" height="12">' . "\n" 
               . '  <param name="movie" value="' . sqm_baseuri() . 'plugins/check_quota/swf/bar.swf">' . "\n" 
               . '  <param name="quality" value="high"><param name="SCALE" value="exactfit">' . "\n" 
               . '  <param name="menu" value="false">' . "\n" 
               . '  <param name=FlashVars value="' 
               . 'p=' . round($percent) 
               . '&y=' . $this->settings['yellow_alert_limit'] 
               . '&r=' . $this->settings['red_alert_limit'] 
               . '&c0=0x' . $c0 
               . '&c1=0x' . $c1 
               . '&c2=0x' . $c2 
               . '&c3=0x' . $c3 . '">' . "\n" 
               . '  <embed src="' . sqm_baseuri() . 'plugins/check_quota/swf/bar.swf"' . "\n"
               . '    FlashVars="' 
               . 'p=' . round($percent) 
               . '&y=' . $this->settings['yellow_alert_limit'] 
               . '&r=' . $this->settings['red_alert_limit'] 
               . '&c0=0x' . $c0 
               . '&c1=0x' . $c1 
               . '&c2=0x' . $c2 
               . '&c3=0x' . $c3 . '"' . "\n" 
               . '    quality="high" menu="false"' . "\n" 
               . '    pluginspage="' . $this->protocol . 'www.macromedia.com/go/getflashplayer"' 
               . ' type="application/x-shockwave-flash" width="' . $this->width . '" height="12" scale="exactfit">' . "\n" 
               . '  </embed></object>';
    }

    if ( !$this->settings['details_above_graph'] )
      $output .= $info;

    if ( check_sm_version(1, 5, 2) )
      return $template_return_array;
    else
      return $output . '</font></p>' . "\n";
  }

  /**
    * MOTD Starter function
    *
    * This function parses the quota information obtained and
    * decides if MOTD will be appended.
    *
    * @param mixed $args The arguments passed to this plugin hook
    *
    */
  function start_motd($args)
  {
    $ret = '';
    // it might not hurt to do this in 1.4.x also... this "already
    // displayed/appended motd" stuff needs a better design
    if ( check_sm_version(1, 5, 2) )
      $this->motd_appended = 0;

    if ( is_array($this->quota) )
    {
      // 0 = size quota, 1 = count quota
      //
      for ( $type = 0; $type < 2; $type++ )
      {
        foreach ( $this->quota as $quota_line )
        {
          list($used, $quota) = $this->parse_quota_info($quota_line, $type);

          if ( !$this->motd_appended && $quota > 0 )
          {
            list($percent, $alt) = $this->format_quota_numbers($used, $quota, $type);  
            $ret = $this->append_motd($args, $percent);
          }

          if ( $this->motd_appended )
            break 2;
        }             
      }  
    }

    return $ret;
  }

  /**
    * MOTD Appender function
    *
    * This function appends warnings to MOTD if needed. The first quota information
    * that generates a warning will be displayed. After that, if there are other critical
    * warnings, they will not be displayed, until the user resolves his/her first
    * warning.
    *
    * @param mixed $args    The arguments passed to this plugin hook
    * @param int   $percent The percentage quota use for this quota information
    *
    */
  function append_motd($args, $percent)
  {
    global $motd, $color;

    sq_change_text_domain('check_quota');

    $alert_pre = '<font color="' . $color[2] . '"><b>' . _("WARNING:") . '</b></font> '
               . sprintf(_("Your quota usage is currently <b>%s%%</b>."), $percent);

    if ( strlen($motd) > 0 )
      $motd_append = '<br /><br />';
    else
      $motd_append = '';

    // display red alert
    //
    if ( $percent >= $this->settings['red_alert_limit'] && $this->settings['show_red_alert_motd'] )
    {
      $this->motd_appended = 1;
      if (check_sm_version(1, 5, 2))
      {
        global $oTemplate;

        $oTemplate->assign('color', $color);
        $oTemplate->assign('motd_pad', (strlen($motd) > 0));
        $oTemplate->assign('warning_title', _("WARNING:"));
        $oTemplate->assign('usage_text', sprintf(_("Your quota usage is currently %s%%."), $percent));
        $oTemplate->assign('message', _("To avoid losing any email, you should immediately empty out your \"Trash\" and \"Sent\" folders and delete emails with large attachments."));
        sq_change_text_domain('squirrelmail');

        $motd .= ' '; // trick so other plugins know MOTD has been added to

        return array('motd_inside' => $oTemplate->fetch('plugins/check_quota/motd_alert.tpl'));
      }
      else
      {
        $motd .= $motd_append . $alert_pre 
              . ' ' . _("To avoid losing any email, you should immediately empty out your &quot;Trash&quot; and &quot;Sent&quot; folders and delete emails with large attachments.");
      }
    }

    // display yellow alert
    //
    elseif ( $percent >= $this->settings['yellow_alert_limit'] && $this->settings['show_yellow_alert_motd'] )
    {
      $this->motd_appended = 1;

      if (check_sm_version(1, 5, 2))
      {
        global $oTemplate;

        $oTemplate->assign('color', $color);
        $oTemplate->assign('motd_pad', (strlen($motd) > 0));
        $oTemplate->assign('warning_title', _("WARNING:"));
        $oTemplate->assign('usage_text', sprintf(_("Your quota usage is currently %s%%."), $percent));
        $oTemplate->assign('message', _("You may want to make sure you empty out your \"Trash\" and clean your \"Sent\" folder."));
        sq_change_text_domain('squirrelmail');

        $motd .= ' '; // trick so other plugins know MOTD has been added to

        return array('motd_inside' => $oTemplate->fetch('plugins/check_quota/motd_alert.tpl'));
      }
      else
      {
        $motd .= $motd_append . $alert_pre 
              . ' ' . _("You may want to make sure you empty out your &quot;Trash&quot; and clean your &quot;Sent&quot; folder.");
      }
    }

    sq_change_text_domain('squirrelmail');
  }


  /* =========================== QUOTA PARSING/UTILITIES =========================== */


  /**
    * Quota Information Parser function
    *
    * Parses quota information to get "used" and "quota" variables.
    *
    * @param array $quota_line The quota information array
    * @param int   $type       Which kind of quota information is
    *                          being requested: 0 = size-based quota;
    *                          1 = count-based quota
    *
    * @return array Three element array: first element is the quota use
    *               figure; second element is the quota figure; third
    *               element is the filesystem name that this quota 
    *               information applies to (only for Unix-based quotas)
    *
    */
  function parse_quota_info($quota_line, $type)
  {
    // get filesystem name for this quota info
    //
    if ( $this->settings['quota_type'] == 0 )
      $fs = ((!empty($this->settings['fs_alias'][$quota_line['fs']]))
          ? $this->settings['fs_alias'][$quota_line['fs']]
          : $quota_line['fs']) . ':';
    else
      $fs = '';

    // get size-based quota info
    //
    if ( $type == 0 )
    {
      $used = $quota_line['blocks'];

      if ( $this->settings['quota_type'] == 1 && !empty($this->settings['imap_size_quota']) && is_int($this->settings['imap_size_quota']) )
        $quota = $this->settings['imap_size_quota'];
      else
        $quota = $quota_line['bquota'];

      $limit = $quota_line['blimit'];
    }

    // get count-based quota info
    //
    elseif ( $type == 1 )
    {
      $used = $quota_line['files'];

      if ( $this->settings['quota_type'] == 1 && !empty($this->settings['imap_count_quota']) && is_int($this->settings['imap_count_quota']) )
        $quota = $this->settings['imap_count_quota'];
      else
        $quota = $quota_line['fquota'];

      $limit = $quota_line['flimit'];
    }           

    if ( $quota == 0 )
      $quota = $limit;

    return array($used, $quota, $fs);
  }

  /**
    * Number Formatter function
    *
    * This function formats quota information according to your
    * quota settings.
    *
    * @param int $used    The usage figure
    * @param int $quota   The limit figure
    * @param int $type    Which kind of quota information is
    *                     being formatted: 0 = size-based 
    *                     quota; 1 = count-based quota
    *
    * @return array A two element array: first element is the
    *               quota percent used, second element is the
    *               textual description of the quota information.
    *
    */
  function format_quota_numbers($used, $quota, $numtype)
  {
    if ( $numtype == 0 && $this->settings['use_1000KB_per_MB'] )
    {
      if ( $used > 1000000 )
        $used_text = number_format($used / 1000000, 2) . ' GB';
      elseif ( $used > 1000 )
        $used_text = number_format($used / 1000, 2) . ' MB';
      else
        $used_text = $used . ' KB';

      if ( $quota > 1000000 )
        $quota_text = number_format($quota / 1000000, 2) . ' GB';
      elseif ( $quota > 1000 )
        $quota_text = number_format($quota / 1000, 2) . ' MB';
      else 
        $quota_text = round($quota) . ' KB';
    }
    elseif ( $numtype == 0 && !$this->settings['use_1000KB_per_MB'] )
    {
      if ( $used > 1048576 )
        $used_text = number_format($used / 1048576, 2) . ' GB';
      elseif ( $used > 1024 )
        $used_text = number_format($used / 1024, 2) . ' MB';
      else 
        $used_text = $used . ' KB';

      if ( $quota > 1048576 )
        $quota_text = number_format($quota / 1048576, 2) . ' GB';
      elseif ( $quota > 1024 )
        $quota_text = number_format($quota / 1024, 2) . ' MB';
      else 
        $quota_text = round($quota) . ' KB';
    }
    else
    {
      $used_text = $used;
      $quota_text = $quota;
    }

    $percent = number_format($used / $quota * 100, 1, '.', '');
    $alt_percent = number_format($used / $quota * 100, 1);

    if ( !empty($this->settings['show_quantity_instead_of_percent']) )
      $alt = str_replace(array('%1', '%2'), array($used_text,$quota_text), _("%1 of %2"));
    else
      $alt = str_replace(array('%1', '%2'), array($alt_percent,$quota_text), _("%1% of %2"));

    return array($percent, $alt);
  }

  /**
    * Reset debug information
    *
    */
  function reset_debug()
  {
    $this->debug_info = array();
  }

  /**
    * Get debug information
    *
    * @return array The array of all debug messages 
    *               generated since the last time 
    *               the debug information was reset.
    *
    */
  function get_debug()
  {
    return $this->debug_info;
  }

  /**
    * Debug interface
    *
    * Stores debug/log information when in 
    * "troubleshoot" mode for later output
    *
    * @param string $header Log message title
    * @param string $info   Log message 
    * @param int    $type   Log type: 0 = normal message;
    *                                 1 = warning message
    *                       (OPTIONAL; default normal message type)
    *
    */
  function add_debug_info($header, $info, $type=0)
  {
    $this->debug_info[] = array($header, $info, $type);
  }

  /**
    * Prints variable contents in HTML-friendly format
    *
    * @param mixed   $data        The variable to format 
    *                             into HTML-friendly output 
    * @param boolean $return_data Whether or not to echo the
    *                             output or return it to the
    *                             caller (when FALSE, output
    *                             is sent directly from this 
    *                             function to the client)
    *                             (OPTIONAL; default is to
    *                             return data to the caller)
    *
    * @return string When $return_data is TRUE, the HTML-formatted 
    *                $data contents, otherwise nothing is returned
    *
    */
  function print_r_html($data, $return_data=TRUE)
  {
    ob_start();
    print_r($data);
    $data = ob_get_contents();
    ob_end_clean();

    $find = array("\r\n","\n\n"," ");
    $replace = array("\n","\n","&nbsp;");

    $data = nl2br(str_replace($find, $replace, $data));

    if ( !$return_data )
      echo $data;   
    else
      return $data;
  }
}
