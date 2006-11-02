<?php
/* $Id: mrg_myisam.lib.php 8104 2005-12-07 10:49:43Z cybot_tm $ */
// vim: expandtab sw=4 ts=4 sts=4:

include_once './libraries/engines/merge.lib.php';

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
