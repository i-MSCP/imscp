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
  * Validate that this plugin is configured correctly
  *
  * @return boolean Whether or not there was a
  *                 configuration error for this plugin.
  *
  */
function add_address_check_configuration_do()
{

   // make sure base config is available
   //
   if (!add_address_init())
   {
      do_err('Add Address plugin is missing its main configuration file', FALSE);
      return TRUE;
   }

}



/**
  * Initialize this plugin (load config values)
  *
  * @return boolean FALSE if no configuration file could be loaded, TRUE otherwise
  *
  */
function add_address_init()
{

   if (!@include_once (SM_PATH . 'config/config_add_address.php'))
      if (!@include_once (SM_PATH . 'plugins/add_address/config.php'))
         if (!@include_once (SM_PATH . 'plugins/add_address/config_default.php'))
            return FALSE;

   return TRUE;

/* ----------  This is how to do the same thing using the Compatibility plugin
   return load_config('add_address',
                      array('../../config/config_add_address.php',
                            'config.php',
                            'config_default.php'),
                      TRUE, TRUE);
----------- */

}



/**
  * Display user configuration options on display preferences page
  *
  */
function add_address_display_options_do()
{

   global $data_dir, $username, $optpage_data, $allow_dns_verification,
          $aa_dont_include_identities;

   add_address_init();

   sq_change_text_domain('add_address');

   $my_optpage_values = array();


   if ($allow_dns_verification)
   {
      // we use the legacy name "abook_take_verify" to ease
      // migration from the "abook_take" plugin to this one
      //
      $aa_verify_addrs = getPref($data_dir, $username, 'abook_take_verify', 0);
      $my_optpage_values[] = array(
         'name'          => 'abook_take_verify',
         'caption'       => _("Attempt To Verify Addresses Being Added To Address Book"),
         'type'          => SMOPT_TYPE_BOOLEAN,
         'initial_value' => $aa_verify_addrs,
         'refresh'       => SMOPT_REFRESH_NONE,
      );
   }


   $aa_search_body = getPref($data_dir, $username, 'aa_search_body', 0);
   $my_optpage_values[] = array(
      'name'          => 'aa_search_body',
      'caption'       => _("Scan Message Body For Addresses To Add To Address Book"),
      'type'          => SMOPT_TYPE_BOOLEAN,
      'initial_value' => $aa_search_body,
      'refresh'       => SMOPT_REFRESH_NONE,
   );


   $aa_dont_include_identities = getPref($data_dir, $username,
                                         'aa_dont_include_identities',
                                         $aa_dont_include_identities);
   $my_optpage_values[] = array(
      'name'          => 'aa_dont_include_identities',
      'caption'       => _("Don't include my personal email addresses"),
      'type'          => SMOPT_TYPE_BOOLEAN,
      'initial_value' => $aa_dont_include_identities,
      'refresh'       => SMOPT_REFRESH_NONE,
   );


   $aa_default_all_checked = getPref($data_dir, $username, 'aa_default_all_checked', 1);
   $my_optpage_values[] = array(
      'name'          => 'aa_default_all_checked',
      'caption'       => _("Precheck \"Add\" Checkboxes"),
      'type'          => SMOPT_TYPE_BOOLEAN,
      'initial_value' => $aa_default_all_checked,
      'refresh'       => SMOPT_REFRESH_NONE,
   );


   $aa_auto_add_sent_addrs = getPref($data_dir, $username, 'aa_auto_add_sent_addrs', 0);
   $my_optpage_values[] = array(
      'name'          => 'aa_auto_add_sent_addrs',
      'caption'       => _("Automatically Add Addresses To Address Book From Outgoing Messages"),
      'type'          => SMOPT_TYPE_BOOLEAN,
      'initial_value' => $aa_auto_add_sent_addrs,
      'refresh'       => SMOPT_REFRESH_NONE,
   );


   if (!empty($my_optpage_values))
   {  

      // 1.5.2+ has a address book section already - add our stuff to that section
      //
      if (check_sm_version(1, 5, 2))
      {
         $optpage_data['vals'][3] = array_merge($optpage_data['vals'][3], $my_optpage_values);
      }  


      // 1.4.x - add an address book section to the display options
      //         (be careful to merge with other options from other
      //         plugins)
      //
      else
      {  
         $optpage_data['grps']['address_book'] = _("Address Book");
         if (empty($optpage_data['vals']['address_book']))
            $optpage_data['vals']['address_book'] = array();
         $optpage_data['vals']['address_book'] = array_merge($optpage_data['vals']['address_book'], $my_optpage_values);
      }  

   }  
         
   sq_change_text_domain('squirrelmail');

}



/**
  * Show note at top of message read page
  *
  */
function aa_show_note_do($args)
{
   if (sqGetGlobalVar('aa_note', $aa_note, SQ_SESSION))
   {
      sqsession_unregister('aa_note');
      if (check_sm_version(1, 5, 2))
      {
         global $oTemplate;
         $oTemplate->assign('note', $aa_note);
         $output = $oTemplate->fetch('plugins/add_address/confirmation_note.tpl');
         return array('read_body_header' => $output);
      }
      else
      {
         echo html_tag('tr', html_tag('td', "<strong>$aa_note</strong>", 'center', '', ' colspan="2"'));
      }
   }
}



/**
  * Automatically add addresses from outgoing messages
  *
  */
function add_address_auto_add_do($args)
{

   // is this functionality enabled?
   //
   global $username, $data_dir;
   $aa_auto_add_sent_addrs = getPref($data_dir, $username, 'aa_auto_add_sent_addrs', 0);
   if (!$aa_auto_add_sent_addrs) return;


   // get list of new addresses
   //
   if (check_sm_version(1, 5, 2))
      $message = $args[1];
   else
      $message = $args[2];
   $addresses = aa_find_addresses($message, NULL, NULL, array('To', 'Cc', 'Bcc'));
   $addresses = build_address_import_list($addresses, FALSE);


   // initialize address book
   //
   global $aa_abook, $aa_abook_list;
   if (empty($aa_abook))
   {
      include_once(SM_PATH . 'functions/addressbook.php');
      $aa_abook = addressbook_init(FALSE);
      $aa_abook_list = NULL;
   }


   // add the addresses to the address book
   //
   $backend = $aa_abook->localbackend;
   foreach ($addresses as $address)
   {
      $address_to_add = array(
         'nickname' => $address['nickname'],
         'email' => $address['email'],
         'firstname' => $address['firstname'],
         'lastname' => $address['lastname'],
         'label' => $address['label'],
      );
      $result = $aa_abook->add($address_to_add, $backend);

      // ignore errors 
      //
//if (!$result) {sm_print_r('ERRORS',$aa_abook->error);exit;}
      //if (!$result)
      //{
      //   // remove backend name from error string
      //   //
      //   $errstr = $aa_abook->error;
      //   $errstr = ereg_replace('^\[.*\] *', '', $errstr);
      //}
   }

}



/**
  * Add link to action options below message headers
  *
  */
function add_address_link_do(&$links)
{

   global $message, $passed_id;
   sqgetGlobalVar('mailbox',   $mailbox,   SQ_FORM);

   // if no new addresses are in this message, no link is needed
   //
   $new_addresses = aa_find_addresses($message, $mailbox, $passed_id);
   if (empty($new_addresses)) return;

   $passed_ent_id = 0;
   sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_FORM);
   sqgetGlobalVar('startMessage',  $startMessage,  SQ_FORM);
   sqgetGlobalVar('view_as_html',  $view_as_html,  SQ_FORM);
   sqgetGlobalVar('account',       $account,       SQ_FORM);

   sq_change_text_domain('add_address');

   $uri = sqm_baseuri() . 'plugins/add_address/add_addresses.php?mailbox='
        . urlencode($mailbox) . '&passed_id=' . $passed_id . '&view_as_html='
        . $view_as_html . '&startMessage=' . $startMessage . '&passed_ent_id='
        . $passed_ent_id . '&account=' . $account;

   if (check_sm_version(1, 5, 2))
      $links = array_merge($links, array(
                           array('URL'  => $uri,
                                 'Text' => _("Add to Address Book"))));

   else
      echo ' | <a href="' . $uri . '">' . _("Add to Address Book") . '</a>';

   sq_change_text_domain('squirrelmail');

}



/**
  * Takes a Message object and parses it, looking for addresses
  * it can add to the user's address book from the message body
  * (only if user prefs dictate, and unless $mailbox or $passed_id
  * is NULL) as well as the following header fields (unless
  * specifically listed in the $headers parameter):
  *    From:
  *    Reply-To:
  *    Cc:
  *    Sender:
  *    Bcc:
  *    To:
  *    Mail-Followup-To:
  *
  * If any addresses are found that are not in the user's address
  * book, they are returned in an array.
  *
  * @param object $message   The message object being inspected
  * @param string $mailbox   The name of the mailbox that contains
  *                          the message being inspected.  If NULL,
  *                          the message body will not be scanned
  *                          (OPTIONAL; default NULL).
  * @param int    $passed_id The message ID of the message
  *                          (or the message's parent if it is
  *                          an attached message).  If NULL, the
  *                          message body will not be scanned
  *                          (OPTIONAL; default NULL).
  * @param array  $headers   A list of the field names to search
  *                          within (OPTIONAL; default is an
  *                          empty value, which indicates that
  *                          all the fields above will be used).
  * @param mixed $no_ident   Allows override of the user preference
  *                          setting for whether or not addresses that
  *                          are in the user's list of identities
  *                          should be included or not.  When NULL,
  *                          the user preference is obeyed, otherwise,
  *                          this value should be boolean and will
  *                          determine if identities will be removed
  *                          from the returned address list (TRUE) or
  *                          not (FALSE - they will be included if any
  *                          exist) (OPTIONAL; default is NULL).
  *
  * @return array A list of addresses that could be added to the
  *               user's address book, or an empty array if none
  *               were found.  Each address can be in one of three
  *               formats:  an AddressStructure object, a parsed
  *               address array (two elements:  email and name) or
  *               a simple email address string (no associated name).
  *
  */
function aa_find_addresses($message, $mailbox=NULL, $passed_id=NULL,
                           $headers='', $no_ident=NULL)
{

   global $data_dir, $username;


   $new_addresses = array();
   $addresses = array();


   // set up headers list
   //
   if (empty($headers))
      $headers = array('From', 'Reply-To', 'Cc', 'Sender', 'Bcc',
                       'To', 'Mail-Followup-To');


   // retrieve message body and search it if necessary
   //
   $addresses = array();
   $aa_search_body = getPref($data_dir, $username, 'aa_search_body', 0);
   if (!is_null($mailbox) && !is_null($passed_id) && $aa_search_body)
   {

      // retrieve IMAP server connection handle (or make our own)
      //
      global $imapConnection, $key, $imapServerAddress, $imapPort,
             $color, $wrap_at;
      $close_connection = FALSE;
      if (check_sm_version(1, 5, 2)) $key = FALSE;
      if (!is_resource($imapConnection))
      {
         $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
         $close_connection = TRUE;
      }



      // pull message body if needed
      //
      // gets html entity if possible, which will include
      // mailto links which is desirable for our purposes
      //
      $ent_ar = $message->findDisplayEntity(array());
      $messagebody = '';
      $cnt = count($ent_ar);
      for ($i = 0; $i < $cnt; $i++)
      {
         // we could pull only the essential code from formatBody(),
         // since we are not displaying the message, but it reduces
         // confusion and lets the core take care of the details so
         // we simply have some text that is parsable for email addresses
         //
         $messagebody .= formatBody($imapConnection, $message, $color, $wrap_at, $ent_ar[$i], $passed_id, $mailbox, TRUE);
         if ($i != $cnt-1)
            $messagebody .= "<hr />";
      }
      if ($close_connection)
         sqimap_logout($imapConnection);



      // the email regex will catch too much of links in the message
      // body to the compose page with a send_to argument that has
      // a valid email address in it, so just insert a space before
      // it to fool the regex
      //
      $messagebody = preg_replace('/(<a href=[^>]*?src\/compose.php\?[^>]*?send_to=)/', '$1 ', $messagebody);



      // always use 1.5.2+ email regular expression
      //
      if (check_sm_version(1, 5, 2))
      {
         global $Email_RegExp_Match;
         //$email_regex = str_replace('/', '\\/', $Email_RegExp_Match);
         $email_regex = $Email_RegExp_Match;
      }
      else
      {
         $IP_RegExp_Match = '\\[?[0-9]{1,3}(\\.[0-9]{1,3}){3}\\]?';
         $Host_RegExp_Match = '(' . $IP_RegExp_Match .
             '|[0-9a-z]([-.]?[0-9a-z])*\\.[a-z][a-z]+)';
         $atext = '([a-z0-9!#$&%*+/=?^_`{|}~-]|&amp;)';
         $dot_atom = $atext . '+(\.' . $atext . '+)*';
         $Email_RegExp_Match = $dot_atom . '(%' . $Host_RegExp_Match . ')?@' .
                               $Host_RegExp_Match;
         $email_regex = str_replace('/', '\\/', $Email_RegExp_Match);
      }



      // search message body for email addresses
      //
      preg_match_all('/' . $email_regex . '/', $messagebody, $addresses, PREG_PATTERN_ORDER);
      $addresses = $addresses[0];

   }



   // now pull all addresses in the message headers
   //
   foreach ($headers as $header)
   {
      $header = strtolower(str_replace('-', '_', $header));
      // DEBUG CODE: $eval = 'var_dump(aa_unify_addresses($message->rfc822_header->' . $header . '));sm_print_r(aa_unify_addresses($message->rfc822_header->' . $header . '));if (isset($message->rfc822_header->' . $header . '))'
      $eval = 'if (isset($message->rfc822_header->' . $header . '))'
            . ' $addresses = array_merge($addresses, aa_unify_addresses($message->rfc822_header->' . $header . '));';
      eval($eval);
   }



//sm_print_r('ALL ADDRESSES:', $addresses);



   // now the $addresses array can contain three types of
   // entries:  AddressStructure objects, parsed address
   // arrays (two elements:  email and name) or a simple
   // email address string (no associated name)
   //
   // loop through them all, testing if they are already
   // in the address book
   //
   global $abook_lookup_threshold, $aa_abook, $aa_abook_list;
   add_address_init();
   if (empty($aa_abook))
   {
      include_once(SM_PATH . 'functions/addressbook.php');
      $aa_abook = addressbook_init(FALSE);
      $aa_abook_list = NULL;
   }
   if (empty($aa_abook_list) && sizeof($addresses) >= $abook_lookup_threshold)
      $aa_abook_list = $aa_abook->list_addr();
   foreach ($addresses as $address)
   {

      if (is_string($address))
      {
         if (!aa_is_in_abook($aa_abook, $address, $aa_abook_list, $no_ident))
            $new_addresses[] = $address;
      }
      else if (is_array($address))
      {
         if (!aa_is_in_abook($aa_abook, $address[0], $aa_abook_list, $no_ident))
            $new_addresses[] = $address;
      }
      else // AddressStructure object
      {
         if (!aa_is_in_abook($aa_abook, $address->mailbox . '@' . $address->host, $aa_abook_list, $no_ident))
            $new_addresses[] = $address;
      }

   }



//sm_print_r('NEW ADDRESSES (NOT IN ABOOK):', $new_addresses);



   return $new_addresses;

}



/**
  * Detects format of given address(es) and returns it/them in an array
  *
  * @param mixed $addresses Can be an array of AddressStructure objects,
  *                         a single AddressStructure object, or a string
  *                         still needing the address to be parsed out.
  *
  * @return array An array containing Either AddressStructure objects
  *               or parsed address arrays (two elements: email and name)
  *
  */
function aa_unify_addresses($addresses)
{

   if (is_object($addresses))
      return array($addresses);
   else if (is_array($addresses))
      return $addresses;
   else if (!is_string($addresses))
   {
      echo 'Unexpected fatal error in Add Address plugin; please report to plugin author<hr />aa_unify_addrseses() input was:<br />';
      sm_print_r($addresses);
      exit;
   }

   if (empty($addresses))
      return array();

   // need to parse address(es) from string
   //
   if (function_exists('parseRFC822Address'))
   {
      $temp_parsed_addresses = parseRFC822Address($addresses);
      $parsed_addresses = array();
      foreach ($temp_parsed_addresses as $address)
         $parsed_addresses[] = array($address[2] . '@' . $address[3], $address[0]);
   }
   else
   {
      $parsed_addresses = parseAddress($addresses);
   }

   return $parsed_addresses;

}



/**
  * Tests if an email address is already in an address book
  *
  * @param object $abook        The address book to test
  * @param string $email        The email address to look for
  * @param array  $address_list The list of all email addresses
  *                             in the address book, in which
  *                             case we'll look in that list
  *                             instead of querying the address
  *                             book itself (OPTIONAL; default
  *                             is empty, although this list
  *                             is populated anyway in
  *                             SquirrelMail versions
  *                             less than 1.4.16)
  * @param mixed $no_identities Allows override of the user
  *                             preference setting for whether or
  *                             not addresses that are in the user's
  *                             list of identities should be included
  *                             or not.  When NULL, the user
  *                             preference is obeyed, otherwise,
  *                             this value should be boolean and
  *                             will determine if identities will
  *                             be removed from the returned address
  *                             list (TRUE) or not (FALSE - they
  *                             will be included if any exist)
  *                             (OPTIONAL; default is NULL).
  *
  * @return boolean TRUE if the address is contained in the
  *                 address book already; FALSE otherwise
  *
  */
function aa_is_in_abook($abook, $email, $address_list=NULL, $no_identities=NULL)
{

   global $username, $data_dir, $aa_dont_include_identities;
   add_address_init();


   // eliminate identities?
   //
   if (is_null($no_identities))
      $no_identities = getPref($data_dir, $username,
                               'aa_dont_include_identities',
                               $aa_dont_include_identities);


   // check identities if needed
   //
   if ($no_identities)
   {
      static $identities = NULL;
      if (is_null($identities))
      {
         include_once(SM_PATH . 'functions/identity.php');
         $identities = get_identities();
      }

      foreach ($identities as $identity)
      {
         if ($identity['email_address'] == $email)
            return TRUE;
      }
   }


   // if no address list given but not 1.4.16, get address list
   //
   if (!check_sm_version(1, 4, 16) && is_null($address_list))
         $address_list = $abook->list_addr();


   // if we have an address list, use it
   //
   if (!is_null($address_list))
   {
      // problem getting the list?  dunno - just guess yes
      //
      if (!is_array($address_list))
         return FALSE;

      // iterate the list and check...
      //
      $found = FALSE;
      foreach ($address_list as $address)
         if (strtolower($address['email']) == strtolower($email))
         {
            $found = TRUE;
            break;
         }
      return $found;
   }


   // query the abook itself
   //
   $address = $abook->lookup($email, -1, SM_ABOOK_FIELD_EMAIL);
   return is_array($address) && !empty($address);

}



/**
  * Verify a given email address by a format/syntax
  * check and optionally a DNS check against the
  * hostname portion.
  *
  * Note that the configuration setting
  * $allow_dns_verification needs to be turned on
  * to allow the DNS checks to proceed.
  *
  * @param string  $email      The address to verify
  * @param boolean $verify_dns When TRUE, a DNS check
  *                            is performed
  *
  * @return boolean TRUE when the address successfully
  *                 validates; FALSE otherwise
  *
  */
function aa_validate_email($email, $verify)
{

   // always use 1.5.2+ email regular expression
   //
   global $Email_RegExp_Match;
   if (check_sm_version(1, 5, 2))
   {
      include_once(SM_PATH . 'functions/url_parser.php');
      $email_regex = $Email_RegExp_Match;
   }
   else
   {
      $IP_RegExp_Match = '\\[?[0-9]{1,3}(\\.[0-9]{1,3}){3}\\]?';
      $Host_RegExp_Match = '(' . $IP_RegExp_Match .
          '|[0-9a-z]([-.]?[0-9a-z])*\\.[a-z][a-z]+)';
      $atext = '([a-z0-9!#$&%*+/=?^_`{|}~-]|&amp;)';
      $dot_atom = $atext . '+(\.' . $atext . '+)*';
      $Email_RegExp_Match = $dot_atom . '(%' . $Host_RegExp_Match . ')?@' .
                            $Host_RegExp_Match;
      $email_regex = $Email_RegExp_Match;
   }


   // check formatting
   //
   if (!eregi('^' . $Email_RegExp_Match . '$', $email))
      return FALSE;


   global $allow_dns_verification;
   add_address_init();


   // if no DNS check is needed, we have a successful validation
   //
   if (!$verify || !$allow_dns_verification)
      return TRUE;


   // if no DNS check is needed, we have a successful validation
   //
   return checkdnsrr(substr(strstr($email, '@'), 1), 'ANY');

}



/**
  * Builds a list of addresses ready for import to
  * the address book
  *
  * @param array $addresses The list of addresses to use to build
  *                         the import list.  Each address can be
  *                         in one of three formats:  an
  *                         AddressStructure object, a parsed
  *                         address array (two elements:  email and
  *                         name) or a simple email address string
  *                         (no associated name).
  * @param mixed $dns_check Allows override of the user preference
  *                         setting for using DNS checks to validate
  *                         email addresses.  When NULL, the user
  *                         preference is obeyed, otherwise this
  *                         value should be boolean and will determine
  *                         if DNS checks are used or not (OPTIONAL;
  *                         default is NULL - obey user preference)
  *
  * @return array A list of unique addresses, each of which has
  *               guaranteed entries for 'nickname' and (validated)
  *               'email', and hopefully 'lastname' and 'firstname'.
  *               Nicknames will be such that there are not any
  *               duplicates in the user's address book.
  *
  */
function build_address_import_list($addresses, $dns_check=NULL)
{

   global $username, $data_dir, $abook_lookup_threshold,
          $aa_abook, $aa_abook_list;


   add_address_init();



   // initialize user address book
   //
   if (empty($aa_abook))
   {
      include_once(SM_PATH . 'functions/addressbook.php');
      $aa_abook = addressbook_init(FALSE);
      $aa_abook_list = NULL;
   }
   if (empty($aa_abook_list) && sizeof($addresses) >= $abook_lookup_threshold)
   {
      $aa_abook_list = $aa_abook->list_addr();
   }



   // we use the legacy name "abook_take_verify" to ease
   // migration from the "abook_take" plugin to this one
   //
   if (is_null($dns_check))
      $dns_check = getPref($data_dir, $username, 'abook_take_verify', 0);



   // try to pre-build sensible nicknames, first and last names
   // and validate the email addresses if needed
   //
   $new_addresses = array();
   $email_list = array();
   $proposed_nicknames = array();
   $i = 0;
   sq_change_text_domain('add_address');
   foreach ($addresses as $address)
   {

      // find email address and nickname
      //
      if (is_string($address))
      {
         $new_addresses[$i]['email'] = $address;
         $new_addresses[$i]['nickname'] = '';
      }
      else if (is_array($address))
      {
         $new_addresses[$i]['email'] = $address[0];
         $new_addresses[$i]['nickname'] = trim($address[1]);
      }
      else // AddressStructure object
      {
         $new_addresses[$i]['email'] = $address->mailbox . '@' . $address->host;
         $new_addresses[$i]['nickname'] = trim($address->personal);
      }



      // validate email address
      //
      $new_addresses[$i]['valid'] = aa_validate_email($new_addresses[$i]['email'],
                                                      $dns_check);
      if (!$new_addresses[$i]['valid'])
         $new_addresses[$i]['label'] = _("Address may be invalid");
      else
         $new_addresses[$i]['label'] = '';



      // fix up nickname: use first part of email if none found,
      // replace spaces with underscores, and make sure nickname
      // is unique (keep copy of nickname w/out underscore
      // replacements for use below)
      //
      if (empty($new_addresses[$i]['nickname']))
         $new_addresses[$i]['nickname'] = substr($new_addresses[$i]['email'], 0, strpos($new_addresses[$i]['email'], '@'));
      $new_addresses[$i]['orig_nickname'] = $new_addresses[$i]['nickname'];
      $new_addresses[$i]['nickname'] = str_replace(' ', '_', $new_addresses[$i]['nickname']);
      $suffix = '';
      $j = 1;
      while (1)
      {

         // look to see if nickname is already in use by a direct
         // address book lookup
         //
         if (is_null($aa_abook_list))
            $addr = $aa_abook->lookup($new_addresses[$i]['nickname'] . $suffix);


         // look to see if nickname is already in use by using
         // cached address book list
         //
         else
         {
            // problem getting the list?  dunno - just guess nick is OK
            //
            if (!is_array($aa_abook_list))
               $addr = FALSE;

            // iterate the list and check...
            //
            else
            {
               $addr = FALSE;
               foreach ($aa_abook_list as $a)
                  if (strtolower($a['nickname']) == strtolower($new_addresses[$i]['nickname'] . $suffix))
                  {
                     $addr = TRUE;
                     break;
                  }
            }
         }


         // found an acceptable nickname?
         //
         if (empty($addr) && !in_array(strtolower($new_addresses[$i]['nickname'] . $suffix),
                                       $proposed_nicknames))
         {
            $new_addresses[$i]['nickname'] = $new_addresses[$i]['nickname'] . $suffix;
            $proposed_nicknames[] = strtolower($new_addresses[$i]['nickname']);
            break;
         }


         // increment suffix (and try again...)
         //
         $suffix = '_' . $j++;

      }
      $new_addresses[$i]['orig_nickname'] = $new_addresses[$i]['orig_nickname'] . $suffix;



      // first/last name guess: "Last, First"
      //
      if (preg_match('/^([^,]+)\s*,\s*(.+)' . $suffix . '$/', $new_addresses[$i]['orig_nickname'], $matches))
      {
         $new_addresses[$i]['lastname'] = $matches[1];
         $new_addresses[$i]['firstname'] = $matches[2];
      }


      // first/last name guess: "First Last"
      //
      else if (preg_match('/^(.+)\s+(\S+)' . $suffix . '$/', $new_addresses[$i]['orig_nickname'], $matches))
      {
         $new_addresses[$i]['firstname'] = $matches[1];
         $new_addresses[$i]['lastname'] = $matches[2];
      }


      // first/last name guess: "First"
      //
      else if (preg_match('/^(.+)' . $suffix . '$/', $new_addresses[$i]['orig_nickname'], $matches))
      {
         $new_addresses[$i]['firstname'] = $matches[1];
         $new_addresses[$i]['lastname'] = '';
      }


      // nothing found for names
      //
      else
      {
         $new_addresses[$i]['firstname'] = '';
         $new_addresses[$i]['lastname'] = '';
      }



      $email_list[$new_addresses[$i]['email']][] = $i;
      $i++;

   }

   sq_change_text_domain('squirrelmail');



   // weed out duplicates
   //
   foreach ($email_list as $indexes)
   {

      // more than one with same email address?
      //
      if (sizeof($indexes) > 1)
      {

         // try to find an entry with all fields filled out
         // and with no suffix (lower chance it's already in abook)
         // and is valid
         //
         $dont_unset = NULL;
         foreach ($indexes as $i)
         {
            if (!empty($new_addresses[$i]['firstname'])
             && !empty($new_addresses[$i]['lastname'])
             && !preg_match('/_\d+$/', $new_addresses[$i]['nickname'])
             && $new_addresses[$i]['valid'])
            {
               $dont_unset = $i;
               break;
            }
         }


         // or try to find an entry with all fields filled out
         // and with no suffix (lower chance it's already in abook)
         //
         if (is_null($dont_unset)) foreach ($indexes as $i)
         {
            if (!empty($new_addresses[$i]['firstname'])
             && !empty($new_addresses[$i]['lastname'])
             && !preg_match('/_\d+$/', $new_addresses[$i]['nickname']))
            {
               $dont_unset = $i;
               break;
            }
         }


         // or try to find an entry with all fields filled out
         // and is valid
         //
         if (is_null($dont_unset)) foreach ($indexes as $i)
         {
            if (!empty($new_addresses[$i]['firstname'])
             && !empty($new_addresses[$i]['lastname'])
             && $new_addresses[$i]['valid'])
            {
               $dont_unset = $i;
               break;
            }
         }


         // or try to find an entry with all fields filled out
         //
         if (is_null($dont_unset)) foreach ($indexes as $i)
         {
            if (!empty($new_addresses[$i]['firstname'])
             && !empty($new_addresses[$i]['lastname']))
            {
               $dont_unset = $i;
               break;
            }
         }


         // or try to find an entry with just a first name
         // and with no suffix (lower chance it's already in abook)
         // and is valid
         //
         if (is_null($dont_unset)) foreach ($indexes as $i)
         {
            if (!empty($new_addresses[$i]['firstname'])
             && !preg_match('/_\d+$/', $new_addresses[$i]['nickname'])
             && $new_addresses[$i]['valid'])
            {
               $dont_unset = $i;
               break;
            }
         }


         // or try to find an entry with just a first name
         // and with no suffix (lower chance it's already in abook)
         //
         if (is_null($dont_unset)) foreach ($indexes as $i)
         {
            if (!empty($new_addresses[$i]['firstname'])
             && !preg_match('/_\d+$/', $new_addresses[$i]['nickname']))
            {
               $dont_unset = $i;
               break;
            }
         }


         // or try to find an entry with just a first name
         // and is valid
         //
         if (is_null($dont_unset)) foreach ($indexes as $i)
         {
            if (!empty($new_addresses[$i]['firstname'])
             && $new_addresses[$i]['valid'])
            {
               $dont_unset = $i;
               break;
            }
         }


         // or try to find an entry with just a first name
         //
         if (is_null($dont_unset)) foreach ($indexes as $i)
         {
            if (!empty($new_addresses[$i]['firstname']))
            {
               $dont_unset = $i;
               break;
            }
         }


         // nothing left, just take the first one
         //
         if (is_null($dont_unset))
            $dont_unset = $indexes[0];


         // remove all addresses except the lucky chosen one
         //
         if (!is_null($dont_unset)) foreach ($indexes as $i)
            if ($i != $dont_unset)
               unset($new_addresses[$i]);

      }

   }


   return $new_addresses;

}



