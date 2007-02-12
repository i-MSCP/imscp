<?php
/**
 * gpg_options_header.php
 * -----------
 *
 * Include this file at the head of all gpg options pages
 *
 * Copyright (c) 2002-2003 Braverock Ventures
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @package gpg
 * @author Joshua Vermette
 * @author Brian Peterson
 *
 * $Id: gpg_options_header.php,v 1.5 2003/11/04 21:38:41 brian Exp $
 *
 */
/*********************************************************************/

/**
 * Load some necessary stuff from squirrelmail.
 */
require_once(SM_PATH.'plugins/gpg/gpg_functions.php');

global $color, $GPG_VERSION;
if ( !check_php_version(4,1) ) global $_GET;


/*********************************************************************/
/**
 *
 * $Log: gpg_options_header.php,v $
 * Revision 1.5  2003/11/04 21:38:41  brian
 * change to use SM_PATH
 *
 * Revision 1.4  2003/10/17 13:12:05  brian
 * corrected phpdoc warnings after updates
 *
 * Revision 1.3  2003/10/17 12:54:36  brian
 * - added DocBlock at top of file
 * - added package and author tags
 * - removed 'T' localization function, becasue it wouldn't work
 *
 * Revision 1.2  2003/08/24 06:57:23  vermette
 * convenient localization function
 *
 * Revision 1.1  2003/07/08 17:54:46  vermette
 * include header for new-style options pages
 *
 *
 */
?>