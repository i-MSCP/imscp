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

/**
 * Check that reseller limits are not smaller than those defined by the given hosting plan
 *
 * @throws iMSCP_Exception
 * @param int $resellerId Reseller unique identifier
 * @param int|string $hp Hosting plan unique identifier or string representing hosting plan properties to check against
 * @return bool
 */
function reseller_limits_check($resellerId, $hp)
{
    if (is_number($hp)) {
        if (isset($_SESSION['ch_hpprops'])) {
            $hostingPlanProperties = $_SESSION['ch_hpprops'];
        } else {
            $stmt = exec_query('SELECT props FROM hosting_plans WHERE id = ?', $hp);

            if ($stmt->rowCount()) {
                $data = $stmt->fetchRow();
                $hostingPlanProperties = $data['props'];
            } else {
                throw new iMSCP_Exception('Hosting plan not found');
            }
        }
    } else {
        $hostingPlanProperties = $hp;
    }

    list(
        , , $newSubLimit, $newAlsLimit, $newMailLimit, $newFtpLimit, $newSqlDbLimit, $newSqlUserLimit, $newTrafficLimit,
        $newDiskspaceLimit
        ) = explode(';', $hostingPlanProperties);

    $stmt = exec_query('SELECT * FROM reseller_props WHERE reseller_id = ?', $resellerId);
    $data = $stmt->fetchRow();
    $currentDmnLimit = $data['current_dmn_cnt'];
    $maxDmnLimit = $data['max_dmn_cnt'];
    $currentSubLimit = $data['current_sub_cnt'];
    $maxSubLimit = $data['max_sub_cnt'];
    $currentAlsLimit = $data['current_als_cnt'];
    $maxAlsLimit = $data['max_als_cnt'];
    $currentMailLimit = $data['current_mail_cnt'];
    $maxMailLimit = $data['max_mail_cnt'];
    $currentFtpLimit = $data['current_ftp_cnt'];
    $ftpMaxLimit = $data['max_ftp_cnt'];
    $currentSqlDbLimit = $data['current_sql_db_cnt'];
    $maxSqlDbLimit = $data['max_sql_db_cnt'];
    $currentSqlUserLimit = $data['current_sql_user_cnt'];
    $maxSqlUserLimit = $data['max_sql_user_cnt'];
    $currentTrafficLimit = $data['current_traff_amnt'];
    $maxTrafficLimit = $data['max_traff_amnt'];
    $currentDiskspaceLimit = $data['current_disk_amnt'];
    $maxDiskspaceLimit = $data['max_disk_amnt'];

    if ($maxDmnLimit != 0 && $currentDmnLimit + 1 > $maxDmnLimit) {
        set_page_message(tr('You have reached your domains limit. You cannot add more domains.'), 'error');
    }

    if ($maxSubLimit != 0 && $newSubLimit != -1) {
        if ($newSubLimit == 0) {
            set_page_message(tr('You have a subdomains limit. You cannot add a user with unlimited subdomains.'), 'error');
        } else if ($currentSubLimit + $newSubLimit > $maxSubLimit) {
            set_page_message(tr('You are exceeding your subdomains limit.'), 'error');
        }
    }

    if ($maxAlsLimit != 0 && $newAlsLimit != -1) {
        if ($newAlsLimit == 0) {
            set_page_message(tr('You have a domain aliases limit. You cannot add a user with unlimited domain aliases.'), 'error');
        } else if ($currentAlsLimit + $newAlsLimit > $maxAlsLimit) {
            set_page_message(tr('You are exceeding you domain aliases limit.'), 'error');
        }
    }

    if ($maxMailLimit != 0) {
        if ($newMailLimit == 0) {
            set_page_message(tr('You have a mail accounts limit. You cannot add a user with unlimited mail accounts.'), 'error');
        } else if ($currentMailLimit + $newMailLimit > $maxMailLimit) {
            set_page_message(tr('You are exceeding your mail accounts limit.'), 'error');
        }
    }

    if ($ftpMaxLimit != 0) {
        if ($newFtpLimit == 0) {
            set_page_message(tr('You have a FTP accounts limit. You cannot add a user with unlimited FTP accounts.'), 'error');
        } else if ($currentFtpLimit + $newFtpLimit > $ftpMaxLimit) {
            set_page_message(tr('You are exceeding your FTP accounts limit.'), 'error');
        }
    }

    if ($maxSqlDbLimit != 0 && $newSqlDbLimit != -1) {
        if ($newSqlDbLimit == 0) {
            set_page_message(tr('You have a SQL databases limit. You cannot add a user with unlimited SQL databases.'), 'error');
        } else if ($currentSqlDbLimit + $newSqlDbLimit > $maxSqlDbLimit) {
            set_page_message(tr('You are exceeding your SQL databases limit.'), 'error');
        }
    }

    if ($maxSqlUserLimit != 0 && $newSqlUserLimit != -1) {
        if ($newSqlUserLimit == 0) {
            set_page_message(tr('You have a SQL users limit. You cannot add a user with unlimited SQL users.'), 'error');
        } else if ($newSqlDbLimit == -1) {
            set_page_message(tr('You have disabled SQL databases for this user. You cannot have SQL users here.'), 'error');
        } else if ($currentSqlUserLimit + $newSqlUserLimit > $maxSqlUserLimit) {
            set_page_message(tr('You are exceeding your SQL users limit.'), 'error');
        }
    }

    if ($maxTrafficLimit != 0) {
        if ($newTrafficLimit == 0) {
            set_page_message(tr('You have a monthly traffic limit. You cannot add a user with unlimited monthly traffic.'), 'error');
        } else if ($currentTrafficLimit + $newTrafficLimit > $maxTrafficLimit) {
            set_page_message(tr('You are exceeding your monthly traffic limit.'), 'error');
        }
    }

    if ($maxDiskspaceLimit != 0) {
        if ($newDiskspaceLimit == 0) {
            set_page_message(tr('You have a disk space limit. You cannot add a user with unlimited disk space.'), 'error');
        } else if ($currentDiskspaceLimit + $newDiskspaceLimit > $maxDiskspaceLimit) {
            set_page_message(tr('You are exceeding your disk space limit.'), 'error');
        }
    }

    if (Zend_Session::namespaceIsset('pageMessages')) {
        return false;
    }

    return true;
}

/**
 * Tells whether or not the given feature is available for the reseller.
 *
 * @throws iMSCP_Exception When $featureName is not known
 * @param string $featureName Feature name
 * @param bool $forceReload If true force data to be reloaded
 * @return bool TRUE if $featureName is available for reseller, FALSE otherwise
 * TODO add hosting_plan feature
 */
function resellerHasFeature($featureName, $forceReload = false)
{
    static $availableFeatures = NULL;
    $featureName = strtolower($featureName);

    if (NULL == $availableFeatures || $forceReload) {
        $cfg = iMSCP_Registry::get('config');
        $resellerProps = imscp_getResellerProperties($_SESSION['user_id'], true);
        $availableFeatures = [
            'domains'            => ($resellerProps['max_dmn_cnt'] != '-1'),
            'subdomains'         => ($resellerProps['max_sub_cnt'] != '-1'),
            'domain_aliases'     => ($resellerProps['max_als_cnt'] != '-1'),
            'mail'               => ($resellerProps['max_mail_cnt'] != '-1'),
            'ftp'                => ($resellerProps['max_ftp_cnt'] != '-1'),
            'sql'                => ($resellerProps['max_sql_db_cnt'] != '-1'), // TODO to be removed
            'sql_db'             => ($resellerProps['max_sql_db_cnt'] != '-1'),
            'sql_user'           => ($resellerProps['max_sql_user_cnt'] != '-1'),
            'php'                => true,
            'php_editor'         => ($resellerProps['php_ini_system'] == 'yes'),
            'cgi'                => true,
            'custom_dns_records' => ($cfg['NAMED_PACKAGE'] != 'Servers::noserver'),
            'aps'                => ($resellerProps['software_allowed'] != 'no'), // aps feature check must be revisted
            'external_mail'      => true,
            'backup'             => ($cfg['BACKUP_DOMAINS'] != 'no'),
            'support'            => ($cfg['IMSCP_SUPPORT_SYSTEM'] && $resellerProps['support_system'] == 'yes')
        ];
    }

    if (!array_key_exists($featureName, $availableFeatures)) {
        throw new iMSCP_Exception(
            sprintf("Feature %s is not known by the resellerHasFeature() function.", $featureName)
        );
    }

    return $availableFeatures[$featureName];
}

/**
 * Whether or not the logged-in reseller has a least the given number of registered customers.
 *
 * @param int $minNbCustomers Minimum number of customers
 * @return bool TRUE if the logged-in reseller has a least the given number of registered customer, FALSE otherwise
 */
function resellerHasCustomers($minNbCustomers = 1)
{
    static $customerCount = NULL;

    if (NULL === $customerCount) {
        $customerCount = exec_query(
            "
                SELECT COUNT(admin_id)
                FROM admin
                WHERE admin_type = 'user'
                AND created_by = ?
                AND admin_status <> 'todelete'",
            $_SESSION['user_id']
        )->fetchRow(PDO::FETCH_COLUMN);
    }

    return ($customerCount >= $minNbCustomers);
}

/**
 * Check user data
 *
 * @param  bool $noPass If true skip password check
 * @return bool True if user data are valid, false otherwise
 */
function check_ruser_data($noPass = false)
{
    global $password, $passwordRepeat, $email, $customerId, $firstName, $lastName, $gender, $firm, $street1, $street2,
           $zip, $city, $state, $country, $phone, $fax;

    // Get data for fields from previous page
    if (isset($_POST['userpassword'])) {
        $password = clean_input($_POST['userpassword']);
    } else {
        $password = '';
    }

    if (isset($_POST['userpassword_repeat'])) {
        $passwordRepeat = clean_input($_POST['userpassword_repeat']);
    } else {
        $passwordRepeat = '';
    }

    if (isset($_POST['useremail'])) {
        $email = clean_input($_POST['useremail']);
    } else {
        $email = '';
    }

    if (isset($_POST['useruid'])) {
        $customerId = clean_input($_POST['useruid']);
    } else {
        $customerId = '';
    }

    if (isset($_POST['userfname'])) {
        $firstName = clean_input($_POST['userfname']);
    } else {
        $firstName = '';
    }

    if (isset($_POST['userlname'])) {
        $lastName = clean_input($_POST['userlname']);
    } else {
        $lastName = '';
    }

    if (isset($_POST['gender']) && get_gender_by_code($_POST['gender'], true) !== NULL) {
        $gender = $_POST['gender'];
    } else {
        $gender = 'U';
    }

    if (isset($_POST['userfirm'])) {
        $firm = clean_input($_POST['userfirm']);
    } else {
        $firm = '';
    }

    if (isset($_POST['userstreet1'])) {
        $street1 = clean_input($_POST['userstreet1']);
    } else {
        $street1 = '';
    }

    if (isset($_POST['userstreet2'])) {
        $street2 = clean_input($_POST['userstreet2']);
    } else {
        $street2 = '';
    }

    if (isset($_POST['userzip'])) {
        $zip = clean_input($_POST['userzip']);
    } else {
        $zip = '';
    }

    if (isset($_POST['usercity'])) {
        $city = clean_input($_POST['usercity']);
    } else {
        $city = '';
    }

    if (isset($_POST['userstate'])) {
        $state = clean_input($_POST['userstate']);
    } else {
        $state = '';
    }

    if (isset($_POST['usercountry'])) {
        $country = clean_input($_POST['usercountry']);
    } else {
        $country = '';
    }

    if (isset($_POST['userphone'])) {
        $phone = clean_input($_POST['userphone']);
    } else {
        $phone = '';
    }

    if (isset($_POST['userfax'])) {
        $fax = clean_input($_POST['userfax']);
    } else {
        $fax = '';
    }

    if (!$noPass) {
        if ('' === $passwordRepeat || '' === $password) {
            set_page_message(tr('Please fill up both data fields for password.'), 'error');
        } elseif ($passwordRepeat !== $password) {
            set_page_message(tr("Passwords do not match."), 'error');
        } else {
            checkPasswordSyntax($password);
        }
    }

    if (!chk_email($email)) {
        set_page_message(tr('Incorrect email length or syntax.'), 'error');
    }

    if ($customerId != '' && strlen($customerId) > 200) {
        set_page_message(tr('Customer ID cannot have more than 200 characters'), 'error');
    }

    if ($firstName != '' && strlen($firstName) > 200) {
        set_page_message(tr('First name cannot have more than 200 characters.'), 'error');
    }

    if ($lastName != '' && strlen($lastName) > 200) {
        set_page_message(tr('Last name cannot have more than 200 characters.'), 'error');
    }

    if ($zip != '' && (strlen($zip) > 200 || is_number(!$zip))) {
        set_page_message(tr('Incorrect post code length or syntax!'), 'error');
    }

    if (Zend_Session::namespaceIsset('pageMessages')) {
        return false;
    }

    return true;
}
