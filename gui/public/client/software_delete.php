<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 * The Original Code is i-MSCP - Multi Server Control Panel.
 *
 * The Initial Developer of the Original Code is i-MSCP Team.
 * Portions created by Initial Developer are Copyright (C) 2010-2011
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright	2010-2011 by i-MSCP | http://i-mscp.net
 * @author		iMSCP Team
 * @author		Sacha Bay <sascha.bay@i-mscp.net>
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('aps')) {
    redirectTo('index.php');
}

if (isset($_GET['id']) AND is_numeric($_GET['id'])) {
	list($dmn_id, $rest) = get_domain_default_props($_SESSION['user_id']);
	$query = "
		SELECT
			`software_id`, `software_res_del`
		FROM
			`web_software_inst`
		WHERE
			`software_id` = ?
		AND
			`domain_id` = ?
	";
	$rs = exec_query($query, array($_GET['id'], $dmn_id));

	if ($rs->recordCount() != 1) {
		set_page_message(tr('Wrong software id.'), 'error');
		redirectTo('software.php');
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
			$res = exec_query($delete, array($_GET['id'], $dmn_id));
			set_page_message(tr('Software deleted.'), 'success');
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
			$res = exec_query($delete, array('delete', $_GET['id'], $dmn_id));
			send_request();
			set_page_message(tr('Software scheduled for deletion.'), 'success');
		}
			redirectTo('software.php');
	}
} else {
	set_page_message(tr('Wrong software id.'), 'error');
	redirectTo('software.php');
}
