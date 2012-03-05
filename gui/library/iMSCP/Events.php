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
 * @version		0.0.13
 */
class iMSCP_Events
{
	/**
	 * The onAfterInitialize event is triggered after i-MSCP has been fully initialized.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - context: An iMSCP_Initializer object, the context in which the event is triggered
	 *
	 * @var string
	 */
	const onAfterInitialize = 'onAfterInitialize';

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
	 *  - templateEngine: An iMSCP_pTemplate object
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
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
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
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
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
	 *   - context: An iMSCP_Exception_Writer_Browser object, the context in which the event is triggered
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
	 * - identity: A stdClass object that contains the user' identity data
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
	 *
	 * @var string
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
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @var string
	 */
	const onAfterAddUser = 'onAfterAddUser';

	/**
	 * The onBeforeEditUser event is triggered before an user is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - userId: An integer representing the ID of user being edited.
	 *
	 * @var string
	 */
	const onBeforeEditUser = 'onBeforeEditUser';

	/**
	 * The onAfterEditUser event is triggered after an user is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - userId: An integer representing the ID of user that has been edited
	 *
	 * @var string
	 */
	const onAfterEditUser = 'onAfterEditUser';

	/**
	 * The onBeforeDeleteUser event is triggered before an user is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - userId: An integer representing the ID of user being deleted
	 *
	 * @var string
	 */
	const onBeforeDeleteUser = 'onBeforeDeleteUser';

	/**
	 * The onAfterDeleteUser event is triggered after an user is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - userId: An integer representing the ID of user that has been deleted
	 *
	 * @var string
	 */
	const onAfterDeleteUser = 'onAfterDeleteUser';

	/**
	 * The onBeforeDeleteDomain event is triggered before a domain is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - domainId: An integer representing the ID of domain being deleted
	 *
	 * @var string
	 */
	const onBeforeDeleteDomain = 'onBeforeDeleteDomain';

	/**
	 * The onAfterDeleteDomain event is triggered after a domain is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - userId: an integer representing the ID of domain that has been deleted
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
	 *  - ftpId: An integer representing the ID of Ftp account being edited
	 *
	 * @var string
	 */
	const onBeforeEditFtp = 'onBeforeEditFtp';

	/**
	 * The onAfterEditFtp event is triggered after a Ftp account is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - ftpId: An integer representing the ID of Ftp account that has been edited
	 *
	 * @var string
	 */
	const onAfterEditFtp = 'onAfterEditFtp';

	/**
	 * The onBeforeDeleteFtp event is triggered before a Ftp account is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - ftpId: An integer representing the ID of Ftp account being deleted
	 *
	 * @var string
	 */
	const onBeforeDeleteFtp = 'onBeforeDeleteFtp';

	/**
	 * The onAfterDeleteFtp event is triggered after a Ftp account is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - ftpId: An integer reprensenting the ID of Ftp account that has been deleted
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
	 *  - sqlUserId: An integer representing the ID of Sql user being edited
	 *
	 * @var string
	 */
	const onBeforeEditSqlUser = 'onBeforeEditSqlUser';

	/**
	 * The onAfterEditSqlUser event is triggered after a Sql user is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - sqlUserId: An integer representing the ID of Sql user that has been edited
	 *
	 * @var string
	 */
	const onAfterEditSqlUser = 'onAfterEditSqlUser';

	/**
	 * The onBeforeDeleteSqlUser event is triggered before a Sql user is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - sqlUserId: An integer representing the ID of Sql user being deleted
	 *
	 * @var string
	 */
	const onBeforeDeleteSqlUser = 'onBeforeDeleteSqlUser';

	/**
	 * The onAfterDeleteSqlUser event is triggered after a Sql user is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - sqlUserId: An integer representing the ID of Sql user that has been deleted
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
	 *  - sqlDbId: An integer representing the ID of Sql database being deleted
	 *
	 * @var string
	 */
	const onBeforeDeleteSqlDb = 'onBeforeDeleteSqlDb';

	/**
	 * The onAfterDeleteSqlDb event is triggered after a Sql database is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - sqlDbId: An integer representing the ID of Sql database that has been deleted
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
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *  - pluginName: Name of the plugin being activated
	 *
	 * @var string
	 */
	const onBeforeActivatePlugin = 'onBeforeActivatePlugin';

	/**
	 * The onAfterActivatePlugin event is triggered after the plugin list is updated.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *  - pluginName: Name of the plugin that has been activated
	 *
	 * @var string
	 */
	const onAfterActivatePlugin = 'onAfterActivatePlugin';

	/**
	 * The onBeforeDeactivatePlugin event is triggered before a plugin is deactivated.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *  - pluginName: Name of the plugin being deactivated
	 *
	 * @var string
	 */
	const onBeforeDeactivatePlugin = 'onBeforeDeactivatePlugin';

	/**
	 * The onAfterDeactivatePlugin event is triggered after a plugin is deactivated.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *  - pluginName: Name of the plugin that has been deactivated
	 *
	 * @var string
	 */
	const onAfterDeactivatePlugin = 'onAfterDeactivatePlugin';

	/**
	 * The onBeforeProtectPlugin event is triggered before a plugin is protected.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *  - pluginName: Name of the plugin being protected
	 *
	 * @var string
	 */
	const onBeforeProtectPlugin = 'onBeforeProtectPlugin';

	/**
	 * The onAfterProtectPlugin event is triggered after a plugin is protected.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 *  - pluginManager: An iMSCP_Plugin_Manager instance
	 *  - pluginName: Name of the plugin that has been protected
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
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - domainName: A string representing the name of the domain being created
	 *  - createdBy: An integer representing the ID of the reseller that adds the domain
	 *  - customerId: An integer representitng the ID of the customer for which the domain is added
	 *  - customerEmail: A string representing the email of the customer for which the domain is added
	 *
	 * @var string
	 */
	const onBeforeAddDomain = 'onBeforeAddDomain';

	/**
	 * The onAfterAddDomain event is triggered after a domain is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - domainName: A string representing the name of a the domain that has been added
	 *  - createdBy: An integer representing the ID of the reseller that added the domain
	 *  - customerId: An integer representing the ID of the customer for which the domain has been added
	 *  - customerEmail: A string representing the email of customer for which the domain has been added
	 *  - domainId: An integer representing the ID of the domain that has been added
	 *
	 * @var string
	 */
	const onAfterAddDomain = 'onAfterAddDomain';

	/**
	 * The onBeforeEditDomain event is triggered before a domain is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - domainId: An integer representing the ID of the domain being edited
	 *
	 * @var string
	 */
	const onBeforeEditDomain = 'onBeforeEditDomain';

	/**
	 * The onAfterEditDomain event is triggered agfter a domain is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - domainId: An integer reprensenting the ID of the domain that has been edited
	 *
	 * @var string
	 */
	const onAfterEditDomain = 'onAfterEditDomain';

	/**
	 * The onBeforeAddSubdomain event is triggered after a subdomain is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - subdomainName: A string representing the name of the subdomain being added
	 *  - domainId: An integer representing the ID of the parent domain
	 *  - customerId: An integer representing the ID of the customer for which the subdomain is added
	 *
	 * @var string
	 */
	const onBeforeAddSubdomain = 'onBeforeAddSubdomain';

	/**
	 * The onAfterAddSubdomain event is triggered after a subdomain is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - subdomainName: A string representing the name of the subdomain that has been added
	 *  - domainId: An integer representing the ID of the parent domain
	 *  - customerId: An integer representing the ID of the customer for wich the subdomain has been added
	 *  - subdomainId: An integer representing the ID of thesubdomain that has been added
	 *
	 * @var string
	 */
	const onAfterAddSubdomain = 'onAfterAddSubdomain';

	/**
	 * The onBeforeEditSubdomain event is triggered after a subdomain is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - subdomainId: An integer representing the ID of the subdomain being edited
	 *
	 * @var string
	 */
	const onBeforeEditSubdomain = 'onBeforeEditSubdomain';

	/**
	 * The onAfterEditSubdomain event is triggered after a subdomain is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - subdomainId: An integer representing the ID of the subdomain that has been edited
	 *
	 * @var string
	 */
	const onAfterEditSubdomain = 'onAfterEditSubdomain';

	/**
	 * The onBeforeDeleteSubdomain event is triggered before a subdomain is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - subdomainId: An integer representing the ID of the subdomain being deleted
	 *
	 * @var string
	 */
	const onBeforeDeleteSubdomain = 'onBeforeDeleteSubdomain';

	/**
	 * The onAfterDeleteSubdomain event is triggered after a subdomain is delteded.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - subdomainId: An integer representing the ID of the subdomain that has been deleted
	 *
	 * @var string
	 */
	const onAfterDeleteSubdomain = 'onAfterDeleteSubdomain';

	/**
	 * The onBeforeAddDomainAlias event is triggered before a domain alias is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - domainId: An integer representing the ID of the parent domain
	 *  - domainAliasName: A string representing the name of the domain alias being added
	 *
	 * @var string
	 */
	const onBeforeAddDomainAlias = 'onBeforeAddDomainAlias';

	/**
	 * The onAfterAddDomainAlias event is triggered after a domain alias is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - domainId: An integer representing the ID of the parent domain
	 *  - domainAliasName: A string representing the name of the domain alias that has been added
	 *  - domainAliasId: An integer representing the ID of the domain alias that has been added
	 *
	 * @var string
	 */
	const onAfterAddDomainAlias = 'onAfterAddDomainAlias';

	/**
	 * The onBeforeEditDomainAlias event is triggered before a domain alias is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - domainAliasId: An integer representing the ID of the domain alias being edited
	 *
	 * @var string
	 */
	const onBeforeEditDomainAlias = 'onBeforeEditDomainAlias';

	/**
	 * The onAfterEditDomainALias event is triggered after a domain alias is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - domainAliasId: An integer representing the ID of the domain alias that has been edited
	 *
	 * @var string
	 */
	const onAfterEditDomainALias = 'onAfterEditDomainAlias';

	/**
	 * The onBeforeDeleteDomainAlias event is triggered before a domain alias is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - domainAliasId: An integer representing the  ID of the domain alias being deleted
	 *
	 * @var string
	 */
	const onBeforeDeleteDomainAlias = 'onBeforeDeleteDomainAlias';

	/**
	 * The onAfterDeleteDomainAlias event is triggered after a domain alias is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - domainAliasId: An integer representing the ID of the domain alias that has been deleted
	 *
	 * @var string
	 */
	const onAfterDeleteDomainAlias = 'onAfterDeleteDomainAlias';

	/**
	 * The onBeforeAddMail event is triggered after a mail account is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - mailUsername: A string representing the local part of the email account being added
	 *  - mailAddress: A string representing the complete email address of the mail account being added
	 *
	 * @var string
	 */
	const onBeforeAddMail = 'onBeforeAddMail';

	/**
	 * The onAfterAddMail event is triggered after a mail account is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 *  - mailUsername: A string representing the local part of the email account that has been added
	 *  - mailAddress: A string representing the complete address of the mail account that has been added
	 *  - mailId: An integer representing the ID of the email account that has been added
	 *
	 * @var string
	 */
	const onAfterAddMail = 'onAfterAddMail';

	/**
	 * The onBeforeEditMail event is triggered before a mail account is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - mailId: An integer representing the ID of the mail account being edited
	 *
	 * @var string
	 */
	const onBeforeEditMail = 'onBeforeEditMail';

	/**
	 * The onAfterEditMail event is triggered after a mail account is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - mailId: An integer representing the ID of the mail account that has been edited
	 *
	 * @var string
	 */
	const onAfterEditMail = 'onAfterEditMail';

	/**
	 * The onBeforeDeleteMail event is triggered before a mail account is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - mailId: An integer representing the ID of the mail account being deleted
	 *
	 * @var string
	 */
	const onBeforeDeleteMail = 'onBeforeDeleteMail';

	/**
	 * The onAfterDeleteMail event is triggered after a mail account is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - mailId: An integer representing the ID of the mail account that has been deleted
	 *
	 * @var string
	 */
	const onAfterDeleteMail = 'onAfterDeleteMail';

	/**
	 * The onBeforeDatabaseConnection event is triggered before the connection to the database is made.
	 *
	 * The listeners receive an iMSCP_Events_Event instance with the following parameter:
	 *
	 * - context: An iMSCP_Database instance, the context in which the event is triggered
	 *
	 * @var string
	 */
	const onBeforeDatabaseConnection = 'onBeforeDatabaseConnection';

	/**
	 * The onAfterDatabaseConnection event is triggered after the connection to the database is made.
	 *
	 * The listeners receive an iMSCP_Events_Event instance with the following parameter:
	 *
	 * - context: An iMSCP_Database object, the context in which the event is triggered
	 *
	 * @var string
	 */
	const onAfterDatabaseConnection = 'onAfterDatabaseConnection';

	/**
	 * The onBeforeQueryPrepare event is triggered before an SQL statement is prepared for execution.
	 *
	 * The listeners receive an iMSCP_Database_Events_Database instance with the following parameters:
	 *
	 * - context: An iMSCP_Database object, the context in which the event is triggered
	 * - query: The SQL statement being prepared
	 *
	 * @var string
	 */
	const onBeforeQueryPrepare = 'onBeforeQueryPrepare';

	/**
	 * The onAfterQueryPrepare event occurs after a SQL statement has been prepared for execution.
	 *
	 * The listeners receive an iMSCP_Database_Events_Statement instance with the following parameters:
	 *
	 *  - context: An iMSCP_Database object, the context in which the event is triggered
	 *  - statement: A PDOStatement object that represent the prepared statement
	 *
	 * @var string
	 */
	const onAfterQueryPrepare = 'onAfterQueryPrepare';

	/**
	 * The onBeforeQueryExecute event is triggered before a prepared SQL statement is executed.
	 *
	 * The listeners receive either :
	 *
	 *	 - an iMSCP_Database_Events_Statement instance with the following parameters:
	 *
	 *		 - context: An iMSCP_Database object, the context in which the event is triggered
	 *		 - statement: A PDOStatement object that represent the prepared statement
	 * Or
	 *
	 *	 - an iMSCP_Database_Events_Database instance with the following arguments:
	 *
	 *		 - context: An iMSCP_Database object, the context in which the event is triggered
	 *		 - query: The SQL statement being prepared and executed (PDO::query())
	 *
	 * @var string
	 */
	const onBeforeQueryExecute = 'onBeforeQueryExecute';

	/**
	 * The onAfterQueryExecute event is triggered after a prepared SQL statement has been executed.
	 *
	 * The listeners receive an iMSCP_Database_Events_Statement instance with the following parameters:
	 *
	 * - context: An iMSCP_Database object, the context in which the event is triggered
	 * - statement: The PDOStatement that has been executed
	 *
	 * @var string
	 */
	const onAfterQueryExecute = 'onAfterQueryExecute';

	/**
	 * The onBeforeAssembleTemplateFiles event is triggered before the first parent template is loaded.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters
	 *
	 *  - context: An iMSCP_pTemplate object, the context in which the event is triggered
	 *  - templatePath: The filepath of the template being loaded
	 *
	 * @var string
	 */
	const onBeforeAssembleTemplateFiles = 'onBeforeAssembleTemplateFiles';

	/**
	 * The onAfterAssembleTemplateFiles event is triggered after the first parent template is loaded.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - context: An iMSCP_pTemplate object, the context in which the event is triggered
	 * - templateContent: The template content as a string
	 *
	 * @var string
	 */
	const onAfterAssembleTemplateFiles = 'onBeforeAssembleTemplateFiles';

	/**
	 * The onBeforeLoadTemplateFile event is triggered before a template is loaded.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters
	 *
	 *  - context: An iMSCP_pTemplate object, the context in which the event is triggered
	 *  - templatePath: The filepath of the template being loaded
	 *
	 * @var string
	 */
	const onBeforeLoadTemplateFile = 'onBeforeLoadTemplateFile';

	/**
	 * The onAfterLoadTemplateFile event is triggered after the loading of a template file.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - context: An iMSCP_pTemplate object, the context in which the event is triggered
	 * - templateContent: The template content as a string
	 *
	 * @var string
	 */
	const onAfterLoadTemplateFile = 'onAfterLoadTemplateFile';

	/**
	 * The onBeforeGenerateNavigation event is triggeed before the navigation is generated.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - templateEngine: An iMSCP_pTemplate object
	 *
	 * @var string
	 */
	const onBeforeGenerateNavigation = 'onBeforeGenerateNavigation';


	/**
	 * The onAfterGenerateNavigation event is triggered after the navigation is generated.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - templateEngine: An iMSCP_pTemplate object
	 *
	 * @var string
	 *
	 */
	const onAfterGenerateNavigation = 'onAfterGenerateNavigation';
}
