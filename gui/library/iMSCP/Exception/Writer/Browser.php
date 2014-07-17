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
 * @package		iMSCP_Exception
 * @subpackage	Writer
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @copyright	2010-2014 by i-MSCP | http://i-mscp.net
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/** @see iMSCP_Exception_Writer */
require_once 'iMSCP/Exception/Writer.php';

/**
 * Browser writer class
 *
 * This writer writes an exception messages to the client browser. This writer acts also as a formatter that will use a
 * specific template for the message formatting. If no template path is given, or if the template file is not reachable,
 * a string that represent the message is write to the client browser.
 *
 * The given template should be a template file that can be treated by a
 * pTemplate object.
 *
 * @category	i-MSCP
 * @package		iMSCP_Exception
 * @subpackage	Writer
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.4
 */
class iMSCP_Exception_Writer_Browser extends iMSCP_Exception_Writer
{
	/**
	 * @var iMSCP_pTemplate
	 */
	protected $_tpl;

	/**
	 * @var string Template file path
	 */
	protected $_templateFile;

	/**
	 * Constructor.
	 *
	 * @param string $templateFile Template file path
	 */
	public function __construct($templateFile = '')
	{
		$this->_templateFile = (string)$templateFile;
	}

	/**
	 * Writes the exception message to the client browser.
	 *
	 * @return void
	 */
	protected function _write()
	{
		if (!($tpl = $this->_tpl)) {
			$tpl = new iMSCP_pTemplate();
			$tpl->define_no_file(
				'layout',
				'
					<html>
						<head>
							<title>i-MSCP - internet Multi Server Control Panel - Exception</title>
						</head>
						<body>
							<h1>{BOX_MESSAGE_TITLE}</h1>
							<p>{BOX_MESSAGE}</p>
						</body>
					</html>
				');

			$tpl->assign(
				array(
					'BOX_MESSAGE_TITLE' => 'An exception has been thrown.',
					'BOX_MESSAGE' => $this->_message
				)
			);
		}

		$tpl->parse('LAYOUT', 'layout');

		iMSCP_Events_Aggregator::getInstance()->dispatch(
			iMSCP_Events::onExceptionToBrowserEnd, array('context' => $this, 'templateEngine' => $tpl)
		);

		$tpl->prnt();
	}

	/**
	 * This methods is called from the subject.
	 *
	 * @param SplSubject $exceptionHandler iMSCP_Exception_Handler
	 * @return void
	 */
	public function update(SplSubject $exceptionHandler)
	{
		iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onExceptionToBrowserStart, array('context', $this));

		/** @var $exceptionHandler iMSCP_Exception_Handler */
		// Always write the real exception message if we are the admin
		if (isset($_SESSION) &&
			((isset($_SESSION['logged_from_type']) && $_SESSION['logged_from_type'] == 'admin')
				|| isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin')
		) {
			/** @var $exception iMSCP_Exception */
			$exception = $exceptionHandler->getException();

			$this->_message .= 'An exception with the following message has been thrown in file ' .
				$exception->getFile() . ' (Line: ' .
				$exception->getLine() . "):\n\n";

			$this->_message .= preg_replace('#([\t\n]+|<br \/>)#', ' ', $exception->getMessage());

			/** @var $exception iMSCP_Exception_Database */
			if ($exception instanceof iMSCP_Exception_Database) {
				$this->_message .= "\n\n<strong>Query was:</strong>\n\n" . $exception->getQuery();
			}
		} else { // Production exception
			$exception = $exceptionHandler->getProductionException();

			if (!$exception) { // If not exception for production is found, we get the original exception
				$exception = $exceptionHandler->getException();
			}

			$this->_message = $exception->getMessage();
		}

		if ($this->_templateFile) {
			$this->_render();
		}

		// Finally, we write the output
		$this->_write();
	}

	/**
	 * Render exception template file.
	 *
	 * @return void
	 */
	protected function _render()
	{
		$tpl = new iMSCP_pTemplate();
		$tpl->define_dynamic(
			array(
				'layout' => 'shared/layouts/simple.tpl',
				'page' => $this->_templateFile,
				'page_message' => 'layout',
				'backlink_block' => 'page'
			)
		);

		if (iMSCP_Registry::isRegistered('backButtonDestination')) {
			$backButtonDestination = iMSCP_Registry::get('backButtonDestination');
		} else {
			$backButtonDestination = 'javascript:history.go(-1)';
		}

		$tpl->assign(
			array(
				'TR_PAGE_TITLE' => 'i-MSCP - internet Multi Server Control Panel - Fatal Error',
				'CONTEXT_CLASS' => ' no_header',
				'productLink' => 'http://www.i-mscp.net',
				'productLongName' => 'internet Multi Server Control Panel',
				'productCopyright' => '© 2010-2014 i-MSCP Team<br/>All Rights Reserved',
				'BOX_MESSAGE_TITLE' => 'An error has been encountered',
				'PAGE_MESSAGE' => '',
				'BOX_MESSAGE' => $this->_message,
				'BACK_BUTTON_DESTINATION' => $backButtonDestination,
				'TR_BACK' => 'Back'
			)
		);

		$tpl->parse('LAYOUT_CONTENT', 'page');

		$this->_tpl = $tpl;
	}
}
