<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_Exception as iMSCPException;
use iMSCP_Registry as Registry;

/**
 * Translate mail type
 *
 * @param string $mailAcc Mail account name
 * @param  string $mailType Mail account type
 * @return string Translated mail account type
 * @throws Zend_Exception
 */
function user_trans_mail_type($mailAcc, $mailType)
{
    switch ($mailType) {
        case MT_NORMAL_MAIL:
        case MT_ALIAS_MAIL:
        case MT_SUBDOM_MAIL:
        case MT_ALSSUB_MAIL:
            return tr('Normal account');
        case MT_NORMAL_FORWARD:
        case MT_ALIAS_FORWARD:
            return tr('Forward account') .
                (in_array($mailAcc, ['abuse', 'hostmaster', 'postmaster', 'webmaster']) ? ' ' . tr('(default)') : '');
        case MT_SUBDOM_FORWARD:
        case MT_ALSSUB_FORWARD:
            return tr('Forward account') . ($mailAcc == 'webmaster' ? ' ' . tr('(default)') : '');
        case MT_NORMAL_MAIL . ',' . MT_NORMAL_FORWARD:
        case MT_ALIAS_MAIL . ',' . MT_ALIAS_FORWARD:
        case MT_SUBDOM_MAIL . ',' . MT_SUBDOM_FORWARD:
        case MT_ALSSUB_MAIL . ',' . MT_ALSSUB_FORWARD:
            return tr('Normal & forward account');
            break;
        case MT_NORMAL_CATCHALL:
        case MT_ALIAS_CATCHALL:
        case MT_SUBDOM_CATCHALL:
        case MT_ALSSUB_CATCHALL:
            return tr('Catch-all account');
        default:
            return tr('Unknown type.');
    }
}

/**
 * Tells whether or not the current customer can access to the given feature(s)
 *
 * @param array|string $featureNames Feature name(s) (insensitive case)
 * @param bool $forceReload If true force data to be reloaded
 * @return bool TRUE if $featureName is available for customer, FALSE otherwise
 * @throws Zend_Exception
 * @throws iMSCP_Exception When $featureName is not known
 * @throws iMSCP_Exception_Database
 */
function customerHasFeature($featureNames, $forceReload = false)
{
    static $availableFeatures = NULL;
    static $debug = false;

    if (NULL === $availableFeatures || $forceReload) {
        $cfg = Registry::get('config');
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
            $stmt = exec_query(
                'SELECT support_system FROM reseller_props WHERE reseller_id = ?', $_SESSION['user_created_by']
            );
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
            throw new iMSCPException(
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
 * @throws iMSCP_Exception
 * @throws Zend_Exception
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
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function customerHasDomain($domainName, $customerId)
{
    $domainName = encode_idna($domainName);

    // Check in domain table
    $stmt = exec_query("SELECT 'found' FROM domain WHERE domain_admin_id = ? AND domain_name = ?", [
        $customerId, $domainName
    ]);

    if ($stmt->rowCount()) {
        return true;
    }

    // Check in domain_aliasses table
    $stmt = exec_query(
        "
            SELECT 1
            FROM domain AS t1
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
            SELECT 1
            FROM domain AS t1
            JOIN subdomain AS t2 ON (t2.domain_id = t1.domain_id)
            WHERE t1.domain_admin_id = ?
            AND CONCAT(t2.subdomain_name, '.', t1.domain_name) = ?
        ",
        [$customerId, $domainName]
    );

    if ($stmt->rowCount()) {
        return true;
    }

    // Check in subdomain_alias table
    $stmt = exec_query(
        "
            SELECT 1 FROM domain AS t1
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
 * Get mount points
 *
 * @param int $domainId Main domain unique identifier
 * @return array List of mount points
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
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
 * @throws iMSCPException
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
            throw new iMSCPException('Unknown domain type');
    }

    $stmt = exec_query($query, [$domainId, $ownerId]);
    if (!$stmt->rowCount()) {
        throw new iMSCPException("Couldn't find domain data");
    }

    return $stmt->fetchRow(PDO::FETCH_NUM);
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
function parseMaildirsize($maildirsizeFilePath, $refreshData = false)
{
    if (!$refreshData
        && !empty($_SESSION['maildirsize'][$maildirsizeFilePath])
        && $_SESSION['maildirsize'][$maildirsizeFilePath]['timestamp'] < (time() + 300)
    ) {
        return $_SESSION['maildirsize'][$maildirsizeFilePath];
    }

    unset($_SESSION['maildirsize'][$maildirsizeFilePath]);

    $fh = @fopen($maildirsizeFilePath, 'r');
    if (!$fh) {
        return false;
    }

    $maildirsize = [
        'quota_bytes'    => 0,
        'quota_messages' => 0,
        'byte_count'     => 0,
        'file_count'     => 0,
        'timestamp'      => time()
    ];

    // Parse quota definition

    if (($line = fgets($fh)) === false) {
        fclose($fh);
        return false;
    }

    $quotaDefinition = explode(',', $line, 2);

    if (!isset($quotaDefinition[0])
        || !preg_match('/(\d+)S/i', $quotaDefinition[0], $m)
    ) {
        // No quota definition. Skip processing...
        fclose($fh);
        return false;
    }

    $maildirsize['quota_bytes'] = $m[1];

    if (isset($quotaDefinition[1])
        && preg_match('/(\d+)C/i', $quotaDefinition[1], $m)
    ) {
        $maildirsize['quota_messages'] = $m[1];
    }

    // Parse byte and file counts

    while (($line = fgets($fh)) !== false) {
        if (preg_match('/^\s*(-?\d+)\s+(-?\d+)\s*$/', $line, $m)) {
            $maildirsize['byte_count'] += $m[1];
            $maildirsize['file_count'] += $m[2];
        }
    }

    fclose($fh);
    return $_SESSION['maildirsize'][$maildirsizeFilePath] = $maildirsize;
}

/**
 * Delete the given subdomain, including any entity that belong to it
 *
 * @param int $id Subdomain unique identifier
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function deleteSubdomain($id)
{
    ignore_user_abort(true);
    set_time_limit(0);

    $stmt = exec_query(
        "
            SELECT t1.domain_id, CONCAT(t1.subdomain_name, '.', t2.domain_name) AS subdomain_name, t1.subdomain_mount
            FROM subdomain AS t1
            JOIN domain AS t2 USING(domain_id)
            WHERE t1.subdomain_id = ?
            AND t2.domain_admin_id = ?
        ",
        [$id, $_SESSION['user_id']]
    );

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

    $db = Database::getInstance();

    try {
        $db->beginTransaction();

        EventsManager::getInstance()->dispatch(Events::onBeforeDeleteSubdomain, [
            'subdomainId'   => $id,
            'subdomainName' => $row['subdomain_name'],
            'subdomainType' => 'sub',
            'type'          => 'sub' # BC -- will be removed in a later version
        ]);

        // Delete FTP groups and FTP accounting/limit data
        $stmt = exec_query(
            "
                SELECT groupname, members
                FROM ftp_group
                JOIN ftp_users USING(gid)
                WHERE userid LIKE CONCAT('%@', ?)
                LIMIT 1
            ",
            $row['subdomain_name']
        );
        if ($stmt->rowCount()) {
            $ftpGroupData = $stmt->fetchRow(PDO::FETCH_ASSOC);
            $members = array_filter(
                preg_split('/,/', $ftpGroupData['members'], -1, PREG_SPLIT_NO_EMPTY),
                function ($member) use ($row) {
                    return !preg_match("/@{$row['subdomain_name']}$/", $member);
                }
            );

            if (empty($members)) {
                exec_query('DELETE FROM ftp_group WHERE groupname = ?', $ftpGroupData['groupname']);
                exec_query('DELETE FROM quotalimits WHERE name = ?', $ftpGroupData['groupname']);
                exec_query('DELETE FROM quotatallies WHERE name = ?', $ftpGroupData['groupname']);
            } else {
                exec_query('UPDATE ftp_group SET members = ? WHERE groupname = ?', [
                    implode(',', $members), $ftpGroupData['groupname']
                ]);
            }

            unset($ftpGroupData, $members);
        }

        // Delete PHP ini entries
        exec_query("DELETE FROM php_ini WHERE domain_id = ? AND domain_type = 'sub'", $id);

        // Schedule FTP accounts deletion
        exec_query("UPDATE ftp_users SET status = 'todelete' WHERE userid LIKE ?", '%@' . $row['subdomain_name']);

        // Schedule mail accounts deletion
        exec_query("UPDATE mail_users SET status = 'todelete' WHERE sub_id = ? AND mail_type LIKE '%subdom_%'", $id);

        // Schedule SSL certificates deletion
        exec_query("UPDATE ssl_certs SET status = 'todelete' WHERE domain_id = ? AND domain_type = 'sub'", $id);

        // Schedule protected area deletion        
        exec_query("UPDATE htaccess SET status = 'todelete' WHERE dmn_id = ? AND path LIKE ?", [
            $row['domain_id'], utils_normalizePath($row['subdomain_mount']) . '%'
        ]);

        // Schedule subdomain deletion
        exec_query("UPDATE subdomain SET subdomain_status = 'todelete' WHERE subdomain_id = ?", $id);

        EventsManager::getInstance()->dispatch(Events::onAfterDeleteSubdomain, [
            'subdomainId'   => $id,
            'subdomainName' => $row['subdomain_name'],
            'subdomainType' => 'sub',
            'type'          => 'sub' # BC -- will be removed in a later version
        ]);

        $db->commit();
        send_request();
        write_log(
            sprintf(
                'Deletion of the %s subdomain has been scheduled by %s', decode_idna($row['subdomain_alias_name']),
                $_SESSION['user_logged']
            ),
            E_USER_NOTICE
        );
        set_page_message(tr('Subdomain scheduled for deletion.'), 'success');
    } catch (iMSCPException $e) {
        $db->rollBack();
        write_log(sprintf('System was unable to remove a subdomain: %s', $e->getMessage()), E_ERROR);
        set_page_message(tr("Couldn't delete subdomain. An unexpected error occurred."), 'error');
    }
}

/**
 * Delete the given subdomain alias, including any entity that belong to it
 *
 * @param int $id Subdomain alias unique identifier
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function deleteSubdomainAlias($id)
{
    ignore_user_abort(true);
    set_time_limit(0);

    $domainId = get_user_domain_id($_SESSION['user_id']);

    $stmt = exec_query(
        "
            SELECT CONCAT(t1.subdomain_alias_name, '.', t2.alias_name) AS subdomain_alias_name,
                t1.subdomain_alias_mount
            FROM subdomain_alias AS t1
            JOIN domain_aliasses AS t2 USING(alias_id)
            WHERE t2.domain_id = ?
            AND t1.subdomain_alias_id = ?
        ",
        [$domainId, $id]
    );

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
    $db = Database::getInstance();

    try {
        $db->beginTransaction();

        EventsManager::getInstance()->dispatch(Events::onBeforeDeleteSubdomain, [
            'subdomainId'   => $id,
            'subdomainName' => $row['subdomain_alias_name'],
            'subdomainType' => 'alssub',
            'type'          => 'alssub', # BC -- will be removed in a later version
        ]);

        // Delete FTP groups and FTP accounting/limit data
        $stmt = exec_query(
            "
                SELECT groupname, members
                FROM ftp_group
                JOIN ftp_users USING(gid)
                WHERE userid LIKE CONCAT('%@', ?)
                LIMIT 1
            ",
            $row['subdomain_alias_name']
        );
        if ($stmt->rowCount()) {
            $ftpGroupData = $stmt->fetchRow(PDO::FETCH_ASSOC);
            $members = array_filter(
                preg_split('/,/', $ftpGroupData['members'], -1, PREG_SPLIT_NO_EMPTY),
                function ($member) use ($row) {
                    return !preg_match("/@{$row['subdomain_alias_name']}$/", $member);
                }
            );

            if (empty($members)) {
                exec_query('DELETE FROM ftp_group WHERE groupname = ?', $ftpGroupData['groupname']);
                exec_query('DELETE FROM quotalimits WHERE name = ?', $ftpGroupData['groupname']);
                exec_query('DELETE FROM quotatallies WHERE name = ?', $ftpGroupData['groupname']);
            } else {
                exec_query('UPDATE ftp_group SET members = ? WHERE groupname = ?', [
                    implode(',', $members), $ftpGroupData['groupname']
                ]);
            }

            unset($ftpGroupData, $members);
        }

        // Delete PHP ini entries
        exec_query("DELETE FROM php_ini WHERE domain_id = ? AND domain_type = 'subals'", $id);

        // Schedule FTP accounts deletion
        exec_query("UPDATE ftp_users SET status = 'todelete' WHERE userid LIKE ?", '%@' . $row['subdomain_alias_name']);

        // Schedule mail accounts deletion
        exec_query("UPDATE mail_users SET status = 'todelete' WHERE sub_id = ? AND mail_type LIKE '%alssub_%'", $id);

        // Schedule SSL certificates deletion
        exec_query("UPDATE ssl_certs SET status = 'todelete' WHERE domain_id = ? AND domain_type = 'alssub'", $id);

        // Schedule protected areas deletion
        exec_query("UPDATE htaccess SET status = 'todelete' WHERE dmn_id = ? AND path LIKE ?", [
            $domainId, utils_normalizePath($row['subdomain_alias_mount']) . '%'
        ]);

        // Schedule subdomain aliases deletion
        exec_query("UPDATE subdomain_alias SET subdomain_alias_status = 'todelete' WHERE subdomain_alias_id = ?", $id);

        EventsManager::getInstance()->dispatch(Events::onAfterDeleteSubdomain, [
            'subdomainId'   => $id,
            'subdomainName' => $row['subdomain_alias_name'],
            'subdomainType' => 'alssub',
            'type'          => 'alssub', # BC -- will be removed in a later version
        ]);

        $db->commit();

        send_request();
        write_log(
            sprintf(
                'Deletion of the %s subdomain has been scheduled by %s', decode_idna($row['subdomain_alias_name']),
                $_SESSION['user_logged']
            ),
            E_USER_NOTICE
        );
        set_page_message(tr('Subdomain scheduled for deletion.'), 'success');
    } catch (iMSCPException $e) {
        $db->rollBack();
        write_log(sprintf('System was unable to remove a subdomain: %s', $e->getMessage()), E_ERROR);
        set_page_message(tr("Couldn't delete subdomain. An unexpected error occurred."), 'error');
        redirectTo('domains_manage.php');
    }
}

/**
 * Check if SQL databases limit of the given customer is reached
 *
 * @return bool TRUE if SQL database limit is reached, FALSE otherwise
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function customerSqlDbLimitIsReached()
{
    $domainProps = get_domain_default_props($_SESSION['user_id']);

    if ($domainProps['domain_sqld_limit'] == 0
        || get_customer_sql_databases_count($domainProps['domain_id']) < $domainProps['domain_sqld_limit']) {
        return false;
    }

    return true;
}
