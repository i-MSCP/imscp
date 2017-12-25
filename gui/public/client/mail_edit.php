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

use iMSCP\Crypt as Crypt;
use iMSCP_Registry as Registry;
use iMSCP\TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get mail account data
 *
 * @param int $mailId Mail account unique identifier
 * @return array mail account data
 */
function client_getEmailAccountData($mailId)
{
    static $mailData = NULL;

    if (NULL !== $mailData) {
        return $mailData;
    }

    $stmt = exec_query('SELECT * FROM mail_users WHERE mail_id = ? AND domain_id = ?', [
        $mailId, get_user_domain_id($_SESSION['user_id'])
    ]);

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    return $stmt->fetch();
}

/**
 * Edit mail account
 *
 * @throws iMSCP_Exception
 * @return bool TRUE on success, FALSE otherwise
 */
function client_editMailAccount()
{
    if (!isset($_POST['password'])
        || !isset($_POST['password_rep'])
        || !isset($_POST['quota'])
        || !isset($_POST['forward_list'])
        || !isset($_POST['account_type'])
        || !in_array($_POST['account_type'], ['1', '2', '3'], true)
    ) {
        showBadRequestErrorPage();
    }

    $mailData = client_getEmailAccountData(clean_input($_GET['id']));
    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);
    $password = $forwardList = '_no_';
    $mailType = '';
    $mailQuotaLimitBytes = NULL;

    if (!preg_match('/^(.*?)_(?:mail|forward)/', $mailData['mail_type'], $match)) {
        throw new iMSCP_Exception('Could not determine mail type');
    }

    $domainType = $match[1];
    $mailTypeNormal = in_array($_POST['account_type'], ['1', '3']);
    $mailTypeForward = in_array($_POST['account_type'], ['2', '3']);

    if (!$mailTypeNormal && !$mailTypeForward) {
        showBadRequestErrorPage();
    }

    if (Registry::get('config')['SERVER_HOSTNAME'] == explode('@', $mailData['mail_addr'])[1]
        && $mailTypeNormal
    ) {
        # SERVER_HOSTNAME is a canonical domain (local domain) which cannot be
        # listed in both `mydestination' and `virtual_mailbox_domains' Postfix
        # parameters. See http://www.postfix.org/VIRTUAL_README.html#canonical
        # This necessarily means that Postfix canonical domains cannot have
        # virtual mailboxes, hence their prohibition.
        set_page_message(tr('You cannot create new mailboxes for that domain. Only forwarded mail accounts are allowed.'), 'warning');
        return false;
    }

    $mailAddr = $mailData['mail_addr'];

    if ($mailTypeNormal) {
        $password = clean_input($_POST['password']);
        $passwordRep = clean_input($_POST['password_rep']);

        if ($mailData['mail_pass'] == '_no_' || $password != '' || $passwordRep != '') {
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

            $password = Crypt::sha512($password);
        } else {
            $password = $mailData['mail_pass'];
        }

        // Check for quota

        $customerEmailQuotaLimitBytes = filter_digits($mainDmnProps['mail_quota'], 0);
        $mailQuotaLimitBytes = filter_digits($_POST['quota']) * 1048576; // MiB to Bytes

        if ($customerEmailQuotaLimitBytes > 0) {
            if ($mailQuotaLimitBytes < 1) {
                set_page_message(tr('Incorrect mail quota.'), 'error');
                return false;
            }

            $customerMailboxesQuotaSumBytes = exec_query(
                'SELECT IFNULL(SUM(quota), 0) FROM mail_users WHERE mail_id <> ? AND domain_id = ?',
                [$mailData['mail_id'], $mainDmnProps['domain_id']]
            )->fetchColumn();

            if ($customerMailboxesQuotaSumBytes >= $customerEmailQuotaLimitBytes) {
                showBadRequestErrorPage(); # Customer should never goes here excepted if it try to bypass js code
            }

            if ($mailQuotaLimitBytes > $customerEmailQuotaLimitBytes - $customerMailboxesQuotaSumBytes) {
                set_page_message(tr('Mail quota cannot be bigger than %s', bytesHuman($mailQuotaLimitBytes)), 'error');
                return false;
            }
        }

        switch ($domainType) {
            case 'normal':
                $mailType = MT_NORMAL_MAIL;
                break;
            case 'subdom':
                $mailType = MT_SUBDOM_MAIL;
                break;
            case 'alias':
                $mailType = MT_ALIAS_MAIL;
                break;
            case 'alssub':
                $mailType = MT_ALSSUB_MAIL;
        }
    }

    if ($mailTypeForward) {
        $forwardList = clean_input($_POST['forward_list']);

        if ($forwardList == '') {
            set_page_message(tr('Forward list is empty.'), 'error');
            return false;
        }

        $forwardList = array_unique(preg_split('/\s|,/', $forwardList, -1, PREG_SPLIT_NO_EMPTY));

        foreach ($forwardList as $key => &$forwardEmailAddr) {
            $forwardEmailAddr = encode_idna(mb_strtolower($forwardEmailAddr));

            if (!chk_email($forwardEmailAddr)) {
                set_page_message(tr('Bad email address in forward list field.'), 'error');
                return false;
            }

            if ($forwardEmailAddr == $mailAddr) {
                set_page_message(tr('You cannot forward %s on itself.', $mailAddr), 'error');
                return false;
            }
        }

        if (empty($forwardList)) {
            set_page_message(tr('Forward list is empty.'), 'error');
            return false;
        }

        $forwardList = implode(',', $forwardList);

        switch ($domainType) {
            case 'normal':
                $mailType .= (($mailType != '') ? ',' : '') . MT_NORMAL_FORWARD;
                break;
            case 'subdom':
                $mailType .= (($mailType != '') ? ',' : '') . MT_SUBDOM_FORWARD;
                break;
            case 'alias':
                $mailType .= (($mailType != '') ? ',' : '') . MT_ALIAS_FORWARD;
                break;
            case 'alssub':
                $mailType .= (($mailType != '') ? ',' : '') . MT_ALSSUB_FORWARD;
        }
    }

    Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onBeforeEditMail, [
        'mailId' => $mailData['mail_id']
    ]);
    exec_query(
        '
                UPDATE mail_users
                SET mail_pass = ?, mail_forward = ?, mail_type = ?, status = ?, po_active = ?, quota = ?
                WHERE mail_id = ?
            ',
        [
            $password, $forwardList, $mailType, 'tochange', $mailTypeNormal ? 'yes' : 'no', $mailQuotaLimitBytes,
            $mailData['mail_id']
        ]
    );

    # Force synching of quota info on next load (or remove cached data in case of normal account changed to forward account)
    $postfixConfig = new iMSCP_Config_Handler_File(
        utils_normalizePath(Registry::get('config')['CONF_DIR'] . '/postfix/postfix.data')
    );
    list($user, $domain) = explode('@', $mailAddr);
    unset($_SESSION['maildirsize'][utils_normalizePath($postfixConfig['MTA_VIRTUAL_MAIL_DIR'] . "/$domain/$user/maildirsize")]);

    Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAfterEditMail, [
        'mailId' => $mailData['mail_id']
    ]);
    send_request();
    write_log(
        sprintf(
            'A mail account (%s) has been edited by %s', decode_idna($mailAddr), $_SESSION['user_logged']
        ),
        E_USER_NOTICE
    );
    set_page_message(tr('Mail account successfully scheduled for update.'), 'success');
    return true;
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 */
function client_generatePage($tpl)
{
    $mailId = clean_input($_GET['id']);
    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);
    $mailData = client_getEmailAccountData($mailId);
    list($username, $domainName) = explode('@', $mailData['mail_addr']);

    $customerMailboxesQuotaSumBytes = exec_query(
        'SELECT IFNULL(SUM(quota), 0) FROM mail_users WHERE mail_id <> ? AND domain_id = ?',
        [$mailId, $mainDmnProps['domain_id']]
    )->fetchColumn();

    $customerEmailQuotaLimitBytes = filter_digits($mainDmnProps['mail_quota'], 0);

    if ($customerEmailQuotaLimitBytes < 1) {
        $tpl->assign([
            'TR_QUOTA'  => tohtml(tr('Quota in MiB (0 ∞)')),
            'MIN_QUOTA' => 0,
            'MAX_QUOTA' => tohtml(17592186044416, 'htmlAttr'), // Max quota = MySQL UNSIGNED BIGINT in MiB
            'QUOTA'     => isset($_POST['quota'])
                ? tohtml(filter_digits($_POST['quota']), 'htmlAttr')
                : tohtml($mailData['quota'] / 1048576, 'htmlAttr') // Bytes to MiB conversion
        ]);
        $mailTypeForwardOnly = false;
    } else {
        if ($customerEmailQuotaLimitBytes > $customerMailboxesQuotaSumBytes) {
            $mailQuotaLimitBytes = $customerEmailQuotaLimitBytes - $customerMailboxesQuotaSumBytes;
            $mailMaxQuotaLimitMib = $mailQuotaLimitBytes / 1048576;
            $mailQuotaLimitMiB = ($mailData['quota'] > 0 && $mailData['quota'] < $mailQuotaLimitBytes)
                ? $mailData['quota'] / 1048576
                : min(10, $mailMaxQuotaLimitMib);
            $mailTypeForwardOnly = false;
        } else {
            set_page_message(tr('You cannot make this account a normal mail account because you have already assigned all your mail quota. If you want make this account a normal mail account, you must first lower the quota assigned to one of your other mail account.'), 'static_info');
            set_page_message(tr('For the time being, you can only edit your forwarded mail account.'), 'static_info');
            $mailQuotaLimitBytes = 1048576; // Only for sanity. Customer won't be able to switch to normal mail account
            $mailMaxQuotaLimitMib = 1;
            $mailQuotaLimitMiB = 1;
            $mailTypeForwardOnly = true;
        }

        $tpl->assign([
            'TR_QUOTA'  => tohtml(tr('Quota in MiB (Max: %s)', bytesHuman($mailQuotaLimitBytes))),
            'MIN_QUOTA' => 1,
            'MAX_QUOTA' => tohtml($mailMaxQuotaLimitMib, 'htmlAttr'),
            'QUOTA'     => isset($_POST['quota'])
                ? tohtml(filter_digits($_POST['quota']), 'htmlAttr') : tohtml($mailQuotaLimitMiB, 'htmlAttr')
        ]);
    }

    $mailType = '';

    if (!isset($_POST['account_type'])
        || !in_array($_POST['account_type'], ['1', '2', '3'])
    ) {
        if (preg_match('/_mail/', $mailData['mail_type'])) {
            $mailType = '1';
        }

        if (preg_match('/_forward/', $mailData['mail_type'])) {
            $mailType = ($mailType == '1') ? '3' : '2';
        }
    } else {
        $mailType = $_POST['account_type'];
    }

    $tpl->assign([
        'MAIL_ID'                => tohtml($mailId),
        'USERNAME'               => tohtml($username),
        'NORMAL_CHECKED'         => ($mailType == '1') ? ' checked' : '',
        'FORWARD_CHECKED'        => ($mailType == '2') ? ' checked' : '',
        'NORMAL_FORWARD_CHECKED' => ($mailType == '3') ? ' checked' : '',
        'PASSWORD'               => isset($_POST['password']) ? tohtml($_POST['password']) : '',
        'PASSWORD_REP'           => isset($_POST['password_rep']) ? tohtml($_POST['password_rep']) : '',
        'FORWARD_LIST'           => isset($_POST['forward_list'])
            ? tohtml($_POST['forward_list'])
            : ($mailData['mail_forward'] != '_no_' ? tohtml($mailData['mail_forward']) : ''),
        'DOMAIN_NAME'            => tohtml($domainName),
        'DOMAIN_NAME_UNICODE'    => tohtml(decode_idna($domainName)),
        'DOMAIN_NAME_SELECTED'   => ' selected'
    ]);

    Registry::get('iMSCP_Application')->getEventsManager()->registerListener(
        'onGetJsTranslations',
        function ($event) use ($mailTypeForwardOnly) {
            /** @var $event iMSCP_Events_Description */
            $event->getParam('translations')->core['mail_add_forward_only'] = $mailTypeForwardOnly;
        }
    );
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptStart);

if (!customerHasFeature('mail') ||
    !isset($_GET['id'])
) {
    showBadRequestErrorPage();
}

if (!empty($_POST) && client_editMailAccount()) {
    redirectTo('mail_accounts.php');
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'client/mail_edit.tpl',
    'page_message' => 'layout'
]);
$tpl->assign([
    'TR_PAGE_TITLE'          => tr('Client / Mail / Edit Mail Account'),
    'TR_MAIl_ACCOUNT_DATA'   => tr('Mail account data'),
    'TR_USERNAME'            => tr('Username'),
    'TR_DOMAIN_NAME'         => tr('Domain name'),
    'TR_MAIL_ACCOUNT_TYPE'   => tr('Mail account type'),
    'TR_NORMAL_MAIL'         => tr('Normal'),
    'TR_FORWARD_MAIL'        => tr('Forward'),
    'TR_FORWARD_NORMAL_MAIL' => tr('Normal + Forward'),
    'TR_PASSWORD'            => tr('Password'),
    'TR_PASSWORD_REPEAT'     => tr('Password confirmation'),
    'TR_FORWARD_TO'          => tr('Forward to'),
    'TR_FWD_HELP'            => tr('Separate addresses by a comma, line-break or space.'),
    'TR_UPDATE'              => tr('Update'),
    'TR_CANCEL'              => tr('Cancel')
]);

client_generatePage($tpl);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
