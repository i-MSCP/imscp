<?php

/**
  * SquirrelMail Add Address Plugin
  * Copyright (c) 1999-2008 The SquirrelMail Project Team
  * Copyright (c) 2008-2009 Paul Lesniewski <paul@squirrelmail.org>,
  * Licensed under the GNU GPL. For full terms see the file COPYING.
  *
  * @package plugins
  * @subpackage add_address
  *
  */



/**
  * Register this plugin with SquirrelMail
  *
  */
function squirrelmail_plugin_init_add_address()
{

   global $squirrelmail_plugin_hooks;


   // add link to action options below message headers
   //
   $squirrelmail_plugin_hooks['read_body_header_right']['add_address']
      = 'add_address_link';


   // automatically add addresses from outgoing messages
   //
   $squirrelmail_plugin_hooks['compose_send_after']['add_address']
      = 'add_address_auto_add';


   // SM 1.4.x - show note at top of message read page
   //
   $squirrelmail_plugin_hooks['read_body_header']['add_address']
      = 'aa_show_note';


   // SM 1.5.x - show note at top of message read page
   //
   $squirrelmail_plugin_hooks['template_construct_read_headers.tpl']['add_address']
      = 'aa_show_note';


   // show options on display preferences page
   //
   $squirrelmail_plugin_hooks['optpage_loadhook_display']['add_address']
      = 'add_address_display_options';


   // configuration check
   //
   $squirrelmail_plugin_hooks['configtest']['add_address']
      = 'add_address_check_configuration';

}



/**
  * Returns info about this plugin
  *
  */
function add_address_info()
{

   return array(
                 'english_name' => 'Add Address',
                 'authors' => array(
                    'Paul Lesniewski' => array(
                       'email' => 'paul@squirrelmail.org',
                       'sm_site_username' => 'pdontthink',
                    ),
                 ),
                 'version' => '1.0.1',
                 'required_sm_version' => '1.4.0',
                 'requires_source_patch' => 0,
                 'requires_configuration' => 0,
                 'summary' => 'Helps users add addresses to their address book from incoming and outgoing messages quickly and easily.',
                 'details' => 'This plugin helps users add addresses to their address book from incoming and outgoing messages quickly and easily.  It pulls email addresses from the headers and body (only if the user chooses to scan the body) of received messages, optionally verifies the DNS records of the addresses and presents a list for editing before adding to the address book.  It can also add all the addresses found in the destination headers in outgoing messages with no user interaction whatsoever.',
                 'per_version_requirements' => array(
                    '1.5.2' => array(
                       'required_plugins' => array(
                          'abook_take' => SQ_INCOMPATIBLE,
                       )
                    ),
                    '1.5.0' => array(
                       'required_plugins' => array(
                          'abook_take' => SQ_INCOMPATIBLE,
                          'compatibility' => array(
                             'version' => '2.0.7',
                             'activate' => FALSE,
                          )
                       )
                    ),
                    '1.4.10' => array(
                       'required_plugins' => array(
                          'abook_take' => SQ_INCOMPATIBLE,
                       )
                    ),
                    '1.4.0' => array(
                       'required_plugins' => array(
                          'abook_take' => SQ_INCOMPATIBLE,
                          'compatibility' => array(
                             'version' => '2.0.7',
                             'activate' => FALSE,
                          )
                       )
                    ),
                 ),
               );

}



/**
  * Returns version info about this plugin
  *
  */
function add_address_version()
{
   $info = add_address_info();
   return $info['version'];
}



/**
  * Add link to action options below message headers
  *
  */
function add_address_link(&$links)
{
   include_once(SM_PATH . 'plugins/add_address/functions.php');
   add_address_link_do($links);
}



/**
  * Automatically add addresses from outgoing messages
  *
  */
function add_address_auto_add($args)
{
   include_once(SM_PATH . 'plugins/add_address/functions.php');
   add_address_auto_add_do($args);
}



/**
  * Show note at top of message read page
  *
  */
function aa_show_note($args)
{
   include_once(SM_PATH . 'plugins/add_address/functions.php');
   return aa_show_note_do($args);
}



/**
  * Display user configuration options on display preferences page
  *
  */
function add_address_display_options()
{
   include_once(SM_PATH . 'plugins/add_address/functions.php');
   add_address_display_options_do();
}



/**
  * Validate that this plugin is configured correctly
  *
  * @return boolean Whether or not there was a
  *                 configuration error for this plugin.
  *
  */
function add_address_check_configuration()
{
   include_once(SM_PATH . 'plugins/add_address/functions.php');
   return add_address_check_configuration_do();
}



