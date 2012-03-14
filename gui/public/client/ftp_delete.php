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
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('ftp')) {
    redirectTo('index.php');
}

if (isset($_GET['id']) && $_GET['id'] !== '') {

	$ftp_id = $_GET['id'];

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeDeleteFtp, array('ftpId' => $ftp_id));
	$dmn_name = $_SESSION['user_logged'];

	$query = "
		SELECT
			`t1`.`userid`, `t1`.`uid`, `t2`.`domain_uid`
		FROM
			`ftp_users` AS `t1`
		JOIN
			`domain` AS `t2`
			ON
				`t1`.`uid` = t2.`domain_uid`
		WHERE
			`t1`.`userid` = ?
		AND
			`t2`.`domain_name` = ?
	";

	$rs = exec_query($query, array($ftp_id, $dmn_name));
	$ftp_name = $rs->fields['userid'];

	if ($rs->recordCount() == 0) {
		set_page_message(tr('Wrong request.'), 'error');
		redirectTo('ftp_accounts.php');
	}

	$query = "
		SELECT
			`t1`.`gid`, t2.`members`
		FROM
			`ftp_users` AS `t1`
		JOIN
			`ftp_group` AS `t2`
			ON
				`t1`.`gid` = `t2`.`gid`
		WHERE
			`t1`.`userid` = ?
	";

	$rs = exec_query($query, $ftp_id);

	$ftp_gid = $rs->fields['gid'];
	$ftp_members = $rs->fields['members'];

	$members = str_replace(",{$ftp_id},", ",", "$ftp_members");
	if ($members == $ftp_members) {
		$members = preg_replace("/(^{$ftp_id},)|(,{$ftp_id}$)|(^{$ftp_id}$)/", "", "$ftp_members");
	}

	if (strlen($members) == 0) {
		$query = "
			DELETE FROM
				`ftp_group`
			WHERE
				`gid` = ?
		";

		$rs = exec_query($query, $ftp_gid);

	} else {
		$query = "
			UPDATE
				`ftp_group`
			SET
				`members` = ?
			WHERE
				`gid` = ?
		";

		$rs = exec_query($query, array($members, $ftp_gid));
	}

	$query = "
		DELETE FROM
			`ftp_users`
		WHERE
			`userid` = ?
	";

	$rs = exec_query($query, $ftp_id);

	$domain_props = get_domain_default_props($_SESSION['user_id']);
	update_reseller_c_props($domain_props[4]);

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterDeleteFtp, array('ftpid' => $ftp_id));

	write_log($_SESSION['user_logged'].": deletes FTP account: ".$ftp_name, E_USER_NOTICE);
	set_page_message(tr('FTP account deleted.'), 'success');
	redirectTo('ftp_accounts.php');

} else {
	redirectTo('ftp_accounts.php');
}
