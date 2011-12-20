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
 * The Original Code is "ispCP - isp Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010-2011 by i-MSCP | http://i-mscp.net
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
 */


/***********************************************************************************************************************
 * Script functions
 */

/**
 * Generates form.
 *
 * @param iMSCP_pTemplate $tpl
 * @param $scriptMode
 */
function client_generateForm($tpl, $scriptMode)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$domainProperties = get_domain_default_props($_SESSION['user_id'], true);

	if($scriptMode == 'add') {
		$query = "
			SELECT
				`domain_id` `zone_id`, `domain_name` `zone_name`, 'domain' `zone_type`
			FROM
				`domain`
			WHERE
				`domain_id` = :domain_id
			UNION
			SELECT
				`alias_id` `zone_id`, `alias_name` `zone_name`, 'domain_alias' `zone_type`
			FROM
				`domain_aliasses`
			WHERE
				`domain_id` = :domain_id
			AND
				`alias_status` != :status
		";
		$stmt = exec_query($query, array('domain_id' => $domainProperties['domain_id'], 'status' => $cfg->ITEM_ORDERED_STATUS));

		// Generate zone liste
		while(!$stmt->EOF) {
			$tpl->assign(array(
				'ZONE_NAME' => $stmt->fields['zone_name'],
				'ZONE_TYPE' => $stmt->fields['zone_type'],
				'ZONE_ID' => $stmt->fields['zone_id'],
				'SELECTED_ZONE' => ''));

			$tpl->parse('ZONE_BLOCK', '.zone_block');
			$stmt->moveNext();
		}

		// Generates type list
		foreach(array('MX', 'A', 'AAAA', 'CNAME', 'SRV') as $type) {
			$tpl->assign(array(
				'RR' => $type,
				'SELECTED_RR' => ''));

			$tpl->parse('RR_BLOCK', '.rr_block');
			$stmt->moveNext();
		}

		// Generates class list
		foreach(array('IN', 'CH', 'HS') as $class) {
			$tpl->assign(array(
				'CLASS' => $class,
				'SELECTED_CLASS' => ''));

			$tpl->parse('CLASS_BLOCK', '.class_block');
			$stmt->moveNext();
		}

		// Generates class list
		foreach(array(
					'_http' => tr('Web service'),
					'_ftp' => tr('File transfer service'),
					'_ldap' => tr('LDAP service'),
					'_imap' => tr('IMAP mail service'),
					'_PKIXREP' => tr('PKIX Repository (X.509 certificates)')) as $srvProtocol => $description
		) {
			$tpl->assign(array(
				'TR_PROTOCOL_VALUE' => $description,
				'PROTOCOL' => $srvProtocol,
				'SELECTED_PROTOCOL' => ''));

			$tpl->parse('PROTOCOL_BLOCK', '.protocol_block');
			$stmt->moveNext();
		}

		$tpl->assign(
			array(
				'NAME' => '',
				'TTL' => '36400',
				'PRIORITY' => '',
				'WEIGHT' => '',
				'PORT' => '',
				'HOST' => '',
				'A' => '',
				'AAAA' => '',
				'CNAME' => '',
				'EDIT_BLOCK' => ''
			)
		);
	}
}

/**
 * Check that a Resource record is valid.
 *
 * @param string $scriptMode
 * @param string $rr Resource record type
 * @param array $data Resource record data
 * @return bool TRUE if RR is valid, FALSE otherwise.
 */
function client_isValidRR($scriptMode, $rr, $data)
{
	$type = strtoupper($rr);

	$retVal=  true;

	// Pre-check for new RR

	if($scriptMode == 'add') {
		if(isset($data['zone'])) {

		} else {
			set_page_message('Wrong request.');
			$retVal = false;
		}
	}

	if ($retVal) {
		switch ($rr) {
			case 'NS': // Not implemented yet
				// check name, ttl, class, host
				set_page_message('NS RR is not supported yet');
				$retVal = false;
			case 'MX':
				// check name, ttl, class, priority and host
			case 'A':
				// check name, ttl, class, ipv4
			case 'AAAA':
				// check name, ttl, class ipv6
			case 'CNAME':
				// check name, ttl, class, host (canonical name)
			case 'SRV':
				// check protocol, service name, ttl, class, priority, weight, port, host
		}
	}

	return $retVal;
}

/**
 * Returns AXFR result for given zone.
 *
 * @param string $zoneName Zone name to be queried
 * @return string
 */
function client_getZoneAXFR($zoneName)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$resolver = new Net_DNS_Resolver();
	$resolver->nameservers(array($cfg->BASE_SERVER_IP, '127.0.0.1'));

	$response = $resolver->axfr($zoneName);

	if($response) {
		return $response;
	} else {
		write_log("System is unable to query the DNS server (AXFR query for the $zoneName zone).", E_USER_ERROR);
		return tr('Unable to query the DNS server or unexpected result. Please, contact your reseller for more information.');
	}
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

// Temporary fix for all 'Strict Standards' errors that come from the Net_DNS library
error_reporting(E_ALL);

require_once 'vendor/Net/DNS.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('custom_dns_records')) {
    redirectTo('index.php');
}

	//client_getZoneAXFR('nuxwin.net');
	//exit;

// Dispatches the request

if(is_xhr() && isset($_GET['zone'])) {
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-cache, private');
	header('Pragma: no-cache');
	echo json_encode(client_getZoneAXFR($_GET['zone']));
	exit;
} elseif(!empty($_POST)) {
	echo '<pre>';
	print_r($_POST);
	exit;

	if(isset($_POST['uaction'])) {
		if($_POST['uaction'] == 'add') {

		} elseif($_POST['uaction'] == 'update') {

		} else {
			set_page_message('Wrong request.');
		}
	} else {
		set_page_message('Wrong request.');
	}
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'page' => $cfg->CLIENT_TEMPLATE_PATH . '/dns_edit.tpl',
		'page_message' => 'page',
		'rr_block' => 'page',
		'add_block1' => 'page',
		'add_block2' => 'page',
		'edit_block' => 'page',
		'zone_block' => 'page',
		'rr_block' => 'page',
		'class_block' => 'page',
		'protocol_block' => 'page'));

$scriptMode = (strpos($_SERVER['REQUEST_URI'], 'add') !== false) ? 'add' : 'edit';

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => ($scriptMode == 'add')
			? tr('i-MSCP - Manage Domain / Add custom DNS resource record')
			: tr('i-MSCP - Manage Domain / Edit custom DNS resource record'),
		'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'ACTION_MODE' => ($scriptMode == 'add') ? 'dns_add.php' : 'dns_edit.php?edit_id={ID}',
		'TR_ZONE' => tr('Zone'),






		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'TR_RECORD_DATA' => tr('Record data'),
		'UNEXPECTED_ERROR' => json_encode(tr('Unexpected error occured.')),


		'TR_EDIT_DNS' => ($scriptMode == 'add') ? tr('Add custom DNS resource record') : tr('Edit custom resource DNS record'),
		'TR_CUSTOM_DNS_RECORD' => tr("Custom DNS resource record"),
		'TR_PROTOCOL' => tr('Service protocol'),
		'TR_NAME' => tr('Name'),
		'TR_CLASS' => tr('Class'),
		'TR_TYPE' => tr('Type'),
		'TR_TTL' => tr('TTL'),
		'TR_A' => tr('IPv4 address'),
		'TR_AAAA' => tr('IPv6 address'),
		'TR_PRIORITY' => tr('Priority'),
		'TR_WEIGHT' => tr('Weight'),
		'TR_HOST' => tr('Host'),
		'TR_JHOST' => json_encode(tr('Host')),
		'TR_PORT' => tr('Port'),
		'TR_JCNAME' => json_encode(tr('Canonical name')),
		'TR_TITLE_CUSTOM_DNS_RECORD' => ($scriptMode == 'add') ? tr('Add custom DNS record') : tr('Edit custom DNS record'),
		'TR_ADD' => tr('Add'),
		'TR_UPDATE' => tr('Update'),
		'TR_CANCEL' => tr('Cancel')));

gen_client_mainmenu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/main_menu_manage_domains.tpl');
gen_client_menu($tpl, $cfg->CLIENT_TEMPLATE_PATH . '/menu_manage_domains.tpl');
client_generateForm($tpl, $scriptMode);
generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
