<?php
/*
 * Copyright 2007-2011 Charles du Jeu <contact (at) cdujeu.me>
 * This file is part of AjaXplorer.
 *
 * AjaXplorer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AjaXplorer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with AjaXplorer.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <http://www.ajaxplorer.info/>.
 *
 * Description : configuration file
 * BASIC REPOSITORY CONFIGURATION.
 * The standard repository will point to the data path (ajaxplorer/data by default), folder "files"
 * Use the GUI to add new repositories.
 *   + Log in as "admin" and open the "Settings" Repository
 */
defined('AJXP_EXEC') or die('Access not allowed');

$REPOSITORIES["imscp_ftp"] = array(
	"DISPLAY" => "FTP Server",
	"DRIVER" => "ftp",
	"DRIVER_OPTIONS" => array(
		"FTP_HOST" => "localhost",
		"FTP_PORT" => "21",
		"DEFAULT_RIGHTS" => "rw",
		"FIX_PERMISSIONS" => "user",
		"USE_SESSION_CREDENTIALS" => true,
		"TMP_UPLOAD" => AJXP_TMP_DIR
	)
);

// DO NOT REMOVE THIS! (Disabled for i-MSCP)
// SHARE ELEMENTS
/*
$REPOSITORIES["ajxp_shared"] = array(
	"DISPLAY" => "Shared Elements",
	"DISPLAY_ID" => "363",
	"DRIVER" => "ajxp_shared",
	"DRIVER_OPTIONS" => array(
		"DEFAULT_RIGHTS" => "rw"
	)
);
*/

// ADMIN REPOSITORY - Admin account disabled for i-MSCP
/*
$REPOSITORIES["ajxp_conf"] = array(
	"DISPLAY" => "Settings",
	"DISPLAY_ID" => "165",
	"DRIVER" => "ajxp_conf",
	"DRIVER_OPTIONS" => array()
);
*/
