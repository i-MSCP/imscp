<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by i-MSCP Team
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
 * @copyright   2010-2015 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * iMSCP_Exception_Writer_Browser
 *
 * This exception writer writes an exception messages to the client browser.
 */
class iMSCP_Exception_Writer_Browser extends iMSCP_Exception_Writer_Abstract
{
	/**
	 * @var iMSCP_pTemplate
	 */
	protected $templateEngine;

	/**
	 * @var string Template file path
	 */
	protected $templateFile;

	/** @var  string message */
	protected $message;

	/**
	 * Constructor
	 *
	 * @param string $templateFile Template file path
	 */
	public function __construct($templateFile = 'message.tpl')
	{
		$this->templateFile = (string)$templateFile;
	}

	/**
	 * onUncaughtException event listener
	 *
	 * @param iMSCP_Exception_Event $event
	 * @return void
	 */
	public function onUncaughtException(iMSCP_Exception_Event $event)
	{
		$exception = $event->getException();

		if(iMSCP_Registry::isRegistered('config')) {
			$debug = iMSCP_Registry::get('config')->DEBUG;
		} else {
			$debug = 1;
		}

		if($debug) {
			$exception = $event->getException();

			$this->message .= sprintf(
				"An exception has been thrown in file %s at line %s:\n\n", $exception->getFile(), $exception->getLine()
			);

			$this->message .= preg_replace('#([\t\n]+|<br \/>)#', ' ', $exception->getMessage());

			/** @var $exception iMSCP_Exception_Database */
			if($exception instanceof iMSCP_Exception_Database) {
				$query = $exception->getQuery();

				if($query !== '') {
					$this->message .= sprintf("<br><br><strong>Query was:</strong><br><br>%s", $exception->getQuery());
				}
			}
		} else {
			$exception = new iMSCP_Exception_Production($exception->getMessage(), $exception->getCode(), $exception);
			$this->message = $exception->getMessage();
		}

		try {
			if($this->templateFile) {
				$this->render();
			}
		} catch(Exception $event) {
		}

		# Fallback to inline template in case something goes wrong with template engine
		if(!($tpl = $this->templateEngine)) {
			echo <<<HTML
<!DOCTYPE html>
<html>
	<head>
		<title>i-MSCP - internet Multi Server Control Panel - Fatal Error</title>
		<meta charset="UTF-8">
		<meta name="robots" content="nofollow, noindex">
		<style>
			h1 {
				font-size: 1.5em;
				letter-spacing: .1em;
				text-align: center;
				padding: 0;
				margin: 0;
			}

			#message_container {
				background: transparent url('/themes/default/assets/images/black/box/message_top.jpg') no-repeat top left;
				position: absolute;
				top:170px;
				left:0;
				right:0;
				width:453px;
				margin: 0 auto 0 auto;
				padding-top: 80px;
				border: 1px solid #ededed;
				border-top: none;
				-webkit-border-radius: 4px;
				-moz-border-radius: 4px;
				border-radius: 4px;
			}

			#message_container pre {
				margin-top: 1em;
				padding: 0 .5em;
				white-space: -moz-pre-wrap;
				white-space: -pre-wrap;
				white-space: -o-pre-wrap;
				white-space: pre-wrap;
				word-wrap: break-word;
			}
		</style>
	</head>
	<body>
		<div id="message_container">
			<h1>An unexpected error occured</h1>
			<pre>{$this->message}</pre>
		</div>
	</body>
</html>
HTML;
		} else {
			$event->setParam('templateEngine', $tpl);
			layout_init($event);
			$tpl->parse('LAYOUT', 'layout');
			$tpl->prnt();
		}
	}

	/**
	 * Render exception template file
	 *
	 * @return void
	 */
	protected function render()
	{
		$tpl = new iMSCP_pTemplate();
		$tpl->define_dynamic(
			array(
				'layout' => 'shared/layouts/simple.tpl',
				'page' => $this->templateFile,
				'page_message' => 'layout',
				'backlink_block' => 'page'
			)
		);

		if(iMSCP_Registry::isRegistered('backButtonDestination')) {
			$backButtonDestination = iMSCP_Registry::get('backButtonDestination');
		} else {
			$backButtonDestination = 'javascript:history.go(-1)';
		}

		$tpl->assign(
			array(
				'TR_PAGE_TITLE' => 'i-MSCP - internet Multi Server Control Panel - Fatal Error',
				'CONTEXT_CLASS' => ' no_header',
				'BOX_MESSAGE_TITLE' => 'An unexpected error occured',
				'PAGE_MESSAGE' => '',
				'BOX_MESSAGE' => $this->message,
				'BACK_BUTTON_DESTINATION' => $backButtonDestination,
				'TR_BACK' => 'Back'
			)
		);

		$tpl->parse('LAYOUT_CONTENT', 'page');

		$this->templateEngine = $tpl;
	}
}
