<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP team
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
 * @package     iMSCP_Database
 * @package     Events
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <ldeclercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/** @see iMSCP_Database_Events_Database */
require_once 'iMSCP/Database/Events/Database.php';

/**
 * Base class for events thrown in the iMSCP_Database component.
 *
 * @category    iMSCP
 * @package     iMSCP_Database
 * @package     Events
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_Database_Events_Statement extends iMSCP_Database_Events_Database
{
	/**
	 * Returns a PDOstatement.
	 *
	 * @return PDOStatement
	 */
	public function getStatement()
	{
		return $this->getParam('statement');
	}

	/**
	 * Returns the query string.
	 *
	 * @return string
	 */
	public function getQueryString()
	{
		return $this->getStatement()->queryString;
	}
}
