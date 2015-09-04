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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/**
 * Class iMSCP_VirtualFileSystem
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

	/** @var string Domain name of this filesystem */
	protected $domain;

	/** @var resource FTP connection handle */
	protected $handle;

	/** @var string FTP user associated to this object */
	protected $user;

	/** @var string FTP user password associated to this object */
	protected $password;

	/**
	 * Constructor
	 *
	 * @param string $domain Domain name of the new VFS
	 */
	public function __construct($domain)
	{
		$this->domain = (string)$domain;

		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');
		defined('VFS_TMP_DIR') or define('VFS_TMP_DIR', $cfg['GUI_ROOT_DIR'] . '/data/tmp');

		$this->createFtpUser();

		$_ENV['PHP_TMPDIR'] = VFS_TMP_DIR;
		$_ENV['TMPDIR'] = VFS_TMP_DIR;
		putenv('PHP_TMPDIR=' . VFS_TMP_DIR);
		putenv('TMPDIR=' . VFS_TMP_DIR);
	}

	/**
	 * Destructor
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this->close();
		$this->removeFtpUser();
	}

	/**
	 * Open the virtual file system
	 *
	 * @return boolean TRUE on success, FALSE on failure
	 */
	public function open()
	{
		if (is_resource($this->handle)) {
			return true;
		}

		$this->handle = @ftp_connect('localhost');
		if (!is_resource($this->handle)) {
			$this->close();
			return false;
		}

		$response = @ftp_login($this->handle, $this->user, $this->password);
		if (!$response) {
			$this->close();
			return false;
		}

		return true;
	}

	/**
	 * Closes the virtual file system
	 *
	 * @return void
	 */
	public function close()
	{
		if ($this->handle) {
			ftp_close($this->handle);
			$this->handle = null;
		}
	}

	/**
	 * Get directory listing
	 *
	 * @param string $dirname VFS directory path
	 * @return array An array of directory entries, FALSE on failure
	 */
	public function ls($dirname)
	{
		if (!$this->open()) {
			return false;
		}

		if (substr($dirname, 0, 1) != '/') {
			$dirname = '/' . $dirname;
		}

		$list = ftp_rawlist($this->handle, "-a $dirname", false);
		if (!$list) {
			return false;
		}

		for ($i = 0, $len = count($list); $i < $len; $i++) {
			$parts = preg_split('/[\s]+/', $list[$i], 9);
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
	 * @param string $file VFS file path
	 * @param int $type Type of the file to match
	 * @return boolean TRUE if file exists, FALSE otherwise
	 */
	public function exists($file, $type = null)
	{
		if (false === $this->open()) {
			return false;
		}

		$directoryName = dirname($file);
		$list = $this->ls($directoryName);

		if (!$list) {
			return false;
		}

		foreach ($list as $entry) {
			if ($entry['file'] != basename($file)) {
				continue;
			}

			if ($type !== null && $entry['type'] != $type) {
				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * Retrieves a file from the virtual file system
	 *
	 * @param string $file VFS file path
	 * @param int $mode VFS transfer mode
	 * @return string|bool File content on success, FALSE on failure
	 */
	public function get($file, $mode = self::VFS_ASCII)
	{
		if (!$this->open()) {
			return false;
		}

		$tmp = tempnam(VFS_TMP_DIR, 'vfs_');

		if (false === ftp_get($this->handle, $tmp, $file, $mode)) {
			return false;
		}

		$res = file_get_contents($tmp);
		unlink($tmp);
		return $res;
	}

	/**
	 * Stores a file inside the virtual file system
	 *
	 * @param string $file VFS file path
	 * @param string $content File content
	 * @param int $mode VFS transfer mode
	 * @return boolean TRUE on success, FALSE on failure
	 */
	public function put($file, $content, $mode = self::VFS_ASCII)
	{
		if (!$this->open()) {
			return false;
		}

		$tmp = tempnam(VFS_TMP_DIR, 'vfs_');

		if (false === file_put_contents($tmp, $content)) {
			return false;
		}

		if (!ftp_put($this->handle, $file, $tmp, $mode)) {
			return false;
		}

		unlink($tmp);
		return true;
	}

	/**
	 * Create an FTP user for this object
	 *
	 * @throws iMSCP_Exception
	 * @return void
	 */
	protected function createFtpUser()
	{
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');
		$stmt = exec_query(
			'
				SELECT
					admin_sys_uid, admin_sys_gid
				FROM
					admin
				INNER JOIN
					domain ON (domain_admin_id = admin_id)
				WHERE
					domain_name = ?
			',
			$this->domain
		);

		if (!$stmt->rowCount()) {
			throw new iMSCP_Exception('Could not create FTP user: Cannot find gid/uid');
		}

		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

		if($cfg['FTPD_SERVER'] == 'vsftpd') {
			# For vsftpd, we use the domain name as userid.
			# This matches default local_root (e.g: /var/www/virtual/$USER)
			$this->user = $this->domain;
		} else {
			$this->user = uniqid('tmp_') . '@' . $this->domain;
		}

		$this->password = \iMSCP\Crypt::randomStr(16);

		exec_query(
			'INSERT INTO ftp_users (userid, passwd, uid, gid, shell, homedir) VALUES (?, ?, ?, ?, ?, ?)', array(
			$this->user, \iMSCP\Crypt::sha512($this->password), $row['admin_sys_uid'], $row['admin_sys_gid'], '/bin/sh',
			$cfg['USER_WEB_DIR'] . '/' . $this->domain
		));
	}

	/**
	 * Removes the FTP user which is associated to this object
	 *
	 * @return void
	 */
	protected function removeFtpUser()
	{
		exec_query('DELETE FROM ftp_users WHERE userid = ?', $this->user);
	}
}
