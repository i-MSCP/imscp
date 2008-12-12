<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_get_real_size()
 *
 * @version $Id: FailTest.php 11677 2008-10-25 13:37:54Z lem9 $
 * @package phpMyAdmin-test
 */

/**
 *
 */
require_once 'PHPUnit/Framework.php';

class FailTest extends PHPUnit_Framework_TestCase
{
    public function testFail()
    {
        $this->assertEquals(0, 1);
    }
}
?>
