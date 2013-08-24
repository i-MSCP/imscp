<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP Team
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
 * @package     Client_Domains
 * @copyright   2010-2013 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get subdomain data
 *
 * @access private
 * @param int $subdomainId Subdomain unique identifier
 * @param string $subdomainType Subdomain Type
 * @return array Subdomain data. If any error occurs FALSE is returned
 */
function _client_getSubdomainData($subdomainId, $subdomainType)
{
	static $subdomainData = null;

	if (null === $subdomainData) {
		$mainDmnProps = get_domain_default_props($_SESSION['user_id']);
		$domainId = $mainDmnProps['domain_id'];
		$domainName = $mainDmnProps['domain_name'];

		if ($subdomainType == 'dmn') {
			$query = '
				SELECT
					`subdomain_name` `subdomain_name` , `subdomain_url_forward` `forward_url`
				FROM
					`subdomain`
				WHERE
					`domain_id` = ?
				AND
					`subdomain_id` = ?
				';
		} else {
			$query = '
				SELECT
					`t1`.`subdomain_alias_name` `subdomain_name`, `t1`.`subdomain_alias_url_forward` `forward_url`,
					`t2`.`alias_name` `aliasName`
				FROM
					`subdomain_alias` `t1`
				INNER JOIN
					`domain_aliasses` `t2` ON (`t1`.`alias_id` = `t2`.`alias_id`)
				WHERE
					`t1`.`domain_id` = ?
				AND
					`subdomain_alias_id` = ?
			';
		}
		$stmt = exec_query($query, array($domainId, $subdomainId));

		if (!$stmt->rowCount()) {
			return false;
		}

		$subdomainData = $stmt->fetchRow(PDO::FETCH_ASSOC);

		if ($subdomainType == 'dmn') {
			$subdomainData['subdomain_name'] .= '.' . $domainName;
		} else {
			$subdomainData['subdomain_name'] .= '.' . $subdomainData['aliasName'];
		}
	}

	return $subdomainData;
}

/**
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
 * @return void
 */
function client_generatePage($tpl)
{
	if (isset($_GET['id']) && isset($_GET['type']) && ($_GET['type'] == 'dmn' || $_GET['type'] == 'als')) {
		$subdomainId = clean_input($_GET['id']);
		$subdomainType = clean_input($_GET['type']);

		if (!($subdomainData = _client_getSubdomainData($subdomainId, $subdomainType))) {
			showBadRequestErrorPage();
		}

		if (empty($_POST)) {
			if ($subdomainData['forward_url'] != 'no') {
				$urlForwarding = true;
				$uri = iMSCP_Uri_Redirect::fromString($subdomainData['forward_url']);
				$forwardUrlScheme = $uri->getScheme();
				$forwardUrl = substr($uri->getUri(), strlen($forwardUrlScheme) + 3);
			} else {
				$urlForwarding = false;
				$forwardUrlScheme = 'http://';
				$forwardUrl = '';
			}
		} else {
			$urlForwarding = (isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes') ? true : false;
			$forwardUrlScheme = (isset($_POST['forward_url_scheme'])) ? $_POST['forward_url_scheme'] : 'http://';
			$forwardUrl = isset($_POST['forward_url']) ? $_POST['forward_url'] : '';
		}

		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		$checked = $cfg->HTML_CHECKED;
		$selected = $cfg->HTML_SELECTED;

		$tpl->assign(
			array(
				'SUBDOMAIN_ID' => $subdomainId,
				'SUBDOMAIN_TYPE' => $subdomainType,
				'SUBDOMAIN_NAME' => tohtml(decode_idna($subdomainData['subdomain_name'])),
				'FORWARD_URL_YES' => ($urlForwarding) ? $checked : '',
				'FORWARD_URL_NO' => ($urlForwarding) ? '' : $checked,
				'HTTP_YES' => ($forwardUrlScheme == 'http://') ? $selected : '',
				'HTTPS_YES' => ($forwardUrlScheme == 'https://') ? $selected : '',
				'FTP_YES' => ($forwardUrlScheme == 'ftp://') ? $selected : '',
				'FORWARD_URL' => tohtml(decode_idna($forwardUrl))
			)
		);
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Edit subdomain
 *
 * @return bool TRUE on success, FALSE on failure
 */
function client_editSubdomain()
{
	if (isset($_GET['id']) && isset($_GET['type']) && ($_GET['type'] == 'dmn' || $_GET['type'] == 'als')) {

		$subdomainId = clean_input($_GET['id']);
		$subdomainType = clean_input($_GET['type']);

		if (!($subdomainData = _client_getSubdomainData($subdomainId, $subdomainType))) {
			showBadRequestErrorPage();
		}

		// Check for URL forwarding option

		$forwardUrl = 'no';

		if (isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes') { // We are safe here
			if (isset($_POST['forward_url_scheme']) && isset($_POST['forward_url'])) {
				$forwardUrl = clean_input($_POST['forward_url_scheme']) . clean_input($_POST['forward_url']);

				try {
					$uri = iMSCP_Uri_Redirect::fromString($forwardUrl);

					if (!$uri->valid()) {
						throw new iMSCP_Exception('Invalid URI');
					}

					$uri->setHost(encode_idna($uri->getHost()));
					$forwardUrl = $uri->getUri();
				} catch (Exception $e) {
					set_page_message(tr('Forward URL %s is not valid', "<strong>$forwardUrl</strong>"), 'error');
					return false;
				}
			} else {
				showBadRequestErrorPage();
			}
		}

		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		iMSCP_Events_Manager::getInstance()->dispatch(
			iMSCP_Events::onBeforeEditSubdomain, array('subdomainId' => $subdomainId)
		);

		if ($subdomainType == 'dmn') {
			$query = '
				UPDATE
					`subdomain`
				SET
					`subdomain_url_forward` = ?, `subdomain_status` = ?
				WHERE
					`subdomain_id` = ?
			';
		} else {
			$query = '
				UPDATE
					`subdomain_alias`
				SET
					`subdomain_alias_url_forward` = ?, `subdomain_alias_status` = ?
				WHERE
					`subdomain_alias_id` = ?
			';
		}

		exec_query($query, array($forwardUrl, $cfg->ITEM_TOCHANGE_STATUS, $subdomainId));

		iMSCP_Events_Manager::getInstance()->dispatch(
			iMSCP_Events::onAfterEditSubdomain, array('subdomainId' => $subdomainId)
		);

		write_log(
			 "{$_SESSION['user_logged']}: scheduled update of subdomain: " . decode_idna($subdomainData['subdomain_name']),
			E_USER_NOTICE
		);
		send_request();
	} else {
		showBadRequestErrorPage();
	}

	return true;
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('subdomains') or showBadRequestErrorPage();

if (!empty($_POST) && client_editSubdomain()) {
	set_page_message(tr('Subdomain successfully scheduled for update'), 'success');
	redirectTo('domains_manage.php');
} else {
	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'client/subdomain_edit.tpl',
			'page_message' => 'layout',
			'domain_option' => 'page',
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Client / Domains / Edit Subdomain'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_SUBDOMAIN' => tr('Subdomain'),
			'TR_SUBDOMAIN_NAME' => tr('Subdomain name'),
			'TR_URL_FORWARDING' => tr('URL forwarding'),
			'TR_FORWARD_TO_URL' => tr('Forward to URL'),
			'TR_URL_FORWARDING_TOOLTIP' => tr('Allows to forward any request made to this subdomain to a specific URL.'),
			'TR_YES' => tr('Yes'),
			'TR_NO' => tr('No'),
			'TR_HTTP' => 'http://',
			'TR_HTTPS' => 'https://',
			'TR_FTP' => 'ftp://',
			'TR_UPDATE' => tr('Update'),
			'TR_CANCEL' => tr('Cancel')
		)
	);

	generateNavigation($tpl);
	client_generatePage($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();

	unsetMessages();
}
