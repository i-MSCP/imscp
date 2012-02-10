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
 * @copyright   2010-2012 by i-MSCP team
 * @author      Klaas Tammling <klaas.tammling@st-city.net>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

/************************************************************************************
 * Script functions
 */

/**
 * Generate List of Domains assigned to IPs.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function listIPDomains($tpl)
{
    global $reseller_id;

    $query = "
		SELECT
			`reseller_ips`
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
	";

    $res = exec_query($query, $reseller_id);
    $data = $res->fetchRow();
    $reseller_ips = explode(";", substr($data['reseller_ips'], 0, -1));

    $query = "
		SELECT
			`ip_id`, `ip_number`
		FROM
			`server_ips`
		WHERE
			`ip_id`
		IN
			(" . implode(',', $reseller_ips) . ")
	";

    $rs = execute_query($query);

    if ($rs->recordCount()) {
        while (!$rs->EOF) {

            $no_domains = false;
            $no_alias_domains = false;

            $query = "
			SELECT
				`d`.`domain_name`, `a`.`admin_name`
			FROM
				`domain` d
			INNER JOIN
				`admin` a
			ON
				(`a`.`admin_id` = `d`.`domain_created_id`)
			WHERE
				`d`.`domain_ip_id` = ?
			AND
				`d`.`domain_created_id` = ?
			ORDER BY
				`d`.`domain_name`
		";

            $rs2 = exec_query($query, array($rs->fields['ip_id'], $reseller_id));
            $domain_count = $rs2->recordCount();

            if ($rs2->recordCount() == 0) {
                $no_domains = true;
            }

            while (!$rs2->EOF) {
                $tpl->assign('DOMAIN_NAME', $rs2->fields['domain_name']);
                $tpl->parse('DOMAIN_ROW', '.domain_row');
                $rs2->moveNext();
            }

            $query = "
			    SELECT
				    `da`.`alias_name`, `a`.`admin_name`
			    FROM
				    `domain_aliasses` da
			    INNER JOIN
				    `domain` d
                ON
				    (`d`.`domain_id` = `da`.`domain_id`)
			    INNER JOIN
				    `admin` a
			    ON
				    (`a`.`admin_id` = `d`.`domain_created_id`)
			    WHERE
				    `da`.`alias_ip_id` = ?
			    AND
				    `d`.`domain_created_id` = ?
			    ORDER BY
				    `da`.`alias_name`
		";

            $rs3 = exec_query($query, array($rs->fields['ip_id'], $reseller_id));
            $alias_count = $rs3->recordCount();

            if ($rs3->recordCount() == 0) {
                $no_alias_domains = true;
            }

            while (!$rs3->EOF) {
                $tpl->assign('DOMAIN_NAME', $rs3->fields['alias_name']);
                $tpl->parse('DOMAIN_ROW', '.domain_row');
                $rs3->moveNext();
            }

            $tpl->assign(array(
                              'IP' => $rs->fields['ip_number'],
                              'RECORD_COUNT' => tr('Total Domains') . " : " . ($domain_count + $alias_count)));

            if ($no_domains && $no_alias_domains) {
                $tpl->assign(array(
                                  'DOMAIN_NAME' => tr("No records found"),
                                  'RESELLER_NAME' => ''));

                $tpl->parse('DOMAIN_ROW', '.domain_row');
            }

            $tpl->parse('IP_ROW', '.ip_row');
            $tpl->assign('DOMAIN_ROW', '');
            $rs->moveNext();
        }
    } else {
        $tpl->assign('IP_USAGE_STATISTICS', '');
        set_page_message(tr('No Ip statistics to be show.'), 'info');
    }
}

/************************************************************************************
 * Main script
 */

check_login(__FILE__);

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
		'domain_row' => 'page'));

$reseller_id = $_SESSION['user_id'];

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('i-MSCP - Reseller/IP Usage'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo()));

generateNavigation($tpl);

listIPDomains($tpl);

$tpl->assign(
	array(
		'TR_DOMAIN_STATISTICS' => tr('Domain statistics'),
		'TR_IP_RESELLER_USAGE_STATISTICS' => tr('Reseller/IP usage statistics'),
		'TR_DOMAIN_NAME' => tr('Domain Name')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
