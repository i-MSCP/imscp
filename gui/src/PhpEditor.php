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

/** @noinspection
 * PhpUnusedParameterInspection
 * PhpUnhandledExceptionInspection
 * PhpDocMissingThrowsInspection
 */

declare(strict_types=1);

namespace iMSCP;

use iMSCP\Config\FileConfig;
use iMSCP\Database\DatabaseException;
use iMSCP_Registry;
use PDO;

/**
 * Class PhpEditor
 * @package iMSCP
 */
class PhpEditor
{
    /**
     * @var PhpEditor
     */
    static protected $instance;

    /**
     * @var array Reseller PHP permissions (including limits for configuration
     *            options)
     */
    protected $rp = [];

    /**
     * @var array Client PHP permissions
     */
    protected $cp = [];

    /**
     * @var array Domain configuration options
     */
    protected $ini = [];

    /**
     * @var bool Tells whether or not domain INI values are set with default
     *           values
     */
    protected $isDefaultIni = true;

    /**
     * @var string PHP INI level
     */
    protected $iniLevel;

    /**
     * Singleton object - Make new unavailable
     */
    private function __construct()
    {
        set_time_limit(0);
        ignore_user_abort(true);
    }

    /**
     * Implements singleton design pattern
     *
     * @return PhpEditor
     */
    static public function getInstance(): PhpEditor
    {
        if (NULL === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Saves reseller PHP permissions
     *
     * @param int $id Reseller unique identifier
     * @return void
     */
    public function saveResellerPermissions($id)
    {
        exec_query(
            '
                UPDATE reseller_props
                SET php_ini_system = ?,
                    php_ini_al_disable_functions = ?,
                    php_ini_al_mail_function = ?,
                    php_ini_al_mail_function = ?,
                    php_ini_al_allow_url_fopen = ?,
                    php_ini_al_display_errors = ?,
                    php_ini_max_post_max_size = ?,
                    php_ini_max_upload_max_filesize = ?,
                    php_ini_max_max_execution_time = ?,
                    php_ini_max_max_input_time = ?,
                    php_ini_max_memory_limit = ?
                WHERE reseller_id = ?
            ',
            [
                $this->rp['phpiniSystem'],
                $this->rp['phpiniAllowUrlFopen'],
                $this->rp['phpiniDisplayErrors'],
                $this->rp['phpiniDisableFunctions'],
                $this->rp['phpiniMailFunction'],
                $this->rp['phpiniPostMaxSize'],
                $this->rp['phpiniUploadMaxFileSize'],
                $this->rp['phpiniMaxExecutionTime'],
                $this->rp['phpiniMaxInputTime'],
                $this->rp['phpiniMemoryLimit'],
                $id
            ]
        );
    }

    /**
     * Sets the value of a reseller PHP permission
     *
     * We are safe here. New value is set only if valid.
     *
     * @param string $perm Permission name
     * @param string $val Permission value
     * @return void
     */
    public function setResellerPermission($perm, $val)
    {
        switch ($perm) {
            case 'phpiniSystem':
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
            case 'phpiniMailFunction':
            case 'phpiniDisableFunctions':
                if ($this->validatePermission($perm, $val)) {
                    $this->rp[$perm] = $val;
                }
                break;
            case 'phpiniMemoryLimit':
            case 'phpiniMaxInputTime':
            case 'phpiniMaxExecutionTime':
                if (is_number($val)
                    && $val >= 1
                    && $val <= 10000
                ) {
                    $this->rp[$perm] = $val;
                }
                break;
            case 'phpiniPostMaxSize':
                // According PHP doc, post_max_size value *should* be lower than
                // memory_limit value
                // Limit released since i-MSCP 1.4.4
                if (is_number($val)
                    //&& $value < $this->resellerPermissions['phpiniMemoryLimit']
                    && $val >= 1
                    && $val <= 10000
                ) {
                    $this->rp[$perm] = $val;
                }
                break;
            case 'phpiniUploadMaxFileSize':
                // According PHP doc, max_upload_filesize value *must* be lower
                // than post_max_size value
                // Equality accepted since i-MSCP 1.4.4
                if (is_number($val)
                    //&& $value < $this->resellerPermissions['phpiniPostMaxSize']
                    && $val <= $this->rp['phpiniPostMaxSize']
                    && $val >= 1
                    && $val <= 10000
                ) {
                    $this->rp[$perm] = $val;
                }
                break;
            default:
                throw new Exception(sprintf(
                    'Unknown reseller PHP permission: %s', $perm
                ));
        }
    }

    /**
     * Validate value for the given PHP permission.
     *
     * @param string $perm Permission name
     * @param string $val Permission value
     * @return bool TRUE if $permission is valid, FALSE otherwise
     *
     * @throws Exception if $permission is unknown
     */
    public function validatePermission($perm, $val)
    {
        switch ($perm) {
            case 'phpiniSystem':
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
            case 'phpiniMailFunction':
                return $val === 'yes' || $val === 'no';
            case 'phpiniDisableFunctions':
                return $val === 'yes' || $val === 'no' || $val === 'exec';
            default:
                throw new Exception(sprintf(
                    'Unknown PHP permission: %s', $perm
                ));
        }
    }

    /**
     * Sets client permission value
     *
     * We are safe here. New value is set only if valid and if client' reseller
     * has the needed permission.
     *
     * @param string $perm Permission name
     * @param string $val Permission value
     * @return void
     * @throws Exception
     */
    public function setClientPermission($perm, $val)
    {
        if (!$this->validatePermission($perm, $val)
            || !$this->resellerHasPermission($perm)
        ) {
            return;
        }

        $this->cp[$perm] = $val;

        if ($perm == 'phpiniAllowUrlFopen' && $val != 'yes') {
            $this->ini['phpiniAllowUrlFopen'] = 'off';
        }

        if ($perm == 'phpiniDisplayErrors' && $val != 'yes') {
            $this->ini['phpiniDisplayErrors'] = 'off';
        }

        if ($perm == 'phpiniDisableFunctions' && $val != 'yes') {
            if ($val == 'no') {
                $this->ini['phpiniDisableFunctions'] = 'exec,passthru,'
                    . 'phpinfo,popen,proc_open,show_source,shell,shell_exec,'
                    . 'symlink,system';
            } else { // exec only
                if (in_array('exec', explode(
                    ',', $this->ini['phpiniDisableFunctions']))
                ) {
                    $this->ini['phpiniDisableFunctions'] = 'exec,'
                        . 'passthru,phpinfo,popen,proc_open,show_source,shell,'
                        . 'shell_exec,symlink,system';
                } else {
                    $this->ini['phpiniDisableFunctions'] = 'passthru,' .
                        'phpinfo,popen,proc_open,show_source,shell,shell_exec,'
                        . 'symlink,system';
                }
            }

            if (!$this->clientHasPermission('phpiniMailFunction')) {
                $this->ini['phpiniDisableFunctions'] .= ',mail';
            }
        }

        if ($perm == 'phpiniMailFunction' && $val == 'no') {
            $disabledFunctions = explode(',', $this->getDomainIni(
                'phpiniDisableFunctions')
            );

            if (!in_array('mail', $disabledFunctions)) {
                $disabledFunctions[] = 'mail';
                $this->ini['phpiniDisableFunctions'] =
                    $this->assembleDisableFunctions($disabledFunctions);
            }
        }
    }

    /**
     * Does the reseller as the given PHP permission?
     *
     * @param string $perm Permission
     * @return bool TRUE if $key is a known and reseller has permission on it
     * @throws Exception if $permission is unknown
     */
    public function resellerHasPermission($perm)
    {
        if ($this->rp['phpiniSystem'] !== 'yes') {
            return false;
        }

        switch ($perm) {
            case 'phpiniSystem':
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
            case 'phpiniDisableFunctions':
            case 'phpiniMailFunction':
                return $this->rp[$perm] === 'yes';
            default;
                throw new Exception(sprintf(
                    'Unknown reseller PHP permission: %s', $perm
                ));
        }
    }

    /**
     * Does the client as the given PHP permission?
     *
     * Be aware that in case of the phpiniDisableFunctions, true is returned
     * as long as the client has either 'exec' or 'full' permission.
     *
     * @param string $perm Permission
     * @return bool TRUE if $key is a known and client has permission on it
     * @throws Exception if $permission is unknown
     */
    public function clientHasPermission($perm)
    {
        if ($this->rp['phpiniSystem'] != 'yes') {
            return false;
        }

        switch ($perm) {
            case 'phpiniSystem':
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
            case 'phpiniMailFunction':
                return $this->cp[$perm] == 'yes';
            case 'phpiniDisableFunctions':
                return $this->cp[$perm] == 'yes'
                    || $this->cp[$perm] == 'exec';
            default:
                throw new Exception(sprintf(
                    'Unknown client PHP permission: %s', $perm
                ));
        }
    }

    /**
     * Gets domain INI option value(s)
     *
     * @param string|null $var Domain configuration option name or null for
     *                             all configuration options
     * @return mixed
     * @throws Exception if $varname is unknown
     */
    public function getDomainIni($var = NULL)
    {
        if (NULL === $var) {
            return $this->ini;
        }

        if (!array_key_exists($var, $this->ini)) {
            throw new Exception(sprintf(
                'Unknown domain configuration option: %s', $var
            ));
        }

        return $this->ini[$var];
    }

    /**
     * Assemble disable_functions parameter from its parts
     *
     * @param array $disabledFuncts List of disabled function
     * @return string
     */
    public function assembleDisableFunctions(array $disabledFuncts)
    {
        return implode(',', array_unique($disabledFuncts));
    }

    /**
     * Synchronise client PHP permissions (including domain INI options) with
     * reseller PHP permissions.
     *
     * @param int $resellerId Reseller unique identifier
     * @param int $clientId OPTIONAL client unique identifier (Client for which
     *                      PHP permissions must be synchronized)
     * @return bool Boolean indicating whether or not a backend request is
     *                      needed
     */
    public function syncClientPermissionsWithResellerPermissions(
        $resellerId, $clientId = NULL
    )
    {
        if (empty($this->rp)) {
            $this->loadResellerPermissions($resellerId);
        }

        $needBackendRequest = false;

        if (NULL !== $clientId) {
            $condition = 'WHERE admin_id = ? AND created_by = ?';
            $params[] = $clientId;
        } else {
            $condition = 'WHERE created_by = ?';
        }

        $params[] = $resellerId;
        $stmt = exec_query("SELECT admin_id FROM admin $condition", $params);

        while ($row = $stmt->fetchRow()) {
            try {
                // Update client PHP permissions
                if (!$this->resellerHasPermission('phpiniSystem')) {
                    // Load client default PHP permissions
                    $this->loadClientPermissions();
                } else {
                    // Load client PHP permissions
                    $this->loadClientPermissions($row['admin_id']);

                    // Update client permissions according reseller permissions
                    if (!$this->resellerHasPermission('phpiniAllowUrlFopen')) {
                        $this->cp['phpiniAllowUrlFopen'] = 'no';
                    }

                    if (!$this->resellerHasPermission('phpiniDisplayErrors')) {
                        $this->cp['phpiniDisplayErrors'] = 'no';
                    }

                    if (!$this->resellerHasPermission(
                        'phpiniDisableFunctions')
                    ) {
                        $this->cp['phpiniDisableFunctions'] = 'no';
                    }

                    if (!$this->resellerHasPermission('phpiniMailFunction')) {
                        $this->cp['phpiniMailFunction'] = 'no';
                    }
                }

                if ($needChange = $this->saveClientPermissions($row['admin_id'])) {
                    $needBackendRequest = true;
                }

                // Sync client PHP INI values with reseller values
                // We are passing NULL for the domain INI values because we
                // don't want set new domain INI values. We only want lower them
                // when needed (client domain INI values cannot be greater than
                // reseller values)
                if ($this->updateClientDomainIni(
                    NULL, $row['admin_id'], $needChange
                )) {
                    $needBackendRequest = true;
                }
            } catch (DatabaseException $e) {
                throw $e;
            }
        }

        return $needBackendRequest;
    }

    /**
     * Loads reseller PHP permissions
     *
     * If a reseller identifier is given, try to load permissions from the
     * database, else, loadthe default reseller permissions.
     *
     * Note: Reseller permissions also include limits for PHP configuration
     * options.
     *
     * @param int|null $id Reseller unique identifier
     * @return void
     */
    public function loadResellerPermissions($id = NULL)
    {
        if (NULL !== $id) {
            $stmt = exec_query(
                '
                    SELECT php_ini_system, php_ini_al_disable_functions,
                        php_ini_al_mail_function, php_ini_al_mail_function,
                        php_ini_al_allow_url_fopen, php_ini_al_display_errors,
                        php_ini_max_post_max_size,
                        php_ini_max_upload_max_filesize,
                        php_ini_max_max_execution_time,
                        php_ini_max_max_input_time, php_ini_max_memory_limit
                    FROM reseller_props WHERE reseller_id = ?
                ',
                $id
            );

            if ($stmt->rowCount()) {
                $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

                // PHP permissions
                $this->rp['phpiniSystem'] = $row['php_ini_system'];
                $this->rp['phpiniAllowUrlFopen'] = $row['php_ini_al_allow_url_fopen'];
                $this->rp['phpiniDisplayErrors'] = $row['php_ini_al_display_errors'];
                $this->rp['phpiniDisableFunctions'] = $row['php_ini_al_disable_functions'];
                $this->rp['phpiniMailFunction'] = $row['php_ini_al_mail_function'];

                // Limits for PHP configuration options
                $this->rp['phpiniPostMaxSize'] = $row['php_ini_max_post_max_size'];
                $this->rp['phpiniUploadMaxFileSize'] = $row['php_ini_max_upload_max_filesize'];
                $this->rp['phpiniMaxExecutionTime'] = $row['php_ini_max_max_execution_time'];
                $this->rp['phpiniMaxInputTime'] = $row['php_ini_max_max_input_time'];
                $this->rp['phpiniMemoryLimit'] = $row['php_ini_max_memory_limit'];
                return;
            }
        }

        // Default PHP permissions
        $this->rp['phpiniSystem'] = 'no';
        $this->rp['phpiniAllowUrlFopen'] = 'no';
        $this->rp['phpiniDisplayErrors'] = 'no';
        $this->rp['phpiniDisableFunctions'] = 'no';
        $this->rp['phpiniMailFunction'] = 'yes';

        // Default limits for PHP configuration options
        $this->rp['phpiniPostMaxSize'] = 8;
        $this->rp['phpiniUploadMaxFileSize'] = 2;
        $this->rp['phpiniMaxExecutionTime'] = 30;
        $this->rp['phpiniMaxInputTime'] = 60;
        $this->rp['phpiniMemoryLimit'] = 128;
    }

    /**
     * Loads client PHP permissions
     *
     * If a client identifier is given, try to load permissions from
     * database, else, load default client permissions.
     *
     * @param int|null $id Domain unique identifier
     * @throws Exception
     */
    public function loadClientPermissions($id = NULL)
    {
        if (empty($this->rp)) {
            throw new Exception('You must first load reseller permissions');
        }

        if (NULL !== $id) {
            $stmt = exec_query(
                '
                    SELECT phpini_perm_system,
                        phpini_perm_allow_url_fopen,
                        phpini_perm_display_errors,
                        phpini_perm_disable_functions,
                        phpini_perm_mail_function
                    FROM domain
                    WHERE domain_admin_id = ?
                ',
                $id
            );

            if ($stmt->rowCount()) {
                $row = $stmt->fetchRow();
                $this->cp['phpiniSystem'] = $row['phpini_perm_system'];
                $this->cp['phpiniAllowUrlFopen'] = $row['phpini_perm_allow_url_fopen'];
                $this->cp['phpiniDisplayErrors'] = $row['phpini_perm_display_errors'];
                $this->cp['phpiniDisableFunctions'] = $row['phpini_perm_disable_functions'];
                $this->cp['phpiniMailFunction'] = $row['phpini_perm_mail_function'];
                return;
            }
        }

        $this->cp['phpiniSystem'] = 'no';
        $this->cp['phpiniAllowUrlFopen'] = 'no';
        $this->cp['phpiniDisplayErrors'] = 'no';
        $this->cp['phpiniDisableFunctions'] = 'no';

        if ($this->resellerHasPermission('phpiniMailFunction')) {
            $this->cp['phpiniMailFunction'] = 'yes';
        } else {
            $this->cp['phpiniMailFunction'] = 'no';
        }
    }

    /**
     * Saves client PHP permissions
     *
     * @param int $clientId Client unique identifier
     * @return bool Boolean indicating whether or not a backend request is needed
     */
    public function saveClientPermissions($clientId)
    {
        $stmt = exec_query(
            '
                UPDATE domain SET phpini_perm_system = ?,
                    phpini_perm_allow_url_fopen = ?,
                    phpini_perm_display_errors = ?,
                    phpini_perm_disable_functions = ?,
                    phpini_perm_mail_function = ?
                WHERE domain_admin_id = ?
            ',
            [
                $this->cp['phpiniSystem'],
                $this->cp['phpiniAllowUrlFopen'],
                $this->cp['phpiniDisplayErrors'],
                $this->cp['phpiniDisableFunctions'],
                $this->cp['phpiniMailFunction'], $clientId
            ]
        );
        return (bool)$stmt->rowCount();
    }

    /**
     * Update client domain INI options for the given client
     *
     * @param null|array $domainIni New Domain INI values
     * @param int $clientId Client identifier
     * @param bool $needChange OPTIONAL whether or not client domains must be
     *                         updated
     * @return bool Whether or not a backend request is needed
     */
    public function updateClientDomainIni(
        $domainIni, $clientId, $needChange = false
    )
    {
        $needBackendRequest = false;

        // We must ensure that there is no missing PHP INI entries (since 1.3.1)
        $this->createMissingPhpIniEntries($clientId);

        $stmt = exec_query(
            'SELECT id, domain_id, domain_type FROM php_ini WHERE admin_id = ?',
            [$clientId]
        );
        while ($row = $stmt->fetchRow()) {
            try {
                if (!$this->clientHasPermission('phpiniSystem')) {
                    // Load domain default PHP configuration options
                    $this->loadDomainIni();
                } else {
                    // Load domain PHP configuration options
                    $this->loadDomainIni(
                        $clientId, $row['domain_id'], $row['domain_type']
                    );

                    if (!$this->clientHasPermission('phpiniAllowUrlFopen')) {
                        $this->ini['phpiniAllowUrlFopen'] = 'off';
                    }

                    if (!$this->clientHasPermission('phpiniDisplayErrors')) {
                        $this->ini['phpiniDisplayErrors'] = 'off';
                    }

                    if (!$this->clientHasPermission('phpiniDisableFunctions')) {
                        if ($this->getClientPermission(
                                'phpiniDisableFunctions') == 'no'
                        ) {
                            $this->ini['phpiniDisableFunctions'] = 'exec,' .
                                'passthru,phpinfo,popen,proc_open,show_source,'
                                . 'shell,shell_exec,symlink,system';
                        } else {
                            if (in_array('exec', explode(
                                ',', $this->ini['phpiniDisableFunctions']))
                            ) {
                                $this->ini['phpiniDisableFunctions'] = 'exec,'
                                    . 'passthru,phpinfo,popen,proc_open,'
                                    . 'show_source,shell,shell_exec,symlink,'
                                    . 'system';
                            } else {
                                $this->ini['phpiniDisableFunctions'] =
                                    'passthru,phpinfo,popen,proc_open,'
                                    . 'show_source,shell,shell_exec,symlink,'
                                    . 'system';
                            }
                        }
                    }

                    if (!$this->clientHasPermission('phpiniMailFunction')) {
                        $disabledFunctions = explode(
                            ',', $this->getDomainIni('phpiniDisableFunctions')
                        );

                        if (!in_array('mail', $disabledFunctions)) {
                            $this->ini['phpiniDisableFunctions'] .= ',mail';
                        }
                    }

                    foreach (
                        [
                            'phpiniMemoryLimit', 'phpiniPostMaxSize',
                            'phpiniUploadMaxFileSize', 'phpiniMaxExecutionTime',
                            'phpiniMaxInputTime'
                        ] as $option
                    ) {
                        if (NULL !== $domainIni) {
                            // Set new INI value
                            $this->setDomainIni($option, $domainIni[$option]);
                        }


                        // We ensure that client domain INI value is not greater
                        //than reseller value
                        $optionValue = $this->getResellerPermission($option);
                        if ($this->getDomainIni($option) > $optionValue) {
                            $this->setDomainIni($option, $optionValue);
                        }
                    }
                }

                if ($needChange
                    || $this->saveDomainIni(
                        $clientId, $row['domain_id'], $row['domain_type']
                    )
                ) {
                    $this->updateDomainStatuses(
                        $this->getIniLevel(),
                        $clientId,
                        $row['domain_id'],
                        $row['domain_type']
                    );
                    $needBackendRequest = true;
                }
            } catch (DatabaseException $e) {
                throw $e;
            }
        }

        return $needBackendRequest;
    }

    /**
     * Create missing PHP INI entries
     *
     * Handle case were an entry has been removed by mistake in the php_ini table
     *
     * @param int $clientId Customer unique identifier
     * @return void
     */
    protected function createMissingPhpIniEntries($clientId)
    {
        $phpini = clone($this);
        $domain = exec_query(
            'SELECT domain_id FROM domain WHERE domain_admin_id = ?',
            [$clientId]
        );
        $domainId = $domain->fetchRow(PDO::FETCH_COLUMN);

        $phpini->loadDomainIni($clientId, $domainId, 'dmn');
        // If no entry found, create one with default values
        if ($phpini->isDefaultDomainIni()) {
            $phpini->saveDomainIni($clientId, $domainId, 'dmn');
        }

        $subdomains = exec_query(
            '
                SELECT subdomain_id
                FROM subdomain
                WHERE domain_id = ? AND subdomain_status <> ?
            ',
            [$domainId, 'todelete']
        );
        while ($subdomain = $subdomains->fetchRow()) {
            $phpini->loadDomainIni(
                $clientId, $subdomain['subdomain_id'], 'sub'
            );

            // If no entry found, create one with default values
            if ($phpini->isDefaultDomainIni()) {
                $phpini->saveDomainIni(
                    $clientId, $subdomain['subdomain_id'], 'sub'
                );
            }
        }
        unset($subdomains);

        $domainAliases = exec_query(
            '
                SELECT alias_id
                FROM domain_aliasses
                WHERE domain_id = ?
                AND alias_status <> ?
            ',
            [$domainId, 'todelete']
        );
        while ($domainAlias = $domainAliases->fetchRow()) {
            $phpini->loadDomainIni($clientId, $domainAlias['alias_id'], 'als');

            // If no entry found, create one with default values
            if ($phpini->isDefaultDomainIni()) {
                $phpini->saveDomainIni(
                    $clientId, $domainAlias['alias_id'], 'als'
                );
            }
        }
        unset($domainAliases);

        $subdomainAliases = exec_query(
            '
                SELECT subdomain_alias_id
                FROM subdomain_alias
                JOIN domain_aliasses USING(alias_id)
                WHERE domain_id = ?
                AND subdomain_alias_status <> ?
            ',
            [$domainId, 'todelete']
        );
        while ($subdomainAlias = $subdomainAliases->fetchRow()) {
            $phpini->loadDomainIni(
                $clientId, $subdomainAlias['subdomain_alias_id'], 'subals'
            );

            // If no entry found, create one with default values
            if ($phpini->isDefaultDomainIni()) {
                $phpini->saveDomainIni
                ($clientId, $subdomainAlias['subdomain_alias_id'], 'subals'
                );
            }
        }
        unset($subdomainAliases);
    }

    /**
     * Loads domain INI values
     *
     * @param int|null $adminId Owner unique identifier
     * @param int|null $domainId Domain unique identifier
     * @param string|null $domainType Domain type (dmn|als|sub|subals)
     * @throws Exception
     */
    public function loadDomainIni(
        $adminId = NULL, $domainId = NULL, $domainType = NULL
    )
    {
        if (empty($this->cp)) {
            throw new Exception('You must first load client permissions.');
        }

        if (NULL !== $adminId
            && NULL !== $domainId
            && NULL !== $domainType
        ) {
            $stmt = exec_query(
                '
                    SELECT *
                    FROM php_ini
                    WHERE admin_id = ?
                    AND domain_id = ?
                    AND domain_type = ?
                ',
                [$adminId, $domainId, $domainType]
            );

            if ($stmt->rowCount()) {
                $row = $stmt->fetchRow();
                $this->ini['phpiniAllowUrlFopen'] = $row['allow_url_fopen'];
                $this->ini['phpiniDisplayErrors'] = $row['display_errors'];
                $this->ini['phpiniErrorReporting'] = $row['error_reporting'];
                $this->ini['phpiniDisableFunctions'] = $row['disable_functions'];
                $this->ini['phpiniPostMaxSize'] = $row['post_max_size'];
                $this->ini['phpiniUploadMaxFileSize'] = $row['upload_max_filesize'];
                $this->ini['phpiniMaxExecutionTime'] = $row['max_execution_time'];
                $this->ini['phpiniMaxInputTime'] = $row['max_input_time'];
                $this->ini['phpiniMemoryLimit'] = $row['memory_limit'];

                $this->isDefaultIni = false;
                return;
            }
        }

        $this->ini['phpiniAllowUrlFopen'] = 'off';
        $this->ini['phpiniDisplayErrors'] = 'off';
        // Production value
        $this->ini['phpiniErrorReporting'] = 'E_ALL & ~E_DEPRECATED & ~E_STRICT';
        $this->ini['phpiniDisableFunctions'] =
            'exec,passthru,phpinfo,popen,proc_open,show_source,shell,shell_exec'
            . ',symlink,system';

        if (!$this->clientHasPermission('phpiniMailFunction')) {
            $this->ini['phpiniDisableFunctions'] .= ',mail';
        }

        // Value taken from Debian default php.ini file
        $this->ini['phpiniMemoryLimit'] = min($this->rp['phpiniMemoryLimit'], 128);
        $this->ini['phpiniPostMaxSize'] = min($this->rp['phpiniPostMaxSize'], 8);
        $this->ini['phpiniUploadMaxFileSize'] = min($this->rp['phpiniUploadMaxFileSize'], 2);
        $this->ini['phpiniMaxExecutionTime'] = min($this->rp['phpiniMaxExecutionTime'], 30);
        $this->ini['phpiniMaxInputTime'] = min($this->rp['phpiniMaxInputTime'], 60);

        $this->isDefaultIni = true;
    }

    /**
     * Whether or not domain INI option values are set with default option
     * values.
     *
     * @return boolean
     */
    public function isDefaultDomainIni()
    {
        return $this->isDefaultIni;
    }

    /**
     * Saves domain INI values
     *
     * @param int $adminId Owner unique identifier
     * @param int $domainId Domain unique identifier
     * @param string $domainType Domain type (dmn|als|sub|subals)
     * @return bool Boolean indicating whether or not a backend request is needed
     * @throws Exception if domain PHP configuration options were not loaded
     */
    public function saveDomainIni($adminId, $domainId, $domainType)
    {
        if (!$this->ini) {
            throw new Exception('Domain PHP INI directives were not loaded.');
        }

        $stmt = exec_query(
            '
                INSERT INTO php_ini (
                    admin_id, 
                    domain_id,
                    domain_type,
                    disable_functions,
                    allow_url_fopen,
                    display_errors,
                    error_reporting,
                    post_max_size,
                    upload_max_filesize,
                    max_execution_time,
                    max_input_time,
                    memory_limit
                ) VALUES (
                    :admin_id,
                    :domain_id,
                    :domain_type,
                    :disable_functions,
                    :allow_url_fopen,
                    :display_errors,
                    :error_reporting,
                    :post_max_size,
                    :upload_max_file_size,
                    :max_execution_time,
                    :max_input_time,
                    :memory_limit
                ) ON DUPLICATE KEY UPDATE
                    disable_functions = :disable_functions,
                    allow_url_fopen = :allow_url_fopen,
                    display_errors = :display_errors,
                    error_reporting = :error_reporting,
                    post_max_size = :post_max_size,
                    upload_max_filesize = :upload_max_file_size,
                    max_execution_time = :max_execution_time,
                    max_input_time = :max_input_time,
                    memory_limit = :memory_limit
            ',
            [
                'admin_id'             => $adminId,
                'domain_id'            => $domainId,
                'domain_type'          => $domainType,
                'disable_functions'    => $this->ini['phpiniDisableFunctions'],
                'allow_url_fopen'      => $this->ini['phpiniAllowUrlFopen'],
                'display_errors'       => $this->ini['phpiniDisplayErrors'],
                'error_reporting'      => $this->ini['phpiniErrorReporting'],
                'post_max_size'        => $this->ini['phpiniPostMaxSize'],
                'upload_max_file_size' => $this->ini['phpiniUploadMaxFileSize'],
                'max_execution_time'   => $this->ini['phpiniMaxExecutionTime'],
                'max_input_time'       => $this->ini['phpiniMaxInputTime'],
                'memory_limit'         => $this->ini['phpiniMemoryLimit']
            ]
        );

        return (bool)$stmt->rowCount();
    }

    /**
     * Gets client PHP permission(s)
     *
     * @param string|null $perm Permission name or null for all permissions
     * @return mixed
     * @throws Exception if $permission is unknown
     */
    public function getClientPermission($perm = NULL)
    {
        if (NULL === $perm) {
            return $this->cp;
        }

        if (!array_key_exists($perm, $this->cp)) {
            throw new Exception(sprintf(
                'Unknown client PHP permission: %s', $perm
            ));
        }

        return $this->cp[$perm];
    }

    /**
     * Sets value for a domain INI option
     *
     * We are safe here. New value is set only if valid.
     *
     * @param string $varname Configuration option name
     * @param string $value Configuration option value
     * @throws Exception
     */
    public function setDomainIni($varname, $value)
    {
        if (empty($this->cp)) {
            throw new Exception('You must first load client permissions.');
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
            case 'phpiniErrorReporting':
                break;
            default:
                if (!$this->clientHasPermission($varname)) {
                    return;
                }
        }

        $this->ini[$varname] = $value;
        $this->isDefaultIni = false;
    }

    /**
     * Validate value for the given domain PHP configuration option
     *
     * Be aware that we don't allow unlimited values. This is by design.
     *
     * @param string $var Configuration option name
     * @param string $val Configuration option value
     * @return bool TRUE if $value is valid, FALSE otherwise
     * @throws Exception if $varname is unknown
     */
    public function validateDomainIni($var, $val)
    {
        switch ($var) {
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
                return $val === 'on' || $val === 'off';
            case 'phpiniErrorReporting':
                return
                    // Default value
                    $val === 'E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED'
                    // All error (development value)
                    || $val === '-1'
                    // Production
                    || $val === 'E_ALL & ~E_DEPRECATED & ~E_STRICT';
            case 'phpiniDisableFunctions':
                $allowedFunctionNames = [
                    'exec', 'mail', 'passthru', 'phpinfo', 'popen', 'proc_open',
                    'show_source', 'shell', 'shell_exec', 'symlink', 'system', ''
                ];

                return array_diff(
                    explode(',', $val), $allowedFunctionNames
                ) ? false : true;
            case 'phpiniMemoryLimit':
            case 'phpiniMaxExecutionTime':
            case 'phpiniMaxInputTime':
                return is_number($val)
                    && $val >= 1
                    && $val <= 10000;
            case 'phpiniPostMaxSize':
                // According PHP doc, post_max_size value *should* be lower than
                // memory_limit value Limit released since i-MSCP 1.4.4
                return is_number($val)
                    //&& $value < $this->domainIni['phpiniMemoryLimit']
                    && $val >= 1
                    && $val <= 10000;
            case 'phpiniUploadMaxFileSize':
                // According PHP doc, max_upload_filesize value *must* be lower
                // than post_max_size value Equality accepted since i-MSCP 1.4.4
                return is_number($val)
                    //&& $value < $this->domainIni['phpiniPostMaxSize']
                    && $val <= $this->ini['phpiniPostMaxSize']
                    && $val >= 1
                    && $val <= 10000;
            default:
                throw new Exception(sprintf(
                    'Unknown configuration option: %s', $var
                ));
        }
    }

    /**
     * Gets reseller PHP permission(s)
     *
     * @param string|null $perm Permission name or null for all permissions
     * @return mixed
     * @throws Exception if $permission is unknown
     */
    public function getResellerPermission($perm = NULL)
    {
        if (NULL === $perm) {
            return $this->rp;
        }

        if (!array_key_exists($perm, $this->rp)) {
            throw new Exception(sprintf(
                'Unknown reseller PHP permission: %s', $perm
            ));
        }

        return $this->rp[$perm];
    }

    /**
     * Update domain statuses
     *
     * @param string $configLevel PHP configuration level
     *                            (per_user|per_domain|per_site)
     * @param int $adminId Owner unique identifier
     * @param int $domainId Domain unique identifier
     * @param string $domainType Domain type (dmn|als|sub|subals)
     */
    public function updateDomainStatuses(
        $configLevel, $adminId, $domainId, $domainType
    )
    {
        if ($configLevel == 'per_user') {
            $domainId = get_user_domain_id($adminId);
            exec_query(
                "
                    UPDATE domain
                    SET domain_status = 'tochange'
                    WHERE domain_id = ?
                    AND domain_status NOT IN('disabled', 'todelete')
                ",
                [$domainId]
            );
            exec_query(
                "
                    UPDATE domain_aliasses
                    SET alias_status = 'tochange'
                    WHERE domain_id = ?
                    AND alias_status NOT IN ('disabled', 'todelete')
                ",
                [$domainId]
            );
        } else {
            switch ($domainType) {
                case 'dmn':
                    $query = "
                        UPDATE domain
                        SET domain_status = 'tochange'
                        WHERE domain_admin_id = ?
                        AND domain_id = ?
                        AND domain_status NOT IN ('disabled', 'todelete')
                    ";
                    break;
                case 'sub':
                    $query = "
                        UPDATE subdomain
                        JOIN domain USING(domain_id)
                        SET subdomain_status = 'tochange'
                        WHERE domain_admin_id = ?
                        AND subdomain_id = ?
                        AND subdomain_status NOT IN ('disabled','todelete')
                    ";
                    break;
                case 'als';
                    $query = "
                        UPDATE domain_aliasses
                        JOIN domain USING(domain_id)
                        SET alias_status = 'tochange'
                        WHERE domain_admin_id = ?
                        AND alias_id = ?
                        AND alias_status NOT IN ('disabled','todelete')
                    ";
                    break;
                case 'subals':
                    $query = "
                        UPDATE subdomain_alias
                        JOIN domain_aliasses USING(alias_id)
                        JOIN domain USING(domain_id)
                        SET subdomain_alias_status = 'tochange'
                        WHERE domain_admin_id = ?
                        AND subdomain_alias_id = ?
                        AND subdomain_alias_status NOT IN (
                            'disabled','todelete'
                        )
                    ";
                    break;
                default:
                    throw new Exception('Unknown domain type');
            }

            exec_query($query, [$adminId, $domainId]);
        }
    }

    /**
     * Return current PHP ini level
     *
     * @return string
     */
    protected function getIniLevel()
    {
        if (NULL === $this->iniLevel) {
            $phpConfig = new FileConfig(utils_normalizePath(
                iMSCP_Registry::get('config')->CONF_DIR . '/php/php.data'
            ));
            $this->iniLevel = $phpConfig['PHP_CONFIG_LEVEL'];
        }

        return $this->iniLevel;
    }
}
