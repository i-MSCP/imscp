<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
 * @package     iMSCP_Bootstap
 * @subpackage  Resource
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Doctrine plugin resource that initialize Doctrine
 *
 * @category    iMSCP
 * @package     iMSCP_Bootstrap
 * @subpackage  Resource
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @since       1.0.0
 * @version     1.0.0
 */
class iMSCP_Bootstrap_Resource_Doctrine extends Zend_Application_Resource_ResourceAbstract
{
	/**
	 * Initialize Doctrine (PHP Object Persistence Libraries)
	 *
	 * @return Doctrine_Manager
	 */
	public function init()
	{
		$dbParams = Zend_Registry::get('config')->resources->db->params;
		$doctrineConfig = Zend_Registry::get('config')->resources->doctrine;

		if ($doctrineConfig->compiled == true && file_exists(ROOT_PATH . '/library/Doctrine.compiled.php')) {
			require_once 'Doctrine.compiled.php';
		} else {
			require_once 'Doctrine/Core.php';
		}

		// Getting Zend_Loader_Autoloader instance
		$autoloader = Zend_Loader_Autoloader::getInstance();

		// Adding Doctrine autoloader
		$autoloader->pushAutoloader(array('Doctrine_Core', 'autoload'), 'Doctrine');

		// Workaround to make  the 'sfYaml' librarie (from Doctrine) loadable
		// See issue DC-288 (http://www.doctrine-project.org/jira/browse/DC-288)
		$autoloader->pushAutoloader(array('Doctrine_Core', 'autoload'), 'sfYaml');

		// Get Doctrine manager
		$mngr = Doctrine_Manager::getInstance();

		// Set models to be autoloaded and not included (Doctrine_Core::MODEL_LOADING_AGGRESSIVE)
		$mngr->setAttribute(Doctrine_Core::ATTR_MODEL_LOADING, Doctrine_Core::MODEL_LOADING_CONSERVATIVE);

		// Enable ModelTable classes to be loaded automatically
		$mngr->setAttribute(Doctrine_Core::ATTR_AUTOLOAD_TABLE_CLASSES, true);

		// Enable validation on save()
		$mngr->setAttribute(Doctrine_Core::ATTR_VALIDATE, Doctrine_Core::VALIDATE_ALL);

		// Enable sql callbacks to make SoftDelete and other behaviours work transparently
		$mngr->setAttribute(Doctrine_Core::ATTR_USE_DQL_CALLBACKS, true);

		// Enable automatic queries resource freeing
		$mngr->setAttribute(Doctrine_Core::ATTR_AUTO_FREE_QUERY_OBJECTS, true);

		// Connect to database
		$mngr->openConnection($this->_getDsn());

		// Set to utf8
		$mngr->connection()->setCharset($dbParams->charset);

		if ($doctrineConfig->cache == true) {
			$cacheDriver = new Doctrine_Cache_Apc();
			$mngr->setAttribute(Doctrine_Core::ATTR_QUERY_CACHE, $cacheDriver);
		}

		return $mngr;
	}

	/**
	 * Get dsn
	 *
	 * @return string
	 */
	protected function _getDsn() {
		$dbParams = Zend_Registry::get('config')->resources->db->params;
		return "$dbParams->type://$dbParams->username:$dbParams->password@$dbParams->host/$dbParams->dbname";
	}
}
