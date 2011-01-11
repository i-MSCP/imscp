<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
 * @subpackage  Controllers
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Controller for managing i-MSCP users
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Controllers
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @since       1.0.0
 * @version     1.0.0
 * @todo To be finished
 */
class UserController extends Zend_Controller_Action
{
	/**
	 * @var Zend_Filter_Input
	 */
	protected $inputFilter;


	/**
	 * List all existent users (by roles)
	 *
	 * @return void
	 */
    public function listAction()
    {
    }

	/**
	 * Add new user
	 *
	 * @return void
	 */
    public function addAction()
    {
		$request = $this->getRequest();

	    if($request->isPost() && $this->isValidInputData()) {
		    // Getting form fields
			$params = $this->inputFilter->getEscaped();

			// Encrypt user password
			$filter = new iMSCP_Filter_Encrypt_McryptBase64(Zend_Registry::get('config')->encryption);
			$params['password'] = $filter->encrypt($params['password']);
			$params['role_id'] = $params['role'];

			// Creating new user record - To be replaced  - Doctrine issue
			/*
			$user = new Core_Model_DbTable_User();
		    $user = $user->createRow($params);
			$user->created_on = new Zend_Db_Expr('NOW()');
			 */

			// Saving new record
			// TODO: add observer that will automatically schedule the task and send request to the daemon
			//$user->save();

			// TODO Flash messenger

		    $this->_redirect('/admin/user/list');
	    }

		// Get all roles
		$this->view->roles = $this->getRoles();

		// Show values on error
		if($this->inputFilter && $this->inputFilter->hasInvalid()) {
			foreach($request->getParams() as $field => $value)
				$this->view->$field = $value;
		} else { // Default form
			$randomPassword = iMSCP_Utilities_String_Random::alnum(8, 'mixed');
			$this->view->password = $this->view->password_confirm = $randomPassword;
			$this->view->is_active = true;
		}
    }

	/**
	 * Edit existent user
	 *
	 * @return void
	 */
    public function editAction()
    {
    }

	/**
	 * Change user password
	 *
	 * @return void
	 */
    public function changepasswordAction()
    {
    }

	/**
	 * Validate input data and set appropriate error message on error
	 *
	 * @return bool TRUE on success, FALSE otherwise
	 */
	private function isValidInputData() {

		$request = $this->getRequest();

		// TODO confirm password fields

		$inputFilter = new Zend_Filter_Input(
			// Strip whitespace (or other characters - See PHP trim function) from
			// the beginning and end of any input field value
			array('*' => 'StringTrim'),
			array(
				'username' => array(
					// username is string of alphanumeric characters
					'Alnum',
					// check min-max length for username
					// TODO Making max-min length configurable
					array('StringLength', 6, 8),

					// check that username is not already registered - To be replaced - Doctrine issue
					//array('Db_NoRecordExists', 'user', 'username')
				),
				'password' => array(
					'Alnum', // password is string of alphanumeric characters
					// check min-max length for password
					// TODO Making max-min length configurable
					array('StringLength', 6, 8)),
				'email' => 'EmailAddress', // check email address syntax
				//'role' => array(
					// check that role exists - To be replaced - Doctrine issue
					// TODO Also check that the current user may assign this role
				//	array('Db_RecordExists', 'role', 'id'),
				//),
				// check is_active field syntax
				'is_active' => 'Int'
			),
			$request->getParams()
		);

		// Reference Zend_Filter_Input for further usage
		$this->inputFilter = $inputFilter;

		if(!$inputFilter->isValid()) {
			// TODO using our own helper to handle messages
			$messages = '';
			foreach ($inputFilter->getMessages() as $field => $rule) {
				$messages .= 'Error in ' . ucfirst($field) . ' field : ' . array_shift($rule) . '<br />';
			}

			$this->view->message = array(
				'type' => 'error', 'message' => $messages,
			);

			return false;
		}

		return true;
	}


	/**
	 * Retrieve all available roles
	 * 
	 * @return array
	 */
	protected function getRoles() {

		// Mimic role model behavior as long is not ready
		$roles = array();

		foreach(array('administrator' => 1, 'reseller' => 2, 'customer' => 3) as $roleName => $id) {
			$role = new stdClass();
			$role->id = $id;
			$role->role = $roleName;

			$roles[] = $role;
		}

		return $roles;
	}

	protected function getRandomPassword() {
		
	}
}
