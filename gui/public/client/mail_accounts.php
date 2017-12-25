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

use iMSCP_Config_Handler_File as ConfigFile;
use iMSCP_Events as Events;
use iMSCP\TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get count of default mail accounts
 *
 * A default mail account is composed of a name matching with:
 *  - abuse, hostmaster, postmaster or webmaster for a domain
 *  - webmaster for a subdomain
 * and is set as forward mail account. If the customeer turn a default
 * mail account into a normal mail account, it is no longer seen as
 * default mail account.
 *
 * @param int $mainDmnId Main domain id
 * @return int Number of default mail accounts
 */
function countDefaultMailAccounts($mainDmnId)
{
    static $count = NULL;

    if (NULL !== $count) {
        return $count;
    }

    return $count = exec_query(
        "
            SELECT COUNT(mail_id)
            FROM mail_users
            WHERE
            (
                (
                    mail_acc IN('abuse', 'hostmaster', 'postmaster', 'webmaster')
                    AND mail_type IN('" . MT_NORMAL_FORWARD . "', '" . MT_ALIAS_FORWARD . "')
                )
                OR
                (
                    mail_acc = 'webmaster'
                    AND mail_type IN('" . MT_SUBDOM_FORWARD . "', '" . MT_ALSSUB_FORWARD . "')
                )
            )
            AND domain_id = ?
        ",
        [$mainDmnId]
    )->fetchColumn();
}

/**
 * Generate dynamic template parts for the given mail account
 *
 * @param TemplateEngine $tpl pTemplate instance
 * @param string $mailAcc Mail account
 * @param string $mailType Mail account type
 * @param string $mailStatus Mail account status
 * @param int $mailAutoResponder Flag indicating whether or not autoresponder is enabled
 * @return void
 */
function generateDynamicTplParts($tpl, $mailAcc, $mailType, $mailStatus, $mailAutoResponder)
{
    if ($mailStatus != 'ok') {
        $tpl->assign([
            'MAIL_ACCOUNT_AUTORESPONDER'              => '',
            'MAIL_ACCOUNT_ACTION_LINKS'               => tr('N/A'),
            'MAIL_ACCOUNT_DISABLED_DELETION_CHECKBOX' => ' disabled'
        ]);
        return;
    }

    if (strpos($mailType, 'catchall') !== false) {
        $tpl->assign([
            'MAIL_ACCOUNT_AUTORESPONDER'              => '',
            'MAIL_ACCOUNT_EDIT_LINK'                  => '',
            'MAIL_ACCOUNT_DELETE_LINK'                => '',
            'MAIL_PROTECTED_MAIL_ACCOUNT'             => '',
            'MAIL_ACCOUNT_DISABLED_DELETION_CHECKBOX' => ' disabled'
        ]);
        $tpl->parse('MAIL_CATCHALL_ACCOUNT_DELETE_LINK', 'mail_catchall_account_delete_link');
        $tpl->parse('MAIL_ACCOUNT_ACTION_LINKS', 'mail_account_action_links');
        return;
    }

    if (Registry::get('config')['PROTECT_DEFAULT_EMAIL_ADDRESSES']
        && (
            (in_array($mailType, [MT_NORMAL_FORWARD, MT_ALIAS_FORWARD])
                && in_array($mailAcc, ['abuse', 'hostmaster', 'postmaster', 'webmaster'])
            )
            || ($mailAcc == 'webmaster' && in_array($mailType, [MT_SUBDOM_FORWARD, MT_ALSSUB_FORWARD]))
        )
    ) {
        if ($mailAutoResponder) {
            $tpl->assign('MAIL_ACCOUNT_AUTORESPONDER_ACTIVATION_LINK', '');
            $tpl->parse('MAIL_ACCOUNT_AUTORESPONDER_DEACTIVATION_LINK', 'mail_account_autoresponder_deactivation_link');
        } else {
            $tpl->assign('MAIL_ACCOUNT_AUTORESPONDER_DEACTIVATION_LINK', '');
            $tpl->parse('MAIL_ACCOUNT_AUTORESPONDER_ACTIVATION_LINK', 'mail_account_autoresponder_activation_link');
        }

        $tpl->parse('MAIL_ACCOUNT_AUTORESPONDER_ITEM', 'mail_account_autoresponder');
        $tpl->assign([
            'MAIL_ACCOUNT_EDIT_LINK'                  => '',
            'MAIL_ACCOUNT_DELETE_LINK'                => '',
            'MAIL_CATCHALL_ACCOUNT_DELETE_LINK'       => '',
            'MAIL_ACCOUNT_DISABLED_DELETION_CHECKBOX' => ' disabled'
        ]);
        $tpl->parse('MAIL_PROTECTED_MAIL_ACCOUNT', 'mail_protected_mail_account');
        $tpl->parse('MAIL_ACCOUNT_ACTION_LINKS', 'mail_account_action_links');
        return;
    }

    if ($mailAutoResponder) {
        $tpl->assign('MAIL_ACCOUNT_AUTORESPONDER_ACTIVATION_LINK', '');
        $tpl->parse('MAIL_ACCOUNT_AUTORESPONDER_DEACTIVATION_LINK', 'mail_account_autoresponder_deactivation_link');
    } else {
        $tpl->assign('MAIL_ACCOUNT_AUTORESPONDER_DEACTIVATION_LINK', '');
        $tpl->parse('MAIL_ACCOUNT_AUTORESPONDER_ACTIVATION_LINK', 'mail_account_autoresponder_activation_link');
    }

    $tpl->parse('MAIL_ACCOUNT_AUTORESPONDER', 'mail_account_autoresponder');
    $tpl->assign([
        'MAIL_CATCHALL_ACCOUNT_DELETE_LINK'       => '',
        'MAIL_PROTECTED_MAIL_ACCOUNT'             => '',
        'MAIL_ACCOUNT_DISABLED_DELETION_CHECKBOX' => ''
    ]);
    $tpl->parse('MAIL_ACCOUNT_EDIT_LINK', 'mail_account_edit_link');
    $tpl->parse('MAIL_ACCOUNT_DELETE_LINK', 'mail_account_delete_link');
    $tpl->parse('MAIL_ACCOUNT_ACTION_LINKS', 'mail_account_action_links');

}

/**
 * Generate Mail accounts list
 *
 * @param TemplateEngine $tpl Template engine
 * @param int $mainDmnId Customer main domain unique identifier
 * @return int number of mail accounts
 */
function generateMailAccountsList($tpl, $mainDmnId)
{
    $where = '';
    if (countDefaultMailAccounts($mainDmnId)) {
        if (!isset($_SESSION['show_default_mail_accounts'])) {
            $tpl->assign('MAIL_HIDE_DEFAULT_MAIL_ACCOUNTS_LINK', '');
            $where .= "
                AND !(
                    (
                        mail_acc IN('abuse', 'hostmaster', 'postmaster', 'webmaster')
                        AND
                        mail_type IN('" . MT_NORMAL_FORWARD . "', '" . MT_ALIAS_FORWARD . "')
                    )
                    OR
                    (
                        mail_acc = 'webmaster'
                        AND
                        mail_type IN('" . MT_SUBDOM_FORWARD . "', '" . MT_ALSSUB_FORWARD . "')
                    )
                )
            ";
        } else {
            $tpl->assign('MAIL_SHOW_DEFAULT_MAIL_ACCOUNTS_LINK', '');
        }
    } else {
        $tpl->assign('MAIL_HIDE_DEFAULT_MAIL_ACCOUNTS_LINK', '');
        $tpl->assign('MAIL_SHOW_DEFAULT_MAIL_ACCOUNTS_LINK', '');
    }

    $stmt = exec_query(
        "
          SELECT mail_id, mail_acc, mail_forward, mail_type, status, mail_auto_respond, quota, mail_addr
          FROM mail_users
          WHERE domain_id = ?
          $where
          ORDER BY mail_addr ASC, mail_type DESC
        ",
        [$mainDmnId]
    );

    unset($where);
    $rowCount = $stmt->rowCount();

    if ($rowCount == 0) {
        $tpl->assign([
            'MAIL_ACCOUNT'                      => '',
            'MAIL_SYNC_QUOTA_INFO_LINK'         => '',
            'MAIL_DELETE_SELECTED_ITEMS_BUTTON' => '',
        ]);
        return 0;
    }

    $postfixConfig = new ConfigFile(utils_normalizePath(Registry::get('config')['CONF_DIR'] . '/postfix/postfix.data'));
    $syncQuotaInfo = isset($_GET['sync_quota_info']);
    $hasMailboxes = $overQuota = false;

    while ($row = $stmt->fetch()) {
        $mailQuotaInfo = '-';
        $quotaPercent = 0;

        foreach (explode(',', $row['mail_type']) as $type) {
            $isCatchall = (strpos($type, 'catchall') !== FALSE);

            if ($isCatchall || strpos($type, 'forward') !== false) {
                $forwardList = implode(
                    ', ', array_map('decode_idna', explode(',', $isCatchall ? $row['mail_acc'] : $row['mail_forward']))
                );
                $tpl->assign([
                    'MAIL_ACCOUNT_LONG_FORWARD_LIST'  => tohtml(wordwrap($forwardList, 75)),
                    'MAIL_ACCOUNT_SHORT_FORWARD_LIST' => tohtml(
                        strlen($forwardList) > 50 ? substr($forwardList, 0, 50) . '...' : $forwardList, 'htmlAttr'
                    )
                ]);

                $tpl->parse('MAIL_ACCOUNT_FORWARD_LIST', 'mail_account_forward_list');
                continue;
            }

            $tpl->assign('MAIL_ACCOUNT_FORWARD_LIST', '');

            $hasMailboxes = true;
            list($user, $domain) = explode('@', $row['mail_addr']);

            $maildirsize = ($row['quota'])
                ? parseMaildirsize(
                    utils_normalizePath($postfixConfig['MTA_VIRTUAL_MAIL_DIR'] . "/$domain/$user/maildirsize"),
                    $syncQuotaInfo
                ) : false;

            if ($maildirsize === false) {
                $mailQuotaInfo = ($row['quota']) ? '- / ' . bytesHuman($row['quota']) : '- / ∞';
                continue;
            }

            $quotaPercent = min(100, round(($maildirsize['byte_count'] / max(1, $maildirsize['quota_bytes'])) * 100));

            if (!$overQuota && $quotaPercent >= 100) {
                $overQuota = true;
            }

            $mailQuotaInfo = sprintf(
                '%s / %s (%.0f%%)',
                bytesHuman($maildirsize['byte_count']),
                bytesHuman($maildirsize['quota_bytes']),
                $quotaPercent
            );
        }

        $tpl->assign([
            'MAIL_ACCOUNT_ID'         => tohtml($row['mail_id']),
            'MAIL_ACCOUNT_ADDR'       => tohtml(substr(decode_idna('-' . $row['mail_addr']), 1)),
            'MAIL_ACCOUNT_TYPE'       => tohtml(user_trans_mail_type($row['mail_acc'], $row['mail_type'])),
            'MAIL_ACCOUNT_QUOTA_INFO' => tohtml($mailQuotaInfo),
            'MAIL_ACCOUNT_STATUS'     => translate_dmn_status($row['status'])
        ]);

        if ($quotaPercent >= 95) {
            $tpl->assign('MAIL_ACCOUNT_NO_QUOTA_WARNING', '');
            $tpl->parse('MAIL_ACCOUNT_QUOTA_WARNING', 'mail_account_quota_warning');
        } else {
            $tpl->assign('MAIL_ACCOUNT_QUOTA_WARNING', '');
            $tpl->parse('MAIL_ACCOUNT_NO_QUOTA_WARNING', 'mail_account_no_quota_warning');
        }

        generateDynamicTplParts($tpl, $row['mail_acc'], $row['mail_type'], $row['status'], $row['mail_auto_respond']);
        $tpl->parse('MAIL_ACCOUNT', '.mail_account');
    }

    if ($syncQuotaInfo) {
        set_page_message(tr('Mailboxes quota info were synced.'), 'success');
        redirectTo('mail_accounts.php');
    }

    if (!$hasMailboxes) {
        $tpl->assign([
            'MAIL_SYNC_QUOTA_INFO_LINK'         => '',
            'MAIL_DELETE_SELECTED_ITEMS_BUTTON' => ''
        ]);
    }

    if ($overQuota) {
        set_page_message(tr('At least one of your mailboxes is over quota.'), 'static_warning');
    }

    return $rowCount;
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl Reference to the pTemplate object
 * @return void
 */
function generatePage($tpl)
{
    if (!customerHasFeature('mail')) {
        $tpl->assign('MAIL_FEATURE', '');
        set_page_message(tr('Mail feature is disabled.'), 'static_info');
        return;
    }

    if (isset($_GET['show_default_mail_accounts'])) {
        if ($_GET['show_default_mail_accounts']) {
            $_SESSION['show_default_mail_accounts'] = '1';
        } else {
            unset($_SESSION['show_default_mail_accounts']);
        }
    }

    $dmnProps = get_domain_default_props($_SESSION['user_id']);
    $mainDmnId = $dmnProps['domain_id'];
    $dmnMailAccLimit = $dmnProps['domain_mailacc_limit'];
    $mailAccountsCount = generateMailAccountsList($tpl, $mainDmnId);
    $defaultMailAccountsCount = countDefaultMailAccounts($mainDmnId);

    if (!Registry::get('config')['COUNT_DEFAULT_EMAIL_ADDRESSES']) {
        if (isset($_SESSION['show_default_mail_accounts'])) {
            $mailAccountsCount -= $defaultMailAccountsCount;
        }
    } elseif (!isset($_SESSION['show_default_mail_accounts'])) {
        $mailAccountsCount += $defaultMailAccountsCount;
    }

    if ($mailAccountsCount || $defaultMailAccountsCount) {
        $tpl->assign([
            'MAIL_TOTAL_MAIL_ACCOUNTS' => tohtml($mailAccountsCount),
            'MAIL_ACCOUNTS_LIMIT'      => tohtml(translate_limit_value($dmnMailAccLimit))
        ]);
        return;
    }

    $tpl->assign('MAIL_ACCOUNTS', '');
    set_page_message(tr('Mail accounts list is empty.'), 'static_info');
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onClientScriptStart);

if (!customerHasMailOrExtMailFeatures()) {
    showBadRequestErrorPage();
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'                                       => 'shared/layouts/ui.tpl',
    'page'                                         => 'client/mail_accounts.phtml',
    'page_message'                                 => 'layout',
    'mail_feature'                                 => 'page',
    'mail_accounts'                                => 'mail_feature',
    'mail_account'                                 => 'mail_accounts',
    'mail_account_autoresponder'                   => 'mail_account',
    'mail_account_autoresponder_activation_link'   => 'mail_account_autoresponder',
    'mail_account_autoresponder_deactivation_link' => 'mail_account_autoresponder',
    'mail_account_forward_list'                    => 'mail_account',
    'mail_account_no_quota_warning'                => 'mail_account',
    'mail_account_quota_warning'                   => 'mail_account',
    'mail_account_action_links'                    => 'mail_account',
    'mail_account_edit_link'                       => 'mail_account_action_links',
    'mail_account_delete_link'                     => 'mail_account_action_links',
    'mail_catchall_account_delete_link'            => 'mail_account_action_links',
    'mail_protected_mail_account'                  => 'mail_account_action_links',
    'mail_show_default_mail_accounts_link'         => 'mail_accounts',
    'mail_hide_default_mail_accounts_link'         => 'mail_accounts',
    'mail_sync_quota_info_link'                    => 'mail_accounts',
    'mail_delete_selected_items_button'            => 'mail_accounts'
]);
$tpl->assign('TR_PAGE_TITLE', tohtml(tr('Client / Mail / Overview')));

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
