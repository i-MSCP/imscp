<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

/**
 * Tells whether or not the status of the given domain
 *
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
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

    $stmt = exec_query($query, $domainId);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow();
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
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function getDomainData($configLevel)
{
    // Per user means only main domain
    $query = "
        SELECT domain_name, domain_status, domain_id, 'dmn' AS domain_type FROM domain AS t1
        WHERE domain_admin_id = :admin_id AND domain_status <> :domain_status
    ";

    # Per domain or per site means also domain aliases
    if ($configLevel == 'per_domain' || $configLevel == 'per_site') {
        $query .= "
            UNION ALL
            SELECT t1.alias_name, t1.alias_status, alias_id, 'als' FROM domain_aliasses AS t1
            JOIN domain AS t2 USING(domain_id)
            WHERE t2.domain_admin_id = :admin_id AND t1.url_forward = 'no' AND t1.alias_status <> :domain_status
        ";
    }

    # Per site also means also subdomains
    if ($configLevel == 'per_site') {
        $query .= "
            UNION ALL
            SELECT CONCAT(t1.subdomain_name, '.', t2.domain_name), t1.subdomain_status, subdomain_id, 'sub'
            FROM subdomain AS t1
            JOIN domain AS t2 USING(domain_id)
            WHERE t2.domain_admin_id  = :admin_id
            AND t1.subdomain_status <> :domain_status
            UNION ALL
            SELECT CONCAT(t1.subdomain_alias_name, '.', t2.alias_name), t1.subdomain_alias_status,
                subdomain_alias_id, 'subals'
            FROM subdomain_alias AS t1
            JOIN domain_aliasses t2 USING(alias_id)
            JOIN domain AS t3 USING(domain_id)
            WHERE domain_admin_id = :admin_id
            AND subdomain_alias_status <> :domain_status
        ";
    }

    $stmt = exec_query($query, ['admin_id' => $_SESSION['user_id'], 'domain_status' => 'todelete']);
    return $stmt->fetchAll();
}

/**
 * Update PHP configuration options
 *
 * @param iMSCP_PHPini $phpini PHP editor instance
 * @param string $configLevel PHP configuration level
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @çeturn void
 */
function updatePhpConfig($phpini, $configLevel)
{
    global $phpini, $configLevel;

    if (isset($_POST['domain_id']) && isset($_POST['domain_type'])) {
        $dmnId = intval($_POST['domain_id']);
        $dmnType = clean_input($_POST['domain_type']);
    } else {
        $dmnId = get_user_domain_id($_SESSION['user_id']);
        $dmnType = 'dmn';
    }

    if ($configLevel == 'per_user' && $dmnType != 'dmn'
        || $configLevel == 'per_domain' && !in_array($dmnType, ['dmn', 'als'])
    ) {
        showBadRequestErrorPage();
    }

    if ($configLevel == 'per_user' && $dmnType != 'dmn' || $configLevel == 'per_domain' &&
        !in_array($dmnType, ['dmn', 'als'])
    ) {
        showBadRequestErrorPage();
    }

    if (!isDomainStatusOk($dmnId, $dmnType)) {
        set_page_message(tr('Domain status is not ok.'), 'error');
        return;
    }

    $phpini->loadDomainIni($_SESSION['user_id'], $dmnId, $dmnType);
    $oldData = $phpini->getDomainIni();

    if (isset($_POST['allow_url_fopen'])) {
        $phpini->setDomainIni('phpiniAllowUrlFopen', clean_input($_POST['allow_url_fopen']));
    }

    if (isset($_POST['display_errors'])) {
        $phpini->setDomainIni('phpiniDisplayErrors', clean_input($_POST['display_errors']));
    }

    if (isset($_POST['error_reporting'])) {
        $phpini->setDomainIni('phpiniErrorReporting', clean_input($_POST['error_reporting']));
    }

    if ($phpini->getClientPermission('phpiniDisableFunctions') == 'yes') {
        $disabledFunctions = [];

        foreach (
            [
                'show_source', 'system', 'shell_exec', 'shell_exec', 'passthru', 'exec', 'phpinfo', 'shell',
                'symlink', 'proc_open', 'popen'
            ] as $function
        ) {
            if (isset($_POST[$function])) {
                $disabledFunctions[] = $function;
            }
        }

        if ((isset($_POST['mail']) && $phpini->clientHasPermission('phpiniMailFunction'))
            || !$phpini->clientHasPermission('phpiniMailFunction')
        ) {
            $disabledFunctions[] = 'mail';
        }

        $phpini->setDomainIni('phpiniDisableFunctions', $phpini->assembleDisableFunctions($disabledFunctions));
    } elseif ($phpini->getClientPermission('phpiniDisableFunctions') == 'exec') {
        $disabledFunctions = explode(',', $phpini->getDomainIni('phpiniDisableFunctions'));

        if (isset($_POST['exec']) && $_POST['exec'] == 'yes') {
            $disabledFunctions = array_diff($disabledFunctions, ['exec']);
        } elseif (!in_array('exec', $disabledFunctions, true)) {
            $disabledFunctions[] = 'exec';
        }

        $phpini->setDomainIni('phpiniDisableFunctions', $phpini->assembleDisableFunctions($disabledFunctions));
    }

    if ($phpini->getDomainIni() == $oldData) {
        set_page_message(tr('Nothing has been changed.'), 'info');
        redirectTo('domains_manage.php');
    }

    $phpini->saveDomainIni($_SESSION['user_id'], $dmnId, $dmnType);
    $phpini->updateDomainStatuses($configLevel, $_SESSION['user_id'], $dmnId, $dmnType);

    send_request();
    set_page_message(tr('PHP configuration scheduled for update.'), 'success');
    redirectTo('domains_manage.php');
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param iMSCP_PHPini $phpini PHP editor instance
 * @param iMSCP_Config_Handler_File $config Configuration handler
 * @param string $configLevel PHP configuration level
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function generatePage($tpl, $phpini, $config, $configLevel)
{
    if (isset($_GET['domain_id']) && isset($_GET['domain_type'])) {
        $dmnId = intval($_GET['domain_id']);
        $dmnType = clean_input($_GET['domain_type']);
    } else {
        $dmnId = get_user_domain_id($_SESSION['user_id']);
        $dmnType = 'dmn';
    }

    if ($configLevel == 'per_user' && $dmnType != 'dmn'
        || $configLevel == 'per_domain' && !in_array($dmnType, ['dmn', 'als'])
    ) {
        showBadRequestErrorPage();
    }

    $dmnsData = getDomainData($configLevel);

    $knowDomain = false;
    foreach ($dmnsData as $dmnData) {
        if ($dmnData['domain_id'] == $dmnId && $dmnData['domain_type'] == $dmnType) {
            $knowDomain = true;
        }
    }

    if (!$knowDomain) {
        showBadRequestErrorPage();
    }

    $phpini->loadDomainIni($_SESSION['user_id'], $dmnId, $dmnType);

    if ($configLevel != 'per_user') {
        foreach ($dmnsData as $dmnData) {
            $tpl->assign([
                'DOMAIN_ID'           => tohtml($dmnData['domain_id'], 'htmlAttr'),
                'DOMAIN_TYPE'         => tohtml($dmnData['domain_type'], 'htmlAttr'),
                'DOMAIN_NAME_UNICODE' => tohtml(decode_idna($dmnData['domain_name'])),
                'SELECTED'            => ($dmnData['domain_id'] == $dmnId && $dmnData['domain_type'] == $dmnType) ? ' selected' : ''
            ]);

            $tpl->parse('DOMAIN_NAME_BLOCK', '.domain_name_block');
        }

        $tpl->assign('DOMAIN_TYPE', $dmnType);
    } else {
        $tpl->assign('DOMAIN_LIST_BLOCK', '');
    }

    if (!$phpini->clientHasPermission('phpiniAllowUrlFopen')) {
        $tpl->assign('ALLOW_URL_FOPEN_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_ALLOW_URL_FOPEN'  => tr('Allow URL fopen'),
            'ALLOW_URL_FOPEN_ON'  => $phpini->getDomainIni('phpiniAllowUrlFopen') == 'on' ? ' checked' : '',
            'ALLOW_URL_FOPEN_OFF' => $phpini->getDomainIni('phpiniAllowUrlFopen') == 'off' ? ' checked' : ''
        ]);
    }

    if (!$phpini->clientHasPermission('phpiniDisplayErrors')) {
        $tpl->assign('DISPLAY_ERRORS_BLOCK', '');
    } else {
        $tpl->assign([
            'TR_DISPLAY_ERRORS'  => tr('Display errors'),
            'DISPLAY_ERRORS_ON'  => $phpini->getDomainIni('phpiniDisplayErrors') == 'on' ? ' checked' : '',
            'DISPLAY_ERRORS_OFF' => $phpini->getDomainIni('phpiniDisplayErrors') == 'off' ? ' checked' : ''
        ]);
    }

    if (!$phpini->clientHasPermission('phpiniDisplayErrors') || $config['HTTPD_SERVER'] == 'apache_itk') {
        $tpl->assign('ERROR_REPORTING_BLOCK', '');
    } else {
        $errorReporting = $phpini->getDomainIni('phpiniErrorReporting');
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

    if ($config['HTTPD_SERVER'] == 'apache_itk' || !$phpini->clientHasPermission('phpiniDisableFunctions')) {
        $tpl->assign([
            'DISABLE_FUNCTIONS_BLOCK' => '',
            'DISABLE_EXEC_BLOCK'      => ''
        ]);
    } elseif ($phpini->getClientPermission('phpiniDisableFunctions') == 'exec') {
        $disableFunctions = explode(',', $phpini->getDomainIni('phpiniDisableFunctions'));
        $execYes = in_array('exec', $disableFunctions) ? false : true;
        $tpl->assign([
            'TR_DISABLE_FUNCTIONS_EXEC' => tohtml(tr('PHP exec() function')),
            'TR_EXEC_HELP'              => tohtml(tr("When set to 'yes', your PHP scripts can call the PHP exec() function."), 'htmlAttr'),
            'EXEC_YES'                  => $execYes ? ' checked' : '',
            'EXEC_NO'                   => $execYes ? '' : ' checked',
            'DISABLE_FUNCTIONS_BLOCK'   => ''
        ]);
    } else {
        $disableableFunctions = [
            'EXEC', 'PASSTHRU', 'PHPINFO', 'POPEN', 'PROC_OPEN', 'SHOW_SOURCE', 'SYSTEM', 'SHELL', 'SHELL_EXEC', 'SYMLINK'
        ];

        if ($phpini->clientHasPermission('phpiniMailFunction')) {
            $disableableFunctions[] = 'MAIL';
        } else {
            $tpl->assign('MAIL_FUNCTION_BLOCK', '');
        }

        $disabledFunctions = explode(',', $phpini->getDomainIni('phpiniDisableFunctions'));
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
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
customerHasFeature('php_editor') or showBadRequestErrorPage();

$phpini = iMSCP_PHPini::getInstance();
$phpini->loadResellerPermissions($_SESSION['user_created_by']); // Load reseller PHP permissions
$phpini->loadClientPermissions($_SESSION['user_id']); // Load client PHP permissions

$config = iMSCP_Registry::get('config');
$confDir = $config['CONF_DIR'];
$srvConfig = new iMSCP_Config_Handler_File("$confDir/php/php.data");
$configLevel = $srvConfig['PHP_CONFIG_LEVEL'];

if (!empty($_POST)) {
    updatePhpConfig($phpini, $configLevel);
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
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
generatePage($tpl, $phpini, $config, $configLevel);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
