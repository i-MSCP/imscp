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
 * Get domain alias data
 *
 * @access private
 * @param int $domainAliasId Subdomain unique identifier
 * @return array Domain alias data. If any error occurs FALSE is returned
 */
function _reseller_getAliasData($domainAliasId)
{
    static $domainAliasData = null;

    if (null === $domainAliasData) {
        $stmt = exec_query(
            "
                SELECT t1.alias_name, t1.url_forward, t1.type_forward, t1.host_forward
                FROM domain_aliasses AS t1
                INNER JOIN domain AS t2 USING(domain_id)
                INNER JOIN admin AS t3 ON(admin_id = domain_admin_id)
                WHERE t1.alias_id = ? AND t1.alias_status = ? AND t3.created_by = ?
            ",
            array($domainAliasId, 'ok', $_SESSION['user_id'])
        );

        if (!$stmt->rowCount()) {
            return false;
        }

        $domainAliasData = $stmt->fetchRow(PDO::FETCH_ASSOC);
        $domainAliasData['alias_name_utf8'] = decode_idna($domainAliasData['alias_name']);
    }

    return $domainAliasData;
}

/**
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
 * @return void
 */
function reseller_generatePage($tpl)
{
    if (!isset($_GET['id'])) {
        showBadRequestErrorPage();
    }

    $domainAliasId = clean_input($_GET['id']);
    $domainAliasData = _reseller_getAliasData($domainAliasId);

    if ($domainAliasData === false) {
        showBadRequestErrorPage();
    }

    $forwardHost = 'Off';

    if (empty($_POST)) {
        if ($domainAliasData['url_forward'] != 'no') {
            $urlForwarding = true;
            $uri = iMSCP_Uri_Redirect::fromString($domainAliasData['url_forward']);
            $forwardUrlScheme = $uri->getScheme();
            $forwardUrl = substr($uri->getUri(), strlen($forwardUrlScheme) + 3);
            $forwardType = $domainAliasData['type_forward'];
            $forwardHost = $domainAliasData['host_forward'];
        } else {
            $urlForwarding = false;
            $forwardUrlScheme = 'http://';
            $forwardUrl = '';
            $forwardType = '302';
        }
    } else {
        $urlForwarding = (isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes') ? true : false;
        $forwardUrlScheme = (isset($_POST['forward_url_scheme'])) ? $_POST['forward_url_scheme'] : 'http://';
        $forwardUrl = isset($_POST['forward_url']) ? $_POST['forward_url'] : '';
        $forwardType = (isset($_POST['forward_type']) && in_array($_POST['forward_type'], array('301', '302', '303', '307', 'proxy'), true)) ? $_POST['forward_type'] : '302';

        if ($forwardType == 'proxy' && isset($_POST['forward_host'])) {
            $forwardHost = 'On';
        }
    }

    $tpl->assign(array(
        'DOMAIN_ALIAS_ID' => $domainAliasId,
        'DOMAIN_ALIAS_NAME' => tohtml($domainAliasData['alias_name_utf8']),
        'FORWARD_URL_YES' => ($urlForwarding) ? ' checked' : '',
        'FORWARD_URL_NO' => ($urlForwarding) ? '' : ' checked',
        'HTTP_YES' => ($forwardUrlScheme == 'http://') ? ' selected' : '',
        'HTTPS_YES' => ($forwardUrlScheme == 'https://') ? ' selected' : '',
        'FORWARD_URL' => tohtml(decode_idna($forwardUrl)),
        'FORWARD_TYPE_301' => ($forwardType == '301') ? ' checked' : '',
        'FORWARD_TYPE_302' => ($forwardType == '302') ? ' checked' : '',
        'FORWARD_TYPE_303' => ($forwardType == '303') ? ' checked' : '',
        'FORWARD_TYPE_307' => ($forwardType == '307') ? ' checked' : '',
        'FORWARD_TYPE_PROXY' => ($forwardType == '307') ? ' checked' : '',
        'FORWARD_HOST' => ($forwardHost == 'On') ? ' checked' : ''
    ));
}

/**
 * Edit domain alias
 *
 * @return bool TRUE on success, FALSE on failure
 */
function reseller_editDomainAlias()
{
    if (!isset($_GET['id'])) {
        showBadRequestErrorPage();
    }

    $domainAliasId = clean_input($_GET['id']);
    $domainAliasData = _reseller_getAliasData($domainAliasId);

    if ($domainAliasData === false) {
        showBadRequestErrorPage();
    }

    $forwardUrl = 'no';
    $forwardType = null;
    $forwardHost = 'Off';
    if (isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes' &&
        isset($_POST['forward_type']) && in_array($_POST['forward_type'], array('301', '302', '303', '307', 'proxy'), true)
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

            $uri->setHost(encode_idna(mb_strtolower($uri->getHost())));
            $uriPath = rtrim(preg_replace('#/+#', '/', $uri->getPath()), '/') . '/'; // normalize path
            $uri->setPath($uriPath);

            if ($uri->getHost() == $domainAliasData['alias_name'] && $uri->getPath() == '/') {
                throw new iMSCP_Exception(
                    tr('Forward URL %s is not valid.', "<strong>$forwardUrl</strong>") . ' ' .
                    tr(
                        'Domain alias %s cannot be forwarded on itself.',
                        "<strong>{$domainAliasData['alias_name_utf8']}</strong>"
                    )
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

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditDomainAlias, array(
        'domainAliasId' => $domainAliasId,
        'forwardUrl' => $forwardUrl,
        'forwardType' => $forwardType,
        'forwardHost' => $forwardHost
    ));

    exec_query(
        'UPDATE domain_aliasses SET url_forward = ?, type_forward = ?, host_forward = ?, alias_status = ? WHERE alias_id = ?',
        array($forwardUrl, $forwardType, $forwardHost, 'tochange', $domainAliasId)
    );

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditDomainALias, array(
        'domainAliasId' => $domainAliasId,
        'forwardUrl' => $forwardUrl,
        'forwardType' => $forwardType,
        'forwardHost' => $forwardHost
    ));

    send_request();

    write_log(
        "{$_SESSION['user_logged']}: scheduled update of domain alias: {$domainAliasData['alias_name_utf8']}.",
        E_USER_NOTICE
    );


    return true;
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);
check_login('reseller');

(resellerHasFeature('domain_aliases') && resellerHasCustomers()) or showBadRequestErrorPage();

if (!empty($_POST) && reseller_editDomainAlias()) {
    set_page_message(tr('Domain alias successfully scheduled for update.'), 'success');
    redirectTo('alias.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'reseller/alias_edit.tpl',
    'page_message' => 'layout'
));

$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Reseller / Domains / Edit Domain Alias'),
    'TR_DOMAIN_ALIAS' => tr('Domain alias'),
    'TR_DOMAIN_ALIAS_NAME' => tr('Domain alias name'),
    'TR_URL_FORWARDING' => tr('URL forwarding'),
    'TR_FORWARD_TO_URL' => tr('Forward to URL'),
    'TR_URL_FORWARDING_TOOLTIP' => tr('Allows to forward any request made to this domain to a specific URL.'),
    'TR_YES' => tr('Yes'),
    'TR_NO' => tr('No'),
    'TR_HTTP' => 'http://',
    'TR_HTTPS' => 'https://',
    'TR_FORWARD_TYPE' => tr('Forward type'),
    'TR_301' => '301',
    'TR_302' => '302',
    'TR_303' => '303',
    'TR_307' => '307',
    'TR_PROXY' => 'PROXY',
    'TR_PROXY_PRESERVE_HOST' => tr('Preserve Host'),
    'TR_UPDATE' => tr('Update'),
    'TR_CANCEL' => tr('Cancel')
));

generateNavigation($tpl);
reseller_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
