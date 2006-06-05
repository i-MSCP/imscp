<?php
/* $Id: header_custom.inc.php,v 2.2 2005/11/25 08:58:11 nijel Exp $ */
// vim: expandtab sw=4 ts=4 sts=4:

// This file includes all custom headers if they exist.

// Include site header
if (file_exists('./config.header.inc.php')) {
    require('./config.header.inc.php');
}
?>
