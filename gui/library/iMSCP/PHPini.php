<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * Class iMSCP_PHPini
 */
class iMSCP_PHPini
{
    /**
     * @var iMSCP_PHPini
     */
    static protected $instance;

    /**
     * @var array Reseller PHP permissions (including limits for configuration options)
     */
    protected $resellerPermissions = array();

    /**
     * @var array Client PHP permissions
     */
    protected $clientPermissions = array();

    /**
     * @var array Domain configuration options
     */
    protected $domainIni = array();

    /**
     * @var bool Tells whether or not domain INI values are set with default values
     */
    protected $isDefaultDomainIni = true;

    /**
     * Singleton object - Make new unavailable
     */
    private function __construct()
    {

    }

    /**
     * Implements singleton design pattern
     *
     * @return iMSCP_PHPini
     */
    static public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Makes clone unavailable
     *
     * @return void
     */
    private function __clone()
    {

    }

    /**
     * Loads reseller PHP permissions
     *
     * If a reseller identifier is given, try to load permissions from
     * database, else, load default reseller permissions.
     *
     * Note: Reseller permissions also include limits for PHP configuration
     * options.
     *
     * @param int|null $resellerId Reseller unique identifier
     * @return void
     */
    public function loadResellerPermissions($resellerId = null)
    {
        if (null !== $resellerId) {
            $stmt = exec_query(
                '
                    SELECT
                        php_ini_system, php_ini_al_disable_functions, php_ini_al_mail_function, php_ini_al_mail_function,
                        php_ini_al_allow_url_fopen, php_ini_al_display_errors, php_ini_max_post_max_size,
                        php_ini_max_upload_max_filesize, php_ini_max_max_execution_time, php_ini_max_max_input_time,
                        php_ini_max_memory_limit
                    FROM
                        reseller_props
                    WHERE
                        reseller_id = ?
                ',
                $resellerId
            );

            if ($stmt->rowCount()) {
                $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

                // PHP permissions
                $this->resellerPermissions['phpiniSystem'] = $row['php_ini_system'];
                $this->resellerPermissions['phpiniAllowUrlFopen'] = $row['php_ini_al_allow_url_fopen'];
                $this->resellerPermissions['phpiniDisplayErrors'] = $row['php_ini_al_display_errors'];
                $this->resellerPermissions['phpiniDisableFunctions'] = $row['php_ini_al_disable_functions'];
                $this->resellerPermissions['phpiniMailFunction'] = $row['php_ini_al_mail_function'];

                // Limits for PHP configuration options
                $this->resellerPermissions['phpiniPostMaxSize'] = $row['php_ini_max_post_max_size'];
                $this->resellerPermissions['phpiniUploadMaxFileSize'] = $row['php_ini_max_upload_max_filesize'];
                $this->resellerPermissions['phpiniMaxExecutionTime'] = $row['php_ini_max_max_execution_time'];
                $this->resellerPermissions['phpiniMaxInputTime'] = $row['php_ini_max_max_input_time'];
                $this->resellerPermissions['phpiniMemoryLimit'] = $row['php_ini_max_memory_limit'];
                return;
            }
        }

        // Default PHP permissions
        $this->resellerPermissions['phpiniSystem'] = 'no';
        $this->resellerPermissions['phpiniAllowUrlFopen'] = 'no';
        $this->resellerPermissions['phpiniDisplayErrors'] = 'no';
        $this->resellerPermissions['phpiniDisableFunctions'] = 'no';
        $this->resellerPermissions['phpiniMailFunction'] = 'yes';

        // Default limits for PHP configuration options
        $this->resellerPermissions['phpiniPostMaxSize'] = 8;
        $this->resellerPermissions['phpiniUploadMaxFileSize'] = 2;
        $this->resellerPermissions['phpiniMaxExecutionTime'] = 30;
        $this->resellerPermissions['phpiniMaxInputTime'] = 60;
        $this->resellerPermissions['phpiniMemoryLimit'] = 128;
    }

    /**
     * Saves reseller PHP permissions
     *
     * @param int $resellerId Reseller unique identifier
     * @return void
     */
    public function saveResellerPermissions($resellerId)
    {
        exec_query(
            '
                UPDATE
                    reseller_props
                SET
                    php_ini_system = ?, php_ini_al_disable_functions = ?, php_ini_al_mail_function = ?,
                    php_ini_al_mail_function = ?, php_ini_al_allow_url_fopen = ?, php_ini_al_display_errors = ?,
                    php_ini_max_post_max_size = ?, php_ini_max_upload_max_filesize = ?, php_ini_max_max_execution_time = ?,
                    php_ini_max_max_input_time = ?, php_ini_max_memory_limit = ?
                WHERE
                    reseller_id = ?
            ',
            array(
                $this->resellerPermissions['phpiniSystem'],
                $this->resellerPermissions['phpiniAllowUrlFopen'],
                $this->resellerPermissions['phpiniDisplayErrors'],
                $this->resellerPermissions['phpiniDisableFunctions'],
                $this->resellerPermissions['phpiniMailFunction'],
                $this->resellerPermissions['phpiniPostMaxSize'],
                $this->resellerPermissions['phpiniUploadMaxFileSize'],
                $this->resellerPermissions['phpiniMaxExecutionTime'],
                $this->resellerPermissions['phpiniMaxInputTime'],
                $this->resellerPermissions['phpiniMemoryLimit'],
                $resellerId
            )
        );
    }

    /**
     * Sets the value of a reseller PHP permission
     *
     * We are safe here. New value is set only if valid.
     *
     * @throws iMSCP_Exception if $permission is unknown
     * @param string $permission Permission name
     * @param string $value Permission value
     * @return void
     */
    public function setResellerPermission($permission, $value)
    {
        switch ($permission) {
            case 'phpiniSystem':
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
            case 'phpiniMailFunction':
            case 'phpiniDisableFunctions':
                if ($this->validatePermission($permission, $value)) {
                    $this->resellerPermissions[$permission] = $value;
                }
                break;
            case 'phpiniMemoryLimit':
            case 'phpiniMaxInputTime':
            case 'phpiniMaxExecutionTime':
                if (is_number($value) && $value >= 1 && $value <= 10000) {
                    $this->resellerPermissions[$permission] = $value;
                }
                break;
            case 'phpiniPostMaxSize':
                // According PHP doc, post_max_size value must be lower than memory_limit value
                if (is_number($value) && $value < $this->resellerPermissions['phpiniMemoryLimit'] && $value >= 1 && $value <= 10000) {
                    $this->resellerPermissions[$permission] = $value;
                }
                break;
            case 'phpiniUploadMaxFileSize':
                // According PHP doc, max_upload_filesize value must be lower than post_max_size value
                if (is_number($value) && $value < $this->resellerPermissions['phpiniPostMaxSize'] && $value >= 1 && $value <= 10000) {
                    $this->resellerPermissions[$permission] = $value;
                }
                break;
            default:
                throw new iMSCP_Exception(sprintf('Unknown `%s` reseller PHP permission.', $permission));
        }
    }

    /**
     * Gets reseller PHP permission(s)
     *
     * @throws iMSCP_Exception if $permission is unknown
     * @param string|null $permission Permission name or null for all permissions
     * @return mixed
     */
    public function getResellerPermission($permission = null)
    {
        if (null === $permission) {
            return $this->resellerPermissions;
        }

        if (!array_key_exists($permission, $this->resellerPermissions)) {
            throw new iMSCP_Exception(sprintf('Unknown `%s` reseller PHP permission.', $permission));
        }

        return $this->resellerPermissions[$permission];
    }

    /**
     * Does the reseller as the given PHP permission?
     *
     * @throws iMSCP_Exception if $permission is unknown
     * @param string $permission Permission
     * @return bool TRUE if $key is a known and reseller has permission on it
     */
    public function resellerHasPermission($permission)
    {
        if ($this->resellerPermissions['phpiniSystem'] !== 'yes') {
            return false;
        }

        switch ($permission) {
            case 'phpiniSystem':
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
            case 'phpiniDisableFunctions':
            case 'phpiniMailFunction':
                return $this->resellerPermissions[$permission] === 'yes';
            default;
                throw new iMSCP_Exception(sprintf('Unknown `%s` reseller PHP permission.', $permission));
        }
    }

    /**
     * Loads client PHP permissions
     *
     * If a client identifier is given, try to load permissions from
     * database, else, load default client permissions.
     *
     * @throws iMSCP_Exception
     * @throws iMSCP_Exception_Database
     * @param int|null $clientId Domain unique identifier
     */
    public function loadClientPermissions($clientId = null)
    {
        if (empty($this->resellerPermissions)) {
            throw new iMSCP_Exception('You must first load reseller permissions');
        }

        if (null !== $clientId) {
            $stmt = exec_query(
                '
                    SELECT phpini_perm_system, phpini_perm_allow_url_fopen, phpini_perm_display_errors,
                        phpini_perm_disable_functions, phpini_perm_mail_function
                    FROM domain WHERE domain_admin_id = ?
                ',
                $clientId
            );

            if ($stmt->rowCount()) {
                $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
                $this->clientPermissions['phpiniSystem'] = $row['phpini_perm_system'];
                $this->clientPermissions['phpiniAllowUrlFopen'] = $row['phpini_perm_allow_url_fopen'];
                $this->clientPermissions['phpiniDisplayErrors'] = $row['phpini_perm_display_errors'];
                $this->clientPermissions['phpiniDisableFunctions'] = $row['phpini_perm_disable_functions'];
                $this->clientPermissions['phpiniMailFunction'] = $row['phpini_perm_mail_function'];
                return;
            }
        }

        $this->clientPermissions['phpiniSystem'] = 'no';
        $this->clientPermissions['phpiniAllowUrlFopen'] = 'no';
        $this->clientPermissions['phpiniDisplayErrors'] = 'no';
        $this->clientPermissions['phpiniDisableFunctions'] = 'no';

        if ($this->resellerHasPermission('phpiniMailFunction')) {
            $this->clientPermissions['phpiniMailFunction'] = 'yes';
        } else {
            $this->clientPermissions['phpiniMailFunction'] = 'no';
        }
    }

    /**
     * Saves client PHP permissions
     *
     * @param int $clientId Client unique identifier
     * @return void
     */
    public function saveClientPermissions($clientId)
    {
        exec_query(
            '
                UPDATE
                    domain
                SET
                    phpini_perm_system = ?, phpini_perm_allow_url_fopen = ?, phpini_perm_display_errors = ?,
                    phpini_perm_disable_functions = ?, phpini_perm_mail_function = ?
                WHERE
                    domain_admin_id = ?
            ',
            array(
                $this->clientPermissions['phpiniSystem'], $this->clientPermissions['phpiniAllowUrlFopen'],
                $this->clientPermissions['phpiniDisplayErrors'], $this->clientPermissions['phpiniDisableFunctions'],
                $this->clientPermissions['phpiniMailFunction'], $clientId
            )
        );
    }

    /**
     * Sets the value of a client PHP permission
     *
     * We are safe here. New value is set only if valid.
     *
     * @param string $permission Permission name
     * @param string $value Permission value
     * @return void
     */
    public function setClientPermission($permission, $value)
    {
        if (!$this->validatePermission($permission, $value) || !$this->resellerHasPermission($permission)) {
            return;
        }

        $this->clientPermissions[$permission] = $value;

        if ($permission == 'phpiniAllowUrlFopen' && $value != 'yes') {
            $this->domainIni['phpiniAllowUrlFopen'] = 'off';
        }

        if ($permission == 'phpiniDisplayErrors' && $value != 'yes') {
            $this->domainIni['phpiniDisplayErrors'] = 'off';
        }

        if ($permission == 'phpiniDisableFunctions' && $value != 'yes') {
            if ($value == 'no') {
                $this->domainIni['phpiniDisableFunctions'] = 'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system';
            } else { // exec only
                if (in_array('exec', explode(',', $this->domainIni['phpiniDisableFunctions']))) {
                    $this->domainIni['phpiniDisableFunctions'] = 'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system';
                } else {
                    $this->domainIni['phpiniDisableFunctions'] = 'passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system';
                }
            }

            if (!$this->clientHasPermission('phpiniMailFunction')) {
                $this->domainIni['phpiniDisableFunctions'] .= ',mail';
            }
        }

        if ($permission == 'phpiniMailFunction' && $value == 'no') {
            $disabledFunctions = explode(',', $this->getDomainIni('phpiniDisableFunctions'));

            if (!in_array('mail', $disabledFunctions)) {
                $disabledFunctions[] = 'mail';
                $this->domainIni['phpiniDisableFunctions'] = $this->assembleDisableFunctions($disabledFunctions);
            }
        }
    }

    /**
     * Gets client PHP permission(s)
     *
     * @throws iMSCP_Exception if $permission is unknown
     * @param string|null $permission Permission name or null for all permissions
     * @return mixed
     */
    public function getClientPermission($permission = null)
    {
        if (null === $permission) {
            return $this->clientPermissions;
        }

        if (!array_key_exists($permission, $this->clientPermissions)) {
            throw new iMSCP_Exception(sprintf('Unknown `%s` client PHP permission.', $permission));
        }

        return $this->clientPermissions[$permission];
    }

    /**
     * Does the client as the given PHP permission?
     *
     * Be aware that in case of the phpiniDisableFunctions, true is returned
     * as long as the client has either 'exec' or 'full' permission.
     *
     * @throws iMSCP_Exception if $permission is unknown
     * @param string $permission Permission
     * @return bool TRUE if $key is a known and client has permission on it
     */
    public function clientHasPermission($permission)
    {
        if ($this->resellerPermissions['phpiniSystem'] != 'yes') {
            return false;
        }

        switch ($permission) {
            case 'phpiniSystem':
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
            case 'phpiniMailFunction':
                return $this->clientPermissions[$permission] == 'yes';
            case 'phpiniDisableFunctions':
                return $this->clientPermissions[$permission] == 'yes' || $this->clientPermissions[$permission] == 'exec';
            default:
                throw new iMSCP_Exception(sprintf('Unknown `%s` client PHP permission.', $permission));
        }
    }

    /**
     * Loads domain configuration options
     *
     * @throws iMSCP_Exception
     * @param int|null $adminId Owner unique identifier
     * @param int|null $domainId Domain unique identifier
     * @param string|null $domainType Domain type (dmn|als|sub|subals)
     */
    public function loadDomainIni($adminId = null, $domainId = null, $domainType = null)
    {
        if (empty($this->clientPermissions)) {
            throw new iMSCP_Exception('You must first load client permissions.');
        }

        if (null !== $adminId && null !== $domainId && null !== $domainType) {
            $stmt = exec_query('SELECT * FROM php_ini WHERE admin_id = ? AND domain_id = ? AND domain_type = ?', array(
                $adminId, $domainId, $domainType
            ));

            if ($stmt->rowCount()) {
                $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
                $this->domainIni['phpiniAllowUrlFopen'] = $row['allow_url_fopen'];
                $this->domainIni['phpiniDisplayErrors'] = $row['display_errors'];
                $this->domainIni['phpiniErrorReporting'] = $row['error_reporting'];
                $this->domainIni['phpiniDisableFunctions'] = $row['disable_functions'];
                $this->domainIni['phpiniPostMaxSize'] = $row['post_max_size'];
                $this->domainIni['phpiniUploadMaxFileSize'] = $row['upload_max_filesize'];
                $this->domainIni['phpiniMaxExecutionTime'] = $row['max_execution_time'];
                $this->domainIni['phpiniMaxInputTime'] = $row['max_input_time'];
                $this->domainIni['phpiniMemoryLimit'] = $row['memory_limit'];

                $this->isDefaultDomainIni = false;
                return;
            }
        }

        $this->domainIni['phpiniAllowUrlFopen'] = 'off';
        $this->domainIni['phpiniDisplayErrors'] = 'off';
        $this->domainIni['phpiniErrorReporting'] = 'E_ALL & ~E_DEPRECATED & ~E_STRICT'; // Production value
        $this->domainIni['phpiniDisableFunctions'] =
            'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system';

        if (!$this->clientHasPermission('phpiniMailFunction')) {
            $this->domainIni['phpiniDisableFunctions'] .= ',mail';
        }

        // Value taken from Debian default php.ini file
        $this->domainIni['phpiniMemoryLimit'] = min($this->resellerPermissions['phpiniMemoryLimit'], 128);
        $this->domainIni['phpiniPostMaxSize'] = min($this->resellerPermissions['phpiniPostMaxSize'], 8);
        $this->domainIni['phpiniUploadMaxFileSize'] = min($this->resellerPermissions['phpiniUploadMaxFileSize'], 2);
        $this->domainIni['phpiniMaxExecutionTime'] = min($this->resellerPermissions['phpiniMaxExecutionTime'], 30);
        $this->domainIni['phpiniMaxInputTime'] = min($this->resellerPermissions['phpiniMaxInputTime'], 60);

        $this->isDefaultDomainIni = true;
    }

    /**
     * Saves domain configuration options
     *
     * @throws iMSCP_Exception if domain PHP configuration options were not loaded
     * @param int $adminId Owner unique identifier
     * @param int $domainId Domain unique identifier
     * @param string $domainType Domain type (dmn|als|sub|subals)
     */
    public function saveDomainIni($adminId, $domainId, $domainType)
    {
        if (!$this->domainIni) {
            throw new iMSCP_Exception('Domain PHP INI directives were not loaded.');
        }

        $stmt = exec_query(
            'SELECT COUNT(admin_id) AS cnt FROM php_ini WHERE admin_id = ? AND domain_id = ? AND domain_type = ? ',
            array($adminId, $domainId, $domainType)
        );
        $row = $stmt->fetchRow();

        if ($row['cnt'] > 0) {
            exec_query(
                '
                    UPDATE
                        php_ini
                    SET
                        disable_functions = ?, allow_url_fopen = ?, display_errors = ?, error_reporting = ?,
                        post_max_size = ?, upload_max_filesize = ?, max_execution_time = ?, max_input_time = ?,
                        memory_limit = ?
                    WHERE
                        admin_id = ?
                    AND
                        domain_id = ?
                    AND
                        domain_type = ?
                ',
                array(
                    $this->domainIni['phpiniDisableFunctions'], $this->domainIni['phpiniAllowUrlFopen'],
                    $this->domainIni['phpiniDisplayErrors'], $this->domainIni['phpiniErrorReporting'],
                    $this->domainIni['phpiniPostMaxSize'], $this->domainIni['phpiniUploadMaxFileSize'],
                    $this->domainIni['phpiniMaxExecutionTime'], $this->domainIni['phpiniMaxInputTime'],
                    $this->domainIni['phpiniMemoryLimit'], $adminId, $domainId, $domainType
                )
            );
            return;
        }

        exec_query(
            '
                INSERT INTO php_ini (
                    admin_id, domain_id, domain_type, disable_functions, allow_url_fopen, display_errors,
                    error_reporting, post_max_size, upload_max_filesize, max_execution_time, max_input_time,
                    memory_limit
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )
            ',
            array(
                $adminId, $domainId, $domainType, $this->domainIni['phpiniDisableFunctions'],
                $this->domainIni['phpiniAllowUrlFopen'], $this->domainIni['phpiniDisplayErrors'],
                $this->domainIni['phpiniErrorReporting'], $this->domainIni['phpiniPostMaxSize'],
                $this->domainIni['phpiniUploadMaxFileSize'], $this->domainIni['phpiniMaxExecutionTime'],
                $this->domainIni['phpiniMaxInputTime'], $this->domainIni['phpiniMemoryLimit']
            )
        );
    }

    /**
     * Sets the value of a domain configuration option
     *
     * We are safe here. New value is set only if valid.
     *
     * @throws iMSCP_Exception
     * @param string $varname Configuration option name
     * @param string $value Configuration option value
     */
    public function setDomainIni($varname, $value)
    {
        if (empty($this->clientPermissions)) {
            throw new iMSCP_Exception('You must first load client permissions.');
        }

        if (!$this->validateDomainIni($varname, $value)) {
            return;
        }

        switch ($varname) {
            case 'phpiniPostMaxSize':
            case 'phpiniUploadMaxFileSize':
            case 'phpiniMaxExecutionTime':
            case 'phpiniMaxInputTime':
            case 'phpiniMemoryLimit':
                if ($value > $this->getResellerPermission($varname)) {
                    return;
                }
                break;
            default:
                if (!$this->clientHasPermission($varname)) {
                    return;
                }
        }

        $this->domainIni[$varname] = $value;
        $this->isDefaultDomainIni = false;
    }

    /**
     * Gets domain configuration option(s)
     *
     * @throws iMSCP_Exception if $varname is unknown
     * @param string|null $varname Domain configuration option name or null for all configuration options
     * @return mixed
     */
    public function getDomainIni($varname = null)
    {
        if (null === $varname) {
            return $this->domainIni;
        }

        if (!array_key_exists($varname, $this->domainIni)) {
            throw new iMSCP_Exception(sprintf('Unknown `%s` domain configuration option.', $varname));
        }

        return $this->domainIni[$varname];
    }

    /**
     * Whether or not domain INI values are set with default values
     *
     * @return boolean
     */
    public function isDefaultDomainIni()
    {
        return $this->isDefaultDomainIni;
    }

    /**
     * Validate value for the given PHP permission
     *
     * @throws iMSCP_Exception if $permission is unknown
     * @param string $permission Permision name
     * @param string $value Permission value
     * @return bool TRUE if $permission is valid, FALSE otherwise
     *
     */
    public function validatePermission($permission, $value)
    {
        switch ($permission) {
            case 'phpiniSystem':
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
            case 'phpiniMailFunction':
                return $value === 'yes' || $value === 'no';
            case 'phpiniDisableFunctions':
                return $value === 'yes' || $value === 'no' || $value === 'exec';
            default:
                throw new iMSCP_Exception(sprintf('Unknown `%s` PHP permission.', $permission));
        }
    }

    /**
     * Validate value for the given domain PHP configuration option
     *
     * Be aware that we don't allow unlimited values. This is by design.
     *
     * @throws iMSCP_Exception if $varname is unknown
     * @param string $varname Configuration option name
     * @param string $value Configuration option value
     * @return bool TRUE if $value is valid, FALSE otherwise
     */
    public function validateDomainIni($varname, $value)
    {
        switch ($varname) {
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
                return $value === 'on' || $value === 'off';
            case 'phpiniErrorReporting':
                return
                    $value === 'E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED' // Default value
                    || $value === '-1' // All error (development value)
                    || $value === 'E_ALL & ~E_DEPRECATED & ~E_STRICT'; // Production
            case 'phpiniDisableFunctions':
                $allowedFunctionNames = array(
                    'exec', 'mail', 'passthru', 'phpinfo', 'popen', 'proc_open', 'show_source', 'shell', 'shell_exec',
                    'symlink', 'system'
                );

                return array_diff(explode(',', $value), $allowedFunctionNames) ? false : true;
            case 'phpiniMemoryLimit':
            case 'phpiniMaxExecutionTime':
            case 'phpiniMaxInputTime':
                return is_number($value) && $value >= 1 && $value <= 10000;
            case 'phpiniPostMaxSize':
                // According PHP doc, post_max_size value must be lower than memory_limit value
                return is_number($value) && $value < $this->domainIni['phpiniMemoryLimit'] && $value >= 1 && $value <= 10000;
            case 'phpiniUploadMaxFileSize':
                // According PHP doc, max_upload_filesize value must be lower than post_max_size value
                return is_number($value) && $value < $this->domainIni['phpiniPostMaxSize'] && $value >= 1 && $value <= 10000;
            case 'phpiniOpenBaseDir':
                return is_string($value);
            default:
                throw new iMSCP_Exception(sprintf('Unknown `%s` configuration option.', $varname));
        }
    }

    /**
     * Assemble disable_functions parameter from its parts
     *
     * @param array $disabledFunctions List of disabled function
     * @return string
     */
    public function assembleDisableFunctions(array $disabledFunctions)
    {
        return implode(',', array_unique($disabledFunctions));
    }

    /**
     * Update domain configuration options for the given client
     *
     * @throws iMSCP_Exception_Database
     * @param int $clientId Client identifier
     * @return void
     */
    public function updateDomainConfigOptions($clientId)
    {
        $config = iMSCP_Registry::get('config');
        $confDir = $config['CONF_DIR'];

        if ($config['HTTPD_SERVER'] == 'apache_fcgid' || $config['HTTPD_SERVER'] == 'apache_itk') {
            $srvConfig = new iMSCP_Config_Handler_File("$confDir/apache/apache.data");
            $configLevel = $srvConfig['INI_LEVEL'];
        } else {
            $srvConfig = new iMSCP_Config_Handler_File("$confDir/php-fpm/phpfpm.data");
            $configLevel = $srvConfig['PHP_FPM_POOLS_LEVEL'];
        }

        $stmt = exec_query('SELECT id, domain_id, domain_type FROM php_ini WHERE admin_id = ?', $clientId);

        $domainConfOptions = $this->getDomainIni();

        while ($row = $stmt->fetchRow()) {
            try {
                if (!$this->clientHasPermission('phpiniSystem')) {
                    $this->loadDomainIni(); // Load domain default PHP configuration options
                } else {
                    // Load domain PHP configuration options
                    $this->loadDomainIni($clientId, $row['domain_id'], $row['domain_type']);

                    if (!$this->clientHasPermission('phpiniAllowUrlFopen')) {
                        $this->domainIni['phpiniAllowUrlFopen'] = 'off';
                    }

                    if (!$this->clientHasPermission('phpiniDisplayErrors')) {
                        $this->domainIni['phpiniDisplayErrors'] = 'off';
                    }

                    if (!$this->clientHasPermission('phpiniDisableFunctions')) {
                        if ($this->getClientPermission('phpiniDisableFunctions') == 'no') {
                            $this->domainIni['phpiniDisableFunctions'] = 'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system';
                        } else {
                            if (in_array('exec', explode(',', $this->domainIni['phpiniDisableFunctions']))) {
                                $this->domainIni['phpiniDisableFunctions'] = 'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system';
                            } else {
                                $this->domainIni['phpiniDisableFunctions'] = 'passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system';
                            }
                        }
                    }

                    if (!$this->clientHasPermission('phpiniMailFunction')) {
                        $disabledFunctions = explode(',', $this->getDomainIni('phpiniDisableFunctions'));
                        if (!in_array('mail', $disabledFunctions)) {
                            $this->domainIni['phpiniDisableFunctions'] .= ',mail';
                        }
                    }

                    foreach (array('phpiniMemoryLimit', 'phpiniPostMaxSize', 'phpiniUploadMaxFileSize',
                                 'phpiniMaxExecutionTime', 'phpiniMaxInputTime') as $option
                    ) {
                        if (isset($domainConfOptions[$option])) {
                            $this->setDomainIni($option, $domainConfOptions[$option]);
                        }

                        $optionValue = $this->getResellerPermission($option);
                        if ($this->getDomainIni($option) > $optionValue) {
                            $this->setDomainIni($option, $optionValue);
                        }
                    }
                }

                $this->saveDomainIni($clientId, $row['domain_id'], $row['domain_type']);
                $this->updateDomainStatuses($configLevel, $clientId, $row['domain_id'], $row['domain_type']);
            } catch (iMSCP_Exception_Database $e) {
                throw $e;
            }
        }
    }

    /**
     * Synchronise client PHP permissions with reseller PHP permissions
     *
     * @param int $resellerId Reseller unique identifier
     * @throws iMSCP_Exception_Database
     * @return void
     */
    public function syncClientPermissionsWithResellerPermissions($resellerId)
    {
        $stmt = exec_query('SELECT admin_id FROM admin WHERE created_by = ?', $resellerId);

        while ($row = $stmt->fetchRow()) {
            try {
                // Update client PHP permissions
                if (!$this->resellerHasPermission('phpiniSystem')) {
                    $this->loadClientPermissions(); // Load client default PHP permissions
                } else {
                    $this->loadClientPermissions($row['admin_id']); // Load client PHP permissions

                    // Update client permissions according reseller permissions
                    if (!$this->resellerHasPermission('phpiniAllowUrlFopen')) {
                        $this->clientPermissions['phpiniAllowUrlFopen'] = 'no';
                    }

                    if (!$this->resellerHasPermission('phpiniDisplayErrors')) {
                        $this->clientPermissions['phpiniDisplayErrors'] = 'no';
                    }

                    if (!$this->resellerHasPermission('phpiniDisableFunctions')) {
                        $this->clientPermissions['phpiniDisableFunctions'] = 'no';
                    }

                    if (!$this->resellerHasPermission('phpiniMailFunction')) {
                        $this->clientPermissions['phpiniMailFunction'] = 'no';
                    }
                }

                $this->saveClientPermissions($row['admin_id']);

                // Update client domain PHP configuration options
                $this->updateDomainConfigOptions($row['admin_id']);
            } catch (iMSCP_Exception_Database $e) {
                throw $e;
            }
        }
    }

    /**
     * Update domain statuses and send request to i-MSCP daemon
     *
     * @throws iMSCP_Exception
     * @throws iMSCP_Exception_Database
     * @param string $configLevel PHP configuration level (per_user|per_domain|per_site)
     * @param int $adminId Owner uique identifier
     * @param int $domainId Domain unique identifier
     * @param string $domainType Domain type (dmn|als|sub|subals)
     */
    public function updateDomainStatuses($configLevel, $adminId, $domainId, $domainType)
    {
        if ($configLevel == 'per_user') {
            $domainId = get_user_domain_id($adminId);
            exec_query(
                "UPDATE domain SET domain_status = ? WHERE domain_id = ? AND domain_status NOT IN('disabled', 'todelete')",
                array('tochange', $domainId)
            );
            exec_query(
                "
                    UPDATE domain_aliasses SET alias_status = ?
                    WHERE domain_id = ? AND alias_status NOT IN ('disabled', 'todelete')
                ",
                array('tochange', $domainId)
            );
        } else {
            switch ($domainType) {
                case 'dmn':
                    $query = "
                        UPDATE domain SET domain_status = 'tochange'
                        WHERE domain_admin_id = ? AND domain_id = ? AND domain_status NOT IN ('disabled', 'todelete')
                    ";
                    break;
                case 'sub':
                    $query = "
                        UPDATE subdomain INNER JOIN domain USING(domain_id) SET subdomain_status = 'tochange'
                        WHERE domain_admin_id = ? AND subdomain_id = ?
                        AND subdomain_status NOT IN ('disabled','todelete')
                    ";
                    break;
                case 'als';
                    $query = "
                        UPDATE domain_aliasses INNER JOIN domain USING(domain_id) SET alias_status = 'tochange'
                        WHERE domain_admin_id = ? AND alias_id = ? AND alias_status NOT IN ('disabled','todelete')
                    ";
                    break;
                case 'subals':
                    $query = "
                        UPDATE subdomain_alias INNER JOIN domain_aliasses USING(alias_id) INNER JOIN domain USING(domain_id)
                        SET subdomain_alias_status = 'tochange'
                        WHERE domain_admin_id = ? AND subdomain_alias_id = ? AND subdomain_alias_status NOT IN ('disabled','todelete')
                    ";
                    break;
                default:
                    throw new iMSCP_Exception('Unknown domain type');
            }

            exec_query($query, array($adminId, $domainId));
        }
    }
}
