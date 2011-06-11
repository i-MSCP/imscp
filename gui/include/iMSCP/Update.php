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
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package     iMSCP_Update
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 * @version     SVN: $Id$
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license     http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Base class for update.
 *
 * @category    iMSCP
 * @package     iMSCP_Update
 * @author      Daniel Andreca <sci2tech@gmail.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     1.0.5
 */
abstract class iMSCP_Update
{
    /**
     * Last error message.
     *
     * @var string
     */
    protected $_lastError = '';

    /**
     * Apply all available update.
     *
     * @abstract
     * @return void
     */
    abstract public function applyUpdate();

    /**
     * Checks for available update.
     *
     * @abstract
     * @return void
     */
    abstract public function isAvailableUpdate();

    /**
     * Returns last applied update.
     *
     * @abstract
     * @return int
     */
    abstract protected function getLastAppliedUpdate();

    /**
     * Return next update.
     *
     * @abstract
     * @return int
     */
    abstract protected function getNextUpdate();

    /**
     * Returns last error occured.
     *
     * @return string Last error
     */
    public function getError()
    {
        return $this->_lastError;
    }
}
