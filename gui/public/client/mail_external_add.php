<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

/***********************************************************************************************************************
 * Functions
 */

/**
 * Validate the given DNS MX record
 *
 * @access private
 * @param string $type MX type
 * @param int $priority MX preference
 * @param string $host Mail host
 * @param $verifiedData
 * @return bool TRUE if the given MX DNS record is valid, FALSE otherwise
 */
function _client_validateDnsMxRecord($type, $priority, $host, $verifiedData)
{
    $validator = iMSCP_Validate::getInstance();
    $mxTypes = array('domain', 'wildcard');

    if (customerHasFeature('mail')) {
        $mxTypes[] = 'filter';
    }

    // Should never occurs since we are using options stack in HTML form
    if (!$validator->assertContains($type, $mxTypes, true, tr('Invalid MX type.'))) {
        if ($type == 'filter') {
            showBadRequestErrorPage();
        } else {
            set_page_message($validator->getLastValidationMessages(), 'error');
            return false;
        }
    }

    // Should never occurs since we are using options stack in HTML form
    if (!$validator->assertContains($priority, range(5, 50, 5), false, tr('Invalid MX priority.'))) {
        set_page_message($validator->getLastValidationMessages(), 'error');
        return false;
    }

    // Mail host must not be equal to the domain for which it's added
    if (!$validator->assertNotEquals($verifiedData['item_name'], encode_idna($host), tr('Mailhost must not be equal to the domain name for which you add it.'))) {
        set_page_message($validator->getLastValidationMessages(), 'error');
        return false;
    }

    if ($host == '') {
        set_page_message(tr('Mailhost cannot be empty.'), 'error');
        return false;
    } elseif (!isValidDomainName($host)) {
        set_page_message(tr("Mailhost %s is not valid.", "<strong>$host</strong>"), 'error');
        return false;
    }

    return true;
}

/**
 * Returns verified data
 *
 * @access private
 * @param int $itemId Item id (Domain ID or domain alias id)
 * @param string $itemType Item type (normal or alias
 * @return array An array that holds verified data (main domain id and item name)
 */
function _client_getVerifiedData($itemId, $itemType)
{
    $domainProps = get_domain_default_props($_SESSION['user_id']);
    $domainId = $domainProps['domain_id'];

    if ($itemType == 'normal') {
        $stmt = exec_query('SELECT domain_id, domain_name AS name FROM domain WHERE domain_id = ?', $domainId);

        if (!$stmt->rowCount() || $stmt->fields['domain_id'] !== $itemId) {
            showBadRequestErrorPage();
        }
    } elseif ($itemType == 'alias') {
        $stmt = exec_query(
            'SELECT domain_id, alias_name AS name FROM domain_aliasses WHERE alias_id = ? AND domain_id = ?',
            array($itemId, $domainId)
        );

        if (!$stmt->rowCount()) {
            showBadRequestErrorPage();
        }
    } else {
        showBadRequestErrorPage();
        exit; // Only to make some IDE happy
    }

    return array(
        'domain_id' => $domainId,
        'item_id' => $itemId,
        'item_name' => $stmt->fields['name'],
        'item_type' => $itemType
    );
}

/**
 * Add external mail server entries
 *
 *
 * @param array $item Item data (item id and item type)
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function client_addExternalMailServerEntries($item)
{
    $verifiedData = _client_getVerifiedData($item[0], $item[1]);

    if (!empty($_POST)) {
        // Preparing entries stack
        $data['type'] = (isset($_POST['type'])) ? $_POST['type'] : array();
        $data['priority'] = (isset($_POST['priority'])) ? $_POST['priority'] : array();
        $data['host'] = (isset($_POST['host'])) ? $_POST['host'] : array();

        $responses = iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeAddExternalMailServer, array(
            'externalMailServerEntries' => $data
        ));

        if (!$responses->isStopped()) {
            $entriesCount = count($data['type']);
            $error = false;
            # Spam Filter ( filter ) MX type has highter precedence
            $spamFilterMX = false;
            $wildcardMxOnly = true;

            // Validate all entries
            for ($index = 0; $index < $entriesCount; $index++) {
                if (isset($data['type'][$index]) && isset($data['priority'][$index]) && isset($data['host'][$index])) {
                    $data['host'][$index] = strtolower(rtrim($data['host'][$index], '.'));

                    if (!_client_validateDnsMxRecord($data['type'][$index], $data['priority'][$index], $data['host'][$index], $verifiedData)) {
                        $error = true;
                    }

                    if ($data['type'][$index] == 'filter') {
                        $spamFilterMX = true;
                        $wildcardMxOnly = false;
                    } elseif ($data['type'][$index] == 'domain') {
                        $wildcardMxOnly = false;
                    }
                } else { // Not all expected data were received
                    showBadRequestErrorPage();
                }
            }

            // Add DNS entries into database
            if (!$error) {
                $db = iMSCP_Database::getInstance();

                try {
                    $db->beginTransaction(); // All successfully inserted or nothing
                    $dnsEntriesIds = '';

                    for ($index = 0; $index < $entriesCount; $index++) {
                        // Add MX record
                        exec_query(
                            '
                                INSERT INTO domain_dns (
                                    domain_id, alias_id, domain_dns, domain_class, domain_type, domain_text, owned_by,
                                    domain_dns_status
                                ) VALUES (
                                    ?, ?, ?, ?, ?, ?, ?, ?
                                )
                            ',
                            array(
                                $verifiedData['domain_id'], $verifiedData['item_type'] == 'alias' ? $verifiedData['item_id'] : 0,
                                $data['type'][$index] != 'wildcard' ? $verifiedData['item_name'] . '.' : '*.' . $verifiedData['item_name'] . '.',
                                'IN', 'MX', "{$data['priority'][$index]}\t" . encode_idna($data['host'][$index]) . '.',
                                'ext_mail_feature', 'toadd'
                            )
                        );

                        $dnsEntriesIds .= ',' . $db->insertId();
                    }

                    if ($verifiedData['item_type'] == 'normal') {
                        exec_query(
                            '
                                UPDATE domain SET external_mail = ?, domain_status = ?, external_mail_dns_ids = ?
                                WHERE domain_id = ?
                            ',
                            array(
                                $spamFilterMX ? 'filter' : ($wildcardMxOnly ? 'wildcard' : 'domain'),
                                'tochange', ltrim($dnsEntriesIds, ','), $verifiedData['domain_id']
                            )
                        );
                    } else {
                        exec_query(
                            '
                                UPDATE domain_aliasses SET external_mail = ?, alias_status = ?, external_mail_dns_ids = ?
                                WHERE alias_id = ?
                            ',
                            array(
                                $spamFilterMX ? 'filter' : (($wildcardMxOnly) ? 'wildcard' : 'domain'),
                                'tochange', ltrim($dnsEntriesIds, ','), $verifiedData['item_id'])
                        );
                    }

                    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterAddExternalMailServer, array(
                        'externalMailServerEntries' => $data
                    ));

                    $db->commit();
                    send_request();
                    set_page_message(tr('External mail server successfully scheduled for addition.'), 'success');
                    redirectTo('mail_external.php');
                } catch (iMSCP_Exception $e) {
                    $db->rollBack();

                    if ($e->getCode() === 23000) {
                        set_page_message(tr('An entry is defined twice.'), 'error');
                    } else {
                        throw $e;
                    }
                }
            }
        } else {
            redirectTo('mail_external.php');
        }
    } else {
        $data['type'][] = 'domain';
        $data['priority'][] = '5';
        $data['host'][] = '';
    }

    client_generateView($verifiedData, $data);
}

/**
 * Generate view
 *
 * @param array $verifiedData Verified data
 * @param array $data Page data
 * @return void
 */
function client_generateView($verifiedData, $data)
{
    /** @var $tpl iMSCP_pTemplate */
    $tpl = iMSCP_Registry::get('templateEngine');
    $cfg = iMSCP_Registry::get('config');
    $selectedOption = $cfg['HTML_SELECTED'];
    $idnItemName = $verifiedData['item_name'];
    $entriesCount = isset($data['type']) ? count($data['type']) : 0;
    $mxTypes = array(
        tr('Domain') => 'domain',
        tr('Wildcard') => 'wildcard'
    );

    if (customerHasFeature('mail')) {
        $mxTypes[tr('Spam Filter')] = 'filter';
    }

    $tpl->assign(array(
        'TR_PAGE_TITLE' => tr('Client / Email / External Mail Server / Add External Mail Server for {DOMAIN_UTF8}'),
        'TR_MX_TYPE' => tr('Type'),
        'DOMAIN_UTF8' => decode_idna($idnItemName),
        'TR_PRIORITY' => tr('Priority'),
        'TR_HOST' => tr('External Mail Host'),
        'TR_ADD_NEW_ENTRY' => tr('Add a new entry'),
        'TR_REMOVE_LAST_ENTRY' => tr('Remove last entry'),
        'TR_RESET_ENTRIES' => tr('Reset entries'),
        'TR_CANCEL' => tr('Cancel'),
        'TR_ADD' => tr('Add'),
        'TR_MX_TYPE_TOOLTIP' =>
            tr('Domain: Setup a DNS MX record to relay mail of your entire domain, including subdomains.') . '<br><br>' . (
            customerHasFeature('mail') ? tr('Wildcard: Setup a DNS MX record to relay mail for inexistent subdomains.') . '<br><br>' : ''
        ) .
            tr('Spam Filter: Setup a DNS MX record to relay mail of your entire domain, including subdomains, but retains our server as final mailhost.') . '<br><br>' .
            tr('Note: You cannot mix Spam filter and domain options'),
        'ITEM' => $verifiedData['item_id'] . ';' . $verifiedData['item_type']
    ));

    iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
        /** @var iMSCP_Events_Event $e */
        $translations = $e->getParam('translations');
        $translations['core']['remove_alert'] = tr('You cannot remove this entry.');
    });

    for ($index = 0; $index < $entriesCount; $index++) {
        // Generates html option elements for the names
        foreach ($mxTypes as $optionName => $optionValue) {
            $tpl->assign(array(
                'OPTION_VALUE' => $optionValue,
                'SELECTED' => $optionValue == $data['type'][$index] ? $selectedOption : '',
                'OPTION_NAME' => $optionName
            ));
            $tpl->parse('TYPE_OPTIONS', '.type_options');
        }

        // Generates html option elements for the MX priority
        foreach (range(5, 50, 5) as $option) {
            $tpl->assign(array(
                'OPTION_VALUE' => $option,
                'SELECTED' => $option == $data['priority'][$index] ? $selectedOption : '',
                'OPTION_NAME' => $option
            ));
            $tpl->parse('PRIORITY_OPTIONS', '.priority_options');
        }

        $tpl->assign('HOST', $data['host'][$index]);
        $tpl->parse('ITEM_ENTRIES', '.item_entries');
        $tpl->assign('TYPE_OPTIONS', ''); // Reset name options stack for next record
        $tpl->assign('PRIORITY_OPTIONS', ''); // Reset priority options stack for next record
    }
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');

if (!customerHasFeature('external_mail')
    || !isset($_REQUEST['item'])
    || count($item = explode(';', $_REQUEST['item'], 2)) != 2
) {
    showBadRequestErrorPage();
    return;
}

$tpl = iMSCP_Registry::set('templateEngine', new iMSCP_pTemplate());
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'client/mail_external_add.tpl',
    'page_message' => 'layout',
    'item_entries' => 'page',
    'type_options' => 'item_entries',
    'priority_options' => 'item_entries'
));

generateNavigation($tpl);
client_addExternalMailServerEntries($item);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

unsetMessages();
