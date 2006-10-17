<?php
/* $Id: show_config_errors.php,v 2.1 2006/03/16 22:15:06 nijel Exp $ */
// vim: expandtab sw=4 ts=4 sts=4:

/* Simple wrapper just to enable error reporting and include config */

echo "Starting to parse config file...\n";

error_reporting(E_ALL);
require('./config.inc.php');

?>
