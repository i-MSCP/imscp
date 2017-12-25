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
use iMSCP_Exception as iMSCPException;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Schedule deletion of the given mail account
 *
 * @throws iMSCPException on error
 * @param int $mailId Mail account unique identifier
 * @param int $domainId Main domain unique identifier
 * @param ConfigFile $config
 * @param ConfigFile $mtaConfig
 * @param int &$nbDeletedMails Counter for deleted mail accounts
 * @return void
 */
function deleteMailAccount($mailId, $domainId, $config, $mtaConfig, &$nbDeletedMails)
{
    $stmt = exec_query('SELECT mail_acc, mail_addr, mail_type FROM mail_users WHERE mail_id = ? AND domain_id = ?', [
        $mailId, $domainId
    ]);

    if (!$stmt->rowCount()) {
        return;
    }

    $row = $stmt->fetch();

    if ($config['PROTECT_DEFAULT_EMAIL_ADDRESSES']
        && (
            (in_array($row['mail_type'], [MT_NORMAL_FORWARD, MT_ALIAS_FORWARD])
                && in_array($row['mail_acc'], ['abuse', 'hostmaster', 'postmaster', 'webmaster'])
            )
            || ($row['mail_acc'] == 'webmaster' && in_array($row['mail_type'], [MT_SUBDOM_FORWARD, MT_ALSSUB_FORWARD]))
        )
    ) {
        return;
    }

    Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onBeforeDeleteMail, ['mailId' => $mailId]);
    exec_query("UPDATE mail_users SET status = 'todelete' WHERE mail_id = ?", [$mailId]);

    if (strpos($row['mail_type'], '_mail') !== false) {
        # Remove cached quota info if any
        list($user, $domain) = explode('@', $row['mail_addr']);
        unset($_SESSION['maildirsize'][utils_normalizePath($mtaConfig['MTA_VIRTUAL_MAIL_DIR'] . "/$domain/$user/maildirsize")]);
    }

    # Update or delete forward and/or catch-all accounts that list mail_addr of
    # the account that is being deleted.
    #
    # Forward accounts:
    #  A forward account that is only forwarded to the mail_addr of the account
    #  that is being deleted will be also deleted, else the mail_addr will be
    #  simply removed from its forward list
    #
    # Catch-all accounts:
    #   A catch-all account that catch only on mail_addr of the account that is
    #   being deleted will be also deleted, else the mail_addr will be simply
    #   deleted from the catch-all addresses list.
    $stmt = exec_query(
        '
            SELECT mail_id, mail_acc, mail_forward
            FROM mail_users
            WHERE mail_id <> ?
            AND (mail_acc RLIKE ? OR mail_forward RLIKE ?) 
        ',
        [$mailId, '(,|^)' . $row['mail_addr'] . '(,|$)', '(,|^)' . $row['mail_addr'] . '(,|$)']
    );

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetch()) {
            if ($row['mail_forward'] == '_no_') {
                # catch-all account
                $row['mail_acc'] = implode(',', preg_grep(
                    '/^' . quotemeta($row['mail_addr']) . '$/', explode(',', $row['mail_acc']), PREG_GREP_INVERT
                ));
            } else {
                # Forward account
                $row['mail_forward'] = implode(',', preg_grep(
                    '/^' . quotemeta($row['mail_addr']) . '$/', explode(',', $row['mail_forward']), PREG_GREP_INVERT
                ));
            }

            if ($row['mail_acc'] === '' || $row['mail_forward'] === '') {
                exec_query("UPDATE mail_users SET status = 'todelete' WHERE mail_id = ?", [$row['mail_id']]);
            } else {
                exec_query(
                    "UPDATE mail_users SET status = 'tochange', mail_acc = ?, mail_forward = ? WHERE mail_id = ?",
                    [$row['mail_acc'], $row['mail_forward'], $row['mail_id']]
                );
            }
        }
    }

    delete_autoreplies_log_entries();
    Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAfterDeleteMail, ['mailId' => $mailId]);
    $nbDeletedMails++;
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onClientScriptStart);

if (!customerHasFeature('mail')
    || !isset($_REQUEST['id'])
) {
    showBadRequestErrorPage();
}

$domainId = get_user_domain_id($_SESSION['user_id']);
$nbDeletedMails = 0;
$mailIds = (array)$_REQUEST['id'];

if (empty($mailIds)) {
    set_page_message(tr('You must select at least one mail account to delete.'), 'error');
    redirectTo('mail_accounts.php');
}

/** @var iMSCP_Database $db */
$db = Registry::get('iMSCP_Application')->getDatabase();

try {
    $db->beginTransaction();
    $config = Registry::get('config');
    $mtaConfig = new ConfigFile(utils_normalizePath(Registry::get('config')['CONF_DIR'] . '/postfix/postfix.data'));

    foreach ($mailIds as $mailId) {
        deleteMailAccount(intval($mailId), $domainId, $config, $mtaConfig, $nbDeletedMails);
    }

    $db->commit();
    send_request();

    if ($nbDeletedMails) {
        write_log(sprintf('%d mail account(s) were deleted by %s', $nbDeletedMails, $_SESSION['user_logged']), E_USER_NOTICE);
        set_page_message(
            ntr(
                'Mail account has been scheduled for deletion.',
                '%d mail accounts were scheduled for deletion.',
                $nbDeletedMails,
                $nbDeletedMails
            ),
            'success'
        );
    } else {
        set_page_message(tr('No mail account has been deleted.'), 'warning');
    }
} catch (iMSCPException $e) {
    $db->rollBack();
    $errorMessage = $e->getMessage();
    $code = $e->getCode();

    write_log(sprintf('An unexpected error occurred while attempting to delete a mail account: %s', $errorMessage), E_USER_ERROR);

    if ($code == 403) {
        set_page_message(tr('Operation cancelled: %s', $errorMessage), 'warning');
    } elseif ($e->getCode() == 400) {
        showBadRequestErrorPage();
    } else {
        set_page_message(tr('An unexpected error occurred. Please contact your reseller.'), 'error');
    }
}

redirectTo('mail_accounts.php');
