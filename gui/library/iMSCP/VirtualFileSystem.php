<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * This class provides a FTP layer allowing to browse and edit all customer's files from i-MSCP frontEnd.
 */
class VirtualFileSystem
{
    /**
     * @var string VFS filetype
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
     * @var string Domain name of this virtual file system
     */
    protected $domain;

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
    protected $rootdir;

    /**
     * @var resource FTP stream
     */
    protected $stream;

    /**
     * Constructor
     *
     * Creates a virtual file system object for the given $domain using $rootDir as root directory.
     *
     * @param string $domain Domain name of the the virtual file system
     * @param string $rootDir OPTIONAL Root directory of the virtual file system (relative to $domain root directory)
     */
    public function __construct($domain, $rootDir = '/')
    {
        $this->domain = $domain;
        $this->rootdir = $rootDir;
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
     * @return boolean TRUE on success, FALSE on failure
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
            $this->stream = @ftp_ssl_connect('127.0.0.1', 21, 60);
        }

        # If no SSL or SSL connect failed, connect without SSL
        if (!$this->stream) {
            $this->stream = @ftp_connect('127.0.0.1', 21, 60);
        }

        if (!$this->stream || !@ftp_login($this->stream, $this->user, $this->passwd)) {
            $this->close();

            $error = error_get_last();
            write_log(sprintf('Could not connect to FTP server for virtual file system access: %s', $error['message']), E_USER_ERROR);
            return false;
        }

        # Try to enable passive mode
        @ftp_pasv($this->stream, true);

        return true;
    }

    /**
     * Closes the virtual file system
     *
     * @return void
     */
    public function close()
    {
        if ($this->stream) {
            ftp_close($this->stream);
            $this->stream = null;
        }

        if ($this->user) {
            $this->removeFtpUser();
        }
    }

    /**
     * Get directory listing
     *
     * @param string $dirname Directory path inside the virtual file system
     * @return array|bool An array of directory entries on success, FALSE on failure
     */
    public function ls($dirname)
    {
        if (!$this->open()) {
            return false;
        }

        if ($this->rootdir != '/') {
            $dirname = $this->rootdir . $dirname;
        }

        // Path is always relative to the root vfs
        if (substr($dirname, 0, 1) != '/') {
            $dirname = '/' . $dirname;
        }

        // No security implications, the FTP server handles this for us
        $list = ftp_rawlist($this->stream, "-a $dirname", false);
        if (!$list) {
            return false;
        }

        for ($i = 0, $len = count($list); $i < $len; $i++) {
            $chunks = preg_split('/\s+/', $list[$i], 9);
            $list[$i] = array(
                'perms' => $chunks[0],
                'number' => $chunks[1],
                'owner' => $chunks[2],
                'group' => $chunks[3],
                'size' => $chunks[4],
                'month' => $chunks[5],
                'day' => $chunks[6],
                'time' => $chunks[7],
                'file' => $chunks[8],
                'type' => substr($chunks[0], 0, 1)
            );
        }

        return $list;
    }

    /**
     * Checks if the given file exists inside this virtual file system
     *
     * @param string $file File path inside the virtual file system
     * @param int $type Type of the file to match
     * @return boolean TRUE if file exists, FALSE otherwise
     */
    public function exists($file, $type = null)
    {
        $list = $this->ls(dirname($file));

        if (!$list) {
            return false;
        }

        // We get filenames only from the listing
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
     * @param int $mode VFS transfer mode
     * @return string|bool File content on success, FALSE on failure
     */
    public function get($file, $mode = self::VFS_ASCII)
    {
        if (!$this->open()) {
            return false;
        }

        // Path is always relative to the root vfs
        if (substr($file, 0, 1) != '/') {
            $file = '/' . $file;
        }

        if ($this->rootdir != '/') {
            $file = $this->rootdir . $file;
        }

        $tmp = tempnam(Registry::get('config')->GUI_ROOT_DIR . '/data/tmp', 'vfs_');

        $rs = ftp_get($this->stream, $tmp, $file, $mode);
        if (false === $rs) {
            return false;
        }

        $rs = @file_get_contents($tmp);
        @unlink($tmp);
        return $rs;
    }

    /**
     * Stores the content of the given file inside this virtual file system
     *
     * @param string $file New file path inside the virtual file system
     * @param string $content File content
     * @param int $mode VFS transfer mode
     * @return boolean TRUE on success, FALSE on failure
     */
    public function put($file, $content, $mode = self::VFS_ASCII)
    {
        if (!$this->open()) {
            return false;
        }

        // Path is always relative to the root vfs
        if (substr($file, 0, 1) != '/') {
            $file = '/' . $file;
        }

        if ($this->rootdir != '/') {
            $file = $this->rootdir . $file;
        }

        $tmp = tempnam(Registry::get('config')->GUI_ROOT_DIR . '/data/tmp', 'vfs_');

        if (false === file_put_contents($tmp, $content)) {
            return false;
        }

        if (!ftp_put($this->stream, $file, $tmp, $mode)) {
            return false;
        }

        @unlink($tmp);
        return true;
    }

    /**
     * Create a FTP user for accessing this virtual file system
     *
     * @throws DatabaseException
     * @return bool TRUE on success, FALSE on failure
     */
    protected function createFtpUser()
    {
        try {
            $stmt = exec_query(
                '
                  SELECT admin_sys_uid, admin_sys_gid
                  FROM admin
                  INNER JOIN domain ON (domain_admin_id = admin_id)
                  WHERE domain_name = ?
                ',
                $this->domain
            );

            if (!$stmt->rowCount()) {
                return false;
            }

            $row = $stmt->fetchRow();
            $this->user = $this->domain;
            $this->passwd = Crypt::randomStr(16);

            exec_query(
                'INSERT INTO ftp_users (userid, passwd, uid, gid, shell, homedir, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
                array(
                    $this->user,
                    Crypt::sha512($this->passwd),
                    $row['admin_sys_uid'],
                    $row['admin_sys_gid'],
                    '/bin/sh',
                    Registry::get('config')->USER_WEB_DIR . '/' . $this->domain, 'ok'
                )
            );
        } catch (\Exception $e) {
            if ($e instanceof DatabaseException && $e->getCode() == 23000) {
                set_page_message(tr('Concurrent FTP connections for the same domain are not allowed.'), 'error');
                return false;
            }

            write_log(sprintf('Could not create FTP user for virtual file system access: %s', $e->getMessage()), E_USER_ERROR);
            return false;
        }

        return true;
    }

    /**
     * Removes the FTP user associated with this virtual file system
     *
     * @return void
     */
    protected function removeFtpUser()
    {
        exec_query('DELETE FROM `ftp_users` WHERE `userid` = ?', $this->user);
    }
}
