<?php
/* view_as_html
 * By Seth Randall <indiri69@users.sourceforge.net>
 *
 * Code for displaying link only when an HTML part exists
 * Ben Brillat and Pete Walker
 *
 * $Id$
 */
    function squirrelmail_plugin_init_view_as_html() {
        global $squirrelmail_plugin_hooks;

        $squirrelmail_plugin_hooks['read_body_top']['view_as_html']
           = 'view_as_html_read_body_top';
        $squirrelmail_plugin_hooks['read_body_header_right']['view_as_html']
           = 'view_as_html_read_body_header_right';
    }

    function view_as_html_info() {
        return array(
            'english_name' => 'View as HTML',
            'authors' => array(
                'Seth Randall' => array(
                    'email' => 'indiri69@users.sourceforge.net',
                    'sm_site_username' => 'randall',
                ),
            ),
            'version' => '3.8',
            'required_sm_version'    => '1.4.10',
            'requires_configuration' => 0,
            'requires_source_patch'  => 0,
            'required_plugins'       => array(),
            'per_version_requirements' => array(),
            'summary' => 'Switch between HTML and plain text version of emails.',
            'details' => 'This plugin provides a link on the message viewing page that switches between the HTML view and the plain text view of an email.'
        );
    }

    function view_as_html_version() {
        $info = view_as_html_info();
        return $info['version'];
    }

    function view_as_html_read_body_top() {
        include_once(SM_PATH . 'plugins/view_as_html/view_as_html.php');
        view_as_html_set();
    }

    function view_as_html_read_body_header_right() {
        include_once(SM_PATH . 'plugins/view_as_html/view_as_html.php');
        view_as_html_link();
    }
