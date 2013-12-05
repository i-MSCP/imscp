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
 * @subpackage  Plugin
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * iMSCP_Plugin class
 *
 * Please, do not inherite from this class. Instead, inherite from the specialized classes localized into
 * gui/library/iMSCP/Plugin/
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Plugin
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
abstract class iMSCP_Plugin
{
	/**
	 * @var array Plugin configuration parameters
	 */
	protected $_config = array();

	/**
	 * @var bool TRUE if plugin configuration is loaded, FALSE otherwise
	 */
	protected $isLoadedConfig = false;

	/**
	 * Constructor
	 *
	 * @return iMSCP_Plugin
	 */
	public function __construct()
	{
		$this->init();
	}

	/**
	 * Returns plugin general information
	 *
	 * Need return an associative array with the following info:
	 *
	 * author: Plugin author name(s)
	 * email: Plugin author email
	 * version: Plugin version
	 * date: Last modified date of the plugin in YYYY-MM-DD format
	 * name: Plugin name
	 * desc: Plugin short description (text only)
	 * url: Website in which it's possible to found more information about the plugin.
	 *
	 * A plugin can provide any other info for its own needs. However, the following keywords are reserved for internal
	 * use:
	 *
	 *  __nversion__      : Contain the last available plugin version
	 *  __installable__   : Tell the plugin manager whether or not the plugin is installable
	 *  __uninstallable__ : Tell the plugin manager whether or not the plugin can be uninstalled
	 *
	 * @throws iMSCP_Plugin_Exception in case plugin info file cannot be read
	 * @return array An array containing information about plugin
	 */
	public function getInfo()
	{
		$parts = explode('_', get_class($this));
		$infoFile = iMSCP_Registry::get('pluginManager')->getPluginDirectory() . '/' . $parts[2] . '/info.php';

		$info = array();

		if (@is_readable($infoFile)) {
			$info = include $infoFile;
		} else {
			if (!file_exists($infoFile)) {
				set_page_message(
					tr(
						'getInfo() not implemented in %s and %s not found. <br /> This is a bug in the %s plugin and should be reported to the plugin author.',
						get_class($this),
						$infoFile,
						$parts[2]
					),
					'warning'
				);
			} else {
				throw new iMSCP_Plugin_Exception("Unable to read the $infoFile file. Please, check file permissions");
			}
		}

		return array_merge(
			array(
				'author' => tr('Unknown'),
				'email' => '',
				'version' => '0.0.0',
				'date' => '0000-00-00',
				'name' => $parts[2],
				'desc' => tr('Not provided'),
				'url' => ''
			),
			$info
		);
	}

	/**
	 * Returns plugin type
	 *
	 * @return string
	 */
	final public function getType()
	{
		static $type = null;

		if (null === $type) {
			list(, , $type) = explode('_', get_parent_class($this), 3);
		}

		return $type;
	}

	/**
	 * Returns plugin name
	 *
	 * @return string
	 */
	final public function getName()
	{
		static $name = null;

		if (null === $name) {
			list(, , $name) = explode('_', get_class($this), 3);
		}

		return $name;
	}

	/**
	 * Return plugin configuration
	 *
	 * @return array An associative array which contain plugin configuration
	 */
	final public function getConfig()
	{
		if (!$this->isLoadedConfig) {
			$this->loadConfig();
		}

		return $this->_config;
	}

	/**
	 * Return plugin configuration from file
	 *
	 * @throws iMSCP_Plugin_Exception in case plugin configuration file is not readable
	 * @return array
	 */
	final public function getConfigFromFile()
	{
		$configFile = iMSCP_Registry::get('pluginManager')->getPluginDirectory() . '/' . $this->getName() . '/config.php';
		$config = array();

		if (@file_exists($configFile)) {
			if (@is_readable($configFile)) {
				$config = include $configFile;
			} else {
				throw new iMSCP_Plugin_Exception(
					sprintf('Unable to read the plugin %s file. Please check file permissions', $configFile)
				);
			}
		}

		return $config;
	}

	/**
	 * Returns the given plugin configuration
	 *
	 * @param string $paramName Configuration parameter name
	 * @param mixed $default Default value returned in case $paramName is not found
	 * @return mixed Configuration parameter value or NULL if $paramName not found
	 */
	final public function getConfigParam($paramName, $default = null)
	{
		if (!$this->isLoadedConfig) {
			$this->loadConfig();
		}

		return (isset($this->_config[$paramName])) ? $this->_config[$paramName] : $default;
	}

	/**
	 * Load plugin configuration from database
	 *
	 * @return void
	 */
	final protected function loadConfig()
	{
		$stmt = exec_query('SELECT plugin_config FROM plugin WHERE plugin_name = ?', $this->getName());

		if ($stmt->rowCount()) {
			$this->_config = json_decode($stmt->fetchRow(PDO::FETCH_COLUMN), true);
		} else {
			$this->_config = array();
		}
	}

	/**
	 * Allow plugin initialization
	 *
	 * This method allow to do some initialization tasks without overriding the constructor.
	 *
	 * @return void
	 */
	protected function init()
	{
	}

	/**
	 * Plugin installation
	 *
	 * This method is automatically called by the plugin manager when the plugin is being installed.
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function install(iMSCP_Plugin_Manager $pluginManager)
	{
	}

	/**
	 * Plugin activation
	 *
	 * This method is automatically called by the plugin manager when the plugin is being enabled (activated).
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function enable(iMSCP_Plugin_Manager $pluginManager)
	{
	}

	/**
	 * Plugin deactivation
	 *
	 * This method is automatically called by the plugin manager when the plugin is being disabled (deactivated).
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function disable(iMSCP_Plugin_Manager $pluginManager)
	{
	}

	/**
	 * Plugin update
	 *
	 * This method is automatically called by the plugin manager when the plugin is being updated.
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @param string $fromVersion Version from which plugin update is initiated
	 * @param string $toVersion Version to which plugin is updated
	 * @return void
	 */
	public function update(iMSCP_Plugin_Manager $pluginManager, $fromVersion, $toVersion)
	{
	}

	/**
	 * Plugin uninstallation
	 *
	 * This method is automatically called by the plugin manager when the plugin is being uninstalled.
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function uninstall(iMSCP_Plugin_Manager $pluginManager)
	{
	}

	/**
	 * Plugin deletion
	 *
	 * This method is automatically called by the plugin manager when the plugin is being deleted.
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function delete(iMSCP_Plugin_Manager $pluginManager)
	{
	}

	/**
	 * Get plugin item with error status
	 *
	 * This method is called by the i-MSCP debugger.
	 *
	 * Note: *MUST* be implemented by any plugin which manage its own items.
	 *
	 * @return array
	 */
	public function getItemWithErrorStatus()
	{
		return array();
	}

	/**
	 * Set status of the given plugin item to 'tochange'
	 *
	 * This method is called by the i-MSCP debugger.
	 *
	 * Note: *MUST* be implemented by any plugin which manage its own items.
	 *
	 * @param string $table Table name
	 * @param string $field Status field name
	 * @param int $itemId item unique identifier
	 * @return void
	 */
	public function changeItemStatus($table, $field, $itemId)
	{
	}

	/**
	 * Return count of request in progress
	 *
	 * This method is called by the i-MSCP debugger.
	 *
	 * Note: *MUST* be implemented by any plugin which manage its own items.
	 *
	 * @return int
	 */
	public function getCountRequests()
	{
		return 0;
	}
}
