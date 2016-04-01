<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
/***********************************************************************************************************************
 * Functions
 */

namespace iMSCP;

use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Events as Events;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Registry as Registry;
use Zend_Session as Session;

/**
 * Generates page
 *
 * @param TemplateEngine $tpl Template engine
 * @return void
 */
function generatePage($tpl)
{
    generateIpsList($tpl);
    generateDevicesList($tpl);

    if (isset($_POST['ip_number'])) {
        $tpl->assign('VALUE_IP', tohtml($_POST['ip_number']));
    } else {
        $tpl->assign('VALUE_IP', '');
    }
}

/**
 * Generates IPs list
 *
 * @param TemplateEngine $tpl Template engine
 * @return void
 */
function generateIpsList($tpl)
{
    /** @var \iMSCP_Database_ResultSet $stmt */
    $stmt = execute_query('SELECT * FROM server_ips');
    if (!$stmt->rowCount()) {
        $tpl->assign('IP_ADDRESSES_BLOCK', '');
        set_page_message(tr('No IP address found.'), 'info');
        return;
    }

    $cfg = Registry::get('config');
    while ($row = $stmt->fetchRow()) {
        list($actionName, $actionUrl) = generateIpAction($row['ip_id'], $row['ip_status']);
        $tpl->assign(array(
            'IP' => $row['ip_number'],
            'NETWORK_CARD' => $row['ip_card'] === null ? '' : tohtml($row['ip_card'])
        ));
        $tpl->assign(array(
            'ACTION_NAME' => $cfg['BASE_SERVER_IP'] == $row['ip_number'] ? tr('Protected') : $actionName,
            'ACTION_URL' => $cfg['BASE_SERVER_IP'] == $row['ip_number'] ? '#' : $actionUrl
        ));
        $tpl->parse('IP_ADDRESS_BLOCK', '.ip_address_block');
    }
}

/**
 * Generates Ips action
 *
 * @param int $ipId Ip address unique identifier
 * @param string $status
 * @return array
 */
function generateIpAction($ipId, $status)
{
    if ($status == 'ok') {
        return array(tr('Remove IP'), 'ip_delete.php?delete_id=' . $ipId);
    }

    if ($status == 'todelete') {
        return array(translate_dmn_status('todelete'), '#');
    }

    if ($status == 'toadd') {
        return array(translate_dmn_status('toadd'), '#');
    }

    if (!in_array($status, array('toadd', 'tochange', 'ok', 'todelete'))) {
        return array(tr('Unknown Error'), '#');
    }

    return array(tr('N/A'), '#');
}

/**
 * Generates network devices list
 *
 * @param TemplateEngine $tpl Template engine
 * @return void
 */
function generateDevicesList($tpl)
{
    $netDevices = array_filter(Net::getInstance()->getDevices(), function ($device) {
        return $device != 'lo';
    });

    sort($netDevices);

    if (empty($netDevices)) {
        set_page_message(tr('Unable to find any network interface. You cannot add new IP address.'), 'error');
        $tpl->assign('IP_ADDRESS_FORM_BLOCK', '');
        return;
    }
    
    foreach ($netDevices as $netDevice) {
        $tpl->assign('NETWORK_CARD', $netDevice);
        $tpl->parse('NETWORK_CARD_BLOCK', '.network_card_block');
    }
}

/**
 * Checks IP data
 *
 * @param string $ipAddr IP address
 * @param string $netDevice Network device
 * @return bool TRUE if data are valid, FALSE otherwise
 */
function checkIpData($ipAddr, $netDevice)
{
    $netDevices = array_filter(Net::getInstance()->getDevices(), function ($device) {
        return $device != 'lo';
    });

    $errFieldsStack = array();

    $stmt = exec_query('SELECT COUNT(IF(ip_number = ?, 1, NULL)) isRegisteredIp FROM server_ips', $ipAddr);
    if (filter_var($ipAddr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false) {
        set_page_message(tr('Wrong IP address.'), 'error');
        $errFieldsStack[] = 'ip_number';
    } elseif ($stmt->fields['isRegisteredIp']) {
        set_page_message(tr('IP address already under the control of i-MSCP.'), 'error');
        $errFieldsStack[] = 'ip_number';
    }

    if (!in_array($netDevice, $netDevices)) {
        set_page_message(tr('You must select a network interface.'), 'error');
    }

    if (Session::namespaceIsset('pageMessages')) {
        if (!empty($errFieldsStack)) {
            Registry::set('errFieldsStack', $errFieldsStack);
        }

        return false;
    }

    return true;
}

/**
 * Register new IP.
 *
 * @param string $ipNumber IP number
 * @param string $netDevice Network device
 * @return void
 */
function registerIp($ipNumber, $netDevice)
{
    exec_query('INSERT INTO server_ips (ip_number, ip_card, ip_status) VALUES (?, ?, ?)', array(
        $ipNumber, $netDevice, 'toadd'
    ));
    send_request();
    set_page_message(tr('IP address successfully scheduled for addition.'), 'success');
    write_log(sprintf('%s added new IP address: %s', $_SESSION['user_logged'], $ipNumber), E_USER_NOTICE);
    redirectTo('ip_manage.php');
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

$eventManager = EventManager::getInstance();
$eventManager->dispatch(Events::onAdminScriptStart);
check_login('admin');

if (!empty($_POST)) {
    $ipAddr = isset($_POST['ip_number']) ? trim($_POST['ip_number']) : '';
    $netDevice = isset($_POST['ip_card']) ? clean_input($_POST['ip_card']) : '';

    if (checkIpData($ipAddr, $netDevice)) {
        registerIp($ipAddr, $netDevice);
    }
}

$tpl = new TemplateEngine();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'admin/ip_manage.tpl',
    'page_message' => 'layout',
    'ip_addresses_block' => 'page',
    'ip_address_block' => 'ip_addresses_block',
    'ip_address_form_block' => 'page',
    'network_card_block' => 'ip_address_form_block'
));

$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Admin / Settings / IP Addresses Management'),
    'TR_IP' => tr('IP Address'),
    'TR_ACTION' => tr('Action'),
    'TR_NETWORK_CARD' => tr('Network interface'),
    'TR_ADD' => tr('Add'),
    'TR_CANCEL' => tr('Cancel'),
    'TR_CONFIGURED_IPS' => tr('IP addresses under control of i-MSCP'),
    'TR_ADD_NEW_IP' => tr('Add new IP address'),
    'TR_IP_DATA' => tr('IP address data'),
    'TR_MESSAGE_DELETE' => json_encode(tr('Are you sure you want to delete this IP: %s?', '%s')),
    'TR_MESSAGE_DENY_DELETE' => json_encode(tr('You cannot remove the %s IP address.', '%s')),
    'ERR_FIELDS_STACK' => (Registry::isRegistered('errFieldsStack'))
        ? json_encode(Registry::get('errFieldsStack')) : '[]',
    'TR_TIP' => tr('This interface allow to add or remove IP addresses. IP addresses listed below are already under the control of i-MSCP. IP addresses which are added through this interface will be automatically added into the i-MSCP database, and will be available for assignment to one or many of your resellers. If an IP address is not already configured on the system, it will be attached to the selected network interface.')
));

$eventManager->registerListener('onGetJsTranslations', function ($e) {
    /** @var $e \iMSCP_Events_Event */
    $e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
});

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
$eventManager->dispatch(Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

unsetMessages();
