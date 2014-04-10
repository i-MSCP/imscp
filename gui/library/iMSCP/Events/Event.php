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
 * @package     iMSCP_Events
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@i-mscp.net>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * Representation of an event
 *
 * Encapsulates the parameters passed, and provides some behavior for interacting with the events manager.
 */
class iMSCP_Events_Event implements iMSCP_Events_Description
{
	/**
	 * @var string Event name
	 */
	protected $name;

	/**
	 * @var array|ArrayAccess|object The event parameters
	 */
	protected $params = array();

	/**
	 * @var bool Whether or not to stop propagation
	 */
	protected $stopPropagation = false;

	/**
	 * Constructor
	 *
	 * @param string $name Event name
	 * @param array|ArrayAccess $params
	 */
	public function __construct($name = null, $params = null)
	{
		if (null !== $name) {
			$this->setName($name);
		}

		if (null !== $params) {
			$this->setParams($params);
		}
	}

	/**
	 * Returns event name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set parameters
	 *
	 * Overwrites parameters
	 *
	 * @throws iMSCP_Events_Exception
	 * @param  array|ArrayAccess|object $params
	 * @return iMSCP_Events_Event Provides fluent interface, returns self
	 */
	public function setParams($params)
	{
		if (!is_array($params) && !is_object($params)) {
			throw new iMSCP_Events_Exception('Event parameters must be an array or object');
		}

		$this->params = $params;

		return $this;
	}

	/**
	 * Returns all parameters
	 *
	 * @return array|object|ArrayAccess
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * Return an individual parameter
	 *
	 * If the parameter does not exist, the $default value will be returned.
	 *
	 * @param  string|int $name Parameter name
	 * @param  mixed $default Default value to be returned if $name doesn't exist
	 * @return mixed
	 */
	public function getParam($name, $default = null)
	{
		// Check in params that are arrays or implement array access
		if (is_array($this->params) || $this->params instanceof ArrayAccess) {
			if (!isset($this->params[$name])) {
				return $default;
			}

			return $this->params[$name];
		}

		// Check in normal objects
		if (!isset($this->params->{$name})) {
			return $default;
		}

		return $this->params->{$name};
	}

	/**
	 * Set the event name
	 *
	 * @param  string $name Event Name
	 * @return iMSCP_Events_Event Provides fluent interface, returns self
	 */
	public function setName($name)
	{
		$this->name = (string)$name;

		return $this;
	}

	/**
	 * Set an individual parameter to a value
	 *
	 * @param string|int $name Parameter name
	 * @param mixed $value Parameter value
	 * @return iMSCP_Events_Event
	 */
	public function setParam($name, $value)
	{
		if (is_array($this->params) || $this->params instanceof ArrayAccess) {
			// Arrays or objects implementing array access
			$this->params[$name] = $value;
		} else {
			// Objects
			$this->params->{$name} = $value;
		}

		return $this;
	}

	/**
	 * Stop further event propagation
	 *
	 * @param  bool $flag TRUE to stop propagation, FALSE otherwise
	 * @return void
	 */
	public function stopPropagation($flag = true)
	{
		$this->stopPropagation = (bool)$flag;
	}

	/**
	 * Is propagation stopped?
	 *
	 * @return bool TRUE if propagation is stopped, FALSE otherwise
	 */
	public function propagationIsStopped()
	{
		return $this->stopPropagation;
	}
}
