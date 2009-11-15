<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $ID$
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2009 by
 * isp Control Panel. All Rights Reserved.
 */

// require_once(INCLUDEPATH . '/class.config.php');

if (@file_exists('/usr/local/etc/ispcp/ispcp.conf')) {
	$cfgfile = '/usr/local/etc/ispcp/ispcp.conf';
} else {
	$cfgfile = '/etc/ispcp/ispcp.conf';
}

// Load config variables from file
try {
	Config::load($cfgfile);
} catch (Exception $e) {
	die('<div style="text-align: center; color: red; font-weight: strong;">' . $e->getMessage() . '<br />Please contact your system administrator</div>');
}


/**
 * @todo use of @ is problematic, instead use try-catch
 */
function decrypt_db_password($db_pass) {
	global $ispcp_db_pass_key, $ispcp_db_pass_iv;

	if ($db_pass == '')
		return '';

	// @todo remove dl() for PHP6 compatibiliy
	if (extension_loaded('mcrypt') || @dl('mcrypt.' . PHP_SHLIB_SUFFIX)) {
		$text = @base64_decode($db_pass . "\n");
		// Open the cipher
		$td = @mcrypt_module_open('blowfish', '', 'cbc', '');
		// Create key
		$key = $ispcp_db_pass_key;
		// Create the IV and determine the keysize length
		$iv = $ispcp_db_pass_iv;

		// Initialize encryption
		@mcrypt_generic_init($td, $key, $iv);
		// Decrypt encrypted string
		$decrypted = @mdecrypt_generic ($td, $text);
		@mcrypt_module_close($td);

		// Show string
		return trim($decrypted);
	} else {
		system_message("ERROR: The php-extension 'mcrypt' not loaded!");
		die();
	}
}

/**
 * @todo use of @ is problematic, instead use try-catch
 */
function encrypt_db_password($db_pass) {
	global $ispcp_db_pass_key, $ispcp_db_pass_iv;

	// @todo remove dl() for PHP6 compatibiliy
	if (extension_loaded('mcrypt') || @dl('mcrypt.' . PHP_SHLIB_SUFFIX)) {
		$td = @mcrypt_module_open(MCRYPT_BLOWFISH, '', 'cbc', '');
		// Create key
		$key = $ispcp_db_pass_key;
		// Create the IV and determine the keysize length
		$iv = $ispcp_db_pass_iv;

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
		//system_message("ERROR: The php-extension 'mcrypt' not loaded!");
		die("ERROR: The php-extension 'mcrypt' not loaded!");
	}
}
