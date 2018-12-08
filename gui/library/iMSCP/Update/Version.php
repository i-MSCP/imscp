<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

/**
 * Class iMSCP_Update_Version
 */
class iMSCP_Update_Version extends iMSCP_Update
{
    /**
     * @var iMSCP_Update
     */
    protected static $instance;

    /**
     * @var array|null Update info
     */
    protected $updateInfo;

    /**
     * Singleton - Make new unavailable
     */
    protected function __construct()
    {

    }

    /**
     * Singleton - Make clone unavailable
     *
     * @return void
     */
    protected function __clone()
    {

    }

    /**
     * Implements Singleton design pattern
     *
     * @return iMSCP_Update
     */
    public static function getInstance()
    {
        if (NULL === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @inheritdoc
     */
    public function isAvailableUpdate()
    {
        return version_compare($this->getNextUpdate(), $this->getLastAppliedUpdate(), '>');
    }

    /**
     * @inheritdoc
     */
    public function applyUpdates()
    {
        $this->setError('i-MSCP version update can be initiated through the i-MSCP installer only.');
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getNextUpdate()
    {
        $updateInfo = $this->getUpdateInfo();
        if (is_array($updateInfo) && isset($updateInfo['tag_name'])) {
            list($version, $build) = array_pad(explode('-', $updateInfo['tag_name']), 2, '0000000000');
            return "$version-$build";
        }

        // We are safe here
        return $this->getLastAppliedUpdate();
    }

    /**
     * @inheritdoc
     */
    public function getLastAppliedUpdate()
    {
        $cfg = iMSCP_Registry::get('config');
        if (stripos($cfg['Version'], 'git') === false) {
            return $cfg['Version'] . '-' . $cfg['Build'] ?: '0000000000';
        }

        // Case where the version in use has not been officially released (eg. git branch).
        return '999.999.999-0000000000';
    }

    /**
     * Get update info from GitHub (using the GitHub API)
     *
     * @throws Zend_Exception
     * @throws iMSCP_Exception
     * @throws iMSCP_Exception_Database
     * @param bool $forceReload Whether data must be reloaded from Github
     * @return array|bool An array containing update info on success, false on failure
     */
    public function getUpdateInfo($forceReload = false)
    {
        if (NULL !== $this->updateInfo) {
            return $this->updateInfo;
        }

        $file = CACHE_PATH . '/imscp_info.json';
        if ($forceReload || !file_exists($file) || strtotime('+1 day', filemtime($file)) < time()) {
            clearstatcache();
            $context = stream_context_create([
                'http' => [
                    'method'           => 'GET',
                    'protocol_version' => '1.1',
                    'header'           => [
                        'Host: api.github.com',
                        'Accept: application/vnd.github.v3+json',
                        'User-Agent: i-MSCP',
                        'Connection: close',
                        'timeout' => 30
                    ]
                ]
            ]);

            if (!stream_context_set_option($context, 'ssl', 'verify_peer', false)) {
                $this->setError(tr('Unable to set sslverifypeer option'));
                return false;
            }

            if (!stream_context_set_option($context, 'ssl', 'allow_self_signed', true)) {
                $this->setError(tr('Unable to set sslallowselfsigned option'));
                return false;
            }

            // Retrieving latest release info from GitHub
            $info = @file_get_contents('https://api.github.com/repos/i-MSCP/imscp/releases/latest', false, $context);
            if ($info === false) {
                $this->setError(tr('Unable to get update info from GitHub'));
            } elseif (!isJson($info)) {
                $this->setError(tr('Invalid payload received from GitHub'));
                return false;
            }

            if (file_exists($file)) {
                if (!@unlink($file)) {
                    $this->setError(tr('Unable to delete i-MSCP info file.'));
                    write_log(sprintf('Unable to delete i-MSCP info file.'), E_USER_ERROR);
                    return false;
                }
            }

            if (@file_put_contents($file, $info, LOCK_EX) === false) {
                write_log(sprintf('Unable to create i-MSCP info file.'), E_USER_ERROR);
            } else {
                write_log(sprintf('New i-MSCP info file has been created.'), E_USER_NOTICE);
            }
        } elseif (($info = file_get_contents($file)) === false) {
            $this->setError(tr('Unable to load i-MSCP info file.'));
            write_log(sprintf('Unable to load i-MSCP info file.'), E_USER_ERROR);
            return false;
        }

        $this->updateInfo = json_decode($info, true);
        return $this->updateInfo;
    }
}
