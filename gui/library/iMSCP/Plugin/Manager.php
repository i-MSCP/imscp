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

use iMSCP\Json\LazyDecoder;
use iMSCP\Plugin\EventsManager as EventsManager;
use iMSCP_Events as Events;
use iMSCP_Events_Manager_Interface as EventManagerInterface;
use iMSCP_Events_Manager_Interface as EventsManagerInterface;
use iMSCP_Exception_Database as DatabaseException;
use iMSCP_Plugin as Plugin;
use iMSCP_Plugin_Action as ActionPlugin;
use iMSCP_Plugin_Exception as PluginException;
use iMSCP_Registry as Registry;
use Zend_Cache_Core as Cache;

/**
 * Plugin Manager class
 */
class iMSCP_Plugin_Manager
{
    /**
     * @const int Action success
     */
    const ACTION_SUCCESS = 1;

    /**
     * @const int Action failure
     */
    const ACTION_FAILURE = 0;

    /**
     * @const int Action stopped
     */
    const ACTION_STOPPED = -1;

    /**
     * @var string Plugins directory
     */
    protected $pluginsDirectory;

    /**
     * @var array[][LazyDecoder] Keys are plugin names and values are array containing plugin data
     */
    protected $pluginData = [];

    /**
     * @var array List of protected plugins
     */
    protected $protectedPlugins = [];

    /**
     * @var bool Whether or not list of protected plugin is loaded
     */
    protected $isLoadedProtectedPluginsList = false;

    /**
     * @var array Plugin by type
     */
    protected $pluginsByType = [];

    /**
     * @var Plugin[]|ActionPlugin[] Array containing all loaded plugins
     */
    protected $loadedPlugins = [];

    /**
     * @var bool Whether or not a backend request should be sent
     */
    protected $backendRequest = false;

    /**
     * @var EventsManagerInterface
     */
    protected $eventsManager;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * Constructor
     *
     * @param string $pluginRootDir Plugins root directory
     * @param EventManagerInterface $em
     * @param Cache $cache
     * @throws PluginException
     */
    public function __construct($pluginRootDir, EventManagerInterface $em, Cache $cache)
    {
        if (!@is_dir($pluginRootDir)) {
            write_log(sprintf('Plugin Manager: Invalid plugin directory: %s', $pluginRootDir), E_USER_ERROR);
            throw new PluginException(tr('Invalid plugin directory: %s', $pluginRootDir));
        }

        $this->pluginSetDirectory($pluginRootDir);
        $this->eventsManager = new EventsManager($em);
        $this->cache = $cache;
        $this->pluginLoadData();
        
        /** @var \iMSCP\Application $app */
        $app = Registry::get('iMSCP_Application');
        foreach(array_keys($this->pluginData) as $plugin) {
            $app->getAutoloader()->addPsr4('iMSCP\\Plugin\\', $this->pluginGetDirectory() . "/$plugin/");
        }

        // register self-autoloader for old-way plugin names
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * Sets plugins root directory
     *
     * @throws PluginException In case $pluginDirectory doesn't exist or is not readable
     * @param string $pluginDir Plugin directory path
     * @return void
     */
    public function pluginSetDirectory($pluginDir)
    {
        if (!@is_writable($pluginDir)) {
            write_log(sprintf(
                "Plugin Manager: Directory %s doesn't exist or is not writable", $pluginDir), E_USER_ERROR
            );
            throw new PluginException(tr("Plugin Manager: Directory %s doesn't exist or is not writable", $pluginDir));
        }

        $this->pluginsDirectory = utils_normalizePath($pluginDir);
    }

    /**
     * Load plugin data from database
     *
     * @return void
     */
    protected function pluginLoadData()
    {
        if (!($metadata = $this->cache->load('iMSCP_Plugin_Manager_Metadata'))) {
            $metadata = [
                'data' => [],
                'type' => []
            ];
            $stmt = execute_query(
                '
                    SELECT plugin_name, plugin_type, plugin_info, plugin_status, plugin_error, plugin_backend,
                        plugin_lockers
                    FROM plugin
                    ORDER BY plugin_priority DESC
                '
            );
            while ($plugin = $stmt->fetch()) {
                $metadata['data'][$plugin['plugin_name']] = [
                    'info'    => new LazyDecoder($plugin['plugin_info']),
                    'status'  => $plugin['plugin_status'],
                    'error'   => $plugin['plugin_error'],
                    'backend' => $plugin['plugin_backend'],
                    'lockers' => new LazyDecoder($plugin['plugin_lockers'])
                ];
                $metadata['type'][$plugin['plugin_type']][] = $plugin['plugin_name'];
            }

            $this->cache->save($metadata, 'iMSCP_Plugin_Manager_Metadata');
        }

        $this->pluginData = $metadata['data'];
        $this->pluginsByType = $metadata['type'];
    }

    /**
     * Send backend request if scheduled
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
     * Autoloader for plugin classes
     *
     * @param string $className Plugin class to load
     * @return void
     */
    public function autoload($className)
    {
        if (strpos($className, 'iMSCP_Plugin_', 0) !== 0) {
            false;
        }

        $basename = substr($className, 13);
        $filePath = $this->pluginGetDirectory() . "/$basename/$basename.php";
        @include_once $filePath;
    }

    /**
     * Return plugins root directory
     *
     * @return string Plugin directory
     */
    public function pluginGetDirectory()
    {
        return $this->pluginsDirectory;
    }

    /**
     * Returns list of known plugins of given type
     *
     * @param string $type The type of plugins to return, 'all' for all plugin types
     * @param bool $enabledOnly Flag indicating if only active plugins must be returned
     * @param bool $noErrorOnly Flag indicating if only plugin with no error must be returned
     * @return array An array containing plugin names
     */
    public function pluginGetList($type = 'all', $enabledOnly = true, $noErrorOnly = true)
    {
        if ($type != 'all'
            && !isset($this->pluginsByType[$type])
        ) {
            return [];
        }

        $pluginNames = $type == 'all' ? array_keys($this->pluginData) : $this->pluginsByType[$type];

        return $enabledOnly || $noErrorOnly
            ? array_filter(
                $pluginNames,
                function ($pluginName) use ($enabledOnly, $noErrorOnly) {
                    if ($enabledOnly && $this->pluginData[$pluginName]['status'] !== 'enabled') {
                        return false;
                    }

                    if ($noErrorOnly && $this->pluginData[$pluginName]['error'] !== NULL) {
                        return false;
                    }

                    return true;
                }
            )
            : $pluginNames;
    }

    /**
     * Return list of loaded plugins by type
     *
     * @param string $type Type of loaded plugins to return (default: all types)
     * @return Plugin[]|ActionPlugin[] Array containing plugins instances
     */
    public function pluginGetLoaded($type = 'all')
    {
        if ($type == 'all') {
            return $this->loadedPlugins;
        }

        if (isset($this->pluginsByType[$type])) {
            return array_intersect(array_keys($this->loadedPlugins), $this->pluginsByType[$type]);
        }

        return [];
    }

    /**
     * Return instance of the given plugin
     *
     * Note: $pluginName must be already loaded.
     *
     * @throws PluginException When $pluginName is not loaded
     * @param string $pluginName Plugin name
     * @return Plugin|ActionPlugin
     */
    public function pluginGet($pluginName)
    {
        if (!$this->pluginIsLoaded($pluginName)) {
            write_log(sprintf('Plugin Manager: Plugin %s is not loaded: %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Plugin %s is not loaded', $pluginName));
        }

        return $this->loadedPlugins[$pluginName];
    }

    /**
     * Is the given plugin loaded?
     *
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin is loaded, FALSE otherwise
     */
    public function pluginIsLoaded($pluginName)
    {
        return isset($this->loadedPlugins[$pluginName]);
    }

    /**
     * Whether or not the given plugin has error
     *
     * @throws PluginException When $pluginName is not known
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin has error, FALSE otherwise
     */
    public function pluginHasError($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        return NULL !== $this->pluginData[$pluginName]['error'];
    }

    /**
     * Is the given plugin known by plugin manager?
     *
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin is know by plugin manager , FALSE otherwise
     */
    public function pluginIsKnown($pluginName)
    {
        return isset($this->pluginData[$pluginName]);
    }

    /**
     * Lock the given plugin
     *
     * @throws PluginException When $pluginName is not known
     * @param string $pluginName Plugin name
     * @param string $locker Locker name
     * @return void
     */
    public function pluginLock($pluginName, $locker)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        if ($this->pluginIsLocked($pluginName, $locker)) {
            return;
        }

        $responses = $this->getEventsManager()->dispatch(Events::onBeforeLockPlugin, [
            'pluginName'   => $pluginName,
            'pluginLocker' => $locker
        ]);

        if ($responses->isStopped()) {
            return;
        }

        /** @var LazyDecoder $lockers */
        $lockers = $this->pluginData[$pluginName]['lockers'];
        $lockers[$locker] = 1;
        exec_query('UPDATE plugin SET plugin_lockers = ? WHERE plugin_name = ?', [
            json_encode($lockers->toArray(), JSON_FORCE_OBJECT), $pluginName
        ]);
        $this->flushCache();
        $this->getEventsManager()->dispatch(Events::onAfterLockPlugin, [
            'pluginName'   => $pluginName,
            'pluginLocker' => $locker
        ]);
    }

    /**
     * Is the given plugin locked?
     *
     * @throws PluginException When $pluginName is not known
     * @param string $pluginName Plugin name
     * @param string|null $locker OPTIONAL Locker name
     * @return bool TRUE if the given plugin is locked, false otherwise
     */
    public function pluginIsLocked($pluginName, $locker = NULL)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        return NULL === $locker
            ? count($this->pluginData[$pluginName]['lockers']) > 0
            : isset($this->pluginData[$pluginName]['lockers'][$locker]);
    }

    /**
     * Return the event manager instance
     *
     * @return EventsManagerInterface
     */
    public function getEventsManager()
    {
        return $this->eventsManager;
    }

    /**
     * Return the event manager instance
     *
     * @return EventsManagerInterface
     * @deprecated Use getEventsManager() instead
     */
    public function getEventManager()
    {
        return $this->getEventsManager();
    }

    /**
     * Unlock the given plugin
     *
     * @throws PluginException When $pluginName is not known
     * @param string $pluginName Plugin name
     * @param string $unlocker Unlocker name
     * @return void
     */
    public function pluginUnlock($pluginName, $unlocker)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        if (!$this->pluginIsLocked($pluginName, $unlocker)) {
            return;
        }

        $responses = $this->getEventsManager()->dispatch(Events::onBeforeUnlockPlugin, [
            'pluginName'     => $pluginName,
            'pluginUnlocker' => $unlocker
        ]);

        if ($responses->isStopped()) {
            return;
        }

        /** @var LazyDecoder $lockers */
        $lockers = $this->pluginData[$pluginName]['lockers'];
        unset($lockers[$unlocker]);
        exec_query('UPDATE plugin SET plugin_lockers = ? WHERE plugin_name = ?', [
            json_encode($lockers->toArray(), JSON_FORCE_OBJECT), $pluginName
        ]);
        $this->flushCache();
        $this->getEventsManager()->dispatch(Events::onAfterUnlockPlugin, [
            'pluginName'     => $pluginName,
            'pluginUnlocker' => $unlocker
        ]);
    }

    /**
     * Check plugin compatibility with current API
     *
     * @throws PluginException
     * @param string $pluginName Plugin name
     * @param array $info Plugin info
     * @return void
     */
    public function pluginCheckCompat($pluginName, array $info)
    {
        if (!isset($info['require_api'])
            || version_compare($this->pluginGetApiVersion(), $info['require_api'], '<')
        ) {
            throw new PluginException(
                tr(
                    'The %s plugin version %s is not compatible with your i-MSCP version.',
                    $pluginName,
                    $info['version']
                )
            );
        }

        if ($this->pluginIsKnown($pluginName)
            && version_compare($this->pluginGetInfo($pluginName)['version'], $info['version'], '>')
        ) {
            throw new PluginException(
                tr('Plugin Manager: Downgrade of %s plugin is not allowed.', $pluginName), 'error'
            );
        }
    }

    /**
     * Returns plugin API version
     *
     * @return string Plugin API version
     */
    public function pluginGetApiVersion()
    {
        return Registry::get('config')['PluginApi'];
    }

    /**
     * Returns plugin info
     *
     * @throws PluginException When $pluginName is not known
     * @param string $pluginName Plugin name
     * @return LazyDecoder An array containing plugin info
     */
    public function pluginGetInfo($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        return $this->pluginData[$pluginName]['info'];
    }

    /**
     * Is the given plugin disabled?
     *
     * @throws PluginException When $pluginName is not known
     * @param string $pluginName Plugin name
     * @return bool TRUE if $pluginName is deactivated FALSE otherwise
     */
    public function pluginIsDisabled($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        return $this->pluginGetStatus($pluginName) == 'disabled';
    }

    /**
     * Return the status of the given plugin
     *
     * @param string $pluginName Plugin name
     * @return string Plugin status
     */
    public function pluginGetStatus($pluginName)
    {
        return $this->pluginIsKnown($pluginName) ? $this->pluginData[$pluginName]['status'] : 'uninstalled';
    }

    /**
     * Is the given plugin installed?
     *
     * @throws PluginException When $pluginName is not known
     * @param string $pluginName Plugin name
     * @return bool TRUE if $pluginName is activated FALSE otherwise
     */
    public function pluginIsInstalled($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        return !in_array($this->pluginGetStatus($pluginName), ['toinstall', 'uninstalled']);
    }

    /**
     * Install the given plugin
     *
     * @see pluginEnable() subaction
     * @param string $pluginName Plugin name
     * @return int
     */
    public function pluginInstall($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            return self::ACTION_FAILURE;
        }

        $pluginStatus = $this->pluginGetStatus($pluginName);

        if (!in_array($pluginStatus, ['toinstall', 'uninstalled'])) {
            return self::ACTION_FAILURE;
        }

        try {
            $pluginInstance = $this->pluginLoad($pluginName);
            $this->pluginSetStatus($pluginName, 'toinstall');
            $this->pluginSetError($pluginName, NULL);
            $responses = $this->getEventsManager()->dispatch(Events::onBeforeInstallPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if ($responses->isStopped()) {
                $this->pluginSetStatus($pluginName, $pluginStatus);
                return self::ACTION_STOPPED;
            }

            $pluginInstance->install($this);
            $this->getEventsManager()->dispatch(Events::onAfterInstallPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            $ret = $this->pluginEnable($pluginName, true);

            if ($ret == self::ACTION_SUCCESS) {
                if ($this->pluginHasBackend($pluginName)) {
                    $this->backendRequest = true;
                } else {
                    $this->pluginSetStatus($pluginName, 'enabled');
                }
            } elseif ($ret == self::ACTION_STOPPED) {
                $this->pluginSetStatus($pluginName, $pluginStatus);
            } else {
                throw new PluginException($this->pluginGetError($pluginName));
            }

            return $ret;
        } catch (PluginException $e) {
            write_log(sprintf('Plugin Manager: %s plugin installation has failed', $pluginName), E_USER_ERROR);
            $this->pluginSetError($pluginName, tr('Plugin installation has failed: %s', $e->getMessage()));
            return self::ACTION_FAILURE;
        }
    }

    /**
     * Enable the given plugin
     *
     * @see pluginUpdate() action
     * @param string $pluginName Plugin name
     * @param bool $isSubAction Whether this action is run as subaction
     * @return int
     */
    public function pluginEnable($pluginName, $isSubAction = false)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            return self::ACTION_FAILURE;
        }

        $pluginStatus = $this->pluginGetStatus($pluginName);

        if (!$isSubAction
            && !in_array($pluginStatus, ['toenable', 'disabled', 'uninstalled'])
        ) {
            return self::ACTION_FAILURE;
        }

        try {
            $pluginInstance = $this->pluginLoad($pluginName);

            if (!$isSubAction) {
                $pluginInfo = $this->pluginGetInfo($pluginName);

                if (version_compare($pluginInfo['version'], $pluginInfo['__nversion__'], '<')) {
                    $this->pluginSetStatus($pluginName, 'toupdate');
                    return $this->pluginUpdate($pluginName);
                }

                if (isset($pluginInfo['__need_change__'])
                    && $pluginInfo['__need_change__']
                ) {
                    $this->pluginSetStatus($pluginName, 'tochange');
                    return $this->pluginChange($pluginName);
                }

                $this->pluginSetStatus($pluginName, 'toenable');
            }

            $this->pluginSetError($pluginName, NULL);
            $responses = $this->getEventsManager()->dispatch(Events::onBeforeEnablePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if ($responses->isStopped()) {
                if (!$isSubAction) {
                    $this->pluginSetStatus($pluginName, $pluginStatus);
                }

                return self::ACTION_STOPPED;
            }

            $pluginInstance->enable($this);
            $this->getEventsManager()->dispatch(Events::onAfterEnablePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if ($this->pluginHasBackend($pluginName)) {
                $this->backendRequest = true;
            } elseif (!$isSubAction) {
                $this->pluginSetStatus($pluginName, 'enabled');
            }

            return self::ACTION_SUCCESS;
        } catch (PluginException $e) {
            write_log(sprintf('Plugin Manager: %s plugin activation has failed', $pluginName), E_USER_ERROR);
            $this->pluginSetError($pluginName, tr('Plugin activation has failed: %s', $e->getMessage()));
            return self::ACTION_FAILURE;
        }
    }

    /**
     * Update the given plugin
     *
     * @see pluginDisable() subaction
     * @see pluginEnable() subaction
     * @param string $pluginName Plugin name
     * @return int
     */
    public function pluginUpdate($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            return self::ACTION_FAILURE;
        }

        $pluginStatus = $this->pluginGetStatus($pluginName);

        if (!in_array($pluginStatus, ['toupdate', 'enabled'])) {
            return self::ACTION_FAILURE;
        }

        try {
            $pluginInstance = $this->pluginLoad($pluginName);
            $this->pluginSetStatus($pluginName, 'toupdate');
            $this->pluginSetError($pluginName, NULL);
            $ret = $this->pluginDisable($pluginName, true);

            if ($ret == self::ACTION_SUCCESS) {
                $pluginInfo = $this->pluginGetInfo($pluginName);
                $responses = $this->getEventsManager()->dispatch(Events::onBeforeUpdatePlugin, [
                    'pluginManager' => $this,
                    'pluginName'    => $pluginName,
                    'fromVersion'   => $pluginInfo['version'],
                    'toVersion'     => $pluginInfo['__nversion__']
                ]);

                if (!$responses->isStopped()) {
                    $pluginInstance->update($this, $pluginInfo['version'], $pluginInfo['__nversion__']);
                    $this->getEventsManager()->dispatch(Events::onAfterUpdatePlugin, [
                        'pluginManager' => $this,
                        'pluginName'    => $pluginName,
                        'fromVersion'   => $pluginInfo['version'],
                        'toVersion'     => $pluginInfo['__nversion__']
                    ]);

                    $ret = $this->pluginEnable($pluginName, true);

                    if ($ret == self::ACTION_SUCCESS) {
                        if ($this->pluginHasBackend($pluginName)) {
                            $this->backendRequest = true;
                        } else {
                            $pluginInfo['version'] = $pluginInfo['__nversion__'];
                            $this->pluginUpdateInfo($pluginName, $pluginInfo->toArray());
                            $this->pluginSetStatus($pluginName, 'enabled');
                        }
                    } elseif ($ret == self::ACTION_STOPPED) {
                        $this->pluginSetStatus($pluginName, $pluginStatus);
                    } else {
                        throw new PluginException($this->pluginGetError($pluginName));
                    }
                } elseif ($ret == self::ACTION_STOPPED) {
                    $this->pluginSetStatus($pluginName, $pluginStatus);
                } else {
                    throw new PluginException($this->pluginGetError($pluginName));
                }
            }

            return $ret;
        } catch (PluginException $e) {
            write_log(sprintf('Plugin Manager: %s plugin update has failed', $pluginName), E_USER_ERROR);
            $this->pluginSetError($pluginName, tr('Plugin update has failed: %s', $e->getMessage()));
            return self::ACTION_FAILURE;
        }
    }

    /**
     * Disable the given plugin
     *
     * @param string $pluginName Plugin name
     * @param bool $isSubAction Whether this action is run as subaction
     * @return int
     */
    public function pluginDisable($pluginName, $isSubAction = false)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            return self::ACTION_FAILURE;
        }

        $pluginStatus = $this->pluginGetStatus($pluginName);

        if (!$isSubAction
            && !in_array($pluginStatus, ['todisable', 'enabled'])
        ) {
            return self::ACTION_FAILURE;
        }

        try {
            $pluginInstance = $this->pluginLoad($pluginName);

            if (!$isSubAction) {
                $this->pluginSetStatus($pluginName, 'todisable');
            }

            $this->pluginSetError($pluginName, NULL);
            $responses = $this->getEventsManager()->dispatch(Events::onBeforeDisablePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if ($responses->isStopped()) {
                if (!$isSubAction) {
                    $this->pluginSetStatus($pluginName, $pluginStatus);
                }

                return self::ACTION_STOPPED;
            }

            $pluginInstance->disable($this);
            $this->getEventsManager()->dispatch(Events::onAfterDisablePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if ($this->pluginHasBackend($pluginName)) {
                $this->backendRequest = true;
            } elseif (!$isSubAction) {
                $this->pluginSetStatus($pluginName, 'disabled');
            }

            return self::ACTION_SUCCESS;
        } catch (PluginException $e) {
            write_log(sprintf('Plugin Manager: %s plugin deactivation has failed', $pluginName), E_USER_ERROR);
            $this->pluginSetError($pluginName, tr('Plugin deactivation has failed: %s', $e->getMessage()));
            return self::ACTION_FAILURE;
        }
    }

    /**
     * Load the given plugin
     *
     * @param string $pluginName Plugin name
     * @return false|Plugin|ActionPlugin Plugin instance, FALSE if plugin class is not found
     */
    public function pluginLoad($pluginName)
    {
        if ($this->pluginIsLoaded($pluginName)) {
            return $this->loadedPlugins[$pluginName];
        }

        $className = "iMSCP\\Plugin\\$pluginName";

        if (!class_exists($className, true)) {
            $className = "iMSCP_Plugin_$pluginName";
            if (!class_exists($className, true)) {
                write_log(
                    sprintf("Plugin Manager: Couldn't load %s plugin - Plugin class not found.", $pluginName),
                    E_USER_ERROR
                );
                return false;
            }
        }

        $this->loadedPlugins[$pluginName] = new $className($this);

        if ($this->pluginIsKnown($pluginName)
            && $this->loadedPlugins[$pluginName] instanceof ActionPlugin
        ) {
            $this->loadedPlugins[$pluginName]->register($this->getEventsManager());
        }

        return $this->loadedPlugins[$pluginName];
    }

    /**
     * Set status field of the given plugin
     *
     * @throws PluginException When $pluginName is not known
     * @param string $pluginName Plugin name
     * @param string $newStatus New plugin status
     * @return void
     */
    public function pluginSetStatus($pluginName, $newStatus)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        if ($this->pluginGetStatus($pluginName) !== $newStatus) {
            exec_query('UPDATE plugin SET plugin_status = ? WHERE plugin_name = ?', [$newStatus, $pluginName]);
            $this->pluginData[$pluginName]['status'] = $newStatus;
            $this->flushCache();
        }
    }

    /**
     * Set error field of the given plugin
     *
     * @throws PluginException When $pluginName is not known
     * @param string $pluginName Plugin name
     * @param null|string $pluginError Plugin error string or NULL if no error
     * @return void
     */
    public function pluginSetError($pluginName, $pluginError)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        if ($pluginError !== $this->pluginData[$pluginName]['error']) {
            exec_query('UPDATE plugin SET plugin_error = ? WHERE plugin_name = ?', [$pluginError, $pluginName]);
            $this->pluginData[$pluginName]['error'] = $pluginError;
            $this->flushCache();
        }
    }

    /**
     * Does the given plugin provides a backend side?
     *
     * @throws PluginException in case $pluginName is not known
     * @param string $pluginName Plugin name
     * @return boolean TRUE if the given plugin provide backend part, FALSE otherwise
     */
    public function pluginHasBackend($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        return $this->pluginData[$pluginName]['backend'] == 'yes';
    }

    /**
     * Update plugin info
     *
     * @param string $pluginName Plugin Name
     * @param array $info Plugin info
     * @return void
     */
    public function pluginUpdateInfo($pluginName, array $info)
    {
        exec_query('UPDATE plugin SET plugin_info = ? WHERE plugin_name = ?', [
            json_encode($info), $pluginName
        ]);
        $this->flushCache();
    }

    /**
     * Flush cache
     *
     * @return void
     */
    protected function flushCache()
    {
        if (!$this->cache->test('iMSCP_Plugin_Manager_Metadata')) {
            return;
        }

        $this->cache->remove('iMSCP_Plugin_Manager_Metadata');
    }

    /**
     * Get plugin error
     *
     * @throws PluginException When $pluginName is not known
     * @param null|string $pluginName Plugin name
     * @return string|null Plugin error string or NULL if no error
     */
    public function pluginGetError($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        return $this->pluginData[$pluginName]['error'];
    }

    /**
     * Change the given plugin
     *
     * @see pluginDisable() subaction
     * @see pluginEnable() subaction
     * @param string $pluginName Plugin name
     * @return int
     */
    public function pluginChange($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            return self::ACTION_FAILURE;
        }

        $pluginStatus = $this->pluginGetStatus($pluginName);

        if (!in_array($pluginStatus, ['tochange', 'enabled'])) {
            return self::ACTION_FAILURE;
        }

        try {
            $this->pluginSetStatus($pluginName, 'tochange');
            $this->pluginSetError($pluginName, NULL);
            $ret = $this->pluginDisable($pluginName, true);

            if ($ret == self::ACTION_SUCCESS) {
                $ret = $this->pluginEnable($pluginName, true);

                if ($ret == self::ACTION_SUCCESS) {
                    if ($this->pluginHasBackend($pluginName)) {
                        $this->backendRequest = true;
                    } else {
                        $pluginInfo = $this->pluginGetInfo($pluginName);
                        $pluginInfo['__need_change__'] = false;
                        $this->pluginUpdateInfo($pluginName, $pluginInfo->toArray());

                        try {
                            exec_query('UPDATE plugin SET plugin_config_prev = plugin_config WHERE plugin_name = ?', [
                                $pluginName
                            ]);
                            $this->pluginSetStatus($pluginName, 'enabled');
                        } catch (DatabaseException $e) {
                            throw new PluginException($e->getMessage(), $e->getCode(), $e);
                        }
                    }
                } elseif ($ret == self::ACTION_STOPPED) {
                    $this->pluginSetStatus($pluginName, $pluginStatus);
                } else {
                    throw new PluginException($this->pluginGetError($pluginName));
                }
            } elseif ($ret == self::ACTION_STOPPED) {
                $this->pluginSetStatus($pluginName, $pluginStatus);
            } else {
                throw new PluginException($this->pluginGetError($pluginName));
            }

            return $ret;
        } catch (PluginException $e) {
            write_log(sprintf('Plugin Manager: %s plugin change has failed', $pluginName), E_USER_ERROR);
            $this->pluginSetError($pluginName, tr('Plugin change has failed: %s', $e->getMessage()));
            return self::ACTION_FAILURE;
        }
    }

    /**
     * Is the given plugin uninstallable?
     *
     * @throws PluginException When $pluginName is not known
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin can be uninstalled, FALSE otherwise
     */
    public function pluginIsUninstallable($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        $info = $this->pluginGetInfo($pluginName);
        if (isset($info['__uninstallable__'])) {
            return $info['__uninstallable__'];
        }

        $rMethod = new ReflectionMethod($this->pluginLoad($pluginName), 'uninstall');
        return 'iMSCP_Plugin' != $rMethod->getDeclaringClass()->getName();
    }

    /**
     * Is the given plugin uninstalled?
     *
     * @throws PluginException When $pluginName is not known
     * @param string $pluginName Plugin name
     * @return bool TRUE if $pluginName is uninstalled FALSE otherwise
     */
    public function pluginIsUninstalled($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        return $this->pluginGetStatus($pluginName) == 'uninstalled';
    }

    /**
     * Uninstall the given plugin
     *
     * @param string $pluginName Plugin name
     * @return int
     */
    public function pluginUninstall($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            return self::ACTION_FAILURE;
        }

        $pluginStatus = $this->pluginGetStatus($pluginName);
        if (!in_array($pluginStatus, ['touninstall', 'disabled'])) {
            return self::ACTION_FAILURE;
        }

        if ($this->pluginIsLocked($pluginName)) {
            set_page_message(
                tr(
                    'Plugin Manager: Could not uninstall the %s plugin. Plugin has been locked by another plugin.',
                    $pluginName
                ),
                'warning'
            );
            return self::ACTION_FAILURE;
        }

        try {
            $pluginInstance = $this->pluginLoad($pluginName);
            $this->pluginSetStatus($pluginName, 'touninstall');
            $this->pluginSetError($pluginName, NULL);
            $responses = $this->getEventsManager()->dispatch(Events::onBeforeUninstallPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if ($responses->isStopped()) {
                $this->pluginSetStatus($pluginName, $pluginStatus);
                return self::ACTION_STOPPED;
            }

            $pluginInstance->uninstall($this);
            $this->getEventsManager()->dispatch(Events::onAfterUninstallPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if ($this->pluginHasBackend($pluginName)) {
                $this->backendRequest = true;
            } else {
                $this->pluginSetStatus(
                    $pluginName, $this->pluginIsInstallable($pluginName) ? 'uninstalled' : 'disabled'
                );
            }

            return self::ACTION_SUCCESS;
        } catch (PluginException $e) {
            write_log(sprintf('Plugin Manager: %s plugin uninstallation has failed', $pluginName), E_USER_ERROR);
            $this->pluginSetError($pluginName, tr('Plugin uninstallation has failed: %s', $e->getMessage()));
            return self::ACTION_FAILURE;
        }
    }

    /**
     * Is the given plugin installable?
     *
     * @throws PluginException When $pluginName is not known
     * @param string $pluginName Plugin name
     * @return bool TRUE if the given plugin is installable, FALSE otherwise
     */
    public function pluginIsInstallable($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        $info = $this->pluginGetInfo($pluginName);
        if (isset($info['__installable__'])) {
            return $info['__installable__'];
        }

        $rMethod = new ReflectionMethod($this->pluginLoad($pluginName), 'install');
        return 'iMSCP_Plugin' !== $rMethod->getDeclaringClass()->getName();
    }

    /**
     * Delete the given plugin
     *
     * @param string $pluginName Plugin name
     * @return int
     */
    public function pluginDelete($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            return self::ACTION_FAILURE;
        }

        $pluginStatus = $this->pluginGetStatus($pluginName);

        if (!in_array($pluginStatus, ['todelete', 'uninstalled', 'disabled'])) {
            return self::ACTION_FAILURE;
        }

        try {
            $pluginInstance = $this->pluginLoad($pluginName);
            $this->pluginSetStatus($pluginName, 'todelete');
            $this->pluginSetError($pluginName, NULL);
            $responses = $this->getEventsManager()->dispatch(Events::onBeforeDeletePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            if ($responses->isStopped()) {
                $this->pluginSetStatus($pluginName, $pluginStatus);
                return self::ACTION_STOPPED;
            }

            $pluginInstance->delete($this);
            $this->pluginDeleteData($pluginName);
            $pluginDir = $this->pluginsDirectory . '/' . $pluginName;

            if (is_dir($pluginDir)
                && !utils_removeDir($pluginDir)
            ) {
                write_log(sprintf("Plugin Manager: Couldn't delete %s plugin files", $pluginName), E_USER_WARNING);
                set_page_message(
                    tr(
                        'Plugin Manager: Could not delete %s plugin files. You should run the set-gui-permissions.pl script and try again.',
                        $pluginName
                    ),
                    'warning'
                );
            }

            $this->getEventsManager()->dispatch(Events::onAfterDeletePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);

            return self::ACTION_SUCCESS;
        } catch (PluginException $e) {
            write_log(sprintf('Plugin Manager: %s plugin deletion has failed', $pluginName), E_USER_ERROR);
            $this->pluginSetError($pluginName, tr('Plugin deletion has failed: %s', $e->getMessage()));
            return self::ACTION_FAILURE;
        }
    }

    /**
     * Delete plugin data
     *
     * @param string $pluginName Plugin name
     * @return bool TRUE if $name has been deleted from database, FALSE otherwise
     */
    protected function pluginDeleteData($pluginName)
    {
        $stmt = exec_query('DELETE FROM plugin WHERE plugin_name = ?', [$pluginName]);
        if (!$stmt->rowCount()) {
            return false;
        }

        // Force protected_plugins.php file to be regenerated or removed if needed
        if ($this->pluginIsProtected($pluginName)) {
            $protectedPlugins = array_flip($this->protectedPlugins);
            unset($protectedPlugins[$pluginName]);
            $this->protectedPlugins = array_flip($protectedPlugins);
            $this->pluginUpdateProtectedFile();
        }

        // Make the plugin manager aware of the deletion by reloading plugin data from database
        $this->flushCache();
        $this->pluginLoadData();
        write_log(sprintf('Plugin Manager: %s plugin has been removed from database', $pluginName), E_USER_NOTICE);
        return true;
    }

    /**
     * Does the given plugin is protected?
     *
     * @throws PluginException in case the given plugin is not known
     * @param string $pluginName Plugin name
     * @return int
     */
    public function pluginIsProtected($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        if (!$this->isLoadedProtectedPluginsList) {
            $protectedPlugins = [];
            @include_once PERSISTENT_PATH . '/protected_plugins.php';
            $this->protectedPlugins = $protectedPlugins;
            $this->isLoadedProtectedPluginsList = true;
        }

        return in_array($pluginName, $this->protectedPlugins);
    }

    /**
     * Handle plugin protection file
     *
     * @return bool TRUE when protection file is successfully created/updated/removed, FALSE otherwise
     */
    protected function pluginUpdateProtectedFile()
    {
        $file = PERSISTENT_PATH . '/protected_plugins.php';
        $lastUpdate = 'Last update: ' . date('Y-m-d H:i:s', time()) . ' by ' . encode_idna($_SESSION['user_logged']);
        $content = "<?php\n/**\n * Protected plugin list\n * Auto-generated by i-MSCP Plugin Manager\n";
        $content .= " * $lastUpdate\n */\n\n";

        if (!empty($this->protectedPlugins)) {
            foreach ($this->protectedPlugins as $pluginName) {
                $content .= "\$protectedPlugins[] = '$pluginName';\n";
            }

            iMSCP_Utility_OpcodeCache::clearAllActive($file); // Be sure to load newest version on next call
            @unlink($file);

            if (@file_put_contents($file, "$content\n", LOCK_EX) === false) {
                write_log(sprintf("Plugin Manager: Couldn't write the %s file for protected plugins.", $file));
                set_page_message(
                    tr('Plugin Manager: Could not write the %s file for protected plugins.', $file), 'error'
                );
                return false;
            }

            return true;
        }

        if (@is_writable($file)) {
            iMSCP_Utility_OpcodeCache::clearAllActive($file); // Be sure to load newest version on next call
            if (!@unlink($file)) {
                write_log(sprintf("Plugin Manager: Couldn't remove the %s file", $file), E_USER_WARNING);
                return false;
            }
        }

        return true;
    }

    /**
     * Protect the given plugin
     *
     * @param string $pluginName Name of the plugin to protect
     * @return bool self::ACTION_SUCCESS|self::ACTION_FAILURE
     */
    public function pluginProtect($pluginName)
    {
        if (!$this->pluginIsEnabled($pluginName)
            || $this->pluginIsProtected($pluginName)
        ) {
            return self::ACTION_FAILURE;
        }

        $responses = $this->getEventsManager()->dispatch(Events::onBeforeProtectPlugin, [
            'pluginManager' => $this,
            'pluginName'    => $pluginName
        ]);

        if ($responses->isStopped()) {
            return self::ACTION_STOPPED;
        }

        $protectedPlugins = $this->protectedPlugins;
        $this->protectedPlugins[] = $pluginName;

        if ($this->pluginUpdateProtectedFile()) {
            $this->getEventsManager()->dispatch(Events::onAfterProtectPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $pluginName
            ]);
            return self::ACTION_SUCCESS;
        }

        $this->protectedPlugins = $protectedPlugins;
        return self::ACTION_FAILURE;
    }

    /**
     * Is the given plugin enabled?
     *
     * @throws PluginException When $pluginName is not known
     * @param string $pluginName Plugin name
     * @return bool TRUE if $pluginName is activated FALSE otherwise
     */
    public function pluginIsEnabled($pluginName)
    {
        if (!$this->pluginIsKnown($pluginName)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
            throw new PluginException(tr('Plugin Manager: Unknown plugin %s', $pluginName));
        }

        return $this->pluginGetStatus($pluginName) == 'enabled';
    }

    /**
     * Update plugin list
     *
     * This method is responsible to update the plugin list and trigger plugin
     * update, change and deletion.
     *
     * @return array An array containing information about added, updated and
     *               deleted plugins
     */
    public function pluginUpdateList()
    {
        $seenPlugins = $toUpdatePlugins = $toChangePlugins = [];
        $returnInfo = ['new' => 0, 'updated' => 0, 'changed' => 0, 'deleted' => 0];

        /** @var $file SplFileInfo */
        foreach (new RecursiveDirectoryIterator($this->pluginGetDirectory(), FilesystemIterator::SKIP_DOTS) as $file) {
            if (!$file->isDir()
                || !$file->isReadable()
            ) {
                continue;
            }

            $pluginName = $file->getBasename();

            if (!($plugin = $this->pluginLoad($pluginName))) {
                set_page_message(tr('Plugin Manager: Could not load plugin %s', $pluginName), 'error');
                continue;
            }

            $seenPlugins[] = $pluginName;
            $info = $plugin->getInfo();
            $infoPrev = $this->pluginIsKnown($pluginName) ? $this->pluginGetInfo($pluginName) : $info;
            $info['__nversion__'] = $info['version'];
            $info['version'] = $infoPrev['version'];

            if (version_compare($info['__nversion__'], $info['version'], '<')) {
                set_page_message(tr('Plugin Manager: Downgrade of %s plugin is not allowed.', $pluginName), 'error');
                continue;
            }

            if (isset($infoPrev['db_schema_version'])) {
                $info['db_schema_version'] = $infoPrev['db_schema_version'];
            }

            $config = $plugin->getConfigFromFile();
            $configPrev = $this->pluginIsKnown($pluginName) ? $plugin->getConfigPrev() : $config;
            $r = new ReflectionMethod($plugin, 'install');
            $info['__installable__'] = 'iMSCP_Plugin' !== $r->getDeclaringClass()->getName();
            $r = new ReflectionMethod($plugin, 'uninstall');
            $info['__uninstallable__'] = 'iMSCP_Plugin' !== $r->getDeclaringClass()->getName();
            $needDataUpdate = $needUpdate = $needChange = false;

            if (!$this->pluginIsKnown($pluginName)) {
                $status = $info['__installable__'] ? 'uninstalled' : 'disabled';
                $returnInfo['new']++;
                $needDataUpdate = true;
                $lockers = new LazyDecoder('{}');
            } else {
                $status = $this->pluginGetStatus($pluginName);
                $needUpdate = version_compare($info['version'], $info['__nversion__'], '<');
                /** @var LazyDecoder $lockers */
                $lockers = $this->pluginData[$pluginName]['lockers'];
                $oldBuild = isset($infoPrev['build']) ? $infoPrev['build'] : '0000000000';
                $newBuild = $info['build'];

                if (!in_array($status, ['uninstalled', 'toinstall', 'touninstall', 'todelete'])
                    && ($config != $configPrev
                        || $infoPrev['__need_change__']
                        || $newBuild > $oldBuild
                        || new DateTime($info['date']) > new DateTime($infoPrev['date'])
                    )
                ) {
                    $needChange = true;
                } elseif ($config != $configPrev) {
                    $configPrev = $config;
                    $needDataUpdate = true;
                } elseif ($newBuild > $oldBuild
                    || new DateTime($info['date']) > new DateTime($infoPrev['date'])
                ) {
                    $needDataUpdate = true;
                }
            }

            $info['__need_change__'] = $needChange;

            if ($needDataUpdate
                || $needUpdate
                || $needChange
            ) {
                $this->pluginUpdateData([
                    'name'        => $pluginName,
                    'type'        => $plugin->getType(),
                    'info'        => json_encode($info),
                    'config'      => json_encode($config),
                    'config_prev' => json_encode($configPrev),
                    'priority'    => isset($info['priority']) ? intval($info['priority']) : 0,
                    'status'      => $status,
                    'backend'     => file_exists($file->getPathname() . "/backend/$pluginName.pm") ? 'yes' : 'no',
                    'lockers'     => json_encode($lockers->toArray(), JSON_FORCE_OBJECT),
                ]);

                if ($status == 'enabled'
                    || $status == 'tochange'
                    || $status == 'toupdate'
                ) {
                    if ($needUpdate) {
                        $toUpdatePlugins[] = $pluginName;
                        $returnInfo['updated']++;
                    } elseif ($needChange) {
                        $toChangePlugins[] = $pluginName;
                        $returnInfo['changed']++;
                    }
                }
            }
        }

        // Make the plugin manager aware of the new plugin data
        $this->flushCache();
        $this->pluginLoadData();

        // Process plugin (update/change/deletion)
        foreach (array_keys($this->pluginData) as $pluginName) {
            if (!in_array($pluginName, $seenPlugins)) {
                if ($this->pluginDeleteData($pluginName)) {
                    $returnInfo['deleted']++;
                }
            } elseif (in_array($pluginName, $toUpdatePlugins)) {
                $ret = $this->pluginUpdate($pluginName);
                if ($ret == self::ACTION_FAILURE
                    || $ret == self::ACTION_STOPPED
                ) {
                    $message = tr(
                        'Plugin Manager: Could not update the %s plugin: %s',
                        $pluginName,
                        $ret == self::ACTION_FAILURE ? tr('Action has failed.') : tr('Action has been stopped.')
                    );
                    set_page_message($message, 'error');
                    $returnInfo['updated']--;
                }
            } elseif (in_array($pluginName, $toChangePlugins)) {
                $ret = $this->pluginChange($pluginName);
                if ($ret == self::ACTION_FAILURE
                    || $ret == self::ACTION_STOPPED
                ) {
                    $message = tr(
                        'Plugin Manager: Could not change the %s plugin: %s',
                        $pluginName,
                        $ret == self::ACTION_FAILURE ? tr('Action has failed.') : tr('Action has been stopped.')
                    );
                    set_page_message($message, 'error');
                    $returnInfo['changed']--;
                }
            }
        }

        return $returnInfo;
    }

    /**
     * Update plugin data
     *
     * @param array $data Plugin data
     * @return void
     */
    protected function pluginUpdateData(array $data)
    {
        if (!isset($this->pluginData[$data['name']])) {
            exec_query(
                '
                    INSERT INTO plugin (
                        plugin_name, plugin_type, plugin_info, plugin_config, plugin_config_prev, plugin_priority,
                        plugin_status, plugin_backend, plugin_lockers
                    ) VALUE (
                        :name, :type, :info, :config, :config_prev, :priority, :status, :backend, :lockers
                    )
                ',
                $data
            );
            return;
        }

        exec_query(
            '
                UPDATE plugin SET plugin_info = ?, plugin_config = ?, plugin_config_prev = ?, plugin_priority = ?,
                    plugin_status = ?, plugin_backend = ?, plugin_lockers = ?
                WHERE plugin_name = ?
            ',
            [
                $data['info'], $data['config'], $data['config_prev'], $data['priority'], $data['status'],
                $data['backend'], $data['lockers'], $data['name']
            ]
        );
    }
}
