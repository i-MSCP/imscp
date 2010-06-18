<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

/**
 * File that should only contain deprecated functions that come from files
 * that live under the include directory. These functions will no longer
 * used and will removed  as soon as possible.
 */

// Moved from include/admin-functions.php - Begin

/**
 * Moved from include/admin-functions.php
 * 
 * @deprecated since 1.0.6
 */
function setConfig_Value($name, $value) {
	$sql = IspCP_Registry::get('Db');

	$query = "SELECT `name` FROM `config` WHERE `name`= ?";

	$res = exec_query($sql, $query, array($name));

	if ($res->RecordCount() == 0) {
		$query = "INSERT INTO `config` (`name`, `value`) VALUES (?, ?)";

		exec_query($sql, $query, array($name, $value));
	} else {
		$query = "UPDATE `config` SET `value` = ? WHERE `name`= ?";

		$res = exec_query($sql, $query, array($value, $name));
	}

	Config::getInstance()->set($name, $value);

	return true;
}

// Moved from include/admin-functions.php - End

// Moved from include/reseller-functions.php - Begin

/**
 * @deprecated function deprecated in revision r2228
 */
/*
function rsl_full_domain_check($data) {

	$data .= '.';
	$match = array();

	$res = preg_match_all(
							"/([^\.]*\.)/",
							$data,
							$match,
							PREG_PATTERN_ORDER
	);

	if ($res == 0) return 0;

	$last = $res - 1;

	for ($i = 0; $i < $last; $i++) {
		$token = chop($match[0][$i], ".");

		if (!check_dn_rsl_token($token)) {
			return 0;
		}
	}

	$res = preg_match("/^[A-Za-z][A-Za-z0-9]*[A-Za-z]\.$/", $match[0][$last]);

	return ($res == 0) ? 0 : 1;
} // end of full_domain_check()
*/
 
// Moved from include/reseller-functions.php - End

// Moved from include/input-check.php - Begin

/**
 * chk_username
 *
 * @param String $data username to be checked
 * @param int $max_char number of max. chars
 * @param int $min_char number of min. chars
 * @return boolean valid username or not
 * @deprecated function deprecated
 */
/*
function chk_username($username, $max_char = null, $min_char = 2) {

	if ($min_char === null || $min_char <= 2) {
		$min_char = 2;
	}
	if ($max_char !== null) {
		(int) $max_char -= 2;
	}
	$pattern = '/^[A-Za-z0-9]([A-Za-z0-9]|[_.]{1,1}|[-]{1,2}){'.(int) ($min_char-2).','.$max_char.'}[A-Za-z0-9]?$/';

	if (preg_match($pattern, $username)) {
		return true;
	}

	return false;
}
*/
 
/**
 * full_domain_check checks the domain for validity
 *
 * @param String $data domain name to be checked
 * @return boolean valid domain name or not
 * @deprecated function deprecated in revision r2228
 */
/*
function full_domain_check($data) {
	$data .= ".";
	$match = array();

	$res = preg_match_all("/([^\.]*\.)/", $data, $match, PREG_PATTERN_ORDER);

	if (!$res) {
		return false;
	}

	$last = $res - 1;

	for ($i = 0; $i < $last; $i++) {
		$token = chop($match[0][$i], ".");

		$res = chk_dmn_token($token);

		if (!$res) {
			return false;
		}
	}

	$res = preg_match("/^[A-Za-z0-9]{2,}\.$/", $match[0][$last]);

	if (!$res) {
		return false;
	}
	return true;
}
*/

/**
 * check_dmn_token checks for a valid domain name token
 *
 * @param String $data domain name token to be checked
 * @return boolean valid domain name token or not
 * @deprecated function deprecated in revision r2228
 */
/*
function chk_dmn_token($data) {

	if ((preg_match("/^-|-$/", $data)) ||
		(preg_match("/[^A-Za-z0-9\-]|\-{2,}/", $data) || $data == '')) {
		return false;
	}

	return true;
}
*/

/**
 * Function for checking domain name tokens; Internel function,
 * for usage in ispcp_* functions
 *
 * @param string $data token data without eol
 * @return boolean true for correct syntax, false otherwise
 * @deprecated function deprecated in revision r2228
 */
/*
function check_dn_rsl_token($data) {

	$pattern = (strlen($data) == 1) ? '/^[A-Za-z0-9]$/D' :
	 '/^[A-Za-z0-9][a-z0-9A-Z\-]*[A-Za-z0-9]$/D';

	return (preg_match($pattern, $data)) ? true : false;
}
*/

/**
 * Function for checking ispCP domains syntax. Here domains are
 * limited to {dname}.{ext} parts
 *
 * @param String $dname ispcp domain data
 * @param int $num number of max. chars
 * @return boolean	false	incorrect syntax
 * 					true	correct syntax
 * @deprecated function deprecated in revision r2228
 */
/*
function chk_dname($dname) {
	// Check for invalid characters first
	if (preg_match('/[^a-z0-9\.\-]+/', $dname)) {
		return false;
	}

	if (!rsl_full_domain_check($dname)) {
		return false;
	}
	$match = array();

	if (preg_match_all("/\./", $dname, $match, PREG_PATTERN_ORDER) <= 0) {
		return false;
	}
	return true;
}
*/

/**
 * Function for checking URL syntax
 *
 * @param String $url URL data
 * @return boolean	false	incorrect syntax
 * 					true	correct syntax
 */
/*function chk_forward_url($url) {
	$dom_mainpart = '[a-zA-Z0-9][a-zA-Z0-9-]*[a-zA-Z0-9]\.';
	$dom_subpart = '(?:[a-zA-Z0-9][a-zA-Z0-9.-]*\.)*';
	$dom_tldpart = '[a-zA-Z]{2,5}';
	$domain = $dom_subpart . $dom_mainpart . $dom_tldpart;

	if (!preg_match("/^(http|https|ftp)\:\/\/" . $domain . "/", $url)) {
		return false;
	}
	return true;
}*/

/**
 * chk_mountp checks if the mount point is valid
 *
 * @param String $data mountpoint data
 * @param int $max_char number of max. chars
 * @param int $min_char number of min. chars
 * @return boolean false incorrect syntax
 *	true correct syntax
 * @deprecated function deprecated in revision r2228
 */
/*
function chk_mountp($data, $max_char = 50, $min_char = 2) {
	if (!preg_match("@^/(.*)$@D", $data)) {
		return false;
	}
	$pattern = "@^/(htdocs|backpus|cgi-bin|errors|logs)$@D";
	if (preg_match($pattern, $data)) {
		return false;
	}

	$match = array();
	$count = preg_match_all("(\/[^\/]*)", $data, $match, PREG_PATTERN_ORDER);

	if (!$count) {
		return false;
	}
	for ($i = 0; $i < $count; $i++) {
		$token = substr($match[0][$i], 1);

		if (!chk_username($token, $max_char, $min_char)) {
			return false;
		}
	}

	return true;
}
*/

/**
 * Function for checking ispCP subdomain syntax.
 *
 * Here subdomains are limited to {subname}.{dname}.{ext} parts.
 * Data passed to this function must be in the upper form, not
 * only subdomain part for example.
 *
 * @param string $subdname ispcp subdomain data;
 * @return	false - incorrect syntax;
 *			true - correct syntax;
 * @deprecated function deprecated in revision r2228
 */
/*
function chk_subdname($subdname) {
	if (!full_domain_check($subdname)) {
		return false;
	}

	$match = array();

	$res = preg_match_all("/\./", $subdname, $match, PREG_PATTERN_ORDER);

	if ($res < 1) {
		return false;
	}

	$res = preg_match("/^(www|ftp|mail|ns)\./", $subdname);

	return !($res == 1);
}
*/

// Moved from include/input-check.php - End
