<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
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
 * @package     Client_Domains_Aliases
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get domain alias data
 *
 * @access private
 * @param int $domainAliasId Subdomain unique identifier
 * @return array Domain alias data. If any error occurs FALSE is returned
 */
function _client_getAliasData($domainAliasId)
{
	static $domainAliasData = null;

	if (null === $domainAliasData) {
		$mainDmnProps = get_domain_default_props($_SESSION['user_id']);
		$domainId = $mainDmnProps['domain_id'];

		$query = "
			SELECT
				`alias_name`, `url_forward` AS `forward_url`
			FROM
				`domain_aliasses`
			WHERE
				`alias_id` = ?
			AND
				`domain_id` = ?
			AND
				`alias_status` = ?
		";
		$stmt = exec_query($query, array($domainAliasId, $domainId, 'ok'));

		if (!$stmt->rowCount()) {
			return false;
		}

		$domainAliasData = $stmt->fetchRow(PDO::FETCH_ASSOC);
		$domainAliasData['alias_name_utf8'] = decode_idna($domainAliasData['alias_name']);
	}

	return $domainAliasData;
}

/**
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
 * @return void
 */
function client_generatePage($tpl)
{
	if (isset($_GET['id'])) {
		$domainAliasId = clean_input($_GET['id']);

		if (!($domainAliasData = _client_getAliasData($domainAliasId))) {
			showBadRequestErrorPage();
		}

		if (empty($_POST)) {
			if ($domainAliasData['forward_url'] != 'no') {
				$urlForwarding = true;
				$uri = iMSCP_Uri_Redirect::fromString($domainAliasData['forward_url']);
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
				'DOMAIN_ALIAS_ID' => $domainAliasId,
				'DOMAIN_ALIAS_NAME' => tohtml($domainAliasData['alias_name_utf8']),
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
 * Edit domain alias
 *
 * @return bool TRUE on success, FALSE on failure
 */
function client_editDomainAlias()
{
	if (isset($_GET['id'])) {
		$domainAliasId = clean_input($_GET['id']);

		if (($domainAliasData = _client_getAliasData($domainAliasId))) {
			// Check for URL forwarding option

			$forwardUrl = 'no';

			if (isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes') { // We are safe here
				if (isset($_POST['forward_url_scheme']) && isset($_POST['forward_url'])) {
					$forwardUrl = clean_input($_POST['forward_url_scheme']) . clean_input($_POST['forward_url']);

					try {
						try {
							$uri = iMSCP_Uri_Redirect::fromString($forwardUrl);
						} catch(Zend_Uri_Exception $e) {
							throw new iMSCP_Exception(tr('Forward URL %s is not valid.', "<strong>$forwardUrl</strong>"));
						}

						$uri->setHost(encode_idna($uri->getHost()));

						if ($uri->getHost() == $domainAliasData['alias_name'] && $uri->getPath() == '/') {
							throw new iMSCP_Exception(
								tr('Forward URL %s is not valid.', "<strong>$forwardUrl</strong>") . ' ' .
								tr(
									'Domain alias %s cannot be forwarded on itself.',
									"<strong>{$domainAliasData['alias_name_utf8']}</strong>"
								)
							);
						}

						$forwardUrl = $uri->getUri();
					} catch (Exception $e) {
						set_page_message($e->getMessage(), 'error');
						return false;
					}
				} else {
					showBadRequestErrorPage();
				}
			}

			iMSCP_Events_Aggregator::getInstance()->dispatch(
				iMSCP_Events::onBeforeEditDomainAlias, array('domainAliasId' => $domainAliasId)
			);

			exec_query(
				'UPDATE `domain_aliasses` SET `url_forward` = ?, `alias_status` = ? WHERE `alias_id` = ?',
				array($forwardUrl, 'tochange', $domainAliasId)
			);

			iMSCP_Events_Aggregator::getInstance()->dispatch(
				iMSCP_Events::onAfterEditDomainALias, array('domainAliasId' => $domainAliasId)
			);

			send_request();

			write_log(
				"{$_SESSION['user_logged']}: scheduled update of domain alias: {$domainAliasData['alias_name_utf8']}.",
				E_USER_NOTICE
			);
		} else {
			showBadRequestErrorPage();
		}
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

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('domain_aliases') or showBadRequestErrorPage();

if (!empty($_POST) && client_editDomainAlias()) {
	set_page_message(tr('Domain alias successfully scheduled for update.'), 'success');
	redirectTo('domains_manage.php');
} else {
	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'client/alias_edit.tpl',
			'page_message' => 'layout'
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Client / Domains / Edit Domain Alias'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_DOMAIN_ALIAS' => tr('Domain alias'),
			'TR_DOMAIN_ALIAS_NAME' => tr('Domain alias name'),
			'TR_URL_FORWARDING' => tr('URL forwarding'),
			'TR_FORWARD_TO_URL' => tr('Forward to URL'),
			'TR_URL_FORWARDING_TOOLTIP' => tr('Allows to forward any request made to this domain alias to a specific URL.'),
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

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();

	unsetMessages();
}
