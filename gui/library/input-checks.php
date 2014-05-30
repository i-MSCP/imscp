<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 */

/**
 * Checks an input value.
 *
 * check_input checks HTML fields of type <input> for content that could be
 * related to cross site scripting. The function will call die() if any of the
 * defined commands is entered in a protected <input> field. An additional
 * information is displayed to the user.
 *
 * @throws iMSCP_Exception
 * @param string $value
 */
function check_input($value = '')
{
	// possible commands for XSS are stored in $CHECK_VARS
	$checkVariables = array(
		"/wget /i", "/chmod /i", "/chown /i", "/lnyx /i", "/curl /i", "/fopen /i",
		"/mkdir /i", "/passwd /i", "/http:/i", "/ftp:/i", "/content-type:/i",
		"/content-transfer-encoding:/i", "/mime-version:/i", "/subject:/i", "/to:/i",
		"/cc:/i", "/bcc:/i", "/\r/", "/\n/", "/%0a/", "/%0d/"
	);

	if (!empty($value)) {
		$value = strtolower($value);

		foreach ($checkVariables as $variable) {
			if (preg_match($variable, $value) > 0) {
				$message = 'Possible hacking attempt. Script terminated.';
				write_log($message, E_USER_ERROR);
				throw new iMSCP_Exception(tr($message));
			}
		}
	}
}

/**
 * clean_html replaces up defined inputs.
 *
 * @param string $text text string to be cleaned
 * @return string cleared text string
 */
function clean_html($text)
{
	$search = array(
		'@<script[^>]*?>.*?</script[\s]*>@si', // remove JavaScript
		'@<[\/\!]*?[^<>]*?>@si', // remove HTML tags
		'@([\r\n])[\s]+@', // remove spaces
		'@&(quot|#34|#034);@i', // change HTML entities
		'@&(apos|#39|#039);@i', // change HTML entities
		'@&(amp|#38);@i',
		'@&(lt|#60);@i',
		'@&(gt|#62);@i',
		'@&(nbsp|#160);@i',
		'@&(iexcl|#161);@i',
		'@&(cent|#162);@i',
		'@&(pound|#163);@i',
		'@&(copy|#169);@i' /*,
		'@&#(\d+);@e'*/
	); // handle as php

	$replace = array(
		'', '', '\1', '"', "'", '&', '<', '>', ' ', chr(161), chr(162), chr(163),
		chr(169),);

	$text = preg_replace($search, $replace, $text);
	// and second one...
	$text = strip_tags($text);

	return $text;
}

/**
 * clean_input
 *
 * @param String $input input data (eg. post-var) to be cleaned
 * @param boolean $htmlencode should return value be html encoded (& -> &amp;)
 * @return String space trimmed, {|} trimmed, stripslashed, eventually htmlencoded input string
 */
function clean_input($input, $htmlencode = false)
{
	// Trim leading and trealing white spaces
	$input = trim($input, "\x20");
	$input = trim($input, '{..}');

	if (get_magic_quotes_gpc()) {
		$input = stripslashes($input);
	}

	if ($htmlencode) {
		return htmlentities($input, ENT_QUOTES, 'UTF-8', false);
	} else {
		return $input;
	}
}

/**
 * Convert any text to HTML.
 *
 * @param string $text Text to be converted
 * @return string HTML entitied text
 */
function tohtml($text)
{
	return htmlentities($text, ENT_QUOTES, 'UTF-8', false);
}

/**
 * Convert any text to JavaScript text
 * @param  $text
 * @return string JavaScript text
 */
function tojs($text)
{
	$result = strtr(
		$text,
		array(
			'\\' => '\\\\',
			"'" => "\\'",
			'"' => '\\"',
			"\r" => '\\r',
			"\n" => '\\n',
			'</' => '<\/'
		));

	return $result;
}

/**
 * Checks if the syntax of the given password is valid.
 *
 * @param string $password username to be checked
 * @param string $unallowedChars RegExp for unallowed characters
 * @param bool $noErrorMsg Whether or not error message should be discarded
 * @return bool TRUE if the password is valid, FALSE otherwise
 */
function checkPasswordSyntax($password, $unallowedChars = '', $noErrorMsg = false)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	$ret = true;
	$passwordLength = strlen($password);

	if($cfg->PASSWD_CHARS < 6) {
		$cfg->PASSWD_CHARS = 6;
	} elseif($cfg->PASSWD_CHARS > 30) {
		$cfg->PASSWD_CHARS = 30;
	}

	if ($passwordLength < $cfg->PASSWD_CHARS) {
		if(!$noErrorMsg) {
			set_page_message(tr('Password is shorter than %s characters.', $cfg->PASSWD_CHARS), 'error');
		}

		$ret = false;
	} elseif($passwordLength > 30) {
		if(!$noErrorMsg) {
			set_page_message(tr('Password cannot be longer than 30 characters.'), 'error');
		}

		$ret = false;
	}

	if (!empty($unallowedChars) && preg_match($unallowedChars, $password)) {
		if(!$noErrorMsg) {
			set_page_message(tr('Password contains unallowed characters.'), 'error');
		}

		$ret = false;
	}

	if ($cfg->PASSWD_STRONG && ! (preg_match('/[0-9]/', $password) && preg_match('/[a-zA-Z]/', $password))) {
		if(!$noErrorMsg) {
			set_page_message(
				tr(
					'Password must be at least %s characters long and contain letters and numbers to be valid.',
					$cfg->PASSWD_CHARS
				),
				'error'
			);
		}

		$ret = false;
	}

	return $ret;

}

/**
 * Validates a username
 *
 * This function validates syntax of usernames.
 * The characters allowed are all alphanumeric in
 * upper or lower case, the hyphen , the low dash
 * and  the dot, the three latter  being banned at
 * the beginning and end of string.
 *
 * Successive instances of a dot or underscore are prohibited
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param string $username the username to be checked
 * @param int $min_char number of min. chars
 * @param int $max_char number min. chars
 * @return boolean True if the username is valid, FALSE otherwise
 */
function validates_username($username, $min_char = 2, $max_char = 30)
{
	$pattern = '@^[[:alnum:]](:?(?<![-_])(:?-*|[_.])?(?![-_])[[:alnum:]]*)*?(?<![-_.])$@';

	return (bool)(preg_match($pattern, $username) && strlen($username) >= $min_char &&
		strlen($username) <= $max_char);
}

/**
 * Check syntax of the given email
 *
 * @param string $email Email addresse to check
 * @param int $num Max
 * @return bool
 */
function chk_email($email, $num = 50)
{
	if (strlen($email) > $num) {
		return false;
	}
	// RegEx begin
	$nonascii = "\x80-\xff"; // non ASCII chars are not allowed

	$nqtext = "[^\\\\$nonascii\015\012\"]"; // all not quotable chars
	$qchar = "\\\\[^$nonascii]"; // matched quoted chars

	$normuser = '[a-zA-Z0-9][a-zA-Z0-9_.-]*';
	$quotedstring = "\"(?:$nqtext|$qchar)+\"";
	$user_part = "(?:$normuser|$quotedstring)";

	$dom_mainpart = '[a-zA-Z0-9][a-zA-Z0-9.-]*[a-zA-Z0-9]\\.';
	$dom_subpart = '(?:[a-zA-Z0-9][a-zA-Z0-9.-]*\\.)*';
	$dom_tldpart = '[a-zA-Z]{2,5}';
	$domain_part = "$dom_subpart$dom_mainpart$dom_tldpart";

	$regex = "$user_part\@$domain_part";

	// RegEx end
	return (bool)preg_match("/^$regex$/", $email);
}

/**
 * Check local part of an email address
 *
 * @param string $email
 * @param int $num
 * @return bool
 */
function imscp_check_local_part($email, $num = 50)
{
	if (strlen($email) > $num) {
		return false;
	}
	// RegEx begin
	$nonascii = "\x80-\xff"; // non ASCII chars are not allowed

	$nqtext = "[^\\\\$nonascii\015\012\"]";
	$qchar = "\\\\[^$nonascii]";

	$normuser = "[a-zA-Z0-9][a-zA-Z0-9_.-]*";
	$quotedstring = "\"(?:$nqtext|$qchar)+\"";
	$user_part = "(?:$normuser|$quotedstring)";

	$regex = $user_part;

	return (bool)preg_match("/^$regex$/", $email);
}

/**
 * Validate a domain name
 *
 * @param string $domainName Domain name
 * @return bool TRUE if the given domain name is valid, FALSE otherwise
 */
function isValidDomainName($domainName)
{
	global $dmnNameValidationErrMsg;

	if(strpos($domainName, '.') === 0 || substr($domainName, -1) == '.') {
		$dmnNameValidationErrMsg = tr('Domain name cannot start nor end with dot.');
		return false;
	} elseif (($asciiDomainName = encode_idna($domainName)) !== false) {
		$asciiDomainName = strtolower($asciiDomainName);

		if (strlen($asciiDomainName) > 255) {
			$dmnNameValidationErrMsg = tr('Domain name (ASCII form) cannot be greater than 255 characters.');
			return false;
		}

		if (preg_match('/([^a-z0-9\-\.])/', $asciiDomainName, $m)) {
			$dmnNameValidationErrMsg = tr('Domain name contains an invalid character: %s', $m[1]);
			return false;
		} elseif(strpos($asciiDomainName, '..') !== false) {
			$dmnNameValidationErrMsg = tr('Usage of dot in domain name labels is prohibited.');
			return false;
		}

		$labels = explode('.', $asciiDomainName);

		if (sizeof($labels) > 1) {
			foreach ($labels as $label) {
				if (strlen($label) > 63) {
					$dmnNameValidationErrMsg = tr('Domain name labels cannot be greater than 63 characters.');
					return false;
				} elseif (preg_match('/([^a-z0-9\-])/', $label, $m)) {
					$dmnNameValidationErrMsg = tr(
						"Domain name label '%s' contain an invalid character: %s", $label, $m[1]
					);
					return false;
				} elseif (preg_match('/^[\-]|[\-]$/', $label)) {
					$dmnNameValidationErrMsg = tr('Domain name labels cannot start nor end with hyphen.');
					return false;
				}
			}
		} else {
			$dmnNameValidationErrMsg = tr('Invalid domain name.');
			return false;
		}
	} else {
		$dmnNameValidationErrMsg = tr('Invalid domain name.');
		return false;
	}

	return true;
}

/**
 * Function for checking i-MSCP limits syntax.
 *
 * @param string $data Limit field data (by default valids are numbers greater equal 0)
 * @param mixed $extra single extra permitted value or array of permitted values
 * @return bool false incorrect syntax (ranges) true correct syntax (ranges)
 */
function imscp_limit_check($data, $extra = -1)
{
	if ($extra !== null && !is_bool($extra)) {
		if (is_array($extra)) {
			$nextra = '';
			$max = count($extra);

			foreach ($extra as $n => $element) {
				$nextra = $element . ($n < $max) ? '|' : '';
			}

			$extra = $nextra;
		} else {
			$extra .= '|';
		}
	} else {
		$extra = '';
	}

	return (bool)preg_match("/^(${extra}0|[1-9][0-9]*)$/D", $data);
}

/**
 * All in one function to check who owns what.
 *
 * @throws iMSCP_Exception
 * @param mixed $id FTP/mail/domain/alias/subdomain/etc id to check
 * @param string $type What kind of id $id is
 * @param boolean $forcefinal Ignore the resolver's is_final value (force as yes)
 * @return int The id of the admin who owns the id $id of $type type
 */
function who_owns_this($id, $type = 'dmn', $forcefinal = false)
{
	$who = null;
	// Fix $type according to type or by alias
	switch ($type) {
		case 'dmn_id':
			$type = 'domain_id';
			break;
		case 'sub_id':
			$type = 'subdomain_id';
			break;
		case 'als_id':
			$type = 'alias_id';
			break;
		case 'user':
			$type = 'client';
			break;
		case 'admin_sys_uid':
			$type = 'uid';
			break;
		case 'ticket':
			$type = 'ticket_id';
			break;
		case 'admin_sys_gid':
			$type = 'gid';
			break;
		case 'sqlu_id':
		case 'sqluser_id':
			$type = 'sql_user_id';
			break;
		case 'sqld_id':
		case 'sqldatabase_id':
			$type = 'sql_database_id';
			break;
		case 'ftpuser':
		case 'ftpuserid':
		case 'ftp_userid':
			$type = 'ftp_user';
			break;
		case 'sqluser':
		case 'sqlu':
		case 'sqlu_name':
			// Can't guess by type
			$type = 'sql_user';
			break;
		case 'sqldatabase':
		case 'sqld':
		case 'sqld_name':
			// Can't guess by type
			$type = 'sql_database';
			break;
		case 'dmn':
		case 'normal':
		case 'domain':
			if (!is_numeric($id)) {
				$type = 'domain';
			} else {
				$type = 'domain_id';
			}
			break;
		case 'als':
		case 'alias':
		case 'domain_alias':
			if (!is_numeric($id)) {
				$type = 'alias';
			} else {
				$type = 'alias_id';
			}
			break;
		case 'sub':
		case 'subdom':
		case 'subdomain':
			if (!is_numeric($id)) {
				$type = 'subdomain';
			} else {
				$type = 'subdomain_id';
			}
			break;
		case 'alssub':
			if (!is_numeric($id)) {
				$type = 'subdomain_alias';
			} else {
				$type = 'subdomain_alias_id';
			}
			break;
	}

	/**
	 * $resolvers is a multi-dimensional array.
	 * Its elements keys are the value that will be matched by $type.
	 * Each element is an array, containing at least two elements:
	 * 'query' and 'is_final'
	 * The former is the SQL query that should only SELECT one item; or false in case a query isn't used.
	 * The latter is a boolean which specifies whether the result of that 'resolver' is an admin id or not
	 *
	 * Other elements might be:
	 * 'next', 'separator', 'pos'
	 *
	 * 'next' is the $type value for the next call to who_owns_this (only used when 'is_final' is false)
	 * 'separator' is the separator to be used when exploding the $id (only used when 'query' is false)
	 * 'post' is the position in the array/result of exploding $id (only used when 'query' is false)
	 *
	 * NOTE: 'query' MUST be formated like: 'SELECT something FROM...' in order to correctly detect the field being selected
	 */
	$resolvers = array();

	$resolvers['domain_id'] = array();
	$resolvers['domain_id']['query'] = 'SELECT `domain_admin_id` FROM `domain` WHERE `domain_id` = ? LIMIT 1;';
	$resolvers['domain_id']['is_final'] = true;

	$resolvers['alias_id'] = array();
	$resolvers['alias_id']['query'] = 'SELECT `domain_id` FROM `domain_aliasses` WHERE `alias_id` = ? LIMIT 1;';
	$resolvers['alias_id']['is_final'] = false;
	$resolvers['alias_id']['next'] = 'dmn';

	$resolvers['alias'] = array();
	$resolvers['alias']['query'] = 'SELECT `domain_id` FROM `domain_aliasses` WHERE `alias_name` = ? LIMIT 1;';
	$resolvers['alias']['is_final'] = false;
	$resolvers['alias']['next'] = 'dmn';

	$resolvers['subdomain_id'] = array();
	$resolvers['subdomain_id']['query'] = 'SELECT `domain_id` FROM `subdomain` WHERE `subdomain_id` = ? LIMIT 1;';
	$resolvers['subdomain_id']['is_final'] = false;
	$resolvers['subdomain_id']['next'] = 'dmn';

	$resolvers['subdomain'] = array();
	$resolvers['subdomain']['query'] = false;
	$resolvers['subdomain']['separator'] = '.';
	$resolvers['subdomain']['pos'] = 1;
	$resolvers['subdomain']['is_final'] = false;
	$resolvers['subdomain']['next'] = 'dmn';

	$resolvers['subdomain_alias_id'] = array();
	$resolvers['subdomain_alias_id']['query'] = 'SELECT `alias_id` FROM `subdomain_alias` WHERE `subdomain_alias_id` = ? LIMIT 1;';
	$resolvers['subdomain_alias_id']['is_final'] = false;
	$resolvers['subdomain_alias_id']['next'] = 'alias';

	$resolvers['subdomain_alias'] = array();
	$resolvers['subdomain_alias']['query'] = false;
	$resolvers['subdomain_alias']['separator'] = '.';
	$resolvers['subdomain_alias']['pos'] = 1;
	$resolvers['subdomain_alias']['is_final'] = false;
	$resolvers['subdomain_alias']['next'] = 'alias';

	$resolvers['client'] = array();
	$resolvers['client']['query'] = 'SELECT `created_by` FROM `admin` WHERE `admin_id` = ? LIMIT 1;';
	$resolvers['client']['is_final'] = true;

	$resolvers['reseller'] = $resolvers['admin'] = $resolvers['client'];

	$resolvers['domain'] = array();
	$resolvers['domain']['query'] = 'SELECT `domain_admin_id` FROM `domain` WHERE `domain` = ? LIMIT 1;';
	$resolvers['domain']['is_final'] = true;

	$resolvers['ticket_id'] = array();
	$resolvers['ticket_id']['query'] = 'SELECT `ticket_from` FROM `ticket` WHERE `ticket_id` = ? LIMIT 1;';
	$resolvers['ticket_id']['is_final'] = true;

	$resolvers['uid'] = array();
	$resolvers['uid']['query'] = 'SELECT `admin_id` FROM `admin` WHERE `admin_sys_uid` = ? LIMIT 1;';
	$resolvers['uid']['is_final'] = true;

	$resolvers['gid'] = array();
	$resolvers['gid']['query'] = 'SELECT `admin_id` FROM `admin` WHERE `admin_sys_gid` = ? LIMIT 1;';
	$resolvers['gid']['is_final'] = true;

	$resolvers['ftp_user'] = array();
	$resolvers['ftp_user']['query'] = 'SELECT `admin_id` FROM `ftp_users` WHERE `userid` = ? LIMIT 1;';
	$resolvers['ftp_user']['is_final'] = true;

	$resolvers['sql_user_id'] = array();
	$resolvers['sql_user_id']['query'] = 'SELECT `sqld_id` FROM `sql_user` WHERE `sqlu_id` = ? LIMIT 1;';
	$resolvers['sql_user_id']['is_final'] = false;
	$resolvers['sql_user_id']['next'] = 'sqld_id';

	$resolvers['sql_database_id'] = array();
	$resolvers['sql_database_id']['query'] = 'SELECT `domain_id` FROM `sql_database` WHERE `sqld_id` = ? LIMIT 1;';
	$resolvers['sql_database_id']['is_final'] = false;
	$resolvers['sql_database_id']['next'] = 'dmn';

	$resolvers['sql_user'] = array();
	$resolvers['sql_user']['query'] = 'SELECT sqld_id FROM sql_user WHERE sqlu_name = ? LIMIT 1;';
	$resolvers['sql_user']['is_final'] = false;
	$resolvers['sql_user']['next'] = 'sqld_id';

	$resolvers['sql_database'] = array();
	$resolvers['sql_database']['query'] = 'SELECT `domain_id` FROM `sql_database` WHERE `sqld_name` = ? LIMIT 1;';
	$resolvers['sql_database']['is_final'] = false;
	$resolvers['sql_database']['next'] = 'dmn';

	$resolvers['mail_id'] = array();
	$resolvers['mail_id']['query'] = 'SELECT `domain_id` FROM `mail_users` WHERE `mail_id` = ? LIMIT 1;';
	$resolvers['mail_id']['is_final'] = false;
	$resolvers['mail_id']['next'] = 'dmn';

	$resolvers['mail'] = array();
	$resolvers['mail']['query'] = false;
	$resolvers['mail']['separator'] = '@';
	$resolvers['mail']['post'] = 1;
	$resolvers['mail']['is_final'] = false;
	$resolvers['mail']['next'] = 'dmn';

	$resolvers['htaccess_id'] = array();
	$resolvers['htaccess_id']['query'] = 'SELECT `dmn_id` FROM `htaccess` WHERE `id` = ? LIMIT 1;';
	$resolvers['htaccess_id']['is_final'] = false;
	$resolvers['htaccess_id']['next'] = 'dmn';

	$resolvers['htaccess_group_id'] = array();
	$resolvers['htaccess_group_id']['query'] = 'SELECT `dmn_id` FROM `htaccess_groups` WHERE `id` = ? LIMIT 1;';
	$resolvers['htaccess_group_id']['is_final'] = false;
	$resolvers['htaccess_group_id']['next'] = 'dmn';

	$resolvers['htaccess_user_id'] = array();
	$resolvers['htaccess_user_id']['query'] = 'SELECT `dmn_id` FROM `htaccess_users` WHERE `id` = ? LIMIT 1;';
	$resolvers['htaccess_user_id']['is_final'] = false;
	$resolvers['htaccess_user_id']['next'] = 'dmn';

	$resolvers['hosting_plan_id'] = array();
	$resolvers['hosting_plan_id']['query'] = 'SELECT `reseller_id` FROM `hosting_plans` WHERE `id` = ? LIMIT 1;';
	$resolvers['hosting_plan_id']['is_final'] = true;

	if (isset($resolvers[$type])) {
		$r = $resolvers[$type];

		if ($r['query']) {
			$matches = array();

			if (!preg_match('/SELECT[ \t]+`([\w]+)`[ \t]+FROM/i', $r['query'], $matches)) {
				throw new iMSCP_Exception(tr('Malformed resolver SQL query'));
			}

			$select = $matches[1];
			$stmt = exec_query($r['query'], $id);

			if ($stmt->rowCount()) {
				if ($r['is_final'] || $forcefinal) {
					$who = $stmt->fields[$select];
				} else {
					$who = who_owns_this($stmt->fields[$select], $r['next']);
				}
			}
		} else {
			$ex = explode($r['separator'], $id);

			if (!$r['is_final'] && !$forcefinal) {
				$who = who_owns_this($r['pos'], $r['next']);
			} else {
				$who = $ex[$r['pos']];
			}
		}
	}

	if ($type != 'admin' && (empty($who) || $who <= 0)) {
		$who = null;
	}

	return $who;
}

/**
 * Checks if a file match the given mimetype(s).
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @throws iMSCP_Exception When magicfile cannot be found or is not valid
 * @throws iMSCP_Exception When the PHP finfo extension is not available
 * @param  string $pathFile File to check for mimetype
 * @param  array|string $mimeTypes Accepted mimetype(s)
 * @return bool|string The file mimetype on success, FALSE otherwise
 */
function checkMimeType($pathFile, $mimeTypes)
{
	static $finfo = null;

	if (null == $finfo) {
		$const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
		$finfo = @finfo_open($const);
	}

	$mimeType = finfo_file($finfo, $pathFile);

	if (!in_array($mimeType, (array)$mimeTypes)) {
		return false;
	}

	return $mimeType;
}
