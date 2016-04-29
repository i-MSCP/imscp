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

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get domain name
 *
 * @param int $domainId Domain entity unique identifier
 * @param string $domainType Domain entity type to update (dmn|als|sub|alssub)
 * @return string|false Domain name or FALSE if the domain name is not found or not owned by logged-in customer
 */
function _client_getDomainName($domainId, $domainType)
{
    static $domainName = null;

    if ($domainName === null) {
        switch ($domainType) {
            case 'dmn':
                $query = 'SELECT domain_name FROM domain WHERE domain_id = ? AND domain_admin_id = ?';
                break;
            case 'als':
                $query = '
                    SELECT alias_name AS domain_name FROM domain_aliasses INNER JOIN domain USING(domain_id)
                    WHERE alias_id = ?
                    AND domain_admin_id = ?
            ';
                break;
            case 'sub':
                $query = "
                    SELECT CONCAT(subdomain_name, '.', domain_name) AS domain_name FROM subdomain
                    INNER JOIN domain USING(domain_id) WHERE subdomain_id = ? AND domain_admin_id = ?
                ";
                break;
            default:
                $query = "
                    SELECT CONCAT(subdomain_alias_name, '.', alias_name) AS domain_name FROM subdomain_alias
                    INNER JOIN domain_aliasses USING(alias_id) INNER JOIN domain USING(domain_id)
                    WHERE subdomain_alias_id = ? AND domain_admin_id = ?
                ";
        }

        $stmt = exec_query($query, array($domainId, $_SESSION['user_id']));

        if (!$stmt->rowCount()) {
            return false;
        }

        $row = $stmt->fetchRow();
        $domainName = $row['domain_name'];
    }

    return $domainName;
}

/**
 * Update status for the given domain
 *
 * @throws iMSCP_Exception_Database
 * @param string $domainType Domain entity type to update (dmn|als|sub|alssub)
 * @param int $domainId Domain entity unique identifier
 * @return void
 */
function _client_updateDomainStatus($domainType, $domainId)
{
    switch ($domainType) {
        case 'dmn':
            $query = 'UPDATE domain SET domain_status = ? WHERE domain_id = ?';
            break;
        case 'als':
            $query = 'UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?';
            break;
        case 'sub':
            $query = 'UPDATE subdomain SET subdomain_status = ? WHERE subdomain_id = ?';
            break;
        default:
            $query = 'UPDATE subdomain_alias SET subdomain_alias_status = ? WHERE subdomain_alias_id = ?';
    }

    exec_query($query, array('tochange', $domainId));
}

/**
 * Generate temporary openssl coonfiguration file
 *
 * @throws iMSCP_Exception_Database
 * @param array $data User data
 * @return bool|string Path to generate openssl temporary file, FALSE on failure
 */
function _client_generateOpenSSLConfFile($data)
{
    global $domainType, $domainId;

    $config = iMSCP_Registry::get('config');

    $altNames = <<<'EOF'
DNS.1 = {DOMAIN_NAME}
DNS.2 = www.{DOMAIN_NAME}
EOF;

    if($domainType == 'dmn') {
        $altNames .= "\nDNS.3 = {ADMIN_SYS_NAME}.{BASE_SERVER_VHOST}\n";
    } elseif($domainType == 'als') {
        $altNames .= "\nDNS.3 = {ADMIN_SYS_NAME}als$domainId.{BASE_SERVER_VHOST}\n";
    } elseif($domainType == 'sub') {
        $altNames .= "\nDNS.3 = {ADMIN_SYS_NAME}sub$domainId.{BASE_SERVER_VHOST}\n";
    } else {
        $altNames .= "\nDNS.3 = {ADMIN_SYS_NAME}alssub$domainId.{BASE_SERVER_VHOST}\n";
    }

    $sslTpl = new iMSCP_pTemplate();
    $sslTpl->setRootDir($config['CONF_DIR'] . '/openssl');
    $sslTpl->define('tpl', 'openssl.cnf.tpl');
    $sslTpl->assign(array(
        'COMMON_NAME' => $data['domain_name'],
        'EMAIL_ADDRESS' => $data['email'],
        'DOMAIN_NAME' => $data['domain_name'],
        'ALT_NAMES' => $altNames,
        'ADMIN_SYS_NAME' => $data['admin_sys_name'],
        'BASE_SERVER_VHOST' => $config['BASE_SERVER_VHOST']
    ));
    $sslTpl->parse('TPL', 'tpl');

    if (!($opensslConfFile = @tempnam(sys_get_temp_dir(), $_SESSION['user_id'] . '-openssl.cnf'))) {
        write_log('Could not create temporary openssl configuration file.', E_USER_ERROR);
        return false;
    }

    register_shutdown_function(function ($file) {
        @unlink($file);
    }, $opensslConfFile);

    if (!@file_put_contents($opensslConfFile, $sslTpl->getLastParseResult())) {
        write_log(sprintf('Could not write in %s openssl temporary configuration file.', $opensslConfFile), E_USER_ERROR);
        return false;
    }

    return $opensslConfFile;
}

/**
 * Generate an self-signed certificate
 *
 * @param string $domainName Domain name
 * @return bool TRUE on success, FALSE otherwise
 */
function client_generateSelfSignedCert($domainName)
{
    $stmt = exec_query(
        'SELECT admin_sys_name, firm, city, state, country, email FROM admin WHERE admin_id = ?', $_SESSION['user_id']
    );

    if (!$stmt->rowCount()) {
        return false;
    }

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
    $row['domain_name'] = $domainName;

    if (!($sslConfigFilePath = _client_generateOpenSSLConfFile($row))) {
        return false;
    }

    $distinguishedName = array(
        'countryName' => 'US', //  TODO map of country names to ISO-3166 codes
        'stateOrProvinceName' => !empty($row['state']) ? $row['state'] : 'N/A',
        'localityName' => !empty($row['city']) ? $row['city'] : 'N/A',
        'organizationName' => !empty($row['firm']) ? $row['firm'] : 'N/A',
        'commonName' => $domainName,
        'emailAddress' => $row['email']
    );

    $sslConfig = array('config' => $sslConfigFilePath);
    $csr = @openssl_csr_new($distinguishedName, $pkey, $sslConfig);
    if (!is_resource($csr)) {
        write_log(sprintf('Could not generate SSL certificate signing request: %s', openssl_error_string()), E_USER_ERROR);
        return false;
    }

    if (@openssl_pkey_export($pkey, $pkeyStr, null, $sslConfig) !== true) {
        write_log(sprintf('Could not export private key: %s', openssl_error_string()), E_USER_ERROR);
        return false;
    }

    $cert = @openssl_csr_sign($csr, null, $pkeyStr, 365, $sslConfig, $_SESSION['user_id'] . time());
    if (!is_resource($cert)) {
        write_log(sprintf('Could not generate SSL certificate: %s', openssl_error_string()));
        return false;
    }

    if (@openssl_x509_export($cert, $certStr) !== true) {
        write_log(sprintf('Could not export SSL certificate: %s', openssl_error_string()), E_USER_ERROR);
        return false;
    }

    openssl_pkey_free($pkey);
    openssl_x509_free($cert);

    $_POST['passphrase'] = '';
    $_POST['private_key'] = $pkeyStr;
    $_POST['certificate'] = $certStr;
    $_POST['ca_bundle'] = '';

    return true;
}

/**
 * Add or update an SSL certificate
 *
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @param int $domainId domain unique identifier
 * @param string $domainType Domain type (dmn|als|sub|alssub)
 * @return void
 */
function client_addSslCert($domainId, $domainType)
{
    $config = iMSCP_Registry::get('config');
    $domainName = _client_getDomainName($domainId, $domainType);
    $allowHSTS = isset($_POST['allow_hsts']) ? 'on' : 'off';
    $hstsMaxAge = (
        $allowHSTS == 'on' && isset($_POST['hsts_max_age']) && $_POST['hsts_max_age'] != '' &&
        $_POST['hsts_max_age'] >= 0
    ) ? intval($_POST['hsts_max_age']) : 31536000;
    $hstsIncludeSubDomains = ($allowHSTS == 'on' && isset($_POST['hsts_include_subdomains'])) ? 'on' : 'off';
    $selfSigned = isset($_POST['selfsigned']);

    if ($domainName === false) {
        showBadRequestErrorPage();
    }

    if ($selfSigned && !client_generateSelfSignedCert($domainName)) {
        set_page_message(tr('Could not generate SSL certificate. An unexpected error occurred.'), 'error');
        return;
    }

    if (!isset($_POST['passphrase']) || !isset($_POST['private_key']) || !isset($_POST['certificate']) ||
        !isset($_POST['ca_bundle']) || !isset($_POST['cert_id'])
    ) {
        showBadRequestErrorPage();
    }

    $passPhrase = clean_input($_POST['passphrase']);
    $privateKey = clean_input($_POST['private_key']);
    $certificate = clean_input($_POST['certificate']);
    $caBundle = clean_input($_POST['ca_bundle']);
    $certId = intval($_POST['cert_id']);

    if (!$selfSigned) { // Validate SSL certificate (private key, SSL certificate and certificate chain)
        $privateKey = @openssl_pkey_get_private($privateKey, $passPhrase);

        if (!is_resource($privateKey)) {
            set_page_message(tr('Invalid private key or passphrase.'), 'error');
            return;
        }

        $certificateStr = $certificate;
        $certificate = @openssl_x509_read($certificate);

        if (!is_resource($certificate)) {
            set_page_message(tr('Invalid SSL certificate.'), 'error');
            return;
        }

        if (@openssl_x509_check_private_key($certificate, $privateKey) !== true) {
            set_page_message(tr("The private key doesn't belong to the provided SSL certificate."), 'error');
            return;
        }

        if (($tmpfname = @tempnam(sys_get_temp_dir(), (intval($_SESSION['user_id']) . 'ssl-ca'))) === false) {
            write_log('Could not create temporary file for CA bundle.', E_USER_ERROR);
            set_page_message(tr('Could not add/update SSL certificate. An unexpected error occurred.'), 'error');
            return;
        }

        register_shutdown_function(function ($file) {
            @unlink($file);
        }, $tmpfname);

        if ($caBundle !== '') {
            if (@file_put_contents($tmpfname, $caBundle) === false) {
                write_log('Could not write CA bundle in temporary file.', E_USER_ERROR);
                set_page_message(tr('Could not add/update SSL certificate. An unexpected error occurred.'), 'error');
                return;
            }

            if (@openssl_x509_checkpurpose($certificate, X509_PURPOSE_SSL_SERVER, array($config['DISTRO_CA_BUNDLE']), $tmpfname) !== true) {
                set_page_message(tr('At least one intermediate certificate is invalid or missing.'), 'error');
                return;
            }
        } else {
            if (@file_put_contents($tmpfname, $certificateStr) === false) {
                write_log('Could not write SSL certificate in temporary file.', E_USER_ERROR);
                set_page_message(tr('Could not add/update SSL certificate. An unexpected error occurred.'), 'error');
                return;
            }

            // Note: Here we also add the certificate in the trusted chain to support self-signed certificates
            if(@openssl_x509_checkpurpose($certificate, X509_PURPOSE_SSL_SERVER, array($config['DISTRO_CA_BUNDLE'], $tmpfname)) !== true) {
                set_page_message(tr('At least one intermediate certificate is invalid or missing.'), 'error');
                return;
            }
        }
    }

    // Preparing data for insertion in database
    if (!$selfSigned) {
        if (@openssl_pkey_export($privateKey, $privateKeyStr) === false) {
            write_log('Could not export private key.', E_USER_ERROR);
            set_page_message(tr('Could not add/update SSL certificate. An unexpected error occurred.'), 'error');
            return;
        }

        @openssl_pkey_free($privateKey);
        if (@openssl_x509_export($certificate, $certificateStr) === false) {
            write_log('Could not export SSL certificate.', E_USER_ERROR);
            set_page_message(tr('Could not add/update SSL certificate. An unexpected error occurred.'), 'error');
            return;
        }

        @openssl_x509_free($certificate);
        $caBundleStr = str_replace("\r\n", "\n", $caBundle);
    } else {
        $privateKeyStr = $privateKey;
        $certificateStr = $certificate;
        $caBundleStr = $caBundle;
    }

    $db = iMSCP_Database::getInstance();

    try {
        $db->beginTransaction();

        if ($certId == 0) { // Add new certificate
            exec_query(
                '
                    INSERT INTO ssl_certs (
                        domain_id, domain_type, private_key, certificate, ca_bundle, allow_hsts,
                        hsts_max_age, hsts_include_subdomains, status
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ',
                array(
                    $domainId, $domainType, $privateKeyStr, $certificateStr, $caBundleStr, $allowHSTS,
                    $hstsMaxAge, $hstsIncludeSubDomains, 'toadd'
                )
            );
        } else { // Update existing certificate
            exec_query(
                '
                    UPDATE ssl_certs SET private_key = ?, certificate = ?, ca_bundle = ?, allow_hsts = ?,
                        hsts_max_age = ?, hsts_include_subdomains = ?, status = ?
                    WHERE cert_id = ? AND domain_id = ? AND domain_type = ?
                ',
                array(
                    $privateKeyStr, $certificateStr, $caBundleStr, $allowHSTS, $hstsMaxAge,
                    $hstsIncludeSubDomains, 'tochange', $certId, $domainId, $domainType
                )
            );
        }

        _client_updateDomainStatus($domainType, $domainId);

        $db->commit();

        send_request();

        if (!$certId) {
            set_page_message(tr('SSL certificate successfully scheduled for addition.'), 'success');
            write_log(sprintf('%s added a new SSL certificate for the %s domain', decode_idna($_SESSION['user_logged']), decode_idna($domainName)), E_USER_NOTICE);
        } else {
            set_page_message(tr('SSL certificate successfully scheduled for update.'), 'success');
            write_log(sprintf('%s updated an SSL certificate for the %s domain', decode_idna($_SESSION['user_logged']), $domainName), E_USER_NOTICE);
        }

        redirectTo("cert_view.php?domain_id=$domainId&domain_type=$domainType");
    } catch (iMSCP_Exception $e) {
        $db->rollBack();
        write_log('Unable to add/update SSL certificate in database', E_USER_ERROR);
        set_page_message('An unexpected error occurred. Please contact your reseller.');
    }
}

/**
 * Delete an SSL certificate
 *
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @param int $domainId domain unique identifier
 * @param string $domainType Domain type (dmn, als, sub, alssub)
 * @return void
 */
function client_deleteSslCert($domainId, $domainType)
{
    $domainName = _client_getDomainName($domainId, $domainType);

    if ($domainName === false) {
        showBadRequestErrorPage();
    }

    if (!isset($_POST['cert_id'])) {
        showBadRequestErrorPage();
    }

    $certId = intval($_POST['cert_id']);
    $db = iMSCP_Database::getInstance();

    try {
        $db->beginTransaction();

        exec_query('UPDATE ssl_certs SET status = ? WHERE cert_id = ? AND domain_id = ? AND domain_type = ?', array(
            'todelete', $certId, $domainId, $domainType
        ));

        _client_updateDomainStatus($domainType, $domainId);

        $db->commit();

        send_request();
        set_page_message(tr('SSL certificate successfully scheduled for deletion.'), 'success');
        write_log(sprintf('%s deleted SSL certificate for the %s domain.', decode_idna($_SESSION['user_logged']), decode_idna($domainName)), E_USER_NOTICE);
        redirectTo('domains_manage.php');
    } catch (iMSCP_Exception $e) {
        $db->rollBack();
        write_log(sprintf('Could not export SSL certificate: %s', $e->getMessage()), E_USER_ERROR);
        set_page_message(tr('Could not delete SSL certificate. An unexpected error occurred.'), 'error');
    }
}

/**
 * Generate page
 *
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @param int $domainId Domain entity unique identifier
 * @param string $domainType Domain entity type
 * @return void
 */
function client_generatePage($tpl, $domainId, $domainType)
{
    $domainName = _client_getDomainName($domainId, $domainType);

    if ($domainName === false) {
        showBadRequestErrorPage();
    }

    $stmt = exec_query('SELECT * FROM ssl_certs WHERE domain_id = ? AND domain_type = ?', array($domainId, $domainType));

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
        $dynTitle = customerHasFeature('ssl') && $row['status'] == 'ok' ? tr('Edit SSL certificate') : tr('Show SSL certificate');
        $certId = $row['cert_id'];
        $privateKey = tohtml($row['private_key']);
        $certificate = tohtml($row['certificate']);
        $caBundle = tohtml($row['ca_bundle']);
        $allowHSTS = ($row['allow_hsts'] == 'on') ? true : false;
        $hstsMaxAge = $row['hsts_max_age'];
        $hstsIncludeSubDomains = ($row['hsts_include_subdomains'] == 'on') ? true : false;
        $trAction = tr('Update');
        $status = $row['status'];
        $tpl->assign('STATUS', in_array($status, array('toadd', 'tochange', 'todelete', 'ok'))
            ? translate_dmn_status($status)
            : '<span style="color: red;font-weight: bold">' . tr('Invalid SSL certificate') . "</span>");
    } else {
        if (customerHasFeature('ssl')) {
            $dynTitle = tr('Add SSL certificate');
            $trAction = tr('Add');
            $certId = '0';
            $privateKey = '';
            $certificate = '';
            $caBundle = '';
            $allowHSTS = false;
            $hstsMaxAge = '31536000';
            $hstsIncludeSubDomains = false;
            $tpl->assign('SSL_CERTIFICATE_STATUS', '');
        } else {
            set_page_message('SSL feature is currently disabled.', 'static_warning');
            redirectTo('domains_manage.php');
            return;
        }
    }

    if (customerHasFeature('ssl') && isset($_POST['cert_id']) && isset($_POST['private_key']) &&
        isset($_POST['certificate']) && isset($_POST['ca_bundle'])
    ) {
        $certId = $_POST['cert_id'];
        $privateKey = $_POST['private_key'];
        $certificate = $_POST['certificate'];
        $caBundle = $_POST['ca_bundle'];
        $allowHSTS = isset($_POST['allow_hsts']);
        $hstsMaxAge = $allowHSTS && isset($_POST['hsts_max_age']) && $_POST['hsts_max_age'] != '' && $_POST['hsts_max_age'] >= 0 ? intval($_POST['hsts_max_age']) : 31536000;
        $hstsIncludeSubDomains = $allowHSTS && isset($_POST['hsts_include_subdomains']);
    }

    /** @var iMSCP_Config_Handler_File $cfg */
    $cfg = iMSCP_Registry::get('config');

    $checked = $cfg->HTML_CHECKED;

    $tpl->assign(array(
        'TR_DYNAMIC_TITLE' => $dynTitle,
        'DOMAIN_NAME' => tohtml(decode_idna($domainName)),
        'HSTS_CHECKED' => $allowHSTS ? $checked : '',
        'HSTS_MAX_AGE' => tohtml(trim($hstsMaxAge)),
        'HSTS_INCLUDE_SUBDOMAINS_CHECKED' => $hstsIncludeSubDomains ? $checked : '',
        'KEY_CERT' => tohtml(trim($privateKey)),
        'CERTIFICATE' => tohtml(trim($certificate)),
        'CA_BUNDLE' => tohtml(trim($caBundle)),
        'CERT_ID' => tohtml(trim($certId)),
        'TR_ACTION' => $trAction
    ));

    if (!customerHasFeature('ssl') || isset($status) && in_array($status, array('toadd', 'tochange', 'todelete'))) {
        $tpl->assign('SSL_CERTIFICATE_ACTIONS', '');
        if (!customerHasFeature('ssl')) {
            set_page_message(tr('SSL feature is not available. You can only view your certificate.'), 'static_warning');
        }
    }
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'client/cert_view.tpl',
    'page_message' => 'layout',
    'ssl_certificate_status' => 'page',
    'ssl_certificate_actions' => 'page'
));

if (!isset($_GET['domain_id']) || !isset($_GET['domain_type']) ||
    !in_array($_GET['domain_type'], array('dmn', 'als', 'sub', 'alssub'))
) {
    showBadRequestErrorPage();
}

$domainId = intval($_GET['domain_id']);
$domainType = clean_input($_GET['domain_type']);

if (customerHasFeature('ssl') && !empty($_POST)) {
    if (isset($_POST['add_update'])) {
        client_addSslCert($domainId, $domainType);
    } elseif (isset($_POST['delete'])) {
        client_deleteSslCert($domainId, $domainType);
    } else {
        showBadRequestErrorPage();
    }
}

$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Client / Domains / SSL Certificate'),
    'TR_CERTIFICATE_DATA' => tr('Certificate data'),
    'TR_CERT_FOR' => tr('Common name'),
    'TR_STATUS' => tr('Status'),
    'TR_ALLOW_HSTS' => tr('Allow HTTP Strict Transport Security'),
    'TR_HSTS_MAX_AGE' => tr('HSTS: Set the max-age'),
    'TR_SEC' => tr('Sec.'),
    'TR_HSTS_INCLUDE_SUBDOMAINS' => tr('HSTS: Include sub domains'),
    'TR_HSTS_INCLUDE_SUBDOMAINS_TOOLTIP' => tr('Enable that feature only if all the subdomains of that domain have an SSL certificate.'),
    'TR_GENERATE_SELFSIGNED_CERTIFICAT' => tr('Generate a self-signed certificate'),
    'TR_PASSWORD' => tr('Private key passphrase if any'),
    'TR_PRIVATE_KEY' => tr('Private key'),
    'TR_CERTIFICATE' => tr('Certificate'),
    'TR_CA_BUNDLE' => tr('Intermediate certificate(s)'),
    'TR_DELETE' => tr('Delete'),
    'TR_CANCEL' => tr('Cancel'),
    'DOMAIN_ID' => tohtml($domainId),
    'DOMAIN_TYPE' => tohtml($domainType)
));

generateNavigation($tpl);
client_generatePage($tpl, $domainId, $domainType);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
