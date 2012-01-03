<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2012 by i-MSCP Team
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
 * @subpackage	Events_Listeners
 * @copyright	2010-2012 by i-MSCP team
 * @author		Laurent Declercq <l.declercq@i-mscp.net>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * Listeners Stack class.
 *
 * Objects of this class represent a listeners stack that belong to a particular
 * event.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Events_Listeners
 * @author		Laurent Declercq <l.declercq@i-mscp.net>
 * @version		0.0.1
 */
class iMSCP_Events_Listeners_Stack implements IteratorAggregate
{
	/**
	 * Listeners stack.
	 *
	 * @var array
	 */
	protected $_listeners = array();

	/**
	 * Adds a listener to the stack.
	 *
	 * @throws iMSCP_EventsManager_Exception When listener is already registered
	 * @throws iMSCP_EventsManager_Exception When listener with same priority is found
	 * @param string|object $listener Fonction name or Listener objet
	 * @param null $stackIndex OPTIONAL Stack index Listener priority
	 * @return iMSCP_Events_Listeners_Stack Provides fluent interface, returns self
	 */
	public function addListener($listener, $stackIndex = null)
	{
		if (false !== array_search($listener, $this->_listeners, true)) {
			require_once 'iMSCP/Events/Exception.php';
			throw new iMSCP_Events_Exception('Listener is already registered.');
		}

		$stackIndex = (int)$stackIndex;

		if ($stackIndex) {
			if (isset($this->_listeners[$stackIndex])) {
				require_once 'iMSCP/Events/Exception.php';
				throw new iMSCP_Events_Exception(
					'Listener with stackIndex "' . $stackIndex . '" already registered');
			}

			$this->_listeners[$stackIndex] = $listener;
		} else {
			$stackIndex = count($this->_listeners);

			while (isset($this->_listeners[$stackIndex])) {
				++$stackIndex;
			}

			$this->_listeners[$stackIndex] = $listener;
		}

		ksort($this->_listeners);

		return $this;
	}

	/**
	 * Remove a listener from the stack.
	 *
	 * @param string|int|object $listener Listener object or class name or stack index
	 * @return iMSCP_Events_Listeners_Stack Provides fluent interface, returns self
	 */
	public function removeListener($listener)
	{
		if (is_object($listener)) {
			$key = array_search($listener, $this->_listeners, true);

			if (false === $key) {
				require_once 'iMSCP/Events/Exception.php';
				throw new iMSCP_Events_Exception('Listener is not registered.');
			}

			unset($this->_listeners[$key]);
		} elseif (is_string($listener)) {
			foreach ($this->_listeners as $index => $_listener) {
				if (is_object($_listener)) {
					$classname = get_class($_listener);

					if ($listener == $classname) {
						unset($this->_listeners[$index]);
					}
				}
			}
		} elseif (is_int($listener)) {
			unset($this->_listeners[$listener]);
		}

		return $this;
	}

	/**
	 * Implements IteratorAggregate interface.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->_listeners);
	}
}
