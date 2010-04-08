<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
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
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

/**
 * Virtual File System main class
 *
 * This class allows the ispCP Control Panel to browse and edit all of the user
 * files
 */
class vfs {
	/*
	 * File types definition
	 */
	const VFS_TYPE_DIR  = 'd';
	const VFS_TYPE_LINK = 'l';
	const VFS_TYPE_FILE = '-';

	/*
	 * Possible VFS Transfer modes
	 */
	const VFS_ASCII  = FTP_ASCII;
	const VFS_BINARY = FTP_BINARY;

	/**
	 * Domain name of this filesystem
	 * @var string
	 */
	private $_domain = '';
	/**
	 * FTP connection handle
	 * @var resource
	 */
	private $_handle = null;
	/**
	 * Database connection handle
	 * @var resource
	 */
	private $_db = null;
	/**
	 * FTP temporary user name
	 * @var string
	 */
	private $_user = '';
	/**
	 * FTP password
	 * @var string
	 */
	private $_passwd = '';

	/**
	 * Constructor - Create a new Virtual File System
	 *
	 * Creates a new Virtual File System object for the
	 * specified domain.
	 *
	 * Warning! $domain parameter is not sanitized, so this is
	 * left as work for the caller.
	 *
	 * @param string $domain Domain name of the new VFS.
	 * @param resource $db Adodb database resource.
	 * @return vfs
	 */
	public function __construct($domain, &$db) {
		$this->_domain = $domain;
		$this->_db = &$db;

		if (!defined("VFS_TMP_DIR")) {
			define("VFS_TMP_DIR", Config::getInstance()->get('GUI_ROOT_DIR') . '/phptmp');
		}
		$_ENV['PHP_TMPDIR'] = VFS_TMP_DIR;
		$_ENV['TMPDIR'] = VFS_TMP_DIR;
		putenv("PHP_TMPDIR=" . $_ENV['PHP_TMPDIR']);
		putenv("TMPDIR=" . $_ENV['PHP_TMPDIR']);
	}

	/**
	 * Destructor, ensure that we logout and remove the
	 * temporary user
	 */
	public function __destruct() {
		$this->close();
	}

	/**
	 * Set ispCP DB handler
	 *
	 * The system uses a "global" $sql variable to store the DB
	 * handler, but we're a "black box" ;).
	 *
	 * @param resource $db Adodb database resource.
	 */
	public function setDb(&$db) {
		$this->_db = &$db;
	}

	/**
	 * Create a temporary FTP user
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	private function _createTmpUser() {
		// Get domain data
		$query = "
			SELECT `domain_uid`, `domain_gid`
			FROM `domain`
			WHERE `domain_name` = ?;";
		$rs = exec_query($this->_db, $query, array($this->_domain));
		if (!$rs) {
			return false;
		}
		// Generate a random userid and password
		$user = uniqid('tmp_') . '@' . $this->_domain;
		$this->_passwd = uniqid('tmp_', true);
		$passwd = crypt_user_pass_with_salt($this->_passwd);
		// Create the temporary user
		$query = "
			INSERT INTO `ftp_users`
				(`userid`, `passwd`, `uid`, `gid`, `shell`, `homedir`)
			VALUES
				(?, ?, ?, ?, ?, ?);";
		$rs = exec_query($this->_db, $query, array($user, $passwd, $rs->fields['domain_uid'], $rs->fields['domain_gid'],
				Config::getInstance()->get('CMD_SHELL'), Config::getInstance()->get('FTP_HOMEDIR') . '/' . $this->_domain
				));
		if (!$rs) {
			return false;
		}
		// All ok
		$this->_user = $user;
		return true;
	}

	/**
	 * Removes the temporary FTP user
	 *
	 * @return Returns TRUE on success or FALSE on failure.
	 */
	private function _removeTmpUser() {
		$query = "
			DELETE FROM `ftp_users`
			WHERE `userid` = ?;";
		$rs = exec_query($this->_db, $query, array($this->_user));

		return $rs ? true : false;
	}

	/**
	 * Open the virtual file system
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function open() {
		// Check if we're already open
		if (is_resource($this->_handle)) {
			return true;
		}
		// Check if we have a valid ispcp database
		if (!$this->_db) {
			return false;
		}
		// Create the temporary ftp account
		$result = $this->_createTmpUser();
		if (!$result) {
			return false;
		}
		// 'localhost' for testing purposes. I have to study if a better
		// $this->_domain would work on all situations
		$this->_handle = @ftp_connect('localhost');
		if (!is_resource($this->_handle)) {
			$this->close();
			return false;
		}
		// Perform actual login
		$response = @ftp_login($this->_handle, $this->_user, $this->_passwd);
		if (!$response) {
			$this->close();
			return false;
		}
		// All went ok! :)
		return true;
	}

	/**
	 * Closes the virtual file system
	 */
	public function close() {
		// Close FTP connection
		if ($this->_handle) {
			ftp_close($this->_handle);
			$this->_handle = null;
		}
		// Remove temporary user
		if ($this->_user) {
			$this->_removeTmpUser();
		}
	}

	/**
	 * Get directory listing
	 *
	 * Get the directory listing of a specified dir,
	 * either in short (default) or long mode.
	 *
	 * @param string $dirname VFS directory path.
	 * @return array Returns an array of directory entries or FALSE on error.
	 */
	public function ls($dirname) {
		// Ensure that we're open
		if (!$this->open()) {
			return false;
		}
		// Path is always relative to the root vfs
		if (substr($dirname, 0, 1) != '/') {
			$dirname = '/' . $dirname;
		}
		// No security implications, the FTP server handles
		// this for us
		$list = ftp_rawlist($this->_handle, '-a ' . $dirname, false);
		if (!$list) {
			return false;
		}

		for ($i = 0, $len = count($list); $i < $len; $i++) {
			$parts = preg_split("/[\s]+/", $list[$i], 9);
			$list[$i] = array(
				'perms' => $parts[0],
				'number' => $parts[1],
				'owner' => $parts[2],
				'group' => $parts[3],
				'size' => $parts[4],
				'month' => $parts[5],
				'day' => $parts[6],
				'time' => $parts[7],
				'file' => $parts[8],
				'type' => substr($parts[0], 0, 1),
			);
		}

		return $list;
	}

	/**
	 * Checks for file existence
	 *
	 * @param string $file VFS file path.
	 * @param int $type Type of the file to match. Must be either {@link self::VFS_TYPE_DIR},
	 *	{@link self::VFS_TYPE_LINK} or {@link self::VFS_TYPE_FILE}.
	 * @return boolean Returns TRUE if file exists or FALSE if it doesn't exist.
	 */
	public function exists($file, $type = null) {
		// Ensure that we're open
		if (false === $this->open()) {
			return false;
		}
		// Actually get the listing
		$dirname = dirname($file);
		$list = $this->ls($dirname);
		if (!$list)
			return false;
		// We get filenames only from the listing
		$file = basename($file);
		// Try to match it
		foreach ($list as $entry) {
			// Skip non-matching files
			if ($entry['file'] != $file) {
				continue;
			}
			// Check type
			if ($type !== null && $entry['type'] != $type) {
				return false;
			}
			// Matched and same type (or no type specified)
			return true;
		}
		return false;
	}

	/**
	 * Retrieves a file from the virtual file system
	 *
	 * @param string $file VFS file path.
	 * @param int $mode VFS transfer mode. Must be either {@link self::VFS_ASCII}
	 *	or {@link self::VFS_BINARY}.
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function get($file, $mode = self::VFS_ASCII) {
		// Ensure that we're open
		if (!$this->open()) {
			return false;
		}
		// Get a temporary file name
		$tmp = tempnam(VFS_TMP_DIR, 'vfs_');
		// Get the actual file
		$res = ftp_get($this->_handle, $tmp, $file, $mode);
		if (false === $res) {
			return false;
		}
		// Retrieve file contents
		$res = file_get_contents($tmp);
		// Delete temporary file
		unlink($tmp);

		return $res;
	}

	/**
	 * Stores a file inside the virtual file system
	 *
	 * @param string $file VFS file path.
	 * @param string $content File contents.
	 * @param int $mode VFS transfer mode. Must be either {@link self::VFS_ASCII}
	 *	or {@link self::VFS_BINARY}.
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	public function put($file, $content, $mode = self::VFS_ASCII) {
		// Ensure that we're open
		if (!$this->open()) {
			return false;
		}
		// Get a temporary file name
		$tmp = tempnam(VFS_TMP_DIR, 'vfs_');
		// Save temporary file
		$res = file_put_contents($tmp, $content);
		if (false === $res) {
			return false;
		}
		// Upload it
		$res = ftp_put($this->_handle, $file, $tmp, $mode);
		if (!$res) {
			return false;
		}
		// Remove temp file
		unlink($tmp);

		return true;
	}
}
