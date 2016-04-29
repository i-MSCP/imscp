<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by i-MSCP Team
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
 * Get customers list
 *
 * @return array Domains list
 */
function getCustomersList()
{
    static $customersList = null;

    if (null !== $customersList) {
        return $customersList;
    }

    $stmt = exec_query(
        '
            SELECT admin_id, admin_name, domain_id FROM admin INNER JOIN domain ON(domain_admin_id = admin_id)
            WHERE created_by = ? AND admin_status = ? ORDER BY admin_name
        ',
        array($_SESSION['user_id'], 'ok')
    );

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $customersList = $stmt->fetchAll();
    return $customersList;
}

/**
 * Get domains list for the given customer
 *
 * @param int $customerId Customer unique identifier
 * @return array Domains list
 */
function getDomainsList($customerId)
{
    static $domainsList = null;

    if (null !== $domainsList) {
        return $domainsList;
    }

    $mainDmnProps = get_domain_default_props($customerId, $_SESSION['user_id']);
    $domainsList = array(array(
        'name' => $mainDmnProps['domain_name'],
        'id' => $mainDmnProps['domain_id'],
        'type' => 'dmn',
        'mount_point' => '/'
    ));

    $stmt = exec_query(
        "
            SELECT CONCAT(t1.subdomain_name, '.', t2.domain_name) AS name, t1.subdomain_mount AS mount_point
            FROM  subdomain AS t1 INNER JOIN domain AS t2 USING(domain_id)
            WHERE t1.domain_id = :domain_id AND t1.subdomain_status = :status_ok
            UNION ALL
            SELECT alias_name AS name, alias_mount AS mount_point
            FROM  domain_aliasses WHERE domain_id = :domain_id AND alias_status = :status_ok
            UNION ALL
            SELECT CONCAT(t1.subdomain_alias_name, '.', t2.alias_name) AS name, t1.subdomain_alias_mount AS mount_point
            FROM subdomain_alias AS t1 INNER JOIN domain_aliasses AS t2 USING(alias_id)
            WHERE t2.domain_id = :domain_id AND subdomain_alias_status = :status_ok
        ",
        array('domain_id' => $mainDmnProps['domain_id'], 'status_ok' => 'ok')
    );

    if ($stmt->rowCount()) {
        $domainsList = array_merge($domainsList, $stmt->fetchAll());
        usort($domainsList, function ($a, $b) {
            return strnatcmp(decode_idna($a['name']), decode_idna($b['name']));
        });
    }

    return $domainsList;
}

/**
 * Get Json domains list for the given customer
 *
 * @param int $customerId Customer unique identifier
 * @return string Json Domains list
 */
function getJsonDomainsList($customerId)
{
    $jsonData = array();

    foreach (getDomainsList($customerId) as $domain) {
        $jsonData[] = array(
            'domain_name' => tohtml($domain['name']),
            'domain_name_unicode' => tohtml(decode_idna($domain['name']))
        );
    }

    return json_encode($jsonData);
}

/**
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
 * @return void
 */
function generatePage($tpl)
{
    $customersList = getCustomersList();

    foreach ($customersList as $customer) {
        $tpl->assign(array(
            'CUSTOMER_ID' => tohtml($customer['admin_id']),
            'CUSTOMER_NAME' => tohtml(decode_idna($customer['admin_name'])),
            'CUSTOMER_SELECTED' => isset($_POST['customer']) ? ' selected' : ''
        ));
        $tpl->parse('CUSTOMER_OPTION', '.customer_option');
    }

    $forwardType = isset($_POST['forward_type']) && in_array($_POST['forward_type'], array('301', '302', '303', '307'), true)
        ? $_POST['forward_type'] : '302';

    $tpl->assign(array(
        'DOMAIN_ALIAS_NAME' => isset($_POST['domain_alias_name']) ? tohtml($_POST['domain_alias_name']) : '',
        'SHARED_MOUNT_POINT_YES' => isset($_POST['shared_mount_point']) && $_POST['shared_mount_point'] == 'yes' ? ' checked' : '',
        'SHARED_MOUNT_POINT_NO' => isset($_POST['shared_mount_point']) && $_POST['shared_mount_point'] == 'yes' ? '' : ' checked',
        'FORWARD_URL_YES' => isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes' ? ' checked' : '',
        'FORWARD_URL_NO' => isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes' ? '' : ' checked',
        'HTTP_YES' => isset($_POST['forward_url_scheme']) && $_POST['forward_url_scheme'] == 'http://' ? ' checked' : '',
        'HTTPS_YES' => isset($_POST['forward_url_scheme']) && $_POST['forward_url_scheme'] == 'https://' ? ' checked' : '',
        'FTP_YES' => isset($_POST['forward_url_scheme']) && $_POST['forward_url_scheme'] == 'ftp://' ? ' checked' : '',
        'FORWARD_URL' => isset($_POST['forward_url']) ? tohtml(decode_idna($_POST['forward_url'])) : '',
        'FORWARD_TYPE_301' => ($forwardType == '301') ? ' checked' : '',
        'FORWARD_TYPE_302' => ($forwardType == '302') ? ' checked' : '',
        'FORWARD_TYPE_303' => ($forwardType == '303') ? ' checked' : '',
        'FORWARD_TYPE_307' => ($forwardType == '307') ? ' checked' : ''
    ));

    $domainList = getDomainsList(isset($_POST['customer_id']) ? clean_input($_POST['customer_id']) : $customersList[0]['admin_id']);

    foreach ($domainList as $domain) {
        $tpl->assign(array(
            'DOMAIN_NAME' => tohtml($domain['name']),
            'DOMAIN_NAME_UNICODE' => tohtml(decode_idna($domain['name'])),
            'SHARED_MOUNT_POINT_DOMAIN_SELECTED' => isset($_POST['shared_mount_point_domain']) && $_POST['shared_mount_point_domain'] == $domain['name'] ? ' selected' : ''
        ));
        $tpl->parse('SHARED_MOUNT_POINT_DOMAIN', '.shared_mount_point_domain');
    }
}

/**
 * Add new domain alias
 *
 * @return bool
 * @throws Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function addDomainAlias()
{
    // Basic check
    if (empty($_POST['customer_id'])) {
        showBadRequestErrorPage();
    }

    $customerId = clean_input($_POST['customer_id']);

    if (empty($_POST['domain_alias_name'])) {
        set_page_message(tr('You must enter a domain alias name.'), 'error');
        return false;
    }

    $domainAliasName = clean_input(strtolower($_POST['domain_alias_name']));

    // Check for domain alias name syntax
    global $dmnNameValidationErrMsg;
    if (!isValidDomainName($domainAliasName)) {
        set_page_message($dmnNameValidationErrMsg, 'error');
        return false;
    }

    // www is considered as an alias of the domain alias
    while (strpos($domainAliasName, 'www.') !== false) {
        $domainAliasName = substr($domainAliasName, 4);
    }

    // Check for domain alias existence
    if (imscp_domain_exists($domainAliasName, $_SESSION['user_id'])) {
        set_page_message(tr('Domain %s is unavailable.', "<strong>$domainAliasName</strong>"), 'error');
        return false;
    }

    $domainAliasNameAscii = encode_idna($domainAliasName);

    // Set default mount point
    $mountPoint = "/$domainAliasNameAscii";

    // Check for shared mount point option
    if (isset($_POST['shared_mount_point']) && $_POST['shared_mount_point'] == 'yes') {
        if (!isset($_POST['shared_mount_point_domain'])) {
            showBadRequestErrorPage();
        }

        $sharedMountPointDomain = clean_input($_POST['shared_mount_point_domain']);
        $domainList = getDomainsList($customerId);

        // Get shared mount point
        foreach ($domainList as $domain) {
            if ($domain['name'] == $sharedMountPointDomain) {
                $mountPoint = $domain['mount_point'];
            }
        }
    }

    // Check for URL forwarding option
    $forwardUrl = 'no';
    $forwardType = null;

    if (isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes' &&
        isset($_POST['forward_type']) && in_array($_POST['forward_type'], array('301', '302', '303', '307'), true)
    ) {
        if (!isset($_POST['forward_url_scheme']) || !isset($_POST['forward_url'])) {
            showBadRequestErrorPage();
        }

        $forwardUrl = clean_input($_POST['forward_url_scheme']) . clean_input($_POST['forward_url']);
        $forwardType = clean_input($_POST['forward_type']);

        try {
            try {
                $uri = iMSCP_Uri_Redirect::fromString($forwardUrl);
            } catch (Zend_Uri_Exception $e) {
                throw new iMSCP_Exception(tr('Forward URL %s is not valid.', "<strong>$forwardUrl</strong>"));
            }

            $uri->setHost(encode_idna($uri->getHost()));

            if ($uri->getHost() == $domainAliasNameAscii && $uri->getPath() == '/') {
                throw new iMSCP_Exception(
                    tr('Forward URL %s is not valid.', "<strong>$forwardUrl</strong>") . ' ' .
                    tr('Domain alias %s cannot be forwarded on itself.', "<strong>$domainAliasName</strong>")
                );
            }

            $forwardUrl = $uri->getUri();
        } catch (Exception $e) {
            set_page_message($e->getMessage(), 'error');
            return false;
        }
    }

    $mainDmnProps = get_domain_default_props($customerId, $_SESSION['user_id']);
    $cfg = iMSCP_Registry::get('config');
    $db = iMSCP_Database::getInstance();

    try {
        $db->beginTransaction();

        iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeAddDomainAlias, array(
            'domainId' => $mainDmnProps['domain_id'],
            'domainAliasName' => $domainAliasNameAscii
        ));

        exec_query(
            '
                INSERT INTO domain_aliasses (
                    domain_id, alias_name, alias_mount, alias_status, alias_ip_id, url_forward, type_forward
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?
                )
            ',
            array($mainDmnProps['domain_id'], $domainAliasNameAscii, $mountPoint, 'toadd', $mainDmnProps['domain_ip_id'], $forwardUrl, $forwardType)
        );

        $id = $db->insertId();

        // Create the phpini entry for that domain alias
        $phpini = iMSCP_PHPini::getInstance();
        $phpini->loadResellerPermissions($_SESSION['user_id']); // Load reseller PHP permissions
        $phpini->loadClientPermissions($mainDmnProps['admin_id']); // Load client PHP permissions
        $phpini->loadDomainIni($mainDmnProps['admin_id'], $mainDmnProps['domain_id'], 'dmn'); // Load main domain PHP configuration options
        $phpini->saveDomainIni($mainDmnProps['admin_id'], $id, 'als');

        // Create default email addresses if needed
        if ($cfg['CREATE_DEFAULT_EMAIL_ADDRESSES'] && $mainDmnProps['admin_email'] !== '') {
            client_mail_add_default_accounts($mainDmnProps['domain_id'], $mainDmnProps['admin_email'], $domainAliasNameAscii, 'alias', $id);
        }

        iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterAddDomainAlias, array(
            'domainId' => $mainDmnProps['domain_id'],
            'domainAliasName' => $domainAliasNameAscii,
            'domainAliasId' => $id
        ));
        
        $db->commit();
        send_request();
        write_log(sprintf('New domain alias `%s` has been added by %', $domainAliasName, $_SESSION['user_logged']), E_USER_NOTICE);
        set_page_message(tr('Domain alias successfully scheduled for addition.'), 'success');
    } catch (iMSCP_Exception $e) {
        $db->rollBack();
        throw $e;
    }

    return true;
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);
check_login('reseller');
resellerHasFeature('domain_aliases') && resellerHasCustomers() or showBadRequestErrorPage();

if (is_xhr() && isset($_POST['customer_id'])) {
    echo getJsonDomainsList(clean_input($_POST['customer_id']));
    return;
}

$resellerProps = imscp_getResellerProperties($_SESSION['user_id']);
if ($resellerProps['max_als_cnt'] != 0 && $resellerProps['current_als_cnt'] >= $resellerProps['max_als_cnt']) {
    set_page_message(tr('You have reached the maximum number of domain aliasses allowed by your subscription.'), 'warning');
    redirectTo('users.php');
}

if (!empty($_POST) && addDomainAlias()) {
    redirectTo('alias.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'reseller/alias_add.tpl',
    'page_message' => 'layout',
    'customer_option' => 'page',
    'shared_mount_point_domain' => 'page'
));
$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Reseller / Domains / Add Domain Alias'),
    'TR_CUSTOMER_ACCOUNT' => tr('Customer account'),
    'TR_DOMAIN_ALIAS' => tr('Domain alias'),
    'TR_DOMAIN_ALIAS_NAME' => tr('Domain alias name'),
    'TR_DOMAIN_ALIAS_NAME_TOOLTIP' => tr("You must omit 'www'. It will be added automatically."),
    'TR_SHARED_MOUNT_POINT' => tr('Shared mount point'),
    'TR_SHARED_MOUNT_POINT_TOOLTIP' => tr('Allows to share the mount point of another domain.'),
    'TR_URL_FORWARDING' => tr('URL forwarding'),
    'TR_URL_FORWARDING_TOOLTIP' => tr('Allows to forward any request made to this domain alias to a specific URL. Be aware that when this option is in use, no Web folder is created for the domain alias.'),
    'TR_FORWARD_TO_URL' => tr('Forward to URL'),
    'TR_YES' => tr('Yes'),
    'TR_NO' => tr('No'),
    'TR_HTTP' => 'http://',
    'TR_HTTPS' => 'https://',
    'TR_FTP' => 'ftp://',
    'TR_FORWARD_TYPE' => tr('Forward type'),
    'TR_301' => '301',
    'TR_302' => '302',
    'TR_303' => '303',
    'TR_307' => '307',
    'TR_ADD' => tr('Add'),
    'TR_CANCEL' => tr('Cancel')
));

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

unsetMessages();
