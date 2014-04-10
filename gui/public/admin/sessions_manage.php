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
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_Core
 * @subpackage  Admin
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @link        http://i-mscp.net
 */

/***********************************************************************************************************************
 * Script functions
 */

/**
 * Kill user session.
 *
 * @return void
 */
function kill_session()
{
	if (isset($_GET['kill']) && $_GET['kill'] !== '' && isset($_GET['username'])) {
		$username = clean_input($_GET['username']);
		$sessionId = clean_input($_GET['kill']);

		// Getting current session id
		$currentSessionId = session_id();

		// Closing current session
		session_write_close();

		// Switch to session to handle
		session_id($sessionId);
		session_start();

		if(isset($_GET['logout_only'])) {
			iMSCP_Authentication::getInstance()->unsetIdentity();
			session_write_close();
			$message = tr('User successfully disconnected.');

		} else {
			iMSCP_Authentication::getInstance()->unsetIdentity();
			session_destroy();
			$message = tr('User session successfully destroyed.');
		}

		// Restore session
		session_id($currentSessionId);
		session_start();

		set_page_message($message, 'success');
		write_log($_SESSION['user_logged'] . ": has disconnected $username or destroyed its session", E_USER_NOTICE);

	} elseif(isset($_GET['own'])) {
		set_page_message(tr("You are not allowed to act on your own session."), 'warning');
	}
}

/**
 * Generates users sessoion list.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function client_generatePage($tpl)
{
	$currentUserSessionId = session_id();

	$query = "SELECT `session_id`, `user_name`, `lastaccess` FROM `login`";
	$stmt = exec_query($query);

	while (!$stmt->EOF) {
		$username = tohtml($stmt->fields['user_name']);
		$sessionId = $stmt->fields['session_id'];

		if ($username === null) {
			$tpl->assign(
				array(
					'ADMIN_USERNAME' => tr('Unknown'),
					'LOGIN_TIME' => date('G:i:s', $stmt->fields['lastaccess'])
				)
			);
		} else {
			$tpl->assign(
				array(
					'ADMIN_USERNAME' =>
					$username . (($username == $_SESSION['user_logged'] && $currentUserSessionId !== $sessionId)
						? ' (' . tr('from other browser') . ')' : ''),
					'LOGIN_TIME' => date('G:i:s', $stmt->fields['lastaccess'])
				)
			);
		}

		if ($currentUserSessionId === $sessionId) { // Deletion of our own session is not allowed
			$tpl->assign(
				array(
					'DISCONNECT_LINK' => 'sessions_manage.php?own=1',
					'KILL_LINK' => 'sessions_manage.php?own=1'
				)
			);
		} else {
			$tpl->assign(
				array(
					'DISCONNECT_LINK'
						=> "sessions_manage.php?logout_only&kill={$stmt->fields['session_id']}&username={$username}",
					'KILL_LINK' => "sessions_manage.php?kill={$stmt->fields['session_id']}&username={$username}"
				)
			);
		}

		$tpl->parse('USER_SESSION', '.user_session');
		$stmt->moveNext();
	}
}

/***********************************************************************************************************************
 * Main script
 */

// Include core library
require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => 'admin/sessions_manage.tpl',
		'page_message' => 'layout',
		'user_session' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Users / Sessions'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_USERNAME' => tr('Username'),
		'TR_USERTYPE' => tr('User type'),
		'TR_LOGIN_ON' => tr('Last access'),
		'TR_ACTIONS' => tr('Actions'),
		'TR_DISCONNECT' => tr('Disconnect'),
		'TR_KILL' => tr('Kill session')
	)
);

generateNavigation($tpl);
kill_session();
client_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
