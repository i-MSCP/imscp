<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * phpMyAdmin configuration file preedided by i-MSCP
 *
 * All directives are explained in Documentation.html and on phpMyAdmin
 * wiki <http://wiki.phpmyadmin.net>.
 *
 * @version     $Id$
 * @package     phpMyAdmin
 * @copyright   2010-2012 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      i-MSCP Team
 */

/**
 * This is needed for cookie based authentication to encrypt password in cookie
 */
$cfg['blowfish_secret']                         = '{BLOWFISH}';

/**
 * Servers configuration
 */
$i                                              = 0;

/**
 * First server
 */
$i++;

/**
 *Authentication type
 */
$cfg['Servers'][$i]['auth_type']                = 'cookie';

/**
 * Parameters set by i-MSCP
 */
$cfg['Servers'][$i]['user']                     = $_POST['pma_username'];
$cfg['Servers'][$i]['password']                 = $_POST['pma_password'];

/**
 * Server parameters
 */
$cfg['Servers'][$i]['host']                     = '{HOSTNAME}';
$cfg['Servers'][$i]['port']                     = '3306';
$cfg['Servers'][$i]['connect_type']             = 'tcp';
$cfg['Servers'][$i]['compress']                 = true;
$cfg['Servers'][$i]['extension']                = 'mysqli';
$cfg['Servers'][$i]['AllowNoPassword']          = false;

/**
 * rajk - for blobstreaming
 */
$cfg['Servers'][$i]['bs_garbage_threshold']     = 50;
$cfg['Servers'][$i]['bs_repository_threshold']  = '32M';
$cfg['Servers'][$i]['bs_temp_blob_timeout']     = 600;
$cfg['Servers'][$i]['bs_temp_log_threshold']    = '32M';

/**
 * User for advanced features
 */
$cfg['Servers'][$i]['controluser']              = '{PMA_USER}';
$cfg['Servers'][$i]['controlpass']              = '{PMA_PASS}';

/**
 * Advanced phpMyAdmin features
 */
$cfg['Servers'][$i]['pmadb']                    = 'phpmyadmin';
$cfg['Servers'][$i]['bookmarktable']            = 'pma_bookmark';
$cfg['Servers'][$i]['relation']                 = 'pma_relation';
$cfg['Servers'][$i]['table_info']               = 'pma_table_info';
$cfg['Servers'][$i]['table_coords']             = 'pma_table_coords';
$cfg['Servers'][$i]['pdf_pages']                = 'pma_pdf_pages';
$cfg['Servers'][$i]['column_info']              = 'pma_column_info';
$cfg['Servers'][$i]['history']                  = 'pma_history';
$cfg['Servers'][$i]['tracking']                 = 'pma_tracking';
$cfg['Servers'][$i]['designer_coords']          = 'pma_designer_coords';
$cfg['Servers'][$i]['hide_db']                  = '(information_schema|phpmyadmin|mysql)';

/**
 * Disabling some warnings (disabled features, suhosin)
 */
$cfg['PmaNoRelation_DisableWarning']            = true;
$cfg['SuhosinDisableWarning']                   = true;

/* Name of the Server displayed */
/*$cfg['Servers'][$i]['verbose']                = 'mysql.myserver.com';*/

$cfg['Servers'][$i]['SignonSession']            = 'i-MSCP';
/* Contrib / Swekey authentication */
// $cfg['Servers'][$i]['auth_swekey_config']    = '/etc/swekey-pma.conf';

/**
 * Directories for saving/loading files from server
 */
$cfg['UploadDir']                               = '{UPLOADS_DIR}';

/**
 * The name of the directory where dumps can be saved. (not used)
 */
//$cfg['SaveDir']                                 = '{TMP_DIR}';

/**
 * The name of the directory where temporary files can be stored.
 */
$cfg['TempDir']                                 = '{TMP_DIR}';

/**
 * Layout preferences
 */
$cfg['LeftFrameLight']                          = true;
$cfg['LeftFrameDBTree']                         = true;
$cfg['LeftFrameDBSeparator']                    = '_';
$cfg['LeftFrameTableSeparator']                 = '__';
$cfg['LeftFrameTableLevel']                     = 1;
$cfg['LeftDisplayLogo']                         = true;
$cfg['LeftDisplayServers']                      = false;
$cfg['LeftPointerEnable']                       = true;
$cfg['QueryHistoryDB']                          = true;
$cfg['QueryHistoryMax']                         = 25;
$cfg['BrowseMIME']                              = true;
$cfg['PDFDefaultPageSize']                      = 'A4';
$cfg['ShowPhpInfo']                             = false;
$cfg['ShowChgPassword']                         = false;
$cfg['AllowArbitraryServer']                    = false;
$cfg['LoginCookieRecall']                       = 'something';
$cfg['LoginCookieValidity']                     = 1440;
$cfg['AllowAnywhereRecoding']                   = true;
$cfg['DefaultCharset']                          = 'utf-8';
$cfg['ForceSSL']                                = false;

$cfg['RecodingEngine']                          = 'iconv';
$cfg['IconvExtraParams']                        = '//TRANSLIT';
$cfg['GD2Available']                            = 'yes';
$cfg['BrowseMIME']                              = true;

/* Default Theme */
$cfg['ThemeDefault']                          = 'pmahomme';

/* switch off new 'hex as binaray' mode */
$cfg['DisplayBinaryAsHex']                      = false;
