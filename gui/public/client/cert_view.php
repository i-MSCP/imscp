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
 * @copyright   2010-2014 by i-MSCP team
 * @author      Daniel Andreca <sci2tech@gmail.com>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/***********************************************************************************************************************
 * Script functions
 */

/**
 * Get full name and owner id for the given domain entity
 *
 * @param string $type Domain entity type to update (dmn, als,sub, alssub)
 * @param int $id Domain entity unique identifier
 * @return array
 */
function client_getFullName($type, $id)
{
	switch ($type) {
		case 'dmn':
			$query = 'SELECT domain_name AS name, domain_admin_id FROM domain WHERE domain_id = ?';
			break;
		case 'als':
			$query = '
                SELECT
                  alias_name AS name, domain_admin_id
                FROM
                  domain_aliasses
                LEFT JOIN
                  domain USING(domain_id)
                WHERE alias_id = ?
            ';
			break;
		case 'sub':
			$query = "
              SELECT
                CONCAT(subdomain_name, '.', domain_name) AS name, domain_admin_id
              FROM
                subdomain
              LEFT JOIN
                domain USING(domain_id)
              WHERE subdomain_id = ?
            ";
			break;
		default:
			$query = "
              SELECT
                CONCAT(subdomain_alias_name, '.', alias_name) AS name, domain_admin_id
              FROM
                subdomain_alias
              INNER JOIN
                domain_aliasses USING(alias_id)
              INNER JOIN domain
                USING(domain_id)
              WHERE
                subdomain_alias_id = ?
            ";
			break;
	}

	$stmt = exec_query($query, array($id));

	$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

	return array($row['name'], $row['domain_admin_id']);
}

/**
 * Update status for the given domain entity
 *
 * @param string $type Domain entity type to update (dmn, als,sub, alssub)
 * @param int $id Domain entity unique identifier
 */
function client_updateEntityStatus($type, $id)
{
	switch ($type) {
		case 'dmn':
			$query = 'UPDATE domain SET domain_status = ? WHERE domain_id = ?';
			break;
		case 'als':
			$query = 'UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?';
			break;
		case 'sub':
			$query = 'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?';
			break;
		default:
			$query = 'UPDATE subdomain_alias SET subdomain_alias_status = ? WHERE subdomain_alias_id = ?';
	}

	exec_query($query, array('tochange', $id));
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $id Domain entity unique identifier
 * @param string $type Domain entity type
 */
function client_generatePage($tpl, $id, $type)
{
	list($name, $owner) = client_getFullName($type, $id);

	if ($owner == $_SESSION['user_id']) {
		if (customerHasFeature('ssl')) {
			if (isset($_POST['send'])) {
				// Validate private key
				$privateKey = new Crypt_RSA();

				if (!empty($_POST['key_cert'])) {
					if (!empty($_POST['key_cert'])) {
						$privateKey->setPassword(clean_input($_POST['passphrase']));
					}

					if (!$privateKey->loadKey(clean_input($_POST['key_cert']))) {
						set_page_message(tr('Invalid private key or passphrase'), 'error');
					}

					// Clear out passphrase
					$privateKey->setPassword();

					// Get unencrypted private key
					$privateKey = $privateKey->getPrivateKey(CRYPT_RSA_PUBLIC_FORMAT_PKCS1);
				} else {
					set_page_message(tr('Private key field cannot be empty'), 'error');
				}

				if (!Zend_Session::namespaceIsset('pageMessages')) {
					$x509 = new File_X509();

					$certificatPattern = '-{5}BEGIN CERTIFICATE-{5}.*?-{5}END CERTIFICATE-{5}';

					// Validate CA Bundle

					$intermediateCertificates = array();

					$caBundle = '';
					if (!empty($_POST['ca_cert'])) {
						$caBundle = trim(clean_input($_POST['ca_cert']));

						if (
							preg_match("/^$certificatPattern$/s", $caBundle) &&
							preg_match_all("/$certificatPattern/s", $caBundle, $intermediateCertificates)
						) {
							$intermediateCertificates = array_reverse($intermediateCertificates[0]);
							$unallowSelfSigned = false;

							// Check each certificate in the chain. Only the top-most certificate can be self-signed
							foreach ($intermediateCertificates as $certificate) {
								if ($x509->loadX509($certificate) && $x509->validateSignature($unallowSelfSigned)) {
									$x509->loadCA($certificate); // Add certificate in CA chain
									$unallowSelfSigned = true;
								} else {
									set_page_message(tr('A certificate in your CA bundle is not valid.'), 'error');
									break;
								}
							}
						} else {
							set_page_message(tr('Invalid CA Bundle.'), 'error');
						}
					}

					if (!Zend_Session::namespaceIsset('pageMessages')) {
						$certificate = '';
						if (!empty($_POST['cert_cert'])) {
							if (preg_match("/^$certificatPattern$/s", trim(clean_input($_POST['cert_cert'])), $match)) {
								$certificate = $match[0];

								if (!$x509->loadCA($certificate)) {
									set_page_message(tr('Certificate is not valid'), 'error');
								}

								if (!openssl_x509_check_private_key($_POST['cert_cert'], $privateKey)) {
									set_page_message(tr("Certificate doesn't corresponds to the private key."), 'error');
								}
							} else {
								set_page_message(tr('Your certificate is not valid.'), 'error');
							}
						} else {
							set_page_message(tr('The certificate field cannot be empty.'), 'error');
						}

						if (!Zend_Session::namespaceIsset('pageMessages')) {
							// Data normalization
							$certificate = str_replace("\r\n", "\n", $certificate) . PHP_EOL;
							$caBundle = str_replace("\r\n", "\n", $caBundle);

							$db = iMSCP_Database::getInstance();

							try {
								$db->beginTransaction();

								exec_query('DELETE FROM ssl_certs WHERE type = ? AND id = ?', array($type, $id));

								exec_query(
									'
									  INSERT INTO ssl_certs (
										id, type, `key`, cert, ca_cert, status
									  ) VALUES (
										?, ?, ?, ?, ?, ?
									  )
									', array($id, $type, $privateKey, $certificate, $caBundle, 'toadd')
								);

								client_updateEntityStatus($type, $id);

								$db->commit();

								send_request();
								set_page_message(
									tr('SSL Certificate successfully scheduled for addition or update.'), 'success'
								);
								write_log(
									sprintf('%s addded/updated SSL certificate (%s)', $_SESSION['user_logged'], $name),
									E_USER_NOTICE
								);

								redirectTo("cert_view.php?id=$id&type=$type");
							} catch (iMSCP_Exception_Database $e) {
								$db->rollBack();
								throw new iMSCP_Exception_Database(
									sprintf('Unable to add/update SSL certificate: %s', $e->getMessage())
								);
							}
						}
					}
				}
			} elseif (isset($_POST['delete'])) {
				$db = iMSCP_Database::getInstance();

				try {
					$db->beginTransaction();

					exec_query(
						'UPDATE ssl_certs SET status = ? WHERE type = ? AND id = ? ',
						array('todelete', $type, $id)
					);

					client_updateEntityStatus($type, $id);

					$db->commit();

					send_request();

					set_page_message(tr('Certificate successfully scheduled for deletion.'), 'success');
					write_log(sprintf('%s deleted SSL certificate (%s)', $_SESSION['user_logged'], $name), E_USER_NOTICE);

					redirectTo('domains_manage.php');
				} catch (iMSCP_Exception_Database $e) {
					$db->rollBack();
					throw new iMSCP_Exception_Database(
						sprintf('Unable to delete SSL certificate: %s', $e->getMessage())
					);
				}
			}
		}

		$stmt = exec_query('SELECT * FROM ssl_certs WHERE type = ? AND id = ?', array($type, $id));

		if ($stmt->rowCount()) {
			if (customerHasFeature('ssl')) {
				$tpl->assign('TR_DYNAMIC_TITLE', tr('Edit SSL certificate'));
			} else {
				$tpl->assign('TR_DYNAMIC_TITLE', tr('View SSL certificate'));
				$tpl->assign('CERT_ENABLE', '');
				set_page_message(tr('SSL feature is not available. You can only view your certificate'), 'warning');
			}

			if (customerHasFeature('ssl') && !empty($_POST)) {
				$privateKey = tohtml($_POST['key_cert']);
				$certificate = tohtml($_POST['cert_cert']);
				$caBundle = tohtml($_POST['ca_cert']);
				$status = tr('N/A');
			} else {
				$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
				$privateKey = tohtml($row['key']);
				$certificate = tohtml($row['cert']);
				$caBundle = tohtml($row['ca_cert']);

				if (in_array($row['status'], array('ok', 'todelete', 'toadd', 'tochange'))) {
					$status = translate_dmn_status($row['status']);
				} else {
					$status = tohtml($row['status']);
				}
			}
		} else {
			$tpl->assign('TR_DYNAMIC_TITLE', tr('Add SSL certificate'));

			if (customerHasFeature('ssl')) {
				$status = tr('No certificate found');
			} else {
				set_page_message(tr('SSL feature is not available.'), 'warning');
			}

			if (customerHasFeature('ssl') && !empty($_POST)) {
				$privateKey = tohtml($_POST['key_cert']);
				$certificate = tohtml($_POST['cert_cert']);
				$caBundle = tohtml($_POST['ca_cert']);
			} else {
				$privateKey = '';
				$certificate = '';
				$caBundle = '';
			}
		}

		if (isset($status)) {
			$tpl->assign(
				array(
					'DOMAIN_NAME' => $name,
					'KEY_CERT' => tohtml(trim($privateKey)),
					'CERT' => tohtml(trim($certificate)),
					'CA_CERT' => tohtml(trim($caBundle)),
					'STATUS' => tohtml($status)
				)
			);
		} else {
			redirectTo('domains_manage.php');
		}
	} else {
		showBadRequestErrorPage();
	}
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/cert_view.tpl',
		'page_message' => 'layout',
		'cert_enable' => 'page'
	)
);

if (!isset($_GET['id']) || !isset($_GET['type']) || !in_array($_GET['type'], array('dmn', 'als', 'sub', 'alssub'))) {
	showBadRequestErrorPage();
	exit;
} else {
	$id = intval($_GET['id']);
	$type = $_GET['type'];
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Domains / SSL Certificates'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_CERTIFICATE_DATA' => tr('Certificate data'),
		'TR_CERT_FOR' => tr('Common name'),
		'TR_STATUS' => tr('Status'),
		'TR_PASSWORD' => tr('Private key passphrase if any'),
		'TR_CERTIFICATE_KEY' => tr('Private key'),
		'TR_CERTIFICATE' => tr('Certificate'),
		'TR_INTERM_CERTIFICATE' => tr('CA bundle'),
		'TR_DELETE' => tr('Delete'),
		'TR_SAVE' => tr('Save'),
		'TR_CANCEL' => tr('Cancel'),
		'ID' => tohtml($id),
		'TYPE' => tohtml($type)
	)
);

generateNavigation($tpl);
client_generatePage($tpl, $id, $type);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
