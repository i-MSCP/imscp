<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * Plugin Manager class
 */
class iMSCP_Plugin_Manager
{
    /**
     * @const string Plugin API version
     */
    const PLUGIN_API_VERSION = '1.0.4';

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
     * Events which are triggered by the event manager
     * @var array
     */
    protected $events = array(
        iMSCP_Events::onBeforeUpdatePluginList,
        iMSCP_Events::onAfterUpdatePluginList,
        iMSCP_Events::onBeforeInstallPlugin,
        iMSCP_Events::onAfterInstallPlugin,
        iMSCP_Events::onBeforeUpdatePlugin,
        iMSCP_Events::onAfterUpdatePlugin,
        iMSCP_Events::onBeforeEnablePlugin,
        iMSCP_Events::onAfterEnablePlugin,
        iMSCP_Events::onBeforeDisablePlugin,
        iMSCP_Events::onAfterDisablePlugin,
        iMSCP_Events::onBeforeUninstallPlugin,
        iMSCP_Events::onAfterUninstallPlugin,
        iMSCP_Events::onBeforeDeletePlugin,
        iMSCP_Events::onAfterDeletePlugin,
        iMSCP_Events::onBeforeLockPlugin,
        iMSCP_Events::onAfterLockPlugin,
        iMSCP_Events::onBeforeUnlockPlugin,
        iMSCP_Events::onAfterUnlockPlugin
    );

    /**
     * @var string Plugins directory
     */
    protected $pluginsDirectory;

    /**
     * @var array Keys are plugin names and values are array containing plugin data
     */
    protected $pluginData = array();

    /**
     * @var array List of protected plugins
     */
    protected $protectedPlugins = array();

    /**
     * @var bool Whether or not list of protected plugin is loaded
     */
    protected $isLoadedProtectedPluginsList = false;

    /**
     * @var array Plugin by type
     */
    protected $pluginsByType = array();

    /**
     * @var iMSCP_Plugin[]|iMSCP_Plugin_Action[] Array containing all loaded plugins
     */
    protected $loadedPlugins = array();

    /**
     * @var bool Whether or not a backend request should be sent
     */
    protected $backendRequest = false;

    /**
     * @var iMSCP_Events_Aggregator
     */
    protected $eventsManager = null;

    /**
     * Constructor
     *
     * @param string $pluginRootDir Plugins root directory
     * @throws iMSCP_Plugin_Exception
     * @return iMSCP_Plugin_Manager
     */
    public function __construct($pluginRootDir)
    {
        if (!@is_dir($pluginRootDir)) {
            write_log(sprintf('Plugin Manager: Invalid plugin directory: %s', $pluginRootDir), E_USER_ERROR);
            throw new iMSCP_Plugin_Exception(sprintf('Invalid plugin directory: %s', $pluginRootDir));
        }

        $this->pluginSetDirectory($pluginRootDir);
        $this->eventsManager = iMSCP_Events_Aggregator::getInstance()->addEvents('pluginManager', $this->events);
        $this->pluginLoadData();
        spl_autoload_register(array($this, '_autoload'));
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
    public function _autoload($className)
    {
        // Do not try to load class outside the plugin namespace
        if (strpos($className, 'iMSCP_Plugin_', 0) === 0) {
            $basename = substr($className, 13);
            $filePath = $this->pluginGetDirectory() . "/$basename/$basename.php";
            @include_once $filePath;
        }
    }

    /**
     * Get event manager
     *
     * @return iMSCP_Events_Manager
     */
    public function getEventManager()
    {
        return $this->eventsManager;
    }

    /**
     * Returns plugin API version
     *
     * @return string Plugin API version
     */
    public function pluginGetApiVersion()
    {
        return self::PLUGIN_API_VERSION;
    }

    /**
     * Sets plugins root directory
     *
     * @throws iMSCP_Plugin_Exception In case $pluginDirectory doesn't exist or is not readable.
     * @param string $pluginDir Plugin directory path
     * @return void
     */
    public function pluginSetDirectory($pluginDir)
    {
        if (!@is_writable($pluginDir)) {
            write_log(sprintf("Plugin Manager: Directory %s doesn't exist or is not writable", $pluginDir), E_USER_ERROR);
            throw new iMSCP_Plugin_Exception(sprintf("Plugin Manager: Directory %s doesn't exist or is not writable", $pluginDir));
        }

        $this->pluginsDirectory = $pluginDir;
    }

    /**
     * Get plugins root directory
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
     * @param string $type The type of plugins to return ('all' means all plugin type).
     * @param bool $enabledOnly TRUE to only return activated plugins (default), FALSE to all plugins
     * @return array An array containing plugin names
     */
    public function pluginGetList($type = 'all', $enabledOnly = true)
    {
        if ($type == 'all') {
            return array_keys(
                $enabledOnly ? array_filter(
                    $this->pluginData,
                    function ($pluginData) {
                        return ($pluginData['status'] == 'enabled');
                    }
                ) : $this->pluginData
            );
        }

        if (isset($this->pluginsByType[$type])) {
            $pluginData = $this->pluginData;
            return $enabledOnly
                ? array_filter(
                    $this->pluginsByType[$type],
                    function ($pluginName) use ($pluginData) {
                        return ($pluginData[$pluginName]['status'] == 'enabled');
                    }
                ) : $this->pluginsByType[$type];
        }

        return array();
    }

    /**
     * Loads the given plugin
     *
     * @param string $name Plugin name
     * @return false|iMSCP_Plugin|iMSCP_Plugin_Action Plugin instance, FALSE if plugin class is not found
     */
    public function pluginLoad($name)
    {
        if ($this->pluginIsLoaded($name)) {
            return $this->loadedPlugins[$name];
        }

        $className = "iMSCP_Plugin_$name";
        if (!class_exists($className, true)) {
            write_log(sprintf('Plugin Manager: Unable to load %s plugin - Class %s not found.', $name, $className), E_USER_ERROR);
            return false;
        }

        $this->loadedPlugins[$name] = new $className($this);

        if ($this->loadedPlugins[$name] instanceof iMSCP_Plugin_Action) {
            $this->loadedPlugins[$name]->register($this->getEventManager());
        }

        return $this->loadedPlugins[$name];
    }

    /**
     * Does the given plugin is loaded?
     *
     * @param string $name Plugin name
     * @return bool TRUE if the given plugin is loaded, FALSE otherwise
     */
    public function pluginIsLoaded($name)
    {
        return isset($this->loadedPlugins[$name]);
    }

    /**
     * Get list of loaded plugins by type
     *
     * @param string $type Type of loaded plugins to return
     * @return iMSCP_Plugin[]|iMSCP_Plugin_Action[] Array containing plugins instances
     */
    public function pluginGetLoaded($type = 'all')
    {
        if ($type == 'all') {
            return $this->loadedPlugins;
        }

        if (isset($this->pluginsByType[$type])) {
            return array_intersect_key($this->loadedPlugins, array_flip($this->pluginsByType[$type]));
        }

        return array();
    }

    /**
     * Get instance of loaded plugin
     *
     * Note: $name must be already loaded.
     *
     * @throws iMSCP_Plugin_Exception When $name is not loaded
     * @param string $name Plugin name
     * @return iMSCP_Plugin|iMSCP_Plugin_Action
     */
    public function pluginGet($name)
    {
        if ($this->pluginIsLoaded($name)) {
            return $this->loadedPlugins[$name];
        }

        throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Plugin %s is not loaded', $name));
    }

    /**
     * Get status of the given plugin
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param string $name Plugin name
     * @return string Plugin status
     */
    public function pluginGetStatus($name)
    {
        return $this->pluginIsKnown($name) ? $this->pluginData[$name]['status'] : 'uninstalled';
    }

    /**
     * Set status for the given plugin
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param string $name Plugin name
     * @param string $newStatus New plugin status
     * @return void
     */
    public function pluginSetStatus($name, $newStatus)
    {
        if (!$this->pluginIsKnown($name)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
            throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
        }

        $status = $this->pluginGetStatus($name);
        if ($status !== $newStatus) {
            exec_query('UPDATE plugin SET plugin_status = ? WHERE plugin_name = ?', array($newStatus, $name));
            $this->pluginData[$name]['status'] = $newStatus;
        }
    }

    /**
     * Get plugin error
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param null|string $name Plugin name
     * @return string|null Plugin error string or NULL if no error
     */
    public function pluginGetError($name)
    {
        if ($this->pluginIsKnown($name)) {
            return $this->pluginData[$name]['error'];
        }

        write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
        throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
    }

    /**
     * Set error for the given plugin
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param string $name Plugin name
     * @param null|string $pluginError Plugin error string or NULL if no error
     * @return void
     */
    public function pluginSetError($name, $pluginError)
    {
        if (!$this->pluginIsKnown($name)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
            throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
        }

        if ($pluginError !== $this->pluginData[$name]['error']) {
            exec_query('UPDATE plugin SET plugin_error = ? WHERE plugin_name = ?', array($pluginError, $name));
            $this->pluginData[$name]['error'] = $pluginError;
        }
    }

    /**
     * Whether or not the given plugin has error
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param string $name Plugin name
     * @return bool TRUE if the given plugin has error, FALSE otherwise
     */
    public function pluginHasError($name)
    {
        if ($this->pluginIsKnown($name)) {
            return null !== $this->pluginData[$name]['error'];
        }

        write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
        throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
    }

    /**
     * Returns plugin info
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param string $name Plugin name
     * @return array An array containing plugin info
     */
    public function pluginGetInfo($name)
    {
        if ($this->pluginIsKnown($name)) {
            return $this->pluginData[$name]['info'];
        }

        write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
        throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
    }

    /**
     * Update plugin info
     *
     * @param string $name Plugin Name
     * @param array $info Plugin info
     * @return void
     */
    public function pluginUpdateInfo($name, array $info)
    {
        exec_query('UPDATE plugin SET plugin_info = ? WHERE plugin_name = ?', array(json_encode($info), $name));
    }

    /**
     * Does the given plugin is locked?
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param string $name Plugin name
     * @return bool TRUE if the given plugin is locked, false otherwise
     */
    public function pluginIsLocked($name)
    {
        if ($this->pluginIsKnown($name)) {
            return (bool)$this->pluginData[$name]['locked'];
        }

        write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
        throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
    }

    /**
     * Lock the given plugin
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param string $name Plugin name
     * @return void
     */
    public function pluginLock($name)
    {
        if (!$this->pluginIsKnown($name)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
            throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
        }

        if ($this->pluginIsLocked($name)) {
            return;
        }

        $responses = $this->eventsManager->dispatch(iMSCP_Events::onBeforeLockPlugin, array(
            'pluginName' => $name
        ));

        if (!$responses->isStopped()) {
            exec_query('UPDATE plugin SET plugin_locked = ? WHERE plugin_name = ?', array(1, $name));
            $this->pluginData[$name]['locked'] = 1;
            $this->eventsManager->dispatch(iMSCP_Events::onAfterLockPlugin, array(
                'pluginName' => $name
            ));
        }
    }

    /**
     * Unlock the given plugin
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param string $name Plugin name
     * @return void
     */
    public function pluginUnlock($name)
    {
        if (!$this->pluginIsKnown($name)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
            throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
        }

        if (!$this->pluginIsLocked($name)) {
            return;
        }
        $responses = $this->eventsManager->dispatch(iMSCP_Events::onBeforeUnlockPlugin, array(
            'pluginName' => $name
        ));

        if (!$responses->isStopped()) {
            exec_query('UPDATE plugin SET plugin_locked = ? WHERE plugin_name = ?', array(0, $name));
            $this->pluginData[$name]['locked'] = 0;
            $this->eventsManager->dispatch(iMSCP_Events::onAfterUnlockPlugin, array(
                'pluginName' => $name
            ));
        }
    }

    /**
     * Does the given plugin is installable?
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param string $name Plugin name
     * @return bool TRUE if the given plugin is installable, FALSE otherwise
     */
    public function pluginIsInstallable($name)
    {
        if (!$this->pluginIsKnown($name)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
            throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
        }

        $info = $this->pluginGetInfo($name);
        if (isset($info['__installable__'])) {
            return $info['__installable__'];
        }

        $pluginInstance = $this->pluginLoad($name);
        $rMethod = new ReflectionMethod($pluginInstance, 'install');
        return 'iMSCP_Plugin' !== $rMethod->getDeclaringClass()->getName();
    }

    /**
     * Does the given plugin is installed?
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param string $name Plugin name
     * @return bool TRUE if $name is activated FALSE otherwise
     */
    public function pluginIsInstalled($name)
    {
        if ($this->pluginIsKnown($name)) {
            return !in_array($this->pluginGetStatus($name), array('toinstall', 'uninstalled'));
        }

        write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
        throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
    }

    /**
     * Install the given plugin
     *
     * @see pluginEnable() subaction
     * @param string $name Plugin name
     * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
     */
    public function pluginInstall($name)
    {
        if (!$this->pluginIsKnown($name)) {
            return self::ACTION_FAILURE;
        }

        $pluginStatus = $this->pluginGetStatus($name);
        if (!in_array($pluginStatus, array('toinstall', 'uninstalled'))) {
            return self::ACTION_FAILURE;
        }

        try {
            $pluginInstance = $this->pluginLoad($name);
            $this->pluginSetStatus($name, 'toinstall');
            $this->pluginSetError($name, null);
            $responses = $this->eventsManager->dispatch(iMSCP_Events::onBeforeInstallPlugin, array(
                'pluginManager' => $this,
                'pluginName' => $name
            ));

            if (!$responses->isStopped()) {
                $pluginInstance->install($this);
                $this->eventsManager->dispatch(iMSCP_Events::onAfterInstallPlugin, array(
                    'pluginManager' => $this,
                    'pluginName' => $name
                ));

                $ret = $this->pluginEnable($name, true);

                if ($ret == self::ACTION_SUCCESS) {
                    if ($this->pluginHasBackend($name)) {
                        $this->backendRequest = true;
                    } else {
                        $this->pluginSetStatus($name, 'enabled');
                    }
                } elseif ($ret == self::ACTION_STOPPED) {
                    $this->pluginSetStatus($name, $pluginStatus);
                } else {
                    throw new iMSCP_Plugin_Exception($this->pluginGetError($name));
                }

                return $ret;
            }

            $this->pluginSetStatus($name, $pluginStatus);
            return self::ACTION_STOPPED;
        } catch (iMSCP_Plugin_Exception $e) {
            $this->pluginSetError($name, sprintf('Plugin installation has failed: %s', $e->getMessage()));
            write_log(sprintf('Plugin Manager: %s plugin installation has failed', $name), E_USER_ERROR);
        }

        return self::ACTION_FAILURE;
    }

    /**
     * Does the given plugin is uninstallable?
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param string $name Plugin name
     * @return bool TRUE if the given plugin can be uninstalled, FALSE otherwise
     */
    public function pluginIsUninstallable($name)
    {
        if (!$this->pluginIsKnown($name)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
            throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
        }

        $info = $this->pluginGetInfo($name);
        if (isset($info['__uninstallable__'])) {
            return $info['__uninstallable__'];
        }

        $pluginInstance = $this->pluginLoad($name);
        $rMethod = new ReflectionMethod($pluginInstance, 'uninstall');
        return 'iMSCP_Plugin' != $rMethod->getDeclaringClass()->getName();
    }

    /**
     * Does the given plugin is uninstalled?
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param string $name Plugin name
     * @return bool TRUE if $name is uninstalled FALSE otherwise
     */
    public function pluginIsUninstalled($name)
    {
        if ($this->pluginIsKnown($name)) {
            return ($this->pluginGetStatus($name) == 'uninstalled');
        }

        write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
        throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
    }

    /**
     * Uninstall the given plugin
     *
     * @param string $name Plugin name
     * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
     */
    public function pluginUninstall($name)
    {
        if (!$this->pluginIsKnown($name)) {
            return self::ACTION_FAILURE;
        }

        $pluginStatus = $this->pluginGetStatus($name);
        if (!in_array($pluginStatus, array('touninstall', 'disabled'))) {
            return self::ACTION_FAILURE;
        }

        if (!$this->pluginIsLocked($name)) {
            try {
                $pluginInstance = $this->pluginLoad($name);
                $this->pluginSetStatus($name, 'touninstall');
                $this->pluginSetError($name, null);
                $responses = $this->eventsManager->dispatch(iMSCP_Events::onBeforeUninstallPlugin, array(
                    'pluginManager' => $this,
                    'pluginName' => $name
                ));

                if (!$responses->isStopped()) {
                    $pluginInstance->uninstall($this);
                    $this->eventsManager->dispatch(iMSCP_Events::onAfterUninstallPlugin, array(
                        'pluginManager' => $this,
                        'pluginName' => $name
                    ));

                    if ($this->pluginHasBackend($name)) {
                        $this->backendRequest = true;
                    } else {
                        $this->pluginSetStatus($name, 'uninstalled');
                    }

                    return self::ACTION_SUCCESS;
                }

                $this->pluginSetStatus($name, $pluginStatus);
                return self::ACTION_STOPPED;
            } catch (iMSCP_Plugin_Exception $e) {
                $this->pluginSetError($name, sprintf('Plugin uninstallation has failed: %s', $e->getMessage()));
                write_log(sprintf('Plugin Manager: %s plugin uninstallation has failed', $name), E_USER_ERROR);
            }
        } else {
            set_page_message(tr('Plugin Manager: Unable to uninstall the %s plugin. Plugin has been locked by another plugin.', $name), 'warning');
        }

        return self::ACTION_FAILURE;
    }

    /**
     * Does the given plugin is enabled?
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param string $name Plugin name
     * @return bool TRUE if $name is activated FALSE otherwise
     */
    public function pluginIsEnabled($name)
    {
        if ($this->pluginIsKnown($name)) {
            return $this->pluginGetStatus($name) == 'enabled';
        }

        write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
        throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
    }

    /**
     * Enable the given plugin
     *
     * @see pluginUpdate() action
     * @param string $name Plugin name
     * @param bool $isSubaction Whether this action is run as subaction
     * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
     */
    public function pluginEnable($name, $isSubaction = false)
    {
        if (!$this->pluginIsKnown($name)) {
            return self::ACTION_FAILURE;
        }

        $pluginStatus = $this->pluginGetStatus($name);
        if (!$isSubaction && !in_array($pluginStatus, array('toenable', 'disabled'))) {
            return self::ACTION_FAILURE;
        }

        try {
            $pluginInstance = $this->pluginLoad($name);

            if (!$isSubaction) {
                $pluginInfo = $this->pluginGetInfo($name);

                if (version_compare($pluginInfo['version'], $pluginInfo['__nversion__'], '<')) {
                    $this->pluginSetStatus($name, 'toupdate');
                    return $this->pluginUpdate($name);
                }

                if (isset($pluginInfo['__need_change__']) && $pluginInfo['__need_change__']) {
                    $this->pluginSetStatus($name, 'tochange');
                    return $this->pluginChange($name);
                }

                $this->pluginSetStatus($name, 'toenable');
            }

            $this->pluginSetError($name, null);
            $responses = $this->eventsManager->dispatch(iMSCP_Events::onBeforeEnablePlugin, array(
                'pluginManager' => $this,
                'pluginName' => $name
            ));

            if (!$responses->isStopped()) {
                $pluginInstance->enable($this);
                $this->eventsManager->dispatch(iMSCP_Events::onAfterEnablePlugin, array(
                    'pluginManager' => $this,
                    'pluginName' => $name
                ));

                if ($this->pluginHasBackend($name)) {
                    $this->backendRequest = true;
                } elseif (!$isSubaction) {
                    $this->pluginSetStatus($name, 'enabled');
                }

                return self::ACTION_SUCCESS;
            }

            if (!$isSubaction) {
                $this->pluginSetStatus($name, $pluginStatus);
            }

            return self::ACTION_STOPPED;
        } catch (iMSCP_Plugin_Exception $e) {
            $this->pluginSetError($name, sprintf('Plugin activation has failed: %s', $e->getMessage()));
            write_log(sprintf('Plugin Manager: %s plugin activation has failed', $name), E_USER_ERROR);
        }

        return self::ACTION_FAILURE;
    }

    /**
     * Does the given plugin is disabled?
     *
     * @throws iMSCP_Plugin_Exception When $name is not known
     * @param string $name Plugin name
     * @return bool TRUE if $name is deactivated FALSE otherwise
     */
    public function pluginIsDisabled($name)
    {
        if ($this->pluginIsKnown($name)) {
            return $this->pluginGetStatus($name) == 'disabled';
        }

        write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
        throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
    }

    /**
     * Disable the given plugin
     *
     * @param string $name Plugin name
     * @param bool $isSubaction Whether this action is run as subaction
     * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
     */
    public function pluginDisable($name, $isSubaction = false)
    {
        if (!$this->pluginIsKnown($name)) {
            return self::ACTION_FAILURE;
        }

        $pluginStatus = $this->pluginGetStatus($name);
        if (!$isSubaction && !in_array($pluginStatus, array('todisable', 'enabled'))) {
            return self::ACTION_FAILURE;
        }

        try {
            $pluginInstance = $this->pluginLoad($name);

            if (!$isSubaction) {
                $this->pluginSetStatus($name, 'todisable');
            }

            $this->pluginSetError($name, null);
            $responses = $this->eventsManager->dispatch(iMSCP_Events::onBeforeDisablePlugin, array(
                'pluginManager' => $this,
                'pluginName' => $name
            ));

            if (!$responses->isStopped()) {
                $pluginInstance->disable($this);
                $this->eventsManager->dispatch(iMSCP_Events::onAfterDisablePlugin, array(
                    'pluginManager' => $this,
                    'pluginName' => $name
                ));

                if ($this->pluginHasBackend($name)) {
                    $this->backendRequest = true;
                } elseif (!$isSubaction) {
                    $this->pluginSetStatus($name, 'disabled');
                }

                return self::ACTION_SUCCESS;
            }

            if (!$isSubaction) {
                $this->pluginSetStatus($name, $pluginStatus);
            }

            return self::ACTION_STOPPED;
        } catch (iMSCP_Plugin_Exception $e) {
            $this->pluginSetError($name, sprintf('Plugin deactivation has failed: %s', $e->getMessage()));
            write_log(sprintf('Plugin Manager: %s plugin deactivation has failed', $name), E_USER_ERROR);
        }

        return self::ACTION_FAILURE;
    }

    /**
     * Change the given plugin
     *
     * @see pluginDisable() subaction
     * @see pluginEnable() subaction
     * @param string $name Plugin name
     * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
     */
    public function pluginChange($name)
    {
        if (!$this->pluginIsKnown($name)) {
            return self::ACTION_FAILURE;
        }

        $pluginStatus = $this->pluginGetStatus($name);
        if (!in_array($pluginStatus, array('tochange', 'enabled'))) {
            return self::ACTION_FAILURE;
        }

        try {
            $this->pluginSetStatus($name, 'tochange');
            $this->pluginSetError($name, null);
            $ret = $this->pluginDisable($name, true);

            if ($ret == self::ACTION_SUCCESS) {
                $ret = $this->pluginEnable($name, true);

                if ($ret == self::ACTION_SUCCESS) {
                    if ($this->pluginHasBackend($name)) {
                        $this->backendRequest = true;
                    } else {
                        $pluginInfo = $this->pluginGetInfo($name);
                        $pluginInfo['__need_change__'] = false;
                        $this->pluginUpdateInfo($name, $pluginInfo);

                        try {
                            exec_query('UPDATE plugin set plugin_config_prev = plugin_config WHERE plugin_name = ?', $name);
                            $this->pluginSetStatus($name, 'enabled');
                        } catch (iMSCP_Exception_Database $e) {
                            throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
                        }
                    }
                } elseif ($ret == self::ACTION_STOPPED) {
                    $this->pluginSetStatus($name, $pluginStatus);
                } else {
                    throw new iMSCP_Plugin_Exception($this->pluginGetError($name));
                }
            } elseif ($ret == self::ACTION_STOPPED) {
                $this->pluginSetStatus($name, $pluginStatus);
            } else {
                throw new iMSCP_Plugin_Exception($this->pluginGetError($name));
            }

            return $ret;
        } catch (iMSCP_Plugin_Exception $e) {
            $this->pluginSetError($name, sprintf('Plugin change has failed: %s', $e->getMessage()));
            write_log(sprintf('Plugin Manager: %s plugin change has failed', $name), E_USER_ERROR);
        }

        return self::ACTION_FAILURE;
    }

    /**
     * Update the given plugin
     *
     * @see pluginDisable() subaction
     * @see pluginEnable() subaction
     * @param string $name Plugin name
     * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
     */
    public function pluginUpdate($name)
    {
        if (!$this->pluginIsKnown($name)) {
            return self::ACTION_FAILURE;
        }

        $pluginStatus = $this->pluginGetStatus($name);
        if (!in_array($pluginStatus, array('toupdate', 'enabled'))) {
            return self::ACTION_FAILURE;
        }

        try {
            $pluginInstance = $this->pluginLoad($name);
            $this->pluginSetStatus($name, 'toupdate');
            $this->pluginSetError($name, null);
            $ret = $this->pluginDisable($name, true);

            if ($ret == self::ACTION_SUCCESS) {
                $pluginInfo = $this->pluginGetInfo($name);
                $responses = $this->eventsManager->dispatch(iMSCP_Events::onBeforeUpdatePlugin, array(
                    'pluginManager' => $this,
                    'pluginName' => $name,
                    'fromVersion' => $pluginInfo['version'],
                    'toVersion' => $pluginInfo['__nversion__']
                ));

                if (!$responses->isStopped()) {
                    $pluginInstance->update($this, $pluginInfo['version'], $pluginInfo['__nversion__']);
                    $this->eventsManager->dispatch(iMSCP_Events::onAfterUpdatePlugin, array(
                        'pluginManager' => $this,
                        'pluginName' => $name,
                        'fromVersion' => $pluginInfo['version'],
                        'toVersion' => $pluginInfo['__nversion__']
                    ));

                    $ret = $this->pluginEnable($name, true);

                    if ($ret == self::ACTION_SUCCESS) {
                        if ($this->pluginHasBackend($name)) {
                            $this->backendRequest = true;
                        } else {
                            $pluginInfo['version'] = $pluginInfo['__nversion__'];
                            $this->pluginUpdateInfo($name, $pluginInfo);
                            $this->pluginSetStatus($name, 'enabled');
                        }
                    } elseif ($ret == self::ACTION_STOPPED) {
                        $this->pluginSetStatus($name, $pluginStatus);
                    } else {
                        throw new iMSCP_Plugin_Exception($this->pluginGetError($name));
                    }
                } elseif ($ret == self::ACTION_STOPPED) {
                    $this->pluginSetStatus($name, $pluginStatus);
                } else {
                    throw new iMSCP_Plugin_Exception($this->pluginGetError($name));
                }
            }

            return $ret;
        } catch (iMSCP_Plugin_Exception $e) {
            $this->pluginSetError($name, sprintf('Plugin update has failed: %s', $e->getMessage()));
            write_log(sprintf('Plugin Manager: %s plugin update has failed', $name), E_USER_ERROR);
        }

        return self::ACTION_FAILURE;
    }

    /**
     * Delete the given plugin
     *
     * @throws iMSCP_Plugin_Exception
     * @param string $name Plugin name
     * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
     */
    public function pluginDelete($name)
    {
        if (!$this->pluginIsKnown($name)) {
            return self::ACTION_FAILURE;
        }

        $pluginStatus = $this->pluginGetStatus($name);
        if (!in_array($pluginStatus, array('todelete', 'uninstalled', 'disabled'))) {
            return self::ACTION_FAILURE;
        }

        try {
            $pluginInstance = $this->pluginLoad($name);
            $this->pluginSetStatus($name, 'todelete');
            $this->pluginSetError($name, null);
            $responses = $this->eventsManager->dispatch(iMSCP_Events::onBeforeDeletePlugin, array(
                'pluginManager' => $this,
                'pluginName' => $name
            ));

            if (!$responses->isStopped()) {
                $pluginInstance->delete($this);
                $this->pluginDeleteData($name);
                $pluginDir = $this->pluginsDirectory . '/' . $name;

                if (is_dir($pluginDir) && !utils_removeDir($pluginDir)) {
                    set_page_message(tr('Plugin Manager: Unable to delete %s plugin files. You should run the set-gui-permissions.pl script and try again.', $name), 'warning');
                    write_log(sprintf('Plugin Manager: Unable to delete %s plugin files', $name), E_USER_WARNING);
                }

                $this->eventsManager->dispatch(iMSCP_Events::onAfterDeletePlugin, array(
                    'pluginManager' => $this,
                    'pluginName' => $name
                ));

                return self::ACTION_SUCCESS;
            }

            $this->pluginSetStatus($name, $pluginStatus);
            return self::ACTION_STOPPED;
        } catch (iMSCP_Plugin_Exception $e) {
            $this->pluginSetError($name, sprintf('Plugin deletion has failed: %s', $e->getMessage()));
            write_log(sprintf('Plugin Manager: %s plugin deletion has failed', $name), E_USER_ERROR);
        }

        return self::ACTION_FAILURE;
    }

    /**
     * Doesq the given plugin is protected?
     *
     * @throws iMSCP_Plugin_Exception in case the given plugin is not known
     * @param string $name Plugin name
     * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
     */
    public function pluginIsProtected($name)
    {
        if (!$this->pluginIsKnown($name)) {
            write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
            throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
        }

        if (!$this->isLoadedProtectedPluginsList) {
            $file = PERSISTENT_PATH . '/protected_plugins.php';
            $protectedPlugins = array();

            if (is_readable($file)) {
                include_once $file;
            }

            $this->protectedPlugins = $protectedPlugins;
            $this->isLoadedProtectedPluginsList = true;
        }

        return in_array($name, $this->protectedPlugins);
    }

    /**
     * Protect the given plugin
     *
     * @param string $name Name of the plugin to protect
     * @return bool self::ACTION_SUCCESS|self::ACTION_FAILURE
     */
    public function pluginProtect($name)
    {
        if (!$this->pluginIsEnabled($name) || $this->pluginIsProtected($name)) {
            return self::ACTION_FAILURE;
        }

        $responses = $this->eventsManager->dispatch(iMSCP_Events::onBeforeProtectPlugin, array(
            'pluginManager' => $this, 'pluginName' => $name
        ));

        if ($responses->isStopped()) {
            return self::ACTION_STOPPED;
        }

        $protectedPlugins = $this->protectedPlugins;
        $this->protectedPlugins[] = $name;

        if ($this->pluginUpdateProtectedFile()) {
            $this->eventsManager->dispatch(iMSCP_Events::onAfterProtectPlugin, array(
                'pluginManager' => $this, 'pluginName' => $name
            ));
            return self::ACTION_SUCCESS;
        }

        $this->protectedPlugins = $protectedPlugins;
        return self::ACTION_FAILURE;
    }

    /**
     * Does the given plugin is known by plugin manager?
     *
     * @param string $name Plugin name
     * @return bool TRUE if the given plugin is know by plugin manager , FALSE otherwise
     */
    public function pluginIsKnown($name)
    {
        return isset($this->pluginData[$name]);
    }

    /**
     * Does the given plugin provides a backend side?
     *
     * @throws iMSCP_Plugin_Exception in case $name is not known
     * @param string $name Plugin name
     * @return boolean TRUE if the given plugin provide backend part, FALSE otherwise
     */
    public function pluginHasBackend($name)
    {
        if ($this->pluginIsKnown($name)) {
            return $this->pluginData[$name]['backend'] == 'yes';
        }

        write_log(sprintf('Plugin Manager: Unknown plugin %s', $name), E_USER_ERROR);
        throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $name));
    }

    /**
     * Check plugin compatibility with current API
     *
     * @throws iMSCP_Plugin_Exception
     * @param string $name Plugin name
     * @param array $info Plugin info
     * @return void
     */
    public function pluginCheckCompat($name, array $info)
    {
        if (!isset($info['require_api'])
            || version_compare($this->pluginGetApiVersion(), $info['require_api'], '<')
        ) {
            throw new iMSCP_Plugin_Exception(tr('The %s plugin version %s is not compatible with your i-MSCP version.', $name, $info['version']));
        }

        if ($this->pluginIsKnown($name)) {
            $oldInfo = $this->pluginGetInfo($name);
            if ($oldInfo['version'] > $info['version']) {
                throw new iMSCP_Plugin_Exception(tr('Plugin Manager: Downgrade of %s plugin is not allowed.', $name), 'error');
            }
        }
    }

    /**
     * Update plugin list
     *
     * This method is responsible to update the plugin list and trigger plugin update, change and deletion.
     *
     * @return array An array containing information about added, updated and deleted plugins
     */
    public function pluginUpdateList()
    {
        $seenPlugins = array();
        $toUpdatePlugins = array();
        $toChangePlugins = array();
        $returnInfo = array('new' => 0, 'updated' => 0, 'changed' => 0, 'deleted' => 0);

        /** @var $file SplFileInfo */
        foreach (new RecursiveDirectoryIterator($this->pluginGetDirectory(), FilesystemIterator::SKIP_DOTS) as $file) {
            if (!$file->isDir() || !$file->isReadable()) {
                continue;
            }

            $name = $file->getBasename();

            if (!($plugin = $this->pluginLoad($name))) {
                set_page_message(tr('Plugin Manager: Unable to load plugin %s', $name), 'error');
                continue;
            }

            $seenPlugins[] = $name;
            $info = $plugin->getInfo();
            $infoPrev = $this->pluginIsKnown($name) ? $this->pluginGetInfo($name) : $info;
            $info['__nversion__'] = $info['version'];
            $info['version'] = $infoPrev['version'];

            if (version_compare($info['__nversion__'], $info['version'], '<')) {
                set_page_message(tr('Plugin Manager: Downgrade of %s plugin is not allowed.', $name), 'error');
                continue;
            }

            if (isset($infoPrev['db_schema_version'])) {
                $info['db_schema_version'] = $infoPrev['db_schema_version'];
            }

            $config = $plugin->getConfigFromFile();
            $configPrev = $this->pluginIsKnown($name) ? $plugin->getConfigPrev() : $config;
            $r = new ReflectionMethod($plugin, 'install');
            $info['__installable__'] = 'iMSCP_Plugin' !== $r->getDeclaringClass()->getName();
            $r = new ReflectionMethod($plugin, 'uninstall');
            $info['__uninstallable__'] = 'iMSCP_Plugin' !== $r->getDeclaringClass()->getName();

            if (!$this->pluginIsKnown($name)) {
                $status = ($info['__installable__']) ? 'uninstalled' : 'disabled';
                $needUpdate = false;
                $needChange = false;
                $returnInfo['new']++;
            } else {
                $status = $this->pluginGetStatus($name);
                $needUpdate = version_compare($info['version'], $info['__nversion__'], '<');
                $needChange = $infoPrev['__need_change__'];

                if (!$needChange
                    && !in_array($status, array('uninstalled', 'toinstall', 'touninstall', 'tochange', 'todelete'))
                    && $config != $configPrev
                ) {
                    $needChange = true;
                }
            }

            $info['__need_change__'] = $needChange;

            if ($needUpdate || $needChange || !$this->pluginIsKnown($name)) {
                $this->pluginUpdateData(array(
                    'name' => $name,
                    'type' => $plugin->getType(),
                    'info' => json_encode($info),
                    'config' => json_encode($config),
                    'config_prev' => json_encode($configPrev),
                    'priority' => isset($info['priority']) ? intval($info['priority']) : 0,
                    'status' => $status,
                    'backend' => file_exists($file->getPathname() . "/backend/$name.pm") ? 'yes' : 'no'
                ));

                if ($status == 'enabled') {
                    if ($needUpdate) {
                        $toUpdatePlugins[] = $name;
                        $returnInfo['updated']++;
                    } elseif ($needChange) {
                        $toChangePlugins[] = $name;
                        $returnInfo['changed']++;
                    }
                }
            }
        }

        // Make the plugin manager aware of the new plugin data
        $this->pluginLoadData();

        // Process plugin (update/change/deletion)
        foreach (array_keys($this->pluginData) as $name) {
            if (!in_array($name, $seenPlugins)) {
                if ($this->pluginDeleteData($name)) {
                    $returnInfo['deleted']++;
                }
            } elseif (in_array($name, $toUpdatePlugins)) {
                $ret = $this->pluginUpdate($name);
                if ($ret == self::ACTION_FAILURE || $ret == self::ACTION_STOPPED) {
                    $message = tr('Plugin Manager: Unable to update the %s plugin: %s', $name, $ret == self::ACTION_FAILURE ? tr('Action has failed.') : tr('Action has been stopped.'));
                    set_page_message($message, 'error');
                    $returnInfo['updated']--;
                }
            } elseif (in_array($name, $toChangePlugins)) {
                $ret = $this->pluginChange($name);
                if ($ret == self::ACTION_FAILURE || $ret == self::ACTION_STOPPED) {
                    $message = tr('Plugin Manager: Unable to change the %s plugin: %s', $name, $ret == self::ACTION_FAILURE ? tr('Action has failed.') : tr('Action has been stopped.'));
                    set_page_message($message, 'error');
                    $returnInfo['changed']--;
                }
            }
        }

        return $returnInfo;
    }

    /**
     * Load plugin data from database
     *
     * @return void
     */
    protected function pluginLoadData()
    {
        $this->pluginData = array();
        $this->pluginsByType = array();

        $stmt = execute_query('SELECT * FROM plugin');
        while ($plugin = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            $this->pluginData[$plugin['plugin_name']] = array(
                'info' => json_decode($plugin['plugin_info'], true),
                'status' => $plugin['plugin_status'],
                'error' => $plugin['plugin_error'],
                'backend' => $plugin['plugin_backend'],
                'locked' => $plugin['plugin_locked']
            );
            $this->pluginsByType[$plugin['plugin_type']][] = $plugin['plugin_name'];
        }
    }

    /**
     * Handle plugin protection file
     *
     * @return bool TRUE when protection file is successfully created/updated or removed FALSE otherwise
     */
    protected function pluginUpdateProtectedFile()
    {
        $file = PERSISTENT_PATH . '/protected_plugins.php';
        $lastUpdate = 'Last update: ' . date('Y-m-d H:i:s', time()) . ' by ' . $_SESSION['user_logged'];
        $content = "<?php\n/**\n * Protected plugin list\n * Auto-generated by i-MSCP Plugin Manager\n";
        $content .= " * $lastUpdate\n */\n\n";

        if (!empty($this->protectedPlugins)) {
            foreach ($this->protectedPlugins as $name) {
                $content .= "\$protectedPlugins[] = '$name';\n";
            }

            iMSCP_Utility_OpcodeCache::clearAllActive($file); // Be sure to load newest version on next call
            @unlink($file);

            if (@file_put_contents($file, "$content\n", LOCK_EX) === false) {
                set_page_message(tr('Plugin Manager: Unable to write the %s file for protected plugins.', $file), 'error');
                write_log(sprintf('Plugin Manager: Unable to write the %s file for protected plugins.', $file));
                return false;
            }

            return true;
        }

        if (@is_writable($file)) {
            iMSCP_Utility_OpcodeCache::clearAllActive($file); // Be sure to load newest version on next call
            if (!@unlink($file)) {
                write_log(sprintf('Plugin Manager: Unable to remove the %s file', $file), E_USER_WARNING);
                return false;
            }
        }

        return true;
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
                        plugin_status, plugin_backend
                    ) VALUE (
                        :name, :type, :info, :config, :config_prev, :priority, :status, :backend
                    )
                ',
                $data
            );
            return;
        }

        exec_query(
            '
                UPDATE plugin SET plugin_info = ?, plugin_config = ?, plugin_config_prev = ?, plugin_priority = ?,
                    plugin_status = ?, plugin_backend = ?
                WHERE plugin_name = ?
            ',
            array(
                $data['info'], $data['config'], $data['config_prev'], $data['priority'], $data['status'],
                $data['backend'], $data['name']
            )
        );
    }

    /**
     * Delete plugin data
     *
     * @param string $name Plugin name
     * @return bool TRUE if $name has been deleted from database, FALSE otherwise
     */
    protected function pluginDeleteData($name)
    {
        $stmt = exec_query('DELETE FROM plugin WHERE plugin_name = ?', $name);
        if (!$stmt->rowCount()) {
            return false;
        }

        // Force protected_plugins.php file to be regenerated or removed if needed
        if ($this->pluginIsProtected($name)) {
            $protectedPlugins = array_flip($this->protectedPlugins);
            unset($protectedPlugins[$name]);
            $this->protectedPlugins = array_flip($protectedPlugins);
            $this->pluginUpdateProtectedFile();
        }

        // Make the plugin manager aware of the deletion by reloading plugin data from database
        $this->pluginLoadData();
        write_log(sprintf('Plugin Manager: %s plugin has been removed from database', $name), E_USER_NOTICE);
        return true;
    }
}
