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
 * @author		Sascha Bay <worst.case@gmx.de>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('external_mail') || !customerHasFeature('mail')) {
    redirectTo('index.php');
}

if (isset($_GET['id']) && $_GET['id'] !== '') {
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$item_id = $_GET['id'];
	$match = array();
	if (preg_match("/(\d+);(normal|alias)/", $item_id, $match) == 1) {
		$item_id = $match[1];
		$item_type = $match[2];
		if ($item_type === 'normal' || $item_type === 'alias') {
			$delete_status = false;
			$dns_name = '';
			$dmn_id = get_user_domain_id($_SESSION['user_id']);
			switch ($item_type) {
				case 'normal':
					if ($item_id === $dmn_id) {
						$query = "
							SELECT
								`domain_name`, `external_mail_dns_ids`
							FROM
								`domain`
							WHERE
								`domain_id` = ?
						";
						$rs = exec_query($query, array($item_id));

						if ($rs->recordCount() > 0) {
							$dns_name = $rs->fields['domain_name'];
							$dns_entry_ids = array();
							$dns_entry_ids = explode(',', $rs->fields['external_mail_dns_ids']);

							if (count($dns_entry_ids) > 0) {
								// Delete DNS record from the database
								$query = "
									DELETE FROM
										`domain_dns`
									WHERE
										`domain_dns_id` IN(".$rs->fields['external_mail_dns_ids'].")
								";
								$rs = exec_query($query);

								$query = "
									UPDATE
										`domain`
									SET
										`domain`.`external_mail` = ?,
										`domain`.`external_mail_status` = ?,
										`domain`.`external_mail_dns_ids` = ''
									WHERE
										`domain`.`domain_id` = ?
								";
								exec_query($query, array('off', $cfg->ITEM_DELETE_STATUS, $item_id));

								$delete_status = true;
							} else {
								redirectTo('mail_external.php');
							}
						} else {
							redirectTo('mail_external.php');
						}
					} else {
						set_page_message(tr('You are not allowed to remove this external mail entry.'), 'error');
						redirectTo('mail_external.php');
					}
					break;
				case 'alias':
					$query = "
						SELECT
							`alias_name`, `external_mail_dns_ids`
						FROM
							`domain_aliasses`
						WHERE
							`domain_id` = ?
						AND
							`alias_id` = ?
						";
					$rs = exec_query($query, array($dmn_id, $item_id));

					if ($rs->recordCount() > 0) {
						$dns_name = $rs->fields['alias_name'];
						$dns_entry_ids = array();
						$dns_entry_ids = explode(',', $rs->fields['external_mail_dns_ids']);

						if (count($dns_entry_ids) > 0) {
							// Delete DNS record from the database
							$query = "
								DELETE FROM
									`domain_dns`
								WHERE
									`domain_dns_id` IN(".$rs->fields['external_mail_dns_ids'].")
							";
							$rs = exec_query($query);

							$query = "
								UPDATE
									`domain_aliasses`
								SET
									`domain_aliasses`.`external_mail` = ?,
									`domain_aliasses`.`external_mail_status` = ?,
									`domain_aliasses`.`external_mail_dns_ids` = ''
								WHERE
									`domain_aliasses`.`domain_id` = ?
								AND
									`domain_aliasses`.`alias_id` = ?
							";
							exec_query($query, array('off', $cfg->ITEM_DELETE_STATUS, $dmn_id, $item_id));

							$delete_status = true;
						} else {
							redirectTo('mail_external.php');
						}
					} else {
						set_page_message(tr('You are not allowed to remove this external mail entry.'), 'error');
						redirectTo('mail_external.php');
					}
					break;
				default :
					redirectTo('mail_external.php');
			}

			if ($delete_status === true) {
				// Send request to i-MSCP daemon
				send_request();

				write_log(
					$_SESSION['user_logged'] . ': deletes external mail server records of domain ' . $dns_name, E_USER_NOTICE
				);

				set_page_message(tr('External mail servers scheduled for deletion.'), 'success');
			}
		} else {
		            redirectTo('mail_external.php');
		}
	}
}

//  Back to the main page
redirectTo('mail_external.php');