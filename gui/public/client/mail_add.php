<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by i-MSCP Team
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

use iMSCP\Crypt as Crypt;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get domains list
 *
 * @return array Domains list
 */
function _client_getDomainsList()
{
    static $domainsList = NULL;

    if (NULL === $domainsList) {
        $mainDmnProps = get_domain_default_props($_SESSION['user_id']);
        $domainsList = array(array(
            'name' => $mainDmnProps['domain_name'],
            'id'   => $mainDmnProps['domain_id'],
            'type' => 'dmn'
        ));
        $stmt = exec_query(
            "
              SELECT CONCAT(t1.subdomain_name, '.', t2.domain_name) AS name, t1.subdomain_id AS id,'sub' AS type
              FROM subdomain AS t1
              INNER JOIN domain AS t2 USING(domain_id)
              WHERE t1.domain_id = :domain_id
              AND t1.subdomain_status = :status_ok
              UNION
              SELECT alias_name AS name, alias_id AS id, 'als' AS type
              FROM domain_aliasses
              WHERE domain_id = :domain_id
              AND alias_status = :status_ok
              UNION
              SELECT CONCAT(t1.subdomain_alias_name, '.', t2.alias_name) AS name, t1.subdomain_alias_id AS id,
                'alssub' AS type
              FROM subdomain_alias AS t1
              INNER JOIN domain_aliasses AS t2 USING(alias_id)
              WHERE t2.domain_id = :domain_id
              AND subdomain_alias_status = :status_ok
          ",
            array('domain_id' => $mainDmnProps['domain_id'], 'status_ok' => 'ok')
        );
        if ($stmt->rowCount()) {
            $domainsList = array_merge($domainsList, $stmt->fetchAll(PDO::FETCH_ASSOC));
            usort($domainsList, function ($a, $b) {
                return strnatcmp(decode_idna($a['name']), decode_idna($b['name']));
            });
        }
    }

    return $domainsList;
}

/**
 * Add mail account
 *
 * @return bool TRUE on success, FALSE otherwise
 */
function client_addMailAccount()
{
    if (!isset($_POST['username'])
        || !isset($_POST['domain_name'])
        || !isset($_POST['password'])
        || !isset($_POST['password_rep'])
        || !isset($_POST['quota'])
        || !isset($_POST['forward_list'])
        || !isset($_POST['account_type'])
        || !in_array($_POST['account_type'], array('1', '2', '3'), true)
    ) {
        showBadRequestErrorPage();
    }

    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);
    $password = $forwardList = '_no_';
    $mailType = $subId = '';
    $mailTypeNormal = in_array($_POST['account_type'], array('1', '3'));
    $mailTypeForward = in_array($_POST['account_type'], array('2', '3'));
    $mailQuota = NULL;

    // Check for username
    $username = mb_strtolower(clean_input($_POST['username']));

    if ($_POST['username'] == '' || !chk_email($username, true)) {
        set_page_message(tr('Invalid email username.'), 'error');
        return false;
    }

    // Check for domain existence and owner
    $domainName = mb_strtolower(clean_input($_POST['domain_name']));
    $domainType = NULL;
    $domainId = NULL;

    foreach (_client_getDomainsList() as $domain) {
        if ($domain['name'] == $domainName) {
            $domainType = $domain['type'];
            $domainId = $domain['id'];
            $subId = ($domainType != 'dmn') ? $domainId : '0';
        }
    }

    if (NULL === $domainType) {
        showBadRequestErrorPage();
    }

    $mailAddr = $username . '@' . $domainName;

    if ($mailTypeNormal) {
        // Check for password
        $password = clean_input($_POST['password']);
        $passwordRep = clean_input($_POST['password_rep']);

        if ($password == '') {
            set_page_message(tr('Password is missing.'), 'error');
            return false;
        }

        if ($passwordRep == '') {
            set_page_message(tr('You must confirm your password.'), 'error');
            return false;
        }

        if ($password !== $passwordRep) {
            set_page_message(tr('Passwords do not match.'), 'error');
            return false;
        }

        if (!checkPasswordSyntax($password)) {
            return false;
        }

        // Check for quota

        $mailQuota = intval($_POST['quota']) * 1048576; // MiB to Bytes

        if ($mainDmnProps['mail_quota'] != 0) {
            if ($mailQuota == 0) {
                set_page_message(tr('Incorrect email quota.'), 'error');
                return false;
            }

            $stmt = exec_query(
                'SELECT COALESCE(SUM(quota), 0) AS quota FROM mail_users WHERE domain_id = ? AND quota IS NOT NULL', $mainDmnProps['domain_id']
            );

            $mailQuotaSumBytes = $stmt->fetchRow(PDO::FETCH_COLUMN);

            if ($mailQuotaSumBytes >= $mainDmnProps['mail_quota']) {
                set_page_message(tr('You cannot add new email account. You have already assigned all your email quota to other mailboxes. Please first, review your quota assignments.'), 'warning');
                redirectTo('mail_accounts.php');
            }

            $mailQuotaLimitBytes = floor($mainDmnProps['mail_quota'] - $mailQuotaSumBytes);
            if ($mailQuota > $mailQuotaLimitBytes) {
                set_page_message(tr('Email quota cannot be bigger than %s', bytesHuman($mailQuotaLimitBytes, null, 0)), 'error');
                return false;
            }
        }

        switch ($domainType) {
            case 'dmn':
                $mailType = MT_NORMAL_MAIL;
                break;
            case 'sub':
                $mailType = MT_SUBDOM_MAIL;
                break;
            case 'als':
                $mailType = MT_ALIAS_MAIL;
                break;
            case 'alssub':
                $mailType = MT_ALSSUB_MAIL;
        }

        $password = Crypt::sha512($password);
    }

    if ($mailTypeForward) {
        // Check forward list
        $forwardList = clean_input($_POST['forward_list']);
        if ($forwardList == '') {
            set_page_message(tr('Forward list is empty.'), 'error');
            return false;
        }

        $forwardList = preg_split("/[\n,]+/", $forwardList);

        foreach ($forwardList as $key => &$forwardEmailAddr) {
            $forwardEmailAddr = encode_idna(mb_strtolower(trim($forwardEmailAddr)));
            if ($forwardEmailAddr == '') {
                unset($forwardList[$key]);
            } elseif (!chk_email($forwardEmailAddr)) {
                set_page_message(tr('Wrong mail syntax in forward list.'), 'error');
                return false;
            } elseif ($forwardEmailAddr == $mailAddr) {
                set_page_message(tr('You cannot forward %s on itself.', $mailAddr), 'error');
                return false;
            }
        }

        $forwardList = implode(',', array_unique($forwardList));
        switch ($domainType) {
            case 'dmn':
                $mailType .= (($mailType != '') ? ',' : '') . MT_NORMAL_FORWARD;
                break;
            case 'sub':
                $mailType .= (($mailType != '') ? ',' : '') . MT_SUBDOM_FORWARD;
                break;
            case 'als':
                $mailType .= (($mailType != '') ? ',' : '') . MT_ALIAS_FORWARD;
                break;
            case 'alssub':
                $mailType .= (($mailType != '') ? ',' : '') . MT_ALSSUB_FORWARD;
        }
    }

    try {
        /** @var $db iMSCP_Database */
        $db = iMSCP_Registry::get('db');

        iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeAddMail, array(
            'mailUsername' => $username,
            'MailAddress'  => $mailAddr
        ));
        exec_query(
            '
              INSERT INTO mail_users (
                mail_acc, mail_pass, mail_forward, domain_id, mail_type, sub_id, status, mail_auto_respond,
                mail_auto_respond_text, quota, mail_addr
              ) VALUES(
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
              )
            ',
            array($username, $password, $forwardList, $mainDmnProps['domain_id'], $mailType, $subId, 'toadd', '0', NULL,
                $mailQuota, $mailAddr
            )
        );
        iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterAddMail, array(
            'mailUsername' => $username,
            'mailAddress'  => $mailAddr,
            'mailId'       => $db->insertId()
        ));
        send_request();
        write_log("{$_SESSION['user_logged']}: added new Email account: $mailAddr", E_USER_NOTICE);
        set_page_message(tr('Email account successfully scheduled for addition.'), 'success');
    } catch (iMSCP_Exception_Database $e) {
        if ($e->getCode() == 23000) {
            set_page_message(tr('Email account already exists.'), 'error');
            return false;
        }
    }

    return true;
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 */
function client_generatePage($tpl)
{
    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);
    $stmt = exec_query(
        'SELECT COALESCE(SUM(quota), 0) AS quota FROM mail_users WHERE domain_id = ? AND quota IS NOT NULL', $mainDmnProps['domain_id']
    );

    $mailQuotaSumBytes = $stmt->fetchRow(PDO::FETCH_COLUMN);

    if ($mainDmnProps['mail_quota'] != '0' && $mailQuotaSumBytes >= $mainDmnProps['mail_quota']) {
        set_page_message(tr('You cannot add new email account. You have already assigned all your email quota to other mailboxes. Please first, review your quota assignments.'), 'warning');
        redirectTo('mail_accounts.php');
    }

    if ($mainDmnProps['mail_quota'] == 0) {
        $tpl->assign(array(
            'TR_QUOTA'  => tohtml(tr('Quota in MiB (0 for unlimited)')),
            'MIN_QUOTA' => 1,
            'MAX_QUOTA' => tohtml(floor(PHP_INT_MAX / 1048576), 'htmlAttr'),
            'QUOTA'     => isset($_POST['quota']) ? tohtml($_POST['quota'], 'htmlAttr') : 10
        ));
    } else {
        $mailQuotaLimitBytes = $mainDmnProps['mail_quota'] - $mailQuotaSumBytes;
        $mailQuotaLimitMiB = floor($mailQuotaLimitBytes / 1048576);
        $tpl->assign(array(
            'TR_QUOTA'  => tohtml(tr('Quota in MiB (Max: %s)', bytesHuman($mailQuotaLimitBytes, NULL, 0))),
            'MIN_QUOTA' => 1,
            'MAX_QUOTA' => tohtml($mailQuotaLimitMiB, 'htmlAttr'),
            'QUOTA'     => isset($_POST['quota'])
                ? tohtml($_POST['quota'], 'htmlAttr')
                : tohtml(($mailQuotaLimitMiB >= 10) ? 10 : $mailQuotaLimitMiB, 'htmlAttr')
        ));
    }

    $mailType = (isset($_POST['account_type']) && in_array($_POST['account_type'], array('1', '2', '3')))
        ? $_POST['account_type'] : '1';

    $tpl->assign(array(
        'USERNAME'               => isset($_POST['username']) ? tohtml($_POST['username']) : '',
        'NORMAL_CHECKED'         => ($mailType == '1') ? ' checked' : '',
        'FORWARD_CHECKED'        => ($mailType == '2') ? ' checked' : '',
        'NORMAL_FORWARD_CHECKED' => ($mailType == '3') ? ' checked' : '',
        'FORWARD_LIST'           => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : '',
    ));

    foreach (_client_getDomainsList() as $domain) {
        $tpl->assign(array(
            'DOMAIN_NAME'          => tohtml($domain['name']),
            'DOMAIN_NAME_UNICODE'  => tohtml(decode_idna($domain['name'])),
            'DOMAIN_NAME_SELECTED' => (isset($_POST['domain_name']) && $_POST['domain_name'] == $domain['name'])
                ? ' selected' : '',
        ));
        $tpl->parse('DOMAIN_NAME_ITEM', '.domain_name_item');
    }
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');
customerHasFeature('mail') or showBadRequestErrorPage();

$dmnProps = get_domain_default_props($_SESSION['user_id']);
$emailAccountsLimit = $dmnProps['domain_mailacc_limit'];

if ($emailAccountsLimit != '0') {
    list($nbEmailAccounts) = get_domain_running_mail_acc_cnt($dmnProps['domain_id']);
    if ($nbEmailAccounts >= $emailAccountsLimit) {
        set_page_message(tr('You have reached the maximum number of email accounts allowed by your subscription.'), 'warning');
        redirectTo('mail_accounts.php');
    }
}

if (!empty($_POST)) {
    if (client_addMailAccount()) {
        redirectTo('mail_accounts.php');
    }
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout'           => 'shared/layouts/ui.tpl',
    'page'             => 'client/mail_add.tpl',
    'page_message'     => 'layout',
    'domain_name_item'     => 'page',
));
$tpl->assign(array(
    'TR_PAGE_TITLE'          => tr('Client / Email / Add Email Account'),
    'TR_MAIl_ACCOUNT_DATA'   => tr('Email account data'),
    'TR_USERNAME'            => tr('Username'),
    'TR_DOMAIN_NAME'         => tr('Domain name'),
    'TR_MAIL_ACCOUNT_TYPE'   => tr('Mail account type'),
    'TR_NORMAL_MAIL'         => tr('Normal'),
    'TR_FORWARD_MAIL'        => tr('Forward'),
    'TR_FORWARD_NORMAL_MAIL' => tr('Normal + Forward'),
    'TR_PASSWORD'            => tr('Password'),
    'TR_PASSWORD_REPEAT'     => tr('Password confirmation'),
    'TR_FORWARD_TO'          => tr('Forward to'),
    'TR_FWD_HELP'            => tr('Separate multiple email addresses by comma or a line-break.'),
    'TR_ADD'                 => tr('Add'),
    'TR_CANCEL'              => tr('Cancel')
));

client_generatePage($tpl);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
