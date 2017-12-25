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

use iMSCP_Config_Handler as ConfigArray;
use iMSCP_Events as Events;
use iMSCP_Exception as iMSCPException;
use iMSCP\TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Validates a service port and sets an appropriate message on error
 *
 * @param string $name Service name
 * @param string $ip Ip address
 * @param int $port Port
 * @param string $protocol Protocle
 * @param bool $show Tell whether or not service must be show on status page
 * @param string $index Item index on update, empty value otherwise
 * @return bool TRUE if valid, FALSE otherwise
 */
function validatesService($name, $ip, $port, $protocol, $show, $index = '')
{
    $dbConfig = Registry::get('dbConfig');

    if (Registry::isRegistered('errorFieldsIds')) {
        $errorFieldsIds = Registry::get('errorFieldsIds');
    } else {
        $errorFieldsIds = [];
    }

    $dbServiceName = "PORT_$name";
    $ip = ($ip == 'localhost') ? '127.0.0.1' : $ip;

    if (!preg_match('/^[\w\-]+$/D', $name)) {
        set_page_message(tr("Invalid service name: %s", $name), 'error');
        $errorFieldsIds[] = "name$index";
    } elseif (strlen($name) > 25) {
        set_page_message(tr("Service name cannot be greater than 25 characters.", $name), 'error');
        $errorFieldsIds[] = "name$index";
    }

    if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
        set_page_message(tr(' Wrong IP address.'), 'error');
        $errorFieldsIds[] = "ip$index";
    }

    if (!is_number($port) || $port < 1 || $port > 65535) {
        set_page_message(tr('Only numbers in range from 0 to 65535 are allowed.'), 'error');
        $errorFieldsIds[] = "port$index";
    }

    if (!is_int($index) && isset($dbConfig[$dbServiceName])) {
        set_page_message(tr('Service with same name already exists.'), 'error');
        $errorFieldsIds[] = "name$index";
    }

    if (($protocol != 'tcp' && $protocol != 'udp') || ($show != '0' && $show != '1')) {
        showBadRequestErrorPage();
    }

    if (Registry::isRegistered('errorFieldsIds')) {
        Registry::set('errorFieldsIds', Registry::get('errorFieldsIds') + $errorFieldsIds);
    } elseif (!empty($errorFieldsIds)) {
        Registry::set('errorFieldsIds', $errorFieldsIds);
    }

    return empty($errorFieldsIds);
}

/**
 * Remove a service port from the database
 *
 * @param string $serviceName Service name
 * @return bool TRUE on success, FALSE otherwise
 */
function deleteService($serviceName)
{
    $dbConfig = Registry::get('dbConfig');

    if (!isset($dbConfig[$serviceName])) {
        set_page_message(tr("Unknown service name '%s'.", $serviceName), 'error');
        return false;
    }

    unset($dbConfig[$serviceName]);
    write_log(
        sprintf('A service port (%s) has been removed by %s', $serviceName, $_SESSION['user_logged']), E_USER_NOTICE
    );
    set_page_message(tr('Service port successfully removed.'), 'success');
    return true;
}

/**
 * Adds or updates services ports
 *
 * @throws iMSCPException
 * @param string $mode Mode in witch act (add or update)
 * @return void
 */
function addOrUpdateServices($mode = 'add')
{
    $dbConfig = Registry::get('dbConfig');

    if ($mode == 'add') {
        if (!isset($_POST['port_new'])
            || !isset($_POST['port_type_new'])
            || !isset($_POST['port_type_new'])
            || !isset($_POST['show_val_new'])
            || !isset($_POST['ip_new'])
        ) {
            showBadRequestErrorPage();
        }

        $port = clean_input($_POST['port_new']);
        $protocol = clean_input($_POST['port_type_new']);
        $name = strtoupper(clean_input($_POST['name_new']));
        $show = clean_input($_POST['show_val_new']);
        $ip = clean_input($_POST['ip_new']);

        if (validatesService($name, $ip, $port, $protocol, $show)) {
            $dbServiceName = "PORT_$name";
            $dbConfig[$dbServiceName] = "$port;$protocol;$name;$show;$ip";
            write_log(
                sprintf('A service port (%s:%s) has been added by %s', $name, $port, $_SESSION['user_logged']),
                E_USER_NOTICE
            );
        }
    } elseif ($mode == 'update') {
        if (!isset($_POST['name'])
            || !is_array($_POST['name'])
            || !isset($_POST['var_name'])
            || !is_array($_POST['var_name'])
            || !isset($_POST['ip'])
            || !is_array($_POST['ip'])
            || !isset($_POST['port'])
            || !is_array($_POST['port'])
            || !isset($_POST['port_type'])
            || !is_array($_POST['port_type'])
            || !isset($_POST['show_val'])
            || !is_array($_POST['show_val'])
        ) {
            showBadRequestErrorPage();
        }

        // Reset counter of update queries
        $dbConfig->resetQueriesCounter('update');

        foreach ($_POST['name'] as $index => $name) {
            $name = strtoupper(clean_input($name));
            $ip = clean_input($_POST['ip'][$index]);
            $port = clean_input($_POST['port'][$index]);
            $protocol = clean_input($_POST['port_type'][$index]);
            $show = $_POST['show_val'][$index];

            if (validatesService($name, $ip, $port, $protocol, $show, $index)) {
                $dbServiceName = $_POST['var_name'][$index];
                $dbConfig[$dbServiceName] = "$port;$protocol;$name;$show;$ip";
            }
        }
    } else {
        throw new iMSCPException('addOrUpdateServices(): Wrong argument for $mode');
    }

    if (Registry::isRegistered('errorFieldsIds')) {
        if ($mode == 'add') {
            Registry::set('error_on_add', [
                'name_new'      => $_POST['name_new'],
                'ip_new'        => $_POST['ip_new'],
                'port_new'      => $_POST['port_new'],
                'port_type_new' => $_POST['port_type_new'],
                'show_val_new'  => $_POST['show_val_new']
            ]);
        } else {
            $errorOnUpdt = [];
            foreach ($_POST['var_name'] as $index => $service) {
                $name = $_POST['name'][$index];
                $ip = $_POST['ip'][$index];
                $port = $_POST['port'][$index];
                $protocol = $_POST['port_type'][$index];
                $show = $_POST['show_val'][$index];
                $errorOnUpdt[] = "$port;$protocol;$name;$show;$ip";
            }

            Registry::set('error_on_updt', $errorOnUpdt);
        }

        return;
    }

    if ($mode == 'add') {
        set_page_message(tr('Service port successfully added'), 'success');
        return;
    }

    $updateCount = $dbConfig->countQueries('update');

    if ($updateCount > 0) {
        set_page_message(
            ntr('Service port has been updated.', '%d service ports were updated.', $updateCount, $updateCount),
            'success'
        );
    } else {
        set_page_message(tr('Nothing has been changed.'), 'info');
    }

    redirectTo('settings_ports.php');
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @return void;
 */
function generatePage($tpl)
{
    if (Registry::isRegistered('error_on_updt')) {
        $values = new ConfigArray(Registry::get('error_on_updt'));
        $services = array_keys($values->toArray());
    } else {
        $values = Registry::get('dbConfig');
        $services = array_filter(
            array_keys($values->toArray()),
            function ($name) {
                return (strlen($name) > 5 && substr($name, 0, 5) == 'PORT_');
            }
        );

        if (Registry::isRegistered('error_on_add')) {
            $errorOnAdd = new ConfigArray(Registry::get('error_on_add'));
        }
    }

    if (empty($services)) {
        $tpl->assign('SERVICE_PORTS', '');
        set_page_message(tr('There are no service ports yet.'), 'static_info');
        return;
    }

    foreach ($services as $index => $service) {
        list($port, $protocol, $name, $status, $ip) = explode(';', $values->{$service});

        $tpl->assign([
            'NAME'         => tohtml($name, 'htmlAttr'),
            'TR_DELETE'    => tohtml(tr('Delete')),
            'DELETE_ID'    => tourl($service),
            'NUM'          => tohtml($index, 'htmlAttr'),
            'VAR_NAME'     => tohtml($service, 'htmlAttr'),
            'IP'           => ($ip == 'localhost') ? '127.0.0.1' : (!$ip ? '0.0.0.0' : tohtml($ip, 'htmlAttr')),
            'PORT'         => tohtml($port, 'htmlAttr'),
            'SELECTED_UDP' => ($protocol == 'udp') ? ' selected' : '',
            'SELECTED_TCP' => ($protocol == 'udp') ? '' : ' selected',
            'SELECTED_ON'  => ($status) ? ' selected' : '',
            'SELECTED_OFF' => ($status) ? '' : ' selected'
        ]);
        $tpl->parse('SERVICE_PORTS', '.service_ports');
    }

    $tpl->assign(
        isset($errorOnAdd) ? [
            'VAL_FOR_NAME_NEW' => $errorOnAdd['name_new'],
            'VAL_FOR_IP_NEW'   => $errorOnAdd['ip_new'],
            'VAL_FOR_PORT_NEW' => $errorOnAdd['port_new']
        ] : [
            'VAL_FOR_NAME_NEW' => '',
            'VAL_FOR_IP_NEW'   => '',
            'VAL_FOR_PORT_NEW' => ''
        ]
    );

    $tpl->assign(
        'ERROR_FIELDS_IDS',
        Registry::isRegistered('errorFieldsIds') ? json_encode(Registry::get('errorFieldsIds')) : '[]'
    );
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('admin');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAdminScriptStart);

if (isset($_POST['uaction']) && $_POST['uaction'] != 'reset') {
    addOrUpdateServices((clean_input($_POST['uaction'])));
} elseif (isset($_GET['delete'])) {
    deleteService(clean_input($_GET['delete']));
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'        => 'shared/layouts/ui.tpl',
    'page'          => 'admin/settings_ports.tpl',
    'page_message'  => 'layout',
    'service_ports' => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE'            => tohtml(tr('Admin / Settings / Service Ports')),
    'TR_YES'                   => tohtml(tr('Yes'), 'htmlAttr'),
    'TR_NO'                    => tohtml(tr('No'), 'htmlAttr'),
    'TR_SERVICE'               => tohtml(tr('Service name')),
    'TR_IP'                    => tohtml(tr('IP address')),
    'TR_PORT'                  => tohtml(tr('Port')),
    'TR_PROTOCOL'              => tohtml(tr('Protocol')),
    'TR_SHOW'                  => tohtml(tr('Show')),
    'TR_DELETE'                => tohtml(tr('Delete')),
    'TR_MESSAGE_DELETE'        => tojs(tr('Are you sure you want to delete the %s service port ?', '%s')),
    'TR_ACTION'                => tohtml(tr('Actions')),
    'VAL_FOR_SUBMIT_ON_UPDATE' => tohtml(tr('Update'), 'htmlAttr'),
    'VAL_FOR_SUBMIT_ON_ADD'    => tohtml(tr('Add'), 'htmlAttr'),
    'VAL_FOR_SUBMIT_ON_RESET'  => tohtml(tr('Reset'), 'htmlAttr')
]);

Registry::get('iMSCP_Application')->getEventsManager()->registerListener(Events::onGetJsTranslations, function ($e) {
    /** @var $e \iMSCP_Events_Event */
    $e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
});

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
