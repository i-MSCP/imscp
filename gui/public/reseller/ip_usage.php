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
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/***********************************************************************************************************************
 * Script functions
 */

/**
 * Generate List of Domains assigned to IPs
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function listIPDomains($tpl)
{
	$resellerId = $_SESSION['user_id'];

	$stmt = exec_query('SELECT reseller_ips FROM reseller_props WHERE reseller_id = ?', $resellerId);
	$data = $stmt->fetchRow();
	$resellerIps = explode(';', substr($data['reseller_ips'], 0, -1));

	$stmt = execute_query('SELECT ip_id, ip_number FROM server_ips WHERE ip_id IN (' . implode(',', $resellerIps) . ')');

	while ($ip = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
		$stmt2 = exec_query(
			'
				SELECT
					domain_name
				FROM
					domain
				INNER JOIN
					admin ON(admin_id = domain_admin_id)
				WHERE
					domain_ip_id = :ip_id
				AND
					created_by = :reseller_id
				UNION
				SELECT
					alias_name AS domain_name
				FROM
					domain_aliasses
				INNER JOIN
					domain USING(domain_id)
				INNER JOIN
					admin ON(admin_id = domain_admin_id)
				WHERE
					alias_ip_id = :ip_id
				AND
					created_by = :reseller_id
			',
			array('ip_id' => $ip['ip_id'], 'reseller_id' => $resellerId)
		);

		$domainsCount = $stmt2->rowCount();

		$tpl->assign(
			array(
				'IP' => tohtml($ip['ip_number']),
				'RECORD_COUNT' => tr('Total Domains') . ': ' . ($domainsCount)
			)
		);

		if ($domainsCount) {
			while ($data = $stmt2->fetchRow(PDO::FETCH_ASSOC)) {
				$tpl->assign('DOMAIN_NAME', tohtml(idn_to_utf8($data['domain_name'])));
				$tpl->parse('DOMAIN_ROW', '.domain_row');
			}
		} else {
			$tpl->assign('DOMAIN_NAME', tr('No used yet'));
			$tpl->parse('DOMAIN_ROW', 'domain_row');
		}

		$tpl->parse('IP_ROW', '.ip_row');
		$tpl->assign('DOMAIN_ROW', '');
	}
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

if (resellerHasCustomers()) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	/** @var $tpl iMSCP_pTemplate */
	$tpl = new iMSCP_pTemplate();

	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'reseller/ip_usage.tpl',
			'page_message' => 'layout',
			'ip_row' => 'page',
			'domain_row' => 'ip_row'
		)
	);

	$reseller_id = $_SESSION['user_id'];

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Reseller / Statistics / IP Usage'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_DOMAIN_STATISTICS' => tr('Domain statistics'),
			'TR_IP_RESELLER_USAGE_STATISTICS' => tr('Reseller/IP usage statistics'),
			'TR_DOMAIN_NAME' => tr('Domain Name'),
			'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()
		)
	);

	generateNavigation($tpl);
	generatePageMessage($tpl);
	listIPDomains($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}
