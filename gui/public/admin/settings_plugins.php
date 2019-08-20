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
 */

/**
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 * PhpIncludeInspection
 */

use iMSCP\Event\EventAggregator;
use iMSCP\Event\EventDescription;
use iMSCP\Event\Events;
use iMSCP\Plugin\PluginActionStoppedException;
use iMSCP\Plugin\PluginException;
use iMSCP\Plugin\PluginManager;
use iMSCP\Registry;
use iMSCP\TemplateEngine;

/**
 * Execute the given action on the given plugin
 *
 * @param PluginManager $pm
 * @param string $action Action (install|uninstall|update|change|enable|disable|
 *                       delete|protect)
 * @param string $plugin Plugin name
 * @return void
 */
function execPluginAction(PluginManager $pm, $action, $plugin)
{
    call_user_func([$pm, 'plugin' . ucfirst($action)], $plugin);

    if ($action == 'protect') {
        set_page_message(
            tr("Plugin '%s' has been successfully protected.", $plugin),
            'success'
        );
        return;
    }

    if ($action != 'delete' && $pm->pluginHasBackend($plugin)) {
        set_page_message(
            tr(
                "Action '%s' successfully scheduled for the %s plugin.",
                $action,
                $plugin
            ),
            'success'
        );
        return;
    }

    set_page_message(
        tr(
            "Action '%s' successfully executed for the %s plugin.",
            $action,
            $plugin
        ),
        'success'
    );
}

/**
 * Generates page
 *
 * @param TemplateEngine $tpl
 * @param PluginManager $pm
 * @return void
 */
function generatePage(TemplateEngine $tpl, PluginManager $pm)
{
    $plugins = $pm->pluginGetList(false);

    if (empty($plugins)) {
        $tpl->assign('PLUGINS_BLOCK', '');
        return;
    }

    natsort($plugins);
    $loadedPlugins = 0;
    foreach ($plugins as $plugin) {
        try {
            $pluginInstance = $pm->pluginGet($plugin);
        } catch (PluginException $e) {
            set_page_message($e->getMessage(), 'static_error');
            continue;
        }

        $loadedPlugins++;
        $info =& $pluginInstance->getInfo();
        $status = $pm->pluginGetStatus($plugin);

        if (is_array($info['author'])) {
            if (count($info['author']) == 2) {
                $info['author'] = implode(
                    ' ' . tohtml(tr('and')) . ' ', $info['author']
                );
            } else {
                $lastEntry = array_pop($info['author']);
                $info['author'] = implode(', ', $info['author']);
                $info['author'] .= ' ' . tohtml(tr('and')) . ' ' . $lastEntry;
            }
        }

        $tpl->assign([
            'PLUGIN_NAME'        => tohtml($plugin),
            'PLUGIN_DESCRIPTION' => tohtml(tr($info['desc'])),
            'PLUGIN_STATUS'      => $pm->pluginHasError($plugin)
                ? tohtml(tr('Unexpected error'))
                : tohtml($pm->pluginTranslateStatus($status)),
            'PLUGIN_VERSION'     => tohtml($info['__nversion__']),
            'PLUGIN_BUILD'       => tohtml($info['__nbuild__']),
            'PLUGIN_AUTHOR'      => tohtml($info['author']),
            'PLUGIN_MAILTO'      => tohtml($info['email'], 'htmlAttr'),
            'PLUGIN_SITE'        => tohtml($info['url'], 'htmlAttr')
        ]);

        if ($pm->pluginHasError($plugin)) {
            $tpl->assign(
                'PLUGIN_STATUS_DETAILS', tohtml($pm->pluginGetError($plugin))
            );
            $tpl->parse(
                'PLUGIN_STATUS_DETAILS_BLOCK', 'plugin_status_details_block'
            );
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
                        tr('To unprotect this plugin, you must edit the %s file',
                            $pm->pluginGetPersistentDataDir()
                            . '/protected_plugins.php'
                        ),
                        'htmlAttr'
                    )
                ]);
                $tpl->parse('PLUGIN_PROTECTED_LINK', 'plugin_protected_link');
            } elseif ($pm->pluginIsUninstalled($plugin)) {
                $tpl->assign([
                    'PLUGIN_DEACTIVATE_LINK' => '',
                    'ACTIVATE_ACTION'        => 'install',
                    'TR_ACTIVATE_TOOLTIP'    => tohtml(
                        tr('Install this plugin'), 'htmlAttr'
                    ),
                    'UNINSTALL_ACTION'       => 'delete',
                    'TR_UNINSTALL_TOOLTIP'   => tohtml(
                        tr('Delete this plugin'), 'htmlAttr'
                    ),
                    'PLUGIN_PROTECTED_LINK'  => ''
                ]);
                $tpl->parse('PLUGIN_ACTIVATE_LINK', 'plugin_activate_link');
            } elseif ($pm->pluginIsDisabled($plugin)) {
                $isUninstallable = $pm->pluginIsUninstallable($plugin);
                $tpl->assign([
                    'PLUGIN_DEACTIVATE_LINK' => '',
                    'ACTIVATE_ACTION'        => 'enable',
                    'TR_ACTIVATE_TOOLTIP'    => tohtml(
                        tr('Activate this plugin'), 'htmlAttr'
                    ),
                    'UNINSTALL_ACTION'       => $isUninstallable
                        ? 'uninstall' : 'delete',
                    'TR_UNINSTALL_TOOLTIP'   => $isUninstallable
                        ? tohtml(tr('Uninstall this plugin'), 'htmlAttr')
                        : tohtml(tr('Delete this plugin'), 'htmlAttr'),
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
        return;
    }

    $tpl->assign('PLUGINS_LIST_EMPTY_BLOCK', '');
}

require 'imscp-lib.php';

check_login('admin');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptStart);

/** @var PluginManager $pm */
$pm = Registry::get('pluginManager');

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
                set_page_message(
                    tr('You must select at least one plugin.'), 'error'
                );
            } else {
                $action = clean_input($_POST['bulk_actions']);
                foreach ($_POST['checked'] as $plugin) {
                    execPluginAction($pm, $action, clean_input($plugin));
                }
            }
        } else {
            showBadRequestErrorPage();
        }
    } catch (PluginException $e) {
        set_page_message(
            $e->getMessage(),
            $e instanceof PluginActionStoppedException
                ? 'static_warning' : 'error'
        );
    }

    redirectTo('settings_plugins.php');
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'                      => 'shared/layouts/ui.tpl',
    'page'                        => 'admin/settings_plugins.phtml',
    'page_message'                => 'layout',
    'plugins_list_empty_block'    => 'page',
    'plugins_block'               => 'page',
    'plugin_block'                => 'plugins_block',
    'plugin_status_details_block' => 'plugin_block',
    'plugin_activate_link'        => 'plugin_block',
    'plugin_deactivate_link'      => 'plugin_block',
    'plugin_protected_link'       => 'plugin_block'
]);
$tpl->assign([
    'TR_PAGE_TITLE'         => tohtml(
        tr('Admin / Settings / Plugin Management')
    ),
    'TR_DEACTIVATE_TOOLTIP' => tohtml(tr('Deactivate this plugin'), 'htmlAttr'),
]);
EventAggregator::getInstance()->registerListener(
    Events::onGetJsTranslations, function (EventDescription $e) {
    $tr = $e->getParam('translations');
    $tr['core']['dataTable'] = getDataTablesPluginTranslations(false);
    $tr['core']['retry'] = tohtml(tr('Retry'));
    $tr['core']['close'] = tohtml(tr('Close'));
    $tr['core']['error_details'] = tohtml(tr('Error details'));
}
);
generateNavigation($tpl);
generatePage($tpl, $pm);
generatePageMessage($tpl);
$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();
unsetMessages();
