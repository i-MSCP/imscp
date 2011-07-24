<?php

/**
 * take.php
 *
 * Copyright (c) 1999-2006 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Address Take -- steals addresses from incoming email messages. Searches
 * the To, Cc, From and Reply-To headers.
 *
 * $Id$
 */
   
/* Path for SquirrelMail required files. */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/addressbook.php');
   
displayPageHeader($color, 'None');

/* input form data */
sqgetGlobalVar('email', $email, SQ_POST);

$abook_take_verify = getPref($data_dir, $username, 'abook_take_verify');

$abook = addressbook_init(false, true);
$name = 'addaddr';

echo '<form action="../../src/addressbook.php" name="f_add" method="post">' ."\n" .
    html_tag( 'table',
        html_tag( 'tr',
            html_tag( 'th', sprintf(_("Add to %s"), $abook->localbackendname), 'center', $color[0] )
        ) ,
    'center', '', 'width="100%" cols="1"' ) .

    html_tag( 'table', '', 'center', '', 'border="0" cellpadding="1" cols="2" width="90%"' ) . "\n" .
            html_tag( 'tr', "\n" .
                html_tag( 'td', _("Nickname") . ':', 'right', $color[4], 'width="50"' ) . "\n" .
                html_tag( 'td', '<input name="' . $name . '[nickname]" size="15" value="">' .
                    '&nbsp;<small>' . _("Must be unique") . '</small>',
                'left', $color[4] )
            ) . "\n" .
            html_tag( 'tr' ) . "\n" .
            html_tag( 'td', _("E-mail address") . ':', 'right', $color[4], 'width="50"' ) . "\n" .
            html_tag( 'td', '', 'left', $color[4] ) .
                '<select name="' . $name . "[email]\">\n";

    foreach ($email as $Val)
    {
        if (valid_email($Val, $abook_take_verify))
        {
            echo '<option value="' . htmlspecialchars($Val) .
                '">' . htmlspecialchars($Val) . "</option>\n";
        } else {
            echo '<option value="' . htmlspecialchars($Val) .
	      '">FAIL - ' . htmlspecialchars($Val) . "</option>\n";
        }
    }
    if ($squirrelmail_language == 'ja_JP') {
        echo '</select></td></tr>' . "\n" . 
            
            html_tag( 'tr', "\n" .
                      html_tag( 'td', _("Last name") . ':', 'right', $color[4], 'width="50"' ) .
                      html_tag( 'td', '<input name="' . $name . '[lastname]" size="45" value="">', 'left', $color[4] )
                      ) . "\n" .
            html_tag( 'tr', "\n" .
                      html_tag( 'td', _("First name") . ':', 'right', $color[4], 'width="50"' ) .
                      html_tag( 'td', '<input name="' . $name . '[firstname]" size="45" value="">', 'left', $color[4] )
                      ) . "\n" .
            html_tag( 'tr', "\n" .
                      html_tag( 'td', _("Additional info") . ':', 'right', $color[4], 'width="50"' ) .
                      html_tag( 'td', '<input name="' . $name . '[label]" size="45" value="">', 'left', $color[4] )
                      ) . "\n" .
            html_tag( 'tr', "\n" .
                      html_tag( 'td',
                                '<input type="submit" name="' . $name . '[SUBMIT]" size="45" value="'. _("Add address") .'">' ,
                                'center', $color[4], 'colspan="2"' )
                      ) . "\n" .
            '</table>';
    } else {
    echo '</select></td></tr>' . "\n" . 

    html_tag( 'tr', "\n" .
        html_tag( 'td', _("First name") . ':', 'right', $color[4], 'width="50"' ) .
        html_tag( 'td', '<input name="' . $name . '[firstname]" size="45" value="">', 'left', $color[4] )
    ) . "\n" .
    html_tag( 'tr', "\n" .
        html_tag( 'td', _("Last name") . ':', 'right', $color[4], 'width="50"' ) .
        html_tag( 'td', '<input name="' . $name . '[lastname]" size="45" value="">', 'left', $color[4] )
    ) . "\n" .
    html_tag( 'tr', "\n" .
        html_tag( 'td', _("Additional info") . ':', 'right', $color[4], 'width="50"' ) .
        html_tag( 'td', '<input name="' . $name . '[label]" size="45" value="">', 'left', $color[4] )
    ) . "\n" .
    html_tag( 'tr', "\n" .
        html_tag( 'td',
            '<input type="submit" name="' . $name . '[SUBMIT]" size="45" value="'. _("Add address") .'">' ,
        'center', $color[4], 'colspan="2"' )
    ) . "\n" .
    '</table>';
    }
?>
</form></body>
</html>