<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

/**
 * @todo check if we can remove commented code
 */
function dump_gui_debug() {
	echo '<span style="color:#0000FF"><u>Content of <b>$_SESSION</b>:</u><br /></span>';
	echo '<pre>';
	echo htmlentities(print_r($_SESSION, true));
	echo '</pre>';
	echo '<span style="color:#0000FF"><u>Content of <b>$_POST</b>:</u><br /></span>';
	echo '<pre>';
	echo htmlentities(print_r($_POST, true));
	echo '</pre>';
	echo '<span style="color:#0000FF"><u>Content of <b>$_GET</b>:</u><br /></span>';
	echo '<pre>';
	echo htmlentities(print_r($_GET, true));
	echo '</pre>';
	echo '<span style="color:#0000FF"><u>Content of <b>$_COOKIE</b>:</u><br /></span>';
	echo '<pre>';
	echo htmlentities(print_r($_COOKIE, true));
	echo '</pre>';
	echo '<span style="color:#0000FF"><u>Content of <b>$_FILES</b>:</u><br /></span>';
	echo '<pre>';
	echo htmlentities(print_r($_FILES, true));
	echo '</pre>';
	return;
	/* // cause of "return;" the following code is not executed
	echo '<span style="color:#0000FF"><u>Content of <b>$GLOBALS</b>:</u><br /></span>';
	echo '<pre>';
	echo htmlentities(print_r($GLOBALS, true));
	echo '</pre>';
	echo '<span style="color:#0000FF"><u>Content of <b>$_SERVER</b>:</u><br /></span>';
	echo '<pre>';
	echo htmlentities(print_r($_SERVER, true));
	echo '</pre>';
	*/
}

?>