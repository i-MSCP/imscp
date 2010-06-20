<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
 *
 * @license
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
 */

/**
 * ispCP_ExceptionHandler class
 *
 * This class is responsible to handle all uncaught exceptions. This class is an
 * observable subject. An object that implement the SplObserver interface can
 * listen events of this class. This class provides only one event that occurs
 * when an uncaught exceptions is raised.
 *
 * What's the event:
 *
 * When an uncaught exception is raised, the exception handler defined by this
 * class will notify all these observers by passing the exception. After, the
 * observers can do whatever they want with this exception.
 *
 * Observers responsibility can be :
 *
 * - Display the error message on the client browser with specific formatting
 * - Send a mail to the administrator that contains information about error
 * - Register the error in the database
 * - Register the error in a logfile
 *
 * Note: It's not a restrictive list.
 *
 * How this class work:
 *
 * <samp>
 * ispCP_ExceptionHandler::getInstance()->attach(
 *  new ispCP_ExceptionHandler_Writer_Browser(
 *		'themes/omega_original/system-message.tpl'
 *  )
 * );
 * <samp>
 *
 * What is done here ?
 *
 *  1. We create an instance of the ispCP_ExceptionHandler class
 *  2. We attach an observer (Writer) that will listen events of this class
 *
 * See the methods documentation for more information about possibilities.
 * See ispCP/ExceptionHandler/Writer for a list of available observers.
 *
 * Note:
 *
 * This class implements the
 * {@link http://en.wikipedia.org/wiki/Fluent_interfacefluent Fluent interface},
 * so, you can chained the calls of several methods such as the
 * {@link ispCP_ExceptionHandler::attach()} method.
 *
 * @author Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
 * @since 1.0.6
 * @version 1.0.0
 */
class ispCP_ExceptionHandler implements SplSubject, IteratorAggregate, Countable {

	/**
	 * Instance of this class
	 *
	 * @var ispCP_ExceptionHandler
	 */
	protected static $_instance = null;

	/**
	 * Exception instance
	 *
	 * @var Exception
	 */
    protected $_exception;

	/**
	 * SplObjectStorage object that contains all references to the observers
	 *
	 * @var SplObjectStorage
	 */
    public $_observers;

	/**
	 * This class implements the Singleton Design Pattern
	 *
	 * Only one instance of this class should exist at same time
	 *
	 * @param boolean $setHandler If TRUE, sets the exception handler
	 *	automatically
	 * @return void
	 */
	protected function __construct($setHandler) {

		$this->_observers = new SplObjectStorage();

		if($setHandler)
			$this->setHandler();
	}

	/**
	 * This class implements the Singleton Design Pattern
	 *
	 * The Object of this class shouldn't be cloned
	 */
	private function __clone(){}

	/**
	 * Get an ispCP_ExceptionHandler instance
	 *
	 * Returns a reference to {@link ispCP_ExceptionHandler} instance, only
	 * creating it if it doesn't already exist.
	 *
	 * @param boolean $setHandler If TRUE, set exception handler automatically
	 * @return ispCP_ExceptionHandler
	 */
	public static function getInstance($setHandler = true) {

		if(self::$_instance == null) {
			self::$_instance = new self($setHandler);
		}

		return self::$_instance;
	}

	/**
	 * Reset the current instance of this class
	 *
	 * This method can be used to purge all observers. After reset, you can
	 * attach a new set of observers.
	 *
	 * @param boolean $setHandler If TRUE, set the exception handler
	 * @return ispCP_ExceptionHandler
	 */
	public static function resetInstance($setHandler = true) {

		self::$_instance->unsetHandler();
		self::$_instance = null;

		return self::getInstance($setHandler);
	}

	/**
	 * Set Exception handler
	 *
	 * This method set the default {@link exceptionHandler() exception handler}
	 * that will used for all uncaught exceptions.
	 *
	 * @see exceptionHandler()
	 * @return ispCP_ExceptionHandler
	 */
	public function setHandler(){

		set_exception_handler(array($this, 'exceptionHandler'));

		return $this;
	}

	/**
	 * Unset exception handler
	 *
	 * This methods don't reset the current ispCP_ExceptionHandler instance and
	 * so, don't remove all attached observers. Only the exception handler is
	 * unset.
	 *
	 * @return ispCP_ExceptionHandler
	 */
	public function unsetHandler() {

		restore_exception_handler();

		return $this ;
	}

	/**
	 * Exception Handler
	 *
	 * This is the exception handler provided by this class.
	 *
	 * This method will act as default exception handler for all uncaught
	 * exceptions.
	 *
	 * Note: In production, the real uncaught exception that is raised will be
	 * overriden by an {@link ispCP_Exception_Production} to hide all important
	 * informations about the ispCP environment.
	 *
	 * @see setHandler()
	 * @see unsetHandler()
	 * @param Exception $exception Exception object
	 * @return void
	 */
	public function exceptionHandler(Exception $exception) {

		if(error_reporting() == 0 || !ini_get('display_errors')) {
			$this->_exception = new ispCP_Exception_Production();
		} else {
			$this->_exception = $exception;
		}

		// Notify all observers
		$this->notify();

		die();
	}

	/**
	 * Accessor method to get the last exception raised
	 *
	 * The methods should be used by the observers of this class to access to
	 * the last raised exception.
	 *
	 * @return Exception The current Exception if exists; FALSE otherwise
	 */
	public function getException() {

        if (!$this->_exception) {
            return false;
        }

		return $this->_exception;
	}

	/**
	 * Attach a observer that will listen all events of this class
	 *
	 * @param SplObserver $observer Observer that listen events of this class
	 *
	 * @return ispCP_ExceptionHandler
	 */
	public function attach(SplObserver $observer) {

		$this->_observers->attach($observer);

		return $this;
	}

	/**
	 * Dettach an observer that listen events of this class
	 *
	 * @param SplObserver $observer Observer that listen events of this class
	 * @return ispCP_ExceptionHandler
	 */
	public function detach(SplObserver $observer) {

		$this->_observers->detach($observer);

		return $this;
	}

	/**
	 * Notify all observers that listen events of this class
	 *
	 * This method notify all these attached observers by calling their update()
	 * method. The instance of this class is passed as argument of this method.
	 *
	 * @throws Exception
	 * @return void
	 */
	public function notify() {

		// {@link getIterator()} method to understand this statement)
		foreach ($this as $observer) {
			try{
				$observer->update($this);
			} catch(Exception $e){
				die($e->getMessage());
			}
		}
	}

	/**
	 * Defined by the spl
	 *
	 * See {@link http://php.net/manual/fr/arrayobject.getiterator.php}
	 */
	public function getIterator() {

		return $this->_observers;
	}

	/**
	 * PHP overloading for count()
	 *
	 * This methods return the number of attached observers
	 *
	 * @return int Number of attached observers
	 */
	public function count() {

		return count($this->_observers);
	}
}
