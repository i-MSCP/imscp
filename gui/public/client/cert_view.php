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
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
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
 * @return string|false Domain name or false if the domain name is not found or not owned by logged-in customer
 */
function client_getDomainName($domainId, $domainType)
{
	static $domainName = null;

	if($domainName === null) {
		switch($domainType) {
			case 'dmn':
				$query = 'SELECT domain_name FROM domain WHERE domain_id = ? AND domain_admin_id = ?';
				break;
			case 'als':
				$query = '
					SELECT
						alias_name AS domain_name
					FROM
						domain_aliasses
					INNER JOIN
						domain USING(domain_id)
					WHERE
						alias_id = ?
					AND
						domain_admin_id = ?
			';
				break;
			case 'sub':
				$query = "
					SELECT
						CONCAT(subdomain_name, '.', domain_name) AS domain_name
					FROM
						subdomain
					INNER JOIN
						domain USING(domain_id)
					WHERE
						subdomain_id = ?
					AND
						domain_admin_id = ?
				";
				break;
			default:
				$query = "
					SELECT
						CONCAT(subdomain_alias_name, '.', alias_name) AS domain_name
					FROM
						subdomain_alias
					INNER JOIN
						domain_aliasses USING(alias_id)
					INNER JOIN domain
						USING(domain_id)
					WHERE
						subdomain_alias_id = ?
					AND
						domain_admin_id = ?
				";
		}

		$stmt = exec_query($query, array($domainId, $_SESSION['user_id']));

		if($stmt->rowCount()) {
			$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
			$domainName = $row['domain_name'];
		} else {
			$domainName = false;
		}
	}

	return $domainName;
}

/**
 * Update status for the given domain
 *
 * @throws iMSCP_Exception_Database
 * @param string $domainType Domain entity type to update (dmn, als,sub, alssub)
 * @param int $domainId Domain entity unique identifier
 * @return void
 */
function client_updateDomainStatus($domainType, $domainId)
{
	switch($domainType) {
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
 * Add or update an SSL certificate
 *
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @param int $domainId domain unique identifier
 * @param string $domainType Domain type (dmn, als, sub, alssub)
 */
function client_addSslCert($domainId, $domainType)
{
	$domainName = client_getDomainName($domainId, $domainType);

	if($domainName !== false) {
		if(
			isset($_POST['passphrase']) && isset($_POST['private_key']) && isset($_POST['certificate']) &&
			isset($_POST['ca_bundle']) && isset($_POST['cert_id'])
		) {
			$passPhrase = clean_input($_POST['passphrase']);
			$privateKey = clean_input($_POST['private_key']);
			$certificate = clean_input($_POST['certificate']);
			$caBundle = clean_input($_POST['ca_bundle']);
			$certId = intval($_POST['cert_id']);

			// Validate private key

			$privateKeyObj = new Crypt_RSA();

			if($privateKey !== '') {
				if($passPhrase !== '') {
					$privateKeyObj->setPassword($passPhrase);
				}

				if(!$privateKeyObj->loadKey($privateKey)) {
					set_page_message(tr('Invalid private key or passphrase.'), 'error');
				}

				// Clear out passphrase if any

				$privateKeyObj->setPassword();

				// Get unencrypted private key
				$privateKey = $privateKeyObj->getPrivateKey(CRYPT_RSA_PUBLIC_FORMAT_PKCS1);
			} else {
				set_page_message(tr('Private key field cannot be empty.'), 'error');
			}

			// Validate CA bundle

			if(!Zend_Session::namespaceIsset('pageMessages')) {
				$certificatPattern = '-{5}BEGIN CERTIFICATE-{5}.*?-{5}END CERTIFICATE-{5}';
				$intCertificates = array();
				$x509 = new File_X509();

				if($caBundle !== '') {
					if(
						preg_match("/^$certificatPattern$/s", $caBundle) &&
						preg_match_all("/$certificatPattern/s", $caBundle, $intCertificates)
					) {
						$intCertificates = array_reverse($intCertificates[0]);
						$unallowSelfSigned = false;

						// Check each certificate in the chain. Only the top-most certificate can be self-signed
						foreach($intCertificates as $intCertificate) {
							if($x509->loadX509($intCertificate) && $x509->validateSignature($unallowSelfSigned)) {
								$x509->loadCA($intCertificate); // Add certificate in CA chain
								$unallowSelfSigned = true;
							} else {
								set_page_message(tr('A certificate in your CA bundle is missing or invalid.'), 'error');
								break;
							}
						}
					} else {
						set_page_message(tr('Invalid CA Bundle.'), 'error');
					}
				}

				// Validate SSL certificate

				if(!Zend_Session::namespaceIsset('pageMessages')) {
					if($certificate !== '') {
						if(preg_match("/^$certificatPattern$/s", $certificate, $match)) {
							$certificate = $match[0];

							if(!$x509->loadX509($certificate)) {
								set_page_message(tr('The certificate is not valid.'), 'error');
							} elseif(!$x509->validateSignature(false)) {
								set_page_message(tr('The certificate is not valid or the CA bundle is missing.'), 'error');
							} elseif(!$x509->validateDate()) {
								set_page_message(tr('The certificate is expired.'), 'error');
							} elseif(!openssl_x509_check_private_key($certificate, $privateKey)) {
								set_page_message(tr("The key is not the private key that corresponds to the certificate."), 'error');
							}
						} else {
							set_page_message(tr('The certificate is not valid.'), 'error');
						}
					} else {
						set_page_message(tr('The certificate field cannot be empty.'), 'error');
					}

					if(!Zend_Session::namespaceIsset('pageMessages')) {
						// Data normalization
						$privateKey = str_replace("\r\n", "\n", $privateKey) . PHP_EOL;
						$certificate = str_replace("\r\n", "\n", $certificate) . PHP_EOL;
						$caBundle = str_replace("\r\n", "\n", $caBundle);

						$db = iMSCP_Database::getInstance();

						try {
							$db->beginTransaction();

							if(!$certId) { // Add new certificate
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


							} else { // Update existing certificate
								exec_query(
									'
										UPDATE
											ssl_certs
										SET
											private_key = ?, certificate = ?, ca_bundle = ?, status = ?
										WHERE
											cert_id = ?
										AND
											domain_id = ?
										AND
											domain_type = ?
									',
									array(
										$privateKey, $certificate, $caBundle, 'tochange', $certId, $domainId,
										$domainType
									)
								);
							}

							client_updateDomainStatus($domainType, $domainId);

							$db->commit();

							send_request();

							if(!$certId) {
								set_page_message(tr('SSL certificate successfully scheduled for addition.'), 'success');
								write_log(
									sprintf(
										'%s added a new SSL certificate for the %s domain', $_SESSION['user_logged'],
										$domainName
									),
									E_USER_NOTICE
								);
							} else {
								set_page_message(tr('SSL certificate successfully scheduled for update.'), 'success');
								write_log(
									sprintf(
										'%s updated an SSL certificate for the %s domain', $_SESSION['user_logged'],
										$domainName
									),
									E_USER_NOTICE
								);
							}

							redirectTo("cert_view.php?domain_id=$domainId&domain_type=$domainType");
						} catch(iMSCP_Exception_Database $e) {
							$db->rollBack();
							throw new iMSCP_Exception_Database(
								sprintf('Unable to add or update SSL certificate: %s', $e->getMessage())
							);
						}
					}
				}
			}
		}
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Delete an SSL certificate
 *
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @param int $domainId domain unique identifier
 * @param string $domainType Domain type (dmn, als, sub, alssub)
 */
function client_deleteSslCert($domainId, $domainType)
{
	$domainName = client_getDomainName($domainId, $domainType);

	if($domainName !== false) {
		if(isset($_POST['cert_id'])) {
			$certId = intval($_POST['cert_id']);

			$db = iMSCP_Database::getInstance();

			try {
				$db->beginTransaction();

				exec_query(
					'UPDATE ssl_certs SET status = ? WHERE cert_id = ? AND domain_id = ? AND domain_type = ?',
					array('todelete', $certId, $domainId, $domainType)
				);

				client_updateDomainStatus($domainType, $domainId);

				$db->commit();

				send_request();

				set_page_message(tr('SSL certificate successfully scheduled for deletion.'), 'success');
				write_log(
					sprintf('%s deleted SSL certificate (%s)', $_SESSION['user_logged'], $domainName), E_USER_NOTICE
				);

				redirectTo('domains_manage.php');
			} catch(iMSCP_Exception_Database $e) {
				$db->rollBack();
				throw new iMSCP_Exception_Database(sprintf('Unable to delete SSL certificate: %s', $e->getMessage()));
			}
		}
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Generate page
 *
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $domainId Domain entity unique identifier
 * @param string $domainType Domain entity type
 */
function client_generatePage($tpl, $domainId, $domainType)
{
	$domainName = client_getDomainName($domainId, $domainType);

	if($domainName !== false) {
		$stmt = exec_query(
			'SELECT * FROM ssl_certs WHERE domain_id = ? AND domain_type = ?', array($domainId, $domainType)
		);

		if($stmt->rowCount()) {
			$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

			$dynTitle = (customerHasFeature('ssl') && $row['status'] == 'ok')
				? tr('Edit SSL certificate') : tr('Show SSL certificate');
			$certId = $row['cert_id'];
			$privateKey = $row['private_key'];
			$certificate = $row['certificate'];
			$caBundle = $row['ca_bundle'];
			$trAction = tr('Update');
			$status = $row['status'];
			$tpl->assign('STATUS', translate_dmn_status($status));
		} else {
			if(customerHasFeature('ssl')) {
				$dynTitle = tr('Add SSL certificate');
				$trAction = tr('Add');
				$certId = '0';
				$privateKey = '';
				$certificate = '';
				$caBundle = '';
				$tpl->assign('SSL_CERTIFICATE_STATUS', '');
			} else {
				set_page_message('SSL feature is currently disabled.', 'static_warning');
				redirectTo('domains_manage.php');
				exit;
			}
		}

		if(
			customerHasFeature('ssl') && isset($_POST['cert_id']) && isset($_POST['private_key']) &&
			isset($_POST['certificate']) && isset($_POST['ca_bundle'])
		) {
			$certId = $_POST['cert_id'];
			$privateKey = $_POST['private_key'];
			$certificate = $_POST['certificate'];
			$caBundle = $_POST['ca_bundle'];
		}

		$tpl->assign(
			array(
				'TR_DYNAMIC_TITLE' => $dynTitle,
				'DOMAIN_NAME' => tohtml(encode_idna($domainName)),
				'KEY_CERT' => tohtml(trim($privateKey)),
				'CERTIFICATE' => tohtml(trim($certificate)),
				'CA_BUNDLE' => tohtml(trim($caBundle)),
				'CERT_ID' => tohtml(trim($certId)),
				'TR_ACTION' => $trAction
			)
		);

		if(!customerHasFeature('ssl') || (isset($status) && $status !== 'ok')) {
			$tpl->assign('SSL_CERTIFICATE_ACTIONS', '');
			if(!customerHasFeature('ssl')) {
				set_page_message(
					tr('SSL feature is not available. You can only view your certificate.'), 'static_warning'
				);
			}
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
		'ssl_certificate_status' => 'page',
		'ssl_certificate_actions' => 'page'
	)
);

if(
	isset($_GET['domain_id']) && isset($_GET['domain_type']) &&
	in_array($_GET['domain_type'], array('dmn', 'als', 'sub', 'alssub'))
) {
	$domainId = intval($_GET['domain_id']);
	$domainType = clean_input($_GET['domain_type']);

	if(customerHasFeature('ssl') && !empty($_POST)) {
		if(isset($_POST['add_update'])) {
			client_addSslCert($domainId, $domainType);
		} elseif(isset($_POST['delete'])) {
			client_deleteSslCert($domainId, $domainType);
		} else {
			showBadRequestErrorPage();
		}
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
			'TR_CANCEL' => tr('Cancel'),
			'DOMAIN_ID' => tohtml($domainId),
			'DOMAIN_TYPE' => tohtml($domainType)
		)
	);

	generateNavigation($tpl);
	client_generatePage($tpl, $domainId, $domainType);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();
} else {
	showBadRequestErrorPage();
}
