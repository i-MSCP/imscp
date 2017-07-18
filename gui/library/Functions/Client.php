<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by i-MSCP Team
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
 * Returns total number of subdomains that belong to a specific domain
 *
 * Note, this function doesn't make any difference between sub domains and the
 * aliasses subdomains. The result is simply the sum of both.
 *
 * @param  int $mainDomainId Main domain identifier unique identifier
 * @return int Total number of subdomains
 */
function get_domain_running_sub_cnt($mainDomainId)
{
    return exec_query(
            'SELECT COUNT(subdomain_id) FROM subdomain WHERE domain_id = ?', $mainDomainId
        )->fetchRow(
            PDO::FETCH_COLUMN
        ) + exec_query(
            '
                SELECT COUNT(subdomain_alias_id) FROM subdomain_alias
                WHERE alias_id IN (SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
            ',
            $mainDomainId
        )->fetchRow(
            PDO::FETCH_COLUMN
        );
}

/**
 * Returns number of domain aliases that belong to a specific domain
 *
 * @param  int $domain_id Domain unique identifier
 * @return int Total number of domain aliases
 */
function get_domain_running_als_cnt($domain_id)
{
    return exec_query(
        'SELECT COUNT(alias_id) FROM domain_aliasses WHERE domain_id = ? AND alias_status <> ?',
        [$domain_id, 'ordered']
    )->fetchRow(PDO::FETCH_COLUMN);
}

/**
 * Returns information about number of mail account for a specific domain
 *
 * @param  int $domainId Domain unique identifier
 * @return array An array of values where the first item is the sum of all other items, and where each other item
 *               represents total number of a specific Mail account type
 */
function get_domain_running_mail_acc_cnt($domainId)
{
    $cfg = iMSCP_Registry::get('config');
    $query = "
        SELECT COUNT(mail_id)
        FROM mail_users
        WHERE
        domain_id = ?
        mail_type RLIKE ?
        AND mail_type NOT LIKE ?
    ";

    if ($cfg['COUNT_DEFAULT_EMAIL_ADDRESSES'] == 0) {
        $query .= " AND mail_acc NOT IN('abuse', 'postmaster', 'webmaster')";
    }

    $stmt = exec_query($query, [$domainId, 'normal_', 'normal_catchall']);
    $dmnMailAcc = $stmt->fetchRow(PDO::FETCH_COLUMN);

    $stmt = exec_query($query, [$domainId, 'alias_', 'alias_catchall']);
    $alsMailAcc = $stmt->fetchRow(PDO::FETCH_COLUMN);

    $stmt = exec_query($query, [$domainId, 'subdom_', 'subdom_catchall']);
    $subMailAcc = $stmt->fetchRow(PDO::FETCH_COLUMN);

    $stmt = exec_query($query, [$domainId, 'alssub_', 'alssub_catchall']);
    $alssubMailAcc = $stmt->fetchRow(PDO::FETCH_COLUMN);

    return [
        $dmnMailAcc + $alsMailAcc + $subMailAcc + $alssubMailAcc, $dmnMailAcc, $alsMailAcc, $subMailAcc, $alssubMailAcc
    ];
}

/**
 * Returns total number of Ftp account owned by the given customer
 *
 * @param  int $customerId Customer unique identifier
 * @return int Number of Ftp account owned by the given customer
 */
function get_customer_running_ftp_acc_cnt($customerId)
{
    return exec_query(
        'SELECT COUNT(userid) FROM ftp_users WHERE admin_id = ?', $customerId
    )->fetchRow(
        PDO::FETCH_COLUMN
    );
}

/**
 * Returns total number of databases that belong to a specific domain
 *
 * @param  int $domainId Domain unique identifier
 * @return int Total number of databases for a specific domain
 */
function get_domain_running_sqld_acc_cnt($domainId)
{
    return exec_query(
        'SELECT COUNT(sqld_id) FROM sql_database WHERE domain_id = ?', $domainId
    )->fetchRow(
        PDO::FETCH_COLUMN
    );
}

/**
 * Returns total number of SQL user that belong to a specific domain
 *
 * @param  int $domainId Domain unique identifier
 * @return int Total number of SQL users for a specific domain
 */
function get_domain_running_sqlu_acc_cnt($domainId)
{
    $stmt = exec_query(
        'SELECT DISTINCT sqlu_name FROM sql_user JOIN sql_database USING(sqld_id) WHERE domain_id = ?',
        $domainId
    );

    return $stmt->rowCount();
}

/**
 * Returns both total number of database and SQL user that belong to a specific domain
 *
 * @param  int $domainId Domain unique identifier
 * @return array An array where the first item is the Database total number, and the second the SQL users total number.
 */
function get_domain_running_sql_acc_cnt($domainId)
{
    return [get_domain_running_sqld_acc_cnt($domainId), get_domain_running_sqlu_acc_cnt($domainId)];
}

/**
 * Get domain limit properties
 *
 * @param  int $domainId Domain unique identifier
 * @return array
 */
function get_domain_running_props_cnt($domainId)
{
    $subCount = get_domain_running_sub_cnt($domainId);
    $alsCount = get_domain_running_als_cnt($domainId);

    list($mailAccCount) = get_domain_running_mail_acc_cnt($domainId);

    // Transitional query - Will be removed asap
    $stmt = exec_query('SELECT domain_admin_id FROM domain WHERE domain_id = ?', $domainId);

    $ftpAccCount = get_customer_running_ftp_acc_cnt($stmt->fields['domain_admin_id']);
    list($sqlDbCount, $sqlUserCount) = get_domain_running_sql_acc_cnt($domainId);
    return [$subCount, $alsCount, $mailAccCount, $ftpAccCount, $sqlDbCount, $sqlUserCount];
}

/**
 * Translate mail type
 *
 * @param  string $mailType
 * @return string Translated mail type
 */
function user_trans_mail_type($mailType)
{
    switch ($mailType) {
        case MT_NORMAL_MAIL:
            return tr('Domain mail');
        case MT_NORMAL_FORWARD:
            return tr('Email forward');
        case MT_ALIAS_MAIL:
            return tr('Alias mail');
        case MT_ALIAS_FORWARD:
            return tr('Alias forward');
        case MT_SUBDOM_MAIL:
            return tr('Subdomain mail');
        case MT_SUBDOM_FORWARD:
            return tr('Subdomain forward');
        case MT_ALSSUB_MAIL:
            return tr('Alias subdomain mail');
        case MT_ALSSUB_FORWARD:
            return tr('Alias subdomain forward');
        case MT_NORMAL_CATCHALL:
            return tr('Domain mail');
        case MT_ALIAS_CATCHALL:
            return tr('Domain mail');
        default:
            return tr('Unknown type.');
    }
}

/**
 * Checks if an user has permissions on a specific SQL user
 *
 * @param  int $sqlUserId SQL user unique identifier
 * @return bool TRUE if the logged in user has permission on SQL user, FALSE otherwise
 */
function check_user_sql_perms($sqlUserId)
{
    return (who_owns_this($sqlUserId, 'sqlu_id') == $_SESSION['user_id']);
}

/**
 * Returns translated gender code
 *
 * @param string $code Gender code to be returned
 * @param bool $nullOnBad Tells whether or not null must be returned on unknow $code
 * @return null|string Translated gender or null in some circonstances.
 */
function get_gender_by_code($code, $nullOnBad = false)
{
    switch (strtolower($code)) {
        case 'm':
        case 'M':
            return tr('Male');
        case 'f':
        case 'F':
            return tr('Female');
        default:
            return !$nullOnBad ? tr('Unknown') : NULL;
    }
}

/**
 * Tells whether or not the current customer can access to the given feature(s)
 *
 * @throws iMSCP_Exception When $featureName is not known
 * @param array|string $featureNames Feature name(s) (insensitive case)
 * @param bool $forceReload If true force data to be reloaded
 * @return bool TRUE if $featureName is available for customer, FALSE otherwise
 */
function customerHasFeature($featureNames, $forceReload = false)
{
    static $availableFeatures = NULL;
    static $debug = false;

    if (NULL === $availableFeatures || $forceReload) {
        $cfg = iMSCP_Registry::get('config');
        $debug = (bool)$cfg['DEBUG'];
        $dmnProps = get_domain_default_props($_SESSION['user_id']);

        $availableFeatures = [
            /*'domain' => ($dmnProps['domain_alias_limit'] != '-1'
                || $dmnProps['domain_subd_limit'] != '-1'
                || $dmnProps['domain_dns'] == 'yes'
                || $dmnProps['phpini_perm_system'] == 'yes'
                || $cfg['ENABLE_SSL']) ? true : false,
            */
            'external_mail'      => ($dmnProps['domain_external_mail'] == 'yes'),
            'php'                => ($dmnProps['domain_php'] == 'yes'),
            'php_editor'         => (
                $dmnProps['phpini_perm_system'] == 'yes'
                && $dmnProps['phpini_perm_allow_url_fopen'] == 'yes'
                || $dmnProps['phpini_perm_display_errors'] == 'yes'
                || in_array($dmnProps['phpini_perm_disable_functions'], ['yes', 'exec'])
            ),
            'cgi'                => ($dmnProps['domain_cgi'] == 'yes'),
            'ftp'                => ($dmnProps['domain_ftpacc_limit'] != '-1'),
            'sql'                => ($dmnProps['domain_sqld_limit'] != '-1'),
            'mail'               => ($dmnProps['domain_mailacc_limit'] != '-1'),
            'subdomains'         => ($dmnProps['domain_subd_limit'] != '-1'),
            'domain_aliases'     => ($dmnProps['domain_alias_limit'] != '-1'),
            'custom_dns_records' => ($dmnProps['domain_dns'] != 'no' && $cfg['NAMED_PACKAGE'] != 'Servers::noserver'),
            'webstats'           => ($cfg['WEBSTATS_PACKAGES'] != 'No'),
            'backup'             => ($cfg['BACKUP_DOMAINS'] != 'no' && $dmnProps['allowbackup'] != ''),
            'protected_areas'    => true,
            'custom_error_pages' => true,
            'aps'                => (
                $dmnProps['domain_software_allowed'] != 'no' && $dmnProps['domain_ftpacc_limit'] != '-1'
            ),
            'ssl'                => ($cfg['ENABLE_SSL'] == 1)
        ];

        if ($cfg['IMSCP_SUPPORT_SYSTEM']) {
            $stmt = exec_query('SELECT support_system FROM reseller_props WHERE reseller_id = ?', $_SESSION['user_created_by']);
            $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
            $availableFeatures['support'] = ($row['support_system'] == 'yes');
        } else {
            $availableFeatures['support'] = false;
        }
    }

    $canAccess = true;
    foreach ((array)$featureNames as $featureName) {
        $featureName = strtolower($featureName);

        if ($debug && !array_key_exists($featureName, $availableFeatures)) {
            throw new iMSCP_Exception(
                sprintf("Feature %s is not known by the customerHasFeature() function.", $featureName)
            );
        }

        if (!$availableFeatures[$featureName]) {
            $canAccess = false;
            break;
        }
    }

    return $canAccess;
}

/**
 * Tells whether or not the current customer can access the mail or external mail feature.
 * @return bool
 */
function customerHasMailOrExtMailFeatures()
{
    return (customerHasFeature('mail') || customerHasFeature('external_mail'));
}

/**
 * Does the given customer is the owner of the given domain?
 *
 * @param string $domainName Domain name (dmn,sub,als,alssub)
 * @param int $customerId Customer unique identifier
 * @return bool TRUE if the given customer is the owner of the given domain, FALSE otherwise
 * TODO add admin_id as foreign key in all domain tables too avoid too many jointures
 */
function customerHasDomain($domainName, $customerId)
{
    $domainName = encode_idna($domainName);

    // Check in domain table
    $stmt = exec_query(
        "SELECT 'found' FROM domain WHERE domain_admin_id = ? AND domain_name = ?", [$customerId, $domainName]
    );

    if ($stmt->rowCount()) {
        return true;
    }

    // Check in domain_aliasses table
    $stmt = exec_query(
        "
            SELECT 'found' FROM domain AS t1
            JOIN domain_aliasses AS t2 ON(t2.domain_id = t1.domain_id)
            WHERE t1.domain_admin_id = ?
            AND t2.alias_name = ?
        ",
        [$customerId, $domainName]
    );

    if ($stmt->rowCount()) {
        return true;
    }

    // Check in subdomain table
    $stmt = exec_query(
        "
            SELECT 'found' FROM domain AS t1
            JOIN subdomain AS t2 ON (t2.domain_id = t1.domain_id)
            WHERE t1.domain_admin_id = ? AND CONCAT(t2.subdomain_name, '.', t1.domain_name) = ?
        ",
        [$customerId, $domainName]
    );

    if ($stmt->rowCount()) {
        return true;
    }

    // Check in subdomain_alias table
    $stmt = exec_query(
        "
            SELECT 'found' FROM domain AS t1
            JOIN domain_aliasses AS t2 ON(t2.domain_id = t1.domain_id)
            JOIN subdomain_alias AS t3 ON(t3.alias_id = t2.alias_id)
            WHERE t1.domain_admin_id = ? AND CONCAT(t3.subdomain_alias_name, '.', t2.alias_name) = ?
        ",
        [$customerId, $domainName]
    );

    if ($stmt->rowCount()) {
        return true;
    }

    return false;
}

/**
 * Delete all autoreplies log for which not mail address is found in the mail_users database table
 *
 * @return void
 */
function delete_autoreplies_log_entries()
{
    exec_query("DELETE FROM autoreplies_log WHERE `from` NOT IN (SELECT mail_addr FROM mail_users)");
}

/**
 * Get mount points
 *
 * @throws iMSCP_Exception_Database
 * @param int $domainId Main domain unique identifier
 * @return array List of mount points
 */
function getMountpoints($domainId)
{
    static $mountpoints = [];

    if (empty($mountpoints)) {
        $stmt = exec_query(
            '
                SELECT subdomain_mount AS mount_point FROM subdomain WHERE domain_id = ?
                UNION ALL
                SELECT alias_mount AS mount_point FROM domain_aliasses WHERE domain_id = ?
                UNION ALL
                SELECT subdomain_alias_mount AS mount_point FROM subdomain_alias
                JOIN domain_aliasses USING(alias_id) WHERE domain_id = ?
            ',
            [$domainId, $domainId, $domainId]
        );

        if ($stmt->rowCount()) {
            $mountpoints = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        array_unshift($mountpoints, '/'); // main domain mount point
    }

    return $mountpoints;
}

/**
 * Get mount point and document root for the given domain
 *
 * @throws iMSCP_Exception
 * @param int $domainId Domain unique identifier
 * @param string $domainType Domain type (dmn,als,sub,alssub)
 * @param int $ownerId Domain owner unique identifier
 * @return array Array containing domain mount point and document root
 */
function getDomainMountpoint($domainId, $domainType, $ownerId)
{
    switch ($domainType) {
        case 'dmn':
            $query = "SELECT '/' AS mount_point, document_root FROM domain WHERE domain_id = ? AND domain_admin_id = ?";
            break;
        case 'sub':
            $query = '
              SELECT subdomain_mount AS mount_point, subdomain_document_root AS document_root
              FROM subdomain
              JOIN domain USING(domain_id)
              WHERE subdomain_id = ?
              AND domain_admin_id = ?
            ';
            break;
        case 'als':
            $query = '
              SELECT alias_mount AS mount_point, alias_document_root AS document_root
              FROM domain_aliasses
              JOIN domain USING(domain_id)
              WHERE alias_id = ?
              AND domain_admin_id = ?
            ';
            break;
        case 'alssub':
            $query = '
              SELECT subdomain_alias_mount AS mount_point, subdomain_alias_document_root AS document_root
              FROM subdomain_alias
              JOIN domain_aliasses USING(alias_id)
              JOIN domain USING(domain_id)
              WHERE subdomain_alias_id = ?
              AND domain_admin_id = ?
            ';
            break;
        default:
            throw new iMSCP_Exception('Unknown domain type');
    }

    $stmt = exec_query($query, [$domainId, $ownerId]);
    if (!$stmt->rowCount()) {
        throw new iMSCP_Exception("Couldn't find domain data");
    }

    return $stmt->fetchRow(PDO::FETCH_NUM);
}

/**
 * Send alias order email
 *
 * @param  string $aliasName
 * @return bool TRUE on success, FALSE on failure
 */
function send_alias_order_email($aliasName)
{
    $userId = $_SESSION['user_id'];
    $resellerId = who_owns_this($userId, 'user');
    $stmt = exec_query('SELECT admin_name, fname, lname, email FROM admin WHERE admin_id = ?', $userId);
    $row = $stmt->fetchRow();
    $data = get_alias_order_email($resellerId);
    $ret = send_mail([
        'mail_id'      => 'alias-order-msg',
        'fname'        => $row['fname'],
        'lname'        => $row['lname'],
        'username'     => $row['admin_name'],
        'email'        => $row['email'],
        'subject'      => $data['subject'],
        'message'      => $data['message'],
        'placeholders' => [
            '{CUSTOMER}' => decode_idna($row['admin_name']),
            '{ALIAS}'    => $aliasName
        ]
    ]);

    if (!$ret) {
        write_log(sprintf("Couldn't send alias order to %s", $row['admin_name']), E_USER_ERROR);
        return false;
    }

    return true;
}

/**
 * Parse data from the given maildirsize file
 *
 * Because processing several maildirsize files can be time consuming, the data are stored in session for next 5 minutes.
 * It is possible to refresh data by changing the $refreshData flag value to TRUE
 *
 * @see http://www.courier-mta.org/imap/README.maildirquota.html
 * @param string $maildirsizeFilePath
 * @param bool $refreshData Flag indicating if data must be refreshed
 * @return array|bool Array containing maildirsize data, FALSE on failure
 */
function parseMaildirsize($maildirsizeFilePath, $refreshData = FALSE)
{
    if (!$refreshData && !empty($_SESSION['maildirsize'][$maildirsizeFilePath])
        && $_SESSION['maildirsize'][$maildirsizeFilePath]['TIMESTAMP'] < (time() + 300)
    ) {
        return $_SESSION['maildirsize'][$maildirsizeFilePath];
    }

    unset($_SESSION['maildirsize'][$maildirsizeFilePath]);

    $fh = @fopen($maildirsizeFilePath, 'r');
    if (!$fh) {
        return false;
    }

    $maildirsize = [
        'QUOTA_BYTES'    => 0,
        'QUOTA_MESSAGES' => 0,
        'BYTE_COUNT'     => 0,
        'FILE_COUNT'     => 0,
        'TIMESTAMP'      => time()
    ];

    // Parse quota definition

    if (($line = fgets($fh)) === false) {
        fclose($fh);
        return false;
    }

    $quotaDefinition = explode(',', $line, 2);

    if (!isset($quotaDefinition[0]) || !preg_match('/(\d+)S/i', $quotaDefinition[0], $m)) {
        // No quota definition. Skip processing...
        fclose($fh);
        return false;
    }

    $maildirsize['QUOTA_BYTES'] = $m[1];

    if (isset($quotaDefinition[1]) && preg_match('/(\d+)C/i', $quotaDefinition[1], $m)) {
        $maildirsize['QUOTA_MESSAGES'] = $m[1];
    }

    // Parse byte and file counts

    while (($line = fgets($fh)) !== false) {
        if (preg_match('/^\s*(-?\d+)\s+(-?\d+)\s*$/', $line, $m)) {
            $maildirsize['BYTE_COUNT'] += $m[1];
            $maildirsize['FILE_COUNT'] += $m[2];
        }
    }

    fclose($fh);
    return $_SESSION['maildirsize'][$maildirsizeFilePath] = $maildirsize;
}
