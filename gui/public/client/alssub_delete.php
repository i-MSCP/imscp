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
if (!customerHasFeature('domain_aliases')) {
    redirectTo('index.php');
}

if (isset($_GET['id']) && $_GET['id'] !== '') {
	$sub_id = $_GET['id'];
	$dmn_id = get_user_domain_id($_SESSION['user_id']);

	$query = "
		SELECT
			`subdomain_alias_id`,
			`subdomain_alias_name`
		FROM
			`subdomain_alias` JOIN `domain_aliasses`
		ON
			`subdomain_alias`.`alias_id` = `domain_aliasses`.`alias_id`
		WHERE
			`domain_id` = ?
		AND
			`subdomain_alias_id` = ?
	";

	$rs = exec_query($query, array($dmn_id, $sub_id));
	$sub_name = $rs->fields['subdomain_alias_name'];

	if ($rs->recordCount() == 0) {
		redirectTo('domains_manage.php');
	}

	// check for mail accounts
	// TODO use prepared statement for constants
	$query = "SELECT COUNT(`mail_id`) AS cnt FROM `mail_users` WHERE (`mail_type` LIKE '".MT_ALSSUB_MAIL."%' OR `mail_type` = '".MT_ALSSUB_FORWARD."') AND `sub_id` = ?";
	$rs = exec_query($query, $sub_id);

	if ($rs->fields['cnt'] > 0) {
		set_page_message(tr('Subdomain you are trying to remove has email accounts !<br>First remove them!'), 'error');
		redirectTo('domains_manage.php');
	}

	$query = "
		UPDATE
			`subdomain_alias`
		SET
			`subdomain_alias_status` = 'delete'
		WHERE
			`subdomain_alias_id` = ?
	";

	$rs = exec_query($query, $sub_id);

	$query = "
		UPDATE
			`ssl_certs`
		SET
			`status` = 'delete'
		WHERE
			`id` = ?
		AND
			`type` = 'alssub'
	";

	$rs = exec_query($query, $sub_id);
	send_request();
	write_log($_SESSION['user_logged'].": delete alias subdomain: ".$sub_name, E_USER_NOTICE);
	set_page_message(tr('Subdomain alias scheduled for deletion.'), 'success');
	redirectTo('domains_manage.php');

} else {
	redirectTo('domains_manage.php');
}
