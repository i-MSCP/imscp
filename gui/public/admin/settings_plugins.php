<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Events_Listener_ResponseCollection as EventCollection;
use iMSCP_Exception as iMSCPException;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Registry as Registry;
use iMSCP_Utility_OpcodeCache as OpcodeCacheUtils;

/**
 * Upload plugin archive into the gui/plugins directory
 *
 * Supported archives: zip tar.gz and tar.bz2
 *
 * @param PluginManager $pluginManager
 * @return bool TRUE on success, FALSE on failure
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function uploadPlugin($pluginManager)
{
    if (!isset($_FILES['plugin_archive'])) {
        showBadRequestErrorPage();
    }

    $pluginName = 'dummy.xxxxxxx';
    $pluginDirectory = $pluginManager->pluginGetDirectory();
    $tmpDirectory = GUI_ROOT_DIR . '/data/tmp';
    $ret = false;

    # Upload plugin archive into gui/data/tmp directory (eg. gui/data/tmp/PluginName.zip)
    $tmpArchPath = utils_uploadFile('plugin_archive', [function ($tmpDirectory) {
        $tmpFilePath = $_FILES['plugin_archive']['tmp_name'];
        if (!checkMimeType($tmpFilePath, ['application/x-gzip', 'application/x-bzip2',])) {
            set_page_message(tr('Only tar.gz and tar.bz2 archives are supported.'), 'error');
            return false;
        }

        $pluginArchiveSize = $_FILES['plugin_archive']['size'];
        $maxUploadFileSize = utils_getMaxFileUpload();

        if ($pluginArchiveSize > $maxUploadFileSize) {
            set_page_message(tr('Plugin archive exceeds the maximum upload size'), 'error');
            return false;
        }

        return $tmpDirectory . '/' . $_FILES['plugin_archive']['name'];
    }, $tmpDirectory]);

    if ($tmpArchPath === false) {
        redirectTo('settings_plugins.php');
    }

    try {
        $arch = new PharData($tmpArchPath);
        $pluginName = $arch->getBasename();

        // Abort early if the plugin is known and is protected
        if ($pluginManager->pluginIsKnown($pluginName) && $pluginManager->pluginIsProtected($pluginName)) {
            throw new iMSCPException(tr('You cannot update a protected plugin.'));
        }

        // Check for plugin integrity (Any plugin must provide at least two files: $pluginName.php and info.php files
        foreach ([$pluginName, 'info'] as $file) {
            if (!isset($arch["$pluginName/$file.php"])) {
                throw new iMSCPException(tr("%s doens't look like an i-MSCP plugin archive.", "$pluginName/$file.php"));
            }
        }

        // Check for plugin compatibility
        $pluginManager->pluginCheckCompat($pluginName, include("phar:///$tmpArchPath/$pluginName/info.php"));

        # Backup current plugin directory in temporary directory if exists
        if ($pluginManager->pluginIsKnown($pluginName)) {
            if (!@rename("$pluginDirectory/$pluginName", "$tmpDirectory/$pluginName" . '-old')) {
                throw new iMSCPException(tr("Could not backup current `%s' plugin directory.", $pluginName));
            }
        }

        # Extract new plugin archive
        $arch->extractTo($pluginDirectory, NULL, true);
        $ret = true;
    } catch (Exception $e) {
        set_page_message($e->getMessage(), 'error');

        if (!empty($pluginName) && is_dir("$tmpDirectory/$pluginName" . '-old')) {
            // Try to restore previous plugin directory on error
            if (!@rename("$tmpDirectory/$pluginName" . '-old', "$pluginDirectory/$pluginName")) {
                set_page_message(tr('Could not restore %s plugin directory', $pluginName), 'error');
            }
        }
    }

    // Cleanup
    @unlink($tmpArchPath);
    utils_removeDir("$tmpDirectory/$pluginName");
    utils_removeDir("$tmpDirectory/$pluginName" . '-old');
    return $ret;
}

/**
 * Translate the given plugin status
 *
 * @param string $pluginStatus Plugin status to translate
 * @return string Translated plugin status
 * @throws Zend_Exception
 */
function translateStatus($pluginStatus)
{
    switch ($pluginStatus) {
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
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Plugin_Exception
 */
function generatePage($tpl, $pluginManager)
{
    $pluginList = $pluginManager->pluginGetList('Action', false);

    if (empty($pluginList)) {
        $tpl->assign('PLUGINS_BLOCK', '');
        set_page_message(tr('Plugin list is empty.'), 'static_info');
        return;
    }

    natsort($pluginList);
    $cacheFile = PERSISTENT_PATH . '/protected_plugins.php';

    foreach ($pluginList as $pluginName) {
        $pluginInfo = $pluginManager->pluginGetInfo($pluginName);
        $pluginStatus = $pluginManager->pluginGetStatus($pluginName);

        if (is_array($pluginInfo['author'])) {
            if (count($pluginInfo['author']) == 2) {
                $pluginInfo['author'] = implode(' ' . tr('and') . ' ', $pluginInfo['author']);
            } else {
                $lastEntry = array_pop($pluginInfo['author']);
                $pluginInfo['author'] = implode(', ', $pluginInfo['author']);
                $pluginInfo['author'] .= ' ' . tr('and') . ' ' . $lastEntry;
            }
        }

        $tpl->assign([
            'PLUGIN_NAME'        => tohtml($pluginName),
            'PLUGIN_DESCRIPTION' => tr($pluginInfo['desc']),
            'PLUGIN_STATUS'      => $pluginManager->pluginHasError($pluginName)
                ? tr('Unexpected error') : translateStatus($pluginStatus),
            'PLUGIN_VERSION'     => isset($pluginInfo['__nversion__'])
                ? tohtml($pluginInfo['__nversion__']) : tr('Unknown'),
            'PLUGIN_BUILD'       => (isset($pluginInfo['build']) && $pluginInfo['build'] > 0)
                ? tohtml($pluginInfo['build']) : tr('N/A'),
            'PLUGIN_AUTHOR'      => tohtml($pluginInfo['author']),
            'PLUGIN_MAILTO'      => tohtml($pluginInfo['email']),
            'PLUGIN_SITE'        => tohtml($pluginInfo['url'])
        ]);

        if ($pluginManager->pluginHasError($pluginName)) {
            $tpl->assign(
                'PLUGIN_STATUS_DETAILS',
                tr('An unexpected error occurred: %s', '<br><br>' . $pluginManager->pluginGetError($pluginName))
            );
            $tpl->parse('PLUGIN_STATUS_DETAILS_BLOCK', 'plugin_status_details_block');
            $tpl->assign([
                'PLUGIN_DEACTIVATE_LINK' => '',
                'PLUGIN_ACTIVATE_LINK'   => '',
                'PLUGIN_PROTECTED_LINK'  => ''
            ]);
        } else {
            $tpl->assign('PLUGIN_STATUS_DETAILS_BLOCK', '');

            if ($pluginManager->pluginIsProtected($pluginName)) { // Protected plugin
                $tpl->assign([
                    'PLUGIN_ACTIVATE_LINK'   => '',
                    'PLUGIN_DEACTIVATE_LINK' => '',
                    'TR_UNPROTECT_TOOLTIP'   => tr('To unprotect this plugin, you must edit the %s file', $cacheFile)
                ]);
                $tpl->parse('PLUGIN_PROTECTED_LINK', 'plugin_protected_link');
            } elseif ($pluginManager->pluginIsUninstalled($pluginName)) { // Uninstalled plugin
                $tpl->assign([
                    'PLUGIN_DEACTIVATE_LINK' => '',
                    'ACTIVATE_ACTION'        => 'install',
                    'TR_ACTIVATE_TOOLTIP'    => tr('Install this plugin'),
                    'UNINSTALL_ACTION'       => 'delete',
                    'TR_UNINSTALL_TOOLTIP'   => tr('Delete this plugin'),
                    'PLUGIN_PROTECTED_LINK'  => ''
                ]);
                $tpl->parse('PLUGIN_ACTIVATE_LINK', 'plugin_activate_link');
            } elseif ($pluginManager->pluginIsDisabled($pluginName)) { // Disabled plugin
                $tpl->assign([
                    'PLUGIN_DEACTIVATE_LINK' => '',
                    'ACTIVATE_ACTION'        => 'enable',
                    'TR_ACTIVATE_TOOLTIP'    => tr('Activate this plugin'),
                    'UNINSTALL_ACTION'       => $pluginManager->pluginIsUninstallable($pluginName)
                        ? 'uninstall' : 'delete',
                    'TR_UNINSTALL_TOOLTIP'   => $pluginManager->pluginIsUninstallable($pluginName)
                        ? tr('Uninstall this plugin') : tr('Delete this plugin'),
                    'PLUGIN_PROTECTED_LINK'  => ''
                ]);
                $tpl->parse('PLUGIN_ACTIVATE_LINK', 'plugin_activate_link');
            } elseif ($pluginManager->pluginIsEnabled($pluginName)) { // Enabled plugin
                $tpl->assign([
                    'PLUGIN_ACTIVATE_LINK'  => '',
                    'PLUGIN_PROTECTED_LINK' => ''
                ]);

                $tpl->parse('PLUGIN_DEACTIVATE_LINK', 'plugin_deactivate_link');
            } else { // Plugin with unknown status
                $tpl->assign([
                    'PLUGIN_DEACTIVATE_LINK' => '',
                    'PLUGIN_ACTIVATE_LINK'   => '',
                    'PLUGIN_PROTECTED_LINK'  => ''
                ]);
            }
        }

        $tpl->parse('PLUGIN_BLOCK', '.plugin_block');
    }
}

/**
 * Check plugin action
 *
 * @param PluginManager $pluginManager
 * @param string $pluginName Name of plugin on which the action is being performed
 * @param string $action Action Plugin action name ( install|uninstall|update|change|enable|disable|delete|protect )
 * @return bool TRUE if the plugin action is allowed, FALSE otherwise
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Plugin_Exception
 */
function checkAction($pluginManager, $pluginName, $action)
{
    if ($pluginManager->pluginIsProtected($pluginName)) {
        set_page_message(tr('Plugin %s is protected.', $pluginName), 'warning');
        return false;
    }

    $ret = true;
    $pluginStatus = $pluginManager->pluginGetStatus($pluginName);

    switch ($action) {
        case 'install':
            if (!$pluginManager->pluginIsInstallable($pluginName)
                || !in_array($pluginStatus, ['toinstall', 'uninstalled'])
            ) {
                set_page_message(tr('Plugin %s cannot be installed.', $pluginName), 'warning');
                $ret = false;
            }

            break;
        case 'uninstall':
            if (!$pluginManager->pluginIsUninstallable($pluginName) ||
                !in_array($pluginStatus, ['touninstall', 'disabled'])
            ) {
                set_page_message(tr('Plugin %s cannot be uninstalled.', $pluginName), 'warning');
                $ret = false;
            }

            break;
        case 'update':
            if ($pluginStatus != 'toupdate') {
                set_page_message(tr('Plugin %s cannot be updated.', $pluginName), 'warning');
                $ret = false;
            }

            break;
        case 'change':
            if ($pluginStatus != 'tochange') {
                set_page_message(tr('Plugin %s cannot be reconfigured.', $pluginName), 'warning');
                $ret = false;
            }

            break;
        case 'enable':
            if (!in_array($pluginStatus, ['toenable', 'disabled'])) {
                set_page_message(tr('Plugin %s cannot be activated.', $pluginName), 'warning');
                $ret = false;
            }

            break;
        case 'disable':
            if (!in_array($pluginStatus, ['todisable', 'enabled'])) {
                set_page_message(tr('Plugin %s cannot be deactivated.', $pluginName), 'warning');
                $ret = false;
            }

            break;
        case 'delete':
            if ($pluginStatus != 'todelete') {
                if ($pluginManager->pluginIsUninstallable($pluginName)) {
                    if ($pluginStatus != 'uninstalled') {
                        $ret = false;
                    }
                } elseif (!in_array($pluginStatus, ['uninstalled', 'disabled'])) {
                    $ret = false;
                }

                if (!$ret) {
                    set_page_message(tr('Plugin %s cannot be deleted.', $pluginName), 'warning');
                }
            }

            break;
        case 'protect':
            if ($pluginStatus != 'enabled') {
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
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function doAction($pluginManager, $pluginName, $action)
{
    if (!$pluginManager->pluginIsKnown($pluginName)) {
        showBadRequestErrorPage();
    }

    try {
        if (in_array($action, ['install', 'update', 'enable'])) {
            $pluginManager->pluginCheckCompat($pluginName, $pluginManager->pluginLoad($pluginName)->getInfo());
        }

        if (!checkAction($pluginManager, $pluginName, $action)) {
            return;
        }

        $ret = call_user_func([$pluginManager, 'plugin' . ucfirst($action)], $pluginName);

        if ($ret === false) {
            set_page_message(tr('An unexpected error occurred.'));
            return;
        }

        if ($ret == PluginManager::ACTION_FAILURE || $ret == PluginManager::ACTION_STOPPED) {
            $msg = $ret == PluginManager::ACTION_FAILURE ? tr('Action has failed.') : tr('Action has been stopped.');

            switch ($action) {
                case 'install':
                    $msg = tr('Could not install the %s plugin: %s', $pluginName, $msg);
                    break;
                case 'uninstall':
                    $msg = tr('Could not uninstall the %s plugin: %s', $pluginName, $msg);
                    break;
                case 'update':
                    $msg = tr('Could not update the %s plugin: %s', $pluginName, $msg);
                    break;
                case 'change':
                    $msg = tr('Could not change the %s plugin: %s', $pluginName, $msg);
                    break;
                case 'enable':
                    $msg = tr('Could not enable the %s plugin: %s', $pluginName, $msg);
                    break;
                case 'disable':
                    $msg = tr('Could not disable the %s plugin: %s', $pluginName, $msg);
                    break;
                case 'delete':
                    $msg = tr('Could not delete the %s plugin: %s', $pluginName, $msg);
                    break;
                default:
                    $msg = tr('Could not protect the %s plugin: %s', $pluginName, $msg);
            }

            set_page_message($msg, 'error');
            return;
        }

        $msg = '';

        if ($action != 'delete' && $pluginManager->pluginHasBackend($pluginName)) {
            switch ($action) {
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
            return;
        }

        switch ($action) {
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
                break;
            case 'protect':
                $msg = tr('Plugin %s protected.', $pluginName);
        }

        set_page_message($msg, 'success');
    } catch (iMSCPException $e) {
        set_page_message($e->getMessage(), 'error');
    }
}

/**
 * Do bulk action (activate|deactivate|protect)
 *
 * @param PluginManager $pluginManager
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function doBulkAction($pluginManager)
{
    $action = clean_input($_POST['bulk_actions']);

    if (!in_array($action, ['install', 'uninstall', 'enable', 'disable', 'delete', 'protect'])) {
        showBadRequestErrorPage();
    }

    if (!isset($_POST['checked']) || !is_array($_POST['checked']) || empty($_POST['checked'])) {
        set_page_message(tr('You must select at least one plugin.'), 'error');
        return;
    }

    foreach ($_POST['checked'] as $pluginName) {
        doAction($pluginManager, clean_input($pluginName), $action);
    }
}

/**
 * Update plugin list
 *
 * @param PluginManager $pluginManager
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 */
function updatePluginList($pluginManager)
{
    /** @var EventCollection $responses */
    $responses = EventManager::getInstance()->dispatch(Events::onBeforeUpdatePluginList, ['pluginManager' => $pluginManager]);
    if ($responses->isStopped()) {
        return;
    }

    $updateInfo = $pluginManager->pluginUpdateList();
    EventManager::getInstance()->dispatch(Events::onAfterUpdatePluginList, ['pluginManager' => $pluginManager]);
    set_page_message(
        tr(
            'Plugins list has been updated: %s new plugin(s) found, %s plugin(s) updated, %s plugin(s) reconfigured, and %s plugin(s) deleted.',
            $updateInfo['new'], $updateInfo['updated'], $updateInfo['changed'], $updateInfo['deleted']
        ),
        'success'
    );
}

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('admin');
EventManager::getInstance()->dispatch(Events::onAdminScriptStart);

/** @var PluginManager $pluginManager */
$pluginManager = Registry::get('pluginManager');

if (!empty($_POST) || !empty($_GET) || !empty($_FILES)) {
    if (isset($_GET['update_plugin_list'])) {
        updatePluginList($pluginManager);
    } elseif (isset($_GET['install'])) {
        doAction($pluginManager, clean_input($_GET['install']), 'install');
    } elseif (isset($_GET['uninstall'])) {
        doAction($pluginManager, clean_input($_GET['uninstall']), 'uninstall');
    } elseif (isset($_GET['enable'])) {
        doAction($pluginManager, clean_input($_GET['enable']), 'enable');
    } elseif (isset($_GET['disable'])) {
        doAction($pluginManager, clean_input($_GET['disable']), 'disable');
    } elseif (isset($_GET['delete'])) {
        doAction($pluginManager, clean_input($_GET['delete']), 'delete');
    } elseif (isset($_GET['protect'])) {
        doAction($pluginManager, clean_input($_GET['protect']), 'protect');
    } elseif (isset($_GET['retry'])) {
        $pluginName = clean_input($_GET['retry']);

        if ($pluginManager->pluginIsKnown($pluginName)) {
            switch ($pluginManager->pluginGetStatus($pluginName)) {
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
    } elseif (isset($_POST['bulk_actions'])) {
        doBulkAction($pluginManager);
    } elseif (!empty($_FILES) && uploadPlugin($pluginManager)) {
        OpcodeCacheUtils::clearAllActive(); // Force newest files to be loaded on next run
        set_page_message(tr('Plugin has been successfully uploaded.'), 'success');
        redirectTo('settings_plugins.php?update_plugin_list');
    }

    redirectTo('settings_plugins.php');
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'                      => 'shared/layouts/ui.tpl',
    'page'                        => 'admin/settings_plugins.tpl',
    'page_message'                => 'layout',
    'plugins_block'               => 'page',
    'plugin_block'                => 'plugins_block',
    'plugin_status_details_block' => 'plugin_block',
    'plugin_activate_link'        => 'plugin_block',
    'plugin_deactivate_link'      => 'plugin_block',
    'plugin_protected_link'       => 'plugin_block'
]);

EventManager::getInstance()->registerListener(Events::onGetJsTranslations, function ($event) {
    /** @var $event \iMSCP_Events_Event $translations */
    $event->getParam('translations')->core = array_merge($event->getParam('translations')->core, [
        'dataTable'     => getDataTablesPluginTranslations(false),
        'force_retry'   => tr('Force retry'),
        'close'         => tr('Close'),
        'error_details' => tr('Error details')
    ]);
});

$tpl->assign([
    'TR_PAGE_TITLE'             => tr('Admin / Settings / Plugin Management'),
    'TR_BULK_ACTIONS'           => tr('Bulk Actions'),
    'TR_PLUGIN'                 => tr('Plugin'),
    'TR_DESCRIPTION'            => tr('Description'),
    'TR_STATUS'                 => tr('Status'),
    'TR_ACTIONS'                => tr('Actions'),
    'TR_INSTALL'                => tr('Install'),
    'TR_ACTIVATE'               => tr('Activate'),
    'TR_DEACTIVATE_TOOLTIP'     => tr('Deactivate this plugin'),
    'TR_DEACTIVATE'             => tr('Deactivate'),
    'TR_UNINSTALL'              => tr('Uninstall'),
    'TR_PROTECT'                => tr('Protect'),
    'TR_DELETE'                 => tr('Delete'),
    'TR_PROTECT_TOOLTIP'        => tr('Protect this plugin'),
    'TR_VERSION'                => tr('Version'),
    'TR_BY'                     => tr('By'),
    'TR_VISIT_PLUGIN_SITE'      => tr('Visit plugin site'),
    'TR_UPDATE_PLUGIN_LIST'     => tr('Update Plugins'),
    'TR_APPLY'                  => tr('Apply'),
    'TR_PLUGIN_UPLOAD'          => tr('Plugins Upload'),
    'TR_UPLOAD'                 => tr('Upload'),
    'TR_PLUGIN_ARCHIVE'         => tr('Plugin archive'),
    'TR_PLUGIN_ARCHIVE_TOOLTIP' => tr('Only tar.gz, tar.bz2 and zip archives are accepted.'),
    'TR_PLUGIN_HINT'            => tr('Plugins hook into i-MSCP to extend its functionality with custom features. Plugins are developed independently from the core i-MSCP application by thousands of developers all over the world. You can find new plugins to install by browsing the %s.', '<a style="text-decoration: underline" href="http://i-mscp.net/filebase/index.php/Filebase/" target="_blank">' . tr('i-MSCP plugin store') . '</a></u>'),
    'TR_CLICK_FOR_MORE_DETAILS' => tr('Click here for more details')
]);

generateNavigation($tpl);
generatePage($tpl, $pluginManager);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventManager::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
