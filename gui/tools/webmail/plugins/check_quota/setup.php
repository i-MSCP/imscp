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
  * Register this plugin with SquirrelMail
  *
  */
function squirrelmail_plugin_init_check_quota()
{
  global $squirrelmail_plugin_hooks;

    
  // For SquirrelMail versions 1.5.1 and below
  //
  $squirrelmail_plugin_hooks['left_main_before']['check_quota'] 
    = 'check_quota_graph_before_do';
  $squirrelmail_plugin_hooks['left_main_after']['check_quota'] 
    = 'check_quota_graph_after_do';
  $squirrelmail_plugin_hooks['right_main_after_header']['check_quota'] 
    = 'check_quota_motd_do';


  // For SquirrelMail versions 1.5.2 and above
  //
  $squirrelmail_plugin_hooks['template_construct_left_main.tpl']['check_quota'] 
    = 'check_quota_graph_do';
  $squirrelmail_plugin_hooks['template_construct_motd.tpl']['check_quota'] 
    = 'check_quota_motd_do';


  // For all SquirrelMail versions
  //
  $squirrelmail_plugin_hooks['optpage_register_block']['check_quota'] 
    = 'check_quota_optpage_register_block_do';
  $squirrelmail_plugin_hooks['configtest']['check_quota']
    = 'check_quota_check_configuration_do';
}



/**
  * Returns info about this plugin
  *
  */
function check_quota_info()
{
   return array(
                'english_name' => 'Check Quota',
                'authors' => array(
                   'Kerem Erkan' => array(
                      'email' => 'kerem@keremerkan.net',
                      'website' => 'http://blog.keremerkan.net',
                      'sm_site_username' => 'kerem.erkan',
                   ),
                   'Paul Lesniewski' => array(
                      'email' => 'paul@squirrelmail.org',
                      'sm_site_username' => 'pdontthink',
                   ),
                ),
                'version' => '2.2',
                'required_sm_version' => '1.4.0',
                'summary' => 'Checks and display users\' mail quota status.',
                'details' => 'This plugin will check and display users\' mail quota status.  Current and maximum quota usage is displayed in easy-to-read graphical format.  Optional warnings may also be displayed to users who are nearing their quota allocation when they log in (where the "Message Of The Day" would normally be displayed).  This plugin is compatible with three types of mail quota systems: UNIX (filesystem), IMAP-based, and cPanel quotas.',
                'requires_configuration' => 1,
                'requires_source_patch' => 0,
                'other_requirements' => 'IMAP, UNIX or cPanel quotas enabled on server',
                'external_project_uri' => 'http://blog.keremerkan.net',
                'required_plugins' => array(
                   'compatibility' => array(
                      'version' => '2.0.7',
                      'activate' => FALSE,
                   )
                )
               );
}


/**
  * Returns version info about this plugin
  *
  */
function check_quota_version()
{
  $info = check_quota_info();
  return $info['version'];
}


/**
  * Displays quota graph above folder list (SM 1.4.x)
  *
  */
function check_quota_graph_before_do()
{
  include_once(SM_PATH . 'plugins/check_quota/functions.php');
  check_quota_graph_before();
}


/**
  * Displays quota graph below folder list (SM 1.4.x)
  *
  */
function check_quota_graph_after_do()
{
  include_once(SM_PATH . 'plugins/check_quota/functions.php');
  check_quota_graph_after();
}



/**
  * Displays quota warnings in MOTD
  *
  */
function check_quota_motd_do($args)
{
  include_once(SM_PATH . 'plugins/check_quota/functions.php');
  return check_quota_motd($args);
}



/**
  * Displays quota graph above/below folder list (SM 1.5.x)
  *
  */
function check_quota_graph_do()
{
  include_once(SM_PATH . 'plugins/check_quota/functions.php');
  return check_quota_graph();
}



/**
  * Displays Check Quota block on Options page
  *
  */
function check_quota_optpage_register_block_do()
{
  include_once(SM_PATH . 'plugins/check_quota/functions.php');
  check_quota_optpage_register_block();
}



/**
  * Validate that this plugin is configured correctly
  *
  * @return boolean Whether or not there was a
  *                 configuration error for this plugin.
  *
  */
function check_quota_check_configuration_do()
{
  include_once(SM_PATH . 'plugins/check_quota/functions.php');
  return check_quota_check_configuration();
}
