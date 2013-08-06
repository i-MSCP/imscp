<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP Team
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
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/***********************************************************************************************************************
 * Script functions
 */

/**
 * Upload plugin archive into the gui/plugins directory
 *
 * @param iMSCP_Plugin_Manager $pluginManager
 * @return bool TRUE on success, FALSE on failure
 */
function admin_pluginManagerUploadPlugin($pluginManager)
{
	$pluginDirectory = $pluginManager->getPluginDirectory();
	$tmpDirectory = GUI_ROOT_DIR . '/data/tmp';
	$ret = false;

	if (isset($_FILES['pluginArchive'])) {
		$beforeMove = function ($tmpDirectory) {
			$tmpFilePath = $_FILES['pluginArchive']['tmp_name'];

			if (!checkMimeType($tmpFilePath, array('application/x-gzip','application/x-bzip2', 'application/zip'))) {
				set_page_message(tr('Only tar.gz, tar.bz2 and zip archives are supported.'), 'error');
				return false;
			}

			$pluginArchiveSize = $_FILES['pluginArchive']['size'];
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

			return $tmpDirectory . '/' . $_FILES['pluginArchive']['name'];
		};

		if (($archPath = utils_uploadFile('pluginArchive', array($beforeMove, $tmpDirectory))) !== false) {
			$zipArch = (substr($archPath, -3) === 'zip');

			try {
				if (!$zipArch) {
					$arch = new PharData($archPath);
					$name = $arch->getBasename();

					if(!isset($arch["$name/$name.php"])) {
						throw new iMSCP_Exception(tr('File %s is missing in plugin directory.', "$name.php"));
					}
				} else {
					$arch = new ZipArchive;

					if ($arch->open($archPath) === true) {
						if(($name = $arch->getNameIndex(0, ZIPARCHIVE::FL_UNCHANGED)) !== false) {
							$name = rtrim($name, '/');

							$index = $arch->locateName("$name.php", ZipArchive::FL_NODIR);

							if($index !== false) {
								if(($stats = $arch->statIndex($index))) {
									if($stats['name'] != "$name/$name.php") {
										throw new iMSCP_Exception(tr('File %s is missing in plugin directory.', "$name.php"));
									}
								} else {
									throw new iMSCP_Exception(tr('Unable to get stats for file %s.', "$name.php"));
								}
							} else {
								throw new iMSCP_Exception(tr('File %s is missing in plugin directory.', "$name.php"));
							}
						} else {
							throw new iMSCP_Exception(tr('Unable to find plugin root directory.'));
						}
					} else {
						throw new iMSCP_Exception(tr('Unable to open archive.'));
					}
				}

				if($pluginManager->isKnown($name) && $pluginManager->isProtected($name)) {
					throw new iMSCP_Exception(tr('You are not allowed to update a protected plugin.'));
				}

				# Backup current plugin directory in temporary directory if exists
				if(is_dir("$pluginDirectory/$name")) {
					if(!@rename("$pluginDirectory/$name", "$tmpDirectory/$name")) {
						throw new iMSCP_Exception(
							tr('Unable to backup %s plugin directory.', "<strong>$name</strong>")
						);
					}
				}

				if(!$zipArch) {
					$arch->extractTo($pluginDirectory, null, true);
				} elseif(!$arch->extractTo($pluginDirectory)) {
					throw new iMSCP_Exception(tr('Unable to extract plugin archive.'));
				}

				$ret  = true;
			} catch (Exception $e) {
				if($e instanceof iMSCP_Exception) {
					set_page_message($e->getMessage(), 'error');
				} else {
					set_page_message(tr('Unable to extract plugin archive: %s', $e->getMessage()), 'error');
				}

				if(isset($name) && is_dir("$tmpDirectory/$name")) {
					// Restore previous plugin directory on error
					if(!@rename("$tmpDirectory/$name", "$pluginDirectory/$name")) {
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
	switch($rawPluginStatus) {
		case 'enabled':
			return tr('Activated');
		case 'uninstalled':
		case 'disabled':
			return tr('Deactivated');
		case 'toinstall':
		case 'toenable':
			return tr('Activation in progress...');
		case 'tochange':
			return tr('Change in progress...');
		case 'toupdate':
			return tr('Update in progress...');
			break;
		case 'touninstall':
		case 'todisable':
			return tr('Deactivation in progress...');
		default:
			return tr('Unknown error');
	}
}

/**
 * Generates plugins list from database
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param iMSCP_Plugin_Manager $pluginManager
 * @return void
 */
function admin_pluginManagerGeneratePluginList($tpl, $pluginManager)
{
	$trAction = array(
		'toinstall' => tr('activation'),
		'toupdate' => tr('update'),
		'touninstall' => tr('deletion'),
		'toenable' => tr('activation'),
		'todisable' => tr('deactivation'),
		'todelete' => tr('deletion'),
		'tochange' => tr('change')
	);

	$pluginList = $pluginManager->getPluginList('Action', false);

	if (empty($pluginList)) {
		$tpl->assign('PLUGINS_BLOCK', '');
		set_page_message(tr('Plugin list is empty.'), 'info');
	} else {
		sort($pluginList);
		$cacheFile = PERSISTENT_PATH . '/protected_plugins.php';
		$protectTooltip = '<span style="color:rgb(96, 0, 14);cursor:pointer" title="' .
			tr('To unprotect this plugin, you must edit the %s file', $cacheFile) . '">' .
			tr('Protected plugin') . '</span>';

		$hasLoadedPlugins = false;

		foreach ($pluginList as $pluginName) {
			if (($plugin = $pluginManager->load($pluginName, false, false)) !== null) {
				$pluginInfo = $plugin->getInfo();
				$pluginStatus = $pluginManager->getStatus($pluginName);
				$tpl->assign(
					array(
						'PLUGIN_NAME' => tohtml($plugin->getName()),
						'PLUGIN_DESCRIPTION' => tohtml($pluginInfo['desc']),
						'PLUGIN_STATUS' => ($pluginManager->hasError($pluginName))
							? tohtml(admin_pluginManagerTrStatus('unknown'))
							: tohtml(admin_pluginManagerTrStatus($pluginStatus)),
						'PLUGIN_VERSION' => tohtml($pluginInfo['version']),
						'PLUGIN_AUTHOR' => tohtml($pluginInfo['author']),
						'PLUGIN_MAILTO' => tohtml($pluginInfo['email']),
						'PLUGIN_SITE' => tohtml($pluginInfo['url'])
					)
				);

				if($pluginManager->hasError($pluginName)) {
					$tpl->assign(
						'PLUGIN_STATUS_DETAILS',
						tr(
							'An unexpected error occurred while plugin %s attempt: %s',
							$trAction[$pluginStatus], '<br /><br />' . $pluginManager->getError($pluginName)
						)
					);
					$tpl->parse('PLUGIN_STATUS_DETAILS_BLOCK', 'plugin_status_details_block');
					$tpl->assign(array('PLUGIN_DEACTIVATE_LINK' => '', 'PLUGIN_ACTIVATE_LINK' => ''));
				} else {
					$tpl->assign('PLUGIN_STATUS_DETAILS_BLOCK', '');

					if($pluginManager->isProtected($pluginName)) {
						$tpl->assign(array('PLUGIN_ACTIVATE_LINK' => $protectTooltip, 'PLUGIN_DEACTIVATE_LINK' => ''));
					} elseif($pluginManager->isDeactivated($pluginName)) {
						$tpl->assign('PLUGIN_DEACTIVATE_LINK', '');
						$tpl->parse('PLUGIN_ACTIVATE_LINK', 'plugin_activate_link');
					} elseif($pluginManager->isActivated($pluginName)) {
						$tpl->assign('PLUGIN_ACTIVATE_LINK', '');
						$tpl->parse('PLUGIN_DEACTIVATE_LINK', 'plugin_deactivate_link');
					}  else {
						$tpl->assign(array('PLUGIN_DEACTIVATE_LINK' => '', 'PLUGIN_ACTIVATE_LINK' => ''));
					}
				}

				$tpl->parse('PLUGIN_BLOCK', '.plugin_block');
				$hasLoadedPlugins = true;
			}
		}

		if (!$hasLoadedPlugins) {
			set_page_message(tr('Plugin list is empty.'), 'info');
			$tpl->assign('PLUGINS_BLOCK', '');
		}
	}
}

/**
 * Execute the given action for the given plugin
 *
 * @param iMSCP_Plugin_Manager $pluginManager
 * @param string $pluginName Plugin name
 * @param string $action Action (activate|deactivate|delete|protect)
 * @param bool $force Force action
 * @return void
 */
function admin_pluginManagerDoAction($pluginManager, $pluginName, $action, $force = false)
{
	$trActions = array(
		'activate' => tr('activate'),
		'update' => tr('update'),
		'change' => tr('change'),
		'deactivate' => tr('deactivate'),
		'delete' => tr('delete'),
		'protect' => tr('protect'),

		'activated' => tr('activated'),
		'updated' => tr('updated'),
		'deactivated' => tr('deactivated'),
		'deleted' => tr('deleted'),
		'protected' => tr('protected')
	);

	$pluginName = clean_input($pluginName);

	if ($pluginManager->isKnown($pluginName)) {
		if ($pluginManager->isProtected($pluginName)) {
			set_page_message(tr('Plugin %s is protected.', "<strong>$pluginName</strong>"), 'warning');
		} elseif ($action == 'activate' && $pluginManager->isActivated($pluginName)) {
			set_page_message(tr('Plugin %s is already activated.', "<strong>$pluginName</strong>"), 'warning');
		} elseif ($action == 'deactivate' && $pluginManager->isDeactivated($pluginName)) {
			set_page_message(tr('Plugin %s is already deactivated.', "<strong>$pluginName</strong>"), 'warning');
		} else {
			if (!$pluginManager->{$action}($pluginName, $force)) {
				set_page_message(
					tr(
						'Plugin manager was unable to %s the %s plugin.', $trActions[$action],
						"<strong>$pluginName</strong>"
					),
					'error'
				);
			} else {
				if($action != 'delete' && $pluginManager->hasBackend($pluginName)) {
					set_page_message(
						tr(
							'Plugin %s successfully scheduled for %s.',
							"<strong>$pluginName</strong>",
							($action == 'activate') ? tr('activation') : tr('deactivation')
						),
						'success'
					);
				} else {
					set_page_message(
						tr(
							'Plugin %s successfully %s.', "<strong>$pluginName</strong>", $trActions[$action . 'd']
						),
						'success'
					);
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
	$action = clean_input($_POST['bulkActions']);

	if(!in_array($action, array('activate', 'deactivate', 'delete', 'protect'))) {
		showBadRequestErrorPage();
	} elseif(isset($_POST['checked']) && is_array($_POST['checked']) && !empty($_POST['checked'])) {
		foreach ($_POST['checked'] as $pluginName) {
			$pluginName = clean_input($pluginName);
			admin_pluginManagerDoAction($pluginManager, $pluginName, $action);
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
	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeUpdatePluginList, array('pluginManager' => $pluginManager)
	);

	$info = $pluginManager->updatePluginList();

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onAfterUpdatePluginList, array('pluginManager' => $pluginManager)
	);

	set_page_message(
		tr('Plugin list successfully updated.') .
		'<br />' .
		tr(
			'%s new plugin(s) found, %s plugin(s) updated, and %s plugin(s) deleted.',
			"<strong>{$info['new']}</strong>",
			"<strong>{$info['updated']}</strong>",
			"<strong>{$info['deleted']}</strong>"
		),
		'success'
	);
}
/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

if(iMSCP_Registry::isRegistered('pluginManager')) {
	/** @var iMSCP_Plugin_Manager $pluginManager */
	$pluginManager = iMSCP_Registry::get('pluginManager');
} else {
	throw new iMSCP_Plugin_Exception('An unexpected error occurred');
}

// Dispatches the request
if (isset($_GET['updatePluginList'])) {
	admin_pluginManagerUpdatePluginList($pluginManager);
	redirectTo('settings_plugins.php');
} elseif (isset($_GET['activate'])) {
	$pluginName = clean_input($_GET['activate']);
	admin_pluginManagerDoAction($pluginManager, $pluginName, 'activate');
	redirectTo('settings_plugins.php');
} elseif (isset($_GET['deactivate'])) {
	$pluginName = clean_input($_GET['deactivate']);
	admin_pluginManagerDoAction($pluginManager, $pluginName, 'deactivate');
	redirectTo('settings_plugins.php');
} elseif (isset($_GET['delete'])) {
	$pluginName = clean_input($_GET['delete']);
	admin_pluginManagerDoAction($pluginManager, $pluginName, 'delete');
	redirectTo('settings_plugins.php');
} elseif (isset($_GET['retry'])) {
	$pluginName = clean_input($_GET['retry']);

	if($pluginManager->isKnown($pluginName)) {
		switch($pluginManager->getStatus($pluginName)) {
			case 'toinstall':
			case 'toenable':
				$action = 'activate';
				break;
			case 'toupdate':
				$action = 'update';
				break;
			case 'todisable':
				$action = 'deactivate';
				break;
			case 'touninstall':
				$action = 'delete';
				break;
			case 'tochange':
				$action = 'change';
				break;
			default:
				showBadRequestErrorPage();
		}

		admin_pluginManagerDoAction($pluginManager, $pluginName, $action, true);
		redirectTo('settings_plugins.php');
	} else {
		showBadRequestErrorPage();
	}
} elseif (isset($_GET['protect'])) {
	$pluginName = clean_input($_GET['protect']);
	admin_pluginManagerDoAction($pluginManager, $pluginName, 'protect');
	redirectTo('settings_plugins.php');
} elseif (isset($_POST['bulkActions'])) {
	admin_pluginManagerDoBulkAction($pluginManager);
	redirectTo('settings_plugins.php');
} elseif(!empty($_FILES)) {
	if (admin_pluginManagerUploadPlugin($pluginManager)) {
		set_page_message(tr('Plugin successfully uploaded.'), 'success');
		admin_pluginManagerUpdatePluginList($pluginManager);
		redirectTo('settings_plugins.php');
	}
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
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Settings / Plugins Management'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'TR_BULK_ACTIONS' => tr('Bulk Actions'),
		'TR_PLUGIN' => tr('Plugin'),
		'TR_DESCRIPTION' => tr('Description'),
		'TR_STATUS' => tr('Status'),
		'TR_ACTIONS' => tr('Actions'),
		'TR_ACTIVATE' => tr('Activate'),
		'TR_ACTIVATE_TOOLTIP' => tr('Activate this plugin.'),
		'TR_DEACTIVATE_TOOLTIP' => tr('Deactivate this plugin.'),
		'TR_DEACTIVATE' => tr('Deactivate'),
		'TR_PROTECT' => tr('Protect'),
		'TR_DELETE' => tr('Delete'),
		'TR_DELETE_TOOLTIP' => ('Delete this plugin'),
		'TR_PROTECT_TOOLTIP' => tr('Protect this plugin'),
		'TR_PLUGIN_CONFIRMATION_TITLE' => tr('Confirmation for plugin protection'),
		'TR_PROTECT_CONFIRMATION' => tr(
			"If you protect a plugin, you'll no longer be able to deactivate it from the plugin management interface."
		),
		'TR_CANCEL' => tr('Cancel'),
		'TR_VERSION' => tr('Version'),
		'TR_BY' => tr('By'),
		'TR_VISIT_PLUGIN_SITE' => tr('Visit plugin site'),
		'TR_UPDATE_PLUGIN_LIST' => tr('Update plugin list'),
		'TR_APPLY' => tr('Apply'),
		'TR_PLUGIN_UPLOAD' => tr('Plugins Upload'),
		'TR_UPLOAD' => tr('Upload'),
		'TR_PLUGIN_ARCHIVE' => tr('Plugin archive'),
		'TR_PLUGIN_ARCHIVE_TOOLTIP' => tr('Only tar.gz, tar.bz2 and zip archives are accepted.'),
		'TR_PLUGIN_HINT' => tr('Plugins hook into i-MSCP to extend its functionality with custom features. Plugins are developed independently from the core i-MSCP application by thousands of developers all over the world. You can find new plugins to install by browsing the %s.', true, '<u><a href="http://plugins.i-mscp.net" target="_blank">' . tr('i-MSCP plugin repository') . '</a></u>'),
		'TR_CLICK_FOR_MORE_DETAILS' => tr('Click here for more details'),
		'TR_ERROR_DETAILS' => tr('Error details'),
		'TR_FORCE_RETRY'  => tr('Force retry'),
		'TR_CLOSE' => tr('Close')
	)
);

generateNavigation($tpl);
admin_pluginManagerGeneratePluginList($tpl, $pluginManager);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
