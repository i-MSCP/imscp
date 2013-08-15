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
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Plugin_Manager
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_Plugin_Manager
{
	/**
	 * @var string Plugins directory
	 */
	protected $pluginsDirectory;

	/**
	 * @var array Keys are plugin names and the values an array containing previous and current status
	 */
	protected $plugins = array();

	/**
	 * @var array Keys are plugin names and the values a string representing plugins error or null if no error
	 */
	protected $pluginsError = array();

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
	 * @var array Keys are the plugin names and the values a string telling if the plugin provides a backend part
	 */
	protected $pluginsBackend = array();

	/**
	 * @var array Array containing all loaded plugins
	 */
	protected $loadedPlugins = array();

	/**
	 * @var bool Whether or not a backend request should be sent
	 */
	protected $backendRequest = false;

	/**
	 * Constructor
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param string $pluginDir
	 * @return iMSCP_Plugin_Manager
	 */
	public function __construct($pluginDir)
	{
		if (is_dir($pluginDir)) {
			$this->setDirectory($pluginDir); // Set plugin directory
			$this->init(); // Initialize plugin manager
			spl_autoload_register(array($this, '_autoload'));  // Setup autoloader for plugins
		} else {
			write_log(sprintf('Plugin manager: Invalid plugin directory: %s', $pluginDir), E_USER_ERROR);
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
		if($this->backendRequest) {
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
		// Do not try to load class outside of the plugin namespace
		if (strpos($className, 'iMSCP_Plugin_', 0) === 0) {
			list(, , $className) = explode('_', $className, 3);
			$filePath = $this->getPluginDirectory() . "/{$className}/{$className}.php";

			if (is_readable($filePath)) {
				require_once $filePath;
			}
		}
	}

	/**
	 * Sets plugins directory
	 *
	 * @throws iMSCP_Plugin_Exception When $pluginDirectory doesn't exist or is not readable.
	 * @param string $pluginDir Plugins directory path
	 */
	public function setDirectory($pluginDir)
	{
		if (is_readable($pluginDir)) {
			$this->pluginsDirectory = $pluginDir;
		} else {
			write_log(
				sprintf("Plugin manager: Directory %s doesn't exist or is not readable", $pluginDir), E_USER_ERROR
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
	 * Returns a list of available plugins of given type
	 *
	 * @param string $type The type of plugins to return ('all' means all plugin type).
	 * @param bool $onlyEnabled TRUE to only return activated plugins (default), FALSE to all plugins
	 *
	 * @return array An array containing plugin names
	 */
	public function getPluginList($type = 'all', $onlyEnabled = true)
	{
		if ($type == 'all') {
			return array_keys(
				$onlyEnabled
					? array_filter($this->plugins, function ($status) { return ($status['current_status'] == 'enabled'); })
					: $this->plugins
			);
		} elseif (isset($this->pluginsByType[$type])) {
			$plugins = $this->plugins;

			return $onlyEnabled
				? array_filter(
					$this->pluginsByType[$type],
					function ($pluginName) use ($plugins) {
						return ($plugins[$pluginName]['current_status'] == 'enabled');
					}
				)
				: $this->pluginsByType[$type];
		}

		return array();
	}

	/**
	 * Loads the given plugin
	 *
	 * @param string $pluginName Name of the plugin to load
	 * @param bool $newInstance true to return a new instance of the plugin, false to use an already loaded instance
	 * @param bool $loadEnabledOnly true to load only enabled plugins
	 * @return null|iMSCP_Plugin
	 */
	public function load($pluginName, $newInstance = false, $loadEnabledOnly = true)
	{
		if ($loadEnabledOnly && ! $this->isActivated($pluginName)) {
			return null;
		}

		$className = "iMSCP_Plugin_$pluginName";

		if (isset($this->loadedPlugins[$pluginName])) {
			if ($newInstance) {
				return class_exists($className, true) ? new $className : null;
			}

			return $this->loadedPlugins[$pluginName];
		}

		if (!class_exists($className, true)) {
			if (is_dir($this->pluginsDirectory . "/{$pluginName}")) {
				write_log(
					sprintf('Plugin manager: Unable to load %s plugin - Class %s not found.', $pluginName, $className),
					E_USER_ERROR
				);
			}

			return null;
		}

		$this->loadedPlugins[$pluginName] = new $className();

		return $this->loadedPlugins[$pluginName];
	}

	/**
	 * Returns loaded plugins
	 *
	 * @param string $type Type of loaded plugins to return
	 * @return array Array containing plugins instances
	 */
	public function getLoadedPlugins($type = 'all')
	{
		if($type == 'all') {
			return $this->loadedPlugins;
		} elseif(isset($this->pluginsByType[$type])) {
			return array_intersect_key($this->loadedPlugins, array_flip($this->pluginsByType[$type]));
		}

		return array();
	}

	/**
	 * Get current status of the given plugin
	 *
	 * @throws iMSCP_Plugin_Exception in case the given plugin is not known
	 * @param string $pluginName Plugin name
	 * @return string Plugin status
	 */
	public function getStatus($pluginName)
	{
		if($this->isKnown($pluginName)) {
			return $this->plugins[$pluginName]['current_status'];
		} else {
			write_log(sprintf('Plugin manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Set status for the given plugin
	 *
	 * @throws iMSCP_Plugin_Exception in case the given plugin is not known
	 * @param string $pluginName Plugin name
	 * @param string $pluginStatus Plugin status
	 * @return void
	 */
	public function setStatus($pluginName, $pluginStatus)
	{
		if($this->isKnown($pluginName)) {
			$currentPluginStatus = $this->getStatus($pluginName);
			$previousStatus = ($currentPluginStatus != $pluginStatus)
				? $currentPluginStatus
				: $this->plugins[$pluginName]['previous_status'];

			exec_query(
				"UPDATE `plugin` SET `plugin_status` = ?, `plugin_previous_status` = ? WHERE `plugin_name` = ?",
				array($pluginStatus, $previousStatus, $pluginName)
			);

			$this->plugins[$pluginName]['previous_status'] = $previousStatus;
			$this->plugins[$pluginName]['current_status'] = $pluginStatus;
		} else {
			write_log(sprintf('Plugin manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Get plugin error
	 *
	 * @throws iMSCP_Plugin_Exception in case the given plugin is not known
	 * @param null|string $pluginName Plugin name
	 * @return string Plugin error
	 */
	public function getError($pluginName)
	{
		if($this->isKnown($pluginName)) {
			return $this->pluginsError[$pluginName];
		} else {
			write_log(sprintf('Plugin manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Set error for the given plugin
	 *
	 * @throws iMSCP_Plugin_Exception in case the given plugin is not known
	 * @param string $pluginName Plugin name
	 * @param null|string $pluginError Plugin error string
	 * @return void
	 */
	public function setError($pluginName, $pluginError)
	{
		if($this->isKnown($pluginName)) {
			exec_query(
				"UPDATE `plugin` SET `plugin_error` = ? WHERE `plugin_name` = ?",
				array($pluginError, $pluginName)
			);

			$this->pluginsError[$pluginName] = $pluginError;
		} else {
			write_log(sprintf('Plugin manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Whether or not the given plugin has error
	 *
	 * @throws iMSCP_Plugin_Exception in case the given plugin is not known
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if the given plugin has error, FALSE otherwise
	 */
	public function hasError($pluginName)
	{
		if($this->isKnown($pluginName)) {
			return ($this->pluginsError[$pluginName] !== null);
		} else {
			write_log(sprintf('Plugin manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Is the given plugin activated?
	 *
	 * @throws iMSCP_Plugin_Exception in case the given plugin is not known
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if $pluginName is activated FALSE otherwise
	 */
	public function isActivated($pluginName)
	{
		if($this->isKnown($pluginName)) {
			return ($this->getStatus($pluginName) == 'enabled');
		} else {
			write_log(sprintf('Plugin manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Activates the given plugin
	 *
	 * @param string $pluginName Name of the plugin to activate
	 * @param bool $force Force action
	 * @return bool TRUE on sucess, false otherwise
	 */
	public function activate($pluginName, $force = false)
	{
		if ($this->isKnown($pluginName)) {
			$pluginStatus = $this->getStatus($pluginName);

			if ($force || in_array($pluginStatus, array('uninstalled', 'disabled'))) {
				$statusTo = array(
					'uninstalled' => array('toinstall', 'install'),
					'toinstall' => array('toinstall', 'install'),
					'disabled' => array('toenable', 'enable')
				);

				$pluginInstance = $this->load($pluginName, false, false);

				$this->setError($pluginName, null);
				$this->setStatus($pluginName, $statusTo[$pluginStatus][0]);

				try {
					iMSCP_Events_Manager::getInstance()->dispatch(
						iMSCP_Events::onBeforeActivatePlugin,
						array('pluginManager' => $this, 'pluginName' => $pluginName)
					);

					$pluginInstance->{$statusTo[$pluginStatus][1]}($this);

					if($this->hasBackend($pluginName)) {
						$this->backendRequest = true;
					} else {
						$this->setStatus($pluginName, 'enabled');
					}

					iMSCP_Events_Manager::getInstance()->dispatch(
						iMSCP_Events::onAfterActivatePlugin,
						array('pluginManager' => $this, 'pluginName' => $pluginName)
					);
				} catch(iMSCP_Plugin_Exception $e) {
					$this->setError($pluginName, sprintf('Plugin activation has failed: %s', $e->getMessage()));
					write_log(sprintf('Plugin manager: %s plugin activation has failed', $pluginName), E_USER_ERROR);
					return false;
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Is the given plugin deactivated?
	 *
	 * @throws iMSCP_Plugin_Exception in case $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if $pluginName is deactivated FALSE otherwise
	 */
	public function isDeactivated($pluginName)
	{
		if($this->isKnown($pluginName)) {
			return in_array($this->getStatus($pluginName), array('uninstalled', 'disabled'));
		} else {
			write_log(sprintf('Plugin manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Deactivates the given plugin
	 *
	 * @param string $pluginName Name of the plugin to deactivate
	 * @param bool $force Force action
	 * @return bool TRUE if $pluginName has been successfully deactivated FALSE otherwise
	 */
	public function deactivate($pluginName, $force = false)
	{
		if ($this->isKnown($pluginName)) {
			if ($force || $this->getStatus($pluginName) == 'enabled') {
				$pluginInstance = $this->load($pluginName, false, false);

				$this->setError($pluginName, null);
				$this->setStatus($pluginName, 'todisable');

				try {
					iMSCP_Events_Manager::getInstance()->dispatch(
						iMSCP_Events::onBeforeDeactivatePlugin,
						array(
							'pluginManager' => $this,
							'pluginName' => $pluginName,
							'PluginInstance' => $pluginInstance
						)
					);

					$pluginInstance->{'disable'}($this);

					if($this->hasBackend($pluginName)) {
						$this->backendRequest = true;
					} else {
						$this->setStatus($pluginName, 'disabled');
					}

					iMSCP_Events_Manager::getInstance()->dispatch(
						iMSCP_Events::onAfterDeactivatePlugin,
						array(
							'pluginManager' => $this,
							'pluginName' => $pluginName,
							'PluginInstance' => $pluginInstance
						)
					);
				} catch(iMSCP_Plugin_Exception $e) {
					$this->setError($pluginName, sprintf('Plugin deactivation has failed: %s', $e->getMessage()));
					write_log(sprintf('Plugin manager: %s plugin deactivation has failed', $pluginName), E_USER_ERROR);
					return false;
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Update the given plugin
	 *
	 * @param string $pluginName Plugin name
	 * @param bool $force Force action
	 * @param string|null $fromVersion Version to which plugin is updated
	 * @param string|null $toVersion Version to which plugin is updated
	 * @return bool TRUE on success, FALSE otherwise
	 */
	public function update($pluginName, $force = false, $fromVersion = null, $toVersion = null)
	{
		if ($this->isKnown($pluginName)) {
			if ($force || !in_array($this->getStatus($pluginName), array('uninstalled', 'toinstall'))) {
				$pluginInstance = $this->load($pluginName, false, false);

				$this->setError($pluginName, null);
				$this->setStatus($pluginName, 'toupdate');

				try {
					iMSCP_Events_Manager::getInstance()->dispatch(
						iMSCP_Events::onBeforeUpdatePlugin,
						array(
							'pluginManager' => $this,
							'pluginName' => $pluginName,
							'PluginInstance' => $pluginInstance
						)
					);

					if($force || $fromVersion === null || $toVersion === null) {
						$query = 'SELECT `plugin_info`, FROM `plugin` WHERE `plugin_name`';
						$stmt = exec_query($query, $pluginName);

						$pluginInfo = $stmt->fetchRow(PDO::FETCH_COLUMN);
						$pluginInfo = json_decode($pluginInfo);
						$fromVersion = $pluginInfo['previous_version'];
						$toVersion =  $pluginInfo['version'];
					}

					$pluginInstance->{'update'}($this, $fromVersion, $toVersion);

					if ($this->hasBackend($pluginName)) {
						$this->backendRequest = true;
					} else {
						$this->setStatus($pluginName, 'enabled');
					}

					iMSCP_Events_Manager::getInstance()->dispatch(
						iMSCP_Events::onAfterUpdatePlugin,
						array(
							'pluginManager' => $this,
							'pluginName' => $pluginName,
							'PluginInstance' => $pluginInstance
						)
					);
				} catch (iMSCP_Plugin_Exception $e) {
					$this->setError($pluginName, sprintf('Plugin update has failed: %s', $e->getMessage()));
					write_log(sprintf('Plugin manager: %s plugin update has failed', $pluginName), E_USER_ERROR);
					return false;
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Change the given plugin
	 *
	 * @param string $pluginName Plugin name
	 * @param bool $force Force action
	 * @return bool TRUE on success, FALSE otherwise
	 */
	public function change($pluginName, $force)
	{
		if ($this->isKnown($pluginName) && $this->hasBackend($pluginName)) {
			if ($force || !in_array($this->getStatus($pluginName), array('uninstalled', 'toinstall'))) {
				$this->setError($pluginName, null);
				$this->setStatus($pluginName, 'tochange');
				$this->backendRequest = true;

				return true;
			}
		}

		return false;
	}

	/**
	 * Delete the given plugin
	 *
	 * @param string $pluginName Plugin name
	 * @param bool $force Force action
	 * @return bool TRUE on success, FALSE otherwise
	 */
	public function delete($pluginName, $force = false)
	{
		if ($this->isKnown($pluginName)) {
			$pluginStatus = $this->getStatus($pluginName);

			if ($force || in_array($pluginStatus, array('uninstalled', 'disabled'))) {
				$pluginInstance = $this->load($pluginName, false, false);

				$this->setError($pluginName, null);
				$this->setStatus($pluginName, 'touninstall');

				try {
					iMSCP_Events_Manager::getInstance()->dispatch(
						iMSCP_Events::onBeforeDeletePlugin,
						array(
							'pluginManager' => $this,
							'pluginName' => $pluginName,
							'PluginInstance' => $pluginInstance
						)
					);

					$pluginInstance->{'uninstall'}($this);

					if($this->hasBackend($pluginName) && $pluginStatus != 'uninstalled') {
						$this->backendRequest = true;
					} else {
						$this->setStatus($pluginName, 'todelete');
					}

					iMSCP_Events_Manager::getInstance()->dispatch(
						iMSCP_Events::onAfterDeletePlugin,
						array(
							'pluginManager' => $this,
							'pluginName' => $pluginName,
							'PluginInstance' => $pluginInstance
						)
					);
				} catch(iMSCP_Plugin_Exception $e) {
					$this->setError($pluginName, sprintf('Plugin deletion has failed: %s', $e->getMessage()));
					write_log(sprintf('Plugin manager: %s plugin deletion has failed', $pluginName), E_USER_ERROR);
					return false;
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Is the given plugin protected?
	 *
	 * @throws iMSCP_Plugin_Exception in case the given plugin is not known
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if $pluginName is protected FALSE otherwise
	 */
	public function isProtected($pluginName)
	{
		if($this->isKnown($pluginName)) {
			if (!$this->isLoadedProtectedPluginsList) {
				$file = PERSISTENT_PATH . '/protected_plugins.php';
				$protectedPlugins = array();

				if (is_readable($file)) include_once $file;

				$this->protectedPlugins = $protectedPlugins;
				$this->isLoadedProtectedPluginsList = true;
			}

			return in_array($pluginName, $this->protectedPlugins);
		} else {
			write_log(sprintf('Plugin manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Protect the given plugin
	 *
	 * @param string $pluginName Name of the plugin to protect
	 * @return bool TRUE if plugin to protect is found and protection is successfully done, FALSE otherwise
	 */
	public function protect($pluginName)
	{
		if ($this->isActivated($pluginName) && !$this->isProtected($pluginName)) {

			iMSCP_Events_Manager::getInstance()->dispatch(
				iMSCP_Events::onBeforeProtectPlugin, array('pluginManager' => $this, 'pluginName' => $pluginName)
			);

			$protectedPlugins = $this->protectedPlugins;
			$this->protectedPlugins[] = $pluginName;

			if($this->updateProtectFile()) {
				iMSCP_Events_Manager::getInstance()->dispatch(
					iMSCP_Events::onAfterProtectPlugin, array('pluginManager' => $this, 'pluginName' => $pluginName)
				);

				return true;
			} else {
				$this->protectedPlugins	= $protectedPlugins;
			}
		}

		return false;
	}

	/**
	 * Is the given plugin known by plugin manager?
	 *
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if the given plugin is know by plugin manager , FALSE otherwise
	 */
	public function isKnown($pluginName)
	{
		return isset($this->plugins[$pluginName]);
	}

	/**
	 * The given plugin provides backend part?
	 *
	 * @throws iMSCP_Plugin_Exception in case $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return boolean TRUE if the given plugin provide backend part, FALSE otherwise
	 */
	public function hasBackend($pluginName)
	{
		if(isset($this->pluginsBackend[$pluginName])) {
			return ($this->pluginsBackend[$pluginName] == 'yes');
		} else {
			write_log(sprintf('Plugin manager: Unknown plugin %s', $pluginName), E_USER_ERROR);
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Update plugin list
	 *
	 * @return array An array containing information about added, updated and deleted plugins.
	 */
	public function updatePluginList()
	{
		$knownPluginsData = array();
		$foundPlugins = array();
		$toUpdatePlugins = array();
		$returnInfo = array('new' => 0, 'updated' => 0, 'deleted' => 0);

		$query = 'SELECT `plugin_name`, `plugin_info`, `plugin_config`, `plugin_status` FROM `plugin`';
		$stmt = execute_query($query);

		if ($stmt->rowCount()) {
			$knownPluginsData = $stmt->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
		}

		/** @var $fileInfo SplFileInfo */
		foreach (new RecursiveDirectoryIterator($this->pluginsDirectory, FilesystemIterator::SKIP_DOTS) as $fileInfo) {
			if ($fileInfo->isDir() && $fileInfo->isReadable()) {
				$pluginName = $fileInfo->getBasename();
				$pluginFile = $fileInfo->getPathname() . '/' . $pluginName . '.php';

				if (is_readable($pluginFile)) {
					$className = "iMSCP_Plugin_{$pluginName}";

					if (class_exists($className, true)) {
						/** @var $pluginInstance iMSCP_Plugin */
						$pluginInstance = new $className();

						$pluginInfo = $pluginInstance->getInfo();
						$pluginVersion = $pluginInfo['version'];
						$pluginBackend = file_exists($fileInfo->getPathname() . "/backend/$pluginName.pm") ? 'yes' : 'no';
						$pluginConfig = $pluginInstance->getConfigFromFile();

						// Is a plugin already known by plugin manager?
						if(isset($knownPluginsData[$pluginName])) {
							$pluginStatus = $knownPluginsData[$pluginName]['plugin_status'];
							$knownPluginInfo = json_decode($knownPluginsData[$pluginName]['plugin_info'], true);
							$knownPluginsConfig = json_decode($knownPluginsData[$pluginName]['plugin_config'], true);

							// Set previous version for later use
							$pluginInfo['previous_version'] = $knownPluginInfo['version'];

							// If the plugin has been already installed, schedule update if needed
							$newVersion = version_compare($pluginVersion,  $knownPluginInfo['version'], '>');

							if(
								!in_array($pluginStatus, array('uninstalled', 'toinstall')) &&
								($newVersion || $pluginConfig !== $knownPluginsConfig)
							) {
								$toUpdatePlugins[$pluginName] = array(
									'from_version' => $pluginInfo['previous_version'],
									'to_version' => $newVersion
								);
								$returnInfo['updated']++;
							}
						} else {
							$pluginStatus = 'uninstalled';
							$returnInfo['new']++;
						}

						$pluginData = array(
							'name' => $pluginName,
							'type' => $pluginInstance->getType(),
							'info' => json_encode($pluginInfo),
							// TODO review this when plugin settings interface will be ready
							// For now, when we update plugin list, we override parameters from database with those
							// found in configuration file. This behavior will change when settings interface
							// will be ready
							'config' => json_encode($pluginConfig),
							'status' => $pluginStatus,
							'backend' => $pluginBackend
						);

						unset($pluginInstance);

						// Add/update plugin data in database
						$this->addPluginIntoDatabase($pluginData);
						$foundPlugins[] = $pluginName;
					} else {
						set_page_message(
							tr(
								'Plugin class %s not found in file %s',
								"<strong>$className</strong>",
								"<strong>$pluginFile</strong>"
							),
							'error'
						);
					}
				} else {
					set_page_message(
						tr("Plugin file %s doesn't exist or is not readable.", "<strong>$pluginFile</strong>"), 'error'
					);
					write_log(sprintf("Plugin file %s doesn't exist or is not readable.", $pluginFile), E_USER_ERROR);
				}
			}
		}

		foreach (array_keys($this->plugins) as $pluginName) {
			if (!in_array($pluginName, $foundPlugins)) {
				if ($this->deletePluginFromDatabase($pluginName)) {
					$returnInfo['deleted']++;
				}
			} elseif(array_key_exists($pluginName, $toUpdatePlugins)) {
				$this->update(
					$pluginName,
					false,
					$toUpdatePlugins[$pluginName]['from_version'],
					$toUpdatePlugins[$pluginName]['to_version']
				);
			}
		}

		return $returnInfo;
	}

	/**
	 * Initialize plugin manager
	 *
	 * - Load plugin list from database
	 * - Delete plugins scheduled for deletion
	 *
	 * @return void
	 */
	protected function init()
	{
		$stmt = execute_query('SELECT * FROM `plugin`');
		$pluginsTodelete = array();

		while ($plugin = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			if($plugin['plugin_status'] != 'todelete') {
				$this->plugins[$plugin['plugin_name']] = array(
					'previous_status' => $plugin['plugin_previous_status'],
					'current_status' => $plugin['plugin_status']
				);
				$this->pluginsError[$plugin['plugin_name']] = $plugin['plugin_error'];
				$this->pluginsByType[$plugin['plugin_type']][] = $plugin['plugin_name'];
				$this->pluginsBackend[$plugin['plugin_name']] = $plugin['plugin_backend'];
			} else {
				$this->plugins[$plugin['plugin_name']] = array(
					'previous_status' => $plugin['plugin_previous_status'],
					'current_status' => $plugin['plugin_status']
				);
				$pluginsTodelete[] = $plugin['plugin_name'];
			}
		}

		if(!empty($pluginsTodelete)) $this->deletePlugins($pluginsTodelete);
	}

	/**
	 * Handle plugin protection file
	 *
	 * @return bool TRUE when protection file is successfully created/updated or removed FALSE otherwise
	 */
	protected function  updateProtectFile()
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
				write_log(sprintf('Plugin manager: Unable to write the %s file.', $file));
				return false;
			}
		} elseif (is_writable($file)) {
			if (!@unlink($file)) {
				write_log(sprintf('Plugin manager: Unable to remove the %s file'), $file, E_USER_WARNING);
				return false;
			}
		}

		return true;
	}

	/**
	 * Add or update plugin in database
	 *
	 * @param Array $pluginData Plugin data
	 * @return void
	 */
	protected function addPluginIntoDatabase($pluginData)
	{
		if (!isset($this->plugins[$pluginData['name']])) {
			exec_query(
				'
					INSERT INTO `plugin` (
						`plugin_name`, `plugin_type`, `plugin_info`, `plugin_config`, `plugin_status`, `plugin_backend`
					) VALUE (
						:name, :type, :info, :config, :status, :backend
					)
				'
				,
				$pluginData
			);
		} else {
			exec_query(
				'
					UPDATE
						`plugin` SET `plugin_info` = ?, `plugin_config` = ?, `plugin_status` = ?, `plugin_backend` = ?
					WHERE
						`plugin_name` = ?
				',
				array(
					$pluginData['info'], $pluginData['config'], $pluginData['status'], $pluginData['backend'],
					$pluginData['name']
				)
			);
		}
	}

	/**
	 * Delete plugin definition from database
	 *
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if plugin was found and deleted from database, FALSE otherwise.
	 */
	protected function deletePluginFromDatabase($pluginName)
	{
		$stmt = exec_query('DELETE FROM `plugin` WHERE `plugin_name` = ?', $pluginName);

		if (!$stmt->rowCount()) {
			return false;
		}

		// Force protected_plugins.php file to be regenerated or removed if needed
		if ($this->isProtected($pluginName)) {
			$protectedPlugins = array_flip($this->protectedPlugins);
			unset($protectedPlugins[$pluginName]);
			$this->protectedPlugins = array_flip($protectedPlugins);
			$this->updateProtectFile();
		}

		unset($this->plugins[$pluginName]);
		write_log(sprintf('Plugin manager: %s plugin has been removed from database', $pluginName), E_USER_NOTICE);

		return true;
	}

	/**
	 * Delete all plugins scheduled for deletions
	 *
	 * @param array $pluginNames List of plugin to delete
	 */
	protected function deletePlugins(array $pluginNames)
	{
		foreach($pluginNames as $pluginName) {
			if(utils_removeDir($this->pluginsDirectory . '/' . $pluginName)) {
				$this->deletePluginFromDatabase($pluginName);
				unset($this->plugins[$pluginName]);
			} else {
				write_log(sprintf('Plugin manager: Unable to delete the %s plugin', $pluginName), E_USER_ERROR);
				set_page_message(tr('Unable to delete the %s plugin. Please, remove it manually.', "<strong>$pluginName</strong>"), 'error');
			}
		}
	}
}
