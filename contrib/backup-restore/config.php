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

// path settings, please do not use a trailing slash:
define('IMSCP_GUI_PATH', '/var/www/imscp/gui');
define('IMSCP_VIRTUAL_PATH', '/var/www/virtual');
define('BACKUP_BASE_PATH', dirname(__FILE__));
define('BACKUP_TEMP_PATH', BACKUP_BASE_PATH.'/tmp');
define('ARCHIVE_PATH', BACKUP_BASE_PATH.'/archive');
