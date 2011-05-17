<?php
/**
 * gpgdecrypt.mod
 * -----------
 * This file creates the decrypted message view
 * The goal is to create a page with as much of the functionality of
 * the read_body.php page as possible, so the user doesn't really
 * notice that this is a seperate page.
 *
 * Copyright (c) 2002-2005 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Portions of this code copyright (c) 1999-2005 the Squirrelmail Development Team
 *
 * $Id$
 *
 * @todo change the file includes to set SM_PATH
 */
/*********************************************************************/
if (!defined ('SM_PATH')){
    if (file_exists('./gpg_encrypt_functions.php')){
        define ('SM_PATH' , '../../');
    } elseif (file_exists('../gpg_encrypt_functions.php')){
        define ('SM_PATH' , '../../../');
    } elseif (file_exists('../plugins/gpg/gpg_encrypt_functions.php')){
        define ('SM_PATH' , '../');
    } else echo "unable to define SM_PATh in gpgdecrypt.mod, exiting abnormally";
}

require_once(SM_PATH.'plugins/gpg/gpg_encrypt_functions.php');
require_once(SM_PATH.'plugins/gpg/gpg_execute.php');
require_once(SM_PATH.'plugins/gpg/gpg_hook_functions.php');
require_once(SM_PATH.'functions/imap.php');
require_once(SM_PATH.'functions/mime.php');
require_once(SM_PATH.'functions/url_parser.php');
require_once(SM_PATH.'config/config.php');

global $debug;
global $charset;
global $draft_folder;
global $startMessage;
global $sort;
global $username;
global $excl_ar;

$passphrase     = $_POST['passphrase'];
$passed_id      = (int) $_POST['passed_id'];
$passed_ent_id  = (int) $_POST['passed_ent_id'];
$mailbox        = $_POST['mailbox'];
$prev           = $_POST['prevmsg'];
$next           = $_POST['nextmsg'];
$username       = $_SESSION['username'];
$key            = $_COOKIE['key'];
$onetimepad     = $_SESSION['onetimepad'];
$base_uri       = $_SESSION['base_uri'];
$delimiter      = $_SESSION['delimiter'];
$gpg_encrypted_message = $_SESSION['gpg_encrypted_message'];
$startMessage   = $_POST['startMessage'];
$sort       = $_POST['sort'];
if ($debug) {
    echo "<br>username = $username \n"
         . "<br>passed_id = $passed_id \n"
         . "<br>mailbox = $mailbox \n";
}

/*********************************************************************/
/**
 * function decryptmenuBar
 *
 * This function is stolen shamelessly from read_body.php
 *
 * Eventually, SM core will create templates, and then we'll be able
 * to call these functions directly, but until then...
 *
 * @param  integer $debug
 * @param  string  $mailbox
 * @param  integer $passed_id
 * @param  integer $passed_ent_id
 * @param  integer $next
 * @param  integer $prev
 * @param  string  $message
 * @param  handle  $imapConnection
 * @return string  $menubar
 *
 */
function decryptmenuBar ($debug, $mailbox, $passed_id, $passed_ent_id, $next, $prev, $message, $imapConnection){

    global $base_uri, $color, $draft_folder;
    global $startMessage;
    global $imapConnection;
    global $sort;
    global $excl_ar;
    global $where, $what;
    global $data_dir, $username;
    if ($debug) {
        echo '<br>Passed ID: '.$passed_id ."\n";
        echo '<br>Passed Entity ID: '.$passed_ent_id."\n";
        echo '<br>Mailbox: '.$mailbox."\n";
    };

    //initialize our return string
    $menubar = '';
    $s ="\n\n";

    $subject = decodeHeader($message->rfc822_header->subject, false, false);

    $topbar_delimiter = '&nbsp;|&nbsp;';
    $urlMailbox = urlencode($mailbox);
    $url_subj = urlencode(trim($message->rfc822_header->subject));
    $urlMailbox = urlencode($mailbox);
    $url_replyto = '';
    //set our reply to strings
    $from_name = $message->rfc822_header->getAddr_s('from');
    if (!$from_name) {
        $from_name = $message->rfc822_header->getAddr_s('sender');
        if (!$from_name) {
            $from_name = _("Unknown sender");
        }
    }

    $compose_new_win = getPref($data_dir,$username,'compose_new_win');
    if ($compose_new_win == '1') {
        $link_open  = '<a href="javascript:void(0)" onclick="comp_in_new(\'';
        $link_close = '\')">';
    } else {
        $link_open  = '<a href="';
        $link_close = '">';
    }

    $from_name = decodeHeader($from_name,false,false);
    $url_replyto = urlencode($from_name);


    /**
    * 3) get the addresses.
    */
    $url_replytoall_ar = $message->rfc822_header->getAddr_a(array('to','cc'), $excl_ar);
    $url_to_ar  = $message->rfc822_header->getAddr_a(array('to'), $excl_ar);
    $url_cc_ar  = $message->rfc822_header->getAddr_a(array('cc'), $excl_ar);
    $url_bcc_ar = $message->rfc822_header->getAddr_a(array('bcc'), $excl_ar);

    /**
    * 4) generate the string.
    */
    //reply to all
    $url_replytoallcc = '';
    foreach( $url_replytoall_ar as $email => $personal) {
      if ($personal) {
         // if personal name contains address separator then surround
         // the personal name with double quotes.
         if (strpos($personal,',') !== false) {
             $personal = '"'.$personal.'"';
         }
         $url_replytoallcc .= ", $personal <$email>";
      } else {
         $url_replytoallcc .= ', '. $email;
      }
    }
    $url_replytoallcc = substr($url_replytoallcc,2);
    $url_replytoallcc = urlencode($url_replytoallcc);

    //to
    $url_to = '';
    foreach( $url_to_ar as $email => $personal) {
      if ($personal) {
         // if personal name contains address separator then surround
         // the personal name with double quotes.
         if (strpos($personal,',') !== false) {
             $personal = '"'.$personal.'"';
         }
         $url_to .= ", $personal <$email>";
      } else {
         $url_to .= ', '. $email;
      }
    }
    $url_to = substr($url_to,2);
    $url_to = urlencode($url_to);

    //cc
    $url_cc = '';
    foreach( $url_cc_ar as $email => $personal) {
      if ($personal) {
         // if personal name contains address separator then surround
         // the personal name with double quotes.
         if (strpos($personal,',') !== false) {
             $personal = '"'.$personal.'"';
         }
         $url_cc .= ", $personal <$email>";
      } else {
         $url_cc .= ', '. $email;
      }
    }
    $url_cc = substr($url_cc,2);
    $url_cc = urlencode($url_cc);

    //bcc
    $url_bcc = '';
    foreach( $url_bcc_ar as $email => $personal) {
      if ($personal) {
         // if personal name contains address separator then surround
         // the personal name with double quotes.
         if (strpos($personal,',') !== false) {
             $personal = '"'.$personal.'"';
         }
         $url_bcc .= ", $personal <$email>";
      } else {
         $url_bcc .= ', '. $email;
      }
    }
    $url_bcc = substr($url_bcc,2);
    $url_bcc = urlencode($url_bcc);

    //start to build out menu bar string $s
    $s .= '<table width="100%" cellpadding="3" cellspacing="0" align="center"'.
         ' border="0" bgcolor="'.$color[9].'"><tr>' .
         html_tag( 'td', '', 'left', '', 'width="33%"' ) . '<small>';

    $msgs_url = $base_uri . 'src/';

    /*
     * this displays the search results link...
     * to use this we would have to pass $where and $what
     */
    if (isset($where) && isset($what)) {
        $msgs_url .= 'search.php?where=' . urlencode($where) .
                     '&amp;what=' . urlencode($what) . '&amp;mailbox=' . $urlMailbox;
        $msgs_str  = _("Search results");
    } else {
        //display the message list link... we might use only this one.
        $msgs_url .= 'right_main.php?sort=' . $sort . '&amp;startMessage=' .
                     $startMessage . '&amp;mailbox=' . $urlMailbox;
        $msgs_str  = _("Message List");
    }

    $s .= '<a href="' . $msgs_url . '">' . $msgs_str . '</a>';

    $delete_url = $base_uri . 'src/delete_message.php?mailbox=' . $urlMailbox .
                  '&amp;message=' . $passed_id . '&amp;';
    if (!(isset($passed_ent_id) && $passed_ent_id)) {
        if ($where && $what) {
            $delete_url .= 'where=' . urlencode($where) . '&amp;what=' . urlencode($what);
        } else {
            $delete_url .= 'sort=' . $sort . '&amp;startMessage=' . $startMessage;
        }
        $s .= $topbar_delimiter;
        $s .= '<a href="' . $delete_url . '">' . _("Delete") . '</a>';
    }
    if ($mailbox == $draft_folder) {
        $comp_uri = $base_uri . 'src/compose.php?' .
                                '&amp;mailbox=' . $urlMailbox;

        $resume_uri     = $comp_uri
                        . "&amp;send_to=$url_to"
                        . "&amp;send_to_cc=$url_cc"
                        . "&amp;send_to_bcc=$url_bcc"
                        . "&amp;subject=$url_subj"
                        . '&amp;delete_draft=' . $passed_id
                        . '&amp;smaction=draft;'
                        . '&amp;gpgreply=1&amp;';

        $comp_alt_string = _("Resume Draft");
        $s .= $topbar_delimiter;
        $s .= $link_open . $resume_uri . $link_close . $comp_alt_string . '</a>';
    }

    $comp_uri = $base_uri . 'src/compose.php' .
                            '?passed_id=' . $passed_id .
                            '&amp;mailbox=' . $urlMailbox .
                            '&amp;startMessage=' . $startMessage .'';

     // add the next and previous links
    $s .= '</small></td><td align="center" width="33%"><small>';

    $prev_link = _("Previous");
    $next_link = _("Next");
    if ($prev != -1) {
        $uri = $base_uri . 'src/read_body.php?passed_id='.$prev.
               '&amp;mailbox='.$urlMailbox.'&amp;sort='.$sort.
               '&amp;startMessage='.$startMessage.'&amp;show_more=0';
        $s .= '<a href="'.$uri.'">'._("Previous").'</a>';
    } else {
        $s .= _("Previous");
    }
    $s .= $topbar_delimiter;
    if ($next != -1) {
        $uri = $base_uri . 'src/read_body.php?passed_id='.$next.
               '&amp;mailbox='.$urlMailbox.'&amp;sort='.$sort.
               '&amp;startMessage='.$startMessage.'&amp;show_more=0';
        $s .= '<a href="'.$uri.'">'._("Next").'</a>';
    } else {
        $s .= _("Next");
    }
    //end next and prev links

    $s .= '</small></td>'
        . "\n"
        . html_tag( 'td', '', 'right', '', 'width="33%" nowrap' )
        . '<small>';


    //if this isn't a draft message, add the reply and forward links
    if ($mailbox != $draft_folder) {
        $comp_action_uri = $comp_uri . '&amp;smaction=forward';
        $s .= $link_open . $comp_action_uri . $link_close . _("Forward") . '</a>';


        $comp_action_uri = $comp_uri . '&amp;smaction=forward_as_attachment';
        $s .= $topbar_delimiter;
        $s .= $link_open . $comp_action_uri . $link_close . _("Forward as Attachment") . '</a>';


        if (substr(strtolower($subject), 0, 3) != 're:') {
                        $subject = 'Re: ' . $subject;
        }

        $url_subj = urlencode($subject);

        $comp_uri = $base_uri . 'src/compose.php?' .
                                    //'?passed_id=' . $passed_id .
                                '&amp;mailbox=' . $urlMailbox;

        //Reply_uri cribbed from SM 1.2.11

        $reply_uri     = $comp_uri . "&amp;send_to=$url_replyto&amp;".
                         "subject=$url_subj&amp;".
                         'smaction=reply&amp;'.
                         'gpgreply=1&amp;';

        $reply_all_uri = $comp_uri ."&amp;send_to=$url_replyto&amp;".
                         "send_to_cc=$url_replytoallcc&amp;subject=$url_subj&amp;".
                         '&amp;smaction=reply_all'.
                         '&amp;gpgreply=1&amp;';

        $s .= $topbar_delimiter;
        $s .= $link_open . $reply_uri . $link_close . _("Reply") . '</a>';

        $s .= $topbar_delimiter;
        $s .= $link_open . $reply_all_uri . $link_close . _("Reply All") . '</a>';
    } //end reply and forward block
    $s .= "<tr><td colspan=4 align=center>";
           $s .= "<form action=\"" . SM_PATH . "src/read_body.php?mailbox=$mailbox&sort=$sort&startMessage=$startMessage&passed_id=$next\" method=\"post\"><small>".
            "<input type=\"hidden\" name=\"show_more\" value=\"0\">".
            "<input type=\"hidden\" name=\"move_id\" value=\"$passed_id\">".
            _("Move to:") .
            ' <select name="targetMailbox">';
    if (isset($lastTargetMailbox) && !empty($lastTargetMailbox)) {
        $s .= sqimap_mailbox_option_list($imapConnection, array(strtolower($lastTargetMailbox)));
    }
    else {
        $s .= sqimap_mailbox_option_list($imapConnection);
    }
    $s .=    '</select> '.
            '<input type="submit" value="' . _("Move") . '">'.
            '</small>'.
           '</form>';
    $s .= '</td></tr>';

    $s .= '</small></td></tr></table>';
    $s .= "\n\n";
    //put in a small gap
    $s .= '<TABLE BGCOLOR="'.$color[9].'" WIDTH="100%" CELLPADDING="1"'.
         ' CELLSPACING="0" BORDER="0" ALIIGN="center">'."\n"
         .'<TR><TD HEIGHT="5" COLSPAN="2" BGCOLOR="'
         . $color[4].'"></TD></TR></table>'."\n";

    $menubar = $s;

    return $menubar;
}; //end decryptmenuBar fn

/*********************************************************************/
/**
 * function formatRecipientString
 *
 * This function is stolen shamelessly from SM 1.2.x compose.php
 *
 * Eventually, SM core will create templates, and then we'll be able
 * to call these functions directly, but until then...
 *
 * @param  object  $recipients
 * @return string  $item
 *
 */
function formatRecipientString($recipients, $item ) {
    global $show_more_cc, $show_more, $show_more_bcc,
           $PHP_SELF;

    $string = '';
    if ((is_array($recipients)) && (isset($recipients[0]))) {

        $cnt = count($recipients);
        foreach($recipients as $r) {
            $add = decodeHeader($r->getAddress(true), false, false);
            if ($string) {
                $string .= '<BR>' . $add;
            } else {
                $string = $add;
                if ($cnt > 1)
                {
                    $string .= '&nbsp;';
//                    $string .= '[<a href="">Show More</a>]';
                    $string .= '[<a href="./modules/gpg_recipientlist.php" target="_blank" onclick="pop_window ( this.href ); return false;">Show More</a>]';
                    sqsession_register($recipients , 'recipients');
                    break;

                }
/*
                    $string .= '&nbsp;(<A HREF="'.$url;
                    if ($show) {
                       $string .= '">'._("less").'</A>)';
                    } else {
                       $string .= '">'._("more").'</A>)';
                       break;
                    }
                }
*/
            }
        }
    }
    return $string;
}
/*********************************************************************/


//get the passphrase
$set_cached_passphrase=false;
if ($passphrase != '') {
    if ($passphrase == 'true' and gpg_is_passphrase_cached()) {
        $passphrase=gpg_get_cached_passphrase();
    } else {
    if (gpg_is_passphrase_cacheable()) {
        $set_cached_passphrase=true;
    }
    }
}  else {
    if ($debug) {
        echo "No passphrase found.<br>";
    }
}

/*********************************************************************/
/**
 * pull all the stuff we need from the imap variables
 */

//get the body text
global $uid_support;
global $imapConnection;
$imapConnection = sqimap_login($username, $key, $imapServerAddress,
                               $imapPort, 0);
if ($imapConnection==false){
    echo _("Connection to IMAP Server to retrieve message body failed.").'<br>';
};

$read = sqimap_mailbox_select($imapConnection, $mailbox, false, false, true);
if ($debug) {
    echo "<br>Mailbox Select returned:$read<br>";
}
//get the entire message object, so we can strip stuff we need:
$message = sqimap_get_message($imapConnection, $passed_id, $mailbox);


//get the body for decrypt,
$body_text = $gpg_encrypted_message;

$from_name = $message->rfc822_header->getAddr_s('from');
if (!$from_name) {
    $from_name = $message->rfc822_header->getAddr_s('sender');
    if (!$from_name) {
        $from_name = _("Unknown sender");
    }
}
$from_name = decodeHeader($from_name,false,false);

$subject   = decodeHeader($message->rfc822_header->subject,false,false);

$date      = decodeHeader($message->rfc822_header->subject,false,false);

//end pulling from IMAP
/*********************************************************************/

if ($debug) {
    echo '<br>Body Text<br><pre>'
         .  "$body_text"
         .  '</pre>'
         .  '<br> Now calling gpg_decrypt function <br>';

}

//now call decrypt
$return = gpg_decrypt($debug, $body_text, $passphrase);
  if (count($return['errors']) > 0) {
    $_SESSION['gpgerror']=$return['errors'];
    ob_end_clean();
    Header("Location: " . SM_PATH . "src/read_body.php?mailbox=$mailbox&passed_id=$passed_id&passed_ent_id=$passed_ent_id&next=$next&prev=$prev");
  }
ob_end_flush();
echo<<<TILLEND
<script language="JavaScript"
        type="text/javascript">
function pop_window ( strPath )
{
window.open ( strPath, "others", "width=450,height=350,50,50,menubar=no,toolbar=no,location=no,status=no,scrollbars=no,resizable=no");
}
</script>
TILLEND;

//display the page title
// ===============================================================
$section_title = _("GPG Decryption Results");
echo gpg_section_header ( $section_title, $color[9] );
// ===============================================================



//use htmlspecialchars on the plaintext, to allow the user to see things like <>
$rawplaintext=$return['plaintext'];
$plaintext = htmlspecialchars($return['plaintext']);

        /**
         * Rewrap $body so that no line is bigger than $editor_size
         * This should only really kick in the sqWordWrap function
         * if the browser doesn't support "VIRTUAL" as the wrap type.
         */

        $body = explode("\n", $plaintext);
        $newBody = '';
        foreach ($body as $line) {

            parseUrl ($line);

            if( $line <> '-- ' ) {
               $line = rtrim($line);
            }
            if (strlen($line) >= $editor_size - 1) {
                sqWordWrap($line, $editor_size);
            }

            $line = str_replace("\t", '        ', $line);

            $quotes = 0;
            $pos = 0;
            $j = strlen($line);

            while ($pos < $j) {
                if ($line[$pos] == ' ') {
                    $pos++;
                } else if (strpos($line, '&gt;', $pos) === $pos) {
                    $pos += 4;
                    $quotes++;
                } else {
                    break;
                }
            }

            if ($quotes % 2) {
                if (!isset($color[13])) {
                    $color[13] = '#800000';
                }
                $line = '<font color="' . $color[13] . '">' . $line . '</font>';
            } elseif ($quotes) {
                if (!isset($color[14])) {
                    $color[14] = '#FF0000';
                }
                $line = '<font color="' . $color[14] . '">' . $line . '</font>';
            }

            $newBody .= $line . "\n";
        }

        $plaintext = $newBody;

//put in our links

    echo "\n\n". decryptmenuBar($debug, $mailbox, $passed_id, $passed_ent_id, $next, $prev, $message, $imapConnection);

    echo '<TABLE BGCOLOR="'.$color[9].'" WIDTH="100%" CELLPADDING="1"'.
         ' CELLSPACING="0" BORDER="0" ALIGN="center">'."\n";
    echo '<TR><TD ALIGN=CENTER>';
    echo '<TABLE BGCOLOR="'.$color[9].'" WIDTH="100%" CELLPADDING="1"'.
         ' CELLSPACING="0" BORDER="0" ALIGN="center">'."\n";
    gpg_decrypt_link ($rawplaintext);
    echo '<TR><TD align=center>'."\n";
    gpg_import_link ($rawplaintext);
    echo '</TD></TR></TABLE>';

    echo '<TABLE WIDTH="100%" CELLPADDING="0" CELLSPACING="2" BORDER="0"';
    echo ' ALIGN="left" BGCOLOR="'.$color[0].'">';

    $env = array();
    $env[_("Subject")] = $subject;
    $env[_("From")] = $from_name;
    $env[_("Date")] = getLongDateString($message->rfc822_header->date);
    $env[_("To")] = formatRecipientString($message->rfc822_header->to, "to");
    $env[_("Cc")] = formatRecipientString($message->rfc822_header->cc, "cc");
    $env[_("Bcc")] = formatRecipientString($message->rfc822_header->bcc, "bcc");
    $env[_("Priority")] = htmlspecialchars(getPriorityStr($message->rfc822_header->priority));
    $env[_("Signature")] = '';
if ($return['signature']) {
    foreach($return['signature'] as $line) {
        $env[_("Signature")] .= htmlspecialchars($line) . "\n<br>";
    }
} else {
    $env[_("Signature")] = _("Unsigned");
}
    foreach ($env as $key => $val) {
        if ($val) {
            echo '<TR>';
            echo html_tag('TD', '<B>' . $key . ':&nbsp;&nbsp;</B>', 'RIGHT', '', 'VALIGN="TOP" WIDTH="20%"') . "\n";
            echo html_tag('TD', $val, 'left', '', 'VALIGN="TOP" WIDTH="80%"') . "\n";
            echo '</TR>';
        }
    }

echo "</table>";
echo "</TD></TR></TABLE>\n\n";
//put a header row here
//echo '<p><br>&nbsp;<br>&nbsp;</p>';
echo '<p><center><strong>'._("Decrypted Message").'</strong></center></p>'."\n";

// if we wanted to more closely match the display from read_body.php
// the table-in-table code to get the border around the body is on
// lines 789-803 in src/read_body.php from SM 1.4.2 STABLE
echo '<table width="100%" cellpadding="0" cellspacing="0" align="center" border="0">';
echo '  <tr><td>';
echo '    <table width="100%" cellpadding="1" cellspacing="0" align="center" border="0" bgcolor="'.$color[9].'">';
echo '      <tr><td>';
echo '        <table width="100%" cellpadding="3" cellspacing="0" align="center" border="0">';
echo '          <tr bgcolor="'.$color[4].'"><td>';
echo html_tag( 'table' ,'' , 'left', '', 'cellpadding="1" cellspacing="5" border="0"' );
echo '              <tr>' . html_tag( 'td', '<br><pre>'. $plaintext."\n</pre>", 'left')
                        . '</tr>';
echo '            </table>';
echo '          </td></tr>';
echo '        </table></td></tr>';
echo '    </table>';
echo '  </td></tr>';

//display the plaintext
//echo "<pre> \n $plaintext \n </pre> \n";

// if we have attachments, display them now
echo '<TR><TD HEIGHT="5" COLSPAN="2" BGCOLOR="'.
          $color[4].'"></TD></TR>'."\n";

//set $ent_ar just like it is in read_body.php
$ent_ar = $message->findDisplayEntity(array(), array('text/plain'));

//and now call the display code like in read_body.php
$attachmentsdisplay = formatAttachments($message,$ent_ar,$mailbox,$passed_id);
if ($attachmentsdisplay) {
   echo '       <table width="100%" cellpadding="0" cellspacing="0"
align="center" border="0" bgcolor="'.$color[4].'">';
   echo '        <tr><td ALIGN="left" bgcolor="'.$color[9].'">';
   echo '           <b>' . _("Attachments") . ':</b>';
   echo '        </td></tr>';
   echo '        <tr><td>';
   echo '          <table width="100%" cellpadding="2" cellspacing="2"
align="center"'.' border="0" bgcolor="'.$color[0].'"><tr><td>';
   echo              $attachmentsdisplay;
   echo '          </td></tr></table>';
   echo '       </td></tr></table>';
}
  echo '    </td></tr></table>';
//now log out of imap connection
sqimap_logout($imapConnection);

//now parse the info, errors, and warnings.
$notclean=0;
$serious=0;
    //echo the errors and warning to this page before continuing.
        if (count($return['warnings']) > 0) {
    echo '<b>'._("Warning: "). '</b><ul>';
        foreach ($return['warnings'] as $warning) {
            $notclean=1;
            echo htmlspecialchars($warning) . '<br>';
        };
    echo '</ul>';
    }
    if (count($return['errors']) > 0) {
    echo '<b>'._("Error: ") . '</b><ul>';
        foreach ($return['errors'] as $error) {
            $notclean=1;
            $serious=1;
            echo htmlspecialchars($error) . '<br>';
        };
    echo '</ul>';
    }
    if (count($return['info']) > 0) {
    echo '<b>'._("Info: "). '</b><ul>';
        foreach ($return['info'] as $info){
            echo htmlspecialchars($info) . '<br>';
        };
    echo '</ul>';
    }

if ($mailbox != $draft_folder) {
    //create the reply text
    /* this corrects some wrapping/quoting problems on replies */
    $rewrap_body = $return['plaintext']; //use the unwrapped version of the plaintext
    // if we sqUnWordWrap here, our paragraphs might run together.
    //sqUnWordWrap($rewrap_body);
    $rewrap_body = explode("\n", $rewrap_body);
    $reply_body = '';
    $cnt = count($rewrap_body);
    for ($i=0;$i<$cnt;$i++) {
      sqWordWrap($rewrap_body[$i], ($editor_size));
        if (preg_match("/^(>+)/", $rewrap_body[$i], $matches)) {
            $gt = $matches[1];
            $reply_body .= '>' . str_replace("\n", "\n>$gt ", rtrim($rewrap_body[$i])) ."\n";
        } else {
            $reply_body .= '> ' . str_replace("\n", "\n> ", rtrim($rewrap_body[$i])) . "\n";
        }
        unset($rewrap_body[$i]);
    }
    $reply_body =  $from_name .' '. _("writes") . ':'. "\n" . $reply_body;
} else { //we are in the $draft_folder
    $reply_body = $return['plaintext']; //use the unwrapped version of the plaintext
}

//if we are caching the passphrase, make sure there are no serious errors first
if (!$serious) {
    if ($passphrase != 'false' and $passphrase !='true' and $set_cached_passphrase and gpg_is_passphrase_cacheable()) {
        gpg_set_cached_passphrase($passphrase);
    }
}

//set the $body global var with the plaintext
$_SESSION['gpg_dbody']=$reply_body;

/**
 * $Log: gpgdecrypt.mod,v $
 * Revision 1.65  2005/07/27 14:07:49  brian
 * - update copyright to 2005
 *
 * Revision 1.64  2004/08/10 03:27:07  joelm
 * fixed a minor "unterminated string literal" javascript error
 *
 * Revision 1.63  2004/05/04 20:38:59  ke
 * -changed decodeHeader calls to set saveHTML to false, so that the proper characters appear in the compose window
 *
 * Revision 1.62  2004/03/10 22:08:16  ke
 * -added ending of output buffering when outputting in decrypt
 *
 * Revision 1.61  2004/03/10 21:48:16  ke
 * -changed order of output and redirect for errors
 *
 * Revision 1.60  2004/03/10 21:24:53  brian
 * - removed troublesome blank line at end of file
 *
 * Revision 1.59  2004/03/09 21:43:21  ke
 * -added redirect on errors back to read body pagae
 * bug 166
 *
 * Revision 1.58  2004/02/26 18:14:36  ke
 * -added global $imapConnection patch from Chuck Foster
 * bug 161
 *
 * Revision 1.57  2004/02/17 22:51:10  ke
 * -E_ALL fixes
 *
 * Revision 1.56  2004/01/23 02:38:10  brian
 * - applied $imapConnection patch provided by alex at kerkhove dot net
 * Bug 146
 *
 * Revision 1.55  2004/01/16 22:50:12  ke
 * -E_ALL fixes
 *
 * Revision 1.54  2004/01/14 22:21:29  ke
 * -added htmlspecialchars call so that signatures will display email addresses on encrypted signed messages
 *
 * Revision 1.53  2004/01/13 20:31:58  ke
 * -added include of gpg_execute centralized functions
 *
 * Revision 1.52  2004/01/09 18:27:15  brian
 * changed SM_PATH defines to use quoted string for E_ALL
 *
 * Revision 1.51  2003/12/10 22:22:18  ke
 * -added move functionality from delete_move_next plugin to decrypted view
 *
 * Revision 1.50  2003/12/01 19:51:43  ke
 * -added getPref for open in new window on decrypted forward, reply, etc
 * bug 129
 *
 * Revision 1.49  2003/11/20 20:20:56  ke
 * -changed gpg message output in decryption to show all errors/warnings/info under one header, indented
 * bug 107
 *
 * Revision 1.48  2003/11/18 17:35:59  walter
 * - removed debug ALERT box
 *
 * Revision 1.47  2003/11/18 15:32:19  walter
 * - framework to display recipient list from decrypted messages
 * Bug 71
 *
 * Revision 1.46  2003/11/17 19:23:04  ke
 * -added border around body text and header
 * -gpgdecrypt.mod now reads encrypted data from a session variable
 * -added call to gpg_decrypt_link to allow decryption of embedded encrypted messages
 *
 * Revision 1.45  2003/11/13 19:36:01  ke
 * -added html and code to import keys into keyring from encrypted message
 * -earlier commits also show the attachments in decrypted view
 * bug 105
 *
 * Revision 1.44  2003/11/13 00:57:18  ke
 * -added brian's attachment handling view
 * -moved logout of imap connection below attachment handling
 *
 * Revision 1.43  2003/11/12 20:40:19  ke
 * -added Signature header
 *
 * Revision 1.42  2003/11/05 20:25:41  ke
 * -added a check for quoted-printable encoding on body, decodes quoted-printable properly now
 * bug 104
 *
 * Revision 1.41  2003/11/04 21:41:01  brian
 * change to use SM_PATH
 *
 * Revision 1.40  2003/11/03 22:39:42  brian
 * Aaron added changes to eliminate 'bad passphrase' errors becasue of improperly cached passphrase.
 *
 * Revision 1.39  2003/11/03 18:44:05  ke
 * -changed to use consolidated caching options checks
 * -moved caching of passphrase until after decryption, so no incorrect passphrases get cached
 * -fixed use of Message List, so link goes back to correct position in mailbox
 * bug 66
 *
 * Revision 1.38  2003/11/03 15:53:14  brian
 * minor changes to comments and debug output
 *
 * Revision 1.37  2003/11/01 21:52:43  brian
 * - removed $msg strings and gpg_Makepage functionality
 * - localized remaining strings
 *
 * Revision 1.36  2003/10/30 20:56:48  brian
 * fixed problems apparent in the xgettext index
 * Bug 35
 *
 * Revision 1.35  2003/10/30 20:27:21  ke
 * -changed single to double quotes in internationalized strings
 * bug 35
 *
 * Revision 1.34  2003/10/22 20:55:55  brian
 * - rearranged order of parameters in gpg_decrypt fn call
 * Bug 56
 *
 * Revision 1.33  2003/10/11 00:21:06  brian
 * - added code to delete the draft from IMAP
 *   server on 'Resume Draft'
 * Bug 76
 *
 * Revision 1.32  2003/10/10 23:44:40  brian
 * - added 'Resume Draft' link
 * - modified body text code to understand difference
 *   between Reply and Resume Draft
 * - added links for to,cc,and bcc for draft
 * Bug 76
 *
 * Revision 1.31  2003/10/07 18:23:27  brian
 * added support to show the TO and CC names
 * Bug 65
 *
 * Revision 1.30  2003/10/07 17:12:44  brian
 * added support for next and previous links in menu bar
 * Bug 65
 *
 * Revision 1.29  2003/10/06 17:49:29  brian
 * minor changes to HTML rendering
 * Bug 65
 *
 * Revision 1.28  2003/10/06 16:47:11  brian
 * - added colorizing of > qoted lines
 * - added url parsing for email addreses and URLs
 * Bug 65
 *
 * Revision 1.27  2003/10/04 19:22:05  brian
 * added Subject|From|Date block to decrypted message view
 * Bug 65
 *
 * Revision 1.26  2003/10/01 23:11:37  brian
 * added support for reply from decrypt
 * - added menubar fn
 * - process extra variables from IMAP server
 * - build wrapped and quoted reply body
 * - place reply body in gpg_dbody for retrieval by compose
 * Bug 65
 *
 * Revision 1.25  2003/09/29 15:31:29  brian
 * added checks for system and user preferences before caching or using cached passphrase
 * Bug 40
 *
 * Revision 1.24  2003/09/26 20:12:09  brian
 * - added word wrap using sqWordWrap fn
 *   NOTE: This may mess with wrapping done by the sender!
 *   fixes: https://hfdev.cryptorights.org/bugzilla/show_bug.cgi?id=109
 *
 * Revision 1.23  2003/09/26 15:40:41  brian
 * - initial changes to support caching
 * - look up or set cached passphrase
 * - changed to full screen from pop-up
 * Bug 40
 *
 * Revision 1.22  2003/07/02 23:42:07  brian
 * - moved errorswarnings/info to bottom of file
 * - changed text size to normal from small
 * - modified entire page to be ready for gettext localization
 * Bug 51
 *
 * Revision 1.21  2003/04/11 03:22:24  brian
 * minor update to debug code for readablility
 *
 * Revision 1.20  2003/04/04 13:27:13  brian
 * add uid support to sqimap_run_copmmand
 *
 * Revision 1.19  2003/04/02 20:48:39  brian
 * fixed spelling errors using aspell
 * TODO - check grammer and sentence structure manually
 * Bug 18
 *
 * Revision 1.18  2003/04/02 19:13:58  brian
 * removed trailing line feeds
 *
 * Revision 1.17  2003/04/02 13:10:04  brian
 * use htmlspecialchars on plaintext
 *
 * Revision 1.16  2003/04/02 12:38:50  brian
 * added javascript to resize window to display decrypted message in.
 *
 * Revision 1.15  2003/04/01 17:53:14  brian
 * fixed includes to reflect different calling directories
 *
 * Revision 1.14  2003/04/01 16:06:44  brian
 * modified to use htmlspecialchars for info, warning, and error returns
 * Bug 8
 *
 * Revision 1.13  2003/04/01 05:28:03  brian
 * merged changes
 *
 * Revision 1.12  2003/04/01 05:07:42  joelm
 * Fixed some paths to included files. Also removed carriage returns from
 * cyphertext in the decrypt module
 *
 * Revision 1.11  2003/03/31 23:43:07  brian
 * modified to strip off the email headers
 *
 * Revision 1.10  2003/03/31 23:33:27  brian
 * modified to implode $body_text array
 *
 * Revision 1.9  2003/03/31 23:21:56  brian
 * added more debug lines
 *
 * Revision 1.8  2003/03/31 23:16:25  brian
 * added more debug lines
 *
 * Revision 1.7  2003/03/31 23:13:51  brian
 * added more debug lines
 *
 * Revision 1.6  2003/03/31 23:10:12  brian
 * - modified to include imap.php for all SM imap functions
 * - call smimap_mailbox_select to select mailbox to FETCH message from
 * Bug 8
 *
 * Revision 1.5  2003/03/31 23:05:22  brian
 * modified to use sqimap_run_command instead of calling sqimap_read_data directly
 * Bug 8
 *
 * Revision 1.4  2003/03/31 22:33:09  brian
 * added debug statements to make it easier to see what is going on here
 *
 * Revision 1.3  2003/03/31 22:16:48  brian
 * modified to make imap connection to grab body of email
 *
 * Revision 1.2  2003/03/31 14:59:54  brian
 * updated decryption modules to use standardized gpg_pop_init.php methods
 * Bug 8
 *
 * Revision 1.1  2003/03/29 23:03:21  brian
 * Initial revision
 * Preparation for decrypt functionality
 * Bug 8
 *
 *
 */
?>