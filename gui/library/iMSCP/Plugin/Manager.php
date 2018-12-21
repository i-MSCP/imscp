<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Plugin_Action as PluginAction;
use iMSCP_Plugin_Exception as PluginException;
use iMSCP_Plugin_Exception_ActionStopped as PluginExceptionActionStopped;
use iMSCP_Registry as Registry;
use Zend_File_Transfer as FileTransfer;
use Zend_File_Transfer_Adapter_Abstract as FileTransferAdapterAbstract;

/**
 * Plugin Manager class
 */
class iMSCP_Plugin_Manager
{
    /**
     * @var array Events triggered by this object
     */
    protected $events = [
        Events::onBeforeSyncPluginData, Events::onAfterSyncPluginData,
        Events::onBeforeInstallPlugin, Events::onAfterInstallPlugin,
        Events::onBeforeUpdatePlugin, Events::onAfterUpdatePlugin,
        Events::onBeforeEnablePlugin, Events::onAfterEnablePlugin,
        Events::onBeforeDisablePlugin, Events::onAfterDisablePlugin,
        Events::onBeforeUninstallPlugin, Events::onAfterUninstallPlugin,
        Events::onBeforeDeletePlugin, Events::onAfterDeletePlugin,
        Events::onBeforeLockPlugin, Events::onAfterLockPlugin,
        Events::onBeforeUnlockPlugin, Events::onAfterUnlockPlugin
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
     * @var array[][\iMSCP\Json\LazyDecoder] Keys are plugin names and values are array containing plugin data
     */
    protected $pluginData = [];

    /**
     * @var array List of protected plugins
     */
    protected $protectedPlugins;

    /**
     * @var iMSCP_Plugin[]|PluginAction[] Array containing all loaded plugins
     */
    protected $loadedPlugins = [];

    /**
     * @var bool Whether or not a backend request should be sent
     */
    protected $backendRequest = false;

    /**
     * @var EventManager
     */
    protected $em;

    /**
     * iMSCP_Plugin_Manager constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->em = EventManager::getInstance()->addEvents('pluginManager', $this->events);
        $this->pluginLoadDataFromDatabase();
        spl_autoload_register([$this, 'autoload']);
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
            return;
        }

        $basename = substr($className, 13);
        @include_once $this->pluginGetRootDir() . "/$basename/$basename.php";
    }

    /**
     * Get event manager
     *
     * @return EventManager
     */
    public function getEventManager()
    {
        return $this->em;
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
     * Sets plugins root directory
     *
     * @param string $rootDir Plugin directory path
     * @return void
     */
    public function pluginSetRootDir($rootDir)
    {
        $rootDir = utils_normalizePath((string)$rootDir);

        if (!@is_dir($rootDir) || !@is_writable($rootDir)) {
            write_log(sprintf("Directory '%s' doesn't exist or is not writable", $rootDir), E_USER_ERROR);
            throw new PluginException(tr("Directory '%s' doesn't exist or is not writable", $rootDir));
        }

        $this->pluginsRootDir = $rootDir;
    }

    /**
     * Get plugins root directory
     *
     * @return string Plugin directory
     */
    public function pluginGetRootDir()
    {
        if (NULL === $this->pluginsRootDir) {
            $this->pluginsRootDir = utils_normalizePath(Registry::get('config')['PLUGINS_DIR']);
        }

        return $this->pluginsRootDir;
    }

    /**
     * Sets persistent data directory
     *
     * @param $persistentDataDir
     * @return void
     */
    public function pluginSetPersistentDataDir($persistentDataDir)
    {
        $persistentDataDir = utils_normalizePath((string)$persistentDataDir);

        if (!@is_dir($persistentDataDir) || !@is_writable($persistentDataDir)) {
            write_log(sprintf("Directory '%s' doesn't exist or is not writable", $persistentDataDir), E_USER_ERROR);
            throw new PluginException(tr("Directory '%s' doesn't exist or is not writable", $persistentDataDir));
        }

        $this->pluginPersistentDataDir = $persistentDataDir;
    }

    /**
     * Get persistent data directory
     *
     * @return string
     */
    public function pluginGetPersistentDataDir()
    {
        if (NULL === $this->pluginPersistentDataDir) {
            return $this->pluginPersistentDataDir = utils_normalizePath(PERSISTENT_PATH);
        }

        return $this->pluginPersistentDataDir;
    }

    /**
     * Returns list of known plugins
     *
     * @param bool $enabledOnly Flag indicating if only enabled plugins must be returned
     * @return array An array containing plugin names
     */
    public function pluginGetList($enabledOnly = true)
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
     * Is the given plugin known?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin is known , FALSE otherwise
     */
    public function pluginIsKnown($plugin)
    {
        return isset($this->pluginData[$plugin]);
    }

    /**
     * Is the given plugin loaded?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin is loaded, FALSE otherwise
     */
    public function pluginIsLoaded($plugin)
    {
        return isset($this->loadedPlugins[$plugin]);
    }

    /**
     * Loads the given plugin
     *
     * @param string $plugin Plugin name
     * @return iMSCP_Plugin|PluginAction Plugin instance
     */
    public function pluginLoad($plugin)
    {
        if ($this->pluginIsLoaded($plugin)) {
            return $this->loadedPlugins[$plugin];
        }

        $class = "iMSCP_Plugin_$plugin";
        if (!class_exists($class, true)) {
            write_log(sprintf("Couldn't load the %s plugin - Plugin class not found.", $plugin), E_USER_ERROR);
            throw new iMSCP_Plugin_Exception(tr("Couldn't load the %s plugin - Plugin class not found.", $plugin));
        }

        $this->loadedPlugins[$plugin] = new $class($this);

        if ($this->pluginIsKnown($plugin) && $this->loadedPlugins[$plugin] instanceof PluginAction) {
            $this->loadedPlugins[$plugin]->register($this->getEventManager());
        }

        return $this->loadedPlugins[$plugin];
    }

    /**
     * Get list of loaded plugins
     *
     * @return iMSCP_Plugin[]|PluginAction[] Array containing plugins instances
     */
    public function pluginGetLoaded()
    {
        return $this->loadedPlugins;
    }

    /**
     * Get instance of a loaded plugin
     *
     * @param string $plugin Plugin name
     * @return iMSCP_Plugin|PluginAction
     */
    public function pluginGet($plugin)
    {
        if (!$this->pluginIsLoaded($plugin)) {
            throw new PluginException(tr("The %s plugin isn't loaded", $plugin));
        }

        return $this->loadedPlugins[$plugin];
    }

    /**
     * Get status of the given plugin
     *
     * @param string $plugin Plugin name
     * @return string Plugin status
     */
    public function pluginGetStatus($plugin)
    {
        if (!$this->pluginIsKnown($plugin)) {
            throw new PluginException(tr('Unknown plugin: %s', $plugin));
        }

        return $this->pluginData[$plugin]['status'];
    }

    /**
     * Set status for the given plugin
     *
     * @param string $plugin Plugin name
     * @param string $status New plugin status
     * @return void
     */
    public function pluginSetStatus($plugin, $status)
    {
        if ($status === $this->pluginGetStatus($plugin) && NULL === $this->pluginGetError($plugin)) {
            return;
        }

        try {
            exec_query('UPDATE plugin SET plugin_status = ?, plugin_error = NULL WHERE plugin_name = ?', [$status, $plugin]);
            $this->pluginData[$plugin]['status'] = $status;
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get plugin error
     *
     * @param null|string $plugin Plugin name
     * @return string|null Plugin error string or NULL if no error
     */
    public function pluginGetError($plugin)
    {
        if (!$this->pluginIsKnown($plugin)) {
            throw new PluginException(tr('Unknown plugin: %s', $plugin));
        }

        return $this->pluginData[$plugin]['error'];
    }

    /**
     * Set error for the given plugin
     *
     * @param string $plugin Plugin name
     * @param null|string $error Plugin error string or NULL if no error
     * @return void
     */
    public function pluginSetError($plugin, $error = NULL)
    {
        if ($error === $this->pluginGetError($plugin)) {
            return;
        }

        try {
            exec_query('UPDATE plugin SET plugin_error = ? WHERE plugin_name = ?', [$error, $plugin]);
            $this->pluginData[$plugin]['error'] = $error;
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Does the given plugin has error?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin has error, FALSE otherwise
     */
    public function pluginHasError($plugin)
    {
        return NULL !== $this->pluginGetError($plugin);
    }

    /**
     * Returns plugin info
     *
     * @param string $plugin Plugin name
     * @return \iMSCP\Json\LazyDecoder An array containing plugin info
     */
    public function pluginGetInfo($plugin)
    {
        if (!$this->pluginIsKnown($plugin)) {
            throw new PluginException(tr('Unknown plugin: %s', $plugin));
        }

        return $this->pluginData[$plugin]['info'];
    }

    /**
     * Update plugin info
     *
     * @param string $plugin Plugin Name
     * @param array $info Plugin info
     * @return void
     */
    public function pluginUpdateInfo($plugin, array $info)
    {
        if ($this->pluginCompareData($info, $this->pluginGetInfo($plugin)->toArray())) {
            return;
        }

        try {
            exec_query('UPDATE plugin SET plugin_info = ? WHERE plugin_name = ?', [json_encode($info), $plugin]);
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Plugin upload
     *
     * @return void
     */
    public function pluginUpload()
    {
        /** @var FileTransferAdapterAbstract $upload */
        $upload = new FileTransfer();
        $upload->setTranslator(Registry::get('translator'));
        $upload->addPrefixPath('iMSCP_Plugin_Validate_File_', 'iMSCP/Plugin/Validate/File', FileTransferAdapterAbstract::VALIDATE);
        $upload->addValidator('Count', true, 1);
        $upload->addValidator('Size', true, utils_getMaxFileUpload());
        $upload->addValidator('Plugin', true, $this);
        $upload->addPrefixPath('iMSCP_Plugin_Filter_File_', 'iMSCP/Plugin/Filter/File', FileTransferAdapterAbstract::FILTER);
        $upload->addFilter('Plugin', $this->pluginGetRootDir());

        if (!$upload->receive()) {
            throw new PluginException(implode("<br>", $upload->getMessages()));
        }

        $plugin = basename($upload->getFileName());
        $this->pluginUpdateData($plugin);
    }

    /**
     * is the given plugin locked?
     *
     * @param string $plugin Plugin name
     * @param string|null $locker OPTIONAL Locker name (default any locker)
     * @return bool TRUE if the given plugin is locked, false otherwise
     */
    public function pluginIsLocked($plugin, $locker = NULL)
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
     * Lock the given plugin
     *
     * @param string $plugin Plugin name
     * @param string $locker Locker name
     * @return void
     */
    public function pluginLock($plugin, $locker)
    {
        if ($this->pluginIsLocked($plugin, $locker)) {
            return;
        }

        try {
            $responses = $this->em->dispatch(Events::onBeforeLockPlugin, [
                'pluginName'   => $plugin,
                'pluginLocker' => $locker
            ]);

            if ($responses->isStopped()) {
                return;
            }

            /** @var \iMSCP\Json\LazyDecoder $lockers */
            $lockers = $this->pluginData[$plugin]['lockers'];
            $lockers[$locker] = 1;
            exec_query('UPDATE plugin SET plugin_lockers = ? WHERE plugin_name = ?', [json_encode($lockers->toArray(), JSON_FORCE_OBJECT), $plugin]);
            $this->em->dispatch(Events::onAfterLockPlugin, [
                'pluginName'   => $plugin,
                'pluginLocker' => $locker
            ]);
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Unlock the given plugin
     *
     * @param string $plugin Plugin name
     * @param string $locker Locker name
     * @return void
     */
    public function pluginUnlock($plugin, $locker)
    {
        if (!$this->pluginIsLocked($plugin, $locker)) {
            return;
        }

        try {
            $responses = $this->em->dispatch(Events::onBeforeUnlockPlugin, [
                'pluginName'   => $plugin,
                'pluginLocker' => $locker
            ]);

            if ($responses->isStopped()) {
                return;
            }

            /** @var \iMSCP\Json\LazyDecoder $lockers */
            $lockers = $this->pluginData[$plugin]['lockers'];
            unset($lockers[$locker]);
            exec_query('UPDATE plugin SET plugin_lockers = ? WHERE plugin_name = ?', [json_encode($lockers->toArray(), JSON_FORCE_OBJECT), $plugin]);
            $this->em->dispatch(Events::onAfterUnlockPlugin, [
                'pluginName'   => $plugin,
                'pluginLocker' => $locker
            ]);
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Is the given plugin installable?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin is installable, FALSE otherwise
     */
    public function pluginIsInstallable($plugin)
    {
        $info = $this->pluginGetInfo($plugin);

        if (isset($info['__installable__'])) {
            return $info['__installable__'];
        }

        $r = new ReflectionMethod($this->pluginLoad($plugin), 'install');

        return 'iMSCP_Plugin' !== $r->getDeclaringClass()->getName();
    }

    /**
     * Is the given plugin is installed?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin is installed FALSE otherwise
     */
    public function pluginIsInstalled($plugin)
    {
        return !in_array($this->pluginGetStatus($plugin), ['toinstall', 'uninstalled']);
    }

    /**
     * Install the given plugin
     *
     * @param string $plugin Plugin name
     * @return void
     */
    public function pluginInstall($plugin)
    {
        $pluginStatus = $this->pluginGetStatus($plugin);

        if (!in_array($pluginStatus, ['toinstall', 'uninstalled'])) {
            throw new PluginException(tr("The '%s' action is forbidden for the %s plugin.", 'install', $plugin));
        }

        try {
            $this->pluginSetStatus($plugin, 'toinstall');
            $responses = $this->em->dispatch(Events::onBeforeInstallPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);

            if ($responses->isStopped()) {
                $this->pluginSetStatus($plugin, $pluginStatus);
                throw new PluginExceptionActionStopped(tr("The '%s' action has been stopped for the %s plugin.", 'install', $plugin));
            }

            $this->pluginLoad($plugin)->install($this);
            $this->em->dispatch(Events::onAfterInstallPlugin, [
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
            if (!($e instanceof PluginExceptionActionStopped)) {
                $this->pluginSetError($plugin, $e->getMessage());
                write_log(sprintf('Installation of the %s plugin has failed.', $plugin), E_USER_ERROR);
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
    public function pluginIsUninstallable($plugin)
    {
        $info = $this->pluginGetInfo($plugin);

        if (isset($info['__uninstallable__'])) {
            return $info['__uninstallable__'];
        }

        $r = new ReflectionMethod($this->pluginLoad($plugin), 'uninstall');

        return 'iMSCP_Plugin' !== $r->getDeclaringClass()->getName();
    }

    /**
     * Is the given plugin uninstalled?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin is uninstalled FALSE otherwise
     */
    public function pluginIsUninstalled($plugin)
    {
        return $this->pluginGetStatus($plugin) == 'uninstalled';
    }

    /**
     * Uninstall the given plugin
     *
     * @param string $plugin Plugin name
     * @return void
     */
    public function pluginUninstall($plugin)
    {
        $pluginStatus = $this->pluginGetStatus($plugin);

        if (!in_array($pluginStatus, ['touninstall', 'disabled']) || !$this->pluginIsUninstallable($plugin)) {
            throw new PluginException(tr("The '%s' action is forbidden for the %s plugin.", 'uninstall', $plugin));
        }

        try {
            $this->pluginSetStatus($plugin, 'touninstall');
            $responses = $this->em->dispatch(Events::onBeforeUninstallPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);

            if ($responses->isStopped()) {
                $this->pluginSetStatus($plugin, $pluginStatus);
                throw new PluginExceptionActionStopped(tr("The '%s' action has been stopped for the %s plugin.", 'uninstall', $plugin));
            }

            $this->pluginLoad($plugin)->uninstall($this);
            $this->em->dispatch(Events::onAfterUninstallPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);

            if ($this->pluginHasBackend($plugin)) {
                $this->backendRequest = true;
                return;
            }

            $this->pluginSetStatus($plugin, $this->pluginIsInstallable($plugin) ? 'uninstalled' : 'disabled');
        } catch (PluginException $e) {
            if (!($e instanceof PluginExceptionActionStopped)) {
                $this->pluginSetError($plugin, $e->getMessage());
                write_log(sprintf('Uninstallation of the %s plugin has failed.', $plugin), E_USER_ERROR);
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
    public function pluginIsEnabled($plugin)
    {
        return $this->pluginGetStatus($plugin) == 'enabled';
    }

    /**
     * Enable the given plugin
     *
     * @param string $plugin Plugin name
     * @param bool $isSubAction Flag indicating whether or not this action is called in context of the install update or change action
     * @return void
     */
    public function pluginEnable($plugin, $isSubAction = false)
    {
        $pluginStatus = $this->pluginGetStatus($plugin);

        if (!$isSubAction && !in_array($pluginStatus, ['toenable', 'disabled'])) {
            throw new PluginException(tr("The '%s' action is forbidden for the %s plugin.", 'enable', $plugin));
        }

        try {
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

                $this->pluginSetStatus($plugin, 'toenable');
            }

            $responses = $this->em->dispatch(Events::onBeforeEnablePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);

            if (!$isSubAction && $responses->isStopped()) {
                $this->pluginSetStatus($plugin, $pluginStatus);
                throw new PluginExceptionActionStopped(tr("The '%s' action has been stopped for the %s plugin.", 'enable', $plugin));
            }

            $this->pluginLoad($plugin)->enable($this);
            $this->em->dispatch(Events::onAfterEnablePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);

            if ($this->pluginHasBackend($plugin)) {
                $this->backendRequest = true;
            } elseif (!$isSubAction) {
                $this->pluginSetStatus($plugin, 'enabled');
            }
        } catch (PluginException $e) {
            if (!($e instanceof PluginExceptionActionStopped)) {
                $this->pluginSetError($plugin, $e->getMessage());
                write_log(sprintf('Activation of the %s plugin has failed.', $plugin), E_USER_ERROR);
            }

            throw $e;
        }
    }

    /**
     * Is the given plugin disabled?
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given is disabled, FALSE otherwise
     */
    public function pluginIsDisabled($plugin)
    {
        return $this->pluginGetStatus($plugin) == 'disabled';
    }

    /**
     * Disable the given plugin
     *
     * @param string $plugin Plugin name
     * @param bool $isSubAction Flag indicating whether or not this action is called in context of the install update or change action
     * @return void
     */
    public function pluginDisable($plugin, $isSubAction = false)
    {
        $pluginStatus = $this->pluginGetStatus($plugin);

        if (!$isSubAction && !in_array($pluginStatus, ['todisable', 'enabled'])) {
            throw new PluginException(tr("The '%s' action is forbidden for the %s plugin.", 'disable', $plugin));
        }

        try {
            if (!$isSubAction) {
                $this->pluginSetStatus($plugin, 'todisable');
            }

            $responses = $this->em->dispatch(Events::onBeforeDisablePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);

            if (!$isSubAction && $responses->isStopped()) {
                $this->pluginSetStatus($plugin, $pluginStatus);
                throw new PluginExceptionActionStopped(tr("The '%s' action has been stopped for the %s plugin.", 'disable', $plugin));
            }

            $this->pluginLoad($plugin)->disable($this);
            $this->em->dispatch(Events::onAfterDisablePlugin, [
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
            if (!($e instanceof PluginExceptionActionStopped)) {
                $this->pluginSetError($plugin, $e->getMessage());
                write_log(sprintf('Deactivation of the %s plugin has failed.', $plugin), E_USER_ERROR);
            }

            throw $e;
        }
    }

    /**
     * Change (reconfigure) the given plugin
     *
     * @param string $plugin Plugin name
     * @param bool $isSubAction Flag indicating whether or not this action is called in context of the update action
     * @return void
     */
    public function pluginChange($plugin, $isSubAction = false)
    {
        $pluginStatus = $this->pluginGetStatus($plugin);

        if (!$isSubAction && !in_array($pluginStatus, ['tochange', 'enabled'])) {
            throw new PluginException(tr("The '%s' action is forbidden for the %s plugin.", 'change', $plugin));
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
                exec_query('UPDATE plugin SET plugin_config_prev = plugin_config WHERE plugin_name = ?', $plugin);
                $this->pluginSetStatus($plugin, 'enabled');
            } catch (Exception $e) {
                throw new PluginException($e->getMessage(), $e->getCode(), $e);
            }
        } catch (PluginException $e) {
            if (!($e instanceof PluginExceptionActionStopped)) {
                $this->pluginSetError($plugin, $e->getMessage());
                write_log(sprintf('Reconfiguration of the %s plugin has failed.', $plugin), E_USER_ERROR);
            }

            throw $e;
        }
    }

    /**
     * Update the given plugin
     *
     * @param string $plugin Plugin name
     * @return void
     */
    public function pluginUpdate($plugin)
    {
        $pluginStatus = $this->pluginGetStatus($plugin);

        if (!in_array($pluginStatus, ['toupdate', 'enabled'])) {
            throw new PluginException(tr("The '%s' action is forbidden for the %s plugin.", 'update', $plugin));
        }

        try {
            $this->pluginSetStatus($plugin, 'toupdate');
            $this->pluginDisable($plugin, true);
            $pluginInfo = $this->pluginGetInfo($plugin);
            $fullVersionNew = $pluginInfo['__nversion__'] . '.' . $pluginInfo['__nbuild__'];
            $fullVersionOld = $pluginInfo['version'] . '.' . $pluginInfo['build'];
            $responses = $this->em->dispatch(Events::onBeforeUpdatePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin,
                'fromVersion'   => $fullVersionOld,
                'toVersion'     => $fullVersionNew
            ]);

            if ($responses->isStopped()) {
                $this->pluginSetStatus($plugin, $pluginStatus);
                throw new PluginExceptionActionStopped(tr("The '%s' action has been stopped for the %s plugin.", 'update', $plugin));
            }

            $this->pluginLoad($plugin)->update($this, $fullVersionOld, $fullVersionNew);

            if (!$this->pluginHasBackend($plugin)) {
                $pluginInfo['version'] = $pluginInfo['__nversion__'];
                $pluginInfo['build'] = $pluginInfo['__nbuild__'];
                $this->pluginUpdateInfo($plugin, $pluginInfo->toArray());
            }

            $this->em->dispatch(Events::onAfterUpdatePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin,
                'fromVersion'   => $fullVersionOld,
                'toVersion'     => $fullVersionNew
            ]);
            $this->pluginEnable($plugin, true);

            if ($this->pluginHasBackend($plugin)) {
                $this->backendRequest = true;
                return;
            }

            $this->pluginSetStatus($plugin, 'enabled');
        } catch (PluginException $e) {
            if (!($e instanceof PluginExceptionActionStopped)) {
                $this->pluginSetError($plugin, $e->getMessage());
                write_log(sprintf('Update of the %s plugin has failed.', $plugin), E_USER_ERROR);
            }

            throw $e;
        }
    }

    /**
     * Delete the given plugin
     *
     * @param string $plugin Plugin name
     * @return void
     */
    public function pluginDelete($plugin)
    {
        $pluginStatus = $this->pluginGetStatus($plugin);

        if (!in_array($pluginStatus, ['todelete', 'uninstalled', 'disabled']) || $this->pluginIsLocked($plugin)) {
            throw new PluginException(tr("The '%s' action is forbidden for the %s plugin.", 'delete', $plugin));
        }

        try {
            $this->pluginSetStatus($plugin, 'todelete');
            $responses = $this->em->dispatch(Events::onBeforeDeletePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);

            if ($responses->isStopped()) {
                $this->pluginSetStatus($plugin, $pluginStatus);
                throw new PluginExceptionActionStopped(tr("The '%s' action has been stopped for the %s plugin.", 'delete', $plugin));
            }

            $this->pluginLoad($plugin)->delete($this);

            if (!utils_removeDir(utils_normalizePath($this->pluginsRootDir . '/' . $plugin))) {
                throw new PluginException(tr("Couldn't delete the %s plugin. You should fix the file permissions and try again.", $plugin));
            }

            try {
                exec_query('DELETE FROM plugin WHERE plugin_name = ?', [$plugin]);
            } catch (Exception $e) {
                throw new PluginException($e->getMessage(), $e->getCode(), $e);
            }

            $this->em->dispatch(Events::onAfterDeletePlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);
        } catch (PluginException $e) {
            if (!($e instanceof PluginExceptionActionStopped)) {
                $this->pluginSetError($plugin, $e->getMessage());
                write_log(sprintf('Deletion of the %s plugin has failed', $plugin), E_USER_ERROR);
            }

            throw $e;
        }
    }

    /**
     * is the given plugin protected?
     *
     * @param string $plugin Plugin name
     * @return int
     */
    public function pluginIsProtected($plugin)
    {
        if (!$this->pluginIsKnown($plugin)) {
            throw new PluginException(tr('Unknown plugin: %s', $plugin));
        }

        if (NULL == $this->protectedPlugins) {
            $this->protectedPlugins = [];
            $file = $this->pluginGetPersistentDataDir() . '/protected_plugins.php';
            if (is_readable($file)) {
                $this->protectedPlugins = include $file;
            }
        }

        return in_array($plugin, $this->protectedPlugins);
    }

    /**
     * Protect the given plugin
     *
     * @param string $plugin Name of the plugin to protect
     * @return void
     */
    public function pluginProtect($plugin)
    {
        if (!$this->pluginIsEnabled($plugin) || $this->pluginIsProtected($plugin)) {
            throw new PluginException(tr("The '%s' action is forbidden for the %s plugin.", 'protect', $plugin));
        }

        try {
            $responses = $this->em->dispatch(Events::onBeforeProtectPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);

            if ($responses->isStopped()) {
                throw new PluginExceptionActionStopped(tr("The '%s' action has been stopped for the %s plugin.", 'protect', $plugin));
            }

            $this->protectedPlugins[] = $plugin;

            $file = utils_normalizePath($this->pluginGetPersistentDataDir() . '/protected_plugins.php');
            $content = sprintf("<?php\n/**\n * Protected plugin list\n * Auto-generated on %s\n */\n\n", date('Y-m-d H:i:s', time()));
            $content .= "return " . var_export($this->protectedPlugins, true) . ";\n";

            if (@file_put_contents($file, $content, LOCK_EX) === false) {
                write_log(sprintf("Couldn't write the %s file.", $file));
                throw new PluginException(tr("Couldn't write the %s file.", $file));
            }

            iMSCP_Utility_OpcodeCache::clearAllActive($file);

            $this->em->dispatch(Events::onAfterProtectPlugin, [
                'pluginManager' => $this,
                'pluginName'    => $plugin
            ]);
        } catch (PluginException $e) {
            if (!($e instanceof PluginExceptionActionStopped)) {
                write_log(sprintf('Protection of the %s plugin has failed', $plugin), E_USER_ERROR);
            }

            throw $e;
        }
    }

    /**
     * Does the given plugin provides a backend side?
     *
     * @param string $plugin Plugin name
     * @return boolean TRUE if the given plugin provide backend part, FALSE otherwise
     */
    public function pluginHasBackend($plugin)
    {
        if (!$this->pluginIsKnown($plugin)) {
            throw new PluginException(tr('Unknown plugin: %s', $plugin));
        }

        return $this->pluginData[$plugin]['backend'] == 'yes';
    }

    /**
     * Check plugin compatibility
     *
     * @param string $plugin Plugin name
     * @param array $info Plugin info
     * @return void
     */
    public function pluginCheckCompat($plugin, array $info)
    {
        if (!isset($info['require_api']) || version_compare($this->pluginGetApiVersion(), $info['require_api'], '<')) {
            throw new PluginException(
                tr('The %s plugin version %s (build %d) is not compatible with your i-MSCP version.', $plugin, $info['version'], $info['build'])
            );
        }

        if (!$this->pluginIsKnown($plugin)) {
            return;
        }

        $oldInfo = $this->pluginGetInfo($plugin);
        if (version_compare($oldInfo['version'] . '.' . $oldInfo['build'], $info['version'] . '.' . $info['build'], '>')) {
            throw new PluginException(
                tr('Downgrade of the %s plugin to version %s (build %s) is forbidden.', $plugin, $info['version'], $info['build']), 'error'
            );
        }
    }

    /**
     * Synchronize all plugins data, executing update/change actions when needed
     *
     * @return void
     */
    public function pluginSyncData()
    {
        $responses = $this->em->dispatch(Events::onBeforeSyncPluginData, ['pluginManager' => $this]);
        if ($responses->isStopped()) {
            return;
        }

        /** @var $file SplFileInfo */
        foreach (new RecursiveDirectoryIterator($this->pluginGetRootDir(), FilesystemIterator::SKIP_DOTS) as $file) {
            if (!$file->isDir() || !$file->isReadable()) {
                continue;
            }

            $this->pluginUpdateData($file->getBasename());
        }

        $this->em->dispatch(Events::onAfterSyncPluginData, ['pluginManager' => $this]);
    }

    /**
     * Guess action to execute for the given plugin according its current status
     *
     * @param string $plugin Plugin name
     * @return string Action to be executed for the given plugin
     */
    public function pluginGuessAction($plugin)
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
                throw new PluginException(tr("Unknown status '%s' for the %s plugin", $status, $plugin));
        }
    }

    /**
     * Translate the given plugin status
     *
     * @param $status
     * @return string
     * @throws Zend_Exception
     */
    public function pluginTranslateStatus($status)
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

    /**
     * Load plugin data from database
     *
     * @return void
     */
    protected function pluginLoadDataFromDatabase()
    {
        $this->pluginData = [];
        $stmt = execute_query(
            '
              SELECT plugin_name, plugin_type, plugin_info, plugin_status, plugin_error, plugin_backend, plugin_lockers
              FROM plugin
              ORDER BY plugin_priority DESC
            '
        );
        while ($plugin = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
            $this->pluginData[$plugin['plugin_name']] = [
                'info'    => new iMSCP\Json\LazyDecoder($plugin['plugin_info']),
                'status'  => $plugin['plugin_status'],
                'error'   => $plugin['plugin_error'],
                'backend' => $plugin['plugin_backend'],
                'lockers' => new iMSCP\Json\LazyDecoder($plugin['plugin_lockers'])
            ];
        }
    }

    /**
     * Store plugin data in database
     *
     * @param array $data Plugin data
     * @return void
     */
    protected function pluginStoreDataInDatabase(array $data)
    {
        try {
            exec_query(
                '
                    INSERT INTO plugin (
                        plugin_name, plugin_type, plugin_info, plugin_config, plugin_config_prev, plugin_priority, plugin_status, plugin_backend,
                        plugin_lockers
                    ) VALUE ( ?, ?, ?, ?, ?, ?, ?, ?, ? ) ON DUPLICATE KEY UPDATE
                        plugin_info = ?, plugin_config = ?, plugin_config_prev = ?, plugin_priority = ?, plugin_status = ?, plugin_backend = ?,
                        plugin_lockers = ?
                ',
                [
                    // Insert data
                    $data['name'], $data['type'], $data['info'], $data['config'], $data['config_prev'], $data['priority'], $data['status'],
                    $data['backend'], $data['lockers'],
                    // Update data
                    $data['info'], $data['config'], $data['config_prev'], $data['priority'], $data['status'], $data['backend'], $data['lockers']
                ]
            );
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Compare the given plugin data
     *
     * @param array $aData
     * @param array $bData
     * @return bool TRUE if data are identical (order doesn't matter), FALSE otherwise
     */
    protected function pluginCompareData(array &$aData, array &$bData)
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
     * Update data for the given plugin, executing update/change actions when needed
     *
     * @param string $plugin
     * @return void
     */
    protected function pluginUpdateData($plugin)
    {
        try {
            $pluginInstance = $this->pluginLoad($plugin);
        } catch (iMSCP_Plugin_Exception $e) {
            set_page_message($e->getMessage(), 'static_error');
            return;
        }

        $pluginIsKnown = $this->pluginIsKnown($plugin);
        $info = $pluginInstance->getInfo();
        $infoPrev = $this->pluginIsKnown($plugin) ? $this->pluginGetInfo($plugin) : $info;
        $info['__nversion__'] = $info['version'];
        $info['version'] = $infoPrev['version'];
        $info['__nbuild__'] = isset($info['build']) ? $info['build'] : '0000000000';
        $info['build'] = isset($infoPrev['build']) ? $infoPrev['build'] : '0000000000';
        $fullVersionNew = $info['__nversion__'] . '.' . $info['__nbuild__'];
        $fullVersionOld = $info['version'] . '.' . $info['build'];

        if (version_compare($fullVersionNew, $fullVersionOld, '<')) {
            set_page_message(tr('Downgrade of the %s plugin is forbidden.', $plugin), 'static_error');
            return;
        }

        if (isset($infoPrev['db_schema_version'])) {
            $info['db_schema_version'] = $infoPrev['db_schema_version'];
        }

        $configNew = $pluginInstance->getConfigFromFile();
        $configOld = $pluginIsKnown ? $pluginInstance->getConfig() : $configNew;
        $r = new ReflectionMethod($pluginInstance, 'install');
        $info['__installable__'] = 'iMSCP_Plugin' !== $r->getDeclaringClass()->getName();
        $r = new ReflectionMethod($pluginInstance, 'uninstall');
        $info['__uninstallable__'] = 'iMSCP_Plugin' !== $r->getDeclaringClass()->getName();
        $action = 'none';

        if ($pluginIsKnown) {
            $status = $this->pluginGetStatus($plugin);
            $lockers = $this->pluginData[$plugin]['lockers'];
            $hashChanges = !$this->pluginCompareData($info, $infoPrev->toArray()) || !$this->pluginCompareData($configNew, $configOld);
            $pluginHasError = $this->pluginHasError($plugin);

            if ($hashChanges) {
                if ($this->pluginIsProtected($plugin)) {
                    set_page_message(tr('The %s plugin changes were ignored as this one is protected.', $plugin), 'static_warning');
                    return;
                }

                if (!$pluginHasError && !in_array($status, ['uninstalled', 'enabled', 'disabled'])) {
                    set_page_message(tr('Changes for the %s plugin were ignored as there is a pending task for this one. Please retry once the task is completed.', $plugin), 'static_warning');
                    return;
                }
            }

            if ($hashChanges && !$pluginHasError) {
                if (version_compare($fullVersionNew, $fullVersionOld, '>')) {
                    $action = 'toupdate';
                } elseif ($hashChanges) {
                    $action = 'tochange';
                }
            }
        } else {
            $status = $info['__installable__'] ? 'uninstalled' : 'disabled';
            $lockers = new \iMSCP\Json\LazyDecoder('{}');
            $action = 'store';
        }

        if ($action == 'none') {
            set_page_message(tr("No changes were detected for the %s plugin.", $plugin), 'success');
            return;
        }

        $this->pluginStoreDataInDatabase([
            'name'        => $plugin,
            'type'        => $pluginInstance->getType(),
            'info'        => json_encode($info),
            'config'      => json_encode($configNew),
            // On plugin change/update, make sure that config_prev also contains new parameters
            'config_prev' => json_encode($pluginIsKnown ? array_merge($configNew, $configOld) : $configNew),
            'priority'    => isset($info['priority']) ? intval($info['priority']) : 0,
            'status'      => $status,
            'backend'     => file_exists($this->pluginGetRootDir() . "/$plugin/backend/$plugin.pm") ? 'yes' : 'no',
            'lockers'     => json_encode($lockers->toArray(), JSON_FORCE_OBJECT),
        ]);

        if ($action == 'store') {
            set_page_message(tr("New %s plugin has been detected.", $plugin), 'success');
            return;
        }

        try {
            switch ($action) {
                case 'toupdate':
                    $this->pluginUpdate($plugin);
                    break;
                case 'tochange':
                    $this->pluginChange($plugin);
            }

            if ($this->pluginHasBackend($plugin)) {
                set_page_message(tr("Action '%s' successfully scheduled for the %s plugin.", $action, $plugin), 'success');
                return;
            }

            set_page_message(tr("Action '%s' successfully executed for the %s plugin.", $action, $plugin), 'success');
        } catch (PluginException $e) {
            set_page_message($e->getMessage(), 'static_error');
        }
    }

    /**
     * Does the given plugin requires change
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin requires change, FALSE otherwise
     */
    protected function pluginRequireChange($plugin)
    {
        $pluginInstance = $this->pluginLoad($plugin);
        return !$this->pluginCompareData($pluginInstance->getConfig(), $pluginInstance->getConfigPrev());
    }

    /**
     * Does the given plugin requires update
     *
     * @param string $plugin Plugin name
     * @return bool TRUE if the given plugin requires change, FALSE otherwise
     */
    protected function pluginRequireUpdate($plugin)
    {
        $pluginInfo = $this->pluginGetInfo($plugin);
        return version_compare($pluginInfo['nversion'] . '.' . $pluginInfo['nbuild'], $pluginInfo['version'] . '.' . $pluginInfo['build'], '>');
    }
}
