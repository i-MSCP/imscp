<?php
/* $Id: show_config_errors.php 8776 2006-03-16 22:15:07Z nijel $ */
// vim: expandtab sw=4 ts=4 sts=4:

/* Simple wrapper just to enable error reporting and include config */

echo "Starting to parse config file...\n";

error_reporting(E_ALL);
require('./config.inc.php');

?>
