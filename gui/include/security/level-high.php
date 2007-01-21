<?php
/************************************************************************
 * IP-Filter v0.5                                     Start: 10/20/2006 *
 * ==============                               Last change: 10/20/2006 *
 *                                                                      *
 * -------------------------------------------------------------------- *
 * File              : level-high.php                                   *
 * -------------------------------------------------------------------- *
 * Short description : Parses $_GET, $_COOKIE for bad data              *
 * -------------------------------------------------------------------- *
 *                                                                      *
 * -------------------------------------------------------------------- *
 * Copyright (c) 2006 by Roland Haeder                                  *
 * For more information visit: http://blog.mxchange.org                 *
 *                                                                      *
 * This program is free software. You can redistribute it and/or modify *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2 of the License.       *
 *                                                                      *
 * This program is distributed in the hope that it will be useful,      *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of       *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        *
 * GNU General Public License for more details.                         *
 *                                                                      *
 * You should have received a copy of the GNU General Public License    *
 * along with this program; if not, write to the Free Software          *
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               *
 * MA  02110-1301  USA                                                  *
 ************************************************************************/

// Check selected security level
if ($cfg['SECURITY_LEVEL'] != "paranoid" && $cfg['SECURITY_LEVEL'] != "high") {
	// Abort on any other levels
	return;
}

// Validate some entries in $_SERVER
$filterPassed = true;
foreach ($tagsFilter as $filter) {
	$test = "";
	if (!empty($_SERVER[$filter])) {
		$test = htmlspecialchars(strip_tags($_SERVER[$filter], ENT_QUOTES));
		if ($test != $_SERVER[$filter]) {
			$filterPassed = false;
			continue;
		}
	}
}

// Check if important arrays are found and define them if missing
if (!isset($_SERVER))
{
	global $_SERVER;
	$_SERVER = $GLOBALS['_SERVER'];
}
if (!isset($HTTP_GET_VARS))
{
	global $HTTP_GET_VARS;
	$HTTP_GET_VARS = $GLOBALS['HTTP_GET_VARS'];
}
if (!isset($HTTP_POST_VARS))
{
	global $HTTP_POST_VARS;
	$HTTP_POST_VARS = $GLOBALS['HTTP_POST_VARS'];
}
if (!isset($_COOKIE))
{
	global $_COOKIE;
	$_COOKIE = $GLOBALS['_COOKIE'];
}

// Generate arrays which holds the relevante chars to replace
global $SEC_CHARS, $URL_CHARS;
$SEC_CHARS = array(
	// The chars we are looking for...
	'from' => array("{", "}", "/", ".", "'", "$", "(", ")", "{--", "--}", "%", ";", "[", "]", ":"),
	// ... and we will replace to.
	'to'   => array(
		"{OPEN_ANCHOR2}",
		"{CLOSE_ANCHOR2}",
		"{SLASH}",
		"{DOT}",
		"{QUOT}",
		"{DOLLAR}",
		"{OPEN_ANCHOR}",
		"{CLOSE_ANCHOR}",
		"{OPEN_TEMPLATE}",
		"{CLOSE_TEMPLATE}",
		"{PER}",
		"{SEMI}",
		"{OPEN_INDEX}",
		"{CLOSE_INDEX}",
		"{DBL_DOT}",
	),
);

// Characters allowed in URLs
//
// Note: Do not replace 'to' with 'from' and vise-versa! When you do this all booked URLs will be
//       rejected because of the {SLASH}, {DOT} and all below listed items inside the URL.
$URL_CHARS = array(
	// Search for these secured characters
	'to'   => array("{SLASH}", "{DOT}", "{PER}", "{DBL_DOT}"),
	// Replace with these characters
	'from' => array("/", ".", "%", ":")
);

// Overworked security part:
foreach ($_GET as $seckey=>$secvalue)
{
	if (is_array($secvalue))
	{
		// Throw arrays away...
		unset($_GET[$seckey]);
	}
	 else
	{
		// Only variables are allowed (non-array) but we secure them all!
		foreach ($SEC_CHARS['from'] as $key=>$char)
		{
			// Pass all through
			$_GET[$seckey] = str_replace($char  , $SEC_CHARS['to'][$key], $_GET[$seckey]);
		}

		// Strip all other out
		$_GET[$seckey] = strip_tags($_GET[$seckey]);
	}
}

// And Strip some POST data
foreach ($_POST as $seckey=>$secvalue)
{
	if (!is_array($secvalue))
	{
		// Only variables are allowed (non-array) to be secured...
		foreach ($SEC_CHARS['from'] as $key=>$char)
		{
			// Pass all through
			$_POST[$seckey] = str_replace($char  , $SEC_CHARS['to'][$key], $_POST[$seckey]);
		}
			// Strip all other out
		$_POST[$seckey] = strip_tags($_POST[$seckey]);
	}
}

// ... and finally cookies
foreach ($_COOKIE as $seckey=>$secvalue)
{
	if (is_array($secvalue))
	{
		// Throw arrays away...
		unset($_COOKIE[$seckey]);
	}
	 else
	{
		// Only variables are allowed (non-array) but we secure them all!
		foreach ($SEC_CHARS['from'] as $key=>$char)
		{
			// Pass all through
			$_COOKIE[$seckey] = str_replace($char  , $SEC_CHARS['to'][$key], $_COOKIE[$seckey]);
		}
		// Strip all other out
		$_COOKIE[$seckey] = strip_tags($_COOKIE[$seckey]);
	}
}

?>
