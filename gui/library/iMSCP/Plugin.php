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
 * @subpackage	Plugin
 * @copyright	2010 - 2012 by i-MSCP Team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.1
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Plugin base class.
 *
 * Please, do not inherite from this class. Instead, inherite from the specialized classes localized into
 * gui/library/iMSCP/Plugin/
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Plugin
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.3
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
	 */
	public function init() {}

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
	 * @return array
	 */
	public function getInfo()
	{
		$parts = explode('_', get_class($this));
		$infoFile = PLUGINS_PATH . '/' . $parts[2] . '/info.php';

		$info = array();

		if (is_readable($infoFile)) {
			$info = include $infoFile;
		} else {
			set_page_message(
				tr(
					'getInfo() not implemented in %s and %s not found. <br /> This is a bug in the %s plugin and should be reported to the plugin author.',
					get_class($this),
					$infoFile,
					$parts[2]
				), 'warning'
			);
		}

		return array_merge(
			array(
				'author' => tr('Unknown'),
				'email' => '',
				'version' => 'Unknown',
				'date' => '0000-00-00',
				'name' => $parts[2],
				'desc' => tr('No provided'),
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
	 * @return array
	 */
	final public function getDefaultConfig()
	{
		return $this->_loadDefaultConfig();
	}

	/**
	 * Returns given configuration parameter.
	 *
	 * @param string $paramName Configuration parameter name
	 * @return mixed Configuration parameter value or NULL if $paramName no found
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
	 * Load plugin configuration parameters.
	 *
	 * @return void
	 */
	final protected function loadConfig()
	{
		/** @var $config iMSCP_Config_Handler_File */
		$config = iMSCP_Registry::get('config');

		$default = $this->_loadDefaultConfig();
		$name = $this->getName();

		// TODO Should be reviewed to avoid too many queries when multiple plugins call this method.
		$stmt = exec_query('SELECT `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', $name);

		if ($stmt->rowCount()) {
			$config->PLUGIN[$name] = unserialize($stmt->fetchRow(PDO::FETCH_COLUMN));

			foreach ($default as $parameter => $value) {
				if (isset($config->PLUGIN[$name][$parameter])) {
					continue;
				}

				$config->PLUGIN[$name][$parameter] = $value;
			}
		} else {
			$config->PLUGIN[$name] = $default;
		}

		$this->_isLoadedConfig = true;
		$this->_config =& $config->PLUGIN[$name];
	}

	/**
	 * Load default plugin configuration parameters.
	 *
	 * @return array
	 */
	final protected function _loadDefaultConfig()
	{
		$configFile = PLUGINS_PATH . '/' . $this->getName() . '/config.php';
		$config = array();

		if (is_readable($configFile)) {
			$config = include $configFile;
		}

		return $config;
	}
}
