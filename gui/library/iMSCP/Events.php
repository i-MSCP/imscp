<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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
 */

/**
 * Class iMSCP_Events
 */
class iMSCP_Events
{
    /**
     * Event triggered after i-MSCP application has been fully bootstrapped
     *
     * iMSCP_Events_Event object parameter:
     *  - context : iMSCP\Application instance
     *
     * @const string
     */
    const onAfterApplicationBootstrap = 'onAfterApplicationBootstrap';

    /**
     * Event triggered at starting of login action script
     *
     * @const string
     */
    const onLoginScriptStart = 'onLoginScriptStart';

    /**
     * Event triggered at end of login script
     *
     * iMSCP_Events_Event object parameter:
     *  - templateEngine : An \iMSCP\TemplateEngine object
     *
     * @const string
     */
    const onLoginScriptEnd = 'onLoginScriptEnd';

    /**
     * Event triggered at starting of the lostpassword action script
     *
     * @const string
     */
    const onLostPasswordScriptStart = 'onLostPasswordScriptStart';

    /**
     * Event triggered at end of the lostpassword action script
     *
     * iMSCP_Events_Event object parameter:
     *  - templateEngine : \iMSCP\TemplateEngine instance
     *
     * @const string
     */
    const onLostPasswordScriptEnd = 'onLostPasswordScriptEnd';

    /**
     * Event triggered at starting of shared scripts
     *
     * @const string
     */
    const onSharedScriptStart = 'onSharedScriptStart';

    /**
     * Event triggered at end of shared scripts
     *
     * iMSCP_Events_Event object parameter:
     *  - templateEngine : \iMSCP\TemplateEngine instance
     *
     * @const string
     */
    const onSharedScriptEnd = 'onSharedScriptEnd';

    /**
     * Event triggered at starting of admin scripts
     *
     * @const string
     */
    const onAdminScriptStart = 'onAdminScriptStart';

    /**
     * Event triggered at end of admin scripts
     *
     * iMSCP_Events_Event object parameter:
     *  - templateEngine : \iMSCP\TemplateEngine instance
     *
     * @const string
     */
    const onAdminScriptEnd = 'onAdminScriptEnd';

    /**
     * Event triggered at starting of reseller scripts
     *
     * @const string
     */
    const onResellerScriptStart = 'onResellerScriptStart';

    /**
     * Event triggered at end of reseller scripts
     *
     * iMSCP_Events_Event object parameter:
     *  - templateEngine : \iMSCP\TemplateEngine instance
     *
     * @const string
     */
    const onResellerScriptEnd = 'onResellerScriptEnd';

    /**
     * Event triggered at starting of client scripts
     *
     * @const string
     */
    const onClientScriptStart = 'onClientScriptStart';

    /**
     * Event triggered end of client scripts
     *
     * iMSCP_Events_Event object parameter:
     *  - templateEngine: An \iMSCP\TemplateEngine instance
     *
     * @const string
     */
    const onClientScriptEnd = 'onClientScriptEnd';

    /**
     * Event triggered before of exception browser write process
     *
     * iMSCP_Events_Event object parameter:
     *  - context : iMSCP_Exception_Writer_Browser object
     *
     * @deprecated This event is deprecated and no longer triggered
     * @const string
     */
    const onExceptionToBrowserStart = 'onExceptionToBrowserStart';

    /**
     * Event triggered at end of exception browser write process
     *
     * iMSCP_Events_Event object parameters:
     *  - context        : iMSCP_Exception_Writer_Browser object
     *  - templateEngine : \iMSCP\TemplateEngine instance
     *
     * @deprecated This event is deprecated and no longer triggered
     * @const string
     */
    const onExceptionToBrowserEnd = 'onExceptionToBrowserEnd';

    /**
     * Event triggered before the authentication process
     *
     * iMSCP_Events_Event object parameter:
     *  - context : iMSCP_Authentication instance
     *
     * @const string
     */
    const onBeforeAuthentication = 'onBeforeAuthentication';

    /**
     * Event triggered on authentication process
     *
     * Listeners receive an iMSCP_Authentication_AuthEvent object.
     *
     * @const string
     */
    const onAuthentication = 'onAuthentication';

    /**
     * Event triggered after the authentication process
     *
     * iMSCP_Events_Event object parameters:
     *  - context    : iMSCP_Authentication instance
     *  - authResult : iMSCP_Authentication_Result object
     *
     * @const string
     */
    const onAfterAuthentication = 'onAfterAuthentication';

    /**
     * Event triggered before an user identity is set
     *
     * iMSCP_Events_Event object parameters:
     *  - context  : iMSCP_Authentication instance
     *  - identity : stdClass object containing user identity data
     *
     * @const string
     */
    const onBeforeSetIdentity = 'onBeforeSetIdentity';

    /**
     * Event triggered after an user identity is set
     *
     * iMSCP_Events_Event object parameter:
     *  - context : iMSCP_Authentication instance
     *
     * @const string
     */
    const  onAfterSetIdentity = 'onAfterSetIdentity';

    /**
     * Event triggered before an user identity is unset
     *
     * iMSCP_Events_Event object parameter:
     *  - context : iMSCP_Authentication instance
     *
     * @const string
     */
    const onBeforeUnsetIdentity = 'onBeforeUnsetIdentity';

    /**
     * Event triggered after an user identity is unset
     *
     * iMSCP_Events_Event object parameter:
     *  - context : iMSCP_Authentication instance
     *
     * @const string
     */
    const  onAfterUnsetIdentity = 'onAfterUnsetIdentity';

    /**
     * Event triggered before editing of admin general settings
     *
     * @const string
     */
    const onBeforeEditAdminGeneralSettings = 'onBeforeEditAdminGeneralSettings';

    /**
     * Event triggered after editing of admin general settings
     *
     * @const string
     */
    const onAfterEditAdminGeneralSettings = 'onAfterEditAdminGeneralSettings';

    /**
     * Event triggered before user addition (admin, reseller)
     *
     * iMSCP_Events_Event object parameter:
     *  - userData : User login and personal data
     *
     * @const string
     */
    const onBeforeAddUser = 'onBeforeAddUser';

    /**
     * Event triggered after user addition (admin, reseller)
     *
     * iMSCP_Events_Event objectparameters:
     *  - userId   : User unique identifier
     *  - userData : User login and personal data
     *
     * @const string
     */
    const onAfterAddUser = 'onAfterAddUser';

    /**
     * Event triggered before user edition
     *
     * iMSCP_Events_Event objectparameters:
     *  - userId   : User unique identifier
     *  - userData : User login and personal data. Depending on context, some
     *               data can be unavailable
     *
     * @const string
     */
    const onBeforeEditUser = 'onBeforeEditUser';

    /**
     * Event triggered after user edition
     *
     * iMSCP_Events_Event object parameters:
     *  - userId   : User unique identifier
     *  - userData : User login and personal data Depending on context, some
     *               data can be unavailable
     *
     * @const string
     */
    const onAfterEditUser = 'onAfterEditUser';

    /**
     * Event triggered before user deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - userId : User unique identifier
     *
     * @const string
     */
    const onBeforeDeleteUser = 'onBeforeDeleteUser';

    /**
     * Event triggered after user deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - userId : User unique identifier
     *
     * @const string
     */
    const onAfterDeleteUser = 'onAfterDeleteUser';

    /**
     * Event triggered when a reseller account is moved from one administrator
     * to another administrator
     *
     * iMSCP_Events_Event object parameter:
     *  - resellerId          : Reseller unique identifier
     *  - fromAdministratorId : Administrator unique identifier
     *  - toAdministratorId   : Administrator unique identifier
     *
     * @const string
     */
    const onMoveReseller = 'onMoveReseller';

    /**
     * Event triggered when a customer account is moved from one reseller to
     * another reseller
     *
     * iMSCP_Events_Event object parameter:
     *  - customerId     : Customer unique identifier
     *  - fromResellerId : Reseller unique identifier
     *  - toResellerId   : Reseller unique identifier
     *
     * @const string
     */
    const onMoveCustomer = 'onMoveCustomer';

    /**
     * Event triggered before customer account deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - customerId : Customer unique identifier
     *
     * @const string
     */
    const onBeforeDeleteCustomer = 'onBeforeDeleteCustomer';

    /**
     * Event triggered after customer account deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - customerId : Customer unique identifier
     *
     * @const string
     */
    const onAfterDeleteCustomer = 'onAfterDeleteCustomer';

    /**
     * Event triggered before FTP user addition
     *
     * iMSCP_Events_Event object parameters:
     *  - ftpUserId    : FTP user unique identifier
     *  - ftpPassword  : FTP user password
     *  - ftpUserUid   : FTP user uid
     *  - ftpUserGid   : FTP user gid
     *  - ftpUserShell : FTP user shell
     *  - ftpUserHome  : FTP user home
     *
     * @const string
     */
    const onBeforeAddFtp = 'onBeforeAddFtp';

    /**
     * Event triggered after FTP user addition
     *
     * iMSCP_Events_Event object parameters:
     *  - ftpUserId    : FTP user unique identifier
     *  - ftpPassword  : FTP user password
     *  - ftpUserUid   : FTP user uid
     *  - ftpUserGid   : FTP user gid
     *  - ftpUserShell : FTP user shell
     *  - ftpUserHome  : FTP user home
     *
     * @const string
     */
    const onAfterAddFtp = 'onAfterAddFtp';

    /**
     * Event triggered before FTP user edition
     *
     * iMSCP_Events_Event object parameters:
     *  - ftpUserId   : FTP user unique identifier
     *  - ftpPassword : FTP user password
     *  - ftpUserHome : FTP user home
     *
     * @const string
     */
    const onBeforeEditFtp = 'onBeforeEditFtp';

    /**
     * Event triggered after FTP user edition
     *
     * iMSCP_Events_Event object parameters:
     *  - ftpUserId   : FTP user unique identifier
     *  - ftpPassword : FTP user password
     *  - ftpUserHome : FTP user home
     *
     * @const string
     */
    const onAfterEditFtp = 'onAfterEditFtp';

    /**
     * Event triggered before FTP user deletion
     *
     * iMSCP_Events_Event objectparameter:
     *  - ftpUserId : FTP user unique identifier
     *
     * @const string
     */
    const onBeforeDeleteFtp = 'onBeforeDeleteFtp';

    /**
     * Event triggered after FTP user deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - ftpUserId : FTP user unique identifier
     *
     * @const string
     */
    const onAfterDeleteFtp = 'onAfterDeleteFtp';

    /**
     * Event triggered triggered before SQL user addition
     *
     * iMSCP_Events_Event object parameter:
     * - SqlUsername     : SQL username
     * - SqlUserHost     : SQL user host
     * - SqlUserPassword : SQL user password for new SQL user or empty string
     * - SqlDatabaseId   : Unique identifier of SQL database to which the SQL
     *                     user is attached
     *
     * @const string
     */
    const onBeforeAddSqlUser = 'onBeforeAddSqlUser';

    /**
     * Event triggered after SQL user addition
     *
     * iMSCP_Events_Event object parameter:
     * - SqlUserId       : SQL user unique identifier
     * - SqlUsername     : SQL username
     * - SqlUserHost     : SQL user host
     * - SqlUserPassword : SQL user password for new SQL user or empty string
     * - SqlDatabaseId   : Unique identifier of SQL database to which the SQL
     *                     user is attached
     *
     * @const string
     */
    const onAfterAddSqlUser = 'onAfterAddSqlUser';

    /**
     * Event triggered before SQL user edition
     *
     * iMSCP_Events_Event object parameter:
     *  - sqlUserId       : SQL user unique identifier
     *  - sqlUserPassword : SQL user password
     *
     * @const string
     */
    const onBeforeEditSqlUser = 'onBeforeEditSqlUser';

    /**
     * Event triggered after SQL user edition
     *
     * iMSCP_Events_Event object parameter:
     *  - sqlUserId : SQL user unique identifier
     *
     * @const string
     */
    const onAfterEditSqlUser = 'onAfterEditSqlUser';

    /**
     * Event triggered before SQL user deletion
     *
     * iMSCP_Events_Event objectparameter:
     *  - sqlUserId : SQL user unique identifier
     *
     * @const string
     */
    const onBeforeDeleteSqlUser = 'onBeforeDeleteSqlUser';

    /**
     * Event triggered after SQL user deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - sqlUserId : SQL user unique identifier
     *
     * @const string
     */
    const onAfterDeleteSqlUser = 'onAfterDeleteSqlUser';

    /**
     * Event triggered before SQL database addition
     *
     * iMSCP_Events_Event object parameter:
     *  - dbName : Database name
     *
     * @const string
     */
    const onBeforeAddSqlDb = 'onBeforeAddSqlDb';

    /**
     * Event triggered after SQl database addition
     *
     * iMSCP_Events_Event object parameter:
     * - dbId:  : Database unique identifier
     * - dbName : Database name
     *
     * @const string
     */
    const onAfterAddSqlDb = 'onAfterAddSqlDb';

    /**
     * Event triggered before SQL database deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - sqlDbId : SQL database unique identifier
     *
     * @const string
     */
    const onBeforeDeleteSqlDb = 'onBeforeDeleteSqlDb';

    /**
     * Event triggered after SQL database deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - sqlDbId : SQL database unique identifier
     *
     * @const string
     */
    const onAfterDeleteSqlDb = 'onAfterSqlDb';

    /**
     * Event triggered before custom DNS resource record addition
     *
     * iMSCP_Events_Event object parameters:
     *  - domainId : Customer main domain unique identifier
     *  - aliasId  : Domain alias unique identifier, 0 if no domain alias
     *  - name     : DNS resource record name field, including TTL
     *  - class    : DNS resource record class field
     *  - type     : DNS resource record type field
     *  - data     : DNS resource record data field
     *
     * @const string
     */
    const onBeforeAddCustomDNSrecord = 'onBeforeAddCustomDNSrecord';

    /**
     * Event triggered after custom DNS resource record addition
     *
     * iMSCP_Events_Event object parameters:
     *  - id       : DNS resource record unique identifier
     *  - domainId : Customer main domain unique identifier
     *  - aliasId  : Domain alias unique identifier, 0 if no domain alias
     *  - name     : DNS resource record name field, including TTL
     *  - class    : DNS resource record class field
     *  - type     : DNS resource record type field
     *  - data     : DNS resource record data field
     *
     * @const string
     */
    const onAfterAddCustomDNSrecord = 'onAfterAddCustomDNSrecord';

    /**
     * Event triggered before custom DNS resource record edition
     *
     * iMSCP_Events_Event object parameters:
     *  - id       : DNS resource record unique identifier
     *  - domainId : Customer main domain unique identifier
     *  - aliasId  : Domain alias unique identifier, 0 if no domain alias
     *  - name     : DNS resource record name field, including TTL
     *  - class    : DNS resource record class field
     *  - type     : DNS resource record type field
     *  - data     : DNS resource record data field
     *
     * @const string
     */
    const onBeforeEditCustomDNSrecord = 'onBeforeEditCustomDNSrecord';

    /**
     * Event triggered after custom DNS resource record edition
     *
     * iMSCP_Events_Event object parameters:
     *  - id       : DNS resource record unique identifier
     *  - domainId : Customer main domain unique identifier
     *  - aliasId  : Domain alias unique identifier, 0 if no domain alias
     *  - name     : DNS resource record name field, including TTL
     *  - class    : DNS resource record class field
     *  - type     : DNS resource record type field
     *  - data     : DNS resource record data field
     *
     * @const string
     */
    const onAfterEditCustomDNSrecord = 'onAfterEditCustomDNSrecord';

    /**
     * Event triggered before custom DNS resource record deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - id : DNS resource record unique identifier
     *
     * @const string
     */
    const onBeforeDeleteCustomDNSrecord = 'onBeforeDeleteCustomDNSrecord';

    /**
     * Event triggered after custom DNS resource record deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - id : DNS resource record unique identifier
     *
     * @const string
     */
    const onAfterDeleteCustomDNSrecord = 'onAfterDeleteCustomDNSrecord';

    /**
     * Event triggered before plugin routing
     *
     * iMSCP_Events_Event object parameter:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *
     * @const string
     */
    const onBeforePluginsRoute = 'onBeforePluginsRoute';

    /**
     * Event triggered after plugin routing
     *
     * iMSCP_Events_Event object parameter:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *  - scriptPath    : Plugin action script path
     *
     * @const string
     */
    const onAfterPluginsRoute = 'onAfterPluginsRoute';

    /**
     * Event triggered before plugin list update
     *
     * iMSCP_Events_Event object parameter:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *
     * @const string
     */
    const onBeforeUpdatePluginList = 'onBeforeUpdatePluginList';

    /**
     * Event triggered after plugin list update
     *
     * iMSCP_Events_Event object parameter:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *
     * @const string
     */
    const onAfterUpdatePluginList = 'onAfterUpdatePluginList';

    /**
     * Event triggered before plugin installation
     *
     * iMSCP_Events_Event object parameters:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *  - pluginName    : Plugin name
     *
     * @const string
     */
    const onBeforeInstallPlugin = 'onBeforeInstallPlugin';

    /**
     * Event triggered after plugin installation
     *
     * iMSCP_Events_Event object parameters:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *  - pluginName    : Plugin name
     *
     * @const string
     */
    const onAfterInstallPlugin = 'onAfterInstallPlugin';

    /**
     * Event triggered before plugin activation
     *
     * iMSCP_Events_Event object parameters:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *  - pluginName    : Plugin name
     *
     * @const string
     */
    const onBeforeEnablePlugin = 'onBeforeEnablePlugin';

    /**
     * Event triggered after plugin activation
     *
     * iMSCP_Events_Event object parameters:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *  - pluginName    : Plugin name
     *
     * @const string
     */
    const onAfterEnablePlugin = 'onAfterEnablePlugin';

    /**
     * Event triggered before plugin deactivation
     *
     * iMSCP_Events_Event object parameter:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *  - pluginName    : Plugin name
     *
     * @const string
     */
    const onBeforeDisablePlugin = 'onBeforeDisablePlugin';

    /**
     * Event triggered after plugin deactivation
     *
     * iMSCP_Events_Event object parameters:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *  - pluginName    : Plugin name
     *
     * @const string
     */
    const onAfterDisablePlugin = 'onAfterDisablePlugin';

    /**
     * Event triggered before plugin update
     *
     * iMSCP_Events_Event object parameters:
     *  - pluginManager     : iMSCP_Plugin_Manager instance
     *  - pluginName        : Plugin name
     *  - pluginFromVersion : Version from which plugin is being updated
     *  - PluginToVersion   : Version to which plugin is being updated
     *
     * @const string
     */
    const onBeforeUpdatePlugin = 'onBeforeUpdatePlugin';

    /**
     * Event triggered after plugin update
     *
     * iMSCP_Events_Event object parameters:
     *  - pluginManager     : iMSCP_Plugin_Manager instance
     *  - pluginName        : Plugin name
     *  - PluginFromVersion : Version to which plugin has been updated
     *  - PluginToVersion   : Version from which plugin has been updated
     *
     * @const string
     */
    const onAfterUpdatePlugin = 'onAfterUpdatePlugin';

    /**
     * Event triggered before plugin uninstallation
     *
     * iMSCP_Events_Event object parameters:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *  - pluginName    : Plugin name
     *
     * @const string
     */
    const onBeforeUninstallPlugin = 'onBeforeUninstallPlugin';

    /**
     * Event triggered after plugin uninstallation
     *
     * iMSCP_Events_Event object parameters:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *  - pluginName    : Plugin name
     *
     * @const string
     */
    const onAfterUninstallPlugin = 'onAfterUninstallPlugin';

    /**
     * Event triggered before plugin deletion
     *
     * iMSCP_Events_Event object parameters:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *  - pluginName    : Plugin name
     *
     * @const string
     */
    const onBeforeDeletePlugin = 'onBeforeDeletePlugin';

    /**
     * Event triggered after plugin deletion
     *
     * iMSCP_Events_Event object parameters:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *  - pluginName    : Plugin name
     *
     * @const string
     */
    const onAfterDeletePlugin = 'onAfterDeletePlugin';

    /**
     * Event triggered before plugin protection
     *
     * iMSCP_Events_Event object parameters:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *  - pluginName    : Plugin name
     *
     * @const string
     */
    const onBeforeProtectPlugin = 'onBeforeProtectPlugin';

    /**
     * Event triggered after plugin protection
     *
     * iMSCP_Events_Event object parameters:
     *  - pluginManager : iMSCP_Plugin_Manager instance
     *  - pluginName    : Plugin name
     *
     * @const string
     */
    const onAfterProtectPlugin = 'onAfterProtectPlugin';

    /**
     * Event triggered before plugin locking.
     *
     * iMSCP_Events_Event object parameter:
     *  - pluginName : Plugin name
     *
     * @const string
     */
    const onBeforeLockPlugin = 'onBeforeLockPlugin';

    /**
     * Event triggered ater plugin locking
     *
     * iMSCP_Events_Event object parameter:
     *  - pluginName : Plugin name
     *
     * @const string
     */
    const onAfterLockPlugin = 'onAfterLockPlugin';

    /**
     * Event triggered before plugin unlocking
     *
     * iMSCP_Events_Event object parameter:
     *  - pluginName : Plugin name
     *
     * @const string
     */
    const onBeforeUnlockPlugin = 'onBeforeUnlockPlugin';

    /**
     * Event triggered after plugin unlocking
     *
     * iMSCP_Events_Event object parameter:
     *  - pluginName : Plugin name
     *
     * @const string
     */
    const onAfterUnlockPlugin = 'onAfterUnlockPlugin';

    /**
     * Event triggered before domain (customer account) addition
     *
     * iMSCP_Events_Event object parameters:
     *  - createdBy     : Reseller unique identifier
     *  - customerId    : Customer unique identifier
     *  - customerEmail : Customer email address
     *  - domainName    : Domain name
     *  - mountPoint    : Domain mount point
     *  - documentRoot  : Domain document root
     *  - forwardUrl    : Domain forward URL, 'no' if no forward URL has been set
     *  - forwardType   : Domain forward URL type
     *  - forwardHost   : Domain forward URL preserve host option
     *
     * @const string
     */
    const onBeforeAddDomain = 'onBeforeAddDomain';

    /**
     * Event triggered after domain (customer account) addition
     *
     * iMSCP_Events_Event object parameters:
     *  - createdBy     : Reseller unique identifier
     *  - customerId    : Customer unique identifier
     *  - customerEmail : Customer email address
     *  - domainId      : Domain unique identifier
     *  - domainName    : Domain name
     *  - mountPoint    : Domain mount point
     *  - documentRoot  : Domain document root
     *  - forwardUrl    : Domain forward URL, 'no' if no forward URL has been set
     *  - forwardType   : Domain forward URL type
     *  - forwardHost   : Domain forward URL preserve host option
     *
     * @const string
     */
    const onAfterAddDomain = 'onAfterAddDomain';

    /**
     * Event triggered before domain edition
     *
     * iMSCP_Events_Event object parameters:
     *  - domainId     : Domain unique identifier
     *  - domainName   : Domain name
     *  - mountPoint   : Domainmount point
     *  - documentRoot : Domain document root
     *  - forwardUrl   : Domain forward URL, 'no' if no forward URL has been set
     *  - forwardType  : Domain forward URL type
     *  - forwardHost  : Domain forward URL preserve host option
     *
     * @const string
     */
    const onBeforeEditDomain = 'onBeforeEditDomain';

    /**
     * Event triggered after domain edition
     *
     * iMSCP_Events_Event object parameters:
     *  - domainId     : Domain unique identifier
     *  - domainName   : Domain name
     *  - mountPoint   : Domainmount point
     *  - documentRoot : Domain document root
     *  - forwardUrl   : Domain forward URL, 'no' if no forward URL has been
     *                   set
     *  - forwardType  : Domain forward URL type
     *  - forwardHost  : Domain forward URL preserve host option
     *
     * @const string
     */
    const onAfterEditDomain = 'onAfterEditDomain';

    /**
     * Event triggered after subdomain addition
     *
     * iMSCP_Events_Event object parameters:
     *  - subdomainName  : Subdomain name
     *  - subdomainType  : Subdomain type, either 'als' or 'dmn' depending on
     *                     parent domain type
     *  - parentDomainId : Parent domain unique identifier
     *  - mountPoint     : Subdomain mount point
     *  - documentRoot   : Subdomain document root
     *  - forwardUrl     : Subdomain forward URL, 'no' if no forward URL has been set
     *  - forwardType    : Subdomain forward URL type
     *  - forwardHost    : Subdomain forward URL preserve host option
     *  - customerId     : Subdomain owner unique identifier
     *
     * @const string
     */
    const onBeforeAddSubdomain = 'onBeforeAddSubdomain';

    /**
     * Event triggered after subdomain addition
     *
     * iMSCP_Events_Event object parameters:
     *  - subdomainId    : Subdomain unique identifier
     *  - subdomainName  : Subdomain name
     *  - subdomainType  : Subdomain type, either 'als' or 'dmn' depending on
     *                     parent domain type
     *  - parentDomainId : Parent domain unique identifier
     *  - mountPoint     : Subdomain mount point
     *  - documentRoot   : Subdomain document root
     *  - forwardUrl     : Subdomain forward URL, 'no' if no forward URL has
     *                     been set
     *  - forwardType    : Subdomain forward URL type
     *  - forwardHost    : Subdomain forward URL preserve host option
     *  - customerId     : Subdomain owner unique identifier
     *
     * @const string
     */
    const onAfterAddSubdomain = 'onAfterAddSubdomain';

    /**
     * Event triggered before subdomain edition
     *
     * iMSCP_Events_Event object parameters:
     *  - subdomainId   : Subdomain unique identifier
     *  - subdomainName : Subdomain name
     *  - subdomainType : Subdomain type, either 'als' or 'dmn' depending on
     *                    parent domain type
     *  - mountPoint    : Subdomain mount point
     *  - documentRoot  : Subdomain document root
     *  - forwardUrl    : Subdomain forward URL, 'no' if no forward URL has
     *                    been set
     *  - forwardType   : Subdomain forward URL type
     *  - forwardHost   : Subdomain forward URL preserve host option
     *
     * @const string
     */
    const onBeforeEditSubdomain = 'onBeforeEditSubdomain';

    /**
     * Event triggered after subdomain edition
     *
     * iMSCP_Events_Event object parameters:
     *  - subdomainId   : Subdomain unique identifier
     *  - subdomainName : Subdomain name
     *  - subdomainType : Subdomain type, either 'als' or 'dmn' depending on
     *                    parent domain type
     *  - mountPoint    : Subdomain mount point
     *  - documentRoot  : Subdomain document root
     *  - forwardUrl    : Subdomain forward URL, 'no' if no forward URL has
     *                    been set
     *  - forwardType   : Subdomain forward URL type
     *  - forwardHost   : Subdomain forward URL preserve host option
     *
     * @const string
     */
    const onAfterEditSubdomain = 'onAfterEditSubdomain';

    /**
     * Event triggered before subdomain deletion
     *
     * iMSCP_Events_Event object parameters:
     *  - subdomainId   : Subdomain unique identifier
     *  - subdomainName : Subdomain name
     *  - subdomainType : Subdomain type, either 'sub' or 'alssub' depending on
     *                    parent domain type
     *  - type (BC)     : Same as subdomainType field; provided for backward
     *                    compatibility
     *
     * @const string
     */
    const onBeforeDeleteSubdomain = 'onBeforeDeleteSubdomain';

    /**
     * Event triggered after subdomain deletion
     *
     * iMSCP_Events_Event object parameters:
     *  - subdomainId   : Subdomain unique identifier
     *  - subdomainName : Subdomain name
     *  - subdomainType : Subdomain type, either 'sub' or 'alssub' depending on
     *                    parent domain type
     *  - type (BC)     : Same as subdomainType field; provided for backward
     *                    compatibility
     *
     * @const string
     */
    const onAfterDeleteSubdomain = 'onAfterDeleteSubdomain';

    /**
     * Event triggered before domain alias addition
     *
     * iMSCP_Events_Event object parameters:
     *  - domainId: Customer main domain unique identifier
     *  - domainAliasName: Domain alias name
     *  - mountPoint: Domain alias mount point
     *  - documentRoot: Domain alias document root
     *  - forwardUrl: Domain alias forward URL, 'no' if no forward URL has been
     *                set
     *  - forwardType: Domain alias forward URL type
     *  - forwardHost: Domain alias forward URL preserve host option
     *
     * @const string
     */
    const onBeforeAddDomainAlias = 'onBeforeAddDomainAlias';

    /**
     * Event triggered after domain alias addition
     *
     * iMSCP_Events_Event object parameters:
     *  - domainId: Customer main domain unique identifier
     *  - domainAliasId: Domain alias unique identifier
     *  - domainAliasName: Domain alias name
     *  - mountPoint: Domain alias mount point
     *  - documentRoot: Domain alias document root
     *  - forwardUrl: Domain alias forward URL, 'no' if no forward URL has been
     *                set
     *  - forwardType: Domain alias forward URL type
     *  - forwardHost: Domain alias forward URL preserve host optio
     *
     * @const string
     */
    const onAfterAddDomainAlias = 'onAfterAddDomainAlias';

    /**
     * Event triggered before domain alias edition
     *
     * iMSCP_Events_Event object parameters:
     *  - domainAliasId  : Domain alias unique identifier
     *  - domainAliasName: Domain alias name
     *  - mountPoint     : Domain alias mount point
     *  - documentRoot   : Domain alias document root
     *  - forwardUrl     : Domain alias forward URL, 'no' if not forward URL
     *                     has been set
     *  - forwardType    : Domain alias forward URL type
     *  - forwardHost    : Domain alias forward URL preserve host option
     *
     * @const string
     */
    const onBeforeEditDomainAlias = 'onBeforeEditDomainAlias';

    /**
     * Event triggered after domain alias edition
     *
     * iMSCP_Events_Event objectparameters:
     *  - domainAliasId   : Domain alias unique identifier
     *  - domainAliasName : Domain alias name
     *  - mountPoint      : Domain alias mount point
     *  - documentRoot    : Domain alias document root
     *  - forwardUrl      : Domain alias forward URL, 'no' if no forward URL
     *                      has been set
     *  - forwardType     : Domain alias forward URL type
     *  - forwardHost     : Domain alias forward URL preserve host option
     *
     * @const string
     */
    const onAfterEditDomainAlias = 'onAfterEditDomainAlias';

    /**
     * Event triggered before domain alias deletion
     *
     * The listeners receive an iMSCP_Events_Event object with the following
     * parameter:
     *
     * - domainAliasId : Domain alias unique identifier
     *
     * @const string
     */
    const onBeforeDeleteDomainAlias = 'onBeforeDeleteDomainAlias';

    /**
     * Event triggered before domain alias deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - domainAliasId : Domain alias unique identifier
     *
     * @const string
     */
    const onAfterDeleteDomainAlias = 'onAfterDeleteDomainAlias';

    /**
     * Event triggered before mail account addition.
     *
     * iMSCP_Events_Event object parameters:
     *  - mailType     : Mail account type (normal, forward, normal+forward)
     *  - mailUsername : Mail account local part
     *  - forwardList  : Mail forward list (only relevant for forward and/or normal+forward mail accounts)
     *  - mailAddress  : Mail account address
     *
     * @const string
     */
    const onBeforeAddMail = 'onBeforeAddMail';

    /**
     * Event triggered after mail account addition
     *
     * iMSCP_Events_Event object parameters:
     *  - mailId       : Mail account unique identifier
     *  - mailType     : Mail account type (normal, forward, normal+forward)
     *  - forwardList  : Mail forward list (only relevant for forward and/or normal+forward mail accounts)
     *  - mailUsername : Mail account local part
     *  - mailAddress  : Mail account address
     *
     * @const string
     */
    const onAfterAddMail = 'onAfterAddMail';

    /**
     * Event triggered before mail account edition
     *
     * iMSCP_Events_Event object parameter:
     *  - mailId : mailId: Mail account unique identifier
     *
     * @const string
     */
    const onBeforeEditMail = 'onBeforeEditMail';

    /**
     * Event triggered after mail account edition
     *
     * iMSCP_Events_Event object parameter:
     *  - mailId : mailId: Mail account unique identifier
     *
     * @const string
     */
    const onAfterEditMail = 'onAfterEditMail';

    /**
     * Event triggered before mail account deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - mailId : mailId: Mail account unique identifier
     *
     * @const string
     */
    const onBeforeDeleteMail = 'onBeforeDeleteMail';

    /**
     * Event triggered after mail account deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - mailId : Mail account unique identifier
     *
     * @const string
     */
    const onAfterDeleteMail = 'onAfterDeleteMail';

    /**
     * Event triggered before catch-all account addition
     *
     * iMSCP_Events_Event object parameters:
     *  - mailCatchallDomain    : Catch-all account domain
     *  - mailCatchallAddresses : Catch-all account addresses
     *
     * @const string
     */
    const onBeforeAddMailCatchall = 'onBeforeAddMailCatchall';

    /**
     * Event triggered after catch-all account addition
     *
     * iMSCP_Events_Event object parameters:
     *  - mailCatchallId        : Catch-all account unique identifier
     *  - mailCatchallDomain    : Catch-all account domain
     *  - mailCatchallAddresses : Catch-all account addresses
     *
     * @const string
     */
    const onAfterAddMailCatchall = 'onAfterAddMailCatchall';

    /**
     * Event triggered before catch-all acount deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - mailCatchallId : Catch-all account unique identifier
     *
     * @const string
     */
    const onBeforeDeleteMailCatchall = 'onBeforeDeleteMailCatchall';

    /**
     * Event triggered after catch-all acount deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - mailCatchallId : Catch-all account unique identifier
     *
     * @const string
     */
    const onAfterDeleteMailCatchall = 'onAfterDeleteMailCatchall';

    /**
     * Event triggered before execution of an SQL statement
     *
     *
     * @const string
     */
    const onBeforeQueryExecute = 'onBeforeQueryExecute';

    /**
     * Event triggered after execution of an SQL prepared statement
     *
     * iMSCP_Database_Events_Statement object parameters:
     *  - statement : iMSCP_Database_ResultSet object object
     *
     * @const string
     */
    const onAfterQueryExecute = 'onAfterQueryExecute';

    /**
     * Event triggered before template loading
     *
     * iMSCP_Events_Event object parameters:
     *  - context      : \iMSCP\TemplateEngine instance
     *  - templatePath : Template path
     *
     * @const string
     */
    const onBeforeLoadTemplateFile = 'onBeforeLoadTemplateFile';

    /**
     * Event triggered on template parsing
     *
     * iMSCP_Events_Event object parameters:
     *  - pname          : Parent template name
     *  - tname          : template name
     *  - templateEngine : \iMSCP\TemplateEngine instance
     *
     * @const string
     */
    const onParseTemplate = 'onParseTemplate';

    /**
     * Event triggered before navigation generation
     *
     * iMSCP_Events_Event object parameter:
     *  - templateEngine: \iMSCP\TemplateEngine instance
     *
     * @const string
     */
    const onBeforeGenerateNavigation = 'onBeforeGenerateNavigation';

    /**
     * Event triggered after navigation generation
     *
     * iMSCP_Events_Event object parameter:
     *  - templateEngine: \iMSCP\TemplateEngine instance
     *
     * @const string
     *
     */
    const onAfterGenerateNavigation = 'onAfterGenerateNavigation';

    /**
     * Event triggered before domain (customer account) activation/deactivation
     *
     * iMSCP_Events_Event object parameters:
     *  - customerId : Customer unique identifier
     *  - action     : The action taking place, either 'activate' or 'deactivate'
     *
     * @const string
     */
    const onBeforeChangeDomainStatus = 'onBeforeChangeDomainStatus';

    /**
     * Event triggered after domain (customer account) activation/deactivation
     *
     * iMSCP_Events_Event object parameters:
     *  - customerId : Customer unique identifier
     *  - action     : The action taking place, either 'activate' or 'deactivate'
     *
     * @const string
     */
    const onAfterChangeDomainStatus = 'onAfterChangeDomainStatus';

    /**
     * Event triggered before an admin or reseller send a circular
     *
     * iMSCP_Events_Event object parameters:
     *  - sender_name  : Sender name
     *  - sender_email : Sender email
     *  - rcpt_to      : Recipient type, either 'all_users',
     *                  'administrators_resellers', 'administrators_customers',
     *                  'resellers_customers', 'administrators', 'resellers' or
     *                  'customers'
     *  - subject      : subject
     *  - body         : body
     *
     * @const string
     */
    const onBeforeSendCircular = 'onBeforeSendCircular';

    /**
     * Event triggered after an admin or reseller has sent a circular
     *
     * iMSCP_Events_Event object parameters:
     *  - sender_name  : Sender name
     *  - sender_email : Sender email
     *  - rcpt_to      : Recipient type, either 'all_users',
     *                  'administrators_resellers', 'administrators_customers',
     *                  'resellers_customers', 'administrators', 'resellers' or
     *                  'customers'
     *  - subject      : subject
     *  - body         : body
     *
     * @const string
     */
    const onAfterSendCircular = 'onAfterSendCircular';

    /**
     * Event triggered by the i18n_getJsTranslations() function
     *
     * iMSCP_Events_Event object parameters:
     *  - translations : An ArrayObject that allows third-party components to add
     *                   their own JS translations
     *
     * @see i18n_getJsTranslations() for more details
     * @const string
     */
    const onGetJsTranslations = 'onGetJsTranslations';

    /**
     * Event triggered by the send_mail() function
     *
     * iMSCP_Events_Event object parameters:
     *  - mail_data : An ArrayObject that allows third-party components to
     *                override mail data which are:
     *               - mail_id      : Mail unique identifier
     *               - fname        : OPTIONAL Receiver firstname
     *               - lname        : OPTIONAL Receiver lastname
     *               - username     : Receiver username
     *               - email        : Receiver email
     *               - sender_name  : Sender name added in Reply-To header
     *               - sender_email : E-mail address added in Reply-To' header
     *               - subject      : Subject of the email to be sent
     *               - message      : Message to be sent
     *               - placeholders : OPTIONAL An array where keys are
     *                                placeholders to replace and values, the
     *                                replacement values. These placeholders
     *                                take precedence on the default
     *                                placeholders.
     *
     * @const string
     */
    const onSendMail = 'onSendMail';

    /**
     * Event triggered on IP address addition
     *
     * iMSCP_Events_Event object parameters:
     *  - ip_number      : IP address
     *  - ip_netmask     : IP netmask
     *  - ip_card        : Network interface to which IP address is attached
     *  - ip_config_mode : Ip address configuration mode (auto|manual)
     *
     * @const string
     */
    const onAddIpAddr = 'onAddIpAddr';

    /**
     * Event triggered on IP address edition
     *
     * iMSCP_Events_Event object parameters:
     *  - ip_id          : IP address unique identifier
     *  - ip_number      : IP address
     *  - ip_netmask     : IP netmask
     *  - ip_card        : Network interface to which IP address is attached
     *  - ip_config_mode : Ip address configuration mode (auto|manual)
     *
     * @const string
     */
    const onEditIpAddr = 'onEditIpAddr';

    /**
     * Event triggered on IP address deletion
     *
     * iMSCP_Events_Event object parameter:
     *  - ip_id : IP address unique identifier
     *
     * @const string
     */
    const onDeleteIpAddr = 'onDeleteIpAddr';

    /**
     * Event triggered on page messages generation
     *
     * iMSCP_Events_Event object parameter:
     * - flashMessenger : Zend_Controller_Action_Helper_FlashMessenger instance
     *
     * @const string
     */
    const onGeneratePageMessages = 'onGeneratePageMessages';
}
