<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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
 */

/**
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 * PhpIncludeInspection
 */

use iMSCP\Authentication\AuthService;
use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\TemplateEngine;

/**
 * Kill user session
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

        // Close current session
        session_write_close();

        // Switch to session to handle
        session_id($sessionId);
        session_start();

        if (isset($_GET['logout_only'])) {
            AuthService::getInstance()->unsetIdentity();
            session_write_close();
            $message = tr('User successfully disconnected.');
        } else {
            AuthService::getInstance()->unsetIdentity();
            session_destroy();
            $message = tr('User session successfully destroyed.');
        }

        session_id($currentSessionId);
        session_start();
        set_page_message($message, 'success');
        write_log(sprintf('The session of the %s user has been disconnected/destroyed by %s', $username, $_SESSION['user_logged']), E_USER_NOTICE);
    } elseif (isset($_GET['own'])) {
        set_page_message(tr("You are not allowed to act on your own session."), 'warning');
    }
}

/**
 * Generates users session list.
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function client_generatePage(TemplateEngine $tpl)
{
    $userLoggedASCII = encode_idna($_SESSION['user_logged']);
    $currentUserSessionId = session_id();
    $stmt = exec_query('SELECT session_id, user_name, lastaccess FROM login');

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        $username = tohtml($row['user_name']);
        $sessionId = $row['session_id'];

        if ($username === NULL) {
            $tpl->assign([
                'ADMIN_USERNAME' => tr('Unknown'),
                'LOGIN_TIME'     => date('G:i:s', $row['lastaccess'])
            ]);
        } else {
            $tpl->assign([
                'ADMIN_USERNAME' => $username
                    . (($username == $userLoggedASCII && $currentUserSessionId !== $sessionId)
                        ? ' (' . tr('from other browser') . ')' : ''
                    ),
                'LOGIN_TIME'     => date('G:i:s', $row['lastaccess'])
            ]);
        }

        if ($currentUserSessionId === $sessionId) { // Deletion of our own session is not allowed
            $tpl->assign([
                'DISCONNECT_LINK' => 'sessions_manage.php?own=1',
                'KILL_LINK'       => 'sessions_manage.php?own=1'
            ]);
        } else {
            $tpl->assign([
                'DISCONNECT_LINK'
                            => "sessions_manage.php?logout_only&kill={$row['session_id']}&username={$username}",
                'KILL_LINK' => "sessions_manage.php?kill={$row['session_id']}&username={$username}"
            ]);
        }

        $tpl->parse('USER_SESSION', '.user_session');
    }
}

require 'imscp-lib.php';

check_login('admin');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptStart);

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'admin/sessions_manage.tpl',
    'page_message' => 'layout',
    'user_session' => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE' => tr('Admin / Users / Sessions'),
    'TR_USERNAME'   => tr('Username'),
    'TR_USERTYPE'   => tr('User type'),
    'TR_LOGIN_ON'   => tr('Last access'),
    'TR_ACTIONS'    => tr('Actions'),
    'TR_DISCONNECT' => tr('Disconnect'),
    'TR_KILL'       => tr('Kill session')
]);

generateNavigation($tpl);
kill_session();
client_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
