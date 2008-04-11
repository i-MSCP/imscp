<?php
/**
 * gpg_test.php - PHPUnit test framework for GPG object
 *
 * Copyright (c) 2005 GPG Plugin Development Team
 * All Rights Reserved.
 *
 *
 * @author Aaron van Meerten
 * $Id: gpg_test.php,v 1.1 2005/11/11 07:22:53 ke Exp $
*/
require_once("PHPUnit/GUI/HTML.php");

require_once('gpg_test_class.php');

//$test = new GPGUnit_TestCase('test_gpg_nopipes');
//$display = new PHPUnit_GUI_HTML($test);

$suite = new PHPUnit_TestSuite( "PHPUnit_GnuPGTestCase" );
$display = new PHPUnit_GUI_HTML($suite);
$display->show();

/**
   * $Log: gpg_test.php,v $
   * Revision 1.1  2005/11/11 07:22:53  ke
   * - test framework for GPG testsy
   *
   *
   *
**/
?>