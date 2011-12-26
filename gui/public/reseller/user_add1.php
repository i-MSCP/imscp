<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010 by i-msCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 *
 * @license
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

/************************************************************************************
 * Script functions
 */

/**
 * Check correction of entered users data.
 *
 * @return bool
 */
function check_user_data()
{
    global $dmn_name, $dmn_expire, $dmn_chp, $dmn_pt, $validation_err_msg;

    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    if (isset($_POST['dmn_name'])) {
        $dmn_name = strtolower(trim($_POST['dmn_name']));
    }

    if(isset($_POST['datepicker']) && !empty($_POST['datepicker'])) {
        if(($dmn_expire = strtotime($_POST['datepicker'])) === false) {
            set_page_message(tr('Invalide domain expiration date.'), 'error');
            return false;
        }
    } elseif(isset($_POST['never_expire'])) {
        $dmn_expire = 0;
    } else {
        set_page_message(tr('Domain expiration date must be filled.'), 'error');
    }

    if (isset($_POST['dmn_tpl'])) {
        $dmn_chp = $_POST['dmn_tpl'];
    }

    if (isset($_POST['chtpl'])) {
        $dmn_pt = $_POST['chtpl'];
    }

    if (!validates_dname($dmn_name)) {
        set_page_message($validation_err_msg, 'error');
        return false;
    }

    // Must be perfomed after domain names syntax validation
    $dmn_name = encode_idna($dmn_name);

    if (imscp_domain_exists($dmn_name, $_SESSION['user_id'])) {
        set_page_message(tr('Domain already registered on the system.'), 'error');
        return false;
    } else if ($dmn_name == $cfg->BASE_SERVER_VHOST) {
        set_page_message(tr('Master domain cannot be used.'), 'error');
        return false;
    }

    // we have plans only for admins
    if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL == 'admin') {
        $dmn_pt = '_no_';
    }

    if ($dmn_pt == '_yes_' || !isset($_POST['dmn_tpl'])) {
        $_SESSION['dmn_name'] = $dmn_name;
        $_SESSION['dmn_expire'] = $dmn_expire;
        $_SESSION['dmn_tpl'] = $dmn_chp;
        $_SESSION['chtpl'] = $dmn_pt;
        $_SESSION['step_one'] = '_yes_';

        redirectTo('user_add2.php');
    } else {
        if (reseller_limits_check($_SESSION['user_id'], $dmn_chp)) {
            $_SESSION['dmn_name'] = $dmn_name;
            $_SESSION['dmn_expire'] = $dmn_expire;
            $_SESSION['dmn_tpl'] = $dmn_chp;
            $_SESSION['chtpl'] = $dmn_pt;
            $_SESSION['step_one'] = '_yes_';

            redirectTo('user_add3.php');
        } else {
            set_page_message(tr('Hosting plan values exceed reseller maximum values.'), 'error');
            return false;
        }
    }

    return true;
}

/**
 * Generates empty page.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function get_empty_au1_page($tpl)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $tpl->assign(
		array(
			'DMN_NAME_VALUE' => '',
			'DATEPICKER_VALUE' => '',
			'CHTPL1_VAL' => '',
			'CHTPL2_VAL' => $cfg->HTML_CHECKED));
}

/**
 * Show first page of add user with data.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function get_data_au1_page($tpl)
{
    global $dmn_name, $dmn_pt, $dmn_expire;

    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

	$tpl->assign(
		array(
			'DMN_NAME_VALUE' => tohtml($dmn_name),
			'DATEPICKER_VALUE' => isset($_POST['datepicker']) ? tohtml($_POST['datepicker']) : '',
			'CHTPL1_VAL' => $dmn_pt === '_yes_' ? $cfg->HTML_CHECKED : '',
			'CHTPL2_VAL' => $dmn_pt === '_yes_' ? '' : $cfg->HTML_CHECKED));
}

/**
 * Get list with hosting plan for selection.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  $reseller_id Reseller unique identifier
 * @return void
 */
function get_hp_data_list($tpl, $reseller_id)
{
    global $dmn_chp;

    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    if (isset($cfg->HOSTING_PLANS_LEVEL) && $cfg->HOSTING_PLANS_LEVEL === 'admin') {
        $query = "
			SELECT
				t1.`id`, t1.`reseller_id`, t1.`name`, t1.`props`, t1.`status`,
				t2.`admin_id`, t2.`admin_type`
			FROM
				`hosting_plans` AS t1, `admin` AS t2
			WHERE
				t2.`admin_type` = ?
			AND
				t1.`reseller_id` = t2.`admin_id`
			AND
				t1.`status` = 1
			ORDER BY
				t1.`name`
		";

        $rs = exec_query($query, 'admin');
        $tpl->assign('PERSONALIZE', '');

        if ($rs->recordCount() == 0) {
            set_page_message(tr('You have no hosting plans. Please contact your system administrator.'), 'error');
            $tpl->assign('ADD_USER', '');
            $tpl->assign('ADD_FORM', '');
        }
    } else {
        $query = "
			SELECT
				`id`, `name`, `props`, `status`
			FROM
				`hosting_plans`
			WHERE
				`reseller_id` = ?
			ORDER BY
				`name`
		";

        $rs = exec_query($query, $reseller_id);
    }

    if (0 !== $rs->rowCount()) {
        $orders_count = 0;
        while (($data = $rs->fetchRow())) {
            list(,,,,,,,,,,,,$hp_allowsoftware) = explode(";", $data['props']);

            if ($hp_allowsoftware == '_no_' || $hp_allowsoftware == '' ||
                $hp_allowsoftware == '_yes_' &&
                get_reseller_sw_installer($reseller_id) == 'yes'
            ) {
                $orders_count++;
                $dmn_chp = isset($dmn_chp) ? $dmn_chp : $data['id'];
                $tpl->assign(
                    array('HP_NAME' => tohtml($data['name']),
                         'CHN' => $data['id'],
                         'CH' . $data['id'] => ($data['id'] == $dmn_chp)
                             ? $cfg->HTML_SELECTED : ''));

                $tpl->parse('HP_ENTRY', '.hp_entry');
            }
        }

        if ($orders_count == 0) {
            $tpl->assign('ADD_USER', '');
        }
    } else {
        $tpl->assign('ADD_USER', '');
    }
}

/************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'reseller/user_add1.tpl',
		'page_message' => 'page',
		'add_user' => 'page',
		'hp_entry' => 'page',
		'personalize' => 'page'));

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Users/Add domain account - step 1'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_ADD_USER' => tr('Add user'),
		'TR_CORE_DATA' => tr('Core data'),
		'TR_DOMAIN_NAME' => tr('Domain name'),
		'TR_DOMAIN_EXPIRE' => tr('Domain expire date'),
		'TR_EXPIRE_CHECKBOX' => tr('or Check for <strong>never Expire</strong>'),
		'TR_CHOOSE_HOSTING_PLAN' => tr('Choose hosting plan'),
		'TR_PERSONALIZE_TEMPLATE' => tr('Personalise template'),
		'TR_YES' => tr('yes'),
		'TR_NO' => tr('no'),
		'TR_NEXT_STEP' => tr('Next step'),
		'TR_DMN_HELP' => tr("You do not need 'www.' i-MSCP will add it on its own.")));

generateNavigation($tpl);

if (isset($_POST['uaction']) && $_POST['uaction'] == 'user_add_nxt') {
    if (!check_user_data()) {
        get_data_au1_page($tpl);
    }
} else {
    get_empty_au1_page($tpl);
}

get_hp_data_list($tpl, $_SESSION['user_id']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
