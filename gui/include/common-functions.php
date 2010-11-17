<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

/**
 * Checks for lock file
 *
 * @return boolean TRUE if file is unlocked, FALSE otherwise
 */
function check_for_lock_file() {

	/**
	 * @var iMSCP_Config_Handler_File $cfg
	 */
	$cfg = iMSCP_Registry::get('Config');

	$fh = fopen($cfg->MR_LOCK_FILE, 'r');

	if (!$fh) {
		return false;
	}

	while (!flock($fh, LOCK_EX|LOCK_NB)) {
		usleep(rand(200, 600)*1000);
		clearstatcache();

		// Send header to keep connection
		header("Cache-Control: no-store, no-cache, must-revalidate");
	}

	return true;
}

/**
 * Reads line from the socket resource
 *
 * @param resource &$socket
 * @return string A line read from the socket resource
 */
function read_line(&$socket) {

	$ch = '';
	$line = '';

	do {
		$ch = socket_read($socket, 1);
		$line = $line . $ch;
	} while ($ch != "\r" && $ch != "\n");

	return $line;
}


/**
 * Send request to the daemon
 *
 * @return string Daemon answer
 * @todo Remove error operator
 */
function send_request() {

	/**
	 * @var iMSCP_Config_Handler_File $cfg
	 */
	$cfg = iMSCP_Registry::get('Config');

	$code = 999;

	@$socket = socket_create (AF_INET, SOCK_STREAM, 0);
	if ($socket < 0) {
		$errno = "socket_create() failed.\n";
		return $errno;
	}

	@$result = socket_connect ($socket, '127.0.0.1', 9876);
	if ($result == false) {
		$errno = "socket_connect() failed.\n";
		return $errno;
	}

	// read one line with welcome string
	$out = read_line($socket);

	list($code) = explode(' ', $out);
	if ($code == 999) {
		return $out;
	}

	// send hello query
	$query = "helo  {$cfg->Version}\r\n";
	socket_write($socket, $query, strlen ($query));

	// read one line with helo answer
	$out = read_line($socket);

	list($code) = explode(' ', $out);
	if ($code == 999) {
		return $out;
	}

	// send reg check query
	$query = "execute query\r\n";
	socket_write ($socket, $query, strlen ($query));
	// read one line key replay
	$execute_reply = read_line($socket);

	list($code) = explode(' ', $execute_reply);
	if ($code == 999) {
		return $out;
	}

	// send quit query
	$quit_query = "bye\r\n";
	socket_write ($socket, $quit_query, strlen($quit_query));
	// read quit answer
	$quit_reply = read_line($socket);

	list($code) = explode(' ', $quit_reply);
	if ($code == 999) {
		return $out;
	}

	list($answer) = explode(' ', $execute_reply);

	socket_close ($socket);

	return $answer;
}

/**
 * Updates dommain expiration date
 *
 * @param  $user_id Customer id
 * @param  $domain_new_expire New expiration date
 * @return void
 */
function update_expire_date ($user_id, $domain_new_expire ) {

	$db = iMSCP_Registry::get('Db');

	$query = "
		UPDATE
			`domain`
		SET
			`domain_expires` = ?
		WHERE
			`domain_id` = ?
		;
	";

	exec_query(
		$db, $query,
		array($domain_new_expire, $user_id)
	);
}

/**
 * Updates customer properties
 *
 * @param  $user_id Customer id
 * @param  $props New properties values
 * @return void
 */
function update_user_props($user_id, $props) {

	/**
	 * @var iMSCP_Config_Handler_File $cfg
	 */
	$cfg = iMSCP_Registry::get('Config');

	/**
	 * @var iMSCP_Database $db
	 */
	$db = iMSCP_Registry::get('Db');

	list(
		,$sub_max,,$als_max,,$mail_max,,$ftp_max,,$sql_db_max,,$sql_user_max,
		$traff_max,$disk_max,$domain_php,$domain_cgi,,$domain_dns,$domain_software_allowed
	) = explode (';', $props);

	// have to check if PHP and/or CGI and/or IP change
	$domain_last_modified = time();

	$query = "
		SELECT
			`domain_name`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		AND
			`domain_php` = ?
		AND
			`domain_cgi` = ?
		AND
			`domain_dns` = ?
		AND
			`domain_software_allowed` = ?
		;
	";

	$rs = exec_query(
		$db, $query, array($user_id, $domain_php, $domain_cgi, $domain_dns, $domain_software_allowed)
	);

	if ($rs->recordCount() == 0) {
		// mama mia, we have to rebuild the system entry for this domain
		// and also all domain alias and subdomains

		$update_status = $cfg->ITEM_CHANGE_STATUS;

		// ... and go update

		// update the domain
		$query = "
			UPDATE
				`domain`
			SET
				`domain_last_modified` = ?,
				`domain_mailacc_limit` = ?,
				`domain_ftpacc_limit` = ?,
				`domain_traffic_limit` = ?,
				`domain_sqld_limit` = ?,
				`domain_sqlu_limit` = ?,
				`domain_status` = ?,
				`domain_alias_limit` = ?,
				`domain_subd_limit` = ?,
				`domain_disk_limit` = ?,
				`domain_php` = ?,
				`domain_cgi` = ?,
				`domain_dns` = ?,
				`domain_software_allowed` = ?
			WHERE
				`domain_id` = ?
			;
		";

		$rs = exec_query(
			$db,
			$query,
			array(
				$domain_last_modified, $mail_max, $ftp_max, $traff_max,
				$sql_db_max, $sql_user_max, $update_status, $als_max, $sub_max,
				$disk_max, $domain_php, $domain_cgi, $domain_dns, $domain_software_allowed, $user_id
			)
		);

		// let's update all alias domains for this domain

		$query = "
			UPDATE
				`domain_aliasses`
			SET
				`alias_status` = ?
			WHERE
				`domain_id` = ?
			;
		";

		$rs = exec_query($db, $query, array($update_status, $user_id));

		// let's update all subdomains for this domain
		$query = "
			UPDATE
				`subdomain`
			SET
				`subdomain_status` = ?
			WHERE
				`domain_id` = ?
			;
		";

		$rs = exec_query($db, $query, array($update_status, $user_id));

		// let's update all alias subdomains for this domain
		$query = "
			UPDATE
				`subdomain_alias`
			SET
				`subdomain_alias_status` = ?
			WHERE
				`alias_id` IN (
					SELECT
						`alias_id`
					FROM
						`domain_aliasses`
					WHERE
						`domain_id` = ?
				)
			;
		";

		$rs = exec_query($db, $query, array($update_status, $user_id));

		// Send request to the i-MSCP daemon
		send_request();

	} else {

		// we do not have IP and/or PHP and/or CGI changes
		// we have to update only the domain props and not
		// to rebuild system entries

		$query = "
			UPDATE
				`domain`
			SET
				`domain_subd_limit` = ?,
				`domain_alias_limit` = ?,
				`domain_mailacc_limit` = ?,
				`domain_ftpacc_limit` = ?,
				`domain_sqld_limit` = ?,
				`domain_sqlu_limit` = ?,
				`domain_traffic_limit` = ?,
				`domain_disk_limit` = ?
			WHERE
				domain_id = ?
			;
		";

		$rs = exec_query(
			$db,
			$query,
			array(
				$sub_max, $als_max, $mail_max, $ftp_max, $sql_db_max,
				$sql_user_max, $traff_max, $disk_max, $user_id
			)
		);
	}
}

/**
 * Should be documented
 *
 * @param  $arr
 * @param bool $asPath
 * @return string
 */
function array_decode_idna($arr, $asPath = false) {

	if ($asPath && !is_array($arr)) {
		return implode('/', array_decode_idna(explode('/', $arr)));
	}

	foreach ($arr as $k => $v) {
		$arr[$k] = decode_idna($v);
	}

	return $arr;
}

/**
 * Should be documented
 *
 * @param $arr
 * @param bool $asPath
 * @return string
 */
function array_encode_idna($arr, $asPath = false) {

	if ($asPath && !is_array($arr)) {
		return implode('/', array_encode_idna(explode('/', $arr)));
	}

	foreach ($arr as $k => $v) {
		$arr[$k] = encode_idna($v);
	}
	return $arr;
}

/**
 * Should be documented
 *
 * @param  $input
 * @return string
 */
function decode_idna($input) {

	if (function_exists('idn_to_unicode')) {
		return idn_to_utf8($input, IDNA_USE_STD3_RULES);
	}

	$IDN = new idna_convert();
	$output = $IDN->decode($input);

	return ($output == false) ? $input : $output;
}

/**
 * Should be documented
 *
 * @param  $input
 * @return string
 */
function encode_idna($input) {

	if (function_exists('idn_to_ascii')) {
		return idn_to_ascii($input);
	}

	$IDN = new idna_convert();
	$output = $IDN->encode($input);

	return $output;
}

/**
 * Should be documented
 *
 * @param $integer Number to be checked
 * @return boolean TRUE if number, FALSE otherwise
 */
function is_number($number) {

	return (bool) preg_match('/^[0-9]+$/D', $number);
}

/**
 * Should be documented
 *
 * @param  $string string to be checked
 * @return bool TRUE if basic string, FALSE otherwise
 */
function is_basicString($string) {
	if (preg_match('/^[\w\-]+$/D', $string)) {
		return true;
	}
	return false;
}

/**
 * Should be documented
 *
 * @return void
 */
function unset_messages() {

	$glToUnset = array();
	$glToUnset[] = 'user_page_message';
	$glToUnset[] = 'user_updated';
	$glToUnset[] = 'user_updated';
	$glToUnset[] = 'dmn_tpl';
	$glToUnset[] = 'chtpl';
	$glToUnset[] = 'step_one';
	$glToUnset[] = 'step_two_data';
	$glToUnset[] = 'ch_hpprops';
	$glToUnset[] = 'user_add3_added';
	$glToUnset[] = 'user_has_domain';
	$glToUnset[] = 'local_data';
	$glToUnset[] = 'reseller_added';
	$glToUnset[] = 'user_added';
	$glToUnset[] = 'aladd';
	$glToUnset[] = 'edit_ID';
	$glToUnset[] = 'hp_added';
	$glToUnset[] = 'aldel';
	$glToUnset[] = 'hpid';
	$glToUnset[] = 'user_deleted';
	$glToUnset[] = 'hdomain';
	$glToUnset[] = 'aledit';
	$glToUnset[] = 'acreated_by';
	$glToUnset[] = 'dhavesub';
	$glToUnset[] = 'ddel';
	$glToUnset[] = 'dhavealias';
	$glToUnset[] = 'dhavealias';
	$glToUnset[] = 'dadel';
	$glToUnset[] = 'local_data';

	foreach ($glToUnset as $toUnset) {
		if (array_key_exists($toUnset, $GLOBALS)) {
			unset($GLOBALS[$toUnset]);
		}
	}

	$sessToUnset = array();
	$sessToUnset[] = 'reseller_added';
	$sessToUnset[] = 'dmn_name';
	$sessToUnset[] = 'dmn_tpl';
	$sessToUnset[] = 'chtpl';
	$sessToUnset[] = 'step_one';
	$sessToUnset[] = 'step_two_data';
	$sessToUnset[] = 'ch_hpprops';
	$sessToUnset[] = 'user_add3_added';
	$sessToUnset[] = 'user_has_domain';
	$sessToUnset[] = 'user_added';
	$sessToUnset[] = 'aladd';
	$sessToUnset[] = 'edit_ID';
	$sessToUnset[] = 'hp_added';
	$sessToUnset[] = 'aldel';
	$sessToUnset[] = 'hpid';
	$sessToUnset[] = 'user_deleted';
	$sessToUnset[] = 'hdomain';
	$sessToUnset[] = 'aledit';
	$sessToUnset[] = 'acreated_by';
	$sessToUnset[] = 'dhavesub';
	$sessToUnset[] = 'ddel';
	$sessToUnset[] = 'dhavealias';
	$sessToUnset[] = 'dadel';
	$sessToUnset[] = 'local_data';

	foreach ($sessToUnset as $toUnset) {
		if (array_key_exists($toUnset, $_SESSION)) {
			unset($_SESSION[$toUnset]);
		}
	}
}

/**
 * Checks for XMLHttpRequest
 *
 * Returns true if the request‘s "X-Requested-With" header
 * contains "XMLHttpRequest".
 *
 * Note: jQuery and Prototype Javascript libraries sends this
 * header with every Ajax request.
 *
 * @author Laurent Declercq (nuxwin) <laurent.declercq@i-mscp.net>
 * @Since r2587
 * @return boolean TRUE if the request‘s "X-Requested-With" header
 *  contains "XMLHttpRequest", FALSE otherwise
 * @todo Move to future Request class
 */
function is_xhr() {

	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
		stristr($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') !== false) {
		return true;
	}

	return false;
}

/**
 * Check if a data is serialized
 *
 * @since 1.0.7
 * @author Laurent Declercq (nuxwin) <laurent.declercq@i-mscp.net>
 * @param mixed $data Data to be checked
 * @return boolean TRUE if serialized data, FALSE otherwise
 */
function is_serialized($data) {

	if (!is_string($data)) {
		return false;
	}

	$data = trim($data);

	if ('N;' == $data) {
		return true;
	}

	if(preg_match( "/^[aOs]:[0-9]+:.*[;}]\$/s", $data) ||
		preg_match( "/^[bid]:[0-9.E-]+;\$/", $data)) {

		return true;
	}

	return false;
}

/**
 * Decrypte database password
 *
 * @throws iMSCP_Exception
 * @param  $db_pass Encrypted database password
 * @return string Decrypted database password
 * @todo Remove error operator
 */
function decrypt_db_password($db_pass) {

	if ($db_pass == '') {
		return '';
	}

	if (extension_loaded('mcrypt')) {

		$text = @base64_decode($db_pass . "\n");
		$td = @mcrypt_module_open('blowfish', '', 'cbc', '');
		$key = iMSCP_Registry::get('MCRYPT_KEY');
		$iv = iMSCP_Registry::get('MCRYPT_IV');

		// Initialize encryption
		@mcrypt_generic_init($td, $key, $iv);
		// Decrypt encrypted string
		$decrypted = @mdecrypt_generic ($td, $text);
		@mcrypt_module_close($td);

		// Show string
		return trim($decrypted);
	} else {
		throw new iMSCP_Exception(
			"Error: PHP extension 'mcrypt' not loaded!"
		);
	}
}

/**
 * Encrypte database password
 *
 * @throws iMSCP_Exception
 * @param $db_pass Database password
 * @return string Encrypted database password
 * @todo Remove error operator
 */
function encrypt_db_password($db_pass) {

	if (extension_loaded('mcrypt')) {

		$td = @mcrypt_module_open(MCRYPT_BLOWFISH, '', 'cbc', '');
		$key = iMSCP_Registry::get('MCRYPT_KEY');
		$iv = iMSCP_Registry::get('MCRYPT_IV');

		// compatibility with used perl pads
		$block_size = @mcrypt_enc_get_block_size($td);
		$strlen = strlen($db_pass);

		$pads = $block_size-$strlen % $block_size;

		$db_pass .= str_repeat(' ', $pads);

		// Initialize encryption
		@mcrypt_generic_init($td, $key, $iv);
		// Encrypt string
		$encrypted = @mcrypt_generic ($td, $db_pass);
		@mcrypt_generic_deinit($td);
		@mcrypt_module_close($td);

		$text = @base64_encode("$encrypted");

		// Show encrypted string
		return trim($text);
	} else {
		throw new iMSCP_Exception(
			tr("ERROR: PHP extension 'mcrypt' not loaded!")
		);
	}
}

/**
 * Convenience method to execute a query
 *
 * <b>Note:</b> You may pass additional parameters. They will be treated as
 * though you called PDOStatement::setFetchMode() on the resultant statement
 * object that is wrapped by the iMSCP_Database_ResultSet object.
 *
 * @see iMSCP_Database::execute()
 * @throws iMSCP_Exception_Database
 * @param  iMSCP_Database $db iMSCP_Database instance
 * @param string $query SQL statement to be executed
 * @param array|int|string $parameters OPTIONAL parameters that represents
 * data to bind to the placeholders for prepared statement, or an integer
 * that represents the Fetch mode for Sql statement. The fetch mode must be
 * one of the PDO::FETCH_* constants
 * @param int|string|object $parameters OPTIONAL parameter for SQL statement
 * only. Can be a colum number, an object, a class name (depending of the
 * Fetch mode used)
 * @param array $parameters OPTIONAL parameter for Sql statements only. Can
 * be an array that contains constructor arguments. (See PDO::FETCH_CLASS)
 * @return iMSCP_Database_ResultSet Returns an iMSCP_Database_ResultSet object
 **/
function execute_query($db, $query, $parameters = null) {

	if(!is_null($parameters)) {
		$parameters = func_get_args();
		array_shift($parameters);
		$stmt = call_user_func_array(array($db, 'execute'), $parameters);
	} else {
		$stmt = $db->execute($query);
	}

	if ($stmt == false)
		throw new iMSCP_Exception_Database($db->getLastErrorMessage());

	return $stmt;
}

/**
 * Convenience method to prepare and execute a query
 *
 * <b>Note:</b> On failure, and if the $failDie parameter is set to TRUE, this
 * function sends a mail to the administrator with some relevant information
 * such as the debug information if the
 * {@link iMSCP_Exception_Writer_Mail writer} is active.
 *
 * @throws iMSCP_Exception_Database
 * @param iMSCP_Database $db iMSCP_Database Instance
 * @param string $query SQL statement
 * @param string|int|array $bind Data to bind to the placeholders
 * @param boolean $failDie If TRUE, throws an iMSCP_Exception_Database exception
 * on failure
 * @return iMSCP_Database_ResultSet Return a iMSCP_Database_ResultSet object
 * that represents a result set or FALSE on failure if $failDie is set to FALSE
 */
function exec_query($db, $query, $bind = null, $failDie = true) {

	if(!($stmt = $db->prepare($query)) || !($stmt = $db->execute($stmt, $bind))) {
		if($failDie) {
			throw new iMSCP_Exception_Database(
				$db->getLastErrorMessage() . " - Query: $query"
			);
		}
	}

	return $stmt;
}

/**
 * Function quoteIdentifier
 *
 * @todo document this function
 */
function quoteIdentifier($identifier) {

	$db = iMSCP_Registry::get('Db');

	$identifier = str_replace(
		$db->nameQuote, '\\' . $db->nameQuote, $identifier
	);

	return $db->nameQuote . $identifier . $db->nameQuote;
}

/**
 * Debug function
 *
 * @return void
 */
function dump_gui_debug() {
	echo '<span style="color:#00f;text-decoration:underline;">Content of <strong>$_SESSION</strong>:<br /></span>';
	echo '<pre>';
	echo htmlentities(print_r($_SESSION, true));
	echo '</pre>';
	echo '<span style="color:#00f;text-decoration:underline;">Content of <strong>$_POST</strong>:<br /></span>';
	echo '<pre>';
	echo htmlentities(print_r($_POST, true));
	echo '</pre>';
	echo '<span style="color:#00f;text-decoration:underline;">Content of <strong>$_GET</strong>:<br /></span>';
	echo '<pre>';
	echo htmlentities(print_r($_GET, true));
	echo '</pre>';
	echo '<span style="color:#00f;text-decoration:underline;">Content of <strong>$_COOKIE</strong>:<br /></span>';
	echo '<pre>';
	echo htmlentities(print_r($_COOKIE, true));
	echo '</pre>';
	echo '<span style="color:#00f;text-decoration:underline;">Content of <strong>$_FILES</strong>:<br /></span>';
	echo '<pre>';
	echo htmlentities(print_r($_FILES, true));
	echo '</pre>';

	/* Activate debug code if needed
	echo '<span style="color:#00f;text-decoration:underline;">Content of <strong>$_SERVER</strong>:<br /></span>';
	echo '<pre>';
	echo htmlentities(print_r($_SERVER, true));
	echo '</pre>';
	*/
}

function get_client_software_permission (&$tpl,&$sql,$user_id) {
	$query = "
		SELECT
			`domain_software_allowed`,
			`domain_ftpacc_limit`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
	";
	$rs = exec_query($sql, $query, array($user_id));
	if ($rs->fields('domain_software_allowed') == 'yes' && $rs->fields('domain_ftpacc_limit') != "-1") {
		$tpl->assign(
				array(
					'SOFTWARE_SUPPORT' => tr('yes'),
					'TR_SOFTWARE_MENU' => tr('i-MSCP application installer'),
					'SOFTWARE_MENU' => tr('yes'),
					'TR_INSTALLATION' => tr('Installation details'),
					'TR_INSTALLATION_INFORMATION' => tr('Please set now the Username and Password for the later Login in the Application. (Required fiels!)'),
					'TR_INSTALL_USER' => tr('Login username'),
					'TR_INSTALL_PWD' => tr('Login password'),
					'TR_INSTALL_EMAIL' => tr('Emailadress'),
					'SW_MSG' => tr('enabled'),
					'SW_ALLOWED' => tr('i-MSCP application installer'),
					'TR_SOFTWARE_DESCRIPTION' => tr('Application Description')
				)
			);
		$tpl->parse('T_SOFTWARE_SUPPORT', '.t_software_support');
		$tpl->parse('T_SOFTWARE_MENU', '.t_software_menu');
	} else {
		$tpl->assign(
				array(
					'T_SOFTWARE_SUPPORT' => '',
					'T_SOFTWARE_MENU' => '',
					'SOFTWARE_ITEM' => '',
					'TR_INSTALLATION' => tr('You do not have permissions to install application yet'),
					'TR_SOFTWARE_DESCRIPTION' => tr('You do not have permissions to install application yet'),
					'SW_MSG' => tr('disabled'),
					'SW_ALLOWED' => tr('i-MSCP application installer')
				)
			);
	}
}

function get_reseller_software_permission (&$tpl,&$sql,$reseller_id) {
	$query = "
		SELECT
			`software_allowed`
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
	";
	$rs = exec_query($sql, $query, array($reseller_id));
	if ($rs->fields('software_allowed') == 'yes') {
		$tpl->assign(
				array(
					'SOFTWARE_SUPPORT' => tr('yes'),
					'SW_ALLOWED' => tr('i-MSCP application installer'),
					'SW_MSG' => tr('enabled')
				)
			);
		$tpl->parse('T_SOFTWARE_SUPPORT', '.t_software_support'); 
	} else {
		$tpl->assign(
				array(
					'SOFTWARE_SUPPORT' => tr('no'),
					'SW_ALLOWED' => tr('i-MSCP application installer'),
					'SW_MSG' => tr('disabled'),
					'T_SOFTWARE_SUPPORT' => ''
				)
			);
	}
}
