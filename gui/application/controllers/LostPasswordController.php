<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * Copyright (C) 2010 by internet Multi Server Control Panel - http://i-mscp.net
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 * The Original Code is "i-MSCP - internet Multi Server Control Panel".
 *
 * The Initial Developer of the Original Code is i-MSCP Team.
 * Portions created by Initial Developer are Copyright (C) 2010 by
 * internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @copyright   2010 by i-MSCP | http://i-mscp.net
 * @author      Zend Tools
 * @author      i-MSCP Team
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/ GPL v2
 */

/**
 * Lost Password controller
 *
 * @author Laurent Declercq <l.declercq@i-mscp.net>
 * @version DRAFT (to be finished)
 */
class LostPasswordController extends Zend_Controller_Action
{

	/**
	 * Initialize the controller
	 * 
	 * @return void
	 */
    public function init()
    {
	    $this->_helper->layout->setLayout('simple');
    }

	/**
	 * Default action
	 * 
	 * @return void
	 */
    public function indexAction()
    {
	    $request = $this->getRequest();

	    if($request->isPost()) {
		    $captchaInputData = $request->getParam('captcha');

			if($this->isValidCaptcha($captchaInputData)) {
				// do - sendrequest
			} else {
				// flash notice
			}
	    }

		$this->view->captchaCode =  $this->generateCaptcha();
    }

	/**
	 * Generate a captcha
	 *
	 * @return string Unique captcha identifier
	 */
	protected function generateCaptcha() {

		$captcha = new Zend_Captcha_Image(
			array(
				'Timeout' => 200,
				'Wordlen' => 8,
				'Height' => 50,
				'Font' => APPLICATION_PATH . '/fonts/Essays1743.ttf',
				'Width' => 190,
				'FontSize' => 25,
				'ImgDir' => PUBLIC_PATH . '/captcha',
				'ImgUrl' => '/captcha',
				'ImgAlt' => 'Captcha code',
				'Expiration' => 300
			)
		);

		// Generate captcha
		$this->view->captchaId = $captcha->generate();

		// Return captcha identifier
		return $captcha->render($this->view);
	}

	/**
	 * Validate captcha
	 * 
	 * @param  $captcha
	 * @return bool TRUE on success, FALSE otherwise
	 */
	protected function isValidCaptcha($captcha) {

		$captchaSession = new Zend_Session_Namespace('Zend_Form_Captcha_' . $captcha['id']);
		$captchaIterator = $captchaSession->getIterator();
		$captchaWord = $captchaIterator['word'];

		return ($captchaWord && $captcha['input'] == $captchaWord);
	}
}
