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
 * @return array|bool Domain alias data or FALSE on error
 */
function _client_getAliasData($domainAliasId)
{
    static $domainAliasData = null;

    if (null !== $domainAliasData) {
        return $domainAliasData;
    }

    $stmt = exec_query(
        '
            SELECT alias_name, alias_mount, alias_document_root, url_forward, type_forward, host_forward
            FROM domain_aliasses WHERE alias_id = ? AND domain_id = ? AND alias_status = ?
        ',
        array($domainAliasId, get_user_domain_id($_SESSION['user_id']), 'ok')
    );

    if (!$stmt->rowCount()) {
        return false;
    }

    $domainAliasData = $stmt->fetchRow();
    $domainAliasData['alias_name_utf8'] = decode_idna($domainAliasData['alias_name']);
    return $domainAliasData;
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

    $domainAliasId = intval($_GET['id']);
    $domainAliasData = _client_getAliasData($domainAliasId);
    if ($domainAliasData === false) {
        showBadRequestErrorPage();
    }

    $forwardHost = 'Off';

    if (empty($_POST)) {
        $documentRoot = isset($domainAliasData['alias_document_root']) ? $domainAliasData['alias_document_root'] : '';
        $documentRoot = substr($documentRoot, 7);

        if ($domainAliasData['url_forward'] != 'no') {
            $urlForwarding = true;
            $uri = iMSCP_Uri_Redirect::fromString($domainAliasData['url_forward']);
            $forwardUrlScheme = $uri->getScheme() . '://';
            $forwardUrl = substr($uri->getUri(), strlen($forwardUrlScheme));
            $forwardType = $domainAliasData['type_forward'];
            $forwardHost = $domainAliasData['host_forward'];
        } else {
            $urlForwarding = false;
            $forwardUrlScheme = 'http';
            $forwardUrl = '';
            $forwardType = '302';
        }
    } else {
        $documentRoot = isset($_POST['document_root']) ? $_POST['document_root'] : '';
        $urlForwarding = (isset($_POST['url_forwarding']) && $_POST['url_forwarding'] == 'yes') ? true : false;
        $forwardUrlScheme = (isset($_POST['forward_url_scheme'])) ? $_POST['forward_url_scheme'] : 'http://';
        $forwardUrl = (isset($_POST['forward_url'])) ? $_POST['forward_url'] : '';
        $forwardType = (isset($_POST['forward_type']) && in_array($_POST['forward_type'], array('301', '302', '303', '307', 'proxy'), true)) ? $_POST['forward_type'] : '302';

        if ($forwardType == 'proxy' && isset($_POST['forward_host'])) {
            $forwardHost = 'On';
        }
    }

    # Set parameters for the FTP chooser
    $_SESSION['vftp_root_dir'] = $domainAliasData['alias_mount'] . '/htdocs';
    $_SESSION['vftp_hidden_dirs'] = array();
    $_SESSION['vftp_unselectable_dirs'] = array();

    $tpl->assign(array(
        'DOMAIN_ALIAS_ID' => $domainAliasId,
        'DOMAIN_ALIAS_NAME' => tohtml($domainAliasData['alias_name_utf8']),
        'DOCUMENT_ROOT' => tohtml($documentRoot),
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

    // Cover the case where the domain alias is currently redirected to another domain
    // In such a case the customer must first disable the redirection, and edit the domain alias again to set an
    // alternative DocumentRoot
    $vfs = new iMSCP_VirtualFileSystem($_SESSION['user_logged'], $domainAliasData['alias_mount']);
    if(!$vfs->exists('/htdocs', iMSCP_VirtualFileSystem::VFS_TYPE_DIR)) {
        $tpl->assign('DOCUMENT_ROOT_BLOC',  '');
    }
}

/**
 * Edit domain alias
 *
 * @return bool TRUE on success, FALSE on failure
 */
function client_editDomainAlias()
{
    if (!isset($_GET['id'])) {
        showBadRequestErrorPage();
    }

    $domainAliasId = intval($_GET['id']);
    $domainAliasData = _client_getAliasData($domainAliasId);

    if ($domainAliasData === false) {
        showBadRequestErrorPage();
    }

    if(isset($_POST['document_root'])) {
        $documentRoot = rtrim(clean_input($_POST['document_root']), '/');

        if($documentRoot != '') {
            $vfs = new iMSCP_VirtualFileSystem($_SESSION['user_logged'], $domainAliasData['alias_mount'] . '/htdocs');
            if(!$vfs->exists($documentRoot, iMSCP_VirtualFileSystem::VFS_TYPE_DIR)) {
                set_page_message(tr('The new document root must pre-exists inside the /htdocs directory.'), 'error');
                return false;
            }
        }

        $documentRoot = '/htdocs' . $documentRoot;
    } else {
        $documentRoot = '/htdocs';
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
        'documentRoot' => $documentRoot,
        'forwardUrl' => $forwardUrl,
        'forwardType' => $forwardType,
        'forwardHost' => $forwardHost
    ));

    exec_query(
        '
          UPDATE domain_aliasses
          SET alias_document_root = ?, url_forward = ?, type_forward = ?, host_forward = ?, alias_status = ?
          WHERE alias_id = ?
        ',
        array($documentRoot, $forwardUrl, $forwardType, $forwardHost, 'tochange', $domainAliasId)
    );

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterEditDomainALias, array(
        'domainAliasId' => $domainAliasId,
        'documentRoot' => $documentRoot,
        'forwardUrl' => $forwardUrl,
        'forwardType' => $forwardType,
        'forwardHost' => $forwardHost
    ));

    send_request();
    write_log(sprintf('%s updated properties of the %s domain alias', $_SESSION['user_logged'], $domainAliasData['alias_name_utf8']), E_USER_NOTICE);
    return true;
}

/***********************************************************************************************************************
 * Main
 */


require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');
customerHasFeature('domain_aliases') or showBadRequestErrorPage();

if (!empty($_POST) && client_editDomainAlias()) {
    set_page_message(tr('Domain alias successfully scheduled for update.'), 'success');
    redirectTo('domains_manage.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'client/alias_edit.tpl',
    'page_message' => 'layout',
    'document_root_bloc' => 'page'
));
$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Client / Domains / Edit Domain Alias'),
    'TR_DOMAIN_ALIAS' => tr('Domain alias'),
    'TR_DOMAIN_ALIAS_NAME' => tr('Domain alias name'),
    'TR_DOCUMENT_ROOT' => tr('Document root'),
    'TR_DOCUMENT_ROOT_TOOLTIP' => tr("You can set an alternative document root. This is mostly needed when using a PHP framework such as Symfony. Note that the new document root will live inside the default  `/htdocs' document root. Be aware that the directory for the new document root must pre-exist."),
    'TR_CHOOSE_DIR' => tr('Choose dir'),
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

iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
    /** @var $e iMSCP_Events_Event */
    $translations = $e->getParam('translations');
    $translations['core']['close'] = tr('Close');
    $translations['core']['ftp_directories'] = tr('Select your own document root');
});

generateNavigation($tpl);
client_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
