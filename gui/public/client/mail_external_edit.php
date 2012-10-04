<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Client
 * @copyright   2010-2012 by i-MSCP team
 * @author        Sascha Bay <worst.case@gmx.de>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Validate the given DNS MX record
 *
 * @access private
 * @param string $name Value specific to mx type (ie domain.tld for domain MX and *.domain.tld for wildcard MX)
 * @param int $priority MX preference
 * @param string $host Mail host
 * @param $verifiedData
 * @return bool TRUE if the given MX DNS record is valid, FALSE otherwise
 */
function _client_validateDnsMxRecord($name, $priority, &$host, $verifiedData)
{
    $validator = iMSCP_Validate::getInstance();

    // Should never occurs since we are using options stack in HTML form
    $nameTmp = strpos($name, '*') === 0 ? substr($name, 2) : $name; // strip out the wildcard part (*.)
    if (!$validator->assertEquals($verifiedData['item_name'], $nameTmp, tr('Invalid name detected'))) {
        set_page_message($validator->getLastValidationMessages(), 'error');
        return false;
    }

    //  // Should never occurs since we are using options stack in HTML form
    if (!$validator->assertContains($priority, array('10', '15', '20', '25', '30'), tr('Wrong MX priority'))) {
        set_page_message($validator->getLastValidationMessages(), 'error');
        return false;
    }

    $host = rtrim($host, '.'); // strip out any trailing dot

    if (
        !$validator->assertNotEquals($verifiedData['item_name'], $host, tr('The name and host values for an MX entry must not be equals')) ||
        !$validator->domainName($host, array('tld' => false))
    ) {
        set_page_message($validator->getLastValidationMessages(), 'error');
        return false;
    } else {
        $host .= '.';
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
    list($domainId) = get_domain_default_props($_SESSION['user_id']);

    if ($itemType == 'normal') {
        $query = 'SELECT `domain_id`, `domain_name` FROM `domain` WHERE `domain_id` = ?';
        $stmt = exec_query($query, $domainId);

        if (!$stmt->rowCount() || $stmt->fields['domain_id'] !== $itemId) {
            set_page_message(tr('Your are not the owner of this domain'), 'error');
            redirectTo('mail_external.php');
        }

        $itemName = $stmt->fields['domain_name'];
    } elseif ($itemType == 'alias') {
        $query = "SELECT `domain_id`, `alias_name` FROM `domain_aliasses` WHERE `alias_id` = ? AND `domain_id` = ?";
        $stmt = exec_query($query, array($itemId, $domainId));

        if (!$stmt->rowCount()) {
            set_page_message(tr('Your are not the owner of this domain alias'), 'error');
            redirectTo('mail_external.php');
        }

        $itemName = $stmt->fields['alias_name'];
    } else {
        set_page_message(tr('Wrong request.'), 'error');
        redirectTo('mail_external.php');
        exit;
    }

    return array(
        'main_domain_id' => $domainId,
        'item_id' => $itemId,
        'item_name' => $itemName,
        'item_type' => $itemType
    );
}

/**
 * Returns data
 *
 * @throws iMSCP_Exception_Database
 * @param array $itemData Item data (item id and item type)
 * @return void
 */
function client_editExternalMailServerEntries($itemData)
{
    $verifiedData = _client_getVerifiedData($itemData[0], $itemData[1]);

    if (!empty($_POST)) {
        // Preparing entries stack
        $data['name'] = $_POST['name'] ? : array();
        $data['priority'] = $_POST['priority'] ? : array();
        $data['host'] = $_POST['host'] ? : array();

        iMSCP_Events_Manager::getInstance()->dispatch(
            iMSCP_Events::onBeforeAddExternalMailServer, array('externalMailServerEntries' => &$data)
        );

        $entriesCount = count($data['name']);
        $error = false;

        // Validate all entries
        for ($index = 0; $index < $entriesCount; $index++) {
            if (isset($data['priority'][$index]) && isset($data['host'][$index])) {
                if (!_client_validateDnsMxRecord(
                    $data['name'][$index], $data['priority'][$index], $data['host'][$index], $verifiedData)
                ) {
                    $error = true;
                }
            } else { // Not all expected data were received
                set_page_message(tr('Wrong request: all expected data were not received.'), 'error');
                redirectTo('mail_external.php');
            }
        }

        // Add DNS entries into database
        if (!$error) {
            /** @var $db iMSCP_Database */
            $db = iMSCP_Registry::get('db');
            $db->beginTransaction(); // All successfully inserted or nothing

            try {
                $dnsEntriesIds = '';
                for ($index = 0; $index < $entriesCount; $index++) {


                    if ($data['to_delete']) { // Entry is marked as 'to_delete'
                        // TODO
                    } elseif ($data['to_update']) { // Entry is marked as 'to_update'
                        // TODO
                    } else {
                        // Try to insert MX record into the domain_dns database table
                        $query = '
                          INSERT INTO `domain_dns` (
                            `domain_id`, `alias_id`, `domain_dns`, `domain_class`, `domain_type`, `domain_text`, `protected`
                          ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?
                          )
                        ';
                        exec_query(
                            $query,
                            array(
                                $verifiedData['main_domain_id'],
                                ($verifiedData['item_type'] == 'alias') ? $verifiedData['item_id'] : 0,
                                $data['name'][$index] . '.',
                                'IN',
                                'MX',
                                "{$data['priority'][$index]}\t" . encode_idna($data['host'][$index]),
                                'yes' // Protect the entry against deletion from the custom dns interface
                            )
                        );

                        // Grab unique id of the domain dns entry that was inserted and add it to the id list
                        $dnsEntriesIds .= ',' . $db->insertId();
                    }
                }

                /** @var $cfg iMSCP_Config_Handler_File */
                $cfg = iMSCP_Registry::get('config');

                if ($verifiedData['item_type'] == 'normal') {
                    $query = '
                      UPDATE
                        `domain` SET `external_mail` = ?, `domain_status` = ?, `external_mail_dns_ids` = ?
                      WHERE
                        `domain_id` = ?
                    ';
                    exec_query($query, array('on', $cfg->ITEM_DNSCHANGE_STATUS, ltrim($dnsEntriesIds, ','), $verifiedData['item_id']));
                } else {
                    $query = '
                      UPDATE
                        `domain_aliasses` SET `external_mail` = ?, `alias_status` = ?, `external_mail_dns_ids` = ?
                      WHERE
                        `alias_id` = ?
                    ';
                    exec_query($query, array('on', $cfg->ITEM_DNSCHANGE_STATUS, ltrim($dnsEntriesIds, ','), $verifiedData['item_id']));
                }


                $db->commit(); // Commit the transaction - All data will be now added into the database

                iMSCP_Events_Manager::getInstance()->dispatch(
                    iMSCP_Events::onAfterAddExternalMailServer, array('externalMailServerEntries' => &$data)
                );

                send_request(); // Ask the daemon to trigger backend dispatcher
                set_page_message(tr('External mail server successfully scheduled for modification.'), 'success');
                redirectTo('mail_external.php');
            } catch (iMSCP_Exception_Database $e) {
                $db->rollBack();

                if ($e->getCode() === 23000) { // Entry already exists in domain_dns table or is defined twice in entries stack?
                    set_page_message(
                        tr(
                            'The MX record "%s IN MX %s %s" already exists or is defined twice below.',
                            $data['name'][$index],
                            $data['priority'][$index],
                            $data['host'][$index]
                        ),
                        'error'
                    );
                } else { // Another error?
                    throw new iMSCP_Exception_Database($e->getMessage(), $e->getQuery(), $e->getCode(), $e);
                }
            }
        }
    } else { // Getting data from the database

        if ($verifiedData['item_type'] == 'normal') { // Retrieving mx entries for the domain external mail server
            $query = "SELECT `external_mail_dns_ids` FROM `domain` WHERE `domain_id` = ?";
        } else { // Retrieving mx entries for the domain alias external mail server
            $query = "SELECT `external_mail_dns_ids` FROM `domain_aliasses` WHERE `alias_id` = ?";
            ;
        }

        $stmt = exec_query($query, $verifiedData['item_id']);

        if ($stmt->rowCount()) {
            $query = 'SELECT * FROM `domain_dns` WHERE `domain_dns_id` IN(' . $stmt->fields['external_mail_dns_ids'] . ')';
            $stmt = exec_query($query);

            if ($stmt->rowCount()) {
                $data = array();
                while (!$stmt->EOF) {
                    $data['entry_id'] = $stmt->fields['domain_dns_id'];
                    $data['name'][] = rtrim($stmt->fields['domain_dns'], '.');
                    list($priority, $host) = explode("\t", $stmt->fields['domain_text'], 2);
                    $data['priority'][] = trim($priority);
                    $data['host'][] = trim($host);

                    $stmt->moveNext();
                }
            } else { // DNS entries pointed by domain or domain alias were not found (should never occurs
                set_page_message('Unable to retrieve DNS MX records entries associated to your external mail server. Please, contact your reseller', 'error');
                redirectTo('mail_external.php');
                exit; // Only to make some IDE happy
            }
        } else {
            set_page_message('An unexpected error occurred, please contact your reseller', 'error');
            redirectTo('external_mail.php'); // No domain or domain alias data found (should never occurs)
            exit; // Only to make some IDE happy
        }
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

    $idnItemName = decode_idna($verifiedData['item_name']);
    $entriesCount = isset($data['name']) ? count($data['name']) : 0;
    $domainMx = tr('Domain MX');
    $wildcardMx = tr('Wildcard MX');

    for ($index = 0; $index < $entriesCount; $index++) {
        // Generates html option elements for the names
        foreach (array($domainMx => $idnItemName, $wildcardMx => "*.$idnItemName") as $optionName => $optionValue) {
            $tpl->assign(
                array(
                    'INDEX' => $index,
                    'OPTION_VALUE' => $optionValue,
                    'SELECTED' => ($optionValue == $data['name'][$index]) ? ' selected' : '',
                    'OPTION_NAME' => $optionName
                )
            );
            $tpl->parse('NAME_OPTIONS', '.name_options');
        }

        // Generates html option elements for the MX priority
        foreach (array('10', '15', '20', '25', '30') as $option) {
            $tpl->assign(
                array(
                    'INDEX' => $index,
                    'OPTION_VALUE' => $option,
                    'SELECTED' => ($option == $data['priority'][$index]) ? ' selected' : ''
                )
            );
            $tpl->parse('PRIORITY_OPTIONS', '.priority_options');
        }

        $tpl->assign(
            array(
                'INDEX' => $index,
                'DOMAIN' => $idnItemName,
                'WILDCARD' => "*.$idnItemName",
                'HOST' => $data['host'][$index],
                'ENTRY_ID' => $data['entry_id'],
                'ITEM_ID' => $verifiedData['item_id'] . ';' . $verifiedData['item_type']
            )
        );

        $tpl->parse('ITEM_ENTRIES', '.item_entries');
        $tpl->assign('NAME_OPTIONS', ''); // Reset name options stack for next record
        $tpl->assign('PRIORITY_OPTIONS', ''); // Reset priority options stack for next record
    }
}


/***********************************************************************************************************************
 * Main
 */
// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('external_mail') || !customerHasFeature('mail')) {
    redirectTo('index.php');
}

if (!isset($_REQUEST['id']) || count($itemData = explode(';', $_REQUEST['id'], 2)) < 2) {
    set_page_message(tr('Wrong request'), 'error');
    redirectTo('mail_external.php');
}

if ($_POST) {
    echo '<pre>';
    print_r($_POST);
    exit;
}

$tpl = iMSCP_Registry::set('templateEngine', new iMSCP_pTemplate());
$tpl->define_dynamic(
    array(
        'layout' => 'shared/layouts/ui.tpl',
        'page' => 'client/mail_external_edit.tpl',
        'page_message' => 'layout',
        'item_entries' => 'page',
        'name_options' => 'item_entries',
        'priority_options' => 'page',
    )
);

$tpl->assign(
    array(
        'TR_PAGE_TITLE' => tr('i-MSCP - Client / Mail Account / Add external mail server'),
        'THEME_CHARSET' => tr('encoding'),
        'ISP_LOGO' => layout_getUserLogo(),
        'TR_TITLE_RELAY_MAIL_USERS' => tr('Add external mail server entry'),
        'TR_MX_TYPE' => tr('Type'),
        'TR_DOMAIN_MX' => tr('Domain MX'),
        'TR_WILDCARD_MX' => tr('Wildcard MX'),
        'TR_PRIORITY' => tr('Priority'),
        'TR_HOST' => tr('External Mail Host'),
        'TR_ADD_NEW_ENTRY' => tr('Add a new entry'),
        'TR_REMOVE_LAST_ENTRY' => tr('Remove last entry'),
        'TR_RESET_ENTRIES' => tr('Reset entries'),
        'TR_CANCEL' => tr('Cancel'),
        'TR_UPDATE' => tr('Update'),
        'TR_MX_TYPE_TOOLTIP' =>
        tr('Domain MX: Setup an MX record to relay mail of your entire domain (including subdomains) to an external mail server. In such case, the mail host provided by imscp is deactivated.') .
            '<br /><br />' .
            tr('Wildcard MX: Setup an MX record for inexistent subdomains, for which an external mail server can handle mail. In such case the mail host provided by imscp keeps active.')
    )
);

generateNavigation($tpl);

client_editExternalMailServerEntries($itemData);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();