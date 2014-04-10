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
 * @package     Client_Subdomains
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get domains list
 *
 * @return array Domains list
 */
function _client_getDomainsList()
{
	static $domainsList = null;

	if (null === $domainsList) {
		$mainDmnProps = get_domain_default_props($_SESSION['user_id']);

		$domainsList = array(
			array(
				'name' => $mainDmnProps['domain_name'],
				'id' => $mainDmnProps['domain_id'],
				'type' => 'dmn',
				'mount_point' => '/'
			)
		);

		$query = "
			SELECT
				CONCAT(`t1`.`subdomain_name`, '.', `t2`.`domain_name`) AS `name`, `t1`.`subdomain_id` AS `id`,
				'sub' AS `type`, `t1`.`subdomain_mount` AS `mount_point`
			FROM
				`subdomain` AS `t1`
			INNER JOIN
				`domain` AS `t2` USING(`domain_id`)
			WHERE
				`t1`.`domain_id` = :domain_id
			AND
				`t1`.`subdomain_status` = :status_ok
			UNION
			SELECT
				`alias_name` AS `name`, `alias_id` AS `id`, 'als' AS `type`, `alias_mount` AS `mount_point`
			FROM
				`domain_aliasses`
			WHERE
				`domain_id` = :domain_id
			AND
				`alias_status` = :status_ok
			UNION
			SELECT
				CONCAT(`t1`.`subdomain_alias_name`, '.', `t2`.`alias_name`) AS `name`, `t1`.`subdomain_alias_id` AS `id`,
				'alssub' AS `type`, `t1`.`subdomain_alias_mount` AS `mount_point`
			FROM
				`subdomain_alias` AS `t1`
			INNER JOIN
				`domain_aliasses` AS `t2` USING(`alias_id`)
			WHERE
				`t2`.`domain_id` = :domain_id
			AND
				`subdomain_alias_status` = :status_ok
		";
		$stmt = exec_query($query, array('domain_id' => $mainDmnProps['domain_id'], 'status_ok' => 'ok'));

		if ($stmt->rowCount()) {
			$domainsList = array_merge($domainsList, $stmt->fetchAll(PDO::FETCH_ASSOC));
			usort($domainsList, function ($a, $b) {
				return strnatcmp(decode_idna($a['name']), decode_idna($b['name']));
			});
		}
	}

	return $domainsList;
}

/**
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
 * @return void
 */
function client_generatePage($tpl)
{
	/** @var iMSCP_Config_Handler_File $cfg */
	$cfg = iMSCP_Registry::get('config');

	$checked = $cfg->HTML_CHECKED;
	$selected = $cfg->HTML_SELECTED;

	$tpl->assign(
		array(
			'SUBDOMAIN_NAME' => (isset($_POST['subdomain_name'])) ? tohtml($_POST['subdomain_name']) : '',
			'SHARED_MOUNT_POINT_YES' => (isset($_POST['shared_mount_point']) && $_POST['shared_mount_point'] == 'yes')
				? $checked : '',
			'SHARED_MOUNT_POINT_NO' => (isset($_POST['shared_mount_point']) && $_POST['shared_mount_point'] == 'yes')
				? '' : $checked,
			'FORWARD_URL_YES' => (isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes') ? $checked : '',
			'FORWARD_URL_NO' => (isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes') ? '' : $checked,
			'HTTP_YES' => (isset($_POST['forward_url_scheme']) && $_POST['forward_url_scheme'] == 'http://')
				? $selected : '',
			'HTTPS_YES' => (isset($_POST['forward_url_scheme']) && $_POST['forward_url_scheme'] == 'https://')
				? $selected : '',
			'FTP_YES' => (isset($_POST['forward_url_scheme']) && $_POST['forward_url_scheme'] == 'ftp://')
				? $selected : '',
			'FORWARD_URL' => (isset($_POST['forward_url'])) ? tohtml(decode_idna($_POST['forward_url'])) : ''
		)
	);

	$domainList = _client_getDomainsList();

	foreach ($domainList as $domain) {
		$tpl->assign(
			array(
				'DOMAIN_NAME' => tohtml($domain['name']),
				'DOMAIN_NAME_UNICODE' => tohtml(decode_idna($domain['name'])),
				'DOMAIN_NAME_SELECTED' => (isset($_POST['domain_name']) && $_POST['domain_name'] == $domain['name'])
					? $selected : '',
				'SHARED_MOUNT_POINT_DOMAIN_SELECTED' =>
				(isset($_POST['shared_mount_point_domain']) && $_POST['shared_mount_point_domain'] == $domain['name'])
					? $selected : ''
			)
		);

		if ($domain['type'] == 'dmn' || $domain['type'] == 'als') {
			$tpl->parse('PARENT_DOMAIN', '.parent_domain');
			$tpl->parse('SHARED_MOUNT_POINT_DOMAIN', '.shared_mount_point_domain');
		} else {
			$tpl->parse('SHARED_MOUNT_POINT_DOMAIN', '.shared_mount_point_domain');
		}
	}
}

/**
 * Add new subdomain
 *
 * @return bool TRUE on success, FALSE on failure
 */
function client_addSubdomain()
{
	// Basic check

	if (empty($_POST['subdomain_name'])) {
		set_page_message(tr('You must enter a subdomain name.'), 'error');
		return false;
	} elseif (empty($_POST['domain_name'])) {
		showBadRequestErrorPage();
	}

	// Check for parent domain

	$domainName = clean_input($_POST['domain_name']);
	$domainType = null;
	$domainId = null;

	$domainList = _client_getDomainsList();

	foreach ($domainList as $domain) {
		if (($domain['type'] == 'dmn' || $domain['type'] == 'als') && $domain['name'] == $domainName) {
			$domainType = $domain['type'];
			$domainId = $domain['id'];
		}
	}

	if (null === $domainType) {
		showBadRequestErrorPage();
	}

	$subLabel = clean_input((strtolower($_POST['subdomain_name'])));

	if($subLabel == 'www') {
		set_page_message(tr('%s is not allowed as subdomain label.', "<strong>www</strong>"), 'error');
		return false;
	}

	$subdomainName = $subLabel . '.' . $domainName;

	// Check for subdomain syntax

	if (!isValidDomainName($subdomainName)) {
		set_page_message(tr('Subdomain name is not valid.'), 'error');
		return false;
	}

	$subLabelAscii = clean_input(encode_idna(strtolower($_POST['subdomain_name'])));
	$subdomainNameAscii = encode_idna($subdomainName);

	// Check for sudomain existence

	foreach ($domainList as $domain) {
		if ($domain['name'] == $subdomainNameAscii) {
			set_page_message(tr('Subdomain %s already exist.', "<strong>$subdomainName</strong>"), 'error');
			return false;
		}
	}

	// Set default mount point
	if($domainType == 'dmn') {
		if(in_array($subLabelAscii, array('backups', 'cgi-bin', 'errors', 'logs', 'phptmp'))) {
			$mountPoint = "/sub_$subLabelAscii";
		} else {
			$mountPoint = "/$subLabelAscii";
		}
	} else {
		if(in_array($subLabelAscii, array('cgi-bin', 'phptmp'))) {
			$mountPoint = "/$domainName/sub_$subLabelAscii";
		} else {
			$mountPoint = "/$domainName/$subLabelAscii";
		}
	}

	// Check for shared mount point option

	if (isset($_POST['shared_mount_point']) && $_POST['shared_mount_point'] == 'yes') { // We are safe here
		if (isset($_POST['shared_mount_point_domain'])) {
			$sharedMountPointDomain = clean_input($_POST['shared_mount_point_domain']);

			// Get shared mount point
			foreach ($domainList as $domain) {
				if ($domain['name'] == $sharedMountPointDomain) {
					$mountPoint = $domain['mount_point'];
				}
			}
		} else {
			showBadRequestErrorPage();
		}
	}

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

				if ($uri->getHost() == $subdomainNameAscii && $uri->getPath() == '/') {
					throw new iMSCP_Exception(
						tr('Forward URL %s is not valid.', "<strong>$forwardUrl</strong>") . ' ' .
						tr('Subdomain %s cannot be forwarded on itself.', "<strong>$subdomainName</strong>")
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

	$db = iMSCP_Database::getInstance();

	iMSCP_Events_Aggregator::getInstance()->dispatch(
		iMSCP_Events::onBeforeAddSubdomain,
		array(
			'subdomainName' => $subdomainName,
			'subdomainType' => $domainType,
			'parentDomainId' => $domainId,
			'mountPoint' => $mountPoint,
			'forwardUrl' => $forwardUrl,
			'customerId' => $_SESSION['user_id']
		)
	);

	if ($domainType == 'als') {
		$query = "
			INSERT INTO `subdomain_alias` (
			    `alias_id`, `subdomain_alias_name`, `subdomain_alias_mount`, `subdomain_alias_url_forward`,
			    `subdomain_alias_status`
			) VALUES (
			    ?, ?, ?, ?, ?
			)
		";
	} else {
		$query = "
			INSERT INTO `subdomain` (
			    `domain_id`, `subdomain_name`, `subdomain_mount`, `subdomain_url_forward`, `subdomain_status`
			) VALUES (
			    ?, ?, ?, ?, ?
			)
		";
	}

	exec_query($query, array($domainId, $subLabelAscii, $mountPoint, $forwardUrl, 'toadd'));

	iMSCP_Events_Aggregator::getInstance()->dispatch(
		iMSCP_Events::onAfterAddSubdomain,
		array(
			'subdomainName' => $subdomainName,
			'subdomainType' => $domainType,
			'parentDomainId' => $domainId,
			'mountPoint' => $mountPoint,
			'forwardUrl' => $forwardUrl,
			'customerId' => $_SESSION['user_id'],
			'subdomainId' => $db->insertId()
		)
	);

	send_request();

	write_log($_SESSION['user_logged'] . ": scheduled addition of subdomain: $subdomainName.", E_USER_NOTICE);

	return true;
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('subdomains') or showBadRequestErrorPage();

$mainDmnProps = get_domain_default_props($_SESSION['user_id']);
$subdomainsCount = get_domain_running_sub_cnt($mainDmnProps['domain_id']);

if ($mainDmnProps['domain_subd_limit'] != 0 && $subdomainsCount >= $mainDmnProps['domain_subd_limit']) {
	set_page_message(tr('You have reached the maximum number of subdomains allowed by your subscription.'), 'warning');
	redirectTo('domains_manage.php');
} elseif (!empty($_POST) && client_addSubdomain()) {
	set_page_message(tr('Subdomain successfully scheduled for addition.'), 'success');
	redirectTo('domains_manage.php');
} else {
	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'client/subdomain_add.tpl',
			'page_message' => 'layout',
			'parent_domain' => 'page',
			'shared_mount_point_domain' => 'page'
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Client / Domains / Add Subdomain'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_SUBDOMAIN' => tr('Subdomain'),
			'TR_SUBDOMAIN_NAME' => tr('Subdomain name'),
			'TR_SHARED_MOUNT_POINT' => tr('Shared mount point'),
			'TR_SHARED_MOUNT_POINT_TOOLTIP' => tr('Allows to share the mount point of another domain.'),
			'TR_URL_FORWARDING' => tr('URL forwarding'),
			'TR_URL_FORWARDING_TOOLTIP' => tr('Allows to forward any request made to this subdomain to a specific URL. Be aware that when this option is in use, no Web folder is created for the subdomain.'),
			'TR_FORWARD_TO_URL' => tr('Forward to URL'),
			'TR_YES' => tr('Yes'),
			'TR_NO' => tr('No'),
			'TR_HTTP' => 'http://',
			'TR_HTTPS' => 'https://',
			'TR_FTP' => 'ftp://',
			'TR_ADD' => tr('Add'),
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
