<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
 * @package     iMSCP_HostingServices
 * @subpackage  Controllers_Account
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Account controller
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Controllers_Account
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @since       1.0.0
 * @version     1.0.0
 */
class HostingServices_AccountController extends Zend_Controller_Action
{
	/**
	 * @return void
	 */
    public function init(){}

	/**
	 * @return void
	 */
	public function customersListAction() {}

	/**
	 * @return void
	 */
	public function resellersListAction() {}

	/**
	 * @return void
	 */
	public function customerCreateAction(){}

	/**
	 * @return void
	 */
	public function resellerCreateAction(){}

	/**
	 * @return void
	 */
	public function customerActivateAction(){}

	/**
	 * @return void
	 */
	public function resellerActivateAction(){}

	/**
	 * @return void
	 */
	public function customerDeleteAction(){}

	/**
	 * @return void
	 */
	public function resellerDeleteAction(){}

	/**
	 * @return void
	 */
	public function customerSuspendAction(){}

	/**
	 * @return void
	 */
	public function resellerSuspendAction(){}
}
