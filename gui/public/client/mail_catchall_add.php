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

use iMSCP_Events as Events;
use iMSCP\TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get catch-all domain
 *
 * @param int $catchallDomainId Domain unique identifier
 * @param int $catchalType Catch-all type
 * @return string Catch-all domain name if owner is verified, FALSE otherwise
 */
function getCatchallDomain($catchallDomainId, $catchalType)
{
    switch ($catchalType) {
        case MT_NORMAL_CATCHALL:
            $stmt = exec_query('SELECT domain_name FROM domain WHERE domain_id = ? AND domain_admin_id = ?', [
                $catchallDomainId, $_SESSION['user_id']
            ]);
            break;
        case MT_SUBDOM_CATCHALL:
            $stmt = exec_query(
                "
                    SELECT CONCAT(subdomain_name, '.', domain_name) FROM subdomain
                    JOIN domain USING(domain_id)
                    WHERE subdomain_id = ?
                    AND domain_admin_id = ?
                ",
                [$catchallDomainId, $_SESSION['user_id']]
            );
            break;
        case MT_ALIAS_CATCHALL:
            $stmt = exec_query(
                "
                    SELECT alias_name FROM domain_aliasses
                    JOIN domain USING(domain_id)
                    WHERE alias_id = ?
                    AND domain_admin_id = ?
                ",
                [$catchallDomainId, $_SESSION['user_id']]
            );
            break;
        case MT_ALSSUB_CATCHALL:
            $stmt = exec_query(
                "
                    SELECT CONCAT(subdomain_alias_name, '.', alias_name) FROM subdomain_alias
                    JOIN domain_aliasses USING(alias_id)
                    JOIN domain USING(domain_id)
                    WHERE subdomain_alias_id = ?
                    AND domain_admin_id = ?
                ",
                [$catchallDomainId, $_SESSION['user_id']]
            );
            break;
        default:
            return false;
    }

    return $stmt->fetchColumn();
}

/**
 * Add catch-all account
 *
 * @param int $catchallDomainId Catch-all domain unique identifier
 * @param string $catchallDomain Catch all domain name
 * @param string $catchallType Catch-all type
 * @return void
 */
function addCatchallAccount($catchallDomainId, $catchallDomain, $catchallType)
{
    if (!isset($_POST['catchall_addresses_type'])
        || !in_array($_POST['catchall_addresses_type'], ['auto', 'manual'])
        || ($_POST['catchall_addresses_type'] == 'manual' && !isset($_POST['manual_catchall_addresses']))
    ) {
        showBadRequestErrorPage();
    }

    if ($_POST['catchall_addresses_type'] == 'auto') {
        if (!isset($_POST['automatic_catchall_addresses'])
            || !is_array($_POST['automatic_catchall_addresses'])
        ) {
            showBadRequestErrorPage();
        }

        if (empty($_POST['automatic_catchall_addresses'])) {
            set_page_message(tr('You must select at least one catch-all address.'), 'error');
            showBadRequestErrorPage();
        }

        $catchallAddresses = [];

        foreach ($_POST['automatic_catchall_addresses'] as $catchallAddressId) {
            $stmt = exec_query('SELECT mail_addr FROM mail_users WHERE mail_id = ? AND domain_id = ?', [
                intval($catchallAddressId), get_user_domain_id($_SESSION['user_id'])
            ]);

            if (!$stmt->rowCount()) {
                showBadRequestErrorPage();
            }

            $catchallAddresses[] = $stmt->fetchColumn();
        }
    } else {
        $catchallAddresses = clean_input($_POST['manual_catchall_addresses']);

        if ($catchallAddresses === '') {
            set_page_message(tr('Catch-all addresses field cannot be empty.'), 'error');
            return;
        }

        $catchallAddresses = array_unique(preg_split('/\s|,/', $catchallAddresses, -1, PREG_SPLIT_NO_EMPTY));

        foreach ($catchallAddresses as $key => &$catchallAddress) {
            $catchallAddress = encode_idna(mb_strtolower(trim($catchallAddress)));

            if (!chk_email($catchallAddress)) {
                set_page_message(tr('Bad email address in catch-all addresses field.'), 'error');
                return;
            }
        }

        if (empty($catchallAddresses)) {
            set_page_message(tr('Catch-all addresses field cannot be empty.'), 'error');
            return;
        }
    }

    $domainId = get_user_domain_id($_SESSION['user_id']);

    switch ($catchallType) {
        case MT_NORMAL_CATCHALL:
            $subId = '0';
            break;
        case MT_ALIAS_CATCHALL:
        case MT_SUBDOM_CATCHALL:
        case MT_ALSSUB_CATCHALL:
            $subId = $catchallDomainId;
            break;
        default:
            showBadRequestErrorPage();
            exit;
    }

    Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onBeforeAddMailCatchall, [
        'mailCatchallDomain'    => $catchallDomain,
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
        [implode(',', $catchallAddresses), $domainId, $catchallType, $subId, '@' . $catchallDomain]
    );
    Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onAfterAddMailCatchall, [
        'mailCatchallId'        => Registry::get('iMSCP_Application')->getDatabase()->lastInsertId(),
        'mailCatchallDomain'    => $catchallDomain,
        'mailCatchallAddresses' => $catchallAddresses
    ]);
    send_request();
    write_log(sprintf('A catch-all account has been created by %s', $_SESSION['user_logged']), E_USER_NOTICE);
    set_page_message(tr('Catch-all successfully scheduled for addition.'), 'success');
    redirectTo('mail_catchall.php');
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @param int $catchallDomainId Catch-all domain unique identifier
 * @param string $catchallType Catch-all type
 * @return void
 */
function generatePage($tpl, $catchallDomainId, $catchallType)
{
    switch ($catchallType) {
        case MT_NORMAL_CATCHALL:
            $stmt = exec_query(
                "SELECT mail_id, mail_addr FROM mail_users WHERE domain_id = ? AND mail_type RLIKE ? AND status = 'ok'",
                [$catchallDomainId, MT_NORMAL_MAIL . '|' . MT_NORMAL_FORWARD]
            );

            if (!$stmt->rowCount()) {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESSES_BLK'  => '',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED' => ' checked',
                    'MANUAL_CATCHALL_ADDRESSES'         => isset($_POST['manual_catchall_addresses'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''
                ]);
            } else {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESSES_CHECKED' => isset($_POST['catchall_addresses_type'])
                    && $_POST['catchall_addresses_type'] == 'manual' ? '' : ' checked',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED'    => isset($_POST['catchall_addresses_type'])
                    && $_POST['catchall_addresses_type'] == 'manual' ? ' checked' : '',
                    'MANUAL_CATCHALL_ADDRESSES'            => isset($_POST['manual_catchall_addresses'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''
                ]);

                while ($row = $stmt->fetch()) {
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
                    SELECT mail_id, mail_addr
                    FROM mail_users
                    WHERE domain_id
                    AND sub_id = ?
                    AND mail_type RLIKE ?
                    AND status = 'ok'
                ",
                [get_user_domain_id($_SESSION['user_id']), $catchallDomainId, MT_SUBDOM_MAIL . '|' . MT_SUBDOM_FORWARD]
            );

            if (!$stmt->rowCount()) {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESSES_BLK'  => '',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED' => ' checked',
                    'MANUAL_CATCHALL_ADDRESSES'         => isset($_POST['manual_catchall_addresses'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''
                ]);
            } else {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESSES_CHECKED' => isset($_POST['catchall_addresses_type'])
                    && $_POST['catchall_addresses_type'] == 'manual' ? '' : ' checked',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED'    => isset($_POST['catchall_addresses_type'])
                    && $_POST['catchall_addresses_type'] == 'manual' ? ' checked' : '',
                    'MANUAL_CATCHALL_ADDRESSES'            => isset($_POST['manual_catchall_addresses'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''
                ]);

                while ($row = $stmt->fetch()) {
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
                    SELECT mail_id, mail_addr
                    FROM mail_users
                    WHERE domain_id = ?
                    AND sub_id = ?
                    AND mail_type RLIKE ?
                    AND status = 'ok'
                ",
                [get_user_domain_id($_SESSION['user_id']), $catchallDomainId, MT_ALIAS_MAIL . '|' . MT_ALIAS_FORWARD]
            );

            if (!$stmt->rowCount()) {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESSES_BLK'  => '',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED' => ' checked',
                    'MANUAL_CATCHALL_ADDRESSES'         => isset($_POST['manual_catchall_addresses'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''

                ]);
            } else {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESSES_CHECKED' => isset($_POST['catchall_addresses_type'])
                    && $_POST['catchall_addresses_type'] == 'manual' ? '' : ' checked',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED'    => isset($_POST['catchall_addresses_type'])
                    && $_POST['catchall_addresses_type'] == 'manual' ? ' checked' : '',
                    'MANUAL_CATCHALL_ADDRESSES'            => isset($_POST['manual_catchall_addresses'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''
                ]);

                while ($row = $stmt->fetch()) {
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
                    SELECT mail_id, mail_addr
                    FROM mail_users
                    WHERE domain_id = ?
                    AND sub_id = ?
                    AND mail_type RLIKE ?
                    AND status = 'ok'
                ",
                [get_user_domain_id($_SESSION['user_id']), $catchallDomainId, MT_ALSSUB_MAIL . '|' . MT_ALSSUB_FORWARD]
            );

            if (!$stmt->rowCount()) {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESSES_BLK'  => '',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED' => ' checked',
                    'MANUAL_CATCHALL_ADDRESSES'         => isset($_POST['forward_list'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''
                ]);
            } else {
                $tpl->assign([
                    'AUTOMATIC_CATCHALL_ADDRESSES_CHECKED' => isset($_POST['catchall_addresses_type'])
                    && $_POST['catchall_addresses_type'] == 'manual' ? '' : ' checked',
                    'MANUAL_CATCHALL_ADDRESSES_CHECKED'    => isset($_POST['catchall_addresses_type'])
                    && $_POST['catchall_addresses_type'] == 'manual' ? ' checked' : '',
                    'MANUAL_CATCHALL_ADDRESSES'            => isset($_POST['forward_list'])
                        ? tohtml($_POST['manual_catchall_addresses']) : ''
                ]);

                while ($row = $stmt->fetch()) {
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

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onClientScriptStart);

if (!customerHasFeature('mail')
    || !isset($_GET['id'])
) {
    showBadRequestErrorPage();
}

$catchallId = clean_input($_GET['id']);

if (!preg_match(
        '/^(?P<catchallDomainId>\d+);(?P<catchallType>(?:'
        . MT_NORMAL_CATCHALL . '|' . MT_SUBDOM_CATCHALL . '|' . MT_ALIAS_CATCHALL . '|' . MT_ALSSUB_CATCHALL
        . '))$/',
        $catchallId,
        $matches
    )
    || ($catchallDomain = getCatchallDomain($matches['catchallDomainId'], $matches['catchallType'])) === false
) {
    showBadRequestErrorPage();
    exit;
}

if (!empty($_POST)) {
    addCatchallAccount($matches['catchallDomainId'], $catchallDomain, $matches['catchallType']);
}

$tpl = new TemplateEngine();
$tpl->define([
    'layout'                            => 'shared/layouts/ui.tpl',
    'page'                              => 'client/mail_catchall_add.phtml',
    'page_message'                      => 'layout',
    'automatic_catchall_addresses_blk'  => 'page',
    'automatic_catchall_address_option' => 'automatic_catchall_addresses_blk'
]);
$tpl->assign([
    'TR_PAGE_TITLE'   => tohtml(tr('Client / Mail / Catch-all Accounts / Add Catch-all account')),
    'CATCHALL_DOMAIN' => tohtml(decode_idna($catchallDomain)),
    'CATCHALL_ID'     => tohtml($catchallId, 'htmlAttr')
]);

generateNavigation($tpl);
generatePage($tpl, $matches['catchallDomainId'], $matches['catchallType']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
