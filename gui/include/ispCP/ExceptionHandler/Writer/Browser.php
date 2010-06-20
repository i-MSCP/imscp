<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
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
 * @see ispCP_ExceptionHandler_Writer_Abstract
 */
require_once  INCLUDEPATH . '/ispCP/ExceptionHandler/Writer/Abstract.php';

/**
 * Browser writer class
 *
 * This writer display the message defined by an exception to the client
 * browser. This writer acts also as a formatter that will use a specific
 * template for the message formatting. If no template path is given, or if the
 * template file is not reachable, a string that represent the  message is send
 * to the client browser.
 *
 * The given template should be a template file that can be treated by a
 * ptemplate object.
 *
 * Note: Will be improved later.
 *
 * @author Laurent Declercq (nuxwin) <laurent.declercq@ispcp.net>
 * @since 1.0.6
 * @version 1.0.0
 * @todo Display more information like trace on debug mode.
 */
class ispCP_ExceptionHandler_Writer_Browser extends ispCP_ExceptionHandler_Writer_Abstract {

	/**
	 * ptemplate instance
	 *
	 * @var ptemplate
	 */
	protected $_ptemplate = null;

	/**
	 * Template file path
	 *
	 * @var string Template file path
	 */
	protected $_templateFile = null;

	/**
	 * Constructor
	 *
	 * @param string Template file path
	 */
	public function __construct($templateFile = '') {

		if($templateFile !='') {
			if(is_readable($templateFile = $templateFile) ||
				is_readable($templateFile = "../$templateFile")) {

				$this->_templateFile = $templateFile;
			}
		}
	}

	/**
	 * Write the output to the client browser
	 *
	 * @return void
	 */
	protected function _write() {
		$this->_ptemplate->prnt();
	}

	/**
	 * This methods is called from the subject (i.e. when an event occur)
	 *
	 * @param ispCP_ExceptionHandler $exceptionHandler ispCP_ExceptionHandler
	 * @return void
	 */
	public function update(SplSubject $exceptionHandler) {

		// Get the last exception raised
		$message = $exceptionHandler->getException()->getMessage();

		if($this->_templateFile != null) {
			$this->_prepareTemplate($message);

			$this->_ptemplate->parse('PAGE', 'page');
		} else {
			 // @todo Replace this by inline template
			echo $message;
		}

		// Finally, we write the output
		$this->_write();
	}

	/**
	 * Prepare the template
	 *
	 * @param string $message Message to be displayed
	 * @return void
	 */
	protected function _prepareTemplate($message) {

		$this->_ptemplate = new pTemplate();
		$this->_ptemplate->define('page', $this->_templateFile);

		$this->_ptemplate->assign(
			array(
				'THEME_COLOR_PATH' => '/themes/' . 'omega_original',
				'BACKBUTTONDESTINATION' => "javascript:history.go(-1)"
			)
		);

		// i18n support is available ?
		if (function_exists('tr')) {
			$this->_ptemplate->assign(
				array(
					'TR_SYSTEM_MESSAGE_PAGE_TITLE' => tr('ispCP Error'),
					'THEME_CHARSET' => tr('encoding'),
					'TR_BACK' => tr('Back'),
					'TR_ERROR_MESSAGE' => tr('Error Message'),
					'MESSAGE' => $message
				)
			);
		} else {
			$this->_ptemplate->assign(
				array(
					'TR_SYSTEM_MESSAGE_PAGE_TITLE' => 'ispCP Error',
					'THEME_CHARSET' => 'UTF-8',
					'TR_BACK' => 'Back',
					'TR_ERROR_MESSAGE' => 'Error Message',
					'MESSAGE' => $message
				)
			);
		}
	} // end prepareTemplate()
}
