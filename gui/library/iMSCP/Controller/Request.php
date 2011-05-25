<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2011 i-MSCP Team
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
 * @package     iMSCP_Controller
 * @subpackage  Request
 * @copyright   2011 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 * @link        http://www.i-mscp.net i-mscp Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/** @See iMSCP_Controller_Request_Interface */
require_once 'iMSCP/Controller/Request/Interface.php';

/**
 * Request class
 *
 * @category    iMSCP
 * @package     iMSCP_Controller
 * @subpackage  Request
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class iMSCP_Controller_Request implements iMSCP_Controller_Request_Interface
{
    /**
     * Tells whether or not the request was dispatched.
     *
     * @var bool
     */
    protected $_dispatched = false;

    /**
     * Set flag indicating whether or not request has been dispatched.
     *
     * @param bool $flag
     * @return iMSCP_Controller_Request_Interface
     */
    public function setDispatched($flag = true)
    {
        $this->_dispatched = $flag ? true : false;

        return $this;
    }

    /**
     * Determine if the request has been dispatched.
     *
     * @return bool
     */
    public function isDispatched()
    {
        return $this->_dispatched;
    }
}
