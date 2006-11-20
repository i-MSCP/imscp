<?php
/* $Id: config.sample.inc.php 9673 2006-11-03 09:05:54Z nijel $ */
// vim: expandtab sw=4 ts=4 sts=4:
/* Modified for VHCS Omega 2006-11-20 */

/**
 * phpMyAdmin Configuration File
 *
 * All directives are explained in Documentation.html
 * look http://your-server.com/vhcs2/tools/pma/Documentation.html#authentication_modes
 */


/* Servers configuration */
$i = 0;

/* Server 1 (cookie) [1] */
$i++;

/* Authentication type */
$cfg['Servers'][$i]['auth_type'] 		= 'cookie';
/* Server parameters */
$cfg['Servers'][$i]['host'] 			= 'localhost';
$cfg['Servers'][$i]['port'] 			= '3306';
$cfg['Servers'][$i]['connect_type'] 	= 'tcp';
$cfg['Servers'][$i]['socket'] 			= '/var/run/mysqld/mysqld.sock';
$cfg['Servers'][$i]['compress'] 		= true;
/* Select mysqli if your server has it */
$cfg['Servers'][$i]['extension'] 		= 'mysql';
/* User for advanced features */
$cfg['Servers'][$i]['controluser'] 		= 'pma';
$cfg['Servers'][$i]['controlpass'] 		= 'pmapw';
/* Advanced phpMyAdmin features */
$cfg['Servers'][$i]['pmadb'] 			= 'phpmyadmin';
$cfg['Servers'][$i]['bookmarktable'] 	= 'pma_bookmark';
$cfg['Servers'][$i]['relation'] 		= 'pma_relation';
$cfg['Servers'][$i]['table_info'] 		= 'pma_table_info';
$cfg['Servers'][$i]['table_coords'] 	= 'pma_table_coords';
$cfg['Servers'][$i]['pdf_pages'] 		= 'pma_pdf_pages';
$cfg['Servers'][$i]['column_info'] 		= 'pma_column_info';
$cfg['Servers'][$i]['history'] 			= 'pma_history';
/* Name of the Server displayed */
$cfg['Servers'][$i]['verbose'] 			= 'mysql.myserver.com';

/*
 * End of servers configuration
 */

/*
 * This is needed for cookie based authentication to encrypt password in
 * cookie
 */
/* YOU MUST FILL IN THIS FOR COOKIE AUTH! */
$cfg['blowfish_secret'] 				= 'VhCsOm3g4kl631po0em3x33g1b.nehir3';

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
$cfg['UploadDir'] 						= '/tmp';
$cfg['SaveDir'] 						= '/tmp';

$cfg['AllowAnywhereRecoding'] 			= true;
$cfg['DefaultCharset'] 					= 'utf-8';
$cfg['ForceSSL'] 						= false;

$cfg['RecodingEngine'] 					= 'iconv';
$cfg['IconvExtraParams'] 				= '//TRANSLIT';
$cfg['GD2Available'] 					= 'yes';
$cfg['BrowseMIME'] 						= true;
/* Changes the default Theme */
$cfg['ThemeDefault'] 					= 'default';
?>
?>
