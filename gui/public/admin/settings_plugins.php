<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * Functions
 */

namespace admin;

use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Registry as Registry;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_Events as Events;
use iMSCP_Events_Listener_ResponseCollection as EventCollection;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Utility_OpcodeCache as OpcodeCacheUtils;
use PharData;
use ZipArchive;
use iMSCP_Exception as iMSCPException;
use Exception;

/**
 * Upload plugin archive into the gui/plugins directory
 *
 * Supported archives: zip tar.gz and tar.bz2
 *
 * @param PluginManager $pluginManager
 * @return bool TRUE on success, FALSE on failure
 */
function uploadPlugin($pluginManager)
{
	$pluginDirectory = $pluginManager->pluginGetDirectory();
	$tmpDirectory = GUI_ROOT_DIR . '/data/tmp';
	$ret = false;

	if(isset($_FILES['plugin_archive'])) {
		$beforeMove = function ($tmpDirectory) {
			$tmpFilePath = $_FILES['plugin_archive']['tmp_name'];

			if(!checkMimeType($tmpFilePath, array('application/x-gzip', 'application/x-bzip2', 'application/zip'))) {
				set_page_message(tr('Only tar.gz, tar.bz2 and zip archives are accepted.'), 'error');
				return false;
			}

			$pluginArchiveSize = $_FILES['plugin_archive']['size'];
			$maxUploadFileSize = utils_getMaxFileUpload();

			if($pluginArchiveSize > $maxUploadFileSize) {
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

		# Upload plugin archive into gui/data/tmp directory ( eg. gui/data/tmp/PluginName.zip )
		$tmpArchPath = utils_uploadFile('plugin_archive', array($beforeMove, $tmpDirectory));

		if($tmpArchPath !== false) {
			$zipArch = (strtolower(pathinfo($tmpArchPath, PATHINFO_EXTENSION)) == 'zip');

			try {
				if(!$zipArch) {
					$arch = new PharData($tmpArchPath);
					$pluginName = $arch->getBasename();

					if(!isset($arch["$pluginName/$pluginName.php"])) {
						throw new iMSCPException(tr('File %s is missing in plugin archive.', "$pluginName.php"));
					}

					$arch->extractTo($tmpDirectory, "$pluginName/info.php", true);
					$pluginManager->pluginCheckCompat($pluginName, include("$tmpDirectory/$pluginName/info.php"));
				} else {
					$arch = new ZipArchive;

					if($arch->open($tmpArchPath) === true) {
						if(($pluginName = $arch->getNameIndex(0, ZIPARCHIVE::FL_UNCHANGED)) !== false) {
							$pluginName = rtrim($pluginName, '/');
							$index = $arch->locateName("$pluginName.php", ZipArchive::FL_NODIR);

							if($index !== false) {
								if(($stats = $arch->statIndex($index))) {
									if($stats['name'] != "$pluginName/$pluginName.php") {
										throw new iMSCPException(
											tr('File %s is missing in plugin archive.', "$pluginName.php")
										);
									}
								} else {
									throw new iMSCPException(tr('Unable to get stats for file %s.', "$pluginName.php"));
								}
							} else {
								throw new iMSCPException(tr('File %s is missing in plugin archive.', "$pluginName.php"));
							}
						} else {
							throw new iMSCPException(tr('Unable to find plugin root directory withing archive.'));
						}

						if($arch->extractTo($tmpDirectory, "$pluginName/info.php")) {
							$pluginManager->pluginCheckCompat($pluginName, include("$tmpDirectory/$pluginName/info.php"));
						} else {
							throw new iMSCPException(tr('Unable to extract info.php file'));
						}
					} else {
						throw new iMSCPException(tr('Unable to open plugin archive.'));
					}
				}

				if($pluginManager->pluginIsKnown($pluginName) && $pluginManager->pluginIsProtected($pluginName)) {
					throw new iMSCPException(tr('You are not allowed to update a protected plugin.'));
				}

				# Backup current plugin directory in temporary directory if exists
				if(is_dir("$pluginDirectory/$pluginName")) {
					if(!@rename("$pluginDirectory/$pluginName", "$tmpDirectory/$pluginName" . '-old')) {
						throw new iMSCPException(tr('Unable to backup %s plugin directory.', $pluginName));
					}
				}

				if(!$zipArch) {
					$arch->extractTo($pluginDirectory, null, true);
				} elseif(!$arch->extractTo($pluginDirectory)) {
					throw new iMSCPException(tr('Unable to extract plugin archive.'));
				}

				$ret = true;
			} catch(Exception $e) {
				if($e instanceof iMSCPException) {
					set_page_message($e->getMessage(), 'error');
				} else {
					set_page_message(tr('Unable to extract plugin archive: %s', $e->getMessage()), 'error');
				}

				if(!empty($pluginName) && is_dir("$tmpDirectory/$pluginName" . '-old')) {
					// Try to restore previous plugin directory on error
					if(!@rename("$tmpDirectory/$pluginName" . '-old', "$pluginDirectory/$pluginName")) {
						set_page_message(tr('Unable to restore %s plugin directory', $pluginName), 'error');
					}
				}
			}

			// Cleanup
			@unlink($tmpArchPath);
			if(!empty($pluginName)) {
				utils_removeDir("$tmpDirectory/$pluginName");
				utils_removeDir("$tmpDirectory/$pluginName" . '-old');
			}
		} else {
			redirectTo('settings_plugins.php');
		}
	} else {
		showBadRequestErrorPage();
	}

	return $ret;
}

/**
 * Translate the given plugin status
 *
 * @param string $pluginStatus Plugin status to translate
 * @return string Translated plugin status
 */
function translateStatus($pluginStatus)
{
	switch($pluginStatus) {
		case 'uninstalled':
			return tr('Uninstalled');
		case 'toinstall':
			return tr('Installation in progress...');
		case 'touninstall':
			return tr('Uninstallation in progress...');
		case 'toupdate':
			return tr('Update in progress...');
		case 'tochange':
			return tr('Reconfiguration in progress...');
		case 'toenable':
			return tr('Activation in progress...');
		case 'todisable':
			return tr('Deactivation in progress...');
		case 'enabled':
			return tr('Activated');
		case 'disabled':
			return tr('Deactivated');
		default:
			return tr('Unknown status');
	}
}

/**
 * Generates plugin list
 *
 * @param TemplateEngine $tpl Template engine instance
 * @param PluginManager $pluginManager
 * @return void
 */
function generatePage($tpl, $pluginManager)
{
	$pluginList = $pluginManager->pluginGetList('Action', false);

	if(empty($pluginList)) {
		$tpl->assign('PLUGINS_BLOCK', '');
		set_page_message(tr('Plugin list is empty.'), 'static_info');
	} else {
		natsort($pluginList);
		$cacheFile = PERSISTENT_PATH . '/protected_plugins.php';

		foreach($pluginList as $pluginName) {
			$pluginInfo = $pluginManager->pluginGetInfo($pluginName);
			$pluginStatus = $pluginManager->pluginGetStatus($pluginName);

			if(is_array($pluginInfo['author'])) {
				if(count($pluginInfo['author']) == 2) {
					$pluginInfo['author'] = implode(' ' . tr('and') . ' ', $pluginInfo['author']);
				} else {
					$lastEntry = array_pop($pluginInfo['author']);
					$pluginInfo['author'] = implode(', ', $pluginInfo['author']);
					$pluginInfo['author'] .= ' ' . tr('and') . ' ' . $lastEntry;
				}
			}

			$tpl->assign(array(
				'PLUGIN_NAME' => tohtml($pluginName),
				'PLUGIN_DESCRIPTION' => tr($pluginInfo['desc']),
				'PLUGIN_STATUS' => ($pluginManager->pluginHasError($pluginName))
					? tr('Unexpected error') : translateStatus($pluginStatus),
				'PLUGIN_VERSION' => (isset($pluginInfo['__nversion__']))
					? tohtml($pluginInfo['__nversion__']) : tr('Unknown'),
				'PLUGIN_AUTHOR' => tohtml($pluginInfo['author']),
				'PLUGIN_MAILTO' => tohtml($pluginInfo['email']),
				'PLUGIN_SITE' => tohtml($pluginInfo['url'])
			));

			if($pluginManager->pluginHasError($pluginName)) {
				$tpl->assign(
					'PLUGIN_STATUS_DETAILS',
					tr('An unexpected error occurred: %s', '<br><br>' . $pluginManager->pluginGetError($pluginName))
				);
				$tpl->parse('PLUGIN_STATUS_DETAILS_BLOCK', 'plugin_status_details_block');
				$tpl->assign(array(
					'PLUGIN_DEACTIVATE_LINK' => '',
					'PLUGIN_ACTIVATE_LINK' => '',
					'PLUGIN_PROTECTED_LINK' => ''
				));
			} else {
				$tpl->assign('PLUGIN_STATUS_DETAILS_BLOCK', '');

				if($pluginManager->pluginIsProtected($pluginName)) { // Protected plugin
					$tpl->assign(array(
						'PLUGIN_ACTIVATE_LINK' => '',
						'PLUGIN_DEACTIVATE_LINK' => '',
						'TR_UNPROTECT_TOOLTIP' => tr('To unprotect this plugin, you must edit the %s file', $cacheFile)
					));

					$tpl->parse('PLUGIN_PROTECTED_LINK', 'plugin_protected_link');
				} elseif($pluginManager->pluginIsUninstalled($pluginName)) { // Uninstalled plugin
					$tpl->assign(array(
						'PLUGIN_DEACTIVATE_LINK' => '',
						'ACTIVATE_ACTION' => 'install',
						'TR_ACTIVATE_TOOLTIP' => tr('Install this plugin'),
						'UNINSTALL_ACTION' => 'delete',
						'TR_UNINSTALL_TOOLTIP' => tr('Delete this plugin'),
						'PLUGIN_PROTECTED_LINK' => ''
					));

					$tpl->parse('PLUGIN_ACTIVATE_LINK', 'plugin_activate_link');
				} elseif($pluginManager->pluginIsDisabled($pluginName)) { // Disabled plugin
					$tpl->assign(array(
						'PLUGIN_DEACTIVATE_LINK' => '',
						'ACTIVATE_ACTION' => 'enable',
						'TR_ACTIVATE_TOOLTIP' => tr('Activate this plugin'),
						'UNINSTALL_ACTION' => $pluginManager->pluginIsUninstallable($pluginName)
							? 'uninstall' : 'delete',
						'TR_UNINSTALL_TOOLTIP' => $pluginManager->pluginIsUninstallable($pluginName)
							? tr('Uninstall this plugin') : tr('Delete this plugin'),
						'PLUGIN_PROTECTED_LINK' => ''
					));

					$tpl->parse('PLUGIN_ACTIVATE_LINK', 'plugin_activate_link');
				} elseif($pluginManager->pluginIsEnabled($pluginName)) { // Enabled plugin
					$tpl->assign(array(
						'PLUGIN_ACTIVATE_LINK' => '',
						'PLUGIN_PROTECTED_LINK' => ''
					));

					$tpl->parse('PLUGIN_DEACTIVATE_LINK', 'plugin_deactivate_link');
				} else { // Plugin with unknown status
					$tpl->assign(array(
						'PLUGIN_DEACTIVATE_LINK' => '',
						'PLUGIN_ACTIVATE_LINK' => '',
						'PLUGIN_PROTECTED_LINK' => ''
					));
				}
			}

			$tpl->parse('PLUGIN_BLOCK', '.plugin_block');
		}
	}
}

/**
 * Check plugin action
 *
 * @param PluginManager $pluginManager
 * @param string $pluginName Name of plugin on which the action is being performed
 * @param string $action Action Plugin action name ( install|uninstall|update|change|enable|disable|delete|protect )
 * @return bool TRUE if the plugin action is allowed, FALSE otherwise
 */
function checkAction($pluginManager, $pluginName, $action)
{
	if($pluginManager->pluginIsProtected($pluginName)) {
		set_page_message(tr('Plugin %s is protected.', $pluginName), 'warning');
		return false;
	}

	$ret = true;

	$pluginStatus = $pluginManager->pluginGetStatus($pluginName);

	switch($action) {
		case 'install':
			if(
				!in_array($pluginStatus, array('toinstall', 'uninstalled')) ||
				!$pluginManager->pluginIsInstallable($pluginName)
			) {
				set_page_message(tr('Plugin %s cannot be installed.', $pluginName), 'warning');
				$ret = false;
			}

			break;
		case 'uninstall':
			if(
				!in_array($pluginStatus, array('touninstall', 'disabled')) ||
				!$pluginManager->pluginIsUninstallable($pluginName)
			) {
				set_page_message(tr('Plugin %s cannot be uninstalled.', $pluginName), 'warning');
				$ret = false;
			}

			break;
		case 'update':
			if(!in_array($pluginStatus, array('toupdate'))) {
				set_page_message(tr('Plugin %s cannot be updated.', $pluginName), 'warning');
				$ret = false;
			}

			break;
		case 'change':
			if(!in_array($pluginStatus, array('tochange'))) {
				set_page_message(tr('Plugin %s cannot be reconfigured.', $pluginName), 'warning');
				$ret = false;
			}

			break;
		case 'enable':
			if(!in_array($pluginStatus, array('toenable', 'disabled'))) {
				set_page_message(tr('Plugin %s cannot be activated.', $pluginName), 'warning');
				$ret = false;
			}

			break;
		case 'disable':
			if(!in_array($pluginStatus, array('todisable', 'enabled'))) {
				set_page_message(tr('Plugin %s cannot be deactivated.', $pluginName), 'warning');
				$ret = false;
			}

			break;
		case 'delete':
			if(!in_array($pluginStatus, array('todelete'))) {
				if(
					($pluginManager->pluginIsUninstallable($pluginName) && $pluginStatus != 'uninstalled') &&
					$pluginStatus != 'disabled'
				) {
					set_page_message(tr('Plugin %s cannot be deleted.', $pluginName), 'warning');
					$ret = false;
				}
			}

			break;
		case 'protect':
			if(!in_array($pluginStatus, array('enabled'))) {
				set_page_message(tr('Plugin %s cannot be protected.', $pluginName), 'warning');
				$ret = false;
			}

			break;
		default:
			showBadRequestErrorPage();
	}

	return $ret;
}

/**
 * Do the given action for the given plugin
 *
 * @param PluginManager $pluginManager
 * @param string $pluginName Plugin name
 * @param string $action Action ( install|uninstall|update|change|enable|disable|delete|protect )
 * @return void
 */
function doAction($pluginManager, $pluginName, $action)
{
	if($pluginManager->pluginIsKnown($pluginName)) {
		try {
			if(in_array($action, array('install', 'update', 'enable'))) {
				$pluginManager->pluginCheckCompat($pluginName, $pluginManager->pluginLoad($pluginName)->getInfo());
			}

			if(checkAction($pluginManager, $pluginName, $action)) {
				$ret = call_user_func(array($pluginManager, 'plugin' . ucfirst($action)), $pluginName);

				if($ret !== false) {
					if($ret == PluginManager::ACTION_FAILURE || $ret == PluginManager::ACTION_STOPPED) {
						$msg = ($ret == PluginManager::ACTION_FAILURE)
							? tr('Action has failed.') : tr('Action has been stopped.');

						switch ($action) {
							case 'install':
								$msg = tr('Unable to install the %s plugin: %s', $pluginName, $msg);
								break;
							case 'uninstall':
								$msg = tr('Unable to uninstall the %s plugin: %s', $pluginName, $msg);
								break;
							case 'update':
								$msg = tr('Unable to update the %s plugin: %s', $pluginName, $msg);
								break;
							case 'change':
								$msg = tr('Unable to change the %s plugin: %s', $pluginName, $msg);
								break;
							case 'enable':
								$msg = tr('Unable to enable the %s plugin: %s', $pluginName, $msg);
								break;
							case 'disable':
								$msg = tr('Unable to disable the %s plugin: %s', $pluginName, $msg);
								break;
							default:
								$msg = tr('Unable to protect the %s plugin: %s', $pluginName, $msg);
						}

						set_page_message($msg, 'error');
					} else {
						$msg = '';

						if($action != 'delete' && $pluginManager->pluginHasBackend($pluginName)) {
							switch($action) {
								case 'install':
									$msg = tr('Plugin %s scheduled for installation.', $pluginName);
									break;
								case 'uninstall':
									$msg = tr('Plugin %s scheduled for uninstallation.', $pluginName);
									break;
								case 'update':
									$msg = tr('Plugin %s scheduled for update.', $pluginName);
									break;
								case 'change':
									$msg = tr('Plugin %s scheduled for change.', $pluginName);
									break;
								case 'enable':
									$msg = tr('Plugin %s scheduled for activation.', $pluginName);
									break;
								case 'disable':
									$msg = tr('Plugin %s scheduled for deactivation.', $pluginName);
									break;
								case 'protect':
									$msg = tr('Plugin %s protected.', $pluginName);
							}

							set_page_message($msg, 'success');
						} else {
							switch($action) {
								case 'install':
									$msg = tr('Plugin %s installed.', $pluginName);
									break;
								case 'uninstall':
									$msg = tr('Plugin %s uninstalled.', $pluginName);
									break;
								case 'update':
									$msg = tr('Plugin %s updated.', $pluginName);
									break;
								case 'change':
									$msg = tr('Plugin %s reconfigured.', $pluginName);
									break;
								case 'enable':
									$msg = tr('Plugin %s activated.', $pluginName);
									break;
								case 'disable':
									$msg = tr('Plugin %s deactivated.', $pluginName);
									break;
								case 'delete':
									$msg = tr('Plugin %s deleted.', $pluginName);
							}

							set_page_message($msg, 'success');
						}
					}
				} else {
					set_page_message(tr('An unexpected error occurred'));
				}
			}
		} catch(iMSCPException $e) {
			set_page_message($e->getMessage(), 'error');
		}
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Do bulk action (activate|deactivate|protect)
 *
 * @param PluginManager $pluginManager
 * @return void
 */
function doBulkAction($pluginManager)
{
	$action = clean_input($_POST['bulk_actions']);

	if(!in_array($action, array('install', 'uninstall', 'enable', 'disable', 'delete', 'protect'))) {
		showBadRequestErrorPage();
	} elseif(isset($_POST['checked']) && is_array($_POST['checked']) && !empty($_POST['checked'])) {
		foreach($_POST['checked'] as $pluginName) {
			doAction($pluginManager, clean_input($pluginName), $action);
		}
	} else {
		set_page_message(tr('You must select at least one plugin.'), 'error');
	}
}

/**
 * Update plugin list
 *
 * @param PluginManager $pluginManager
 * @param iMSCP
 * @return void
 */
function updatePluginList($pluginManager)
{
	$eventManager = $pluginManager->getEventManager();

	/** @var EventCollection $responses */
	$responses = $eventManager->dispatch(Events::onBeforeUpdatePluginList, array('pluginManager' => $pluginManager));

	if(!$responses->isStopped()) {
		$updateInfo = $pluginManager->pluginUpdateList();
		$eventManager->dispatch(Events::onAfterUpdatePluginList, array('pluginManager' => $pluginManager));

		set_page_message(
			tr(
				'Plugins list has been updated: %s new plugin(s) found, %s plugin(s) updated, %s plugin(s) reconfigured, and %s plugin(s) deleted.',
				$updateInfo['new'], $updateInfo['updated'], $updateInfo['changed'], $updateInfo['deleted']
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

EventManager::getInstance()->dispatch(Events::onAdminScriptStart);

check_login('admin');

/** @var PluginManager $pluginManager */
$pluginManager = Registry::get('pluginManager');

// Dispatches the request
if(!empty($_POST) || !empty($_GET) || !empty($_FILES)) {
	if(isset($_GET['update_plugin_list'])) {
		updatePluginList($pluginManager);
	} elseif(isset($_GET['install'])) {
		doAction($pluginManager, clean_input($_GET['install']), 'install');
	} elseif(isset($_GET['uninstall'])) {
		doAction($pluginManager, clean_input($_GET['uninstall']), 'uninstall');
	} elseif(isset($_GET['enable'])) {
		doAction($pluginManager, clean_input($_GET['enable']), 'enable');
	} elseif(isset($_GET['disable'])) {
		doAction($pluginManager, clean_input($_GET['disable']), 'disable');
	} elseif(isset($_GET['delete'])) {
		doAction($pluginManager, clean_input($_GET['delete']), 'delete');
	} elseif(isset($_GET['protect'])) {
		doAction($pluginManager, clean_input($_GET['protect']), 'protect');
	} elseif(isset($_GET['retry'])) {
		$pluginName = clean_input($_GET['retry']);

		if($pluginManager->pluginIsKnown($pluginName)) {
			switch($pluginManager->pluginGetStatus($pluginName)) {
				case 'toinstall':
					$action = 'install';
					break;
				case 'touninstall':
					$action = 'uninstall';
					break;
				case 'toupdate':
					$action = 'update';
					break;
				case 'tochange':
					$action = 'change';
					break;
				case 'toenable':
					$action = 'enable';
					break;
				case 'todisable':
					$action = 'disable';
					break;
				case 'todelete':
					$action = 'delete';
					break;
				default:
					// Handle case where the error field is not NULL and status field is in unexpected state
					// Should never occurs...
					$pluginManager->pluginSetStatus($pluginName, 'todisable');
					$action = 'disable';
			}

			doAction($pluginManager, $pluginName, $action);
		} else {
			showBadRequestErrorPage();
		}
	} elseif(isset($_POST['bulk_actions'])) {
		doBulkAction($pluginManager);
	} elseif(!empty($_FILES) && uploadPlugin($pluginManager)) {
		OpcodeCacheUtils::clearAllActive(); // Force newest files to be loaded on next run
		set_page_message(tr('Plugin has been successfully uploaded.'), 'success');
		redirectTo('settings_plugins.php?update_plugin_list');
	}

	redirectTo('settings_plugins.php');
}

$tpl = new TemplateEngine();
$tpl->define_dynamic(array(
	'layout' => 'shared/layouts/ui.tpl',
	'page' => 'admin/settings_plugins.tpl',
	'page_message' => 'layout',
	'plugins_block' => 'page',
	'plugin_block' => 'plugins_block',
	'plugin_status_details_block' => 'plugin_block',
	'plugin_activate_link' => 'plugin_block',
	'plugin_deactivate_link' => 'plugin_block',
	'plugin_protected_link' => 'plugin_block'
));

EventManager::getInstance()->registerListener(Events::onGetJsTranslations, function ($event) {
	/** @var $event \iMSCP_Events_Event $translations */
	$event->getParam('translations')->core = array_merge($event->getParam('translations')->core, array(
		'dataTable' => getDataTablesPluginTranslations(false),
		'force_retry' => tr('Force retry'),
		'close' => tr('Close'),
		'error_details' => tr('Error details')
	));
});

$tpl->assign(array(
	'TR_PAGE_TITLE' => tr('Admin / Settings / Plugin Management'),
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
	'TR_PROTECT' => tr('Protect'),
	'TR_DELETE' => tr('Delete'),
	'TR_PROTECT_TOOLTIP' => tr('Protect this plugin'),
	'TR_VERSION' => tr('Version'),
	'TR_BY' => tr('By'),
	'TR_VISIT_PLUGIN_SITE' => tr('Visit plugin site'),
	'TR_UPDATE_PLUGIN_LIST' => tr('Update Plugins'),
	'TR_APPLY' => tr('Apply'),
	'TR_PLUGIN_UPLOAD' => tr('Plugins Upload'),
	'TR_UPLOAD' => tr('Upload'),
	'TR_PLUGIN_ARCHIVE' => tr('Plugin archive'),
	'TR_PLUGIN_ARCHIVE_TOOLTIP' => tr('Only tar.gz, tar.bz2 and zip archives are accepted.'),
	'TR_PLUGIN_HINT' => tr('Plugins hook into i-MSCP to extend its functionality with custom features. Plugins are developed independently from the core i-MSCP application by thousands of developers all over the world. You can find new plugins to install by browsing the %s.', '<a style="text-decoration: underline" href="http://i-mscp.net/filebase/index.php/Filebase/" target="_blank">' . tr('i-MSCP plugin store') . '</a></u>'),
	'TR_CLICK_FOR_MORE_DETAILS' => tr('Click here for more details')
));

generateNavigation($tpl);
generatePage($tpl, $pluginManager);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventManager::getInstance()->dispatch(Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

unsetMessages();
