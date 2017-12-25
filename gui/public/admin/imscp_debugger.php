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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2018 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

use iMSCP_Registry as Registry;
use iMSCP\TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get user errors
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 */
function debugger_getUserErrors($tpl)
{
    $stmt = execute_query(
        "
            SELECT admin_name, admin_status, admin_id
            FROM admin
            WHERE admin_type = 'user'
            AND admin_status NOT IN ('ok', 'toadd', 'tochange', 'tochangepwd', 'todelete')
        "
    );

    if (!$stmt->rowCount()) {
        $tpl->assign(['USER_ITEM' => '', 'TR_USER_MESSAGE' => tr('No error found')]);
        $tpl->parse('USER_MESSAGE', 'user_message');
        return;
    }

    while ($row = $stmt->fetch()) {
        $tpl->assign([
            'USER_MESSAGE' => '',
            'USER_NAME'    => tohtml(decode_idna($row['admin_name'])),
            'USER_ERROR'   => tohtml($row['admin_status']),
            'CHANGE_ID'    => tohtml($row['admin_id']),
            'CHANGE_TYPE'  => 'user'
        ]);
        $tpl->parse('USER_ITEM', '.user_item');
    }
}

/**
 * Get domain errors
 *
 * @param iMSCP\TemplateEngine $tpl Template engine instance
 * @return void
 */
function debugger_getDmnErrors($tpl)
{
    $stmt = execute_query(
        "
            SELECT domain_name, domain_status, domain_id
            FROM domain
            WHERE domain_status
            NOT IN ('ok', 'disabled', 'toadd', 'tochange', 'torestore', 'toenable', 'todisable', 'todelete')
        "
    );

    if (!$stmt->rowCount()) {
        $tpl->assign(['DMN_ITEM' => '', 'TR_DMN_MESSAGE' => tr('No error found')]);
        $tpl->parse('DMN_MESSAGE', 'dmn_message');
        return;
    }

    while ($row = $stmt->fetch()) {
        $tpl->assign([
            'DMN_MESSAGE' => '',
            'DMN_NAME'    => tohtml(decode_idna($row['domain_name'])),
            'DMN_ERROR'   => tohtml($row['domain_status']),
            'CHANGE_ID'   => tohtml($row['domain_id']),
            'CHANGE_TYPE' => 'domain'
        ]);
        $tpl->parse('DMN_ITEM', '.dmn_item');
    }
}

/**
 * Get domain aliases errors
 *
 * @param iMSCP\TemplateEngine $tpl Template engine instance
 * @return void
 */
function debugger_getAlsErrors($tpl)
{
    $stmt = execute_query(
        "
            SELECT alias_name, alias_status, alias_id
            FROM domain_aliasses
            WHERE alias_status NOT IN (
                'ok', 'disabled', 'toadd', 'tochange', 'torestore', 'toenable', 'todisable', 'todelete', 'ordered'
            )
        "
    );

    if (!$stmt->rowCount()) {
        $tpl->assign(['ALS_ITEM' => '', 'TR_ALS_MESSAGE' => tr('No error found')]);
        $tpl->parse('ALS_MESSAGE', 'als_message');
        return;
    }

    while ($row = $stmt->fetch()) {
        $tpl->assign([
            'ALS_MESSAGE' => '',
            'ALS_NAME'    => tohtml(decode_idna($row['alias_name'])),
            'ALS_ERROR'   => tohtml($row['alias_status']),
            'CHANGE_ID'   => $row['alias_id'],
            'CHANGE_TYPE' => 'alias',
        ]);
        $tpl->parse('ALS_ITEM', '.als_item');
    }
}

/**
 * Get subdomains errors
 *
 * @param iMSCP\TemplateEngine $tpl Template engine instance
 * @return void
 */
function debugger_getSubErrors($tpl)
{
    $stmt = execute_query(
        "
            SELECT subdomain_name, subdomain_status, subdomain_id, domain_name
            FROM subdomain
            LEFT JOIN domain ON (subdomain.domain_id = domain.domain_id)
            WHERE subdomain_status NOT IN (
                'ok', 'disabled', 'toadd', 'tochange', 'torestore', 'toenable', 'todisable', 'todelete'                
            )
        "
    );

    if (!$stmt->rowCount()) {
        $tpl->assign(['SUB_ITEM' => '', 'TR_SUB_MESSAGE' => tr('No error found')]);
        $tpl->parse('SUB_MESSAGE', 'sub_message');
        return;
    }

    while ($row = $stmt->fetch()) {
        $tpl->assign([
            'SUB_MESSAGE' => '',
            'SUB_NAME'    => tohtml(decode_idna($row['subdomain_name'] . '.' . $row['domain_name'])),
            'SUB_ERROR'   => tohtml($row['subdomain_status']),
            'CHANGE_ID'   => $row['subdomain_id'],
            'CHANGE_TYPE' => 'subdomain'
        ]);
        $tpl->parse('SUB_ITEM', '.sub_item');
    }
}

/**
 * Get subdomain aliases errors
 *
 * @param iMSCP\TemplateEngine $tpl Template engine instance
 * @return void
 */
function debugger_getAlssubErrors($tpl)
{
    $stmt = execute_query(
        "
            SELECT subdomain_alias_name, subdomain_alias_status, subdomain_alias_id, alias_name
            FROM subdomain_alias
            LEFT JOIN domain_aliasses ON (subdomain_alias_id = domain_aliasses.alias_id)
            WHERE subdomain_alias_status NOT IN (
                'ok', 'disabled', 'toadd', 'tochange', 'torestore', 'toenable', 'todisable', 'todelete'
            )
        "
    );

    if (!$stmt->rowCount()) {
        $tpl->assign(['ALSSUB_ITEM' => '', 'TR_ALSSUB_MESSAGE' => tr('No error found')]);
        $tpl->parse('ALSSUB_MESSAGE', 'alssub_message');
        return;
    }

    while ($row = $stmt->fetch()) {
        $tpl->assign([
            'ALSSUB_MESSAGE' => '',
            'ALSSUB_NAME'    => tohtml(decode_idna($row['subdomain_alias_name'] . '.' . $row['alias_name'])),
            'ALSSUB_ERROR'   => tohtml($row['subdomain_alias_status']),
            'CHANGE_ID'      => $row['subdomain_alias_id'],
            'CHANGE_TYPE'    => 'subdomain_alias'
        ]);
        $tpl->parse('ALSSUB_ITEM', '.alssub_item');
    }
}

/**
 * Get custom dns errors
 *
 * @param iMSCP\TemplateEngine $tpl Template engine instance
 * @return void
 */
function debugger_getCustomDNSErrors($tpl)
{
    $stmt = execute_query(
        "
            SELECT domain_dns, domain_dns_status, domain_dns_id
            FROM domain_dns
            WHERE domain_dns_status NOT IN (
                'ok', 'disabled', 'toadd', 'tochange', 'torestore', 'toenable', 'todisable', 'todelete'
            )
        "
    );

    if (!$stmt->rowCount()) {
        $tpl->assign(['CUSTOM_DNS_ITEM' => '', 'TR_CUSTOM_DNS_MESSAGE' => tr('No error found')]);
        $tpl->parse('CUSTOM_DNS_MESSAGE', 'custom_dns_message');
        return;
    }

    while ($row = $stmt->fetch()) {
        $tpl->assign([
            'CUSTOM_DNS_MESSAGE' => '',
            'CUSTOM_DNS_NAME'    => tohtml(decode_idna($row['domain_dns'])),
            'CUSTOM_DNS_ERROR'   => tohtml($row['domain_dns_status']),
            'CHANGE_ID'          => tohtml($row['domain_dns_id']),
            'CHANGE_TYPE'        => 'custom_dns'
        ]);
        $tpl->parse('CUSTOM_DNS_ITEM', '.custom_dns_item');
    }
}

/**
 * Gets htaccess errors
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 */
function debugger_getHtaccessErrors($tpl)
{
    $stmt = execute_query(
        "
            SELECT id, dmn_id, auth_name AS name, status, 'htaccess' AS type
            FROM htaccess
            WHERE status NOT IN ('ok', 'disabled', 'toadd', 'tochange', 'todelete')
            UNION ALL
            SELECT id, dmn_id, ugroup AS name, status, 'htgroup' AS type
            FROM htaccess_groups
            WHERE status NOT IN ('ok', 'disabled', 'toadd', 'tochange', 'todelete')
            UNION ALL
            SELECT id, dmn_id, uname AS name, status, 'htpasswd' AS type
            FROM htaccess_users
            WHERE status NOT IN ('ok', 'disabled', 'toadd', 'tochange', 'todelete')
        "
    );

    if (!$stmt->rowCount()) {
        $tpl->assign(['HTACCESS_ITEM' => '', 'TR_HTACCESS_MESSAGE' => tr('No error found')]);
        $tpl->parse('HTACCESS_MESSAGE', 'htaccess_message');
        return;
    }

    while ($row = $stmt->fetch()) {
        $tpl->assign([
            'HTACCESS_MESSAGE' => '',
            'HTACCESS_NAME'    => tohtml($row['name']),
            'HTACCESS_TYPE'    => tohtml($row['type']),
            'HTACCESS_ERROR'   => tohtml($row['status']),
            'CHANGE_ID'        => $row['id'],
            'CHANGE_TYPE'      => $row['type']
        ]);
        $tpl->parse('HTACCESS_ITEM', '.htaccess_item');
    }
}

/**
 * Get FTP user errors
 *
 * @param TemplateEngine $tpl
 * @throws iMSCP_Exception_Database
 */
function debugger_getFtpUserErrors($tpl)
{
    $stmt = execute_query(
        "
            SELECT userid, status
            FROM ftp_users
            WHERE status NOT IN (
                'ok', 'disabled', 'toadd', 'tochange', 'toenable', 'todisable', 'todelete'
            )
        "
    );

    if (!$stmt->rowCount()) {
        $tpl->assign(['FTP_ITEM' => '', 'TR_FTP_MESSAGE' => tr('No error found')]);
        $tpl->parse('FTP_MESSAGE', 'ftp_message');
        return;
    }

    while ($row = $stmt->fetch()) {
        $tpl->assign([
            'FTP_MESSAGE' => '',
            'FTP_NAME'    => tohtml(decode_idna($row['userid'])),
            'FTP_ERROR'   => tohtml($row['status']),
            'CHANGE_ID'   => tohtml($row['userid']),
            'CHANGE_TYPE' => 'ftp'
        ]);
        $tpl->parse('FTP_ITEM', '.ftp_item');
    }
}

/**
 * Get mails errors
 *
 * @throws iMSCP_Exception
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 */
function debugger_getMailsErrors($tpl)
{
    $stmt = execute_query(
        "
            SELECT mail_acc, domain_id, mail_type, status, mail_id FROM mail_users
            WHERE status NOT IN (
                'ok', 'disabled', 'toadd', 'tochange', 'torestore', 'toenable', 'todisable', 'todelete', 'ordered'
            )
        "
    );

    if (!$stmt->rowCount()) {
        $tpl->assign(['MAIL_ITEM' => '', 'TR_MAIL_MESSAGE' => tr('No error found')]);
        $tpl->parse('MAIL_MESSAGE', 'mail_message');
        return;
    }

    while ($row = $stmt->fetch()) {
        $searchedId = $row['domain_id'];
        $mailAcc = $row['mail_acc'];
        $mailType = $row['mail_type'];
        $mailId = $row['mail_id'];
        $mailStatus = $row['status'];

        switch ($mailType) {
            case MT_NORMAL_MAIL:
            case MT_NORMAL_FORWARD:
            case MT_NORMAL_MAIL . ',' . MT_NORMAL_FORWARD:
                $query = "SELECT CONCAT('@', domain_name) AS domain_name FROM domain WHERE domain_id = ?";
                break;
            case MT_SUBDOM_MAIL:
            case MT_SUBDOM_FORWARD:
            case MT_SUBDOM_MAIL . ',' . MT_SUBDOM_FORWARD:
                $query = "
                    SELECT CONCAT(
                        '@', subdomain_name, '.', IF(t2.domain_name IS NULL,'" . tr('missing domain') . "',t2.domain_name)
                    ) AS 'domain_name'
                    FROM subdomain AS t1
                    LEFT JOIN domain AS t2 ON (t1.domain_id = t2.domain_id)
                    WHERE subdomain_id = ?
                ";
                break;
            case MT_ALSSUB_MAIL:
            case MT_ALSSUB_FORWARD:
            case MT_ALSSUB_MAIL . ',' . MT_ALSSUB_FORWARD:
                $query = "
                    SELECT CONCAT('@', t1.subdomain_alias_name, '.', IF(t2.alias_name IS NULL,'" . tr('missing alias')
                    . "',t2.alias_name) ) AS domain_name
                    FROM subdomain_alias AS t1
                    LEFT JOIN domain_aliasses AS t2 ON (t1.alias_id = t2.alias_id)
                    WHERE subdomain_alias_id = ?
                ";
                break;
            case MT_NORMAL_CATCHALL:
            case MT_ALIAS_CATCHALL:
            case MT_ALSSUB_CATCHALL:
            case MT_SUBDOM_CATCHALL:
                $query = 'SELECT mail_addr AS domain_name FROM mail_users WHERE mail_id = ?';
                $searchedId = $mailId;
                $mailAcc = '';
                break;
            case MT_ALIAS_MAIL:
            case MT_ALIAS_FORWARD:
            case MT_ALIAS_MAIL . ',' . MT_ALIAS_FORWARD:
                $query = "SELECT CONCAT('@', alias_name) AS domain_name FROM domain_aliasses WHERE alias_id = ?";
                break;
            default:
                throw new iMSCP_Exception('FIXME: ' . __FILE__ . ':' . __LINE__ . $mailType);
        }

        $domainName = ltrim(exec_query($query, $searchedId)->fetchColumn(), '@');
        $tpl->assign([
            'MAIL_MESSAGE' => '',
            'MAIL_NAME'    => tohtml($mailAcc . '@' . (
                $domainName == '' ? ' ' . tr('orphan entry') : decode_idna($domainName))
            ),
            'MAIL_ERROR'   => tohtml($mailStatus),
            'CHANGE_ID'    => $mailId,
            'CHANGE_TYPE'  => 'mail'
        ]);
        $tpl->parse('MAIL_ITEM', '.mail_item');
    }
}

/**
 * Get IP errors
 *
 * @param TemplateEngine $tpl Template engine instance
 * @return void
 */
function debugger_getIpErrors($tpl)
{
    $stmt = execute_query(
        "
            SELECT ip_id, ip_number, ip_card, ip_status
            FROM server_ips
            WHERE ip_status NOT IN ('ok', 'toadd', 'tochange', 'todelete')
        "
    );

    if (!$stmt->rowCount()) {
        $tpl->assign(['IP_ITEM' => '', 'TR_IP_MESSAGE' => tr('No error found')]);
        $tpl->parse('IP_MESSAGE', 'ip_message');
        return;
    }

    while ($row = $stmt->fetch()) {
        $tpl->assign([
            'IP_MESSAGE'  => '',
            'IP_NAME'     => tohtml((($row['ip_number'] == '0.0.0.0') ? tr('Any') : $row['ip_number'])
                . ' ' . '(' . $row['ip_card'] . (strpos($row['ip_number'], ':') == FALSE ? ':'
                    . ($row['ip_id'] + 1000) : '') . ')'),
            'IP_ERROR'    => tohtml($row['ip_status']),
            'CHANGE_ID'   => tohtml($row['ip_id']),
            'CHANGE_TYPE' => 'ip'
        ]);
        $tpl->parse('IP_ITEM', '.ip_item');
    }
}

/**
 * Get plugin items errors
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function debugger_getPluginItemErrors($tpl)
{
    /** @var iMSCP_Plugin_Manager $pluginManager */
    $pluginManager = Registry::get('iMSCP_Application')->getPluginManager();

    /** @var iMSCP_Plugin[] $plugins */
    $plugins = $pluginManager->pluginGetLoaded();

    $itemFound = false;
    foreach ($plugins as $plugin) {
        $items = $plugin->getItemWithErrorStatus();

        if (!empty($items)) {
            $itemFound = true;
            foreach ($items as $item) {
                $tpl->assign([
                    'PLUGIN_ITEM_MESSAGE' => '',
                    'PLUGIN_NAME'         => tohtml($plugin->getName()) . ' (' . tohtml($item['item_name']) . ')',
                    'PLUGIN_ITEM_ERROR'   => tohtml($item['status']),
                    'CHANGE_ID'           => $item['item_id'],
                    'CHANGE_TYPE'         => tohtml($plugin->getName()),
                    'TABLE'               => tohtml($item['table']),
                    'FIELD'               => tohtml($item['field'])
                ]);
                $tpl->parse('PLUGIN_ITEM_ITEM', '.plugin_item_item');
            }
        }
    }

    if (!$itemFound) {
        $tpl->assign(['PLUGIN_ITEM_ITEM' => '', 'TR_PLUGIN_ITEM_MESSAGE' => tr('No error found')]);
        $tpl->parse('PLUGIN_ITEM_MESSAGE', 'plugin_item_message');
    }
}

/**
 * Change plugin item status
 *
 * @param string $pluginName Plugin name
 * @param string $table Table name
 * @param string $field Status field name
 * @param int $itemId item unique identifier
 * @return bool
 */
function debugger_changePluginItemStatus($pluginName, $table, $field, $itemId)
{
    /** @var iMSCP_Plugin_Manager $pluginManager */
    $pluginManager = Registry::get('iMSCP_Application')->getPluginManager();
    if ($pluginManager->pluginIsLoaded($pluginName)) {
        $pluginManager->pluginGet($pluginName)->changeItemStatus($table, $field, $itemId);
        return true;
    }

    return false;
}

/**
 * Returns the number of requests that still to run.
 *
 * Note: Without any argument, this function will trigger the getCountRequests() method on all enabled plugins
 *
 * @param string $statusField status database field name
 * @param string $tableName i-MSCP database table name
 * @return int Number of request
 */
function debugger_countRequests($statusField = NULL, $tableName = NULL)
{
    if (NULL !== $statusField && NULL !== $tableName) {
        $statusField = quoteIdentifier($statusField);
        $tableName = quoteIdentifier($tableName);
        $stmt = execute_query(
            "
                SELECT $statusField
                FROM $tableName
                WHERE $statusField IN (
                    'toinstall', 'toupdate', 'touninstall', 'toadd', 'tochange', 'torestore', 'toenable', 'todisable',
                    'todelete'
                )
            "
        );
        return $stmt->rowCount();
    }

    /** @var iMSCP_Plugin[] $plugins */
    $plugins = Registry::get('iMSCP_Application')->getPluginManager()->pluginGetLoaded();
    $nbRequests = 0;

    if (!empty($plugins)) {
        foreach ($plugins as $plugin) {
            $nbRequests += $plugin->getCountRequests();
        }
    }

    return $nbRequests;
}

/***********************************************************************************************************************
 * Main
 */


require 'imscp-lib.php';

check_login('admin');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAdminScriptStart);

/** @var iMSCP_Plugin_Manager $plugingManager */
$plugingManager = Registry::get('iMSCP_Application')->getPluginManager();

$rqstCount = debugger_countRequests('admin_status', 'admin');
$rqstCount += debugger_countRequests('domain_status', 'domain');
$rqstCount += debugger_countRequests('alias_status', 'domain_aliasses');
$rqstCount += debugger_countRequests('subdomain_status', 'subdomain');
$rqstCount += debugger_countRequests('subdomain_alias_status', 'subdomain_alias');
$rqstCount += debugger_countRequests('domain_dns_status', 'domain_dns');
$rqstCount += debugger_countRequests('status', 'ftp_users');
$rqstCount += debugger_countRequests('status', 'mail_users');
$rqstCount += debugger_countRequests('status', 'htaccess');
$rqstCount += debugger_countRequests('status', 'htaccess_groups');
$rqstCount += debugger_countRequests('status', 'htaccess_users');
$rqstCount += debugger_countRequests('ip_status', 'server_ips');
$rqstCount += debugger_countRequests(); // Plugin items

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'run') {
        if ($rqstCount > 0) {
            if (send_request()) {
                set_page_message(tr('Daemon request successful.'), 'success');
            } else {
                set_page_message(tr('Daemon request failed.'), 'error');
            }
        } else {
            set_page_message(tr('There is no pending task. Operation canceled.'), 'warning');
        }

        redirectTo('imscp_debugger.php');
        exit;
    }

    if ($_GET['action'] == 'change'
        && (isset($_GET['id']) && isset($_GET['type']))
    ) {
        switch ($_GET['type']) {
            case 'user':
                $query = "UPDATE admin SET admin_status = 'tochange' WHERE admin_id = ?";
                break;
            case 'domain':
                $query = "UPDATE domain SET domain_status = 'tochange' WHERE domain_id = ?";
                break;
            case 'alias':
                $query = "UPDATE domain_aliasses SET alias_status = 'tochange' WHERE alias_id = ?";
                break;
            case 'subdomain':
                $query = "UPDATE subdomain SET subdomain_status = 'tochange' WHERE subdomain_id = ?";
                break;
            case 'subdomain_alias':
                $query = "UPDATE subdomain_alias SET subdomain_alias_status = 'tochange' WHERE subdomain_alias_id = ?";
                break;
            case 'custom_dns':
                $query = "UPDATE domain_dns SET domain_dns_status = 'tochange' WHERE domain_dns_id = ?";
                break;
            case 'ftp':
                $query = "UPDATE ftp_users SET status = 'tochange' WHERE userid = ?";
                break;
            case 'mail':
                $query = "UPDATE mail_users SET status = 'tochange' WHERE mail_id = ?";
                break;
            case 'htaccess':
                $query = "UPDATE htaccess SET status = 'tochange'  WHERE id = ?";
                break;
            case 'htgroup':
                $query = "UPDATE htaccess_groups SET status = 'tochange' WHERE id = ?";
                break;
            case 'htpasswd':
                $query = "UPDATE htaccess_users SET status = 'tochange' WHERE id = ?";
                break;
            case 'ip':
                $query = "UPDATE server_ips SET ip_status = 'tochange' WHERE ip_id = ?";
                break;
            case 'plugin':
                $query = "UPDATE plugin SET plugin_status = 'tochange' WHERE plugin_id = ?";
                break;
            default:
                if (!isset($_GET['table']) || !isset($_GET['field'])) {
                    showBadRequestErrorPage();
                }

                if (!debugger_changePluginItemStatus($_GET['type'], $_GET['table'], $_GET['field'], $_GET['id'])) {
                    set_page_message(tr('Unknown type.'), 'error');
                } else {
                    set_page_message(tr('Done'), 'success');
                }

                redirectTo('imscp_debugger.php');
                exit;
        }

        $stmt = exec_query($query, [$_GET['id']]);
        set_page_message(tr('Done'), 'success');
        redirectTo('imscp_debugger.php');
    }
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'              => 'shared/layouts/ui.tpl',
    'page'                => 'admin/imscp_debugger.tpl',
    'page_message'        => 'layout',
    'user_message'        => 'page',
    'user_item'           => 'page',
    'dmn_message'         => 'page',
    'dmn_item'            => 'page',
    'als_message'         => 'page',
    'als_item'            => 'page',
    'sub_message'         => 'page',
    'sub_item'            => 'page',
    'alssub_message'      => 'page',
    'alssub_item'         => 'page',
    'custom_dns_message'  => 'page',
    'custom_dns_item'     => 'page',
    'htaccess_message'    => 'page',
    'htaccess_item'       => 'page',
    'ftp_message'         => 'page',
    'ftp_item'            => 'page',
    'mail_message'        => 'page',
    'mail_item'           => 'page',
    'ip_message'          => 'page',
    'ip_item'             => 'page',
    'plugin_message'      => 'page',
    'plugin_item'         => 'page',
    'plugin_item_message' => 'page',
    'plugin_item_item'    => 'page'
]);

debugger_getUserErrors($tpl);
debugger_getDmnErrors($tpl);
debugger_getAlsErrors($tpl);
debugger_getSubErrors($tpl);
debugger_getAlssubErrors($tpl);
debugger_getCustomDNSErrors($tpl);
debugger_getFtpUserErrors($tpl);
debugger_getMailsErrors($tpl);
debugger_getHtaccessErrors($tpl);
debugger_getIpErrors($tpl);
debugger_getPluginItemErrors($tpl);

$tpl->assign([
    'TR_PAGE_TITLE'         => tr('Admin / System Tools / Debugger'),
    'TR_USER_ERRORS'        => tr('User errors'),
    'TR_DMN_ERRORS'         => tr('Domain errors'),
    'TR_ALS_ERRORS'         => tr('Domain alias errors'),
    'TR_SUB_ERRORS'         => tr('Subdomain errors'),
    'TR_ALSSUB_ERRORS'      => tr('Subdomain alias errors'),
    'TR_CUSTOM_DNS_ERRORS'  => tr('Custom DNS errors'),
    'TR_FTP_ERRORS'         => tr('FTP user errors'),
    'TR_MAIL_ERRORS'        => tr('Mail account errors'),
    'TR_IP_ERRORS'          => tr('IP errors'),
    'TR_HTACCESS_ERRORS'    => tr('Htaccess, htgroups and htpasswd errors'),
    'TR_PLUGINS_ERRORS'     => tr('Plugin errors'),
    'TR_PLUGIN_ITEM_ERRORS' => tr('Plugin item errors'),
    'TR_PENDING_TASKS'      => tr('Pending tasks'),
    'TR_EXEC_TASKS'         => tr('Execute tasks'),
    'TR_CHANGE_STATUS'      => tr("Change status of this item for a new attempt"),
    'EXEC_COUNT'            => $rqstCount
]);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAdminScriptEnd, [
    'templateEngine' => $tpl
]);
$tpl->prnt();

unsetMessages();
