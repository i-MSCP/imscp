<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
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
 * @category	ispCP
 * @package		ispCP_Exception
 * @subpackage	Handler
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @author		Laurent Declercq <laurent.declercq@ispcp.net>
 * @version		SVN: $Id$
 * @link		http://isp-control.net ispCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 * @filesource
 */

/**
 * ispCP Exception Handler class
 *
 * This class is responsible to handle all uncaught exceptions. This class is an
 * observable subject. An object that implement {@link ispCP_Exception_Writer}
 * interface can listen events of this class. This class has only one event that
 * occurs when an uncaught exceptions is raised.
 *
 * What's the event:
 *
 * When an uncaught exception is raised, the exception handler defined by this
 * class notify all these writer by passing the ispCP_Exception_Handler
 * instance. After, each observer should call the getException() method to get
 * the exception and do whatever they want with it
 *
 * In production, a secondary {@link ispCP_Exception_Production} object is
 * created. Each writer that write on the client browser must check if this
 * exception exist and use it in place of the real {@link $_exception} that was
 * raised to avoid revealing important information about the environment.
 *
 * See the {@link getProductionException} to learn how to check if an
 * {@link ispCP_Exception_Production} was created and how to get it.
 *
 * Writer observer responsibility can be :
 *
 * - Writing the error message on the client browser with specific formatting
 * - Writing a mail to the administrator that contains information about error
 * - Writing the error in the database
 * - Writing the error in a logfile
 *
 * Note: It's not a restrictive list.
 *
 * How this class work:
 *
 * <code>
 * ispCP_Exception_Handler::getInstance()->attach(
 *  new ispCP_Exception_Writer_Browser(
 *		'themes/omega_original/system-message.tpl'
 *  )
 * );
 * </code>
 *
 * What is done here ?
 *
 *  1. We create an instance of the {@link ispCP_Exception_Handler} class
 *  2. We attach an {@link ispCP_Exception_Writer} that will listen this class
 *
 * See ispCP/Exception/Writer for a list of availables 
 *	{@link ispCP_Exception_Writer writers}.
 *
 * Note:
 *
 * This class implements the
 * {@link http://en.wikipedia.org/wiki/Fluent_interface Fluent interface},
 * so, you can chained the calls of several methods such as the
 * {@link ispCP_Exception_Handler::attach()} method.
 *
 * @category	ispCP
 * @package		ispCP_Exception
 * @subpackage	Handler
 * @author		Laurent Declercq <laurent.declercq@ispcp.net>
 * @since		1.0.6
 * @version		1.0.3
 */
class ispCP_Exception_Handler implements SplSubject, IteratorAggregate, Countable {

	/**
	 * Instance of this class
	 *
	 * @var ispCP_Exception_Handler
	 */
	protected static $_instance = null;

	/**
	 * Production exception
	 *
	 * This variable can contains an {@link ispCP_Exception_Production} object
	 * depending of the current execution context (Development|Production)
	 *
	 * @see getProductionException()
	 * @var ispCP_Exception_Production
	 */
	protected $_productionException = null;

	/**
	 * Exception instance
	 *
	 * This variable contains the real exception raised.
	 *
	 * @see getException()
	 * @var Exception
	 */
    protected $_exception = null;

	/**
	 * SplObjectStorage object
	 *
	 * This storage contains all {@link ispCP_Exception_Writer} objects.
	 *
	 * @var SplObjectStorage
	 */
    protected $_writers;

	/**
	 * This class implements the Singleton Design Pattern
	 *
	 * @return void
	 */
	protected function __construct() {

		$this->_writers = new SplObjectStorage();
	}

	/**
	 * This class implements the Singleton Design Pattern
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Get an ispCP_Exception_Handler instance
	 *
	 * Returns an {@link ispCP_Exception_Handler} instance, only creating it if
	 * it doesn't already exist.
	 *
	 * @return ispCP_Exception_Handler
	 */
	public static function &getInstance() {

		if(self::$_instance == null) {
			self::$_instance = new self();
		}

		return  self::$_instance;
	}

	/**
	 * Reset the current instance
	 *
	 * This method reset the current {@link $_instance} of this class. So,
	 * all writers are automatically removed and then, the exception handler
	 * is set to the new instance {@link $_instance}.
	 *
	 * @param boolean $setHandler If TRUE, recreate the instance automatically
	 * @return ispCP_Exception_Handler|null
	 */
	public static function resetInstance($recreate = true) {

		self::$_instance = null;

		return ($recreate) ? self::getInstance() : null;
	}

	/**
	 * Set exception handler
	 *
	 * This method set the {@link exceptionHandler() exception handler} that
	 * is used for all uncaught exceptions.
	 *
	 * @see exceptionHandler()
	 * @return ispCP_Exception_Handler
	 */
	public function &setHandler(){

		set_exception_handler(array(&self::$_instance, 'exceptionHandler'));

		return self::$_instance;
	}

	/**
	 * Unset exception handler
	 *
	 * This methods restore the previously defined exception handler function
	 *
	 * @return ispCP_Exception_Handler
	 */
	public function &unsetHandler() {

		restore_exception_handler();

		return self::$_instance;
	}

	/**
	 * Exception Handler
	 *
	 * This is the exception handler provided by this class.
	 * This method act like an exception handler for all uncaught exceptions.
	 *
	 * Note: In production, this exception handler create a secondary
	 * ispCP_Exception_Production object. This exception should be used by all
	 * writers that write on the client browser in place of the real raised
	 * exception to avoid revealing important information about the environment.
	 *
	 * @see setHandler()
	 * @see unsetHandler()
	 * @param Exception $exception Exception object
	 * @return void
	 */
	public function exceptionHandler(Exception $exception) {

		if(error_reporting() == 0 || !ini_get('display_errors')) {
			
			if($exception instanceof ispCP_Exception_Production) {
				$this->_exception = $exception;
			} else {
				$this->_exception = $exception;
				$this->_productionException = new ispCP_Exception_Production();
			}
		} else {
			$this->_exception = $exception;
		}

		// Notify all writers
		$this->notify();

		die();
	}

	/**
	 * Accessor method to get the exception raised
	 *
	 * This methods should be used by the {@link ispCP_Exception_Writer} writers
	 * to get the exception raised.
	 *
	 * @return Exception The ispCP_Exception if exists; FALSE otherwise
	 */
	public function getException() {

        if ($this->_exception == null) {
            return false;
        }

		return $this->_exception;
	}

	/**
	 * Accessor method to get the ispCP_Exception_Production object
	 *
	 * Note the {@link ispCP_Exception_Production} is only raised in production.
	 *
	 * @return ispCP_Exception_Production|false
	 */
	public function getProductionException() {

        if ($this->_productionException == null) {
            return false;
        }

		return $this->_productionException;
	}

	/**
	 * Attach a writer that listen all events of this class
	 *
	 * @param ispCP_Exception_Writer $writer Writer that listen events of this
	 *	class
	 * @return ispCP_Exception_Handler
	 */
	public function attach(SplObserver $writer) {

		$this->_writers->attach($writer);

		return $this;
	}

	/**
	 * Dettach an observer that listen events of this class
	 *
	 * @param ispCP_Exception_Writer $writer Writer that listen events of this
	 *	class
	 * @return ispCP_Exception_Handler
	 */
	public function detach(SplObserver $writer) {

		$this->_writers->detach($writer);

		return $this;
	}

	/**
	 * Notify all observers that listen events of this class
	 *
	 * This method notify all these attached observers by calling their update()
	 * method. The {@link $_instance instance} of this class is passed as
	 * argument of this method.
	 *
	 * @throws ispCP_Exception
	 * @return void
	 */
	public function notify() {

		// See the {@link getIterator()} method to understand this statement
		foreach ($this as $writer) {
			try {
				$writer->update($this);
			} catch(Exception $e) {

				// Avoid loop with broken writer
				$this->detach($writer);

				$writerName = get_class($writer);
				$previousMessage = $this->_exception->getMessage();
				$lastMessage = $e->getMessage();

				$message =
					"Error: The Exception Writer `$writerName` was unable" .
					" to write the following message: $previousMessage! Reason: " .
					$lastMessage;

				if(!count($this)) {
					trigger_error($message, E_USER_ERROR);
				} else {
					$this->exceptionHandler(new ispCP_Exception($message));
					die();
				}
			}
		}
	}

	/**
	 * Defined by the SPL
	 *
	 * See {@link http://php.net/manual/fr/arrayobject.getiterator.php}
	 *
	 * @return An iterator to iterate on the attached writers
	 */
	public function getIterator() {

		return $this->_writers;
	}

	/**
	 * PHP overloading for count()
	 *
	 * This methods return the number of attached observers.
	 *
	 * @return int Count of attached members
	 */
	public function count() {

		return count($this->_writers);
	}
}
