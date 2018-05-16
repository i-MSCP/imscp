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

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get domains list
 *
 * @return array Domains list
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function getDomainsList()
{
    static $domainsList = NULL;

    if (NULL !== $domainsList) {
        return $domainsList;
    }

    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);
    $domainsList = [
        [
            'name'        => $mainDmnProps['domain_name'],
            'id'          => $mainDmnProps['domain_id'],
            'type'        => 'dmn',
            'mount_point' => '/',
            'url_forward' => $mainDmnProps['url_forward']
        ]
    ];

    $stmt = exec_query(
        "
            SELECT CONCAT(t1.subdomain_name, '.', t2.domain_name) AS name, t1.subdomain_id AS id,
                'sub' AS type, t1.subdomain_mount AS mount_point, t1.subdomain_url_forward AS url_forward
            FROM subdomain AS t1
            JOIN domain AS t2 USING(domain_id)
            WHERE t1.domain_id = :domain_id
            AND t1.subdomain_status = :status_ok
            UNION ALL
            SELECT alias_name, alias_id, 'als', alias_mount, url_forward
            FROM domain_aliasses
            WHERE domain_id = :domain_id
            AND alias_status = :status_ok
            UNION ALL
            SELECT CONCAT(t1.subdomain_alias_name, '.', t2.alias_name), t1.subdomain_alias_id, 'alssub',
                t1.subdomain_alias_mount, t1.subdomain_alias_url_forward AS url_forward
            FROM subdomain_alias AS t1
            JOIN domain_aliasses AS t2 USING(alias_id)
            WHERE t2.domain_id = :domain_id
            AND t1.subdomain_alias_status = :status_ok
        ",
        ['domain_id' => $mainDmnProps['domain_id'], 'status_ok' => 'ok']
    );

    if ($stmt->rowCount()) {
        $domainsList = array_merge($domainsList, $stmt->fetchAll(PDO::FETCH_ASSOC));
        usort($domainsList, function ($a, $b) {
            return strnatcmp(decode_idna($a['name']), decode_idna($b['name']));
        });
    }

    return $domainsList;
}

/**
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
 * @return void
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 */
function generatePage($tpl)
{
    $forwardType = (
        isset($_POST['forward_type'])
        && in_array($_POST['forward_type'], ['301', '302', '303', '307', 'proxy'], true)
    ) ? $_POST['forward_type'] : '302';
    $forwardHost = ($forwardType == 'proxy' && isset($_POST['forward_host'])) ? 'On' : 'Off';

    $tpl->assign([
        'SUBDOMAIN_NAME'     => (isset($_POST['subdomain_name'])) ? tohtml($_POST['subdomain_name']) : '',
        'FORWARD_URL_YES'    => (isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes') ? ' checked' : '',
        'FORWARD_URL_NO'     => (isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes') ? '' : ' checked',
        'HTTP_YES'           => (isset($_POST['forward_url_scheme']) && $_POST['forward_url_scheme'] == 'http://') ? ' selected' : '',
        'HTTPS_YES'          => (isset($_POST['forward_url_scheme']) && $_POST['forward_url_scheme'] == 'https://') ? ' selected' : '',
        'FORWARD_URL'        => (isset($_POST['forward_url'])) ? tohtml($_POST['forward_url']) : '',
        'FORWARD_TYPE_301'   => ($forwardType == '301') ? ' checked' : '',
        'FORWARD_TYPE_302'   => ($forwardType == '302') ? ' checked' : '',
        'FORWARD_TYPE_303'   => ($forwardType == '303') ? ' checked' : '',
        'FORWARD_TYPE_307'   => ($forwardType == '307') ? ' checked' : '',
        'FORWARD_TYPE_PROXY' => ($forwardType == 'proxy') ? ' checked' : '',
        'FORWARD_HOST'       => ($forwardHost == 'On') ? ' checked' : ''
    ]);

    $shareableMountpointCount = 0;
    foreach (getDomainsList() as $domain) {
        if ($domain['url_forward'] == 'no') {
            $shareableMountpointCount++;
        }

        $tpl->assign([
            'DOMAIN_NAME'          => tohtml($domain['name']),
            'DOMAIN_NAME_UNICODE'  => tohtml(decode_idna($domain['name'])),
            'DOMAIN_NAME_SELECTED' => (isset($_POST['domain_name']) && $_POST['domain_name'] == $domain['name']) ? ' selected' : '',
        ]);

        if ($domain['type'] == 'dmn' || $domain['type'] == 'als') {
            $tpl->parse('PARENT_DOMAIN', '.parent_domain');
        }

        if ($domain['url_forward'] == 'no') {
            $tpl->assign(
                'SHARED_MOUNT_POINT_DOMAIN_SELECTED',
                (isset($_POST['shared_mount_point_domain']) && $_POST['shared_mount_point_domain'] == $domain['name']) ? ' selected' : ''
            );
            $tpl->parse('SHARED_MOUNT_POINT_DOMAIN', '.shared_mount_point_domain');
        }
    }

    if ($shareableMountpointCount == 0) {
        $tpl->assign('SHARED_MOUNT_POINT_OPTION_JS', '');
        $tpl->assign('SHARED_MOUNT_POINT_OPTION', '');
    } else {
        $tpl->assign([
            'SHARED_MOUNT_POINT_YES' => (isset($_POST['shared_mount_point']) && $_POST['shared_mount_point'] == 'yes') ? ' checked' : '',
            'SHARED_MOUNT_POINT_NO'  => (isset($_POST['shared_mount_point']) && $_POST['shared_mount_point'] == 'yes') ? '' : ' checked'
        ]);
    }
}

/**
 * Add new subdomain
 *
 * @return bool TRUE on success, FALSE on failure
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function addSubdomain()
{
    global $mainDmnProps;

    // Basic check
    if (empty($_POST['subdomain_name'])) {
        set_page_message(tr('You must enter a subdomain name.'), 'error');
        return false;
    }

    if (empty($_POST['domain_name'])) {
        showBadRequestErrorPage();
    }

    // Check for parent domain
    $domainName = mb_strtolower(clean_input($_POST['domain_name']));
    $domainType = $domainId = NULL;
    $domainList = getDomainsList();

    foreach ($domainList as $domain) {
        if (($domain['type'] == 'dmn' || $domain['type'] == 'als') && $domain['name'] == $domainName) {
            $domainType = $domain['type'];
            $domainId = $domain['id'];
        }
    }

    if (NULL === $domainType) {
        showBadRequestErrorPage();
    }

    $subLabel = mb_strtolower(clean_input($_POST['subdomain_name']));

    if ($subLabel == 'www' || strpos($subLabel, 'www.') === 0) {
        set_page_message(tr('%s is not allowed as subdomain label.', "<strong>www</strong>"), 'error');
        return false;
    }

    $subdomainName = $subLabel . '.' . $domainName;

    // Check for subdomain syntax
    if (!isValidDomainName($subdomainName)) {
        set_page_message(tr('Subdomain name is not valid.'), 'error');
        return false;
    }

    // Ensure that this subdomain doesn't already exists as domain or domain alias
    $stmt = exec_query('
        SELECT domain_id FROM domain WHERE domain_name = :subdomain_name
        UNION ALL
        SELECT alias_id FROM domain_aliasses WHERE alias_name = :subdomain_name',
        ['subdomain_name' => $subdomainName]
    );
    if ($stmt->rowCount()) {
        set_page_message(tr('Subdomain %s is unavailable.', "<strong>$subdomainName</strong>"), 'error');
        return false;
    }

    $subLabelAscii = encode_idna($subLabel);
    $subdomainNameAscii = encode_idna($subdomainName);

    // Check for subdomain existence
    foreach ($domainList as $domain) {
        if ($domain['name'] == $subdomainNameAscii) {
            set_page_message(tr('Subdomain %s already exist.', "<strong>$subdomainName</strong>"), 'error');
            return false;
        }
    }

    // Set default mount point
    if ($domainType == 'dmn') {
        if (in_array($subLabelAscii, ['backups', 'cgi-bin', 'errors', 'logs', 'phptmp'])) {
            $mountPoint = "/sub_$subLabelAscii";
        } else {
            $mountPoint = "/$subLabelAscii";
        }
    } else {
        if ($subLabelAscii === 'cgi-bin') {
            $mountPoint = "/$domainName/sub_$subLabelAscii";
        } else {
            $mountPoint = "/$domainName/$subLabelAscii";
        }
    }

    // Check for shared mount point option
    if (isset($_POST['shared_mount_point']) && $_POST['shared_mount_point'] == 'yes') { // We are safe here
        if (!isset($_POST['shared_mount_point_domain'])) {
            showBadRequestErrorPage();
        }

        $sharedMountPointDomain = clean_input($_POST['shared_mount_point_domain']);

        // Get shared mount point
        foreach ($domainList as $domain) {
            if ($domain['name'] == $sharedMountPointDomain) {
                $mountPoint = $domain['mount_point'];
            }
        }
    }


    // Default values
    $documentRoot = '/htdocs';
    $forwardUrl = 'no';
    $forwardType = NULL;
    $forwardHost = 'Off';

    // Check for URL forwarding option
    if (isset($_POST['url_forwarding'])
        && $_POST['url_forwarding'] == 'yes'
        && isset($_POST['forward_type'])
        && in_array($_POST['forward_type'], ['301', '302', '303', '307', 'proxy'], true)
    ) {
        if (!isset($_POST['forward_url_scheme']) || !isset($_POST['forward_url'])) {
            showBadRequestErrorPage();
        }

        $forwardUrl = clean_input($_POST['forward_url_scheme']) . clean_input($_POST['forward_url']);
        $forwardType = clean_input($_POST['forward_type']);

        if ($forwardType == 'proxy' && isset($_POST['forward_host'])) {
            $forwardHost = 'On';
        }

        try {
            try {
                $uri = iMSCP_Uri_Redirect::fromString($forwardUrl);
            } catch (Zend_Uri_Exception $e) {
                throw new iMSCP_Exception(tr('Forward URL %s is not valid.', "<strong>$forwardUrl</strong>"));
            }

            $uri->setHost(encode_idna(mb_strtolower($uri->getHost()))); // Normalize URI host
            $uri->setPath(rtrim(utils_normalizePath($uri->getPath()), '/') . '/'); // Normalize URI path

            if ($uri->getHost() == $subdomainNameAscii
                && ($uri->getPath() == '/' && in_array($uri->getPort(), ['', 80, 443]))
            ) {
                throw new iMSCP_Exception(
                    tr('Forward URL %s is not valid.', "<strong>$forwardUrl</strong>") . ' ' .
                    tr('Subdomain %s cannot be forwarded on itself.', "<strong>$subdomainName</strong>")
                );
            }

            if ($forwardType == 'proxy') {
                $port = $uri->getPort();
                if ($port && $port < 1025) {
                    throw new iMSCP_Exception(tr('Unallowed port in forward URL. Only ports above 1024 are allowed.', 'error'));
                }
            }

            $forwardUrl = $uri->getUri();
        } catch (Exception $e) {
            set_page_message($e->getMessage(), 'error');
            return false;
        }
    }

    $db = iMSCP_Database::getInstance();

    try {
        $db->beginTransaction();

        iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeAddSubdomain, [
            'subdomainName'  => $subdomainName,
            'subdomainType'  => $domainType,
            'parentDomainId' => $domainId,
            'mountPoint'     => $mountPoint,
            'documentRoot'   => $documentRoot,
            'forwardUrl'     => $forwardUrl,
            'forwardType'    => $forwardType,
            'forwardHost'    => $forwardHost,
            'customerId'     => $_SESSION['user_id']
        ]);

        if ($domainType == 'als') {
            $query = "
                INSERT INTO subdomain_alias (
                    alias_id, subdomain_alias_name, subdomain_alias_mount, subdomain_alias_document_root,
                    subdomain_alias_url_forward, subdomain_alias_type_forward, subdomain_alias_host_forward,
                    subdomain_alias_status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?
                )
            ";
        } else {
            $query = "
                INSERT INTO subdomain (
                    domain_id, subdomain_name, subdomain_mount, subdomain_document_root, subdomain_url_forward,
                    subdomain_type_forward, subdomain_host_forward, subdomain_status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?
                )
            ";
        }

        exec_query($query, [
            $domainId, $subLabelAscii, $mountPoint, $documentRoot, $forwardUrl, $forwardType, $forwardHost, 'toadd'
        ]);

        $subdomainId = $db->insertId();

        // Create the phpini entry for that subdomain
        $phpini = iMSCP_PHPini::getInstance();
        $phpini->loadResellerPermissions($_SESSION['user_created_by']); // Load reseller PHP permissions
        $phpini->loadClientPermissions($_SESSION['user_id']); // Load client PHP permissions
        $phpini->loadDomainIni($_SESSION['user_id'], $mainDmnProps['domain_id'], 'dmn'); // Load main domain PHP configuration options
        $phpini->saveDomainIni($_SESSION['user_id'], $subdomainId, $domainType == 'dmn' ? 'sub' : 'subals');

        $cfg = iMSCP_Registry::get('config');

        if ($cfg['CREATE_DEFAULT_EMAIL_ADDRESSES']) {
            createDefaultMailAccounts(
                $mainDmnProps['domain_id'],
                iMSCP_Authentication::getInstance()->getIdentity()->email,
                $subdomainNameAscii,
                ($domainType == 'dmn') ? MT_SUBDOM_FORWARD : MT_ALSSUB_FORWARD, $subdomainId
            );
        }

        iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterAddSubdomain, [
            'subdomainName'  => $subdomainName,
            'subdomainType'  => $domainType,
            'parentDomainId' => $domainId,
            'mountPoint'     => $mountPoint,
            'documentRoot'   => $documentRoot,
            'forwardUrl'     => $forwardUrl,
            'forwardType'    => $forwardType,
            'forwardHost'    => $forwardHost,
            'customerId'     => $_SESSION['user_id'],
            'subdomainId'    => $subdomainId
        ]);

        $db->commit();
        send_request();
        write_log(sprintf('A new subdomain (%s) has been created by %s', $subdomainName, $_SESSION['user_logged']), E_USER_NOTICE);
        return true;
    } catch (iMSCP_Exception $e) {
        $db->rollBack();
        write_log(sprintf('System was unable to create the %s subdomain: %s', $subdomainName, $e->getMessage()), E_USER_ERROR);
        set_page_message('Could not create subdomain. An unexpected error occurred.', 'error');
        return false;
    }
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
customerHasFeature('subdomains') or showBadRequestErrorPage();

$mainDmnProps = get_domain_default_props($_SESSION['user_id']);
$subdomainsCount = get_customer_subdomains_count($mainDmnProps['domain_id']);

if ($mainDmnProps['domain_subd_limit'] != 0 && $subdomainsCount >= $mainDmnProps['domain_subd_limit']) {
    set_page_message(tr('You have reached the maximum number of subdomains allowed by your subscription.'), 'warning');
    redirectTo('domains_manage.php');
}

if (!empty($_POST) && addSubdomain()) {
    set_page_message(tr('Subdomain successfully scheduled for addition.'), 'success');
    redirectTo('domains_manage.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'                       => 'shared/layouts/ui.tpl',
    'page'                         => 'client/subdomain_add.tpl',
    'page_message'                 => 'layout',
    'parent_domain'                => 'page',
    'shared_mount_point_option_js' => 'page',
    'shared_mount_point_option'    => 'page',
    'shared_mount_point_domain'    => 'shared_mount_point_option'
]);
$tpl->assign([
    'TR_PAGE_TITLE'                 => tr('Client / Domains / Add Subdomain'),
    'TR_SUBDOMAIN'                  => tr('Subdomain'),
    'TR_SUBDOMAIN_NAME'             => tr('Subdomain name'),
    'TR_SHARED_MOUNT_POINT'         => tr('Shared mount point'),
    'TR_SHARED_MOUNT_POINT_TOOLTIP' => tr('Allows to share the mount point of another domain.'),
    'TR_URL_FORWARDING'             => tr('URL forwarding'),
    'TR_URL_FORWARDING_TOOLTIP'     => tr('Allows to forward any request made to this domain to a specific URL.'),
    'TR_FORWARD_TO_URL'             => tr('Forward to URL'),
    'TR_YES'                        => tr('Yes'),
    'TR_NO'                         => tr('No'),
    'TR_HTTP'                       => 'http://',
    'TR_HTTPS'                      => 'https://',
    'TR_FORWARD_TYPE'               => tr('Forward type'),
    'TR_301'                        => '301',
    'TR_302'                        => '302',
    'TR_303'                        => '303',
    'TR_307'                        => '307',
    'TR_PROXY'                      => 'PROXY',
    'TR_PROXY_PRESERVE_HOST'        => tr('Preserve Host'),
    'TR_ADD'                        => tr('Add'),
    'TR_CANCEL'                     => tr('Cancel')
]);

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
