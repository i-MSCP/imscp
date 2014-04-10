<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
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
 * @package     Events
 * @copyright   2010-2014 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Interface iMSCP_Events_Description
 */
interface iMSCP_Events_Description
{
	/**
	 * Returns event name
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Returns parameters passed to the event
	 *
	 * @return array|ArrayAccess
	 */
	public function getParams();

	/**
	 * Returns a single parameter by name
	 *
	 * @param string $name
	 * @param mixed $default Default value to return if parameter does not exist
	 * @return mixed
	 */
	public function getParam($name, $default = null);

	/**
	 * Set the event name
	 *
	 * @param string $name Event name
	 * @return iMSCP_Events_Description Provides fluent interface, return self
	 */
	public function setName($name);

	/**
	 * Set event parameters.
	 *
	 * @param  string $params
	 * @return iMSCP_Events_Description Provides fluent interface, return self
	 */
	public function setParams($params);

	/**
	 * Set a single parameter by name
	 *
	 * @param  string $name Parameter name
	 * @param  mixed $value Parameter value
	 * @return iMSCP_Events_Description Provides fluent interface, return self
	 */
	public function setParam($name, $value);

	/**
	 * Indicate whether or not the parent iMSCP_Events_Manager_Interface should stop propagating events
	 *
	 * @param  bool $flag
	 * @return void
	 */
	public function stopPropagation($flag = true);

	/**
	 * Has this event indicated event propagation should stop?
	 *
	 * @return bool
	 */
	public function propagationIsStopped();

}
