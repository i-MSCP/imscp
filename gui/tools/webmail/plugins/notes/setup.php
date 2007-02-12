<?php
/**
 * setup.php
 *
 * @author Jimmy Conner <jimmy@sqmail.org>
 * @copyright Copyright &copy; 2005, Jimmy Conner (Licensed under the GNU GPL see "LICENSE")
 *
 * @package plugins
 * @subpackage notes
 * @version $Id: setup.php,v 1.1 2005/01/05 15:02:26 cigamit Exp $
 */

function notes_version() { 
   return '1.2';
}

function squirrelmail_plugin_init_notes() {
   global $squirrelmail_plugin_hooks;
   $squirrelmail_plugin_hooks['notes_plugin']['notes'] = 'notes';
}

function notes() {
   include_once(SM_PATH . 'plugins/notes/functions.php');
   notes_add_link();
}

?>