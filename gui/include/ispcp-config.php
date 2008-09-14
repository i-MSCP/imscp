<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2008 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

require_once(INCLUDEPATH . '/class.config.php');

if (@file_exists('/usr/local/etc/ispcp/ispcp.conf')) {
	$cfgfile = '/usr/local/etc/ispcp/ispcp.conf';
}
else {
	$cfgfile = '/etc/ispcp/ispcp.conf';
}

// Load config variables from file
try {
	Config::load($cfgfile);
} catch (Exception $e) {
	die('<div style="text-align: center; color: red; font-weight: strong;">' . $e->getMessage() . '<br />Please contact your system administrator</div>');
}


function decrypt_db_password ($db_pass) {
	global $ispcp_db_pass_key, $ispcp_db_pass_iv;

	if ($db_pass == '')
		return '';

	if (extension_loaded('mcrypt') || @dl('mcrypt.' . PHP_SHLIB_SUFFIX)) {
		$text = @base64_decode($db_pass . "\n");
		// Open the cipher
		$td = @mcrypt_module_open ('blowfish', '', 'cbc', '');
		// Create key
		$key = $ispcp_db_pass_key;
		// Create the IV and determine the keysize length
		$iv = $ispcp_db_pass_iv;

		// Intialize encryption
		@mcrypt_generic_init ($td, $key, $iv);
		// Decrypt encrypted string
		$decrypted = @mdecrypt_generic ($td, $text);
		@mcrypt_module_close ($td);

		// Show string
		return trim($decrypted);
	} else {
		system_message("ERROR: The php-extension 'mcrypt' not loaded!");
		die();
	}
}

function encrypt_db_password($db_pass){
	global $ispcp_db_pass_key, $ispcp_db_pass_iv;
	
	if (extension_loaded('mcrypt') || @dl('mcrypt.' . PHP_SHLIB_SUFFIX)) {
		$td = @mcrypt_module_open (MCRYPT_BLOWFISH, '', 'cbc', '');
		// Create key
		$key = $ispcp_db_pass_key;
		// Create the IV and determine the keysize length
		$iv = $ispcp_db_pass_iv;
		
		//compatibility with used perl pads
		$block_size=@mcrypt_enc_get_block_size($td);
		$strlen=strlen($db_pass);

		$pads=$block_size-$strlen % $block_size;

		for ($i=0; $i<$pads;$i++){
			$db_pass.=" ";
		}
		// Intialize encryption
		@mcrypt_generic_init ($td, $key, $iv);
		//Encrypt string
		$encrypted = @mcrypt_generic ($td, $db_pass);
		@mcrypt_generic_deinit($td);
		@mcrypt_module_close($td);

		$text = @base64_encode("$encrypted");
		$text=trim($text);
		return $text;
	} else {
		//system_message("ERROR: The php-extension 'mcrypt' not loaded!");
		die("ERROR: The php-extension 'mcrypt' not loaded!");
	}
}

?>