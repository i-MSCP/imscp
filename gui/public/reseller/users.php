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
 * Generates users list.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  int $resellerId Reseller unique identifier
 * @return void
 */
function generate_users_list($tpl, $resellerId)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $rows_per_page = $cfg->DOMAIN_ROWS_PER_PAGE;

    if (isset($_POST['details']) && !empty($_POST['details'])) {
        $_SESSION['details'] = $_POST['details'];
    } else {
        if (!isset($_SESSION['details'])) {
            $_SESSION['details'] = 'hide';
        }
    }

    if (isset($_GET['psi']) && $_GET['psi'] == 'last') {
        if (isset($_SESSION['search_page'])) {
            $_GET['psi'] = $_SESSION['search_page'];
        } else {
            unset($_GET['psi']);
        }
    }

    // Search request generated?
    if (isset($_POST['search_for']) && !empty($_POST['search_for'])) {
        $_SESSION['search_for'] = trim(clean_input($_POST['search_for']));
        $_SESSION['search_common'] = $_POST['search_common'];
        $_SESSION['search_status'] = $_POST['search_status'];
        $start_index = 0;
    } else {
        $start_index = isset($_GET['psi']) ? (int)$_GET['psi'] : 0;

        if (isset($_SESSION['search_for']) && !isset($_GET['psi'])) {
            // He have not got scroll through patient records.
            unset($_SESSION['search_for']);
            unset($_SESSION['search_common']);
            unset($_SESSION['search_status']);
        }
    }

    $_SESSION['search_page'] = $start_index;

    $search_query = '';
    $count_query = '';

    if (isset($_SESSION['search_for'])) {
        gen_manage_domain_query($search_query, $count_query, $resellerId,
                                $start_index, $rows_per_page, $_SESSION['search_for'],
                                $_SESSION['search_common'], $_SESSION['search_status']);

        gen_manage_domain_search_options($tpl, $_SESSION['search_for'],
                                         $_SESSION['search_common'],
                                         $_SESSION['search_status']);
    } else {
        gen_manage_domain_query($search_query, $count_query, $resellerId,
                                $start_index, $rows_per_page, 'n/a', 'n/a', 'n/a');

        gen_manage_domain_search_options($tpl, 'n/a', 'n/a', 'n/a');
    }

    $stmt = execute_query($count_query);
    $records_count = $stmt->fields['cnt'];
    $stmt = execute_query($search_query);

    if ($records_count == 0) {
        if (isset($_SESSION['search_for'])) {
            $tpl->assign(array(
                              'USERS_LIST' => '',
                              'SCROLL_PREV' => '',
                              'SCROLL_NEXT' => '',
                              'TR_VIEW_DETAILS' => tr('View aliases'),
                              'SHOW_DETAILS' => tr('Show')));

            set_page_message(tr('Not found user records matching the search criteria.'), 'info');

            unset($_SESSION['search_for']);
            unset($_SESSION['search_common']);
            unset($_SESSION['search_status']);
        } else {
            $tpl->assign(array(
                              'USERS_SEARCH' => '',
                              'USERS_LIST' => '',
                              'SCROLL_PREV' => '',
                              'SCROLL_PREV_GRAY' => '',
                              'SCROLL_NEXT' => '',
                              'SCROLL_NEXT_GRAY' => '',
                              'TR_VIEW_DETAILS' => tr('View aliases'),
                              'SHOW_DETAILS' => tr('Show')));

            set_page_message(tr('You do not have customers.'), 'info');
        }
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

        while (!$stmt->EOF) {
            if ($stmt->fields['domain_status'] == $cfg->ITEM_OK_STATUS) {
                $status_icon = 'ok.png';
                $status_domain = 'ok';
                $status_bool = true;
            } else if ($stmt->fields['domain_status'] == $cfg->ITEM_DISABLED_STATUS) {
                $status_icon = 'disabled.png';
                $status_domain = 'disabled';
                $status_bool = false;
            } else if ($stmt->fields['domain_status'] == $cfg->ITEM_ADD_STATUS
                       || $stmt->fields['domain_status'] == $cfg->ITEM_CHANGE_STATUS
                       || $stmt->fields['domain_status'] == $cfg->ITEM_TOENABLE_STATUS
                       || $stmt->fields['domain_status'] == $cfg->ITEM_RESTORE_STATUS
                       || $stmt->fields['domain_status'] == $cfg->ITEM_TODISABLED_STATUS
                       || $stmt->fields['domain_status'] == $cfg->ITEM_DELETE_STATUS
            ) {
                $status_icon = 'reload.png';
                $status_domain = 'reload';
                $status_bool = false;
            } else {
                $status_icon = 'error.png';
                $status_domain = 'error';
                $status_bool = false;
            }

            $status_url = $stmt->fields['domain_id'];

            $tpl->assign(array(
                              'STATUS_DOMAIN' => $status_domain,
                              'STATUS_ICON' => $status_icon,
                              'URL_CHANGE_STATUS' => $status_url));

			$admin_name = decode_idna($stmt->fields['domain_name']);
			
			if($status_bool == false) { // reload
				$tpl->assign('STATUS_RELOAD_TRUE', '');
				$tpl->assign('NAME', tohtml($admin_name));
				$tpl->parse('STATUS_RELOAD_FALSE', 'status_reload_false');
			} else {
				$tpl->assign('STATUS_RELOAD_FALSE', '');
				$tpl->assign('NAME', tohtml($admin_name));
				$tpl->parse('STATUS_RELOAD_TRUE', 'status_reload_true');
			}

            $dom_created = $stmt->fields['domain_created'];
            $dom_expires = $stmt->fields['domain_expires'];

            if ($dom_created == 0) {
                $dom_created = tr('N/A');
            } else {
                $dom_created = date($cfg->DATE_FORMAT, $dom_created);
            }

            if ($dom_expires == 0) {
                $dom_expires = tr('Not Set');
            } else {
                $dom_expires = date($cfg->DATE_FORMAT, $dom_expires);
            }

            $tpl->assign(array(
                              'CREATION_DATE' => $dom_created,
                              'EXPIRE_DATE' => $dom_expires,
                              'DOMAIN_ID' => $stmt->fields['domain_id'],
                              'ACTION' => tr('Delete'),
                              'USER_ID' => $stmt->fields['domain_admin_id'],
                              'CHANGE_INTERFACE' => tr('Switch'),
                              'DISK_USAGE' => ($stmt->fields['domain_disk_limit'])
                                  ? tr('%1$s of %2$s MB', round($stmt->fields['domain_disk_usage'] / 1024 / 1024, 1),
                                       $stmt->fields['domain_disk_limit'])
                                  : tr('%1$s of <b>unlimited</b> MB', round($stmt->fields['domain_disk_usage'] / 1024 / 1024, 1))));

            gen_domain_details($tpl, $stmt->fields['domain_id']);
            $tpl->parse('USER_ENTRY', '.user_entry');
            $stmt->moveNext();
        }

        //$tpl->parse('USER_LIST', 'users_list');
    }
}

/**
 * Checks for external event.
 *
 * @return void
 */
function check_externel_events()
{
    global $externel_event;

    if (isset($_SESSION['user_add3_added'])) {
        if ($_SESSION['user_add3_added'] === '_yes_') {
            set_page_message(tr('Domain account successfully scheduled for creation.'), 'success');

            $externel_event = '_on_';
            unset($_SESSION['user_add3_added']);
        }
    } elseif (isset($_SESSION['edit'])) {
        if ('_yes_' === $_SESSION['edit']) {
            set_page_message(tr('User data were successfully updated.'), 'success');
        } else {
            set_page_message(tr('User data were not updated.'), 'error');
        }
        unset($_SESSION['edit']);
    } elseif (isset($_SESSION['user_has_domain'])) {
        if ($_SESSION['user_has_domain'] == '_yes_') {
            set_page_message(tr('This user has domain record.<br>First remove the domain from the system.'), 'error');
        }

        unset($_SESSION['user_has_domain']);
    } elseif (isset($_SESSION['user_deleted'])) {
        if ($_SESSION['user_deleted'] == '_yes_') {
            set_page_message(tr('User scheduled for deletion.'), 'info');
        } else {
            set_page_message(tr('User was not deleted.'), 'error');
        }

        unset($_SESSION['user_deleted']);
    }
}

/************************************************************************************
 * Main script
 */
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/users.tpl',
		'users_list' => 'page',
		'user_entry' => 'users_list',
		'status_reload_true' => 'users_list',
		'status_reload_false' => 'users_list',
		'user_details' => 'users_list',
		'page_message' => 'layout',
		'scroll_prev_gray' => 'page',
		'scroll_prev' => 'page',
		'scroll_next_gray', 'page',
		'scroll_next' => 'page',
		'edit_option' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Users'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_MANAGE_USERS' => tr('Manage users'),
		'TR_USERS' => tr('Users'),
		'TR_USER_STATUS' => tr('Status'),
		'TR_DETAILS' => tr('Details'),
		'TR_SEARCH' => tr('Search'),
		'TR_USERNAME' => tr('Username'),
		'TR_ACTION' => tr('Actions'),
		'TR_CREATION_DATE' => tr('Creation date'),
		'TR_EXPIRE_DATE' => tr('Expire date'),
		'TR_CHANGE_USER_INTERFACE' => tr('Switch to user interface'),
		'TR_BACK' => tr('Back'),
		'TR_TITLE_BACK' => tr('Return to previous menu'),
		'TR_TABLE_NAME' => tr('Users list'),
		'TR_MESSAGE_CHANGE_STATUS' => tr('Are you sure you want to change the status of %s?', true, '%s'),
		'TR_MESSAGE_DELETE_ACCOUNT' => tr('Are you sure you want to delete %s?', true, '%s'),
		'TR_STAT' => tr('Stats'),
		'VL_MONTH' => date('m'),
		'VL_YEAR' => date('Y'),
		'TR_EDIT_DOMAIN' => tr('Edit Domain'),
		'TR_EDIT_USER' => tr('Edit User'),
		'TR_BW_USAGE' => tr('Bandwidth'),
		'TR_DISK_USAGE' => tr('Disk'),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_NEXT' => tr('Next')));

if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
    $tpl->assign('EDIT_OPTION', '');
}

generateNavigation($tpl);
generate_users_list($tpl, $_SESSION['user_id']);
check_externel_events();

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();


// Cleanup
unset($_SESSION['dmn_name']);
unset($_SESSION['ch_hpprops']);
unset($_SESSION['local_data']);
unset($_SESSION['dmn_ip']);
unset($_SESSION['dmn_id']);
unset($GLOBALS['dmn_name']);
unset($GLOBALS['ch_hpprops']);
unset($GLOBALS['local_data']);
unset($GLOBALS['user_add3_added']);
unset($GLOBALS['user_add3_added']);
unset($GLOBALS['dmn_ip']);
unset($GLOBALS['dmn_id']);
unsetMessages();
