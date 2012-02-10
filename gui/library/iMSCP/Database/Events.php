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
 * @package		iMSCP
 * @package		iMSCP_Database
 * @subpackage	Events
 * @copyright	2010-2012 by i-MSCP team
 * @author		Laurent Declercq <ldeclercq@l.declercq@nuxwin.com>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Describes all events triggered in the iMSCP_Database class.
 *
 * @package	 iMSCP
 * @package	 iMSCP_Database
 * @subpackage  Events
 * @author	  Laurent Declercq <l.declercq@nuxwin.com>
 * @version	 0.0.2
 * @TODO Merge this class with iMSCP_Events (all core events in same class is much readable and more easy to found)
 */
final class iMSCP_Database_Events
{
	/**
	 * The onBeforeConnection event is triggered before the connection to the database is made.
	 *
	 * The listeners receive an iMSCP_Events_Event instance with the following argument:
	 *
	 * - context: An iMSCP_Database instance, the context in which the event is triggered
	 *
	 * @var string
	 */
	const onBeforeConnection = 'onBeforeConnection';

	/**
	 * The onAfterConnection event is triggered after the connection to the database is made.
	 *
	 * The listeners receive an iMSCP_Events_Event instance with the following parameter:
	 *
	 * - context: An iMSCP_Database object, the context in which the event is triggered
	 *
	 * @var string
	 */
	const onAfterConnection = 'onAfterConnection';

	/**
	 * The onBeforePrepare event is triggered before an SQL statement was prepared for execution.
	 *
	 * The listeners receive an iMSCP_Database_Events_Database instance with the following parameters:
	 *
	 * - context: An iMSCP_Database object, the context in which the event is triggered
	 * - query: The SQL statement being prepared
	 *
	 * @var string
	 */
	const onBeforePrepare = 'onBeforePrepare';

	/**
	 * The onAfterPrepare event occurs after a SQL statement has been prepared for execution.
	 *
	 * The listeners receive an iMSCP_Database_Events_Statement instance with the following parameters:
	 *
	 *  - context: An iMSCP_Database object, the context in which the event is triggered
	 *  - statement: A PDOStatement object that represent the prepared statement
	 *
	 * @var string
	 */
	const onAfterPrepare = 'onAfterPrepare';

	/**
	 * The onBeforeExecute event is triggered before a prepared SQL statement is executed.
	 *
	 * The listeners receive either :
	 *
	 * 	- an iMSCP_Database_Events_Statement instance with the following parameters:
	 *
	 * 		- context: An iMSCP_Database object, the context in which the event is triggered
	 * 		- statement: A PDOStatement object that represent the prepared statement
	 * Or
	 *
	 * 	- an iMSCP_Database_Events_Database instance with the following arguments:
	 *
	 * 		- context: An iMSCP_Database object, the context in which the event is triggered
	 * 		- query: The SQL statement being prepared and executed (PDO::query())
	 *
	 * @var string
	 */
	const onBeforeExecute = 'onBeforeExecute';

	/**
	 * The onAfterExecute event is triggered after a prepared SQL statement was executed.
	 *
	 * The listeners receive an iMSCP_Database_Events_Statement instance with the following parameters:
	 *
	 * - context: An iMSCP_Database object, the context in which the event is triggered
	 * - statement: The PDOStatement that has been executed
	 *
	 * @var string
	 */
	const onAfterExecute = 'onAfterExecute';
}
