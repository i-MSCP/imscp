<?php
/**
 * ispCP ω (OMEGA) complete domain backup/restore tool
 * Restore application
 *
 * @copyright 	2010 Thomas Wacker
 * @author 		Thomas Wacker <zuhause@thomaswacker.de>
 * @version 	SVN: $Id$
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
 */

require_once 'config.php';
if (!defined('IMSCP_GUI_PATH')) {
	exit(-1);
}

require_once IMSCP_GUI_PATH.'/include/imscp-lib.php';
require_once dirname(__FILE__).'/utils.php';
require_once dirname(__FILE__).'/Base.php';

iMSCP_Config::set('DB_TYPE', iMSCP_Config::get('DATABASE_TYPE'));
iMSCP_Config::set('DB_HOST', iMSCP_Config::get('DATABASE_HOST'));
iMSCP_Config::set('DB_USER', iMSCP_Config::get('DATABASE_USER'));
iMSCP_Config::set('DB_PASS', decrypt_db_password(iMSCP_Config::get('DATABASE_PASSWORD')));
iMSCP_Config::set('DB_NAME', iMSCP_Config::get('DATABASE_NAME'));
