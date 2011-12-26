<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010 by i-MSCP | http://i-mscp.net
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

require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
} else if (isset($_POST['edit_id'])) {
    $edit_id = $_POST['edit_id'];
} else {
    redirectTo('manage_users.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/admin_edit.tpl',
		'page_message' => 'page',
		'hosting_plans' => 'page'));

$tpl->assign(
	array(
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

/**
 *
 * @return void
 */
function update_data()
{
    global $edit_id;

    $cfg = iMSCP_Registry::get('config');

    if (isset($_POST['Submit']) && isset($_POST['uaction']) &&
        $_POST['uaction'] === 'edit_user'
    ) {
        if (check_user_data()) {
            $user_id = $_SESSION['user_id'];
            $fname = clean_input($_POST['fname']);
            $lname = clean_input($_POST['lname']);
            $firm = clean_input($_POST['firm']);
            $gender = clean_input($_POST['gender']);
            $zip = clean_input($_POST['zip']);
            $city = clean_input($_POST['city']);
            $state = clean_input($_POST['state']);
            $country = clean_input($_POST['country']);
            $email = clean_input($_POST['email']);
            $phone = clean_input($_POST['phone']);
            $fax = clean_input($_POST['fax']);
            $street1 = clean_input($_POST['street1']);
            $street2 = clean_input($_POST['street2']);

            if (empty($_POST['pass'])) {
                $query = "
					UPDATE
					    `admin`
					SET
						`fname` = ?, `lname` = ?, `firm` = ?, `zip` = ?, `city` = ?,
						`state` = ?, `country` = ?, `email` = ?, `phone` = ?,
						`fax` = ?, `street1` = ?, `street2` = ?, `gender` = ?
					WHERE
						`admin_id` = ?
				";
                exec_query($query, array($fname, $lname, $firm, $zip, $city, $state,
                                              $country, $email, $phone, $fax, $street1,
                                              $street2, $gender, $edit_id));
            } else {
                $edit_id = $_POST['edit_id'];

                if ($_POST['pass'] != $_POST['pass_rep']) {
                    set_page_message(tr("Entered passwords do not match!"), 'error');

                    redirectTo('admin_edit.php?edit_id=' . $edit_id);
                }

                if (!chk_password($_POST['pass'])) {
                    if ($cfg->PASSWD_STRONG) {
                        set_page_message(sprintf(tr('The password must be at least %s long and contain letters and numbers to be valid.'),
                                                 $cfg->PASSWD_CHARS), 'error');
                    } else {
                        set_page_message(sprintf(tr('Password data is shorter than %s signs or includes not permitted signs.'),
                                                 $cfg->PASSWD_CHARS), 'error');
                    }

                    redirectTo('admin_edit.php?edit_id=' . $edit_id);
                }

                $upass = crypt_user_pass($_POST['pass']);

                $query = "
					UPDATE
						`admin`
					SET
	                    `admin_pass` = ?, `fname` = ?, `lname` = ?, `firm` = ?,
						`zip` = ?, `city` = ?, `state` = ?, `country` = ?, `email` = ?,
						`phone` = ?, `fax` = ?, `street1` = ?, `street2` = ?, `gender` = ?
					WHERE
						`admin_id` = ?
				";
                exec_query($query, array($upass, $fname, $lname, $firm, $zip, $city,
                                        $state, $country, $email, $phone, $fax,
                                        $street1, $street2, $gender, $edit_id));

                // Kill any existing session of the edited user

                $admin_name = get_user_name($edit_id);
                $query = "DELETE FROM `login` WHERE `user_name` = ?";

                $rs = exec_query($query, $admin_name);
                if ($rs->recordCount() != 0) {
                    set_page_message(tr('User session was killed!'));
                    write_log($_SESSION['user_logged'] . " killed " . $admin_name . "'s session because of password change", E_USER_WARNING);
                }
            }

            $edit_username = clean_input($_POST['edit_username']);
            $user_logged = $_SESSION['user_logged'];
            write_log("$user_logged: changes data/password for $edit_username!", E_USER_NOTICE);

            if (isset($_POST['send_data']) && !empty($_POST['pass'])) {
                $query = "SELECT admin_type FROM admin WHERE admin_id='" . addslashes(htmlspecialchars($edit_id)) . "'";

                $res = execute_query($query);

                if ($res->fields['admin_type'] == 'admin') {
                    $admin_type = tr('Administrator');
                } else if ($res->fields['admin_type'] == 'reseller') {
                    $admin_type = tr('Reseller');
                } else {
                    $admin_type = tr('Domain account');
                }

                send_add_user_auto_msg($user_id, $edit_username,
                                       clean_input($_POST['pass']),
                                       clean_input($_POST['email']),
                                       clean_input($_POST['fname']),
                                       clean_input($_POST['lname']),
                                       tr($admin_type),
                                       $gender);
            }

            $_SESSION['user_updated'] = 1;
            redirectTo('manage_users.php');
        }
    }
}

/**
 * @return bool
 */
function check_user_data()
{
    if (!chk_email($_POST['email'])) {
        set_page_message(tr("Incorrect email length or syntax!"), 'error');

        return false;
    }

    return true;
}

if ($edit_id == $_SESSION['user_id']) {
    redirectTo('personal_change.php');
}

$query = "
	SELECT
		`admin_name`, `admin_type`, `fname`, `lname`, `firm`, `zip`, `city`, `state`,
		`country`, `phone`, `fax`, `street1`, `street2`, `email`, `gender`
	FROM
		`admin`
	WHERE
		`admin_id` = ?
";

$rs = exec_query($query, $edit_id);

if ($rs->recordCount() <= 0) {
    redirectTo('manage_users.php');
}

generateNavigation($tpl);
update_data();

$admin_name = decode_idna($rs->fields['admin_name']);

if (isset($_POST['genpass'])) {
    $tpl->assign('VAL_PASSWORD', passgen());
} else {
    $tpl->assign('VAL_PASSWORD', '');
}

$tpl->assign(array(
                  'TR_PAGE_TITLE' => ($rs->fields['admin_type'] == 'admin'
                      ? tr('i-MSCP - Admin/Manage users/Edit Administrator')
                      : tr('i-MSCP - Admin/Manage users/Edit User')),
                  'TR_EMPTY_OR_WORNG_DATA' => tr('Empty data or wrong field!'),
                  'TR_PASSWORD_NOT_MATCH' => tr("Passwords don't match!"),
                  'TR_EDIT_ADMIN' => ($rs->fields['admin_type'] == 'admin'
                      ? tr('Edit admin')
                      : tr('Edit user')),
                  'TR_CORE_DATA' => tr('Core data'),
                  'TR_USERNAME' => tr('Username'),
                  'TR_PASSWORD' => tr('Password'),
                  'TR_PASSWORD_REPEAT' => tr('Repeat password'),
                  'TR_EMAIL' => tr('Email'),
                  'TR_ADDITIONAL_DATA' => tr('Additional data'),
                  'TR_FIRST_NAME' => tr('First name'),
                  'TR_LAST_NAME' => tr('Last name'),
                  'TR_COMPANY' => tr('Company'),
                  'TR_ZIP_POSTAL_CODE' => tr('Zip/Postal code'),
                  'TR_CITY' => tr('City'),
                  'TR_STATE_PROVINCE' => tr('State/Province'),
                  'TR_COUNTRY' => tr('Country'),
                  'TR_STREET_1' => tr('Street 1'),
                  'TR_STREET_2' => tr('Street 2'),
                  'TR_PHONE' => tr('Phone'),
                  'TR_FAX' => tr('Fax'),
                  'TR_PHONE' => tr('Phone'),
                  'TR_GENDER' => tr('Gender'),
                  'TR_MALE' => tr('Male'),
                  'TR_FEMALE' => tr('Female'),
                  'TR_UNKNOWN' => tr('Unknown'),
                  'TR_UPDATE' => tr('Update'),
                  'TR_SEND_DATA' => tr('Send new login data'),
                  'TR_PASSWORD_GENERATE' => tr('Generate password'),
                  'FIRST_NAME' => empty($rs->fields['fname']) ? ''
                      : tohtml($rs->fields['fname']),
                  'LAST_NAME' => empty($rs->fields['lname']) ? ''
                      : tohtml($rs->fields['lname']),
                  'FIRM' => empty($rs->fields['firm']) ? ''
                      : tohtml($rs->fields['firm']),
                  'ZIP' => empty($rs->fields['zip']) ? ''
                      : tohtml($rs->fields['zip']),
                  'CITY' => empty($rs->fields['city']) ? ''
                      : tohtml($rs->fields['city']),
                  'STATE_PROVINCE' => empty($rs->fields['state']) ? ''
                      : tohtml($rs->fields['state']),
                  'COUNTRY' => empty($rs->fields['country']) ? ''
                      : tohtml($rs->fields['country']),
                  'STREET_1' => empty($rs->fields['street1']) ? ''
                      : tohtml($rs->fields['street1']),
                  'STREET_2' => empty($rs->fields['street2']) ? ''
                      : tohtml($rs->fields['street2']),
                  'PHONE' => empty($rs->fields['phone']) ? ''
                      : tohtml($rs->fields['phone']),
                  'FAX' => empty($rs->fields['fax']) ? ''
                      : tohtml($rs->fields['fax']),
                  'USERNAME' => tohtml($admin_name),
                  'EMAIL' => tohtml($rs->fields['email']),
                  'VL_MALE' => (($rs->fields['gender'] === 'M') ? $cfg->HTML_SELECTED
                      : ''),
                  'VL_FEMALE' => (($rs->fields['gender'] === 'F')
                      ? $cfg->HTML_SELECTED : ''),
                  'VL_UNKNOWN' => ((($rs->fields['gender'] === 'U') || (empty($rs->fields['gender'])))
                      ? $cfg->HTML_SELECTED : ''),
                  'EDIT_ID' => $edit_id,
				  'USER_ICON_COLOR' => ($rs->fields['admin_type'] == 'admin') ? 'yellow' : 'blue',
                  // The entries below are for Demo versions only
                  'PASSWORD_DISABLED' => tr('Password change is disabled!'),
                  'DEMO_VERSION' => tr('Demo Version!')));


generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
