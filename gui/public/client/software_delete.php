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

// Include core library
require_once 'imscp-lib.php';

check_login('user');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
customerHasFeature('aps') or showBadRequestErrorPage();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $domainProps = get_domain_default_props($_SESSION['user_id']);
    $dmn_id = $domainProps['domain_id'];
    $query = "
		SELECT
			`software_id`, `software_res_del`
		FROM
			`web_software_inst`
		WHERE
			`software_id` = ?
		AND
			`domain_id` = ?
	";
    $rs = exec_query($query, [$_GET['id'], $dmn_id]);

    if ($rs->recordCount() != 1) {
        set_page_message(tr('Wrong software id.'), 'error');
        redirectTo('software.php');
    } else {
        if ($rs->fields['software_res_del'] === '1') {
            $delete = "
				DELETE FROM
					`web_software_inst`
				WHERE
					`software_id` = ?
				AND
					`domain_id` = ?
			";
            $res = exec_query($delete, [$_GET['id'], $dmn_id]);
            set_page_message(tr('Software deleted.'), 'success');
        } else {
            $delete = "
				UPDATE
					`web_software_inst`
				SET
					`software_status` = ?
				WHERE
					`software_id` = ?
				AND
					`domain_id` = ?
			";
            $res = exec_query($delete, ['todelete', $_GET['id'], $dmn_id]);
            send_request();
            set_page_message(tr('Software successfully scheduled for deletion.'), 'success');
        }
        redirectTo('software.php');
    }
} else {
    set_page_message(tr('Wrong software id.'), 'error');
    redirectTo('software.php');
}
