<?php
/* $Id: binlog.lib.php 8105 2005-12-07 10:55:34Z cybot_tm $ */
// vim: expandtab sw=4 ts=4 sts=4:

class PMA_StorageEngine_binlog extends PMA_StorageEngine
{
    /**
     * returns string with filename for the MySQL helppage
     * about this storage engne
     *
     * @return  string  mysql helppage filename
     */
    function getMysqlHelpPage()
    {
        return 'binary-log';
    }
}

?>
