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

$cfg = iMSCP_Registry::get('Config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/software_reseller.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('logged_from', 'page');
$tpl->define_dynamic('list_software', 'page');
$tpl->define_dynamic('no_software_list', 'page');
$tpl->define_dynamic('list_softwaredepot', 'page');
$tpl->define_dynamic('no_softwaredepot_list', 'page');
$tpl->define_dynamic('no_reseller_list', 'page');
$tpl->define_dynamic('list_reseller', 'page');

if (isset($_GET['id'])){
	if (isset($_GET['id']) && is_numeric($_GET['id'])) {
		$reseller_id = $_GET['id'];
	} else {
		set_page_message(tr('Wrong reseller id.'));
		header('Location: software_manage.php');
	}

} else {
	set_page_message(tr('Wrong reseller id.'));
	header('Location: software_manage.php');
}

function get_installed_res_software (&$tpl, &$sql, $reseller_id) {
	$query="
		SELECT
			a.`software_id` as id,
			a.`software_name` as name,
			a.`software_version` as version,
			a.`software_language` as language,
			a.`software_type` as type,
			a.`software_desc` as description,
			a.`reseller_id`,
			a.`software_archive` as filename,
			a.`software_status` as swstatus,
			a.`software_depot` as swdepot,
			b.`admin_id`,
			b.`admin_name` as admin
		FROM
			`web_software` a,
			`admin` b
		WHERE
			a.`reseller_id` = b.`admin_id`
		AND
			a.`reseller_id` = ?
		AND
			a.`software_status` = 'ok' 
		ORDER BY
			a.`reseller_id` ASC,
			a.`software_type` ASC,
			a.`software_name` ASC
	";
	$rs = exec_query($sql, $query, $reseller_id);
	if ($rs->recordCount() > 0) {
		while(!$rs->EOF) {
				$query2="
					SELECT
						`domain`.`domain_id` as did,
						`domain`.`domain_name` as domain,
						`web_software_inst`.`domain_id` as wdid,
						`web_software_inst`.`software_id` as sid,
						`web_software`.`software_id` as wsid
					FROM
						`domain`,
						`web_software`,
						`web_software_inst`
					WHERE
						`web_software_inst`.`software_id`= ?
					AND
						`web_software`.`software_id` = `web_software_inst`.`software_id`
					AND
						`domain`.`domain_id` = `web_software_inst`.`domain_id`
				";
				$rs2 = exec_query($sql, $query2, $rs->fields['id']);
				if($rs2->recordCount() > 0){
					$swinstalled_domain = tr('This package is installed on following domain(s):');
					$swinstalled_domain .= "<ul>";
					while(!$rs2->EOF){
						$swinstalled_domain .= "<li>".$rs2->fields['domain']."</li>";
						$rs2->moveNext();
					}
					$swinstalled_domain .= "</ul>";
					$tpl->assign('SW_INSTALLED', $swinstalled_domain);
				} else {
					$tpl->assign('SW_INSTALLED', tr('This package is not installed'));
				}
				if ($rs->fields['swdepot'] == "yes") {
					$tpl->assign('TR_NAME', tr('%1$s - (Softwaredepot)', $rs->fields['name']));
				} else {
					$tpl->assign('TR_NAME', $rs->fields['name']);
				}
				$tpl->assign(
						array(
							'LINK_COLOR' 		=> '#000000',
							'TR_TOOLTIP' 		=> $rs->fields['description'],
							'TR_VERSION' 		=> $rs->fields['version'],
							'TR_LANGUAGE' 		=> $rs->fields['language'],
							'TR_TYPE' 			=> $rs->fields['type'],
							'TR_ADMIN' 			=> 'List',
							'TR_RESELLER' 		=> $rs->fields['admin'],
							'TR_SOFTWARE_DEPOT' => tr('%1$s`s - Software', $rs->fields['admin'])
							)
						);
			$tpl->parse('LIST_SOFTWAREDEPOT', '.list_softwaredepot');
			$rs->moveNext();
		}
		$tpl->assign('NO_SOFTWAREDEPOT_LIST', '');
	} else {
		$query="
			SELECT
				`admin_name` as admin
			FROM
				`admin`
			WHERE
				`admin_id` = ?
		";
		$reseller = exec_query($sql, $query, $reseller_id);
		if ($reseller->recordCount() > 0) {
			$tpl->assign(
					array(
						'NO_SOFTWAREDEPOT' => tr('No software available!'),
						'TR_SOFTWARE_DEPOT' => tr('%1$s`s - Software', $reseller->fields['admin']),
						)
					);
			$tpl->parse('NO_SOFTWAREDEPOT_LIST', '.no_softwaredepot_list');
			$tpl->assign('LIST_SOFTWAREDEPOT', '');
		} else {
			set_page_message(tr('Wrong reseller id.'));
			header('Location: software_manage.php');
		}
	}
	return $rs->recordCount();
}

function get_reseller_software (&$tpl, &$sql) {
	$query="
		SELECT
			t1.`admin_id` as reseller_id,
			t1.`admin_name` as reseller
		FROM
			`admin` t1
		LEFT JOIN
				`reseller_props` AS t2 ON t2.reseller_id = t1.`admin_id`
		WHERE
			t1.`admin_type` = 'reseller'
		AND
			t2.`software_allowed` = 'yes' 
		ORDER BY
			t1.`admin_id` ASC
	";
	$rs = exec_query($sql, $query, array());
	if ($rs->recordCount() > 0) {
		while(!$rs->EOF) {
			$query="
				SELECT
					`software_id`
				FROM
					`web_software`
				WHERE
					`reseller_id` = ?
			";
			$rssoftware = exec_query($sql, $query, $rs->fields['reseller_id']);
			$software_ids = array();
			while ($data = $rssoftware->fetchRow()) {
				$software_ids[] = $data['software_id'];
			}
			$query="
				SELECT
					count(`software_id`) as swdepot
				FROM
					`web_software`
				WHERE
					`software_active` = 1
				AND
					`software_depot` = 'yes'
				AND
					`reseller_id` = ?
			";
			$rscountswdepot = exec_query($sql, $query, $rs->fields['reseller_id']);
			$query="
				SELECT
					count(`software_id`) as waiting
				FROM
					`web_software`
				WHERE
					`software_active` = 0
				AND
					`reseller_id` = ?
			";
			$rscountwaiting = exec_query($sql, $query, $rs->fields['reseller_id']);
			$query="
				SELECT
					count(`software_id`) as activated
				FROM
					`web_software`
				WHERE `software_active` = 1
				AND
					`reseller_id` = ?
			";
			$rscountactivated = exec_query($sql, $query, $rs->fields['reseller_id']);
			if(count($software_ids) > 0){
				$query="
					SELECT
						count(`domain_id`) as in_use
					FROM
						`web_software_inst`
					WHERE
						`software_id`
					IN
						(".implode(',', $software_ids ).")
					AND
						`software_status` = 'ok'
				";
				$rscountin_use = exec_query($sql, $query, array());
				$sw_in_use = $rscountin_use->fields['in_use'];
			}else{
				$sw_in_use = 0;
			}
			$tpl->assign(
					array(
						'RESELLER_NAME' 			=> $rs->fields['reseller'],
						'RESELLER_ID' 				=> $rs->fields['reseller_id'],
						'RESELLER_COUNT_SWDEPOT' 	=> $rscountswdepot->fields['swdepot'],
						'RESELLER_COUNT_WAITING' 	=> $rscountwaiting->fields['waiting'],
						'RESELLER_COUNT_ACTIVATED' 	=> $rscountactivated->fields['activated'],
						'RESELLER_SOFTWARE_IN_USE' 	=> $sw_in_use
						)
					);
			$tpl->parse('LIST_RESELLER', '.list_reseller');
			$rs->moveNext();
		}
		$tpl->assign('NO_RESELLER_LIST', '');
	} else {
		$tpl->assign('NO_RESELLER', tr('No reseller with activated software installer found!'));
		$tpl->parse('NO_RESELLER_LIST', '.no_reseller_list');
		$tpl->assign('LIST_RESELLER', '');
	}
	return $rs->recordCount();
}

$tpl->assign(
		array(
			'TR_MANAGE_SOFTWARE_PAGE_TITLE' => tr('i-MSCP - Application Management'),
			'THEME_COLOR_PATH'				=> "../themes/{$cfg->USER_INITIAL_THEME}",
			'THEME_CHARSET'					=> tr('encoding'),
			'ISP_LOGO'						=> get_logo($_SESSION['user_id'])
			)
	);

$software_cnt = get_installed_res_software (&$tpl, &$sql, $_GET['id']);
$res_cnt = get_reseller_software (&$tpl, &$sql, $_GET['id']);

$tpl->assign(
		array(
			'RESELLER_ID'					=> $reseller_id,
			'TR_SOFTWARE_INSTALLED' 		=> tr('Installed on'),
			'TR_SOFTWARE_RIGHTS' 			=> tr('Softwarerights'),
			'TR_SOFTWAREDEPOT_COUNT' 		=> tr('Software total'),
			'TR_SOFTWAREDEPOT_NUM' 			=> $software_cnt,
			'TR_AWAITING_ACTIVATION' 		=> tr('Awaiting Activation'),
			'TR_ACTIVATED_SOFTWARE' 		=> tr('Reseller list'),
			'TR_SOFTWARE_NAME' 				=> tr('Application'),
			'TR_SOFTWARE_VERSION' 			=> tr('App-Version'),
			'TR_SOFTWARE_LANGUAGE' 			=> tr('Language'),
			'TR_SOFTWARE_TYPE' 				=> tr('Type'),
			'TR_RESELLER_NAME' 				=> tr('Reseller'),
			'TR_RESELLER_ACT_COUNT' 		=> tr('Reseller total'),
			'TR_RESELLER_ACT_NUM' 			=> $res_cnt,
			'TR_RESELLER_COUNT_SWDEPOT' 	=> tr('Softwaredepot'),
			'TR_RESELLER_COUNT_WAITING' 	=> tr('Waiting for activation'),
			'TR_RESELLER_COUNT_ACTIVATED' 	=> tr('Activated software'),
			'TR_RESELLER_SOFTWARE_IN_USE' 	=> tr('Total installations'),
			'TR_ADMIN_SOFTWARE_PAGE_TITLE' 	=> tr('i-MSCP - Application Management')
			)
	);

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_users_manage.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_users_manage.tpl');

gen_logged_from($tpl);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');

$tpl->prnt();

if ($cfg->DUMP_GUI_DEBUG) {
	dump_gui_debug();
}

unset_messages();
?>
