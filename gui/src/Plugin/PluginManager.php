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
 * @noinspection
 * PhpUnhandledExceptionInspection
 * PhpDocMissingThrowsInspection
 * PhpIncludeInspection
 */

# FIXME
/*
IMSCP Core:

FIXME: When an exception is raised from an event listener that listen on the onBeforeInstallPlugin, plugin status shouldn't be set to 'toinstall'. The failure should be reported only.
BTW: Maybe occurs with other event too...

Currently, status is set before the before* event propagation and reset back if the even has been stopped
Best would be to set the status once the even has been fully propagated

 */

declare(strict_types=1);

namespace iMSCP\Plugin;

use DirectoryIterator;
use Error;
use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\Json\LazyDecoder;
use iMSCP\Plugin\Filter\PluginArchive as PluginArchiveFilter;
use iMSCP\Plugin\Validate\PluginArchive as PluginArchiveValidator;
use iMSCP\Registry;
use iMSCP\Utility\OpcodeCache;
use PDO;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use Throwable;
use Zend_File_Transfer;
use Zend_File_Transfer_Adapter_Abstract;

/**
 * Class PluginManager
 * @package iMSCP\Plugin
 */
class PluginManager
{
    /**
     * @var array Events triggered by this object
     */
    protected $listenEvents = [
        Events::onBeforeSyncPluginData,
        Events::onAfterSyncPluginData,
        Events::onBeforeInstallPlugin,
        Events::onAfterInstallPlugin,
        Events::onBeforeUpdatePlugin,
        Events::onAfterUpdatePlugin,
        Events::onBeforeEnablePlugin,
        Events::onAfterEnablePlugin,
        Events::onBeforeDisablePlugin,
        Events::onAfterDisablePlugin,
        Events::onBeforeUninstallPlugin,
        Events::onAfterUninstallPlugin,
        Events::onBeforeDeletePlugin,
        Events::onAfterDeletePlugin,
        Events::onBeforeLockPlugin,
        Events::onAfterLockPlugin,
        Events::onBeforeUnlockPlugin,
        Events::onAfterUnlockPlugin
    ];

    /**
     * @var string Plugins root directory
     */
    protected $pluginsRootDir;

    /**
     * @var string Plugins persistent data directory
     */
    protected $pluginPersistentDataDir;

    /**
     * @var array[][LazyDecoder] Keys are plugin names and values are array
     *                           containing plugin data
     */
    protected $pluginData = [];

    /**
     * @var array List of protected plugins
     */
    protected $protectedPlugins;

    /**
     * @var AbstractPlugin[] Plugin instances
     */
    protected $plugins = [];

    /**
     * @var bool Whether or not a backend request should be sent
     */
    protected $backendRequest = false;

    /**
     * @var EventAggregator
     */
    protected $events;

    /** @var ContainerInterface */
    protected $container;

    /**
     * PluginManager constructor.
     *
     * @param $container ContainerInterface
     * @param $events EventAggregator
     * @return void
     */
    public function __construct(
        ContainerInterface $container,
        EventAggregator $events
    )
    {
        $this->container = $container;

        $this->events = $events;
        $this->events->addEvents(__CLASS__, $this->listenEvents);

        spl_autoload_register([$this, 'autoload']);

        $this->pluginLoadDataFromDatabase();
    }

    /**
     * Load plugin data from database.
     *
     * @return void
     */
    protected function pluginLoadDataFromDatabase(): void
    {
        $this->pluginData = [];
        $stmt = execute_query(
            '
                SELECT `plugin_name`, `plugin_status`, `plugin_error`,
                    `plugin_backend`, `plugin_lockers`
                FROM `plugin`
                ORDER BY `plugin_priority` DESC
            '
        );
        while ($plugin = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            $this->pluginData[$plugin['plugin_name']] = [
                'status'  => $plugin['plugin_status'],
                'error'   => $plugin['plugin_error'],
                'backend' => $plugin['plugin_backend'],
                'lockers' => new LazyDecoder($plugin['plugin_lockers'])
            ];
        }
    }

    /**
     * Send backend request if scheduled.
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->backendRequest) {
            send_request();
        }
    }

    /**
     * Autoloader for plugin classes.
     *
     * @param string $class Plugin class to load
     * @return void
     */
    public function autoload(string $class): void
    {
        if (strpos($class, 'iMSCP_Plugin_', 0) !== 0) {
            return;
        }

        $basename = substr($class, 13);

        @include_once $this->pluginGetRootDir()
            . DIRECTORY_SEPARATOR
            . $basename
            . DIRECTORY_SEPARATOR
            . "$basename.php";
    }

    /**
     * Get plugins root directory.
     *
     * @return string Plugin directory
     */
    public function pluginGetRootDir(): string
    {
        if (NULL === $this->pluginsRootDir) {
            $this->pluginsRootDir = utils_normalizePath(
                Registry::get('config')['PLUGINS_DIR']
            );
        }

        return $this->pluginsRootDir;
    }

    /**
     * Sets plugins root directory.
     *
     * @param string $rootDir Plugin directory path
     * @return void
     */
    public function pluginSetRootDir(string $rootDir): void
    {
        $rootDir = utils_normalizePath((string)$rootDir);

        if (!@is_dir($rootDir) || !@is_writable($rootDir)) {
            write_log(
                sprintf(
                    "Directory '%s' doesn't exist or is not writable", $rootDir
                ),
                E_USER_ERROR
            );
            throw new PluginException(
                tr("Directory '%s' doesn't exist or is not writable", $rootDir)
            );
        }

        $this->pluginsRootDir = $rootDir;
    }

    /**
     * Get plugins root directory.
     *
     * @return string
     * @deprecated Replaced by pluginGetRootDir()
     */
    public function pluginGetDirectory(): string
    {
        return $this->pluginGetRootDir();
    }

    /**
     * Sets persistent data directory.
     *
     * @param string $persistentDataDir
     * @return void
     */
    public function pluginSetPersistentDataDir(string $persistentDataDir): void
    {
        $persistentDataDir = utils_normalizePath((string)$persistentDataDir);

        if (!@is_dir($persistentDataDir) || !@is_writable($persistentDataDir)) {
            write_log(
                sprintf(
                    "Directory '%s' doesn't exist or is not writable",
                    $persistentDataDir
                ),
                E_USER_ERROR
            );
            throw new PluginException(tr(
                "Directory '%s' doesn't exist or is not writable",
                $persistentDataDir
            ));
        }

        $this->pluginPersistentDataDir = $persistentDataDir;
    }

    /**
     * Returns list of known plugins.
     *
     * @param bool $enabledOnly Flag indicating if only enabled plugins must be
     *                          returned
     * @return array An array containing plugin names
     */
    public function pluginGetList(bool $enabledOnly = true): array
    {
        $plugins = array_keys($this->pluginData);

        if (!$enabledOnly) {
            return $plugins;
        }

        $pluginData =& $this->pluginData;

        return array_filter($plugins, function ($plugin) use (&$pluginData) {
            return $pluginData[$plugin]['status'] == 'enabled';
        });
    }

    /**
     * Get list of loaded plugins.
     *
     * @return AbstractPlugin[] Array containing plugin instances
     */
    public function pluginGetLoaded(): array
    {
        return $this->plugins;
    }

    /**
     * Returns plugin info.
     *
     * @param string $plugin Plugin name
     * @return array
     * @deprecated Deprecated. Make use of the getInfo() method on plugin
     *                         instance instead.
     */
    public function pluginGetInfo($plugin): array
    {
        return $this->pluginGet($plugin)->getInfo();
    }

    /**
     * Get instance of the given plugin.
     *
     * @param string $plugin Plugin name
     * @return AbstractPlugin
     */
    public function pluginGet(string $plugin): AbstractPlugin
    {
        try {
            if (!$this->pluginIsLoaded($plugin)) {
                $class = "iMSCP\\Plugin\\$plugin\\$plugin";
                $this->plugins[$plugin] = new $class($this);
            }
        } catch (Error $e) {
            try {
                $class = "iMSCP_Plugin_$plugin";
                $this->plugins[$plugin] = new $class($this);
            } catch (Error $e) {
                write_log(
                    sprintf(
                        "Couldn't load the '%s' plugin - Plugin entry point (class) not found.",
                        $plugin
                    ),
                    E_USER_ERROR
                );

                throw new PluginException(
                    tr(
                        "Couldn't load the '%s' plugin - Plugin entry point (class) class not found.",
                        $plugin
                    ),
                    $e->getCode(),
                    $e
                );
            }
        }

        if (!is_subclass_of($this->plugins[$plugin], AbstractPlugin::class)) {
            throw new PluginException(tr(
                "The '%s' plugin class MUST extend the '%s' plugin base class",
                $plugin,
                AbstractPlugin::class
            ));
        }

        // FIXME: Why core service container should be aware of plugin services?
        //if($pluginServiceProvider = $this->plugins[$plugin]->getServiceProvider()) {
        //    // Register plugin services into application container
        //    $pluginServiceProvider->register($this->getContainer());
        //}

        // Register plugin event listeners
        $this->plugins[$plugin]->register($this->getEventManager());

        return $this->plugins[$plugin];
    }

    /**
     * Is the given plugin loaded?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin is loaded, FALSE otherwise
     */
    public function pluginIsLoaded(string $plugin): bool
    {
        return isset($this->plugins[$plugin]);
    }

    /**
     * Get service container.
     *
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get event manager.
     *
     * @return EventAggregator
     */
    public function getEventManager(): EventAggregator
    {
        return $this->events;
    }

    /**
     * Plugin upload.
     *
     * @return void
     */
    public function pluginUpload(): void
    {
        /** @var Zend_File_Transfer_Adapter_Abstract $fileTransfer */
        $fileTransfer = new Zend_File_Transfer();
        $fileTransfer->setTranslator(Registry::get('translator'));
        // We don't accept more than one plugin archive at time
        $fileTransfer->addValidator('Count', true, 1);
        // We want restrict size of accepted plugin archives
        $fileTransfer->addValidator('Size', true, utils_getMaxFileUpload());
        // Add plugin archive validator
        $fileTransfer->addValidator(
            new PluginArchiveValidator(['plugin_manager' => $this]),
            true
        );
        // Add plugin archive filter
        $fileTransfer->addFilter(new PluginArchiveFilter([
            'destination' => $this->pluginGetRootDir(),
        ]));

        if (!$fileTransfer->receive()) {
            throw new PluginException(
                implode("<br>", $fileTransfer->getMessages())
            );
        }

        $info = include $fileTransfer->getFileName() . DIRECTORY_SEPARATOR
            . 'info.php';

        $this->pluginUpdateData($info['name']);
    }

    /**
     * Update data for the given plugin, executing update/change actions when
     * needed.
     *
     * @param string $plugin
     * @return void
     */
    protected function pluginUpdateData(string $plugin)
    {
        try {
            $inst = $this->pluginGet($plugin);
        } catch (PluginException $e) {
            set_page_message($e->getMessage(), 'static_error');
            return;
        }

        $infoNew = $inst->getInfoFromFile();

        if ($this->pluginIsKnown($plugin)) {
            $infoOld =& $inst->getInfo();
        } else {
            $infoOld =& $infoNew;
        }

        $infoNew['__nversion__'] = $infoNew['version'];
        $infoNew['version'] = $infoOld['version'];
        $infoNew['__nbuild__'] = isset($infoNew['build'])
            ? $infoNew['build'] : '0000000000';
        $infoNew['build'] = isset($infoOld['build'])
            ? $infoOld['build'] : '0000000000';

        $fullVersionNew = $infoNew['__nversion__'] . '.'
            . $infoNew['__nbuild__'];
        $fullVersionOld = $infoNew['version'] . '.' . $infoNew['build'];

        $validator = new PluginArchiveValidator(
            ['plugin_manager' => $this]
        );
        if (!$validator->_isValidPlugin($infoNew)) {
            throw new PluginException(
                implode("<br>", $validator->getMessages())
            );
        }

        if (version_compare($fullVersionNew, $fullVersionOld, '<')) {
            set_page_message(
                tr(
                    "Downgrade of the '%s' plugin to version '%s' (build '%s') is forbidden.",
                    $plugin,
                    $infoNew['version'],
                    $infoOld['build'],
                    ),
                //tr('Downgrade of the %s plugin is forbidden.', $plugin),
                'static_error'
            );
            return;
        }

        if (isset($infoOld['__migration__'])) {
            $infoNew['__migration__'] = $infoOld['__migration__'];
        }

        $configNew = $inst->getConfigFromFile();

        if ($this->pluginIsKnown($plugin)) {
            $configOld =& $inst->getConfig();
        } else {
            $configOld =& $configNew;
        }

        $r = new ReflectionMethod($inst, 'install');
        $infoNew['__installable__'] =
            AbstractPlugin::class !== $r->getDeclaringClass()->getName();
        $r = new ReflectionMethod($inst, 'uninstall');
        $infoNew['__uninstallable__'] =
            AbstractPlugin::class !== $r->getDeclaringClass()->getName();
        $action = 'none';

        if ($this->pluginIsKnown($plugin)) {
            $status = $this->pluginGetStatus($plugin);
            $lockers = $this->pluginData[$plugin]['lockers'];

            // Plugin has changes, either info or config
            if (!$this->pluginCompareData($infoNew, $infoOld)
                || !$this->pluginCompareData($configNew, $configOld)
            ) {
                // Plugin is protected
                if ($this->pluginIsProtected($plugin)) {
                    set_page_message(
                        tr(
                            'The %s plugin changes were ignored as this one is protected.',
                            $plugin
                        ),
                        'static_warning'
                    );
                    return;
                }

                // No error but pending task
                if (!$this->pluginHasError($plugin) &&
                    !in_array($status, ['uninstalled', 'enabled', 'disabled'])
                ) {
                    set_page_message(
                        tr(
                            'Changes for the %s plugin were ignored as there is a pending task for this one. Please retry once the task is completed.',
                            $plugin
                        ),
                        'static_warning'
                    );
                    return;
                }

                if ($status != 'enabled' || $this->pluginHasError($plugin)) {
                    $action = 'store';
                } elseif (version_compare($fullVersionNew, $fullVersionOld, '>')) {
                    $action = 'toupdate';
                } else {
                    $action = 'tochange';
                }
            }
        } else {
            $status = $infoNew['__installable__'] ? 'uninstalled' : 'disabled';
            $lockers = new LazyDecoder('{}');
            $action = 'store';
        }

        if ($action == 'none') {
            set_page_message(
                tr("No changes were detected for the %s plugin.", $plugin),
                'success'
            );
            return;
        }

        $this->pluginStoreDataInDatabase([
            'name'        => $plugin,
            'info'        => json_encode($infoNew),
            'config'      => json_encode($configNew),
            // On plugin change/update, make sure that config_prev also contains
            // new parameters
            'config_prev' => json_encode($this->pluginIsKnown($plugin)
                ? array_merge_recursive($configNew, $configOld) : $configNew),
            'priority'    => $infoNew['priority'],
            'status'      => $status,
            'backend'     => file_exists($this->pluginGetRootDir()
                . DIRECTORY_SEPARATOR . $plugin . DIRECTORY_SEPARATOR
                . 'backend' . DIRECTORY_SEPARATOR . "$plugin.pm"
            ) ? 'yes' : 'no',
            'lockers'     => json_encode($lockers->toArray(), JSON_FORCE_OBJECT)
        ]);

        try {
            switch ($action) {
                case 'toupdate':
                    $this->pluginUpdate($plugin);
                    break;
                case 'tochange':
                    $this->pluginChange($plugin);
                    break;
                default:
                    set_page_message(
                        tr(
                            'New %s plugin data were successfully stored.',
                            $plugin
                        ),
                        'success'
                    );
                    return;
            }

            if ($this->pluginHasBackend($plugin)) {
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
        } catch (PluginException $e) {
            set_page_message($e->getMessage(), 'static_error');
        }
    }

    /**
     * Get status of the given plugin.
     *
     * @param string $plugin Plugin name
     * @return string Plugin status
     */
    public function pluginGetStatus($plugin): string
    {
        if (!$this->pluginIsKnown($plugin)) {
            throw new PluginException(tr('Unknown plugin: %s', $plugin));
        }

        return $this->pluginData[$plugin]['status'];
    }

    /**
     * is the given plugin protected?
     *
     * @param string $plugin Plugin name
     * @return bool
     */
    public function pluginIsProtected(string $plugin): bool
    {
        if (!$this->pluginIsKnown($plugin)) {
            throw new PluginException(tr('Unknown plugin: %s', $plugin));
        }

        if (NULL == $this->protectedPlugins) {
            $this->protectedPlugins = [];
            $file = $this->pluginGetPersistentDataDir()
                . DIRECTORY_SEPARATOR . 'protected_plugins.php';
            if (is_readable($file)) {
                $this->protectedPlugins = include $file;
            }
        }

        return in_array($plugin, $this->protectedPlugins);
    }

    /**
     * Get persistent data directory.
     *
     * @return string
     */
    public function pluginGetPersistentDataDir(): string
    {
        if (NULL === $this->pluginPersistentDataDir) {
            return $this->pluginPersistentDataDir = utils_normalizePath(
                PERSISTENT_PATH
            );
        }

        return $this->pluginPersistentDataDir;
    }

    /**
     * Does the given plugin has error?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin has error, FALSE otherwise
     */
    public function pluginHasError(string $plugin): bool
    {
        return NULL !== $this->pluginGetError($plugin);
    }

    /**
     * Get plugin error.
     *
     * @param null|string $plugin Plugin name
     * @return string|null Plugin error string or NULL if no error
     */
    public function pluginGetError(string $plugin): ?string
    {
        if (!$this->pluginIsKnown($plugin)) {
            throw new PluginException(tr('Unknown plugin: %s', $plugin));
        }

        return $this->pluginData[$plugin]['error'];
    }

    /**
     * Is the given plugin known?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin is known , FALSE otherwise
     */
    public function pluginIsKnown(string $plugin): bool
    {
        return isset($this->pluginData[$plugin]);
    }

    /**
     * Store plugin data in database.
     *
     * @param array $data Plugin data
     * @return void
     */
    protected function pluginStoreDataInDatabase(array $data): void
    {
        try {
            exec_query(
                '
                    INSERT INTO plugin (
                        `plugin_name`, `plugin_info`, `plugin_config`,
                        `plugin_config_prev`, `plugin_priority`,
                        `plugin_status`, `plugin_backend`, `plugin_lockers`
                    ) VALUE ( ?, ?, ?, ?, ?, ?, ?, ? ) ON DUPLICATE KEY UPDATE
                        `plugin_info` = ?, `plugin_config` = ?,
                        `plugin_config_prev` = ?, `plugin_priority` = ?,
                        `plugin_status` = ?, `plugin_backend` = ?,
                        `plugin_lockers` = ?
                ',
                [
                    // Insert data
                    $data['name'], $data['info'], $data['config'],
                    $data['config_prev'], $data['priority'], $data['status'],
                    $data['backend'], $data['lockers'],
                    // Update data
                    $data['info'], $data['config'], $data['config_prev'],
                    $data['priority'], $data['status'], $data['backend'],
                    $data['lockers']
                ]
            );
        } catch (Throwable $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Update the given plugin.
     *
     * @param string $plugin Plugin name
     * @return void
     */
    public function pluginUpdate(string $plugin): void
    {
        $pluginStatus = $this->pluginGetStatus($plugin);

        if (!in_array($pluginStatus, ['toupdate', 'enabled'])) {
            throw new PluginException(tr(
                "The '%s' action is forbidden for the %s plugin.",
                'update',
                $plugin
            ));
        }

        try {
            $inst = $this->pluginGet($plugin);
            $this->pluginSetStatus($plugin, 'toupdate');
            $this->pluginDisable($plugin, true);
            $pluginInfo = $inst->getInfo();
            $fullVersionNew = $pluginInfo['__nversion__'] . '.'
                . $pluginInfo['__nbuild__'];
            $fullVersionOld = $pluginInfo['version'] . '.'
                . $pluginInfo['build'];
            $responses = $this->events->dispatch(
                Events::onBeforeUpdatePlugin,
                [
                    'pluginManager' => $this,
                    'pluginName'    => $plugin,
                    'fromVersion'   => $fullVersionOld,
                    'toVersion'     => $fullVersionNew
                ]
            );

            if ($responses->isStopped()) {
                $this->pluginSetStatus($plugin, $pluginStatus);
                throw new PluginActionStoppedException(tr(
                    "The '%s' action has been stopped for the %s plugin.",
                    'update',
                    $plugin
                ));
            }

            $inst->update($this, $fullVersionOld, $fullVersionNew);

            if (!$this->pluginHasBackend($plugin)) {
                $pluginInfo['version'] = $pluginInfo['__nversion__'];
                $pluginInfo['build'] = $pluginInfo['__nbuild__'];
                $this->pluginUpdateInfo($plugin, $pluginInfo);
            }

            $this->events->dispatch(
                Events::onAfterUpdatePlugin,
                [
                    'pluginManager' => $this,
                    'pluginName'    => $plugin,
                    'fromVersion'   => $fullVersionOld,
                    'toVersion'     => $fullVersionNew
                ]
            );
            $this->pluginEnable($plugin, true);

            if ($this->pluginHasBackend($plugin)) {
                $this->backendRequest = true;
                return;
            }

            $this->pluginSetStatus($plugin, 'enabled');
        } catch (PluginException $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($plugin, $e->getMessage());
                write_log(
                    sprintf('Update of the %s plugin has failed.', $plugin),
                    E_USER_ERROR
                );
            }

            throw $e;
        }
    }

    /**
     * Set status for the given plugin.
     *
     * @param string $plugin Plugin name
     * @param string $status New plugin status
     * @return void
     */
    public function pluginSetStatus(string $plugin, string $status): void
    {
        if ($status === $this->pluginGetStatus($plugin)
            && NULL === $this->pluginGetError($plugin)
        ) {
            return;
        }

        try {
            exec_query(
                '
                    UPDATE `plugin`
                    SET `plugin_status` = ?, `plugin_error` = NULL
                    WHERE `plugin_name` = ?
                ',
                [$status, $plugin]
            );
            $this->pluginData[$plugin]['status'] = $status;
        } catch (Throwable $e) {
            throw new PluginException(
                $e->getMessage(), $e->getCode(), $e
            );
        }
    }

    /**
     * Disable the given plugin.
     *
     * @param string $plugin Plugin name
     * @param bool $isSubAction Flag indicating whether or not this action is
     *                          called in context of the install update or
     *                          change action
     * @return void
     */
    public function pluginDisable(
        string $plugin, bool $isSubAction = false
    ): void
    {
        $pluginStatus = $this->pluginGetStatus($plugin);

        if (!$isSubAction
            && !in_array($pluginStatus, ['todisable', 'enabled'])
        ) {
            throw new PluginException(tr(
                "The '%s' action is forbidden for the %s plugin.",
                'disable',
                $plugin
            ));
        }

        try {
            $inst = $this->pluginGet($plugin);

            if (!$isSubAction) {
                $this->pluginSetStatus($plugin, 'todisable');
            }

            $responses = $this->events->dispatch(
                Events::onBeforeDisablePlugin,
                [
                    'pluginManager' => $this,
                    'pluginName'    => $plugin
                ]
            );

            if (!$isSubAction && $responses->isStopped()) {
                $this->pluginSetStatus($plugin, $pluginStatus);
                throw new PluginActionStoppedException(tr(
                    "The '%s' action has been stopped for the %s plugin.",
                    'disable',
                    $plugin
                ));
            }

            $inst->disable($this);
            $this->events->dispatch(Events::onAfterDisablePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);

            if ($this->pluginHasBackend($plugin)) {
                $this->backendRequest = true;
                return;
            }

            if (!$isSubAction) {
                $this->pluginSetStatus($plugin, 'disabled');
            }
        } catch (PluginException $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($plugin, $e->getMessage());
                write_log(
                    sprintf(
                        'Deactivation of the %s plugin has failed.', $plugin
                    ),
                    E_USER_ERROR
                );
            }

            throw $e;
        }
    }

    /**
     * Does the given plugin provides a backend side?
     *
     * @param string $plugin Plugin name
     * @return boolean TRUE if the given plugin provide backend part, FALSE
     *                 otherwise
     */
    public function pluginHasBackend(string $plugin): bool
    {
        if (!$this->pluginIsKnown($plugin)) {
            throw new PluginException(tr('Unknown plugin: %s', $plugin));
        }

        return $this->pluginData[$plugin]['backend'] == 'yes';
    }

    /**
     * Set error for the given plugin.
     *
     * @param string $plugin Plugin name
     * @param null|string $error Plugin error string or NULL if no error
     * @return void
     */
    public function pluginSetError(string $plugin, ?string $error): void
    {
        if ($error === $this->pluginGetError($plugin)) {
            return;
        }

        try {
            exec_query(
                '
                    UPDATE `plugin`
                    SET `plugin_error` = ?
                    WHERE `plugin_name` = ?
                ',
                [$error, $plugin]
            );
            $this->pluginData[$plugin]['error'] = $error;
        } catch (Throwable $e) {
            throw new PluginException(
                $e->getMessage(), $e->getCode(), $e
            );
        }
    }

    /**
     * Update plugin info.
     *
     * @param string $plugin Plugin Name
     * @param array $infoNew New plugin info
     * @return void
     */
    public function pluginUpdateInfo(string $plugin, array $infoNew): void
    {
        $oldInfo =& $this->pluginGet($plugin)->getInfo();

        if ($this->pluginCompareData($infoNew, $oldInfo)) {
            return;
        }

        try {
            exec_query(
                'UPDATE `plugin` SET `plugin_info` = ? WHERE `plugin_name` = ?',
                [json_encode($infoNew), $plugin]
            );
            $oldInfo = $infoNew;
        } catch (Throwable $e) {
            throw new PluginException(
                $e->getMessage(), $e->getCode(), $e
            );
        }
    }

    /**
     * Compare the given plugin data.
     *
     * @param array $aData
     * @param array $bData
     * @return bool TRUE if data are identical (order doesn't matter), FALSE
     *              otherwise
     */
    protected function pluginCompareData(array &$aData, array &$bData): bool
    {
        if (count($aData) != count($bData)) {
            return false;
        }

        foreach ($aData as $k => $v) {
            if (!array_key_exists($k, $bData)) {
                return false;
            }

            if (is_array($v) && is_array($bData[$k])) {
                if (!$this->pluginCompareData($v, $bData[$k])) {
                    return false;
                }
            } elseif ($v !== $bData[$k]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Enable the given plugin.
     *
     * @param string $plugin Plugin name
     * @param bool $isSubAction Flag indicating whether or not this action is
     *                          called in context of the install update or
     *                          change action
     * @return void
     */
    public function pluginEnable(
        string $plugin, bool $isSubAction = false
    ): void
    {
        $pluginStatus = $this->pluginGetStatus($plugin);

        if (!$isSubAction
            && !in_array($pluginStatus, ['toenable', 'disabled'])
        ) {
            throw new PluginException(tr(
                "The '%s' action is forbidden for the %s plugin.",
                'enable',
                $plugin
            ));
        }

        try {
            $inst = $this->pluginGet($plugin);

            if (!$isSubAction) {
                if ($this->pluginRequireUpdate($plugin)) {
                    $this->pluginSetStatus($plugin, 'toupdate');
                    $this->pluginUpdate($plugin);
                    return;
                }

                if ($this->pluginRequireChange($plugin)) {
                    $this->pluginSetStatus($plugin, 'tochange');
                    $this->pluginChange($plugin);
                    return;
                }
            }

            if (!$isSubAction) {
                $this->pluginSetStatus($plugin, 'toenable');
            }

            $responses = $this->events->dispatch(
                Events::onBeforeEnablePlugin,
                [
                    'pluginManager' => $this,
                    'pluginName'    => $plugin
                ]
            );

            if (!$isSubAction && $responses->isStopped()) {
                $this->pluginSetStatus($plugin, $pluginStatus);
                throw new PluginActionStoppedException(tr(
                    "The '%s' action has been stopped for the %s plugin.",
                    'enable',
                    $plugin
                ));
            }

            $inst->enable($this);
            $this->events->dispatch(Events::onAfterEnablePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);

            if ($this->pluginHasBackend($plugin)) {
                $this->backendRequest = true;
            } elseif (!$isSubAction) {
                $this->pluginSetStatus($plugin, 'enabled');
            }
        } catch (PluginException $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($plugin, $e->getMessage());
                write_log(
                    sprintf('Activation of the %s plugin has failed.', $plugin),
                    E_USER_ERROR
                );
            }

            throw $e;
        }
    }

    /**
     * Does the given plugin requires update.
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin requires change, FALSE otherwise
     */
    protected function pluginRequireUpdate(string $plugin): bool
    {
        $info = $this->pluginGet($plugin)->getInfo();

        return version_compare(
            $info['nversion'] . '.' . $info['nbuild'],
            $info['version'] . '.' . $info['build'],
            '>'
        );
    }

    /**
     * Does the given plugin requires change.
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin requires change, FALSE otherwise
     */
    protected function pluginRequireChange(string $plugin): bool
    {
        $inst = $this->pluginGet($plugin);

        return !$this->pluginCompareData(
            $inst->getConfig(), $inst->getConfigPrev()
        );
    }

    /**
     * Change (reconfigure) the given plugin.
     *
     * @param string $plugin Plugin name
     * @param bool $isSubAction Flag indicating whether or not this action is
     *                          called in context of the update action
     * @return void
     */
    public function pluginChange(
        string $plugin, bool $isSubAction = false
    ): void
    {
        $pluginStatus = $this->pluginGetStatus($plugin);

        if (!$isSubAction
            && !in_array($pluginStatus, ['tochange', 'enabled'])
        ) {
            throw new PluginException(tr(
                "The '%s' action is forbidden for the %s plugin.",
                'change', $plugin
            ));
        }

        try {
            $this->pluginSetStatus($plugin, 'tochange');

            if (!$isSubAction) {
                $this->pluginDisable($plugin, true);
                $this->pluginEnable($plugin, true);
            }

            if ($this->pluginHasBackend($plugin)) {
                $this->backendRequest = true;
                return;
            }

            try {
                exec_query(
                    '
                        UPDATE `plugin`
                        SET `plugin_config_prev` = `plugin_config`
                        WHERE `plugin_name` = ?
                     ',
                    $plugin
                );
                $this->pluginSetStatus($plugin, 'enabled');
            } catch (Throwable $e) {
                throw new PluginException($e->getMessage(), $e->getCode(), $e);
            }
        } catch (PluginException $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($plugin, $e->getMessage());
                write_log(
                    sprintf(
                        'Reconfiguration of the %s plugin has failed.', $plugin
                    ),
                    E_USER_ERROR
                );
            }

            throw $e;
        }
    }

    /**
     * Lock the given plugin.
     *
     * @param string $plugin Plugin name
     * @param string $locker Locker name
     * @return void
     */
    public function pluginLock(string $plugin, string $locker): void
    {
        if ($this->pluginIsLocked($plugin, $locker)) {
            return;
        }

        try {
            $responses = $this->events->dispatch(
                Events::onBeforeLockPlugin,
                [
                    'pluginName'   => $plugin,
                    'pluginLocker' => $locker
                ]
            );

            if ($responses->isStopped()) {
                return;
            }

            $lockers = $this->pluginData[$plugin]['lockers'];
            $lockers[$locker] = 1;
            exec_query(
                '
                    UPDATE `plugin`
                    SET `plugin_lockers` = ?
                    WHERE `plugin_name` = ?
                ',
                [json_encode($lockers->toArray(), JSON_FORCE_OBJECT), $plugin]
            );
            $this->events->dispatch(
                Events::onAfterLockPlugin,
                [
                    'pluginName'   => $plugin,
                    'pluginLocker' => $locker
                ]
            );
        } catch (Throwable $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * is the given plugin locked?
     *
     * @param string $plugin Plugin name
     * @param string|null $locker OPTIONAL Locker name (default any locker)
     * @return bool TRUE if the given plugin is locked, false otherwise
     */
    public function pluginIsLocked(string $plugin, ? string $locker = NULL)
    {
        if (!$this->pluginIsKnown($plugin)) {
            throw new PluginException(tr('Unknown plugin: %s', $plugin));
        }

        if (NULL === $locker) {
            return count($this->pluginData[$plugin]['lockers']) > 0;
        }

        return isset($this->pluginData[$plugin]['lockers'][$locker]);
    }

    /**
     * Unlock the given plugin.
     *
     * @param string $plugin Plugin name
     * @param string $locker Locker name
     * @return void
     */
    public function pluginUnlock(string $plugin, string $locker): void
    {
        if (!$this->pluginIsLocked($plugin, $locker)) {
            return;
        }

        try {
            $responses = $this->events->dispatch(
                Events::onBeforeUnlockPlugin,
                [
                    'pluginName'   => $plugin,
                    'pluginLocker' => $locker
                ]
            );

            if ($responses->isStopped()) {
                return;
            }

            /** @var LazyDecoder $lockers */
            $lockers = $this->pluginData[$plugin]['lockers'];
            unset($lockers[$locker]);
            exec_query(
                '
                    UPDATE `plugin`
                    SET `plugin_lockers` = ?
                    WHERE `plugin_name` = ?
                ',
                [json_encode($lockers->toArray(), JSON_FORCE_OBJECT), $plugin]
            );
            $this->events->dispatch(Events::onAfterUnlockPlugin, [
                'pluginName'   => $plugin,
                'pluginLocker' => $locker
            ]);
        } catch (Throwable $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Is the given plugin is installed?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin is installed FALSE otherwise
     */
    public function pluginIsInstalled(string $plugin): bool
    {
        return !in_array(
            $this->pluginGetStatus($plugin), ['toinstall', 'uninstalled']
        );
    }

    /**
     * Install the given plugin.
     *
     * @param string $plugin Plugin name
     * @return void
     */
    public function pluginInstall(string $plugin): void
    {
        $pluginStatus = $this->pluginGetStatus($plugin);

        if (!in_array($pluginStatus, ['toinstall', 'uninstalled'])) {
            throw new PluginException(tr(
                "The '%s' action is forbidden for the %s plugin.", 'install',
                $plugin
            ));
        }

        try {
            $inst = $this->pluginGet($plugin);
            $this->pluginSetStatus($plugin, 'toinstall');
            $responses = $this->events->dispatch(
                Events::onBeforeInstallPlugin,
                [
                    'pluginManager' => $this,
                    'pluginName'    => $plugin
                ]
            );

            if ($responses->isStopped()) {
                $this->pluginSetStatus($plugin, $pluginStatus);
                throw new PluginActionStoppedException(tr(
                    "The '%s' action has been stopped for the %s plugin.",
                    'install',
                    $plugin
                ));
            }

            $inst->install($this);
            $this->events->dispatch(Events::onAfterInstallPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);
            $this->pluginEnable($plugin, true);

            if ($this->pluginHasBackend($plugin)) {
                $this->backendRequest = true;
                return;
            }

            $this->pluginSetStatus($plugin, 'enabled');
        } catch (PluginException $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($plugin, $e->getMessage());
                write_log(
                    sprintf(
                        'Installation of the %s plugin has failed.', $plugin
                    ),
                    E_USER_ERROR
                );
            }

            throw $e;
        }
    }

    /**
     * Is the given plugin uninstalled?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin is uninstalled FALSE otherwise
     */
    public function pluginIsUninstalled(string $plugin): bool
    {
        return $this->pluginGetStatus($plugin) == 'uninstalled';
    }

    /**
     * Uninstall the given plugin.
     *
     * @param string $plugin Plugin name
     * @return void
     */
    public function pluginUninstall(string $plugin): void
    {
        $pluginStatus = $this->pluginGetStatus($plugin);

        if (!in_array($pluginStatus, ['touninstall', 'disabled'])
            || !$this->pluginIsUninstallable($plugin)
        ) {
            throw new PluginException(tr(
                "The '%s' action is forbidden for the %s plugin.",
                'uninstall',
                $plugin
            ));
        }

        try {
            $inst = $this->pluginGet($plugin);
            $this->pluginSetStatus($plugin, 'touninstall');
            $responses = $this->events->dispatch(
                Events::onBeforeUninstallPlugin,
                [
                    'pluginManager' => $this,
                    'pluginName'    => $plugin
                ]
            );

            if ($responses->isStopped()) {
                $this->pluginSetStatus($plugin, $pluginStatus);
                throw new PluginActionStoppedException(tr(
                    "The '%s' action has been stopped for the %s plugin.",
                    'uninstall',
                    $plugin
                ));
            }

            $inst->uninstall($this);
            $this->events->dispatch(Events::onAfterUninstallPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);

            if ($this->pluginHasBackend($plugin)) {
                $this->backendRequest = true;
                return;
            }

            $this->pluginSetStatus(
                $plugin,
                $this->pluginIsInstallable($plugin) ? 'uninstalled' : 'disabled'
            );
        } catch (PluginException $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($plugin, $e->getMessage());
                write_log(
                    sprintf(
                        'Uninstallation of the %s plugin has failed.', $plugin
                    ),
                    E_USER_ERROR
                );
            }

            throw $e;
        }
    }

    /**
     * Is the given plugin uninstallable?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin can be uninstalled, FALSE otherwise
     */
    public function pluginIsUninstallable(string $plugin): bool
    {
        $inst = $this->pluginGet($plugin);
        $info = $inst->getInfo();

        if (isset($info['__uninstallable__'])) {
            return $info['__uninstallable__'];
        }

        $r = new ReflectionMethod($inst, 'uninstall');

        return AbstractPlugin::class !== $r->getDeclaringClass()->getName();
    }

    /**
     * Is the given plugin installable?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin is installable, FALSE otherwise
     */
    public function pluginIsInstallable(string $plugin): bool
    {
        $inst = $this->pluginGet($plugin);
        $info = $inst->getInfo();

        if (isset($info['__installable__'])) {
            return $info['__installable__'];
        }

        $r = new ReflectionMethod($inst, 'install');

        return AbstractPlugin::class !== $r->getDeclaringClass()->getName();
    }

    /**
     * Is the given plugin disabled?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given is disabled, FALSE otherwise
     */
    public function pluginIsDisabled(string $plugin): bool
    {
        return $this->pluginGetStatus($plugin) == 'disabled';
    }

    /**
     * Delete the given plugin.
     *
     * @param string $plugin Plugin name
     * @return void
     */
    public function pluginDelete(string $plugin): void
    {
        $pluginStatus = $this->pluginGetStatus($plugin);

        if (!in_array($pluginStatus, ['todelete', 'uninstalled', 'disabled'])
            || $this->pluginIsLocked($plugin)
        ) {
            throw new PluginException(tr(
                "The '%s' action is forbidden for the %s plugin.",
                'delete',
                $plugin
            ));
        }

        try {
            $inst = $this->pluginGet($plugin);
            $this->pluginSetStatus($plugin, 'todelete');
            $responses = $this->events->dispatch(
                Events::onBeforeDeletePlugin,
                [
                    'pluginManager' => $this,
                    'pluginName'    => $plugin
                ]
            );

            if ($responses->isStopped()) {
                $this->pluginSetStatus($plugin, $pluginStatus);
                throw new PluginActionStoppedException(tr(
                    "The '%s' action has been stopped for the %s plugin.",
                    'delete',
                    $plugin
                ));
            }

            $inst->delete($this);

            if (!utils_removeDir(utils_normalizePath(
                $this->pluginsRootDir . DIRECTORY_SEPARATOR . $plugin
            ))) {
                throw new PluginException(tr(
                    "Couldn't delete the %s plugin. You should fix the file permissions and try again.",
                    $plugin
                ));
            }

            try {
                exec_query('DELETE FROM `plugin` WHERE `plugin_name` = ?', [
                    $plugin
                ]);
            } catch (Throwable $e) {
                throw new PluginException($e->getMessage(), $e->getCode(), $e);
            }

            $this->events->dispatch(Events::onAfterDeletePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);
        } catch (PluginException $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                $this->pluginSetError($plugin, $e->getMessage());
                write_log(
                    sprintf('Deletion of the %s plugin has failed', $plugin),
                    E_USER_ERROR
                );
            }

            throw $e;
        }
    }

    /**
     * Protect the given plugin.
     *
     * @param string $plugin Name of the plugin to protect
     * @return void
     */
    public function pluginProtect(string $plugin): void
    {
        if (!$this->pluginIsEnabled($plugin)
            || $this->pluginIsProtected($plugin)
        ) {
            throw new PluginException(tr(
                "The '%s' action is forbidden for the %s plugin.",
                'protect',
                $plugin
            ));
        }

        try {
            $responses = $this->events->dispatch(
                Events::onBeforeProtectPlugin,
                [
                    'pluginManager' => $this,
                    'pluginName'    => $plugin
                ]
            );

            if ($responses->isStopped()) {
                throw new PluginActionStoppedException(tr(
                    "The '%s' action has been stopped for the %s plugin.",
                    'protect',
                    $plugin
                ));
            }

            $this->protectedPlugins[] = $plugin;

            $file = utils_normalizePath(
                $this->pluginGetPersistentDataDir()
                . DIRECTORY_SEPARATOR
                . 'protected_plugins.php'
            );
            $content = sprintf(
                "<?php\n/**\n * Protected plugin list\n * Auto-generated on %s\n */\n\n",
                date('Y-m-d H:i:s', time())
            );
            $content .= "return " .
                var_export($this->protectedPlugins, true) . ";\n";

            if (@file_put_contents($file, $content, LOCK_EX) === false) {
                write_log(sprintf("Couldn't write the %s file.", $file));
                throw new PluginException(tr(
                    "Couldn't write the %s file.", $file
                ));
            }

            OpcodeCache::clearAllActive($file);

            $this->events->dispatch(Events::onAfterProtectPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);
        } catch (PluginException $e) {
            if (!($e instanceof PluginActionStoppedException)) {
                write_log(
                    sprintf('Protection of the %s plugin has failed', $plugin),
                    E_USER_ERROR
                );
            }

            throw $e;
        }
    }

    /**
     * Is the given plugin enabled?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin is enabled, FALSE otherwise
     */
    public function pluginIsEnabled(string $plugin): bool
    {
        return $this->pluginGetStatus($plugin) == 'enabled';
    }

    /**
     * Check plugin compatibility.
     *
     * @param string $plugin Plugin name
     * @param array $infoNew New plugin info
     * @return void
     */
    public function pluginCheckCompat(string $plugin, array $infoNew): void
    {
        if (!isset($infoNew['require_api'])
            || version_compare(
                $this->pluginGetApiVersion(), $infoNew['require_api'], '<'
            )
        ) {
            throw new PluginException(tr(
                'The %s plugin version %s (build %d) is not compatible with your i-MSCP version.',
                $plugin,
                $infoNew['version'],
                $infoNew['build']
            ));
        }

        if (!$this->pluginIsKnown($plugin)) {
            return;
        }

        $infoOld =& $this->pluginGet($plugin)->getInfo();
        if (version_compare(
            $infoOld['version'] . '.' . $infoOld['build'],
            $infoNew['version'] . '.' . $infoNew['build'],
            '>'
        )) {
            throw new PluginException(tr(
                "Downgrade of the '%s' plugin to version '%s' (build '%s') is forbidden.",
                $plugin,
                $infoNew['version'],
                $infoNew['build']
            ));
        }
    }

    /**
     * Returns plugin API version.
     *
     * @return string Plugin API version
     */
    public function pluginGetApiVersion(): string
    {
        return Registry::get('config')['PluginApi'];
    }

    /**
     * Synchronize all plugins data, executing update/change actions when
     * needed.
     *
     * @return void
     */
    public function pluginSyncData(): void
    {
        $responses = $this->events->dispatch(
            Events::onBeforeSyncPluginData, ['pluginManager' => $this]
        );
        if ($responses->isStopped()) {
            return;
        }

        foreach (new DirectoryIterator($this->pluginGetRootDir()) as $dentry) {
            if ($dentry->isDot() || !$dentry->isDir()) {
                continue;
            }

            $info = include $dentry->getPathname()
                . DIRECTORY_SEPARATOR
                . 'info.php';

            $this->pluginUpdateData($info['name']);
        }

        $this->events->dispatch(
            Events::onAfterSyncPluginData, ['pluginManager' => $this]
        );
    }

    /**
     * Guess action to execute for the given plugin according its current
     * status.
     *
     * @param string $plugin Plugin name
     * @return string Action to be executed for the given plugin
     */
    public function pluginGuessAction(string $plugin): string
    {
        $status = $this->pluginGetStatus($plugin);

        switch ($status) {
            case 'uninstalled':
                return 'install';
            case 'toinstall':
                return 'install';
            case 'touninstall':
                return 'uninstall';
            case 'toupdate':
                return 'update';
            case 'enabled':
                return 'disable';
            case 'disabled':
                return 'enable';
            case 'tochange':
                return 'change';
            default:
                throw new PluginException(tr(
                    "Unknown status '%s' for the %s plugin", $status, $plugin
                ));
        }
    }

    /**
     * Translate the given plugin status.
     *
     * @param string $status
     * @return string
     */
    public function pluginTranslateStatus(string $status): string
    {
        switch ($status) {
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
}
