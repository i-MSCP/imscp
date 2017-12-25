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
customerHasFeature('sql') && isset($_GET['sqld_id']) or showBadRequestErrorPage();

$sqldId = intval($_GET['sqld_id']);

if (!delete_sql_database(get_user_domain_id($_SESSION['user_id']), $sqldId)) {
    write_log(
        sprintf('Could not delete SQL database with ID %s. An unexpected error occurred.', $sqldId), E_USER_NOTICE
    );
    set_page_message(tr('Could not delete SQL database. An unexpected error occurred.'), 'error');
    redirectTo('sql_manage.php');
}

set_page_message(tr('SQL database successfully deleted.'), 'success');
write_log(sprintf('%s deleted SQL database with ID %s', $_SESSION['user_logged'], $sqldId), E_USER_NOTICE);
redirectTo('sql_manage.php');
