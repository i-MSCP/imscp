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
 * @subpackage	Reseller
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if(!resellerHasFeature('domain_aliases')) {
	return 'index.php';
}

$cfg = iMSCP_Registry::get('config');

if (isset($_GET['del_id']))
	$del_id = $_GET['del_id'];
else {
	$_SESSION['aldel'] = '_no_';
	redirectTo('alias.php');
}
$reseller_id = $_SESSION['user_id'];

$query = "
	SELECT
		t1.`domain_id`, t1.`alias_id`, t1.`alias_name`,
		t2.`domain_id`, t2.`domain_created_id`
	FROM
		`domain_aliasses` AS t1,
		`domain` AS t2
	WHERE
		t1.`alias_id` = ?
	AND
		t1.`domain_id` = t2.`domain_id`
	AND
		t2.`domain_created_id` = ?
";

$rs = exec_query($query, array($del_id, $reseller_id));

if ($rs->recordCount() == 0) {
	redirectTo('alias.php');
}

$alias_name = $rs->fields['alias_name'];

// check for mail acc in ALIAS domain (ALIAS MAIL) and delete them
$query = "
	UPDATE
		`mail_users`
	SET
		`status` = ?
	WHERE
		(`sub_id` = ?
		AND
		`mail_type` LIKE '%alias_%')
	OR
		(`sub_id` IN (SELECT `subdomain_alias_id` FROM `subdomain_alias` WHERE `alias_id` = ?)
		AND
		`mail_type` LIKE '%alssub_%')
";

exec_query($query, array($cfg->ITEM_DELETE_STATUS, $del_id, $del_id));

$res = exec_query("SELECT `alias_name` FROM `domain_aliasses` WHERE `alias_id` = ?", $del_id);
$dat = $res->fetchRow();

// Delete custom DNS and external mailservers for this alias
$query = "DELETE FROM `domain_dns` WHERE `alias_id` = ?";
exec_query($query, $del_id);

// TODO Use prepared statements
$query = "UPDATE `ssl_certs` SET `status` = ? WHERE `type` = 'alssub' AND `id` IN (SELECT `subdomain_alias_id` FROM `subdomain_alias` WHERE `alias_id` = ? )";
exec_query($query, array($cfg->ITEM_DELETE_STATUS, $del_id));
$query = "UPDATE `subdomain_alias` SET `subdomain_alias_status` = ? WHERE `alias_id` = ?";
exec_query($query, array($cfg->ITEM_DELETE_STATUS, $del_id));

// TODO Use prepared statements
$query = "UPDATE `ssl_certs` SET `status` = ? WHERE `type` = 'als' AND `id` = ?";
exec_query($query, array($cfg->ITEM_DELETE_STATUS, $del_id));
$query = "UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?";
exec_query($query, array($cfg->ITEM_DELETE_STATUS, $del_id));

// Checks for FTP accounts in ALIAS domain (ALIAS FTP) and delete them
$query = "
 SELECT
     `userid`
 FROM
     `ftp_users`
 WHERE
     `userid` LIKE '%@".$alias_name."'
";
$stmt = exec_query($query);

if ($stmt->recordCount() > 0) {
	while (!$stmt->EOF) {
		$ftp_id = $stmt->fields['userid'];

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

		$stmt->moveNext();
	}
}

update_reseller_c_props($reseller_id);

send_request();
$admin_login = $_SESSION['user_logged'];
write_log("$admin_login: deletes domain alias: " . $dat['alias_name'], E_USER_NOTICE);

$_SESSION['aldel'] = '_yes_';

redirectTo('alias.php');
