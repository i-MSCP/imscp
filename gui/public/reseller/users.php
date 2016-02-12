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
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/***********************************************************************************************************************
 * Functions
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
    $cfg = iMSCP_Registry::get('config');

    $rowsPerPage = $cfg['DOMAIN_ROWS_PER_PAGE'];

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
        $startIndex = 0;
    } else {
        $startIndex = isset($_GET['psi']) ? (int)$_GET['psi'] : 0;

        if (isset($_SESSION['search_for']) && !isset($_GET['psi'])) {
            // He have not got scroll through patient records.
            unset($_SESSION['search_for']);
            unset($_SESSION['search_common']);
            unset($_SESSION['search_status']);
        }
    }

    $_SESSION['search_page'] = $startIndex;
    $searchQuery = '';
    $countQuery = '';

    if (isset($_SESSION['search_for'])) {
        gen_manage_domain_query(
            $searchQuery, $countQuery, $resellerId, $startIndex, $rowsPerPage, $_SESSION['search_for'],
            $_SESSION['search_common'], $_SESSION['search_status']
        );
        gen_manage_domain_search_options(
            $tpl, $_SESSION['search_for'], $_SESSION['search_common'], $_SESSION['search_status']
        );
    } else {
        gen_manage_domain_query(
            $searchQuery, $countQuery, $resellerId, $startIndex, $rowsPerPage, 'n/a', 'n/a', 'n/a'
        );
        gen_manage_domain_search_options($tpl, 'n/a', 'n/a', 'n/a');
    }

    $stmt = execute_query($countQuery);
    $rowCount = $stmt->fields['cnt'];
    $stmt = execute_query($searchQuery);

    if ($rowCount == 0) {
        if (isset($_SESSION['search_for'])) {
            $tpl->assign(array(
                'USR_MESSAGE' => tr('No records found matching the search criteria.'),
                'USERS_LIST' => '',
                'SCROLL_PREV' => '',
                'SCROLL_NEXT' => '',
                'TR_VIEW_DETAILS' => tr('View aliases'),
                'SHOW_DETAILS' => tr('Show')
            ));
            unset($_SESSION['search_for']);
            unset($_SESSION['search_common']);
            unset($_SESSION['search_status']);
        } else {
            $tpl->assign(array(
                'USERS_SEARCH' => '',
                'USR_MESSAGE' => tr('No customer accounts found.'),
                'USERS_LIST' => '',
                'SCROLL_PREV' => '',
                'SCROLL_PREV_GRAY' => '',
                'SCROLL_NEXT' => '',
                'SCROLL_NEXT_GRAY' => '',
                'TR_VIEW_DETAILS' => tr('View aliases'),
                'SHOW_DETAILS' => tr('Show')
            ));
        }

        $tpl->parse('USR_MESSAGE', 'usr_message');
    } else {
        $prevSi = $startIndex - $rowsPerPage;

        if ($startIndex == 0) {
            $tpl->assign('SCROLL_PREV', '');
        } else {
            $tpl->assign(array(
                'SCROLL_PREV_GRAY' => '',
                'PREV_PSI' => $prevSi
            ));
        }

        $nextSi = $startIndex + $rowsPerPage;

        if ($nextSi + 1 > $rowCount) {
            $tpl->assign('SCROLL_NEXT', '');
        } else {
            $tpl->assign(array(
                'SCROLL_NEXT_GRAY' => '',
                'NEXT_PSI' => $nextSi
            ));
        }

        while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            if ($row['admin_status'] == 'ok' && $row['domain_status'] == 'ok') {
                $statusIcon = 'ok';
                $statusDomain = translate_dmn_status($row['domain_status']);
                $domainStatusTooltip = tr('Click to deactivate');
                $statusBool = true;
                $canChange = true;
            } else if ($row['domain_status'] == 'disabled') {
                $statusIcon = 'disabled';
                $statusDomain = translate_dmn_status($row['domain_status']);
                $domainStatusTooltip = tr('Click to activate');
                $statusBool = false;
                $canChange = true;
            } else if (
                $row['domain_status'] == 'toadd' || $row['domain_status'] == 'torestore' ||
                $row['domain_status'] == 'tochange' || $row['domain_status'] == 'toenable' ||
                $row['domain_status'] == 'todisable' || $row['domain_status'] == 'todelete'
            ) {
                $statusIcon = 'reload';
                $statusDomain = $domainStatusTooltip = translate_dmn_status($row['admin_status'] != 'ok' ? $row['admin_status'] : $row['domain_status']);
                $statusBool = false;
                $canChange = false;
            } else {
                $statusIcon = 'error';
                $statusDomain = translate_dmn_status($row['admin_status'] != 'ok' ? $row['admin_status'] : $row['domain_status']);
                $domainStatusTooltip = tr('An unexpected error occurred. Please contact your administrator.');
                $statusBool = false;
                $canChange = false;
            }

            $domainId = $row['domain_id'];

            $tpl->assign(array(
                'DOMAIN_STATUS' => $statusDomain,
                'DOMAIN_STATUS_TOOLTIP' => $domainStatusTooltip,
                'STATUS_ICON' => $statusIcon,
                'DOMAIN_ID' => $domainId
            ));

            if ($canChange) {
                $tpl->assign('DOMAIN_STATUS_NOCHANGE', '');
                $tpl->parse('DOMAIN_STATUS_CHANGE', 'domain_status_change');
            } else {
                $tpl->assign('DOMAIN_STATUS_CHANGE', '');
                $tpl->parse('DOMAIN_STATUS_NOCHANGE', 'domain_status_nochange');
            }

            $adminName = decode_idna($row['domain_name']);

            if ($statusBool == false) { // reload
                $tpl->assign('STATUS_RELOAD_TRUE', '');
                $tpl->assign('NAME', tohtml($adminName));
                $tpl->parse('STATUS_RELOAD_FALSE', 'status_reload_false');
            } else {
                $tpl->assign('STATUS_RELOAD_FALSE', '');
                $tpl->assign('NAME', $adminName);
                $tpl->parse('STATUS_RELOAD_TRUE', 'status_reload_true');
            }

            $domainCreated = $row['domain_created'];

            if ($domainCreated == 0) {
                $domainCreated = tr('N/A');
            } else {
                $domainCreated = date($cfg['DATE_FORMAT'], $domainCreated);
            }

            $tpl->assign(array(
                'CREATION_DATE' => $domainCreated,
                'ACTION' => tr('Delete'),
                'USER_ID' => $row['domain_admin_id'],
                'CHANGE_INTERFACE' => tr('Switch'),
                'DISK_USAGE' => $row['domain_disk_limit']
                    ? tr('%1$s of %2$s', bytesHuman($row['domain_disk_usage']), mebibyteHuman($row['domain_disk_limit']))
                    : tr('%1$s of <b>unlimited</b>', bytesHuman($row['domain_disk_usage']))
            ));

            gen_domain_details($tpl, $row['domain_id']);
            $tpl->parse('USER_ENTRY', '.user_entry');
        }

        $tpl->assign('USR_MESSAGE', '');
        $tpl->parse('USER_LIST', 'users_list');
    }
}

/**
 * Checks for external event.
 *
 * @return void
 */
function check_externel_events()
{
    if (isset($_SESSION['edit'])) {
        if ('_yes_' == $_SESSION['edit']) {
            set_page_message(tr('User data were successfully updated.'), 'success');
        } else {
            set_page_message(tr('User data were not updated.'), 'error');
        }
        unset($_SESSION['edit']);
    } elseif (isset($_SESSION['user_has_domain'])) {
        if ($_SESSION['user_has_domain'] == '_yes_') {
            set_page_message(tr('This user has domain record.<br/>First remove the domain from the system.'), 'error');
        }

        unset($_SESSION['user_has_domain']);
    } elseif (isset($_SESSION['user_deleted'])) {
        if ($_SESSION['user_deleted'] == '_yes_') {
            set_page_message(tr('User scheduled for deletion.'), 'info');
        } else {
            set_page_message(tr('User has not been deleted.'), 'error');
        }

        unset($_SESSION['user_deleted']);
    }
}

/***********************************************************************************************************************
 * Main
 */
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);
check_login('reseller');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'reseller/users.tpl',
    'user_search' => 'page',
    'usr_message' => 'page',
    'users_list' => 'page',
    'user_entry' => 'users_list',
    'domain_status_change' => 'user_entry',
    'domain_status_nochange' => 'user_entry',
    'status_reload_true' => 'users_list',
    'status_reload_false' => 'users_list',
    'user_details' => 'users_list',
    'page_message' => 'layout',
    'scroll_prev_gray' => 'users_list',
    'scroll_prev' => 'users_list',
    'scroll_next_gray', 'users_list',
    'scroll_next' => 'users_list',
));

$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Reseller / Customers / Overview'),
    'TR_USER_STATUS' => tr('Status'),
    'TR_DETAILS' => tr('Details'),
    'TR_SEARCH' => tr('Search'),
    'TR_USERNAME' => tr('Username'),
    'TR_ACTION' => tr('Actions'),
    'TR_CREATION_DATE' => tr('Creation date'),
    'TR_STAT' => tr('Stats'),
    'TR_EDIT_DOMAIN' => tr('Edit domain'),
    'TR_EDIT_USER' => tr('Edit user'),
    'TR_DISK_USAGE' => tr('Disk usage'),
    'TR_PREVIOUS' => tr('Previous'),
    'TR_NEXT' => tr('Next')
));

generateNavigation($tpl);
generate_users_list($tpl, $_SESSION['user_id']);
check_externel_events();
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

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
