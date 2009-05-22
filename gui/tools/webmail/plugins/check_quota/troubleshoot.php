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


if (file_exists('../../include/init.php'))
   include_once('../../include/init.php');
else if (file_exists('../../include/validate.php'))
{
   define('SM_PATH', '../../');
   include_once(SM_PATH . 'include/validate.php');
} 
else 
{
   chdir('..');
   define('SM_PATH', '../');
   include_once(SM_PATH . 'src/validate.php');
}

include_once(SM_PATH . 'functions/imap.php');

/* Check Quota required functions */
include_once(SM_PATH . 'plugins/check_quota/functions.php');

global $cq_cpanel_refresh;

$cq_cpanel_refresh = 1;

$check_quota = new CheckQuota();

if ( empty($check_quota->settings['troubleshoot']) )
{
  unset($check_quota);
  exit();
}

$cq_to = '<tr><td colspan="2" bgcolor="' . $color[4] . '" align="center"><font color="' . $color[8] . '"><b>';
$cq_tc = '</font></b>';
$cq_ho = '<tr><td width="230" bgcolor="' . $color[9] . '"><font color="' . $color[8] . '"><b>';
$cq_hc = '</b></font></td><td bgcolor="' . $color[0] . '">';
$cq_no = '<font color="' . $color[8] . '">';
$cq_nc = '</font>';
$cq_wo = '<font color="' . $color[2] . '"><b>';
$cq_wc = '</b></font>';
$cq_ctr = '</td></tr>' . "\n";
$cq_config_error = 0;
$cq_info = '';

displayPageHeader($color, 'None');

sq_change_text_domain('check_quota');

$cq_info .= $cq_to . _("Mandatory Settings") . $cq_tc . $cq_ctr;

// Quota Type Control

$cq_info .= $cq_ho . _("Quota Type:") . $cq_hc;

switch ($check_quota->settings['quota_type'])
{
  case 0:
    $cq_info .= $cq_no . 'UNIX' . $cq_nc;
    break;
  case 1:
    $cq_info .= $cq_no . 'IMAP' . $cq_nc;
    break;
  case 2:
    $cq_info .= $cq_no . 'cPanel' . $cq_nc;
    break;
  default:
    $cq_config_error++;
    $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
}

$cq_info .= $cq_ctr;

// Remote UNIX checking Control

if ( $check_quota->settings['quota_type'] == 0 )
{
  $cq_info .= $cq_ho . _("Check UNIX Quotas:") . $cq_hc;

  switch ($check_quota->settings['check_unix_on_remote_server'])
  {
    case 0:
      $cq_info .= $cq_no . _("On local server") . $cq_nc;
      break;
    case 1:
      $cq_info .= $cq_no . _("On remote server") . $cq_nc;
      break;
    default:
      $cq_config_error++;
      $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
  }
}

if ( $check_quota->settings['quota_type'] == 0 && !empty($check_quota->settings['check_unix_on_remote_server']) )
{
  $cq_info .= $cq_ho . _("Remote Username:") . $cq_hc;

  if ( !empty($check_quota->settings['remote_unix_user']) )
    $cq_info .= $cq_no . $check_quota->settings['remote_unix_user'] . $cq_nc;
  else
  {
    $cq_config_error++;
    $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read INSTALL for details on setting this variable.") . $cq_wc;
  }
}

if ( $check_quota->settings['quota_type'] == 0 && !empty($check_quota->settings['check_unix_on_remote_server']) )
{
  $cq_info .= $cq_ho . _("Remote Server:") . $cq_hc;

  if ( !empty($check_quota->settings['remote_unix_server']) )
    $cq_info .= $cq_no . $check_quota->settings['remote_unix_server'] . $cq_nc;
  else
  {
    $cq_config_error++;
    $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read INSTALL for details on setting this variable.") . $cq_wc;
  }
}

// Binary Control

if ( $check_quota->settings['quota_type'] == 0 )
{
  $cq_info .= $cq_ho . _("SUDO Binary:") . $cq_hc;

  if ( !empty($check_quota->settings['sudo_binary']) )
  {
    if ( empty($check_quota->settings['check_unix_on_remote_server']) )
    {
      if ( is_executable($check_quota->settings['sudo_binary']) )
        $cq_info .= $cq_no . $check_quota->settings['sudo_binary'] . $cq_nc;
      else
      {
        $cq_config_error++;
        $cq_info .= $cq_wo . _("INVALID!") . ' &quot;' . $check_quota->settings['sudo_binary'] . '&quot; ' . _("file does not exist or not executable.") . ' ' .
                   _("Please read INSTALL for details on setting this variable.") . $cq_wc;
      }
    }
    else
    {
      $cq_info .= $cq_no . $check_quota->settings['sudo_binary'] . ' - ' . _("INFO:") . ' ' . _("Remote checking is enabled, cannot check validity of this variable.") . $cq_nc;
    }
  }
  else
  {
    $cq_config_error++;
    $cq_info .=  $cq_wo . _("INVALID!") . ' ' . _("Please read INSTALL for details on setting this variable.") . $cq_wc;
  }

  $cq_info .= $cq_ctr;
}

if ( $check_quota->settings['quota_type'] == 0 && !empty($check_quota->settings['check_unix_on_remote_server']) )
{
  $cq_info .= $cq_ho . _("SSH Binary:") . $cq_hc;

  if ( !empty($check_quota->settings['ssh_binary']) )
  {
    if ( is_executable($check_quota->settings['ssh_binary']) )
      $cq_info .= $cq_no . $check_quota->settings['ssh_binary'] . $cq_nc;
    else
    {
      $cq_config_error++;
      $cq_info .= $cq_wo . _("INVALID!") . ' &quot;' . $check_quota->settings['ssh_binary'] . '&quot; ' . _("file does not exist or not executable.") . ' ' .
                 _("Please read INSTALL for details on setting this variable.") . $cq_wc;
    }
  }
  else
  {
    $cq_config_error++;
    $cq_info .=  $cq_wo . _("INVALID!") . ' ' . _("Please read INSTALL for details on setting this variable.") . $cq_wc;
  }

  $cq_info .= $cq_ctr;
}

if ( $check_quota->settings['quota_type'] == 0 )
{
  $cq_info .= $cq_ho . _("QUOTA Binary:") . $cq_hc;

  if ( !empty($check_quota->settings['quota_binary']) )
  {
    if ( empty($check_quota->settings['check_unix_on_remote_server']) )
    {
      if ( is_executable($check_quota->settings['quota_binary']) )
        $cq_info .= $cq_no . $check_quota->settings['quota_binary'] . $cq_nc;
      else
      {
        $cq_config_error++;
        $cq_info .= $cq_wo . _("INVALID!") . ' &quot;' . $check_quota->settings['quota_binary'] . '&quot; ' . _("file does not exist or not executable.") . ' ' .
                   _("Please read INSTALL for details on setting this variable.") . $cq_wc;
      }
    }
    else
    {
      $cq_info .= $cq_no . $check_quota->settings['quota_binary'] . ' - ' . _("INFO:") . ' ' . _("Remote checking is enabled, cannot check validity of this variable.") . $cq_nc;
    }
  }
  else
  {
    $cq_config_error++;
    $cq_info .=  $cq_wo . _("INVALID!") . ' ' . _("Please read INSTALL for details on setting this variable.") . $cq_wc;
  }

  $cq_info .= $cq_ctr;
}

if ( $check_quota->settings['quota_type'] == 2 )
{
  $cq_info .= $cq_ho . _("DU Binary:") . $cq_hc;

  if ( !empty($check_quota->settings['du_binary']) )
  {
    if ( is_executable($check_quota->settings['du_binary']) )
      $cq_info .= $cq_no . $check_quota->settings['du_binary'] . $cq_nc;
    else
    {
      $cq_config_error++;
      $cq_info .= $cq_wo . _("INVALID!") . ' &quot;' . $check_quota->settings['du_binary'] . '&quot; ' . _("file does not exist or not executable.") . ' ' .
                 _("Please read INSTALL for details on setting this variable.") . $cq_wc;
    }
  }
  else
  {
    $cq_config_error++;
    $cq_info .=  $cq_wo . _("INVALID!") . ' ' . _("Please read INSTALL for details on setting this variable.") . $cq_wc;
  }

  $cq_info .= $cq_ctr;
}

if ( $check_quota->settings['quota_type'] == 2 )
{
  $cq_info .= $cq_ho . _("cPanel Root Folder:") . $cq_hc;

  if ( !empty($check_quota->settings['cpanel_root']) )
  {
    if ( is_dir($check_quota->settings['cpanel_root']) )
      $cq_info .= $cq_no . $check_quota->settings['cpanel_root'] . $cq_nc;
    else
    {
      $cq_config_error++;
      $cq_info .= $cq_wo . _("INVALID!") . ' &quot;' . $check_quota->settings["cpanel_root"] . '&quot; ' . _("directory does not exist or not readable.") . ' ' .
                  _("Please read INSTALL for details on setting this variable.") . $cq_wc;
    }
  }
  else
  {
    $cq_config_error++;
    $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read INSTALL for details on setting this variable.") . $cq_wc;
  }

  $cq_info .= $cq_ctr;
}

// Use Separate IMAP Connection?

if ( $check_quota->settings['quota_type'] == 1 )
{
  $cq_info .= $cq_ho . _("Use Separate IMAP Connection:") . $cq_hc;

  switch ($check_quota->settings['use_separate_imap_connection'])
  {
    case 0:
      $cq_info .= $cq_no . _("No") . $cq_nc;
      break;
    case 1:
      $cq_info .= $cq_no . _("Yes") . $cq_nc;
      break;
    default:
      $cq_config_error++;
      $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
  }

  $cq_info .= $cq_ctr;
}

$cq_info .= $cq_to . _("Display Settings") . $cq_tc . $cq_ctr;

// Graph Type

$cq_info .= $cq_ho . _("Graph Type:") . $cq_hc;

switch ($check_quota->settings['graph_type'])
{
  case 0:
    $cq_info .= $cq_no . _("Standard HTML tables") . $cq_nc;
    break;
  case 1:
    $cq_info .= $cq_no . _("GD images");
    if ( !function_exists('imagecreate') )
    {
      $cq_config_error++;
      $cq_info .= ':' . $cq_nc . $cq_wo . ' ' . _("WARNING:") . ' ' . _("Your server does not have GD support.") . ' ' .
                  _("Graph type will revert to standard HTML tables.") . $cq_wc;
    }
    else
      $cq_info .= $cq_nc;
    break;
  case 2:
    $cq_info .= $cq_no . 'Flash graphs' . $cq_nc;
    break;
  default:
    $cq_config_error++;
    $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
}

$cq_info .= $cq_ctr;

// Graph Location

$cq_info .= $cq_ho . _("Graph Location:") . $cq_hc;

switch ($check_quota->settings['info_above_folders_list'])
{
  case 0:
    $cq_info .= $cq_no . _("Below folders list") . $cq_nc;
    break;
  case 1:
    $cq_info .= $cq_no . _("Above folders list") . $cq_nc;
    break;
  default:
    $cq_config_error++;
    $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
}

$cq_info .= $cq_ctr;

// Font Size

$cq_info .= $cq_ho . _("Font Size:") . $cq_hc;

if ( is_int(abs($check_quota->settings['font_size'])) )
  $cq_info .= $cq_no . $check_quota->settings['font_size'] . $cq_nc;
else
{
  $cq_config_error++;
  $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
}

$cq_info .= $cq_ctr;

// Graph Alignment

$cq_info .= $cq_ho . _("Graph Alignment:") . $cq_hc;

switch ($check_quota->settings['graph_alignment'])
{
  case 'left':
    $cq_info .= $cq_no . _("Left") . $cq_nc;
    break;
  case 'right':
    $cq_info .= $cq_no . _("Right") . $cq_nc;
    break;
  case 'center':
    $cq_info .= $cq_no . _("Center") . $cq_nc;
    break;
  default:
    $cq_config_error++;
    $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
}

$cq_info .= $cq_ctr;

// Use Horizontal Rules?

$cq_info .= $cq_ho . _("Use Horizontal Rules:") . $cq_hc;

switch ($check_quota->settings['use_hr'])
{
  case 0:
    $cq_info .= $cq_no . _("No") . $cq_nc;
    break;
  case 1:
    $cq_info .= $cq_no . _("Yes") . $cq_nc;
    break;
  default:
    $cq_config_error++;
    $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
}

$cq_info .= $cq_ctr;

// Show Intro Texts?

$cq_info .= $cq_ho . _("Show Intro Texts:") . $cq_hc;

switch ($check_quota->settings['show_intro_texts'])
{
  case 0:
    $cq_info .= $cq_no . _("No") . $cq_nc;
    break;
  case 1:
    $cq_info .= $cq_no . _("Yes") . $cq_nc;
    break;
  default:
    $cq_config_error++;
    $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
}

$cq_info .= $cq_ctr;

// Details Location

$cq_info .= $cq_ho . _("Quota Details Location:") . $cq_hc;

switch ($check_quota->settings['details_above_graph'])
{
  case 0:
    $cq_info .= $cq_no . _("Below quota graph") . $cq_nc;
    break;
  case 1:
    $cq_info .= $cq_no . _("Above quota graph") . $cq_nc;
    break;
  default:
    $cq_config_error++;
    $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
}

$cq_info .= $cq_ctr;

// Show Quantity or Percent?

$cq_info .= $cq_ho . _("Show Quantity or Percent:") . $cq_hc;

switch ($check_quota->settings['show_quantity_instead_of_percent'])
{
  case 0:
    $cq_info .= $cq_no . _("Show percent") . $cq_nc;
    break;
  case 1:
    $cq_info .= $cq_no . _("Show quantity (size or count)") . $cq_nc;
    break;
  default:
    $cq_config_error++;
    $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
}

$cq_info .= $cq_ctr;

// Yellow Alert Threshold

$cq_info .= $cq_ho . _("Yellow Alert Threshold:") . $cq_hc;

if ( is_int($check_quota->settings['yellow_alert_limit']) && $check_quota->settings['yellow_alert_limit'] > 0 )
  $cq_info .= $cq_no . sprintf(_("%s%%"),$check_quota->settings['yellow_alert_limit']) . $cq_nc;
else
{
  $cq_config_error++;
  $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
}

$cq_info .= $cq_ctr;

// Red Alert Threshold

$cq_info .= $cq_ho . _("Red Alert Threshold:") . $cq_hc;

if ( is_int($check_quota->settings['red_alert_limit']) && $check_quota->settings['red_alert_limit'] > 0 )
  $cq_info .= $cq_no . sprintf(_("%s%%"),$check_quota->settings['red_alert_limit']) . $cq_nc;
else
{
  $cq_config_error++;
  $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
}

$cq_info .= $cq_ctr;

// Show Yellow Alert MOTD?

$cq_info .= $cq_ho . _("Show Yellow Alert Warning:") . $cq_hc;

switch ($check_quota->settings['show_yellow_alert_motd'])
{
  case 0:
    $cq_info .= $cq_no . _("No") . $cq_nc;
    break;
  case 1:
    $cq_info .= $cq_no . _("Yes") . $cq_nc;
    break;
  default:
    $cq_config_error++;
    $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
}

$cq_info .= $cq_ctr;

// Show Red Alert MOTD?

$cq_info .= $cq_ho . _("Show Red Alert Warning:") . $cq_hc;

switch ($check_quota->settings['show_red_alert_motd'])
{
  case 0:
    $cq_info .= $cq_no . _("No") . $cq_nc;
    break;
  case 1:
    $cq_info .= $cq_no . _("Yes") . $cq_nc;
    break;
  default:
    $cq_config_error++;
    $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
}

$cq_info .= $cq_ctr;

// KB/MB Conversion

$cq_info .= $cq_ho . _("KB/MB Conversion:") . $cq_hc;

switch ($check_quota->settings['use_1000KB_per_MB'])
{
  case 0:
    $cq_info .= $cq_no . '1 MB = 1024 KB' . $cq_nc;
    break;
  case 1:
    $cq_info .= $cq_no . '1 MB = 1000 KB' . $cq_nc;
    break;
  default:
    $cq_config_error++;
    $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
}

$cq_info .= $cq_ctr;

// Show Filesystem Names?

if ( $check_quota->settings['quota_type'] == 0 )
{
  $cq_info .= $cq_ho . _("Show Filesystems:") . $cq_hc;

  switch ($check_quota->settings['show_filesystems'])
  {
    case 0:
      $cq_info .= $cq_no . _("No") . $cq_nc;
      break;
    case 1:
      $cq_info .= $cq_no . _("Yes") . $cq_nc;
      break;
    default:
      $cq_config_error++;
      $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
  }

  $cq_info .= $cq_ctr;
}

if ( ( $check_quota->settings['quota_type'] == 0 && $check_quota->settings['show_filesystems'] == 1) || $check_quota->settings['quota_type'] == 1 || $check_quota->settings['quota_type'] == 2 )
  $cq_info .= $cq_to . _("Override Settings") . $cq_tc . $cq_ctr;

// Filesystem Aliases

if ( $check_quota->settings['quota_type'] == 0 && $check_quota->settings['show_filesystems'] == 1 )
{
  $cq_info .= $cq_ho . _("Filesystem Aliases:") . $cq_hc;

  if ( empty($check_quota->settings['fs_alias']) )
    $cq_info .= $cq_no . _("No aliases set") . $cq_nc;
  else
  {
    $cq_info .= $cq_no;
    foreach($check_quota->settings["fs_alias"] as $name => $value )
    {
      $cq_info .= '&quot;' . $name . '&quot; ' . _("will be shown as") . ' &quot;' . $value . '&quot;<br />';
    }
    $cq_info .= $cq_nc;
  }

  $cq_info .= $cq_ctr;
}

// Excluded Filesystems

if ( $check_quota->settings['quota_type'] == 0 && $check_quota->settings['show_filesystems'] == 1 )
{
  $cq_info .= $cq_ho . _("Excluded Filesystems:") . $cq_hc;

  if ( empty($check_quota->settings['exclude_fs']) )
    $cq_info .= $cq_no . _("All filesystems with quota enabled will be shown.") . $cq_nc;
  else
  {
    $cq_info .= $cq_no;
    foreach($check_quota->settings['exclude_fs'] as $name => $value )
    {
      if ( !empty($value) )
        $cq_info .= '&quot;' . $name . '&quot; ' . _("will be excluded from quota information.") . '<br />';
    }
    $cq_info .= $cq_nc;
  }

  $cq_info .= $cq_ctr;
}

// Override IMAP Size Quota

if ( $check_quota->settings['quota_type'] == 1 )
{
  $cq_info .= $cq_ho . _("Override IMAP Size Quota:") . $cq_hc;

  if ( !empty($check_quota->settings['imap_size_quota']) )
    if ( is_int($check_quota->settings['imap_size_quota']) )
      $cq_info .= $cq_no . $check_quota->settings['imap_size_quota'] . ' KB' . $cq_nc;
    else
    {
      $cq_config_error++;
      $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
    }
  else
    $cq_info .= $cq_no . _("No override set") . $cq_nc;

  $cq_info .= $cq_ctr;
}

// Override IMAP Count Quota

if ( $check_quota->settings['quota_type'] == 1 )
{
  $cq_info .= $cq_ho . _("Override IMAP Count Quota:") . $cq_hc;

  if ( !empty($check_quota->settings['imap_count_quota']) )
    if ( is_int($check_quota->settings['imap_count_quota']) )
      $cq_info .= $cq_no . $check_quota->settings['imap_count_quota'] . ' ' . _("Files") . $cq_nc;
    else
    {
      $cq_config_error++;
      $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
    }
  else
    $cq_info .= $cq_no . _("No override set") . $cq_nc;

  $cq_info .= $cq_ctr;
}

// Always Refresh cPanel Info?

if ( $check_quota->settings['quota_type'] == 2 )
{
  $cq_info .= $cq_ho . _("Always Refresh cPanel:") . $cq_hc;

  switch ($check_quota->settings['always_refresh_cpanel'])
  {
    case 0:
      $cq_info .= $cq_no . _("No") . $cq_nc;
      break;
    case 1:
      $cq_info .= $cq_no . _("Yes") . $cq_nc;
      break;
    default:
      $cq_config_error++;
      $cq_info .= $cq_wo . _("INVALID!") . ' ' . _("Please read config.php file for possible values.") . $cq_wc;
  }

  $cq_info .= $cq_ctr;
}

switch ($check_quota->settings['quota_type'])
{
  case 0:
    $cq_info .= $cq_to . 'UNIX ' . _("Quota Output") . $cq_tc . $cq_ctr;
    break;
  case 1:
    $cq_info .= $cq_to . 'IMAP ' . _("Quota Output") . $cq_tc . $cq_ctr;
    break;
  case 2:
    $cq_info .= $cq_to . 'cPanel ' . _("Quota Output") . $cq_tc . $cq_ctr;
    break;
}


// add debug information to output
//
$debug_messages = $check_quota->get_debug();
foreach ($debug_messages as $message_info)
{
  $cq_info .= $cq_ho . $message_info[0] . $cq_hc;

  if ( $message_info[2] == 0 )
    $cq_info .= $cq_no . $message_info[1] . $cq_nc;
  else
  {
    $cq_config_error++;
    $cq_info .= $cq_wo . $message_info[1] . $cq_wc;
  }
}


?>
<div align="center">
<table bgcolor="<?php echo $color[9]; ?>" align="center" width="70%" cellpadding="0" cellspacing="3" border="0">
  <tr>
    <td>
      <table bgcolor="<?php echo $color[4]; ?>" width="100%" cellpadding="5" cellspacing="1" border="0">
<?php
if ( $cq_config_error > 0 )
{
  echo '<tr><td bgcolor="' . $color[4] . '" align="center"><p><font color="' . $color[2] . '"><b>' . _("WARNING:") . '</b></font> <font color="' . $color[8] . '">' . 
       _("You have") . " " . $cq_config_error . " " .
//FIXME: this all needs to go into one sentence for better i18n (L to R languages, etc)... there might be other strings that are broken like this, but I am out of time for now
       (($cq_config_error == 1)? _("error or warning"):_("errors or warnings")) . ' ' . _("in your configuration. You can find the details below.") . ' ' .
       _("Please correct") . ' ' . (($cq_config_error == 1)? _("this error or warning"):_("these errors or warnings")) . ' ' .
       _("in order to have &quot;Check Quota&quot; work properly.") . '</font></td></p></tr>' . "\n";
}
else
{
  echo '<tr><td bgcolor="' . $color[4] . '" align="center"><p><font color="' . $color[8] . '">' .
       _("Your configuration seems to be correct. &quot;Check Quota&quot; should work properly.") . '</font></p></td></tr>' . "\n";
}
?>
      </table>
    </td>
  </tr>
</table>
<br />
<table width="100%" cellpadding="4" cellspacing="3" border="0">
<?php
echo $cq_info;

sq_change_text_domain('squirrelmail');

?>
</table>
</div>
</body>
</html>
