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
 * @copyright   2010-2015 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Delete one or many external mail server related entries
 *
 * @throws iMSCP_Exception_Database
 * @param array $items Item(s) to delete
 * @param bool $postRequest Flag indicating whether POST data were received
 * @return void
 */
function client_deleteExternalMailServers($items, $postRequest)
{
	if(isset($items['normal']) || isset($items['alias'])) {
		$domainId = get_user_domain_id($_SESSION['user_id']);

		/** @var $db iMSCP_Database */
		$db = iMSCP_Database::getInstance();

		try {
			$db->beginTransaction();

			$numberDeletedEntries = 0;

			if(!empty($items['normal'])) {
				$itemId = array_shift($items['normal']);

				if($itemId == $domainId) {
					$stmt = exec_query('SELECT external_mail_dns_ids FROM domain WHERE domain_id = ?', $domainId);

					if($stmt->rowCount()) {
						$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

						if($row['external_mail_dns_ids'] != '') {
							exec_query(
								'
									UPDATE
										domain_dns
									SET
										domain_dns_status = ?
									WHERE
										domain_dns_id IN(' . $row['external_mail_dns_ids'] . ')
								',
								'todelete'
							);

							exec_query(
								'
									UPDATE
										domain
									SET
										external_mail = ?, domain_status = ?, external_mail_dns_ids = ?
									WHERE
										domain_id = ?
								',
								array('off', 'tochange', null, $itemId)
							);

							$numberDeletedEntries++;
						}
					}
				}
			}

			if(!empty($items['alias'])) {
				foreach((array)$items['alias'] as $itemId) {
					$stmt = exec_query(
						'
							SELECT
								alias_name, external_mail_dns_ids
							FROM
								domain_aliasses
							WHERE
								alias_id = ?
							AND
								domain_id = ?
						',
						array($itemId, $domainId)
					);

					if($stmt->rowCount()) {
						$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

						if($row['external_mail_dns_ids'] != '') {
							exec_query(
								'
									UPDATE
										domain_dns
									SET
										domain_dns_status = ?
									WHERE
										domain_dns_id IN(' . $row['external_mail_dns_ids'] . ')
								',
								'todelete'
							);

							exec_query(
								'
									UPDATE
										domain_aliasses
									SET
										external_mail = ?, alias_status = ?, external_mail_dns_ids = ?
									WHERE
										alias_id = ?
									AND
										domain_id = ?
								',
								array('off', 'tochange', null, $itemId, $domainId)
							);

							$numberDeletedEntries++;
						}
					}
				}
			}

			$db->commit();

			send_request();

			if($numberDeletedEntries > 0) {
				set_page_message(tr('External mail server successfully scheduled for deactivation.'), 'success');
			} else {
				set_page_message(tr('Nothing has been scheduled for deactivation.'), 'error');
			}
		} catch(iMSCP_Exception_Database $e) {
			$db->rollBack();
			throw $e;
		}
	} else {
		if($postRequest) {
			set_page_message(tr('You must select a least one item to deactivate.'), 'warning');
		} else {
			showBadRequestErrorPage();
		}
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

// If the feature is disabled, redirects in silent way
if(customerHasFeature('external_mail')) {
	if(!empty($_POST)) {
		$items['normal'] = (isset($_POST['normal'])) ? $_POST['normal'] : null;
		$items['alias'] = (isset($_POST['alias'])) ? $_POST['alias'] : null;
		$postRequest = true;
	} else if(isset($_GET['item']) && count($item = explode(';', $_GET['item'], 2)) == 2) {
		$items[$item[1]][] = $item[0];
		$postRequest = false;
	} else {
		showBadRequestErrorPage();
		exit;
	}

	client_deleteExternalMailServers($items, $postRequest);
	redirectTo('mail_external.php');
} else {
	showBadRequestErrorPage();
}

redirectTo('index.php');
