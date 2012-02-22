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
 * @package		iMSCP_Events
 * @copyright	2010-2012 by i-MSCP team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Describes all events triggered in the iMSCP core code.
 *
 * @category	iMSCP
 * @package		iMSCP_Events
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.9
 */
class iMSCP_Events
{
	/**
	 * The 'onRestRequest' event is triggered in the rest.php action script when the 'X-Requested-With" header contains
	 * "RestHttpRequest".
	 *
	 * The listener receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - request: The request data (for now, it's a copy of $_REQUEST)
	 *
	 * @var string
	 */
	const onRestRequest = 'onRestRequest';
	/**
	 * The onLoginScriptStart event is triggered at the very beginning of Login script.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onLoginScriptStart = 'onLoginScriptStart';

	/**
	 * The onLoginScriptEnd event is triggered at the end of Login script.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * templateEngine: An iMSCP_pTemplate object
	 *
	 * @var string
	 */
	const onLoginScriptEnd = 'onLoginScriptEnd';

	/**
	 * The onLostPasswordScriptStart event is triggered at the very beginning of the LostPassword script.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onLostPasswordScriptStart = 'onLostPasswordScriptStart';

	/**
	 * The onLostPasswordScriptEnd event is triggered at the end of the LostPassword script.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - templateEngine: An iMSCP_pTemplate object
	 *
	 * @var string
	 */
	const onLostPasswordScriptEnd = 'onLostPasswordScriptEnd';

	/**
	 * The onAdminScriptStart event is triggered at the very beginning of admin scripts.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onAdminScriptStart = 'onAdminScriptStart';

	/**
	 * The onAdminScriptEnd event is triggered at the end of admin scripts.
	 *
	 * The listeners receive iMSCP_Events_Event object with the following parameter:
	 *
	 *  - templateEngine: An iMSCP_pTemplate object
	 *
	 * @var string
	 */
	const onAdminScriptEnd = 'onAdminScriptEnd';

	/**
	 * The onResellerScriptStart event is triggered at the very beginning of reseller scripts.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onResellerScriptStart = 'onResellerScriptStart';

	/**
	 * The onResellerScriptEnd event is triggered at the end of reseller scripts.
	 *
	 * The listeners receive iMSCP_Events_Event object with the following parameter:
	 *
	 *  - templateEngine: An iMSCP_pTemplate object
	 *
	 * @var string
	 */
	const onResellerScriptEnd = 'onResellerScriptEnd';

	/**
	 * The onClientScriptStart event is triggered at the very beginning of client scripts.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onClientScriptStart = 'onClientScriptStart';

	/**
	 * The onClientScriptEnd event is triggered at the end of client scripts.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - templateEngine: An iMSCP_pTemplate object
	 *
	 * @var string
	 */
	const onClientScriptEnd = 'onClientScriptEnd';

	/**
	 * The onOrderPanelScriptStart is triggered occurs at the very beginning of orderpanel scripts.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onOrderPanelScriptStart = 'onOrderPanelScriptStart';

	/**
	 * The onOrderPanelScriptEnd event is triggered at the end of orderpanel scripts.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - templateEngine: An iMSCP_pTemplate object
	 *
	 * @var string
	 */
	const onOrderPanelScriptEnd = 'onOrderPanelScriptEnd';

	/**
	 * The onExceptioToBrowserStart event is triggered before of exception browser write processs.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 *  - context: An iMSCP_Exception_Writer_Browser object, the context in which the event is triggered
	 *
	 * @var string
	 */
	const onExceptionToBrowserStart = 'onExceptionToBrowserStart';

	/**
	 * The onExceptionToBrowserEnd event is triggered at the end of exception browser write process.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - context: An iMSCP_Exception_Writer_Browser object, the context in which the event is triggered
	 *  - templateEngine: An iMSCP_pTemplate object
	 *
	 * @var string
	 */
	const onExceptionToBrowserEnd = 'onExceptionToBrowserEnd';


	/**
	 * The onBeforeAuthentication event is triggered before the authentication process.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - context: An iMSCP_Authentication object, the context in which the event is triggered
	 */
	const onBeforeAuthentication = 'onBeforeAuthentication';

	/**
	 * The onBeforeAuthentication event is triggered after the authentication process.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - context: An iMSCP_Authentication object, the context in which the event is triggered
	 * - authResult: An iMSCP_Authentication_Result object, an object that encapsulates the authentication result
	 *
	 * @var string
	 */
	const onAfterAuthentication = 'onAfterAuthentication';

	/**
	 * The onBeforeSetIdentity event is triggered before an user identity is set (logged on).
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - context: An iMSCP_Authentication object, the context in which the event is triggered
	 * - identity: A stdClass object that represent user identity data
	 *
	 * @var string
	 */
	const onBeforeSetIdentity = 'onBeforeSetIdentity';

	/**
	 * The onAfterSetIdentity event is triggered after an user identity is set (logged on).
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - context: An iMSCP_Authentication object, the context in which the event is triggered
	 *
	 * @var string
	 */
	const  onAfterSetIdentity = 'onAfterSetIdentity';

	/**
	 * The onBeforeUnsetIdentity event is triggered before an user identity is unset (logout).
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - context: An iMSCP_Authentication object, the context in which the event is triggered
	 *
	 * @var string
	 */
	const onBeforeUnsetIdentity = 'onBeforeUnsetIdentity';

	/**
	 * The onAfterUnsetIdentity event is triggered after an user identity is unset (logged on).
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - context: An iMSCP_Authentication object, the context in which the event is triggered
	 *
	 * @var string
	 */
	const  onAfterUnsetIdentity = 'onAfterUnsetIdentity';

	/**
	 * The onBeforeEditAdminGeneralSettings event is triggered before the admin general settings are editied.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 */
	const onBeforeEditAdminGeneralSettings = 'onBeforeEditAdminGeneralSettings';

	/**
	 * The onAfterEditAdminGeneralSettings event is triggered after the admin general settings are editied.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 */
	const onAfterEditAdminGeneralSettings = 'onAfterEditAdminGeneralSettings';

	/**
	 * The onBeforeAddUser event is triggered before an user is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onBeforeAddUser = 'onBeforeAddUser';

	/**
	 * The onAfterAddUser event is triggered after an user is created.
	 *
	 * The listeners receive an iMSCP_Events_Event.
	 *
	 * @var string
	 */
	const onAfterAddUser = 'onAfterAddUser';

	/**
	 * The onBeforeEditUser event is triggered before an user is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - userId: The user id being edited.
	 *
	 * @var string
	 */
	const onBeforeEditUser = 'onBeforeEditUser';

	/**
	 * The onAfterEditUser event is triggered after an user is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - userId: The user id that has been edited.
	 *
	 * @var string
	 */
	const onAfterEditUser = 'onAfterEditUser';

	/**
	 * The onBeforeDeleteUser event is triggered before an user is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - userId: The user id being deleted.
	 *
	 * @var string
	 */
	const onBeforeDeleteUser = 'onBeforeDeleteUser';

	/**
	 * The onAfterDeleteUser event is triggered after an user is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - userId: The user id that has been deleted.
	 *
	 * @var string
	 */
	const onAfterDeleteUser = 'onAfterDeleteUser';

	/**
	 * The onBeforeDeleteDomain event is triggered before a domain is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - domainId: The domain id being deleted.
	 *
	 * @var string
	 */
	const onBeforeDeleteDomain = 'onBeforeDeleteDomain';

	/**
	 * The onAfterDeleteDomain event is triggered after a domain is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - userId: The domain id that has been deleted.
	 *
	 * @var string
	 */
	const onAfterDeleteDomain = 'onAfterDeleteDomain';

	/**
	 * The onBeforeAddFtp event is triggered after a Ftp account is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onBeforeAddFtp = 'onBeforeAddFtp';

	/**
	 * The onAfterAddFtp event is triggered after a Ftp account is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onAfterAddFtp = 'onAfterAddFtp';

	/**
	 * The onBeforeEditFtp event is triggered before a Ftp account is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - ftpId: The Ftp account id being edited.
	 *
	 * @var string
	 */
	const onBeforeEditFtp = 'onBeforeEditFtp';

	/**
	 * The onAfterEditFtp event is triggered after a Ftp account is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - ftpId: The Ftp account id that has been edited.
	 *
	 * @var string
	 */
	const onAfterEditFtp = 'onAfterEditFtp';

	/**
	 * The onBeforeDeleteFtp event is triggered before a Ftp account is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - ftpId: The Ftp account id being deleted.
	 *
	 * @var string
	 */
	const onBeforeDeleteFtp = 'onBeforeDeleteFtp';

	/**
	 * The onAfterDeleteFtp event is triggered after a Ftp account is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - ftpId: The Ftp account id that has been deleted.
	 *
	 * @var string
	 */
	const onAfterDeleteFtp = 'onAfterDeleteFtp';

	/**
	 * The onBeforeAddSqlUser event is triggered before a Sql user is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onBeforeAddSqlUser = 'onBeforeAddSqlUser';

	/**
	 * The onAfterAddSqlUser event is triggered after a Sql user is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onAfterAddSqlUser = 'onAfterAddSqlUser';

	/**
	 * The onBeforeEditSqlUser event is triggered before a Sql user is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - sqlUserId: The Sql user id being edited.
	 *
	 * @var string
	 */
	const onBeforeEditSqlUser = 'onBeforeEditSqlUser';

	/**
	 * The onAfterEditSqlUser event is triggered after a Sql user is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - sqlUserId: The Sql user id that has been edited.
	 *
	 * @var string
	 */
	const onAfterEditSqlUser = 'onAfterEditSqlUser';

	/**
	 * The onBeforeDeleteSqlUser event is triggered before a Sql user is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - sqlUserId: The Sql user id being deleted.
	 *
	 * @var string
	 */
	const onBeforeDeleteSqlUser = 'onBeforeDeleteSqlUser';

	/**
	 * The onAfterDeleteSqlUser event is triggered after a Sql user is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - sqlUserId: The Sql user id that has been deleted.
	 *
	 * @var string
	 */
	const onAfterDeleteSqlUser = 'onAfterDeleteSqlUser';

	/**
	 * The onBeforeAddSqlDb event is triggered before a Sql database is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onBeforeAddSqlDb = 'onBeforeAddSqlDb';

	/**
	 * The onAfterAddSqlDb event is triggered after a Sql database is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onAfterAddSqlDb = 'onAfterAddSqlDb';

	/**
	 * The onBeforeDeleteSqlDb event is triggered before a Sql database is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - sqlDbId: The Sql user id being deleted.
	 *
	 * @var string
	 */
	const onBeforeDeleteSqlDb = 'onBeforeDeleteSqlDb';

	/**
	 * The onAfterDeleteSqlDb event is triggered after a Sql database is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - sqlDbId: The Sql user id that has been deleted.
	 *
	 * @var string
	 */
	const onAfterDeleteSqlDb = 'onAfterSqlDb';

	/**
	 * The onAfterUpdatePluginList event is triggered before the plugin list is updated.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *
	 * @var string
	 */
	const onBeforeUpdatePluginList = 'onBeforeUpdatePluginList';

	/**
	 * The onAfterUpdatePluginList event is triggered before the plugin list is updated.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *
	 * @var string
	 */
	const onAfterUpdatePluginList = 'onAfterUpdatePLuginList';


	/**
	 * The onAfterUpdatePluginList event is triggered before the plugin list is updated.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *
	 * @var string
	 */
	const onBeforeActivatePlugin = 'onBeforeActivatePlugin';

	/**
	 * The onAfterActivatePlugin event is triggered after the plugin list is updated.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *
	 * @var string
	 */
	const onAfterActivatePlugin = 'onAfterActivatePlugin';

	/**
	 * The onBeforeDeactivatePlugin event is triggered before a plugin is deactivated.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *
	 * @var string
	 */
	const onBeforeDeactivatePlugin = 'onBeforeDeactivatePlugin';

	/**
	 * The onAfterDeactivatePlugin event is triggered after a plugin is deactivated.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *
	 * @var string
	 */
	const onAfterDeactivatePlugin = 'onAfterDeactivatePlugin';

	/**
	 * The onBeforeProtectPlugin event is triggered before a plugin is protected.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *
	 * @var string
	 */
	const onBeforeProtectPlugin = 'onBeforeProtectPlugin';

	/**
	 * The onAfterProtectPlugin event is triggered after a plugin is protected.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *
	 * @var string
	 */
	const onAfterProtectPlugin = 'onAfterProtectPlugin';

	/**
	 * The onBeforeBulkAction event is triggered before a plugin bulk action.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *
	 * @var string
	 */
	const onBeforeBulkAction = 'onBeforeBulkAction';

	/**
	 * The onAfterBulkAction event is triggered after a plugin bulk action.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *
	 * @var string
	 */
	const onAfterBulkAction = 'onAfterBulkAction';

	/**
	 * The onBeforeAddDomain event is triggered before a domain is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - domain_name: Name of a domain to create
	 *  - created_by: Id of a reseller who is adding domain
	 *  - customer_id: Id of owner of the domain
	 *  - email: email of an owner
	 *
	 * @var string
	 */
	const onBeforeAddDomain = 'onBeforeAddDomain';

	/**
	 * The onAfterAddDomain event is triggered after a domain is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - domain_name: Name of a domain to create
	 *  - created_by: Id of a reseller who is adding domain
	 *  - customer_id: Id of owner of the domain
	 *  - email: email of an owner
	 *  - domain_id: id of created database record
	 *
	 * @var string
	 */
	const onAfterAddDomain = 'onAfterAddDomain';

	/**
	 * The onBeforeEditDomain event is triggered before a domain is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - domain_id: Id of a domain to edit
	 *
	 * @var string
	 */
	const onBeforeEditDomain = 'onBeforeEditDomain';

	/**
	 * The onAfterEditDomain event is triggered agfter a domain is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - domain_id: Id of a domain to edit
	 *
	 * @var string
	 */
	const onAfterEditDomain = 'onAfterEditDomain';

	/**
	 * The onBeforeAddSubdomain event is triggered after a subdomain is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - subdomain_name: name of a subdomain to add
	 *  - domain_id: parent domain id
	 *  - user_id: owner id
	 *
	 * @var string
	 */
	const onBeforeAddSubdomain = 'onBeforeAddSubdomain';

	/**
	 * The onAfterAddSubdomain event is triggered after a subdomain is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - subdomain_name: name of a subdomain to add
	 *  - domain_id: parent domain id
	 *  - user_id: owner id
	 *  - subdomain_id: id of a created database record
	 *
	 * @var string
	 */
	const onAfterAddSubdomain = 'onAfterAddSubdomain';

	/**
	 * The onBeforeEditSubdomain event is triggered after a subdomain is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - subdomain_id: id of a subdomain
	 *
	 * @var string
	 */
	const onBeforeEditSubdomain = 'onBeforeEditSubdomain';

	/**
	 * The onAfterEditSubdomain event is triggered after a subdomain is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - subdomain_id: id of a subdomain
	 *
	 * @var string
	 */
	const onAfterEditSubdomain = 'onAfterEditSubdomain';

	/**
	 * The onBeforeDeleteSubdomain event is triggered before a subdomain is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - subdomain_id: id of a subdomain
	 *
	 * @var string
	 */
	const onBeforeDeleteSubdomain = 'onBeforeDeleteSubdomain';

	/**
	 * The onAfterDeleteSubdomain event is triggered after a subdomain is delteded.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - subdomain_id: id of a subdomain
	 *
	 * @var string
	 */
	const onAfterDeleteSubdomain = 'onAfterDeleteSubdomain';

	/**
	 * The onBeforeAddDomainAlias event is triggered before a domain alias is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - domain_id: id of a parent domain
	 *  - alias_name: name of alias to add
	 *
	 * @var string
	 */
	const onBeforeAddDomainAlias = 'onBeforeAddDomainAlias';

	/**
	 * The onAfterAddDomainAlias event is triggered after a domain alias is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - domain_id: id of a parent domain
	 *  - alias_name: name of alias to add
	 *
	 * @var string
	 */
	const onAfterAddDomainAlias = 'onAfterAddDomainAlias';

	/**
	 * The onBeforeEditDomainAlias event is triggered before a domain alias is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - alias_id: id of an alias to edit
	 *
	 * @var string
	 */
	const onBeforeEditDomainAlias = 'onBeforeEditDomainAlias';

	/**
	 * The onAfterEditDomainALias event is triggered after a domain alias is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - alias_id: id of a created database record
	 *
	 * @var string
	 */
	const onAfterEditDomainALias = 'onAfterEditDomainAlias';

	/**
	 * The onBeforeDeleteDomainAlias event is triggered before a domain alias is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - alias_id: id of alias to delete
	 *
	 * @var string
	 */
	const onBeforeDeleteDomainAlias = 'onBeforeDeleteDomainAlias';

	/**
	 * The onAfterDeleteDomainAlias event is triggered after a domain alias is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - alias_id: id of alias to delete
	 *
	 * @var string
	 */
	const onAfterDeleteDomainAlias = 'onAfterDeleteDomainAlias';

	/**
	 * The onBeforeAddMail event is triggered after a mail account is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - mail_acc: email login (before @)
	 *  - mail_addr: email (with domain included)
	 *
	 * @var string
	 */
	const onBeforeAddMail = 'onBeforeAddMail';

	/**
	 * The onAfterAddMail event is triggered after a mail account is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - mail_acc: email login (before @)
	 *  - mail_addr: email (with domain included)
	 *  - mail_id: id of a created database record
	 *
	 * @var string
	 */
	const onAfterAddMail = 'onAfterAddMail';

	/**
	 * The onBeforeEditMail event is triggered before a mail account is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - mail_id: id of a mail to edit
	 *
	 * @var string
	 */
	const onBeforeEditMail = 'onBeforeEditMail';

	/**
	 * The onAfterEditMail event is triggered after a mail account is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - mail_id: id of a mail which was edited
	 *
	 * @var string
	 */
	const onAfterEditMail = 'onAfterEditMail';

	/**
	 * The onBeforeDeleteMail event is triggered before a mail account is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - mail_id: id of a mail to delete
	 *
	 * @var string
	 */
	const onBeforeDeleteMail = 'onBeforeDeleteMail';

	/**
	 * The onAfterDeleteMail event is triggered after a mail account is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - mail_id: id of a mail which was deleted
	 *
	 * @var string
	 */
	const onAfterDeleteMail = 'onAfterDeleteMail';

}
