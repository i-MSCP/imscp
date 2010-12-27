<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 by internet Multi Server Control Panel
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
 * @category    i-MSCP
 * @copyright   2010 by i-MSCP | http://i-mscp.net
 * @author      i-MSCP Team
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

class Core_AuthController extends Zend_Controller_Action
{

    public function init()
    {
        $this->_helper->layout->setLayout('simple');
    }

    public function indexAction()
    {
	    $auth = Zend_Auth::getInstance();
	    // If we're already logged in, just redirect
	    if ($auth->hasIdentity()) {
		    $this->successRedirect();
	    }
        $request = $this->getRequest();
	    $this->view->message = null;
	    if ($request->isPost()) {
		    $username = $request->getParam('uname');
		    $password = $request->getParam('upass');
		    if ($username == 'admin' && $password == 'password') {
			    $auth->getStorage()->write(1);
			    $this->successRedirect();
		    } else {
			    // TODO: Build the message handler into a View Helper
			    $this->view->message = array(
				    'type' => 'error',
				    'message' => 'Invalid username and password combination.',
			    );
		    }
	    }
    }

	public function logoutAction() {
		Zend_Auth::getInstance()->clearIdentity();
		$this->_redirect('');
	}

	public function successRedirect() {
		// Figure out where they are supposed to go.
		$this->_redirect('admin/user/list');
	}
}

