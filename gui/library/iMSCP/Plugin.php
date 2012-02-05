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
 * Note: Based upon the docuwiki plugin base class.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Plugin
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.1
 */
abstract class iMSCP_Plugin
{
	/**
	 * Plugin configuration parameter.
	 *
	 * @var array
	 */
	protected $_config = array();

	/**
	 * Whether or not plugin configuration is loaded.
	 *
	 * @var bool TRUE if plugin config is loaded, FALSE otherwise
	 */
	protected $_isLoadedConfig = false;

	/**
	 * Return plugin general information.
	 *
	 * Need return an associative array with the following info:
	 *
	 * author: Plugin author name
	 * email: Plugin author email
	 * date: Last modified date of the plugin in YYYY-MM-DD format
	 * name: Plugin name
	 * desc: Plugin short description (text only)
	 * url: Website in which it's possible to found more information about the plugin.
	 *
	 * @return array Array
	 */
	public function getInfo()
	{
		$parts = explode('_', get_class($this));
		$infoFile = LIBRARY_PATH . '/../plugins/' . $parts[2] .  '/info.php';

		if (is_readable($infoFile)) {
			return include $infoFile;
		}

		set_page_message(
			tr(
				'getInfo() not implemented in %s and %s not found. <br /> This is a bug in the %s plugin and should be reported to the plugin author.',
				get_class($this),
				$infoFile,
				$parts[2]
			), 'warning'
		);

		return array(
			'author' => tr('Unknown'),
			'email' => '',
			'date' => '0000-00-00',
			'name' => $parts[2],
			'desc' => tr('No provided'),
			'url' => ''
		);
	}

	/**
	 * Returns the plugin type.
	 *
	 * @return string
	 */
	public function getType()
	{
		list(, , $type) = explode('_', get_parent_class($this), 3);

		return $type;
	}

	/**
	 * Return plugin name.
	 *
	 * @return string
	 */
	public function getName()
	{
		list(, , $name) = explode('_', get_class($this), 3);
		return $name;
	}

	/**
	 * Return given configuration parameter.
	 *
	 * @param string $paramName Configuration parameter name
	 * @return mixed Configuration parameter value or NULL if $paramName no found
	 */
	public function getConfig($paramName)
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
	 * Load plugin configuration parameter
	 *
	 * @return void
	 */
	protected function loadConfig()
	{
		$config = iMSCP_Registry::get('config');

		$default = $this->loadDefaultConfig();
		$name = $this->getName();

		if (!isset($config->PLUGIN)) { // TODO must be review
			$config->PLUGIN = array();
		}

		foreach ($default as $parameter => $value) {

			if (isset($config->PLUGIN[$name][$parameter])) {
				continue;
			}

			$config->PLUGIN[$name][$parameter] = $value;
		}

		$this->_isLoadedConfig = true;
		$this->_config =& $config->PLUGIN[$name];
	}

	/**
	 * Load default plugin configuration parameters.
	 *
	 * @return array
	 */
	protected function loadDefaultConfig()
	{
		$path = LIBRARY_PATH . '/../plugins/' . $this->getName() . '/';
		$config = array();

		if (file_exists($path . 'config.php')) {
			$config = include $path . 'config.php';
		}

		return $config;
	}
}
