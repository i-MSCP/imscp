<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id: phpinfo.php 10240 2007-04-01 11:02:46Z cybot_tm $
 */

/**
 * Gets core libraries and defines some variables
 */
define('PMA_MINIMUM_COMMON', true);
require_once './libraries/common.inc.php';


/**
 * Displays PHP information
 */
if ($GLOBALS['cfg']['ShowPhpInfo']) {
    phpinfo();
}
?>
