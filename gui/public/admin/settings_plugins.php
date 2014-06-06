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
 * @package     iMSCP_Core
 * @subpackage  Admin_Plugin
 * @copyright   2010-2014 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Upload plugin archive into the gui/plugins directory
 *
 * Supported archives: zip tar.gz and tar.bz2
 *
 * @param iMSCP_Plugin_Manager $pluginManager
 * @return bool TRUE on success, FALSE on failure
 */
function admin_pluginManagerUploadPlugin($pluginManager)
{
	$pluginDirectory = $pluginManager->getPluginDirectory();
	$tmpDirectory = GUI_ROOT_DIR . '/data/tmp';
	$ret = false;

	if (isset($_FILES['plugin_archive'])) {
		$beforeMove = function ($tmpDirectory) {
			$tmpFilePath = $_FILES['plugin_archive']['tmp_name'];

			if (!checkMimeType($tmpFilePath, array('application/x-gzip', 'application/x-bzip2', 'application/zip'))) {
				set_page_message(tr('Only tar.gz, tar.bz2 and zip archives are accepted.'), 'error');
				return false;
			}

			$pluginArchiveSize = $_FILES['plugin_archive']['size'];
			$maxUploadFileSize = utils_getMaxFileUpload();

			if ($pluginArchiveSize > $maxUploadFileSize) {
				set_page_message(
					tr(
						'Plugin archive exceeds the maximum upload size (%s). Max upload size is: %s.',
						bytesHuman($pluginArchiveSize),
						bytesHuman($maxUploadFileSize)
					),
					'error'
				);
				return false;
			}

			return $tmpDirectory . '/' . $_FILES['plugin_archive']['name'];
		};

		if (($archPath = utils_uploadFile('plugin_archive', array($beforeMove, $tmpDirectory))) !== false) {
			$zipArch = (substr($archPath, -3) === 'zip');

			try {
				if (!$zipArch) {
					$arch = new PharData($archPath);
					$name = $arch->getBasename();

					if (!isset($arch["$name/$name.php"])) {
						throw new iMSCP_Exception(tr('File %s is missing in plugin directory.', "$name.php"));
					}
				} else {
					$arch = new ZipArchive;

					if ($arch->open($archPath) === true) {
						if (($name = $arch->getNameIndex(0, ZIPARCHIVE::FL_UNCHANGED)) !== false) {
							$name = rtrim($name, '/');

							$index = $arch->locateName("$name.php", ZipArchive::FL_NODIR);

							if ($index !== false) {
								if (($stats = $arch->statIndex($index))) {
									if ($stats['name'] != "$name/$name.php") {
										throw new iMSCP_Exception(
											tr('File %s has not been found in plugin directory.', "$name.php")
										);
									}
								} else {
									throw new iMSCP_Exception(tr('Unable to get stats for file %s.', "$name.php"));
								}
							} else {
								throw new iMSCP_Exception(
									tr('File %s has not been found in plugin directory.', "$name.php")
								);
							}
						} else {
							throw new iMSCP_Exception(tr('Unable to find plugin root directory.'));
						}
					} else {
						throw new iMSCP_Exception(tr('Unable to open archive.'));
					}
				}

				if ($pluginManager->isPluginKnown($name) && $pluginManager->isPluginProtected($name)) {
					throw new iMSCP_Exception(tr('You are not allowed to update a protected plugin.'));
				}

				# Backup current plugin directory in temporary directory if exists
				if (is_dir("$pluginDirectory/$name")) {
					if (!@rename("$pluginDirectory/$name", "$tmpDirectory/$name")) {
						throw new iMSCP_Exception(
							tr('Unable to backup %s plugin directory.', "<strong>$name</strong>")
						);
					}
				}

				if (!$zipArch) {
					$arch->extractTo($pluginDirectory, null, true);
				} elseif (!$arch->extractTo($pluginDirectory)) {
					throw new iMSCP_Exception(tr('Unable to extract plugin archive.'));
				}

				$ret = true;
			} catch (Exception $e) {
				if ($e instanceof iMSCP_Exception) {
					set_page_message($e->getMessage(), 'error');
				} else {
					set_page_message(tr('Unable to extract plugin archive: %s', $e->getMessage()), 'error');
				}

				if (isset($name) && is_dir("$tmpDirectory/$name")) {
					// Restore previous plugin directory on error
					if (!@rename("$tmpDirectory/$name", "$pluginDirectory/$name")) {
						set_page_message(tr('Unable to restore %s plugin directory', "<strong>$name</strong>"), 'error');
					}
				}
			}

			@unlink($archPath); // Cleanup
			if (isset($name)) utils_removeDir("$tmpDirectory/$name"); // cleanup
		}
	} else {
		showBadRequestErrorPage();
	}

	return $ret;
}

/**
 * Translate plugin status
 *
 * @param string $rawPluginStatus Raw plugin status
 * @return string Translated plugin status
 */
function admin_pluginManagerTrStatus($rawPluginStatus)
{
	switch ($rawPluginStatus) {
		case 'uninstalled':
			return tr('Uninstalled');
		case 'toinstall':
			return tr('Installation in progress...');
		case 'toenable':
			return tr('Activation in progress...');
		case 'todisable':
			return tr('Deactivation in progress...');
		case 'tochange':
			return tr('Change in progress...');
		case 'toupdate':
			return tr('Update in progress...');
			break;
		case 'enabled':
			return tr('Activated');
		case 'disabled':
			return tr('Deactivated');
		case 'touninstall':
			return tr('Uninstallation in progress...');
		default:
			return tr('Unknown status');
	}
}

/**
 * Generates plugin list
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param iMSCP_Plugin_Manager $pluginManager
 * @return void
 */
function admin_pluginManagerGeneratePluginList($tpl, $pluginManager)
{
	$pluginList = $pluginManager->getPluginList('Action', false);

	if (empty($pluginList)) {
		$tpl->assign('PLUGINS_BLOCK', '');
		set_page_message(tr('Plugin list is empty.'), 'info');
	} else {
		natsort($pluginList);
		$cacheFile = PERSISTENT_PATH . '/protected_plugins.php';

		foreach ($pluginList as $pluginName) {
			$pluginInfo = $pluginManager->getPluginInfo($pluginName);
			$pluginStatus = $pluginManager->getPluginStatus($pluginName);

			if (is_array($pluginInfo['author'])) {
				$pluginInfo['author'] = implode(' ' . tr('and') . ' ', $pluginInfo['author']);
			}

			$tpl->assign(
				array(
					'PLUGIN_NAME' => tohtml($pluginName),
					'PLUGIN_DESCRIPTION' => tohtml($pluginInfo['desc']),
					'PLUGIN_STATUS' => ($pluginManager->hasPluginError($pluginName))
							? tohtml(tr('Unknown Error')) : tohtml(admin_pluginManagerTrStatus($pluginStatus)),
					'PLUGIN_VERSION' => tohtml($pluginInfo['__nversion__']),
					'PLUGIN_AUTHOR' => tohtml($pluginInfo['author']),
					'PLUGIN_MAILTO' => tohtml($pluginInfo['email']),
					'PLUGIN_SITE' => tohtml($pluginInfo['url'])
				)
			);

			if ($pluginManager->hasPluginError($pluginName)) {
				$tpl->assign(
					'PLUGIN_STATUS_DETAILS',
					tr('An unexpected error occurred: %s', '<br /><br />' . $pluginManager->getPluginError($pluginName))
				);
				$tpl->parse('PLUGIN_STATUS_DETAILS_BLOCK', 'plugin_status_details_block');
				$tpl->assign(
					array(
						'PLUGIN_DEACTIVATE_LINK' => '',
						'PLUGIN_ACTIVATE_LINK' => '',
						'PLUGIN_PROTECTED_LINK' => ''
					)
				);
			} else {
				$tpl->assign('PLUGIN_STATUS_DETAILS_BLOCK', '');

				if ($pluginManager->isPluginProtected($pluginName)) { // Protected plugin
					$tpl->assign(
						array(
							'PLUGIN_ACTIVATE_LINK' => '',
							'PLUGIN_DEACTIVATE_LINK' => '',
							'TR_UNPROTECT_TOOLTIP' => tr(
								'To unprotect this plugin, you must edit the %s file', $cacheFile
							)
						)
					);

					$tpl->parse('PLUGIN_PROTECTED_LINK', 'plugin_protected_link');
				} elseif ($pluginManager->isPluginUninstalled($pluginName)) { // Uninstalled plugin
					$tpl->assign(
						array(
							'PLUGIN_DEACTIVATE_LINK' => '',
							'ACTIVATE_ACTION' => 'install',
							'TR_ACTIVATE_TOOLTIP' => tr('Install this plugin'),
							'UNINSTALL_ACTION' => 'delete',
							'TR_UNINSTALL_TOOLTIP' => tr('Delete this plugin'),
							'PLUGIN_PROTECTED_LINK' => ''
						)
					);

					$tpl->parse('PLUGIN_ACTIVATE_LINK', 'plugin_activate_link');
				} elseif ($pluginManager->isPluginDisabled($pluginName)) { // Disabled plugin
					$tpl->assign(
						array(
							'PLUGIN_DEACTIVATE_LINK' => '',
							'ACTIVATE_ACTION' => 'enable',
							'TR_ACTIVATE_TOOLTIP' => tr('Activate this plugin'),
							'UNINSTALL_ACTION' => $pluginManager->isPluginUninstallable($pluginName)
									? 'uninstall' : 'delete',
							'TR_UNINSTALL_TOOLTIP' => $pluginManager->isPluginUninstallable($pluginName)
									? tr('Uninstall this plugin') : tr('Delete this plugin'),
							'PLUGIN_PROTECTED_LINK' => ''
						)
					);

					$tpl->parse('PLUGIN_ACTIVATE_LINK', 'plugin_activate_link');
				} elseif ($pluginManager->isPluginEnabled($pluginName)) { // Enabled plugin
					$tpl->assign(
						array(
							'PLUGIN_ACTIVATE_LINK' => '',
							'PLUGIN_PROTECTED_LINK' => ''
						)
					);

					$tpl->parse('PLUGIN_DEACTIVATE_LINK', 'plugin_deactivate_link');
				} else { // Plugin with unknown status - TODO add specific action for such case
					$tpl->assign(
						array(
							'PLUGIN_DEACTIVATE_LINK' => '',
							'PLUGIN_ACTIVATE_LINK' => '',
							'PLUGIN_PROTECTED_LINK' => ''
						)
					);
				}
			}

			$tpl->parse('PLUGIN_BLOCK', '.plugin_block');
		}
	}
}

/**
 * Check plugin action
 *
 * @param iMSCP_Plugin_Manager $pluginManager
 * @param string $pluginName PLugin name
 * @param string $action Action
 * @return bool
 */
function admin_pluginManagerCheckAction($pluginManager, $pluginName, $action)
{
	if ($pluginManager->isPluginProtected($pluginName)) {
		set_page_message(tr('Plugin %s is protected.', "<strong>$pluginName</strong>"), 'warning');
		return false;
	}

	$ret = true;

	$pluginStatus = $pluginManager->getPluginStatus($pluginName);

	switch ($action) {
		case 'install':
			if ($pluginStatus != 'toinstall') {
				if ($pluginManager->isPluginInstallable($pluginName)) {
					if ($pluginManager->isPluginInstalled($pluginName)) {
						set_page_message(
							tr('Plugin %s is already installed.', "<strong>$pluginName</strong>"), 'warning'
						);
						$ret = false;
					}
				} else {
					set_page_message(tr('Plugin %s is not installable.', "<strong>$pluginName</strong>"), 'warning');
					$ret = false;
				}
			}
			break;
		case 'update':
			if($pluginStatus != 'toupdate') {
				if (!$pluginManager->isPluginEnabled($pluginName)) {
					set_page_message(tr('Plugin %s cannot be updated.', "<strong>$pluginName</strong>"), 'warning');
					$ret = false;
				}
			}
			break;
		case 'change':
			if($pluginStatus != 'tochange') {
				if (!$pluginManager->isPluginEnabled($pluginName)) {
					set_page_message(tr('Plugin %s cannot be changed.', "<strong>$pluginName</strong>"), 'warning');
					$ret = false;
				}
			}
			break;
		case 'uninstall':
			if ($pluginStatus != 'touninstall') {
				if ($pluginManager->isPluginUninstallable($pluginName)) {
					if ($pluginManager->isPluginUninstalled($pluginName)) {
						set_page_message(
							tr('Plugin %s is already uninstalled.', "<strong>$pluginName</strong>"), 'warning'
						);
						$ret = false;
					}
				} else {
					set_page_message(tr('Plugin %s is not uninstallable.', "<strong>$pluginName</strong>"), 'warning');
					$ret = false;
				}
			}
			break;
		case 'enable':
			if ($pluginStatus != 'toenable') {
				if ($pluginManager->isPluginEnabled($pluginName)) {
					set_page_message(tr('Plugin %s is already activated.', "<strong>$pluginName</strong>"), 'warning');
					$ret = false;
				} elseif (!$pluginManager->isPluginDisabled($pluginName)) {
					set_page_message(tr('Plugin %s cannot be enabled.', "<strong>$pluginName</strong>"), 'warning');
					$ret = false;
				}
			}
			break;
		case 'disable':
			if ($pluginStatus != 'todisable') {
				if ($pluginManager->isPluginDisabled($pluginName)) {
					set_page_message(
						tr('Plugin %s is already deactivated.', "<strong>$pluginName</strong>"), 'warning'
					);
					$ret = false;
				} elseif (!$pluginManager->isPluginEnabled($pluginName)) {
					set_page_message(tr('Plugin %s cannot be disabled.', "<strong>$pluginName</strong>"), 'warning');
					$ret = false;
				}
			}
			break;
		case 'delete':
			if ($pluginStatus != 'todelete') {

				if($pluginManager->isPluginInstallable($pluginName) && $pluginManager->isPluginInstalled($pluginName)) {
					set_page_message(
						tr('Plugin %s cannot be deleted. You must uninstall it first.', "<strong>$pluginName</strong>"),
						'warning'
					);
					$ret = false;
				} else {
					if($pluginManager->isPluginEnabled($pluginName)) {
						set_page_message(
							tr('Plugin %s cannot be deleted. You must deactivate it first.', "<strong>$pluginName</strong>"),
							'warning'
						);
						$ret = false;
					}
				}
			}
			break;
		case 'protect':
			break;
		default:
			showBadRequestErrorPage();
	}

	return $ret;
}

/**
 * Do the given action for the given plugin
 *
 * @param iMSCP_Plugin_Manager $pluginManager
 * @param string $pluginName Plugin name
 * @param string $action Action (activate|deactivate|delete|protect)
 * @return void
 */
function admin_pluginManagerDoAction($pluginManager, $pluginName, $action)
{
	if ($pluginManager->isPluginKnown($pluginName)) {
		if (admin_pluginManagerCheckAction($pluginManager, $pluginName, $action)) {
			$ret = $pluginManager->{"plugin{$action}"}($pluginName);

			if ($ret == iMSCP_Plugin_Manager::ACTION_FAILURE || $ret == iMSCP_Plugin_Manager::ACTION_STOPPED) {
				$msg = ($ret == iMSCP_Plugin_Manager::ACTION_FAILURE)
					? tr('Action has failed.') : tr('Action has been stopped.');

				switch ($action) {
					case 'install':
						$msg = tr('Unable to install the %s plugin: %s', "<strong>$pluginName</strong>", $msg);
						break;
					case 'enable':
						$msg = tr('Unable to activate the %s plugin: %s', "<strong>$pluginName</strong>", $msg);
						break;
					case 'disable':
						$msg = tr('Unable to deactivate the %s plugin: %s', "<strong>$pluginName</strong>", $msg);
						break;
					case 'change':
						$msg = tr('Unable to change the %s plugin: %s', "<strong>$pluginName</strong>", $msg);
						break;
					case 'update':
						$msg = tr('Unable to update the %s plugin: %s', "<strong>$pluginName</strong>", $msg);
						break;
					case 'uninstall':
						$msg = tr('Unable to uninstall the %s plugin: %s', "<strong>$pluginName</strong>", $msg);
						break;
					case 'delete':
						$msg = tr('Unable to delete the %s plugin: %s', "<strong>$pluginName</strong>", $msg);
						break;
					case 'protect':
						$msg = tr('Unable to protect the %s plugin: %s', "<strong>$pluginName</strong>", $msg);
						break;
					default:
						$msg = tr('Unable to protect the %s plugin: %s', "<strong>$pluginName</strong>", $msg);
				}

				set_page_message($msg, 'error');
			} else {
				if ($pluginManager->hasPluginBackend($pluginName)) {
					switch ($action) {
						case 'install':
							$msg = tr('Plugin %s scheduled for installation.', "<strong>$pluginName</strong>");
							break;
						case 'enable':
							$msg = tr('Plugin %s scheduled for activation.', "<strong>$pluginName</strong>");
							break;
						case 'disable':
							$msg = tr('Plugin %s scheduled for deactivation.', "<strong>$pluginName</strong>");
							break;
						case 'change':
							$msg = tr('Plugin %s scheduled for change.', "<strong>$pluginName</strong>");
							break;
						case 'update':
							$msg = tr('Plugin %s scheduled for update.', "<strong>$pluginName</strong>");
							break;
						case 'uninstall':
							$msg = tr('Plugin %s scheduled for uninstallation.', "<strong>$pluginName</strong>");
							break;
						default:
							$msg = tr('Plugin %s deleted.', "<strong>$pluginName</strong>");
					}

					set_page_message($msg, 'success');
				} else {
					switch ($action) {
						case 'install':
							$msg = tr('Plugin %s installed.', "<strong>$pluginName</strong>");
							break;
						case 'enable':
							$msg = tr('Plugin %s activated.', "<strong>$pluginName</strong>");
							break;
						case 'disable':
							$msg = tr('Plugin %s deactivated.', "<strong>$pluginName</strong>");
							break;
						case 'change':
							$msg = tr('Plugin %s changed.', "<strong>$pluginName</strong>");
							break;
						case 'update':
							$msg = tr('Plugin %s updated.', "<strong>$pluginName</strong>");
							break;
						case 'uninstall':
							$msg = tr('Plugin %s uninstalled.', "<strong>$pluginName</strong>");
							break;
						case 'protect':
							$msg = tr('Plugin %s protected.', "<strong>$pluginName</strong>");
							break;
						default:
							$msg = tr('Plugin %s deleted.', "<strong>$pluginName</strong>");
					}

					set_page_message($msg, 'success');
				}
			}
		}
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Do bulk action (activate|deactivate|protect)
 *
 * @param iMSCP_Plugin_Manager $pluginManager
 * @return void
 */
function admin_pluginManagerDoBulkAction($pluginManager)
{
	$action = clean_input($_POST['bulk_actions']);

	if (!in_array($action, array('install', 'enable', 'disable', 'uninstall', 'delete', 'protect'))) {
		showBadRequestErrorPage();
	} elseif (isset($_POST['checked']) && is_array($_POST['checked']) && !empty($_POST['checked'])) {
		foreach ($_POST['checked'] as $pluginName) {
			admin_pluginManagerDoAction($pluginManager, clean_input($pluginName), $action);
		}
	} else {
		set_page_message(tr('You must select a plugin.'), 'error');
	}
}

/**
 * Update plugin list
 *
 * @param iMSCP_Plugin_Manager $pluginManager
 * @return void
 */
function admin_pluginManagerUpdatePluginList($pluginManager)
{
	$eventManager = $pluginManager->getEventManager();

	/** @var iMSCP_Events_Listener_ResponseCollection $responses */
	$responses = $eventManager->dispatch(
		iMSCP_Events::onBeforeUpdatePluginList, array('pluginManager' => $pluginManager)
	);

	if (!$responses->isStopped()) {
		$updateInfo = $pluginManager->updatePluginList();

		$eventManager->dispatch(iMSCP_Events::onAfterUpdatePluginList, array('pluginManager' => $pluginManager));

		set_page_message(
			tr(
				'Plugins list has been updated: %s new plugin(s) found, %s plugin(s) updated, %s plugin(s) changed, and %s plugin(s) deleted.',
				"<strong>{$updateInfo['new']}</strong>",
				"<strong>{$updateInfo['updated']}</strong>",
				"<strong>{$updateInfo['changed']}</strong>",
				"<strong>{$updateInfo['deleted']}</strong>"
			),
			'success'
		);
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

/** @var iMSCP_Plugin_Manager $pluginManager */
$pluginManager = iMSCP_Registry::get('pluginManager');

// Dispatches the request
if (!empty($_POST) || !empty($_GET) || !empty($_FILES)) {
	if (isset($_GET['update_plugin_list'])) {
		admin_pluginManagerUpdatePluginList($pluginManager);
	} elseif (isset($_GET['install'])) {
		admin_pluginManagerDoAction($pluginManager, clean_input($_GET['install']), 'install');
	} elseif (isset($_GET['enable'])) {
		admin_pluginManagerDoAction($pluginManager, clean_input($_GET['enable']), 'enable');
	} elseif (isset($_GET['disable'])) {
		admin_pluginManagerDoAction($pluginManager, clean_input($_GET['disable']), 'disable');
	} elseif (isset($_GET['uninstall'])) {
		admin_pluginManagerDoAction($pluginManager, clean_input($_GET['uninstall']), 'uninstall');
	} elseif (isset($_GET['delete'])) {
		admin_pluginManagerDoAction($pluginManager, clean_input($_GET['delete']), 'delete');
	} elseif (isset($_GET['protect'])) {
		admin_pluginManagerDoAction($pluginManager, clean_input($_GET['protect']), 'protect');
	} elseif (isset($_GET['retry'])) {
		$pluginName = clean_input($_GET['retry']);

		if ($pluginManager->isPluginKnown($pluginName)) {
			switch ($pluginManager->getPluginStatus($pluginName)) {
				case 'toinstall':
					$action = 'install';
					break;
				case 'toenable':
					$action = 'enable';
					break;
				case 'todisable':
					$action = 'disable';
					break;
				case 'tochange':
					$action = 'change';
					break;
				case 'toupdate':
					$action = 'update';
					break;
				case 'touninstall':
					$action = 'uninstall';
					break;
				case 'todelete':
					$action = 'delete';
					break;
				default:
					// Handle case where the error field is not NULL and status field is in unexpected state
					$pluginManager->setPluginStatus($pluginName, 'todisable');
					$action = 'disable';
			}

			admin_pluginManagerDoAction($pluginManager, $pluginName, $action);
		} else {
			showBadRequestErrorPage();
		}
	} elseif (isset($_POST['bulk_actions'])) {
		admin_pluginManagerDoBulkAction($pluginManager);
	} elseif (!empty($_FILES)) {
		if (admin_pluginManagerUploadPlugin($pluginManager)) {
			set_page_message(tr('Plugin has been uploaded.'), 'success');
			redirectTo('settings_plugins.php?update_plugin_list=all');
		}
	}

	redirectTo('settings_plugins.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/settings_plugins.tpl',
		'page_message' => 'layout',
		'plugins_block' => 'page',
		'plugin_block' => 'plugins_block',
		'plugin_status_details_block' => 'plugin_block',
		'plugin_activate_link' => 'plugin_block',
		'plugin_deactivate_link' => 'plugin_block',
		'plugin_protected_link' => 'plugin_block'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Settings / Plugin Management'),
		'ISP_LOGO' => layout_getUserLogo(),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'TR_BULK_ACTIONS' => tr('Bulk Actions'),
		'TR_PLUGIN' => tr('Plugin'),
		'TR_DESCRIPTION' => tr('Description'),
		'TR_STATUS' => tr('Status'),
		'TR_ACTIONS' => tr('Actions'),
		'TR_INSTALL' => tr('Install'),
		'TR_ACTIVATE' => tr('Activate'),
		'TR_DEACTIVATE_TOOLTIP' => tr('Deactivate this plugin'),
		'TR_DEACTIVATE' => tr('Deactivate'),
		'TR_UNINSTALL' => tr('Uninstall'),
		'TR_PROTECT' => tojs(tr('Protect', true)),
		'TR_DELETE' => tr('Delete'),
		'TR_PROTECT_TOOLTIP' => tr('Protect this plugin'),
		'TR_PLUGIN_CONFIRMATION_TITLE' => tojs(tr('Confirmation for plugin protection', true)),
		'TR_PROTECT_CONFIRMATION' => tr("If you protect a plugin, you'll no longer be able to deactivate it from the plugin management interface."),
		'TR_CANCEL' => tojs(tr('Cancel', true)),
		'TR_VERSION' => tr('Version'),
		'TR_BY' => tr('By'),
		'TR_VISIT_PLUGIN_SITE' => tr('Visit plugin site'),
		'TR_UPDATE_PLUGIN_LIST' => tr('Update Plugins'),
		'TR_APPLY' => tr('Apply'),
		'TR_PLUGIN_UPLOAD' => tr('Plugins Upload'),
		'TR_UPLOAD' => tr('Upload'),
		'TR_PLUGIN_ARCHIVE' => tr('Plugin archive'),
		'TR_PLUGIN_ARCHIVE_TOOLTIP' => tr('Only tar.gz, tar.bz2 and zip archives are accepted.'),
		'TR_PLUGIN_HINT' => tr('Plugins hook into i-MSCP to extend its functionality with custom features. Plugins are developed independently from the core i-MSCP application by thousands of developers all over the world. You can find new plugins to install by browsing the %s.', true, '<u><a href="http://i-mscp.net/filebase/index.php/Filebase/" target="_blank">' . tr('i-MSCP plugin store') . '</a></u>'),
		'TR_CLICK_FOR_MORE_DETAILS' => tr('Click here for more details'),
		'TR_ERROR_DETAILS' => tojs(tr('Error details', true)),
		'TR_FORCE_RETRY' => tojs(tr('Force retry', true)),
		'TR_CLOSE' => tojs(tr('Close', true))
	)
);

generateNavigation($tpl);
admin_pluginManagerGeneratePluginList($tpl, $pluginManager);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
