<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 - 2012 by i-MSCP Team
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
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Plugin_Manager
 * @copyright	2010 - 2012 by i-MSCP Team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Plugin Manager class.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Plugin_Manager
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.3
 */
class iMSCP_Plugin_Manager
{
	/**
	 * @var string Plugins directory
	 */
	protected $_pluginsDirectory;

	/**
	 * @var array Keys are the plugin names and the values a bool to tell if the plugins are activated or deactivated
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
	 * Constructor.
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

		// Register autoloader for plugin classes
		spl_autoload_register(array($this, '_autoload'));
	}

	/**
	 * Generates plugin lists.
	 *
	 * @return array
	 */
	protected function _generatePluginLists()
	{
		$stmt = execute_query('SELECT `plugin_name`, `plugin_type`, `plugin_status` FROM `plugin`');

		while ($plugin = $stmt->fetchRow(PDO::FETCH_OBJ)) {
			if ($plugin->plugin_status == 'enabled') {
				$this->_plugins[$plugin->plugin_name] = true;
				$this->_pluginsByType[$plugin->plugin_type]['enabled'][] = $plugin->plugin_name;
			} elseif ($plugin->plugin_status == 'disabled') {
				$this->_plugins[$plugin->plugin_name] = false;
				$this->_pluginsByType[$plugin->plugin_type]['disabled'][] = $plugin->plugin_name;
			}
		}
	}

	/**
	 * Autoloader for plugin classes.
	 *
	 * @param string $className Plugin class to load
	 */
	public function _autoload($className)
	{
		list(, , $className) = explode('_', $className, 3);
		$filePath = $this->getPluginDirectory() . "/{$className}/{$className}.php";

		if (is_readable($filePath)) {
			require_once $filePath;
		}
	}

	/**
	 * Sets plugins directory.
	 *
	 * @thrown iMSCP_Plugin_Exception When $pluginDirectory doesn't exists or is not readable.
	 * @param string $pluginDirectory Plugins directory path
	 */
	public function setDirectory($pluginDirectory)
	{
		$pluginDirectory = (string)$pluginDirectory;

		if (is_readable($pluginDirectory)) {
			$this->_pluginsDirectory = $pluginDirectory;
		} else {
			throw new iMSCP_Plugin_Exception(
				sprintf("The %s plugins directory doesn't exists or is not readable", $pluginDirectory)
			);
		}
	}

	/**
	 * Returns plugin directory.
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
	 * @param bool $onlyEnabled TRUE to only return enabled plugins (default), FALSE to return both enabled and
	 *							 disabled plugins
	 *
	 * @return array of plugin names
	 */
	function getPluginList($type = 'all', $onlyEnabled = true)
	{
		if ($type == 'all') {
			return $onlyEnabled ? array_keys(array_filter($this->_plugins)) : array_keys($this->_plugins);
		}

		if (!isset($this->_pluginsByType[$type]['enabled'])) {
			$this->_pluginsByType[$type]['enabled'] = array();
		}

		if (!isset($this->_pluginsByType[$type]['disabled'])) {
			$this->_pluginsByType[$type]['disabled'] = array();
		}

		return !$onlyEnabled
			? array_merge($this->_pluginsByType[$type]['enabled'], $this->_pluginsByType[$type]['disabled'])
			: $this->_pluginsByType[$type]['enabled'];
	}

	/**
	 * Loads the given plugin and creates an object of it.
	 *
	 * @param string $pluginType Type of plugin to load
	 * @param string $pluginName Name of the plugin to load
	 * @param bool $newInstance true to return a new instance of the plugin, false to use an already loaded instance
	 * @param bool $loadDisabled true to load even disabled plugins
	 * @return null|iMSCP_Plugin
	 */
	public function load($pluginType, $pluginName, $newInstance = false, $loadDisabled = false)
	{
		if (!$loadDisabled && $this->isDeactivated($pluginName)) {
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
	 * Is plugin activated?
	 *
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if $pluginName is activated FALSE otherwise
	 */
	public function isActivated($pluginName)
	{
		return (bool)($this->_plugins[$pluginName]);
	}

	/**
	 * Activates the given plugin.
	 *
	 * @param string $pluginName Name of the plugin to activate
	 * @return bool TRUE if $pluginName has been successfully activated FALSE otherwise
	 */
	public function activate($pluginName)
	{
		if (array_key_exists($pluginName, $this->_plugins)) {
			$stmt = exec_query(
				"UPDATE `plugin` SET `plugin_status` = ? WHERE `plugin_name` = ?", array('enabled', $pluginName)
			);

			if ($stmt->rowCount()) {
				$this->_plugins[$pluginName] = true;
				return true;
			}
		}

		return false;
	}

	/**
	 * Is plugin deactivated ?
	 *
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if $pluginName is deactivated FALSE otherwise
	 */
	public function isDeactivated($pluginName)
	{
		return (bool)(!$this->_plugins[$pluginName]);
	}

	/**
	 * Deactivates the given plugin.
	 *
	 * @param string $pluginName Name of the plugin to deactivate
	 * @return bool TRUE if $pluginName has been successfully deactivated FALSE otherwise
	 */
	public function deactivate($pluginName)
	{
		if (array_key_exists($pluginName, $this->_plugins)) {
			$stmt = exec_query(
				"UPDATE `plugin` SET `plugin_status` = ? WHERE `plugin_name` = ?", array('disabled', $pluginName)
			);

			if ($stmt->rowCount()) {
				$this->_plugins[$pluginName] = false;
				return true;
			}
		}

		return false;
	}

	/**
	 * Is plugin protected.
	 *
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if $pluginName is protected FALSE otherwise
	 */
	public function isProtected($pluginName)
	{
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
	}

	/**
	 * Protect the given plugin against deactivation.
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
	 * Handle plugin protection file.
	 *
	 * @return bool TRUE when protection file is successfully created/updated or removed FALSE otherwise
	 */
	public function  _protect()
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
				// TODO: Be more generic
				set_page_message(tr('Plugin manager was unable to write the %s cache file for protected plugins.', $file), 'error');
				write_log(sprintf('Plugin manager was unable to write the %s cache file for protected plugins.', $file));
				return false;
			}
		} elseif (is_writable($file)) {
			if (!@unlink($file)) {
				// TODO: Be more generic
				write_log(tr('Plugin manager was unable to remove the %s file', $file), E_USER_WARNING);
				return false;
			}
		}

		return true;
	}

	/**
	 * Update plugin list.
	 *
	 * @return array An array that contains information about added, updated and deleted plugins.
	 */
	public function updatePluginList()
	{
		$knownPLugins = array();
		$foundPlugins = array();
		$returnInfo = array('added' => 0, 'updated' => 0, 'deleted' => 0);

		$query = 'SELECT `plugin_name`, `plugin_info`, `plugin_config`, `plugin_status` FROM `plugin`';
		$stmt = execute_query($query);

		if ($stmt->rowCount()) {
			$knownPLugins = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
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
							'status' => array_key_exists($pluginName, $knownPLugins)
								? $knownPLugins[$pluginName][0]['plugin_status'] : 'disabled'
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
						// TODO: Be more generic
						set_page_message(tr('The <strong>%s</strong> class for the <strong>%s</strong> plugin was not found in file <strong>%s</strong>', $className, $pluginName, $pluginFile), 'error');
					}
				} else {
					// TODO: Be more generic
					set_page_message(tr('The <strong>%s</strong> file for the <strong>%s</strong> plugin do not exists or is not readable.', $pluginFile, $pluginName), 'error');
				}
			}
		}

		// Delete orphan plugin definitions
		foreach (array_keys($knownPLugins) as $pluginName) {
			if (!in_array($pluginName, $foundPlugins)) {
				if ($this->_deletePluginFromDatabase($pluginName)) {
					$returnInfo['deleted']++;
				}
			}
		}

		return $returnInfo;
	}

	/**
	 * Add or update plugin in database.
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
						`plugin_name`, `plugin_type`, `plugin_info`, `plugin_config`, `plugin_status`
					) VALUE (
						:name, :type, :info, :config, :status
					)
			';
			exec_query($query, $pluginData);
			return 1;
		}

		$query = 'UPDATE `plugin` SET `plugin_info` = ?, `plugin_config` = ? WHERE `plugin_name` = ?';
		exec_query($query, array($pluginData['info'], $pluginData['config'], $pluginData['name']));

		return 2;

	}

	/**
	 * Delete plugin definition from database.
	 *
	 * @param string $pluginName Plugin name
	 * @return bool TRUE if plugin was found and deleted from database, FALSE otherwise.
	 */
	protected function _deletePluginFromDatabase($pluginName)
	{
		$stmt = exec_query('DELETE FROM `plugin` WHERE `plugin_name` = ?', (string)$pluginName);

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
