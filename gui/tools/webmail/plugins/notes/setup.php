<?php
/**
 * setup.php
 *
 * @author Jimmy Conner <jimmy@sqmail.org>
 * @copyright Copyright &copy; 2005, Jimmy Conner (Licensed under the GNU GPL see "LICENSE")
 *
 * @package plugins
 * @subpackage notes
 * @version $Id$
 */

function notes_version() { 
   return '1.2';
}

function squirrelmail_plugin_init_notes() {
   global $squirrelmail_plugin_hooks;
   $squirrelmail_plugin_hooks['menuline']['notes'] = 'notes';
}

function notes() {
   include_once(SM_PATH . 'plugins/notes/functions.php');
   notes_add_link();
}

?>