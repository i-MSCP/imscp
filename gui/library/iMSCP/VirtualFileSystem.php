<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace iMSCP;

use iMSCP_Exception_Database as DatabaseException;
use iMSCP_Registry as Registry;

/**
 * Virtual File System class
 *
 * This class provides a FTP layer allowing to browse and edit customers's files from the i-MSCP frontEnd.
 */
class VirtualFileSystem
{
    /**
     * @var string VFS file type
     */
    const
        VFS_TYPE_DIR = 'd',
        VFS_TYPE_LINK = 'l',
        VFS_TYPE_FILE = '-';

    /**
     * @var int VFS Transfer modes
     */
    const
        VFS_ASCII = FTP_ASCII,
        VFS_BINARY = FTP_BINARY;

    /**
     * @var string FTP username
     */
    protected $user;

    /**
     * @var string Plaintext FTP user password
     */
    protected $passwd;

    /**
     * @var string Virtual file system root directory (relative to domain root directory)
     */
    protected $rootDir;

    /**
     * @var resource FTP stream
     */
    protected $stream;

    /**
     * Constructor
     *
     * Creates a virtual file system object for the given $user using $rootDir as root directory.
     *
     * @param string $user FTP username. That is, the username that matches with the unix user's homedir
     * @param string $rootDir OPTIONAL Root directory of the virtual file system (relative to $domain root directory)
     */
    public function __construct($user, $rootDir = '/')
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $this->user = encode_idna((string)$user);
        $this->rootDir = (string)$rootDir;
    }

    /**
     * Destructor, ensure that we logout and remove the temporary user
     *
     * @return void
     * @throws DatabaseException
     * @throws \Zend_Exception
     * @throws \iMSCP_Exception
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Open the virtual file system
     *
     * @return boolean TRUE on success, FALSE on failure
     * @throws DatabaseException
     * @throws \Zend_Exception
     * @throws \iMSCP_Exception
     */
    public function open()
    {
        if ($this->stream) {
            return true;
        }

        if (!$this->createFtpUser()) {
            return false;
        }

        if (Registry::get('config')->SERVICES_SSL_ENABLED == 'yes') {
            $this->stream = @ftp_ssl_connect('127.0.0.1', 21, 30);
            if ($this->stream === false) {
                $this->writeLog("Couldn't connect to FTP server using SSL.", E_USER_NOTICE);
            }
        }

        # If no SSL or SSL connect failed, connect without SSL
        if (!$this->stream) {
            $this->stream = @ftp_connect('127.0.0.1', 21, 30);
        }

        if (!$this->stream || !@ftp_login($this->stream, $this->user, $this->passwd)) {
            $this->writeLog("Couldn't connect to FTP server.");
            $this->close();
            return false;
        }

        // Try to enable passive mode, excepted if the FTP daemon is vsftpd
        // vsftpd doesn't allows to operate on a per IP basis (IP masquerading)
        if (Registry::get('config')->FTPD_PACKAGE != 'Servers::ftpd::vsftpd' && !@ftp_pasv($this->stream, true)) {
            $this->writeLog("Couldn't enable passive mode.", E_USER_NOTICE);
        }

        return true;
    }

    /**
     * Closes the virtual file system
     *
     * @return void
     * @throws DatabaseException
     * @throws \Zend_Exception
     * @throws \iMSCP_Exception
     */
    public function close()
    {
        if ($this->stream) {
            if (!@ftp_close($this->stream)) {
                $this->writeLog("Couldn't close connection.", E_USER_WARNING);
            }

            $this->stream = NULL;
        }

        if ($this->user) {
            $this->removeFtpUser();
        }
    }

    /**
     * Get directory listing
     *
     * @param string $dirname OPTIONAL Directory path inside the virtual file system
     * @return array|bool An array of directory entries on success, FALSE on failure
     * @throws DatabaseException
     * @throws \Zend_Exception
     * @throws \iMSCP_Exception
     */
    public function ls($dirname = '/')
    {
        if (!is_string($dirname) || strlen($dirname) == 0 || !$this->open()) {
            return false;
        }

        $dirname = utils_normalizePath($dirname);

        // Make sure that $dirname is relative to the root vfs
        if ($dirname[0] != '/') {
            $dirname = '/' . $dirname;
        }

        if ($this->rootDir != '/') {
            $dirname = $this->rootDir . $dirname;
        }

        // No security implications, the FTP server handles this for us
        $list = @ftp_rawlist($this->stream, "-a $dirname", false);
        if (!$list) {
            return false;
        }

        for ($i = 0, $len = count($list); $i < $len; $i++) {
            $chunks = preg_split('/\s+/', $list[$i], 9);
            $list[$i] = [
                'perms'  => $chunks[0],
                'number' => $chunks[1],
                'owner'  => $chunks[2],
                'group'  => $chunks[3],
                'size'   => $chunks[4],
                'month'  => $chunks[5],
                'day'    => $chunks[6],
                'time'   => $chunks[7],
                'file'   => $chunks[8],
                'type'   => substr($chunks[0], 0, 1)
            ];
        }

        return $list;
    }

    /**
     * Checks if the given file exists inside this virtual file system
     *
     * @param string $file File path inside the virtual file system
     * @param int $type OPTIONAL Type of the file to match
     * @return boolean TRUE if file exists, FALSE otherwise
     * @throws DatabaseException
     * @throws \Zend_Exception
     * @throws \iMSCP_Exception
     */
    public function exists($file, $type = NULL)
    {
        if (!is_string($file) || strlen($file) == 0) {
            return false;
        }

        $list = $this->ls(dirname(utils_normalizePath($file)));

        if (!$list) {
            return false;
        }

        // We get file names only from the listing
        $file = basename($file);

        foreach ($list as $entry) {
            if ($entry['file'] != $file) {
                continue;
            }

            if ($type && $entry['type'] != $type) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Get the content of the given file from this virtual file system
     *
     * @param string $file VFS file path
     * @param int $transferMode OPTIONAL VFS transfer mode
     * @return string|bool File content on success, FALSE on failure
     * @throws DatabaseException
     * @throws \Zend_Exception
     * @throws \iMSCP_Exception
     */
    public function get($file, $transferMode = self::VFS_ASCII)
    {
        if (is_string($file) || strlen($file) == 0 || !$this->open()) {
            return false;
        }

        $file = utils_normalizePath($file);

        // Make sure that $file is relative to the root vfs
        if ($file[0] != '/') {
            $file = '/' . $file;
        }

        if ($this->rootDir != '/') {
            $file = $this->rootDir . $file;
        }

        $tmpFile = @tempnam(Registry::get('config')->GUI_ROOT_DIR . '/data/tmp', 'vfs_');
        if ($tmpFile === false) {
            $this->writeLog("Couldn't create temporary file.");
            return false;
        }

        $ret = true;
        if (@ftp_get($this->stream, $tmpFile, $file, $transferMode) === false) {
            $this->writeLog("Couldn't get file content.");
            $ret = false;
        }

        if ($ret && @file_get_contents($tmpFile) === false) {
            $this->writeLog("Couldn't get file content.");
            $ret = false;
        }

        if (file_exists($tmpFile) && !@unlink($tmpFile)) {
            $this->writeLog("Couldn't remove temporary file.");
        }

        return $ret;
    }

    /**
     * Stores the content of the given file inside this virtual file system
     *
     * @param string $file New file path inside the virtual file system
     * @param string $content File content
     * @param int $transferMode VFS transfer mode
     * @return boolean TRUE on success, FALSE on failure
     * @throws DatabaseException
     * @throws \Zend_Exception
     * @throws \iMSCP_Exception
     */
    public function put($file, $content, $transferMode = self::VFS_ASCII)
    {
        if (!is_string($file) || strlen($file) == 0 || !is_string($content) || !$this->open()) {
            return false;
        }

        $file = utils_normalizePath($file);

        // Make sure that $file is relative to the root vfs
        if ($file[0] != '/') {
            $file = '/' . $file;
        }

        if ($this->rootDir != '/') {
            $file = $this->rootDir . $file;
        }

        $tmpFile = @tempnam(Registry::get('config')->GUI_ROOT_DIR . '/data/tmp', 'vfs_');
        if ($tmpFile === false) {
            $this->writeLog("Couldn't create temporary file.", E_USER_ERROR);
            return false;
        }

        $ret = true;
        if (@file_put_contents($tmpFile, $content) === false) {
            $this->writeLog("Couldn't write file content.", E_USER_ERROR);
            $ret = false;
        }

        if ($ret && !@ftp_put($this->stream, $file, $tmpFile, $transferMode)) {
            $this->writeLog("Couldn't upload file", E_USER_ERROR);
            $ret = false;
        }

        if (file_exists($tmpFile) && !@unlink($tmpFile)) {
            $this->writeLog("Couldn't remove temporary file.");
        }

        return $ret;
    }

    /**
     * Create a FTP user for accessing this virtual file system
     *
     * @return bool TRUE on success, FALSE on failure
     * @throws DatabaseException
     * @throws \Zend_Exception
     * @throws \iMSCP_Exception
     */
    protected function createFtpUser()
    {
        try {
            $stmt = exec_query('SELECT admin_sys_uid, admin_sys_gid FROM admin WHERE admin_name = ?', $this->user);

            if (!$stmt->rowCount()) {
                return false;
            }

            $row = $stmt->fetchRow();
            $this->passwd = Crypt::randomStr(16);

            exec_query(
                'INSERT INTO ftp_users (userid, passwd, uid, gid, shell, homedir, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $this->user,
                    Crypt::sha512($this->passwd),
                    $row['admin_sys_uid'],
                    $row['admin_sys_gid'],
                    '/bin/sh',
                    utils_normalizePath(Registry::get('config')->USER_WEB_DIR . '/' . $this->user), 'ok'
                ]
            );
        } catch (\Exception $e) {
            if ($e instanceof DatabaseException && $e->getCode() == 23000) {
                $this->writeLog('Concurrent FTP connections are not allowed.', E_USER_WARNING);
                return false;
            }

            $this->writeLog(sprintf("Couldn't create FTP user: %s", $e->getMessage()));
            return false;
        }

        return true;
    }

    /**
     * Removes the FTP user associated with this virtual file system
     *
     * @return void
     * @throws DatabaseException
     * @throws \iMSCP_Events_Exception
     */
    protected function removeFtpUser()
    {
        exec_query('DELETE FROM ftp_users WHERE userid = ?', $this->user);
    }

    /**
     * Write log
     *
     * @param string $message Message to write
     * @param int $level OPTIONAL Message level
     * @throws DatabaseException
     * @throws \Zend_Exception
     * @throws \iMSCP_Exception
     */
    protected function writeLog($message, $level = E_USER_ERROR)
    {
        write_log(sprintf('VirtualFileSystem: %s', $message), $level);
    }
}
