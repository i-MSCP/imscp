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
 * Get domain data
 *
 * @access private
 * @param int $domainId Domain unique identifier
 * @return array|bool Domain data or FALSE on error
 */
function _client_getDomainData($domainId)
{
    static $domainData = null;

    if (null !== $domainData) {
        return $domainData;
    }

    $stmt = exec_query(
        '
            SELECT domain_name, url_forward, type_forward, host_forward
            FROM domain WHERE domain_id = ? AND domain_admin_id = ? AND domain_status = ?
        ',
        array($domainId, $_SESSION['user_id'], 'ok')
    );

    if (!$stmt->rowCount()) {
        return false;
    }

    $domainData = $stmt->fetchRow();
    $domainData['domain_name_utf8'] = decode_idna($domainData['domain_name']);
    return $domainData;
}

/**
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
 * @return void
 */
function client_generatePage($tpl)
{
    if (!isset($_GET['id'])) {
        showBadRequestErrorPage();
    }

    $domainId = intval($_GET['id']);
    $domainData = _client_getDomainData($domainId);
    if ($domainData === false) {
        showBadRequestErrorPage();
    }

    $forwardHost = 'Off';

    if (empty($_POST)) {
        if ($domainData['url_forward'] != 'no') {
            $urlForwarding = true;
            $uri = iMSCP_Uri_Redirect::fromString($domainData['url_forward']);
            $forwardUrlScheme = $uri->getScheme() . '://';
            $forwardUrl = substr($uri->getUri(), strlen($forwardUrlScheme));
            $forwardType = $domainData['type_forward'];
            $forwardHost = $domainData['host_forward'];
        } else {
            $urlForwarding = false;
            $forwardUrlScheme = 'http';
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
        'DOMAIN_ID' => $domainId,
        'DOMAIN_NAME' => tohtml($domainData['domain_name_utf8']),
        'FORWARD_URL_YES' => ($urlForwarding) ? ' checked' : '',
        'FORWARD_URL_NO' => ($urlForwarding) ? '' : ' checked',
        'HTTP_YES' => ($forwardUrlScheme == 'http://') ? ' selected' : '',
        'HTTPS_YES' => ($forwardUrlScheme == 'https://') ? ' selected' : '',
        'FORWARD_URL' => $forwardUrl !== '' ? tohtml(decode_idna($forwardUrl)) : '',
        'FORWARD_TYPE_301' => ($forwardType == '301') ? ' checked' : '',
        'FORWARD_TYPE_302' => ($forwardType == '302') ? ' checked' : '',
        'FORWARD_TYPE_303' => ($forwardType == '303') ? ' checked' : '',
        'FORWARD_TYPE_307' => ($forwardType == '307') ? ' checked' : '',
        'FORWARD_TYPE_PROXY' => ($forwardType == 'proxy') ? ' checked' : '',
        'FORWARD_HOST' => ($forwardHost == 'On') ? ' checked' : ''
    ));
}

/**
 * Edit domain
 *
 * @return bool TRUE on success, FALSE on failure
 */
function client_editDomain()
{
    if (!isset($_GET['id'])) {
        showBadRequestErrorPage();
    }

    $domainId = intval($_GET['id']);
    $domainData = _client_getDomainData($domainId);

    if ($domainData === false) {
        showBadRequestErrorPage();
    }

    // Check for URL forwarding option
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

            if ($uri->getHost() == $domainData['domain_name'] && $uri->getPath() == '/') {
                throw new iMSCP_Exception(
                    tr('Forward URL %s is not valid.', "<strong>$forwardUrl</strong>") . ' ' .
                    tr(
                        'Domain %s cannot be forwarded on itself.',
                        "<strong>{$domainData['domain_name_utf8']}</strong>"
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

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeEditDomain, array(
        'domainId' => $domainId,
        'forwardUrl' => $forwardUrl,
        'forwardType' => $forwardType,
        'forwardHost' => $forwardHost
    ));

    exec_query(
        'UPDATE domain SET url_forward = ?, type_forward = ?, host_forward = ?, domain_status = ? WHERE domain_id = ?',
        array($forwardUrl, $forwardType, $forwardHost, 'tochange', $domainId)
    );

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditDomain, array(
        'domainId' => $domainId,
        'forwardUrl' => $forwardUrl,
        'forwardType' => $forwardType,
        'forwardHost' => $forwardHost
    ));

    send_request();
    write_log(sprintf('%s updated properties of the %s domain', $_SESSION['user_logged'], $domainData['domain_name_utf8']), E_USER_NOTICE);
    return true;
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');

if (!empty($_POST) && client_editDomain()) {
    set_page_message(tr('Domain successfully scheduled for update.'), 'success');
    redirectTo('domains_manage.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'client/domain_edit.tpl',
    'page_message' => 'layout'
));
$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Client / Domains / Edit Domain'),
    'TR_DOMAIN' => tr('Domain'),
    'TR_DOMAIN_NAME' => tr('Domain name'),
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
client_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
