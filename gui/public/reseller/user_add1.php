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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2016 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Check input data
 *
 * @return void
 */
function reseller_checkData()
{
    $cfg = iMSCP_Registry::get('config');

    if (!isset($_POST['dmn_name']) || $_POST['dmn_name'] === '') {
        set_page_message(tr('Domain name cannot be empty.'), 'error');
        return;
    }

    $dmnName = clean_input($_POST['dmn_name']);

    global $dmnNameValidationErrMsg;
    if (!isValidDomainName($dmnName)) {
        set_page_message($dmnNameValidationErrMsg, 'error');
        return;
    }

    // www is considered as an alias of the domain
    while (strpos($dmnName, 'www.') !== false) {
        $dmnName = substr($dmnName, 4);
    }

    $asciiDmnName = encode_idna($dmnName);

    if (imscp_domain_exists($asciiDmnName, $_SESSION['user_id']) || $asciiDmnName == $cfg['BASE_SERVER_VHOST']) {
        set_page_message(tr('Domain %s is unavailable.', "<strong>$dmnName</strong>"), 'error');
        return;
    }

    if ((!isset($_POST['datepicker']) || $_POST['datepicker'] === '') && !isset($_POST['never_expire'])) {
        set_page_message(tr('Domain expiration date must be filled.'), 'error');
        return;
    }

    $dmnExpire = isset($_POST['datepicker']) ? @strtotime(clean_input($_POST['datepicker'])) : 0;

    if ($dmnExpire === false) {
        set_page_message('Invalid expiration date.', 'error');
        return;
    }

    $hpId = isset($_POST['dmn_tpl']) ? clean_input($_POST['dmn_tpl']) : 0;
    $customizeHp = $hpId > 0 && isset($_POST['chtpl']) ? $_POST['chtpl'] : '_no_';

    if ($hpId == 0 || $customizeHp == '_yes_') {
        $_SESSION['dmn_name'] = $asciiDmnName;
        $_SESSION['dmn_expire'] = $dmnExpire;
        $_SESSION['dmn_tpl'] = $hpId;
        $_SESSION['chtpl'] = '_yes_';
        $_SESSION['step_one'] = '_yes_';
        redirectTo('user_add2.php');
    }

    if (reseller_limits_check($_SESSION['user_id'], $hpId)) {
        $_SESSION['dmn_name'] = $asciiDmnName;
        $_SESSION['dmn_expire'] = $dmnExpire;
        $_SESSION['dmn_tpl'] = $hpId;
        $_SESSION['chtpl'] = $customizeHp;
        $_SESSION['step_one'] = '_yes_';
        redirectTo('user_add3.php');
    }

    set_page_message(tr('Hosting plan limits exceed reseller limits.'), 'error');
}

/**
 * Show first page of add user with data
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function reseller_generatePage($tpl)
{
    $tpl->assign(array(
        'DOMAIN_NAME_VALUE' => isset($_POST['dmn_name']) ? tohtml($_POST['dmn_name']) : '',
        'DATEPICKER_VALUE' => isset($_POST['datepicker']) ? tohtml($_POST['datepicker']) : '',
        'DATEPICKER_DISABLED' => isset($_POST['datepicker']) ? '' : ' disabled',
        'NEVER_EXPIRE_CHECKED' => isset($_POST['datepicker']) ? '' : '  checked',
        'CHTPL1_VAL' => isset($_POST['chtpl']) && $_POST['chtpl'] == '_yes_' ? ' checked' : '',
        'CHTPL2_VAL' => isset($_POST['chtpl']) && $_POST['chtpl'] == '_yes_' ? '' : ' checked'
    ));

    $stmt = exec_query('SELECT id, name FROM hosting_plans WHERE reseller_id = ? AND status = ? ORDER BY name', array(
        $_SESSION['user_id'], '1'
    ));

    if (!$stmt->rowCount()) {
        $tpl->assign('HOSTING_PLAN_ENTRIES_BLOCK', '');
        return;
    }

    while ($row = $stmt->fetchRow()) {
        $hpId = isset($_POST['dmn_tpl']) ? $_POST['dmn_tpl'] : '';
        $tpl->assign(array(
            'HP_NAME' => tohtml($row['name']),
            'HP_ID' => tohtml($row['id']),
            'HP_SELECTED' => $row['id'] == $hpId ? ' selected' : ''
        ));
        $tpl->parse('HOSTING_PLAN_ENTRY_BLOCK', '.hosting_plan_entry_block');
    }
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);
check_login('reseller');

$cfg = iMSCP_Registry::get('config');

if (!empty($_POST)) {
    reseller_checkData();
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'reseller/user_add1.tpl',
    'page_message' => 'layout',
    'hosting_plan_entries_block' => 'page',
    'hosting_plan_entry_block' => 'hosting_plan_entries_block',
    'customize_hosting_plan_block' => 'hosting_plan_entries_block'
));

$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Reseller / Customers / Add Customer'),
    'TR_ADD_USER' => tr('Add user'),
    'TR_CORE_DATA' => tr('Domain data'),
    'TR_DOMAIN_NAME' => tr('Domain name'),
    'TR_DOMAIN_EXPIRE' => tr('Domain expiration date'),
    'TR_EXPIRE_CHECKBOX' => tr('Never'),
    'TR_CHOOSE_HOSTING_PLAN' => tr('Choose hosting plan'),
    'TR_PERSONALIZE_TEMPLATE' => tr('Personalise template'),
    'TR_YES' => tr('yes'),
    'TR_NO' => tr('no'),
    'TR_NEXT_STEP' => tr('Next step')
));

generateNavigation($tpl);
reseller_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

unsetMessages();
