<?php
/* $Id: config.sample.inc.php 9673 2006-11-03 09:05:54Z nijel $ */
// vim: expandtab sw=4 ts=4 sts=4:
/**
 *  ispCP (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 */

/**
 * phpMyAdmin Configuration File
 *
 * All directives are explained in Documentation.html
 * look http://your-server.com/ispcp/tools/pma/Documentation.html#authentication_modes
 */


/* Servers configuration */
$i = 0;

/* Server 1 (cookie) [1] */
$i++;

/* Authentication type */
$cfg['Servers'][$i]['auth_type'] 		= 'cookie';
/* Server parameters */
$cfg['Servers'][$i]['host'] 			= '{HOSTNAME}';
$cfg['Servers'][$i]['port'] 			= '3306';
$cfg['Servers'][$i]['connect_type'] 	= 'tcp';
$cfg['Servers'][$i]['socket'] 			= '/var/run/mysqld/mysqld.sock';
$cfg['Servers'][$i]['compress'] 		= true;
/* Select mysqli if your server has it */
$cfg['Servers'][$i]['extension'] 		= 'mysql';
/* User for advanced features */
$cfg['Servers'][$i]['controluser'] 		= '{PMA_USER}';
$cfg['Servers'][$i]['controlpass'] 		= '{PMA_PASS}';
/* Advanced phpMyAdmin features */
//$cfg['Servers'][$i]['pmadb'] 			= 'phpmyadmin';
$cfg['Servers'][$i]['bookmarktable'] 	= 'pma_bookmark';
$cfg['Servers'][$i]['relation'] 		= 'pma_relation';
$cfg['Servers'][$i]['table_info'] 		= 'pma_table_info';
$cfg['Servers'][$i]['table_coords'] 	= 'pma_table_coords';
$cfg['Servers'][$i]['pdf_pages'] 		= 'pma_pdf_pages';
$cfg['Servers'][$i]['column_info'] 		= 'pma_column_info';
$cfg['Servers'][$i]['history'] 			= 'pma_history';
/* Name of the Server displayed */
/*$cfg['Servers'][$i]['verbose'] 			= 'mysql.myserver.com';*/ // reactivate if domain is set in SETUP

/*
 * End of servers configuration
 */

/*
 * This is needed for cookie based authentication to encrypt password in
 * cookie
 */
/* YOU MUST FILL IN THIS FOR COOKIE AUTH! */
$cfg['blowfish_secret'] 			= '{BLOWFISH}';

/* Layout preferences */
$cfg['LeftFrameLight'] 					= true;
$cfg['LeftFrameDBTree'] 				= true;
$cfg['LeftFrameDBSeparator'] 			= '_';
$cfg['LeftFrameTableSeparator'] 		= '__';
$cfg['LeftFrameTableLevel'] 			= 1;
$cfg['LeftDisplayLogo'] 				= true;
$cfg['LeftDisplayServers'] 				= false;
$cfg['LeftPointerEnable'] 				= true;
$cfg['QueryHistoryDB'] 					= true;
$cfg['QueryHistoryMax'] 				= 25;
$cfg['BrowseMIME'] 						= true;
$cfg['PDFDefaultPageSize'] 				= 'A4';
$cfg['ShowPhpInfo'] 					= false;
$cfg['ShowChgPassword'] 				= false;
$cfg['AllowArbitraryServer'] 			= false;
$cfg['LoginCookieRecall'] 				= 'something';
$cfg['LoginCookieValidity'] 			= 1800;
/* Directories for saving/loading files from server */
$cfg['UploadDir'] 						= '{TMP_DIR}';
$cfg['SaveDir'] 						= '{TMP_DIR}';

$cfg['AllowAnywhereRecoding'] 			= true;
$cfg['DefaultCharset'] 					= 'utf-8';
$cfg['ForceSSL'] 						= false;

$cfg['RecodingEngine'] 					= 'iconv';
$cfg['IconvExtraParams'] 				= '//TRANSLIT';
$cfg['GD2Available'] 					= 'yes';
$cfg['BrowseMIME'] 						= true;
/* Changes the default Theme */
$cfg['ThemeDefault'] 					= 'omega';
?>