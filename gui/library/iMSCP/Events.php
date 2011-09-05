<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP team
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
 * @category    iMSCP
 * @package     iMSCP_Core
 * @copyright   2010-2011 by i-MSCP team
 * @author      Laurent Declercq <laurent.declercq@nuxwin.com>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Contains all common events thrown in the i-MSCP scripts.
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class iMSCP_Events
{
    /**
     * The onLoginScriptStart event occurs at the very beginning of Login script.
     *
     * The event listener method receives a iMSCP_Events_Event instance.
     *
     * @var string
     */
    const onLoginScriptStart = 'onLoginScriptStart';

    /**
     * The onLoginScriptEnd event occurs at the end of Login script.
     *
     * The event listener method receives a iMSCP_Events_Response instance.
     *
     * @var string
     */
    const onLoginScriptEnd = 'onLoginScriptEnd';

    /**
     * The onLostPasswordScriptStart event occurs at the very beginning of the
     * LostPassword script.
     *
     * The event listener method receives a iMSCP_Events_Event instance.
     *
     * @var string
     */
    const onLostPasswordScriptStart = 'onLostPasswordScriptStart';

    /**
     * The onLostPasswordScriptEnd event occurs at the end of the LostPassword script.
     *
     * The event listener method receives a iMSCP_Events_Response instance.
     *
     * @var string
     */
    const onLostPasswordScriptEnd = 'onLostPasswordScriptEnd';

    /**
     * The onAdminScriptStart event occurs at the very beginning of admin scripts.
     *
     * The event listener method receives a iMSCP_Events_Event instance.
     *
     * @var string
     */
    const onAdminScriptStart = 'onAdminScriptStart';

    /**
     * The onAdminScriptEnd event occurs at the end of admin scripts.
     *
     * The event listener method receives a iMSCP_Events_Response instance.
     *
     * @var string
     */
    const onAdminScriptEnd = 'onAdminScriptEnd';

    /**
     * The onResellerScriptStart event occurs at the very beginning of reseller scripts.
     *
     * The event listener method receives a iMSCP_Events_Event instance.
     *
     * @var string
     */
    const onResellerScriptStart = 'onResellerScriptStart';

    /**
     * The onResellerScriptEnd event occurs at the end of reseller scripts.
     *
     * The event listener method receives a iMSCP_Events_Response instance.
     *
     * @var string
     */
    const onResellerScriptEnd = 'onResellerScriptEnd';

    /**
     * The onClientScriptStart event occurs at the very beginning of client scripts.
     *
     * The event listener method receives a iMSCP_Events_Event instance.
     *
     * @var string
     */
    const onClientScriptStart = 'onClientScriptStart';

    /**
     * The onClientScriptEnd event occurs at the end of client scripts.
     *
     * The event listener method receives a iMSCP_Events_Response instance.
     *
     * @var string
     */
    const onClientScriptEnd = 'onClientScriptEnd';

    /**
     * The onOrderPanelScriptStart event occurs at the very beginning of orderpanel scripts.
     *
     * The event listener method receives a iMSCP_Events_Event instance.
     *
     * @var string
     */
    const onOrderPanelScriptStart = 'onOrderPanelScriptStart';

    /**
     * The onOrderPanelScriptEnd event occurs at the end of orderpanel scripts.
     *
     * The event listener method receives a iMSCP_Events_Response instance.
     *
     * @var string
     */
    const onOrderPanelScriptEnd = 'onOrderPanelScriptEnd';
}
