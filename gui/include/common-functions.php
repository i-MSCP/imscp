<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2011 by i-MSCP | http://i-mscp.net
 * @version     SVN: $Id$
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
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
 * Checks for lock file.
 *
 * @return bool TRUE if file is unlocked, FALSE otherwise
 */
function check_for_lock_file()
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $fh = fopen($cfg->MR_LOCK_FILE, 'r');

    if (!$fh) {
        return false;
    }

    while (!flock($fh, LOCK_EX | LOCK_NB)) {
        usleep(rand(200, 600) * 1000);
        clearstatcache();

        // Send header to keep connection
        header("Cache-Control: no-store, no-cache, must-revalidate");
    }

    return true;
}

/**
 * Reads line from the socket resource.
 *
 * @param resource &$socket
 * @return string A line read from the socket resource
 */
function read_line(&$socket)
{
    $line = '';

    do {
        $ch = socket_read($socket, 1);
        $line = $line . $ch;
    } while ($ch != "\r" && $ch != "\n");

    return $line;
}


/**
 * Send a request to the daemon.
 *
 * @return string Daemon answer
 * @todo Remove error operator
 */
function send_request()
{
    /** @var $cfg  iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    //$code = 999;

    @$socket = socket_create(AF_INET, SOCK_STREAM, 0);
    if ($socket < 0) {
        $errno = "socket_create() failed.\n";
        return $errno;
    }

    @$result = socket_connect($socket, '127.0.0.1', 9876);
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
    socket_write($socket, $query, strlen($query));

    // read one line with helo answer
    $out = read_line($socket);

    list($code) = explode(' ', $out);
    if ($code == 999) {
        return $out;
    }

    // send reg check query
    $query = "execute query\r\n";
    socket_write($socket, $query, strlen($query));
    // read one line key replay
    $execute_reply = read_line($socket);

    list($code) = explode(' ', $execute_reply);
    if ($code == 999) {
        return $out;
    }

    // send quit query
    $quit_query = "bye\r\n";
    socket_write($socket, $quit_query, strlen($quit_query));

    // read quit answer
    $quit_reply = read_line($socket);

    list($code) = explode(' ', $quit_reply);
    if ($code == 999) {
        return $out;
    }

    list($answer) = explode(' ', $execute_reply);

    socket_close($socket);

    return $answer;
}

/**
 * Updates dommain expiration date.
 *
 * @param  int $user_id User unique identifier
 * @param  int $domain_new_expire New expiration date
 * @return void
 */
function update_expire_date($user_id, $domain_new_expire)
{
    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $query = "
		UPDATE
			`domain`
		SET
			`domain_expires` = ?
		WHERE
			`domain_id` = ?
		;
	";
    exec_query($db, $query, array($domain_new_expire, $user_id));
}

/**
 * Updates client properties.
 *
 * @param  $user_id User unique identifier
 * @param  $props New properties values
 * @return void
 */
function update_user_props($user_id, $props)
{
    /** @var $cfg iMSCP_Config_Handler_File $cfg **/
    $cfg = iMSCP_Registry::get('config');

    /** @var $db abook_database */
    $db = iMSCP_Registry::get('db');

    list(,$sub_max,, $als_max,, $mail_max,, $ftp_max,, $sql_db_max,,$sql_user_max,
        $traff_max, $disk_max, $domain_php, $domain_cgi,, $domain_dns,
        $domain_software_allowed) = explode(';', $props);

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

    $rs = exec_query($db, $query, array(
                                       $user_id, $domain_php, $domain_cgi,
                                       $domain_dns, $domain_software_allowed));

    if ($rs->recordCount() == 0) {
        // We have to rebuild the system entry for this domain
        // and also all domain alias and subdomains

        $update_status = $cfg->ITEM_CHANGE_STATUS;

        // ... and go update

        // update the domain
        $query = "
			UPDATE
				`domain`
			SET
				`domain_last_modified` = ?, `domain_mailacc_limit` = ?,
				`domain_ftpacc_limit` = ?, `domain_traffic_limit` = ?,
				`domain_sqld_limit` = ?, `domain_sqlu_limit` = ?,
				`domain_status` = ?, `domain_alias_limit` = ?,
				`domain_subd_limit` = ?, `domain_disk_limit` = ?,
				`domain_php` = ?, `domain_cgi` = ?,
				`domain_dns` = ?, `domain_software_allowed` = ?
			WHERE
				`domain_id` = ?
			;
		";
        exec_query($db, $query, array(
                                     $domain_last_modified, $mail_max, $ftp_max,
                                     $traff_max, $sql_db_max, $sql_user_max,
                                     $update_status, $als_max, $sub_max,
                                     $disk_max, $domain_php, $domain_cgi,
                                     $domain_dns, $domain_software_allowed,
                                     $user_id));

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
        exec_query($db, $query, array($update_status, $user_id));

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
        exec_query($db, $query, array($update_status, $user_id));

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
        exec_query($db, $query, array($update_status, $user_id));

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
				`domain_subd_limit` = ?, `domain_alias_limit` = ?,
				`domain_mailacc_limit` = ?, `domain_ftpacc_limit` = ?,
				`domain_sqld_limit` = ?, `domain_sqlu_limit` = ?,
				`domain_traffic_limit` = ?, `domain_disk_limit` = ?
			WHERE
				domain_id = ?
			;
		";
        exec_query($db, $query, array(
                                     $sub_max, $als_max, $mail_max, $ftp_max,
                                     $sql_db_max,$sql_user_max, $traff_max, $disk_max,
                                     $user_id));
    }
}

/**
 * Should be documented
 *
 * @param  $array
 * @param bool $asPath
 * @return string
 */
function array_decode_idna($array, $asPath = false)
{
    if ($asPath && !is_array($array)) {
        return implode('/', array_decode_idna(explode('/', $array)));
    }

    foreach ($array as $k => $v) {
        $arr[$k] = decode_idna($v);
    }

    return $array;
}

/**
 * Must be documented.
 *
 * @param array $array Indexed array that containt
 * @param bool $asPath
 * @return string
 */
function array_encode_idna($array, $asPath = false)
{
    if ($asPath && !is_array($array)) {
        return implode('/', array_encode_idna(explode('/', $array)));
    }

    foreach ($array as $k => $v) {
        $array[$k] = encode_idna($v);
    }
    return $array;
}

/**
 * Convert domain name to IDNA ASCII form.
 *
 * @param  $domain Domain to convert.
 * @return string Domain name encoded in ASCII-compatible form
 */
function encode_idna($domain)
{
    if (function_exists('idn_to_ascii')) {
        return idn_to_ascii($domain);
    }

    $idn = new idna_convert();
    return $idn->encode($domain);
}

/**
 * Convert domain name from IDNA ASCII to Unicode.
 *
 * @param  string $domain Domain to convert in IDNA ASCII-compatible format.
 * @return string Domain name in Unicode.
 */
function decode_idna($domain)
{
    if (function_exists('idn_to_unicode')) {
        return idn_to_utf8($domain, IDNA_USE_STD3_RULES);
    }

    $idn = new idna_convert();
    $result = $idn->decode($domain);

    return ($result == false) ? $domain : $result;
}

/**
 * Checks if all of the characters in the provided string are numerical.
 *
 * @param string $number string to be checked
 * @return bool TRUE if all characters are numerical, FALSE otherwise
 */
function is_number($number)
{
    return (bool)preg_match('/^[0-9]+$/D', $number);
}

/**
 * Checks if all of the characters in the provided string match like a basic string.
 *
 * @param  $string string to be checked
 * @return bool TRUE if all characters match like a basic string, FALSE otherwise
 */
function is_basicString($string)
{
    return (bool)preg_match('/^[\w\-]+$/D', $string);
}

/**
 * Unset global variables
 *
 * @return void
 */
function unsetMessages()
{
    $glToUnset = array();
    $glToUnset[] = 'user_page_message';
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
 * Checks for XMLHttpRequest.
 *
 * Returns true if the request‘s "X-Requested-With" header contains "XMLHttpRequest".
 *
 * Note: jQuery and Prototype Javascript libraries sends this header with every Ajax
 * request.
 *
 * @author Laurent Declercq (nuxwin) <l.declercq@nuxwin.com>
 * @Since r2587
 * @return boolean  TRUE if the request‘s "X-Requested-With" header contains
 *                  "XMLHttpRequest", FALSE otherwise
 */
function is_xhr()
{
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        stristr($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') !== false
    ) {
        return true;
    }

    return false;
}

/**
 * Check if a data is serialized
 *
 * @since ispCP 1.0.7
 * @author Laurent Declercq (nuxwin) <l.declercq@nuxwin.com>
 * @param mixed $data Data to be checked
 * @return boolean TRUE if serialized data, FALSE otherwise
 */
function is_serialized($data)
{
    if (!is_string($data)) {
        return false;
    }

    $data = trim($data);

    if ('N;' == $data) {
        return true;
    }

    if (preg_match("/^[aOs]:[0-9]+:.*[;}]\$/s", $data) ||
        preg_match("/^[bid]:[0-9.E-]+;\$/", $data)
    ) {

        return true;
    }

    return false;
}

/**
 * Decrypte database password.
 *
 * @throws iMSCP_Exception
 * @param  string $password Encrypted database password
 * @return string Decrypted database password
 * @todo Remove error operator
 */
function decrypt_db_password($password)
{
    if ($password == '') {
        return '';
    }

    if (extension_loaded('mcrypt')) {

        $text = @base64_decode($password . "\n");
        $td = @mcrypt_module_open('blowfish', '', 'cbc', '');
        $key = iMSCP_Registry::get('MCRYPT_KEY');
        $iv = iMSCP_Registry::get('MCRYPT_IV');

        // Initialize encryption
        @mcrypt_generic_init($td, $key, $iv);
        // Decrypt encrypted string
        $decrypted = @mdecrypt_generic($td, $text);
        @mcrypt_module_close($td);

        // Show string
        return trim($decrypted);
    } else {
        throw new iMSCP_Exception("PHP extension 'mcrypt' not loaded!");
    }
}

/**
 * Encrypte database password.
 *
 * @throws iMSCP_Exception
 * @param $password Database password
 * @return string Encrypted database password
 * @todo Remove error operator
 */
function encrypt_db_password($password)
{
    if (extension_loaded('mcrypt')) {
        $td = @mcrypt_module_open(MCRYPT_BLOWFISH, '', 'cbc', '');
        $key = iMSCP_Registry::get('MCRYPT_KEY');
        $iv = iMSCP_Registry::get('MCRYPT_IV');

        // compatibility with used perl pads
        $block_size = @mcrypt_enc_get_block_size($td);
        $strlen = strlen($password);

        $pads = $block_size - $strlen % $block_size;

        $password .= str_repeat(' ', $pads);

        // Initialize encryption
        @mcrypt_generic_init($td, $key, $iv);
        // Encrypt string
        $encrypted = @mcrypt_generic($td, $password);
        @mcrypt_generic_deinit($td);
        @mcrypt_module_close($td);

        $text = @base64_encode($encrypted);

        return trim($text);
    } else {
        throw new iMSCP_Exception("PHP extension 'mcrypt' not loaded!");
    }
}


/**
 * Executes a SQL statement.
 *
 * Note: You may pass additional parameters. They will be treated as though you
 * called PDOStatement::setFetchMode() on the resultant statement object that is
 * wrapped by the iMSCP_Database_ResultSet object.
 *
 * @see iMSCP_Database::execute()
 * @throws iMSCP_Exception_Database
 * @param  iMSCP_Database $db           iMSCP_Database instance
 * @param string $query                 SQL statement to be executed
 * @param array|int|string $parameters  OPTIONAL parameters - See iMSCP_Database::execute()
 * @return iMSCP_Database_ResultSet     An iMSCP_Database_ResultSet object
 */
function execute_query($db, $query, $parameters = null)
{
    if (null !== $parameters) {
        $parameters = func_get_args();
        array_shift($parameters);
        $stmt = call_user_func_array(array($db, 'execute'), $parameters);
    } else {
        $stmt = $db->execute($query);
    }

    if ($stmt == false) {
        throw new iMSCP_Exception_Database($db->getLastErrorMessage());
    }

    return $stmt;
}

/**
 * Convenience method to prepare and execute a query.
 *
 * <b>Note:</b> On failure, and if the $failDie parameter is set to TRUE, this
 * function sends a mail to the administrator with some relevant information
 * such as the debug information if the
 * {@link iMSCP_Exception_Writer_Mail writer} is active.
 *
 * @throws iMSCP_Exception_Database
 * @param iMSCP_Database $db        iMSCP_Database Instance
 * @param string $query             SQL statement
 * @param string|int|array $bind    Data to bind to the placeholders
 * @param boolean $failDie          If TRUE, throws an iMSCP_Exception_Database
 *                                  exception on failure
 * @return iMSCP_Database_ResultSet A iMSCP_Database_ResultSet object that represents
 *                                  a result set or FALSE on failure if $failDie is
 *                                  set to FALSE
 */
function exec_query($db, $query, $bind = null, $failDie = true)
{
    if (!($stmt = $db->prepare($query)) || !($stmt = $db->execute($stmt, $bind))) {
        if ($failDie) {
            throw new iMSCP_Exception_Database(
                $db->getLastErrorMessage() . " - Query: $query"
            );
        }
    }

    return $stmt;
}

/**
 * Quote SQL identifier.
 *
 * Note: An Identifier is essentially a name of a database, table, or table column.
 *
 * @param  $identifier Identifier to quote
 * @return string quoted identifier
 */
function quoteIdentifier($identifier)
{
    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $quoteIdentifierSymbol = $db->getQuoteIdentifierSymbol();

    $identifier = str_replace($quoteIdentifierSymbol,
                              '\\' .
                              $quoteIdentifierSymbol, $identifier);

    return $quoteIdentifierSymbol . $identifier . $quoteIdentifierSymbol;
}

/**
 * Debug function
 *
 * @return void
 */
function dump_gui_debug()
{
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

/**
 * Returns client software permissions.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  iMSCP_Database $sql Database instance
 * @param  $user_id User unique identifier
 * @return void
 */
function get_client_software_permission($tpl, $sql, $user_id)
{
    $query = "
		SELECT
			`domain_software_allowed`,
			`domain_ftpacc_limit`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
		;
	";
    $rs = exec_query($sql, $query, array($user_id));

    if ($rs->fields('domain_software_allowed') == 'yes' && $rs->fields('domain_ftpacc_limit') != "-1") {
        $tpl->assign(array(
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
                          'TR_SOFTWARE_DESCRIPTION' => tr('Application Description')));

        $tpl->parse('T_SOFTWARE_SUPPORT', '.t_software_support');
        $tpl->parse('T_SOFTWARE_MENU', '.t_software_menu');
    } else {
        $tpl->assign(array(
                          'T_SOFTWARE_SUPPORT' => '',
                          'T_SOFTWARE_MENU' => '',
                          'SOFTWARE_ITEM' => '',
                          'TR_INSTALLATION' => tr('You do not have permissions to install application yet'),
                          'TR_SOFTWARE_DESCRIPTION' => tr('You do not have permissions to install application yet'),
                          'SW_MSG' => tr('disabled'),
                          'SW_ALLOWED' => tr('i-MSCP application installer')));
    }
}

/**
 * Returns reseller software permissions.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  iMSCP_Database $sql Database instance
 * @param  $reseller_id Reseller unique identifier
 * @return void
 */
function get_reseller_software_permission($tpl, $sql, $reseller_id)
{
    $query = "
		SELECT
			`software_allowed`
		FROM
			`reseller_props`
		WHERE
			`reseller_id` = ?
		;
	";
    $rs = exec_query($sql, $query, array($reseller_id));

    if ($rs->fields('software_allowed') == 'yes') {
        $tpl->assign(array(
                          'SOFTWARE_SUPPORT' => tr('yes'),
                          'SW_ALLOWED' => tr('i-MSCP application installer'),
                          'SW_MSG' => tr('enabled')));
        $tpl->parse('T_SOFTWARE_SUPPORT', '.t_software_support');
    } else {
        $tpl->assign(array(
                          'SOFTWARE_SUPPORT' => tr('no'),
                          'SW_ALLOWED' => tr('i-MSCP application installer'),
                          'SW_MSG' => tr('disabled'),
                          'T_SOFTWARE_SUPPORT' => ''));
    }
}

/**
 * Get all config data from i-MSCP application installer
 *
 * @since 1.0.0
 * @author Sascha Bay (TheCry) <sascha.bay@i-mscp.net>
 * @return array
 */
function get_application_installer_conf()
{
    /** @var $sql iMSCP_Database */
    $sql = iMSCP_Registry::get('db');

    $query = "SELECT * FROM `web_software_options`;";
    $rs = exec_query($sql, $query);

    return array(
        $rs->fields['use_webdepot'], $rs->fields['webdepot_xml_url'],
        $rs->fields['webdepot_last_update']);
}

/**
 * Check wheter the package is still installed this system
 *
 * @since 1.0.0
 * @author Sascha Bay (TheCry) <sascha.bay@i-mscp.net>
 * @param $package_installtype
 * @param $package_name
 * @param $package_version
 * @param $package_language
 * @param $user_id
 * @return result array
 */
function check_package_is_installed($package_installtype, $package_name,
    $package_version, $package_language, $user_id)
{
    /**
     * @var $sql iMSCP_Database
     */
    $db = iMSCP_Registry::get('db');

    $query = "
        SELECT
            `admin_type`,
            `admin_name`
        FROM
            `admin`
        WHERE
            `admin_id` = '" . $user_id . "'
        ;
    ";
    $rs_admin_type = exec_query($db, $query);
    if ($rs_admin_type->fields['admin_type'] == "admin") {
        $query = "
            SELECT
                `software_id`
            FROM
                `web_software`
            WHERE
                `software_installtype`  = '" . $package_installtype . "'
            AND
                `software_name`         = '" . $package_name . "'
            AND
                `software_version`      = '" . $package_version . "'
            AND
                `software_language`     = '" . $package_language . "'
            AND
                `software_depot`        = 'no'
            ;
        ";
    } else {
        $query = "
            SELECT
                `software_id`
            FROM
                `web_software`
            WHERE
                `software_installtype`  = '" . $package_installtype . "'
            AND
                `software_name`         = '" . $package_name . "'
            AND
                `software_version`      = '" . $package_version . "'
            AND
                `software_language`     = '" . $package_language . "'
            AND
                `reseller_id`           = '" . $user_id . "'
            AND
                `software_depot`        = 'no'
            ;
        ";
    }
    $rs = exec_query($db, $query);
    $sw_count_res = $rs->recordCount();

    $query = "
        SELECT
            `software_id`
        FROM
            `web_software`
        WHERE
            `software_installtype`  = '" . $package_installtype . "'
        AND
            `software_name`         = '" . $package_name . "'
        AND
            `software_version`      = '" . $package_version . "'
        AND
            `software_language`     = '" . $package_language . "'
        AND
            `software_master_id`    = '0'
        AND
            `software_depot`        = 'yes'
        ;
    ";
    $rs = exec_query($db, $query);
    $sw_count_swdepot = $rs->recordCount();

    if ($sw_count_res > 0 || $sw_count_swdepot > 0) {
        if ($sw_count_res > 0) {
            return array(true, 'reseller');
        } else {
            return array(true, 'sw_depot');
        }
    } else {
        return array(false, 'not_installed');
    }
}

/**
 * Get all software packages from database since last update from the websoftware
 * depot.
 *
 * @since 1.0.0
 * @author Sascha Bay (TheCry) <sascha.bay@i-mscp.net>
 * @param iMSCP_pTemplate $tpl Template engine
 * @param $user_id User unique identifier
 * @return int
 */
function get_webdepot_software_list($tpl, $user_id)
{
    /** @var $sql iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $query = "
		SELECT
			*
		FROM
			`web_software_depot`
		ORDER BY
		    `package_install_type` ASC,
		    `package_title` ASC
		;
	";
    $rs = exec_query($db, $query);

    if ($rs->recordCount() > 0) {
        while (!$rs->EOF) {
            $tpl->assign(array(
                              'TR_PACKAGE_NAME' => $rs->fields['package_title'],
                              'TR_PACKAGE_TOOLTIP' => $rs->fields['package_description'],
                              'TR_PACKAGE_INSTALL_TYPE' => $rs->fields['package_install_type'],
                              'TR_PACKAGE_VERSION' => $rs->fields['package_version'],
                              'TR_PACKAGE_LANGUAGE' => $rs->fields['package_language'],
                              'TR_PACKAGE_TYPE' => $rs->fields['package_type'],
                              'TR_PACKAGE_VENDOR_HP' => ($rs->fields['package_vendor_hp'] == '')
                                  ? tr('N/A')
                                  : '<a href="' . $rs->fields['package_vendor_hp'] . '" target="_blank">' . tr('Vendor hompage') . '</a>'));

            list($is_installed,$installed_on) = check_package_is_installed(
                $rs->fields['package_install_type'], $rs->fields['package_title'],
                $rs->fields['package_version'], $rs->fields['package_language'],
                $user_id
            );

            if ($is_installed) {
                $tpl->assign(array(
                                  'PACKAGE_HTTP_URL' => '',
                                  'TR_PACKAGE_INSTALL' => ($installed_on == "sw_depot")
                                      ? tr('Installed in software depot')
                                      : tr('Installed in reseller depot'),
                                  'TR_MESSAGE_INSTALL' => ''));
                $tpl->parse('PACKAGE_INFO_LINK', 'package_info_link');
                $tpl->assign('PACKAGE_INSTALL_LINK', '');
            } else {
                $tpl->assign(array(
                                  'PACKAGE_HTTP_URL' => $rs->fields['package_download_link'],
                                  'TR_PACKAGE_INSTALL' => tr('Start installation'),
                                  'TR_MESSAGE_INSTALL' => tr('Are you sure to install this package from the webdepot?', true)));
                $tpl->parse('PACKAGE_INSTALL_LINK', 'package_install_link');
                $tpl->assign('PACKAGE_INFO_LINK', '');
            }

            $tpl->parse('LIST_WEBDEPOTSOFTWARE', '.list_webdepotsoftware');
            $rs->moveNext();
        }
        $tpl->assign('NO_WEBDEPOTSOFTWARE_LIST', '');
    } else {
        $tpl->assign('NO_WEBDEPOTSOFTWARE_AVAILABLE',
                     tr('No software in webdepot found!'));

        $tpl->parse('NO_WEBDEPOTSOFTWARE_LIST',
                    '.no_webdepotsoftware_list');

        $tpl->assign('LIST_WEBDEPOTSOFTWARE', '');
    }

    return $rs->recordCount();
}

/**
 * Update database from the websoftware depot xml file list
 *
 * @since 1.0.0
 * @author Sascha Bay (TheCry) <sascha.bay@i-mscp.net>
 * @param iMSCP_pTemplate $tpl Template engine
 * @param string $XML_URL
 * @param string $webdepot_last_update
 */
function update_webdepot_software_list($tpl, $XML_URL, $webdepot_last_update)
{
    /** @var $sql iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $opts = array('http' => array('user_agent' => 'PHP libxml agent'));
    $context = stream_context_create($opts);
    libxml_set_streams_context($context);

    $webdepot_xml_file = new DOMDocument('1.0', 'iso-8859-1');
    $webdepot_xml_file->load($XML_URL);
    $XML_FILE = simplexml_import_dom($webdepot_xml_file);
    if (utf8_decode($XML_FILE->LAST_UPDATE->DATE) != $webdepot_last_update) {
        $truncatequery = "TRUNCATE TABLE `web_software_depot`;";
        exec_query($sql, $truncatequery);
        foreach ($XML_FILE->PACKAGE as $output) {
            if (!empty($output->INSTALL_TYPE) && !empty($output->INSTALL_TYPE) &&
                !empty($output->INSTALL_TYPE) && !empty($output->INSTALL_TYPE) &&
                !empty($output->INSTALL_TYPE) && !empty($output->INSTALL_TYPE) &&
                !empty($output->INSTALL_TYPE) && !empty($output->INSTALL_TYPE) &&
                !empty($output->INSTALL_TYPE)
            ) {
                $query = "
                    INSERT INTO
                        `web_software_depot`
                            (
                                `package_install_type`, `package_title`,
                                `package_version`, `package_language`, `package_type`,
                                `package_description`, `package_vendor_hp`,
                                `package_download_link`, `package_signature_link`
                            ) VALUES (
                                ?, ?, ?, ?, ?, ?, ?, ?, ?
                            )
                    ;
                ";
                exec_query($db, $query,
                           array(
                                utf8_decode(clean_input($output->INSTALL_TYPE)),
                                utf8_decode(clean_input($output->TITLE)),
                                utf8_decode(clean_input($output->VERSION)),
                                utf8_decode(clean_input($output->LANGUAGE)),
                                utf8_decode(clean_input($output->TYPE)),
                                utf8_decode(clean_input($output->DESCRIPTION)),
                                encode_idna(utf8_decode(strtolower(clean_input($output->VENDOR_HP)))),
                                encode_idna(utf8_decode(strtolower(clean_input($output->DOWNLOAD_LINK)))),
                                encode_idna(utf8_decode(strtolower(clean_input($output->SIGNATURE_LINK))))));
            }
        }
        $updatequery = "
            UPDATE
                `web_software_options`
            SET
                `webdepot_last_update` = '" . $XML_FILE->LAST_UPDATE->DATE . "'
            ;
        ";
        exec_query($db, $updatequery);
        set_page_message(tr("Websoftware depot list was updated"), 'info');
    } else {
        set_page_message(tr("No update for the websoftware depot list available"), 'warning');
    }
}
