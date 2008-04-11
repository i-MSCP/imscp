<?php

function squirrelmail_plugin_init_bookmarks() {
    global $squirrelmail_plugin_hooks;
    $squirrelmail_plugin_hooks['menuline']['bookmarks'] = 'bookmarks';
}

function bookmarks() {
    displayInternalLink('plugins/bookmarks/bookmarks.php',_("Bookmarks"),'right');
    echo "&nbsp;&nbsp\n";
}

?>
