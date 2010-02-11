<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
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

	/* Activate debug code if needed
	echo '<span style="color:#0000FF"><u>Content of <b>$_SERVER</b>:</u><br /></span>';
	echo '<pre>';
	echo htmlentities(print_r($_SERVER, true));
	echo '</pre>';
	*/
}
