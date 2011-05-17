<?php

/**
  * SquirrelMail Message Flags & Icons Plugin
  * Copyright (c) 2002-2007 Robert Jaakke <robert@jaakke.com>
  * Copyright (c) 2003-2009 Paul Lesniewski <paul@squirrelmail.org>
  * Licensed under the GNU GPL. For full terms see the file COPYING.
  *
  * @package plugins
  * @subpackage msg_flags
  *
  */


   
/**
  * Register this plugin with SquirrelMail
  *
  */
function squirrelmail_plugin_init_msg_flags() 
{
}



/**
  * Returns info about this plugin
  *
  */
function msg_flags_info()
{

   return array(
                 'english_name' => 'Message Flags & Icons',
                 'authors' => array(
                    'Paul Lesniewski' => array(
                       'email' => 'paul@squirrelmail.org',
                       'sm_site_username' => 'pdontthink',
                    ),
                    'Robert Jaakke' => array(
                       'email' => 'robert@jaakke.com',
                    ),
                 ),
                 'version' => '1.4.20',
                 'required_sm_version' => '1.4.3',
                 'requires_configuration' => 0,
                 'requires_source_patch' => 1,
                 'summary' => 'Adds flag buttons and icons that indicate various message statuses on the message list page.',
                 'details' => 'This plugin/modification provides several visual enhancements for the SquirrelMail interface, such as using small envelope icons to indicate new and read messages, as well as which messages have been replied to, forwarded, etc.  The folder list also has a few icons added to it.<br /><br /> Additionally, this plugin adds functionality that allows users to flag their messages (by adding "Flag"/"Unflag" buttons to the message list screen).<br /><br />PLEASE NOTE that this plugin comes integrated with SquirrelMail as of version 1.5.0, and should not be installed separately therein.',
                 'per_version_requirements' => array(
                    '1.5.0' => SQ_INCOMPATIBLE,
                    '1.4.10' => array(
                       'required_plugins' => array()
                    ),
                    '1.4.3' => array(
                       'required_plugins' => array(
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
function msg_flags_version()
{

   $info = msg_flags_info();
   return $info['version'];
}



