<?php
/**
 * i-MSCP a internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Client
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

// Include core library
require_once 'imscp-lib.php';

// Temporary fix for all 'Strict Standards' errors that come from the Net_DNS library
error_reporting(E_ALL);

require_once 'vendor/Net_DNS/DNS.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('custom_dns_records')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/dns_edit.tpl');
$tpl->define_dynamic('page_message', 'layout');
$tpl->define_dynamic('logged_from', 'page');

$DNS_allowed_types = array('A', 'AAAA', 'SRV', 'CNAME', 'MX', 'TXT');

$add_mode = preg_match('~dns_add.php~', $_SERVER['REQUEST_URI']);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => ($add_mode)
			? tr("i-MSCP - Manage Domain Alias/Add DNS zones record")
			: tr("i-MSCP - Manage Domain Alias/Edit DNS zones record"),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'ACTION_MODE' => ($add_mode) ? 'dns_add.php' : 'dns_edit.php?edit_id={ID}'));

$tpl->assign(
	array(
		'TR_MODIFY' => tr('Modify'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_ADD' => tr('Add'),
		'TR_DOMAIN' => tr('Domain'),
		'TR_EDIT_DNS' => ($add_mode) ? tr("Add custom DNS record") : tr("Edit custom DNS record"),
		'TR_DNS' => tr("Custom DNS record"),
		'TR_DNS_NAME' => tr('Name'),
		'TR_DNS_CLASS' => tr('Class'),
		'TR_DNS_TYPE' => tr('Type'),
		'TR_DNS_SRV_NAME' => tr('Service name'),
		'TR_DNS_IP_ADDRESS' => tr('IP address'),
		'TR_DNS_IP_ADDRESS_V6' => tr('IPv6 address'),
		'TR_DNS_SRV_PROTOCOL' => tr('Service protocol'),
		'TR_DNS_SRV_TTL' => tr('TTL'),
		'TR_DNS_SRV_PRIO' => tr('Priority'),
		'TR_DNS_SRV_WEIGHT' => tr('Relative weight for records with the same priority'),
		'TR_DNS_SRV_HOST' => tr('Target host'),
		'TR_DNS_SRV_PORT' => tr('Target port'),
		'TR_DNS_CNAME' => tr('Canonical name'),
		'TR_DNS_PLAIN' => tr('Plain record data'),
		'TR_DNS_TXT_DATA' => tr('TXT data'),
		'TR_TITLE_CUSTOM_DNS_RECORD' => ($add_mode) ? tr("Add custom DNS record") : tr("Edit custom DNS record"),
		'TR_CUSTOM_DNS_RECORD_DATA' => tr('Custom DNS record data')));

generateNavigation($tpl);

$tpl->assign(($add_mode) ? 'FORM_EDIT_MODE' : 'FORM_ADD_MODE', '');

// "Modify" button has been pressed
$editid = null;
if (isset($_POST['uaction']) && ($_POST['uaction'] === 'modify')) {
	if (isset($_GET['edit_id'])) {
		$editid = $_GET['edit_id'];
	} else if (isset($_SESSION['edit_ID'])) {
		$editid = $_SESSION['edit_ID'];
	} else {
		unset($_SESSION['edit_ID']);
		not_allowed();
	}
	// Save data to db
	if (check_fwd_data($tpl, $editid)) {
		//$_SESSION['dnsedit'] = "_yes_";
		set_page_message(tr('Custom DNS record scheduled for update.'), 'success');
		redirectTo('domains_manage.php');
	}
} elseif (isset($_POST['uaction']) && ($_POST['uaction'] === 'add')) {
	if (check_fwd_data($tpl, true)) {
		//$_SESSION['dnsedit'] = "_yes_";
		set_page_message(tr('Custom DNS record scheduled for addition.'), 'success');
		redirectTo('domains_manage.php');
	}

} else {
	// Get user id that come for edit
	if (isset($_GET['edit_id'])) {
		$editid = $_GET['edit_id'];
	} else
		$editid = 0;
	$_SESSION['edit_ID'] = $editid;
}

gen_editdns_page($tpl, $editid);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();

/**
 * @param $object
 * @param null $default
 * @return array
 */
function mysql_get_enum($object, &$default = null) {

	list($table, $col) = explode(".", $object);

	$res = exec_query("SHOW COLUMNS FROM ".$table." LIKE '".$col."'");
	$row = $res->fetchRow();
	$default = $row['Default'];

	return (($row)
		? explode("','", preg_replace("/(enum|set)\('(.+?)'\)/","\\2", $row['Type']))
		: array(0 => 'None'));
}

/**
 * @param $data
 * @param null $value
 * @return string
 */
function create_options($data, $value = null) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$res = '';
	reset($data);

	foreach ($data as $item) {
		$res .= '<option value="' . $item . '"' .
				(($item == $value) ? $cfg->HTML_SELECTED : '') . '>' . $item .
				'</option>';
	}
	return $res;
}

/**
 * @return void
 */
function not_allowed() {
	$_SESSION['dnsedit'] = '_no_';
	redirectTo('domains_manage.php');
}

/**
 * @param $data
 * @return array
 */
function decode_zone_data($data) {

	$address = $addressv6 = $srv_name = $srv_proto = $cname = $txt = $name = '';
	$srv_TTL = $srv_prio = $srv_weight = $srv_host = $srv_port = '';

	if (is_array($data)) {
		$name = $data['domain_dns'];
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
					$srv_name = $srv[1];
					$srv_proto = $srv[2];
					$srv_TTL = $srv[3];
				}
				if (preg_match('~([\d]+)[\s]+([\d]+)[\s]+([\d]+)[\s]+([^\s]+)+~', $data['domain_text'], $srv)) {
					$srv_prio = $srv[1];
					$srv_weight = $srv[2];
					$srv_port = $srv[3];
					$srv_host = $srv[4];
				}
				break;
			case 'MX':
				$name = '';
				if (preg_match('~([\d]+)[\s]+([^\s]+)+~', $data['domain_text'], $srv)) {
					$srv_prio = $srv[1];
					$srv_host = $srv[2];
				}
				break;
			default:
				$txt = $data['domain_text'];
		}
	}
	return array(
		$name, $address, $addressv6, $srv_name, $srv_proto, $srv_TTL, $srv_prio,
		$srv_weight, $srv_host, $srv_port, $cname, $txt, $data['protected']
	);
}

/**
 * @param iMSCP_pTemplate $tpl
 * @param $edit_id
 * @return void
 */
function gen_editdns_page($tpl, $edit_id) {

	global $DNS_allowed_types;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	list(
		$dmn_id, $dmn_name,,,,,,,,,,,,,,,,,,,,,$dmn_dns
	) = get_domain_default_props($_SESSION['user_id']);

	if ($dmn_dns != 'yes') {
		not_allowed();
	}
	if ($GLOBALS['add_mode']) {
		$data = null;
		$query = "
			SELECT
				'0' AS `alias_id`,
				`domain`.`domain_name` AS `domain_name`
			FROM
				`domain`
			WHERE
				`domain_id` = :domain_id
			UNION
			SELECT
				`domain_aliasses`.`alias_id`,
				`domain_aliasses`.`alias_name`
			FROM
				`domain_aliasses`
			WHERE
				`domain_aliasses`.`domain_id` = :domain_id
			AND `alias_status` <> :state
		";

		$res = exec_query($query, array('domain_id' => $dmn_id, 'state' => $cfg->ITEM_ORDERED_STATUS));
		$sel = '';
		while ($row = $res->fetchRow()) {
			$sel.= '<option value="' . $row['alias_id'] . '">' .
					$row['domain_name'] . '</option>';
		}
		$tpl->assign('SELECT_ALIAS', $sel);

	} else {
		$query = "
            SELECT
                *
            FROM
                `domain_dns`
            WHERE
                `domain_dns_id` = ?
            AND
                `domain_id` = ?
		";
		$res = exec_query($query, array($edit_id, $dmn_id));
		if ($res->recordCount() <= 0)
		not_allowed();
		$data = $res->fetchRow();
		$tpl->assign('ADD_RECORD', '');
	}

	list(
		$name, $address, $addressv6, $srv_name, $srv_proto, $srv_ttl, $srv_prio,
		$srv_weight, $srv_host, $srv_port, $cname, $plain, $protected
	) = decode_zone_data($data);

	// Protection against edition (eg. for external mail MX record)
	if($protected == 'yes') {
		set_page_message(tr('You are not allowed to edit this DNS record.'), 'error');
		not_allowed();
	}

	$dns_type = create_options(array_intersect($DNS_allowed_types, mysql_get_enum("domain_dns.domain_type")), tryPost('type', $data['domain_type']));
	$dns_class = create_options(mysql_get_enum("domain_dns.domain_class"), tryPost('class', $data['domain_class']));

	$tpl->assign(
		array(
			'SELECT_DNS_TYPE'			=> $dns_type,
			'SELECT_DNS_CLASS'			=> $dns_class,
			'DNS_NAME'					=> tohtml($name),
			'DNS_ADDRESS'				=> tohtml(tryPost('dns_A_address', $address)),
			'DNS_ADDRESS_V6'			=> tohtml(tryPost('dns_AAAA_address', $addressv6)),
			'SELECT_DNS_SRV_PROTOCOL'	=> create_options(array('tcp', 'udp'), tryPost('srv_proto', $srv_proto)),
			'DNS_SRV_NAME'				=> tohtml(tryPost('dns_srv_name', $srv_name)),
			'DNS_SRV_TTL'				=> tohtml(tryPost('dns_srv_ttl', $srv_ttl)),
			'DNS_SRV_PRIO'				=> tohtml(tryPost('dns_srv_prio', $srv_prio)),
			'DNS_SRV_WEIGHT'			=> tohtml(tryPost('dns_srv_weight', $srv_weight)),
			'DNS_SRV_HOST'				=> tohtml(tryPost('dns_srv_host', $srv_host)),
			'DNS_SRV_PORT'				=> tohtml(tryPost('dns_srv_port', $srv_port)),
			'DNS_CNAME'					=> tohtml(tryPost('dns_cname', $cname)),
			'DNS_PLAIN'					=> tohtml(tryPost('dns_plain_data', $plain)),
			'DNS_TXT_DATA'				=> tohtml(tryPost('dns_txt_data', $plain)),
			'ID'						=> $edit_id
		)
	);
}

/**
 * @param $id
 * @param $data
 * @return
 */
function tryPost($id, $data) {

	if (array_key_exists($id, $_POST)) {
		return $_POST[$id];
	}
	return $data;
}

/**
 * @param $record
 * @param null $err
 * @return bool
 */
function validate_CNAME($record, &$err = null)
{
	if(!iMSCP_Validate::getInstance()->domainName($record['dns_cname'], array('tld' => false))) {
		$err .= tr('Usage of disallowed character in CNAME');
		return false;
	}

	if (empty($record['dns_name'])) {
		$err .= tr('Name must be filled.');
		return false;
	}

	return true;
}

/**
 * @param $record
 * @param null $err
 * @return bool
 */
function validate_A($record, &$err = null) {

	if (filter_var($record['dns_A_address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
		$err .= sprintf(tr('Wrong IPv4 address ("%s")'), $record['dns_A_address']);
		return false;
	}
	if (empty($record['dns_name'])) {
		$err .= tr('Name must be filled.');
		return false;
	}
	return true;
}

/**
 * @param $record
 * @param null $err
 * @return bool
 */
function validate_AAAA($record, &$err = null) {

	if (filter_var($record['dns_AAAA_address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
		$err .= sprintf(tr('Wrong IPv6 address ("%s")'), $record['dns_AAAA_address']);
		return false;
	}

	if (empty($record['dns_name'])) {
		$err .= tr('Name must be filled.');
		return false;
	}

	return true;
}

/**
 * @param $record
 * @param null $err
 * @return bool
 */
function validate_TXT($record, &$err = null) {

	if (!preg_match('/^([a-zA-Z0-9\+\?\-_~=:. \/])+$/', str_replace('"','',$record['dns_txt_data']))) {
		$err .= sprintf(tr('Invalid characters in TXT data ("%s")'), str_replace('"','',$record['dns_txt_data']));
		return false;
	}

	if (empty($record['dns_txt_data'])) {
		$err .= tr('TXT data must be filled.');
		return false;
	}
	return true;
}

/**
 * @param $record
 * @param $err
 * @param $dns
 * @param $text
 * @return bool
 */
function validate_SRV($record, &$err, &$dns, &$text) {

	if (!preg_match('~^([\d]+)$~', $record['dns_srv_port'])) {
		$err .= tr('Port must be a number!');
		return false;
	}

	if (!preg_match('~^([\d]+)$~', $record['dns_srv_ttl'])) {
		$err .= tr('TTL must be a number!');
		return false;
	}

	if (!preg_match('~^([\d]+)$~', $record['dns_srv_prio'])) {
		$err .= tr('Priority must be a number!');
		return false;
	}

	if (!preg_match('~^([\d]+)$~', $record['dns_srv_weight'])) {
		$err .= tr('Relative weight must be a number!');
		return false;
	}

	if (empty($record['dns_srv_name'])) {
		$err .= tr('Service must be filled.');
		return false;
	}

	if (empty($record['dns_srv_host'])) {
		$err .= tr('Host must be filled.');
		return false;
	}

	$dns = sprintf("_%s._%s\t%d", $record['dns_srv_name'], $record['srv_proto'], $record['dns_srv_ttl']);
	$text = sprintf("%d\t%d\t%d\t%s", $record['dns_srv_prio'], $record['dns_srv_weight'], $record['dns_srv_port'], $record['dns_srv_host']);

	return true;
}

/**
 * @param $record
 * @param $err
 * @param $text
 * @return bool
 */
function validate_MX($record, &$err, &$text) {

	// Add a dot in the end if not
	if (substr($record['dns_srv_host'], -1) != '.') {
		$record['dns_srv_host'] .= '.';
	}


	if (!preg_match('~^([\d]+)$~', $record['dns_srv_prio'])) {
		$err .= tr('Priority must be a number!');
		return false;
	}

	if (empty($record['dns_srv_host'])) {
		$err .= tr('Host must be filled.');
		return false;
	}

	$text = sprintf("%d\t%s", $record['dns_srv_prio'], $record['dns_srv_host']);
	return true;
}

/**
 * @param $domain
 * @param $err
 * @return bool
 */
function check_CNAME_conflict($domain,&$err) {

	$resolver = new Net_DNS_resolver();
	$resolver->nameservers = array('localhost');
	$res = $resolver->query($domain, 'CNAME');

	if ($res === false) {
		return true;
	}

	$err .= tr('conflict with CNAME record');
	return false;
}

/**
 * @param $domain
 * @param $err
 * @return bool
 */
function validate_NAME($domain, &$err)
{
	if(strpos($domain['name'], '.') === false) {
		$entry = $domain['name'].'.dummy';
	} else {
		$entry = $domain['name'];
	}

	if(!iMSCP_Validate::getInstance()->domainName($entry, array('tld' => false))) {
		$err .= tr('Invalid NAME');
		return false;
	}
	if (preg_match('/\.$/', $domain['name'])) {
		if (!preg_match('/'.str_replace('.', '\.', $domain['domain']).'\.$/', $domain['name'])) {
			$err .= sprintf(tr('Record "%s" is not part of domain "%s".', $domain['name'], $domain['domain']));
			return false;
		}
	}
	return true;
}
/**
 * @throws iMSCP_Exception_Database
 * @param $tpl
 * @param $edit_id
 * @return bool
 */
function check_fwd_data($tpl, $edit_id) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$add_mode = $edit_id === true;

	$admin_login = '';
	$err = '';

	$_text = '';
	$_class = $_POST['class'];
	$_type = $_POST['type'];

	list($dmn_id) = get_domain_default_props($_SESSION['user_id']);
	if ($add_mode) {
		$query = "
			SELECT
				*
			FROM (
				SELECT
					'0' AS `alias_id`,
					`domain`.`domain_name` AS `domain_name`
				FROM
					`domain`
				WHERE
					`domain_id` = ?
				UNION
				SELECT
					`domain_aliasses`.`alias_id`,
					`domain_aliasses`.`alias_name`
				FROM
					`domain_aliasses`
				WHERE
					`domain_aliasses`.`domain_id` = ?
			) AS `tbl`
			WHERE
				IFNULL(`tbl`.`alias_id`, 0) = ?
		";
		$res = exec_query($query, array($dmn_id, $dmn_id, $_POST['alias_id']));
		if ($res->recordCount() <= 0) {
			not_allowed();
		}
		$alias_id = $res->fetchRow();
		$record_domain = $alias_id['domain_name'];
		// if no alias is selected, ID is 0 else the real alias_id
		$alias_id = $alias_id['alias_id'];
	} else {
		$res = exec_query("
		SELECT
			 domain_dns.*,
			IFNULL(`domain_aliasses`.`alias_name`,`domain`.`domain_name`) AS `domain_name`
		FROM
			`domain_dns`
			LEFT JOIN `domain_aliasses` USING (`domain_id`, `alias_id`)
			LEFT JOIN `domain` USING (`domain_id`)
		WHERE
			`domain_dns_id` = ?
		AND
		`domain_id` = ?
		", array($edit_id, $dmn_id));
		if ($res->recordCount() <= 0) {
			not_allowed();
		}
		$data = $res->fetchRow();
		$record_domain = $data['domain_name'];
		$alias_id = $data['alias_id'];
		$_dns = $data['domain_dns'];
	}

	$nameValidationError = '';
	if(in_array($_POST['type'], array('A', 'AAAA', 'CNAME'))) {
		if(!validate_NAME(array('name' => $_POST['dns_name'], 'domain' => $record_domain), $nameValidationError)) {
			set_page_message(sprintf(tr("Cannot validate %s record. Reason: '%s'."), $_POST['type'], $nameValidationError), 'error');
		}
	}

	switch ($_POST['type']) {
		case 'CNAME':
			if (!validate_CNAME($_POST, $err))
				set_page_message(sprintf(tr("Cannot validate %s record. Reason: '%s'."), $_POST['type'], $err), 'error');
			$_text = $_POST['dns_cname'];
			$_dns = $_POST['dns_name'];
			break;
		case 'A':
			if (!validate_A($_POST, $err))
				set_page_message(sprintf(tr("Cannot validate %s record. Reason: '%s'."), $_POST['type'], $err), 'error');
			if (!check_CNAME_conflict($_POST['dns_name'].'.'.$record_domain, $err))
				set_page_message(sprintf(tr("Cannot validate %s record. Reason: '%s'."), $_POST['type'], $err), 'error');
			$_text = $_POST['dns_A_address'];
			$_dns = $_POST['dns_name'];
			break;
		case 'AAAA':
			if (!validate_AAAA($_POST, $err))
				set_page_message(sprintf(tr("Cannot validate %s record. Reason: '%s'."), $_POST['type'], $err), 'error');
			if (!check_CNAME_conflict($_POST['dns_name'].'.'.$record_domain, $err))
				set_page_message(sprintf(tr("Cannot validate %s record. Reason: '%s'."), $_POST['type'], $err), 'error');
			$_text = $_POST['dns_AAAA_address'];
			$_dns = $_POST['dns_name'];
			break;
		case 'SRV':
			if (!validate_SRV($_POST, $err, $_dns, $_text))
				set_page_message(sprintf(tr("Cannot validate %s record. Reason: '%s'."), $_POST['type'], $err), 'error');
			break;
		case 'MX':
			$_dns = '';
			if (!validate_MX($_POST, $err, $_text)) {
				set_page_message(sprintf(tr("Cannot validate %s record. Reason: '%s'."), $_POST['type'], $err), 'error');
			} else {
				$_dns = $record_domain . '.';
			}
			break;
		case 'TXT':
			if (!validate_TXT($_POST, $err)) {
				set_page_message(sprintf(tr("Cannot validate %s record. Reason: '%s'."), $_POST['type'], $err), 'error');
			} else {
				
				$_text = "\"".str_replace('"','',$_POST['dns_txt_data'])."\"";
				$_dns = $record_domain . '.';
			}
			break;
		default :
			set_page_message(sprintf(tr('Unknown zone type %s.'), $_POST['type']), 'error');
	}

	if (!Zend_Session::namespaceIsset('pageMessages')) {
		if ($add_mode) {
			$query = "
				INSERT INTO
					`domain_dns` (
						`domain_id`, `alias_id`, `domain_dns`, `domain_class`,
						`domain_type`, `domain_text`
					) VALUES (
						?, ?, ?, ?, ?, ?
					)
			";

			$rs = exec_query(
				$query,
				array($dmn_id, $alias_id, $_dns, $_class, $_type, $_text),
				false
			);

			# Error because duplicate entry ? (SQLSTATE 23000)
			if($rs === false) {
                /** @var $db iMSCP_Database */
                $db = iMSCP_Registry::get('db');
				if($db->getLastErrorCode() == 23000) {
					set_page_message(tr('DNS record already exist.'), 'error');
					return false;
				} else { # Another error ? Throw exception
					throw new iMSCP_Exception_Database(
						$db->getLastErrorMessage() . " - Query: $query"
					);
				}
			}

		} else {
			$query = "
				UPDATE
					`domain_dns`
				SET
					`domain_dns` = ?, `domain_class` = ?, `domain_type` = ?,
					`domain_text` = ?
				WHERE
					`domain_dns_id` = ?
			";

			exec_query(
				$query, array($_dns, $_class, $_type, $_text, $edit_id)
			);
		}

		if ($alias_id == 0) {

			$query = "
				UPDATE
					`domain`
 				SET
					`domain`.`domain_status` = ?
 				WHERE
    				`domain`.`domain_id` = ?
   			";

			exec_query(
				$query, array($cfg->ITEM_DNSCHANGE_STATUS, $dmn_id)
			);

		} else {

			$query = "
 				UPDATE
 					`domain_aliasses`
				SET
					`domain_aliasses`.`alias_status` = ?
 				WHERE
					`domain_aliasses`.`domain_id` = ?
				AND	`domain_aliasses`.`alias_id` = ?
			";

			exec_query(
				$query,
				array($cfg->ITEM_DNSCHANGE_STATUS, $dmn_id, $alias_id)
			);
		}

		// Send request to i-MSCP daemon
		send_request();

		$admin_login = $_SESSION['user_logged'];
		write_log("$admin_login: " . (($add_mode) ? 'add new' : ' modify') . " dns zone record.", E_USER_NOTICE);

		unset($_SESSION['edit_ID']);
		return true;
	} else {
		return false;
	}
} // End of check_user_data()
