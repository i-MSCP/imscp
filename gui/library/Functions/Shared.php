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

use iMSCP\Database\ResultSet;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_Exception as iMSCPException;
use iMSCP_Exception_Database as DatabaseException;
use iMSCP_Registry as Registry;
use Mso\IdnaConvert\IdnaConvert;

// Available mail types
define('MT_NORMAL_MAIL', 'normal_mail');
define('MT_NORMAL_FORWARD', 'normal_forward');
define('MT_ALIAS_MAIL', 'alias_mail');
define('MT_ALIAS_FORWARD', 'alias_forward');
define('MT_SUBDOM_MAIL', 'subdom_mail');
define('MT_SUBDOM_FORWARD', 'subdom_forward');
define('MT_ALSSUB_MAIL', 'alssub_mail');
define('MT_ALSSUB_FORWARD', 'alssub_forward');
define('MT_NORMAL_CATCHALL', 'normal_catchall');
define('MT_SUBDOM_CATCHALL', 'subdom_catchall');
define('MT_ALIAS_CATCHALL', 'alias_catchall');
define('MT_ALSSUB_CATCHALL', 'alssub_catchall');

/**
 * Create default mails accounts
 *
 * @throws iMSCPException
 * @param int $mainDmnId Customer main domain unique identifier
 * @param string $userEmail Customer email address
 * @param string $dmnName Domain name
 * @param string $forwardType Forward type(MT_NORMAL_FORWARD|MT_ALIAS_FORWARD|MT_SUBDOM_FORWARD|MT_ALSSUB_FORWARD)
 * @param int $subId OPTIONAL Sub-ID if default mail accounts are being created for a domain alias or subdomain
 * @return void
 */
function createDefaultMailAccounts($mainDmnId, $userEmail, $dmnName, $forwardType = MT_NORMAL_FORWARD, $subId = 0)
{
    /** @var iMSCP_Events_Manager_Interface $em */
    $em = Registry::get('iMSCP_Application')->getEventsManager();

    /** @var iMSCP_Database $db */
    $db = Registry::get('iMSCP_Application')->getDatabase();

    try {
        if ($subId == 0
            && $forwardType != MT_NORMAL_FORWARD
        ) {
            throw new iMSCPException("Mail account forward type doesn't match with provided child domain ID");
        }

        if (empty($userEmail)
            || !chk_email($userEmail)
        ) {
            write_log(
                sprintf(
                    "Couldn't create default mail accounts for the %s domain. Customer email address is not set or invalid.",
                    $dmnName
                ),
                E_USER_WARNING
            );
            return;
        }

        $userEmail = encode_idna($userEmail);

        if (in_array($forwardType, [MT_NORMAL_FORWARD, MT_ALIAS_FORWARD])) {
            $mailAccounts = ['abuse', 'hostmaster', 'postmaster', 'webmaster'];
        } else {
            $mailAccounts = ['webmaster'];
        }

        $db->beginTransaction();

        $stmt = $db->prepare(
            "
                INSERT INTO mail_users (
                    mail_acc, mail_forward, domain_id, mail_type, sub_id, status, po_active, mail_addr
                ) VALUES (
                    ?, ?, ?, ? ,?, 'toadd', 'no', CONCAT(?, '@', ?)
                )
            "
        );

        $stmt->bindParam(1, $mailAccount, PDO::PARAM_STR);
        $stmt->bindParam(2, $userEmail, PDO::PARAM_STR);
        $stmt->bindParam(3, $mainDmnId, PDO::PARAM_STR);
        $stmt->bindParam(4, $forwardType, PDO::PARAM_STR);
        $stmt->bindParam(5, $subId, PDO::PARAM_STR);
        $stmt->bindParam(6, $mailAccount, PDO::PARAM_STR);
        $stmt->bindParam(7, $dmnName, PDO::PARAM_STR);

        foreach ($mailAccounts as $mailAccount) {
            $em->dispatch(Events::onBeforeAddMail, [
                'mailType'     => 'forward',
                'mailUsername' => $mailAccount,
                'forwardList'  => $userEmail,
                'mailAddress'  => "$mailAccount@$dmnName"
            ]);
            $stmt->execute();
            $em->dispatch(Events::onAfterAddMail, [
                'mailId'       => $db->lastInsertId(),
                'mailType'     => 'forward',
                'mailUsername' => $mailAccount,
                'forwardList'  => $userEmail,
                'mailAddress'  => "$mailAccount@$dmnName"
            ]);
        }

        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        throw new $e;
    }
}

/**
 * Delete all autoreplies log for which no mail address is found in the mail_users database table
 *
 * @return void
 */
function delete_autoreplies_log_entries()
{
    execute_query(
        "DELETE FROM autoreplies_log WHERE `from` NOT IN (SELECT mail_addr FROM mail_users WHERE status <> 'todelete')"
    );
}

/***********************************************************************************************************************
 * Account functions
 */

/**
 * Returns user name matching identifier
 *
 * @param int $userId User unique identifier
 * @return string|false Username
 */
function get_user_name($userId)
{
    static $stmt = NULL;

    if (NULL === $stmt) {
        /** @var iMSCP_Database $db */
        $db = Registry::get('iMSCP_Application')->getDatabase();
        $stmt = $db->prepare('SELECT admin_name FROM admin WHERE admin_id = ?');
    }

    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

/***********************************************************************************************************************
 * Domain related functions
 */

/**
 * Checks if the given domain name already exist
 *
 * Rules:
 *
 * A domain is considered as existing if:
 *
 * - It is found either in the domain table or in the domain_aliasses table
 * - It is a subzone of another domain which doesn't belong to the given reseller
 * - It already exist as subdomain, whatever the subdomain type (sub,alssub)
 *
 * @param string $domainName Domain name to match
 * @param int $resellerId Reseller unique identifier
 * @return bool TRUE if the domain already exist, FALSE otherwise
 */
function imscp_domain_exists($domainName, $resellerId)
{
    // Be sure to work with ASCII domain name
    $domainName = encode_idna($domainName);

    // $domainName already exist in the domain table?
    $stmt = exec_query('SELECT COUNT(domain_id) FROM domain WHERE domain_name = ?', [$domainName]);

    if ($stmt->fetchColumn() > 0) {
        return true;
    }

    // $domainName already exists in the domain_aliasses table?
    $stmt = exec_query('SELECT COUNT(alias_id) FROM domain_aliasses WHERE alias_name = ?', [$domainName]);
    if ($stmt->fetchColumn() > 0) {
        return true;
    }

    # $domainName is a subzone of another domain which doesn't belong to the given reseller?
    $queryDomain = '
        SELECT COUNT(domain_id)
        FROM domain
        JOIN admin ON(admin_id = domain_admin_id)
        WHERE domain_name = ?
        AND created_by <> ?
    ';
    $queryAliases = '
        SELECT COUNT(alias_id)
        FROM domain_aliasses
        JOIN domain USING(domain_id)
        JOIN admin ON(admin_id = domain_admin_id)
        WHERE alias_name = ?
        AND created_by <> ?
    ';

    $domainLabels = explode('.', trim($domainName));
    $domainPartCnt = 0;

    for ($i = 0, $countDomainLabels = count($domainLabels) - 1; $i < $countDomainLabels; $i++) {
        $domainPartCnt = $domainPartCnt + strlen($domainLabels[$i]) + 1;
        $parentDomain = substr($domainName, $domainPartCnt);

        // Execute query the redefined queries for domains/accounts and aliases tables
        if (exec_query($queryDomain, [$parentDomain, $resellerId])->fetchColumn() > 0) {
            return true;
        }

        if (exec_query($queryAliases, [$parentDomain, $resellerId])->fetchColumn() > 0) {
            return true;
        }
    }

    // $domainName already exists as subdomain?
    $stmt = exec_query(
        "
            SELECT COUNT(subdomain_id)
            FROM subdomain
            JOIN domain USING(domain_id)
            WHERE CONCAT(subdomain_name, '.', domain_name) = ?
        ",
        [$domainName]
    );
    if ($stmt->fetchColumn() > 0) {
        return true;
    }

    $stmt = exec_query(
        "
            SELECT COUNT(subdomain_alias_id)
            FROM subdomain_alias
            JOIN domain_aliasses USING(alias_id)
            WHERE CONCAT(subdomain_alias_name, '.', alias_name) = ?
        ",
        [$domainName]
    );
    if ($stmt->fetchColumn() > 0) {
        return true;
    }

    return false;
}

/**
 * Returns domain default properties
 *
 * Note: For performance reasons, the data are retrieved once per request.
 *
 * @param int $domainAdminId Customer unique identifier
 * @param int|null $createdBy OPTIONAL reseller unique identifier
 * @return array Returns an associative array where each key is a domain propertie name.
 */
function get_domain_default_props($domainAdminId, $createdBy = NULL)
{
    static $domainProperties = NULL;

    if (NULL !== $domainProperties) {
        return $domainProperties;
    }

    if (is_null($createdBy)) {
        $stmt = exec_query('SELECT * FROM domain WHERE domain_admin_id = ?', [$domainAdminId]);
    } else {
        $stmt = exec_query(
            '
                SELECT *
                FROM domain
                JOIN admin ON(admin_id = domain_admin_id)
                WHERE domain_admin_id = ?
                AND created_by = ?
            ',
            [$domainAdminId, $createdBy]
        );
    }

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $domainProperties = $stmt->fetch();
    return $domainProperties;
}

/**
 * Return main domain unique identifier of the given customer
 *
 * @throws iMSCPException in case the domain id cannot be found
 * @param int $customeId Customer unique identifier
 * @param bool $forceReload Flag indicating whether or not data must be fetched again from database
 * @return int main domain unique identifier
 */
function get_user_domain_id($customeId, $forceReload = false)
{
    static $domainId = NULL;
    static $stmt = NULL;

    if (NULL === $stmt) {
        /** @var iMSCP_Database $db */
        $db = Registry::get('iMSCP_Application')->getDatabase();
        $stmt = $db->prepare('SELECT domain_id FROM domain WHERE domain_admin_id = ?');
    }

    if (!$forceReload && NULL !== $domainId) {
        return $domainId;
    }

    $stmt->execute([$customeId]);

    if (($domainId = $stmt->fetchColumn()) === false) {
        throw new iMSCPException(sprintf("Couldn't find domain ID of user with ID %s", $customeId));
    }

    return $domainId;
}

/**
 * Returns translated item status
 *
 * @param string $status Item status to translate
 * @param bool $showError Whether or not show true error string
 * @return string Translated status
 */
function translate_dmn_status($status, $showError = false)
{
    switch ($status) {
        case 'ok':
            return tr('Ok');
        case 'toadd':
            return tr('Addition in progress...');
        case 'tochange':
        case 'torestore':
        case 'tochangepwd':
            return tr('Modification in progress...');
        case 'todelete':
            return tr('Deletion in progress...');
        case 'disabled':
            return tr('Deactivated');
        case 'toenable':
            return tr('Activation in progress...');
        case 'todisable':
            return tr('Deactivation in progress...');
        case 'ordered':
            return tr('Awaiting for approval');
        default:
            return $showError ? $status : tr('Unexpected error');
    }
}

/**
 * Recalculates count of assigned items for the given reseller
 *
 * This is not based on the objects consumed by customers. This is based on
 * objects assigned by the reseller to its customers.
 *
 * @param int $resellerId unique reseller identifier
 * @return void
 */
function update_reseller_c_props($resellerId)
{
    exec_query(
        "
            UPDATE reseller_props AS t1
            JOIN (
                SELECT COUNT(domain_id) AS dmn_count,
                    IFNULL(SUM(IF(domain_subd_limit >= 0, domain_subd_limit, 0)), 0) AS sub_limit,
                    IFNULL(SUM(IF(domain_alias_limit >= 0, domain_alias_limit, 0)), 0) AS als_limit,
                    IFNULL(SUM(IF(domain_mailacc_limit >= 0, domain_mailacc_limit, 0)), 0) AS mail_limit,
                    IFNULL(SUM(IF(domain_ftpacc_limit >= 0, domain_ftpacc_limit, 0)), 0) AS ftp_limit,
                    IFNULL(SUM(IF(domain_sqld_limit >= 0, domain_sqld_limit, 0)), 0) AS sqld_limit,
                    IFNULL(SUM(IF(domain_sqlu_limit >= 0, domain_sqlu_limit, 0)), 0) AS sqlu_limit,
                    IFNULL(SUM(domain_disk_limit), 0) AS disk_limit,
                    IFNULL(SUM(domain_traffic_limit), 0) AS traffic_limit
                FROM admin
                JOIN domain ON(domain_admin_id = admin_id)
                WHERE created_by = ?
                AND domain_status <> 'todelete'
            ) AS t2
            SET t1.current_dmn_cnt = t2.dmn_count,
              t1.current_sub_cnt = t2.sub_limit,
              t1.current_als_cnt = t2.als_limit,
              t1.current_mail_cnt = t2.mail_limit,
              t1.current_ftp_cnt = t2.ftp_limit,
              t1.current_sql_db_cnt = t2.sqld_limit,
              t1.current_sql_user_cnt = t2.sqlu_limit,
              t1.current_disk_amnt = t2.disk_limit,
              t1.current_traff_amnt = t2.traffic_limit
            WHERE t1.reseller_id = ?
        ",
        [$resellerId, $resellerId]
    );
}

/**
 * Activate or deactivate the given customer account
 *
 * @throws iMSCPException
 * @param int $customerId Customer unique identifier
 * @param string $action Action to schedule
 * @return void
 */
function change_domain_status($customerId, $action)
{
    ignore_user_abort(true);
    set_time_limit(0);

    if ($action == 'deactivate') {
        $newStatus = 'todisable';
    } else if ($action == 'activate') {
        $newStatus = 'toenable';
    } else {
        throw new iMSCPException("Unknown action: $action");
    }

    $stmt = exec_query(
        '
            SELECT domain_id, admin_name
            FROM domain
            JOIN admin ON(admin_id = domain_admin_id)
            WHERE domain_admin_id = ?
        ',
        [$customerId]
    );

    if (!$stmt->rowCount()) {
        throw new iMSCPException(sprintf("Couldn't find domain for user with ID %s", $customerId));
    }

    $row = $stmt->fetch();
    $domainId = $row['domain_id'];
    $adminName = decode_idna($row['admin_name']);

    /** @var iMSCP_Database $db */
    $db = Registry::get('iMSCP_Application')->getDatabase();

    try {
        $db->beginTransaction();

        EventsManager::getInstance()->dispatch(Events::onBeforeChangeDomainStatus, [
            'customerId' => $customerId,
            'action'     => $action
        ]);

        if ($action == 'deactivate') {
            if (Registry::get('config')['HARD_MAIL_SUSPENSION']) { # SMTP/IMAP/POP disabled
                exec_query('UPDATE mail_users SET status = ?, po_active = ? WHERE domain_id = ?', [
                    'todisable', 'no', $domainId
                ]);
            } else { # IMAP/POP disabled
                exec_query('UPDATE mail_users SET po_active = ? WHERE domain_id = ?', ['no', $domainId]);
            }
        } else {
            exec_query(
                "
                    UPDATE mail_users
                    SET status = ?, po_active = IF(mail_type LIKE '%_mail%', 'yes', po_active)
                    WHERE domain_id = ? AND status = ?
                ",
                ['toenable', $domainId, 'disabled']
            );
            exec_query(
                "
                    UPDATE mail_users
                    SET po_active = IF(mail_type LIKE '%_mail%', 'yes', po_active)
                    WHERE domain_id = ?
                    AND status <> ?
                ",
                [$domainId, 'disabled']
            );
        }

        # TODO implements customer deactivation
        #exec_query('UPDATE admin SET admin_status = ? WHERE admin_id = ?', array($newStatus, $customerId));
        exec_query('UPDATE ftp_users SET status = ? WHERE admin_id = ?', [$newStatus, $customerId]);
        exec_query('UPDATE htaccess SET status = ? WHERE dmn_id = ?', [$newStatus, $domainId]);
        exec_query('UPDATE htaccess_groups SET status = ? WHERE dmn_id = ?', [$newStatus, $domainId]);
        exec_query('UPDATE htaccess_users SET status = ? WHERE dmn_id = ?', [$newStatus, $domainId]);
        exec_query("UPDATE domain SET domain_status = ? WHERE domain_id = ?", [$newStatus, $domainId]);
        exec_query("UPDATE subdomain SET subdomain_status = ? WHERE domain_id = ?", [$newStatus, $domainId]);
        exec_query("UPDATE domain_aliasses SET alias_status = ? WHERE domain_id = ?", [$newStatus, $domainId]);
        exec_query(
            '
                UPDATE subdomain_alias
                JOIN domain_aliasses USING(alias_id)
                SET subdomain_alias_status = ?
                WHERE domain_id = ?
            ',
            [$newStatus, $domainId]
        );
        exec_query('UPDATE domain_dns SET domain_dns_status = ? WHERE domain_id = ?', [$newStatus, $domainId]);

        EventsManager::getInstance()->dispatch(Events::onAfterChangeDomainStatus, [
            'customerId' => $customerId,
            'action'     => $action
        ]);

        $db->commit();
        send_request();

        if ($action == 'deactivate') {
            write_log(
                sprintf('%s: scheduled deactivation of customer account: %s', $_SESSION['user_logged'], $adminName),
                E_USER_NOTICE
            );
            set_page_message(tr('Customer account successfully scheduled for deactivation.'), 'success');
        } else {
            write_log(
                sprintf('%s: scheduled activation of customer account: %s', $_SESSION['user_logged'], $adminName),
                E_USER_NOTICE
            );
            set_page_message(tr('Customer account successfully scheduled for activation.'), 'success');
        }
    } catch (iMSCPException $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Deletes an SQL user
 *
 * @param int $dmnId Domain unique identifier
 * @param int $userId Sql user unique identifier
 * @return bool TRUE on success, FALSE otherwise
 */
function sql_delete_user($dmnId, $userId)
{
    ignore_user_abort(true);
    set_time_limit(0);

    $stmt = exec_query(
        '
            SELECT sqlu_name, sqlu_host, sqld_name
            FROM sql_user
            JOIN sql_database USING(sqld_id)
            WHERE sqlu_id = ?
            AND domain_id = ?
        ',
        [$userId, $dmnId]
    );

    if (!$stmt->rowCount()) {
        return false;
    }

    $row = $stmt->fetch();
    $user = $row['sqlu_name'];
    $host = $row['sqlu_host'];
    $dbName = $row['sqld_name'];

    EventsManager::getInstance()->dispatch(Events::onBeforeDeleteSqlUser, [
        'sqlUserId'   => $userId,
        'sqlUsername' => $user,
        'sqlUserhost' => $host
    ]);

    $stmt = exec_query('SELECT COUNT(sqlu_id) AS cnt FROM sql_user WHERE sqlu_name = ? AND sqlu_host = ?', [
        $user, $host
    ]);

    $row = $stmt->fetch();

    if ($row['cnt'] < 2) {
        exec_query('DELETE FROM mysql.user WHERE User = ? AND Host = ?', [$user, $host]);
        exec_query('DELETE FROM mysql.db WHERE Host = ? AND User = ?', [$host, $user]);
    } else {
        $dbName = preg_replace('/([%_])/', '\\\\$1', $dbName);
        exec_query('DELETE FROM mysql.db WHERE Host = ? AND Db = ? AND User = ?', [$host, $dbName, $user]);
    }

    exec_query('DELETE FROM sql_user WHERE sqlu_id = ?', [$userId]);
    execute_query('FLUSH PRIVILEGES');

    EventsManager::getInstance()->dispatch(Events::onAfterDeleteSqlUser, [
        'sqlUserId'   => $userId,
        'sqlUsername' => $user,
        'sqlUserhost' => $host
    ]);

    return true;
}

/**
 * Deletes the given SQL database
 *
 * @param int $dmnId Domain unique identifier
 * @param int $dbId Databse unique identifier
 * @return bool TRUE on success, false otherwise
 */
function delete_sql_database($dmnId, $dbId)
{
    ignore_user_abort(true);
    set_time_limit(0);

    $stmt = exec_query('SELECT sqld_name FROM sql_database WHERE domain_id = ? AND sqld_id = ?', [$dmnId, $dbId]);
    if (($dbName = $stmt->fetchColumn()) === false) {
        return false;
    }

    EventsManager::getInstance()->dispatch(Events::onBeforeDeleteSqlDb, [
        'sqlDbId'         => $dbId,
        'sqlDatabaseName' => $dbName
    ]);

    $stmt = exec_query(
        'SELECT sqlu_id FROM sql_user JOIN sql_database USING(sqld_id) WHERE sqld_id = ? AND domain_id = ?',
        [$dbId, $dmnId]
    );

    while ($row = $stmt->fetch()) {
        if (!sql_delete_user($dmnId, $row['sqlu_id'])) {
            return false;
        }
    }

    execute_query(sprintf('DROP DATABASE IF EXISTS %s', quoteIdentifier($dbName)));
    exec_query('DELETE FROM sql_database WHERE domain_id = ? AND sqld_id = ?', [$dmnId, $dbId]);

    EventsManager::getInstance()->dispatch(Events::onAfterDeleteSqlDb, [
        'sqlDbId'         => $dbId,
        'sqlDatabaseName' => $dbName
    ]);

    return true;
}

/**
 * Deletes the given customer
 *
 * @throws iMSCPException
 * @param integer $customerId Customer unique identifier
 * @param boolean $checkCreatedBy Tell whether or not customer must have been created by logged-in user
 * @return bool TRUE on success, FALSE otherwise
 */
function deleteCustomer($customerId, $checkCreatedBy = false)
{
    ignore_user_abort(true);
    set_time_limit(0);

    // Get username, uid and gid of domain user
    $query = '
        SELECT admin_name, created_by, domain_id
        FROM admin
        JOIN domain ON(domain_admin_id = admin_id)
        WHERE admin_id = ?
    ';

    if ($checkCreatedBy) {
        $query .= ' AND created_by = ?';
        $stmt = exec_query($query, [$customerId, $_SESSION['user_id']]);
    } else {
        $stmt = exec_query($query, [$customerId]);
    }

    if (!$stmt->rowCount()) {
        return false;
    }

    $data = $stmt->fetch();

    /** @var iMSCP_Database $db */
    $db = Registry::get('iMSCP_Application')->getDatabase();

    try {
        // Delete customer session data
        exec_query('DELETE FROM login WHERE user_name = ?', [$data['admin_name']]);

        // Delete SQL databases and SQL users
        $stmt = exec_query('SELECT sqld_id FROM sql_database WHERE domain_id = ?', [$data['domain_id']]);
        while ($sqlId = $stmt->fetchColumn()) {
            delete_sql_database($data['domain_id'], $sqlId);
        }

        $db->beginTransaction();

        EventsManager::getInstance()->dispatch(Events::onBeforeDeleteCustomer, [
            'customerId' => $customerId
        ]);

        // Delete protected areas
        exec_query(
            '
                DELETE t2, t3, t4
                FROM domain AS t1
                LEFT JOIN htaccess AS t2 ON (t2.dmn_id = t1.domain_id)
                LEFT JOIN htaccess_users AS t3 ON (t3.dmn_id = t1.domain_id)
                LEFT JOIN htaccess_groups AS t4 ON (t4.dmn_id = t1.domain_id)
                WHERE t1.domain_id = ?
            ',
            [$data['domain_id']]
        );

        // Delete traffic data
        exec_query('DELETE FROM domain_traffic WHERE domain_id = ?', [$data['domain_id']]);

        // Delete custom DNS
        exec_query('DELETE FROM domain_dns WHERE domain_id = ?', [$data['domain_id']]);

        // Delete FTP group and FTP accounting/limit data
        exec_query('DELETE FROM ftp_group WHERE groupname = ?', [$data['admin_name']]);
        exec_query('DELETE FROM quotalimits WHERE name = ?', [$data['admin_name']]);
        exec_query('DELETE FROM quotatallies WHERE name = ?', [$data['admin_name']]);

        // Delete support tickets
        exec_query('DELETE FROM tickets WHERE ticket_from = ? OR ticket_to = ?', [$customerId, $customerId]);

        // Delete user gui properties
        exec_query('DELETE FROM user_gui_props WHERE user_id = ?', [$customerId]);

        // Delete PHP ini
        exec_query('DELETE FROM php_ini WHERE admin_id = ?', [$customerId]);

        // Schedule FTP accounts deletion
        exec_query("UPDATE ftp_users SET status = 'todelete' WHERE admin_id = ?", [$customerId]);

        // Schedule mail accounts deletion
        exec_query("UPDATE mail_users SET status = 'todelete' WHERE domain_id = ?", [$data['domain_id']]);

        // Schedule subdomain aliases deletion
        exec_query(
            "
                UPDATE subdomain_alias AS t1
                JOIN domain_aliasses AS t2 ON(t2.domain_id = ?)
                SET t1.subdomain_alias_status = 'todelete'
                WHERE t1.alias_id = t2.alias_id
            ",
            [$data['domain_id']]
        );

        // Schedule domain aliases deletion
        exec_query("UPDATE domain_aliasses SET alias_status = 'todelete' WHERE domain_id = ?", [$data['domain_id']]);

        // Schedule subdomains deletion
        exec_query("UPDATE subdomain SET subdomain_status = 'todelete' WHERE domain_id = ?", [$data['domain_id']]);

        // Schedule domain deletion
        exec_query("UPDATE domain SET domain_status = 'todelete' WHERE domain_id = ?", [$data['domain_id']]);

        // Schedule customer deletion
        exec_query("UPDATE admin SET admin_status = 'todelete' WHERE admin_id = ?", [$customerId]);

        // Schedule SSL certificates deletion
        exec_query(
            "UPDATE ssl_certs SET status = 'todelete' WHERE domain_type = 'dmn' AND domain_id = ?", [$data['domain_id']]
        );
        exec_query(
            "
                UPDATE ssl_certs
                SET status = 'todelete'
                WHERE domain_id IN (SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
                AND domain_type = 'als'

            ",
            [$data['domain_id']]
        );
        exec_query(
            "
                UPDATE ssl_certs
                SET status = 'todelete'
                WHERE domain_id IN (SELECT subdomain_id FROM subdomain WHERE domain_id = ?)
                AND domain_type = 'sub'
            ",
            [$data['domain_id']]
        );
        exec_query(
            "
                UPDATE ssl_certs
                SET status = 'todelete'
                WHERE domain_id IN (
                    SELECT subdomain_alias_id
                    FROM subdomain_alias
                    WHERE alias_id IN (SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
                )
                AND domain_type = 'alssub'
            ",
            [$data['domain_id']]
        );

        // Delete autoreplies log entries
        delete_autoreplies_log_entries();

        // Update reseller properties
        update_reseller_c_props($data['created_by']);

        EventsManager::getInstance()->dispatch(Events::onAfterDeleteCustomer, [
            'customerId' => $customerId
        ]);

        $db->commit();
    } catch (iMSCPException $e) {
        $db->rollBack();
        throw $e;
    }

    // We are now ready to send a request to the daemon for delegated tasks.
    // Note: We are safe here. If the daemon doesn't answer, some entities will not be removed. In such case the
    // sysadmin will have to fix the problem causing deletion break and send a request to the daemon manually via the
    // panel, or run the imscp-rqst-mngr script manually.
    send_request();
    return true;
}

/**
 * Delete the given domain alias, including any entity that belong to it
 *
 * @param int $customerId Customer unique identifier
 * @param int $mainDomainId Customer main domain identifier
 * @param int $aliasId Domain alias unique identifier
 * @param string $aliasName Domain alias name
 * @param string $aliasMount Domain alias mount point
 * @return void
 */
function deleteDomainAlias($customerId, $mainDomainId, $aliasId, $aliasName, $aliasMount)
{
    ignore_user_abort(true);
    set_time_limit(0);

    /** @var iMSCP_Database $db */
    $db = Registry::get('iMSCP_Application')->getDatabase();

    try {
        $db->beginTransaction();

        EventsManager::getInstance()->dispatch(Events::onBeforeDeleteDomainAlias, [
            'domainAliasId'   => $aliasId,
            'domainAliasName' => $aliasName
        ]);

        // Delete FTP groups and FTP accounting/limit data
        $stmt = exec_query(
            '
                SELECT t1.groupname, t1.members
                FROM ftp_group AS t1
                JOIN admin AS t2 ON(t2.admin_name = t1.groupname)
                WHERE admin_id = ?
            ',
            [$customerId]
        );
        if ($stmt->rowCount()) {
            $ftpGroupData = $stmt->fetch();
            $members = array_filter(
                preg_split('/,/', $ftpGroupData['members'], -1, PREG_SPLIT_NO_EMPTY),
                function ($member) use ($aliasName) {
                    return !preg_match("/@(?:.+\\.)*$aliasName$/", $member);
                }
            );

            if (empty($members)) {
                exec_query('DELETE FROM ftp_group WHERE groupname = ?', [$ftpGroupData['groupname']]);
                exec_query('DELETE FROM quotalimits WHERE name = ?', [$ftpGroupData['groupname']]);
                exec_query('DELETE FROM quotatallies WHERE name = ?', [$ftpGroupData['groupname']]);
            } else {
                exec_query('UPDATE ftp_group SET members = ? WHERE groupname = ?', [
                    implode(',', $members), $ftpGroupData['groupname']
                ]);
            }

            unset($ftpGroupData, $members);
        }

        // Delete custom DNS
        exec_query('DELETE FROM domain_dns WHERE alias_id = ?', [$aliasId]);

        // Delete PHP ini
        exec_query("DELETE FROM php_ini WHERE domain_id = ? AND domain_type = 'als'", [$aliasId]);
        exec_query(
            "
                DELETE t1 FROM php_ini AS t1
                JOIN subdomain_alias AS t2 ON(t2.subdomain_alias_id = t1.domain_id  AND t1.domain_type = 'subals')
                WHERE alias_id = ?
            ",
            [$aliasId]
        );

        // Schedule FTP accounts deletion
        exec_query(
            "
                UPDATE ftp_users AS t1
                LEFT JOIN domain_aliasses AS t2 ON(alias_id = ?)
                LEFT JOIN subdomain_alias AS t3 USING(alias_id)
                SET status = 'todelete'
                WHERE (
                    userid LIKE CONCAT('%@', t3.subdomain_alias_name, '.', t2.alias_name)
                    OR
                    userid LIKE CONCAT('%@', t2.alias_name)
                )
            ",
            [$aliasId]
        );

        // Schedule mail accounts deletion
        exec_query(
            "
                UPDATE mail_users
                SET status = 'todelete'
                WHERE (
                    sub_id = ? AND mail_type LIKE '%alias_%'
                ) OR (
                    sub_id IN (SELECT subdomain_alias_id FROM subdomain_alias WHERE alias_id = ?)
                    AND mail_type LIKE '%alssub_%'
                )
            ",
            [$aliasId, $aliasId]
        );

        // Schedule SSL certificates deletion
        exec_query(
            "
                UPDATE ssl_certs
                SET status = 'todelete'
                WHERE domain_id IN (SELECT subdomain_alias_id FROM subdomain_alias WHERE alias_id = ?)
                AND domain_type = 'alssub'
            ",
            [$aliasId]
        );
        exec_query("UPDATE ssl_certs SET status = 'todelete' WHERE domain_id = ? and domain_type = 'als'", [$aliasId]);

        // Schedule protected areas deletion
        exec_query(
            "UPDATE htaccess SET status = 'todelete' WHERE dmn_id = ? AND path LIKE ?",
            [$mainDomainId, utils_normalizePath($aliasMount) . '%']
        );

        // Schedule subdomain aliases deletion
        exec_query("UPDATE subdomain_alias SET subdomain_alias_status = 'todelete' WHERE alias_id = ?", [$aliasId]);

        // Schedule domain alias deletion
        exec_query("UPDATE domain_aliasses SET alias_status = 'todelete' WHERE alias_id = ?", [$aliasId]);

        EventsManager::getInstance()->dispatch(Events::onAfterDeleteDomainAlias, [
            'domainAliasId'   => $aliasId,
            'domainAliasName' => $aliasName
        ]);

        $db->commit();

        send_request();
        write_log(
            sprintf('%s scheduled deletion of the %s domain alias', $_SESSION['user_logged'], $aliasName),
            E_USER_NOTICE
        );
        set_page_message(tr('Domain alias successfully scheduled for deletion.'), 'success');
    } catch (iMSCPException $e) {
        $db->rollBack();
        write_log(sprintf('System was unable to remove a domain alias: %s', $e->getMessage()), E_ERROR);
        set_page_message(tr("Couldn't delete domain alias. An unexpected error occurred."), 'error');
    }
}

/***********************************************************************************************************************
 * Reseller related functions
 */

/**
 * Returns properties for the given reseller
 *
 * @throws iMSCPException When reseller properties are not found
 * @param int $resellerId Reseller unique identifier
 * @param bool $forceReload Whether or not force properties reload from database
 * @return array
 */
function imscp_getResellerProperties($resellerId, $forceReload = false)
{
    static $properties = NULL;

    if (NULL === $properties || $forceReload) {
        $stmt = exec_query('SELECT * FROM reseller_props WHERE reseller_id = ? LIMIT 1', [$resellerId]);

        if (!$stmt->rowCount()) {
            throw new iMSCPException(tr('Properties for reseller with ID %d were not found in database.', $resellerId));
        }

        $properties = $stmt->fetch();
    }

    return $properties;
}

/**
 * Update reseller properties
 *
 * @param  int $resellerId Reseller unique identifier.
 * @param  array $props Array that contain new properties values
 * @return ResultSet|null
 */
function update_reseller_props($resellerId, $props)
{
    ignore_user_abort(true);
    set_time_limit(0);

    if (empty($props)) {
        return NULL;
    }

    list($dmnCur, $dmnMax, $subCur, $subMax, $alsCur, $alsMax, $mailCur, $mailMax, $ftpCur, $ftpMax, $sqlDbCur,
        $sqlDbMax, $sqlUserCur, $sqlUserMax, $traffCur, $traffMax, $diskCur, $diskMax) = explode(';', $props);

    $stmt = exec_query(
        '
            UPDATE reseller_props SET current_dmn_cnt = ?, max_dmn_cnt = ?, current_sub_cnt = ?, max_sub_cnt = ?,
                current_als_cnt = ?, max_als_cnt = ?, current_mail_cnt = ?, max_mail_cnt = ?, current_ftp_cnt = ?,
                max_ftp_cnt = ?, current_sql_db_cnt = ?, max_sql_db_cnt = ?, current_sql_user_cnt = ?,
                max_sql_user_cnt = ?, current_traff_amnt = ?, max_traff_amnt = ?, current_disk_amnt = ?,
                max_disk_amnt = ?
            WHERE reseller_id = ?
        ',
        [
            $dmnCur, $dmnMax, $subCur, $subMax, $alsCur, $alsMax, $mailCur, $mailMax, $ftpCur, $ftpMax, $sqlDbCur,
            $sqlDbMax, $sqlUserCur, $sqlUserMax, $traffCur, $traffMax, $diskCur, $diskMax, $resellerId
        ]
    );

    return $stmt;
}

/***********************************************************************************************************************
 * Mail functions
 */

/**
 * Synchronizes mailboxes quota that belong to the given domain using the given quota limit
 *
 * Algorythm:
 *
 * 1. In case the new quota limit is 0 (unlimited), equal or bigger than the sum of current quotas, we do nothing
 * 2. We have a running total, which start at zero
 * 3. We divide the quota of each mailbox by the sum of current quotas, then we multiply the result by the new quota limit
 * 4. We store the original value of the running total elsewhere, then we add the amount we have just calculated in #3
 * 5. We ensure that new quota is a least 1 MiB (each mailbox must have 1 MiB minimum quota)
 * 5. We round both old value and new value of the running total to integers, and take the difference
 * 6. We update the mailbox quota result calculated in step 5
 * 7. We repeat steps 3-6 for each quota
 *
 * This algorythm guarantees to have the total amount prorated equal to the sum of all quota after update. It also
 * ensure that each mailboxes has 1 MiB quota minimum.
 *
 * Note:  For the sum calculation of current quotas, we consider that a mailbox with a value equal to 0 (unlimited) is
 * equal to the new quota limit.
 *
 * @param int $domainId Customer main domain unique identifier
 * @param int $newQuota New quota limit in bytes
 * @return void
 */
function sync_mailboxes_quota($domainId, $newQuota)
{
    ignore_user_abort(true);
    set_time_limit(0);

    if ($newQuota == 0) {
        return;
    }

    $cfg = Registry::get('config');
    $stmt = exec_query('SELECT mail_id, quota FROM mail_users WHERE domain_id = ? AND quota IS NOT NULL', [$domainId]);

    if (!$stmt->rowCount()) {
        return;
    }

    $mailboxes = $stmt->fetchAll();
    $totalQuota = 0;

    foreach ($mailboxes as $mailbox) {
        $totalQuota += ($mailbox['quota'] == 0) ? $newQuota : $mailbox['quota'];
    }

    $totalQuota /= 1048576;
    $newQuota /= 1048576;

    if ($newQuota < $totalQuota
        || (isset($cfg['EMAIL_QUOTA_SYNC_MODE']) && $cfg['EMAIL_QUOTA_SYNC_MODE'])
        || $totalQuota == 0
    ) {
        /** @var iMSCP_Database $db */
        $db = Registry::get('iMSCP_Application')->getDatabase();

        $stmt = $db->prepare('UPDATE mail_users SET quota = ? WHERE mail_id = ?');
        $result = 0;

        foreach ($mailboxes as $mailbox) {
            $oldResult = $result;
            $mailboxQuota = (($mailbox['quota']) ? $mailbox['quota'] / 1048576 : $newQuota);
            $result += $newQuota * $mailboxQuota / $totalQuota;

            if ($result < 1) {
                $result = 1;
            }

            $stmt->execute([((int)$result - (int)$oldResult) * 1048576, $mailbox['mail_id']]);
        }
    }
}

/***********************************************************************************************************************
 * Utils functions
 */

/**
 * Redirect to the given location
 *
 * @param string $location URL to redirect to
 * @return void
 */
function redirectTo($location)
{
    header('Location: ' . $location);
    exit;
}

/**
 * Encode the given UTF-8 string to ACE form
 *
 * @param  string $string UTF-8 string to encode
 * @return string Encoded UTF-8 string (ACE string), or original string on failure
 */
function encode_idna($string)
{
    if (!Registry::isRegistered('IdnaConvert')) {
        Registry::set('IdnaConvert', new IdnaConvert([
            'encoding'    => 'utf8',
            'idn_version' => 2008,
            'strict_mode' => false // Accept any string, not only individual domain name parts
        ]));
    }

    try {
        return Registry::get('IdnaConvert')->encode($string);
    } catch (Exception $e) {
        return $string;
    }
}

/**
 * Decode the given ACE string to UTF-8
 *
 * @param  string $string ACE string to decode
 * @return string Decoded ACE string (UTF-8 string), or original string on failure
 */
function decode_idna($string)
{
    if (!Registry::isRegistered('IdnaConvert')) {
        Registry::set('IdnaConvert', new IdnaConvert([
            'encoding'    => 'utf8',
            'idn_version' => 2008,
            'strict_mode' => false // Accept any string, not only individual domain name parts   
        ]));
    }

    try {
        return Registry::get('IdnaConvert')->decode($string);
    } catch (Exception $e) {
        return $string;
    }
}

/**
 * Utils function to upload file
 *
 * @param string $inputFieldName upload input field name
 * @param string|array $destPath Destination path string or an array where the first item is an anonymous function to
 *                               run before moving file and any other items the arguments passed to the anonymous
 *                               function. The anonymous function must return a string that is the destination path or
 *                               FALSE on failure.
 *
 * @return string|bool File destination path on success, FALSE otherwise
 */
function utils_uploadFile($inputFieldName, $destPath)
{
    if (isset($_FILES[$inputFieldName]) && $_FILES[$inputFieldName]['error'] == UPLOAD_ERR_OK) {
        $tmpFilePath = $_FILES[$inputFieldName]['tmp_name'];

        if (!is_readable($tmpFilePath)) {
            set_page_message(tr('File is not readable.'), 'error');
            return false;
        }

        if (!is_string($destPath) && is_array($destPath)) {
            if (!($destPath = call_user_func_array(array_shift($destPath), $destPath))) {
                return false;
            }
        }

        if (!@move_uploaded_file($tmpFilePath, $destPath)) {
            set_page_message(tr('Unable to move file.'), 'error');
            return false;
        }
    } else {
        switch ($_FILES[$inputFieldName]['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                set_page_message(tr('File exceeds the size limit.'), 'error');
                break;
            case UPLOAD_ERR_PARTIAL:
                set_page_message(tr('The uploaded file was only partially uploaded.'), 'error');
                break;
            case UPLOAD_ERR_NO_FILE:
                set_page_message(tr('No file was uploaded.'), 'error');
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                set_page_message(tr('Temporary folder not found.'), 'error');
                break;
            case UPLOAD_ERR_CANT_WRITE:
                set_page_message(tr('Failed to write file to disk.'), 'error');
                break;
            case UPLOAD_ERR_EXTENSION:
                set_page_message(tr('A PHP extension stopped the file upload.'), 'error');
                break;
            default:
                set_page_message(
                    tr('An unknown error occurred during file upload: %s', $_FILES[$inputFieldName]['error']), 'error'
                );
        }

        return false;
    }

    return $destPath;
}

/**
 * Returns Upload max file size in bytes
 *
 * @return int Upload max file size in bytes
 */
function utils_getMaxFileUpload()
{
    $uploadMaxFilesize = utils_getPhpValueInBytes(ini_get('upload_max_filesize'));
    $postMaxSize = utils_getPhpValueInBytes(ini_get('post_max_size'));
    $memoryLimit = utils_getPhpValueInBytes(ini_get('memory_limit'));
    return min($uploadMaxFilesize, $postMaxSize, $memoryLimit);
}

/**
 * Returns PHP directive value in bytes
 *
 * Note: If $value do not come with shorthand byte value, the value is retured
 * as this.
 *
 * See http://fr2.php.net/manual/en/faq.using.php#faq.using.shorthandbytes for
 * further explaination
 *
 * @throws iMSCPException
 * @param int|string PHP directive value
 * @return int Value in bytes
 */
function utils_getPhpValueInBytes($value)
{
    $value = trim($value);

    if (ctype_digit($value)) {
        return $value;
    }

    $unit = strtolower($value[strlen($value) - 1]);
    $value = substr($value, 0, -1);

    if ($unit == 'g') {
        return ($value * 1024);
    }

    if ($unit == 'm') {
        return ($value * 1024 * 1024);
    }

    if ($unit == 'k') {
        return ($value * 1024 * 1024 * 1024);
    }

    return $value;
}

/**
 * Normalize the given path (e.g. A//B, A/./B and A/foo/../B all become A/B)
 *
 * It should be understood that this may change the meaning of the path if it
 * contains symbolic links.
 *
 * @param string $path Path
 * @param bool $posixCompliant Be POSIX compliant regarding initial slashes?
 * @return string Normalized path
 */
function utils_normalizePath($path, $posixCompliant = false)
{
    if (strlen($path) == 0)
        return '.';

    // Attempt to avoid path encoding problems.
    $path = iconv('UTF-8', 'UTF-8//IGNORE//TRANSLIT', $path);

    $initialSlashes = strpos($path, '/') === 0;
    // POSIX allows one or two initial slashes, but treats three or more as
    // single slash.
    if ($posixCompliant
        && $initialSlashes
        && strpos($path, '//') === 0
        && strpos($path, '///') !== 0
    ) {
        $initialSlashes = 2;
    }

    $segments = explode('/', $path);
    $newSegments = [];

    foreach ($segments as $segment) {
        if ($segment === '' || $segment === '.')
            continue;

        if ($segment !== '..'
            || (!$initialSlashes && !$newSegments)
            || ($newSegments && end($newSegments) === '..')
        ) {
            array_push($newSegments, $segment);
        } elseif ($newSegments) {
            array_pop($newSegments);
        }
    }

    $path = implode('/', $newSegments);

    if ($initialSlashes) {
        $path = str_repeat('/', $initialSlashes) . $path;
    }

    return (isset($path)) ? $path : '.';
}

/**
 * Remove the given directory recursively
 *
 * @param string $directory Path of directory to remove
 * @return boolean TRUE on success, FALSE otherwise
 */
function utils_removeDir($directory)
{
    $directory = rtrim($directory, '/');

    if (!is_dir($directory)) {
        return false;
    }

    if (!is_readable($directory)) {
        return true;
    }
    $handle = opendir($directory);

    while (false !== ($item = readdir($handle))) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        $path = $directory . '/' . $item;

        if (is_dir($path)) {
            utils_removeDir($path);
        } else {
            @unlink($path);
        }

    }

    closedir($handle);

    if (!@rmdir($directory)) {
        return false;
    }

    return true;
}

/**
 * Merge two arrays
 *
 * For duplicate keys, the following is done:
 *  - Nested arrays are recursively merged
 *  - Items in $array2 with INTEGER keys are appended
 *  - Items in $array2 with STRING keys overwrite current values
 *
 * @param array $array1
 * @param array $array2
 * @return array
 */
function utils_arrayMergeRecursive(array $array1, array $array2)
{
    foreach ($array2 as $key => $value) {
        if (!array_key_exists($key, $array1)) {
            $array1[$key] = $value;
            continue;
        }

        if (is_int($key)) {
            $array1[] = $value;
        } elseif (is_array($value) && is_array($array1[$key])) {
            $array1[$key] = utils_arrayMergeRecursive($array1[$key], $value);
        } else {
            $array1[$key] = $value;
        }
    }

    return $array1;
}

/**
 * Compares array1 against array2 (recursively) and returns the difference
 *
 * @param array $array1 The array to compare from
 * @param array $array2 An array to compare against
 * @return array An array containing all the entries from array1 that are not
 *               present in $array2.
 */
function utils_arrayDiffRecursive(array $array1, array $array2)
{
    $diff = [];
    foreach ($array1 as $key => $value) {
        if (!array_key_exists($key, $array2)) {
            $diff[$key] = $value;
            continue;
        }

        if (is_array($value)) {
            $arrDiff = utils_arrayDiffRecursive($value, $array2[$key]);

            if (count($arrDiff)) {
                $diff[$key] = $arrDiff;
            }
        } elseif ($value != $array2[$key]) {
            $diff[$key] = $value;
        }
    }

    return $diff;
}

/***********************************************************************************************************************
 * Checks functions
 */

/**
 * Checks if all of the characters in the provided string are numerical
 *
 * @param string $number string to be checked
 * @return bool TRUE if all characters are numerical, FALSE otherwise
 */
function is_number($number)
{
    return (bool)preg_match('/^[0-9]+$/D', $number);
}

/**
 * Is the request a Javascript XMLHttpRequest?
 *
 * Returns true if the request‘s "X-Requested-With" header contains
 * "XMLHttpRequest".
 *
 * Note: jQuery and Prototype Javascript libraries both set this header with every Ajax request.
 *
 * @return boolean TRUE if the request‘s "X-Requested-With" header contains
 *                 "XMLHttpRequest", FALSE otherwise
 */
function is_xhr()
{
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
    ) {
        return true;
    }

    return false;
}

/**
 * Check if a data is serialized.
 *
 * @param string $data Data to be checked
 * @return boolean TRUE if serialized data, FALSE otherwise
 */
function isSerialized($data)
{
    if (!is_string($data)) {
        return false;
    }

    $data = trim($data);

    if ('N;' == $data) {
        return true;
    }

    if (preg_match("/^[aOs]:[0-9]+:.*[;}]\$/s", $data) ||
        preg_match("/^[bid]:[0-9.E-]+;\$/", $data)
    ) {
        return true;
    }

    return false;
}

/**
 * Check if the given string look like json data
 *
 * @param $string $string $string to be checked
 * @return boolean TRUE if the given string look like json data, FALSE
 *                 otherwise
 */
function isJson($string)
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

/**
 * Is https secure request
 *
 * @return boolean TRUE if is https secure request, FALSE otherwise
 */
function isSecureRequest()
{
    if ((!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (
            !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && in_array(strtolower(
                current(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO']))), ['https', 'on', 'ssl', '1']
            )
        )
    ) {
        return true;
    }

    return false;
}

/**
 * Get request scheme
 *
 * @return string
 */
function getRequestScheme()
{
    return isSecureRequest() ? 'https' : 'http';
}

/**
 * Get request host
 *
 * Code borrowed to Symfony project
 *
 * @return string
 */
function getRequestHost()
{
    $possibleHostSources = ['HTTP_X_FORWARDED_HOST', 'HTTP_HOST', 'SERVER_NAME', 'SERVER_ADDR'];
    $sourceTransformations = [
        "HTTP_X_FORWARDED_HOST" => function ($value) {
            $elements = explode(',', $value);
            return trim(end($elements));
        }
    ];

    $host = '';
    foreach ($possibleHostSources as $source) {
        if (!empty($host)) {
            break;
        }

        if (empty($_SERVER[$source])) {
            continue;
        }

        $host = $_SERVER[$source];

        if (array_key_exists($source, $sourceTransformations)) {
            $host = $sourceTransformations[$source]($host);
        }
    }

    // trim and remove port number from host
    // host is lowercase as per RFC 952/2181
    $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));

    // as the host can come from the user (HTTP_HOST and depending on the
    // configuration, SERVER_NAME too can come from the user) check that it
    // does not contain forbidden characters (see RFC 952 and RFC 2181)
    // use preg_replace() instead of preg_match() to prevent DoS attacks with
    // long host names
    if ($host && '' !== preg_replace('/(?:^\[)?[a-zA-Z0-9-:\]_]+\.?/', '', $host)) {
        throw new \UnexpectedValueException(sprintf('Invalid Host "%s"', $host));
    }

    return $host;
}

/**
 * Get request port
 *
 * @return string
 */
function getRequestPort()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
        return $_SERVER['HTTP_X_FORWARDED_PORT'];
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
    ) {
        return 443;
    }

    if ($host = $_SERVER['HTTP_HOST']) {
        if ($host[0] === '[') {
            $pos = strpos($host, ':', strrpos($host, ']'));
        } else {
            $pos = strrpos($host, ':');
        }

        if (false !== $pos) {
            return (int)substr($host, $pos + 1);
        }

        return 'https' === getRequestScheme() ? 443 : 80;
    }

    return $_SERVER['SERVER_PORT'];
}

/**
 * Get HTTP host
 *
 * The port name will be appended to the host if it's non-standard.
 *
 * @return string
 */
function getHttpHost()
{
    $scheme = getRequestScheme();
    $port = getRequestPort();

    if (('http' == $scheme && $port == 80)
        || ('https' == $scheme && $port == 443)
    ) {
        return getRequestHost();
    }

    return getRequestHost() . ':' . $port;
}

/**
 * Get request base URL
 *
 * @return string
 */
function getRequestBaseUrl()
{
    $scheme = getRequestScheme();
    $port = getRequestPort();

    if (('http' == $scheme && $port == 80)
        || ('https' == $scheme && $port == 443)
    ) {
        return $scheme . '://' . getRequestHost();
    }

    return $scheme . '://' . getRequestHost() . ':' . $port;
}

/***********************************************************************************************************************
 * Logging related functions
 */

/**
 * Writes a log message in database and notify administrator by email
 *
 * @param string $msg Message
 * @param int $logLevel Log level
 * @return void
 */
function write_log($msg, $logLevel = E_USER_WARNING)
{
    if (defined('IMSCP_SETUP')) {
        return;
    }

    $msg = '[' . getIpAddr() . '] ' . replace_html($msg);
    exec_query('INSERT INTO `log` (`log_time`,`log_message`) VALUES(NOW(), ?)', [$msg]);

    $cfg = Registry::get('config');
    if ($logLevel > $cfg['LOG_LEVEL']) {
        return;
    }

    $msg = strip_tags(preg_replace('/<br\s*\/?>/', "\n", $msg));

    if ($logLevel == E_USER_NOTICE) {
        $severity = 'Notice';
    } elseif ($logLevel == E_USER_WARNING) {
        $severity = 'Warning';
    } elseif ($logLevel == E_USER_ERROR) {
        $severity = 'Error';
    } else {
        $severity = 'Unknown error';
    }

    send_mail([
        'mail_id'      => 'imscp-log',
        'username'     => tr('administrator'),
        'email'        => $cfg['DEFAULT_ADMIN_ADDRESS'],
        'subject'      => "i-MSCP Notification ($severity)",
        'message'      => tr('Dear {NAME},

This is an automatic email sent by your i-MSCP control panel:

Server name: {HOSTNAME}
Server IP:   {SERVER_IP}
Client IP:   {CLIENT_IP}
Version:     {VERSION}
Build:       {BUILDDATE}
Severity:    {MESSAGE_SEVERITY}

==========================================================================
{MESSAGE}
==========================================================================

Please do not reply to this email.

________________
i-MSCP Mailer'),
        'placeholders' => [
            '{USERNAME}'         => tr('administrator'),
            '{HOSTNAME}'         => $cfg['SERVER_HOSTNAME'],
            '{SERVER_IP}'        => $cfg['BASE_SERVER_PUBLIC_IP'],
            '{CLIENT_IP}'        => getIpAddr() ? getIpAddr() : 'unknown',
            '{VERSION}'          => $cfg['Version'],
            '{BUILDDATE}'        => $cfg['BuildDate'] ?: tr('Unavailable'),
            '{MESSAGE_SEVERITY}' => $severity,
            '{MESSAGE}'          => $msg
        ],
    ]);
}

/**
 * Send add user email
 *
 * @param int $adminId Administrator or reseller unique identifier
 * @param string $uname Username
 * @param string $upass User password
 * @param string $uemail User email
 * @param string $ufname User firstname
 * @param string $ulname User lastname
 * @param string $utype User type
 * @return bool TRUE on success, FALSE on failure
 */
function send_add_user_auto_msg($adminId, $uname, $upass, $uemail, $ufname, $ulname, $utype)
{
    $data = get_welcome_email($adminId);
    $ret = send_mail([
        'mail_id'      => 'add-user-auto-msg',
        'fname'        => $ufname,
        'lname'        => $ulname,
        'username'     => $uname,
        'email'        => decode_idna($uemail),
        'subject'      => $data['subject'],
        'message'      => $data['message'],
        'placeholders' => [
            '{USERTYPE}' => $utype,
            '{PASSWORD}' => $upass
        ]
    ]);

    if (!$ret) {
        write_log(sprintf("Lost Password: Couldn't send welcome email to %s", $uname), E_USER_ERROR);
        return false;
    }

    return true;
}

/***********************************************************************************************************************
 * Softwares installer functions
 */

/**
 * Get all software installer options
 *
 * @throws iMSCPException in case software installer options cannot be retrieved
 * @return array An array containing software installer options
 */
function get_application_installer_conf()
{
    $stmt = execute_query('SELECT * FROM web_software_options');
    if (!$stmt->rowCount()) {
        throw new iMSCPException("Couldn't retrieve software installer options in database");
    }

    $row = $stmt->fetch();
    return [$row['use_webdepot'], $row['webdepot_xml_url'], $row['webdepot_last_update']];
}

/**
 * Check wheter the package is still installed this system
 *
 * @throws iMSCPException in case the given user cannot be retrieved in database
 * @param string $packageInstallType Package install type
 * @param string $packageName Package name
 * @param string $packageVersion Package version
 * @param string $packageLanguage Package language
 * @param int $userId User unique identifier
 * @return array
 */
function check_package_is_installed($packageInstallType, $packageName, $packageVersion, $packageLanguage, $userId)
{
    $stmt = exec_query('SELECT admin_type FROM admin WHERE admin_id = ?', [$userId]);

    if (!$stmt->rowCount()) {
        throw new iMSCPException("Couldn't found the given user in database");
    }

    $row = $stmt->fetch();

    if ($row['admin_type'] == 'admin') {
        $query = "
            SELECT software_id
            FROM web_software
            WHERE software_installtype  = ?
            AND software_name = ?
            AND software_version = ?
            AND software_language = ?
            AND software_depot = 'no'
        ";
    } else {
        $query = "
            SELECT software_id
            FROM web_software
            WHERE software_installtype = ?
            AND software_name = ?
            AND software_version= ?
            AND software_language = ?
            AND reseller_id = '" . $userId . "'
            AND software_depot = 'no'
        ";
    }

    $stmt = exec_query($query, [$packageInstallType, $packageName, $packageVersion, $packageLanguage]);
    $softwaresCount = $stmt->rowCount();
    $query = "
        SELECT software_id
        FROM web_software
        WHERE software_installtype  = ?
        AND software_name = ?
        AND software_version = ?
        AND software_language = ?
        AND software_master_id = '0'
        AND software_depot = 'yes'
    ";
    $stmt = exec_query($query, [$packageInstallType, $packageName, $packageVersion, $packageLanguage]);
    $softwaresCountDepot = $stmt->rowCount();

    if ($softwaresCount || $softwaresCountDepot) {
        if ($softwaresCount) {
            return [true, 'reseller'];
        }

        return [true, 'sw_depot'];
    }

    return [false, 'not_installed'];
}

/**
 * Get all software packages from database since last update from the websoftware depot
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $userId User unique identifier
 * @return int
 */
function get_webdepot_software_list($tpl, $userId)
{
    $stmt = execute_query('SELECT * FROM web_software_depot ORDER BY package_install_type ASC, package_title ASC');
    $rowCount = $stmt->rowCount();

    if ($rowCount) {
        while ($row = $stmt->fetch()) {
            $tpl->assign([
                'TR_PACKAGE_NAME'         => tohtml($row['package_title']),
                'TR_PACKAGE_TOOLTIP'      => tohtml($row['package_description'], 'htmlAttr'),
                'TR_PACKAGE_INSTALL_TYPE' => tohtml($row['package_install_type']),
                'TR_PACKAGE_VERSION'      => tohtml($row['package_version']),
                'TR_PACKAGE_LANGUAGE'     => tohtml($row['package_language']),
                'TR_PACKAGE_TYPE'         => tohtml($row['package_type']),
                'TR_PACKAGE_VENDOR_HP'    => $row['package_vendor_hp'] === ''
                    ? tr('N/A') : '<a href="' . $row['package_vendor_hp'] . '" target="_blank">' . tr('Vendor hompage') . '</a>'
            ]);

            list($isInstalled, $installedOn) = check_package_is_installed(
                $row['package_install_type'], $row['package_title'], $row['package_version'], $row['package_language'],
                $userId
            );

            if ($isInstalled) {
                $tpl->assign([
                    'PACKAGE_HTTP_URL'   => '',
                    'TR_PACKAGE_INSTALL' => ($installedOn == "sw_depot")
                        ? tr('Installed in software repository') : tr('Installed in reseller repository'),
                    'TR_MESSAGE_INSTALL' => ''
                ]);
                $tpl->parse('PACKAGE_INFO_LINK', 'package_info_link');
                $tpl->assign('PACKAGE_INSTALL_LINK', '');
            } else {
                $tpl->assign([
                    'PACKAGE_HTTP_URL'   => $row['package_download_link'],
                    'TR_PACKAGE_INSTALL' => tr('Start installation'),
                    'TR_MESSAGE_INSTALL' => tr('Are you sure you want to install this package from the Web software repository?')
                ]);
                $tpl->parse('PACKAGE_INSTALL_LINK', 'package_install_link');
                $tpl->assign('PACKAGE_INFO_LINK', '');
            }

            $tpl->parse('LIST_WEBDEPOTSOFTWARE', '.list_webdepotsoftware');
        }

        $tpl->assign('NO_WEBDEPOTSOFTWARE_LIST', '');
    } else {
        $tpl->assign([
            'NO_WEBDEPOTSOFTWARE_AVAILABLE' => tr('No software in Web repository found!'),
            'WEB_SOFTWARE_REPOSITORY'       => ''
        ]);
    }

    return $rowCount;
}

/**
 * Update repository index
 *
 * @param string $repositoryIndexFile Repository index file URI
 * @param string $webRepositoryLastUpdate Web repository last update
 */
function update_webdepot_software_list($repositoryIndexFile, $webRepositoryLastUpdate)
{
    $options = ['http' => ['user_agent' => 'PHP libxml agent']];
    $context = stream_context_create($options);
    libxml_set_streams_context($context);

    $webRepositoryIndexFile = new DOMDocument('1.0', 'UTF-8');
    $webRepositoryIndexFile->load($repositoryIndexFile);
    $webRepositoryIndexFile = simplexml_import_dom($webRepositoryIndexFile);

    /** @noinspection PhpUndefinedFieldInspection */
    if (utf8_decode($webRepositoryIndexFile->LAST_UPDATE->DATE) != $webRepositoryLastUpdate) {
        exec_query('TRUNCATE TABLE web_software_depot');

        $badSoftwarePackageDefinition = 0;

        /** @noinspection PhpUndefinedFieldInspection */
        foreach ($webRepositoryIndexFile->PACKAGE as $package) {
            if (!empty($package->INSTALL_TYPE) && !empty($package->TITLE) && !empty($package->VERSION) &&
                !empty($package->LANGUAGE) && !empty($package->TYPE) && !empty($package->DESCRIPTION) &&
                !empty($package->VENDOR_HP) && !empty($package->DOWNLOAD_LINK) && !empty($package->SIGNATURE_LINK)
            ) {
                exec_query(
                    '
                        INSERT INTO
                            web_software_depot (
                                package_install_type, package_title, package_version, package_language, package_type,
                                package_description, package_vendor_hp, package_download_link, package_signature_link
                            ) VALUES (
                                ?, ?, ?, ?, ?, ?, ?, ?, ?
                            )
                    ',
                    [
                        clean_input($package->INSTALL_TYPE), clean_input($package->TITLE), clean_input($package->VERSION),
                        clean_input($package->LANGUAGE), clean_input($package->TYPE), clean_input($package->DESCRIPTION),
                        encode_idna(strtolower(clean_input($package->VENDOR_HP))),
                        encode_idna(strtolower(clean_input($package->DOWNLOAD_LINK))),
                        encode_idna(strtolower(clean_input($package->SIGNATURE_LINK)))
                    ]
                );
            } else {
                $badSoftwarePackageDefinition++;
                break;
            }
        }
        if (!$badSoftwarePackageDefinition) {
            /** @noinspection PhpUndefinedFieldInspection */
            exec_query('UPDATE web_software_options SET webdepot_last_update = ?', [
                $webRepositoryIndexFile->LAST_UPDATE->DATE
            ]);
            set_page_message(tr('Web software repository index been successfully updated.'), 'success');
        } else {
            set_page_message(
                tr('Update of Web software repository index has been aborted. Missing or empty fields.'), 'error'
            );
        }
    } else {
        set_page_message(tr('Web software repository index is already up to date.'), 'info');
    }
}

/**
 * Returns token
 *
 * @return string
 */
function generate_software_upload_token()
{
    return $_SESSION['software_upload_token'] = md5(uniqid(microtime(), true));
}

/***********************************************************************************************************************
 * iMSCP daemon related functions
 */

/**
 * Read an answer from i-MSCP daemon
 *
 * @param resource &$socket
 * @return bool TRUE on success, FALSE otherwise
 */
function daemon_readAnswer(&$socket)
{
    if (($answer = @socket_read($socket, 1024, PHP_NORMAL_READ)) === false) {
        write_log(
            sprintf('Unable to read answer from i-MSCP daemon: %s' . socket_strerror(socket_last_error())), E_USER_ERROR
        );
        return false;
    }

    list($code) = explode(' ', $answer);
    $code = intval($code);

    if ($code != 250) {
        write_log(sprintf('i-MSCP daemon returned an unexpected answer: %s', $answer), E_USER_ERROR);
        return false;
    }


    return true;
}

/**
 * Send a command to i-MSCP daemon
 *
 * @param resource &$socket
 * @param string $command Command
 * @return bool TRUE on success, FALSE otherwise
 */
function daemon_sendCommand(&$socket, $command)
{
    $command .= "\n";
    $commandLength = strlen($command);

    while (true) {
        if (($bytesSent = @socket_write($socket, $command, $commandLength)) == false) {
            write_log(
                sprintf("Couldn't send command to i-MSCP daemon: %s", socket_strerror(socket_last_error())),
                E_USER_ERROR
            );
            return false;
        }

        if ($bytesSent < $commandLength) {
            $command = substr($command, $bytesSent);
            $commandLength -= $bytesSent;
        } else {
            return true;
        }
    }

    return false;
}

/**
 * Send a request to the daemon
 *
 * @return bool TRUE on success, FALSE otherwise
 */
function send_request()
{
    if (
        ($socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false ||
        @socket_connect($socket, '127.0.0.1', 9876) === false
    ) {
        write_log(
            sprintf("Couldn't connect to the i-MSCP daemon: %s", socket_strerror(socket_last_error())), E_USER_ERROR
        );
        return false;
    }

    $version = Registry::get('config')['Version'];

    if (daemon_readAnswer($socket) && // Read Welcome message from i-MSCP daemon
        daemon_sendCommand($socket, "helo $version") && // Send helo command to i-MSCP daemon
        daemon_readAnswer($socket) && // Read answer from i-MSCP daemon
        daemon_sendCommand($socket, 'execute query') && // Send execute query command to i-MSCP daemon
        daemon_readAnswer($socket) && // Read answer from i-MSCP daemon
        daemon_sendCommand($socket, 'bye') && // Send bye command to i-MSCP daemon
        daemon_readAnswer($socket) // Read answer from i-MSCP daemon
    ) {
        $ret = true;
    } else {
        $ret = false;
    }

    socket_close($socket);
    return $ret;
}

/***********************************************************************************************************************
 * Database related functions
 */

/**
 * Convenience function to prepare and execute a SQL statement
 *
 * @see iMSCP_Database::query()
 * @throws DatabaseException
 * @param string $statement SQL statement
 * @param int $mode
 * @param null $arg3
 * @param array $ctorargs
 * @return ResultSet|false
 */
function execute_query($statement, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = NULL, array $ctorargs = array())
{
    try {
        /** @var iMSCP_Database $db */
        $db = Registry::get('iMSCP_Application')->getDatabase();
        $stmt = func_num_args() > 1 ? call_user_func_array([$db, 'query'], func_get_args()) : $db->query($statement);
        return $stmt;
    } catch (PDOException $e) {
        throw new DatabaseException($e->getMessage(), $statement, $e->getCode(), $e);
    }
}

/**
 * Convenience function to prepare and execute a SQL statement with optional parameters
 *
 * @throws DatabaseException When query fail
 * @param string $statement SQL statement
 * @param array $bind Data to bind to the placeholders
 * @return ResultSet|false
 */
function exec_query($statement, $bind = NULL)
{
    try {
        /** @var iMSCP_Database $db */
        $db = Registry::get('iMSCP_Application')->getDatabase();
        /** @var ResultSet $stmt */
        $stmt = $db->prepare($statement);
        $stmt->execute($bind);
        return $stmt;
    } catch (PDOException $e) {
        throw new DatabaseException($e->getMessage(), $statement, $e->getCode(), $e);
    }
}

/**
 * Quote SQL identifier.
 *
 * Note: An Identifier is essentially a name of a database, table, or table column.
 *
 * @param  string $identifier Identifier to quote
 * @return string quoted identifier
 */
function quoteIdentifier($identifier)
{
    return Registry::get('iMSCP_Application')->getDatabase()->quoteIdentifier($identifier);
}

/**
 * Quote value
 *
 * @param mixed $value Value to quote
 * @param int $parameterType Parameter type
 * @return mixed quoted value
 */
function quoteValue($value, $parameterType = PDO::PARAM_STR)
{
    return Registry::get('iMSCP_Application')->getDatabase()->quote($value, $parameterType);
}

/***********************************************************************************************************************
 * Unclassified functions
 */

/**
 * Unset global variables
 *
 * @return void
 */
function unsetMessages()
{
    $glToUnset = [
        'user_updated', 'dmn_tpl', 'chtpl', 'step_one', 'step_two_data', 'ch_hpprops', 'user_add3_added',
        'user_has_domain', 'local_data', 'reseller_added', 'user_added', 'aladd', 'edit_ID', 'aldel', 'hpid',
        'user_deleted', 'hdomain', 'aledit', 'acreated_by', 'dhavesub', 'ddel', 'dhavealias', 'dhavealias', 'dadel',
        'local_data'
    ];

    foreach ($glToUnset as $toUnset) {
        if (array_key_exists($toUnset, $GLOBALS)) {
            unset($GLOBALS[$toUnset]);
        }
    }

    $sessToUnset = [
        'reseller_added', 'dmn_name', 'dmn_tpl', 'chtpl', 'step_one', 'step_two_data', 'ch_hpprops', 'user_add3_added',
        'user_has_domain', 'user_added', 'aladd', 'edit_ID', 'aldel', 'hpid', 'user_deleted', 'hdomain', 'aledit',
        'acreated_by', 'dhavesub', 'ddel', 'dhavealias', 'dadel', 'local_data',
        'dmn_expire', 'dmn_url_forward', 'dmn_type_forward', 'dmn_host_forward'
    ];

    foreach ($sessToUnset as $toUnset) {
        if (array_key_exists($toUnset, $_SESSION)) {
            unset($_SESSION[$toUnset]);
        }
    }
}

if (!function_exists('http_build_url')) {
    define('HTTP_URL_REPLACE', 1); // Replace every part of the first URL when there's one of the second URL
    define('HTTP_URL_JOIN_PATH', 2); // Join relative paths
    define('HTTP_URL_JOIN_QUERY', 4); // Join query strings
    define('HTTP_URL_STRIP_USER', 8); // Strip any user authentication information
    define('HTTP_URL_STRIP_PASS', 16); // Strip any password authentication information
    define('HTTP_URL_STRIP_AUTH', 32); // Strip any authentication information
    define('HTTP_URL_STRIP_PORT', 64); // Strip explicit port numbers
    define('HTTP_URL_STRIP_PATH', 128); // Strip complete path
    define('HTTP_URL_STRIP_QUERY', 256); // Strip query string
    define('HTTP_URL_STRIP_FRAGMENT', 512); // Strip any fragments (#identifier)
    define('HTTP_URL_STRIP_ALL', 1024); // Strip anything but scheme and host

    /**
     * Build an URL.
     *
     * The parts of the second URL will be merged into the first according to the flags argument.
     *
     * @param mixed $url (Part(s) of) an URL in form of a string or associative array like parse_url() returns
     * @param mixed $parts Same as the first argument
     * @param int $flags A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
     * @param bool|array $newUrl If set, it will be filled with the parts of the composed url like parse_url() would return
     * @return string URL
     */
    function http_build_url($url, $parts = [], $flags = HTTP_URL_REPLACE, &$newUrl = false)
    {
        $keys = ['user', 'pass', 'port', 'path', 'query', 'fragment'];

        // HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
        if ($flags & HTTP_URL_STRIP_ALL) {
            $flags |= HTTP_URL_STRIP_USER;
            $flags |= HTTP_URL_STRIP_PASS;
            $flags |= HTTP_URL_STRIP_PORT;
            $flags |= HTTP_URL_STRIP_PATH;
            $flags |= HTTP_URL_STRIP_QUERY;
            $flags |= HTTP_URL_STRIP_FRAGMENT;
        } // HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
        else if ($flags & HTTP_URL_STRIP_AUTH) {
            $flags |= HTTP_URL_STRIP_USER;
            $flags |= HTTP_URL_STRIP_PASS;
        }

        // Parse the original URL
        $parseUrl = parse_url($url);

        // Scheme and Host are always replaced
        if (isset($parts['scheme'])) {
            $parseUrl['scheme'] = $parts['scheme'];
        }

        if (isset($parts['host'])) {
            $parseUrl['host'] = $parts['host'];
        }

        // (If applicable) Replace the original URL with it's new parts
        if ($flags & HTTP_URL_REPLACE) {
            foreach ($keys as $key) {
                if (isset($parts[$key])) {
                    $parseUrl[$key] = $parts[$key];
                }
            }
        } else {
            // Join the original URL path with the new path
            if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH)) {
                if (isset($parseUrl['path'])) {
                    $parseUrl['path'] = rtrim(str_replace(basename($parseUrl['path']), '', $parseUrl['path']), '/') .
                        '/' . ltrim($parts['path'], '/');
                } else {
                    $parseUrl['path'] = $parts['path'];
                }
            }

            // Join the original query string with the new query string
            if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY)) {
                if (isset($parseUrl['query'])) {
                    $parseUrl['query'] .= '&' . $parts['query'];
                } else {
                    $parseUrl['query'] = $parts['query'];
                }
            }
        }

        // Strips all the applicable sections of the URL
        // Note: Scheme and Host are never stripped
        foreach ($keys as $key) {
            if ($flags & (int)constant('HTTP_URL_STRIP_' . strtoupper($key))) {
                unset($parseUrl[$key]);
            }
        }

        $newUrl = $parseUrl;

        return
            (isset($parseUrl['scheme']) ? $parseUrl['scheme'] . '://' : '')
            . (isset($parseUrl['user'])
                ? $parseUrl['user'] . (isset($parseUrl['pass'])
                    ? ':' . $parseUrl['pass'] : '') . '@' : '')
            . (isset($parseUrl['host']) ? $parseUrl['host'] : '')
            . (isset($parseUrl['port']) ? ':' . $parseUrl['port'] : '')
            . (isset($parseUrl['path']) ? $parseUrl['path'] : '')
            . (isset($parseUrl['query']) ? '?' . $parseUrl['query'] : '')
            . (isset($parseUrl['fragment']) ? '#' . $parseUrl['fragment'] : '');
    }
}

/**
 * Turns byte counts to human readable format
 *
 * If you feel like a hard-drive manufacturer, you can start counting bytes by power
 * of 1000 (instead of the generous 1024). Just set power to 1000.
 *
 * But if you are a floppy disk manufacturer and want to start counting in units of
 * 1024 (for your "1.44 MB" disks ?) let the default value for power.
 *
 * The units for power 1000 are: ('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB')
 *
 * Those for power 1024 are: ('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB')
 *
 * with the horrible names: bytes, kibibytes, mebibytes, etc.
 *
 * @see http://physics.nist.gov/cuu/Units/binary.html
 * @throws iMSCPException if power or unit value is unknown
 * @param int|float $bytes Bytes value to convert
 * @param string $unit OPTIONAL Unit to calculate to
 * @param int $decimals OPTIONAL Number of decimal to be show
 * @param int $power OPTIONAL Power to use for conversion (1024 or 1000)
 * @return string
 */
function bytesHuman($bytes, $unit = NULL, $decimals = 2, $power = 1024)
{
    if ($power == 1000) {
        $units = ['B' => 0, 'kB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8];
    } elseif ($power == 1024) {
        $units = [
            'B' => 0, 'kiB' => 1, 'MiB' => 2, 'GiB' => 3, 'TiB' => 4, 'PiB' => 5, 'EiB' => 6, 'ZiB' => 7, 'YiB' => 8
        ];
    } else {
        throw new iMSCPException('Unknown power value');
    }

    $value = 0;

    if ($bytes > 0) {
        if (!array_key_exists($unit, $units)) {
            if (NULL === $unit) {
                $pow = floor(log($bytes) / log($power));
                $unit = array_search($pow, $units);
            } else {
                throw new iMSCPException('Unknown unit value');
            }
        }

        $value = ($bytes / pow($power, floor($units[$unit])));
    } else {
        $unit = 'B';
    }

    // If decimals is not numeric or decimals is less than 0
    // then set default value
    if (!is_numeric($decimals)
        || $decimals < 0
    ) {
        $decimals = 2;
    }

    // units Translation
    switch ($unit) {
        case 'B':
            $unit = tr('B');
            break;
        case 'kB':
            $unit = tr('kB');
            break;
        case 'kiB':
            $unit = tr('kiB');
            break;
        case 'MB':
            $unit = tr('MB');
            break;
        case 'MiB':
            $unit = tr('MiB');
            break;
        case 'GB':
            $unit = tr('GB');
            break;
        case 'GiB':
            $unit = tr('GiB');
            break;
        case 'TB':
            $unit = tr('TB');
            break;
        case 'TiB':
            $unit = tr('TiB');
            break;
        case 'PB':
            $unit = tr('PB');
            break;
        case 'PiB':
            $unit = tr('PiB');
            break;
        case 'EB':
            $unit = tr('EB');
            break;
        case 'EiB':
            $unit = tr('EiB');
            break;
        case 'ZB':
            $unit = tr('ZB');
            break;
        case 'ZiB':
            $unit = tr('ZiB');
            break;
        case 'YB':
            $unit = tr('YB');
            break;
        case 'YiB':
            $unit = tr('YiB');
            break;
    }

    return sprintf('%.' . $decimals . 'f ' . $unit, $value);
}

/**
 * Turns mebibyte counts to human readable format
 *
 * @see bytesHuman()
 * @param int|float $mebibyte Mebibyte value to convert
 * @param string $unit OPTIONAL Unit to calculate to
 * @param int $decimals OPTIONAL Number of decimal to be show
 * @param int $power OPTIONAL Power to use for conversion (1024 or 1000)
 * @return string
 */
function mebibytesHuman($mebibyte, $unit = NULL, $decimals = 2, $power = 1024)
{
    return bytesHuman($mebibyte * 1048576, $unit, $decimals, $power);
}

/**
 * Translates '-1', 'no', 'yes', '0' or mebibyte value string into human
 * readable string
 *
 * @param int $value variable to be translated
 * @param bool $autosize calculate value in different unit (default false)
 * @param string $to OPTIONAL Unit to calclulate to ('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB')
 * @return String
 */
function translate_limit_value($value, $autosize = false, $to = NULL)
{
    $trEnabled = '<span style="color:green">' . tr('Enabled') . '</span>';
    $trDisabled = '<span style="color:red">' . tr('Disabled') . '</span>';

    switch ($value) {
        case '-1':
            return '-';
        case  '0':
            return '∞';
        case '_yes_':
        case 'yes':
            return $trEnabled;
        case '_no_':
        case 'no':
            return $trDisabled;
        case 'full':
            return '<span style="color:green">' . tr('Domain and SQL databases') . '</span>';
        case 'dmn':
            return '<span style="color:green">' . tr('Web files only') . '</span>';
        case 'sql':
            return '<span style="color:green">' . tr('SQL databases only') . '</span>';
        default:
            return !$autosize ? $value : mebibytesHuman($value, $to);
    }
}

/**
 * Return UNIX timestamp representing first day of month for the given month and year
 *
 * @param int $month OPTIONAL a month (date('n')
 * @param int $year OPTIONAL A year (date('Y'))
 * @return int
 */
function getFirstDayOfMonth($month = NULL, $year = NULL)
{
    $date = new Zend_Date(NULL, NULL, Registry::get('Zend_Locale'));
    $date->setYear($year ?: date('Y'));
    $date->setMonth($month ?: date('n'));
    $date->setDay(1);
    $date->setHour(0);
    $date->setMinute(0);
    $date->setSecond(0);

    return $date->getTimestamp();
}

/**
 * Return UNIX timestamp representing last day of month for the given month and year
 *
 * @param int $month OPTIONAL a month (date('n')
 * @param int $year OPTIONAL A year (date('Y'))
 * @return int
 */
function getLastDayOfMonth($month = NULL, $year = NULL)
{
    $date = new Zend_Date(NULL, NULL, Registry::get('Zend_Locale'));
    $date->setYear($year ?: date('Y'));
    $date->setMonth($month ?: date('n'));
    $date->setDay($date->get(Zend_Date::MONTH_DAYS));
    $date->setHour(23);
    $date->setMinute(59);
    $date->setSecond(59);

    return $date->getTimestamp();
}

/**
 * Get list of available webmail
 *
 * @return array
 */
function getWebmailList()
{
    $config = Registry::get('config');

    if (isset($config['WEBMAIL_PACKAGES'])
        && strtolower($config['WEBMAIL_PACKAGES']) != 'no'
    ) {
        return explode(',', $config['WEBMAIL_PACKAGES']);
    }

    return [];
}

/**
 * Returns the user Ip address
 *
 * @return string User's Ip address
 */
function getIpAddr()
{
    $ipAddr = !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : false;

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddrs = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);

        if ($ipAddr) {
            array_unshift($ipAddrs, $ipAddr);
            $ipAddr = false;
        }

        $countIpAddrs = count($ipAddrs);

        // Loop over ip stack as long an ip out of private range is not found
        for ($i = 0; $i < $countIpAddrs; $i++) {
            if (filter_var($ipAddrs[$i], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
                $ipAddr = $ipAddrs[$i];
                break;
            }
        }
    }

    return $ipAddr ? $ipAddr : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : tr('Unknown'));
}
