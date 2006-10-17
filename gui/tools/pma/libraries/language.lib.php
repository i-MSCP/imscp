<?php
/* $Id: language.lib.php,v 2.1 2006/04/27 12:13:52 nijel Exp $ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * phpMyAdmin Language Loading File
 */

// Detection is done here
require_once('./libraries/select_lang.lib.php');

// Load the translation
require_once $lang_path . $available_languages[$GLOBALS['lang']][1] . '.inc.php';

?>
