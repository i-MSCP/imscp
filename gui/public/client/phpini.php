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
use iMSCP_Config_Handler_File as ConfigFile;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Tells whether or not the status of the given domain
 *
 * @throws iMSCP_Exception
 * @param int $domainId Domain unique identifier
 * @param string $domainType Domain type (dmn|als|sub|subals)
 * @return bool TRUE if domain status is 'ok', FALSE otherwise
 */
function isDomainStatusOk($domainId, $domainType)
{
    switch ($domainType) {
        case 'dmn':
            $query = 'SELECT domain_status AS status FROM domain WHERE domain_id = ?';
            break;
        case 'als':
            $query = 'SELECT alias_status AS status FROM domain_aliasses WHERE alias_id = ?';
            break;
        case 'sub':
            $query = 'SELECT subdomain_status AS status FROM subdomain WHERE subdomain_id = ?';
            break;
        case 'subals':
            $query = 'SELECT subdomain_alias_status AS status FROM subdomain_alias WHERE subdomain_alias_id = ?';
            break;
        default:
            throw new iMSCP_Exception('Unknown domain type');
    }

    $stmt = exec_query($query, [$domainId]);

    if ($stmt->rowCount()) {
        $row = $stmt->fetch();

        if ($row['status'] == 'ok') {
            return true;
        }
    }

    return false;
}

/**
 * Get domain data
 *
 * @param string $configLevel PHP configuration level
 * @return array
 */
function getDomainData($configLevel)
{
    $params = [];

    // Per user means only main domain
    $query = "
        SELECT domain_name, domain_status, domain_id, 'dmn' AS domain_type
        FROM domain
        WHERE domain_admin_id = ?
        AND domain_status <> 'todelete'
    ";
    $params[] = $_SESSION['user_id'];

    # Per domain or per site means also domain aliases
    # FIXME: we should mention that parameters are also for subdomains in the per_domain case
    if ($configLevel == 'per_domain' || $configLevel == 'per_site') {
        $query .= "
            UNION ALL
            SELECT t1.alias_name, t1.alias_status, alias_id, 'als'
            FROM domain_aliasses AS t1
            JOIN domain AS t2 USING(domain_id)
            WHERE t2.domain_admin_id = ?
            AND t1.url_forward = 'no'
            AND t1.alias_status <> 'todelete'
        ";
        $params[] = $_SESSION['user_id'];
    }

    # Per site also means also subdomains
    if ($configLevel == 'per_site') {
        $query .= "
            UNION ALL
            SELECT CONCAT(t1.subdomain_name, '.', t2.domain_name), t1.subdomain_status, subdomain_id, 'sub'
            FROM subdomain AS t1
            JOIN domain AS t2 USING(domain_id)
            WHERE t2.domain_admin_id  = ?
            AND t1.subdomain_status <> 'todelete'
            UNION ALL
            SELECT CONCAT(t1.subdomain_alias_name, '.', t2.alias_name), t1.subdomain_alias_status, subdomain_alias_id, 'subals'
            FROM subdomain_alias AS t1
            JOIN domain_aliasses t2 USING(alias_id)
            JOIN domain AS t3 USING(domain_id)
            WHERE domain_admin_id = ?
            AND subdomain_alias_status <> 'todelete'
        ";
        $params[] = $_SESSION['user_id'];
        $params[] = $_SESSION['user_id'];
    }

    return exec_query($query, $params)->fetchAll();
}

/**
 * Update PHP configuration options
 *
 * @throws iMSCP_Exception
 * @param PHPini $phpini PHP editor instance
 * @çeturn void
 */
function updatePhpConfig($phpini)
{
    global $phpini;

    if (isset($_POST['domain_id']) && isset($_POST['domain_type'])) {
        $domainId = intval($_POST['domain_id']);
        $domainType = clean_input($_POST['domain_type']);
    } else {
        $domainId = get_user_domain_id($_SESSION['user_id']);
        $domainType = 'dmn';
    }

    $configLevel = $phpini->getClientPermission('phpiniConfigLevel');

    if (($configLevel == 'per_user' && $domainType !== 'dmn') || ($configLevel == 'per_domain' && !in_array($domainType, ['dmn', 'als'], true))) {
        showBadRequestErrorPage();
    }

    if (!isDomainStatusOk($domainId, $domainType)) {
        set_page_message(tr('Domain status is not ok.'), 'error');
        return;
    }

    $phpini->loadIniOptions($_SESSION['user_id'], $domainId, $domainType);

    if (isset($_POST['allow_url_fopen'])) {
        $phpini->setIniOption('phpiniAllowUrlFopen', clean_input($_POST['allow_url_fopen']));
    }

    if (isset($_POST['display_errors'])) {
        $phpini->setIniOption('phpiniDisplayErrors', clean_input($_POST['display_errors']));
    }

    if (isset($_POST['error_reporting'])) {
        $phpini->setIniOption('phpiniErrorReporting', clean_input($_POST['error_reporting']));
    }

    if ($phpini->getClientPermission('phpiniDisableFunctions') == 'yes') {
        $disabledFunctions = [];

        foreach (
            ['show_source', 'system', 'shell_exec', 'shell_exec', 'passthru', 'exec', 'phpinfo', 'shell', 'symlink', 'proc_open', 'popen'] as $funct
        ) {
            if (isset($_POST[$funct])) {
                $disabledFunctions[] = $funct;
            }
        }

        if ((isset($_POST['mail']) && $phpini->clientHasPermission('phpiniMailFunction')) || !$phpini->clientHasPermission('phpiniMailFunction')) {
            $disabledFunctions[] = 'mail';
        }

        $phpini->setIniOption('phpiniDisableFunctions', $phpini->assembleDisableFunctions($disabledFunctions));
    } elseif ($phpini->getClientPermission('phpiniDisableFunctions') == 'exec') {
        $disabledFunctions = explode(',', $phpini->getIniOption('phpiniDisableFunctions'));

        if (isset($_POST['exec']) && $_POST['exec'] == 'yes') {
            $disabledFunctions = array_diff($disabledFunctions, ['exec']);
        } elseif (!in_array('exec', $disabledFunctions, true)) {
            $disabledFunctions[] = 'exec';
        }

        $phpini->setIniOption('phpiniDisableFunctions', $phpini->assembleDisableFunctions($disabledFunctions));
    }

    $phpini->saveIniOptions($_SESSION['user_id'], $domainId, $domainType);
    $phpini->updateDomainStatuses($_SESSION['user_id'], $domainId, $domainType, true);

    set_page_message(tr('PHP configuration successfuly updated.'), 'success');
    redirectTo('domains_manage.php');
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl Template engine
 * @param PHPini $phpini PHP editor instance
 * @return void
 */
function generatePage($tpl, $phpini)
{
    $config = Registry::get('config');

    if (isset($_GET['domain_id']) && isset($_GET['domain_type'])) {
        $domainId = intval($_GET['domain_id']);
        $domainType = clean_input($_GET['domain_type']);
    } else {
        $domainId = get_user_domain_id($_SESSION['user_id']);
        $domainType = 'dmn';
    }

    $configLevel = $phpini->getClientPermission('phpiniConfigLevel');

    if (($configLevel == 'per_user' && $domainType != 'dmn') || ($configLevel == 'per_domain' && !in_array($domainType, ['dmn', 'als'], true))) {
        showBadRequestErrorPage();
    }

    $dmnsData = getDomainData($configLevel);

    $knowDomain = false;
    foreach ($dmnsData as $dmnData) {
        if ($dmnData['domain_id'] == $domainId && $dmnData['domain_type'] == $domainType) {
            $knowDomain = true;
        }
    }

    if (!$knowDomain) {
        showBadRequestErrorPage();
    }

    $phpini->loadIniOptions($_SESSION['user_id'], $domainId, $domainType);

    if ($configLevel != 'per_user') {
        foreach ($dmnsData as $dmnData) {
            $tpl->assign([
                'DOMAIN_ID'           => tohtml($dmnData['domain_id'], 'htmlAttr'),
                'DOMAIN_TYPE'         => tohtml($dmnData['domain_type'], 'htmlAttr'),
                'DOMAIN_NAME_UNICODE' => tohtml(decode_idna($dmnData['domain_name'])),
                'SELECTED'            => $dmnData['domain_id'] == $domainId && $dmnData['domain_type'] == $domainType ? ' selected' : ''
            ]);

            $tpl->parse('DOMAIN_NAME_BLOCK', '.domain_name_block');
        }

        $tpl->assign('DOMAIN_TYPE', $domainType);
    } else {
        $tpl->assign('DOMAIN_LIST_BLOCK', '');
    }

    if (!$phpini->clientHasPermission('phpiniAllowUrlFopen')) {
        $tpl->assign('ALLOW_URL_FOPEN_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_ALLOW_URL_FOPEN'  => tr('Allow URL fopen'),
            'ALLOW_URL_FOPEN_ON'  => $phpini->getIniOption('phpiniAllowUrlFopen') == 'on' ? ' checked' : '',
            'ALLOW_URL_FOPEN_OFF' => $phpini->getIniOption('phpiniAllowUrlFopen') == 'off' ? ' checked' : ''
        ]);
    }

    if (!$phpini->clientHasPermission('phpiniDisplayErrors')) {
        $tpl->assign('DISPLAY_ERRORS_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_DISPLAY_ERRORS'  => tr('Display errors'),
            'DISPLAY_ERRORS_ON'  => $phpini->getIniOption('phpiniDisplayErrors') == 'on' ? ' checked' : '',
            'DISPLAY_ERRORS_OFF' => $phpini->getIniOption('phpiniDisplayErrors') == 'off' ? ' checked' : ''
        ]);
    }

    if (strpos($config{'Servers::httpd'}, 'apache2') !== false) {
        $apache2Config = new ConfigFile(utils_normalizePath(Registry::get('config')['CONF_DIR'] . '/apache2/apache.data'));
        $isApache2Itk = $apache2Config['APACHE2_MPM'] == 'itk';
    } else {
        $isApache2Itk = false;
    }

    if (!$phpini->clientHasPermission('phpiniDisplayErrors') || $isApache2Itk) {
        $tpl->assign('ERROR_REPORTING_BLOCK', '');
    } else {
        $errorReporting = $phpini->getIniOption('phpiniErrorReporting');
        $tpl->assign([
            'TR_ERROR_REPORTING'              => tohtml(tr('Error reporting')),
            'TR_ERROR_REPORTING_DEFAULT'      => tohtml(tr('All errors, except E_NOTICES, E_STRICT AND E_DEPRECATED (Default)')),
            'TR_ERROR_REPORTING_DEVELOPEMENT' => tohtml(tr('All errors (Development)')),
            'TR_ERROR_REPORTING_PRODUCTION'   => tohtml(tr('All errors, except E_DEPRECATED and E_STRICT (Production)')),
            'ERROR_REPORTING_0'               => $errorReporting == 'E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED' ? ' selected' : '',
            'ERROR_REPORTING_1'               => $errorReporting == 'E_ALL & ~E_DEPRECATED & ~E_STRICT' ? ' selected' : '',
            'ERROR_REPORTING_2'               => $errorReporting == '-1' ? ' selected' : ''
        ]);
    }

    if (strpos($config['Servers:httpd'], 'Apache2') !== false) {
        $apache2Config = new ConfigFile(utils_normalizePath(Registry::get('config')['CONF_DIR'] . '/apache2/apache.data'));
        $isApache2Itk = $apache2Config['APACHE2_MPM'] == 'itk';
    } else {
        $isApache2Itk = false;
    }

    if ($isApache2Itk || !$phpini->clientHasPermission('phpiniDisableFunctions')) {
        $tpl->assign([
            'DISABLE_FUNCTIONS_BLOCK' => '',
            'DISABLE_EXEC_BLOCK'      => ''
        ]);
    } elseif ($phpini->getClientPermission('phpiniDisableFunctions') == 'exec') {
        $disableFunctions = explode(',', $phpini->getIniOption('phpiniDisableFunctions'));
        $execYes = in_array('exec', $disableFunctions) ? false : true;
        $tpl->assign([
            'TR_DISABLE_FUNCTIONS_EXEC' => tohtml(tr('PHP exec() function')),
            'TR_EXEC_HELP'              => tohtml(tr("When set to 'yes', your PHP scripts can call the PHP exec() function."), 'htmlAttr'),
            'EXEC_YES'                  => $execYes ? ' checked' : '',
            'EXEC_NO'                   => $execYes ? '' : ' checked',
            'DISABLE_FUNCTIONS_BLOCK'   => ''
        ]);
    } else {
        $disableableFunctions = ['EXEC', 'PASSTHRU', 'PHPINFO', 'POPEN', 'PROC_OPEN', 'SHOW_SOURCE', 'SYSTEM', 'SHELL', 'SHELL_EXEC', 'SYMLINK'];

        if ($phpini->clientHasPermission('phpiniMailFunction')) {
            $disableableFunctions[] = 'MAIL';
        } else {
            $tpl->assign('MAIL_FUNCTION_BLOCK', '');
        }

        $disabledFunctions = explode(',', $phpini->getIniOption('phpiniDisableFunctions'));
        foreach ($disableableFunctions as $function) {
            $tpl->assign($function, in_array(strtolower($function), $disabledFunctions, true) ? ' checked' : '');
        }

        $tpl->assign([
            'TR_DISABLE_FUNCTIONS' => tohtml(tr('Disabled functions')),
            'DISABLE_EXEC_BLOCK'   => ''
        ]);
    }

    $tpl->assign([
        'TR_PHP_SETTINGS' => tohtml(tr('PHP Settings')),
        'TR_YES'          => tohtml(tr('Yes')),
        'TR_NO'           => tohtml(tr('No'))
    ]);
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptStart);
customerHasFeature('php_editor') or showBadRequestErrorPage();

$phpini = PHPini::getInstance();
$phpini->loadResellerPermissions($_SESSION['user_created_by']);
$phpini->loadClientPermissions($_SESSION['user_id']);

if (!empty($_POST)) {
    updatePhpConfig($phpini);
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'                  => 'shared/layouts/ui.tpl',
    'page'                    => 'client/phpini.tpl',
    'page_message'            => 'layout',
    'domain_list_block'       => 'page',
    'domain_name_block'       => 'domain_list_block',
    'allow_url_fopen_block'   => 'page',
    'display_errors_block'    => 'page',
    'disable_functions_block' => 'page',
    'mail_function_block'     => 'disable_functions_block',
    'disable_exec_block'      => 'page',
    'error_reporting_block'   => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE'     => tohtml(tr('Client / Domains / PHP Settings'), 'htmlAttr'),
    'TR_MENU_PHPINI'    => tohtml(tr('PHP Editor')),
    'TR_DOMAIN'         => tohtml(tr('Domain')),
    'TR_DOMAIN_TOOLTIP' => tohtml(tr('Domain for which PHP Editor must act.'), 'htmlAttr'),
    'TR_UPDATE'         => tohtml(tr('Update'), 'htmlAttr'),
    'TR_CANCEL'         => tohtml(tr('Cancel'))
]);

generateNavigation($tpl);
generatePage($tpl, $phpini);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
