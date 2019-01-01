<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * @noinspection PhpUnhandledExceptionInspection PhpDocMissingThrowsInspection
 */

/**
 * Execute the given action on the given plugin
 *
 * @param iMSCP_Plugin_Manager $pm
 * @param string $action Action (install|uninstall|update|change|enable|disable|delete|protect)
 * @param string $plugin Plugin name
 * @return void
 */
function execPluginAction(iMSCP_Plugin_Manager $pm, $action, $plugin)
{
    call_user_func([$pm, 'plugin' . ucfirst($action)], $plugin);

    if ($action == 'protect') {
        set_page_message(tr("Plugin '%s' has been successfully protected.", $plugin), 'success');
        return;
    }

    if ($action != 'delete' && $pm->pluginHasBackend($plugin)) {
        set_page_message(tr("Action '%s' successfully scheduled for the %s plugin.", $action, $plugin), 'success');
        return;
    }

    set_page_message(tr("Action '%s' successfully executed for the %s plugin.", $action, $plugin), 'success');
}

/**
 * Generates page
 *
 * @param iMSCP_pTemplate $tpl
 * @param iMSCP_Plugin_Manager $pm
 * @return void
 */
function generatePage(iMSCP_pTemplate $tpl, iMSCP_Plugin_Manager $pm)
{
    $plugins = $pm->pluginGetList(false);

    if (empty($plugins)) {
        $tpl->assign('PLUGINS_BLOCK', '');
        set_page_message(tr('Plugin list is empty.'), 'static_info');
        return;
    }

    natsort($plugins);
    $loadedPlugins = 0;
    foreach ($plugins as $plugin) {
        try {
            $pluginInstance = $pm->pluginGet($plugin);
        } catch (iMSCP_Plugin_Exception $e) {
            set_page_message($e->getMessage(), 'static_error');
            continue;
        }

        $loadedPlugins++;
        $info =& $pluginInstance->getInfo();
        $status = $pm->pluginGetStatus($plugin);

        if (is_array($info['author'])) {
            if (count($info['author']) == 2) {
                $info['author'] = implode(' ' . tohtml(tr('and')) . ' ', $info['author']);
            } else {
                $lastEntry = array_pop($info['author']);
                $info['author'] = implode(', ', $info['author']);
                $info['author'] .= ' ' . tohtml(tr('and')) . ' ' . $lastEntry;
            }
        }

        $tpl->assign([
            'PLUGIN_NAME'        => tohtml($plugin),
            'PLUGIN_DESCRIPTION' => tohtml(tr($info['desc'])),
            'PLUGIN_STATUS'      => $pm->pluginHasError($plugin) ? tohtml(tr('Unexpected error')) : tohtml($pm->pluginTranslateStatus($status)),
            'PLUGIN_VERSION'     => tohtml($info['__nversion__']),
            'PLUGIN_BUILD'       => tohtml($info['__nbuild__']),
            'PLUGIN_AUTHOR'      => tohtml($info['author']),
            'PLUGIN_MAILTO'      => tohtml($info['email'], 'htmlAttr'),
            'PLUGIN_SITE'        => tohtml($info['url'], 'htmlAttr')
        ]);

        if ($pm->pluginHasError($plugin)) {
            $tpl->assign('PLUGIN_STATUS_DETAILS', tohtml($pm->pluginGetError($plugin)));
            $tpl->parse('PLUGIN_STATUS_DETAILS_BLOCK', 'plugin_status_details_block');
            $tpl->assign([
                'PLUGIN_DEACTIVATE_LINK' => '',
                'PLUGIN_ACTIVATE_LINK'   => '',
                'PLUGIN_PROTECTED_LINK'  => ''
            ]);
        } else {
            $tpl->assign('PLUGIN_STATUS_DETAILS_BLOCK', '');

            if ($pm->pluginIsProtected($plugin)) {
                $tpl->assign([
                    'PLUGIN_ACTIVATE_LINK'   => '',
                    'PLUGIN_DEACTIVATE_LINK' => '',
                    'TR_UNPROTECT_TOOLTIP'   => tohtml(
                        tr('To unprotect this plugin, you must edit the %s file', $pm->pluginGetPersistentDataDir() . '/protected_plugins.php'),
                        'htmlAttr'
                    )
                ]);
                $tpl->parse('PLUGIN_PROTECTED_LINK', 'plugin_protected_link');
            } elseif ($pm->pluginIsUninstalled($plugin)) {
                $tpl->assign([
                    'PLUGIN_DEACTIVATE_LINK' => '',
                    'ACTIVATE_ACTION'        => 'install',
                    'TR_ACTIVATE_TOOLTIP'    => tohtml(tr('Install this plugin'), 'htmlAttr'),
                    'UNINSTALL_ACTION'       => 'delete',
                    'TR_UNINSTALL_TOOLTIP'   => tohtml(tr('Delete this plugin'), 'htmlAttr'),
                    'PLUGIN_PROTECTED_LINK'  => ''
                ]);
                $tpl->parse('PLUGIN_ACTIVATE_LINK', 'plugin_activate_link');
            } elseif ($pm->pluginIsDisabled($plugin)) {
                $isUninstallable = $pm->pluginIsUninstallable($plugin);
                $tpl->assign([
                    'PLUGIN_DEACTIVATE_LINK' => '',
                    'ACTIVATE_ACTION'        => 'enable',
                    'TR_ACTIVATE_TOOLTIP'    => tohtml(tr('Activate this plugin'), 'htmlAttr'),
                    'UNINSTALL_ACTION'       => $isUninstallable ? 'uninstall' : 'delete',
                    'TR_UNINSTALL_TOOLTIP'   => $isUninstallable ? tohtml(tr('Uninstall this plugin'), 'htmlAttr') : tohtml(tr('Delete this plugin'), 'htmlAttr'),
                    'PLUGIN_PROTECTED_LINK'  => ''
                ]);
                $tpl->parse('PLUGIN_ACTIVATE_LINK', 'plugin_activate_link');
            } elseif ($pm->pluginIsEnabled($plugin)) {
                $tpl->assign([
                    'PLUGIN_ACTIVATE_LINK'  => '',
                    'PLUGIN_PROTECTED_LINK' => ''
                ]);
                $tpl->parse('PLUGIN_DEACTIVATE_LINK', 'plugin_deactivate_link');
            } else {
                // Plugin with unknown status
                $tpl->assign([
                    'PLUGIN_DEACTIVATE_LINK' => '',
                    'PLUGIN_ACTIVATE_LINK'   => '',
                    'PLUGIN_PROTECTED_LINK'  => ''
                ]);
            }
        }

        $tpl->parse('PLUGIN_BLOCK', '.plugin_block');
    }

    if ($loadedPlugins < 1) {
        $tpl->assign('PLUGINS_BLOCK', '');
        set_page_message(tr('Plugin list is empty.'), 'static_info');
        return;
    }
}

require 'imscp-lib.php';

check_login('admin');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

/** @var iMSCP_Plugin_Manager $pm */
$pm = iMSCP_Registry::get('pluginManager');

if (!empty($_POST) || !empty($_FILES['plugin_archive']) || !empty($_GET)) {
    try {
        if (!empty($_FILES['plugin_archive'])) {
            $pm->pluginUpload();
        } elseif (isset($_GET['sync'])) {
            $pm->pluginSyncData();
        } elseif (isset($_GET['action']) && isset($_GET['plugin'])) {
            $plugin = clean_input($_GET['plugin']);
            $action = clean_input($_GET['action']);
            if ($action == 'retry') {
                $action = $pm->pluginGuessAction($plugin);
            }
            execPluginAction($pm, $action, $plugin);
        } elseif (isset($_POST['bulk_actions'])) {
            if (empty($_POST['checked']) || !is_array($_POST['checked'])) {
                set_page_message(tr('You must select at least one plugin.'), 'error');
            } else {
                $action = clean_input($_POST['bulk_actions']);
                foreach ($_POST['checked'] as $plugin) {
                    execPluginAction($pm, $action, clean_input($plugin));
                }
            }
        } else {
            showBadRequestErrorPage();
        }
    } catch (iMSCP_Plugin_Exception $e) {
        set_page_message($e->getMessage(), $e instanceof iMSCP_Plugin_Exception_ActionStopped ? 'static_warning' : 'error');
    }

    redirectTo('settings_plugins.php');
}

$tpl = new iMSCP_pTemplate();
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
$tpl->assign([
    'TR_PAGE_TITLE'             => tohtml(tr('Admin / Settings / Plugin Management')),
    'TR_BULK_ACTIONS'           => tohtml(tr('Bulk Actions')),
    'TR_PLUGIN'                 => tohtml(tr('Plugin')),
    'TR_DESCRIPTION'            => tohtml(tr('Description')),
    'TR_STATUS'                 => tohtml(tr('Status')),
    'TR_ACTIONS'                => tohtml(tr('Actions')),
    'TR_INSTALL'                => tohtml(tr('Install')),
    'TR_ACTIVATE'               => tohtml(tr('Activate')),
    'TR_DEACTIVATE_TOOLTIP'     => tohtml(tr('Deactivate this plugin'), 'htmlAttr'),
    'TR_DEACTIVATE'             => tohtml(tr('Deactivate')),
    'TR_UNINSTALL'              => tohtml(tr('Uninstall')),
    'TR_PROTECT'                => tohtml(tr('Protect')),
    'TR_DELETE'                 => tohtml(tr('Delete')),
    'TR_PROTECT_TOOLTIP'        => tohtml(tr('Protect this plugin'), 'htmlAttr'),
    'TR_VERSION'                => tohtml(tr('Version')),
    'TR_BY'                     => tohtml(tr('By')),
    'TR_VISIT_PLUGIN_SITE'      => tohtml(tr('Visit plugin site')),
    'TR_SYNC_PLUGINS_DATA'      => tohtml(tr('Synchronize Plugins')),
    'TR_APPLY'                  => tohtml(tr('Apply')),
    'TR_PLUGIN_UPLOAD'          => tohtml(tr('Plugins Upload')),
    'TR_UPLOAD'                 => tohtml(tr('Upload', 'htmlAttr')),
    'TR_PLUGIN_ARCHIVE'         => tohtml(tr('Plugin archive')),
    'TR_PLUGIN_ARCHIVE_TOOLTIP' => tohtml(tr('Only tar.gz, tar.bz2 and zip archives are accepted.'), 'htmlAttr'),
    'TR_PLUGIN_HINT'            => tr('Plugins hook into i-MSCP to extend its functionality with custom features. Plugins are developed independently from the core i-MSCP application by thousands of developers all over the world. You can find new plugins to install by browsing the %s.', '<a style="text-decoration: underline" href="http://i-mscp.net/filebase/index.php/Filebase/" target="_blank">' . tr('i-MSCP plugin store') . '</a></u>'),
    'TR_CLICK_FOR_MORE_DETAILS' => tohtml(tr('Click here for more details'), 'htmlAttr'),
    'MAX_FILE_SIZE'             => tohtml(utils_getMaxFileUpload(), 'htmlAttr')
]);
iMSCP_Events_Aggregator::getInstance()->registerListener(iMSCP_Events::onGetJsTranslations, function ($event) {
    /** @var $event \iMSCP_Events_Event */
    $translations = $event->getParam('translations');
    $translations['core']['dataTable'] = getDataTablesPluginTranslations(false);
    $translations['core']['retry'] = tohtml(tr('Retry'));
    $translations['core']['close'] = tohtml(tr('Close'));
    $translations['core']['error_details'] = tohtml(tr('Error details'));
});
generateNavigation($tpl);
generatePage($tpl, $pm);
generatePageMessage($tpl);
$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();
unsetMessages();
