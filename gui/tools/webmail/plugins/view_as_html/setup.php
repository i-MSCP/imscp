<?php
/* view_as_html -- Version 3.7a-1.4.x
 * By Seth Randall <indiri69@users.sourceforge.net>
 *
 * Code for displaying link only when an HTML part exists
 * Ben Brillat and Pete Walker
 *
 * $Id$
 */
    require_once(SM_PATH . 'functions/global.php');

    function squirrelmail_plugin_init_view_as_html() {
        global $squirrelmail_plugin_hooks;
        $squirrelmail_plugin_hooks['read_body_top']['view_as_html'] = 'view_as_html_read_body_top';
        $squirrelmail_plugin_hooks['read_body_header_right']['view_as_html'] = 'view_as_html_read_body_header_right';
    }

    function view_as_html_read_body_top() {
        include_once(SM_PATH . 'plugins/view_as_html/view_as_html.php');
        view_as_html_set();
    }

    function view_as_html_read_body_header_right() {
        include_once(SM_PATH . 'plugins/view_as_html/view_as_html.php');
        view_as_html_link();
    }

    function view_as_html_version() {
        return '3.7a-1.4.x';
    }
?>
