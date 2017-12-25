<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Events as Events;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(Events::onClientScriptStart);
customerHasFeature('sql') && isset($_GET['sqlu_id']) or showBadRequestErrorPage();

$sqluId = intval($_GET['sqlu_id']);

if (!sql_delete_user(get_user_domain_id($_SESSION['user_id']), $sqluId)) {
    write_log(sprintf('Could not delete SQL user with ID %d. An unexpected error occurred.', $sqluId), E_USER_ERROR);
    set_page_message(tr('Could not delete SQL user. An unexpected error occurred.'), 'error');
    redirectTo('sql_manage.php');
}

set_page_message(tr('SQL user successfully deleted.'), 'success');
write_log(sprintf('%s deleted SQL user with ID %d', $_SESSION['user_logged'], $sqluId), E_USER_NOTICE);
redirectTo('sql_manage.php');
