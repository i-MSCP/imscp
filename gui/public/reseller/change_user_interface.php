<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by i-MSCP Team
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

require 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);
check_login('reseller');

// Switch back to admin
if (isset($_SESSION['logged_from'])
    && isset($_SESSION['logged_from_id'])
    && isset($_GET['action'])
    && $_GET['action'] == 'go_back'
) {
    change_user_interface($_SESSION['user_id'], $_SESSION['logged_from_id']);
}

if (isset($_SESSION['user_id'])
    && isset($_GET['to_id'])
) { // Switch to customer
    $toUserId = intval($_GET['to_id']);

    // Admin logged as reseller
    if (isset($_SESSION['logged_from'])
        && isset($_SESSION['logged_from_id'])
    ) {
        $fromUserId = $_SESSION['logged_from_id'];
    }

    // reseller to customer
    else {
        $fromUserId = $_SESSION['user_id'];

        if (who_owns_this($toUserId, 'client') != $fromUserId) {
            showBadRequestErrorPage();
        }
    }

    change_user_interface($fromUserId, $toUserId);
}

showBadRequestErrorPage();
