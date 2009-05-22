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



// set up SquirrelMail environment
//
if (file_exists('../../include/init.php')) 
   include_once('../../include/init.php');
else if (file_exists('../../include/validate.php')) 
{
   define('SM_PATH', '../../');
   include_once(SM_PATH . 'include/validate.php');
} 
else 
{
// not compatible with SM version less than 1.4.0
die('Sorry, Add Address is not compatible with SquirrelMail versions less than 1.4.0');
   chdir('..');
   define('SM_PATH', '../');
   include_once(SM_PATH . 'src/validate.php');
}



global $color, $plugins, $username, $data_dir, $imapServerAddress,
       $imapPort, $javascript_on;
$default_all_checked = getPref($data_dir, $username, 'aa_default_all_checked', 1);
$abook_take_verify = getPref($data_dir, $username, 'abook_take_verify', 0);
include_once(SM_PATH . 'plugins/add_address/functions.php');
$erroneous_addresses = array();
$display_errors = array();
if (!sqGetGlobalVar('selected_backend', $selected_backend, SQ_FORM))
   $selected_backend = NULL;
$aa_import_abook = NULL;
$aa_import_msg = NULL;



// Make sure plugin is activated!
//
if (!in_array('add_address', $plugins))
   exit;



// process a form submit first
//
if (((sqGetGlobalVar('aa_import_abook', $aa_import_abook, SQ_FORM) && !empty($aa_import_abook))
 || (sqGetGlobalVar('aa_import_msg', $aa_import_msg, SQ_FORM) && !empty($aa_import_msg)))
 && sqGetGlobalVar('import_list', $import_list, SQ_FORM) && !empty($import_list))
{

   sq_change_text_domain('add_address');

   // initialize user address book
   //
   global $aa_abook, $aa_book_list;
   if (empty($aa_abook))
   {
      include_once(SM_PATH . 'functions/addressbook.php');
      $aa_abook = addressbook_init(FALSE);
      $aa_abook_list = NULL;
   }


   $errors = array();
   $total_added = 0;
   foreach ($import_list as $i)
   {

      // skip any addresses not checked
      //
      if (!sqGetGlobalVar('add_addr_' . $i, $add, SQ_FORM) || empty($add))
         continue;


      // if we have all the needed fields, try to add
      //
      if (sqGetGlobalVar('disp_number_' . $i, $disp_number, SQ_FORM)
       && sqGetGlobalVar('nickname_' . $i, $nickname, SQ_FORM)
       && sqGetGlobalVar('email_' . $i, $email, SQ_FORM)
       && sqGetGlobalVar('firstname_' . $i, $firstname, SQ_FORM)
       && sqGetGlobalVar('lastname_' . $i, $lastname, SQ_FORM)
       && sqGetGlobalVar('valid_' . $i, $valid, SQ_FORM)
       && sqGetGlobalVar('label_' . $i, $label, SQ_FORM))
      {

         $nickname = trim($nickname);
         $email = trim($email);
         $firstname = trim($firstname);
         $lastname = trim($lastname);
         $label = trim($label);
         if (empty($disp_number)) $disp_number = '?';

         // check for missing fields
         //
         if (empty($nickname))
            $errors[$i][] = sprintf(_("Missing nickname from address number %s"), $disp_number);
         if (empty($email))
            $errors[$i][] = sprintf(_("Missing email address from address number %s"), $disp_number);
         if (empty($firstname) && empty($lastname))
            $errors[$i][] = sprintf(_("Missing name from address number %s"), $disp_number);


         // no errors found?  now we can go add to the abook
         //
         if (empty($errors[$i]))
         {

            $address_to_add = array(
                                      'nickname' => $nickname,
                                      'email' => $email,
                                      'firstname' => $firstname,
                                      'lastname' => $lastname,
                                      'label' => $label,
                                   );
            if (is_null($selected_backend))
                $selected_backend = $aa_abook->localbackend;
            else
                $selected_backend = (int)$selected_backend;
            $result = $aa_abook->add($address_to_add, $selected_backend);

            // Handle error messages
            if (!$result)
            {
               // remove backend name from error string
               //
               $errstr = $aa_abook->error;
               $errstr = ereg_replace('^\[.*\] *', '', $errstr);
               $errors[$i][] = sprintf(_("%s for address number %s"), $errstr, $disp_number);
            }
            else $total_added++;

         }

      }
      else
      {

         // make sure all variables were queried (could
         // have stopped half way through above)
         //
         $valid = $nickname = $email = $firstname = $lastname = $label = $disp_number = '';
         sqGetGlobalVar('disp_number_' . $i, $disp_number, SQ_FORM);
         sqGetGlobalVar('nickname_' . $i, $nickname, SQ_FORM);
         sqGetGlobalVar('email_' . $i, $email, SQ_FORM);
         sqGetGlobalVar('firstname_' . $i, $firstname, SQ_FORM);
         sqGetGlobalVar('lastname_' . $i, $lastname, SQ_FORM);
         sqGetGlobalVar('valid_' . $i, $valid, SQ_FORM);
         sqGetGlobalVar('label_' . $i, $label, SQ_FORM);
         $nickname = trim($nickname);
         $email = trim($email);
         $firstname = trim($firstname);
         $lastname = trim($lastname);
         $label = trim($label);
         if (empty($disp_number)) $disp_number = '?';


         $errors[$i][] = sprintf(_("Some fields are missing from address number %s"), $disp_number);
      }


      // if there were errors for this address, add it to the list
      // of addresses that will be redisplayed
      //
      if (!empty($errors[$i]))
      {

         // recalculate $valid because user could have changed $email
         //
         $valid = aa_validate_email($email, $abook_take_verify);
         $erroneous_addresses[] = array(
            'nickname' => $nickname,
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'valid' => $valid,
            'label' => (!$valid && empty($label) ? _("Address may be invalid") : $label),
            'disp_number' => $disp_number,
         );

         // user had already checked to add the erroneous entries,
         // which are the only thing that will be shown this time,
         // so let's be helpful and check them again for the user
         //
         $default_all_checked = 1;

      }

   }



   // if no errors, we are done
   //
   if (empty($erroneous_addresses))
   {
      if (!empty($aa_import_abook))
         header('Location: ' . sqm_baseuri() . 'src/addressbook.php?new_bnum=' . $selected_backend);
      else
      {
/*
         if ($total_added > 1)
            // i18n: Not used
            sqsession_register(_("Addresses successfully added to address book"), 'aa_note');
         else if ($total_added == 1)
            // i18n: Not used
            sqsession_register(_("Address successfully added to address book"), 'aa_note');
         else
            // i18n: Not used
            sqsession_register(_("No addresses added to address book"), 'aa_note');
*/
         if ($total_added == 0)
         {
            // i18n: This is a special case string used when no addresses were added.
            sqsession_register(_("No addresses were added to the address book"), 'aa_note');
         }
         else
         {
            sqsession_register(sprintf(ngettext("%d address was added to the address book", "%d addresses were added to the address book", $total_added), $total_added), 'aa_note');
         }
         sqgetGlobalVar('mailbox',       $mailbox,       SQ_FORM);
         $passed_ent_id = 0;
         sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_FORM);
         sqgetGlobalVar('startMessage',  $startMessage,  SQ_FORM);
         sqgetGlobalVar('view_as_html',  $view_as_html,  SQ_FORM);
         sqgetGlobalVar('account',       $account,       SQ_FORM);
         if (sqGetGlobalVar('passed_id', $passed_id, SQ_FORM))
            // fix for Dovecot UIDs can be bigger than normal integers
            $passed_id = (preg_match('/^[0-9]+$/', $passed_id) ? $passed_id : '0');
         header('Location: ' . sqm_baseuri() . 'src/read_body.php?mailbox='
              . urlencode($mailbox) . '&passed_id=' . $passed_id . '&view_as_html='
              . $view_as_html . '&startMessage=' . $startMessage . '&passed_ent_id='
              . $passed_ent_id . '&account=' . $account);
      }
      exit;
   }



   sq_change_text_domain('squirrelmail');

}



// if the user already submitted addresses and there were
// errors, use that as our list to populate this page
//
if (!empty($erroneous_addresses))
{

   $addresses = $erroneous_addresses;

   // pull error messages together
   //
   foreach ($errors as $i => $error_list)
      foreach ($error_list as $error)
         $display_errors[] = $error;

}



// otherwise, coming in fresh, need to retrieve the target
// message and any addresses from within it
//
else
{

   // get ready to retrieve message
   //
   include_once(SM_PATH . 'functions/imap.php');
   $passed_ent_id = 0;
   $what = 0;
   sqgetGlobalVar('mailbox',       $mailbox,       SQ_FORM);
   sqgetGlobalVar('passed_ent_id', $passed_ent_id, SQ_FORM);
   sqgetGlobalVar('startMessage',  $startMessage,  SQ_FORM);
   sqgetGlobalVar('account',       $account,       SQ_FORM);
   sqgetGlobalVar('what',          $what,          SQ_FORM);
   if (sqGetGlobalVar('passed_id', $passed_id, SQ_FORM))
      // fix for Dovecot UIDs can be bigger than normal integers
      $passed_id = (preg_match('/^[0-9]+$/', $passed_id) ? $passed_id : '0');



   // try to log into IMAP and fetch the message (SM 1.5.2+)
   // (mostly ripped from src/read_body.php)
   //
   if (check_sm_version(1, 5, 2))
   {
      include_once(SM_PATH . 'functions/mailbox_display.php');
      include_once(SM_PATH . 'functions/mime.php');
      include_once(SM_PATH . 'functions/date.php');
      include_once(SM_PATH . 'functions/url_parser.php');

      // last parameter here guarantees that login errors will be handled for us
      //
      $imapConnection = sqimap_login($username, false, $imapServerAddress, $imapPort, 0);
      $aMailbox = sqm_api_mailbox_select($imapConnection, $account, $mailbox, array('setindex' => $what, 'offset' => $startMessage), array());
      if (isset($aMailbox['MSG_HEADERS'][$passed_id]['MESSAGE_OBJECT']))
         $message = $aMailbox['MSG_HEADERS'][$passed_id]['MESSAGE_OBJECT'];
      else
         $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);

      if ($passed_ent_id)
      {
         $message = $message->getEntity($passed_ent_id);
         if ($message->type0 != 'message'  && $message->type1 != 'rfc822')
            $message = $message->parent;
         $read = sqimap_run_command ($imapConnection, "FETCH $passed_id BODY[$passed_ent_id.HEADER]", true, $response, $msg, TRUE);
         $rfc822_header = new Rfc822Header();
         $rfc822_header->parseHeader($read);
         $message->rfc822_header = $rfc822_header;
      }
      else if ($message->type0 == 'message'  && $message->type1 == 'rfc822' && isset($message->entities[0]))
      {
         $read = sqimap_run_command ($imapConnection, "FETCH $passed_id BODY[1.HEADER]", true, $response, $msg, TRUE);
         $rfc822_header = new Rfc822Header();
         $rfc822_header->parseHeader($read);
         $message->rfc822_header = $rfc822_header;
      }
   }



   // try to log into IMAP and fetch the message (SM 1.4.x)
   // (mostly ripped from src/read_body.php)
   //
   else
   {
      sqgetGlobalVar('key', $key, SQ_COOKIE);

      // last parameter here guarantees that login errors will be handled for us
      //
      $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
      global $uid_support;
      if (!sqgetGlobalVar('messages', $messages, SQ_SESSION)) 
         $messages = array();
      $mbx_response   = sqimap_mailbox_select($imapConnection, $mailbox, false, false, true);
      $uidvalidity = $mbx_response['UIDVALIDITY'];

      if (!isset($messages[$uidvalidity]))
         $messages[$uidvalidity] = array();
      if (!isset($messages[$uidvalidity][$passed_id]) || !$uid_support)
         $message = sqimap_get_message($imapConnection, $passed_id, $mailbox);
      else
         $message = $messages[$uidvalidity][$passed_id];

      if ($passed_ent_id)
      {
         $message = $message->getEntity($passed_ent_id);
         if ($message->type0 != 'message'  && $message->type1 != 'rfc822')
            $message = $message->parent;
         $read = sqimap_run_command ($imapConnection, "FETCH $passed_id BODY[$passed_ent_id.HEADER]", true, $response, $msg, $uid_support);
         $rfc822_header = new Rfc822Header();
         $rfc822_header->parseHeader($read);
         $message->rfc822_header = $rfc822_header;
      }
   }



   // get potential addresses
   //
   $addresses = aa_find_addresses($message, $mailbox, $passed_id);



   // get a list that is ready for import
   //
   $addresses = build_address_import_list($addresses);

}



//sm_print_r('HERE IS YOUR FINAL LIST', $addresses);



// build backend list
//
global $aa_abook, $aa_book_list;
if (empty($aa_abook))
{
   include_once(SM_PATH . 'functions/addressbook.php');
   $aa_abook = addressbook_init(FALSE);
   $aa_abook_list = NULL;
}
$backends = array();
$backend_list = $aa_abook->get_backend_list();
foreach ($backend_list as $backend)
   if (!empty($backend->writeable))
      $backends[$backend->bnum] = $backend->sname;
if (sizeof($backends) < 2)
   $backends = array();



displayPageHeader($color, '');
sq_change_text_domain('add_address');



// print out page with import widgets
//
if (check_sm_version(1, 5, 2))
{
   global $oTemplate;
   $oTemplate->assign('color', $color);
   $oTemplate->assign('javascript_on', $javascript_on);
   $oTemplate->assign('addresses', $addresses);
   $oTemplate->assign('backends', $backends);
   $oTemplate->assign('selected_backend', $selected_backend);
   $oTemplate->assign('display_errors', $display_errors);
   $oTemplate->assign('error_header_style', '');
   $oTemplate->assign('default_all_checked', $default_all_checked);
   $oTemplate->display('plugins/add_address/import.tpl');
   $oTemplate->display('footer.tpl');
}
else
{

   $error_header_style = ' style="font-weight: bold; color: ' . $color[2] . '"';

   // we can still use the template file - just trick
   // the one from the default template set
   //
   global $t;
   $t = array(); // no need to put config vars herein, they are already global
// (sq_)htmlspecialchars unto all or part of $addresses??

   include_once(SM_PATH . 'plugins/add_address/templates/default/import.tpl');
   echo '</body></html>';

}



