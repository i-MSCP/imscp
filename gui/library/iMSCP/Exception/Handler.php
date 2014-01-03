<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Exception_Hander
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2014 by i-MSCP | http://i-mscp.net
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * i-MSCP Exception Handler class
 *
 * This class is responsible to handle all uncaught exceptions. This class is an observable subject. An object that
 * implements {@link iMSCP_Exception_Writer} interface can listen events of this class. This class has only one event
 * that occurs when an uncaught exceptions is raised.
 *
 * <b>What's the event:</b>
 *
 * When an uncaught exception is throws, the exception handler defined by this class notify all these writers by passing
 * the iMSCP_Exception_Handler instance to it. After, each observer should call the {@link getException()} method to get
 * the exception and do whatever they want with it.
 *
 * In production, a secondary {@link iMSCP_Exception_Production} object is created. Each writer that writes on the
 * client browser must check if this exception exists and use it in place of the real {@link $_exception} that was
 * raised to avoid revealing important information about the environment.
 *
 * See the {@link getProductionException} method to learn how to check if an {@link iMSCP_Exception_Production exception}
 * was created and how to get it.
 *
 * <b>Writer observer responsibility can be :</b>
 *
 * - Writing error message on the client browser with specific formatting;
 * - Writing a mail to the administrator that contains information about error;
 * - Writing the error in the database;
 * - Writing the error in a logfile;
 *
 * <b>Note:</b> It's not a restrictive list.
 *
 * <b>Usage example:</b>
 * <code>
 * iMSCP_Exception_Handler::getInstance()->attach(new iMSCP_Exception_Writer_Browser('themes/default/exception.tpl'));
 * </code>
 *
 * <b>What is done here ?</b>
 *
 *  1. We create an instance of the {@link iMSCP_Exception_Handler} class
 *  2. We attach an {@link iMSCP_Exception_Writer} that will listen this class
 *
 * See iMSCP/Exception/Writer for a list of available
 * {@link iMSCP_Exception_Writer writers}.
 *
 * <b>Note:</b>
 *
 * This class implements the {@link http://en.wikipedia.org/wiki/Fluent_interface Fluent interface}, so, you can chained
 * the calls of several methods such as the {@link iMSCP_Exception_Handler::attach()} method.
 *
 * @category	i-MSCP
 * @package		iMSCP_Core
 * @subpackage	Exception_Handler
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.6
 */
class iMSCP_Exception_Handler implements SplSubject, IteratorAggregate, Countable
{
	/**
	 * Instance of this class
	 *
	 * @var iMSCP_Exception_Handler
	 */
	protected static $_instance;

	/**
	 * Production exception.
	 *
	 * This variable can contains an {@link iMSCP_Exception_Production} object
	 * depending of the current execution context (Development|Production)
	 *
	 * @var iMSCP_Exception_Production
	 */
	protected $_productionException;

	/**
	 * Exception instance.
	 *
	 * This variable contains the real exception raised.
	 *
	 * @var Exception
	 */
	protected $_exception;

	/**
	 * SplObjectStorage object.
	 *
	 * This storage contains all {@link iMSCP_Exception_Writer} objects.
	 *
	 * @var SplObjectStorage
	 */
	protected $_writers;

	/**
	 * This class implements the Singleton Design Pattern.
	 */
	protected function __construct()
	{
		$this->_writers = new SplObjectStorage();
		$this->setHandler();
	}

	/**
	 * This class implements the Singleton Design Pattern.
	 *
	 * @return void
	 */
	private function __clone()
	{
	}

	/**
	 * Get an iMSCP_Exception_Handler instance.
	 *
	 * Returns an {@link iMSCP_Exception_Handler} instance, only creating it if it doesn't already exist.
	 *
	 * @return iMSCP_Exception_Handler
	 */
	public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Reset the current instance.
	 *
	 * This method reset the current {@link $_instance} of this class. So, all writers are automatically removed and
	 * then, the exception handler is set to the new instance {@link $_instance}.
	 *
	 * @param boolean $recreate If TRUE, recreate the instance automatically
	 * @return iMSCP_Exception_Handler|null
	 */
	public static function resetInstance($recreate = true)
	{
		self::$_instance = null;
		return ($recreate) ? self::getInstance() : null;
	}

	/**
	 * Sets exception handler.
	 *
	 * This method set the {@link exceptionHandler() exception handler} that is used for all uncaught exceptions.
	 *
	 * @see exceptionHandler()
	 * @return iMSCP_Exception_Handler Provide fluent interface, returns self
	 */
	public function setHandler()
	{
		set_exception_handler(array($this, 'exceptionHandler'));

		return $this;
	}

	/**
	 * Unset exception handler.
	 *
	 * This methods restore the previous exception handler
	 *
	 * @return iMSCP_Exception_Handler Provide fluent interface, returns self
	 */
	public function unsetHandler()
	{
		restore_exception_handler();

		return $this;
	}

	/**
	 * Exception Handler
	 *
	 * This is the exception handler provided by this class. This method act like an exception handler for all uncaught
	 * exceptions.
	 *
	 * <b>Note:</b> In production, this exception handler create a secondary iMSCP_Exception_Production object. This
	 * exception should be used by all writers that write on the client browser in place of the real raised exception to
	 * avoid revealing important information about the environment.
	 *
	 * @param Exception $exception Exception object
	 * @return void
	 */
	public function exceptionHandler(Exception $exception)
	{
		if (error_reporting() == 0 || !ini_get('display_errors')) {
			if ($exception instanceof iMSCP_Exception_Production) {
				$this->_exception = $exception;
			} else {
				$this->_exception = $exception;
				$this->_productionException = new iMSCP_Exception_Production();
			}
		} else {
			$this->_exception = $exception;
		}

		// Notify all observers
		$this->notify();
	}

	/**
	 * Accessor method to get the exception raised.
	 *
	 * This methods should be used by the {@link iMSCP_Exception_Writer} writers to
	 * get the exception raised.
	 *
	 * @return iMSCP_Exception
	 */
	public function getException()
	{
		return $this->_exception;
	}

	/**
	 * Accessor method to get the iMSCP_Exception_Production object.
	 *
	 * <b>Note:</b> The {@link iMSCP_Exception_Production} is only raised in production.
	 *
	 * @return iMSCP_Exception_Production
	 */
	public function getProductionException()
	{
		return $this->_productionException;
	}

	/**
	 * Attach a writer that listen all events.
	 *
	 * @param SplObserver $writer Writer that listen events of this object
	 * @return iMSCP_Exception_Handler Provides fluent interface, returns self
	 */
	public function attach(SplObserver $writer)
	{
		$this->_writers->attach($writer);

		return $this;
	}

	/**
	 * Dettach an observer that listen events of this object.
	 *
	 * @param SplObserver $writer Writer that listen events of this class
	 * @return iMSCP_Exception_Handler Provides fluent interface, returns self
	 */
	public function detach(SplObserver $writer)
	{
		$this->_writers->detach($writer);

		return $this;
	}

	/**
	 * Notify all observers that listen events of this object.
	 *
	 * This method notify all these attached observers by calling their update() method. The {@link $_instance instance}
	 * is passed to each writer as argument of their update() method.
	 *
	 * @throws iMSCP_Exception
	 * @return void
	 */
	public function notify()
	{
		// See the {@link getIterator()} method to understand this statement
		/** @var $writer iMSCP_Exception_Writer */
		foreach ($this as $writer) {
			try {
				$writer->update($this);
			} catch (Exception $e) {
				// An exception was raised from a writer code point, we disable it to avoid loop
				$this->detach($writer);

				$message = sprintf(
					"The Exception Writer `%s` was unable to write the following message: `%s` Reason: %s",
					get_class($writer),
					$this->_exception->getMessage() . ' - at line: ' . $this->_exception->getLine() . ' in file: ' .
						$this->_exception->getFile(),
					$e->getMessage() . ' - at line: ' . $e->getLine() . ' in file: ' . $e->getFile());

				if ($writer instanceof iMSCP_Exception_Writer_Browser || !count($this)) {
					if(iMSCP_Registry::isRegistered('config')) {
						$cfg = iMSCP_Registry::get('config');

						if (isset($cfg->DEBUG) && !$cfg->DEBUG) {
							echo 'An error occurred. Please contact your administrator or reseller.';
							exit;
						}
					}

					trigger_error($message, E_USER_WARNING);
				} else {
					throw new iMSCP_Exception($message);
				}
			}
		}
	}

	/**
	 * Defined by the SPL.
	 *
	 * See {@link http://php.net/manual/fr/arrayobject.getiterator.php}
	 *
	 * @return Iterator An iterator to iterate on the attached writers
	 */
	public function getIterator()
	{
		return $this->_writers;
	}

	/**
	 * PHP overloading for count()
	 *
	 * This methods returns the number of attached observers.
	 *
	 * @return int Count of attached members
	 */
	public function count()
	{
		return count($this->_writers);
	}
}
