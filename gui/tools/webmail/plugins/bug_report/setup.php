<?php

/**
 * setup.php
 *
 * Copyright (c) 1999-2006 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This is a standard Squirrelmail-1.2 API for plugins.
 *
 * $Id: setup.php,v 1.6.2.5 2006/02/03 22:27:52 jervfors Exp $
 */

/* This button fills out a form with your setup information already
   gathered -- all you have to do is type. */


/* Initialize the bug report plugin */
function squirrelmail_plugin_init_bug_report() {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['menuline']['bug_report'] = 'bug_report_button';
    $squirrelmail_plugin_hooks['options_display_inside']['bug_report'] = 'bug_report_options';
    $squirrelmail_plugin_hooks['options_display_save']['bug_report'] = 'bug_report_save';
    $squirrelmail_plugin_hooks['loading_prefs']['bug_report'] = 'bug_report_load';
}


/* Show the button in the main bar */
function bug_report_button() {
    global $color, $bug_report_visible;

    if (! $bug_report_visible) {
        return;
    }

    displayInternalLink('plugins/bug_report/bug_report.php', _("Bug"), '');
    echo "&nbsp;&nbsp;\n";
}


function bug_report_save() {
    global $username,$data_dir;

    if( sqgetGlobalVar('bug_report_bug_report_visible', $vis, SQ_POST) ) {
        setPref($data_dir, $username, 'bug_report_visible', '1');
    } else {
        setPref($data_dir, $username, 'bug_report_visible', '');
    }
}


function bug_report_load() {
    global $username, $data_dir;
    global $bug_report_visible;

    $bug_report_visible = getPref($data_dir, $username, 'bug_report_visible');
}


function bug_report_options() {
    global $bug_report_visible;

    echo '<tr>' . html_tag('td',_("Bug Reports:"),'right','','nowrap') . "\n" .
         '<td><input name="bug_report_bug_report_visible" type=checkbox';
    if ($bug_report_visible) {
        echo ' checked';
    }
    echo ' /> ' . _("Show button in toolbar") . "</td></tr>\n";
}

?>