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

/************************************************************************************
 * Script functions
 */

/**
 * Generates aliases list.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  int $reseller_id Reseller unique identifier
 * @return void
 */
function generate_als_list($tpl, $reseller_id)
{
    /** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	list(,,,,,,$uals_current) = generate_reseller_user_props($reseller_id);

	$resellerProperties = imscp_getResellerProperties($reseller_id);

	if ($uals_current >= $resellerProperties['max_als_cnt'] && $resellerProperties['max_als_cnt'] != '0') {
		$tpl->assign('ALS_ADD_BUTTON', '');
	}

	$start_index = 0;

	$rows_per_page = $cfg->DOMAIN_ROWS_PER_PAGE;

	$current_psi = 0;
	$_SESSION['search_for'] = '';
	$search_common = '';
	$search_for = '';

	if (isset($_GET['psi'])) {
		$start_index = $_GET['psi'];
		$current_psi = $_GET['psi'];
	}

	if (isset($_POST['uaction']) && !empty($_POST['uaction'])) {

		$_SESSION['search_for'] = trim(clean_input($_POST['search_for']));
		$_SESSION['search_common'] = $_POST['search_common'];
		$search_for = $_SESSION['search_for'];
		$search_common = $_SESSION['search_common'];

	} elseif(isset($_SESSION['search_for']) && !isset($_GET['psi'])) {
			unset($_SESSION['search_for']);
			unset($_SESSION['search_common']);
	}

    $tpl->assign(array(
                      'PSI' => $current_psi,
                      'SEARCH_FOR' => tohtml($search_for),
                      'TR_SEARCH' => tr('Search'),
                      'M_ALIAS_NAME' => tr('Alias name'),
                      'M_ACCOUNT_NAME' => tr('Account name')));

	if (isset($_SESSION['search_for']) && $_SESSION['search_for'] != '') {
		if (isset($search_common) && $search_common == 'alias_name') {
			$query = "
				SELECT
					t1.*, t2.`domain_id`, t2.`domain_name`, t2.`domain_created_id`
				FROM
					`domain_aliasses` AS t1, `domain` AS t2
				WHERE
					`alias_name` RLIKE '$search_for'
				AND
					t2.`domain_created_id` = ?
				AND
					t1.`domain_id` = t2.`domain_id`
				ORDER BY
					t1.`alias_name` ASC
				LIMIT
					$start_index, $rows_per_page
			";

			// count query
			$count_query = "
				SELECT
					COUNT(`alias_id`) AS cnt
				FROM
					`domain_aliasses` AS t1, `domain` AS t2
				WHERE
					t2.`domain_created_id` = ?
				AND
					`alias_name` RLIKE '$search_for'
				AND
					t1.`domain_id` = t2.`domain_id`
			";
		} else {
			$query = "
				SELECT
					t1.*, t2.`domain_id`, t2.`domain_name`, t2.`domain_created_id`
				FROM
					`domain_aliasses` AS t1, `domain` AS t2
				WHERE
					t2.`domain_name` RLIKE '$search_for'
				AND
					t1.`domain_id` = t2.`domain_id`
				AND
					t2.`domain_created_id` = ?
				ORDER BY
					t1.`alias_name` ASC
				LIMIT
					$start_index, $rows_per_page
				;
			";

			// count query
			$count_query = "
				SELECT
					COUNT(`alias_id`) AS cnt
				FROM
					`domain_aliasses` AS t1, `domain` AS t2
				WHERE
					t2.`domain_created_id` = ?
				AND
					t2.`domain_name` RLIKE '$search_for'
				AND
					t1.`domain_id` = t2.`domain_id`
			";
		}
	} else {
		$query = "
			SELECT
				t1.*, t2.`domain_id`, t2.`domain_name`, t2.`domain_created_id`
			FROM
				`domain_aliasses` AS t1, `domain` AS t2
			WHERE
				t1.`domain_id` = t2.`domain_id`
			AND
				t2.`domain_created_id` = ?
			ORDER BY
				t1.`alias_name` ASC
			LIMIT
				$start_index, $rows_per_page
		";

		// count query
		$count_query = "
			SELECT
				COUNT(`alias_id`) AS cnt
			FROM
				`domain_aliasses` AS t1, `domain` AS t2
			WHERE
				t1.`domain_id` = t2.domain_id
			AND
				t2.`domain_created_id` = ?
		";
	}

	// let's count
	$rs = exec_query($count_query, $reseller_id);
	$records_count = $rs->fields['cnt'];

	// Get all alias records
	$rs = exec_query($query, $reseller_id);

	if ($records_count == 0) {
		if (isset($_SESSION['search_for']) && $_SESSION['search_for'] != '') {
            $tpl->assign(array(
                              'TABLE_LIST' => '',
                              'USERS_LIST' => '',
                              'SCROLL_PREV' => '',
                              'SCROLL_NEXT' => '',
                              'M_DOMAIN_NAME_SELECTED' => '',
                              'M_ACCOUN_NAME_SELECTED' => ''));
		} else {
            $tpl->assign(array(
                             'SEARCH_FORM' => '',
                              'TABLE_LIST' => '',
                              'TABLE_HEADER' => '',
                              'USERS_LIST' => '',
                              'SCROLL_PREV' => '',
                              'SCROLL_PREV_GRAY' => '',
                              'SCROLL_NEXT' => '',
                             'SCROLL_NEXT_GRAY' => ''));
		}

		if (isset($_SESSION['search_for'])) {
            set_page_message(tr('Not found user records matching the search criteria', 'info'));
		} else {
			if (isset($_SESSION['almax'])) {
				if ($_SESSION['almax'] === '_yes_') {
                    set_page_message(tr('Domain aliases limit reached.'), 'error');
                } else {
                    set_page_message(tr('You do not have order for domain aliases.'), 'info');
                }
				unset($_SESSION['almax']);
			} else {
                set_page_message(tr('You do not have order for domain aliases.'), 'info');
			}
		}
		return;
	} else {
		$prev_si = $start_index - $rows_per_page;

		if ($start_index == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
            $tpl->assign(array(
                              'SCROLL_PREV_GRAY' => '',
                              'PREV_PSI' => $prev_si));
		}

		$next_si = $start_index + $rows_per_page;

		if ($next_si + 1 > $records_count) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
            $tpl->assign(array(
                              'SCROLL_NEXT_GRAY' => '',
                              'NEXT_PSI' => $next_si));
		}
	}

	$i = 1;
	while (!$rs->EOF) {
		$als_id = $rs->fields['alias_id'];
		$als_name = $rs->fields['alias_name'];
		$als_mount_point = ($rs->fields['alias_mount'] != '')
			? $rs->fields['alias_mount'] : '/';
		$als_status = $rs->fields['alias_status'];
		$als_ip_id = $rs->fields['alias_ip_id'];
		$als_fwd = $rs->fields['url_forward'];
		$show_als_fwd = ($als_fwd == 'no') ? "-" : $als_fwd;
		$domain_name = decode_idna($rs->fields['domain_name']);

		$query = "SELECT `ip_number`, `ip_domain` FROM `server_ips` WHERE `ip_id` = ?";

		$alsip_r = exec_query($query, $als_ip_id);
		$alsip_d = $alsip_r->fetchRow();

		$als_ip = $alsip_d['ip_number'];
		$als_ip_name = $alsip_d['ip_domain'];

		$page_cont = ($i % 2 == 0) ? 'content' : 'content2';

		if ($als_status === $cfg->ITEM_OK_STATUS) {
			$delete_link = "alias_delete.php?del_id=" . $als_id;
			$edit_link = "alias_edit.php?edit_id=" . $als_id;
			$action_text = tr('Delete');
			$edit_text = tr('Edit');
            $status_bool = true;
		} else if ($als_status === $cfg->ITEM_ORDERED_STATUS) {
			$delete_link = 'alias_order.php?action=delete&del_id=' . $als_id;
			$edit_link = 'alias_order.php?action=activate&act_id=' . $als_id;
			$action_text = tr('Delete order');
			$edit_text = tr('Activate');
            $status_bool = false;
		} else {
			$delete_link = '#';
			$edit_link = '#';
			$action_text = tr('N/A');
			$edit_text = tr('N/A');
            $status_bool = false;
		}
		$als_status = translate_dmn_status($als_status);
		$als_name = decode_idna($als_name);
		$show_als_fwd = decode_idna($show_als_fwd);

		if (isset($_SESSION['search_common'])
			&& $_SESSION['search_common'] === 'account_name'
        ){
			$domain_name_selected = '';
			$account_name_selected = $cfg->HTML_SELECTED;
		} else {
			$domain_name_selected = $cfg->HTML_SELECTED;
			$account_name_selected = '';
		}

        if($status_bool == false) { // reload
            $tpl->assign('STATUS_RELOAD_TRUE', '');
            $tpl->assign('NAME', tohtml($als_name));
            $tpl->parse('STATUS_RELOAD_FALSE', 'status_reload_false');
        } else {
            $tpl->assign('STATUS_RELOAD_FALSE', '');
            $tpl->assign('NAME', tohtml($als_name));
            $tpl->parse('STATUS_RELOAD_TRUE', 'status_reload_true');
        }

        $tpl->assign(array(
                          'NAME' => tohtml($als_name),
                          'ALIAS_IP' => tohtml("$als_ip ($als_ip_name)"),
                          'REAL_DOMAIN' => tohtml($domain_name),
                          'REAL_DOMAIN_MOUNT' => tohtml($als_mount_point),
                          'FORWARD' => tohtml($show_als_fwd),
                          'STATUS' => $als_status,
                          'ID' => $als_id,
                          'DELETE' => $action_text,
                          'CONTENT' => $page_cont,
                          'DELETE_LINK' => $delete_link,
                          'EDIT_LINK' => $edit_link,
                          'EDIT' => $edit_text,
                          'M_DOMAIN_NAME_SELECTED' => $domain_name_selected,
                          'M_ACCOUN_NAME_SELECTED' => $account_name_selected));

		$i++;
		$tpl->parse('TABLE_ITEM', '.table_item');
		$rs->moveNext();
	}
}

/**
 * Generate messages
 *
 * @return void
 */
function generate_als_messages()
{
    if(isset($_SESSION['user_page_message'])) {
        return;
	} elseif (isset($_SESSION["dahavemail"])) {
        set_page_message(tr('Domain alias you are trying to remove has email accounts.<br>First remove them.'), 'error');
		unset($_SESSION['dahavemail']);
	} elseif (isset($_SESSION["dahaveftp"])) {
        set_page_message(tr('Domain alias you are trying to remove has FTP accounts.<br>First remove them.'), 'error');
		unset($_SESSION['dahavemail']);
	} elseif (isset($_SESSION["aldel"])) {
		if ('_yes_' === $_SESSION['aldel']) {
            set_page_message(tr('Domain alias successfully scheduled for deletion.'), 'success');
        } else {
            set_page_message(tr('Domain alias not scheduled for deletion.'), 'error');
        }
		unset($_SESSION['aldel']);
	} elseif (isset($_SESSION['aladd'])) {
		if ('_yes_' === $_SESSION['aladd']) {
            set_page_message(tr('Domain alias successfully scheduled for addition'), 'success');
        } else {
            set_page_message(tr('Domain alias not scheduled for addition.'), 'error');
        }
		unset($_SESSION['aladd']);
	} elseif (isset($_SESSION['aledit'])) {
		if ('_yes_' === $_SESSION['aledit']) {
            set_page_message(tr('Domain alias successfully modified'), 'success');
        } else {
            set_page_message(tr('Domain alias not modified.'), 'error');
        }
		unset($_SESSION['aledit']);
	} elseif (isset($_SESSION['orderaldel'])) {
		if ('_no_' === $_SESSION['orderaldel']) {
            set_page_message(tr('Order for domain alias not deleted.'), 'error');
		}
		unset($_SESSION['orderaldel']);
	} elseif (isset($_SESSION['orderalact'])) {
		if ('_yes_' === $_SESSION['orderalact']) {
            set_page_message(tr('Domain alias successfully scheduled for addition.'), 'success');
        } else {
            set_page_message(tr('Domain alias not scheduled for addition.'), 'error');
        }
		unset($_SESSION['orderalact']);
	} elseif (isset($_SESSION['almax'])) {
		if ('_yes_' === $_SESSION['almax']) {
            set_page_message(tr('Domain alias limit reached.'), 'error');
        }
		unset($_SESSION['almax']);
	}
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if(!resellerHasFeature('domain_aliases')) {
	return 'index.php';
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

/** @var $tpl iMSCP_pTemplate */
$tpl = new iMSCP_pTemplate();

$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/domain_alias.tpl',
		'page_message' => 'layout',
		'table_list' => 'page',
		'table_header' => 'page',
		'table_item' => 'table_list',
		'status_reload_true' => 'table_list',
		'status_reload_false' => 'table_list',
		'scroll_prev' => 'page',
		'scroll_next_gray' => 'page',
		'scroll_next' => 'page',
		'als_add_button' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Manage Domain/Alias'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

generateNavigation($tpl);
generate_als_list($tpl, $_SESSION['user_id']);
generate_als_messages();
generatePageMessage($tpl);

$tpl->assign(
	array(
		'TR_MANAGE_ALIAS' => tr('Manage alias'),
		'TR_NAME' => tr('Name'),
		'TR_REAL_DOMAIN' => tr('Real domain'),
		'TR_FORWARD' => tr('Forward'),
		'TR_STATUS' => tr('Status'),
		'TR_ACTION' => tr('Action'),
		'TR_ADD_ALIAS' => tr('Add alias'),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', true, '%s'),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_NEXT' => tr('Next')));

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
