<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace iMSCP\Assertion;

use Zend_Acl;
use Zend_Acl_Resource_Interface;
use Zend_Acl_Role_Interface;

class ClientHasCustomErrorPagesFeatureAssertion implements \Zend_Acl_Assert_Interface
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
        return customerHasFeature('custom_error_pages');
    }
}
