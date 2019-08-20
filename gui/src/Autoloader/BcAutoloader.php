<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

declare(strict_types=1);

namespace iMSCP\Autoloader;

/**
 * Class BcAutoloader - Alias legacy i-MSCP classes/interfaces to the news
 * @package iMSCP\Autoloader
 */
class BcAutoloader
{
    /**
     * @var array Maps legacy i-MSCP classes/interface to the news
     */
    private static $map = [
        // library/iMSCP
        'iMSCP_Application'                        => 'iMSCP\\Application',
        'iMSCP_Authentication'                    => 'iMSCP\\Authentication\\AuthService',
        'iMSCP_Config'                             => 'iMSCP\\Config\\Config',
        'iMSCP_Database'                           => 'iMSCP\\Database\\DatabaseMySQL',
        'iMSCP_Events'                             => 'iMSCP\\Event\\Events',
        'iMSCP_Exception'                          => 'iMSCP\\Exception\\Exception',
        'iMSCP_Net'                                => 'iMSCP\\Net',
        'iMSCP_PHPini'                             => 'iMSCP\\PhpEditor',
        'iMSCP_Registry'                           => 'iMSCP\\Registry',
        'iMSCP_Services'                           => 'iMSCP\\Services',
        'iMSCP_SystemInfo'                         => 'iMSCP\\SystemInfo',
        'iMSCP_Update'                             => 'iMSCP\\Update\\AbstractUpdate',
        'iMSCP_Validate'                           => 'iMSCP\\Validate\\Common',
        'iMSCP_pTemplate'                          => 'iMSCP\\TemplateEngine',
        // library/iMSCP/Authentication
        'iMSCP_Authentication_AuthEvent'           => 'iMSCP\\Authentication\\AuthEvent',
        'iMSCP_Authentication_Result'              => 'iMSCP\\Authentication\\AuthResult',
        // library/iMSCP/Config
        'iMSCP_Config_Handler_Db'                  => 'iMSCP\\Config\\DbConfig',
        'iMSCP_Config_Handler_File'                => 'iMSCP\\Config\\FileConfig',
        'iMSCP_Config_Handler'                     => 'iMSCP\\Config\\ArrayConfig',
        // library/iMSCP/Database
        'iMSCP_Database_Events_Database'           => 'iMSCP\\Database\\DatabaseEvent',
        'iMSCP_Database_Events_Statement'          => 'iMSCP\\Database\\DatabaseStatementEvent',
        'iMSCP_Database_ResultSet'                 => 'iMSCP\\Database\\DatabaseResultSet',
        // library/iMSCP/Events
        'iMSCP_Events_Listener_Exception'          => 'iMSCP\\Event\\Listener\\ListenerException',
        'iMSCP_Events_Listener_PriorityQueue'      => 'iMSCP\\Event\\Listener\\PriorityQueue',
        'iMSCP_Events_Listener_ResponseCollection' => 'iMSCP\\Event\\Listener\\ResponseCollection',
        'iMSCP_Events_Listener_SplPriorityQueue'   => 'iMSCP\\Event\\Listener\\SplPriorityQueue',
        'iMSCP_Events_Manager_Exception'           => 'iMSCP\\Event\\EventManagerException',
        'iMSCP_Events_Manager_Interface'           => 'iMSCP\\Event\\EventManagerInterface',
        'iMSCP_Events_Aggregator'                  => 'iMSCP\\Event\\EventAggregator',
        'iMSCP_Events_Description'                 => 'iMSCP\\Event\\EventDescription',
        'iMSCP_Events_Event'                       => 'iMSCP\\Event\\Event',
        'iMSCP_Events_Exception'                   => 'iMSCP\\Event\\EventException',
        'iMSCP_Events_Listener'                    => 'iMSCP\\Event\\Listener\\EventListener',
        'iMSCP_Events_Manager'                     => 'iMSCP\\Event\\EventManager',
        // library/iMSCP/Exception
        'iMSCP_Exception_Writer_Abstract'          => 'iMSCP\\Exception\\AbstractExceptionWriter',
        'iMSCP_Exception_Writer_Browser'           => 'iMSCP\\Exception\\BrowserExceptionWriter',
        'iMSCP_Exception_Writer_Mail'              => 'iMSCP\\Exception\\MailExceptionWriter',
        'iMSCP_Exception_Database'                 => 'iMSCP\\Database\\DatabaseException',
        'iMSCP_Exception_Event'                    => 'iMSCP\\Exception\\ExceptionEvent',
        'iMSCP_Exception_Handler'                  => 'iMSCP\\Exception\\ExceptionHandler',
        'iMSCP_Exception_Production'               => 'iMSCP\\Exception\\ProductionException',
        // library/iMSCP/Filter
        'iMSCP_Filter_Compress_Gzip'               => 'iMSCP\\Filter\\GzipFilter',
        'iMSCP_Filter_AlnumAndHyphen'              => 'iMSCP\\Filter\\AlnumAndHyphenFilter',
        'iMSCP_Filter_Noop'                        => 'iMSCP\\Filter\\NoopFilter',
        // library/iMSCP/I18n
        'iMSCP_I18n_Parser_Exception'              => 'iMSCP\\I18n\\ParserException',
        'iMSCP_I18n_Parser_Gettext'                => 'iMSCP\\I18n\\GettextParser',
        // library/iMSCP/Plugin
        'iMSCP_Plugin_Exception_ActionStopped'     => 'iMSCP\\Plugin\\PluginActionStoppedException',
        'iMSCP_Plugin_Action'                      => 'iMSCP\\Plugin\\AbstractPlugin',
        'iMSCP_Plugin_Bruteforce'                  => 'iMSCP\\Plugin\\BruteForce',
        'iMSCP_Plugin_Exception'                   => 'iMSCP\\Plugin\\PluginException',
        'iMSCP_Plugin_Manager'                     => 'iMSCP\\Plugin\\PluginManager',
        // library/iMSCP/Update
        'iMSCP_Update_Database'                    => 'iMSCP\\Update\\DatabaseUpdate',
        'iMSCP_Update_Exception'                   => 'iMSCP\\Update\\UpdateException',
        'iMSCP_Update_Version'                     => 'iMSCP\\Update\\VersionUpdate',
        // library/iMSCP/Uri
        'iMSCP_Uri_Exception'                      => 'iMSCP\\Uri\\UriException',
        'iMSCP_Uri_Redirect'                       => 'iMSCP\\Uri\\UriRedirect',
        // library/iMSCP/Utility
        'iMSCP_Utility_OpcodeCache'                => 'iMSCP\\Utility\\OpcodeCache',
        // library/iMSCP/Validate
        'iMSCP_Validate_AlnumAndHyphen'            => 'iMSCP\\Validate\\AlnumAndHyphen',
        'iMSCP_Validate_Uri'                       => 'iMSCP\\Validate\\Uri'
    ];

    /**
     * Register an autoloader for legacy iMSCP classes/interfaces.
     *
     * This autoloader is prepended to the stack of already registered
     * autoloaders. If the class/interface to be loaded is a legacy one, it will
     * be aliased to the new one which in turn will be loaded by the composer
     * autoloader.
     *
     * @return void
     */
    public static function register(): void
    {
        spl_autoload_register(
            self::createAutoloader(), true, true
        );
    }

    /**
     * Create autoloader.
     *
     * @return callable
     */
    private static function createAutoloader(): callable
    {
        return function ($class) {
            if (!isset(self::$map[$class])) {
                return;
            }

            class_alias(self::$map[$class], $class);
        };
    }
}
