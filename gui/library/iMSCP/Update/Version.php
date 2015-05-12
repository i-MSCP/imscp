<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Check for available update
	 *
	 * @return bool TRUE if an update is available, FALSE otherwise
	 */
	public function isAvailableUpdate()
	{
		if (version_compare($this->getNextUpdate(), $this->getLastAppliedUpdate(), '>')) {
			return true;
		}

		return false;
	}

	/**
	 * Apply all available update
	 *
	 * @return bool TRUE on success, FALSE othewise
	 */
	public function applyUpdates()
	{
		$this->setError('i-MSCP version update can be initiated through the i-MSCP installer only.');

		return false;
	}

	/**
	 * Get update info from GitHub (using the GitHub API)
	 *
	 * @param bool $forceReload Whether data must be reloaded from Github
	 * @return array|bool An array containing update info on success, false on failure
	 */
	public function getUpdateInfo($forceReload = false)
	{
		if (null === $this->updateInfo) {
			$file = CACHE_PATH . '/imscp_info.json';

			if ($forceReload || !file_exists($file) || strtotime('+1 day', filemtime($file)) < time()) {
				clearstatcache();

				$context = stream_context_create(
					array(
						'http' => array(
							'method' => 'GET',
							'protocol_version' => '1.1',
							'header' => array(
								'Host: api.github.com',
								'Accept: application/vnd.github.v3+json',
								'User-Agent: i-MSCP',
								'Connection: close',
								'timeout' => 3
							)
						)
					)
				);

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
					$this->setError(tr('Unable to get update info from Github'));
				} elseif(!isJson($info)) {
					$this->setError(tr('Invalid payload received from GitHub'));
					return false;
				}

				if(file_exists($file)) {
					if(!@unlink($file)) {
						$this->setError(tr('Unable to delete i-MSCP info file.'));
						write_log(sprintf('Unable to deelte i-MSCP info file.'), E_USER_ERROR);
						return false;
					}
				}

				if (@file_put_contents($file, $info, LOCK_EX) === false) {
					write_log(sprintf('Unable to create i-MSCP info file.'), E_USER_ERROR);
				} else {
					write_log(sprintf('New i-MSCP info file has been created.'), E_USER_NOTICE);
				}
			} else {
				if(($info = file_get_contents($file)) === false) {
					$this->setError(tr('Unable to load i-MSCP info file.'));
					write_log(sprintf('Unable to load i-MSCP info file.'), E_USER_ERROR);
					return false;
				}
			}

			$this->updateInfo = json_decode($info, true);
		}

		return $this->updateInfo;
	}

	/**
	 * Return build number for the last available i-MSCP version
	 *
	 * @return string
	 */
	protected function getNextUpdate()
	{
		$updateInfo = $this->getUpdateInfo();

		if (is_array($updateInfo) && isset($updateInfo['tag_name'])) {
			return $updateInfo['tag_name'];
		}

		return $this->getLastAppliedUpdate(); // We are safe here
	}

	/**
	 * Returns last applied update
	 *
	 * @throws iMSCP_Update_Exception When unable to retrieve last applied update
	 * @return string
	 */
	protected function getLastAppliedUpdate()
	{
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		if (isset($cfg['Version']) && stripos($cfg['Version'], 'git') === false) {
			return $cfg['Version'];
		}

		return '99'; // Case where the version in use has not been officially released (eg. git branch).
	}
}
