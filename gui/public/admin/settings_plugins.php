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

// TODO datatable (Must fix layout issue before - table header + checkbox)

/***********************************************************************************************************************
 * Script functions
 */

/**
 * Upload plugin archive into the gui/plugins directory
 *
 * @return bool TRUE on success, FALSE on failure
 */
function admin_pluginManagerUploadPlugin()
{
	$ret = true;

	if (isset($_FILES['pluginArchive'])) {
		$tmpDir = GUI_ROOT_DIR . '/data/tmp';
		$extToMimetypes = array(
			'application/x-gzip' => 'tar.gz', 'application/x-bzip2' => 'tar.bz2', 'application/zip' => 'zip'
		);

		$beforeMove = function ($extToMimetypes, $tmpDir) {
			$tmpFilePath = $_FILES['pluginArchive']['tmp_name'];

			$pluginArchiveSize = $_FILES['pluginArchive']['size'];
			$maxUploadFileSize = utils_getMaxFileUpload();

			if($pluginArchiveSize > $maxUploadFileSize) {
				set_page_message(
					tr(
						'Plugin archive is too big (%s). Max size is: %s.',
						bytesHuman($pluginArchiveSize),
						bytesHuman($maxUploadFileSize)
					), 'error'
				);
				return false;
			}

			// Check file mime type
			if (!($fileMimeType = checkMimeType($tmpFilePath, array_keys($extToMimetypes)))) {
				set_page_message(tr('Only tar.gz, tar.bz2 and zip archives are supported.'), 'error');
				return false;
			}

			return $tmpDir . '/' . $_FILES['pluginArchive']['name'];
		};

		$archPath = utils_uploadFile('pluginArchive', array($beforeMove, $extToMimetypes, $tmpDir));

		if ($archPath === false) {
			$ret = false;
		} else {
			## TODO remove previous plugin dir

			$archType = $extToMimetypes[checkMimeType($archPath, array_keys($extToMimetypes))];

			try {
				if ($archType != 'zip') {
					$arch = new PharData($archPath);
					$arch = $arch->decompress();
					$archTarPath = $arch->getPath();
				}

				$arch = new PharData(isset($archTarPath) ? $archTarPath : $archPath);
				$arch->extractTo(PLUGINS_PATH, null, true);
			} catch (Exception $e) {
				set_page_message(tr('Unable to extract plugin archive: %s', $e->getMessage()), 'error');
				$ret = false;
			}

			if (isset($archTarPath)) @unlink($archTarPath); // Cleanup
			@unlink($archPath); // Cleanup
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
		case 'install':
			return tr('Install in progress...');
			break;
//		case 'update':
//			return tr('Update in progress...');
//			break;
		case 'uninstall':
			return tr('Uninstall in progress...');
			break;
		case 'enabled':
			return tr('Activated');
			break;
		case 'disabled':
			return tr('Deactivated');
			break;
		default:
			return tr('Unknown error: %s', $rawPluginStatus);
	}
}

/**
 * Generates plugins list from database.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param iMSCP_Plugin_Manager $pluginManager
 */
function admin_pluginManagerGeneratePluginList($tpl, $pluginManager)
{
	$pluginList = $pluginManager->getPluginList('Action', false);

	if (empty($pluginList)) {
		$tpl->assign('PLUGINS_BLOCK', '');
		set_page_message(tr('Plugin list is empty. To install a new plugin, extract its archive content into the <strong>gui/plugins</strong> directory and update the plugin list.'), 'info');
	} else {
		sort($pluginList);
		$cacheFile = 'gui/data/persistent/protected_plugins.php';
		$protectTooltip = '<span style="color:rgb(96, 0, 14);cursor:pointer" title="' . tr('To unprotect this plugin, you must edit the %s file', $cacheFile) . '">' . tr('Protected plugin') . '</span>';

		$hasLoadedPlugins = false;

		foreach ($pluginList as $pluginName) {
			if (($plugin = $pluginManager->load('Action', $pluginName, false, false)) !== null) {
				$pluginInfo = $plugin->getInfo();
				$tpl->assign(
					array(
						'PLUGIN_NAME' => tohtml($plugin->getName()),
						'PLUGIN_DESCRIPTION' => tohtml($pluginInfo['desc']),
						'PLUGIN_STATUS' => tohtml(admin_pluginManagerTrStatus($pluginManager->getStatus($pluginName))),
						'PLUGIN_VERSION' => tohtml($pluginInfo['version']),
						'PLUGIN_AUTHOR' => tohtml($pluginInfo['author']),
						'PLUGIN_MAILTO' => tohtml($pluginInfo['email']),
						'PLUGIN_SITE' => tohtml($pluginInfo['url'])
					)
				);

				if ($pluginManager->isProtected($pluginName)) {
					$tpl->assign('PLUGIN_DEACTIVATE_LINK', '');
					$tpl->assign('PLUGIN_ACTIVATE_LINK', $protectTooltip);
				} elseif ($pluginManager->isActivated($pluginName)) {
					$tpl->assign('PLUGIN_ACTIVATE_LINK', '');
					$tpl->parse('PLUGIN_DEACTIVATE_LINK', 'plugin_deactivate_link');
				} elseif ($pluginManager->isDeactivated($pluginName)) {
					$tpl->assign('PLUGIN_DEACTIVATE_LINK', '');
					$tpl->parse('PLUGIN_ACTIVATE_LINK', 'plugin_activate_link');
				} else { # Install, Update and Unknown status cases
					$tpl->assign('PLUGIN_DEACTIVATE_LINK', '');
					$tpl->assign('PLUGIN_ACTIVATE_LINK', '');
				}

				$tpl->parse('PLUGIN_BLOCK', '.plugin_block');
				$hasLoadedPlugins = true;
			}
		}

		if (!$hasLoadedPlugins) {
			set_page_message(tr('Plugin list is empty. To install a new plugin, extract its archive content into the <strong>gui/plugins</strong> directory and update the plugin list.'), 'info');
			$tpl->assign('PLUGINS_BLOCK', '');
		}
	}
}

/**
 * Do bulk action (activate|deactivate|protect).
 *
 * @param iMSCP_Plugin_Manager $pluginManager
 * @return void
 */
function admin_pluginManagerDoBulkAction($pluginManager)
{
	$action = clean_input($_POST['bulkActions']);

	if (isset($_POST['checked']) && is_array($_POST['checked']) && !empty($_POST['checked'])) {
		if (!empty($_POST['checked'])) {
			$trActions = array(
				'activate' => tr('activate'), 'deactivate' => tr('deactivate'), 'protect' => tr('protect'),
				'activated' => tr('activated'), 'deactivated' => tr('deactivated'), 'protectd' => tr('protected')
			);

			$eventAction = array(
				'activate' => array(iMSCP_Events::onBeforeActivatePlugin, iMSCP_Events::onAfterActivatePlugin),
				'deactivate' => array(iMSCP_Events::onBeforeDeactivatePlugin, iMSCP_Events::onAfterDeactivatePlugin),
				'protect' => array(iMSCP_Events::onBeforeProtectPlugin, iMSCP_Events::onAfterProtectPlugin),
			);

			foreach ($_POST['checked'] as $pluginName) {
				if (!$pluginManager->isProtected($pluginName)) {
					iMSCP_Events_Manager::getInstance()->dispatch(
						$eventAction[$action][0], array('pluginManager' => $pluginManager, 'pluginName' => $pluginName)
					);

					if (!$pluginManager->{$action}(clean_input($pluginName))) {
						set_page_message(tr('Plugin manager was unable to %s the %s plugin.', $trActions[$action], "<strong>$pluginName</strong>"), 'error');
					} else {
						iMSCP_Events_Manager::getInstance()->dispatch(
							$eventAction[$action][1], array('pluginManager' => $pluginManager, 'pluginName' => $pluginName)
						);

						set_page_message(tr('Plugin %s successfully %s.', "<strong>$pluginName</strong>", $trActions[$action . 'd']), 'success');
					}
				} else {
					set_page_message(tr('The plugin %s is protected. You cannot perform an action on it.', "<strong>$pluginName</strong>"), 'warning');
				}
			}
		}
	} else {
		set_page_message(tr('You must select one or more plugins to operate on.'), 'error');
	}
}

/**
 * Update plugin list
 *
 * @param iMSCP_Plugin_Manager $pluginManager
 *
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
		tr('Plugin list successfully updated.<br/><strong>%d</strong> new plugin(s) found, <strong>%d</strong> plugin(s) updated, and <strong>%d</strong> plugin(s) deleted.', $info['added'], $info['updated'], $info['deleted']), 'success'
	);
}
/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

/** @var $pluginManager iMSCP_Plugin_Manager */
$pluginManager = iMSCP_Registry::get('pluginManager');

// Dispatches the request
if (isset($_GET['updatePluginList'])) {
	admin_pluginManagerUpdatePluginList($pluginManager);
	redirectTo('settings_plugins.php');
} elseif (isset($_GET['activate'])) {
	$pluginName = clean_input($_GET['activate']);

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeActivatePlugin, array('pluginManager' => $pluginManager, 'pluginName' => $pluginName)
	);

	if ($pluginManager->activate($pluginName)) {
		iMSCP_Events_Manager::getInstance()->dispatch(
			iMSCP_Events::onAfterActivatePlugin, array('pluginManager' => $pluginManager, 'pluginName' => $pluginName)
		);

		set_page_message(tr('Plugin successfully activated.'), 'success');
	} else {
		set_page_message(tr('Plugin manager was unable to activate the plugin.'), 'error');
	}

	redirectTo('settings_plugins.php');
} elseif (isset($_GET['deactivate'])) {
	$pluginName = clean_input($_GET['deactivate']);

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeDeactivatePlugin, array('pluginManager' => $pluginManager, 'pluginName' => $pluginName)
	);

	if ($pluginManager->deactivate($pluginName)) {
		iMSCP_Events_Manager::getInstance()->dispatch(
			iMSCP_Events::onAfterDeactivatePlugin, array('pluginManager' => $pluginManager, 'pluginName' => $pluginName)
		);

		set_page_message(tr('Plugin successfully deactivated.'), 'success');
	} else {
		set_page_message(tr('Plugin manager was unable to deactivate the plugin.'), 'error');
	}

	redirectTo('settings_plugins.php');
} elseif (isset($_GET['protect'])) {
	$pluginName = clean_input($_GET['protect']);

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeProtectPlugin, array('pluginManager' => $pluginManager, 'pluginName' => $pluginName)
	);

	if ($pluginManager->protect($pluginName)) {
		iMSCP_Events_Manager::getInstance()->dispatch(
			iMSCP_Events::onAfterProtectPlugin, array('pluginManager' => $pluginManager, 'pluginName' => $pluginName)
		);

		set_page_message(tr('Plugin successfully protected.'), 'success');
	} else {
		set_page_message(tr('Plugin manager was unable to protect the plugin.'), 'error');
	}

	redirectTo('settings_plugins.php');
} elseif (isset($_POST['bulkActions']) && in_array($_POST['bulkActions'], array('activate', 'deactivate', 'protect'))) {
	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeBulkAction, array('pluginManager' => $pluginManager)
	);

	admin_pluginManagerDoBulkAction($pluginManager);

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onAfterBulkAction, array('pluginManager' => $pluginManager)
	);

	redirectTo('settings_plugins.php');
} elseif(!empty($_FILES)) {
	if (admin_pluginManagerUploadPlugin()) {
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
		'plugin_activate_link' => 'plugin_block',
		'plugin_deactivate_link' => 'plugin_block',
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Settings / Plugins management'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'TR_BULK_ACTIONS' => tr('Bulk Actions'),
		'TR_PLUGIN' => tr('Plugin'),
		'TR_DESCRIPTION' => tr('Description'),
		'TR_STATUS' => tr('Status'),
		'TR_ACTIONS' => tr('Actions'),
		'TR_ACTIVATE' => tr('Activate'),
		'TR_ACTIVATE_TOOLTIP' => tr('Activate this plugin'),
		//'TR_UPDATE' => tr('Update'),
		//'TR_UPDATE_TOOLTIP' => tr('Force plugin update'),
		'TR_DEACTIVATE_TOOLTIP' => tr('Deactivate this plugin'),
		'TR_DEACTIVATE' => tr('Deactivate'),
		'TR_PROTECT' => tr('Protect'),
		'TR_PROTECT_TOOLTIP' => tr('Protect this plugin'),
		'TR_PLUGIN_CONFIRMATION_TITLE' => tr('Confirmation for plugin protection'),
		'TR_PROTECT_CONFIRMATION' => tr("If you protect a plugin, you'll no longer be able to deactivate it from the plugin management interface.<br /><br />To unprotect  a plugin, you'll have to edit the %s file.", 'gui/data/persistent/protected_plugins.php'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_VERSION' => tr('Version'),
		'TR_BY' => tr('By'),
		'TR_VISIT_PLUGIN_SITE' => tr('Visit plugin site'),
		'TR_UPDATE_PLUGIN_LIST' => tr('Update plugin list'),
		'TR_APPLY' => tr('Apply'),

		'TR_PLUGIN_UPLOAD' => tr('Plugins Upload'),
		'TR_UPLOAD' => tr('Upload'),
		'TR_PLUGIN_ARCHIVE' => tr('Plugin archive'),
		'TR_PLUGIN_ARCHIVE_TOOLTIP' => 'Only tar.gz, tar.bz2 and zip archives are accepted.',
		'TR_UPLOAD' => tr('Upload')
	)
);

generateNavigation($tpl);
admin_pluginManagerGeneratePluginList($tpl, $pluginManager);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
