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
 * @subpackage  Plugin_Manager
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Plugin Manager class
 *
 * See http://forum.i-mscp.net/Thread-DEV-Plugin-API-documentation-Relation-between-plugin-status-and-actions for more
 * info about specification.
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Plugin_Manager
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_Plugin_Manager
{
	/**
	 * @const string Plugin API version
	 */
	const PLUGIN_API_VERSION = '0.2.0';

	/**
	 * @const int Action failure
	 */
	const ACTION_FAILURE = 0;

	/**
	 * @const int Action stopped
	 */
	const ACTION_STOPPED = -1;

	/**
	 * @const int Action success
	 */
	const ACTION_SUCCESS = 1;

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
	 * @var array Array containing all loaded plugins
	 */
	protected $loadedPlugins = array();

	/**
	 * @var bool Whether or not a backend request should be sent
	 */
	protected $backendRequest = false;

	/**
	 * @var iMSCP_Events_Manager
	 */
	protected $eventsManager = null;

	/**
	 * Constructor
	 *
	 * @throws iMSCP_Plugin_Exception In case $pluginDir is not valid
	 * @param string $pluginDir Plugin directory
	 * @return iMSCP_Plugin_Manager
	 */
	public function __construct($pluginDir)
	{
		if (@is_dir($pluginDir)) {
			$this->setPluginDirectory($pluginDir);
			$this->eventsManager = iMSCP_Events_Manager::getInstance();
			$this->init();
			spl_autoload_register(array($this, '_autoload'));
		} else {
			write_log(sprintf('Plugin Manager: Invalid plugin directory: %s', $pluginDir), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Invalid plugin directory: %s', $pluginDir));
		}
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
			list(, , $className) = explode('_', $className, 3);
			$filePath = $this->pluginsDirectory . "/$className/$className.php";

			if (@is_readable($filePath)) {
				require_once $filePath;
			}
		}
	}

	/**
	 * Returns plugin API version
	 *
	 * @return string Plugin API version
	 */
	public function getPluginApiVersion()
	{
		return self::PLUGIN_API_VERSION;
	}

	/**
	 * Sets plugins directory
	 *
	 * @throws iMSCP_Plugin_Exception In case $pluginDirectory doesn't exist or is not readable.
	 * @param string $pluginDir Plugin directory path
	 */
	public function setPluginDirectory($pluginDir)
	{
		if (@is_readable($pluginDir)) {
			$this->pluginsDirectory = $pluginDir;
		} else {
			write_log(
				sprintf("Plugin Manager: Directory %s doesn't exist or is not readable", $pluginDir), E_USER_ERROR
			);
			throw new iMSCP_Plugin_Exception(sprintf("Directory %s doesn't exist or is not readable", $pluginDir));
		}
	}

	/**
	 * Returns plugin directory
	 *
	 * @return string Plugin directory
	 */
	public function getPluginDirectory()
	{
		return $this->pluginsDirectory;
	}

	/**
	 * Returns list of known plugins of given type
	 *
	 * @param string $type The type of plugins to return ('all' means all plugin type).
	 * @param bool $onlyEnabled TRUE to only return activated plugins (default), FALSE to all plugins
	 * @return array An array containing plugin names
	 */
	public function getPluginList($type = 'all', $onlyEnabled = true)
	{
		if ($type == 'all') {
			return array_keys(
				$onlyEnabled ? array_filter(
					$this->pluginData,
					function ($pluginData) {
						return ($pluginData['status'] == 'enabled');
					}
				) : $this->pluginData
			);
		} elseif (isset($this->pluginsByType[$type])) {
			$pluginData = $this->pluginData;

			return $onlyEnabled
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
	 * @param string $pluginName Name of the plugin to load
	 * @param bool $newInstance TRUE to return a new instance of the plugin, FALSE to use an already loaded instance
	 * @param bool $loadEnabledOnly true to load only enabled plugins
	 * @return null|iMSCP_Plugin|iMSCP_Plugin_Action
	 */
	public function loadPlugin($pluginName, $newInstance = false, $loadEnabledOnly = true)
	{
		if ($loadEnabledOnly && !$this->isPluginEnabled($pluginName)) {
			return null;
		}

		$className = "iMSCP_Plugin_$pluginName";

		if (isset($this->loadedPlugins[$pluginName])) {
			if ($newInstance) {
				return class_exists($className, true) ? new $className() : null;
			}

			return $this->loadedPlugins[$pluginName];
		}

		if (!class_exists($className, true)) {
			write_log(
				sprintf('Plugin Manager: Unable to load %s plugin - Class %s not found.', $pluginName, $className),
				E_USER_ERROR
			);

			return null;
		}

		$this->loadedPlugins[$pluginName] = new $className();

		return $this->loadedPlugins[$pluginName];
	}

	/**
	 * Whether or not the given plugin is loaded
	 *
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if the given plugin is loaded, FALSE otherwise
	 */
	public function isLoadedPlugin($pluginName)
	{
		return (isset($this->loadedPlugins[$pluginName]));
	}

	/**
	 * Returns loaded plugins
	 *
	 * @param string $type Type of loaded plugins to return
	 * @return array Array containing plugins instances
	 */
	public function getLoadedPlugins($type = 'all')
	{
		if ($type == 'all') {
			return $this->loadedPlugins;
		} elseif (isset($this->pluginsByType[$type])) {
			return array_intersect_key($this->loadedPlugins, array_flip($this->pluginsByType[$type]));
		}

		return array();
	}

	/**
	 * Get plugin instance
	 *
	 * Note: $pluginName must be already loaded.
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginName is not loaded
	 * @param string $pluginName Plugin name
	 * @return iMSCP_Plugin
	 */
	public function getPlugin($pluginName)
	{
		if ($this->isLoadedPlugin($pluginName)) {
			return $this->loadedPlugins[$pluginName];
		}

		throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Plugin %s is not loaded', $pluginName));
	}

	/**
	 * Get status of the given plugin
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return string Plugin status
	 */
	public function getPluginStatus($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			return $this->pluginData[$pluginName]['status'];
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Set status for the given plugin
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @param string $newStatus New plugin status
	 * @return void
	 */
	public function setPluginStatus($pluginName, $newStatus)
	{
		if ($this->isPluginKnown($pluginName)) {
			$status = $this->getPluginStatus($pluginName);

			if ($status !== $newStatus) {
				exec_query('UPDATE plugin SET plugin_status = ? WHERE plugin_name = ?', array($newStatus, $pluginName));
				$this->pluginData[$pluginName]['status'] = $newStatus;
			}
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Get plugin error
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginName is not known
	 * @param null|string $pluginName Plugin name
	 * @return string|null Plugin error string or NULL if no error
	 */
	public function getPluginError($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			return $this->pluginData[$pluginName]['error'];
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Set error for the given plugin
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @param null|string $pluginError Plugin error string or NULL if no error
	 * @return void
	 */
	public function setPluginError($pluginName, $pluginError)
	{
		if ($this->isPluginKnown($pluginName)) {
			if ($pluginError !== $this->pluginData[$pluginName]['error']) {
				exec_query(
					'UPDATE plugin SET plugin_error = ? WHERE plugin_name = ?', array($pluginError, $pluginName)
				);
				$this->pluginData[$pluginName]['error'] = $pluginError;
			}
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Whether or not the given plugin has error
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if the given plugin has error, FALSE otherwise
	 */
	public function hasPluginError($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			return (null !== $this->pluginData[$pluginName]['error']);
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Returns plugin info
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return array An array containing plugin info
	 */
	public function getPluginInfo($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			return $this->pluginData[$pluginName]['info'];
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Set plugin info
	 *
	 * This method allow to override plugin info for this plugin manager instance.
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @param array $info
	 */
	public function overridePluginInfo($pluginName, array $info)
	{
		if ($this->isPluginKnown($pluginName)) {
			$this->pluginData[$pluginName]['info'] = $info;
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Is the given plugin installable?
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if the given plugin is installable, FALSE otherwise
	 */
	public function isPluginInstallable($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			$info = $this->getPluginInfo($pluginName);

			if (isset($info['__installable__'])) {
				return $info['__installable__'];
			} else {
				$pluginInstance = $this->loadPlugin($pluginName, true, false);
				$rMethod = new ReflectionMethod($pluginInstance, 'install');
				return ('iMSCP_Plugin' !== $rMethod->getDeclaringClass()->getName());
			}
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Is the given plugin installed?
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if $pluginName is activated FALSE otherwise
	 */
	public function isPluginInstalled($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			return ($this->getPluginStatus($pluginName) != 'uninstalled');
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Install the given plugin
	 *
	 * @param string $pluginName Plugin name
	 * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
	 */
	public function pluginInstall($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			$pluginStatus = $this->getPluginStatus($pluginName);

			if (in_array($pluginStatus, array('toinstall', 'uninstalled'))) {
				try {
					$pluginInstance = $this->loadPlugin($pluginName, false, false);
					$pluginInstance->register($this->eventsManager);

					$this->setPluginStatus($pluginName, 'toinstall');
					$this->setPluginError($pluginName, null);

					// Trigger the onBeforeInstallPLugin event
					$responses = $this->eventsManager->dispatch(
						iMSCP_Events::onBeforeInstallPlugin,
						array(
							'pluginManager' => $this,
							'pluginName' => $pluginName
						)
					);

					if (!$responses->isStopped()) {
						// Run the install plugin method
						$pluginInstance->install($this);

						// Trigger the onAfterInstallPLugin event
						$this->eventsManager->dispatch(
							iMSCP_Events::onAfterInstallPlugin,
							array(
								'pluginManager' => $this,
								'pluginName' => $pluginName
							)
						);

						// Run the enable subaction
						$ret = $this->pluginEnable($pluginName, true);

						if ($ret == self::ACTION_SUCCESS) {
							if ($this->hasPluginBackend($pluginName)) {
								$this->backendRequest = true;
							} else {
								$this->setPluginStatus($pluginName, 'enabled');
							}
						} elseif ($ret == self::ACTION_STOPPED) {
							// The enable subaction has been stopped by an event listener before having a chance to
							// start. Therefore, the plugin status is set back to its initial state.
							$this->setPluginStatus($pluginName, $pluginStatus);
						} else {
							// The enable subaction has failed. Therefore, the plugin status is left untouched, giving a
							// chance to re-run this action once the problem is fixed.
							throw new iMSCP_Plugin_Exception($this->getPluginError($pluginName));
						}

						return $ret;
					}

					// This action has been stopped by an event listener before having a chance to start. Therefore, the
					// plugin status is set back to its initial state.
					$this->setPluginStatus($pluginName, $pluginStatus);
					return self::ACTION_STOPPED;
				} catch (iMSCP_Plugin_Exception $e) {
					$this->setPluginError($pluginName, sprintf('Plugin installation has failed: %s', $e->getMessage()));
					write_log(sprintf('Plugin Manager: %s plugin installation has failed', $pluginName), E_USER_ERROR);
				}
			}
		}

		return self::ACTION_FAILURE;
	}

	/**
	 * Is the given plugin uninstallable?
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if the given plugin can be uninstalled, FALSE otherwise
	 */
	public function isPluginUninstallable($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			$info = $this->getPluginInfo($pluginName);

			if (isset($info['__uninstallable__'])) {
				return $info['__uninstallable__'];
			} else {
				$pluginInstance = $this->loadPlugin($pluginName, true, false);
				$rMethod = new ReflectionMethod($pluginInstance, 'uninstall');
				return ('iMSCP_Plugin' != $rMethod->getDeclaringClass()->getName());
			}
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Is the given plugin uninstalled?
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if $pluginName is uninstalled FALSE otherwise
	 */
	public function isPluginUninstalled($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			return ($this->getPluginStatus($pluginName) == 'uninstalled');
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Uninstall the given plugin
	 *
	 * @param string $pluginName Plugin name
	 * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
	 */
	public function pluginUninstall($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			$pluginStatus = $this->getPluginStatus($pluginName);

			if (in_array($pluginStatus, array('touninstall', 'disabled'))) {
				try {
					$pluginInstance = $this->loadPlugin($pluginName, false, false);
					$pluginInstance->register($this->eventsManager);

					$this->setPluginStatus($pluginName, 'touninstall');
					$this->setPluginError($pluginName, null);

					// Trigger the onBeforeUninstallPLugin event
					$responses = $this->eventsManager->dispatch(
						iMSCP_Events::onBeforeUninstallPlugin,
						array(
							'pluginManager' => $this,
							'pluginName' => $pluginName
						)
					);

					if (!$responses->isStopped()) {
						// Run the uninstall plugin method
						$pluginInstance->uninstall($this);

						// Trigger the onAfterUninstallPLugin event
						$this->eventsManager->dispatch(
							iMSCP_Events::onAfterUninstallPlugin,
							array(
								'pluginManager' => $this,
								'pluginName' => $pluginName
							)
						);

						if ($this->hasPluginBackend($pluginName)) {
							$this->backendRequest = true;
						} else {
							$this->setPluginStatus($pluginName, 'uninstalled');
						}

						return self::ACTION_SUCCESS;
					}

					// This action has been stopped by an event listener before having a chance to start. Therefore, the
					// plugin status is set back to its initial state.
					$this->setPluginStatus($pluginName, $pluginStatus);
					return self::ACTION_STOPPED;
				} catch (iMSCP_Plugin_Exception $e) {
					$this->setPluginError(
						$pluginName, sprintf('Plugin uninstallation has failed: %s', $e->getMessage())
					);
					write_log(
						sprintf('Plugin Manager: %s plugin uninstallation has failed', $pluginName), E_USER_ERROR
					);
				}
			}
		}

		return self::ACTION_FAILURE;
	}

	/**
	 * Is the given plugin enabled?
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if $pluginName is activated FALSE otherwise
	 */
	public function isPluginEnabled($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			return ($this->getPluginStatus($pluginName) == 'enabled');
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Enable (activate) the given plugin
	 *
	 * @param string $pluginName Plugin name
	 * @param bool $inheritPluginStatus Inherit plugin status
	 * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
	 */
	public function pluginEnable($pluginName, $inheritPluginStatus = false)
	{
		if ($this->isPluginKnown($pluginName)) {
			$pluginStatus = $this->getPluginStatus($pluginName);

			if ($inheritPluginStatus || in_array($pluginStatus, array('toenable', 'disabled'))) {
				try {
					$pluginInstance = $this->loadPlugin($pluginName, false, false);
					$pluginInstance->register($this->eventsManager);

					if (!$inheritPluginStatus) {
						$this->setPluginStatus($pluginName, 'toenable');
					}

					$this->setPluginError($pluginName, null);

					// Trigger the onBeforeEnablePLugin
					$responses = $this->eventsManager->dispatch(
						iMSCP_Events::onBeforeEnablePlugin,
						array(
							'pluginManager' => $this,
							'pluginName' => $pluginName
						)
					);

					if (!$responses->isStopped()) {
						// Run the enable plugin method
						$pluginInstance->enable($this);

						// Trigger the onAfterEnablePlugin event
						$this->eventsManager->dispatch(
							iMSCP_Events::onAfterEnablePlugin,
							array(
								'pluginManager' => $this,
								'pluginName' => $pluginName
							)
						);

						if ($this->hasPluginBackend($pluginName)) {
							$this->backendRequest = true;
						} elseif (!$inheritPluginStatus) {
							$this->setPluginStatus($pluginName, 'enabled');
						}

						return self::ACTION_SUCCESS;
					} elseif (!$inheritPluginStatus) {
						// This action has been stopped by an event listener before having a chance to start. Therefore,
						// the plugin status is set back to its initial state.
						$this->setPluginStatus($pluginName, $pluginStatus);
					}

					return self::ACTION_STOPPED;
				} catch (iMSCP_Plugin_Exception $e) {
					$this->setPluginError($pluginName, sprintf('Plugin activation has failed: %s', $e->getMessage()));
					write_log(sprintf('Plugin Manager: %s plugin activation has failed', $pluginName), E_USER_ERROR);
				}
			}
		}

		return self::ACTION_FAILURE;
	}

	/**
	 * Is the given plugin disabled?
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if $pluginName is deactivated FALSE otherwise
	 */
	public function isPluginDisabled($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			return in_array($this->getPluginStatus($pluginName), array('uninstalled', 'disabled'));
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Disable (deactivate) the given plugin
	 *
	 * @param string $pluginName Plugin name
	 * @param bool $inheritPluginStatus Inherit plugin status
	 * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
	 */
	public function pluginDisable($pluginName, $inheritPluginStatus = false)
	{
		if ($this->isPluginKnown($pluginName)) {
			$pluginStatus = $this->getPluginStatus($pluginName);

			if ($inheritPluginStatus || in_array($pluginStatus, array('todisable', 'enabled'))) {
				try {
					$pluginInstance = $this->loadPlugin($pluginName, false, false);
					$pluginInstance->register($this->eventsManager);

					if (!$inheritPluginStatus) {
						$this->setPluginStatus($pluginName, 'todisable');
					}

					$this->setPluginError($pluginName, null);

					// Trigger the onBeforeDisablePlugin event
					$responses = $this->eventsManager->dispatch(
						iMSCP_Events::onBeforeDisablePlugin,
						array(
							'pluginManager' => $this,
							'pluginName' => $pluginName
						)
					);

					if (!$responses->isStopped()) {
						// Run the disable plugin method
						$pluginInstance->disable($this);

						// Trigger the onAfterDisablePlugin event
						$this->eventsManager->dispatch(
							iMSCP_Events::onAfterDisablePlugin,
							array(
								'pluginManager' => $this,
								'pluginName' => $pluginName
							)
						);

						if ($this->hasPluginBackend($pluginName)) {
							$this->backendRequest = true;
						} elseif (!$inheritPluginStatus) {
							$this->setPluginStatus($pluginName, 'disabled');
						}

						return self::ACTION_SUCCESS;
					} elseif (!$inheritPluginStatus) {
						// This action has been stopped by an event listener before having a chance to start. Therefore,
						// the plugin status is set back to its initial state.
						$this->setPluginStatus($pluginName, $pluginStatus);
					}

					return self::ACTION_STOPPED;
				} catch (iMSCP_Plugin_Exception $e) {
					$this->setPluginError($pluginName, sprintf('Plugin deactivation has failed: %s', $e->getMessage()));
					write_log(sprintf('Plugin Manager: %s plugin deactivation has failed', $pluginName), E_USER_ERROR);
				}
			}
		}

		return self::ACTION_FAILURE;
	}

	/**
	 * Change the given plugin
	 *
	 * @param string $pluginName Plugin name
	 * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
	 */
	public function pluginChange($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			$pluginStatus = $this->getPluginStatus($pluginName);

			if (in_array($pluginStatus, array('tochange', 'enabled'))) {
				try {
					$pluginInstance = $this->loadPlugin($pluginName, false, false);
					$pluginInstance->register($this->eventsManager);

					$this->setPluginStatus($pluginName, 'tochange');
					$this->setPluginError($pluginName, null);

					// Run the disable subaction
					$ret = $this->pluginDisable($pluginName, true);

					if ($ret == self::ACTION_SUCCESS) {
						// Run the enable subaction
						$ret = $this->pluginEnable($pluginName, true);

						if ($ret == self::ACTION_SUCCESS) {
							if ($this->hasPluginBackend($pluginName)) {
								$this->backendRequest = true;
							} else {
								$this->setPluginStatus($pluginName, 'enabled');
							}
						} elseif ($ret == self::ACTION_STOPPED) {
							// The enable subaction has been stopped by an event listener before having a change to
							// start. Therefore, the plugin status is set back to its initial state.
							$this->setPluginStatus($pluginName, $pluginStatus);
						} else {
							// The enable subaction has failed. Therefore, the plugin status is left untouched, giving a
							// chance to re-run this action once the problem is fixed.
							throw new iMSCP_Plugin_Exception($this->getPluginError($pluginName));
						}
					} elseif ($ret == self::ACTION_STOPPED) {
						// The disable subaction has been stopped by an event listener before having a change to start.
						// Therefore, the plugin status is set back to its initial state.
						$this->setPluginStatus($pluginName, $pluginStatus);
					} else {
						// The disable subaction has failed. Therefore, the plugin status is left untouched, giving a
						// chance to re-run this action once the problem is fixed.
						throw new iMSCP_Plugin_Exception($this->getPluginError($pluginName));
					}

					return $ret;
				} catch (iMSCP_Plugin_Exception $e) {
					$this->setPluginError($pluginName, sprintf('Plugin change has failed: %s', $e->getMessage()));
					write_log(sprintf('Plugin Manager: %s plugin change has failed', $pluginName), E_USER_ERROR);
				}
			}
		}

		return self::ACTION_FAILURE;
	}

	/**
	 * Update the given plugin
	 *
	 * @param string $pluginName Plugin name
	 * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
	 */
	public function pluginUpdate($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			$pluginStatus = $this->getPluginStatus($pluginName);

			if (in_array($pluginStatus, array('toupdate', 'enabled'))) {
				try {
					$pluginInstance = $this->loadPlugin($pluginName, false, false);
					$pluginInstance->register($this->eventsManager);

					$this->setPluginStatus($pluginName, 'toupdate');
					$this->setPluginError($pluginName, null);

					// Run the disable subaction
					$ret = $this->pluginDisable($pluginName, true);

					if ($ret == self::ACTION_SUCCESS) {
						$pluginInfo = $this->getPluginInfo($pluginName);

						// Trigger the onBeforeUpdatePlugin event
						$responses = $this->eventsManager->dispatch(
							iMSCP_Events::onBeforeUpdatePlugin,
							array(
								'pluginManager' => $this,
								'pluginName' => $pluginName,
								'PluginFromVersion' => $pluginInfo['version'],
								'PluginToVersion' => $pluginInfo['nversion']
							)
						);

						if (!$responses->isStopped()) {
							// Run the update plugin method
							$pluginInstance->update($this, $pluginInfo['version'], $pluginInfo['nversion']);

							// Trigger the onAfterUpdatePlugin event
							$this->eventsManager->dispatch(
								iMSCP_Events::onAfterUpdatePlugin,
								array(
									'pluginManager' => $this,
									'pluginName' => $pluginName,
									'PluginFromVersion' => $pluginInfo['version'],
									'PluginToVersion' => $pluginInfo['nversion']
								)
							);

							// Run the enable subaction
							$ret = $this->pluginEnable($pluginName, true);

							if ($ret == self::ACTION_SUCCESS) {
								if ($this->hasPluginBackend($pluginName)) {
									$this->backendRequest = true;
								} else {
									$pluginInfo['version'] = $pluginInfo['nversion'];
									$this->overridePluginInfo($pluginName, $pluginInfo);
									$this->updatePluginInfo($pluginName, $pluginInfo);
									$this->setPluginStatus($pluginName, 'enabled');
								}
							} elseif ($ret == self::ACTION_STOPPED) {
								// The enable subaction has been stopped by an event listener before having a chance to
								// start. Therefore, the plugin status is set back to its initial state.
								$this->setPluginStatus($pluginName, $pluginStatus);
							} else {
								// The enable subaction has failed. Therefore, the plugin status is left untouched,
								// giving a chance to re-run this action once the problem is fixed.
								throw new iMSCP_Plugin_Exception($this->getPluginError($pluginName));
							}
						} elseif ($ret == self::ACTION_STOPPED) {
							// The disable subaction has been stopped by an event listener before having a chance to
							// start. Therefore, the plugin status is set back to its initial state.
							$this->setPluginStatus($pluginName, $pluginStatus);
						} else {
							// The disable subaction has failed. Therefore, the plugin status is left untouched, giving
							//a chance to re-run this action once the problem is fixed.
							throw new iMSCP_Plugin_Exception($this->getPluginError($pluginName));
						}
					}

					return $ret;
				} catch (iMSCP_Plugin_Exception $e) {
					$this->setPluginError($pluginName, sprintf('Plugin update has failed: %s', $e->getMessage()));
					write_log(sprintf('Plugin Manager: %s plugin update has failed', $pluginName), E_USER_ERROR);
				}
			}
		}

		return self::ACTION_FAILURE;
	}

	/**
	 * Delete the given plugin
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param string $pluginName Plugin name
	 * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
	 */
	public function pluginDelete($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			$pluginStatus = $this->getPluginStatus($pluginName);

			if (in_array($pluginStatus, array('todelete', 'uninstalled', 'disabled'))) {
				try {
					$pluginInstance = $this->loadPlugin($pluginName, false, false);
					$pluginInstance->register($this->eventsManager);

					$this->setPluginStatus($pluginName, 'todelete');
					$this->setPluginError($pluginName, null);

					// Trigger the onBeforeDeletePlugin event
					$responses = $this->eventsManager->dispatch(
						iMSCP_Events::onBeforeDeletePlugin,
						array(
							'pluginManager' => $this,
							'pluginName' => $pluginName
						)
					);

					if (!$responses->isStopped()) {
						// Run the delete plugin method
						$pluginInstance->delete($this);

						$this->deletePluginFromDatabase($pluginName);
						unset($this->pluginData[$pluginName]);

						$pluginDir = $this->pluginsDirectory . '/' . $pluginName;

						if (is_dir($pluginDir)) {
							if (!utils_removeDir($pluginDir)) {
								write_log(
									sprintf('Plugin Manager: Unable to delete the %s plugin files', $pluginName),
									E_USER_ERROR
								);

								throw new iMSCP_Plugin_Exception(
									sprintf(
										'Unable to delete the %s plugin files. Please, remove them manually.',
										"<strong>$pluginName</strong>"
									)
								);
							}
						}

						// Trigger the onAfterDeletePlugin event
						$this->eventsManager->dispatch(
							iMSCP_Events::onAfterDeletePlugin,
							array(
								'pluginManager' => $this,
								'pluginName' => $pluginName
							)
						);

						// At this stage, the plugin must be fully deleted.
						return self::ACTION_SUCCESS;
					}

					// This action has been stopped by an event listener before having a chance to start. Therefore, the
					// plugin status is set back to its initial state.
					$this->setPluginStatus($pluginName, $pluginStatus);
					return self::ACTION_STOPPED;
				} catch (iMSCP_Plugin_Exception $e) {
					$this->setPluginError($pluginName, sprintf('Plugin deletion has failed: %s', $e->getMessage()));
					write_log(sprintf('Plugin Manager: %s plugin deletion has failed', $pluginName), E_USER_ERROR);
				}
			}
		}

		return self::ACTION_FAILURE;
	}

	/**
	 * Is the given plugin protected?
	 *
	 * @throws iMSCP_Plugin_Exception in case the given plugin is not known
	 * @param string $pluginName Plugin name
	 * @return self::ACTION_SUCCESS|self::ACTION_STOPPED|self::ACTION_FAILURE
	 */
	public function isPluginProtected($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			if (!$this->isLoadedProtectedPluginsList) {
				$file = PERSISTENT_PATH . '/protected_plugins.php';
				$protectedPlugins = array();

				if (is_readable($file)) include_once $file;

				$this->protectedPlugins = $protectedPlugins;
				$this->isLoadedProtectedPluginsList = true;
			}

			return in_array($pluginName, $this->protectedPlugins);
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Protect the given plugin
	 *
	 * @param string $pluginName Name of the plugin to protect
	 * @return bool self::ACTION_SUCCESS|self::ACTION_FAILURE
	 */
	public function pluginProtect($pluginName)
	{
		if ($this->isPluginEnabled($pluginName) && !$this->isPluginProtected($pluginName)) {
			$responses = $this->eventsManager->dispatch(
				iMSCP_Events::onBeforeProtectPlugin, array('pluginManager' => $this, 'pluginName' => $pluginName)
			);

			if (!$responses->isStopped()) {
				$protectedPlugins = $this->protectedPlugins;
				$this->protectedPlugins[] = $pluginName;

				if ($this->updateProtectFile()) {
					$this->eventsManager->dispatch(
						iMSCP_Events::onAfterProtectPlugin, array('pluginManager' => $this, 'pluginName' => $pluginName)
					);

					return self::ACTION_SUCCESS;
				}

				$this->protectedPlugins = $protectedPlugins;
			} else {
				return self::ACTION_STOPPED;
			}
		}

		return self::ACTION_FAILURE;
	}

	/**
	 * Is the given plugin known by plugin manager?
	 *
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if the given plugin is know by plugin manager , FALSE otherwise
	 */
	public function isPluginKnown($pluginName)
	{
		return isset($this->pluginData[$pluginName]);
	}

	/**
	 * The given plugin provides backend part?
	 *
	 * @throws iMSCP_Plugin_Exception in case $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return boolean TRUE if the given plugin provide backend part, FALSE otherwise
	 */
	public function hasPluginBackend($pluginName)
	{
		if ($this->isPluginKnown($pluginName)) {
			return ($this->pluginData[$pluginName]['backend'] == 'yes');
		} else {
			write_log(sprintf('Plugin Manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception(sprintf('Plugin Manager: Unknown plugin %s', $pluginName));
		}
	}

	/**
	 * Update plugin list
	 *
	 * This method is responsible to updated the plugin list. In order, the following tasks are accomplished:
	 *
	 * - New plugins found in the plugin directory are added into the database. The status for these plugin is
	 *   initialized to 'uninstalled' or 'disabled', depending if the plugin implement the install method or not.
	 * - All data for known plugins get updated.
	 * - Enabled plugins for which a new version is found get updated
	 * - Enabled plugins for which configuration has been updated get changed.
	 * - Plugins which are not longer available in the plugin repository are deleted from the database
	 *
	 * @return array An array containing information about added, updated and deleted plugins.
	 */
	public function updatePluginList()
	{
		$knownPluginsData = array();
		$foundPlugins = array();
		$toUpdatePlugins = array();
		$toChangePlugins = array();
		$returnInfo = array('new' => 0, 'updated' => 0, 'changed' => 0, 'deleted' => 0);

		$stmt = execute_query('SELECT plugin_name, plugin_config FROM plugin');

		if ($stmt->rowCount()) {
			$knownPluginsData = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
		}

		/** @var $fileInfo SplFileInfo */
		foreach (new RecursiveDirectoryIterator($this->pluginsDirectory, FilesystemIterator::SKIP_DOTS) as $fileInfo) {
			if ($fileInfo->isDir() && $fileInfo->isReadable()) {
				$pluginName = $fileInfo->getBasename();

				// Try to load plugin
				$pluginInstance = $this->loadPlugin($pluginName, true, false);

				if ($pluginInstance) {
					// Get newest plugin info
					$newestPluginInfo = $pluginInstance->getInfo();

					// Does this plugin provides a backend?
					$pluginBackend = file_exists($fileInfo->getPathname() . "/backend/$pluginName.pm") ? 'yes' : 'no';

					// Get newest plugin config
					$newestpluginConfig = $pluginInstance->getConfigFromFile();

					// Does this plugin is already known by plugin manager?
					if (isset($knownPluginsData[$pluginName])) {
						$pluginInfo = $this->getPluginInfo($pluginName);
						$pluginStatus = $this->getPluginStatus($pluginName);
						$newestPluginInfo['__nversion__'] = $newestPluginInfo['version'];
						$newestPluginInfo['version'] = $pluginInfo['version'];
						$knownPluginsConfig = json_decode($knownPluginsData[$pluginName]['plugin_config'], true);

						// Does this plugin is enabled?
						if ($pluginStatus == 'enabled') {
							// Override plugin info as known by this plugin manager instance
							$this->overridePluginInfo($pluginName, $newestPluginInfo);

							// If the plugin version has been incremented we schedule an update
							if (version_compare($newestPluginInfo['version'], $newestPluginInfo['__nversion__'], '<')) {
								$toUpdatePlugins[] = $pluginName;
								$returnInfo['updated']++;

								// Else if the plugin configuration has been changed, we schedule a plugin change
							} elseif ($newestpluginConfig !== $knownPluginsConfig) {
								$toChangePlugins[] = $pluginName;
								$returnInfo['changed']++;
							}
						} else {
							$newestpluginConfig = $knownPluginsConfig;
						}

						$rMethod = new ReflectionMethod($pluginInstance, 'install');
						$newestPluginInfo['__installable__'] = (
							'iMSCP_Plugin' !== $rMethod->getDeclaringClass()->getName()
						);

						$rMethod = new ReflectionMethod($pluginInstance, 'uninstall');
						$newestPluginInfo['__uninstallable__'] = (
							'iMSCP_Plugin' !== $rMethod->getDeclaringClass()->getName()
						);
					} else {
						$newestPluginInfo['__nversion__'] = $newestPluginInfo['version'];

						$rMethod = new ReflectionMethod($pluginInstance, 'install');

						if ('iMSCP_Plugin' !== $rMethod->getDeclaringClass()->getName()) {
							$pluginStatus = 'uninstalled';
							$newestPluginInfo['__installable__'] = true;
						} else {
							$pluginStatus = 'disabled';
							$newestPluginInfo['__installable__'] = false;
						}

						$rMethod = new ReflectionMethod($pluginInstance, 'uninstall');
						$newestPluginInfo['__uninstallable__'] = (
							'iMSCP_Plugin' !== $rMethod->getDeclaringClass()->getName()
						);

						$returnInfo['new']++;
					}

					// Add/update plugin data in database
					$this->addPluginIntoDatabase(
						array(
							'name' => $pluginName,
							'type' => $pluginInstance->getType(),
							'info' => json_encode($newestPluginInfo),
							'config' => json_encode($newestpluginConfig),
							'status' => $pluginStatus,
							'backend' => $pluginBackend
						)
					);

					$foundPlugins[] = $pluginName;
				} else {
					set_page_message(tr('Plugin manager was unable to load plugin %s', $pluginName), 'error');
				}
			}
		}

		foreach (array_keys($this->pluginData) as $pluginName) {
			if (!in_array($pluginName, $foundPlugins)) {
				if ($this->deletePluginFromDatabase($pluginName)) {
					$returnInfo['deleted']++;
				}
			} elseif (in_array($pluginName, $toUpdatePlugins)) {
				$ret = $this->pluginUpdate($pluginName);

				if ($ret == self::ACTION_FAILURE || $ret == self::ACTION_STOPPED) {
					$message = tr(
						'Plugin Manager: Unable to update the %s plugin: %s',
						"<strong>$pluginName</strong>",
						($ret == self::ACTION_FAILURE) ? tr('Action has failed.') : tr('Action has been stopped.')
					);
					set_page_message($message, 'error');
					$returnInfo['updated']--;
				}
			} elseif (in_array($pluginName, $toChangePlugins)) {
				$ret = $this->pluginChange($pluginName);

				if ($ret == self::ACTION_FAILURE || $ret == self::ACTION_STOPPED) {
					$message = tr(
						'Plugin Manager: Unable to change the %s plugin: %s',
						"<strong>$pluginName</strong>",
						($ret == self::ACTION_FAILURE) ? tr('Action has failed.') : tr('Action has been stopped.')
					);
					set_page_message($message, 'error');
					$returnInfo['changed']--;
				}
			}
		}

		return $returnInfo;
	}

	/**
	 * Initialize plugin manager
	 *
	 * @return void
	 */
	protected function init()
	{
		$stmt = execute_query('SELECT * FROM plugin');

		while ($pluginData = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			$this->pluginData[$pluginData['plugin_name']] = array(
				'info' => json_decode($pluginData['plugin_info'], true),
				'status' => $pluginData['plugin_status'],
				'error' => $pluginData['plugin_error'],
				'backend' => $pluginData['plugin_backend']
			);
			$this->pluginsByType[$pluginData['plugin_type']][] = $pluginData['plugin_name'];
		}
	}

	/**
	 * Handle plugin protection file
	 *
	 * @return bool TRUE when protection file is successfully created/updated or removed FALSE otherwise
	 */
	protected function updateProtectFile()
	{
		$file = PERSISTENT_PATH . '/protected_plugins.php';
		$lastUpdate = 'Last update: ' . date('Y-m-d H:i:s', time()) . " by {$_SESSION['user_logged']}";
		$content = "<?php\n/**\n * Protected plugin list\n * Auto-generated by i-MSCP plugin manager\n";
		$content .= " * $lastUpdate\n */\n\n";

		if (!empty($this->protectedPlugins)) {
			foreach ($this->protectedPlugins as $pluginName) {
				$content .= "\$protectedPlugins[] = '$pluginName';\n";
			}

			@unlink($file);

			if (@file_put_contents($file, "$content\n", LOCK_EX) === false) {
				set_page_message(tr('Unable to write the %s file for protected plugins.', $file), 'error');
				write_log(sprintf('Plugin manager has not been able to write the %s file.', $file));
				return false;
			}
		} elseif (@is_writable($file)) {
			if (!@unlink($file)) {
				write_log(sprintf('Plugin manager was unable to remove the %s file'), $file, E_USER_WARNING);
				return false;
			}
		}

		return true;
	}

	/**
	 * Add or update plugin in database
	 *
	 * @param array $pluginData Plugin data
	 * @return void
	 */
	protected function addPluginIntoDatabase(array $pluginData)
	{
		if (!isset($this->pluginData[$pluginData['name']])) {
			exec_query(
				'
					INSERT INTO plugin (
						plugin_name, plugin_type, plugin_info, plugin_config, plugin_status, plugin_backend
					) VALUE (
						:name, :type, :info, :config, :status, :backend
					)
				',
				$pluginData
			);
		} else {
			exec_query(
				'
					UPDATE
						plugin SET plugin_info = ?, plugin_config = ?, plugin_status = ?, plugin_backend = ?
					WHERE
						plugin_name = ?
				',
				array(
					$pluginData['info'], $pluginData['config'], $pluginData['status'], $pluginData['backend'],
					$pluginData['name']
				)
			);
		}
	}

	/**
	 * Update plugin info
	 *
	 * @param string $pluginName Plugin info
	 * @param array $info plugin info
	 * @return void
	 */
	protected function updatePluginInfo($pluginName, array $info)
	{
		$info = json_encode($info);
		exec_query('UPDATE plugin SET plugin_info = ? WHERE plugin_name = ?', array($info, $pluginName));
	}

	/**
	 * Delete plugin from database
	 *
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if $pluginName has been deleted from database, FALSE otherwise.
	 */
	protected function deletePluginFromDatabase($pluginName)
	{
		$stmt = exec_query('DELETE FROM plugin WHERE plugin_name = ?', $pluginName);

		if (!$stmt->rowCount()) {
			return false;
		}

		// Force protected_plugins.php file to be regenerated or removed if needed
		if ($this->isPluginProtected($pluginName)) {
			$protectedPlugins = array_flip($this->protectedPlugins);
			unset($protectedPlugins[$pluginName]);
			$this->protectedPlugins = array_flip($protectedPlugins);
			$this->updateProtectFile();
		}

		unset($this->pluginData[$pluginName]);
		write_log(sprintf('Plugin Manager: %s plugin has been removed from database', $pluginName), E_USER_NOTICE);

		return true;
	}
}
