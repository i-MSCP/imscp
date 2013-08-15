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
 * Plugin base class.
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
	 * Plugin configuration parameters.
	 *
	 * @var array
	 */
	protected $_config = array();

	/**
	 * Whether or not plugin configuration is loaded.
	 *
	 * @var bool TRUE if plugin configuration is loaded, FALSE otherwise
	 */
	protected $_isLoadedConfig = false;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->init();
	}

	/**
	 * Allow plugin initialization.
	 * return void
	 */
	public function init() {}

	/**
	 * Plugin installation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function install(iMSCP_Plugin_Manager $pluginManager) {}

	/**
	 * Plugin update
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @param string $fromVersion Version from which update is initiated
	 * @param string $toVersion Version to which plugin is updated
	 * @return void
	 */
	public function update(iMSCP_Plugin_Manager $pluginManager, $fromVersion, $toVersion) {}

	/**
	 * PLugin uninstallation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function uninstall(iMSCP_Plugin_Manager $pluginManager) {}

	/**
	 * Plugin activation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function enable(iMSCP_Plugin_Manager $pluginManager) {}

	/**
	 * Plugin deactivation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function disable(iMSCP_Plugin_Manager $pluginManager) {}

	/**
	 * Returns plugin general information.
	 *
	 * Need return an associative array with the following info:
	 *
	 * author: Plugin author name
	 * email: Plugin author email
	 * version: Plugin version
	 * date: Last modified date of the plugin in YYYY-MM-DD format
	 * name: Plugin name
	 * desc: Plugin short description (text only)
	 * url: Website in which it's possible to found more information about the plugin.
	 *
	 * @throws iMSCP_Plugin_Exception in case plugin info file cannot be read
	 * @return array An array containing information about plugin
	 */
	public function getInfo()
	{
		$parts = explode('_', get_class($this));
		$infoFile = iMSCP_Registry::get('pluginManager')->getPluginDirectory() . '/' . $parts[2] . '/info.php';

		$info = array();

		if(is_readable($infoFile)) {
			$info = include $infoFile;
		} else {
			if(!file_exists($infoFile)) {
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
	 * Returns plugin type.
	 *
	 * @return string
	 */
	final public function getType()
	{
		list(, , $type) = explode('_', get_parent_class($this), 3);

		return $type;
	}

	/**
	 * Returns plugin name.
	 *
	 * @return string
	 */
	final public function getName()
	{
		list(, , $name) = explode('_', get_class($this), 3);
		return $name;
	}

	/**
	 * Return plugin configuration parameters.
	 *
	 * @return array
	 */
	final public function getConfig()
	{
		if (!$this->_isLoadedConfig) {
			$this->loadConfig();
		}

		return $this->_config;
	}

	/**
	 * Return plugin default configuration parameters.
	 *
	 * @throws iMSCP_Plugin_Exception in case plugin configuration file is not readable
	 * @return array
	 */
	final public function getConfigFromFile()
	{
		$configFile = iMSCP_Registry::get('pluginManager')->getPluginDirectory() . '/' . $this->getName() . '/config.php';
		$config = array();

		if(file_exists($configFile)) {
			if (is_readable($configFile)) {
				$config = include $configFile;
			} else {
				throw new iMSCP_Plugin_Exception(
					"Unable to read the plugin $configFile file. Please, check file permissions"
				);
			}
		}

		return $config;
	}

	/**
	 * Returns given configuration parameter.
	 *
	 * @param string $paramName Configuration parameter name
	 * @return mixed Configuration parameter value or NULL if $paramName not found
	 */
	final public function getConfigParam($paramName = null)
	{
		if (!$this->_isLoadedConfig) {
			$this->loadConfig();
		}

		if (isset($this->_config[$paramName])) {
			return $this->_config[$paramName];
		}

		return null;
	}

	/**
	 * Load plugin configuration parameters from database.
	 *
	 * @return void
	 */
	final protected function loadConfig()
	{
		$stmt = exec_query('SELECT `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', $this->getName());

		if($stmt->rowCount()) {
			$this->_config = json_decode($stmt->fetchRow(PDO::FETCH_COLUMN), true);
		} else {
			$this->_config = array();
		}
	}
}
