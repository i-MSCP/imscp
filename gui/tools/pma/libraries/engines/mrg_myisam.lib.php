<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @version $Id: mrg_myisam.lib.php 11981 2008-11-24 10:18:44Z nijel $
 * @package phpMyAdmin-Engines
 */

/**
 *
 */
include_once './libraries/engines/merge.lib.php';

/**
 *
 * @package phpMyAdmin-Engines
 */
class PMA_StorageEngine_mrg_myisam extends PMA_StorageEngine_merge
{
    /**
     * returns string with filename for the MySQL helppage
     * about this storage engne
     *
     * @return  string  mysql helppage filename
     */
    function getMysqlHelpPage()
    {
        return 'merge';
    }
}

?>
