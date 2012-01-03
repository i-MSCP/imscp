<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2012 by i-MSCP team
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
 * @package		iMSCP_Database
 * @package		Events
 * @copyright	2010-2012 by i-MSCP team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/** @see iMSCP_Events_Event */
require_once 'iMSCP/Events/Event.php';

/**
 * Base class for events thrown in the iMSCP_Database component.
 *
 * @category	iMSCP
 * @package		iMSCP_Database
 * @package		Events
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.1
 */
class iMSCP_Database_Events_Database extends iMSCP_Events_Event
{
	/**
	 * The Database instance in which this event was dispatched.
	 *
	 * @var iMSCP_Database
	 */
	protected $_db;

	/**
	 * Query string.
	 *
	 * @var string
	 */
	protected $_query;

	/**
	 * Constructor.
	 *
	 * @param string $query
	 * @param iMSCP_Database $db Database instance
	 * @return iMSCP_Database_Events_Database
	 */
	public function __construct($query = '', iMSCP_Database $db)
	{
		$this->_db = $db;
		$this->_query = (string)$query;
	}

	/**
	 * Returns the database instance in which this event was dispatched.
	 *
	 * @return iMSCP_Database
	 */
	public function getDb()
	{
		return $this->_db;
	}

	/**
	 * Returns the query string.
	 *
	 * @return string
	 */
	public function getQueryString()
	{
		return $this->_query;
	}
}
