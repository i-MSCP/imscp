<?php

function squirrelmail_plugin_init_bookmarks() {
    global $squirrelmail_plugin_hooks;
    $squirrelmail_plugin_hooks['bookmark_plugin']['bookmarks'] = 'bookmarks';
}

function bookmarks() {
    displayInternalLink('plugins/bookmarks/bookmarks.php','<div id="bookmark_button" title="'._("Bookmarks").'">&nbsp;&nbsp;&nbsp;</div>','');
  //  echo "&nbsp;&nbsp\n";
}

?>
