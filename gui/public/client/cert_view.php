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
 * @param int $domainId Domain entity unique identifier
 * @param string $domainType Domain entity type to update (dmn, als,sub, alssub)
 * @return array
 */
function client_getFullName($domainId, $domainType)
{
	switch ($domainType) {
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

	$stmt = exec_query($query, $domainId);

	$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

	return array($row['name'], $row['domain_admin_id']);
}

/**
 * Update status for the given domain entity
 *
 * @param string $domainType Domain entity type to update (dmn, als,sub, alssub)
 * @param int $domainId Domain entity unique identifier
 */
function client_updateEntityStatus($domainType, $domainId)
{
	switch ($domainType) {
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

	exec_query($query, array('tochange', $domainId));
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $domainId Domain entity unique identifier
 * @param string $domainType Domain entity type
 */
function client_generatePage($tpl, $domainId, $domainType)
{
	list($name, $owner) = client_getFullName($domainId, $domainType);

	if ($owner == $_SESSION['user_id']) {
		if (customerHasFeature('ssl')) {
			if (isset($_POST['send'])) {
				// Validate private key
				$privateKey = new Crypt_RSA();

				if (!empty($_POST['private_key'])) {
					if (!empty($_POST['private_key'])) {
						$privateKey->setPassword(clean_input($_POST['passphrase']));
					}

					if (!$privateKey->loadKey(clean_input($_POST['private_key']))) {
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
					if (!empty($_POST['ca_bundle'])) {
						$caBundle = trim(clean_input($_POST['ca_bundle']));

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
									set_page_message(
										tr('A certificate in your CA bundle is missing or invalid.'), 'error'
									);
									break;
								}
							}
						} else {
							set_page_message(tr('Invalid CA Bundle.'), 'error');
						}
					}

					if (!Zend_Session::namespaceIsset('pageMessages')) {
						$certificate = '';
						if (!empty($_POST['certificate'])) {
							if (preg_match("/^$certificatPattern$/s", trim(clean_input($_POST['certificate'])), $match)) {
								$certificate = $match[0];

								// Check certificate
								if (!$x509->loadX509($certificate)) {
									set_page_message(tr('Your certificate is not valid.'), 'error');
								} elseif(! $x509->validateSignature(false)) {
									set_page_message(
										tr('Your certificate is not valid or the CA bundle is missing.'), 'error'
									);
								} elseif(!$x509->validateDate()) {
									set_page_message(tr('Your certificate is expired'), 'error');
								} elseif (!openssl_x509_check_private_key($certificate, $privateKey)) {
									set_page_message(tr("The certificate doesn't match with the private key."), 'error');
								}

								// TODO check for CN
							} else {
								set_page_message(tr('Your certificate is not valid.'), 'error');
							}
						} else {
							set_page_message(tr('The certificate field cannot be empty.'), 'error');
						}

						if (!Zend_Session::namespaceIsset('pageMessages')) {
							// Data normalization
							$privateKey = str_replace("\r\n", "\n", $privateKey) . PHP_EOL;
							$certificate = str_replace("\r\n", "\n", $certificate) . PHP_EOL;
							$caBundle = str_replace("\r\n", "\n", $caBundle);

							$db = iMSCP_Database::getInstance();

							try {
								$db->beginTransaction();

								exec_query(
									'DELETE FROM ssl_certs WHERE domain_id = ? AND domain_type = ? ',
									array($domainId, $domainType)
								);

								exec_query(
									'
									  INSERT INTO ssl_certs (
										domain_id, domain_type, private_key, certificate, ca_bundle, status
									  ) VALUES (
										?, ?, ?, ?, ?, ?
									  )
									',
									array($domainId, $domainType, $privateKey, $certificate, $caBundle, 'toadd')
								);

								client_updateEntityStatus($domainType, $domainId);

								$db->commit();

								send_request();
								set_page_message(
									tr('SSL Certificate successfully scheduled for addition or update.'), 'success'
								);
								write_log(
									sprintf(
										'%s added or updated an SSL certificate (%s)', $_SESSION['user_logged'], $name
									),
									E_USER_NOTICE
								);

								redirectTo("cert_view.php?domain_id=$domainId&domain_type=$domainType");
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
						'UPDATE ssl_certs SET status = ? WHERE domain_id = ? AND domain_type = ? ',
						array('todelete', $domainId, $domainType)
					);

					client_updateEntityStatus($domainType, $domainId);

					$db->commit();

					send_request();

					set_page_message(tr('Certificate successfully scheduled for deletion.'), 'success');
					write_log(
						sprintf('%s deleted SSL certificate (%s)', $_SESSION['user_logged'], $name), E_USER_NOTICE
					);

					redirectTo('domains_manage.php');
				} catch (iMSCP_Exception_Database $e) {
					$db->rollBack();
					throw new iMSCP_Exception_Database(
						sprintf('Unable to delete SSL certificate: %s', $e->getMessage())
					);
				}
			}
		}

		$stmt = exec_query(
			'SELECT * FROM ssl_certs WHERE domain_id = ? AND domain_type = ?', array($domainId, $domainType)
		);

		if ($stmt->rowCount()) {
			if (customerHasFeature('ssl')) {
				$tpl->assign('TR_DYNAMIC_TITLE', tr('Edit SSL certificate'));
			} else {
				$tpl->assign('TR_DYNAMIC_TITLE', tr('Show SSL certificate'));
				$tpl->assign('CERT_ENABLE', '');
				set_page_message(tr('SSL feature is not available. You can only view your certificate.'), 'warning');
			}

			if (customerHasFeature('ssl') && !empty($_POST)) {
				$privateKey = tohtml($_POST['private_key']);
				$certificate = tohtml($_POST['certificate']);
				$caBundle = tohtml($_POST['ca_bundle']);
				$status = tr('N/A');
			} else {
				$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
				$privateKey = tohtml($row['private_key']);
				$certificate = tohtml($row['certificate']);
				$caBundle = tohtml($row['ca_bundle']);

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
				$privateKey = tohtml($_POST['private_key']);
				$certificate = tohtml($_POST['certificate']);
				$caBundle = tohtml($_POST['ca_bundle']);
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
					'CERTIFICATE' => tohtml(trim($certificate)),
					'CA_BUNDLE' => tohtml(trim($caBundle)),
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

if (
	!isset($_GET['domain_id']) || !isset($_GET['domain_type']) ||
	!in_array($_GET['domain_type'], array('dmn', 'als', 'sub', 'alssub'))
) {
	showBadRequestErrorPage();
	exit;
} else {
	$domainId = intval($_GET['domain_id']);
	$domainType = clean_input($_GET['domain_type']);
}

$tpl->assign(
	array(
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_PAGE_TITLE' => tr('Client / Domains / SSL Certificate'),
		'TR_CERTIFICATE_DATA' => tr('Certificate data'),
		'TR_CERT_FOR' => tr('Common name'),
		'TR_STATUS' => tr('Status'),
		'TR_PASSWORD' => tr('Private key passphrase if any'),
		'TR_PRIVATE_KEY' => tr('Private key'),
		'TR_CERTIFICATE' => tr('Certificate'),
		'TR_CA_BUNDLE' => tr('CA bundle'),
		'TR_DELETE' => tr('Delete'),
		'TR_SAVE' => tr('Save'),
		'TR_CANCEL' => tr('Cancel'),
		'ID' => tohtml($domainId),
		'TYPE' => tohtml($domainType)
	)
);

generateNavigation($tpl);
client_generatePage($tpl, $domainId, $domainType);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
