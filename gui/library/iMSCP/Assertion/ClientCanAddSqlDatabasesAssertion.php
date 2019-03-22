<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace iMSCP\Assertion;

use Zend_Acl;
use Zend_Acl_Resource_Interface;
use Zend_Acl_Role_Interface;

class ClientCanAddSqlDatabasesAssertion implements \Zend_Acl_Assert_Interface
{
    /**
     * @inheritdoc
     */
    public function assert(
        Zend_Acl $acl,
        Zend_Acl_Role_Interface $role = NULL,
        Zend_Acl_Resource_Interface $resource = NULL,
        $privilege = NULL
    ) {
        if (customerSqlDbLimitIsReached()) {
            if (\iMSCP_Registry::get('navigation')->findOneBy('uri', '/client/sql_manage.php')->isActive()) {
                set_page_message(tr("SQL databases limit is reached. You cannot add new SQL databases."), 'static_info');
            }

            return false;
        }

        return true;
    }
}
