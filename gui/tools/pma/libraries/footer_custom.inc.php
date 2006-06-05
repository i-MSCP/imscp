<?php
/* $Id: footer_custom.inc.php,v 2.2 2005/11/25 08:58:11 nijel Exp $ */
// vim: expandtab sw=4 ts=4 sts=4:

// This file includes all custom footers if they exist.

// Include site footer
if (file_exists('./config.footer.inc.php')) {
    require('./config.footer.inc.php');
}
?>
