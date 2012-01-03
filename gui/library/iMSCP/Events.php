<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2012 by i-MSCP team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Events
 * @copyright	2010-2012 by i-MSCP team
 * @author		Laurent Declercq <laurent.declercq@i-mscp.net>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Class that describes all core events fired in the i-MSCP actions scripts.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Events
 * @author		Laurent Declercq <l.declercq@i-mscp.net>
 * @version		0.0.5
 */
class iMSCP_Events
{
	/**
	 * The onLoginScriptStart event is fired at the very beginning of Login script.
	 *
	 * The event listener method receives an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onLoginScriptStart = 'onLoginScriptStart';

	/**
	 * The onLoginScriptEnd event is fired at the end of Login script.
	 *
	 * The event listener method receives an iMSCP_Events_Response object.
	 *
	 * @var string
	 */
	const onLoginScriptEnd = 'onLoginScriptEnd';

	/**
	 * The onBeforeRegister event is fired before an user is registered (logged on).
	 *
	 * The event listener method receives an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onBeforeRegisterUser = 'onBeforeRegisterUser';

	/**
	 * The onAfterRegister event is fired after an user is registered (logged on).
	 *
	 * The event listener method receives an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const  onAfterRegisterUser = 'onAfterRegisterUser';

	/**
	 * The onLostPasswordScriptStart event is fired at the very beginning of the
	 * LostPassword script.
	 *
	 * The event listener method receives an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onLostPasswordScriptStart = 'onLostPasswordScriptStart';

	/**
	 * The onLostPasswordScriptEnd event is fired at the end of the LostPassword script.
	 *
	 * The event listener method receives an iMSCP_Events_Response object.
	 *
	 * @var string
	 */
	const onLostPasswordScriptEnd = 'onLostPasswordScriptEnd';

	/**
	 * The onAdminScriptStart event is fired at the very beginning of admin scripts.
	 *
	 * The event listener method receives an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onAdminScriptStart = 'onAdminScriptStart';

	/**
	 * The onAdminScriptEnd event is fired at the end of admin scripts.
	 *
	 * The event listener method receives an iMSCP_Events_Response object.
	 *
	 * @var string
	 */
	const onAdminScriptEnd = 'onAdminScriptEnd';

	/**
	 * The onResellerScriptStart event is fired at the very beginning of reseller scripts.
	 *
	 * The event listener method receives an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onResellerScriptStart = 'onResellerScriptStart';

	/**
	 * The onResellerScriptEnd event is fired at the end of reseller scripts.
	 *
	 * The event listener method receives an iMSCP_Events_Response object.
	 *
	 * @var string
	 */
	const onResellerScriptEnd = 'onResellerScriptEnd';

	/**
	 * The onClientScriptStart event is fired at the very beginning of client scripts.
	 *
	 * The event listener method receives an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onClientScriptStart = 'onClientScriptStart';

	/**
	 * The onClientScriptEnd event is fired at the end of client scripts.
	 *
	 * The event listener method receives an iMSCP_Events_Response object.
	 *
	 * @var string
	 */
	const onClientScriptEnd = 'onClientScriptEnd';

	/**
	 * The onOrderPanelScriptStart is fired occurs at the very beginning of orderpanel scripts.
	 *
	 * The event listener method receives an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onOrderPanelScriptStart = 'onOrderPanelScriptStart';

	/**
	 * The onOrderPanelScriptEnd event is fired at the end of orderpanel scripts.
	 *
	 * The event listener method receives an iMSCP_Events_Response object.
	 *
	 * @var string
	 */
	const onOrderPanelScriptEnd = 'onOrderPanelScriptEnd';

	/**
	 * The onExceptioToBrowserStart event is fired before of exception browser write processs.
	 *
	 * The event listener method receives a iMSCP_Envents_Event object.
	 */
	const onExceptionToBrowserStart = 'onExceptionToBrowserStart';

	/**
	 * The onExceptionToBrowserEnd event is fired at the end of exception browser write process.
	 *
	 * The event listener method receives a iMSCP_Events_Response object.
	 *
	 * @var string
	 */
	const onExceptionToBrowserEnd = 'onExceptionToBrowserEnd';
}
