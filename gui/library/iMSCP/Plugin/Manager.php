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
 * @version		0.0.1
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Manager class.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Plugin_Manager
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.1
 */
class iMSCP_Plugin_Manager
{

	/**
	 * Plugins directory.
	 *
	 * @var string
	 */
	protected $_pluginsDirectory;

	/**
	 * Plugins manager configuration parameters.
	 *
	 * @var array()
	 */
	protected $_config;

	/**
	 * Constructor.
	 *
	 * @param null $pluginDirectory
	 */
	public function __construct($pluginDirectory)
	{
		if($pluginDirectory) {
			$this->setPluginsDirectory($pluginDirectory);
		}

		$this->_loadConfig();
	}

	/**
	 * Sets plugin directory.
	 *
	 * @thrown iMSCP_Plugin_Exception When $pluginDirectory doesn't exists or is not readable.
	 * @param string $pluginDirectory Plugins directory path
	 * @throws iMSCP_Plugin_Exception
	 */
	public function setPluginsDirectory($pluginDirectory)
	{
		$pluginDirectory = realpath($pluginDirectory);

		if($pluginDirectory && is_readable($pluginDirectory)) {
			$this->_pluginsDirectory = realpath($pluginDirectory);
		} else {
			throw new iMSCP_Plugin_Exception(sprintf("The %s plugins directory doesn't exists or is not readable", $pluginDirectory));
		}
	}

	/**
	 *  Load plugins manager configuration parameters.
	 *
	 * @return void
	 */
	protected  function _loadConfig()
	{
		$config = iMSCP_Registry::get('config');

		if(!isset($config->PLUGIN)) {
			$config->PLUGIN =  array();
			$config->PLUGIN['pluginsManager'] = array();
		} elseif(!isset($config->PLUGIN['pluginsManager'])) {
			$config->PLUGIN['pluginsManager'] = array();
		}

		$this->_config =& $config->PLUGIN['pluginsManager'];
	}


	public function rebuildPluginsIndex()
	{
		$pluginsDirectory = LIBRARY_PATH . '/../plugins';
		$plugins = array();

		$iterator =new RecursiveDirectoryIterator($pluginsDirectory, FilesystemIterator::SKIP_DOTS);


		/** @var $fileInfo SplFileInfo */
		foreach($iterator as $fileInfo)
		{
			if($fileInfo->isDir() && is_readable($pluginsDirectory . '/' . $fileInfo->getFilename())) {
				$plugins = $fileInfo->getFilename();
			}
		}

		$this->_config['plugins'] = $plugins;
	}
}
