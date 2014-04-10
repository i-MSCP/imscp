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
 * @subpackage  Events_Listener
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * Class iMSCP_Listener
 */
class iMSCP_Listener
{
	/**
	 * @var string|array|callable
	 */
	protected $handler;

	/**
	 * @var array Listener metadata, if any
	 */
	protected $metadata;

	/**
	 * @var bool PHP version is greater than 5.4rc1?
	 */
	protected static $isPhp54;

	/**
	 * Constructor
	 *
	 * @param string|array|object|callable $listener PHP callback
	 * @param array $metadata Listener metadata
	 */
	public function __construct($listener, array $metadata = array())
	{
		$this->metadata = $metadata;
		$this->registerHandler($listener);
	}

	/**
	 * Registers the listener provided in the constructor
	 *
	 * @param callable $handler Listener handler
	 * @throws iMSCP_Events_Listener_Exception
	 * @return void
	 */
	protected function registerHandler($handler)
	{
		$event = $this->getMetadatum('event');

		if($event && is_callable(array($handler, $event))) {
			$this->handler = array($handler, $event);
		} elseif(is_callable($handler)) {
			$this->handler = $handler;
		} else {
			throw new iMSCP_Events_Listener_Exception('Invalid handler provided; not callable');
		}
	}

	/**
	 * Return listener handler
	 *
	 * @return callable
	 */
	public function getHandler()
	{
		return $this->handler;
	}

	/**
	 * Invoke listener
	 *
	 * @param  array $args Arguments to pass to listener
	 * @return mixed
	 */
	public function call(array $args = array())
	{
		$handler = $this->getHandler();

		// Minor performance tweak, if the listener gets called more than once
		if (!isset(static::$isPhp54)) {
			static::$isPhp54 = version_compare(PHP_VERSION, '5.4.0rc1', '>=');
		}

		$argCount = count($args);

		if (static::$isPhp54 && is_string($handler)) {
			$result = $this->validateStringCallbackFor54($handler);

			if ($result !== true && $argCount <= 3) {
				$handler = $result;
				$this->handler = $result; // Minor performance tweak, if the listener gets called more than once
			}
		}

		// Minor performance tweak; use call_user_func() until > 3 arguments reached
		switch ($argCount) {
			case 0:
				if (static::$isPhp54) {
					return $handler();
				}

				return call_user_func($handler);
			case 1:
				if (static::$isPhp54) {
					return $handler(array_shift($args));
				}

				return call_user_func($handler, array_shift($args));
			case 2:
				$arg1 = array_shift($args);
				$arg2 = array_shift($args);

				if (static::$isPhp54) {
					return $handler($arg1, $arg2);
				}

				return call_user_func($handler, $arg1, $arg2);
			case 3:
				$arg1 = array_shift($args);
				$arg2 = array_shift($args);
				$arg3 = array_shift($args);

				if (static::$isPhp54) {
					return $handler($arg1, $arg2, $arg3);
				}

				return call_user_func($handler, $arg1, $arg2, $arg3);
			default:
				return call_user_func_array($handler, $args);
		}
	}

	/**
	 * Invoke as functor
	 *
	 * @return mixed
	 */
	public function __invoke()
	{
		return $this->call(func_get_args());
	}

	/**
	 * Get all listener metadata
	 *
	 * @return array
	 */
	public function getMetadata()
	{
		return $this->metadata;
	}

	/**
	 * Retrieve a single metadatum
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getMetadatum($name)
	{
		if (array_key_exists($name, $this->metadata)) {
			return $this->metadata[$name];
		}

		return null;
	}

	/**
	 * Validate a static method call
	 *
	 * Validates that a static method call in PHP 5.4 will actually work
	 *
	 * @param string $handler
	 * @return bool|array
	 * @throws iMSCP_Events_Listener_Exception if invalid
	 */
	protected function validateStringCallbackFor54($handler)
	{
		if (!strstr($handler, '::')) {
			return true;
		}

		list($class, $method) = explode('::', $handler, 2);

		if (!class_exists($class)) {
			throw new iMSCP_Events_Listener_Exception(
				sprintf('Static method call "%s" refers to a class which does not exist', $handler)
			);
		}

		$reflection = new ReflectionClass($class);

		if (!$reflection->hasMethod($method)) {
			throw new iMSCP_Events_Listener_Exception(
				sprintf('Static method call "%s" refers to a method which does not exist', $handler)
			);
		}

		$reflectionMethod = $reflection->getMethod($method);

		if (!$reflectionMethod->isStatic()) {
			throw new iMSCP_Events_Listener_Exception(
				sprintf('Static method call "%s" refers to a method which is not static', $handler)
			);
		}

		// Returning a non boolean value may not be nice for a validate method, but that allows the usage of a static
		// string listener without using the call_user_func function.
		return array($class, $method);
	}
}
