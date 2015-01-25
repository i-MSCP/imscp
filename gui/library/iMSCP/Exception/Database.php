<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by i-MSCP Team
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
 * @copyright   2010-2015 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/** @see iMSCP_Exception */
require_once 'iMSCP/Exception.php';

/**
 * Class iMSCP_Exception_Database
 */
class iMSCP_Exception_Database extends iMSCP_Exception
{
	/**
	 * @var string Query that failed
	 */
	protected $query = null;

	/**
	 * Constructor
	 *
	 * @param string $msg Exception Message
	 * @param string $query query Last query executed
	 * @param int $code Exception code
	 * @param Exception $previous OPTIONAL Previous exception
	 */
	public function __construct($msg = '', $query = null, $code = 0, Exception $previous = null)
	{
		$this->query = (string)preg_replace("/[\t\n]+/", ' ', $query);

		parent::__construct($msg, $code, $previous);
	}

	/**
	 * Gets query
	 *
	 * @return string
	 */
	public function getQuery()
	{
		return $this->query;
	}
}
