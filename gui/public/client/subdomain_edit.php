<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * @noinspection
 * PhpDocMissingThrowsInspection
 * PhpUnhandledExceptionInspection
 * PhpIncludeInspection
 */

use iMSCP\Event\Event;
use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\Exception\Exception;
use iMSCP\TemplateEngine;
use iMSCP\Uri\UriException;
use iMSCP\Uri\UriRedirect;
use iMSCP\VirtualFileSystem;

/**
 * Get subdomain data
 *
 * @access private
 * @param int $subdomainId Subdomain unique identifier
 * @param string $subdomainType Subdomain Type
 * @return array|bool Subdomain data or FALSE on error
 */
function _client_getSubdomainData($subdomainId, $subdomainType)
{
    static $subdomainData = NULL;

    if (NULL !== $subdomainData) {
        return $subdomainData;
    }

    $mainDmnProps = get_domain_default_props($_SESSION['user_id']);
    $domainId = $mainDmnProps['domain_id'];
    $domainName = $mainDmnProps['domain_name'];

    if ($subdomainType == 'dmn') {
        $query = '
            SELECT subdomain_name , subdomain_mount AS subdomain_mount,
                subdomain_document_root AS document_root,
                subdomain_url_forward AS url_forward,
                subdomain_type_forward AS type_forward,
                subdomain_host_forward AS host_forward,
                subdomain_wildcard_alias AS wildcard_alias
            FROM subdomain
            WHERE subdomain_id = ?
            AND domain_id = ?
            AND subdomain_status = ?
        ';
    } else {
        $query = '
            SELECT t1.subdomain_alias_name AS subdomain_name,
                t1.subdomain_alias_mount AS subdomain_mount,
                t1.subdomain_alias_document_root AS document_root,
                t1.subdomain_alias_url_forward AS url_forward,
                t1.subdomain_alias_type_forward AS type_forward,
                t1.subdomain_alias_host_forward AS host_forward,
                t1.subdomain_alias_wildcard_alias AS wildcard_alias,
                t2.alias_name AS alias_name
            FROM subdomain_alias AS t1
            JOIN domain_aliasses AS t2 USING(alias_id)
            WHERE subdomain_alias_id = ?
            AND t2.domain_id = ?
            AND t1.subdomain_alias_status = ?
        ';
    }

    $stmt = exec_query($query, [$subdomainId, $domainId, 'ok']);
    if (!$stmt->rowCount()) {
        return false;
    }

    $subdomainData = $stmt->fetchRow();

    if ($subdomainType == 'dmn') {
        $subdomainData['subdomain_name'] .= '.' . $domainName;
        $subdomainData['subdomain_name_utf8'] = decode_idna(
            $subdomainData['subdomain_name']
        );
    } else {
        $subdomainData['subdomain_name'] .= '.' . $subdomainData['alias_name'];
        $subdomainData['subdomain_name_utf8'] = decode_idna(
            $subdomainData['subdomain_name']
        );
    }

    return $subdomainData;
}

/**
 * Generate page
 *
 * @param $tpl TemplateEngine
 * @return void
 */
function client_generatePage(TemplateEngine $tpl)
{
    if (!isset($_GET['id'])
        || !isset($_GET['type'])
        || !($_GET['type'] == 'dmn'
            || $_GET['type'] == 'als')
    ) {
        showBadRequestErrorPage();
    }

    $subdomainId = intval($_GET['id']);
    $subdomainType = clean_input($_GET['type']);
    $subdomainData = _client_getSubdomainData($subdomainId, $subdomainType);
    if ($subdomainData === false) {
        showBadRequestErrorPage();
    }

    $forwardHost = 'Off';

    if (empty($_POST)) {
        $documentRoot = strpos(
            $subdomainData['document_root'], '/htdocs'
        ) !== false
            ? substr($subdomainData['document_root'], 7)
            : '';

        if ($subdomainData['url_forward'] != 'no') {
            $urlForwarding = true;
            $uri = UriRedirect::fromString(
                $subdomainData['url_forward']
            );
            $uri->setHost(decode_idna($uri->getHost()));
            $forwardUrlScheme = $uri->getScheme() . '://';
            $forwardUrl = substr($uri->getUri(), strlen($forwardUrlScheme));
            $forwardType = $subdomainData['type_forward'];
            $forwardHost = $subdomainData['host_forward'];
        } else {
            $urlForwarding = false;
            $forwardUrlScheme = 'http://';
            $forwardUrl = '';
            $forwardType = '302';
        }

        $wildcardAlias = $subdomainData['wildcard_alias'];
    } else {
        $documentRoot = isset($_POST['document_root'])
            ? $_POST['document_root'] : '';
        $urlForwarding = isset($_POST['url_forwarding'])
        && $_POST['url_forwarding'] == 'yes'
            ? true : false;
        $forwardUrlScheme = isset($_POST['forward_url_scheme'])
            ? $_POST['forward_url_scheme'] : 'http://';
        $forwardUrl = isset($_POST['forward_url'])
            ? $_POST['forward_url'] : '';
        $forwardType = isset($_POST['forward_type'])
        && in_array(
            $_POST['forward_type'],
            ['301', '302', '303', '307', 'proxy'],
            true
        )
            ? $_POST['forward_type'] : '302';

        if ($forwardType == 'proxy'
            && isset($_POST['forward_host'])
        ) {
            $forwardHost = 'On';
        }

        $wildcardAlias = isset($_POST['wildcard_alias'])
        && in_array($_POST['wildcard_alias'], ['yes', 'no'], true)
            ? $_POST['wildcard_alias'] : 'no';
    }

    $tpl->assign([
        'SUBDOMAIN_ID'       => tohtml($subdomainId, 'htmlAttr'),
        'SUBDOMAIN_TYPE'     => tohtml($subdomainType, 'htmlAttr'),
        'SUBDOMAIN_NAME'     => tohtml($subdomainData['subdomain_name_utf8'], 'htmlAttr'),
        'DOCUMENT_ROOT'      => tohtml($documentRoot, 'htmlAttr'),
        'FORWARD_URL_YES'    => $urlForwarding ? ' checked' : '',
        'FORWARD_URL_NO'     => $urlForwarding ? '' : ' checked',
        'HTTP_YES'           => $forwardUrlScheme == 'http://' ? ' selected' : '',
        'HTTPS_YES'          => $forwardUrlScheme == 'https://' ? ' selected' : '',
        'FORWARD_URL'        => tohtml($forwardUrl, 'htmlAttr'),
        'FORWARD_TYPE_301'   => $forwardType == '301' ? ' checked' : '',
        'FORWARD_TYPE_302'   => $forwardType == '302' ? ' checked' : '',
        'FORWARD_TYPE_303'   => $forwardType == '303' ? ' checked' : '',
        'FORWARD_TYPE_307'   => $forwardType == '307' ? ' checked' : '',
        'FORWARD_TYPE_PROXY' => $forwardType == 'proxy' ? ' checked' : '',
        'FORWARD_HOST'       => $forwardHost == 'On' ? ' checked' : '',
        'WILDCARD_ALIAS_YES' => $wildcardAlias == 'yes' ? ' checked' : '',
        'WILDCARD_ALIAS_NO'  => $wildcardAlias == 'no' ? ' checked' : ''
    ]);

    // Cover the case where URL forwarding feature is activated and that the
    // default /htdocs directory doesn't exists yet
    if ($subdomainData['url_forward'] != 'no') {
        $vfs = new VirtualFileSystem(
            $_SESSION['user_logged'], $subdomainData['subdomain_mount']
        );

        if (!$vfs->exists('/htdocs')) {
            $tpl->assign('DOCUMENT_ROOT_BLOC', '');
            return;
        }
    }

    # Set parameters for the FTP chooser
    $_SESSION['ftp_chooser_domain_id'] = get_user_domain_id(
        $_SESSION['user_id']
    );
    $_SESSION['ftp_chooser_user'] = $_SESSION['user_logged'];
    $_SESSION['ftp_chooser_root_dir'] = utils_normalizePath(
        $subdomainData['subdomain_mount'] . '/htdocs'
    );
    $_SESSION['ftp_chooser_hidden_dirs'] = [];
    $_SESSION['ftp_chooser_unselectable_dirs'] = [];
}

/**
 * Edit subdomain
 *
 * @return bool TRUE on success, FALSE on failure
 */
function client_editSubdomain()
{
    if (!isset($_GET['id'])
        | !isset($_GET['type'])
        || !($_GET['type'] == 'dmn'
            || $_GET['type'] == 'als')
    ) {
        showBadRequestErrorPage();
    }

    $subdomainId = clean_input($_GET['id']);
    $subdomainType = clean_input($_GET['type']);
    $subdomainData = _client_getSubdomainData($subdomainId, $subdomainType);

    if ($subdomainData === false) {
        showBadRequestErrorPage();
    }

    // Default values
    $documentRoot = $subdomainData['document_root'];
    $forwardUrl = 'no';
    $forwardType = NULL;
    $forwardHost = 'Off';

    // Check for URL forwarding option
    if (isset($_POST['url_forwarding'])
        && $_POST['url_forwarding'] == 'yes'
        && isset($_POST['forward_type'])
        && in_array(
            $_POST['forward_type'], ['301', '302', '303', '307', 'proxy'], true
        )
    ) {
        if (!isset($_POST['forward_url_scheme'])
            || !isset($_POST['forward_url'])
        ) {
            showBadRequestErrorPage();
        }

        $forwardUrl = clean_input($_POST['forward_url_scheme'])
            . clean_input($_POST['forward_url']);
        $forwardType = clean_input($_POST['forward_type']);

        if ($forwardType == 'proxy' && isset($_POST['forward_host'])) {
            $forwardHost = 'On';
        }

        try {
            try {
                $uri = UriRedirect::fromString($forwardUrl);
            } catch (UriException $e) {
                throw new Exception(
                    tr('Forward URL %s is not valid.', $forwardUrl)
                );
            }

            // Normalize URI host
            $uri->setHost(encode_idna(mb_strtolower($uri->getHost())));
            // Normalize URI path
            $uri->setPath(rtrim(utils_normalizePath($uri->getPath()), '/') . '/');

            if ($uri->getHost() == $subdomainData['subdomain_name']
                && ($uri->getPath() == '/'
                    && in_array($uri->getPort(), ['', 80, 443])
                )
            ) {
                throw new Exception(
                    tr('Forward URL %s is not valid.', $forwardUrl) . ' ' .
                    tr(
                        'Subdomain %s cannot be forwarded on itself.',
                        $subdomainData['subdomain_name_utf8']
                    )
                );
            }

            if ($forwardType == 'proxy') {
                $port = $uri->getPort();
                if ($port && $port < 1025) {
                    throw new Exception(
                        tr('Unallowed port in forward URL. Only ports above 1024 are allowed.')
                    );
                }
            }

            $forwardUrl = $uri->getUri();
        } catch (Exception $e) {
            set_page_message(tohtml($e->getMessage()), 'error');
            return false;
        }
    } // Check for alternative DocumentRoot option
    elseif (isset($_POST['document_root'])) {
        $documentRoot = utils_normalizePath(
            '/' . clean_input($_POST['document_root'])
        );

        if ($documentRoot !== '') {
            $vfs = new VirtualFileSystem(
                $_SESSION['user_logged'],
                $subdomainData['subdomain_mount'] . '/htdocs'
            );

            if ($documentRoot !== '/'
                && !$vfs->exists($documentRoot, VirtualFileSystem::VFS_TYPE_DIR)
            ) {
                set_page_message(
                    tohtml(tr('The new document root must pre-exists inside the /htdocs directory.')),
                    'error'
                );
                return false;
            }
        }

        $documentRoot = utils_normalizePath('/htdocs' . $documentRoot);
    }

    $wildcardAlias = isset($_POST['wildcard_alias'])
    && in_array($_POST['wildcard_alias'], [0, 1])
        ? $_POST['wildcard_alias'] : 0;

    EventAggregator::getInstance()->dispatch(
        Events::onBeforeEditSubdomain, [
            'subdomainId'   => $subdomainId,
            'subdomainName' => $subdomainData['subdomain_name'],
            'subdomainType' => $subdomainType,
            'mountPoint'    => $subdomainData['subdomain_mount'],
            'documentRoot'  => $documentRoot,
            'forwardUrl'    => $forwardUrl,
            'forwardType'   => $forwardType,
            'forwardHost'   => $forwardHost,
            'wildcardAlias' => $wildcardAlias
        ]
    );

    if ($subdomainType == 'dmn') {
        $query = '
            UPDATE subdomain
            SET subdomain_document_root = ?, subdomain_url_forward = ?,
                subdomain_type_forward = ?, subdomain_host_forward = ?,
                subdomain_wildcard_alias = ?, subdomain_status = ?
            WHERE subdomain_id = ?
        ';
    } else {
        $query = '
            UPDATE subdomain_alias
            SET subdomain_alias_document_root = ?,
                subdomain_alias_url_forward = ?,
                subdomain_alias_type_forward = ?,
                subdomain_alias_host_forward = ?,
                subdomain_wildcard_alias = ?, subdomain_alias_status = ?
            WHERE subdomain_alias_id = ?
        ';
    }

    exec_query($query, [
        $documentRoot, $forwardUrl, $forwardType, $forwardHost, $wildcardAlias,
        'tochange', $subdomainId
    ]);

    EventAggregator::getInstance()->dispatch(
        Events::onAfterEditSubdomain,
        [
            'subdomainId'   => $subdomainId,
            'subdomainName' => $subdomainData['subdomain_name'],
            'subdomainType' => $subdomainType,
            'mountPoint'    => $subdomainData['subdomain_mount'],
            'documentRoot'  => $documentRoot,
            'forwardUrl'    => $forwardUrl,
            'forwardType'   => $forwardType,
            'forwardHost'   => $forwardHost,
            'wildcardAlias' => $wildcardAlias
        ]
    );

    send_request();
    write_log(
        sprintf(
            '%s updated properties of the %s subdomain',
            $_SESSION['user_logged'], $subdomainData['subdomain_name_utf8']
        ),
        E_USER_NOTICE
    );
    return true;
}

require_once 'imscp-lib.php';

check_login('user');
EventAggregator::getInstance()->dispatch(Events::onClientScriptStart);
customerHasFeature('subdomains') or showBadRequestErrorPage();

if (!empty($_POST) && client_editSubdomain()) {
    set_page_message(
        tohtml(tr('Subdomain successfully scheduled for update')),
        'success'
    );
    redirectTo('domains_manage.php');
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'             => 'shared/layouts/ui.tpl',
    'page'               => 'client/subdomain_edit.tpl',
    'page_message'       => 'layout',
    'document_root_bloc' => 'page'
]);
$tpl->assign([
    'TR_PAGE_TITLE'             => tohtml(tr('Client / Domains / Edit Subdomain')),
    'TR_SUBDOMAIN'              => tohtml(tr('Subdomain')),
    'TR_SUBDOMAIN_NAME'         => tohtml(tr('Subdomain name')),
    'TR_DOCUMENT_ROOT'          => tohtml(tr('Document root')),
    'TR_DOCUMENT_ROOT_TOOLTIP'  => tohtml(tr("You can set an alternative document root. This is mostly needed when using a PHP framework such as Symfony. Note that the new document root will live inside the default  `/htdocs' document root. Be aware that the directory for the new document root must pre-exist.")),
    'TR_CHOOSE_DIR'             => tohtml(tr('Choose dir')),
    'TR_URL_FORWARDING'         => tohtml(tr('URL forwarding')),
    'TR_FORWARD_TO_URL'         => tohtml(tr('Forward to URL')),
    'TR_URL_FORWARDING_TOOLTIP' => tohtml(tr('Allows to forward any request made to this domain to a specific URL.')),
    'TR_YES'                    => tohtml(tr('Yes')),
    'TR_NO'                     => tohtml(tr('No')),
    'TR_HTTP'                   => tohtml('http://'),
    'TR_HTTPS'                  => tohtml('https://'),
    'TR_FORWARD_TYPE'           => tohtml(tr('Forward type')),
    'TR_301'                    => tohtml('301'),
    'TR_302'                    => tohtml('302'),
    'TR_303'                    => tohtml('303'),
    'TR_307'                    => tohtml('307'),
    'TR_PROXY'                  => tohtml('PROXY'),
    'TR_PROXY_PRESERVE_HOST'    => tohtml(tr('Preserve Host')),
    'TR_WILDCARD_ALIAS_TOOLTIP' => tohtml(tr("If enabled, a wildcard alias entry such as '*.sub.domain.tld' will be added. This option is most suitable for software that provide multisite feature such as the Wordpress CMS. Be aware that the control panel doesn't check for possible conflicts with other subdomains."), 'htmlAttr'),
    'TR_WILDCARD_ALIAS'         => tohtml(tr('Wildcard alias')),
    'TR_UPDATE'                 => tohtml(tr('Update'), 'htmlAttr'),
    'TR_CANCEL'                 => tohtml(tr('Cancel'))
]);

EventAggregator::getInstance()->registerListener(
    Events::onGetJsTranslations,
    function (Event $e) {
        $translations = $e->getParam('translations');
        $translations['core']['close'] = tr('Close');
        $translations['core']['ftp_directories'] = tr(
            'Select your own document root'
        );
    }
);

generateNavigation($tpl);
client_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(
    Events::onClientScriptEnd, ['templateEngine' => $tpl]
);
$tpl->prnt();

unsetMessages();
