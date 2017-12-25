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

namespace iMSCP\Update;

use iMSCP_Registry as Registry;

/**
 * Class UpdateVersion
 * @package iMSCP\Update
 */
class UpdateVersion extends UpdateAbstract
{
    /**
     * @var string Github API URL
     */
    protected $githubApiUrl = 'https://api.github.com/repos/i-MSCP/imscp/releases/latest';

    /**
     * @var array Update info
     */
    protected $updateInfo;

    /**
     * @inheritdoc
     */
    public function isAvailableUpdate()
    {
        if (version_compare($this->getNextUpdate(), $this->getLastAppliedUpdate(), '>')) {
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getNextUpdate()
    {
        $updateInfo = $this->getUpdateInfo();

        if (is_array($updateInfo)
            && isset($updateInfo['tag_name'])
        ) {
            return $updateInfo['tag_name'];
        }

        return $this->getLastAppliedUpdate();
    }

    /**
     * Get update info from GitHub (using the GitHub API)
     *
     * @throws UpdateException
     * @param bool $forceReload Whether data must be reloaded from Github
     * @return array An array containing update info on success, false on failure
     */
    public function getUpdateInfo($forceReload = false)
    {
        if (NULL !== $this->updateInfo
            && !$forceReload
        ) {
            return $this->updateInfo;
        }

        $file = utils_normalizePath(CACHE_PATH . '/imscp_info.json');

        if ($forceReload
            || !file_exists($file)
            || strtotime('+1 day', filemtime($file)) < time()
        ) {
            clearstatcache();

            $context = stream_context_create([
                'ssl'  => [
                    'capath'            => utils_normalizePath(Registry::get('config')['DISTRO_CA_PATH']),
                    'verify_peer'       => true,
                    'verify_peer_name'  => true,
                    'allow_self_signed' => false,
                    'verify_depth'      => 5
                ],
                'http' => [
                    'method'           => 'GET',
                    'protocol_version' => '1.1',
                    'header'           => [
                        'Host: api.github.com',
                        'Accept: application/vnd.github.v3+json',
                        'User-Agent: i-MSCP',
                        'Connection: close',
                        'timeout' => 5
                    ]
                ]
            ]);

            $info = @file_get_contents($this->githubApiUrl, false, $context);

            if ($info === false) {
                throw new UpdateException(
                    sprintf("Couldn't get update info from Github: %s", error_get_last()['message'])
                );
            }

            if (!isJson($info)) {
                throw new UpdateException('Invalid payload received from GitHub');
            }

            if (file_exists($file)
                && !@unlink($file)
            ) {
                throw new UpdateException(sprintf("Couldn't delete i-MSCP info file: %s", error_get_last()['message']));
            }

            if (@file_put_contents($file, $info, LOCK_EX) === false) {
                throw new UpdateException(sprintf("Couldn't create i-MSCP info file: %s", error_get_last()['message']));
            }

            return $this->updateInfo = json_decode($info, true);
        }

        if (($info = @file_get_contents($file)) === false) {
            throw new UpdateException(sprintf("Couldn't load i-MSCP info file: %s", error_get_last()['message']));
        }

        return $this->updateInfo = json_decode($info, true);
    }

    /**
     * @inheritdoc
     */
    public function getLastAppliedUpdate()
    {
        $cfg = Registry::get('config');
        if (isset($cfg['Version']) && stripos($cfg['Version'], 'git') === false) {
            return $cfg['Version'];
        }

        return '99'; // Case where the version in use has not been officially released (eg. git branch).
    }

    /**
     * @inheritdoc
     */
    public function applyUpdates()
    {
        throw new UpdateException('i-MSCP version update can be initiated through the i-MSCP installer only.');
    }
}
