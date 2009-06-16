<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @version $Id: berkeleydb.lib.php 11981 2008-11-24 10:18:44Z nijel $
 * @package phpMyAdmin-Engines
 */

/**
 * Load BDB class.
 */
include_once './libraries/engines/bdb.lib.php';

/**
 * This is same as BDB.
 * @package phpMyAdmin-Engines
 */
class PMA_StorageEngine_berkeleydb extends PMA_StorageEngine_bdb
{
}

?>
