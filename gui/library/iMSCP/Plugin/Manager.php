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
 * Plugin Manager class.
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
	protected $_pluginsDirectory;

	/**
	 * @var array Keys are the plugin names and the values a string representing plugins status
	 */
	protected $_plugins = array();

	/**
	 * @var array List of protected plugins
	 */
	protected $_protectedPlugins = array();

	/**
	 * @var bool Whether or not list of protected plugin is loaded
	 */
	protected $_isLoadedProtectedPluginsList = false;

	/**
	 * @var array Plugin by type
	 */
	protected $_pluginsByType = array();

	/**
	 * @var array Keys are the plugin names and the values a string telling if the plugin provides a backend part
	 */
	protected $_pluginsBackend = array();

	/**
	 * Constructor
	 *
	 * @param string $pluginDirectory
	 */
	public function __construct($pluginDirectory)
	{
		if ($pluginDirectory) {
			$this->setDirectory($pluginDirectory);
		}

		$this->_generatePluginLists();

		// Allow access to loaded plugins outside this namespace
		iMSCP_Registry::set('PLUGINS', array());

		// Setup autoloader for plugin classes
		spl_autoload_register(array($this, '_autoload'));
	}

	/**
	 * Generates plugin lists
	 *
	 * @return array
	 */
	protected function _generatePluginLists()
	{
		$stmt = execute_query('SELECT `plugin_name`, `plugin_type`, `plugin_status`, `plugin_backend` FROM `plugin`');

		while ($plugin = $stmt->fetchRow(PDO::FETCH_OBJ)) {
			if ($plugin->plugin_status == 'install') {
				$this->_plugins[$plugin->plugin_name] = $plugin->plugin_status;
				$this->_pluginsBackend[$plugin->plugin_name] = $plugin->plugin_backend;
				$this->_pluginsByType[$plugin->plugin_type]['install'][] = $plugin->plugin_name;
			} elseif ($plugin->plugin_status == 'uninstall') {
				$this->_plugins[$plugin->plugin_name] = $plugin->plugin_status;
				$this->_pluginsBackend[$plugin->plugin_name] = $plugin->plugin_backend;
				$this->_pluginsByType[$plugin->plugin_type]['uninstall'][] = $plugin->plugin_name;
			} elseif ($plugin->plugin_status == 'enabled') {
				$this->_plugins[$plugin->plugin_name] = $plugin->plugin_status;
				$this->_pluginsBackend[$plugin->plugin_name] = $plugin->plugin_backend;
				$this->_pluginsByType[$plugin->plugin_type]['enabled'][] = $plugin->plugin_name;
			} elseif ($plugin->plugin_status == 'disabled') {
				$this->_plugins[$plugin->plugin_name] = $plugin->plugin_status;
				$this->_pluginsBackend[$plugin->plugin_name] = $plugin->plugin_backend;
				$this->_pluginsByType[$plugin->plugin_type]['disabled'][] = $plugin->plugin_name;
			} else {
				$this->_plugins[$plugin->plugin_name] = $plugin->plugin_status;
				$this->_pluginsBackend[$plugin->plugin_name] = $plugin->plugin_backend;
				$this->_pluginsByType[$plugin->plugin_type]['unknown'][] = $plugin->plugin_name;
			}
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
	 * @throws iMSCP_Plugin_Exception When $pluginDirectory doesn't exists or is not readable.
	 * @param string $pluginDirectory Plugins directory path
	 */
	public function setDirectory($pluginDirectory)
	{
		if (is_readable($pluginDirectory)) {
			$this->_pluginsDirectory = $pluginDirectory;
		} else {
			throw new iMSCP_Plugin_Exception(
				sprintf("The %s plugins directory doesn't exists or is not readable", $pluginDirectory)
			);
		}
	}

	/**
	 * Returns plugin directory
	 *
	 * @return string Plugin directory
	 */
	public function getPluginDirectory()
	{
		return $this->_pluginsDirectory;
	}

	/**
	 * Returns a list of available plugins of given type
	 *
	 * @param string $type PLugin The type of plugin to return ('all' means all plugin type).
	 * @param bool $onlyEnabled TRUE to only return enabled plugins (default), FALSE to all plugins
	 *
	 * @return array of plugin names
	 */
	function getPluginList($type = 'all', $onlyEnabled = true)
	{
		if ($type == 'all') {
			$onlyEnabled
				? (array_filter($this->_plugins, function ($status) { return ($status == 'enabled'); }))
				: array_keys($this->_plugins);
		}

		if (!isset($this->_pluginsByType[$type]['install'])) {
			$this->_pluginsByType[$type]['install'] = array();
		}

		if (!isset($this->_pluginsByType[$type]['uninstall'])) {
			$this->_pluginsByType[$type]['uninstall'] = array();
		}

		if (!isset($this->_pluginsByType[$type]['enabled'])) {
			$this->_pluginsByType[$type]['enabled'] = array();
		}

		if (!isset($this->_pluginsByType[$type]['disabled'])) {
			$this->_pluginsByType[$type]['disabled'] = array();
		}

		if (!isset($this->_pluginsByType[$type]['unknown'])) {
			$this->_pluginsByType[$type]['unknown'] = array();
		}

		return !$onlyEnabled
			? array_merge(
				$this->_pluginsByType[$type]['install'], $this->_pluginsByType[$type]['uninstall'],
				$this->_pluginsByType[$type]['enabled'], $this->_pluginsByType[$type]['disabled'],
				$this->_pluginsByType[$type]['unknown']
			)
			: $this->_pluginsByType[$type]['enabled'];
	}

	/**
	 * Loads the given plugin and return an instance of it
	 *
	 * @param string $pluginType Type of plugin to load
	 * @param string $pluginName Name of the plugin to load
	 * @param bool $newInstance true to return a new instance of the plugin, false to use an already loaded instance
	 * @param bool $loadEnabledOnly true to load only enabled plugins
	 * @return null|iMSCP_Plugin
	 */
	public function load($pluginType, $pluginName, $newInstance = false, $loadEnabledOnly = true)
	{
		if ($loadEnabledOnly && ! $this->isActivated($pluginName)) {
			return null;
		}

		$className = "iMSCP_Plugin_$pluginName";
		$loadedPlugins =& iMSCP_Registry::get('PLUGINS');

		if (!empty($loadedPlugins[$pluginType][$pluginName])) {
			if ($newInstance) {
				return class_exists($className, true) ? new $className : null;
			}

			return $loadedPlugins[$pluginType][$pluginName];
		}

		if (!class_exists($className, true)) {
			if (is_dir($this->_pluginsDirectory . "/{$pluginName}")) {
				write_log(sprintf('Plugin manager was unable to load the %s plugin - Class %s not found.', $pluginName, $className), E_USER_WARNING);
			}

			return null;
		}

		$loadedPlugins[$pluginType][$pluginName] = new $className;

		return $loadedPlugins[$pluginType][$pluginName];
	}

	/**
	 * Get status of the given plugin
	 *
	 * @throws iMSCP_Plugin_Exception in case $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return string Plugin status
	 */
	public function getStatus($pluginName)
	{
		if(isset($this->_plugins[$pluginName])) {
			return $this->_plugins[$pluginName];
		} else {
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Set status of the given plugin
	 *
	 * @throws iMSCP_Plugin_Exception in case $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @param string $pluginStatus Plugin status
	 * @return void
	 */
	public function setStatus($pluginName, $pluginStatus)
	{
		if(isset($this->_plugins[$pluginName])) {
			exec_query(
				"UPDATE `plugin` SET `plugin_status` = ? WHERE `plugin_name` = ?", array($pluginStatus, $pluginName)
			);
		} else {
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Is the given plugin activated?
	 *
	 * @throws iMSCP_Plugin_Exception in case $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if $pluginName is activated FALSE otherwise
	 */
	public function isActivated($pluginName)
	{
		if(isset($this->_plugins[$pluginName])) {
			return ($this->_plugins[$pluginName] == 'enabled');
		} else {
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Activates the given plugin
	 *
	 * @param string $pluginName Name of the plugin to activate
	 * @param boolean $forceReinstall Whether or not reinstallation must be forced
	 * @return bool TRUE if $pluginName has been successfully activated FALSE otherwise
	 */
	public function activate($pluginName, $forceReinstall = false)
	{
		if (array_key_exists($pluginName, $this->_plugins)) {
			if ($this->_plugins[$pluginName] == 'disabled' || $forceReinstall) {
				// TODO find plugin type dynamically
				$pluginInstance = $this->load('Action', $pluginName, false, false);

				if($pluginInstance && is_callable(array($pluginInstance, 'install'))) {
					$this->setStatus($pluginName, 'install');
					$this->_plugins[$pluginName] = 'install';

					try {
						$pluginInstance->{'install'}($this);

						if($this->hasBackend($pluginName)) {
							send_request();
						} else {
							$this->setStatus($pluginName, 'enabled');
							$this->_plugins[$pluginName] = 'enabled';
						}
					} catch(iMSCP_Plugin_Exception $e) {
						$this->setStatus($pluginName, tr('Plugin installation has failed: %s', $e->getMessage()));
						$this->_plugins[$pluginName] = 'unknown';
					}
				} elseif($this->hasBackend($pluginName)) {
					$this->setStatus($pluginName, 'install');
					$this->_plugins[$pluginName] = 'install';
					send_request();
				} else {
					$this->setStatus($pluginName, 'enabled');
					$this->_plugins[$pluginName] = 'enabled';
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
		if(isset($this->_plugins[$pluginName])) {
			return ($this->_plugins[$pluginName] == 'disabled');
		} else {
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Deactivates the given plugin
	 *
	 * @param string $pluginName Name of the plugin to deactivate
	 * @return bool TRUE if $pluginName has been successfully deactivated FALSE otherwise
	 */
	public function deactivate($pluginName)
	{
		if (array_key_exists($pluginName, $this->_plugins)) {
			if ($this->_plugins[$pluginName] == 'enabled') {
				// TODO find plugin type dynamically
				$pluginInstance = $this->load('Action', $pluginName, false, false);

				if($pluginInstance && is_callable(array($pluginInstance, 'uninstall'))) {
					$this->setStatus($pluginName, 'uninstall');
					$this->_plugins[$pluginName] = 'uninstall';

					try {
						$pluginInstance->{'uninstall'}($this);

						if($this->hasBackend($pluginName)) {
							send_request();
						} else {
							$this->setStatus($pluginName, 'disabled');
							$this->_plugins[$pluginName] = 'disabled';
						}
					} catch(iMSCP_Plugin_Exception $e) {
						$this->setStatus($pluginName, tr('Plugin un-installation has failed: %s', $e->getMessage()));
						$this->_plugins[$pluginName] = 'unknown';
					}
				} elseif($this->hasBackend($pluginName)) {
					$this->setStatus($pluginName, 'uninstall');
					$this->_plugins[$pluginName] = 'uninstall';
					send_request();
				} else {
					$this->setStatus($pluginName, 'disabled');
					$this->_plugins[$pluginName] = 'disabled';
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Is the given plugin protected?
	 *
	 * @throws iMSCP_Plugin_Exception in case $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if $pluginName is protected FALSE otherwise
	 */
	public function isProtected($pluginName)
	{
		if(isset($this->_plugins[$pluginName])) {
			if (!$this->_isLoadedProtectedPluginsList) {
				$file = PERSISTENT_PATH . '/protected_plugins.php';
				$protectedPlugins = array();

				if (is_readable($file)) {
					include_once $file;
				}

				$this->_protectedPlugins = $protectedPlugins;
				$this->_isLoadedProtectedPluginsList = true;
			}

			return in_array($pluginName, $this->_protectedPlugins);
		} else {
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
		if (array_key_exists($pluginName, $this->_plugins) && $this->isActivated($pluginName) &&
			!$this->isProtected($pluginName)
		) {
			$this->_protectedPlugins[] = $pluginName;
			return $this->_protect();
		}

		return false;
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
		if(isset($this->_pluginsBackend[$pluginName])) {
			return ($this->_pluginsBackend[$pluginName] == 'yes');
		} else {
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Is the given plugin installable?
	 *
	 * @throws iMSCP_Plugin_Exception in case $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return boolean TRUE if the given plugin is installable, FALSE otherwise
	 */
	public function isInstallable($pluginName)
	{
		if(isset($this->_pluginsBackend[$pluginName])) {
			$pluginInstance = $this->load('Action', $pluginName, false, false);

			return is_callable(array($pluginInstance, 'install'));
		} else {
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Is the given plugin uninstallable?
	 *
	 * @throws iMSCP_Plugin_Exception in case $pluginName is not known
	 * @param string $pluginName Plugin name
	 * @return boolean TRUE if the given plugin is uninstallable, FALSE otherwise
	 */
	public function isUninstallable($pluginName)
	{
		if(isset($this->_pluginsBackend[$pluginName])) {
			$pluginInstance = $this->load('Action', $pluginName, false, false);

			return is_callable(array($pluginInstance, 'uninstall'));
		} else {
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Force plugin reinsntallation
	 *
	 * @throws iMSCP_Plugin_Exception in case $pluginName is not known
	 * @param string $pluginName Plugin Name
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	public function forceReinstall($pluginName)
	{
		if(isset($this->_plugins[$pluginName])) {
			if($this->isInstallable($pluginName)) {
				$this->activate($pluginName, true);
			}
		} else {
			throw new iMSCP_Plugin_Exception("Unknown plugin $pluginName");
		}
	}

	/**
	 * Update plugin list
	 *
	 * @return array An array that contains information about added, updated and deleted plugins.
	 */
	public function updatePluginList()
	{
		$knownPlugins = array();
		$foundPlugins = array();
		$returnInfo = array('added' => 0, 'updated' => 0, 'deleted' => 0);

		$query = 'SELECT `plugin_name`, `plugin_info`, `plugin_config`, `plugin_status` FROM `plugin`';
		$stmt = execute_query($query);

		if ($stmt->rowCount()) {
			$knownPlugins = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
		}

		/** @var $fileInfo SplFileInfo */
		foreach (new RecursiveDirectoryIterator($this->_pluginsDirectory, FilesystemIterator::SKIP_DOTS) as $fileInfo) {
			if ($fileInfo->isDir() && $fileInfo->isReadable()) {
				$pluginName = $fileInfo->getBasename();
				$pluginFile = $fileInfo->getPathname() . '/' . $pluginName . '.php';

				if (is_readable($pluginFile)) {
					$className = "iMSCP_Plugin_{$pluginName}";

					if (class_exists($className, true)) {
						/** @var $plugin iMSCP_Plugin */
						$plugin = new $className();
						$pluginData = array(
							'name' => $pluginName,
							'type' => $plugin->getType(),
							'info' => serialize($plugin->getInfo()),
							// TODO review this when plugin settings interface will be ready
							// For now, when we update plugin list, we override parameters with those
							// found in default configuration file. This behavior will change when settings interface
							// will be ready
							'config' => serialize($plugin->getDefaultConfig()),
							'status' => array_key_exists($pluginName, $knownPlugins)
								? $knownPlugins[$pluginName][0]['plugin_status'] : 'disabled',
							'backend' => file_exists($fileInfo->getPathname() . "/backend/$pluginName.pm") ? 'yes' : 'no'
						);

						unset($plugin);
						$retVal = $this->_addPluginIntoDatabase($pluginData);

						if ($retVal == 1) {
							$returnInfo['added']++;
						} elseif ($retVal == 2) {
							$returnInfo['updated']++;
						}

						$foundPlugins[] = $pluginName;
					} else {
						set_page_message(tr('The <strong>%s</strong> class for the <strong>%s</strong> plugin was not found in file <strong>%s</strong>', $className, $pluginName, $pluginFile), 'error');
					}
				} else {
					set_page_message(tr('The <strong>%s</strong> file for the <strong>%s</strong> plugin do not exists or is not readable.', $pluginFile, $pluginName), 'error');
				}
			}
		}

		// Delete orphan plugin definitions
		foreach (array_keys($knownPlugins) as $pluginName) {
			if (!in_array($pluginName, $foundPlugins)) {
				if ($this->_deletePluginFromDatabase($pluginName)) {
					$returnInfo['deleted']++;
				}
			}
		}

		return $returnInfo;
	}

	/**
	 * Handle plugin protection file
	 *
	 * @return bool TRUE when protection file is successfully created/updated or removed FALSE otherwise
	 */
	protected function  _protect()
	{
		$file = PERSISTENT_PATH . '/protected_plugins.php';
		$lastUpdate = 'Last update: ' . date('Y/m/d', time()) . " by {$_SESSION['user_logged']}";
		$content = "<?php\n/**\n * Protected plugin list\n * Auto-generated by i-MSCP plugin manager\n";
		$content .= " * $lastUpdate\n */\n\n";

		if (!empty($this->_protectedPlugins)) {
			foreach ($this->_protectedPlugins as $pluginName) {
				$content .= "\$protectedPlugins[] = '$pluginName';\n";
			}

			@unlink($file);

			if (@file_put_contents($file, "$content\n", LOCK_EX) === false) {
				set_page_message(tr('Plugin manager was unable to write the %s cache file for protected plugins.', $file), 'error');
				write_log(sprintf('Plugin manager was unable to write the %s cache file for protected plugins.', $file));
				return false;
			}
		} elseif (is_writable($file)) {
			if (!@unlink($file)) {
				write_log(tr('Plugin manager was unable to remove the %s file', $file), E_USER_WARNING);
				return false;
			}
		}

		return true;
	}

	/**
	 * Add or update plugin in database
	 *
	 * @param Array $pluginData An associative array where each key correspond to a table name without the 'plugin_' prefix.
	 * @return int 1 when new plugin definition was inserted, 2 when plugin definition was updated
	 */
	protected function _addPluginIntoDatabase($pluginData)
	{
		if (!array_key_exists($pluginData['name'], $this->_plugins)) {
			$query = '
				INSERT INTO
					`plugin` (
						`plugin_name`, `plugin_type`, `plugin_info`, `plugin_config`, `plugin_status`, `plugin_backend`
					) VALUE (
						:name, :type, :info, :config, :status, :backend
					)
			';
			exec_query($query, $pluginData);
			return 1;
		}

		$query = '
			UPDATE
				`plugin`
			SET
				`plugin_info` = ?, `plugin_config` = ?, `plugin_backend` = ?
			WHERE
				`plugin_name` = ?
		';
		exec_query(
			$query, array($pluginData['info'], $pluginData['config'], $pluginData['backend'], $pluginData['name'])
		);

		return 2;
	}

	/**
	 * Delete plugin definition from database
	 *
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if plugin was found and deleted from database, FALSE otherwise.
	 */
	protected function _deletePluginFromDatabase($pluginName)
	{
		$stmt = exec_query('DELETE FROM `plugin` WHERE `plugin_name` = ?', $pluginName);

		if (!$stmt->rowCount()) {
			return false;
		}

		// Force protected_plugins.php file to be regenerated or removed if needed
		if ($this->isProtected($pluginName)) {
			$protectedPlugins = array_flip($this->_protectedPlugins);
			unset($protectedPlugins[$pluginName]);
			$this->_protectedPlugins = array_flip($protectedPlugins);
			$this->_protect();
		}

		unset($this->_plugins[$pluginName]);
		write_log(sprintf('Plugin %s was removed from database by plugin manager', $pluginName), E_USER_NOTICE);

		return true;
	}
}
