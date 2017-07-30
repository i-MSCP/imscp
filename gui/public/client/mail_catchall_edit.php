<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_pTemplate as TemplateEngine;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Check catch-all owner
 *
 * @param int $catchallId Catch-all unique identifier
 * @return string Catch-all domain name if owner is verified
 */
function checkCatchallOwner($catchallId)
{

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    return $stmt->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Add catch-all account
 *
 * @param int $catchAllDomainId Catch-all domain unique identifier
 * @param string $catchAllDomain Catch all domain name
 * @param string $catchallType Catch-all type
 * @return void
 */
function editCatchallAccount($catchAllDomainId, $catchAllDomain, $catchallType)
{
    if (!isset($_POST['catchall_address_type'])
        || !in_array($_POST['catchall_address_type'], ['auto', 'manual'])
        || ($_POST['catchall_address_type'] == 'auto' && !isset($_POST['automatic_catchall_address_id']))
        || ($_POST['catchall_address_type'] == 'manual' && !isset($_POST['manual_catchall_addresses']))
    ) {
        showBadRequestErrorPage();
    }

    if ($_POST['catchall_address_type'] == 'auto') {
        $stmt = exec_query(
            '
                SELECT sub_id, mail_addr FROM mail_users
                JOIN domain USING(domain_id)
                WHERE mail_id = ? AND domain_admin_id = ?
            ',
            [intval($_POST['automatic_catchall_address_id']), $_SESSION['user_id']]
        );

        if (!$stmt->rowCount()) {
            showBadRequestErrorPage();
        }

        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        EventsManager::getInstance()->dispatch(Events::onBeforeAddMailCatchall, [
            'mailCatchallDomain'    => $catchAllDomain,
            'mailCatchallAddresses' => [$row['mail_addr']]
        ]);
        exec_query(
            "
                INSERT INTO mail_users (
                    mail_acc, mail_forward, domain_id, mail_type, sub_id, status, po_active, mail_addr
                ) VALUES (
                    ?, '_no_', ?, ?, ?, 'toadd', 'no', ?
                )
            ",
            [$row['mail_addr'], $catchAllDomainId, $catchallType, $row['sub_id'], '@' . $catchAllDomain]
        );
        EventsManager::getInstance()->dispatch(Events::onAfterAddMailCatchall, [
            'mailCatchallId'        => Database::getInstance()->insertId(),
            'mailCatchallDomain'    => $catchAllDomain,
            'mailCatchallAddresses' => [$row['mail_addr']]
        ]);
    } else {
        switch ($catchallType) {
            case MT_NORMAL_CATCHALL:
                $domainId = $catchAllDomainId;
                $subId = '0';
                break;
            case MT_ALIAS_CATCHALL:
            case MT_SUBDOM_CATCHALL:
            case MT_ALSSUB_CATCHALL:
                $domainId = get_user_domain_id($_SESSION['user_id']);
                $subId = $catchAllDomainId;
                break;
            default:
                showBadRequestErrorPage();
                exit;
        }

        $catchallAddresses = clean_input($_POST['manual_catchall_addresses']);

        if ($catchallAddresses == '') {
            set_page_message(tr('Catch-all addresses field cannot be empty.'), 'error');
            return;
        }

        $catchallAddresses = preg_split("/[\n,]+/", $catchallAddresses);

        foreach ($catchallAddresses as $key => &$forwardEmailAddr) {
            $forwardEmailAddr = encode_idna(mb_strtolower(trim($forwardEmailAddr)));

            if ($forwardEmailAddr == '') {
                unset($catchallAddresses[$key]);
            } elseif (!chk_email($forwardEmailAddr)) {
                set_page_message(tr('Bad email address in catch-all addresses field.'), 'error');
                return;
            }
        }

        $catchallAddresses = array_unique($catchallAddresses);

        if (empty($catchallAddresses)) {
            set_page_message(tr('Catch-all addresses field cannot be empty.'), 'error');
            return;
        }

        EventsManager::getInstance()->dispatch(Events::onBeforeAddMailCatchall, [
            'mailCatchallDomain'    => $catchAllDomain,
            'mailCatchallAddresses' => $catchallAddresses
        ]);
        exec_query(
            "
            INSERT INTO mail_users (
                mail_acc, mail_forward, domain_id, mail_type, sub_id, status, po_active, mail_addr
            ) VALUES (
                ?, '_no_', ?, ?, ?, 'toadd', 'no', ?
            )
        ",
            [implode(',', $catchallAddresses), '_no_', $domainId, $catchallType, $subId, '@' . $catchAllDomain]
        );
        EventsManager::getInstance()->dispatch(Events::onAfterAddMailCatchall, [
            'mailCatchallId'        => Database::getInstance()->insertId(),
            'mailCatchallDomain'    => $catchAllDomain,
            'mailCatchallAddresses' => $catchallAddresses
        ]);
    }

    send_request();
    write_log(sprintf('A catch-all account has been created by %s', $_SESSION['user_logged']), E_USER_NOTICE);
    set_page_message(tr('Catch-all successfully scheduled for addition.'), 'success');
    redirectTo('mail_catchall.php');
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @param int $catchAllDomainId Catch-all domain unique identifier
 * @param string $catchallType Catch-all type
 * @return void
 */
function generatePage($tpl, $catchAllDomainId, $catchallType)
{
    switch ($catchallType) {
        case MT_NORMAL_CATCHALL:
            $stmt = exec_query(
                "SELECT mail_id, mail_addr FROM mail_users WHERE domain_id = ? AND mail_type RLIKE ? AND status = 'ok'",
                [$catchAllDomainId, MT_NORMAL_MAIL . '|' . MT_NORMAL_FORWARD]
            );

            if (!$stmt->rowCount()) {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESSES'      => '',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED' => ' checked',
                    'MANUAL_CATCHALL_ADDRESSES'         => isset($_POST['manual_catchall_addresses'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''
                ]);
            } else {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESS_CHECKED' => isset($_POST['catchall_address_type'])
                    && $_POST['catchall_address_type'] == 'manual' ? '' : ' checked',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED'  => isset($_POST['catchall_address_type'])
                    && $_POST['catchall_address_type'] == 'manual' ? ' checked' : '',
                    'MANUAL_CATCHALL_ADDRESSES'          => isset($_POST['manual_catchall_addresses'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''
                ]);

                while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                    $tpl->assign([
                        'AUTOMATIC_CATCHALL_ADDRESS_ID' => $row['mail_id'],
                        'AUTOMATIC_CATCHALL_ADDRESS'    => tohtml(decode_idna($row['mail_addr']))
                    ]);
                    $tpl->parse('AUTOMATIC_CATCHALL_ADDRESS_OPTION', '.automatic_catchall_address_option');
                }
            }
            break;
        case MT_SUBDOM_CATCHALL:
            $stmt = exec_query(
                "
                    SELECT mail_id, mail_addr FROM mail_users
                    WHERE domain_id AND sub_id = ? AND mail_type RLIKE ? AND status = 'ok'",
                [get_user_domain_id($_SESSION['user_id']), $catchAllDomainId, MT_SUBDOM_MAIL . '|' . MT_SUBDOM_FORWARD]
            );

            if (!$stmt->rowCount()) {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESSES'      => '',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED' => ' checked',
                    'MANUAL_CATCHALL_ADDRESSES'         => isset($_POST['manual_catchall_addresses'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''

                ]);
            } else {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESS_CHECKED' => isset($_POST['catchall_address_type'])
                    && $_POST['catchall_address_type'] == 'manual' ? '' : ' checked',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED'  => isset($_POST['catchall_address_type'])
                    && $_POST['catchall_address_type'] == 'manual' ? ' checked' : '',
                    'MANUAL_CATCHALL_ADDRESSES'          => isset($_POST['manual_catchall_addresses'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''
                ]);

                while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                    $tpl->assign([
                        'AUTOMATIC_CATCHALL_ADDRESS_ID' => $row['mail_id'],
                        'AUTOMATIC_CATCHALL_ADDRESS'    => tohtml(decode_idna($row['mail_addr']))
                    ]);
                    $tpl->parse('AUTOMATIC_CATCHALL_ADDRESS_OPTION', '.automatic_catchall_address_option');
                }
            }
            break;
        case MT_ALIAS_CATCHALL:
            $stmt = exec_query(
                "
                    SELECT mail_id, mail_addr FROM mail_users
                    WHERE domain_id = ? AND sub_id = ? AND mail_type RLIKE ? AND status = 'ok'
                ",
                [get_user_domain_id($_SESSION['user_id']), $catchAllDomainId, MT_ALIAS_MAIL . '|' . MT_ALIAS_FORWARD]
            );

            if (!$stmt->rowCount()) {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESSES'      => '',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED' => ' checked',
                    'MANUAL_CATCHALL_ADDRESSES'         => isset($_POST['manual_catchall_addresses'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''

                ]);
            } else {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESS_CHECKED' => isset($_POST['catchall_address_type'])
                    && $_POST['catchall_address_type'] == 'manual' ? '' : ' checked',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED'  => isset($_POST['catchall_address_type'])
                    && $_POST['catchall_address_type'] == 'manual' ? ' checked' : '',
                    'MANUAL_CATCHALL_ADDRESSES'          => isset($_POST['manual_catchall_addresses'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''
                ]);

                while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                    $tpl->assign([
                        'AUTOMATIC_CATCHALL_ADDRESS_ID' => $row['mail_id'],
                        'AUTOMATIC_CATCHALL_ADDRESS'    => tohtml(decode_idna($row['mail_addr']))
                    ]);
                    $tpl->parse('AUTOMATIC_CATCHALL_ADDRESS_OPTION', '.automatic_catchall_address_option');
                }
            }
            break;
        case MT_ALSSUB_CATCHALL:
            $stmt = exec_query(
                "
                    SELECT mail_id, mail_addr FROM mail_users
                    WHERE domain_id = ? AND sub_id = ? AND mail_type RLIKE ? AND status = 'ok'
                ",
                [get_user_domain_id($_SESSION['user_id']), $catchAllDomainId, MT_ALSSUB_MAIL . '|' . MT_ALSSUB_FORWARD]
            );

            if (!$stmt->rowCount()) {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESSES'      => '',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED' => ' checked',
                    'MANUAL_CATCHALL_ADDRESSES'         => isset($_POST['forward_list'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''
                ]);
            } else {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESS_CHECKED' => isset($_POST['catchall_address_type'])
                    && $_POST['catchall_address_type'] == 'manual' ? '' : ' checked',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED'  => isset($_POST['catchall_address_type'])
                    && $_POST['catchall_address_type'] == 'manual' ? ' checked' : '',
                    'MANUAL_CATCHALL_ADDRESSES'          => isset($_POST['forward_list'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''
                ]);

                while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
                    $tpl->assign([
                        'AUTOMATIC_CATCHALL_ADDRESS_ID' => $row['mail_id'],
                        'AUTOMATIC_CATCHALL_ADDRESS'    => tohtml(decode_idna($row['mail_addr']))
                    ]);
                    $tpl->parse('AUTOMATIC_CATCHALL_ADDRESS_OPTION', '.automatic_catchall_address_option');
                }
            }
            break;
        default:
            showBadRequestErrorPage();
    }
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

EventsManager::getInstance()->dispatch(Events::onClientScriptStart);
check_login('user');

if (!customerHasFeature('mail')
    || !isset($_REQUEST['id'])
) {
    showBadRequestErrorPage();
}

$catchallId = clean_input($_REQUEST['id']);

if (!($catchAllDomainName = checkCatchallOwner($catchallId))
) {
    showBadRequestErrorPage();
    exit;
}

if (!empty($_POST)) {
    editCatchallAccount($catchallId);
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'                            => 'shared/layouts/ui.tpl',
    'page'                              => 'client/mail_catchall_add.phtml',
    'page_message'                      => 'layout',
    'automatic_catchall_addresses'      => 'page',
    'automatic_catchall_address_option' => 'automatic_catchall_addresses'
]);
$tpl->assign([
    'TR_PAGE_TITLE'        => tohtml(tr('Client / Mail / Catch-all Accounts / Edit Catch-all account')),
    'CATCHALL_DOMAIN_NAME' => tohtml(decode_idna($catchAllDomainName)),
    'CATCHALL_ID'          => tohtml($catchallId, 'htmlAttr')
]);

generateNavigation($tpl);
generatePageMessage($tpl);
generatePage($tpl, $catchallId);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventsManager::getInstance()->dispatch(Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
