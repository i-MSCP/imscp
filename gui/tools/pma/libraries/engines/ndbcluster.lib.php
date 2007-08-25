<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @version $Id: ndbcluster.lib.php 10137 2007-03-19 17:55:39Z cybot_tm $
 */

/**
 *
 */
class PMA_StorageEngine_ndbcluster extends PMA_StorageEngine
{
    /**
     * @return  array
     */
    function getVariables()
    {
        return array(
            'ndb_connectstring' => array(
            ),
         );
    }

    /**
     * @return  string  SQL query LIKE pattern
     */
    function getVariablesLikePattern()
    {
        return 'ndb\\_%';
    }

    /**
     * returns string with filename for the MySQL helppage
     * about this storage engne
     *
     * @return  string  mysql helppage filename
     */
    function getMysqlHelpPage()
    {
        return 'ndbcluster';
    }
}

?>
