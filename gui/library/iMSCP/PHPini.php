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

namespace iMSCP;

/**
 * Class PHPini
 * @package iMSCP
 */
class PHPini
{
    /**
     * @var PHPini
     */
    static protected $instance;

    /**
     * @var array Reseller permissions (including limits for INI options)
     */
    protected $resellerPermissions = [];

    /**
     * @var array Client permissions
     */
    protected $clientPermissions = [];

    /**
     * @var array INI options
     */
    protected $iniOptions = [];

    /**
     * @var bool Tells whether or not INI options are set with default values
     */
    protected $isDefaultIniOptions = true;

    /**
     * @var bool Whether or not a backend request is needed for change of INI options in client production files
     */
    protected $isBackendRequestNeeded = false;

    /**
     * Singleton object - Make new unavailable
     */
    private function __construct()
    {
        set_time_limit(0);
        ignore_user_abort(true);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if ($this->isBackendRequestNeeded) {
            send_request();
        }
    }

    /**
     * Implements singleton design pattern
     *
     * @return PHPini
     */
    static public function getInstance()
    {
        if (NULL === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Loads reseller permissions
     *
     * If a reseller identifier is given, try to load current permissions for
     * that reseller, else, load default permissions for resellers.
     *
     * Reseller permissions also include limits for INI options.
     *
     * @param int|null $resellerId Reseller unique identifier
     * @return void
     */
    public function loadResellerPermissions($resellerId = NULL)
    {
        if (NULL !== $resellerId) {
            $stmt = exec_query(
                '
                    SELECT php_ini_system, php_ini_al_config_level, php_ini_al_disable_functions, php_ini_al_mail_function, php_ini_al_mail_function,
                        php_ini_al_allow_url_fopen, php_ini_al_display_errors, php_ini_max_post_max_size, php_ini_max_upload_max_filesize,
                        php_ini_max_max_execution_time, php_ini_max_max_input_time, php_ini_max_memory_limit
                    FROM reseller_props WHERE reseller_id = ?
                ',
                [$resellerId]
            );

            if ($stmt->rowCount()) {
                $row = $stmt->fetch();

                // PHP permissions
                $this->resellerPermissions['phpiniSystem'] = $row['php_ini_system'];
                $this->resellerPermissions['phpiniConfigLevel'] = $row['php_ini_al_config_level'];
                $this->resellerPermissions['phpiniAllowUrlFopen'] = $row['php_ini_al_allow_url_fopen'];
                $this->resellerPermissions['phpiniDisplayErrors'] = $row['php_ini_al_display_errors'];
                $this->resellerPermissions['phpiniDisableFunctions'] = $row['php_ini_al_disable_functions'];
                $this->resellerPermissions['phpiniMailFunction'] = $row['php_ini_al_mail_function'];

                // Limits for PHP INI options
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
        $this->resellerPermissions['phpiniConfigLevel'] = 'per_site';
        $this->resellerPermissions['phpiniAllowUrlFopen'] = 'no';
        $this->resellerPermissions['phpiniDisplayErrors'] = 'no';
        $this->resellerPermissions['phpiniDisableFunctions'] = 'no';
        $this->resellerPermissions['phpiniMailFunction'] = 'yes';

        // Default limits for PHP INI options
        $this->resellerPermissions['phpiniPostMaxSize'] = 8;
        $this->resellerPermissions['phpiniUploadMaxFileSize'] = 2;
        $this->resellerPermissions['phpiniMaxExecutionTime'] = 30;
        $this->resellerPermissions['phpiniMaxInputTime'] = 60;
        $this->resellerPermissions['phpiniMemoryLimit'] = 128;
    }

    /**
     * Does the reseller as the given permission?
     *
     * @param string $permission Permission
     * @return bool TRUE if $key is a known and reseller has permission on it
     */
    public function resellerHasPermission($permission)
    {
        if (empty($this->resellerPermissions)) {
            throw new \LogicException("You must first load reseller permissions");
        }

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
            case 'phpiniConfigLevel':
                return in_array($this->resellerPermissions[$permission], ['per_site', 'per_domain']);
            default;
                throw new \InvalidArgumentException(sprintf('Unknown reseller PHP permission: %s', $permission));
        }
    }

    /**
     * Gets the the given reseller permission or all reseller permissions if no permission is given
     *
     * @param string|null $permission Permission name or null for all permissions
     * @return mixed
     */
    public function getResellerPermission($permission = NULL)
    {
        if (empty($this->resellerPermissions)) {
            throw new \LogicException("You must first load reseller permissions");
        }

        if (NULL === $permission) {
            return $this->resellerPermissions;
        }

        if (!array_key_exists($permission, $this->resellerPermissions)) {
            throw new \InvalidArgumentException(sprintf('Unknown reseller PHP permission: %s', $permission));
        }

        return $this->resellerPermissions[$permission];
    }

    /**
     * Sets reseller permission
     *
     * New permission value is set only if valid.
     *
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
                if (is_number($value) && $value >= 1 && $value <= 10000) {
                    $this->resellerPermissions[$permission] = $value;
                }
                break;
            case 'phpiniUploadMaxFileSize':
                if (is_number($value) && $value <= $this->resellerPermissions['phpiniPostMaxSize'] && $value >= 1 && $value <= 10000) {
                    $this->resellerPermissions[$permission] = $value;
                }
                break;
            case 'phpiniConfigLevel':
                if ($this->validatePermission($permission, $value) && $value != $this->resellerPermissions[$permission]) {
                    $this->resellerPermissions[$permission] = $value;
                }
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown reseller PHP permission: %s', $permission));
        }
    }

    /**
     * Saves reseller permissions
     *
     * @param int $resellerId Reseller unique identifier
     * @return void
     */
    public function saveResellerPermissions($resellerId)
    {
        if (empty($this->resellerPermissions)) {
            throw new \LogicException("You must first load and set new reseller permissions");
        }

        exec_query(
            '
                UPDATE reseller_props
                SET php_ini_system = ?, php_ini_al_config_level = ?, php_ini_al_disable_functions = ?, php_ini_al_mail_function = ?,
                    php_ini_al_allow_url_fopen = ?, php_ini_al_display_errors = ?, php_ini_max_post_max_size = ?, php_ini_max_upload_max_filesize = ?,
                    php_ini_max_max_execution_time = ?, php_ini_max_max_input_time = ?, php_ini_max_memory_limit = ?
                WHERE reseller_id = ?
            ',
            [
                $this->resellerPermissions['phpiniSystem'],
                $this->resellerPermissions['phpiniConfigLevel'],
                $this->resellerPermissions['phpiniDisableFunctions'],
                $this->resellerPermissions['phpiniMailFunction'],
                $this->resellerPermissions['phpiniAllowUrlFopen'],
                $this->resellerPermissions['phpiniDisplayErrors'],
                $this->resellerPermissions['phpiniPostMaxSize'],
                $this->resellerPermissions['phpiniUploadMaxFileSize'],
                $this->resellerPermissions['phpiniMaxExecutionTime'],
                $this->resellerPermissions['phpiniMaxInputTime'],
                $this->resellerPermissions['phpiniMemoryLimit'],
                $resellerId
            ]
        );
    }

    /**
     * Loads client permissions
     *
     * If a client identifier is given, try to load current permissions for
     * that client, else, load default permissions for clients, based on
     * reseller permissions.
     *
     * @param int|null $clientId Domain unique identifier
     */
    public function loadClientPermissions($clientId = NULL)
    {
        if (empty($this->resellerPermissions)) {
            throw new \LogicException("You must first load permissions of the client's reseller");
        }

        if (NULL !== $clientId) {
            $stmt = exec_query(
                '
                    SELECT phpini_perm_system, phpini_perm_config_level, phpini_perm_allow_url_fopen, phpini_perm_display_errors,
                        phpini_perm_disable_functions, phpini_perm_mail_function
                    FROM domain
                    WHERE domain_admin_id = ?
                ',
                [$clientId]
            );

            if ($stmt->rowCount()) {
                $row = $stmt->fetch();
                $this->clientPermissions['phpiniSystem'] = $row['phpini_perm_system'];
                $this->clientPermissions['phpiniConfigLevel'] = $row['phpini_perm_config_level'];
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
        $this->clientPermissions['phpiniConfigLevel'] = $this->getResellerPermission('phpiniConfigLevel');
        $this->clientPermissions['phpiniMailFunction'] = $this->resellerHasPermission('phpiniMailFunction') ? 'yes' : 'no';
    }

    /**
     * Does the client as the given PHP permission?
     *
     * In case of the phpiniDisableFunctions, true is returned as long as the
     * client has either 'exec' or 'full' permission.
     *
     * @param string $permission Permission
     * @return bool TRUE if $key is a known and client has permission on it
     */
    public function clientHasPermission($permission)
    {
        if (empty($this->resellerPermissions)) {
            throw new \LogicException("You must first load reseller permissions");
        }

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
            case 'phpiniConfigLevel':
                return true;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown client PHP permission: %s', $permission));
        }
    }

    /**
     * Gets the the given client permission or all client permissions if no permission is given
     *
     * @param string|null $permission Permission name or null for all permissions
     * @return mixed
     */
    public function getClientPermission($permission = NULL)
    {
        if (empty($this->resellerPermissions)) {
            throw new \LogicException("You must first load client permissions");
        }

        if (NULL === $permission) {
            return $this->clientPermissions;
        }

        if (!array_key_exists($permission, $this->clientPermissions)) {
            throw new \InvalidArgumentException(sprintf('Unknown client PHP permission: %s', $permission));
        }

        return $this->clientPermissions[$permission];
    }

    /**
     * Sets client permission
     *
     * New permission value is set only if valid.
     *
     * @param string $permission Permission name
     * @param string $value Permission value
     * @return void
     */
    public function setClientPermission($permission, $value)
    {
        if (empty($this->resellerPermissions)) {
            throw new \LogicException("You must first load reseller permissions");
        }

        if (!$this->validatePermission($permission, $value)
            || !$this->resellerHasPermission($permission)
            || ($permission == 'phpiniConfigLevel' && $this->getResellerPermission('phpiniConfigLevel') == 'per_domain'
                && !in_array($value, ['per_domain', 'per_user'], true)
            )
        ) {
            return;
        }

        $this->clientPermissions[$permission] = $value;

        if ($permission == 'phpiniAllowUrlFopen' && $value != 'yes') {
            $this->iniOptions['phpiniAllowUrlFopen'] = 'off';
        }

        if ($permission == 'phpiniDisplayErrors' && $value !== 'yes') {
            $this->iniOptions['phpiniDisplayErrors'] = 'off';
        }

        if ($permission == 'phpiniDisableFunctions' && $value != 'yes') {
            if ($value == 'no') {
                $this->iniOptions['phpiniDisableFunctions'] = 'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system';
            } elseif (in_array('exec', explode(',', $this->iniOptions['phpiniDisableFunctions'], true))) {
                $this->iniOptions['phpiniDisableFunctions'] = 'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system';
            } else {
                $this->iniOptions['phpiniDisableFunctions'] = 'passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system';
            }

            if (!$this->clientHasPermission('phpiniMailFunction')) {
                $this->iniOptions['phpiniDisableFunctions'] .= ',mail';
            }
        }

        if ($permission == 'phpiniMailFunction' && $value == 'no') {
            $disabledFunctions = explode(',', $this->getIniOption('phpiniDisableFunctions'));

            if (!in_array('mail', $disabledFunctions, true)) {
                $disabledFunctions[] = 'mail';
                $this->iniOptions['phpiniDisableFunctions'] = $this->assembleDisableFunctions($disabledFunctions);
            }
        }
    }

    /**
     * Saves client permissions
     *
     * @param int $clientId Client unique identifier
     * @return void
     */
    public function saveClientPermissions($clientId)
    {
        if (empty($this->clientPermissions)) {
            throw new \LogicException("You must first load and set new client permissions");
        }

        exec_query(
            '
                UPDATE domain
                SET phpini_perm_system = ?, phpini_perm_config_level = ?, phpini_perm_allow_url_fopen = ?, phpini_perm_display_errors = ?,
                    phpini_perm_disable_functions = ?, phpini_perm_mail_function = ?
                WHERE domain_admin_id = ?
            ',
            [
                $this->clientPermissions['phpiniSystem'], $this->clientPermissions['phpiniConfigLevel'],
                $this->clientPermissions['phpiniAllowUrlFopen'], $this->clientPermissions['phpiniDisplayErrors'],
                $this->clientPermissions['phpiniDisableFunctions'], $this->clientPermissions['phpiniMailFunction'], $clientId
            ]
        );
    }

    /**
     * Loads INI options
     *
     * If a client identifier, domain and and type are given, try to load
     * current INI options for that client and domain, else, load default
     * INI options, based on both client and reseller permissions.
     *
     * @param int|null $clientId OPTIONAL Client unique identifier
     * @param int|null $domainId OPTIONAL Domain unique identifier
     * @param string|null $domainType OPTIONAL Domain type (dmn|als|sub|subals)
     */
    public function loadIniOptions($clientId = NULL, $domainId = NULL, $domainType = NULL)
    {
        if (empty($this->clientPermissions)) {
            throw new \LogicException('You must first load client permissions.');
        }

        if (NULL !== $clientId) {
            if (NULL == $domainId && NULL == $domainType) {
                throw new \InvalidArgumentException('Both domain identifier and domain type are required');
            }

            $stmt = exec_query('SELECT * FROM php_ini WHERE admin_id = ? AND domain_id = ? AND domain_type = ?', [$clientId, $domainId, $domainType]);

            if ($stmt->rowCount()) {
                $row = $stmt->fetch();
                $this->iniOptions['phpiniAllowUrlFopen'] = $row['allow_url_fopen'];
                $this->iniOptions['phpiniDisplayErrors'] = $row['display_errors'];
                $this->iniOptions['phpiniErrorReporting'] = $row['error_reporting'];
                $this->iniOptions['phpiniDisableFunctions'] = $row['disable_functions'];
                $this->iniOptions['phpiniPostMaxSize'] = $row['post_max_size'];
                $this->iniOptions['phpiniUploadMaxFileSize'] = $row['upload_max_filesize'];
                $this->iniOptions['phpiniMaxExecutionTime'] = $row['max_execution_time'];
                $this->iniOptions['phpiniMaxInputTime'] = $row['max_input_time'];
                $this->iniOptions['phpiniMemoryLimit'] = $row['memory_limit'];
                $this->isDefaultIniOptions = false;
                return;
            }
        }

        $this->iniOptions['phpiniAllowUrlFopen'] = 'off';
        $this->iniOptions['phpiniDisplayErrors'] = 'off';
        $this->iniOptions['phpiniErrorReporting'] = 'E_ALL & ~E_DEPRECATED & ~E_STRICT'; // Production value
        $this->iniOptions['phpiniDisableFunctions'] = 'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system';

        if (!$this->clientHasPermission('phpiniMailFunction')) {
            $this->iniOptions['phpiniDisableFunctions'] .= ',mail';
        }

        // Value taken from Debian default php.ini file
        $this->iniOptions['phpiniMemoryLimit'] = min($this->resellerPermissions['phpiniMemoryLimit'], 128);
        $this->iniOptions['phpiniPostMaxSize'] = min($this->resellerPermissions['phpiniPostMaxSize'], 8);
        $this->iniOptions['phpiniUploadMaxFileSize'] = min($this->resellerPermissions['phpiniUploadMaxFileSize'], 2);
        $this->iniOptions['phpiniMaxExecutionTime'] = min($this->resellerPermissions['phpiniMaxExecutionTime'], 30);
        $this->iniOptions['phpiniMaxInputTime'] = min($this->resellerPermissions['phpiniMaxInputTime'], 60);
        $this->isDefaultIniOptions = true;
    }

    /**
     * Whether or not INI options are set with default values
     *
     * @return boolean
     */
    public function isDefaultIniOptions()
    {
        return $this->isDefaultIniOptions;
    }

    /**
     * Gets the the given INI option or all INI option if no INI option is given
     *
     * @param string|null OPTIONAL $varname INI option name
     * @return mixed
     */
    public function getIniOption($varname = NULL)
    {
        if (empty($this->iniOptions)) {
            throw new \LogicException("You must first load client domain INI options");
        }

        if (NULL === $varname) {
            return $this->iniOptions;
        }

        if (!array_key_exists($varname, $this->iniOptions)) {
            throw new \InvalidArgumentException(sprintf('Unknown domain INI option: %s', $varname));
        }

        return $this->iniOptions[$varname];
    }

    /**
     * Sets value for the given INI option
     *
     * New INI option value is set only if valid.
     *
     * @param string $varname Configuration option name
     * @param string $value Configuration option value
     */
    public function setIniOption($varname, $value)
    {
        if (empty($this->clientPermissions)) {
            throw new \LogicException('You must first load client permissions.');
        }

        if (!$this->validateIniOption($varname, $value)) {
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
            case 'phpiniErrorReporting':
                break;
            default:
                if (!$this->clientHasPermission($varname)) {
                    return;
                }
        }

        $this->iniOptions[$varname] = $value;
        $this->isDefaultIniOptions = false;
    }

    /**
     * Saves INI options for the given client and domain
     *
     * @param int $adminId Owner unique identifier
     * @param int $domainId Domain unique identifier
     * @param string $domainType Domain type (dmn|als|sub|subals)
     * @return void
     */
    public function saveIniOptions($adminId, $domainId, $domainType)
    {
        if (empty($this->iniOptions)) {
            throw new \LogicException('You must first load client domain INI options.');
        }

        $stmt = exec_query(
            '
                INSERT INTO php_ini (
                    admin_id, domain_id, domain_type, disable_functions, allow_url_fopen, display_errors, error_reporting, post_max_size,
                    upload_max_filesize, max_execution_time, max_input_time,memory_limit
                ) VALUES (
                    :admin_id, :domain_id, :domain_type, :disable_functions, :allow_url_fopen, :display_errors, :error_reporting, :post_max_size,
                    :upload_max_file_size, :max_execution_time, :max_input_time,:memory_limit
                ) ON DUPLICATE KEY UPDATE
                    disable_functions = :disable_functions, allow_url_fopen = :allow_url_fopen, display_errors = :display_errors,
                    error_reporting = :error_reporting, post_max_size = :post_max_size, upload_max_filesize = :upload_max_file_size,
                    max_execution_time = :max_execution_time, max_input_time = :max_input_time, memory_limit = :memory_limit
            ',
            [
                'admin_id'             => $adminId,
                'domain_id'            => $domainId,
                'domain_type'          => $domainType,
                'disable_functions'    => $this->iniOptions['phpiniDisableFunctions'],
                'allow_url_fopen'      => $this->iniOptions['phpiniAllowUrlFopen'],
                'display_errors'       => $this->iniOptions['phpiniDisplayErrors'],
                'error_reporting'      => $this->iniOptions['phpiniErrorReporting'],
                'post_max_size'        => $this->iniOptions['phpiniPostMaxSize'],
                'upload_max_file_size' => $this->iniOptions['phpiniUploadMaxFileSize'],
                'max_execution_time'   => $this->iniOptions['phpiniMaxExecutionTime'],
                'max_input_time'       => $this->iniOptions['phpiniMaxInputTime'],
                'memory_limit'         => $this->iniOptions['phpiniMemoryLimit']
            ]
        );

        if ($stmt->rowCount() > 0) {
            $this->isBackendRequestNeeded = true;
        }
    }

    /**
     * Synchronise client permissions and INI optiions according reseller permissions
     *
     * @param int $resellerId Reseller unique identifier
     * @param int $clientId OPTIONAL client unique identifier (Default: All reseller's clients)
     * @return void
     */
    public function syncClientPermissionsAndIniOptions($resellerId, $clientId = NULL)
    {
        if (empty($this->resellerPermissions)) {
            $this->loadResellerPermissions($resellerId);
        }

        $params = [];

        if (NULL !== $clientId) {
            $condition = 'WHERE admin_id = ? AND created_by = ?';
            $params[] = $clientId;
        } else {
            $condition = 'WHERE created_by = ?';
        }

        $params[] = $resellerId;
        $stmt = exec_query("SELECT admin_id FROM admin $condition", $params);

        while ($row = $stmt->fetch()) {
            $this->loadClientPermissions($row['admin_id']);
            $configLevel = $this->getClientPermission('phpiniConfigLevel');

            if (!$this->resellerHasPermission('phpiniSystem')) {
                // Reset client's permissions to their default values based on the permissions of its reseller.
                $this->loadClientPermissions();
                $this->saveClientPermissions($row['admin_id']);
                $this->updateClientIniOptions($row['admin_id'], $configLevel != $this->getClientPermission('phpiniConfigLevel'), true);
                continue;
            }

            // Adjusts client's permissions based on permissions of its reseller.

            if (!$this->resellerHasPermission('phpiniConfigLevel') && $this->clientPermissions['phpiniConfigLevel'] != 'per_user') {
                $this->clientPermissions['phpiniConfigLevel'] = 'per_user';
                $this->isBackendRequestNeeded = true;
            } elseif ($this->getResellerPermission('phpiniConfigLevel') == 'per_domain'
                && !in_array($this->clientPermissions['phpiniConfigLevel'], ['per_user', 'per_domain'], true)
            ) {
                $this->clientPermissions['phpiniConfigLevel'] = 'per_domain';
                $this->isBackendRequestNeeded = true;
            }

            foreach (['phpiniAllowUrlFopen', 'phpiniDisplayErrors', 'phpiniDisableFunctions', 'phpiniMailFunction'] as $permissions) {
                if (!$this->resellerHasPermission($permissions)) {
                    $this->clientPermissions[$permissions] = 'no';
                }
            }

            $this->saveClientPermissions($row['admin_id']);
            $this->updateClientIniOptions($row['admin_id'], $configLevel != $this->getClientPermission('phpiniConfigLevel'), true);
        }
    }

    /**
     * Update client INI options for all its domains, including subdomains
     *
     * @param int $clientId Client unique identifier
     * @param bool $isBackendRequestNeeded OPTIONAL Is a request backend needed for the given client?
     * @param bool $loadIniOptions OPTIONAL Whether or not INI options must be loaded
     * @return void
     */
    public function updateClientIniOptions($clientId, $isBackendRequestNeeded = false, $loadIniOptions = false)
    {
        if (empty($this->clientPermissions)) {
            $this->loadClientPermissions($clientId);
        }

        $isBackendRequestNeededPrev = $this->isBackendRequestNeeded;
        $stmt = exec_query('SELECT id, domain_id, domain_type FROM php_ini WHERE admin_id = ?', [$clientId]);

        while ($row = $stmt->fetch()) {
            $this->isBackendRequestNeeded = $isBackendRequestNeeded ? true : false;

            if (!$this->clientHasPermission('phpiniSystem')) {
                // Reset INI options to their default values
                $this->loadIniOptions();
                $this->saveIniOptions($clientId, $row['domain_id'], $row['domain_type']);
                $this->updateDomainStatuses($clientId, $row['domain_id'], $row['domain_type']);
                continue;
            }

            if ($loadIniOptions) {
                // Load current INI options
                $this->loadIniOptions($row['domain_id'], $row['domain_type']);
            }

            if (!$this->clientHasPermission('phpiniAllowUrlFopen')) {
                $this->iniOptions['phpiniAllowUrlFopen'] = 'off';
            }

            if (!$this->clientHasPermission('phpiniDisplayErrors')) {
                $this->iniOptions['phpiniDisplayErrors'] = 'off';
            }

            if (!$this->clientHasPermission('phpiniDisableFunctions')) {
                if ($this->getClientPermission('phpiniDisableFunctions') == 'no') {
                    $this->iniOptions['phpiniDisableFunctions'] = 'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system';
                } elseif (in_array('exec', explode(',', $this->iniOptions['phpiniDisableFunctions']), true)) {
                    $this->iniOptions['phpiniDisableFunctions'] = 'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system';
                } else {
                    $this->iniOptions['phpiniDisableFunctions'] = 'passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec,symlink,system';
                }
            }

            if (!$this->clientHasPermission('phpiniMailFunction')) {
                if (!in_array('mail', explode(',', $this->iniOptions['phpiniDisableFunctions']), true)) {
                    $this->iniOptions['phpiniDisableFunctions'] .= ',mail';
                }
            }

            // Make sure that client INI options are not above reseller's limits
            foreach (
                ['phpiniMemoryLimit', 'phpiniPostMaxSize', 'phpiniUploadMaxFileSize', 'phpiniMaxExecutionTime', 'phpiniMaxInputTime'] as $iniOption
            ) {
                $resellerLimit = $this->resellerPermissions[$iniOption];

                if ($this->iniOptions[$iniOption] > $resellerLimit) {
                    $this->iniOptions[$iniOption] = $resellerLimit;
                }
            }

            $this->saveIniOptions($clientId, $row['domain_id'], $row['domain_type']);
            $this->updateDomainStatuses($clientId, $row['domain_id'], $row['domain_type']);
        }

        if ($isBackendRequestNeededPrev && !$this->isBackendRequestNeeded) {
            $this->isBackendRequestNeeded = $isBackendRequestNeededPrev;
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
     * Update domain statuses if needed
     *
     * @param int $clientId Client unique identifier
     * @param int $domainId Domain unique identifier
     * @param string $domainType Domain type (dmn|als|sub|subals)
     * @param bool $configLevelBased Whether domains statuses must be updated based on client PHP configuration level
     * @return void
     */
    public function updateDomainStatuses($clientId, $domainId, $domainType, $configLevelBased = false)
    {
        if (!$this->isBackendRequestNeeded) {
            return;
        }

        if (empty($this->clientPermissions)) {
            throw new \LogicException('You must first load client permissions');
        }

        if ($configLevelBased) {
            switch ($this->clientPermissions['phpiniConfigLevel']) {
                case 'per_user':
                    // Per user = Identical PHP configuration for all domains, including subdomains.
                    // We need update status of all client's domains
                    $domainId = get_user_domain_id($clientId);

                    // Update main domain, including its subdomains, except those that are disabled, being disabled or deleted
                    exec_query(
                        "
                            UPDATE domain AS t1
                            LEFT JOIN subdomain AS t2 ON(t1.domain_id = t2.domain_id AND t2.subdomain_status NOT IN('disabled', 'todelete'))
                            SET t1.domain_status = 'tochange', t2.subdomain_status = 'tochange'
                            WHERE t1.domain_id = ?
                            AND t1.domain_status <> 'disabled'
                        ",
                        [$domainId]
                    );

                    // Update domain aliases, including their subdomains, except those that are disabled, being disabled or deleted
                    exec_query(
                        "
                            UPDATE domain_aliasses AS t1
                            LEFT JOIN subdomain_alias AS t2 ON(t1.alias_id = t2.alias_id AND t2.subdomain_alias_status NOT IN('disabled', 'todelete'))
                            SET t1.alias_status = 'tochange', t2.subdomain_alias_status = 'tochange'
                            WHERE t1.domain_id = ?
                            AND t1.alias_status NOT IN('disabled', 'todelete')
                        ",
                        [$domainId]
                    );
                    return;
                case 'per_domain':
                    // per_domain = Identical PHP configuration for each domains, including subdomains.
                    // We need update status of $domainId-$domainType including its subdomains
                    switch ($domainType) {
                        case 'dmn':
                            // Update main domain, including its subdomains, except those that are disabled, being disabled or deleted
                            exec_query(
                                "
                                    UPDATE domain AS t1
                                    LEFT JOIN subdomain AS t2 ON(
                                        t1.domain_id = t2.domain_id AND t2.subdomain_status NOT IN('disabled', 'todisable', 'todelete'
                                    )
                                    SET t1.domain_status = 'tochange', t2.subdomain_status = 'tochange'
                                    WHERE t1.domain_id = ?
                                    AND t1.domain_admin_id = ?
                                    AND t1.domain_status NOT IN('disabled', 'todisable', 'todelete')
                                "
                                [$domainId]
                            );
                            break;
                        case 'als':
                            // Update domain aliases, including their subdomains, except those that are disabled, being disabled or deleted
                            exec_query(
                                "
                                    UPDATE domain_aliasses AS t1
                                    LEFT JOIN subdomain_alias AS t2 ON(
                                        t1.alias_id = t2.alias_id AND t2.subdomain_alias_status NOT IN('disabled', 'todisable', 'todelete')
                                    )
                                    SET t1.alias_status = 'tochange', t2.subdomain_alias_status = 'tochange'
                                    WHERE t1.domain_id = ?
                                    AND t1.alias_status NOT IN('disabled', 'todisable', 'todelete')
                                "
                                [$domainId]
                            );
                            break;
                        default:
                            // Nothing to do here. Such request (sub, subals) should never occurs in per_domain level
                            return;
                    }

                    return;
                default:
                    // per_site = Different PHP configuration for each domains, including subdomains.
                    // We need update statuses of $domainId-$domainType only
            }
        }

        switch ($domainType) {
            case 'dmn':
                // Update main domain, except if it is disabled, being disabled or deleted
                exec_query(
                    "
                        UPDATE domain
                        SET domain_status = 'tochange'
                        WHERE domain_id = ?
                        AND domain_admin_id = ?
                        AND domain_status NOT IN ('disabled', 'todisable', 'todelete')
                    ",
                    [$domainId, $clientId]
                );
                return;
            case 'sub':
                // Update subdomains except if it is disabled, being disabled or deleted
                $query = "
                    UPDATE subdomain AS t1
                    JOIN domain AS t2 USING(domain_id)
                    SET t1.subdomain_status = 'tochange'
                    WHERE t1.subdomain_id = ?
                    AND t2.domain_id = ?
                    AND t1.subdomain_status NOT IN ('disabled', 'todisable', 'todelete')
                ";
                break;
            case 'als';
                // Update domain alias except if it is disabled, being disabled or deleted
                $query = "
                    UPDATE domain_aliasses AS t1
                    JOIN domain AS t2 USING(domain_id)
                    SET t1.alias_status = 'tochange'
                    WHERE t1.alias_id = ?
                    AND t2.domain_id = ?
                    AND t1.alias_status NOT IN ('disabled', 'todisable', 'todelete')
                 ";
                break;
            case 'subals':
                // Update subdomains of domain alias except if it is disabled, being disabled or deleted
                $query = "
                    UPDATE subdomain_alias AS t1
                    JOIN domain_aliasses AS t2 USING(alias_id)
                    SET t1.subdomain_alias_status = 'tochange'
                    WHERE t1.subdomain_alias_id = ?
                    AND t2.domain_id = ?
                    AND t1.subdomain_alias_status NOT IN ('disabled', 'todisable', 'todelete')
                ";
                break;
            default:
                throw new \InvalidArgumentException('Unknown domain type');
        }

        exec_query($query, [$domainId, get_user_domain_id($clientId)]);
    }

    /**
     * Validate the give permission
     *
     * @param string $permission Permission name
     * @param string $value Permission value
     * @return bool TRUE if $permission is valid, FALSE otherwise
     *
     */
    protected function validatePermission($permission, $value)
    {
        switch ($permission) {
            case 'phpiniSystem':
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
            case 'phpiniMailFunction':
                return in_array($value, ['yes', 'no'], true);
            case 'phpiniDisableFunctions':
                return in_array($value, ['yes', 'no', 'exec'], true);
            case 'phpiniConfigLevel':
                return in_array($value, ['per_domain', 'per_site', 'per_user'], true);
            default:
                throw new \InvalidArgumentException(sprintf('Unknown PHP permission: %s', $permission));
        }
    }

    /**
     * Validate the given INI option
     *
     * Unlimited values are not allowed for safety reasons
     *
     * @param string $varname Configuration option name
     * @param string $value Configuration option value
     * @return bool TRUE if $value is valid, FALSE otherwise
     */
    protected function validateIniOption($varname, $value)
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
                $allowedFunctionNames = [
                    'exec', 'mail', 'passthru', 'phpinfo', 'popen', 'proc_open', 'show_source', 'shell', 'shell_exec', 'symlink', 'system', ''
                ];

                return array_diff(explode(',', $value), $allowedFunctionNames) ? false : true;
            case 'phpiniMemoryLimit':
            case 'phpiniMaxExecutionTime':
            case 'phpiniMaxInputTime':
                return is_number($value) && $value >= 1 && $value <= 10000;
            case 'phpiniPostMaxSize':
                return is_number($value) && $value >= 1 && $value <= 10000;
            case 'phpiniUploadMaxFileSize':
                return is_number($value) && $value <= $this->iniOptions['phpiniPostMaxSize'] && $value >= 1 && $value <= 10000;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown configuration option: %s', $varname));
        }
    }
}
