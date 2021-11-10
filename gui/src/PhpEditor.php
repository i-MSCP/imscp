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
 * PhpUnused
 */

declare(strict_types=1);

namespace iMSCP;

use iMSCP\Config\FileConfig;
use iMSCP\Exception\Exception;
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
    static private $instance;

    /**
     * @var array Reseller PHP permissions, including limits for configuration
     *            options
     */
    private $rp = [];

    /**
     * @var array Client PHP permissions
     */
    private $cp = [];

    /**
     * @var array Domain configuration options
     */
    private $ini = [];

    /**
     * @var bool Tells whether or not domain INI values are set with default
     *           values
     */
    private $isDefaultIni = true;

    /**
     * @var string PHP INI level
     */
    private $iniLevel;

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
     * @param string $resellerId Reseller unique identifier
     * @return void
     */
    public function saveResellerPermissions(string $resellerId): void
    {
        exec_query(
            '
                UPDATE `reseller_props`
                SET `php_ini_system` = ?,
                    `php_ini_al_disable_functions` = ?,
                    `php_ini_al_mail_function` = ?,
                    `php_ini_al_mail_function` = ?,
                    `php_ini_al_allow_url_fopen` = ?,
                    `php_ini_al_display_errors` = ?,
                    `php_ini_max_post_max_size` = ?,
                    `php_ini_max_upload_max_filesize` = ?,
                    `php_ini_max_max_execution_time` = ?,
                    `php_ini_max_max_input_time` = ?,
                    `php_ini_max_memory_limit` = ?
                WHERE `reseller_id` = ?
            ',
            [
                $this->rp['phpiniSystem'], $this->rp['phpiniAllowUrlFopen'],
                $this->rp['phpiniDisplayErrors'],
                $this->rp['phpiniDisableFunctions'],
                $this->rp['phpiniMailFunction'],
                $this->rp['phpiniPostMaxSize'],
                $this->rp['phpiniUploadMaxFileSize'],
                $this->rp['phpiniMaxExecutionTime'],
                $this->rp['phpiniMaxInputTime'], $this->rp['phpiniMemoryLimit'],
                $resellerId
            ]
        );
    }

    /**
     * Sets the value of a reseller PHP permission
     *
     * We are safe here. New value is set only if valid.
     *
     * @param string $permission Permission name
     * @param int|string $value Permission value
     * @return void
     */
    public function setResellerPermission(string $permission, $value): void
    {
        switch ($permission) {
            case 'phpiniSystem':
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
            case 'phpiniMailFunction':
            case 'phpiniDisableFunctions':
                if ($this->validatePermission($permission, $value)) {
                    $this->rp[$permission] = $value;
                }
                break;
            case 'phpiniMemoryLimit':
            case 'phpiniMaxInputTime':
            case 'phpiniMaxExecutionTime':
                if (is_number($value) && $value >= 1 && $value <= 10000) {
                    $this->rp[$permission] = $value;
                }
                break;
            case 'phpiniPostMaxSize':
                // According PHP doc, post_max_size value *should* be lower than
                // memory_limit value
                // Limit released since i-MSCP 1.4.4
                if (is_number($value)
                    //&& $value < $this->resellerPermissions['phpiniMemoryLimit']
                    && $value >= 1 && $value <= 10000
                ) {
                    $this->rp[$permission] = $value;
                }
                break;
            case 'phpiniUploadMaxFileSize':
                // According PHP doc, max_upload_filesize value *must* be lower
                // than post_max_size value
                // Equality accepted since i-MSCP 1.4.4
                if (is_number($value)
                    //&& $value < $this->resellerPermissions['phpiniPostMaxSize']
                    && $value <= $this->rp['phpiniPostMaxSize']
                    && $value >= 1 && $value <= 10000
                ) {
                    $this->rp[$permission] = $value;
                }
                break;
            default:
                throw new Exception(sprintf(
                    'Unknown reseller PHP permission: %s', $permission
                ));
        }
    }

    /**
     * Validate value for the given PHP permission.
     *
     * @param string $permission Permission name
     * @param string $value Permission value
     * @return bool TRUE if $permission is valid, FALSE otherwise
     * @throws Exception if $permission is unknown
     */
    public function validatePermission(string $permission, string $value): bool
    {
        switch ($permission) {
            case 'phpiniSystem':
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
            case 'phpiniMailFunction':
                return $value == 'yes' || $value == 'no';
            case 'phpiniDisableFunctions':
                return $value == 'yes' || $value == 'no' || $value == 'exec';
            default:
                throw new Exception(sprintf(
                    'Unknown PHP permission: %s', $permission
                ));
        }
    }

    /**
     * Sets client permission value
     *
     * We are safe here. New value is set only if valid and if client' reseller
     * has the required permission.
     *
     * @param string $permission Permission name
     * @param string $value Permission value
     * @return void
     * @throws Exception
     */
    public function setClientPermission(string $permission, string $value): void
    {
        if (!$this->validatePermission($permission, $value)
            || !$this->resellerHasPermission($permission)
        ) {
            return;
        }

        $this->cp[$permission] = $value;

        if ($permission == 'phpiniAllowUrlFopen' && $value != 'yes') {
            $this->ini['phpiniAllowUrlFopen'] = 'off';
        }

        if ($permission == 'phpiniDisplayErrors' && $value != 'yes') {
            $this->ini['phpiniDisplayErrors'] = 'off';
        }

        if ($permission == 'phpiniDisableFunctions' && $value != 'yes') {
            if ($value == 'no') {
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

        if ($permission == 'phpiniMailFunction' && $value == 'no') {
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
     * @param string $permission Permission
     * @return bool TRUE if $key is a known and reseller has permission on it
     * @throws Exception if $permission is unknown
     */
    public function resellerHasPermission(string $permission): bool
    {
        if ($this->rp['phpiniSystem'] != 'yes') {
            return false;
        }

        switch ($permission) {
            case 'phpiniSystem':
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
            case 'phpiniDisableFunctions':
            case 'phpiniMailFunction':
                return $this->rp[$permission] == 'yes';
            default;
                throw new Exception(sprintf(
                    'Unknown reseller PHP permission: %s', $permission
                ));
        }
    }

    /**
     * Does the client as the given PHP permission?
     *
     * Be aware that in case of the phpiniDisableFunctions, true is returned
     * as long as the client has either 'exec' or 'full' permission.
     *
     * @param string $permission Permission
     * @return bool TRUE if $key is a known and client has permission on it
     * @throws Exception if $permission is unknown
     */
    public function clientHasPermission(string $permission): bool
    {
        if ($this->rp['phpiniSystem'] != 'yes') {
            return false;
        }

        switch ($permission) {
            case 'phpiniSystem':
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
            case 'phpiniMailFunction':
                return $this->cp[$permission] == 'yes';
            case 'phpiniDisableFunctions':
                return $this->cp[$permission] == 'yes'
                    || $this->cp[$permission] == 'exec';
            default:
                throw new Exception(sprintf(
                    'Unknown client PHP permission: %s', $permission
                ));
        }
    }

    /**
     * Gets domain INI option value(s)
     *
     * @param string $varname Domain configuration option name
     * @return string|int|array
     * @throws Exception if $varname is unknown
     */
    public function getDomainIni(?string $varname = NULL)
    {
        if (NULL === $varname) {
            return $this->ini;
        }

        if (!array_key_exists($varname, $this->ini)) {
            throw new Exception(sprintf(
                'Unknown domain configuration option: %s', $varname
            ));
        }

        return $this->ini[$varname];
    }

    /**
     * Assemble disable_functions parameter from its parts
     *
     * @param array $disabledFuncts List of disabled function
     * @return string
     */
    public function assembleDisableFunctions(array $disabledFuncts): string
    {
        return implode(',', array_unique($disabledFuncts));
    }

    /**
     * Synchronise client PHP permissions (including domain INI options) with
     * reseller PHP permissions.
     *
     * @param string $resellerId Reseller unique identifier
     * @param string $clientId Identifier of the client for which the PHP
     *                         permissions must be synchronized.
     * @return bool Boolean indicating whether or not a backend request is
     *                      needed
     */
    public function syncClientPermissionsWithResellerPermissions(
        string $resellerId, ?string $clientId = NULL
    ): bool
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
                [], $row['admin_id'], $needChange
            )) {
                $needBackendRequest = true;
            }
        }

        return $needBackendRequest;
    }

    /**
     * Loads reseller PHP permissions
     *
     * If a reseller identifier is given, try to load permissions from the
     * database, else, load the default reseller permissions.
     *
     * Note: Reseller permissions also include limits for PHP configuration
     * options.
     *
     * @param string|null $resellerId Reseller unique identifier
     * @return void
     */
    public function loadResellerPermissions(?string $resellerId = NULL): void
    {
        if (NULL !== $resellerId) {
            $stmt = exec_query(
                '
                    SELECT
                        `php_ini_system`,
                        `php_ini_al_disable_functions`,
                        `php_ini_al_mail_function`,
                        `php_ini_al_mail_function`,
                        `php_ini_al_allow_url_fopen`,
                        `php_ini_al_display_errors`,
                        `php_ini_max_post_max_size`,
                        `php_ini_max_upload_max_filesize`,
                        `php_ini_max_max_execution_time`,
                        `php_ini_max_max_input_time`,
                        `php_ini_max_memory_limit`
                    FROM `reseller_props`
                    WHERE `reseller_id` = ?
                ',
                $resellerId
            );

            if ($stmt->rowCount()) {
                $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

                // PHP permissions
                $this->rp['phpiniSystem'] = $row['php_ini_system'];
                $this->rp['phpiniAllowUrlFopen'] =
                    $row['php_ini_al_allow_url_fopen'];
                $this->rp['phpiniDisplayErrors'] =
                    $row['php_ini_al_display_errors'];
                $this->rp['phpiniDisableFunctions'] =
                    $row['php_ini_al_disable_functions'];
                $this->rp['phpiniMailFunction'] =
                    $row['php_ini_al_mail_function'];

                // Limits for PHP configuration options
                $this->rp['phpiniPostMaxSize'] =
                    $row['php_ini_max_post_max_size'];
                $this->rp['phpiniUploadMaxFileSize'] =
                    $row['php_ini_max_upload_max_filesize'];
                $this->rp['phpiniMaxExecutionTime'] =
                    $row['php_ini_max_max_execution_time'];
                $this->rp['phpiniMaxInputTime'] =
                    $row['php_ini_max_max_input_time'];
                $this->rp['phpiniMemoryLimit'] =
                    $row['php_ini_max_memory_limit'];
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
     * @param string $clientId Client unique identifier
     * @return void
     * @throws Exception
     */
    public function loadClientPermissions(string $clientId = NULL): void
    {
        if (empty($this->rp)) {
            throw new Exception('You must first load the reseller permissions');
        }

        if (NULL !== $clientId) {
            $stmt = exec_query(
                '
                    SELECT
                        `phpini_perm_system`,
                        `phpini_perm_allow_url_fopen`,
                        `phpini_perm_display_errors`,
                        `phpini_perm_disable_functions`,
                        `phpini_perm_mail_function`
                    FROM `domain`
                    WHERE `domain_admin_id` = ?
                ',
                $clientId
            );

            if ($stmt->rowCount()) {
                $row = $stmt->fetchRow();
                $this->cp['phpiniSystem'] = $row['phpini_perm_system'];
                $this->cp['phpiniAllowUrlFopen'] =
                    $row['phpini_perm_allow_url_fopen'];
                $this->cp['phpiniDisplayErrors'] =
                    $row['phpini_perm_display_errors'];
                $this->cp['phpiniDisableFunctions'] =
                    $row['phpini_perm_disable_functions'];
                $this->cp['phpiniMailFunction'] =
                    $row['phpini_perm_mail_function'];
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
     * @param string $clientId Client unique identifier
     * @return bool Boolean Whether or not a backend request is needed
     */
    public function saveClientPermissions(string $clientId): bool
    {
        $stmt = exec_query(
            '
                UPDATE `domain`
                SET `phpini_perm_system` = ?,
                    `phpini_perm_allow_url_fopen` = ?,
                    `phpini_perm_display_errors` = ?,
                    `phpini_perm_disable_functions` = ?,
                    `phpini_perm_mail_function` = ?
                WHERE `domain_admin_id` = ?
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
     * @param array $domainIni New Domain INI values. Can be empty
     * @param string $clientId Client identifier
     * @param bool $needChange whether or not client domains must be updated
     * @return bool Whether or not a backend request is needed
     */
    public function updateClientDomainIni(
        array $domainIni, string $clientId, ?bool $needChange = false
    ): bool
    {
        $needBackendRequest = false;

        // We must ensure that there is no missing PHP INI entries (since 1.3.1)
        $this->createMissingPhpIniEntries($clientId);

        $stmt = exec_query(
            '
                SELECT `id`, `domain_id`, `domain_type`
                FROM `php_ini`
                WHERE `admin_id` = ?
            ',
            [$clientId]
        );
        while ($row = $stmt->fetchRow()) {
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
                    if (isset($domainIni[$option])) {
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

        }

        return $needBackendRequest;
    }

    /**
     * Create missing PHP INI entries
     *
     * Handle case were an entry has been removed by mistake in the php_ini
     * table.
     *
     * @param string $clientId Customer unique identifier
     * @return void
     */
    protected function createMissingPhpIniEntries(string $clientId): void
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
                SELECT `subdomain_id`
                FROM `subdomain`
                WHERE `domain_id` = ?
                AND `subdomain_status` <> ?
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
                SELECT `alias_id`
                FROM `domain_aliasses`
                WHERE `domain_id` = ?
                AND `alias_status` <> ?
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
                SELECT `subdomain_alias_id`
                FROM `subdomain_alias`
                JOIN `domain_aliasses` USING(`alias_id`)
                WHERE `domain_id` = ?
                AND `subdomain_alias_status` <> ?
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
     * @param string $adminId Owner unique identifier
     * @param string $domainId Domain unique identifier
     * @param string $domainType Domain type (dmn|als|sub|subals)
     * @reutrn void
     * @throws Exception
     */
    public function loadDomainIni(
        ?string $adminId = NULL,
        ?string $domainId = NULL,
        ?string $domainType = NULL
    ): void
    {
        if (empty($this->cp)) {
            throw new Exception('You must first load client permissions.');
        }

        if (NULL !== $adminId && NULL !== $domainId && NULL !== $domainType) {
            $stmt = exec_query(
                '
                    SELECT *
                    FROM `php_ini`
                    WHERE `admin_id` = ?
                    AND `domain_id` = ?
                    AND `domain_type` = ?
                ',
                [$adminId, $domainId, $domainType]
            );

            if ($stmt->rowCount()) {
                $row = $stmt->fetchRow();
                $this->ini['phpiniAllowUrlFopen'] = $row['allow_url_fopen'];
                $this->ini['phpiniDisplayErrors'] = $row['display_errors'];
                $this->ini['phpiniErrorReporting'] = $row['error_reporting'];
                $this->ini['phpiniDisableFunctions'] =
                    $row['disable_functions'];
                $this->ini['phpiniPostMaxSize'] = $row['post_max_size'];
                $this->ini['phpiniUploadMaxFileSize'] =
                    $row['upload_max_filesize'];
                $this->ini['phpiniMaxExecutionTime'] =
                    $row['max_execution_time'];
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
        $this->ini['phpiniMemoryLimit'] = min(
            $this->rp['phpiniMemoryLimit'], 128
        );
        $this->ini['phpiniPostMaxSize'] = min(
            $this->rp['phpiniPostMaxSize'], 8
        );
        $this->ini['phpiniUploadMaxFileSize'] = min(
            $this->rp['phpiniUploadMaxFileSize'], 2
        );
        $this->ini['phpiniMaxExecutionTime'] = min(
            $this->rp['phpiniMaxExecutionTime'], 30
        );
        $this->ini['phpiniMaxInputTime'] = min(
            $this->rp['phpiniMaxInputTime'], 60
        );

        $this->isDefaultIni = true;
    }

    /**
     * Whether or not domain INI option values are set with default option
     * values.
     *
     * @return bool
     */
    public function isDefaultDomainIni(): bool
    {
        return $this->isDefaultIni;
    }

    /**
     * Saves domain INI values
     *
     * @param string $adminId Owner unique identifier
     * @param string $domainId Domain unique identifier
     * @param string $domainType Domain type (dmn|als|sub|subals)
     * @return bool Boolean Whether or not a backend request is needed
     * @throws Exception if domain PHP configuration options were not loaded
     */
    public function saveDomainIni(
        string $adminId, string $domainId, string $domainType
    ): bool
    {
        if (!$this->ini) {
            throw new Exception('Domain PHP INI directives were not loaded.');
        }

        $stmt = exec_query(
            '
                INSERT INTO php_ini (
                    `admin_id`, `domain_id`, `domain_type`, `disable_functions`,
                    `allow_url_fopen`, `display_errors`, `error_reporting`,
                    `post_max_size`, `upload_max_filesize`, `max_execution_time`,
                    `max_input_time`, `memory_limit`
                ) VALUES (
                    :admin_id, :domain_id, :domain_type, :disable_functions,
                    :allow_url_fopen, :display_errors, :error_reporting,
                    :post_max_size, :upload_max_file_size, :max_execution_time,
                    :max_input_time, :memory_limit
                ) ON DUPLICATE KEY UPDATE
                    `disable_functions` = :disable_functions,
                    `allow_url_fopen` = :allow_url_fopen,
                    `display_errors` = :display_errors,
                    `error_reporting` = :error_reporting,
                    `post_max_size` = :post_max_size,
                    `upload_max_filesize` = :upload_max_file_size,
                    `max_execution_time` = :max_execution_time,
                    `max_input_time` = :max_input_time,
                    `memory_limit` = :memory_limit
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
     * @param string $permission Permission name
     * @return string|int|array Client permissions
     * @throws Exception if $permission is unknown
     */
    public function getClientPermission(?string $permission = NULL)
    {
        if (NULL === $permission) {
            return $this->cp;
        }

        if (!array_key_exists($permission, $this->cp)) {
            throw new Exception(sprintf(
                'Unknown client PHP permission: %s', $permission
            ));
        }

        return $this->cp[$permission];
    }

    /**
     * Sets value for a domain INI option
     *
     * We are safe here. New value is set only if valid.
     *
     * @param string $varname Configuration option name
     * @param int|string $value Configuration option value
     * @reutrn void
     * @throws Exception
     */
    public function setDomainIni(string $varname, $value): void
    {
        if (empty($this->cp)) {
            throw new Exception('You must first load the client permissions.');
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
     * @param string $varname Configuration option name
     * @param int|string $value Configuration option value
     * @return bool TRUE if $value is valid, FALSE otherwise
     * @throws Exception if $varname is unknown
     */
    public function validateDomainIni(string $varname, $value): bool
    {
        switch ($varname) {
            case 'phpiniAllowUrlFopen':
            case 'phpiniDisplayErrors':
                return $varname === 'on' || $value === 'off';
            case 'phpiniErrorReporting':
                return
                    // Default value
                    $value === 'E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED'
                    // All error (development value)
                    || $value === '-1'
                    // Production
                    || $value === 'E_ALL & ~E_DEPRECATED & ~E_STRICT';
            case 'phpiniDisableFunctions':
                $allowedFunctionNames = [
                    'exec', 'mail', 'passthru', 'phpinfo', 'popen', 'proc_open',
                    'show_source', 'shell', 'shell_exec', 'symlink', 'system', ''
                ];

                return array_diff(
                    explode(',', $value), $allowedFunctionNames
                ) ? false : true;
            case 'phpiniMemoryLimit':
            case 'phpiniMaxExecutionTime':
            case 'phpiniMaxInputTime':
                return is_number($value) && $value >= 1 && $value <= 10000;
            case 'phpiniPostMaxSize':
                // According PHP doc, post_max_size value *should* be lower than
                // memory_limit value Limit released since i-MSCP 1.4.4
                return is_number($value)
                    //&& $value < $this->domainIni['phpiniMemoryLimit']
                    && $value >= 1 && $value <= 10000;
            case 'phpiniUploadMaxFileSize':
                // According PHP doc, max_upload_filesize value *must* be lower
                // than post_max_size value Equality accepted since i-MSCP 1.4.4
                return is_number($value)
                    //&& $value < $this->domainIni['phpiniPostMaxSize']
                    && $value <= $this->ini['phpiniPostMaxSize']
                    && $value >= 1
                    && $value <= 10000;
            default:
                throw new Exception(sprintf(
                    'Unknown configuration option: %s', $varname
                ));
        }
    }

    /**
     * Gets reseller PHP permission(s)
     *
     * @param string|null $permission Permission name or null for all permissions
     * @return array|int|string Reseller permissions
     * @throws Exception if $permission is unknown
     */
    public function getResellerPermission(string $permission = NULL)
    {
        if (NULL === $permission) {
            return $this->rp;
        }

        if (!array_key_exists($permission, $this->rp)) {
            throw new Exception(sprintf(
                'Unknown reseller PHP permission: %s', $permission
            ));
        }

        return $this->rp[$permission];
    }

    /**
     * Update domain statuses
     *
     * @param string $configLevel PHP configuration level
     *                            (per_user|per_domain|per_site)
     * @param string $adminId Owner unique identifier
     * @param string $domainId Domain unique identifier
     * @param string $domainType Domain type (dmn|als|sub|subals)
     * @return void
     */
    public function updateDomainStatuses(
        string $configLevel,
        string $adminId,
        string $domainId,
        string $domainType
    ): void
    {
        if ($configLevel == 'per_user') {
            $domainId = get_user_domain_id($adminId);
            exec_query(
                "
                    UPDATE `domain`
                    SET `domain_status` = 'tochange'
                    WHERE `domain_id` = ?
                    AND `domain_status` NOT IN('disabled', 'todelete')
                ",
                [$domainId]
            );
            exec_query(
                "
                    UPDATE `domain_aliasses`
                    SET `alias_status` = 'tochange'
                    WHERE `domain_id` = ?
                    AND `alias_status` NOT IN ('disabled', 'todelete')
                ",
                [$domainId]
            );
        } else {
            switch ($domainType) {
                case 'dmn':
                    $query = "
                        UPDATE `domain`
                        SET `domain_status` = 'tochange'
                        WHERE `domain_admin_id` = ?
                        AND `domain_id` = ?
                        AND `domain_status` NOT IN ('disabled', 'todelete')
                    ";
                    break;
                case 'sub':
                    $query = "
                        UPDATE `subdomain`
                        JOIN `domain` USING(domain_id)
                        SET `subdomain_status` = 'tochange'
                        WHERE `domain_admin_id` = ?
                        AND `subdomain_id` = ?
                        AND `subdomain_status` NOT IN ('disabled','todelete')
                    ";
                    break;
                case 'als';
                    $query = "
                        UPDATE `domain_aliasses`
                        JOIN `domain` USING(`domain_id`)
                        SET `alias_status` = 'tochange'
                        WHERE `domain_admin_id` = ?
                        AND `alias_id` = ?
                        AND `alias_status` NOT IN ('disabled','todelete')
                    ";
                    break;
                case 'subals':
                    $query = "
                        UPDATE `subdomain_alias`
                        JOIN `domain_aliasses` USING(`alias_id`)
                        JOIN `domain` USING(`domain_id`)
                        SET `subdomain_alias_status` = 'tochange'
                        WHERE `domain_admin_id` = ?
                        AND `subdomain_alias_id` = ?
                        AND `subdomain_alias_status` NOT IN (
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
    protected function getIniLevel(): string
    {
        if (NULL === $this->iniLevel) {
            $phpConfig = new FileConfig(utils_normalizePath(
                Registry::get('config')['CONF_DIR'] . '/php/php.data'
            ));
            $this->iniLevel = $phpConfig['PHP_CONFIG_LEVEL'];
        }

        return $this->iniLevel;
    }
}
