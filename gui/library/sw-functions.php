<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP team
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
 * @package     iMSCP_SoftwareInstaller
 * @copyright   2010-2014 by i-MSCP team
 * @author 		Sascha Bay
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/************************************************************************************
 * This file contains app installer functions that are used at many level
 * (eg. admin, reseller, client)
 */


/************************************************************************************
 * These functions are used by admin
 */

/**
 * Must be documented
 *
 * @param int $software_id Software unique identifier
 * @param int $reseller_id Reseller unique identifier
 * @param int $software_master_id
 * @param bool $software_deleted
 * @return void
 */
function update_existing_client_installations_res_upload($software_id, $reseller_id,
	$software_master_id, $software_deleted = false)
{
	$query = "
        SELECT
            domain_id
        FROM
            domain
        INNER JOIN
        	admin on (admin_id = domain_admin_id)
        WHERE
            domain_software_allowed = 'yes'
        AND
            created_by = ?
    ";
	$res = exec_query($query, $reseller_id);

	if ($res->RecordCount() > 0) {
		while (!$res->EOF) {
			if ($software_deleted === false) {
				$update_query = "
                    UPDATE
                        `web_software_inst`
                    SET
                        `software_id` = ?, `software_master_id` = ?, `software_depot` = ?
                    WHERE
                        `software_id` = ?
                    AND
                        `domain_id` = ?
                ";
				exec_query($update_query, array(
											   $software_id, $software_master_id, 'yes',
											   $software_master_id, $res->fields['domain_id']));
			} else {
				$update_query = "
                    UPDATE
                        `web_software_inst`
                    SET
                        `software_res_del` = 1
                    WHERE
                        `software_id` = ?
                    AND
                        `domain_id` = ?
                ";
				exec_query($update_query, array($software_id, $res->fields['domain_id']));
			}

			$res->MoveNext();
		}
	}
}

/**
 * Must be documented
 *
 * @param int $software_id Software unique identifier
 * @param int $software_master_id
 * @param int $reseller_id
 * @return void
 */
function update_existing_client_installations_sw_depot($software_id, $software_master_id, $reseller_id)
{
	$query = "
		SELECT
			domain_id
		FROM
			domain
		INNER JOIN
			admin ON(admin_id = domain_admin_id)
		WHERE
			domain_software_allowed = 'yes'
		AND
			created_by = ?
     ";
	$stmt = exec_query($query, $reseller_id);

	if ($stmt->rowCount() > 0) {
		while (!$stmt->EOF) {
			$update_query = "
				UPDATE
					`web_software_inst`
				SET
					`software_id` = ?, `software_res_del` = 0
				WHERE
					`software_master_id` = ?
				AND
					`software_res_del` = 1
				AND
					`domain_id` = ?
			";
			exec_query($update_query, array($software_id, $software_master_id, $stmt->fields['domain_id']));

			$stmt->MoveNext();
		}
	}
}

/**
 * Must be documented
 *
 * @param int $reseller_id Reseller unique identifier
 * @param string $file_name
 * @param int $sw_id
 * @return void
 */
function send_activated_sw($reseller_id, $file_name, $sw_id)
{
	$query = "SELECT `admin_name` as `reseller`, `created_by`, `email` as `res_email` FROM `admin` WHERE `admin_id` = ?";
	$stmt = exec_query($query, $reseller_id);

	$to_name = $stmt->fields['reseller'];
	$to_email = $stmt->fields['res_email'];
	$admin_id = $stmt->fields['created_by'];

	$query = "SELECT `email` as adm_email, `admin_name` as `admin` FROM `admin` WHERE `admin_id` = ?";
	$stmt = exec_query($query, $admin_id);

	$from_name = $stmt->fields['admin'];
	$from_email = $stmt->fields['adm_email'];

	if ($from_name) {
		$from = encode_mime_header($from_name) . " <$from_email>";
	} else {
		$from = $from_email;
	}

	$search = array();
	$replace = array();

	$search [] = '{ADMIN}';
	$replace[] = $from_name;
	$search [] = '{SOFTWARE}';
	$replace[] = $file_name;
	$search [] = '{SOFTWARE_ID}';
	$replace[] = $sw_id;
	$search [] = '{RESELLER}';
	$replace[] = $to_name;

	$headers = "From: $from\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/plain; charset=utf-8\r\n";
	$headers .= "Content-Transfer-Encoding: 8bit\r\n";
	$headers .= "X-Mailer: i-MSCP Mailer";

	$subject = tr('{ADMIN} activated your software package');
	$message = tr('Dear {RESELLER},
	Your uploaded a software package was succesful activated by {ADMIN}.

	Details:
	Package Name: {SOFTWARE}
	Package ID: {SOFTWARE_ID}

	Please login into your i-MSCP control panel for more details.', true);

	$subject = str_replace($search, $replace, $subject);
	$message = str_replace($search, $replace, $message);

	$subject = encode_mime_header($subject);

	mail($to_email, $subject, $message, $headers, "-f $from_email");
}

/**
 * Must be documented
 *
 * @param int $reseller_id
 * @param string $file_name
 * @param int $sw_id
 * @param string $subject_input
 * @param string $message_input
 * @return void
 */
function send_deleted_sw($reseller_id, $file_name, $sw_id, $subject_input, $message_input)
{
	$query = "SELECT  `admin_name` as reseller, `created_by`, `email` as res_email FROM `admin` WHERE `admin_id` = ?";
	$stmt = exec_query($query, $reseller_id);

	$to_name = $stmt->fields['reseller'];
	$to_email = $stmt->fields['res_email'];
	$admin_id = $stmt->fields['created_by'];

	$query = "SELECT `email` as adm_email, `admin_name` as admin FROM `admin` WHERE `admin_id` = ?";
	$stmt = exec_query($query, $admin_id);

	$from_name = $stmt->fields['admin'];
	$from_email = $stmt->fields['adm_email'];

	if ($from_name) {
		$from = encode_mime_header($from_name) . " <$from_email>";
	} else {
		$from = $from_email;
	}

	$search = array();
	$replace = array();
	$search [] = '{ADMIN}';
	$replace[] = $from_name;
	$search [] = '{SOFTWARE}';
	$replace[] = $file_name;
	$search [] = '{SOFTWARE_ID}';
	$replace[] = $sw_id;
	$search [] = '{RESELLER}';
	$replace[] = $to_name;

	$headers = "From: $from\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/plain; charset=utf-8\r\n";
	$headers .= "Content-Transfer-Encoding: 8bit\r\n";
	$headers .= "X-Mailer: i-MSCP Mailer";

	// lets send mail to the reseller => new order
	$subject = tr($subject_input . ' was deleted by {ADMIN}!');
	$message = tr('Dear {RESELLER},
		Your uploaded software was deleted by {ADMIN}.

	Details:
	Package Name: {SOFTWARE}
	Package ID: {SOFTWARE_ID}

	Message from {ADMIN}:
	' . $message_input, true);

	$subject = str_replace($search, $replace, $subject);
	$message = str_replace($search, $replace, $message);

	$subject = encode_mime_header($subject);

	mail($to_email, $subject, $message, $headers, "-f $from_email");
}

/**
 * Must be documented.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return int
 */
function get_avail_software($tpl)
{
	$query = "
		SELECT
			`a`.`software_id` as `id`, `a`.`software_name` as `name`,
			`a`.`software_version` as `version`,
			`a`.`software_language` as `language`, `a`.`software_type` as `type`,
			`a`.`software_desc` as `description`, `a`.`reseller_id`, `b`.`admin_id`,
			`b`.`admin_name` as `reseller`
		FROM
			`web_software` a, `admin` b
		WHERE
			`a`.`software_active` = 0
		AND
			`a`.`reseller_id` = `b`.`admin_id`
		AND
			`a`.software_depot = 'no'
		ORDER BY
			`a`.`reseller_id` ASC, `a`.`software_type` ASC, `a`.`software_name` ASC
	";
	$rs = execute_query($query);

	if ($rs->recordCount() > 0) {
		while (!$rs->EOF) {
			$import_url = "software_import.php?id=" . $rs->fields['id'];
			$act_url = "software_activate.php?id=" . $rs->fields['id'];
			$del_url = "software_delete.php?id=" . $rs->fields['id'];
			$dow_url = "software_download.php?id=" . $rs->fields['id'];

			$tpl->assign(array(
							  'TR_NAME' => $rs->fields['name'],
							  'TR_TOOLTIP' => $rs->fields['description'],
							  'TR_VERSION' => $rs->fields['version'],
							  'TR_LANGUAGE' => $rs->fields['language'],
							  'TR_TYPE' => $rs->fields['type'],
							  'TR_RESELLER' => $rs->fields['reseller'],
							  'DOWNLOAD_LINK' => $dow_url,
							  'TR_DOWNLOAD' => tr('Download'),
							  'ACTIVATE_LINK' => $act_url,
							  'IMPORT_LINK' => $import_url,
							  'TR_ACTIVATION' => tr('Activate'),
							  'TR_IMPORT' => tr('Import'),
							  'DELETE_LINK' => $del_url,
							  'TR_DELETE' => tr('Delete')));

			$tpl->parse('LIST_SOFTWARE', '.list_software');
			$rs->moveNext();
		}
		$tpl->assign('NO_SOFTWARE_LIST', '');
	} else {
		$tpl->assign(array('NO_SOFTWARE' => tr('No software is waiting for activation')));
		$tpl->parse('NO_SOFTWARE_LIST', '.no_software_list');
		$tpl->assign('LIST_SOFTWARE', '');
	}

	return $rs->recordCount();
}

/**
 * Must be documented.
 *
 * @param iMSCP_pTemplate $tpl Teamplate engine
 * @return int
 */
function get_avail_softwaredepot($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			`a`.`software_id` as `id`, `a`.`software_name` as `name`,
			`a`.`software_version` as `version`, `a`.`software_language` as `language`,
			`a`.`software_type` as `type`, `a`.`software_desc` as `description`,
			`a`.`reseller_id`, `a`.`software_archive` as `filename`,
			`a`.`software_status` as `swstatus`, `b`.`admin_id`,
			`b`.`admin_name` as `admin`
		FROM
			`web_software` a, `admin` b
		WHERE
			`a`.`software_depot` = 'yes'
		AND
			`a`.`software_master_id` = 0
		AND
			`a`.`reseller_id` = `b`.`admin_id`
		ORDER BY
			`a`.`reseller_id` ASC, `a`.`software_type` ASC, `a`.`software_name` ASC
	";
	$rs = execute_query($query);

	if ($rs->recordCount() > 0) {
		while (!$rs->EOF) {
			if ($rs->fields['swstatus'] == 'ok' || $rs->fields['swstatus'] == 'ready') {
				if ($rs->fields['swstatus'] == 'ready') {
					$updatequery = "UPDATE `web_software` SET `software_status` = 'ok' WHERE `software_id` = ?";
					exec_query($updatequery, $rs->fields['id']);
					set_page_message(tr('Package installed successfully!'), 'success');
				}

				$del_url = 'software_delete.php?id=' . $rs->fields['id'];
				$dow_url = 'software_download.php?id=' . $rs->fields['id'];
				$rights_url = 'software_rights.php?id=' . $rs->fields['id'];

				$tpl->assign(array(
								  'TR_NAME' => $rs->fields['name'],
								  'LINK_COLOR' => '#000000',
								  'TR_TOOLTIP' => $rs->fields['description'],
								  'TR_VERSION' => $rs->fields['version'],
								  'TR_LANGUAGE' => $rs->fields['language'],
								  'TR_TYPE' => $rs->fields['type'],
								  'TR_ADMIN' => $rs->fields['admin'],
								  'DOWNLOAD_LINK' => $dow_url,
								  'TR_DOWNLOAD' => tr('Download'),
								  'DELETE_LINK' => $del_url,
								  'TR_DELETE' => tr('Delete'),
								  'SOFTWARE_ICON' => 'edit',
								  'SOFTWARE_RIGHTS_LINK' => $rights_url,
								  'RIGHTS_LINK' => tr('Rights'),
								  'TR_SOFTWARE_RIGHTS' => tr('Permissions')));
			} else {
				if ($rs->fields['swstatus'] == 'toadd') {
					$del_url = 'software_delete.php?id=' . $rs->fields['id'];
					$tpl->assign(array(
									  'TR_NAME' => tr('Installing your uploaded package. Please refresh this page.'),
									  'LINK_COLOR' => '#FF0000',
									  'TR_VERSION' => '',
									  'TR_LANGUAGE' => '',
									  'TR_TOOLTIP' => tr(
										  'The package will be installed automaticly to your system after upload.<br />Refresh your site to see the new status!'
									  ),
									  'TR_DOWNLOAD' => '',
									  'DOWNLOAD_LINK' => '',
									  'DELETE_LINK' => $del_url,
									  'TR_DELETE' => tr('Delete'),
									  'TR_TYPE' => '<font color="#FF0000">' . tr('installing') . '</font>',
									  'TR_ADMIN' => $rs->fields['admin'],
									  'SOFTWARE_ICON' => 'disabled',
									  'RIGHTS_LINK' => '',
									  'TR_SOFTWARE_RIGHTS' => '',
									  'SOFTWARE_RIGHTS_LINK' => ''));
				} else {
					if ($rs->fields['swstatus'] == 'todelete') {
						$tpl->assign(array(
										  'TR_NAME' => tr('Failure in the package. Deleting!'),
										  'LINK_COLOR' => '#FF0000',
										  'TR_VERSION' => '',
										  'TR_LANGUAGE' => '',
										  'TR_TOOLTIP' => tr(
											  'There is an Error inside your package. Please check it!<br />Refresh your site to see the new status!'
										  ),
										  'TR_DOWNLOAD' => '',
										  'DOWNLOAD_LINK' => '',
										  'DELETE_LINK' => '',
										  'TR_DELETE' => '',
										  'TR_TYPE' => '<font color="#FF0000">' . tr('deleting') . '</font>',
										  'TR_ADMIN' => $rs->fields['admin'],
										  'SOFTWARE_ICON' => 'delete',
										  'RIGHTS_LINK' => '',
										  'TR_SOFTWARE_RIGHTS' => '',
										  'SOFTWARE_RIGHTS_LINK' => ''));

						set_page_message(tr('Your package is corrupt. Please correct it!'), 'error');
					} elseif (preg_match('/double_depot_/i', $rs->fields['swstatus'])) {
						$tpl->assign(array(
										  'TR_NAME' => tr('Package already exists in the software depot!'),
										  'LINK_COLOR' => '#FF0000',
										  'TR_VERSION' => '',
										  'TR_LANGUAGE' => '',
										  'TR_TOOLTIP' => tr(
											  'Check your software depot uploads!<br />It is not allowed to upload this package two times.<br />Refresh your site to see the new status!'
										  ),
										  'TR_DOWNLOAD' => '',
										  'DOWNLOAD_LINK' => '',
										  'DELETE_LINK' => '',
										  'TR_DELETE' => '',
										  'TR_TYPE' => '<font color="#FF0000">' . tr('deleting') . '</font>',
										  'TR_ADMIN' => $rs->fields['admin'],
										  'SOFTWARE_ICON' => 'delete',
										  'RIGHTS_LINK' => '',
										  'TR_SOFTWARE_RIGHTS' => '',
										  'SOFTWARE_RIGHTS_LINK' => ''));

						set_page_message(tr('This package already exists in the software depot!'), 'warning');
					} elseif (preg_match('/double_res_/i', $rs->fields['swstatus'])) {
						$tpl->assign(array(
										  'TR_NAME' => tr('Package already exists in the reseller depot!'),
										  'LINK_COLOR' => '#FF0000',
										  'TR_VERSION' => '',
										  'TR_LANGUAGE' => '',
										  'TR_TOOLTIP' => tr(
											  'Check the reseller uploads!<br />It is not allowed to upload this package two times.<br />Refresh your site to see the new status!'
										  ),
										  'TR_DOWNLOAD' => '',
										  'DOWNLOAD_LINK' => '',
										  'DELETE_LINK' => '',
										  'TR_DELETE' => '',
										  'TR_TYPE' => '<font color="#FF0000">' . tr('deleting') . '</font>',
										  'TR_ADMIN' => $rs->fields['admin'],
										  'SOFTWARE_ICON' => 'delete',
										  'RIGHTS_LINK' => '',
										  'TR_SOFTWARE_RIGHTS' => '',
										  'SOFTWARE_RIGHTS_LINK' => ''));

						$exist_software_id = substr(strrchr($rs->fields['swstatus'], '_'), 1);

						$query = "
							SELECT
								`a`.`reseller_id`,  `b`.`admin_id`,  `b`.`admin_name` as `resellername`
							FROM
								`web_software` a, `admin` b
							WHERE
								a.`software_id` = ?
							AND
								a.`reseller_id` = b.`admin_id`
						";
						$rs_res = exec_query($query, $exist_software_id);

						set_page_message(
							tr(
								'This package already exists in the depot of the reseller "%1$s"!',
								$rs_res->fields['resellername']
							), 'warning');
					}

					$del_path = $cfg->GUI_APS_DEPOT_DIR . '/' . $rs->fields['filename'] . '-' .
								$rs->fields['id'] . '.tar.gz';

					@unlink($del_path);
					$delete = "DELETE FROM `web_software` WHERE `software_id` = ?";
					exec_query($delete, $rs->fields['id']);
				}
			}

			$tpl->parse('LIST_SOFTWAREDEPOT', '.list_softwaredepot');
			$rs->moveNext();
		}

		$tpl->assign('NO_SOFTWAREDEPOT_LIST', '');
	} else {
		$tpl->assign('NO_SOFTWAREDEPOT', tr('No software in software depot available!'));
		$tpl->parse('NO_SOFTWAREDEPOT_LIST', '.no_softwaredepot_list');
		$tpl->assign('LIST_SOFTWAREDEPOT', '');
	}

	return $rs->recordCount();
}

/**
 * Must be documented.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $reseller_id
 * @return int
 */
function get_installed_res_software($tpl, $reseller_id)
{
	$query = "
		SELECT
			a.`software_id` as id, a.`software_name` as name,
			a.`software_version` as version, a.`software_language` as language,
			a.`software_type` as type, a.`software_desc` as description,
			a.`reseller_id`, a.`software_archive` as filename,
			a.`software_status` as swstatus, a.`software_depot` as swdepot,
			b.`admin_id`, b.`admin_name` as admin
		FROM
			`web_software` a,
			`admin` b
		WHERE
			a.`reseller_id` = b.`admin_id`
		AND
			a.`reseller_id` = ?
		AND
			a.`software_status` = 'ok'
		ORDER BY
			a.`software_type` ASC, a.`software_name` ASC
	";
	$rs = exec_query($query, $reseller_id);

	if ($rs->recordCount() > 0) {
		while (!$rs->EOF) {
			$query2 = "
				SELECT
					`domain`.`domain_id` as did,
					`domain`.`domain_name` as domain,
					`web_software_inst`.`domain_id` as wdid,
					`web_software_inst`.`software_id` as sid,
					`web_software`.`software_id` as wsid
				FROM
					`domain`, `web_software`, `web_software_inst`
				WHERE
					`web_software_inst`.`software_id`= ?
				AND
					`web_software`.`software_id` = `web_software_inst`.`software_id`
				AND
					`domain`.`domain_id` = `web_software_inst`.`domain_id`
			";
			$rs2 = exec_query($query2, $rs->fields['id']);

			if ($rs2->recordCount() > 0) {
				$swinstalled_domain = tr('This package is installed on following domain(s):');
				$swinstalled_domain .= "<ul>";

				while (!$rs2->EOF) {
					$swinstalled_domain .= "<li>" . $rs2->fields['domain'] . "</li>";
					$rs2->moveNext();
				}
				$swinstalled_domain .= "</ul>";
				$tpl->assign('SW_INSTALLED', $swinstalled_domain);
			} else {
				$tpl->assign('SW_INSTALLED', tr('This package is not installed'));
			}

			if ($rs->fields['swdepot'] == "yes") {
				$tpl->assign('TR_NAME', tr('%1$s - (Softwaredepot)', $rs->fields['name']));
				$tpl->assign('SOFTWARE_IS_NOT_IN_SOFTWAREDEPOT', '');
				$tpl->parse('SOFTWARE_IS_IN_SOFTWAREDEPOT', 'software_is_in_softwaredepot');
			} else {
				$import_url = "software_import.php?id=" . $rs->fields['id'];
				$del_url = "software_delete.php?id=" . $rs->fields['id'];
				$tpl->assign(array(
								  'TR_NAME' => $rs->fields['name'],
								  'IMPORT_LINK' => $import_url,
								  'DELETE_LINK' => $del_url));

				$tpl->parse('SOFTWARE_IS_NOT_IN_SOFTWAREDEPOT', 'software_is_not_in_softwaredepot');
				$tpl->assign('SOFTWARE_IS_IN_SOFTWAREDEPOT', '');
			}

			$tpl->assign(array(
							  'LINK_COLOR' => '#000000',
							  'TR_TOOLTIP' => $rs->fields['description'],
							  'TR_VERSION' => $rs->fields['version'],
							  'TR_LANGUAGE' => $rs->fields['language'],
							  'TR_TYPE' => $rs->fields['type'],
							  'TR_ADMIN' => 'List',
							  'TR_RESELLER' => $rs->fields['admin'],
							  'TR_SOFTWARE_DEPOT' => tr('%1$s`s - Software', $rs->fields['admin']),
							  'TR_IMPORT' => tr('Import'),
							  'TR_SOFTWARE_IMPORT' => tr('Depot import'),
							  'TR_SOFTWARE_DELETE' => tr('Delete'),
							  'TR_DELETE' => tr('Delete'),
							  'IS_IN_SOFTWAREDEPOT' => tr('N/A'),
							  'TR_MESSAGE_IMPORT' => tr('Are you sure you want to import this package into the software depot?', true),
							  'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete this package?', true)));

			$tpl->parse('LIST_SOFTWAREDEPOT', '.list_softwaredepot');
			$rs->moveNext();
		}
		$tpl->assign('NO_SOFTWAREDEPOT_LIST', '');
	} else {
		$query = "
			SELECT
				`admin_name` as admin
			FROM
				`admin`
			WHERE
				`admin_id` = ?
		";
		$reseller = exec_query($query, $reseller_id);

		if ($reseller->recordCount() > 0) {
			$tpl->assign(array(
							  'NO_SOFTWAREDEPOT' => tr('No software available'),
							  'TR_SOFTWARE_DEPOT' => tr('%1$s`s - Software', $reseller->fields['admin']),
							  'TR_SOFTWARE_IMPORT' => tr('Depot import'),
							  'TR_SOFTWARE_DELETE' => tr('Delete')));

			$tpl->parse('NO_SOFTWAREDEPOT_LIST', 'no_softwaredepot_list');
			$tpl->assign('LIST_SOFTWAREDEPOT', '');
		} else {
			set_page_message(tr('Wrong reseller id.'), 'error');
			redirectTo('software_manage.php');
		}
	}

	return $rs->recordCount();
}

/**
 * Must be documented.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return int
 */
function get_reseller_software($tpl)
{
	$query = "
		SELECT
			t1.`admin_id` as reseller_id, t1.`admin_name` as reseller
		FROM
			`admin` t1
		LEFT JOIN
			`reseller_props` AS t2 ON t2.reseller_id = t1.`admin_id`
		WHERE
			t1.`admin_type` = 'reseller'
		AND
			t2.`software_allowed` = 'yes'
		ORDER BY
			t1.`admin_id` ASC
	";
	$rs = execute_query($query);

	if ($rs->recordCount() > 0) {
		while (!$rs->EOF) {
			$query = "
				SELECT
					`software_id`
				FROM
					`web_software`
				WHERE
					`reseller_id` = ?
			";

			$rssoftware = exec_query($query, $rs->fields['reseller_id']);
			$software_ids = array();

			while ($data = $rssoftware->fetchRow()) {
				$software_ids[] = $data['software_id'];
			}

			$query = "
				SELECT
					count(`software_id`) as swdepot
				FROM
					`web_software`
				WHERE
					`software_active` = 1
				AND
					`software_depot` = 'yes'
				AND
					`reseller_id` = ?
			";
			$rscountswdepot = exec_query($query, $rs->fields['reseller_id']);

			$query = "
				SELECT
					count(`software_id`) as waiting
				FROM
					`web_software`
				WHERE
					`software_active` = 0
				AND
					`reseller_id` = ?
			";
			$rscountwaiting = exec_query($query, $rs->fields['reseller_id']);

			$query = "
				SELECT
					count(`software_id`) as activated
				FROM
					`web_software`
				WHERE
					`software_active` = 1
				AND
					`reseller_id` = ?
			";
			$rscountactivated = exec_query($query, $rs->fields['reseller_id']);

			if (count($software_ids) > 0) {
				$query = "
					SELECT
						count(`domain_id`) as in_use
					FROM
						`web_software_inst`
					WHERE
						`software_id`
					IN
						(" . implode(',', $software_ids) . ")
					AND
						`software_status` = 'ok'
				";
				$rscountin_use = execute_query($query);
				$sw_in_use = $rscountin_use->fields['in_use'];
			} else {
				$sw_in_use = 0;
			}

			$tpl->assign(array(
							  'RESELLER_NAME' => $rs->fields['reseller'],
							  'RESELLER_ID' => $rs->fields['reseller_id'],
							  'RESELLER_COUNT_SWDEPOT' => $rscountswdepot->fields['swdepot'],
							  'RESELLER_COUNT_WAITING' => $rscountwaiting->fields['waiting'],
							  'RESELLER_COUNT_ACTIVATED' => $rscountactivated->fields['activated'],
							  'RESELLER_SOFTWARE_IN_USE' => $sw_in_use));

			$tpl->parse('LIST_RESELLER', '.list_reseller');
			$rs->moveNext();
		}
		$tpl->assign('NO_RESELLER_LIST', '');
	} else {
		$tpl->assign('NO_RESELLER', tr('No reseller with activated software installer found!'));
		$tpl->parse('NO_RESELLER_LIST', '.no_reseller_list');
		$tpl->assign('LIST_RESELLER', '');
	}

	return $rs->recordCount();
}


/**
 * Must be documented
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $software_id
 * @return int
 */
function get_reseller_rights($tpl, $software_id)
{
	$query = "
		SELECT
			a.`software_id`, a.`software_master_id`, a.`reseller_id`,
			a.`rights_add_by`, b.`admin_name` as reseller
		FROM
			`web_software` a,
			`admin` b
		WHERE
			a.`reseller_id` = b.`admin_id`
		AND
			a.`software_depot` = 'yes'
		AND
			a.`software_master_id` = ?
	";
	$rs = exec_query($query, $software_id);

	if ($rs->recordCount() > 0) {
		while (!$rs->EOF) {
			$adminquery = "
				SELECT
					`admin_name` as administrator
				FROM
					`admin`
				WHERE
					`admin_id` = ?
			";
			$rs_admin = exec_query($adminquery, $rs->fields['rights_add_by']);

			if ($rs_admin->fields['administrator'] == "") {
				$added_by = tr('Admin not available');
			} else {
				$added_by = $rs_admin->fields['administrator'];
			}

			$remove_rights_url = "software_change_rights.php?id=" . $rs->fields['software_master_id'] . "&reseller_id=" . $rs->fields['reseller_id'];
			$tpl->assign(array(
							  'RESELLER' => $rs->fields['reseller'],
							  'ADMINISTRATOR' => $added_by,
							  'TR_REMOVE_RIGHT' => tr('Remove'),
							  'TR_MESSAGE_REMOVE' => tr('Are you sure to remove the permissions ?', true),
							  'REMOVE_RIGHT_LINK' => $remove_rights_url));

			$tpl->parse('LIST_RESELLER', '.list_reseller');
			$rs->moveNext();
		}
		$tpl->assign('NO_RESELLER_LIST', '');
	} else {
		$tpl->assign(array(
						  'NO_RESELLER' => tr('No reseller with permissions for this software found.'),
						  'LIST_RESELLER' => ''));

		$tpl->parse('NO_RESELLER_LIST', '.no_reseller_list');
	}

	return $rs->recordCount();
}

/**
 * Must be documented.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $software_id
 * @return void
 */
function get_reseller_list($tpl, $software_id)
{
	$query = "
		SELECT
			a.`reseller_id`, b.`admin_name` as reseller
		FROM
			`reseller_props` a, `admin` b
		WHERE
			a.`reseller_id` = b.`admin_id`
		AND
			a.`software_allowed` = 'yes'
		AND
			a.`softwaredepot_allowed` = 'yes'
	";
	$rs = execute_query($query);

	if ($rs->recordCount() > 0) {
		$reseller_count = 0;

		while (!$rs->EOF) {
			$query2 = "
				SELECT
					`reseller_id`
				FROM
					`web_software`
				WHERE
					`reseller_id` = ?
				AND
					`software_master_id` = ?
			";
			$rs2 = exec_query($query2, array($rs->fields['reseller_id'], $software_id));

			if ($rs2->recordCount() === 0) {
				$tpl->assign(array(
								  'ALL_RESELLER_NAME' => tr('All reseller'),
								  'RESELLER_ID' => $rs->fields['reseller_id'],
								  'RESELLER_NAME' => $rs->fields['reseller'],
								  'SOFTWARE_ID_VALUE' => $software_id));

				$tpl->parse('RESELLER_ITEM', '.reseller_item');
				$reseller_count++;
			}

			$rs->moveNext();
		}

		if ($reseller_count > 0) {
			$tpl->parse('SELECT_RESELLER', '.select_reseller');
			$tpl->assign('NO_SELECT_RESELLER', '');
		} else {
			$tpl->assign(array(
							  'NO_RESELLER_AVAILABLE' => tr('No reseller available to add the permissions.'),
							  'SELECT_RESELLER' => '',
							  'RESELLER_ITEM' => ''));

			$tpl->parse('NO_SELECT_RESELLER', '.no_select_reseller');
		}
	} else {
		$tpl->assign(array(
						  'NO_RESELLER_AVAILABLE' => tr('No reseller available to add the permissions.'),
						  'SELECT_RESELLER' => '',
						  'RESELLER_ITEM' => ''));

		$tpl->parse('NO_SELECT_RESELLER', '.no_select_reseller');
	}
}


/***********************************************************************************************************************
 * These functions are used by reseller
 */

/**
 * Must be documented.
 *
 * @param int $reseller_id Reseller unique identifier
 * @param string $file_name
 * @param int $sw_id
 * @return void
 */
function send_new_sw_upload($reseller_id, $file_name, $sw_id)
{
	$query = 'SELECT `admin_name` as reseller, `created_by`, `email` as res_email FROM `admin` WHERE `admin_id` = ?';
	$stmt = exec_query($query, $reseller_id);

	$from_name = $stmt->fields['reseller'];
	$from_email = $stmt->fields['res_email'];
	$admin_id = $stmt->fields['created_by'];

	$query = 'SELECT `email` as adm_email, `admin_name` as admin FROM `admin` WHERE `admin_id` = ?';
	$stmt = exec_query($query, $admin_id);

	$to_name = $stmt->fields['admin'];
	$to_email = $stmt->fields['adm_email'];

	if ($from_name) {
		$from = encode_mime_header($from_name) . " <$from_email>";
	} else {
		$from = $from_email;
	}

	$search = array();
	$replace = array();
	$search [] = '{ADMIN}';
	$replace[] = $to_name;
	$search [] = '{SOFTWARE}';
	$replace[] = $file_name;
	$search [] = '{SOFTWARE_ID}';
	$replace[] = $sw_id;
	$search [] = '{RESELLER}';
	$replace[] = $from_name;
	$search [] = '{RESELLER_ID}';
	$replace[] = $reseller_id;

	$headers = "From: $from\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/plain; charset=utf-8\r\n";
	$headers .= "Content-Transfer-Encoding: 8bit\r\n";
	$headers .= "X-Mailer: i-MSCP Mailer";

	$subject = tr('{RESELLER} uploaded a new software package');
	$message = tr('Dear {ADMIN},
	{RESELLER} has uploaded a new software package.

	Details:
	Reseller ID: {RESELLER_ID}
	Package Name: {SOFTWARE}
	Package ID: {SOFTWARE_ID}

	Please login into your i-MSCP control panel for more details.', true);

	$subject = str_replace($search, $replace, $subject);
	$message = str_replace($search, $replace, $message);

	$subject = encode_mime_header($subject);

	mail($to_email, $subject, $message, $headers, "-f $from_email");
}

/**
 * Check wheter the reseller has access to the websoftware depot
 *
 * @param int $user_id
 * @return string yes if reseller has access to the web software repository, no otherwise
 */
function ask_reseller_is_allowed_web_depot($user_id)
{
	$query = "
		SELECT
			`websoftwaredepot_allowed`
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
	";
	$rs = exec_query($query, $user_id);

	return $rs->fields['websoftwaredepot_allowed'];
}

/**
 * Must be documented.
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $user_id Reseller unique identifier
 * @return int
 */
function get_avail_software_reseller($tpl, $user_id)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			`software_allowed`
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
	";
	$rs = exec_query($query, $user_id);

	$software_allowed = $rs->fields('software_allowed');

	if ($software_allowed == 'yes') {
		if (isset($_GET['sortby']) && isset($_GET['order'])) {
			if ($_GET['order'] === 'asc' || $_GET['order'] === "desc") {
				if ($_GET['sortby'] === 'name') {
					$ordertype = "`software_name` " . $_GET['order'];
				} elseif ($_GET['sortby'] === 'status') {
					$ordertype = "`software_active` " . $_GET['order'];
				} elseif ($_GET['sortby'] === 'language') {
					$ordertype = '`software_language` ' . $_GET['order'];
				} elseif ($_GET['sortby'] === 'type') {
					$ordertype = '`software_type` ' . $_GET['order'];
				} else {
					$ordertype = '`software_active` ASC, `software_type` ASC';
				}
			} else {
				$ordertype = '`software_active` ASC, `software_type` ASC';
			}
		} else {
			$ordertype = '`software_active` ASC, `software_type` ASC';
		}

		$query = "
			SELECT
				`software_id` as id, `reseller_id` as resellerid,
				`software_name` as name, `software_version` as version,
				`software_language` as language, `software_desc` as description,
				`software_type` as type, `software_active` as swactive,
				`software_archive` as filename, `software_status` as swstatus,
				`software_depot` as softwaredepot
			FROM
				`web_software`
			WHERE
				`reseller_id` = ?
			ORDER BY
				$ordertype
		";
		$rs = exec_query($query, $user_id);

		if ($rs->recordCount() > 0) {
			while (!$rs->EOF) {
				if ($rs->fields['swstatus'] == 'ok' || $rs->fields['swstatus'] == 'ready') {
					if ($rs->fields['swstatus'] == "ready") {
						$updatequery = "
							UPDATE
								`web_software`
							SET
								`software_status` = 'ok'
							WHERE
								`software_id` = ?
						";
						exec_query($updatequery, $rs->fields['id']);

						send_new_sw_upload($user_id, $rs->fields['filename'] . ".tar.gz", $rs->fields['id']);
						set_page_message(tr('Package installed successfully... Awaiting release from admin!'), 'success');
					}

					$url = "software_delete.php?id=" . $rs->fields['id'];

					$query2 = "
						SELECT
							`domain`.`domain_id` as did,
							`domain`.`domain_name` as domain,
							`web_software_inst`.`domain_id` as wdid,
							`web_software_inst`.`software_id` as sid,
							`web_software`.`software_id` as wsid
						FROM
							`domain`, `web_software`, `web_software_inst`
						WHERE
							`web_software_inst`.`software_id` = ?
						AND
							`web_software`.`software_id` = `web_software_inst`.`software_id`
						AND
							`domain`.`domain_id` = `web_software_inst`.`domain_id`
					";
					$rs2 = exec_query($query2, $rs->fields['id']);

					if ($rs2->recordCount() > 0) {
						$swinstalled_domain = tr('This software is installed on following domain(s):');
						$swinstalled_domain .= "<ul>";

						while (!$rs2->EOF) {
							$swinstalled_domain .= "<li>" . $rs2->fields['domain'] . "</li>";
							$rs2->moveNext();
						}

						$swinstalled_domain .= "</ul>";
						$tpl->assign('SW_INSTALLED', $swinstalled_domain);
					} else {
						$tpl->assign('SW_INSTALLED', tr('This package is not installed yet'));
					}

					$tpl->assign(array(
									  'SW_NAME' => $rs->fields['name'],
									  'LINK_COLOR' => '#000000',
									  'SW_VERSION' => $rs->fields['version'],
									  'SW_LANGUAGE' => $rs->fields['language'],
									  'SW_DESCRIPTION' => $rs->fields['description'],
									  'SW_TYPE' => $rs->fields['type'],
									  'DELETE' => $url,
									  'TR_DELETE' => tr('Delete'),
									  'WAITING_SOFTWARE_LIST' => '',
									  'SOFTWARE_ICON' => 'delete'));

					if ($rs->fields['swactive'] == "0") {
						$tpl->assign('SW_STATUS', tr('waiting for activation'));
					} elseif ($rs->fields['swactive'] == "1" && $rs->fields['softwaredepot'] == "yes") {
						$tpl->assign('SW_STATUS', tr('activated (Softwaredepot)'));
					} else {
						$tpl->assign('SW_STATUS', tr('activated'));
					}
				} else {
					if ($rs->fields['swstatus'] == 'toadd') {
						$url = 'software_delete.php?id=' . $rs->fields['id'];
						$tpl->assign(array(
										  'SW_NAME' => tr('Installing your uploaded package. Please refresh this page.'),
										  'LINK_COLOR' => '#FF0000',
										  'SW_VERSION' => '',
										  'SW_LANGUAGE' => '',
										  'SW_DESCRIPTION' => tr('After your upload the package it will be installed on your systems.<br />Refresh your site to see the new status!'),
										  'SW_TYPE' => '',
										  'DELETE' => $url,
										  'TR_DELETE' => tr('Delete'),
										  'SW_STATUS' => tr('installing'),
										  'SOFTWARE_ICON' => 'disabled'));
					} else {
						if ($rs->fields['swstatus'] == 'todelete') {
							$tpl->assign(array(
											  'SW_NAME' => tr('Failure in the package. Deleting!'),
											  'LINK_COLOR' => '#FF0000',
											  'SW_VERSION' => '',
											  'SW_LANGUAGE' => '',
											  'SW_DESCRIPTION' => tr('Check your package. There is an error inside!<br />Refresh your site to see the new status!'),
											  'SW_TYPE' => '',
											  'DELETE' => '',
											  'TR_DELETE' => '',
											  'SW_STATUS' => tr('deleting'),
											  'SOFTWARE_ICON' => 'disabled'));

							set_page_message(tr('The package is corrupt. Please correct it.'), 'error');
						} elseif (preg_match("/double_depot_/i", $rs->fields['swstatus'])) {
							$tpl->assign(array(
											  'SW_NAME' => tr('Package already exists in the software repository!'),
											  'LINK_COLOR' => '#FF0000',
											  'SW_VERSION' => '',
											  'SW_LANGUAGE' => '',
											  'SW_DESCRIPTION' => tr('Please contact the administrator!<br />Ask him for the permissions to use this package.<br />It is not allowed to upload this packet two times.<br />Refresh your site to see the new status!'),
											  'SW_TYPE' => '',
											  'DELETE' => '',
											  'TR_DELETE' => '',
											  'SW_STATUS' => tr('deleting'),
											  'SOFTWARE_ICON' => 'disabled'));
							set_page_message(tr('This package already exists in the administrator software repository.'), 'error');
						} elseif (preg_match("/double_res_/i", $rs->fields['swstatus'])) {
							$tpl->assign(array(
											  'SW_NAME' => tr('Package already exists in your software repository!'),
											  'LINK_COLOR' => '#FF0000',
											  'SW_VERSION' => '',
											  'SW_LANGUAGE' => '',
											  'SW_DESCRIPTION' => tr('Check your own uploads!<br />Ask the administrator if you don\'t find the package.<br />It is not allowed to upload this packages two times.<br />Refresh your site to see the new status!'),
											  'SW_TYPE' => '',
											  'DELETE' => '',
											  'TR_DELETE' => '',
											  'SW_STATUS' => tr('deleting'),
											  'SOFTWARE_ICON' => 'disabled'));

							set_page_message(tr('This package already exists in your software repository.'), 'error');
						}

						$del_path = $cfg->GUI_APS_DIR . "/" . $rs->fields['resellerid'] . "/" . $rs->fields['filename'] . "-" . $rs->fields['id'] . ".tar.gz";
						@unlink($del_path);

						$delete = "DELETE FROM `web_software` WHERE `software_id` = ?";
						exec_query($delete, $rs->fields['id']);
					}
				}
				$tpl->parse('LIST_SOFTWARE', '.list_software');
				$rs->moveNext();
			}
			$tpl->assign('NO_SOFTWARE_LIST', '');
		} else {
			$tpl->assign(array(
							  'NO_SOFTWARE' => tr('You do not have any software uploaded yet'),
							  'LIST_SOFTWARE' => ''));
			$tpl->parse('NO_SOFTWARE_LIST', '.no_software_list');
		}
		return $rs->recordCount();
	} else {
		$tpl->assign(array(
						  'NO_SOFTWARE' => tr('You do not have permissions to upload software'),
						  'LIST_SOFTWARE' => ''));
		$tpl->parse('NO_SOFTWARE_LIST', '.no_software_list');

		return 0;
	}
}

/************************************************************************************
 * This functions are used by client
 */

/**
 * Generate user actions for a specific software.
 *
 * @param int $software_id Software unique identifier
 * @param int $dmn_id Domain unique identifier
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @return array
 */
function gen_user_software_action($software_id, $dmn_id, $tpl)
{
	/** @var iMSCP_Config_Handler_File $cfg */
	$cfg = iMSCP_Registry::get('config');

	$query = "SELECT `software_status` FROM `web_software_inst` WHERE `software_id` = ? AND `domain_id` = ?";
	$stmt = exec_query($query, array($software_id, $dmn_id));

	if (!$stmt->rowCount()) {
		$software_status = 'not installed';
		$software_icon = 'edit';
	} else {
		if ($stmt->fields['software_status'] == 'ok') {
			$software_status = 'installed';
			$software_icon = 'delete';
		} elseif ($stmt->fields['software_status'] == 'toadd') {
			$software_status = 'installing';
			$software_icon = 'disabled';
		} elseif ($stmt->fields['software_status'] == 'todelete') {
			$software_status = 'deleting';
			$software_icon = 'delete';
		} else {
			$software_status = 'n/a';
			$software_icon = 'disabled';
		}
	}
	if ($software_status == 'installing') {
		$tpl->assign(
			array(
				'TR_MESSAGE_DELETE' => '',
				'TR_MESSAGE_INSTALL' => ''));

		$tpl->parse('SOFTWARE_ACTION_DELETE', '');
		return array(tr('Install in progress'), '', '', $software_status, $software_icon);
	} elseif ($software_status == 'deleting') {
		$tpl->assign(
			array(
				'TR_MESSAGE_DELETE' => '',
				'TR_MESSAGE_INSTALL' => ''));

		$tpl->parse('SOFTWARE_ACTION_DELETE', '');

		return array(tr('Deletion in progress'), '', '', $software_status, $software_icon);
	} elseif ($software_status == 'installed') {
		$tpl->assign(
			array(
				'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete this package?', true),
				'SOFTWARE_ACTION_INSTALL' => ''));

		return array(tr('Uninstall'), 'software_delete.php?id=' . $software_id, 'software_view.php?id=' . $software_id, $software_status, $software_icon);
	} else {
		$tpl->assign(
			array(
				'TR_MESSAGE_INSTALL' => tr('Are you sure to install this package?', true),
				'SOFTWARE_ACTION_DELETE' => ''));

		return array(tr('Install'), 'software_install.php?id=' . $software_id, 'software_view.php?id=' . $software_id, $software_status, $software_icon);
	}
}

/**
 * Generate available software list for a specific customer.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $domainId Domain unique identifier
 * @param int $resellerId Reseller unique identifier
 * @return int Total number of available software
 */
function gen_software_list($tpl, $domainId, $resellerId)
{
	/** @var iMSCP_Config_Handler_File $cfg */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			`software_id`, `software_status`, `software_res_del`, `software_name`, `software_version`
		FROM
			`web_software_inst`
		WHERE
			`domain_id` = ?
		AND
			software_res_del = ?
	";
	$stmt = exec_query($query, array($domainId, 1));

	if (!$stmt->rowCount()) {
		$tpl->assign(
			array(
				'SOFTWARE_DEL_ITEM' => '',
				'DEL_SOFTWARE_SUPPORT' => ''
			)
		);
	} else {
		while (!$stmt->EOF) {
			if ($stmt->fields['software_status'] == 'ok') {
				$delsoftware_status = 'installed';
				$del_software_action_script = "software_delete.php?id=" . $stmt->fields['software_id'];
				$tpl->assign(
					array(
						'DEL_SOFTWARE_ACTION' => tr('Uninstall'),
						'TR_RES_MESSAGE_DELETE' => tr('Are you sure you want to delete this package?', true
						)
					)
				);
			} elseif ($stmt->fields['software_status'] == 'toadd') {
				$delsoftware_status = 'installing';
				$del_software_action_script = "software_delete.php?id=" . $stmt->fields['software_id'];
				$tpl->assign(
					array(
						'DEL_SOFTWARE_ACTION' => tr('Uninstall'),
						'TR_RES_MESSAGE_DELETE' => tr('Are you sure you want to delete this package?', true)
					)
				);
			} elseif ($stmt->fields['software_status'] == 'todelete') {
				$delsoftware_status = 'deleting';
				$del_software_action_script = '';

				$tpl->assign(
					array(
						'DEL_SOFTWARE_ACTION' => '',
						'TR_RES_MESSAGE_DELETE' => ''
					)
				);
			} else {
				$delsoftware_status = 'n/a';
				$del_software_action_script = '';
			}

			$software_name = $stmt->fields['software_name'];
			$software_version = $stmt->fields['software_version'];

			$tpl->assign(
				array(
					'SOFTWARE_DEL_RES_MESSAGE' => tr('This Package (%s, V%s) was deleted by your reseller. You can only uninstall this package!<br />Please delete the files and database for this package manually!', $software_name, $software_version),
					'DEL_SOFTWARE_STATUS' => $delsoftware_status,
					'DEL_SOFTWARE_ACTION_SCRIPT' => $del_software_action_script
				)
			);

			$tpl->parse('DEL_SOFTWARE_ITEM', '.del_software_item');
			$stmt->moveNext();
		}

		$tpl->assign(
			array(
				'TR_DEL_SOFTWARE' => tr('Installed Package which was deleted by your reseller.'),
				'TR_DEL_STATUS' => tr('Status'),
				'TR_DEL_ACTION' => tr('Action')
			)
		);

		$tpl->parse('DEL_SOFTWARE_SUPPORT', '.del_software_support');
	}

	$query = "
			SELECT
				`software_id`, `software_name`, `software_version`, `software_language`, `software_type`,
				`software_db`, `software_desc`
			FROM
				`web_software`
			WHERE
				`reseller_id` = ?
			AND
				`software_active` = ?
			ORDER BY
				`software_name`
		";
	$stmt = exec_query($query, array($resellerId, 1));

	if (!$stmt->rowCount()) {
		$tpl->assign(
			array(
				'NO_SOFTWARE_AVAIL' => tr('No software available'),
				'SOFTWARE_LIST' => ''
			)
		);
		return 0;
	} else {
		$tpl->assign('NO_SOFTWARE_SUPPORT', '');

		while (!$stmt->EOF) {
			list(
				$software_action, $software_action_script, $view_software_script,
				$software_status, $software_icon
			) = gen_user_software_action($stmt->fields['software_id'], $domainId, $tpl);

			$tpl->assign(
				array(
					'SOFTWARE_NAME' => $stmt->fields['software_name'],
					'SOFTWARE_DESCRIPTION' => $stmt->fields['software_desc'],
					'SOFTWARE_VERSION' => $stmt->fields['software_version'],
					'SOFTWARE_LANGUAGE' => $stmt->fields['software_language'],
					'SOFTWARE_TYPE' => $stmt->fields['software_type'],
					'SOFTWARE_STATUS' => $software_status,
					'SOFTWARE_ACTION' => $software_action,
					'SOFTWARE_ACTION_SCRIPT' => $software_action_script,
					'VIEW_SOFTWARE_SCRIPT' => $view_software_script,
					'SOFTWARE_ICON' => $software_icon
				)
			);

			if ($stmt->fields['software_db'] == '1') {
				$tpl->assign('SOFTWARE_NEED_DATABASE', tr('required'));
			} else {
				$tpl->assign('SOFTWARE_NEED_DATABASE', tr('not required'));
			}

			if ($software_status == 'installed') {
				$tpl->parse('SOFTWARE_ACTION_DELETE', 'software_action_delete');
			} elseif ($software_status == "not installed") {
				$tpl->parse('SOFTWARE_ACTION_INSTALL', 'software_action_install');
			}

			$tpl->parse('SOFTWARE_ITEM', '.software_item');
			$stmt->moveNext();
		}

		return $stmt->rowCount();
	}
}

/**
 * Must be documented.
 *
 * @param  $software_id
 * @param $dmn_created_id
 * @return bool
 */
function check_software_avail($software_id, $dmn_created_id)
{
	$check_avail = "
			SELECT
				`reseller_id` AS reseller
			FROM
				`web_software`
			WHERE
				`software_id` = ?
			AND
				`reseller_id` = ?
	";
	$stmt = exec_query($check_avail, array($software_id, $dmn_created_id));

	if (!$stmt->rowCount()) {
		return false;
	} else {
		return true;
	}
}

/**
 * Must be documented.
 *
 * @param  iMSCP_pTemplate $tpl
 * @param $dmn_id
 * @param $software_id
 * @return void
 */
function check_is_installed($tpl, $dmn_id, $software_id)
{
	$is_installed = "
		SELECT
			`software_id`, `software_prefix`, `db`, `path`
		FROM
			`web_software_inst`
		WHERE
			`domain_id` = ?
		AND
			`software_id` = ?
	";
	$stmt = exec_query($is_installed, array($dmn_id, $software_id));

	if (!$stmt->rowCount()) {
		$tpl->assign(
			array(
				'INSTALLED_SOFTWARE_INFO' => '',
				'SOFTWARE_INSTALL_BUTTON' => 'software_install.php?id=' . $software_id
			)
		);
		$tpl->parse('SOFTWARE_INSTALL', '.software_install');
	} else {
		$tpl->assign(
			array(
				'SOFTWARE_INSTALL_BUTTON' => '',
				'SOFTWARE_STATUS' => tr('installed'),
				'SOFTWARE_INSTALL_PATH' => $stmt->fields['path'],
				'SOFTWARE_INSTALL_DATABASE' => $stmt->fields['db'],
				'TR_SOFTWARE_INFO' => tr('Installation details'),
				'TR_SOFTWARE_STATUS' => tr('Software status'),
				'TR_SOFTWARE_INSTALL_PATH' => tr('Installation path'),
				'TR_SOFTWARE_INSTALL_DATABASE' => tr('Used database'),
				'SOFTWARE_INSTALL' => ''
			)
		);

		$tpl->parse('INSTALLED_SOFTWARE_INFO', '.installed_software_info');
	}
}

/**
 * Must be documented.
 *
 * @param  iMSCP_pTemplate $tpl
 * @param $dmn_id
 * @param $software_id
 * @param $dmn_created_id
 * @param $dmn_sqld_limit
 * @return void
 */
function get_software_props($tpl, $dmn_id, $software_id, $dmn_created_id, $dmn_sqld_limit)
{
	if (!check_software_avail($software_id, $dmn_created_id)) {
		set_page_message(tr('Software not found!'), 'error');
		redirectTo('software.php');
		exit;
	} else {
		$query = "
			SELECT
				`software_name`, `software_version`, `software_language`, `software_type`, `software_db`,
				`software_link`, `software_desc`
			FROM
				`web_software`
			WHERE
				`software_id` = ?
			AND
				`reseller_id` = ?
		";
		$stmt = exec_query($query, array($software_id, $dmn_created_id));

		if ($stmt->fields['software_db'] == 1) {
			$tpl->assign('SOFTWARE_DB', tr('yes'));

			if ($dmn_sqld_limit == '-1') {
				$tpl->assign(
					array(
						'STATUS_COLOR' => 'red',
						'STATUS_MESSAGE' => tr('You need a Database for this software')));

				$tpl->parse('SOFTWARE_MESSAGE', '.software_message');
			} else {
				$tpl->assign(
					array(
						'STATUS_COLOR' => 'green',
						'STATUS_MESSAGE' => '',
						'SOFTWARE_MESSAGE' => ''));
			}
		} else {
			$tpl->assign(
				array(
					'SOFTWARE_DB' => tr('no'),
					'SOFTWARE_MESSAGE' => '',
					'STATUS_MESSAGE' => ''));
		}

		$sw_link = $stmt->fields['software_link'];

		if (!preg_match("/http:/", $sw_link) && !preg_match("/https:/", $sw_link)) {
			$sw_link = "http://" . $sw_link;
		}

		$tpl->assign(
			array(
				'SOFTWARE_NAME' => $stmt->fields['software_name'],
				'SOFTWARE_VERSION' => $stmt->fields['software_version'],
				'SOFTWARE_LANGUAGE' => $stmt->fields['software_language'],
				'SOFTWARE_TYPE' => $stmt->fields['software_type'],
				'SOFTWARE_LINK' => $sw_link,
				'SOFTWARE_DESC' => tohtml($stmt->fields['software_desc'])));

		check_is_installed($tpl, $dmn_id, $software_id);
		$tpl->parse('SOFTWARE_ITEM', 'software_item');
	}
}

/**
 * Must be documented.
 *
 * @param  iMSCP_pTemplate $tpl
 * @param $dmn_id
 * @param $software_id
 * @param $dmn_created_id
 * @param $dmn_sqld_limit
 * @return void
 */
function get_software_props_install($tpl, $dmn_id, $software_id, $dmn_created_id, $dmn_sqld_limit)
{
	if (!check_software_avail($software_id, $dmn_created_id)) {
		set_page_message(tr('Software not found!'), 'error');
		redirectTo('software.php');
	} else {
		gen_user_domain_list($tpl, $_SESSION['user_id']);
		$query = "
			SELECT
				`software_name`, `software_type`, `software_db`
			FROM
				`web_software`
			WHERE
				`software_id` = ?
			AND
				`reseller_id` = ?
		";
		$stmt = exec_query($query, array($software_id, $dmn_created_id));

		check_is_installed($tpl, $dmn_id, $software_id);

		if ($stmt->fields['software_db'] == 1) {
			$tpl->assign('SOFTWARE_DB', tr('yes'));

			if ($dmn_sqld_limit == '-1') {
				$tpl->parse('REQUIRE_INSTALLDB', '.require_installdb');
			}

			generate_sqlDbUserLists($tpl, $dmn_id, $dmn_sqld_limit);
		} else {
			$tpl->assign(
				array(
					'SOFTWARE_DB' => tr('no'),
					'REQUIRE_INSTALLDB' => ''));
		}

		$tpl->assign(
			array(
				'TR_SOFTWARE_NAME' => $stmt->fields['software_name'],
				'SOFTWARE_TYPE' => $stmt->fields['software_type']));

		$tpl->parse('SOFTWARE_ITEM', '.software_item');
	}
}

/**
 * Must be documented.
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $user_id
 * @return void
 */
function gen_user_domain_list($tpl, $user_id)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$domain_id = get_user_domain_id($user_id);

	//Get Domain Data
	$querydomain = "SELECT `domain_name` FROM `domain` WHERE `domain_status` = 'ok' AND `domain_id` = ?";
	$rsdomain = exec_query($querydomain, $domain_id);

	//Get Aliase
	$queryaliase = "
		SELECT
			`alias_id`, `alias_name`, `alias_mount`
		FROM
			`domain_aliasses`
		WHERE
			`alias_status` = 'ok'
		AND
			`url_forward` = 'no'
		AND
			`domain_id` = ?
	";
	$rsaliase = exec_query($queryaliase, $domain_id);

	//Get Subdomains
	$querysubdomain = "
		SELECT
			`subdomain_id`, `subdomain_name`, `subdomain_mount`, `domain`.`domain_name`
		FROM
			`subdomain` JOIN `domain`
		ON
			`subdomain`.`domain_id` = `domain`.`domain_id`
		WHERE
			`subdomain`.`subdomain_status` = 'ok'
		AND
			`subdomain`.`domain_id` = ?
	";
	$rssubdomain = exec_query($querysubdomain, $domain_id);

	//Get Subaliase
	$querysubaliase = "
		SELECT
			`subdomain_alias_id`, `subdomain_alias_name`, `subdomain_alias_mount`,
			`domain_aliasses`.`alias_name`
		FROM
			`subdomain_alias` JOIN `domain_aliasses`
		ON
			`subdomain_alias`.`alias_id` = `domain_aliasses`.`alias_id`
		WHERE
			`subdomain_alias`.`subdomain_alias_status` = 'ok'
		AND
			`domain_id` = ?
	";
	$rssubaliase = exec_query($querysubaliase, $domain_id);

	if (isset($_POST['selected_domain'])) {
		list (
			$posted_domain_id, $posted_aliasdomain_id, $posted_subdomain_id, $posted_aliassubdomain_id,
		) = explode(';', $_POST['selected_domain']);
	} else {
		$posted_aliasdomain_id = 0;
		$posted_subdomain_id = 0;
		$posted_aliassubdomain_id = 0;
	}

	if (($rsaliase->rowCount() + $rssubdomain->recordCount() + $rssubaliase->recordCount()) > 0) {
		while (!$rsaliase->EOF) {
			if (isset($_POST['selected_domain']) && $posted_aliasdomain_id != 0) {
				if ($posted_aliasdomain_id == $rsaliase->fields['alias_id']) {
					$selecteddomain = $cfg->HTML_SELECTED;
				} else {
					$selecteddomain = '';
				}
			} else {
				$selecteddomain = '';
			}

			$tpl->assign(array(
							  'SELECTED_DOMAIN' => $selecteddomain,
							  'DOMAIN_NAME_VALUES' => $domain_id . ';' . $rsaliase->fields['alias_id'] . ';0;0;' . $rsaliase->fields['alias_mount'] . '/htdocs',
							  'DOMAIN_NAME' => decode_idna($rsaliase->fields['alias_name'])));

			$tpl->parse('SHOW_DOMAIN_LIST', '.show_domain_list');
			$rsaliase->moveNext();
		}
		while (!$rssubdomain->EOF) {
			if (isset($_POST['selected_domain']) && $posted_subdomain_id != 0) {
				if ($posted_subdomain_id == $rssubdomain->fields['subdomain_id']) {
					$selecteddomain = $cfg->HTML_SELECTED;
				} else {
					$selecteddomain = '';
				}
			} else {
				$selecteddomain = '';
			}

			$subdomainname = $rssubdomain->fields['subdomain_name'] . "." . $rssubdomain->fields['domain_name'];
			$tpl->assign(array(
							  'SELECTED_DOMAIN' => $selecteddomain,
							  'DOMAIN_NAME_VALUES' => $domain_id . ';0;' . $rssubdomain->fields['subdomain_id'] . ';0;' . $rssubdomain->fields['subdomain_mount'] . '/htdocs',
							  'DOMAIN_NAME' => decode_idna($subdomainname)));

			$tpl->parse('SHOW_DOMAIN_LIST', '.show_domain_list');
			$rssubdomain->moveNext();
		}
		while (!$rssubaliase->EOF) {
			if (isset($_POST['selected_domain']) && $posted_aliassubdomain_id != 0) {
				if ($posted_aliassubdomain_id == $rssubaliase->fields['subdomain_alias_id']) {
					$selecteddomain = $cfg->HTML_SELECTED;
				} else {
					$selecteddomain = '';
				}
			} else {
				$selecteddomain = '';
			}

			$aliassubdomainname = $rssubaliase->fields['subdomain_alias_name'] . "." . $rssubaliase->fields['alias_name'];
			$tpl->assign(array(
							  'SELECTED_DOMAIN' => $selecteddomain,
							  'DOMAIN_NAME_VALUES' => $domain_id . ';0;0;' . $rssubaliase->fields['subdomain_alias_id'] . ';' . $rssubaliase->fields['subdomain_alias_mount'] . '/htdocs',
							  'DOMAIN_NAME' => decode_idna($aliassubdomainname)));

			$tpl->parse('SHOW_DOMAIN_LIST', '.show_domain_list');
			$rssubaliase->moveNext();
		}

		$tpl->assign(array(
						  'DOMAINSTANDARD_NAME_VALUES' => $domain_id . ';0;0;0;/htdocs',
						  'DOMAINSTANDARD_NAME' => decode_idna($rsdomain->fields['domain_name'])));
	} else {
		$tpl->assign(array(
						  'SELECTED_DOMAIN' => '',
						  'DOMAIN_NAME_VALUES' => '',
						  'DOMAIN_NAME' => '',
						  'DOMAINSTANDARD_NAME_VALUES' => $domain_id . ';0;0;0;/htdocs',
						  'DOMAINSTANDARD_NAME' => decode_idna($rsdomain->fields['domain_name']),
						  'SHOW_DOMAIN_LIST' => ''));
	}
}

/**
 * Create lists of databases and their associated SQL users
 *
 * Note: Any database which doesn't have at least one SQL user is skipped.
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $dmnId
 * @param int $sqlDbLimit
 * @return int
 */
function generate_sqlDbUserLists($tpl, $dmnId, $sqlDbLimit)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$sqlUserFound = false;

	$stmt = exec_query('SELECT sqld_id, sqld_name FROM sql_database WHERE domain_id = ? ORDER BY sqld_name', $dmnId);
	$dbCount = $stmt->rowCount();

	if ($dbCount) {
		while ($db = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			$stmt1 = exec_query(
				'SELECT sqlu_id, sqlu_name FROM sql_user WHERE sqld_id = ? ORDER BY sqlu_name', $db['sqld_id']
			);

			if ($stmt1->rowCount()) {
				$sqlUserFound = true;

				$tpl->assign(
					array(
						'DB_NAME' => tohtml($db['sqld_name']),
						'SELECTED_DB' => (
								isset($_POST['selected_db']) && $_POST['selected_db'] == $db['sqld_name']
							) ? $cfg->HTML_SELECTED : ''
					)
				);

				$tpl->parse('INSTALLDB_ITEM', '.installdb_item');

				while ($sqlUser = $stmt1->fetchRow(PDO::FETCH_ASSOC)) {
					$tpl->assign(
						array(
							'SQLUSER_NAME' => tohtml($sqlUser['sqlu_name']),
							'SELECTED_DBUSER' => (
									isset($_POST['sql_user']) && $_POST['sql_user'] == $sqlUser['sqlu_name']
								) ? $cfg->HTML_SELECTED : ''
						)
					);

					$tpl->parse('INSTALLDBUSER_ITEM', '.installdbuser_item');
				}
			}
		}

		if (!$sqlUserFound) {
			goto NO_DATABASE_WITH_SQL_USER;
		} else {
			$tpl->assign(
				array(
					'CREATE_MESSAGE_DB' => '',
					'SOFTWAREDBUSER_MESSAGE' => ''
				)
			);
		}
	} else {
		NO_DATABASE_WITH_SQL_USER:
		$tpl->assign(
			array(
				'SELECT_INSTALLDB' => '',
				'SELECT_INSTALLDBUSER' => '',
				'ADD_DATABASE_MESSAGE' => tr('At first you must create a database which have at least one SQL user.'),
				'SOFTWAREDBUSER_MESSAGE' => '',
				'SOFTWARE_INSTALL' => ''
			)
		);

		$tpl->parse('CREATE_MESSAGE_DB', '.create_message_db');
	}

	if ($dbCount < $sqlDbLimit OR $sqlDbLimit == 0) {
		$tpl->assign(
			array(
				'ADD_DB_LINK' => 'sql_database_add.php',
				'BUTTON_ADD_DB' => tr('Add new database')
			)
		);

		$tpl->parse('CREATE_DB', '.create_db');
	} else {
		$tpl->assign(
			array(
				'CREATE_DB' => '',
				'CREATE_MESSAGE_DB' => ''
			)
		);
	}
}

/**
 * Check database connection.
 *
 * @param string $sql_database
 * @param string $sql_user
 * @param string $sql_pass
 * @return bool
 */
function check_db_connection($sql_database, $sql_user, $sql_pass)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	try {
		iMSCP_Database::connect(
			$sql_user, $sql_pass, $cfg->DATABASE_TYPE, $cfg->DATABASE_HOST, $sql_database, 'privateConnection'
		);

	} catch (PDOException $e) {
		return false;
	}

	return true;
}
