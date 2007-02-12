<?php
/**
 * Message Details plugin - top frame with buttons
 *
 * Plugin to view the RFC822 raw message output and the bodystructure of a message
 *
 * @author Marc Groot Koerkamp
 * @copyright Copyright &copy; 2002 Marc Groot Koerkamp, The Netherlands
 * @copyright Copyright &copy; 2004-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: message_details_top.php,v 1.11.2.4 2006/02/03 22:27:52 jervfors Exp $
 * @package plugins
 * @subpackage message_details
 */

/** @ignore */
define('SM_PATH','../../');

/* SquirrelMail required files. */
require_once(SM_PATH . 'include/validate.php');

displayHtmlHeader( _("Message Details"),
             "<script language=\"javascript\">\n".
             "<!--\n".
             "function printPopup() {\n".
                "parent.frames[1].focus();\n".
                "parent.frames[1].print();\n".
             "}\n".
             "-->\n".
             "</script>\n", FALSE );

sqgetGlobalVar('passed_id', $passed_id, SQ_GET);
sqgetGlobalVar('mailbox', $mailbox, SQ_GET);

echo "<body text=\"$color[8]\" bgcolor=\"$color[3]\" link=\"$color[7]\" vlink=\"$color[7]\" alink=\"$color[7]\">\n" .
     '<center><b>' .
     '<form action="' . SM_PATH . 'src/download.php" method="GET">' .     
     '<input type="button" value="' . _("Print") . '" onClick="printPopup()" />&nbsp;&nbsp;'.
     '<input type="button" value="' . _("Close Window") . '" onClick="window.parent.close()" />&nbsp;&nbsp;'.
     '<input type="submit" value="' . _("Save Message") . '" /> '.
     '<input type="hidden" name="mailbox" value="' . urlencode($mailbox) . '" />' .
     '<input type="hidden" name="passed_id" value="' . urlencode($passed_id) . '" />' .
     '<input type="hidden" name="ent_id" value="0" />' .
     '<input type="hidden" name="absolute_dl" value="true" />' .
     '</form>'.
     '</b>'.
     '</body>'.
     "</html>\n";
?>