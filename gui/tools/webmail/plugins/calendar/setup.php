<?php

/**
 * Calendar plugin activation script
 *
 * @copyright &copy; 2002-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: setup.php,v 1.5.2.5 2006/05/06 07:52:19 tokul Exp $
 * @package plugins
 * @subpackage calendar
 */

/**
 * Initialize the plugin
 * @return void
 */
function squirrelmail_plugin_init_calendar() {
    global $squirrelmail_plugin_hooks;
    $squirrelmail_plugin_hooks['calendar_plugin']['calendar'] = 'calendar';
}

/**
 * Adds Calendar link to upper menu
 * @return void
 */
function calendar() {

        global $base_uri;

    displayInternalLink('plugins/calendar/calendar.php','<div id="calendar_button" title="'._("Calendar").'">&nbsp;&nbsp;&nbsp;</div>','');
      //  echo '</td>';
}

?>
