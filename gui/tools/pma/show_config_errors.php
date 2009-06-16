<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Simple wrapper just to enable error reporting and include config
 *
 * @version $Id: show_config_errors.php 11986 2008-11-24 11:05:40Z nijel $
 * @package phpMyAdmin
 */

echo "Starting to parse config file...\n";

error_reporting(E_ALL);
/**
 * Read config file.
 */
require './config.inc.php';

?>
