<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 - 2012 by i-MSCP Team
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
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Admin_Plugin
 * @copyright	2010 - 2012 by i-MSCP Team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.1
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

// TODO datatable (Must fix layout issue before - table header + checkbox)

/*******************************************************************************
 * Script function
 */

/**
 * Generates plugins list from database.
 *
 * @param iMSCP_pTemplate $tpl
 * @param iMSCP_Plugin_Manager $pluginManager
 */
function admin_generatesPluginList($tpl, $pluginManager)
{
	$pluginList = $pluginManager->getPluginList('Action', false);
	sort($pluginList);

	if (empty($pluginList)) {
		$tpl->assign('PLUGINS_BLOCK', '');
		set_page_message(tr('Plugin list is empty. To install a new plugin, extract its content into the <strong>gui/plugins</strong> directory and update the plugin list.'), 'info');
	} else {
		$cacheFile = 'gui/cache/protected_plugins.php';
		$protectTooltip = '<span style="color:rgb(96, 0, 14);cursor:pointer" title="' . tr('To unprotect this plugin, you must edit the %s file', $cacheFile) . '">' . tr('Protected plugin') . '</span>';

		$hasLoadedPlugins = false;

		foreach ($pluginList as $pluginName) {
			$plugin = $pluginManager->load('Action', $pluginName, false, true);
			if(null === $plugin) continue;
			$pluginInfo = $plugin->getInfo();
			$tpl->assign(
				array(
					'PLUGIN_NAME' => tohtml($plugin->getName()),
					'PLUGIN_DESCRIPTION' => tohtml($pluginInfo['desc']),
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
			}

			$tpl->parse('PLUGIN_BLOCK', '.plugin_block');
			$hasLoadedPlugins = true;
		}

		if(!$hasLoadedPlugins) {
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
function admin_doBulkAction($pluginManager)
{
	$action = clean_input($_POST['bulkActions']);

	if (isset($_POST['checked']) && is_array($_POST['checked'])) {
		if (!empty($_POST['checked'])) {
			foreach ($_POST['checked'] as $pluginName) {
				// TODO: Add check for possible failure and $pluginName is unknown
				$pluginManager->{$action}(clean_input($pluginName));
			}

			switch ($action) {
				case 'activate':
					set_page_message(tr('Plugin(s) successfully activated.'), 'success');
					break;
				case 'deactivate':
					set_page_message(tr('Plugin(s) successfully deactivated.'), 'success');
					break;
				case 'protect':
					set_page_message(tr('Plugin(s) successfully protected.'), 'success');
					break;
			}
		}
	}
}

/*******************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $pluginManager iMSCP_Plugin_Manager */
$pluginManager = iMSCP_Registry::get('pluginManager');

// Dispatches the request
if (isset($_GET['update'])) {
	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeUpdatePluginList, array('pluginManager' => $pluginManager)
	);

	$newPluginsCount = $pluginManager->updatePluginList();

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onAfterUpdatePluginList, array('pluginManager' => $pluginManager)
	);

	// TODO message about updated plugins
	set_page_message(tr('Plugin list successfully updated. <strong>%d</strong> new plugin(s) found.', $newPluginsCount), 'success');
} elseif (isset($_GET['activate'])) {
	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeActivatePlugin, array('pluginManager' => $pluginManager));

	$pluginManager->activate(clean_input($_GET['activate']));

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onAfterActivatePlugin, array('pluginManager' => $pluginManager)
	);

	set_page_message(tr('Plugin successfully activated.'), 'success');
} elseif (isset($_GET['deactivate'])) {
	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeDeactivatePlugin, array('pluginManager' => $pluginManager)
	);

	$pluginManager->deactivate(clean_input($_GET['deactivate']));

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onAfterDeactivatePlugin, array('pluginManager' => $pluginManager)
	);

	set_page_message(tr('Plugin successfully deactivated.'), 'success');
} elseif (isset($_GET['protect'])) {
	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeProtectPlugin, array('pluginManager' => $pluginManager)
	);

	$pluginManager->protect(clean_input($_GET['protect']));

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onAfterProtectPlugin, array('pluginManager' => $pluginManager)
	);

	set_page_message(tr('Plugin successfully protected.'), 'success');
} elseif (isset($_POST['bulkActions']) && in_array($_POST['bulkActions'], array('activate', 'deactivate', 'protect'))) {
	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onBeforeBulkAction, array('pluginManager' => $pluginManager)
	);

	admin_doBulkAction($pluginManager);

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onAfterBulkAction, array('pluginManager' => $pluginManager)
	);
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
		'TR_PAGE_TITLE' => tr('i-MSCP - Admin / Settings / Plugin management'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'TR_BULK_ACTIONS' => tr('Bulk Actions'),
		'TR_PLUGIN' => tr('Plugin'),
		'TR_DESCRIPTION' => tr('description'),
		'TR_ACTIVATE' => tr('Activate'),
		'TR_ACTIVATE_TOOLTIP' => tr('Activate this plugin'),
		'TR_DEACTIVATE_TOOLTIP' => tr('Deactivate this plugin'),
		'TR_DEACTIVATE' => tr('Deactivate'),
		'TR_PROTECT' => tr('Protect'),
		'TR_PROTECT_TOOLTIP' => tr('Protect this plugin'),
		'TR_PLUGIN_CONFIRMATION_TITLE' => tr('Confirmation for plugin protection'),
		'TR_PROTECT_CONFIRMATION' => tr("If you protect a plugin, you'll no longer be able to deactivate it from the plugin management interface.<br /><br />To unprotect  a plugin, you'll have to edit the %s file.", 'gui/cache/protected_plugins.php'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_VERSION' => tr('Version'),
		'TR_BY' => tr('By'),
		'TR_VISIT_PLUGIN_SITE' => tr('Visit plugin site'),
		'TR_UPDATE_PLUGIN_LIST' => tr('Update plugin list')
	)
);

generateNavigation($tpl);
admin_generatesPluginList($tpl, $pluginManager);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
