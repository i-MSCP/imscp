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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package     iMSCP
 * @package     iMSCP_Database
 * @subpackage  Events
 * @copyright   2010-2012 by i-MSCP team
 * @author      Laurent Declercq <ldeclercq@l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Contains all events thrown in the iMSCP_Database component
 *
 * @package     iMSCP
 * @package     iMSCP_Database
 * @subpackage  Events
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
final class iMSCP_Database_Events
{
    /**
     * The onBeforeConnection event occurs before connection to the database is made.
     *
     * The event listener method receives an iMSCP_Database_Events_Database instance.
     *
     * @var string
     */
    const onBeforeConnection = 'onBeforeConnection';

    /**
     * The onAfterConnection event occurs after connection to the database is made.
     *
     * The event listener method receives an iMSCP_Database_Events_Database instance.
     *
     * @var string
     */
    const onAfterConnection = 'onAfterConnection';

    /**
     * The onBeforePrepare event occurs before an SQL statement was prepared for execution.
     *
     * The event listener method receives an iMSCP_Database_Events_Database instance.
     *
     * @var string
     */
    const onBeforePrepare = 'onBeforePrepare';

    /**
     * The onAfterPrepare event occurs after an SQL statement was prepared for execution.
     *
     * The event listener method receives an iMSCP_Database_Events_Statement instance.
     *
     * @var string
     */
    const onAfterPrepare = 'onAfterPrepare';

    /**
     * The onBeforeExecute event occurs before a prepared SQL statement is executed.
     *
     * The event listener method receives an iMSCP_Database_Events_Statement instance.
     *
     * @var string
     */
    const onBeforeExecute = 'onBeforeExecute';

    /**
     * The onAfterExecute event occurs after a prepared SQL statement was executed.
     *
     * The event listener method receives an iMSCP_Database_Events_Statement instance.
     *
     * @var string
     */
    const onAfterExecute = 'onAfterExecute';
}
