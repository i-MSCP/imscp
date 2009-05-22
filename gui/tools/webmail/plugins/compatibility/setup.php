<?php

/**
  * SquirrelMail Compatibility Plugin
  * Copyright (c) 2004-2009 Paul Lesniewski <paul@squirrelmail.org>
  * Licensed under the GNU GPL. For full terms see the file COPYING.
  *
  * @package plugins
  * @subpackage compatibility
  *
  */



/**
  * Returns info about this plugin
  *
  */
function compatibility_info()
{

   return array(
                 'english_name' => 'Compatibility',
                 'authors' => array(
                    'Paul Lesniewski' => array(
                       'email' => 'paul@squirrelmail.org',
                       'sm_site_username' => 'pdontthink',
                    ),
                 ),
                 'version' => '2.0.14',
                 'required_sm_version' => '1.0',
                 'requires_configuration' => 0,
                 'requires_source_patch' => 2,
                 'required_plugins' => array(),
                 'per_version_requirements' => array(
                    '1.5.1' => array(
                       'requires_source_patch' => 0,
                    ),
                    '1.5.0' => array(
                       'requires_source_patch' => 1,
                    ),
                    '1.4.13' => array(
                       'requires_source_patch' => 0,
                    ),
                    '1.0.0' => array(
                       'requires_source_patch' => 1,
                    ),
                 ),
                 'summary' => 'Allows plugins to remain compatible with all versions of SquirrelMail.',
                 'details' => 'This plugin allows any other plugin access to the functions and special variables needed to make it backward (and forward) compatible with most versions of SM in wide use.  This eliminates the need for duplication of certain functions throughout many plugins.  It also provides functionality that helps check that plugins have been installed and set up correctly.',
               );

}



/**
  * Returns version info about this plugin
  *
  */
function compatibility_version()
{

   $info = compatibility_info();
   return $info['version'];

}



