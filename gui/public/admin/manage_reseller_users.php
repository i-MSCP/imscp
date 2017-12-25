<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

use iMSCP\PHPini;
use iMSCP\TemplateEngine;
use iMSCP_Events as Events;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Move the given customer from the given reseller to the given reseller
 *
 * @throws Exception
 * @param int $customerId Customer unique identifier
 * @param int $fromResellerId Reseller unique identifier
 * @param int $toResellerId Reseller unique identifier
 * @return void
 */
function moveCustomer($customerId, $fromResellerId, $toResellerId)
{
    /** @var iMSCP_Database $db */
    $db = Registry::get('iMSCP_Application')->getDatabase();

    try {
        $toResellerProps = imscp_getResellerProperties($toResellerId);
        $customerToResellerLimits = [
            'domain_subd_limit'    => ['current_sub_cnt', 'max_sub_cnt'],
            'domain_alias_limit'   => ['current_als_cnt', 'max_als_cnt'],
            'domain_mailacc_limit' => ['current_mail_cnt', 'max_mail_cnt'],
            'domain_ftpacc_limit'  => ['current_ftp_cnt', 'max_ftp_cnt'],
            'domain_sqld_limit'    => ['current_sql_db_cnt', 'max_sql_db_cnt'],
            'domain_sqlu_limit'    => ['current_sql_user_cnt', 'max_sql_user_cnt'],
            'domain_traffic_limit' => ['current_traff_amnt', 'max_traff_amnt'],
            'domain_disk_limit'    => ['current_disk_amnt', 'max_disk_amnt']
        ];
        $resellerToCustomerPerms = [
            'software_allowed' => 'domain_software_allowed'
        ];

        $stmt = exec_query(
            '
                SELECT domain_subd_limit, domain_alias_limit, domain_mailacc_limit, domain_ftpacc_limit, domain_sqld_limit, domain_sqlu_limit,
                    domain_traffic_limit, domain_disk_limit, domain_ip_id, domain_software_allowed
                FROM domain
                WHERE domain_admin_id = ?
            ',
            [$customerId]
        );

        if (!$stmt->rowCount()) {
            throw new Exception(tr("Couldn't find domain properties for customer with ID %d.", $customerId));
        }

        $customerProps = $stmt->fetch();

        $db->beginTransaction();

        // For each item (sub, mail, ftp....), we adjust the target reseller
        // limits according the customer limits. We cannot do the reverse side
        // because this would involve too much works and unpredictable result.
        // Most of time, the administrator do not want downgrade limits of
        // customers.
        foreach ($customerToResellerLimits as $customerLimit => $resellerLimit) {
            if ($toResellerProps[$resellerLimit[1]] == 0 || $customerProps[$customerLimit] == -1) {
                // The target reseller is not limited for the item, or the
                // customer has no rights for the item.
                continue;
            }

            if ($customerProps[$customerLimit] == 0) {
                // Customer is not limited for the item. The target reseller
                // must not be limited.
                $toResellerProps[$resellerLimit[1]] = 0;
                continue;
            }

            if ($toResellerProps[$resellerLimit[1]] == -1) {
                // The target reseller has no rights for the item but customer.
                // The Target reseller limit must be at least equal to customer
                // limit.
                $toResellerProps[$resellerLimit[1]] = $customerProps[$customerLimit];
                continue;
            }

            if (($toResellerProps[$resellerLimit[1]] - $toResellerProps[$resellerLimit[0]]) < $customerProps[$customerLimit]) {
                // The target reseller limit after soustracting total consumed
                // items, taking into account the customer limit would be
                // negative. The target reseller limit must be increased up to customer limit.
                $toResellerProps[$resellerLimit[1]] += $customerProps[$customerLimit] - (
                        $toResellerProps[$resellerLimit[1]] - $toResellerProps[$resellerLimit[0]]);
            }
        }

        // Adjust the customer permissions according target reseller permissions
        foreach ($resellerToCustomerPerms as $resellerPerms => $customerPerm) {
            if ($customerProps[$customerPerm] == 'yes' && $toResellerProps[$resellerPerms] != 'yes') {
                $customerProps[$customerPerm] = 'no';
            }
        }

        // The customer IP address must be in the target reseller IP addresses list
        $toResellerProps['reseller_ips'] = implode(
            ';', array_unique(explode(';', $toResellerProps['reseller_ips'] . $customerProps['domain_ip_id'] . ';'))
        );

        // Move the customer to the target reseller
        exec_query('UPDATE admin SET created_by = ? WHERE admin_id = ?', [$toResellerId, $customerId]);

        // Update the customer permissions according the target reseller permissions

        exec_query('UPDATE domain SET domain_software_allowed = ? WHERE domain_admin_id = ?', [
            $customerProps['domain_software_allowed'], $customerId
        ]);

        PhpIni::getInstance()->syncClientPermissionsAndIniOptions($toResellerId, $customerId);

        // Update the target reseller limits, permissions and IP addresses 
        exec_query(
            '
                UPDATE reseller_props 
                SET max_sub_cnt = ?, max_als_cnt = ?, max_mail_cnt = ?, max_ftp_cnt = ?, max_sql_db_cnt = ?, max_sql_user_cnt = ?, max_traff_amnt = ?,
                    max_disk_amnt = ?, reseller_ips = ?, software_allowed = ?
                WHERE reseller_id = ?
            ',
            [
                $toResellerProps['max_sub_cnt'], $toResellerProps['max_als_cnt'], $toResellerProps['max_mail_cnt'], $toResellerProps['max_ftp_cnt'],
                $toResellerProps['max_sql_db_cnt'], $toResellerProps['max_sql_user_cnt'], $toResellerProps['max_traff_amnt'],
                $toResellerProps['max_disk_amnt'], $toResellerProps['reseller_ips'], $toResellerProps['software_allowed'], $toResellerId
            ]
        );

        // Recalculate count of assigned items for both source and target resellers
        update_reseller_c_props($toResellerId);
        update_reseller_c_props($fromResellerId);

        Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onMoveCustomer, [
            'customerId'     => $customerId,
            'fromResellerId' => $fromResellerId,
            'toResellerId'   => $toResellerId
        ]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        write_log(sprintf("Couldn't move customer with ID %d: %s", $customerId, $e->getMessage()), E_USER_ERROR);
        throw new Exception(tr("Couldn't move customer with ID %d: %s", $customerId, $e->getMessage()), $e->getCode(), $e);
    }
}

/**
 * Move selected customers
 *
 * @return bool TRUE on success, other on failure
 */
function moveCustomers()
{
    if (!isset($_POST['from_reseller'])
        || !isset($_POST['to_reseller'])
        || !isset($_POST['reseller_customers'])
        || !is_array($_POST['reseller_customers'])
    ) {
        showBadRequestErrorPage();
    }

    set_time_limit(0);
    ignore_user_abort(true);

    try {
        $fromResellerId = intval($_POST['from_reseller']);
        $toResellerId = intval($_POST['to_reseller']);

        if ($fromResellerId == $toResellerId) {
            showBadRequestErrorPage();
        }

        foreach ($_POST['reseller_customers'] as $customerId) {
            moveCustomer(intval($customerId), $fromResellerId, $toResellerId);
        }
    } catch (Exception $e) {
        set_page_message(tohtml($e->getMessage()), 'error');
        return false;
    }

    return true;
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function generatePage(TemplateEngine $tpl)
{
    $resellers = $stmt = execute_query("SELECT admin_id, admin_name FROM admin WHERE admin_type = 'reseller'")->fetchAll();
    $fromResellerId = isset($_POST['from_reseller']) ? intval($_POST['from_reseller']) : $resellers[0]['admin_id'];
    $toResellerId = isset($_POST['to_reseller']) ? intval($_POST['to_reseller']) : $resellers[1]['admin_id'];

    // Generate source/target reseller lists
    foreach ($resellers as $reseller) {
        $tpl->assign([
            'FROM_RESELLER_ID'       => tohtml($reseller['admin_id'], 'htmlAttr'),
            'FROM_RESELLER_NAME'     => tohtml($reseller['admin_name']),
            'FROM_RESELLER_SELECTED' => $fromResellerId == $reseller['admin_id'] ? ' selected' : ''
        ]);
        $tpl->parse('FROM_RESELLER_ITEM', '.from_reseller_item');
        $tpl->assign([
            'TO_RESELLER_ID'       => tohtml($reseller['admin_id'], 'htmlAttr'),
            'TO_RESELLER_NAME'     => tohtml($reseller['admin_name']),
            'TO_RESELLER_SELECTED' => $toResellerId == $reseller['admin_id'] ? ' selected' : ''
        ]);
        $tpl->parse('TO_RESELLER_ITEM', '.to_reseller_item');
    }

    // Generate customers list for the selected source reseller
    $customers = exec_query(
        "SELECT admin_id, admin_name FROM admin WHERE created_by = ? AND admin_type = 'user' AND admin_status <> 'todelete'", [$fromResellerId]
    )->fetchAll();

    if (empty($customers)) {
        $tpl->assign('FROM_RESELLER_CUSTOMERS_LIST', '');
        return;
    }

    $selectedCustomers = isset($_POST['reseller_customers']) ? $_POST['reseller_customers'] : [];
    foreach ($customers as $customer) {
        $tpl->assign([
            'CUSTOMER_ID'               => tohtml($customer['admin_id'], 'htmlAttr'),
            'CUSTOMER_NAME'             => tohtml(decode_idna($customer['admin_name'])),
            'RESELLER_CUSTOMER_CHECKED' => in_array($customer['admin_id'], $selectedCustomers) ? ' checked' : ''
        ]);
        $tpl->parse('FROM_RESELLER_CUSTOMER_ITEM', '.from_reseller_customer_item');
    }
}

/***********************************************************************************************************************
 * Main
 *
 */

require 'imscp-lib.php';

check_login('admin');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAdminScriptStart);
systemHasResellers(2) or showBadRequestErrorPage();

if (isset($_POST['uaction']) && $_POST['uaction'] == 'move_customers' && moveCustomers()) {
    set_page_message(tr('Customer(s) successfully moved.'), 'success');
    redirectTo('users.php');
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'                       => 'shared/layouts/ui.tpl',
    'page'                         => 'admin/manage_reseller_users.phtml',
    'page_message'                 => 'layout',
    'from_reseller_customers_list' => 'page',
    'from_reseller_customer_item'  => 'from_reseller_customers_list',
    'from_reseller_item'           => 'page',
    'to_reseller_item'             => 'page'
]);
$tpl->assign('TR_PAGE_TITLE', tohtml(tr('Admin / Users / Customer Assignments')));

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
