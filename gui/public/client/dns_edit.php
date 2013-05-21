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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2013 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2013 by i-MSCP | http://i-mscp.net
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
function getPost($id, $defaultValue)
{
	if (array_key_exists($id, $_POST)) {
		return clean_input($_POST[$id]);
	}

	return $defaultValue;
}

/**
 * Validate CNAME DNS record
 *
 * @param array $record DNS record data
 * @param string &$errorString Reference to variable, which contain error string
 * @return bool TRUE if the record is valid, FALSE otherwise
 */
function validate_CNAME($record, &$errorString)
{
	if(substr($record['dns_cname'], -1) == '.') {
		$cnameRecord = rtrim($record['dns_cname'], '.');
	} else {
		$cnameRecord = $record['dns_cname'];
	}

	if(!iMSCP_Validate::getInstance()->domainName($cnameRecord, array('tld' => false))) {
		$errorString .= tr('Usage of disallowed character in CNAME');
		return false;
	}

	if (empty($record['dns_name'])) {
		$errorString .= tr('Name must be filled.');
		return false;
	}

	return true;
}

/**
 * Validate A DNS record
 *
 * @param array $record DNS record data
 * @param string &$errorString Reference to variable, which contain error string
 * @return bool TRUE if the record is valid, FALSE otherwise
 */
function validate_A($record, &$errorString)
{
	if (filter_var($record['dns_A_address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
		$errorString .= sprintf(tr('Wrong IPv4 address ("%s")'), $record['dns_A_address']);
		return false;
	}

	if (empty($record['dns_name'])) {
		$errorString .= tr('Name must be filled.');
		return false;
	}

	return true;
}

/**
 * Validate AAAA DNS record
 *
 * @param array $record DNS record data
 * @param string &$errorString Reference to variable, which contain error string
 * @return bool TRUE if the record is valid, FALSE otherwise
 */
function validate_AAAA($record, &$errorString)
{
	if (filter_var($record['dns_AAAA_address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
		$errorString .= sprintf(tr('Wrong IPv6 address ("%s")'), $record['dns_AAAA_address']);
		return false;
	}

	if (empty($record['dns_name'])) {
		$errorString .= tr('Name must be filled.');
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
function validate_TXT($record, &$errorString)
{
	if (!preg_match('/^([a-zA-Z0-9\+\?\-\*_~=:. \/])+$/', str_replace('"', '', $record['dns_txt_data']))) {
		$errorString .= sprintf(tr('Invalid characters in TXT data ("%s")'), str_replace('"', '', $record['dns_txt_data']));
		return false;
	}

	if (empty($record['dns_txt_data'])) {
		$errorString .= tr('TXT data must be filled.');
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
function validate_SRV($record, &$errorString, &$dns, &$text)
{
	if (!preg_match('~^([\d]+)$~', $record['dns_srv_port'])) {
		$errorString .= tr('Port must be a number.');
		return false;
	}

	if (!preg_match('~^([\d]+)$~', $record['dns_srv_ttl'])) {
		$errorString .= tr('TTL must be a number.');
		return false;
	}

	if (!preg_match('~^([\d]+)$~', $record['dns_srv_prio'])) {
		$errorString .= tr('Priority must be a number.');
		return false;
	}

	if (!preg_match('~^([\d]+)$~', $record['dns_srv_weight'])) {
		$errorString .= tr('Relative weight must be a number.');
		return false;
	}

	if (empty($record['dns_srv_name'])) {
		$errorString .= tr('Service must be filled.');
		return false;
	}

	if (empty($record['dns_srv_host'])) {
		$errorString .= tr('Host must be filled.');
		return false;
	}

	$dns = sprintf("_%s._%s\t%d", $record['dns_srv_name'], $record['srv_proto'], $record['dns_srv_ttl']);

	$text = sprintf(
		"%d\t%d\t%d\t%s", $record['dns_srv_prio'], $record['dns_srv_weight'], $record['dns_srv_port'],
		$record['dns_srv_host']
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
function checkConflict($name, $type, &$errorString)
{
	$type = strtoupper($type);

	$resolver = new Net_DNS2_Resolver(array('nameservers' => array('127.0.0.1')));

	try {
		$result = $resolver->query($name, $type);

		if (count($result->answer) == 0) {
			return true;
		}
	} catch(Net_DNS2_Exception $e) {
		if($e->getCode() != Net_DNS2_Lookups::RCODE_NXDOMAIN) {

			$errorString .= tr("DNS server unavailable. Please, contact your reseller.");
			write_log(
				'System was unable to validate custom DNS record using the local DNS server: ' . $e->getMessage(),
				E_USER_ERROR

			);

			return false;
		}

		return true;
	}

	$errorString .= tr("conflict with $type record");

	return false;
}

/**
 * Validate name of DNS record
 *
 * @param array $domain DNS record data
 * @param string &$errorString Reference to variable, which contain error string or NULL
 * @return bool TRUE if the record is valid, FALSE otherwise
 */
function validate_NAME($domain, &$errorString)
{
	if(strpos($domain['name'], '.') === false) {
		$entry = $domain['name'] . '.dummy';
	} else {
		$entry = $domain['name'];
	}

	if(!iMSCP_Validate::getInstance()->domainName($entry, array('tld' => false))) {
		$errorString .= tr('Invalid NAME');
		return false;
	}

	if (preg_match('/\.$/', $domain['name'])) {
		if (!preg_match('/'.str_replace('.', '\.', $domain['domain']) . '\.$/', $domain['name'])) {
			$errorString .= sprintf(tr('Record "%s" is not part of domain "%s".', $domain['name'], $domain['domain']));
			return false;
		}
	}

	return true;
}

/**
 * @param array $data Options data
 * @param null $value
 * @return string
 */
function create_options($data, $value = null)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$options = '';
	reset($data);

	foreach ($data as $item) {
		$options .=
			'<option value="' . $item . '"' . (($item == $value) ? $cfg->HTML_SELECTED : '') . '>' . $item . '</option>';
	}

	return $options;
}

/**
 * Decode DNS record data
 *
 * @param array|null $data DNS record data or NULL
 * @return array
 */
function decodeDnsRecordData($data)
{
	$address = $addressv6 = $srvName = $srvProto = $cname = $txt = $name = ''; $srvTTL = $srvPriority = $srvWeight =
	$srvHost = $srvPost = '';
	$protected = 'no';

	if (is_array($data)) {
		$name = $data['domain_dns'];
		$protected = $data['protected'];

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
		$txt, $protected
	);
}

/**
 * Generate page data
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $dnsRecordId DNS record unique identifier (0 for new record)
 * @return void
 */
function generatePageData($tpl, $dnsRecordId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$mainDomainId = get_user_domain_id($_SESSION['user_id']);

	if (!$dnsRecordId) { // Add DNS record
		$data = null;

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
		$stmt = exec_query($query, array($mainDomainId, $mainDomainId, $cfg->ITEM_ORDERED_STATUS));

		$domainId = getPost('domain_id', '0');
		$selectOptions = '';

		while ($row = $stmt->fetchRow()) {
			$selectOptions .=
				'<option value="' . $row['domain_id'] . '"' . (($row['domain_id'] == $domainId) ? $cfg->HTML_SELECTED : '') . '>' .
					decode_idna($row['domain_name']) .
				'</option>';
		}

		$tpl->assign('SELECT_DOMAINS', $selectOptions);

	} else { // Edit DNS record
		$query = "SELECT * FROM `domain_dns` WHERE `domain_dns_id` = ? AND `domain_id` = ?";
		$stmt = exec_query($query, array($dnsRecordId, $mainDomainId));

		if (!$stmt->recordCount()) {
			showBadRequestErrorPage();
		}

		$data = $stmt->fetchRow();
		$tpl->assign('ADD_RECORD', '');
	}

	list(
		$name, $address, $addressv6, $srvName, $srvProto, $srvTTL, $srvPriority, $srvWeight, $srvHost, $srvPort, $cname,
		$plain, $protected
	) = decodeDnsRecordData($data);

	// Protection against edition (eg. for external mail MX record)
	if($protected == 'yes') {
		showBadRequestErrorPage();
	}

	$dnsTypes = create_options(array('A', 'AAAA', 'SRV', 'CNAME', 'TXT'), getPost('type', $data['domain_type']));
	$dnsClasses = create_options(array('IN'), getPost('class', $data['domain_class']));

	$tpl->assign(
		array(
			'SELECT_DNS_TYPE' => $dnsTypes,
			'SELECT_DNS_CLASS' => $dnsClasses,
			'DNS_NAME' => tohtml(getPost('dns_name', $name)),
			'DNS_ADDRESS' => tohtml(getPost('dns_A_address', $address)),
			'DNS_ADDRESS_V6' => tohtml(getPost('dns_AAAA_address', $addressv6)),
			'SELECT_DNS_SRV_PROTOCOL' => create_options(array('tcp', 'udp'), getPost('srv_proto', $srvProto)),
			'DNS_SRV_NAME' => tohtml(getPost('dns_srv_name', $srvName)),
			'DNS_SRV_TTL' => tohtml(getPost('dns_srv_ttl', $srvTTL)),
			'DNS_SRV_PRIO' => tohtml(getPost('dns_srv_prio', $srvPriority)),
			'DNS_SRV_WEIGHT' => tohtml(getPost('dns_srv_weight', $srvWeight)),
			'DNS_SRV_HOST' => tohtml(getPost('dns_srv_host', $srvHost)),
			'DNS_SRV_PORT' => tohtml(getPost('dns_srv_port', $srvPort)),
			'DNS_CNAME' => tohtml(getPost('dns_cname', $cname)),
			'DNS_PLAIN' => tohtml(getPost('dns_plain_data', $plain)),
			'DNS_TXT_DATA' => tohtml(getPost('dns_txt_data', $plain)),
			'ID' => $dnsRecordId
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
function saveDnsRecord($dnsRecordId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$mainDomainId = get_user_domain_id($_SESSION['user_id']);

	$errorString = '';
	$dnsRecordClass = clean_input($_POST['class']);
	$dnsRecordType = clean_input($_POST['type']);
	$dnsRecordText = '';

	if (!$dnsRecordId) {
		$query = "
			SELECT
				*
			FROM (
				SELECT
					'0' AS `domain_id`, `domain_name`
				FROM
					`domain`
				WHERE
					`domain_id` = ?
				UNION
				SELECT
					`alias_id` AS `domain_id`, `alias_name` AS `domain_name`
				FROM
					`domain_aliasses`
				WHERE
					`domain_id` = ?
			) AS `result`
			WHERE
				IFNULL(`result`.`domain_id`, 0) = ?
		";
		$stmt = exec_query($query, array($mainDomainId, $mainDomainId, $_POST['domain_id']));

		if (!$stmt->rowCount()) {
			showBadRequestErrorPage();
		}

		$domainName = $stmt->fields['domain_name'];
		$domainId = $stmt->fields['domain_id'];
	} else {
		$query = "
			SELECT
				`t1`.*,
				IFNULL(`t3`.`alias_name`, `t2`.`domain_name`) `domain_name`,
				IFNULL(`t3`.`alias_status`, `t2`.`domain_status`) `domain_status`
			FROM
				`domain_dns` AS `t1`
			LEFT JOIN
				`domain` AS `t2` ON (`t2`.`domain_id` = `t1`.`domain_id`)
			LEFT JOIN
				`domain_aliasses` AS `t3` ON (`t3`.`alias_id` = `t1`.`alias_id`)
			WHERE
				`domain_dns_id` = ?
			AND
				`t1`.`domain_id` = ?
		";
		$stmt = exec_query($query, array($dnsRecordId, $mainDomainId));

		if (!$stmt->rowCount()) {
			showBadRequestErrorPage();
		}

		$domainId = $stmt->fields['domain_id'];
		$domainName = $stmt->fields['domain_name'];
		$dnsRecordName = $stmt->fields['domain_dns'];
	}

	$nameValidationError = '';

	if(in_array($_POST['type'], array('A', 'AAAA', 'CNAME'))) {
		if(!validate_NAME(array('name' => $_POST['dns_name'], 'domain' => $domainName), $nameValidationError)) {
			set_page_message(
				sprintf(tr("Cannot validate %s record: %s"), $dnsRecordType, $nameValidationError), 'error'
			);
		}
	}

	switch ($_POST['type']) {
		case 'CNAME':
			if (!validate_CNAME($_POST, $errorString)) {
				set_page_message(
					sprintf(tr("Cannot validate %s record: %s"), $dnsRecordType, $errorString), 'error'
				);
			} elseif (!checkConflict($_POST['dns_name'] . '.' .  $domainName, 'CNAME', $errorString)) {
				set_page_message(
					sprintf(tr("Cannot validate %s record: %s"), $dnsRecordType, $errorString), 'error'
				);
			} elseif (!checkConflict($_POST['dns_name'] . '.' .  $domainName, 'A', $errorString)) {
				set_page_message(
					sprintf(tr("Cannot validate %s record: %s"), $dnsRecordType, $errorString), 'error'
				);
			} elseif (!checkConflict($_POST['dns_name'] . '.' .  $domainName, 'AAAA', $errorString)) {
				set_page_message(
					sprintf(tr("Cannot validate %s record: %s"), $dnsRecordType, $errorString), 'error'
				);
			}

			$dnsRecordText = encode_idna($_POST['dns_cname']);
			$dnsRecordName = $_POST['dns_name'];
			break;
		case 'A':
			if (!validate_A($_POST, $errorString)) {
				set_page_message(
					sprintf(tr("Cannot validate %s record: %s"), $dnsRecordType, $errorString), 'error'
				);
			} elseif (!checkConflict($_POST['dns_name'] . '.' . $domainName, 'CNAME', $errorString)) {
				set_page_message(
					sprintf(tr("Cannot validate %s record: %s"), $dnsRecordType, $errorString), 'error'
				);
			}

			$dnsRecordText = $_POST['dns_A_address'];
			$dnsRecordName = $_POST['dns_name'];
			break;
		case 'AAAA':
			if (!validate_AAAA($_POST, $errorString)) {
				set_page_message(
					sprintf(tr("Cannot validate %s record: %s"), $dnsRecordType, $errorString), 'error'
				);
			} elseif (!checkConflict($_POST['dns_name'] . '.' .  $domainName, 'CNAME', $errorString)) {
				set_page_message(
					sprintf(tr("Cannot validate %s record: %s"), $dnsRecordType, $errorString), 'error'
				);
			}

			$dnsRecordText = $_POST['dns_AAAA_address'];
			$dnsRecordName = $_POST['dns_name'];
			break;
		case 'SRV':
			if (!validate_SRV($_POST, $errorString, $dnsRecordName, $dnsRecordText)) {
				set_page_message(
					sprintf(tr("Cannot validate %s record: %s"), $dnsRecordType, $errorString), 'error'
				);
			}

			break;
		case 'TXT':
			if (!validate_TXT($_POST, $errorString)) {
				set_page_message(
					sprintf(tr("Cannot validate %s record: %s"), $dnsRecordType, $errorString), 'error'
				);
			}

			$dnsRecordText = '"' . str_replace('"', '', $_POST['dns_txt_data']) . '"';
			$dnsRecordName = $domainName . '.';

			break;
		default :
			showBadRequestErrorPage();
			exit;
	}

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		if (!$dnsRecordId) {
			try {
				$query = "
					INSERT INTO `domain_dns` (
						`domain_id`, `alias_id`, `domain_dns`, `domain_class`, `domain_type`, `domain_text`
					) VALUES (
						?, ?, ?, ?, ?, ?
					)
				";
				 exec_query(
					$query,
					 array($mainDomainId, $domainId, $dnsRecordName, $dnsRecordClass, $dnsRecordType, $dnsRecordText)
				);
			} catch (iMSCP_Exception_Database $e) {
				if($e->getCode() == 23000) { // Duplicate entries
					set_page_message(tr('DNS record already exist.'), 'error');
					return false;
				}

				throw new iMSCP_Exception_Database($e->getMessage(), $e->getQuery(), $e->getCode(), $e);
			}
		} else {
			$query = "
				UPDATE
					`domain_dns`
				SET
					`domain_dns` = ?, `domain_class` = ?, `domain_type` = ?, `domain_text` = ?
				WHERE
					`domain_dns_id` = ?
			";
			exec_query($query, array($dnsRecordName, $dnsRecordClass, $dnsRecordType, $dnsRecordText, $dnsRecordId));
		}

		if (!$domainId) {
			$query = "UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?";
			exec_query($query, array($cfg->ITEM_DNSCHANGE_STATUS, $mainDomainId));
		} else {
			$query = "UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `domain_id` = ? AND`alias_id` = ?";
			exec_query($query, array($cfg->ITEM_DNSCHANGE_STATUS, $mainDomainId, $domainId));
		}

		send_request();
		write_log(
			$_SESSION['user_logged'] . ((!$dnsRecordId) ? 'added new' : ' updated') . " DNS record.", E_USER_NOTICE
		);
		return true;
	}

	return false;
}

/***********************************************************************************************************************
 * Main
 */
// Include core library
require_once 'imscp-lib.php';

require_once 'vendor/Net/DNS2.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

// If the feature is disabled, redirects in silent way
customerHasFeature('custom_dns_records') or showBadRequestErrorPage();

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/dns_edit.tpl',
		'page_message' => 'layout',
		'logged_from' => 'page'
	)
);

$dnsRecordId = isset($_GET['edit_id']) ? clean_input($_GET['edit_id']) : 0;

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => (!$dnsRecordId)
			? tr("i-MSCP - Manage Domain / Add Custom DNS  record")
			: tr("i-MSCP - Manage Domain / Edit Custom DNS record"),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'ACTION_MODE' => (!$dnsRecordId) ? 'dns_add.php' : 'dns_edit.php?edit_id={ID}',
		'TR_CUSTOM_DNS_RECORD_DATA' => tr('Custom DNS record data'),
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

generateNavigation($tpl);

$tpl->assign((!$dnsRecordId) ? 'FORM_EDIT_MODE' : 'FORM_ADD_MODE', '');

if (isset($_POST['uaction']) && ($_POST['uaction'] == 'update')) {
	if (!$dnsRecordId) {
		showBadRequestErrorPage();
	}

	if (saveDnsRecord($dnsRecordId)) {
		set_page_message(tr('Custom DNS record successfully scheduled for update.'), 'success');
		redirectTo('domains_manage.php');
	}
} elseif (isset($_POST['uaction']) && ($_POST['uaction'] == 'add')) {
	if (saveDnsRecord($dnsRecordId)) {
		set_page_message(tr('Custom DNS record successfully scheduled for addition.'), 'success');
		redirectTo('domains_manage.php');
	}
}

generatePageData($tpl, $dnsRecordId);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
