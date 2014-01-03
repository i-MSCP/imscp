<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @package     iMSCP_VirtualFileSystem
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Virtual File System class
 *
 * This class provides a FTP layer allowing to browse and edit all customer's files from i-MSCP.
 *
 * @category    i-MSCP
 * @package     iMSCP_VirtualFileSystem
 */
class iMSCP_VirtualFileSystem
{
	/**
	 * File types definition
	 */
	const VFS_TYPE_DIR = 'd';
	const VFS_TYPE_LINK = 'l';
	const VFS_TYPE_FILE = '-';

	/**
	 * Possible VFS Transfer modes
	 */
	const VFS_ASCII = FTP_ASCII;
	const VFS_BINARY = FTP_BINARY;

	/**
	 * Domain name of this filesystem
	 *
	 * @var string
	 */
	protected $_domain = '';

	/**
	 * FTP connection handle
	 *
	 * @var resource
	 */
	protected $_handle = null;

	/**
	 * FTP temporary user name
	 *
	 * @var string
	 */
	protected $_user = '';

	/**
	 * FTP password
	 *
	 * @var string
	 */
	protected $_passwd = '';

	/**
	 * Constructor - Create a new Virtual File System
	 *
	 * Creates a new Virtual File System object for the specified domain.
	 *
	 * @param string $domain Domain name of the new VFS.
	 */
	public function __construct($domain)
	{
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		$this->_domain = $domain;

		defined('VFS_TMP_DIR') or define('VFS_TMP_DIR', $cfg->GUI_ROOT_DIR . '/data/tmp');

		$_ENV['PHP_TMPDIR'] = VFS_TMP_DIR;
		$_ENV['TMPDIR'] = VFS_TMP_DIR;

		putenv("PHP_TMPDIR={$_ENV['PHP_TMPDIR']}");
		putenv("TMPDIR={$_ENV['PHP_TMPDIR']}");
	}

	/**
	 * Destructor, ensure that we logout and remove the temporary user
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this->close();
	}

	/**
	 * Open the virtual file system
	 *
	 * @return boolean TRUE on success, FALSE on failure.
	 */
	public function open()
	{
		// Check if we're already open
		if (is_resource($this->_handle)) {
			return true;
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
	public function close()
	{
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
	 * Get the directory listing of a specified dir, either in short (default) or long mode.
	 *
	 * @param string $dirname VFS directory path.
	 * @return array An array of directory entries, FALSE on failure.
	 */
	public function ls($dirname)
	{
		// Ensure that we're open
		if (!$this->open()) {
			return false;
		}

		// Path is always relative to the root vfs
		if (substr($dirname, 0, 1) != '/') {
			$dirname = '/' . $dirname;
		}

		// No security implications, the FTP server handles this for us
		$list = ftp_rawlist($this->_handle, "-a $dirname", false);
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
				'type' => substr($parts[0], 0, 1)
			);
		}

		return $list;
	}

	/**
	 * Checks for file existence
	 *
	 * @param string $file VFS file path.
	 * @param int $type Type of the file to match. Must be either
	 * {@link self::VFS_TYPE_DIR}, {@link self::VFS_TYPE_LINK} or
	 * {@link self::VFS_TYPE_FILE}.
	 * @return boolean TRUE if file exists, FALSE otherwise.
	 */
	public function exists($file, $type = null)
	{
		// Ensure that we're open
		if (false === $this->open()) {
			return false;
		}

		// Actually get the listing
		$directoryName = dirname($file);
		$list = $this->ls($directoryName);

		if (!$list) {
			return false;
		}

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
	 * @param int $mode VFS transfer mode. Must be either {@link self::VFS_ASCII} or {@link self::VFS_BINARY}.
	 * @return string|bool File content on success, FALSE on failure.
	 */
	public function get($file, $mode = self::VFS_ASCII)
	{
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
	 * @param string $content File content.
	 * @param int $mode VFS transfer mode. Must be either {@link self::VFS_ASCII} or {@link self::VFS_BINARY}.
	 * @return boolean TRUE on success, FALSE on failure.
	 */
	public function put($file, $content, $mode = self::VFS_ASCII)
	{
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

	/**
	 * Create a temporary FTP user
	 *
	 * @return boolean TRUE on success, FALSE on failure
	 */
	protected function _createTmpUser()
	{
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		// Get user uid/gid
		$query = "
			SELECT
				`admin_sys_uid`, `admin_sys_gid`
			FROM
				`admin`
			INNER JOIN
				`domain` ON (`domain_admin_id` = `admin_id`)
			WHERE
				`domain_name` = ?
        ";
		$stmt = exec_query($query, $this->_domain);

		if (!$stmt) {
			return false;
		}

		// Generate a random userid and password
		$user = uniqid('tmp_') . '@' . $this->_domain;
		$this->_passwd = uniqid('tmp_', true);
		$password = cryptPasswordWithSalt($this->_passwd);

		// Create the temporary user
		$query = "
			INSERT INTO
				`ftp_users` (
					`userid`, `passwd`, `uid`, `gid`, `shell`, `homedir`
				) VALUES (
					?, ?, ?, ?, ?, ?
				)
		";

		$stmt = exec_query($query, array(
			$user, $password, $stmt->fields['admin_sys_uid'], $stmt->fields['admin_sys_gid'], $cfg->CMD_SHELL,
			"{$cfg->FTP_HOMEDIR}/{$this->_domain}"));

		if (!$stmt) {
			return false;
		}

		$this->_user = $user;

		return true;
	}

	/**
	 * Removes the temporary FTP user
	 *
	 * @return boolean TRUE on success, FALSE on failure.
	 */
	protected function _removeTmpUser()
	{
		$query = "DELETE FROM `ftp_users` WHERE `userid` = ?";
		$stmt = exec_query($query, $this->_user);

		return $stmt ? true : false;
	}
}
