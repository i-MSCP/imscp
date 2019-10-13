<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

/** @noinspection
 * PhpUnhandledExceptionInspection
 * PhpDocMissingThrowsInspection
 * PhpUnused
 */

declare(strict_types=1);

namespace iMSCP;

use iMSCP\Database\DatabaseException;
use iMSCP\Exception\Exception;
use InvalidArgumentException;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use RuntimeException;
use Throwable;

/**
 * Class VirtualFileSystem
 * @package iMSCP
 */
class VirtualFileSystem
{
    /**
     * @var string VFS file type
     */
    const
        VFS_TYPE_DIR = 'dir',
        VFS_TYPE_FILE = 'file';

    /**
     * @var string FTP username
     */
    private $ftpUser;

    /**
     * @var string FTP user password
     */
    private $ftpUserPassword;

    /**
     * @var string VFS root directory, relative to the home directory of an
     *             i-MSCP user's UNIX user
     */
    private $vfsRootDir;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     *
     * Creates a new VFS.
     *
     * @param string $username username An i-MSCP username
     * @param string $vfsRootDir Root directory of the VFS, relative to
     *                           $username' UNIX user homedir
     */
    public function __construct(string $username, ?string $vfsRootDir = '/')
    {
        $this->ftpUser = encode_idna($username);
        $this->vfsRootDir = $vfsRootDir;
    }

    /**
     * Close connection to the VFS and delete the associated FTP user.
     *
     * @return void
     */
    public function __destruct()
    {
        $this->disconnect();
        $this->deleteFtpUser();
    }

    /**
     * Whether the given file exists inside this VFS and is of the given type.
     *
     * @param string $file File path, relative to the VFS root directory.
     * @param string $type Type Expected file type
     * @return boolean TRUE if the file exists, FALSE otherwise
     */
    public function exists(
        string $file, ?string $type = self::VFS_TYPE_FILE
    ): bool
    {
        try {
            return $this->connect()->getMetadata($file)['type'] == $type;
        } catch (FileNotFoundException $e) {
            return false;
        } catch (Throwable $e) {
            throw new RuntimeException(
                sprintf("Couldn't check file/dir existence: %s", $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * List content of the given directory inside this VFS.
     *
     * @param string $dirname Directory path, relative to the VFS root directory
     * @return array An array of file entries on success, FALSE on failure
     */
    public function ls(?string $dirname = ''): array
    {
        return $this->connect()->listContents($dirname);
    }

    /**
     * Get the content of the given file from this VFS.
     *
     * @param string $file VFS file path
     * @return string|bool File content on success, FALSE on failure
     */
    public function get(string $file)
    {
        return $this->connect()->read($file);

    }

    /**
     * Stores the content of the given file inside this VFS.
     *
     * @param string $file File path
     * @param string $content File content
     * @return bool TRUE on success, FALSE on failure
     */
    public function put(string $file, string $content): bool
    {
        $fs = $this->connect();

        if (!$this->exists($file, self::VFS_TYPE_FILE)) {
            return $fs->write($file, $content);
        }

        return $fs->update($file, $content);
    }

    /**
     * Create a FTP user for accessing this VFS.
     *
     * @return void
     */
    private function createFtpUser(): void
    {
        try {
            $stmt = exec_query(
                '
                    SELECT `admin_sys_uid`, `admin_sys_gid`
                    FROM `admin`
                    WHERE `admin_name` = ?
                ',
                $this->ftpUser
            );

            if (!$stmt->rowCount()) {
                throw new InvalidArgumentException(sprintf(
                    "The %s user has not been found", $this->ftpUser
                ));
            }

            $row = $stmt->fetchRow();
            $this->ftpUserPassword = Crypt::randomStr(16);

            exec_query(
                "
                    INSERT INTO `ftp_users` (
                        `userid`, `passwd`, `uid`, `gid`, `shell`, `homedir`,
                        `status`
                    ) VALUES (
                        ?, ?, ?, ?, '/bin/sh', ?, 'ok'
                    )
                ",
                [
                    $this->ftpUser,
                    Crypt::sha512($this->ftpUserPassword),
                    $row['admin_sys_uid'],
                    $row['admin_sys_gid'],
                    utils_normalizePath(
                        Registry::get('config')['USER_WEB_DIR']
                        . '/'
                        . $this->ftpUser
                    )
                ]
            );
        } catch (Exception $e) {
            if ($e instanceof DatabaseException && $e->getCode() == 23000) {
                throw new RuntimeException(
                    'Concurrent connections to a VFS with an identical FTP user are forbidden.'
                );
            }

            throw new RuntimeException(
                "Couldn't create FTP user for connection to VFS."
            );
        }
    }

    /**
     * Open the VFS.
     *
     * @return Filesystem
     */
    private function connect(): Filesystem
    {
        try {
            if (NULL !== $this->fs) {
                return $this->fs;
            }

            $this->createFtpUser();

            $this->fs = new Filesystem(new Ftp([
                'host'                           => '127.0.0.1',
                'port'                           => 21,
                'username'                       => $this->ftpUser,
                'password'                       => $this->ftpUserPassword,
                'ssl'                            =>
                    Registry::get('config')['SERVICES_SSL_ENABLED'] == 'yes',
                'timeout'                        => 30,
                'root'                           => $this->vfsRootDir,
                'passive'                        => true,
                'transferMode'                   => FTP_BINARY,
                'ignorePassiveAddress'           => true,
                'utf8'                           => true,
                'enableTimestampsOnUnixListings' => false
            ]));

            return $this->fs;
        } catch (Throwable $e) {
            throw new RuntimeException(
                sprintf("Couldn't open VFS connection: %s", $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Closes the VFS.
     *
     * @return void
     */
    private function disconnect(): void
    {
        if (NULL === $this->fs) {
            return;
        }

        /** @var Ftp $adapter */
        $adapter = $this->fs->getAdapter();
        @$adapter->disconnect();
        $this->fs = NULL;
    }

    /**
     * Deletes the FTP user.
     *
     * @return void
     */
    private function deleteFtpUser(): void
    {
        if (NULL === $this->ftpUser) {
            return;
        }

        exec_query('DELETE FROM ftp_users WHERE userid = ?', $this->ftpUser);
        $this->ftpUser = NULL;
    }
}
