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
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.7
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Class that describes all core events fired in the i-MSCP actions scripts.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Events
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.7
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
	 * The onBeforeRegisterUser event is fired before an user is registered (logged on).
	 *
	 * The event listener method receives an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onBeforeRegisterUser = 'onBeforeRegisterUser';

	/**
	 * The onAfterRegisterUser event is fired after an user is registered (logged on).
	 *
	 * The event listener method receives an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const  onAfterRegisterUser = 'onAfterRegisterUser';

	/**
	 * The onLostPasswordScriptStart event is fired at the very beginning of the LostPassword script.
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

	/**
	 * The onBeforeEditAdminGeneralSettings event is fired before the admin general settings are editied.
	 *
	 * The event listener method receives an iMSCP_Events_Event object.
	 */
	const onBeforeEditAdminGeneralSettings = 'onBeforeEditAdminGeneralSettings';

	/**
	 * The onAfterEditAdminGeneralSettings event is fired after the admin general settings are editied.
	 *
	 * The event listener method receives an iMSCP_Events_Event object.
	 */
	const onAfterEditAdminGeneralSettings = 'onAfterEditAdminGeneralSettings';

	/**
	 * The onBeforeAddUser event is fired before an user is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onBeforeAddUser = 'onBeforeAddUser';

	/**
	 * The onAfterAddUser event is fired after an user is created.
	 *
	 * The event listener method receives an iMSCP_Event object.
	 *
	 * @var string
	 */
	const onAfterAddUser = 'onAfterAddUser';

	/**
	 * The onBeforeEditUser event is fired before an user is edited.
	 *
	 * The event listener method receives the user unique identifier.
	 *
	 * @var string
	 */
	const onBeforeEditUser = 'onBeforeEditUser';

	/**
	 * The onAfterEditUser event is fired after an user is edited.
	 *
	 * The event listener method receives the user unique identifier.
	 *
	 * @var string
	 */
	const onAfterEditUser = 'onAfterEditUser';

	/**
	 * The onBeforeDeleteUser event is fired before an user is deleted.
	 *
	 * The event listener method receives the user unique identifier.
	 *
	 * @var string
	 */
	const onBeforeDeleteUser = 'onBeforeDeleteUser';

	/**
	 * The onAfterDeleteUser event is fired after an user is deleted.
	 *
	 * The event listener method receives the user unique identifier.
	 *
	 * @var string
	 */
	const onAfterDeleteUser = 'onAfterDeleteUser';

	/**
	 * The onBeforeAddDomain event is fired before  a domain is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onBeforeAddDomain = 'onBeforeAddDomain';

	/**
	 * The onAfterAddDomain event is fired after a domain is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onAfterAddDomain = 'onAfterAddDomain';

	/**
	 * The onBeforeEditDomain event is fired before a domain is edited.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onBeforeEditDomain = 'onBeforeEditDomain';

	/**
	 * The onAfterEditDomain event is fired agfter a domain is edited.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onAfterEditDomain = 'onAfterEditDomain';

	/**
	 * The onBeforeDeleteDomain event is fired before a domain is deleted.
	 *
	 * The event listener method receives the domain unique identifier.
	 *
	 * @var string
	 */
	const onBeforeDeleteDomain = 'onBeforeDeleteDomain';

	/**
	 * The onAfterDeleteDomain event is fired after a domain is deleted.
	 *
	 * The event listener method receives a the domain unique identifier.
	 *
	 * @var string
	 */
	const onAfterDeleteDomain = 'onAfterDeleteDomain';

	/**
	 * The onBeforeAddSubdomain event is fired after a subdomain is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onBeforeAddSubdomain = 'onBeforeAddSubdomain';

	/**
	 * The onAfterAddSubdomain event is fired after a subdomain is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onAfterAddSubdomain = 'onAfterAddSubdomain';

	/**
	 * The onBeforeEditSubdomain event is fired after a subdomain is edited.
	 *
	 * @var string
	 */
	//const onBeforeEditSubdomain = 'onBeforeEditSubdomain';

	/**
	 * The onAfterEditSubdomain event is fired after a subdomain is edited.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onAfterEditSubdomain = 'onAfterEditSubdomain';

	/**
	 * The onBeforeDeleteSubdomain event is fired before a subdomain is deleted.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onBeforeDeleteSubdomain = 'onBeforeDeleteSubdomain';

	/**
	 * The onAfterDeleteSubdomain event is fired after a subdomain is delteded.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onAfterDeleteSubdomain = 'onAfterDeleteSubdomain';

	/**
	 * The onBeforeAddDomainAlias event is fired before a domain alias is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onBeforeAddDomainAlias = 'onBeforeAddDomainAlias';

	/**
	 * The onAfterAddDomainAlias event is fired after a domain alias is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onAfterAddDomainAlias = 'onAfterAddDomainAlias';

	/**
	 * The onBeforeEditDomainAlias event is fired before a domain alias is edited.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onBeforeEditDomainAlias = 'onBeforeEditDomainAlias';

	/**
	 * The onAfterEditDomainALias event is fired after a domain alias is edited.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onAfterEditDomainALias = 'onAfterEditDomainAlias';

	/**
	 * The onBeforeDeleteDomainAlias event is fired before a domain alias is deleted.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onBeforeDeleteDomainAlias = 'onBeforeDeleteDomainAlias';

	/**
	 * The onAfterDeleteDomainAlias event is fired after a domain alias is deleted.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onAfterDeleteDomainAlias = 'onBeforeDeleteDomainAlias';

	/**
	 * The onBeforeAddMail event is fired after a mail account is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onBeforeAddMail = 'onBeforeAddMail';

	/**
	 * The onAfterAddMail event is fired after a mail account is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onAfterAddMail = 'onAfterAddMail';

	/**
	 * The onBeforeEditMail event is fired before a mail account is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onBeforeEditMail = 'onBeforeEditMail';

	/**
	 * The onAfterEditMail event is fired after a mail account is edited.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onAfterEditMail = 'onAfterEditMail';

	/**
	 * The onBeforeDeleteMail event is fired before a mail account is deleted.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onBeforeDeleteMail = 'onBeforeDeleteMail';

	/**
	 * The onAfterDeleteMail event is fired after a mail account is deleted.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	//const onAfterDeleteMail = 'onAfterDeleteMail';

	/**
	 * The onBeforeAddFtp event is fired after a Ftp account is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onBeforeAddFtp = 'onBeforeAddFtp';

	/**
	 * The onAfterAddFtp event is fired after a Ftp account is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onAfterAddFtp = 'onAfterAddFtp';

	/**
	 * The onBeforeEditFtp event is fired before a Ftp account is edited.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onBeforeEditFtp = 'onBeforeEditFtp';

	/**
	 * The onAfterEditFtp event is fired after a Ftp account is edited.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onAfterEditFtp = 'onAfterEditFtp';

	/**
	 * The onBeforeDeleteFtp event is fired before a Ftp account is deleted.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onBeforeDeleteFtp = 'onBeforeDeleteFtp';

	/**
	 * The onAfterDeleteFtp event is fired after a Ftp account is deleted.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onAfterDeleteFtp = 'onAfterDeleteFtp';

	// Sql Users and databases

	/**
	 * The onBeforeAddSqlUser event is fired before a Sql user is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onBeforeAddSqlUser = 'onBeforeAddSqlUser';

	/**
	 * The onAfterAddSqlUser event is fired after a Sql user is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onAfterAddSqlUser = 'onAfterAddSqlUser';

	/**
	 * The onBeforeEditSqlUser event is fired before a Sql user is edited.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onBeforeEditSqlUser = 'onBeforeEditSqlUser';

	/**
	 * The onAfterEditSqlUser event is fired after a Sql user is edited.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onAfterEditSqlUser = 'onAfterEditSqlUser';

	/**
	 * The onBeforeDeleteSqlUser event is fired before a Sql user is deleted.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onBeforeDeleteSqlUser = 'onBeforeDeleteSqlUser';

	/**
	 * The onAfterDeleteSqlUser event is fired after a Sql user is deleted.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onAfterDeleteSqlUser = 'onAfterDeleteSqlUser';

	/**
	 * The onBeforeAddSqlDb event is fired before a Sql database is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onBeforeAddSqlDb = 'onBeforeAddSqlDb';

	/**
	 * The onAfterAddSqlDb event is fired after a Sql database is created.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onAfterAddSqlDb = 'onAfterAddSqlDb';

	/**
	 * The onBeforeDeleteSqlDb event is fired before a Sql database is deleted.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onBeforeDeleteSqlDb = 'onBeforeDeleteSqlDb';

	/**
	 * The onAfterDeleteSqlDb event is fired after a Sql database is deleted.
	 *
	 * The event listener method receives a iMSCP_Event object.
	 *
	 * @var string
	 */
	const onAfterDeleteSqlDb = 'onAfterSqlDb';
}
