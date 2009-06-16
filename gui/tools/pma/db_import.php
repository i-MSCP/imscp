<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id: db_import.php 11982 2008-11-24 10:32:56Z nijel $
 * @package phpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';

/**
 * Gets tables informations and displays top links
 */
require './libraries/db_common.inc.php';
require './libraries/db_info.inc.php';

$import_type = 'database';
require './libraries/display_import.lib.php';

/**
 * Displays the footer
 */
require './libraries/footer.inc.php';
?>

