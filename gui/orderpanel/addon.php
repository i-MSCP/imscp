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

require '../include/ispcp-lib.php';

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('PURCHASE_TEMPLATE_PATH') . '/addon.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('purchase_header', 'page');
$tpl->define_dynamic('purchase_footer', 'page');

/**
 * functions start
 */

function addon_domain($dmn_name) {

	if (!validates_dname($dmn_name)) {
		global $validation_err_msg;
		set_page_message(tr($validation_err_msg));
		return;
	}

	// Should be performed after domain name validation now
	$dmn_name = encode_idna(strtolower($dmn_name));

	if(ispcp_domain_exists($dmn_name, 0)) {
		set_page_message(tr('Domain with that name already exists on the system!'));
		return;
	}

	$_SESSION['domainname'] = $dmn_name;
	user_goto('address.php');
}

function is_plan_available(&$sql, $plan_id, $user_id) {
	if (Config::exists('HOSTING_PLANS_LEVEL') &&
		Config::get('HOSTING_PLANS_LEVEL') === 'admin') {
		$query = "
			SELECT
				*
			FROM
				`hosting_plans`
			WHERE
				`id` = ?
			";

			$rs = exec_query($sql, $query, array($plan_id));
        } else {
			$query = "
                        SELECT
                                *
                        FROM
                                `hosting_plans`
                        WHERE
                                `reseller_id` = ?
                        AND
                                `id` = ?
                ";

                $rs = exec_query($sql, $query, array($user_id, $plan_id));
        }
	return $rs->RecordCount() > 0 && $rs->fields['status'] != 0;
}

/**
 * functions end
 */

/**
 * static page messages.
 */

if (isset($_SESSION['user_id'])) {
	$user_id = $_SESSION['user_id'];

	if (isset($_SESSION['plan_id'])) {
		$plan_id = $_SESSION['plan_id'];
	} else if (isset($_GET['id'])) {
		$plan_id = $_GET['id'];
		if (is_plan_available($sql, $plan_id, $user_id)) {
			$_SESSION['plan_id'] = $plan_id;
		} else {
			system_message(tr('This hosting plan is not available for purchase'));
		}
	} else {
		system_message(tr('You do not have permission to access this interface!'));
	}
} else {
	system_message(tr('You do not have permission to access this interface!'));
}

if (isset($_SESSION['domainname'])) {
	user_goto('address.php');
}

if (isset($_POST['domainname']) && $_POST['domainname'] != '') {
	addon_domain($_POST['domainname']);
}

gen_purchase_haf($tpl, $sql, $user_id);
gen_page_message($tpl);

$tpl->assign(
	array(
		'DOMAIN_ADDON'		=> tr('Add On A Domain'),
		'TR_DOMAIN_NAME'	=> tr('Domain name'),
		'TR_CONTINUE'		=> tr('Continue'),
		'TR_EXAMPLE'		=> tr('(e.g. domain-of-your-choice.com)'),
		'THEME_CHARSET'		=> tr('encoding'),
	)
);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG')) {
	dump_gui_debug();
}

unset_messages();
