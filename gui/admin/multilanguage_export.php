<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

// Include needed libraries
require '../include/imscp-lib.php';

// Check for login
check_login(__FILE__);

if (isset($_GET['export_lang']) && $_GET['export_lang'] !== '') {

	$sql = iMSCP_Registry::get('Db');
	$language_table = $_GET['export_lang'];

	$query = "
		SELECT
			`msgstr`
		FROM
			`$language_table`
		WHERE
			`msgid` = 'encoding'
		;
	";

	$stmt = execute_query($sql, $query);

	if ($stmt->RowCount() > 0 && $stmt->fields['msgstr'] != '') {

		$encoding = $stmt->fields['msgstr'];
	} else {
		$encoding = 'UTF-8';
	}

	$query = "
		SELECT
			`msgid`,
			`msgstr`
		FROM
			`$language_table`
		;
	";

	/**
	 * @var $stmt iMSCP_Database_ResultSet
	 */
	$stmt = exec_query($sql, $query);

	if ($stmt->recordCount() == 0) {
		set_page_message(tr('Incorrect data input!'));
		user_goto('multilanguage.php');
	} else {
		// Get all translation strings
		$data = '';

		while (!$stmt->EOF) {
			$msgid = $stmt->fields['msgid'];
			$msgstr = $stmt->fields['msgstr'];

			if ($msgid !== '' && $msgstr !== '') {
				$data .= "$msgid = $msgstr\n";
			}

			$stmt->moveNext();
		}

		$filename = str_replace('lang_', '', $language_table) . '.txt';

		if(isset($_GET['compress'])) {
			$filter = new iMSCP_Filter_Compress_Gzip();
			$data = $filter->filter($data);
			$filename .= '.gz';
			$mime_type = 'application/x-gzip';
		} else {
			$mime_type = 'text/plain;';
		}

		// Common headers
		header("Content-type: $mime_type;");
		header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		// Get client browser information
		$browserInfo = get_browser(null, true);

		// Headers according client browser
		if($browserInfo['browser'] == 'msie') {
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
		} else {
			header('Pragma: no-cache');

			if($browserInfo['browser'] == 'safari') {
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			}
		}
		print $data;
	}
} else {
	set_page_message(tr('Incorrect data input!'));
	user_goto('multilanguage.php');
}
