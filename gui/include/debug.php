<?php
/**
 *  ispCP (OMEGA) a Virtual Hosting Control Panel
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 **/



function dump_gui_debug() {

	print '<font color="#0000FF"><u><b>$_SESSION[]</b> Contents:</u><br></font>';

	foreach ($_SESSION as $key => $value) {

		print "$key = $value <br>";

	}


	print '<font color="#0000FF"><u><b>$_POST[]</b> Contents:</u><br></font>';

	foreach ($_POST as $key => $value) {

		print "$key = $value <br>";

	}

	print '<font color="#0000FF"><u><b>$_GET[]</b> Contents:</u><br></font>';

	foreach ($_GET as $key => $value) {

		print "$key = $value <br>";

	}

	print '<font color="#0000FF"><u><b>$_COOKIE[]</b> Contents:</u><br></font>';

	foreach ($_COOKIE as $key => $value) {

		print "$key = $value <br>";

	}

	print '<font color="#0000FF"><u><b>$_FILES[]</b> Contents:</u><br></font>';

	foreach ($_FILES as $key => $value) {

		print "$key = $value <br>";

	}

	print '<font color="#0000FF"><u><b>$GLOBALS[]</b> Contents:</u><br></font>';

	foreach ($GLOBALS as $key => $value) {

		print "$key = $value <br>";

	}

	print '<font color="#0000FF"><u><b>$_SERVER[]</b> Contents:</u><br></font>';

	foreach ($_SERVER as $key => $value) {

		print "$key = $value <br>";

	}

}

?>