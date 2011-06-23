<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2011 by i-MSCP | http://i-mscp.net
 * @version     SVN: $Id$
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
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
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

require '../include/imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (!isset($_GET['delete_lang'])) {
    redirectTo('multilanguage.php');
}

$language = $_GET['delete_lang'];

if ($language == $cfg->USER_INITIAL_LANG) {
    set_page_message("You can't delete default language.", 'error');
    redirectTo('multilanguage.php');
} elseif($language = 'lang_en_GB') {
    set_page_message(tr("You can't delete system language."), 'error');
    redirectTo('multilanguage.php');
} elseif(strpos($tableName, 'lang_') == false) {
    set_page_message(tr("Wrong language table name."), 'error');
}

$query = "SELECT count(`lang`) `cnt` FROM `user_gui_props` WHERE `lang` = ?";
$stmt = exec_query($query, $language);

if ($stmt->fields['cnt'] > 0) {
    set_page_message(tr('This language is used by one or more users.'), 'error');
    redirectTo('multilanguage.php');
}

$query = "DROP TABLE `$language`";
exec_query($query);

write_log(sprintf('%s removed language: %s', $_SESSION['user_logged'], $language));
set_page_message(tr('Language was successfully removed.'), 'success');
redirectTo('multilanguage.php');
