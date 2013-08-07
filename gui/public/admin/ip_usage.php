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
 * @subpackage  Admin
 * @copyright   2010-2013 by i-MSCP team
 * @author      iMSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate List of Domains assigned to IPs
 *
 * @param iMSCP_pTemplate $tpl
 * @return void
 */
function listIPDomains($tpl)
{
	$stmt = execute_query('SELECT `ip_id`, `ip_number` FROM `server_ips`');

	if ($stmt->rowCount()) {
		while (!$stmt->EOF) {
			$noDomains = false;
			$noDomainAliases = false;

			$query = "
				SELECT
					`d`.`domain_name`, `a`.`admin_name`
				FROM
					`domain` d
				INNER JOIN
					`admin` a ON (`a`.`admin_id` = `d`.`domain_created_id`)
				WHERE
					`d`.`domain_ip_id` = ?
				ORDER BY
					`d`.`domain_name`
			";

			$stmt2 = exec_query($query, $stmt->fields['ip_id']);
			$domainsCount = $stmt2->recordCount();

			if (!$stmt2->rowCount()) {
				$noDomains = true;
			}

			while (!$stmt2->EOF) {
				$tpl->assign(
					array(
						'DOMAIN_NAME' => $stmt2->fields['domain_name'],
						'RESELLER_NAME' => $stmt2->fields['admin_name']
					)
				);

				$tpl->parse('DOMAIN_ROW', '.domain_row');
				$stmt2->moveNext();
			}

			$query = "
				SELECT
					`da`.`alias_name`, `a`.`admin_name`
				FROM
				`domain_aliasses` da
				INNER JOIN
					`domain` d ON (`d`.`domain_id` = `da`.`domain_id`)
				INNER JOIN
					`admin` a ON (`a`.`admin_id` = `d`.`domain_created_id`)
				WHERE
					`da`.`alias_ip_id` = ?
				ORDER BY
					`da`.`alias_name`
			";

			$stmt3 = exec_query($query, $stmt->fields['ip_id']);
			$domainaliasesCount = $stmt3->recordCount();

			if (!$stmt3->rowCount()) {
				$noDomainAliases = true;
			}

			while (!$stmt3->EOF) {
				$tpl->assign(
					array(
						'DOMAIN_NAME' => $stmt3->fields['alias_name'],
						'RESELLER_NAME' => $stmt3->fields['admin_name']
					)
				);

				$tpl->parse('DOMAIN_ROW', '.domain_row');
				$stmt3->moveNext();
			}

			$tpl->assign(
				array(
					'IP' => $stmt->fields['ip_number'],
					'RECORD_COUNT' => tr('Total Domains') . ' : ' . ($domainsCount + $domainaliasesCount)
				)
			);

			if ($noDomains && $noDomainAliases) {
				$tpl->assign(
					array(
						'DOMAIN_NAME' => tr("No records found"),
						'RESELLER_NAME' => ''
					)
				);

				$tpl->parse('DOMAIN_ROW', '.domain_row');
			}

			$tpl->parse('IP_ROW', '.ip_row');
			$tpl->assign('DOMAIN_ROW', '');
			$stmt->moveNext();
		}
	} else {
		$tpl->assign('STATISTICS', '');
		set_page_message('No statistics available.', 'info');
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

if(!systemHasResellersOrCustomers()) {
	showBadRequestErrorPage();
}

$tpl = new iMSCP_pTemplate();

$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/ip_usage.tpl',
		'ip_row' => 'page',
		'domain_row' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Statistics / IP Usage'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_SERVER_STATISTICS' => tr('Server statistics'),
		'TR_IP_ADMIN_USAGE_STATISTICS' => tr('Admin/IP usage statistics'),
		'TR_DOMAIN_NAME' => tr('Domain Name'),
		'TR_RESELLER_NAME' => tr('Reseller Name')
	)
);

generateNavigation($tpl);
listIPDomains($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
