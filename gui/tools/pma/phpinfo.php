<?php
/* $Id: phpinfo.php,v 2.5 2005/11/22 11:58:37 cybot_tm Exp $ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets core libraries and defines some variables
 */
define( 'PMA_MINIMUM_COMMON', true );
require_once('./libraries/common.lib.php');


/**
 * Displays PHP information
 */
if ( $GLOBALS['cfg']['ShowPhpInfo'] ) {
    phpinfo();
}
?>
