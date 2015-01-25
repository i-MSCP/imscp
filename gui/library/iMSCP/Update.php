<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by i-MSCP team
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
 *
 * @category    iMSCP
 * @package     iMSCP_Update
 * @copyright   2010-2015 by i-MSCP team
 * @author      Daniel Andreca <sci2tech@gmail.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * Base class for update
 *
 * @category    iMSCP
 * @package     iMSCP_Update
 * @author      Daniel Andreca <sci2tech@gmail.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
abstract class iMSCP_Update
{
    /**
     * Last error message
     *
     * @var string|null
     */
    protected $lastError;

	/**
	 * Set error
	 *
	 * @param string $error
	 * @return self
	 */
	protected function setError($error)
	{
		$this->lastError = $error;

		return $this;
	}

    /**
     * Returns last error that occurred
     *
     * @return string Last error
     */
    public function getError()
    {
        return $this->lastError;
    }

    /**
     * Apply all available update
     *
     * @abstract
     * @return bool TRUE on success, FALSE othewise
     */
    abstract public function applyUpdates();

    /**
     * Checks for available update
     *
     * @abstract
     * @return bool TRUE if an update available, FALSE otherwise
     */
    abstract public function isAvailableUpdate();

    /**
     * Returns last applied update
     *
     * @abstract
     * @return mixed
     */
    abstract protected function getLastAppliedUpdate();

    /**
     * Return next update
     *
     * @abstract
     * @return mixed next update info
     */
    abstract protected function getNextUpdate();
}
