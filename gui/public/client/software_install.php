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
 * @author      Sacha Bay <sascha.bay@i-mscp.net>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/************************************************************************************
 *  Script functions
 */

/**
 * Generate Page.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $customerId Customer unique identifier
 * @return int Software unique identifier
 */
function client_generatePage($tpl, $customerId)
{
	if (!isset($_GET['id']) || $_GET['id'] == '' || !is_numeric($_GET['id'])) {
		showBadRequestErrorPage();
		exit; // Uselesss but avoid IDE warning about possible undefined variable
	} else {
		$softwareId = intval($_GET['id']);
	}

	$domainProperties = get_domain_default_props($customerId);

	$stmt = exec_query('SELECT created_by FROM admin WHERE admin_id = ?', $customerId);

	get_software_props_install(
		$tpl, $domainProperties['domain_id'], $softwareId, $stmt->fields['created_by'],
		$domainProperties['domain_sqld_limit']
	);

	return $softwareId;
}

/************************************************************************************
 * Main program
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('aps') or showBadRequestErrorPage();

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'client/software_install.tpl',
		'page_message' => 'layout',
		'software_item' => 'page',
		'show_domain_list' => 'page',
		'software_install' => 'page',
		'no_software' => 'page',
		'installdb_item' => 'page',
		'select_installdb' => 'page',
		'require_installdb' => 'page',
		'select_installdbuser' => 'page',
		'installdbuser_item' => 'page',
		'softwaredbuser_message' => 'page',
		'create_db' => 'page',
		'create_message_db' => 'page'
	)
);

if (!empty($_POST)) {
	$id = intval($_GET['id']);
	$domain_path = '';
	$other_dir = clean_input($_POST['other_dir'], true);

	$query = "
		SELECT
			`software_master_id`, `software_db`, `software_name`, `software_version`, `software_language`,
			`software_depot`
		FROM
			`web_software`
		WHERE
			`software_id` = ?
	";
	$stmt = exec_query($query, $_GET['id']);

	$install_username = clean_input($_POST['install_username'], true);
	$install_password = clean_input($_POST['install_password'], true);
	$install_email = clean_input($_POST['install_email'], true);

	if (isset($_POST['createdir']) && $_POST['createdir'] == '1') {
		$createdir = clean_input($_POST['createdir'], true);
		set_page_message(tr('The directory %s was created.', $other_dir), 'success');
	} else {
		$createdir = '0';
	}

	// Check dir exists
	$domain = $_SESSION['user_logged'];
	$vfs = new iMSCP_VirtualFileSystem($domain);
	$list = $vfs->ls($other_dir);

	$domainProps = get_domain_default_props($_SESSION['user_id']);
	$dmn_id = $domainProps['domain_id'];

	$query = "
		SELECT
			`software_name` `swname`, `software_version` `swversion`
		FROM
			`web_software_inst`
		WHERE
			`domain_id` = ?
		AND
			`path` = ?
	";
	$rspath = exec_query($query, array($dmn_id, $other_dir));

	list (
		$posted_domain_id, $posted_aliasdomain_id, $posted_subdomain_id, $posted_aliassubdomain_id, $posted_mountpath
	) = explode(';', $_POST['selected_domain']);

	if (($posted_aliasdomain_id + $posted_subdomain_id + $posted_aliassubdomain_id) > 0) {
		if ($posted_aliasdomain_id > 0) {
			$query = "SELECT `alias_mount` `domainpath` FROM `domain_aliasses` WHERE `alias_id` = ?";
			$rsdomainpath = exec_query($query, $posted_aliasdomain_id);

			$domain_path = $rsdomainpath->fields['domainpath'];
			$domain_path = str_replace("/", "\/", $domain_path);
		} elseif ($posted_subdomain_id > 0) {
			$query = "SELECT `subdomain_mount` `domainpath` FROM `subdomain` WHERE `subdomain_id` = ?";
			$rsdomainpath = exec_query($query, $posted_subdomain_id);

			$domain_path = $rsdomainpath->fields['domainpath'];
			$domain_path = str_replace("/", "\/", $domain_path);
		} elseif ($posted_aliassubdomain_id > 0) {
			$query = "SELECT `subdomain_alias_mount` `domainpath` FROM `subdomain_alias` WHERE `subdomain_alias_id` = ?";
			$rsdomainpath = exec_query($query, $posted_aliassubdomain_id);

			$domain_path = $rsdomainpath->fields['domainpath'];
			$domain_path = str_replace("/", "\/", $domain_path);
		} else {
			$domain_path = $posted_mountpath;
		}
	} else {
		$domain_path = $posted_mountpath;
	}

	$db_connection_ok = true;
	$selected_db = '';
	$sql_user = '';
	$sql_pass = '';

	if ($stmt->fields['software_db'] == '1') {
		$selected_db = clean_input($_POST['selected_db'], true);
		$sql_user = clean_input($_POST['sql_user'], true);
		$rsdatabase = exec_query('SELECT sqlu_pass FROM sql_user WHERE sqlu_name = ?', $sql_user);

		$db_connection_ok = check_db_connection($selected_db, $sql_user, $rsdatabase->fields['sqlu_pass']);
		$sql_pass = $rsdatabase->fields['sqlu_pass'];
	}

	if ($stmt->fields['software_db'] == '1' && !$db_connection_ok) {
		set_page_message(tr('Please select a valid SQL user for the database.'), 'error');
	} elseif (empty($install_username) || empty($install_password) || empty($install_email)) {
		set_page_message(tr('All fields are required.'), 'error');
	} elseif (!preg_match('/htdocs/', $other_dir)) {
		set_page_message(tr("You can't install the software outside the htdocs directory."), 'error');
	} elseif (($posted_aliasdomain_id + $posted_subdomain_id + $posted_aliassubdomain_id) > 0 && !preg_match("/" . $domain_path . "/", $other_dir)) {
		set_page_message(tr("You have chosen a directory which doesn't match the domain directory."), 'error');
	} elseif (!$list && $createdir == '0') {
		set_page_message(tr("The directory %s doesn't exist. Please create it first.", $other_dir), 'error');
	} elseif ($rspath->recordCount() > 0) {
		set_page_message(tr('Please select another directory. %s (%s) is installed there.', $rspath->fields['swname'], $rspath->fields['swversion']), 'error');
	} elseif (checkPasswordSyntax($install_password)) {
		$sw_db_required = $stmt->fields['software_db'];
		$sw_software_name = $stmt->fields['software_name'];
		$sw_software_version = $stmt->fields['software_version'];
		$software_master_id = $stmt->fields['software_master_id'];
		$software_depot = $stmt->fields['software_depot'];
		$software_language = $stmt->fields['software_language'];

		$query = "SELECT `software_prefix` FROM `web_software` WHERE `software_id` = ?";
		$stmt = exec_query($query, $_GET['id']);

		$prefix = $stmt->fields['software_prefix'];

		if ($sw_db_required == '1') {
			$query = "
				INSERT INTO `web_software_inst` (
                    `domain_id`, `alias_id`, `subdomain_id`, `subdomain_alias_id`,
                    `software_id`, `software_master_id`, `software_name`,
                    `software_version`, `software_language`, `path`, `software_prefix`,
                    `db`, `database_user`, `database_tmp_pwd`, `install_username`,
                    `install_password`, `install_email`, `software_status`, `software_depot`
				) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
			";
			$stmt = exec_query(
				$query,
				array(
					$dmn_id, $posted_aliasdomain_id, $posted_subdomain_id, $posted_aliassubdomain_id, $id,
					$software_master_id, $sw_software_name, $sw_software_version, $software_language,
					$other_dir, $prefix, $selected_db, $sql_user, $sql_pass, $install_username, $install_password,
					$install_email, 'toadd', $software_depot));
		} else {
			$query = "
				INSERT INTO `web_software_inst` (
                    `domain_id`, `alias_id`, `subdomain_id`, `subdomain_alias_id`, `software_id`, `software_master_id`,
                    `software_name`, `software_version`, `software_language`, `path`, `software_prefix`, `db`,
                    `database_user`, `database_tmp_pwd`, `install_username`, `install_password`, `install_email`,
                    `software_status`, `software_depot`
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
			";
			$stmt = exec_query(
				$query,
				array(
					$dmn_id, $posted_aliasdomain_id, $posted_subdomain_id, $posted_aliassubdomain_id, $id,
					$software_master_id, $sw_software_name, $sw_software_version, $software_language, $other_dir,
					'not_required', 'not_required', 'not_required', 'not_required', $install_username, $install_password,
					$install_email, 'toadd', $software_depot));
		}

		send_request();
		redirectTo('software.php');
	}

	if ($stmt->fields['software_db'] == '1') {
		$tpl->assign(
			array(
				'VAL_OTHER_DIR' => $other_dir,
				'CHECKED_CREATEDIR' => ($createdir == '1') ? $cfg->HTML_CHECKED : '',
				'VAL_INSTALL_USERNAME' => $install_username,
				'VAL_INSTALL_PASSWORD' => $install_password,
				'VAL_INSTALL_EMAIL' => $install_email
			)
		);
	} else {
		$tpl->assign(
			array(
				'VAL_OTHER_DIR' => $other_dir,
				'CHECKED_CREATEDIR' => ($createdir == '1') ? $cfg->HTML_CHECKED : '',
				'VAL_INSTALL_USERNAME' => $install_username,
				'VAL_INSTALL_PASSWORD' => $install_password,
				'VAL_INSTALL_EMAIL' => $install_email
			)
		);
	}
} else {
	$tpl->assign(
		array(
			'VAL_OTHER_DIR' => '/htdocs',
			'CHECKED_CREATEDIR' => '',
			'VAL_INSTALL_USERNAME' => '',
			'VAL_INSTALL_PASSWORD' => '',
			'VAL_INSTALL_EMAIL' => ''
		)
	);
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Webtools / Software / Install'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_INSTALL_SOFTWARE' => tr('Install Software'),
		'SOFTWARE_ID' => client_generatePage($tpl, $_SESSION['user_id']),
		'TR_NAME' => tr('Software'),
		'TR_TYPE' => tr('Type'),
		'TR_DB' => tr('Database required'),
		'TR_SELECT_DOMAIN' => tr('Select Domain'),
		'TR_BACK' => tr('Back'),
		'TR_INSTALL' => tr('Install'),
		'TR_PATH' => tr('Install path'),
		'TR_FTP_DIRECTORIES' => tojs(('Ftp directories')),
		'TR_CLOSE' => tojs(tr('Close')),
		'TR_CHOOSE_DIR' => tr('Choose dir'),
		'CREATEDIR_MESSAGE' => tr('Create directory, if not existing!'),
		'TR_SELECT_DB' => tr('Select database'),
		'TR_SQL_USER' => tr('SQL user'),
		'TR_SQL_PWD' => tr('Password'),
		'TR_SOFTWARE_MENU' => tr('Software installation'),
		'TR_INSTALLATION' => tr('Installation details'),
		'TR_INSTALLATION_INFORMATION' => tr('Username and password for application login'),
		'TR_INSTALL_USER' => tr('Login username'),
		'TR_INSTALL_PWD' => tr('Login password'),
		'TR_INSTALL_EMAIL' => tr('Email address')));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
