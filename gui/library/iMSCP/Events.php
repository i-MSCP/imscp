<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP team
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
 * @package     iMSCP_Events
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Describes all events triggered in the iMSCP core code.
 *
 * @category    iMSCP
 * @package     iMSCP_Events
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
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
	 * @const string
	 */
	const onAfterInitialize = 'onAfterInitialize';

	/**
	 * The onLoginScriptStart event is triggered at the very beginning of Login script.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @const string
	 */
	const onLoginScriptStart = 'onLoginScriptStart';

	/**
	 * The onLoginScriptEnd event is triggered at the end of Login script.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - templateEngine: An iMSCP_pTemplate object
	 *
	 * @const string
	 */
	const onLoginScriptEnd = 'onLoginScriptEnd';

	/**
	 * The onLostPasswordScriptStart event is triggered at the very beginning of the LostPassword script.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @const string
	 */
	const onLostPasswordScriptStart = 'onLostPasswordScriptStart';

	/**
	 * The onLostPasswordScriptEnd event is triggered at the end of the LostPassword script.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - templateEngine: An iMSCP_pTemplate object
	 *
	 * @const string
	 */
	const onLostPasswordScriptEnd = 'onLostPasswordScriptEnd';

	/**
	 * The onAdminScriptStart event is triggered at the very beginning of admin scripts.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @const string
	 */
	const onAdminScriptStart = 'onAdminScriptStart';

	/**
	 * The onAdminScriptEnd event is triggered at the end of admin scripts.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - templateEngine: An iMSCP_pTemplate object
	 *
	 * @const string
	 */
	const onAdminScriptEnd = 'onAdminScriptEnd';

	/**
	 * The onResellerScriptStart event is triggered at the very beginning of reseller scripts.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @const string
	 */
	const onResellerScriptStart = 'onResellerScriptStart';

	/**
	 * The onResellerScriptEnd event is triggered at the end of reseller scripts.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - templateEngine: An iMSCP_pTemplate object
	 *
	 * @const string
	 */
	const onResellerScriptEnd = 'onResellerScriptEnd';

	/**
	 * The onClientScriptStart event is triggered at the very beginning of client scripts.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @const string
	 */
	const onClientScriptStart = 'onClientScriptStart';

	/**
	 * The onClientScriptEnd event is triggered at the end of client scripts.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - templateEngine: An iMSCP_pTemplate object
	 *
	 * @const string
	 */
	const onClientScriptEnd = 'onClientScriptEnd';

	/**
	 * The onExceptioToBrowserStart event is triggered before of exception browser write process.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 * - context: An iMSCP_Exception_Writer_Browser object, the context in which the event is triggered
	 *
	 * @const string
	 */
	const onExceptionToBrowserStart = 'onExceptionToBrowserStart';

	/**
	 * The onExceptionToBrowserEnd event is triggered at the end of exception browser write process.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - context: An iMSCP_Exception_Writer_Browser object, the context in which the event is triggered
	 * - templateEngine: An iMSCP_pTemplate object
	 *
	 * @const string
	 */
	const onExceptionToBrowserEnd = 'onExceptionToBrowserEnd';

	/**
	 * The onBeforeAuthentication event is triggered before the authentication process.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - context: An iMSCP_Authentication object, the context in which the event is triggered
	 *
	 * @const string
	 */
	const onBeforeAuthentication = 'onBeforeAuthentication';

	/**
	 * The onAuthentication event is triggered on authentication process.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - context: An iMSCP_Authentication object, the context in which the event is triggered
	 * - username: Username
	 * - password: Password
	 *
	 * @const string
	 */
	const onAuthentication = 'onAuthentication';

	/**
	 * The onBeforeAuthentication event is triggered after the authentication process.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - context: An iMSCP_Authentication object, the context in which the event is triggered
	 * - authResult: An iMSCP_Authentication_Result object, an object that encapsulates the authentication result
	 *
	 * @const string
	 */
	const onAfterAuthentication = 'onAfterAuthentication';

	/**
	 * The onBeforeSetIdentity event is triggered before a user identity is set (logged on).
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - context: An iMSCP_Authentication object, the context in which the event is triggered
	 * - identity: A stdClass object that contains the user identity data
	 *
	 * @const string
	 */
	const onBeforeSetIdentity = 'onBeforeSetIdentity';

	/**
	 * The onAfterSetIdentity event is triggered after a user identity is set (logged on).
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - context: An iMSCP_Authentication object, the context in which the event is triggered
	 *
	 * @const string
	 */
	const  onAfterSetIdentity = 'onAfterSetIdentity';

	/**
	 * The onBeforeUnsetIdentity event is triggered before a user identity is unset (logout).
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - context: An iMSCP_Authentication object, the context in which the event is triggered
	 *
	 * @const string
	 */
	const onBeforeUnsetIdentity = 'onBeforeUnsetIdentity';

	/**
	 * The onAfterUnsetIdentity event is triggered after a user identity is unset (logged on).
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - context: An iMSCP_Authentication object, the context in which the event is triggered
	 *
	 * @const string
	 */
	const  onAfterUnsetIdentity = 'onAfterUnsetIdentity';

	/**
	 * The onBeforeEditAdminGeneralSettings event is triggered before the admin general settings are edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @const string
	 */
	const onBeforeEditAdminGeneralSettings = 'onBeforeEditAdminGeneralSettings';

	/**
	 * The onAfterEditAdminGeneralSettings event is triggered after the admin general settings are edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @const string
	 */
	const onAfterEditAdminGeneralSettings = 'onAfterEditAdminGeneralSettings';

	/**
	 * The onBeforeAddUser event is triggered before an user is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @const string
	 */
	const onBeforeAddUser = 'onBeforeAddUser';

	/**
	 * The onAfterAddUser event is triggered after an user is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @const string
	 */
	const onAfterAddUser = 'onAfterAddUser';

	/**
	 * The onBeforeEditUser event is triggered before an user is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - userId: An integer representing the ID of user being edited.
	 *
	 * @const string
	 */
	const onBeforeEditUser = 'onBeforeEditUser';

	/**
	 * The onAfterEditUser event is triggered after an user is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - userId: An integer representing the ID of user that has been edited
	 *
	 * @const string
	 */
	const onAfterEditUser = 'onAfterEditUser';

	/**
	 * The onBeforeDeleteUser event is triggered before an user is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - userId: An integer representing the ID of user being deleted
	 *
	 * @const string
	 */
	const onBeforeDeleteUser = 'onBeforeDeleteUser';

	/**
	 * The onAfterDeleteUser event is triggered after an user is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - userId: An integer representing the ID of user that has been deleted
	 *
	 * @const string
	 */
	const onAfterDeleteUser = 'onAfterDeleteUser';

	/**
	 * The onBeforeDeleteDomain event is triggered before a customer account is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 *  - customerId: An integer representing the ID of customer being deleted
	 *
	 * @const string
	 */
	const onBeforeDeleteCustomer = 'onBeforeDeleteCustomer';

	/**
	 * The onAfterDeleteCustomer event is triggered after a customer account is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - customerId: An integer representing the ID of customer that has been deleted
	 *
	 * @const string
	 */
	const onAfterDeleteCustomer = 'onAfterDeleteCustomer';

	/**
	 * The onBeforeAddFtp event is triggered after an Ftp account is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - ftpUserId: A string representing Ftp account username
	 * - ftpPassword: A string representing Ftp account encrypted password
	 * - ftpRawPassword: A string representing Ftp account raw password
	 * - ftpUserUid: A string representing Ftp user uid
	 * - ftpUserGid: A string representing Ftp user gid
	 * - ftpUserShell: A string representing Ftp user shell
	 * - ftpUserHome: A string representing Ftp user home
	 *
	 * @const string
	 */
	const onBeforeAddFtp = 'onBeforeAddFtp';

	/**
	 * The onAfterAddFtp event is triggered after an Ftp account is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - ftpUserId: A string representing Ftp account username
	 * - ftpPassword: A string representing Ftp account encrypted password
	 * - ftpRawPassword: A string representing Ftp account raw password
	 * - ftpUserUid: A string representing Ftp user uid
	 * - ftpUserGid: A string representing Ftp user gid
	 * - ftpUserShell: A string representing Ftp user shell
	 * - ftpUserHome: A string representing Ftp user home
	 *
	 * @const string
	 */
	const onAfterAddFtp = 'onAfterAddFtp';

	/**
	 * The onBeforeEditFtp event is triggered before an Ftp account is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - ftpUserId: A string representing Ftp account username being edited
	 *
	 * @const string
	 */
	const onBeforeEditFtp = 'onBeforeEditFtp';

	/**
	 * The onAfterEditFtp event is triggered after an Ftp account is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - ftpUserId: A string representing Ftp account username that has been edited
	 *
	 * @const string
	 */
	const onAfterEditFtp = 'onAfterEditFtp';

	/**
	 * The onBeforeDeleteFtp event is triggered before an Ftp account is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - ftpUserId: A string representing Ftp account username being deleted
	 *
	 * @const string
	 */
	const onBeforeDeleteFtp = 'onBeforeDeleteFtp';

	/**
	 * The onAfterDeleteFtp event is triggered after an Ftp account is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - ftpUserId: A string representing Ftp account username that has been deleted
	 *
	 * @const string
	 */
	const onAfterDeleteFtp = 'onAfterDeleteFtp';

	/**
	 * The onBeforeAddSqlUser event is triggered before an Sql user is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @const string
	 */
	const onBeforeAddSqlUser = 'onBeforeAddSqlUser';

	/**
	 * The onAfterAddSqlUser event is triggered after an Sql user is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object.
	 *
	 * @const string
	 */
	const onAfterAddSqlUser = 'onAfterAddSqlUser';

	/**
	 * The onBeforeEditSqlUser event is triggered before an Sql user is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - sqlUserId: An integer representing the ID of Sql user being edited
	 *
	 * @const string
	 */
	const onBeforeEditSqlUser = 'onBeforeEditSqlUser';

	/**
	 * The onAfterEditSqlUser event is triggered after an Sql user is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - sqlUserId: An integer representing the ID of Sql user that has been edited
	 *
	 * @const string
	 */
	const onAfterEditSqlUser = 'onAfterEditSqlUser';

	/**
	 * The onBeforeDeleteSqlUser event is triggered before an Sql user is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - sqlUserId: An integer representing the ID of Sql user being deleted
	 *
	 * @const string
	 */
	const onBeforeDeleteSqlUser = 'onBeforeDeleteSqlUser';

	/**
	 * The onAfterDeleteSqlUser event is triggered after an Sql user is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - sqlUserId: An integer representing the ID of Sql user that has been deleted
	 *
	 * @const string
	 */
	const onAfterDeleteSqlUser = 'onAfterDeleteSqlUser';

	/**
	 * The onBeforeAddSqlDb event is triggered before an Sql database is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - dbName: Name of database being added
	 *
	 * @const string
	 */
	const onBeforeAddSqlDb = 'onBeforeAddSqlDb';

	/**
	 * The onAfterAddSqlDb event is triggered after an Sql database is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - dbName: Name of database that was added
	 *
	 * @const string
	 */
	const onAfterAddSqlDb = 'onAfterAddSqlDb';

	/**
	 * The onBeforeDeleteSqlDb event is triggered before an Sql database is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - sqlDbId: An integer representing the ID of Sql database being deleted
	 *
	 * @const string
	 */
	const onBeforeDeleteSqlDb = 'onBeforeDeleteSqlDb';

	/**
	 * The onAfterDeleteSqlDb event is triggered after an Sql database is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - sqlDbId: An integer representing the ID of Sql database that has been deleted
	 *
	 * @const string
	 */
	const onAfterDeleteSqlDb = 'onAfterSqlDb';

	/**
	 * The onBeforePluginRoute event is triggered before routing of plugins.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 *
	 * @const string
	 */
	const onBeforePluginsRoute = 'onBeforePluginsRoute';

	/**
	 * The onAfterPluginRoute event is triggered after routing of plugins.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - controllePath: Plugin controller path
	 *
	 * @const string
	 */
	const onAfterPluginsRoute = 'onAfterPluginsRoute';

	/**
	 * The onAfterUpdatePluginList event is triggered before the plugin list is updated.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 *
	 * @const string
	 */
	const onBeforeUpdatePluginList = 'onBeforeUpdatePluginList';

	/**
	 * The onAfterUpdatePluginList event is triggered before the plugin list is updated.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameter:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 *
	 * @const string
	 */
	const onAfterUpdatePluginList = 'onAfterUpdatePLuginList';

	/**
	 * The onBeforeInstallPlugin event is triggered before a plugin installation.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 *
	 * @const string
	 */
	const onBeforeInstallPlugin = 'onBeforeInstallPlugin';

	/**
	 * The onAfterInstallPlugin event is triggered after a plugin installation.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 *
	 * @const string
	 */
	const onAfterInstallPlugin = 'onAfterInstallPlugin';

	/**
	 * The onBeforeEnablePlugin event is triggered before a plugin activation.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 *
	 * @const string
	 */
	const onBeforeEnablePlugin = 'onBeforeEnablePlugin';

	/**
	 * The onAfterEnablePlugin event is triggered after a plugin activation.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 *
	 * @const string
	 */
	const onAfterEnablePlugin = 'onAfterEnablePlugin';

	/**
	 * The onBeforeDisablePlugin event is triggered before a plugin deactivation.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 *
	 * @const string
	 */
	const onBeforeDisablePlugin = 'onBeforeDisablePlugin';

	/**
	 * The onAfterDisablePlugin event is triggered after a plugin deactivation.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 *
	 * @const string
	 */
	const onAfterDisablePlugin = 'onAfterDisablePlugin';

	/**
	 * The onBeforeUpdatePlugin event is triggered before a plugin update.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 * - pluginFromVersion: Version from wich plugin is being updated
	 * - PluginToVersion: Version to which plugin is being updated
	 *
	 * @const string
	 */
	const onBeforeUpdatePlugin = 'onBeforeUpdatePlugin';

	/**
	 * The onAfterUpdatePlugin event is triggered after a plugin update.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 * - PluginFromVersion: Version to which plugin has been updated
	 * - PluginToVersion: Version from which plugin has been updated
	 *
	 * @const string
	 */
	const onAfterUpdatePlugin = 'onAfterUpdatePlugin';

	/**
	 * The onBeforeUninstallPlugin event is triggered before a plugin installation.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 *
	 * @const string
	 */
	const onBeforeUninstallPlugin = 'onBeforeUninstallPlugin';

	/**
	 * The onAfterUninstallPlugin event is triggered after a plugin installation.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 *
	 * @const string
	 */
	const onAfterUninstallPlugin = 'onAfterUninstallPlugin';

	/**
	 * The onBeforeUninstall event is triggered before a plugin uninstallation.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 *
	 * @const string
	 */
	const onBeforeUninstall = 'onBeforeUninstall';

	/**
	 * The onAfterDeletePlugin event is triggered after a plugin uninstallation.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 *
	 * @const string
	 */
	const onAfterUninstall = 'onAfterUninstall';

	/**
	 * The onBeforeDeletePlugin event is triggered before a plugin deletion.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 *
	 * @const string
	 */
	const onBeforeDeletePlugin = 'onBeforeDeletePlugin';

	/**
	 * The onAfterDeletePlugin event is triggered after a plugin deletion.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 *
	 * @const string
	 */
	const onAfterDeletePlugin = 'onAfterDeletePlugin';

	/**
	 * The onBeforeProtectPlugin event is triggered before a plugin protection.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 *
	 * @const string
	 */
	const onBeforeProtectPlugin = 'onBeforeProtectPlugin';

	/**
	 * The onAfterProtectPlugin event is triggered after a plugin protection.
	 *
	 * The listeners receive an iMSCP_Envents_Event object with the following parameters:
	 *
	 * - pluginManager: iMSCP_Plugin_Manager instance
	 * - pluginName: Plugin name
	 *
	 * @const string
	 */
	const onAfterProtectPlugin = 'onAfterProtectPlugin';

	/**
	 * The onBeforeAddDomain event is triggered before a domain is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - domainName: A string representing the name of the domain being created
	 * - createdBy: An integer representing the ID of the reseller that adds the domain
	 * - customerId: An integer representitng the ID of the customer for which the domain is added
	 * - customerEmail: A string representing the email of the customer for which the domain is added
	 *
	 * @const string
	 */
	const onBeforeAddDomain = 'onBeforeAddDomain';

	/**
	 * The onAfterAddDomain event is triggered after a domain is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - domainName: A string representing the name of a the domain that has been added
	 * - createdBy: An integer representing the ID of the reseller that added the domain
	 * - customerId: An integer representing the ID of the customer for which the domain has been added
	 * - customerEmail: A string representing the email of customer for which the domain has been added
	 * - domainId: An integer representing the ID of the domain that has been added
	 *
	 * @const string
	 */
	const onAfterAddDomain = 'onAfterAddDomain';

	/**
	 * The onBeforeEditDomain event is triggered before a domain is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - domainId: An integer representing the ID of the domain being edited
	 *
	 * @const string
	 */
	const onBeforeEditDomain = 'onBeforeEditDomain';

	/**
	 * The onAfterEditDomain event is triggered agfter a domain is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - domainId: An integer reprensenting the ID of the domain that has been edited
	 *
	 * @const string
	 */
	const onAfterEditDomain = 'onAfterEditDomain';

	/**
	 * The onBeforeAddSubdomain event is triggered after a subdomain is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - subdomainName: A string representing the name of the subdomain being added
	 * - subdomainType: A string representing the type of subdomain (als|dmn)
	 * - parentDomainId: An integer representing the ID of the parent domain
	 * - mountPoint: A string representing the mount point of the subdomain
	 * - forwardUrl: A string representing the forward URL or no in case Forward URL option is not used
	 * - customerId: An integer representing the ID of the customer for which the subdomain is added
	 *
	 * @const string
	 */
	const onBeforeAddSubdomain = 'onBeforeAddSubdomain';

	/**
	 * The onAfterAddSubdomain event is triggered after a subdomain is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - subdomainName: A string representing the name of the subdomain that has been added
	 * - subdomainType: A string representing the type of subdomain (als|dmn)
	 * - parentDomainId: An integer representing the ID of the parent domain
	 * - mountPoint: A string representing the mount point of the subdomain
	 * - forwardUrl: A string representing the forward URL or no in case Forward URL option is not used
	 * - customerId: An integer representing the ID of the customer for wich the subdomain has been added
	 * - subdomainId: An integer representing the ID of thesubdomain that has been added
	 *
	 * @const string
	 */
	const onAfterAddSubdomain = 'onAfterAddSubdomain';

	/**
	 * The onBeforeEditSubdomain event is triggered after a subdomain is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - subdomainId: An integer representing the ID of the subdomain being edited
	 *
	 * @const string
	 */
	const onBeforeEditSubdomain = 'onBeforeEditSubdomain';

	/**
	 * The onAfterEditSubdomain event is triggered after a subdomain is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - subdomainId: An integer representing the ID of the subdomain that has been edited
	 *
	 * @const string
	 */
	const onAfterEditSubdomain = 'onAfterEditSubdomain';

	/**
	 * The onBeforeDeleteSubdomain event is triggered before a subdomain is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - subdomainId: An integer representing the ID of the subdomain being deleted
	 * - type: A string representing the type of subdomain (sub|alssub)
	 *
	 * @const string
	 */
	const onBeforeDeleteSubdomain = 'onBeforeDeleteSubdomain';

	/**
	 * The onAfterDeleteSubdomain event is triggered after a subdomain is delteded.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - subdomainId: An integer representing the ID of the subdomain that has been deleted
	 * - type: A string representing the type of subdomain (sub|alssub)
	 *
	 * @const string
	 */
	const onAfterDeleteSubdomain = 'onAfterDeleteSubdomain';

	/**
	 * The onBeforeAddDomainAlias event is triggered before a domain alias is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - domainId: An integer representing the ID of the parent domain
	 * - domainAliasName: A string representing the name of the domain alias being added
	 *
	 * @const string
	 */
	const onBeforeAddDomainAlias = 'onBeforeAddDomainAlias';

	/**
	 * The onAfterAddDomainAlias event is triggered after a domain alias is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - domainId: An integer representing the ID of the parent domain
	 * - domainAliasName: A string representing the name of the domain alias that has been added
	 * - domainAliasId: An integer representing the ID of the domain alias that has been added
	 *
	 * @const string
	 */
	const onAfterAddDomainAlias = 'onAfterAddDomainAlias';

	/**
	 * The onBeforeEditDomainAlias event is triggered before a domain alias is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - domainAliasId: An integer representing the ID of the domain alias being edited
	 *
	 * @const string
	 */
	const onBeforeEditDomainAlias = 'onBeforeEditDomainAlias';

	/**
	 * The onAfterEditDomainALias event is triggered after a domain alias is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - domainAliasId: An integer representing the ID of the domain alias that has been edited
	 *
	 * @const string
	 */
	const onAfterEditDomainALias = 'onAfterEditDomainAlias';

	/**
	 * The onBeforeDeleteDomainAlias event is triggered before a domain alias is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - domainAliasId: An integer representing the  ID of the domain alias being deleted
	 *
	 * @const string
	 */
	const onBeforeDeleteDomainAlias = 'onBeforeDeleteDomainAlias';

	/**
	 * The onAfterDeleteDomainAlias event is triggered after a domain alias is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - domainAliasId: An integer representing the ID of the domain alias that has been deleted
	 *
	 * @const string
	 */
	const onAfterDeleteDomainAlias = 'onAfterDeleteDomainAlias';

	/**
	 * The onBeforeAddMail event is triggered after a mail account is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - mailUsername: A string representing the local part of the email account being added
	 * - mailAddress: A string representing the complete email address of the mail account being added
	 *
	 * @const string
	 */
	const onBeforeAddMail = 'onBeforeAddMail';

	/**
	 * The onAfterAddMail event is triggered after a mail account is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - mailUsername: A string representing the local part of the email account that has been added
	 * - mailAddress: A string representing the complete address of the mail account that has been added
	 * - mailId: An integer representing the ID of the email account that has been added
	 *
	 * @const string
	 */
	const onAfterAddMail = 'onAfterAddMail';

	/**
	 * The onBeforeEditMail event is triggered before a mail account is created.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - mailId: An integer representing the ID of the mail account being edited
	 *
	 * @const string
	 */
	const onBeforeEditMail = 'onBeforeEditMail';

	/**
	 * The onAfterEditMail event is triggered after a mail account is edited.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - mailId: An integer representing the ID of the mail account that has been edited
	 *
	 * @const string
	 */
	const onAfterEditMail = 'onAfterEditMail';

	/**
	 * The onBeforeDeleteMail event is triggered before a mail account is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - mailId: An integer representing the ID of the mail account being deleted
	 *
	 * @const string
	 */
	const onBeforeDeleteMail = 'onBeforeDeleteMail';

	/**
	 * The onAfterDeleteMail event is triggered after a mail account is deleted.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - mailId: An integer representing the ID of the mail account that has been deleted
	 *
	 * @const string
	 */
	const onAfterDeleteMail = 'onAfterDeleteMail';

	/**
	 * The onBeforeQueryPrepare event is triggered before an SQL statement is prepared for execution.
	 *
	 * The listeners receive an iMSCP_Database_Events_Database instance with the following parameters:
	 *
	 * - context: An iMSCP_Database object, the context in which the event is triggered
	 * - query: The SQL statement being prepared
	 *
	 * @const string
	 */
	const onBeforeQueryPrepare = 'onBeforeQueryPrepare';

	/**
	 * The onAfterQueryPrepare event occurs after a SQL statement has been prepared for execution.
	 *
	 * The listeners receive an iMSCP_Database_Events_Statement instance with the following parameters:
	 *
	 * - context: An iMSCP_Database object, the context in which the event is triggered
	 * - statement: A PDOStatement object that represent the prepared statement
	 *
	 * @const string
	 */
	const onAfterQueryPrepare = 'onAfterQueryPrepare';

	/**
	 * The onBeforeQueryExecute event is triggered before a prepared SQL statement is executed.
	 *
	 * The listeners receive either :
	 *
	 *  an iMSCP_Database_Events_Statement instance with the following parameters:
	 *
	 *   - context: An iMSCP_Database object, the context in which the event is triggered
	 *   - statement: A PDOStatement object that represent the prepared statement
	 * Or
	 *
	 *  an iMSCP_Database_Events_Database instance with the following arguments:
	 *
	 *   - context: An iMSCP_Database object, the context in which the event is triggered
	 *   - query: The SQL statement being prepared and executed (PDO::query())
	 *
	 * @const string
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
	 * @const string
	 */
	const onAfterQueryExecute = 'onAfterQueryExecute';

	/**
	 * The onBeforeAssembleTemplateFiles event is triggered before the first parent template is loaded.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - context: An iMSCP_pTemplate object, the context in which the event is triggered
	 * - templatePath: The filepath of the template being loaded
	 *
	 * @const string
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
	 * @const string
	 */
	const onAfterAssembleTemplateFiles = 'onBeforeAssembleTemplateFiles';

	/**
	 * The onBeforeLoadTemplateFile event is triggered before a template is loaded.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - context: An iMSCP_pTemplate object, the context in which the event is triggered
	 * - templatePath: The filepath of the template being loaded
	 *
	 * @const string
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
	 * @const string
	 */
	const onAfterLoadTemplateFile = 'onAfterLoadTemplateFile';

	/**
	 * The onBeforeGenerateNavigation event is triggeed before the navigation is generated.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - templateEngine: An iMSCP_pTemplate object
	 *
	 * @const string
	 */
	const onBeforeGenerateNavigation = 'onBeforeGenerateNavigation';

	/**
	 * The onAfterGenerateNavigation event is triggered after the navigation is generated.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - templateEngine: An iMSCP_pTemplate object
	 *
	 * @const string
	 *
	 */
	const onAfterGenerateNavigation = 'onAfterGenerateNavigation';

	/**
	 * The onBeforeAddExternalMailServer event is triggered before addition of external mail server entries in database.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - externalMailServerEntries: A reference to an array containing all external mail entries
	 *
	 * @const string
	 */
	const onBeforeAddExternalMailServer = 'onBeforeAddExternalMailServer';

	/**
	 * The onAfterAddExternalMailServer event is triggered after addition of external mail server entries in database.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameter:
	 *
	 * - externalMailServerEntries: A reference to an array containing all external mail entries
	 *
	 * @const string
	 */
	const onAfterAddExternalMailServer = 'onAfterAddExternalMailServer';

	/**
	 * The onBeforeChangeDomainStatus event is triggered before an user account is being activated or deactivated.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - customerId: An integer representing the ID of the customer for wich the subdomain has been added
	 * - action: An string representing the action being processed (activate|deactivate)
	 *
	 * @const string
	 */
	const onBeforeChangeDomainStatus = 'onBeforeChangeDomainStatus';

	/**
	 * The onAfterChangeDomainStatus event is triggered before an user account get activated or deactivated.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - customerId: An integer representing the ID of the customer for wich the subdomain has been added
	 * - action: - action: An string representing the action that was processed (activate|deactivate)
	 *
	 * @const string
	 */
	const onAfterChangeDomainStatus = 'onAfterChangeDomainStatus';

	/**
	 * The onBeforeSendCircular event is triggered before an admin or reseller send a circular.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - sender_name Sender name
	 * - sender_email Sender email
	 * - rcpt_to recipient type (all_users, aministrator_resellers, administrators_customers, resellers_customers,
	 *  administrators, resellers, customers)
	 * - subject Circular subject
	 * - body Circular body
	 */
	const onBeforeSendCircular = 'onBeforeSendCircular';

	/**
	 * The onAfterSendCircular event is triggered after an admin or reseller has sent a circular.
	 *
	 * The listeners receive an iMSCP_Events_Event object with the following parameters:
	 *
	 * - sender_name Sender name
	 * - sender_email Sender email
	 * - rcpt_to recipient type (all_users, aministrator_resellers, administrators_customers, resellers_customers,
	 *  administrators, resellers, customers)
	 * - subject Circular subject
	 * - body Circular body
	 */
	const onAfterSendCircular = 'onAfterSendCircular';
}
