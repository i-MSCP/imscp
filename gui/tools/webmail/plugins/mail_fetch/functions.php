<?php

/**
 **  mail_fetch/functions.php
 **
 **  Copyright (c) 1999-2006 The SquirrelMail Project Team
 **  Licensed under the GNU GPL. For full terms see the file COPYING.
 **
 **  Functions for the mailfetch plugin.
 **
 **  Original code from LexZEUS <lexzeus@mifinca.com>
 **  and josh@superfork.com (extracted from php manual)
 **  Adapted for MailFetch by Philippe Mingo <mingo@rotedic.com>
 **
 **  $Id$
 **/

/** declare plugin globals */
global $mail_fetch_allow_unsubscribed;

/**
 * Controls use of unsubscribed folders in plugin
 * @global boolean $mail_fetch_allow_unsubscribed
 * @since 1.5.1 and 1.4.5
 */
$mail_fetch_allow_unsubscribed = false;

function hex2bin( $data ) {
    /* Original code by josh@superfork.com */

    $len = strlen($data);
    $newdata = '';
    for( $i=0; $i < $len; $i += 2 ) {
        $newdata .= pack( "C", hexdec( substr( $data, $i, 2) ) );
    }
    return $newdata;
}

function mf_keyED( $txt ) {
    global $MF_TIT;

    if( !isset( $MF_TIT ) ) {
        $MF_TIT = "MailFetch Secure for SquirrelMail 1.x";
    }

    $encrypt_key = md5( $MF_TIT );
    $ctr = 0;
    $tmp = "";
    for( $i = 0; $i < strlen( $txt ); $i++ ) {
        if( $ctr == strlen( $encrypt_key ) ) $ctr=0;
        $tmp.= substr( $txt, $i, 1 ) ^ substr( $encrypt_key, $ctr, 1 );
        $ctr++;
    }
    return $tmp;
}

function encrypt( $txt ) {
    srand( (double) microtime() * 1000000 );
    $encrypt_key = md5( rand( 0, 32000 ) );
    $ctr = 0;
    $tmp = "";
    for( $i = 0; $i < strlen( $txt ); $i++ ) {
        if ($ctr==strlen($encrypt_key)) $ctr=0;
        $tmp.= substr($encrypt_key,$ctr,1) .
            (substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1));
        $ctr++;
    }
    return bin2hex( mf_keyED( $tmp ) );
}

function decrypt( $txt ) {
    $txt = mf_keyED( hex2bin( $txt ) );
    $tmp = '';
    for ( $i=0; $i < strlen( $txt ); $i++ ) {
        $md5 = substr( $txt, $i, 1 );
        $i++;
        $tmp.= ( substr( $txt, $i, 1 ) ^ $md5 );
    }
    return $tmp;
}

/**
 * check mail folder
 * @param stream $imap_stream imap connection resource
 * @param string $imap_folder imap folder name
 * @return boolean true, when folder can be used to store messages.
 * @since 1.5.1 and 1.4.5
 */
function mail_fetch_check_folder($imap_stream,$imap_folder) {
    global $mail_fetch_allow_unsubscribed;

    // check if folder is subscribed or only exists.
    if (sqimap_mailbox_is_subscribed($imap_stream,$imap_folder)) {
        $ret = true;
    } elseif ($mail_fetch_allow_unsubscribed && sqimap_mailbox_exists($imap_stream,$imap_folder)) {
        $ret = true;
    } else {
        $ret = false;
    }

    // make sure that folder can store messages
    if ($ret && mail_fetch_check_noselect($imap_stream,$imap_folder)) {
        $ret = false;
    }

    return $ret;
}

/**
 * Checks if folder is noselect (can't store messages)
 * 
 * Function does not check if folder subscribed.
 * @param stream $imap_stream imap connection resource
 * @param string $imap_folder imap folder name
 * @return boolean true, when folder has noselect flag. false in any other case.
 * @since 1.5.1 and 1.4.5
 */
function mail_fetch_check_noselect($imap_stream,$imap_folder) {
    $boxes=sqimap_mailbox_list($imap_stream);
    foreach($boxes as $box) {
        if ($box['unformatted']==$imap_folder) {
            return (bool) check_is_noselect($box['raw']);
        }
    }
    return false;
}
?>