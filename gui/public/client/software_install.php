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
 */

/***********************************************************************************************************************
 *  Script functions
 */

/**
 * Generate Page
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $softwareId Software uique identifier
 * @return int Software unique identifier
 */
function client_generatePage($tpl, $softwareId)
{
	$customerId = $_SESSION['user_id'];
	$domainProperties = get_domain_default_props($customerId);

	$stmt = exec_query('SELECT created_by FROM admin WHERE admin_id = ?', $customerId);

	if ($stmt->rowCount()) {
		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

		get_software_props_install(
			$tpl, $domainProperties['domain_id'], $softwareId, $row['created_by'], $domainProperties['domain_sqld_limit']
		);
	} else {
		throw new iMSCP_Exception('An unexpected error occurred. Please contact your reseller.');
	}
}

/***********************************************************************************************************************
 * Main program
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

customerHasFeature('aps') or showBadRequestErrorPage();

if (isset($_GET['id']) && is_number($_GET['id'])) {
	$softwareId = intval($_GET['id']);

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
		if (
			isset($_POST['selected_domain']) && isset($_POST['other_dir']) && isset($_POST['install_username']) &&
			isset($_POST['install_password']) && isset($_POST['install_email'])
		) {
			# Required data
			$otherDir = clean_input($_POST['other_dir']);
			$appLoginName = clean_input($_POST['install_username']);
			$appPassword = clean_input($_POST['install_password']);
			$appEmail = clean_input($_POST['install_email']);

			$stmt = exec_query(
				'
					SELECT
						software_master_id, software_db, software_name, software_version, software_language,
						software_prefix, software_depot
					FROM
						web_software
					WHERE
						software_id = ?
				',
				$softwareId
			);

			if ($stmt->rowCount()) {
				$softwareData = $stmt->fetchRow(PDO::FETCH_ASSOC);

				$selectedDomain = explode(';', $_POST['selected_domain']);

				if (count($selectedDomain) == 5) {
					$domainId = intval($selectedDomain[0]);
					$aliasId = intval($selectedDomain[1]);
					$subId = intval($selectedDomain[2]);
					$subAliasId = intval($selectedDomain[3]);

					$domainProps = get_domain_default_props($_SESSION['user_id']);
					$error = false;

					if ($domainId == $domainProps['domain_id']) { # Ensure that selected domain is owner by customer
						# If any, ensure that the selected alias|subdomain|subAlias is owner by customer and get the
						# mount point
						if (($aliasId || $subId || $subAliasId)) {
							if ($aliasId) {
								$stmt = exec_query(
									'
										SELECT
											alias_mount mpoint
										FROM
											domain_aliasses
										WHERE
											alias_id = ?
										AND
											domain_id = ?
									',
									array($aliasId, $domainId)
								);
							} elseif ($subId) {
								$stmt = exec_query(
									'
										SELECT
											subdomain_mount mpoint
										FROM
											subdomain
										WHERE
											subdomain_id = ?
										AND
											domain_id = ?
									',
									array($subId, $domainId)
								);
							} elseif ($subAliasId) {
								$stmt = exec_query(
									'
										SELECT
											subdomain_alias_mount mpoint
										FROM
											subdomain_alias
										INNER JOIN
											domain_aliasses USING(alias_id)
										WHERE
											subdomain_alias_id = ?
										AND
											domain_id = ?
									',
									array($subAliasId, $domainId)
								);
							}
							if ($stmt->rowCount()) {
								$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
								$targetBasePath = $row['mpoint'];
							} else {
								showBadRequestErrorPage();
								exit;
							}
						} else {
							$targetBasePath = '';
						}

						$targetPathReg = '%^' . quotemeta($targetBasePath . '/htdocs') . '(?:/.*)?$%';

						if (!preg_match($targetPathReg, $otherDir)) {
							set_page_message(
								tr("You can't install the software outside the htdocs directory of the selected domain."),
								'error'
							);
							$error = true;
						} else {
							$vfs = new iMSCP_VirtualFileSystem($domainProps['domain_name']);

							if (!$vfs->exists($otherDir, 'd')) {
								set_page_message(
									tr(
										"The directory %s doesn't exists. Please create that directory using your file manager.",
										$otherDir
									),
									'error'
								);
								$error = true;
							} else {
								$stmt = exec_query(
									'
										SELECT
											software_name, software_version
										FROM
											web_software_inst
										WHERE
											domain_id = ?
										AND
											path = ?
									',
									array($domainId, $otherDir)
								);
								if ($stmt->rowCount()) {
									$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

									set_page_message(
										tr(
											'Please select another directory. %s (%s) is installed there.',
											$row['software_name'],
											$row['software_version']
										),
										'error'
									);

									$error = true;
								}
							}
						}

						# Note: Comma is not allowed in input data because it is used as data delimiter by the backend.

						# Check application username
						if (!validates_username($appLoginName)) {
							set_page_message(tr('Invalid username.'), 'error');
							$error = true;
						}

						# Check application password
						if (!checkPasswordSyntax($appPassword)) {
							$error = true;
						} elseif (strpos($appPassword, ',') !== false) {
							set_page_message(tr('Password with comma(s) are not accepted.'), 'error');
							$error = true;
						}

						# Check application email
						if (!chk_email($appEmail)) {
							set_page_message(tr('Invalid email address.'), 'error');
							$error = true;
						} elseif (strpos($appLoginName, ',') !== false) {
							set_page_message(tr('Email address with comma(s) are not accepted.'), 'error');
							$error = true;
						}

						# Check application database if required
						if ($softwareData['software_db']) {
							if (isset($_POST['selected_db']) && isset($_POST['sql_user'])) {
								$appDatabase = clean_input($_POST['selected_db']);
								$appSqlUser = clean_input($_POST['sql_user']);

								if (strpos($appDatabase, ',') !== false) {
									set_page_message(tr('Database with comma(s) in name is not accepted.'), 'error');
									$error = true;
								} elseif (strpos($appDatabase, ',') !== false) {
									set_page_message(tr('SQL user with comma(s) in name is not accepted.'), 'error');
									$error = true;
								} else {
									# Ensure that both SQL user and database are owned by customer and get SQL password
									$stmt = exec_query(
										'
											SELECT
												sqlu_pass
											FROM
												sql_user
											INNER JOIN
												sql_database USING(sqld_id)
											INNER JOIN
												domain using(domain_id)
											WHERE
												sqlu_name = ?
											AND
												sqld_name = ?
											AND
												domain_admin_id = ?
										',
										array($appSqlUser, $appDatabase, $_SESSION['user_id'])
									);
									if ($stmt->rowCount()) {
										$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
										if (check_db_connection($appDatabase, $appSqlUser, $row['sqlu_pass'])) {
											$appSqlPassword = $row['sqlu_pass'];
										} else {
											set_page_message(
												tr('Unable to connect to the selected database using the selected SQL user.'),
												'error'
											);
											$error = true;
										}
									} else {
										showBadRequestErrorPage();
										exit;
									}
								}
							} else {
								showBadRequestErrorPage();
								exit;
							}

							$softwarePrefix = $softwareData['software_prefix'];
						} else {
							$softwarePrefix = $appDatabase = $appSqlUser = $appSqlPassword = 'no_required';
						}

						if (!$error && isset($appSqlPassword)) {
							exec_query(
								'
									INSERT INTO web_software_inst (
										domain_id, alias_id, subdomain_id, subdomain_alias_id, software_id,
										software_master_id, software_name, software_version, software_language, path,
										software_prefix, db, database_user, database_tmp_pwd, install_username,
										install_password, install_email, software_status, software_depot
									) VALUES (
										?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
									)
								',
								array(
									$domainId, $aliasId, $subId, $subAliasId, $softwareId,
									$softwareData['software_master_id'], $softwareData['software_name'],
									$softwareData['software_version'], $softwareData['software_language'],
									rtrim($otherDir, '/'), $softwarePrefix, $appDatabase, $appSqlUser, $appSqlPassword,
									$appLoginName, $appPassword, $appEmail, 'toadd', $softwareData['software_depot']
								)
							);

							write_log(
								sprintf(
									'%s added new software instance: %s', decode_idna($_SESSION['user_logged']),
									$softwareData['software_name']
								),
								E_USER_NOTICE
							);

							send_request();

							set_page_message(tr('Software instance has been scheduled for installation'), 'success');
							redirectTo('software.php');
						}
					} else {
						showBadRequestErrorPage();
						exit;
					}
				} else {
					showBadRequestErrorPage();
					exit;
				}
			} else {
				showBadRequestErrorPage();
				exit;
			}
		} else {
			showBadRequestErrorPage();
			exit;
		}
	} else {
		$otherDir = '/htdocs';
		$appLoginName = '';
		$appLoginName = '';
		$appPassword = '';
		$appEmail = '';
	}

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Client / Webtools / Software / Software Installation'),
			'SOFTWARE_ID' => tohtml($softwareId),
			'TR_NAME' => tr('Software'),
			'TR_TYPE' => tr('Type'),
			'TR_DB' => tr('Database required'),
			'TR_SELECT_DOMAIN' => tr('Target domain'),
			'TR_CANCEL' => tr('Cancel'),
			'TR_INSTALL' => tr('Install'),
			'TR_PATH' => tr('Installation path'),
			'TR_CHOOSE_DIR' => tr('Choose dir'),
			'TR_SELECT_DB' => tr('Database'),
			'TR_SQL_USER' => tr('SQL user'),
			'TR_SQL_PWD' => tr('Password'),
			'TR_SOFTWARE_MENU' => tr('Software installation'),
			'TR_INSTALLATION' => tr('Installation details'),
			'TR_INSTALLATION_INFORMATION' => tr('Username and password for application login'),
			'TR_INSTALL_USER' => tr('Login username'),
			'TR_INSTALL_PWD' => tr('Login password'),
			'TR_INSTALL_EMAIL' => tr('Email address'),
			'VAL_OTHER_DIR' => tohtml($otherDir),
			'VAL_INSTALL_USERNAME' => tohtml($appLoginName),
			'VAL_INSTALL_EMAIL' => tohtml($appEmail)
		)
	);

	iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
		/** @var $e iMSCP_Events_Event */
		$translations = $e->getParam('translations');
		$translations['core']['close']= tr('Close');
		$translations['core']['ftp_directories']= tr('Ftp directories');
	});

	client_generatePage($tpl, $softwareId);
	generateNavigation($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}
