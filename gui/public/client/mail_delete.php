<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by i-MSCP Team
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

/**
 * Schedule deletion of the given mail account
 *
 * @throws iMSCP_Exception on error
 * @param int $mailId Mail account unique identifier
 * @param int $domainId Main domain unique identifier
 * @return void
 */
function client_deleteMailAccount($mailId, $domainId)
{
    $stmt = exec_query('SELECT mail_addr FROM mail_users WHERE mail_id = ? AND domain_id = ?', array(
        $mailId, $domainId
    ));

    if (!$stmt->rowCount()) {
        throw new iMSCP_Exception('Bad request.', 400);
    }

    $row = $stmt->fetchRow();
    $mailAddr = $row['mail_addr'];

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeDeleteMail, array('mailId' => $mailId));
    exec_query('UPDATE mail_users SET status = ? WHERE mail_id = ?', array('todelete', $mailId));
    
    # Update or delete forward accounts and/or catch-alls that list mail_addr of the account that is being deleted
    #  Forward accounts:
    #   A forward account which only forward on the mail_addr of the account that is being deleted will be also deleted,
    #   else mail_addr will be simply removed from its forward list
    # Catch-alls:
    #   A catchall that catch only on mail_addr of the account that is being deleted will be also deleted, else
    #   mail_addr will be simply deleted from the catchall list.
    $stmt = exec_query(
        '
            SELECT mail_id, mail_acc, mail_forward FROM mail_users
            WHERE mail_addr <> :mail_addr AND (mail_acc RLIKE :rlike OR mail_forward RLIKE :rlike) 
        ',
        array(
            'mail_addr' => $mailAddr,
            'rlike' => '(,|^)' . $mailAddr . '(,|$)'
        )
    );
    if ($stmt->rowCount()) {
        while ($row = $stmt->fetchRow()) {
            if ($row['mail_forward'] == '_no_') {
                # Catchall
                $row['mail_acc'] = implode(',', preg_grep(
                    '/^' . quotemeta($mailAddr) . '$/', explode(',', $row['mail_acc']), PREG_GREP_INVERT
                ));
            } else {
                # Forward account
                $row['mail_forward'] = implode(',', preg_grep(
                    '/^' . quotemeta($mailAddr) . '$/', explode(',', $row['mail_forward']), PREG_GREP_INVERT
                ));
            }

            if ($row['mail_acc'] == '' || $row['mail_forward'] == '') {
                exec_query('UPDATE mail_users SET status = ? WHERE mail_id = ?', array('todelete', $row['mail_id']));
            } else {
                exec_query('UPDATE mail_users SET status = ?, mail_acc = ?, mail_forward = ? WHERE mail_id = ?', array(
                    'tochange', $row['mail_acc'], $row['mail_forward'], $row['mail_id']
                ));
            }
        }
    }

    delete_autoreplies_log_entries();
    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterDeleteMail, array('mailId' => $mailId));
    set_page_message(tr('Mail account %s successfully scheduled for deletion.', decode_idna($mailAddr)), 'success');
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');

if (!customerHasFeature('mail') || !isset($_REQUEST['id'])) {
    showBadRequestErrorPage();
}

$domainId = get_user_domain_id($_SESSION['user_id']);
$nbDeletedMails = 0;
$mailIds = (array)$_REQUEST['id'];

if (empty($mailIds)) {
    set_page_message(tr('You must select a least one mail account to delete.'), 'error');
    redirectTo('mail_accounts.php');
}

$db = iMSCP_Database::getInstance();

try {
    $db->beginTransaction();

    foreach ($mailIds as $mailId) {
        $mailId = intval($mailId);
        client_deleteMailAccount($mailId, $domainId);
        $nbDeletedMails++;
    }

    $db->commit();
    send_request();
    write_log(sprintf("{$_SESSION['user_logged']} deleted %d mail account(s)", $nbDeletedMails), E_USER_NOTICE);
} catch (iMSCP_Exception $e) {
    $db->rollBack();

    if (Zend_Session::namespaceIsset('pageMessages')) {
        Zend_Session::namespaceUnset('pageMessages');
    }

    $errorMessage = $e->getMessage();
    $code = $e->getCode();

    write_log(sprintf(
        'An unexpected error occurred while attempting to delete a mail account: %s', $errorMessage), E_USER_ERROR
    );

    if ($code == 403) {
        set_page_message(tr('Operation cancelled: %s', $errorMessage), 'warning');
    } elseif ($e->getCode() == 400) {
        showBadRequestErrorPage();
    } else {
        set_page_message(tr('An unexpected error occurred. Please contact your reseller.'), 'error');
    }
}

redirectTo('mail_accounts.php');

