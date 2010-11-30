<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
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
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category i-MSCP
 * @copyright 2006-2010 by ispCP | http://isp-control.net
 * @copyright 2006-2010 by ispCP | http://i-mscp.net
 * @author ispCP Team
 * @author i-MSCP Team
 * @version SVN: $Id: Database.php 3702 2010-11-16 14:20:55Z thecry $
 * @link http://i-mscp.net i-MSCP Home Site
 * @license http://www.mozilla.org/MPL/ MPL 1.1
 */

require '../include/imscp-lib.php';

check_login(__FILE__);

if (isset($_GET['id']) AND is_numeric($_GET['id'])) {
	list($dmn_id, $rest) = get_domain_default_props($sql, $_SESSION['user_id']);
	$query = "
		SELECT
			`software_id`,
			`software_res_del`
		FROM
			`web_software_inst`
		WHERE
			`software_id` = ?
		AND
			`domain_id` = ?
	";
	$rs = exec_query($sql, $query, array($_GET['id'], $dmn_id));
	if ($rs->recordCount() != 1) {
		set_page_message(tr('Wrong software id.'));
		header('Location: software.php');
	} else {
		if ($rs->fields['software_res_del'] === '1') {
			$delete = "
				DELETE FROM
					`web_software_inst`
				WHERE
					`software_id` = ?
				AND
					`domain_id` = ?
			";
			$res = exec_query($sql, $delete, array($_GET['id'], $dmn_id));
			set_page_message(tr('Software deleted successful.'));
		}else{
			$delete = "
				UPDATE
					`web_software_inst`
				SET
					`software_status` = ?
				WHERE
					`software_id` = ?
				AND
					`domain_id` = ?
			";
			$res = exec_query($sql, $delete, array('delete', $_GET['id'], $dmn_id));
			send_request();
			set_page_message(tr('Software will be deleted now.'));
		}
			header('Location: software.php');
	}
} else {
	set_page_message(tr('Wrong software id.'));
	header('Location: software.php');
}
