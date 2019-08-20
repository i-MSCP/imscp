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

use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\Exception\Exception;
use iMSCP\TemplateEngine;
use iMSCP\Uri\UriException;
use iMSCP\Uri\UriRedirect;

/**
 * Check input data
 *
 * @return void
 */
function reseller_checkData()
{
    if (!isset($_POST['dmn_name']) || $_POST['dmn_name'] === '') {
        set_page_message(tr('Domain name cannot be empty.'), 'error');
        return;
    }

    $dmnName = mb_strtolower(clean_input($_POST['dmn_name']));

    global $dmnNameValidationErrMsg;
    if (!isValidDomainName($dmnName)) {
        set_page_message($dmnNameValidationErrMsg, 'error');
        return;
    }

    // www is considered as an alias of the domain
    while (strpos($dmnName, 'www.') !== false) {
        $dmnName = substr($dmnName, 4);
    }

    $asciiDmnName = encode_idna($dmnName);

    if (imscp_domain_exists($asciiDmnName, $_SESSION['user_id'])) {
        set_page_message(
            tohtml(tr('Domain %s is unavailable.'), $dmnName),
            'error'
        );
        return;
    }

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

            if ($uri->getHost() == $asciiDmnName
                && ($uri->getPath() == '/' && in_array($uri->getPort(), ['', 80, 443]))
            ) {
                throw new Exception(
                    tr('Forward URL %s is not valid.', $forwardUrl) . ' ' .
                    tr('Domain %s cannot be forwarded on itself.', $dmnName)
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
            return;
        }
    }

    $wildcardAlias = isset($_POST['wildcard_alias'])
    && in_array($_POST['wildcard_alias'], ['yes', 'no'], true)
        ? $_POST['wildcard_alias'] : 'no';

    if ((!isset($_POST['datepicker'])
            || $_POST['datepicker'] === '')
        && !isset($_POST['never_expire'])
    ) {
        set_page_message(
            tohtml(tr('Domain expiration date must be filled.')), 'error'
        );
        return;
    }

    $dmnExpire = isset($_POST['datepicker'])
        ? @strtotime(clean_input($_POST['datepicker'])) : 0;

    if ($dmnExpire === false) {
        set_page_message('Invalid expiration date.', 'error');
        return;
    }

    $hpId = isset($_POST['dmn_tpl']) ? clean_input($_POST['dmn_tpl']) : 0;
    $customizeHp = $hpId > 0 && isset($_POST['chtpl']) ? $_POST['chtpl'] : '_no_';

    if ($hpId == 0 || $customizeHp == '_yes_') {
        $_SESSION['dmn_name'] = $asciiDmnName;
        $_SESSION['dmn_expire'] = $dmnExpire;
        $_SESSION['dmn_url_forward'] = $forwardUrl;
        $_SESSION['dmn_type_forward'] = $forwardType;
        $_SESSION['dmn_host_forward'] = $forwardHost;
        $_SESSION['dmn_wildcard_alias'] = $wildcardAlias;
        $_SESSION['dmn_tpl'] = $hpId;
        $_SESSION['chtpl'] = '_yes_';
        $_SESSION['step_one'] = '_yes_';
        redirectTo('user_add2.php');
    }

    if (!reseller_limits_check($_SESSION['user_id'], $hpId)) {
        set_page_message(
            tohtml(tr('Hosting plan limits exceed reseller limits.')),
            'error'
        );
        return;
    }

    $_SESSION['dmn_name'] = $asciiDmnName;
    $_SESSION['dmn_expire'] = $dmnExpire;
    $_SESSION['dmn_url_forward'] = $forwardUrl;
    $_SESSION['dmn_type_forward'] = $forwardType;
    $_SESSION['dmn_host_forward'] = $forwardHost;
    $_SESSION['dmn_wildcard_alias'] = $wildcardAlias;
    $_SESSION['dmn_tpl'] = $hpId;
    $_SESSION['chtpl'] = $customizeHp;
    $_SESSION['step_one'] = '_yes_';
    redirectTo('user_add3.php');
}

/**
 * Show first page of add user with data
 *
 * @param TemplateEngine $tpl Template engine
 * @return void
 */
function reseller_generatePage(TemplateEngine $tpl)
{
    $forwardType = (
        isset($_POST['forward_type'])
        && in_array(
            $_POST['forward_type'], ['301', '302', '303', '307', 'proxy'], true
        )
    ) ? $_POST['forward_type'] : '302';
    $forwardHost = $forwardType == 'proxy' && isset($_POST['forward_host'])
        ? 'On' : 'Off';
    $wildcardAlias = isset($_POST['wildcard_alias'])
    && in_array($_POST['wildcard_alias'], ['yes', 'no'], true)
        ? $_POST['wildcard_alias'] : 'no';

    $tpl->assign([
        'DOMAIN_NAME_VALUE'    => isset($_POST['dmn_name'])
            ? tohtml($_POST['dmn_name']) : '',
        'FORWARD_URL_YES'      => isset($_POST['url_forwarding'])
        && $_POST['url_forwarding'] == 'yes'
            ? ' checked' : '',
        'FORWARD_URL_NO'       => isset($_POST['url_forwarding'])
        && $_POST['url_forwarding'] == 'yes'
            ? '' : ' checked',
        'HTTP_YES'             => isset($_POST['forward_url_scheme'])
        && $_POST['forward_url_scheme'] == 'http://'
            ? ' selected' : '',
        'HTTPS_YES'            => isset($_POST['forward_url_scheme'])
        && $_POST['forward_url_scheme'] == 'https://'
            ? ' selected' : '',
        'FORWARD_URL'          => isset($_POST['forward_url'])
            ? tohtml($_POST['forward_url']) : '',
        'FORWARD_TYPE_301'     => $forwardType == '301' ? ' checked' : '',
        'FORWARD_TYPE_302'     => $forwardType == '302' ? ' checked' : '',
        'FORWARD_TYPE_303'     => $forwardType == '303' ? ' checked' : '',
        'FORWARD_TYPE_307'     => $forwardType == '307' ? ' checked' : '',
        'FORWARD_TYPE_PROXY'   => $forwardType == 'proxy' ? ' checked' : '',
        'FORWARD_HOST'         => $forwardHost == 'On' ? ' checked' : '',
        'WILDCARD_ALIAS_YES'   => $wildcardAlias == 'yes' ? ' checked' : '',
        'WILDCARD_ALIAS_NO'    => $wildcardAlias == 'no' ? ' checked' : '',
        'DATEPICKER_VALUE'     => isset($_POST['datepicker'])
            ? tohtml($_POST['datepicker']) : '',
        'DATEPICKER_DISABLED'  => isset($_POST['datepicker'])
            ? '' : ' disabled',
        'NEVER_EXPIRE_CHECKED' => isset($_POST['datepicker'])
            ? '' : ' checked',
        'CHTPL1_VAL'           => isset($_POST['chtpl'])
        && $_POST['chtpl'] == '_yes_'
            ? ' checked' : '',
        'CHTPL2_VAL'           => isset($_POST['chtpl'])
        && $_POST['chtpl'] == '_yes_'
            ? '' : ' checked'
    ]);

    $stmt = exec_query(
        '
            SELECT id, name
            FROM hosting_plans
            WHERE reseller_id = ?
            AND status = ?
            ORDER BY name
        ',
        [$_SESSION['user_id'], '1']
    );

    if (!$stmt->rowCount()) {
        $tpl->assign('HOSTING_PLAN_ENTRIES_BLOCK', '');
        return;
    }

    while ($row = $stmt->fetchRow()) {
        $hpId = isset($_POST['dmn_tpl']) ? $_POST['dmn_tpl'] : '';
        $tpl->assign([
            'HP_NAME'     => tohtml($row['name']),
            'HP_ID'       => tohtml($row['id'], 'htmlAttr'),
            'HP_SELECTED' => $row['id'] == $hpId ? ' selected' : ''
        ]);
        $tpl->parse('HOSTING_PLAN_ENTRY_BLOCK', '.hosting_plan_entry_block');
    }
}

require 'imscp-lib.php';

check_login('reseller');
EventAggregator::getInstance()->dispatch(Events::onResellerScriptStart);

if (!empty($_POST)) {
    reseller_checkData();
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'                       => 'shared/layouts/ui.tpl',
    'page'                         => 'reseller/user_add1.tpl',
    'page_message'                 => 'layout',
    'hosting_plan_entries_block'   => 'page',
    'hosting_plan_entry_block'     => 'hosting_plan_entries_block',
    'customize_hosting_plan_block' => 'hosting_plan_entries_block'
]);
$tpl->assign([
    'TR_PAGE_TITLE'             => tohtml(tr('Reseller / Customers / Add Customer')),
    'TR_CORE_DATA'              => tohtml(tr('Domain data')),
    'TR_DOMAIN_NAME'            => tohtml(tr('Domain name')),
    'TR_DOMAIN_EXPIRE'          => tohtml(tr('Domain expiration date')),
    'TR_EXPIRE_CHECKBOX'        => tohtml(tr('Never')),
    'TR_CHOOSE_HOSTING_PLAN'    => tohtml(tr('Choose hosting plan')),
    'TR_PERSONALIZE_TEMPLATE'   => tohtml(tr('Personalise template')),
    'TR_URL_FORWARDING'         => tohtml(tr('URL forwarding')),
    'TR_URL_FORWARDING_TOOLTIP' => tohtml(tr('Allows to forward any request made to this domain to a specific URL.'), 'htmlAttr'),
    'TR_FORWARD_TO_URL'         => tohtml(tr('Forward to URL')),
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
    'TR_WILDCARD_ALIAS_TOOLTIP' => tohtml(tr("If enabled, a wildcard alias entry such as '*.domain.tld' will be added in the Web server configuration. This option is most suitable for software that provide multisite feature such as the Wordpress CMS. Be aware that the control panel won't check for possible conflicts with subdomains."), 'htmlAttr'),
    'TR_WILDCARD_ALIAS'         => tohtml(tr('Wildcard alias')),
    'TR_NEXT_STEP'              => tohtml(tr('Next step'), 'htmlAttr')
]);

generateNavigation($tpl);
reseller_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventAggregator::getInstance()->dispatch(
    Events::onResellerScriptEnd, ['templateEngine' => $tpl]
);
$tpl->prnt();
