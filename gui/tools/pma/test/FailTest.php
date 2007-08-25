<?php
/* vim: expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_get_real_size()
 *
 * @version $Id: FailTest.php 10146 2007-03-20 14:16:18Z cybot_tm $
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