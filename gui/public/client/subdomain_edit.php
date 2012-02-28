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

/************************************************************************************
 * Script functions / classes
 */

/**
 * Class that wrap some form element values and that allows to validate its.
 *
 * @author	Laurent Declercq <l.declercq@nuxwin.com>
 * @version	0.0.1
 * @todo replace this class by Zend_Form component
 */
class client_subdomainEditForm
{
	/**
	 * Contains the 'subdomainName' form element value.
	 *
	 * @var string
	 */
	public $subdomainName = '';

	/**
	 * Contains the 'urlRedirect' form element value.
	 *
	 * @var string
	 */
	public $urlRedirect = 0;

	/**
	 * Contains the 'scheme' form element value.
	 *
	 * @var string
	 */
	public $scheme = 'http://';

	/**
	 * Contains the 'schemeSpecific' form element value.
	 *
	 * @var string
	 */
	public $schemeSpecific = '';

	/**
	 * Set form element values from an associative array.
	 *
	 * @param array $elements Form
	 * @return void
	 */
	public function setFromArray(array $elements)
	{
		foreach($elements as $element => $value) {
			if(property_exists($this, $element)) {
				$this->{$element} = strtolower(clean_input($value));
			}
		}
	}

	/**
	 * Validate the form elements values.
	 *
	 * @return bool TRUE if all form elements value are valid, FALSE otherwise
	 */
	public function isValid()
	{
		return $this->validateUrlRedirect()
			and $this->validateScheme()
			and $this->validateSchemeSpecific();
	}

	/**
	 * Validate the 'urlRedirect' form element value.
	 *
	 * @return bool TRUE if valid, FALSE otherwise
	 */
	public function validateUrlRedirect()
	{
		$urlRedirect = (int) $this->urlRedirect;

		if($urlRedirect !== 0 && $urlRedirect !== 1) {
			return false;
		}

		return true;
	}

	/**
	 * Validates the 'scheme' form element value.
	 *
	 * @return bool TRUE if valid, FALSE otherwise
	 */
	public function validateScheme()
	{
		return in_array($this->scheme, array('http://', 'https://', 'ftp://'));
	}

	/**
	 * Validates the 'schemeSpecific' form element value.
	 *
	 * @return bool TRUE if valid, FALSE otherwise
	 */
	public function validateSchemeSpecific()
	{
		$uriValidator = new iMSCP_Validate_Uri();

		if($this->schemeSpecific != '' || $this->urlRedirect) {
			// Scheme is not relevant here (Zend validator only support http(s)
			if (!$uriValidator->isValid($this->scheme . $this->schemeSpecific)) {
				return false;
			}
		}

		return true;
	}
}

/**
 * Subdomain edit action.
 *
 * @return void
 */
function client_subdomainEditAction()
{
	if(!isset($_GET['id']) || !isset($_GET['type']) || ($_GET['type'] != 'dmn' &&
	   $_GET['type'] != 'als')
	) {
		set_page_message('Wrong request.', 'error');
		redirectTo('domains_manage.php');
		exit; // Useless but avoid IDE warning - See http://youtrack.jetbrains.net/issue/WI-7462
	} else {
		$subdomainId = clean_input($_GET['id']);
		$subdomainType = clean_input($_GET['type']);
	}

	if(!($subdomainData = _client_getSubdomainData($subdomainId, $subdomainType))) {
		redirectTo('domains_manage.php');
	}

	$form = new client_subdomainEditForm();

	if($_SERVER['REQUEST_METHOD'] == 'POST') {
		if(isset($_POST['cancel'])) { // Cancel requested by user ?
			redirectTo('domains_manage.php');
		}

		$form->setFromArray($_POST);

		if($form->isValid()) {
			if($form->urlRedirect) {
				$forwardUrl = $form->scheme . $form->schemeSpecific;
			} else {
				$forwardUrl = '';
			}

			$subdomainData['schemeSpecific'] = ($subdomainData['schemeSpecific'] != 'no')
				? $subdomainData['schemeSpecific'] : '';

			if($subdomainData['schemeSpecific'] != $form->schemeSpecific) {
				_client_updateSubdomainData($subdomainId, $subdomainType, $forwardUrl);
			} else {
				set_page_message(tr('Nothing been changed.'), 'info');
				redirectTo('domains_manage.php');
			}

			// Send request to the i-MSCP daemon for backend process
			send_request();

			set_page_message(tr('Subdomain scheduled for update.'), 'success');
			redirectTo('domains_manage.php');
		} else {
			set_page_message(tr('Wrong redirect URL.'), 'error');
		}
	} else {
		// Dealing with data

		if($subdomainData['schemeSpecific'] != 'no') {
			$subdomainData['urlRedirect'] = 1;
			list($subdomainData['scheme'], $subdomainData['schemeSpecific']) = explode(
				'://', $subdomainData['schemeSpecific']);

			$subdomainData['scheme'] .= '://';
		} else {
			$subdomainData['schemeSpecific'] = '';
		}

		$form->setFromArray($subdomainData);
	}

	// Process view

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$tpl = _client_initView();
	$tpl->assign(
		array(
			'TR_MANAGE_SUBDOMAIN' => tr('Manage subdomain'),
			'TR_EDIT_SUBDOMAIN' => tr('Edit subdomain'),
			'TR_SUBDOMAIN_NAME' => tr('Subdomain name'),
			'TR_REDIRECT_URL' => tr('Redirect to URL'),
			'TR_UPDATE' => tr('Update'),
			'TR_CANCEL' => tr('Cancel'),
			'TR_REDIRECT' => tr('Redirect'),
			'TR_ENABLE' => tr('Enable'),
			'TR_DISABLE' => tr('Disable'),
			'TR_HTTP_SCHEME' => 'http://',
			'TR_HTTPS_SCHEME' => 'https://',
			'TR_FTP_SCHEME' => 'ftp://',
			'SUBDOMAIN_ID' => $subdomainId,
			'SUBDOMAIN_TYPE' => $subdomainType,
			'SUBDOMAIN_NAME' => decode_idna($form->subdomainName),
			'RADIO_ENABLED' => $form->urlRedirect ? $cfg->HTML_CHECKED : '',
			'RADIO_DISABLED' => $form->urlRedirect ? '' : $cfg->HTML_CHECKED,
			'INPUT_READONLY' => $form->urlRedirect ? '' : $cfg->HTML_READONLY,
			'SELECT_DISABLED' => $form->urlRedirect ? '' : $cfg->HTML_DISABLED,
			'SCHEME_SPECIFIC' => $form->schemeSpecific));

	foreach(array('http://', 'https://', 'ftp://') as $scheme) {
		$tpl->assign(
			array(
				'SCHEME' => $scheme,
				'SELECTED' => ($form->scheme == $scheme)
					? $cfg->HTML_SELECTED : ''));

		$tpl->parse('SCHEME_OPTIONS', '.scheme_options');
	}
}

/**
 * Initialize view and register ptemplate instance in registry for further usage.
 *
 * @access private
 * @return iMSCP_ptemplate
 */
function _client_initView()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
	$tpl->define_dynamic(
		array(
			'page' => 'client/subdomain_edit.tpl',
			'page_message' => 'layout',
			'scheme_options' => 'page'));

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('i-MSCP - Manage domains / Edit Subdomain'),
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => layout_getUserLogo()));

	generateNavigation($tpl);

	return iMSCP_Registry::set('templateEngine', $tpl);
}

/**
 * Returns subdomain data.
 *
 * @access private
 * @param $subdomainId Subdomain unique identifier
 * @param $subdomainType Subdomain Type
 * @return array Subdomain data. If any error occurs FALSE is returned
 */
function _client_getSubdomainData($subdomainId, $subdomainType)
{
	$subdomainId = (int) $subdomainId;
	$subdomainType = (string) $subdomainType;

	// Getting some user's domain data
	list($domainId, $domainName) = get_domain_default_props($_SESSION['user_id']);

	if($subdomainType == 'dmn') {
		$query = '
			SELECT
				`subdomain_name` `subdomainName` , `subdomain_url_forward`
				`schemeSpecific`
			FROM
				`subdomain`
			WHERE
				`domain_id` = ?
			AND
				`subdomain_id` = ?
			';
	} elseif($subdomainType == 'als') {
		$query = '
			SELECT
				`t1`.`subdomain_alias_name` `subdomainName`,
				`t1`.`subdomain_alias_url_forward` `schemeSpecific`,
				`t2`.`alias_name` `aliasName`
			FROM
				`subdomain_alias` `t1`
			LEFT JOIN
				`domain_aliasses` `t2` ON (t1.`alias_id` = t2.`alias_id`)
			WHERE
				t1.`alias_id` IN (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = ?)
			AND
				`subdomain_alias_id` = ?
		';
	} else {
		set_page_message(tr('Unknown subdomain type.'), 'error');
		return false;
	}

	$stmt = exec_query($query, array($domainId, $subdomainId));

	if($stmt->rowCount() == 0) {
		set_page_message(tr('No corresponding subdomain found.'), 'error');
		return false;
	}

	$subdomainData = $stmt->fetchRow();

	if($subdomainType == 'dmn') {
		$subdomainData['subdomainName'] .= ".$domainName";
	} else {
		$subdomainData['subdomainName'] .= '.' . $subdomainData['aliasName'];
	}

	return $subdomainData;
}

/**
 * Update subdomain data.
 *
 * @access private
 * @param int $subdomainId Subdomain unique identifier
 * @param string $subdomainType Subdomain type (dmn|als)
 * @param string $forwardUrl Forward url
 * @return bool TRUE on success, FALSE otherwise
 */
function _client_updateSubdomainData($subdomainId, $subdomainType, $forwardUrl)
{
	$forwardUrl = empty($forwardUrl) ? 'no' : $forwardUrl;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($subdomainType == 'dmn') {
		$query = '
			UPDATE
				`subdomain`
			SET
				`subdomain_url_forward` = ?, `subdomain_status` = ?
			WHERE
				`subdomain_id` = ?
		';
	} elseif ($subdomainType == 'als') {
		$query = '
			UPDATE
				`subdomain_alias`
			SET
				`subdomain_alias_url_forward` = ?, `subdomain_alias_status` = ?
			WHERE
				`subdomain_alias_id` = ?
		';
	} else {
		return false;
	}

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onBeforeEditSubdomain, array('subdomainId' => $subdomainId));

	exec_query($query, array($forwardUrl, $cfg->ITEM_CHANGE_STATUS, $subdomainId));

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAfterEditSubdomain, array('subdomainId' => $subdomainId));

	return true;
}

/************************************************************************************
 * Main script
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('subdomains')) {
    redirectTo('index.php');
}

client_subdomainEditAction();

/** @var $tpl iMSCP_pTemplate */
$tpl = iMSCP_Registry::get('templateEngine');

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
