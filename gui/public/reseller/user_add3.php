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

/**
 * Get data from previous step
 *
 * @return bool
 */
function getPreviousStepData()
{
    global $hpId, $dmnName, $adminName, $dmnExpire;

    if (isset($_SESSION['dmn_expire'])) {
        $dmnExpire = $_SESSION['dmn_expire'];
    }

    if (isset($_SESSION['step_one'])) {
        $stepTwo = $_SESSION['dmn_name'] . ';' . $_SESSION['dmn_tpl'];
        $hpId = $_SESSION['dmn_tpl'];
        unset($_SESSION['dmn_name']);
        unset($_SESSION['dmn_tpl']);
        unset($_SESSION['chtpl']);
        unset($_SESSION['step_one']);
    } elseif (isset($_SESSION['step_two_data'])) {
        $stepTwo = $_SESSION['step_two_data'];
        unset($_SESSION['step_two_data']);
    } elseif (isset($_SESSION['local_data'])) {
        $stepTwo = $_SESSION['local_data'];
        unset($_SESSION['local_data']);
    } else {
        $stepTwo = "'';0";
    }

    list($dmnName, $hpId) = explode(';', $stepTwo);
    $adminName = $dmnName;

    if (!isValidDomainName($dmnName) || $hpId == '') {
        return false;
    }

    return true;
}

/**
 * Generates page
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function generatePage($tpl)
{
    global $hpId, $dmnName, $adminName, $email, $customerId, $firstName, $lastName, $gender, $firm, $zip, $city, $state,
           $country, $street1, $street2, $phone, $fax, $domainIp;

    $adminName = decode_idna($adminName);

    $tpl->assign(array(
        'VL_USERNAME' => tohtml($adminName, 'htmlAttr'),
        'VL_MAIL' => tohtml($email, 'htmlAttr'),
        'VL_USR_ID' => tohtml($customerId, 'htmlAttr'),
        'VL_USR_NAME' => tohtml($firstName, 'htmlAttr'),
        'VL_LAST_USRNAME' => tohtml($lastName, 'htmlAttr'),
        'VL_USR_FIRM' => tohtml($firm, 'htmlAttr'),
        'VL_USR_POSTCODE' => tohtml($zip, 'htmlAttr'),
        'VL_USRCITY' => tohtml($city, 'htmlAttr'),
        'VL_USRSTATE' => tohtml($state, 'htmlAttr'),
        'VL_MALE' => $gender == 'M' ? ' selected' : '',
        'VL_FEMALE' => $gender == 'F' ? ' selected' : '',
        'VL_UNKNOWN' => $gender == 'U' ? ' selected' : '',
        'VL_COUNTRY' => tohtml($country, 'htmlAttr'),
        'VL_STREET1' => tohtml($street1, 'htmlAttr'),
        'VL_STREET2' => tohtml($street2, 'htmlAttr'),
        'VL_PHONE' => tohtml($phone, 'htmlAttr'),
        'VL_FAX' => tohtml($fax, 'htmlAttr')
    ));

    reseller_generate_ip_list($tpl, $_SESSION['user_id'], $domainIp);
    $_SESSION['local_data'] = "$dmnName;$hpId";
}

/**
 * Add customer
 *
 * @throws iMSCP_Exception
 * @return void
 */
function addCustomer()
{
    global $hpId, $dmnName, $dmnExpire, $domainIp, $adminName, $email, $password, $customerId, $firstName, $lastName,
           $gender, $firm, $zip, $city, $state, $country, $phone, $fax, $street1, $street2;

    if (!isset($_POST['domain_ip'])) {
        showBadRequestErrorPage();
    }

    $domainIp = intval($_POST['domain_ip']);
    $stmt = exec_query('SELECT reseller_ips FROM reseller_props WHERE reseller_id = ?', $_SESSION['user_id']);
    if (!$stmt->rowCount()) {
        throw new iMSCP_Exception(sprintf('Could not find IPs for reseller with ID %s', $_SESSION['user_id']));
    }

    $resellerIps = $stmt->fetchRow();
    $resellerIps = explode(';', rtrim($resellerIps['reseller_ips'], ';'));
    if (!in_array($domainIp, $resellerIps)) {
        showBadRequestErrorPage();
    }

    $cfg = iMSCP_Registry::get('config');

    if (isset($_SESSION['ch_hpprops'])) {
        $props = $_SESSION['ch_hpprops'];
        unset($_SESSION['ch_hpprops']);
    } else {
        $stmt = exec_query('SELECT props FROM hosting_plans WHERE reseller_id = ? AND id = ?', array(
            $_SESSION['user_id'], $hpId
        ));
        $data = $stmt->fetchRow();
        $props = $data['props'];
    }

    list(
        $php, $cgi, $sub, $als, $mail, $ftp, $sql_db, $sql_user, $traff, $disk, $backup, $dns, $aps, $phpEditor,
        $phpiniAllowUrlFopen, $phpiniDisplayErrors, $phpiniDisableFunctions, $phpMailFunction, $phpiniPostMaxSize,
        $phpiniUploadMaxFileSize, $phpiniMaxExecutionTime, $phpiniMaxInputTime, $phpiniMemoryLimit, $extMailServer,
        $webFolderProtection, $mailQuota
    ) = explode(';', $props);

    $php = str_replace('_', '', $php);
    $cgi = str_replace('_', '', $cgi);
    $backup = str_replace('_', '', $backup);
    $dns = str_replace('_', '', $dns);
    $aps = str_replace('_', '', $aps);
    $extMailServer = str_replace('_', '', $extMailServer);
    $webFolderProtection = str_replace('_', '', $webFolderProtection);
    $encryptedPassword = cryptPasswordWithSalt($password);
    $db = iMSCP_Database::getInstance();

    try {
        $db->beginTransaction();

        iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeAddDomain, array(
            'domainName' => $dmnName,
            'createdBy' => $_SESSION['user_id'],
            'customerId' => $customerId,
            'customerEmail' => $email
        ));

        exec_query(
            '
                INSERT INTO admin (
                    admin_name, admin_pass, admin_type, domain_created, created_by, fname, lname, firm, zip, city, state,
                    country, email, phone, fax, street1, street2, customer_id, gender, admin_status
                ) VALUES (
                    ?, ?, ?, unix_timestamp(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ',
            array(
                $adminName, $encryptedPassword, 'user', $_SESSION['user_id'], $firstName, $lastName, $firm, $zip, $city,
                $state, $country, $email, $phone, $fax, $street1, $street2, $customerId, $gender, 'toadd'
            )
        );

        $adminId = $db->insertId();

        exec_query(
            '
                INSERT INTO domain (
                    domain_name, domain_admin_id, domain_created, domain_expires, domain_mailacc_limit,
                    domain_ftpacc_limit, domain_traffic_limit, domain_sqld_limit, domain_sqlu_limit, domain_status,
                    domain_alias_limit, domain_subd_limit, domain_ip_id, domain_disk_limit, domain_disk_usage,
                    domain_php, domain_cgi, allowbackup, domain_dns, domain_software_allowed, phpini_perm_system,
                    phpini_perm_allow_url_fopen, phpini_perm_display_errors, phpini_perm_disable_functions,
                    phpini_perm_mail_function, domain_external_mail, web_folder_protection, mail_quota
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ',
            array(
                $dmnName, $adminId, time(), $dmnExpire, $mail, $ftp, $traff, $sql_db, $sql_user, 'toadd', $als, $sub,
                $domainIp, $disk, 0, $php, $cgi, $backup, $dns, $aps, $phpEditor, $phpiniAllowUrlFopen,
                $phpiniDisplayErrors, $phpiniDisableFunctions, $phpMailFunction, $extMailServer, $webFolderProtection,
                $mailQuota
            )
        );

        $dmnId = $db->insertId();

        if ($phpEditor == 'yes') {
            $phpini = iMSCP_PHPini::getInstance();
            $phpini->setDomainIni('phpiniMemoryLimit', $phpiniMemoryLimit); // Must be set before phpiniPostMaxSize
            $phpini->setDomainIni('phpiniPostMaxSize', $phpiniPostMaxSize); // Must be set before phpiniUploadMaxFileSize
            $phpini->setDomainIni('phpiniUploadMaxFileSize', $phpiniUploadMaxFileSize);
            $phpini->setDomainIni('phpiniMaxExecutionTime', $phpiniMaxExecutionTime);
            $phpini->setDomainIni('phpiniMaxInputTime', $phpiniMaxInputTime);
            $phpini->saveDomainIni($adminId, $dmnId, 'dmn');
        }

        exec_query('INSERT INTO htaccess_users (dmn_id, uname, upass, status) VALUES (?, ?, ?, ?)', array(
            $dmnId, $dmnName, $encryptedPassword, 'toadd'
        ));
        exec_query('INSERT INTO htaccess_groups (dmn_id, ugroup, members, status) VALUES (?, ?, ?, ?)', array(
            $dmnId, 'statistics', $db->insertId(), 'toadd'
        ));

        if ($cfg['CREATE_DEFAULT_EMAIL_ADDRESSES']) {
            client_mail_add_default_accounts($dmnId, $email, $dmnName);
        }

        send_add_user_auto_msg($_SESSION['user_id'], $adminName, $password, $email, $firstName, $lastName, tr('Customer'));
        exec_query('INSERT INTO user_gui_props (user_id, lang, layout) VALUES (?, ?, ?)', array(
            $adminId, $cfg['USER_INITIAL_LANG'], $cfg['USER_INITIAL_THEME']
        ));
        update_reseller_c_props($_SESSION['user_id']);

        iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterAddDomain, array(
            'domainName' => $dmnName,
            'createdBy' => $_SESSION['user_id'],
            'customerId' => $adminId,
            'customerEmail' => $email,
            'domainId' => $dmnId
        ));

        $db->commit();
        send_request();
        write_log("{$_SESSION['user_logged']} added new customer: $adminName", E_USER_NOTICE);
        set_page_message(tr('Customer account successfully scheduled for creation.'), 'success');
        redirectTo('users.php');
    } catch (iMSCP_Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);
check_login('reseller');

// Initialize global variables
$email = $customerId = $firstName = $lastName = $firm = $zip = $city = $state = $country = $street1 = $street2 = '';
$phone = $mail = $fax = $domainIp = '';
$gender = 'U';

if (!getPreviousStepData()) {
    set_page_message(tr('Data were altered. Please try again.'), 'error');
    unsetMessages();
    redirectTo('user_add1.php');
}

$phpini = iMSCP_PHPini::getInstance();
$phpini->loadResellerPermissions($_SESSION['user_id']); // Load reseller PHP permissions
$phpini->loadClientPermissions(); // Load client default PHP permissions
$phpini->loadDomainIni(); // Load domain default PHP configuration options

if (isset($_POST['uaction']) && 'user_add3_nxt' == $_POST['uaction'] && !isset($_SESSION['step_two_data'])) {
    if (check_ruser_data()) {
        addCustomer();
    }
} else {
    unset($_SESSION['step_two_data']);
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'reseller/user_add3.tpl',
    'page_message' => 'layout',
    'ip_entry' => 'page',
    'alias_feature' => 'page'
));
$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Reseller / Customers / Add Customer - Next Step'),
    'TR_ADD_USER' => tr('Add user'),
    'TR_CORE_DATA' => tr('Core data'),
    'TR_USERNAME' => tr('Username'),
    'TR_PASSWORD' => tr('Password'),
    'TR_REP_PASSWORD' => tr('Repeat password'),
    'TR_DOMAIN_IP' => tr('Domain IP'),
    'TR_USREMAIL' => tr('Email'),
    'TR_ADDITIONAL_DATA' => tr('Additional data'),
    'TR_CUSTOMER_ID' => tr('Customer ID'),
    'TR_FIRSTNAME' => tr('First name'),
    'TR_LASTNAME' => tr('Last name'),
    'TR_GENDER' => tr('Gender'),
    'TR_MALE' => tr('Male'),
    'TR_FEMALE' => tr('Female'),
    'TR_UNKNOWN' => tr('Unknown'),
    'TR_COMPANY' => tr('Company'),
    'TR_POST_CODE' => tr('Zip'),
    'TR_CITY' => tr('City'),
    'TR_STATE_PROVINCE' => tr('State/Province'),
    'TR_COUNTRY' => tr('Country'),
    'TR_STREET1' => tr('Street 1'),
    'TR_STREET2' => tr('Street 2'),
    'TR_MAIL' => tr('Email'),
    'TR_PHONE' => tr('Phone'),
    'TR_FAX' => tr('Fax'),
    'TR_BTN_ADD_USER' => tr('Add user')
));

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
