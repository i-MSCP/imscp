<?php
/**
 * gpg_view_verify_text.php -- Displays the signature verification
 *
 * Copyright (c) 1999-2003 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Who knows what this file does. However PUT IT HERE DID NOT PUT
 * A SINGLE FREAKING COMMENT IN! Whoever is responsible for this,
 * be very ashamed.
 *
 * $Id: gpg_view_verify_text.php,v 1.2 2004/01/05 14:32:53 alexl Exp $
 */

/* Path for SquirrelMail required files. */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/global.php');
require_once(SM_PATH . 'functions/mime.php');
require_once(SM_PATH . 'functions/html.php');

sqgetGlobalVar('key',        $key,          SQ_COOKIE);
sqgetGlobalVar('username',   $username,     SQ_SESSION);
sqgetGlobalVar('onetimepad', $onetimepad,   SQ_SESSION);
sqgetGlobalVar('delimiter',  $delimiter,    SQ_SESSION);
sqgetGlobalVar('QUERY_STRING', $QUERY_STRING, SQ_SERVER);
sqgetGlobalVar('messages', $messages);
sqgetGlobalVar('passed_id', $passed_id, SQ_GET);
//sqgetGlobalVar('gpgverifyinfo',$info,SQ_SESSION);
sqgetGlobalVar('sig_ent_id',$sig_ent_id,SQ_GET);
sqgetGlobalVar('gpgverifyinfo',$info,SQ_SESSION);
if ( sqgetGlobalVar('mailbox', $temp, SQ_GET) ) {
  $mailbox = $temp;
}
if ( !sqgetGlobalVar('ent_id', $ent_id, SQ_GET) ) {
  $ent_id = '';
}

$msg_url = '../../src/read_body.php?' . $QUERY_STRING;
$msg_url = set_url_var($msg_url, 'ent_id', 0);

$body = $info[$sig_ent_id];

displayPageHeader($color, 'None');

/**
 * set the localization variables
 * Now tell gettext where the locale directory for your plugin is
 * this is in relation to the src/ directory
 */
bindtextdomain('gpg', SM_PATH . 'plugins/gpg/locale');
/* Switch to your plugin domain so your messages get translated */
textdomain('gpg');

echo "<BR><TABLE WIDTH=\"100%\" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">".
     "<B><CENTER>".
     _("Viewing signature verification output for attachment: ") . $filename . " - ";	//That $filename is undefined
echo '<a href="'.$msg_url.'">'. _("View message") . '</a>';

$dwnld_url = '../../src/download.php?'. $QUERY_STRING.'&amp;absolute_dl=true';
echo '</b></td><tr><tr><td><CENTER><A HREF="'.$dwnld_url. '">'.
     _("Download signed attachment").
     "</A></CENTER><BR>".
     "</CENTER></B>".
     "</TD></TR></TABLE>".
     "<TABLE WIDTH=\"98%\" BORDER=0 CELLSPACING=0 CELLPADDING=2 ALIGN=CENTER><TR><TD BGCOLOR=\"$color[0]\">".
     "<TR><TD BGCOLOR=\"$color[4]\"><TT>";

 /*   if (isset($languages[$squirrelmail_language]['XTRA_CODE']) &&
        function_exists($languages[$squirrelmail_language]['XTRA_CODE'])) {
        if (mb_detect_encoding($body) != 'ASCII') {
            $body = $languages[$squirrelmail_language]['XTRA_CODE']('decode', $body);
        }
    }
*/
//    $body = MagicHTML( $body, $passed_id, $message, $mailbox);

translateText($body, $wrap_at, $charset);	//That $charset is undefined
echo $body . '</TT></TD></TR></TABLE>';

/* Switch back to the SquirrelMail domain */
bindtextdomain('squirrelmail', SM_PATH . 'locale');
textdomain('squirrelmail');

if (function_exists('DisplayPageFooter'))	//Be ahead of our time ;)
    DisplayPageFooter();
else
    echo '</body></html>';

/**
 * $Log: gpg_view_verify_text.php,v $
 * Revision 1.2  2004/01/05 14:32:53  alexl
 * Add textdomain switching and closing page tags.
 *
 * Revision 1.1  2003/12/02 01:08:12  brian
 * -New file to mimic view of text attachments in squirrelmail
 * -Displays gpg signature verification output
 *
 */
?>