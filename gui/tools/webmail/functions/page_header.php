<?php

/**
 * page_header.php
 *
 * Prints the page header (duh)
 *
 * @copyright &copy; 1999-2007 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: page_header.php 13289 2008-09-28 13:19:08Z kink $
 * @package squirrelmail
 *
 * @modified by ispCP Omega Team http://isp-control.net
 */

/** Include required files from SM */
require_once(SM_PATH . 'functions/strings.php');
require_once(SM_PATH . 'functions/html.php');
require_once(SM_PATH . 'functions/imap_mailbox.php');
require_once(SM_PATH . 'functions/global.php');

/* Always set up the language before calling these functions */
function displayHtmlHeader( $title = 'SquirrelMail', $xtra = '', $do_hook = TRUE ) {
    global $squirrelmail_language;

    if ( !sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION) ) {
        global $base_uri;
    }
    global $theme_css, $custom_css, $pageheader_sent;

    echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">' .
         "\n\n" . html_tag( 'html' ,'' , '', '', '' ) . "\n<head>\n" .
         "<meta name=\"robots\" content=\"noindex,nofollow\">\n";

    if ( !isset( $custom_css ) || $custom_css == 'none' ) {
        if ($theme_css != '') {
            echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$theme_css\">";
        }
    } else {
        echo '<link rel="stylesheet" type="text/css" href="' .
             $base_uri . 'themes/css/'.$custom_css.'">';
    }

    if ($squirrelmail_language == 'ja_JP') {
        // Why is it added here? Header ('Content-Type:..) is used in i18n.php
        echo "<!-- \xfd\xfe -->\n";
        echo '<meta http-equiv="Content-type" content="text/html; charset=euc-jp">' . "\n";
    }

    if ($do_hook) {
        do_hook('generic_header');
    }

    echo "\n<title>$title</title>$xtra\n";

    /* work around IE6's scrollbar bug */
    echo <<<ECHO
<!--[if IE 6]>
<style type="text/css">
/* avoid stupid IE6 bug with frames and scrollbars */
body {
    width: expression(document.documentElement.clientWidth - 30);
}
</style>
<![endif]-->

ECHO;

    echo "\n</head>\n\n";

    /* this is used to check elsewhere whether we should call this function */
    $pageheader_sent = TRUE;
}

function makeInternalLink($path, $text, $target='') {
    sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION);
    if ($target != '') {
        $target = " target=\"$target\"";
    }
    return '<a href="'.$base_uri.$path.'"'.$target.'>'.$text.'</a>';
}

function displayInternalLink($path, $text, $target='') {
    echo makeInternalLink($path, $text, $target);
}

function displayPageHeader($color, $mailbox, $xtra='', $session=false) {

    global $hide_sm_attributions, $frame_top,
           $compose_new_win, $compose_width, $compose_height,
           $attachemessages, $provider_name, $provider_uri,
           $javascript_on, $default_use_mdn, $mdn_user_support,
           $startMessage, $org_title;

    sqgetGlobalVar('base_uri', $base_uri, SQ_SESSION );
    sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION );
    if (!isset($frame_top)) {
        $frame_top = '_top';
    }

    if ($session) {
        $compose_uri = $base_uri.'src/compose.php?mailbox='.urlencode($mailbox).'&amp;session='."$session";
    } else {
        $compose_uri = $base_uri.'src/compose.php?newmessage=1';
        $session = 0;
    }

    // only output JavaScript if actually turned on
    if($javascript_on || strpos($xtra, 'new_js_autodetect_results.value') ) {
        if ( !defined('PAGE_NAME') ) define('PAGE_NAME', NULL);
        switch ( PAGE_NAME ) {
        case 'read_body':
            $js ='';

            // compose in new window code
            if ($compose_new_win == '1') {
                if (!preg_match("/^[0-9]{3,4}$/", $compose_width)) {
                    $compose_width = '640';
                }
                if (!preg_match("/^[0-9]{3,4}$/", $compose_height)) {
                    $compose_height = '550';
                }
                $js .= "function comp_in_new(comp_uri) {\n".
                     "       if (!comp_uri) {\n".
                     '           comp_uri = "'.$compose_uri."\";\n".
                     '       }'. "\n".
                     '    var newwin = window.open(comp_uri' .
                     ', "_blank",'.
                     '"width='.$compose_width. ',height='.$compose_height.
                     ',scrollbars=yes,resizable=yes,status=yes");'."\n".
                     "}\n\n";
            }

            // javascript for sending read receipts
            if($default_use_mdn && $mdn_user_support) {
                $js .= "function sendMDN() {\n".
                         "    mdnuri=window.location+'&sendreceipt=1';\n" .
                         "    window.location = mdnuri;\n" .
                       "\n}\n\n";
            }

            // if any of the above passes, add the JS tags too.
            if($js) {
                $js = "\n".'<script language="JavaScript" type="text/javascript">' .
                      "\n<!--\n" . $js . "// -->\n</script>\n";
            }

            displayHtmlHeader($org_title, $js);
            $onload = $xtra;
          break;
        case 'compose':
            $js = '<script language="JavaScript" type="text/javascript">' .
             "\n<!--\n" .
             "var alreadyFocused = false;\n" .
             "function checkForm() {\n" .
             "\n    if (alreadyFocused) return;\n";

            global $action, $reply_focus;
            if (strpos($action, 'reply') !== FALSE && $reply_focus)
            {
                if ($reply_focus == 'select') $js .= "document.forms['compose'].body.select();}\n";
                else if ($reply_focus == 'focus') $js .= "document.forms['compose'].body.focus();}\n";
                else if ($reply_focus == 'none') $js .= "}\n";
            }
            // no reply focus also applies to composing new messages
            else if ($reply_focus == 'none')
            {
                $js .= "}\n";
            }
            else
                $js .= "    var f = document.forms.length;\n".
                "    var i = 0;\n".
                "    var pos = -1;\n".
                "    while( pos == -1 && i < f ) {\n".
                "        var e = document.forms[i].elements.length;\n".
                "        var j = 0;\n".
                "        while( pos == -1 && j < e ) {\n".
                "            if ( document.forms[i].elements[j].type == 'text' ) {\n".
                "                pos = j;\n".
                "            }\n".
                "            j++;\n".
                "        }\n".
                "        i++;\n".
                "    }\n".
                "    if( pos >= 0 ) {\n".
                "        document.forms[i-1].elements[pos].focus();\n".
                "    }\n".
                "}\n";

            $js .= "// -->\n".
                 "</script>\n";
            $onload = 'onload="checkForm();"';
            displayHtmlHeader($org_title, $js);
            break;

        default:
            $js = '<script language="JavaScript" type="text/javascript">' .
             "\n<!--\n" .
             "function checkForm() {\n".
             "   var f = document.forms.length;\n".
             "   var i = 0;\n".
             "   var pos = -1;\n".
             "   while( pos == -1 && i < f ) {\n".
             "       var e = document.forms[i].elements.length;\n".
             "       var j = 0;\n".
             "       while( pos == -1 && j < e ) {\n".
             "           if ( document.forms[i].elements[j].type == 'text' " .
             "           || document.forms[i].elements[j].type == 'password' ) {\n".
             "               pos = j;\n".
             "           }\n".
             "           j++;\n".
             "       }\n".
             "   i++;\n".
             "   }\n".
             "   if( pos >= 0 ) {\n".
             "       document.forms[i-1].elements[pos].focus();\n".
             "   }\n".
             "   $xtra\n".
             "}\n";

            if ($compose_new_win == '1') {
                if (!preg_match("/^[0-9]{3,4}$/", $compose_width)) {
                    $compose_width = '640';
                }
                if (!preg_match("/^[0-9]{3,4}$/", $compose_height)) {
                    $compose_height = '550';
                }
                $js .= "function comp_in_new(comp_uri) {\n".
                     "       if (!comp_uri) {\n".
                     '           comp_uri = "'.$compose_uri."\";\n".
                     '       }'. "\n".
                     '    var newwin = window.open(comp_uri' .
                     ', "_blank",'.
                     '"width='.$compose_width. ',height='.$compose_height.
                     ',scrollbars=yes,resizable=yes,status=yes");'."\n".
                     "}\n\n";

            }
        $js .= "// -->\n". "</script>\n";


        $onload = 'onload="checkForm();"';
        displayHtmlHeader($org_title, $js);
      } // end switch module
    } else {
        // JavaScript off
        displayHtmlHeader($org_title);
        $onload = '';
    }

    echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\" $onload>\n\n";
    /** Here is the header and wrapping table **/
    $shortBoxName = htmlspecialchars(imap_utf7_decode_local(
                      readShortMailboxName($mailbox, $delimiter)));
    if ( $shortBoxName == 'INBOX' ) {
        $shortBoxName = _("INBOX");
    }
    //
	// Modified by ispCP Omega - http://isp-control.net
	//
    echo "<a name=\"pagetop\"></a>\n"
    /**** commented out
        . html_tag( 'table', '', '', $color[4], 'border="0" width="100%" cellspacing="0" cellpadding="2"' ) ."\n"
        . html_tag( 'tr', '', '', $color[9] ) ."\n"
        . html_tag( 'td', '', 'left' ) ."\n";

    if ( $shortBoxName <> '' && strtolower( $shortBoxName ) <> 'none' ) {
        echo '         ' . _("Current Folder") . ": <b>$shortBoxName&nbsp;</b>\n";
    } else {
        echo '&nbsp;';
    }
    echo  "      </td>\n"
        . html_tag( 'td', '', 'right' ) ."<b>\n";
    displayInternalLink ('src/signout.php', _("Sign Out"), $frame_top);
    echo "</b></td>\n"
        . "   </tr>\n"
        . html_tag( 'tr', '', '', $color[4] ) ."\n"
        . ($hide_sm_attributions ? html_tag( 'td', '', 'left', '', 'colspan="2"' )
                                 : html_tag( 'td', '', 'left' ) )
        . "\n";
	*/
		. html_tag( 'table', NULL, NULL, NULL, 'border="0" width="100%" cellspacing="0" cellpadding="2"' ) ."\n"
		. html_tag( 'tr', NULL, NULL, NULL, 'class="main_header_bar"' ) ."\n"
		. ($hide_sm_attributions ? html_tag( 'td', NULL, 'left', NULL, 'colspan="2"' ) : html_tag( 'td', NULL, 'left' ) )
		. "\n";
    //
	// End Modification
	//
    $urlMailbox = urlencode($mailbox);
    $startMessage = (int)$startMessage;
	//
	// Modified by ispCP Omega - http://isp-control.net
	//
	/**** commented out
    echo makeComposeLink('src/compose.php?mailbox='.$urlMailbox.'&amp;startMessage='.$startMessage);
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ('src/addressbook.php', _("Addresses"));
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ('src/folders.php', _("Folders"));
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ('src/options.php', _("Options"));
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ("src/search.php?mailbox=$urlMailbox", _("Search"));
    echo "&nbsp;&nbsp;\n";
    displayInternalLink ('src/help.php', _("Help"));
    echo "&nbsp;&nbsp;\n";

    do_hook('menuline');
    */
    // Menu Icons
	echo displayInternalLink('src/right_main.php?PG_SHOWALL=0&amp;sort=0&amp;startMessage=1&amp;mailbox=INBOX', "<div id='inbox_button'><p class='button_text'>"._("INBOX")."</p></div>") . "\n";
	echo makeComposeLink('src/compose.php?mailbox='.$urlMailbox.'&amp;startMessage='.$startMessage, "<div id='compose_button'><p class='button_text'>"._("Compose")."</p></div>") . "\n";
	echo displayInternalLink ('src/addressbook.php', "<div id='addresses_button'><p class='button_text'>"._("Addresses")."</p></div>") . "\n";
	echo displayInternalLink ('src/folders.php', "<div id='folders_button'><p class='button_text'>"._("Folders")."</p></div>") . "\n";
	echo displayInternalLink ('src/options.php', "<div id='options_button'><p class='button_text'>"._("Options")."</p></div>") . "\n";
	//
	// End Modification
	//

    echo "      </td>\n";

	//
	// Modified by ispCP Omega - http://isp-control.net
	//
	/**** commented out
    if (!$hide_sm_attributions)
    {
        echo html_tag( 'td', '', 'right' ) ."\n";
        if (!isset($provider_uri)) $provider_uri= 'http://www.squirrelmail.org/';
        if (!isset($provider_name)) $provider_name= 'SquirrelMail';
        echo '<a href="'.$provider_uri.'" target="_blank">'.$provider_name.'</a>';
        echo "</td>\n";
    }
	*/
	//
	// End Modification
	//

	//
	// Modified by ispCP Omega - http://isp-control.net
	//
    // Top Header
	echo "      <td class=\"inbox_bar_header\" align=\"right\" width=\"30%\">";
	echo displayInternalLink ('src/signout.php', "<div id='exit_button' title='"._("Sign Out")."'>&nbsp;&nbsp;&nbsp;</div>", $frame_top) . "\n";
	echo displayInternalLink ("src/search.php?mailbox=$urlMailbox", "<div id='search_button' title='"._("Search")."'>&nbsp;&nbsp;&nbsp;</div>") . "\n";
	echo displayInternalLink ('src/help.php', "<div id='help_button' title='"._("Help")."'>&nbsp;&nbsp;&nbsp;</div>") . "\n";
	do_hook('fetchmail');
	do_hook('calendar_plugin');
	do_hook('bookmark_plugin');
	do_hook('notes_plugin');
	do_hook('todo_plugin');
	echo "      </td>";
    echo "   </tr>\n".
        "</table>\n\n";

    // Welcome Bar
	//
	// Modified by ispCP Omega - http://isp-control.net
	//
	echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n
		    <tr>\n
			  <td class=\"top_left_bar_handle\" width=\"5px\">\n
	            <div align=\"right\"><img src=\"".$base_uri."images/blank.png\"></div>\n
			</td>\n";
	if ( $shortBoxName <> '' && strtolower( $shortBoxName ) <> 'none' ) {
        echo "      <td align=\"left\" class=\"inbox_bar_header\">" . _("Current Folder") . ": <b>$shortBoxName&nbsp;</b></td>\n";
    } else {
        echo "      <td align=\"left\" class=\"inbox_bar_header\">" . _("Current Folder") . ": <b>Option&nbsp;</b></td>\n";
    }
	echo "  <td class=\"top_bar_header\" align=\"right\" >";
	do_hook('menuline');
	echo "&nbsp;</td>";
	//
	// End Modification
	//
	echo "   </tr>\n
		  </table><br>\n\n";
}

/* blatently copied/truncated/modified from the above function */
function compose_Header($color, $mailbox) {

    global $delimiter, $hide_sm_attributions, $base_uri,
           $data_dir, $username, $frame_top, $compose_new_win;


    if (!isset($frame_top)) {
        $frame_top = '_top';
    }

    /*
        Locate the first displayable form element
    */
    if ( !defined('PAGE_NAME') ) define('PAGE_NAME', NULL);
    switch ( PAGE_NAME ) {
    case 'search':
        $pos = getPref($data_dir, $username, 'search_pos', 0 ) - 1;
        $onload = "onload=\"document.forms[$pos].elements[2].focus();\"";
        displayHtmlHeader (_("Compose"));
        break;
    default:
        $js = '<script language="JavaScript" type="text/javascript">' .
             "\n<!--\n" .
             "var alreadyFocused = false;\n" .
             "function checkForm() {\n" .
             "\n    if (alreadyFocused) return;\n";

            global $action, $reply_focus;
            if (strpos($action, 'reply') !== FALSE && $reply_focus)
            {
                if ($reply_focus == 'select') $js .= "document.forms['compose'].body.select();}\n";
                else if ($reply_focus == 'focus') $js .= "document.forms['compose'].body.focus();}\n";
                else if ($reply_focus == 'none') $js .= "}\n";
            }
            // no reply focus also applies to composing new messages
            else if ($reply_focus == 'none')
            {
                $js .= "}\n";
            }
            else
                $js .= "var f = document.forms.length;\n".
                "var i = 0;\n".
                "var pos = -1;\n".
                "while( pos == -1 && i < f ) {\n".
                    "var e = document.forms[i].elements.length;\n".
                    "var j = 0;\n".
                    "while( pos == -1 && j < e ) {\n".
                        "if ( document.forms[i].elements[j].type == 'text' ) {\n".
                            "pos = j;\n".
                        "}\n".
                        "j++;\n".
                    "}\n".
                "i++;\n".
                "}\n".
                "if( pos >= 0 ) {\n".
                    "document.forms[i-1].elements[pos].focus();\n".
                "}\n".
            "}\n";
        $js .= "// -->\n".
                 "</script>\n";
        $onload = 'onload="checkForm();"';
        displayHtmlHeader (_("Compose"), $js);
        break;

    }

    echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\" $onload>\n\n";
}
