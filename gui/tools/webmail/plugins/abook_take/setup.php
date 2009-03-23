<?php

/**
 * setup.php
 *
 * Copyright (c) 1999-2006 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Address Take -- steals addresses from incoming email messages. Searches
 * the To, Cc, From and Reply-To headers, also searches the body of the
 * message.
 *
 * $Id$
 */

if (!defined('SM_PATH'))  {
    define('SM_PATH','../../');
}

/* SquirrelMail required files. */
require_once(SM_PATH . 'functions/url_parser.php');

function squirrelmail_plugin_init_abook_take()
{
    global $squirrelmail_plugin_hooks;
  
    $squirrelmail_plugin_hooks['read_body_bottom']['abook_take'] = 'abook_take_read';
    $squirrelmail_plugin_hooks['loading_prefs']['abook_take'] = 'abook_take_pref';
    $squirrelmail_plugin_hooks['options_display_inside']['abook_take'] = 'abook_take_options';
    $squirrelmail_plugin_hooks['options_display_save']['abook_take'] = 'abook_take_save';
}

function valid_email ($email, $verify)
{
    global $Email_RegExp_Match;
  
    if (! eregi('^' . $Email_RegExp_Match . '$', $email))
        return false;
    
    if (! $verify)
        return true;

    return checkdnsrr(substr(strstr($email, '@'), 1), 'ANY') ;
}

function abook_take_read_string($str)
{
    global $abook_found_email, $Email_RegExp_Match;

    while (eregi('(' . $Email_RegExp_Match . ')', $str, $hits))
    {
        $str = substr(strstr($str, $hits[0]), strlen($hits[0]));
        if (! isset($abook_found_email[$hits[0]]))
        {
            echo '<input type="hidden" name="email[]" value="' .
                 htmlspecialchars($hits[0]) . "\" />\n";
            $abook_found_email[$hits[0]] = 1;
        }
    }

    return;
}

function abook_take_read_array($array)
{
    foreach ($array as $item)
        abook_take_read_string($item->getAddress());
}

function abook_take_read()
{
    global $message;

    echo '<br /><form action="../plugins/abook_take/take.php" method="post"><center>'."\n";

    if (isset($message->rfc822_header->reply_to))
        abook_take_read_array($message->rfc822_header->reply_to);
    if (isset($message->rfc822_header->from))
        abook_take_read_array($message->rfc822_header->from);
    if (isset($message->rfc822_header->cc))
        abook_take_read_array($message->rfc822_header->cc);
    if (isset($message->rfc822_header->to))
        abook_take_read_array($message->rfc822_header->to);

    echo '<input type="submit" value="' . _("Take Address") . '" />' .
         '</center></form>';
}

function abook_take_pref()
{ 
    global $username, $data_dir, $abook_take_verify;

    $abook_take_verify = getPref($data_dir, $username, 'abook_take_verify');
}

function abook_take_options()
{
    global $abook_take_verify;

    echo '<tr>' . html_tag('td',_("Address Book Take:"),'right','','nowrap') . "\n" .
         '<td><input name="abook_take_abook_take_verify" type="checkbox"';
    if (isset($abook_take_verify) && $abook_take_verify)
	echo ' checked';
    echo ' /> ' . _("Try to verify addresses") . "</td></tr>\n";
}


function abook_take_save()
{
    global $username, $data_dir;
  
    if (sqgetGlobalVar('abook_take_abook_take_verify', $abook_take_abook_take_verify, SQ_POST)) 
        setPref($data_dir, $username, 'abook_take_verify', '1');
    else 
        setPref($data_dir, $username, 'abook_take_verify', '');
}

?>