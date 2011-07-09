<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2011 by i-msCP | http://i-mscp.net
 * @version     SVN: $Id$
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
 * Validate activation parameters.
 *
 * @param int $order_id ID in table orders
 * @param string $key hash value to compare with
 * @return boolean true - validation correct
 */
function validate_order_key($order_id, $key)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $result = false;

    $query = "SELECT * FROM `orders` WHERE `id` = ? AND `status` = ?";
    $stmt = exec_query($query, array($order_id, 'unconfirmed'));

    if ($stmt->recordCount() == 1) {
        $domain_name = $stmt->fields['domain_name'];
        $admin_id = $stmt->fields['user_id'];
        $coid = isset($cfg->CUSTOM_ORDERPANEL_ID) ? $cfg->CUSTOM_ORDERPANEL_ID : '';
        $ckey = sha1($order_id . '-' . $domain_name . '-' . $admin_id . '-' . $coid);

        if ($ckey == $key) {
            $result = true;
        }
    }

    return $result;
}

/**
 * Set order to confirmed so that reseller can activate this.
 *
 * @param int $order_id Order unique identifier
 */
function confirm_order($order_id)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $query = "SELECT * FROM `orders` WHERE `id` = ?";
    $stmt = exec_query($query, $order_id);

    if ($stmt->recordCount() == 1) {
        $query = "UPDATE `orders` SET `status` = ? WHERE `id` = ?";
        exec_query($query, array('new', $order_id));

        $admin_id = $stmt->fields['user_id'];
        $domain_name = $stmt->fields['domain_name'];
        $ufname = $stmt->fields['fname'];
        $ulname = $stmt->fields['lname'];
        $uemail = $stmt->fields['email'];
        $name = trim($ufname . ' ' . $ulname);

        $data = get_order_email($admin_id);

        $from_name = $data['sender_name'];
        $from_email = $data['sender_email'];

        $search [] = '{DOMAIN}';
        $replace[] = $domain_name;
        $search [] = '{MAIL}';
        $replace[] = $uemail;
        $search [] = '{NAME}';
        $replace[] = $name;

        if ($from_name) {
            $from = '"' . encode($from_name) . "\" <" . $from_email . ">";
        } else {
            $from = $from_email;
        }

        // let's send mail to the reseller => new order
        $subject = encode(tr("You have a new order"));

        $message = tr('

Dear {RESELLER},

you have received a new order from {NAME} <{MAIL}> for the domain {DOMAIN}.

Please login into your i-MSCP control panel for more details.

');

        $search [] = '{RESELLER}';
        $replace[] = $from_name;
        $message = str_replace($search, $replace, $message);
        $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

        $headers = "From: " . $from . "\n";
        $headers .= "MIME-Version: 1.0\n" . "Content-Type: text/plain; charset=utf-8\n" .
                    "Content-Transfer-Encoding: 8bit\n" . "X-Mailer: i-MSCP " .
                    $cfg->Version . " Service Mailer";

        mail($from, $subject, $message, $headers);
    }
}

/************************************************************************************
 * Main script
 */

// Include needed libraries
require '../include/imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onOrderPanelScriptStart);

$cfg = iMSCP_Registry::get('config');

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['k'])) {
    throw new iMSCP_Exception_Production(
        tr('You do not have permission to access this interface!'));
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
                          'page' => $cfg->PURCHASE_TEMPLATE_PATH . '/activate.tpl',
                          'page_message' => 'page'));

$theme_color = isset($_SESSION['user_theme'])
    ? $_SESSION['user_theme'] : $cfg->USER_INITIAL_THEME;

$tpl->assign(array(
                  'THEME_COLOR_PATH' => '../themes/' . $theme_color,
                  'THEME_CHARSET' => tr('encoding')));


if (validate_order_key($_GET['id'], $_GET['k'])) {
    confirm_order($_GET['id']);
    $msg = tr('Your order has been successfully created.');
} else {
    $msg = tr('Error creating order! Perhaps already activated?');
}

$tpl->assign(array(
                  'ORDER_STATUS_MESSAGE' => $msg,
                  'PAGE_TITLE' => tr('Order confirmation')));

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onOrderPanelScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();
