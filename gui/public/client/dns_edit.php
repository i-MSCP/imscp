<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get post value
 *
 * @param string $id Data identifier
 * @param string $defaultValue Value returned in case Data has not been found in $_POST
 * @return string
 */
function client_getPost($id, $defaultValue = '')
{
	if (array_key_exists($id, $_POST)) {
		return clean_input($_POST[$id]);
	}

	return $defaultValue;
}

/**
 * Validate Name of DNS record
 *
 * @param string $name Name
 * @param string $domainName Domain name
 * @param string &$errorString Error string
 * @return bool TRUE if name is valid, FALSE otherwise
 */
function client_validate_NAME($name, $domainName, &$errorString)
{
	if (empty($name) || $name == '.') {
		$errorString .= tr('NAME field is empty or invalid.');
		return false;
	}

	if (substr($name, -1) == '.') {
		$entry = rtrim($name, '.');
	} else {
		$entry = $name;
	}

	if (strpos($entry, '.') === false) {
		$entry .= $domainName;
	}

	if (!isValidDomainName($entry)) {
		$errorString .= tr('Invalid NAME.');
		return false;
	}

	if (preg_match('/\.$/', $name)) {
		if (!preg_match('/' . str_replace('.', '\.', $domainName) . '\.$/', $name)) {
			$errorString .= sprintf(
				tr(
					'%s is not part of domain %s.',
					"<strong>$name</strong>",
					'<strong>' . decode_idna($domainName) . '</strong>'
				)
			);

			return false;
		}
	}

	return true;
}

/**
 * Validate CNAME DNS record
 *
 * @param string $cname Cname
 * @param string $domainName Domain name
 * @param string &$errorString Error string
 * @return bool TRUE if cname is valid, FALSE otherwise
 */
function client_validate_CNAME($cname, $domainName, &$errorString)
{
	if (empty($cname) || $cname == '.') {
		$errorString .= tr('CNAME field is empty or invalid.');
		return false;
	}

	if (substr($cname, -1) == '.') {
		$cnameRecord = rtrim($cname, '.');
	} else {
		$cnameRecord = $cname;
	}

	if (strpos($cnameRecord, '.') === false) {
		$cnameRecord .= $domainName;
	}

	if (!isValidDomainName($cnameRecord)) {
		$errorString .= tr('Invalid CNAME');
		return false;
	}

	return true;
}

/**
 * Validate A DNS record
 *
 * @param string $ip IPv4 address
 * @param string &$errorString Error string
 * @return bool
 */
function client_validate_A($ip, &$errorString)
{
	if (empty($ip)) {
		$errorString .= tr('IP address field is empty or invalid.');
		return false;
	} elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
		$errorString .= tr('Invalid IPv4 address.');
		return false;
	}

	return true;
}

/**
 * Validate AAAA DNS record
 *
 * @param array $ip IPv6 address
 * @param string &$errorString Reference to variable, which contain error string
 * @return bool TRUE if the record is valid, FALSE otherwise
 */
function client_validate_AAAA($ip, &$errorString)
{
	if (empty($ip)) {
		$errorString .= tr('IPv6 address field is empty or invalid.');
		return false;
	} elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
		$errorString .= tr('Invalid IPv6 address.');
		return false;
	}

	return true;
}

/**
 * Validate TXT DNS record
 *
 * @param array $record DNS record data
 * @param string &$errorString Reference to variable, which contain error string
 * @return bool TRUE if the record is valid, FALSE otherwise
 */
function client_validate_TXT($record, &$errorString)
{
	if (empty($record['dns_txt_data'])) {
		$errorString .= tr('TXT field is empty or invalid.');
		return false;
	} elseif (!preg_match('/^([a-zA-Z0-9\+\?\-\*_~=:. \/])+$/', str_replace('"', '', $record['dns_txt_data']))) {
		$errorString .= tr('Invalid TXT data');
		return false;
	}

	return true;
}

/**
 * Validate SRV DNS record
 *
 * @param string $record DNS record data
 * @param string &$errorString Reference to variable, which contain error string or NULL
 * @param string &$dns
 * @param string $text
 * @return bool TRUE if the record is valid, FALSE otherwise
 */
function client_validate_SRV($record, &$errorString, &$dns, &$text)
{
	if (empty($record['dns_srv_port'])) {
		$errorString .= tr('%s field is empty or invalid.', tr('Target port'));
		return false;
	} elseif (!is_number($record['dns_srv_port'])) {
		$errorString .= tr('Port must be a number.');
		return false;
	}

	if (empty($record['dns_srv_ttl'])) {
		$errorString .= tr('%sfield is empty or invalid.', tr('TTL'));
		return false;
	} elseif (!is_number($record['dns_srv_ttl'])) {
		$errorString .= tr('TTL must be a number.');
		return false;
	}

	if (empty($record['dns_srv_prio'])) {
		$errorString .= tr('%s field is empty or invalid.', tr('Priority'));
		return false;
	} elseif (!is_number($record['dns_srv_prio'])) {
		$errorString .= tr('Priority must be a number.');
		return false;
	}

	if (empty($record['dns_srv_weight'])) {
		$errorString .= tr('%s field is empty or invalid.', tr('Relative weight'));
		return false;
	} elseif (!is_number($record['dns_srv_weight'])) {
		$errorString .= tr('Relative weight must be a number.');
		return false;
	}

	if (empty($record['dns_srv_name'])) {
		$errorString .= tr('%s field is empty or invalid.', tr('Service name'));
		return false;
	}

	if (empty($record['dns_srv_host'])) {
		$errorString .= tr('%s host field is empty or invalid.', tr('Target host'));
		return false;
	}

	$dns = sprintf("_%s._%s\t%d", $record['dns_srv_name'], $record['srv_proto'], $record['dns_srv_ttl']);

	$text = sprintf(
		"%d\t%d\t%d\t%s", $record['dns_srv_prio'], $record['dns_srv_weight'], $record['dns_srv_port'],
		encode_idna($record['dns_srv_host'])
	);

	return true;
}

/**
 * Check for DNS record conflict
 *
 * @throws iMSCP_Exception in case check cannot be done (eg. when DNS server is down or in error state)
 * @param string $name Name part of the DNS record to check
 * @param string $type DNS record type to check (A|AAAA|CNAME...)
 * @param string &$errorString Reference to variable, which contain error string
 * @return bool TRUE if not conflict is found, FALSE otherwise
 */
function client_checkConflict($name, $type, &$errorString)
{
	$resolver = new Net_DNS2_Resolver(array('nameservers' => array('127.0.0.1')));
	$type = strtoupper($type);

	try {
		/** @var Net_DNS2_Packet_Response $result */
		$result = $resolver->query($name, strtoupper($type));

		if (count($result->answer) == 0) {
			return true;
		} elseif(client_getPost('type') != 'CNAME') {
			if(
				($type == 'A' && $result->answer[0]->address != client_getPost('dns_A_address')) ||
				($type == 'AAAA' && inet_pton($result->answer[0]->address) != inet_pton(client_getPost('dns_AAAA_address')))
			) {
				return true;
			}
		}
	} catch (Net_DNS2_Exception $e) {
		if ($e->getCode() != Net_DNS2_Lookups::RCODE_NXDOMAIN) {

			$errorString .= tr("DNS server unavailable. Please contact your reseller.");
			write_log(
				'System was unable to validate custom DNS record using the local DNS server: ' . $e->getMessage(),
				E_USER_ERROR

			);

			return false;
		}

		return true;
	}

	$errorString .= tr("Conflict with an existent %s record.", "<strong>$type</strong>");

	return false;
}

/**
 * Create HTML option elements
 *
 * @param array $data Options data
 * @param null|string $value
 * @return string
 */
function client_create_options($data, $value = null)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$options = '';
	reset($data);

	foreach ($data as $item) {
		$options .=
			'<option value="' . $item . '"' . (($item == $value) ? $cfg->HTML_SELECTED : '') . '>' .
			$item .
			'</option>';
	}

	return $options;
}

/**
 * Decode DNS record data
 *
 * @param array|null $data DNS record data or NULL
 * @return array
 */
function client_decodeDnsRecordData($data)
{
	$address = $addressv6 = $srvName = $srvProto = $cname = $txt = $name = '';
	$srvTTL = $srvPriority = $srvWeight =
	$srvHost = $srvPost = '';
	$ownedBy = 'custom_dns_feature';

	if (is_array($data)) {
		$name = $data['domain_dns'];
		$ownedBy = $data['owned_by'];

		switch ($data['domain_type']) {
			case 'A':
				$address = $data['domain_text'];
				break;
			case 'AAAA':
				$addressv6 = $data['domain_text'];
				break;
			case 'CNAME':
				$cname = $data['domain_text'];
				break;
			case 'SRV':
				$name = '';

				if (preg_match('~_([^\.]+)\._([^\s]+)[\s]+([\d]+)~', $data['domain_dns'], $srv)) {
					$srvName = $srv[1];
					$srvProto = $srv[2];
					$srvTTL = $srv[3];
				}

				if (preg_match('~([\d]+)[\s]+([\d]+)[\s]+([\d]+)[\s]+([^\s]+)+~', $data['domain_text'], $srv)) {
					$srvPriority = $srv[1];
					$srvWeight = $srv[2];
					$srvPost = $srv[3];
					$srvHost = $srv[4];
				}

				break;
			default:
				$txt = $data['domain_text'];
		}
	}

	return array(
		$name, $address, $addressv6, $srvName, $srvProto, $srvTTL, $srvPriority, $srvWeight, $srvHost, $srvPost, $cname,
		$txt, $ownedBy
	);
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $dnsRecordId DNS record unique identifier (0 for new record)
 * @return void
 */
function client_generatePage($tpl, $dnsRecordId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$mainDomainId = get_user_domain_id($_SESSION['user_id']);

	if (!$dnsRecordId) { // Add DNS record
		$query = "
			SELECT
				'0' AS `domain_id`, `domain_name`
			FROM
				`domain`
			WHERE
				`domain_id` = ?
			UNION
			SELECT
				`alias_id` AS `domain_id`, `alias_name` AS domain_name
			FROM
				`domain_aliasses`
			WHERE
				`domain_id` = ?
			AND
				`alias_status` <> ?
		";
		$stmt = exec_query($query, array($mainDomainId, $mainDomainId, 'ordered'));

		$domainId = client_getPost('domain_id', '0');
		$selected = $cfg->HTML_SELECTED;
		$selectOptions = '';

		while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			$selectOptions .=
				'<option value="' . $data['domain_id'] . '"' . (($data['domain_id'] == $domainId) ? $selected : '') . '>' .
					decode_idna($data['domain_name']) .
				'</option>';
		}

		$tpl->assign('SELECT_DOMAINS', $selectOptions);
	} else { // Edit DNS record
		$query = "SELECT * FROM `domain_dns` WHERE `domain_dns_id` = ? AND `domain_id` = ?";
		$stmt = exec_query($query, array($dnsRecordId, $mainDomainId));

		if (!$stmt->rowCount()) {
			showBadRequestErrorPage();
		}

		$data = $stmt->fetchRow(PDO::FETCH_ASSOC);
		$tpl->assign('ADD_RECORD', '');
	}

	list(
		$name, $address, $addressv6, $srvName, $srvProto, $srvTTL, $srvPriority, $srvWeight, $srvHost, $srvPort, $cname,
		$plain, $ownedBy
		) = client_decodeDnsRecordData($data);

	// Protection against edition (eg. for external mail MX record)
	if ($ownedBy != 'custom_dns_feature') {
		showBadRequestErrorPage();
	}

	$dnsTypes = client_create_options(
		array('A', 'AAAA', 'SRV', 'CNAME', 'TXT'), client_getPost('type', $data['domain_type'])
	);

	$dnsClasses = client_create_options(array('IN'), client_getPost('class', $data['domain_class']));

	$tpl->assign(
		array(
			'SELECT_DNS_TYPE' => $dnsTypes,
			'SELECT_DNS_CLASS' => $dnsClasses,
			'DNS_NAME' => tohtml(client_getPost('dns_name', decode_idna($name))),
			'DNS_ADDRESS' => tohtml(client_getPost('dns_A_address', $address)),
			'DNS_ADDRESS_V6' => tohtml(client_getPost('dns_AAAA_address', $addressv6)),
			'SELECT_DNS_SRV_PROTOCOL' => client_create_options(
				array('tcp', 'udp'), client_getPost('srv_proto', $srvProto)
			),
			'DNS_SRV_NAME' => tohtml(client_getPost('dns_srv_name', decode_idna($srvName))),
			'DNS_SRV_TTL' => tohtml(client_getPost('dns_srv_ttl', $srvTTL)),
			'DNS_SRV_PRIO' => tohtml(client_getPost('dns_srv_prio', $srvPriority)),
			'DNS_SRV_WEIGHT' => tohtml(client_getPost('dns_srv_weight', $srvWeight)),
			'DNS_SRV_HOST' => tohtml(client_getPost('dns_srv_host', $srvHost)),
			'DNS_SRV_PORT' => tohtml(client_getPost('dns_srv_port', $srvPort)),
			'DNS_CNAME' => tohtml(client_getPost('dns_cname', decode_idna($cname))),
			'DNS_PLAIN' => tohtml(client_getPost('dns_plain_data', $plain)),
			'DNS_TXT_DATA' => tohtml(client_getPost('dns_txt_data', $plain)),
			'ID' => tohtml($dnsRecordId)
		)
	);
}

/**
 * Check and save DNS record
 *
 * @throws iMSCP_Exception_Database
 * @param int $dnsRecordId DNS record unique identifier (0 for new record)
 * @return bool TRUE on success, FALSE otherwise
 */
function client_saveDnsRecord($dnsRecordId)
{
	$mainDmnProps = get_domain_default_props($_SESSION['user_id']);
	$mainDmnId = $mainDmnProps['domain_id'];

	$errorString = '';
	$dnsRecordName = '';
	$dnsRecordClass = client_getPost('class');
	$dnsRecordType = client_getPost('type');

	if ($dnsRecordClass != 'IN' || !in_array($dnsRecordType, array('A', 'AAAA', 'CNAME', 'SRV', 'TXT'))) {
		showBadRequestErrorPage();
	}

	$dnsRecordData = '';

	if (!$dnsRecordId) {
		if ($_POST['domain_id'] == 0) {
			$domainName = $mainDmnProps['domain_name'];
			$domainId = 0;
			$domainType = 'dmn';
		} else {
			$query = "SELECT `alias_id`, `alias_name` FROM `domain_aliasses` WHERE `alias_id` = ? AND `domain_id` = ?";
			$stmt = exec_query($query, array($_POST['domain_id'], $mainDmnId));

			if (!$stmt->rowCount()) {
				showBadRequestErrorPage();
			}

			$domainName = $stmt->fields['alias_name'];
			$domainId = $stmt->fields['alias_id'];
			$domainType = 'als';
		}
	} else {
		$query = "
			SELECT
				`t1`.*,
				IFNULL(`t3`.`alias_name`, `t2`.`domain_name`) `domain_name`,
				IFNULL(`t3`.`alias_status`, `t2`.`domain_status`) `domain_status`
			FROM
				`domain_dns` AS `t1`
			LEFT JOIN
				`domain` AS `t2` USING(`domain_id`)
			LEFT JOIN
				`domain_aliasses` AS `t3` USING (`alias_id`)
			WHERE
				`domain_dns_id` = ?
			AND
				`t1`.`domain_id` = ?
		";
		$stmt = exec_query($query, array($dnsRecordId, $mainDmnId));

		if (!$stmt->rowCount()) {
			showBadRequestErrorPage();
		}

		$domainId = ($stmt->fields['alias_id']) ? $stmt->fields['alias_id'] : $stmt->fields['domain_id'];
		$domainName = $stmt->fields['domain_name'];
		$dnsRecordName = $stmt->fields['domain_dns'];
		$domainType = ($stmt->fields['alias_id']) ? 'als' : 'dmn';
	}

	$nameValidationError = '';

	if (in_array($dnsRecordType, array('A', 'AAAA', 'CNAME'))) {
		if (!client_validate_NAME(client_getPost('dns_name'), $domainName, $nameValidationError)) {
			set_page_message(sprintf(tr("Cannot validate record: %s"), $nameValidationError), 'error');
		}
	}

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		switch ($dnsRecordType) {
			case 'CNAME':
				$cname = client_getPost('dns_cname');
				$newName = encode_idna(
					(substr(client_getPost('dns_name'), -1) == '.')
						? client_getPost('dns_name') : client_getPost('dns_name') . '.' . $domainName
				);
				$oldName = ($dnsRecordName != '') ? ((substr($dnsRecordName, -1) == '.')
					? $dnsRecordName : $dnsRecordName . '.' . $domainName) : '';

				if (!client_validate_CNAME($cname, $domainName, $errorString)) {
					set_page_message(sprintf(tr("Cannot validate record: %s"), $errorString), 'error');
				} elseif ($newName != $oldName && !client_checkConflict($newName, 'CNAME', $errorString)) {
					set_page_message(sprintf(tr("Cannot validate record: %s"), $errorString), 'error');
				} elseif ($newName != $oldName && !client_checkConflict($newName, 'A', $errorString)) {
					set_page_message(sprintf(tr("Cannot validate record: %s"), $errorString), 'error');
				} elseif ($newName != $oldName && !client_checkConflict($newName, 'AAAA', $errorString)) {
					set_page_message(sprintf(tr("Cannot validate record: %s"), $errorString), 'error');
				}

				$dnsRecordName = encode_idna(client_getPost('dns_name'));
				$dnsRecordData = encode_idna($cname);
				break;
			case 'A':
				$ip = client_getPost('dns_A_address');
				$newName = encode_idna(
					(substr(client_getPost('dns_name'), -1) == '.')
						? client_getPost('dns_name') : client_getPost('dns_name') . '.' . $domainName
				);

				if (!client_validate_A($ip, $errorString)) {
					set_page_message(sprintf(tr("Cannot validate record: %s"), $errorString), 'error');
				} elseif (!client_checkConflict($newName, 'CNAME', $errorString)) {
					set_page_message(sprintf(tr("Cannot validate record: %s"), $errorString), 'error');
				} elseif (!client_checkConflict($newName, 'A', $errorString)) {
					set_page_message(sprintf(tr("Cannot validate record: %s"), $errorString), 'error');
				}

				$dnsRecordName = encode_idna(client_getPost('dns_name'));
				$dnsRecordData = $ip;
				break;
			case 'AAAA':
				$ip = client_getPost('dns_AAAA_address');
				$newName = encode_idna(
					(substr(client_getPost('dns_name'), -1) == '.')
						? client_getPost('dns_name') : client_getPost('dns_name') . '.' . $domainName
				);

				if (!client_validate_AAAA(client_getPost('dns_AAAA_address'), $errorString)) {
					set_page_message(sprintf(tr("Cannot validate record: %s"), $errorString), 'error');
				} elseif (!client_checkConflict($newName, 'CNAME', $errorString)) {
					set_page_message(sprintf(tr("Cannot validate %s record: %s"), $errorString), 'error');
				} elseif (!client_checkConflict($newName, 'AAAA', $errorString)) {
					set_page_message(sprintf(tr("Cannot validate record: %s"), $errorString), 'error');
				}

				$dnsRecordName = encode_idna(client_getPost('dns_name'));
				$dnsRecordData = $ip;
				break;
			case 'SRV':
				if (!client_validate_SRV($_POST, $errorString, $dnsRecordName, $dnsRecordData)) {
					set_page_message(sprintf(tr("Cannot validate record: %s"), $errorString), 'error');
				}

				break;
			case 'TXT':
				if (!client_validate_TXT($_POST, $errorString)) {
					set_page_message(sprintf(tr("Cannot validate record: %s"), $errorString), 'error');
				}

				$dnsRecordData = '"' . str_replace('"', '', $_POST['dns_txt_data']) . '"';
				$dnsRecordName = $domainName . '.';

				break;
			default :
				showBadRequestErrorPage();
				exit;
		}

		if (!Zend_Session::namespaceIsset('pageMessages')) {
			/** @var $db iMSCP_Database */
			$db = iMSCP_Database::getInstance();

			try {
				$db->beginTransaction();

				if (!$dnsRecordId) {
					$query = '
						INSERT INTO `domain_dns` (
							`domain_id`, `alias_id`, `domain_dns`, `domain_class`, `domain_type`, `domain_text`,
							`owned_by`
						) VALUES (
							?, ?, ?, ?, ?, ?, ?
						)
					';
					exec_query(
						$query,
						array(
							$mainDmnId, $domainId, $dnsRecordName, $dnsRecordClass, $dnsRecordType, $dnsRecordData,
							'custom_dns_feature'
						)
					);
				} else {
					$query = '
						UPDATE
							`domain_dns`
						SET
							`domain_dns` = ?, `domain_class` = ?, `domain_type` = ?, `domain_text` = ?
						WHERE
							`domain_dns_id` = ?
					';
					exec_query(
						$query,
						array($dnsRecordName, $dnsRecordClass, $dnsRecordType, $dnsRecordData, $dnsRecordId)
					);
				}

				if ($domainType == 'dmn') {
					$query = 'UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?';
					exec_query($query, array('tochange', $mainDmnId));
				} else {
					$query = 'UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `domain_id` = ? AND `alias_id` = ?';
					exec_query($query, array('tochange', $mainDmnId, $domainId));
				}

				$db->commit();

				send_request();

				write_log(
					"{$_SESSION['user_logged']} scheduled ". ((!$dnsRecordId) ? 'addition' : 'update') .
					' of custom DNS record'
				);
			} catch (iMSCP_Exception_Database $e) {
				$db->rollBack();

				if ($e->getCode() == 23000) { // Duplicate entries
					set_page_message(tr('DNS record already exist.'), 'error');
					return false;
				}

				throw $e;
			}

			return true;
		}
	}

	return false;
}

/***********************************************************************************************************************
 * Main
 */
// Include core library
require_once 'imscp-lib.php';

require_once 'vendor/Net/DNS2.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

// If the feature is disabled, redirects in silent way
customerHasFeature('custom_dns_records') or showBadRequestErrorPage();

$dnsRecordId = isset($_GET['id']) ? clean_input($_GET['id']) : 0;

if(!empty($_POST)) {
	if (client_saveDnsRecord($dnsRecordId)) {
		if($dnsRecordId) {
			set_page_message(tr('Custom DNS record successfully scheduled for update.'), 'success');
		} else {
			set_page_message(tr('Custom DNS record successfully scheduled for addition.'), 'success');
		}

		redirectTo('domains_manage.php');
	}
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/dns_edit.tpl',
		'page_message' => 'layout',
		'logged_from' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => (!$dnsRecordId)
			? tr("Client / Domains / Add Custom DNS record")
			: tr("Client / Domain / Edit Custom DNS record"),
		'ISP_LOGO' => layout_getUserLogo(),
		'ACTION_MODE' => (!$dnsRecordId) ? 'dns_add.php' : 'dns_edit.php?id={ID}',
		'TR_CUSTOM_DNS_RECORD' => tr('Custom DNS record'),
		'TR_DOMAIN' => tr('Domain'),
		'TR_DNS_TYPE' => tr('Type'),
		'TR_DNS_CLASS' => tr('Class'),
		'TR_DNS_NAME' => tr('Name'),
		'TR_DNS_SRV_NAME' => tr('Service name'),
		'TR_DNS_IP_ADDRESS' => tr('IP address'),
		'TR_DNS_IP_ADDRESS_V6' => tr('IPv6 address'),
		'TR_DNS_SRV_PROTOCOL' => tr('Service protocol'),
		'TR_DNS_SRV_TTL' => tr('TTL'),
		'TR_DNS_SRV_PRIO' => tr('Priority'),
		'TR_DNS_SRV_WEIGHT' => tr('Relative weight'),
		'TR_DNS_SRV_HOST' => tr('Target host'),
		'TR_DNS_SRV_PORT' => tr('Target port'),
		'TR_DNS_CNAME' => tr('Canonical name'),
		'TR_DNS_TXT_DATA' => tr('TXT data'),
		'TR_ADD' => tr('Add'),
		'TR_UPDATE' => tr('Update'),
		'TR_CANCEL' => tr('Cancel')
	)
);

$tpl->assign((!$dnsRecordId) ? 'FORM_EDIT_MODE' : 'FORM_ADD_MODE', '');

generateNavigation($tpl);
client_generatePage($tpl, $dnsRecordId);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
