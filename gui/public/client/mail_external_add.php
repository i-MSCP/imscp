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
 * @package     Client_Mail
 * @copyright   2010-2013 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
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
 * @param string &$name Name
 * @param int $priority MX preference
 * @param string $host Mail host
 * @param $verifiedData
 * @return bool TRUE if the given MX DNS record is valid, FALSE otherwise
 */
function _client_validateDnsMxRecord($name, $priority, $host, $verifiedData)
{
    $validator = iMSCP_Validate::getInstance();

    // Should never occurs since we are using options stack in HTML form
    $nameTmp = (strpos($name, '*') === 0) ? substr($name, 2) : $name; // strip out the wildcard part (*.) in any
    if (!$validator->assertEquals($verifiedData['item_name'], $nameTmp, tr('Invalid type detected.'))) {
        set_page_message($validator->getLastValidationMessages(), 'error');
        return false;
    }

    //  // Should never occurs since we are using options stack in HTML form
    if (!$validator->assertContains($priority, array('10', '15', '20', '25', '30'), tr('Invalid MX priority.'))) {
        set_page_message($validator->getLastValidationMessages(), 'error');
        return false;
    }

    // Mail host must not be equal to the domain for which it's added
    if (
        !$validator->assertNotEquals(
            $verifiedData['item_name'],
			encode_idna($host),
			tr('The mail host must not be equal to the domain name for which you add it.')
        )
    ) {
        set_page_message($validator->getLastValidationMessages(), 'error');
        return false;
    }

	if(!isValidDomainName($host)) {
		set_page_message(tr('Mail host is not valid.'), 'error');
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

    if ($itemType === 'normal') {
        $query = 'SELECT `domain_id`, `domain_name` AS `name` FROM `domain` WHERE `domain_id` = ?';
        $stmt = exec_query($query, $domainId);

        if (!$stmt->rowCount() || $stmt->fields['domain_id'] !== $itemId) {
            set_page_message(tr('Your are not the owner of this domain.'), 'error');
            redirectTo('mail_external.php');
        }
    } elseif ($itemType === 'alias') {
        $query = "SELECT `domain_id`, `alias_name` AS `name` FROM `domain_aliasses` WHERE `alias_id` = ? AND `domain_id` = ?";
        $stmt = exec_query($query, array($itemId, $domainId));

        if (!$stmt->rowCount()) {
            set_page_message(tr('Your are not the owner of this domain alias.'), 'error');
            redirectTo('mail_external.php');
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
 * @throws iMSCP_Exception_Database
 * @param array $item Item data (item id and item type)
 * @return void
 */
function client_addExternalMailServerEntries($item)
{
    $verifiedData = _client_getVerifiedData($item[0], $item[1]);

    if (!empty($_POST)) {
        // Preparing entries stack
        $data['name'] = (isset($_POST['name'])) ? $_POST['name'] : array();
        $data['priority'] = (isset($_POST['priority'])) ? $_POST['priority'] : array();
        $data['host'] = (isset($_POST['host'])) ? $_POST['host'] : array();

        iMSCP_Events_Manager::getInstance()->dispatch(
            iMSCP_Events::onBeforeAddExternalMailServer, array('externalMailServerEntries' => &$data)
        );

        $entriesCount = count($data['name']);
        $wildcardMxOnly = true;
        $error = false;

        // Validate all entries
        for ($index = 0; $index < $entriesCount; $index++) {
            if (isset($data['name'][$index]) && isset($data['priority'][$index]) && isset($data['host'][$index])) {
                $data['host'][$index] = strtolower(rtrim($data['host'][$index], '.'));

                if (!_client_validateDnsMxRecord(
                    $data['name'][$index], $data['priority'][$index], $data['host'][$index], $verifiedData)
                ) {
                    $error = true;
                }

                if(strpos($data['name'][$index], '*') === false) {
                    $wildcardMxOnly = false;
                }
            } else { // Not all expected data were received
				showBadRequestErrorPage();
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
                    // Try to insert MX record into the domain_dns database table
                    $query = '
                      INSERT INTO `domain_dns` (
                        `domain_id`, `alias_id`, `domain_dns`, `domain_class`, `domain_type`, `domain_text`, `owned_by`
                      ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?
                      )
                    ';
                    exec_query(
                        $query,
                        array(
                            $verifiedData['domain_id'],
                            ($verifiedData['item_type'] == 'alias') ? $verifiedData['item_id'] : 0,
                            $data['name'][$index] . '.',
                            'IN',
                            'MX',
                            "{$data['priority'][$index]}\t" . encode_idna($data['host'][$index]) . '.',
                            'ext_mail_feature'
                        )
                    );

                    $dnsEntriesIds .= ',' . $db->insertId();
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
                    exec_query(
                        $query,
                        array(
                            ($wildcardMxOnly) ? 'wildcard' : 'on',
                            $cfg->ITEM_TOCHANGE_STATUS,
                            ltrim($dnsEntriesIds, ','),
                            $verifiedData['item_id'])
                    );
                } else {
                    $query = '
                      UPDATE
                        `domain_aliasses` SET `external_mail` = ?, `alias_status` = ?, `external_mail_dns_ids` = ?
                      WHERE
                        `alias_id` = ?
                    ';
                    exec_query(
                        $query,
                        array(
                            ($wildcardMxOnly) ? 'wildcard' : 'on',
                            $cfg->ITEM_TOCHANGE_STATUS,
                            ltrim($dnsEntriesIds, ','),
                            $verifiedData['item_id'])
                    );
                }

                $db->commit(); // Commit the transaction - All data will be now added into the database

                iMSCP_Events_Manager::getInstance()->dispatch(
                    iMSCP_Events::onAfterAddExternalMailServer, array('externalMailServerEntries' => &$data)
                );

                send_request(); // Ask the daemon to trigger backend dispatcher
                set_page_message(tr('External mail server entries successfully scheduled for addition.'), 'success');
                redirectTo('mail_external.php');
            } catch (iMSCP_Exception_Database $e) {
                $db->rollBack();

                if ($e->getCode() === 23000) { // Entry already exists in domain_dns table or is defined twice in entries stack?
                    set_page_message(
                        tr(
                            'The entry %s already exists or is defined twice below.',
                            "<strong>{$data['name'][$index]} IN MX {$data['priority'][$index]} {$data['host'][$index]}</strong>"
                        ),
                        'error'
                    );
                } else { // Another error?
                    throw new iMSCP_Exception_Database($e->getMessage(), $e->getQuery(), $e->getCode(), $e);
                }
            }
        }
    } else {
        $data['name'][] = $verifiedData['item_name'];
        $data['priority'][] = '10';
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
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');
    $selectedOption = $cfg->HTML_SELECTED;
    $idnItemName = $verifiedData['item_name'];
    $entriesCount = isset($data['name']) ? count($data['name']) : 0;
    $domainMx = tr('Domain');
    $wildcardMx = tr('Wildcard');

    $tpl->assign(
        array(
            'TR_PAGE_TITLE' => tr('Client / Email / External Mail Server / Add External Mail Server for {DOMAIN_UTF8}'),
            'ISP_LOGO' => layout_getUserLogo(),
            'TR_MX_TYPE' => tr('Type'),
            'TR_DOMAIN' => tr('Domain'),
            'DOMAIN_UTF8' => decode_idna($idnItemName),
            'DOMAIN' => $idnItemName,
            'TR_WILDCARD' => tr('Wildcard'),
            'WILDCARD' => "*.$idnItemName",
            'TR_PRIORITY' => tr('Priority'),
            'TR_HOST' => tr('External Mail Host'),
            'TR_ADD_NEW_ENTRY' => tr('Add a new entry'),
            'TR_REMOVE_LAST_ENTRY' => tr('Remove last entry'),
            'TR_TRIGGER_REMOVE_ALERT' => tr('You cannot remove this entry.'),
            'TR_RESET_ENTRIES' => tr('Reset entries'),
            'TR_CANCEL' => tr('Cancel'),
            'TR_ADD' => tr('Add'),
            'TR_MX_TYPE_TOOLTIP' =>
            tr('Domain: Setup an MX record to relay mail of your entire domain (including subdomains) to an external mail server.') .
                '<br /><br />' .
                tr('Wildcard: Setup an MX record for inexistent subdomains, for which an external mail server can handle mail.') .
                '<br /><br />' .
                tr('Note: You can mix these options.'),
            'ITEM' => $verifiedData['item_id'] . ';' . $verifiedData['item_type']
        )
    );

    for ($index = 0; $index < $entriesCount; $index++) {
        // Generates html option elements for the names
        foreach (array($domainMx => $idnItemName, $wildcardMx => "*.$idnItemName") as $optionName => $optionValue) {
            $tpl->assign(
                array(
                    'INDEX' => $index,
                    'OPTION_VALUE' => $optionValue,
                    'SELECTED' => ($optionValue == $data['name'][$index]) ? $selectedOption : '',
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
                    'SELECTED' => ($option == $data['priority'][$index]) ? $selectedOption : '',
                    'OPTION_NAME' => $option
                )
            );
            $tpl->parse('PRIORITY_OPTIONS', '.priority_options');
        }

        $tpl->assign(
            array(
                'INDEX' => $index,
                'HOST' => $data['host'][$index],
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

check_login('user');

if (customerHasFeature('external_mail')) {
    if (isset($_REQUEST['item']) && count($item = explode(';', $_REQUEST['item'], 2)) == 2) {
        $tpl = iMSCP_Registry::set('templateEngine', new iMSCP_pTemplate());
        $tpl->define_dynamic(
            array(
                'layout' => 'shared/layouts/ui.tpl',
                'page' => 'client/mail_external_add.tpl',
                'page_message' => 'layout',
                'item_entries' => 'page',
                'name_options' => 'item_entries',
                'priority_options' => 'item_entries',
            )
        );

        generateNavigation($tpl);
        client_addExternalMailServerEntries($item);
        generatePageMessage($tpl);
        $tpl->parse('LAYOUT_CONTENT', 'page');
        iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
        $tpl->prnt();
        unsetMessages();
    } else {
		showBadRequestErrorPage();
    }
} else {
    showBadRequestErrorPage();
}
