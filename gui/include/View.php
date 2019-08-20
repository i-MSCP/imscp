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

/** @noinspection
 * PhpUnusedParameterInspection
 * PhpUnhandledExceptionInspection
 * PhpDocMissingThrowsInspection
 */

use iMSCP\Assertion\CallbackAssertion;
use iMSCP\Assertion\ClientHasFtpFeatureAssertion;
use iMSCP\Assertion\ClientHasMailFeatureAssertion;
use iMSCP\Assertion\ClientHasSqlFeatureAssertion;
use iMSCP\Assertion\ClientHasWebstatsFeatureAssertion;
use iMSCP\Event\EventAggregator;
use iMSCP\Event\Events;
use iMSCP\Exception\Exception;
use iMSCP\Registry;
use iMSCP\TemplateEngine;

// Common

/**
 * Generates list of available languages
 *
 * @param TemplateEngine $tpl
 * @param string $selectedLanguage Selected language
 * @return void
 */
function generateLanguagesList(TemplateEngine $tpl, $selectedLanguage)
{
    foreach (i18n_getAvailableLanguages() as $language) {
        $tpl->assign([
            'LANG_VALUE'    => tohtml($language['locale'], 'htmlAttr'),
            'LANG_SELECTED' => $language['locale'] == $selectedLanguage
                ? ' selected' : '',
            'LANG_NAME'     => tohtml($language['language'])
        ]);
        $tpl->parse('DEF_LANGUAGE', '.def_language');
    }
}

/**
 * Generate lists for days, months and years
 *
 * @param TemplateEngine $tpl
 * @param int $day Selected day
 * @param int $month Selected month (date(
 * @param int $year Selected year (4 digits expected)
 * @param int $nPastYears Number of past years to display in years select list
 * @return void
 */
function generateDMYlists(TemplateEngine $tpl, $day, $month, $year, $nPastYears)
{
    if (!in_array($month, range(1, 12))) {
        $month = date('n');
    }

    if ($tpl->is_dynamic_tpl('day_list')) {
        $nDays = date('t', mktime(0, 0, 0, $month, 1, $year));

        // 0 = all days
        if (!in_array($day, range(0, $nDays))) {
            $day = 0;
        }

        foreach (range(0, $nDays) as $lday) {
            $tpl->assign([
                'OPTION_SELECTED' => $lday == $day ? ' selected' : '',
                'VALUE'           => tohtml($lday, 'htmlAttr'),
                'HUMAN_VALUE'     => $lday == 0 ? tohtml(tr('All')) : tohtml($lday)
            ]);
            $tpl->parse('DAY_LIST', '.day_list');
        }
    }

    foreach (range(1, 12) as $lmonth) {
        $tpl->assign([
            'OPTION_SELECTED' => ($lmonth == $month) ? ' selected' : '',
            'MONTH_VALUE'     => tohtml($lmonth)
        ]);
        $tpl->parse('MONTH_LIST', '.month_list');
    }

    $curYear = date('Y');

    foreach (range($curYear - $nPastYears, $curYear) as $lyear) {
        $tpl->assign([
            'OPTION_SELECTED' => ($lyear == $year) ? ' selected' : '',
            'YEAR_VALUE'      => tohtml($lyear, 'htmlAttr'),
        ]);
        $tpl->parse('YEAR_LIST', '.year_list');
    }
}

/**
 * Generate navigation
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function generateNavigation(TemplateEngine $tpl)
{
    EventAggregator::getInstance()->dispatch(
        Events::onBeforeGenerateNavigation, ['templateEngine' => $tpl]
    );

    /** @var $navigation Zend_Navigation */
    $navigation = Registry::get('navigation');

    $cfg = Registry::get('config');

    if ($cfg['IMSCP_SUPPORT_SYSTEM']) {
        // Dynamic links (All levels)
        $tpl->assign([
            'SUPPORT_SYSTEM_PATH'   => 'ticket_system.php',
            'SUPPORT_SYSTEM_TARGET' => '_self'
        ]);
    }

    // Custom menus
    if ($customMenus = getCustomMenus($_SESSION['user_type'])) {
        foreach ($customMenus as $customMenu) {
            $navigation->addPage([
                'order'  => $customMenu['menu_order'],
                'label'  => tohtml($customMenu['menu_name']),
                'uri'    => $customMenu['menu_link'],
                'target' => !empty($customMenu['menu_target']
                    ? tohtml($customMenu['menu_target']) : '_self'),
                'class'  => 'custom_link'
            ]);
        }
    }

    $currentUriPath = parse_url(
        (new Zend_Controller_Request_Http)->getRequestUri(), PHP_URL_PATH
    );
    $acl = new Zend_Acl();
    $acl->addRole($_SESSION['user_type']);

    /** @var Zend_Navigation_Page_Uri $page */
    foreach ($navigation as $page) {
        $page->setCurrentUriPath($currentUriPath);
        $resource = $page->getResource();
        $assertion = $page->get('assertion');
        if ($resource && $assertion) {
            $acl->addResource($resource);
            $acl->allow(
                $_SESSION['user_type'],
                $resource,
                $page->getPrivilege(),
                new $assertion
            );
        }

        foreach (new RecursiveIteratorIterator(
                     $page, RecursiveIteratorIterator::SELF_FIRST) as $childPage
        ) {
            $childPage->setCurrentUriPath($currentUriPath);
            $resource = $childPage->getResource();
            $assertion = $childPage->get('assertion');
            if ($resource && $assertion) {
                $acl->addResource($resource);
                $acl->allow(
                    $_SESSION['user_type'],
                    $resource,
                    $childPage->getPrivilege(),
                    new $assertion
                );
                continue;
            }

            // Only for backward compatibility with plugins. Will be removed in
            //a later release.
            if ($callbacks = $childPage->get('privilege_callback')) {
                $callbacks = isset($callbacks['name'])
                    ? [$callbacks] : $callbacks;
                $resource = $childPage->getUri();
                $assertion = new CallbackAssertion(
                    function () use ($callbacks) {
                        foreach ($callbacks as $callback) {
                            if (!call_user_func_array(
                                $callback['name'], isset($callback['param'])
                                ? (array)$callback['param'] : [])
                            ) {
                                return false;
                            }
                        }

                        return true;
                    }
                );
                $childPage->setResource($resource);
                $acl->addResource($resource);
                $acl->allow(
                    $_SESSION['user_type'],
                    $resource,
                    $childPage->getPrivilege(),
                    $assertion
                );
            }
        }
    }

    // Dynamic pages (user level)
    if ($_SESSION['user_type'] === 'user') {
        if ($sqlAdminToolPackages = getSqlAdminToolPackages()) {
            $acl->addResource('sql_admin_tool');
            $acl->allow(
                $_SESSION['user_type'],
                'sql_admin_tool',
                NULL,
                new ClientHasSqlFeatureAssertion()
            );
            $parentPage = $navigation->findOneBy('class', 'database');

            foreach ($sqlAdminToolPackages as $sqlAdminToolPackage) {
                $parentPage->addPage([
                    'label'    => $sqlAdminToolPackage,
                    'uri'      => '/' . strtolower($sqlAdminToolPackage) . '/',
                    'target'   => '_blank',
                    'resource' => 'sql_admin_tool'
                ]);
            }
        }

        if ($webFtpClientPackages = getWebFtpClientPackages()) {
            $acl->addResource('web_ftp');
            $acl->allow(
                $_SESSION['user_type'],
                'web_ftp',
                NULL,
                new ClientHasFtpFeatureAssertion()
            );
            $parentPage = $navigation->findOneBy('class', 'ftp');

            foreach ($webFtpClientPackages as $webFtpClientPackage) {
                $parentPage->addPage([
                    'label'    => $webFtpClientPackage,
                    'uri'      => '/' . strtolower($webFtpClientPackage) . '/',
                    'target'   => '_blank',
                    'resource' => 'web_ftp'
                ]);
            }
        }

        if ($webmailClientPackages = getWebmailClientPackages()) {
            $acl->addResource('webmail');
            $acl->allow(
                $_SESSION['user_type'],
                'webmail',
                NULL,
                new ClientHasMailFeatureAssertion()
            );
            $parentPage = $navigation->findOneBy('class', 'email');

            foreach ($webmailClientPackages as $webmailClientPackage) {
                $parentPage->addPage([
                    'label'    => $webmailClientPackage,
                    'uri'      => '/' . strtolower($webmailClientPackage) . '/',
                    'target'   => '_blank',
                    'resource' => 'webmail'
                ]);
            }
        }

        if ($webStatisticPackages = getWebStatisticPackages()) {
            $acl->addResource('webstats');
            $acl->allow(
                $_SESSION['user_type'],
                'webstats',
                NULL,
                new ClientHasWebstatsFeatureAssertion()
            );
            $parentPage = $navigation->findOneBy('class', 'statistics');

            foreach ($webStatisticPackages as $webStatisticPackage) {
                $parentPage->addPage([
                    'label'    => $webStatisticPackage,
                    'uri'      => decode_idna(get_domain_default_props(
                            $_SESSION['user_id'])['domain_name']
                        ) . '/' . strtolower($webStatisticPackage) . '/',
                    'target'   => '_blank',
                    'resource' => 'webstats'
                ]);
            }
        }
    }

    /** @var Zend_View_Helper_Navigation $navigationHelper */
    $navigationHelper = new Zend_View_Helper_Navigation();
    $navigationHelper->setContainer($navigation)
        ->setAcl($acl)
        ->setRole($_SESSION['user_type'])
        ->setView(new Zend_View());

    /** @noinspection PhpUndefinedFieldInspection */
    $tpl->navigation = $navigationHelper;

    EventAggregator::getInstance()->dispatch(
        Events::onAfterGenerateNavigation, ['templateEngine' => $tpl]
    );
}

/**
 * Get custom menus for the given user
 *
 * @param string $userLevel User type (admin, reseller or user)
 * @return null|[] Array containing custom menus definitions or NULL in case no
 *                 custom menu is found
 */
function getCustomMenus($userLevel)
{
    if ($userLevel == 'admin') {
        $param = 'A';
    } elseif ($userLevel == 'reseller') {
        $param = 'R';
    } elseif ($userLevel == 'user') {
        $param = 'C';
    } else {
        throw new Exception(
            "Unknown user level '$userLevel' for getCustomMenus() function."
        );
    }

    $stmt = exec_query(
        'SELECT * FROM custom_menus WHERE menu_level LIKE ?', ["%$param%"]
    );
    if ($stmt->rowCount()) {
        return $stmt->fetchAll();
    }

    return NULL;
}

// Admin

/**
 * Generate administrator list
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function gen_admin_list(TemplateEngine $tpl)
{
    $stmt = execute_query(
        "
          SELECT t1.admin_id, t1.admin_name, t1.domain_created,
            t2.admin_name AS created_by
          FROM admin AS t1
          LEFT JOIN admin AS t2 ON (t1.created_by = t2.admin_id)
          WHERE t1.admin_type = 'admin'
          ORDER BY t1.admin_name ASC
        "
    );

    if (!$stmt->rowCount()) {
        $tpl->assign('ADMINISTRATOR_LIST', '');
        return;
    }

    $tpl->assign('ADMINISTRATOR_MESSAGE', '');

    $cfg = Registry::get('config');

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        $tpl->assign([
            'ADMINISTRATOR_USERNAME'   => tohtml($row['admin_name']),
            'ADMINISTRATOR_CREATED_ON' => tohtml(($row['domain_created'] == 0)
                ? tr('N/A') : date($cfg['DATE_FORMAT'], $row['domain_created'])
            ),
            'ADMINISTRATOR_CREATED_BY' => tohtml(
                is_null($row['created_by']) ? tr('System') : $row['created_by']
            ),
            'ADMINISTRATOR_ID'         => $row['admin_id']
        ]);

        if (is_null($row['created_by'])
            || $row['admin_id'] == $_SESSION['user_id']
        ) {
            $tpl->assign('ADMINISTRATOR_DELETE_LINK', '');
        } else {
            $tpl->parse('ADMINISTRATOR_DELETE_LINK', 'administrator_delete_link');
        }

        $tpl->parse('ADMINISTRATOR_ITEM', '.administrator_item');
    }
}

/**
 * Generate reseller list
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function gen_reseller_list(TemplateEngine $tpl)
{
    $stmt = execute_query(
        "
          SELECT t1.admin_id, t1.admin_name, t1.domain_created,
            t2.admin_name AS created_by
          FROM admin AS t1
          LEFT JOIN admin AS t2 ON (t1.created_by = t2.admin_id)
          WHERE t1.admin_type = 'reseller'
          ORDER BY t1.admin_name ASC
        "
    );

    if (!$stmt->rowCount()) {
        $tpl->assign('RESELLER_LIST', '');
        return;
    }

    $tpl->assign('RESELLER_MESSAGE', '');

    $cfg = Registry::get('config');

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        $tpl->assign([
            'RESELLER_USERNAME'   => tohtml($row['admin_name']),
            'RESELLER_CREATED_ON' => tohtml(($row['domain_created'] == 0)
                ? tr('N/A') : date($cfg['DATE_FORMAT'], $row['domain_created'])
            ),
            'RESELLER_CREATED_BY' => tohtml(
                is_null($row['created_by']) ? tr('Unknown') : $row['created_by']
            ),
            'RESELLER_ID'         => $row['admin_id']
        ]);
        $tpl->parse('RESELLER_ITEM', '.reseller_item');
    }
}

// Admin/reseller

/**
 * Get count and search queries for users search
 *
 * @param int $sLimit Start limit
 * @param int $eLimit End limit
 * @param string|null $searchField Field to search
 * @param string|null $searchValue Value to search
 * @param string|null $searchStatus Status to search
 * @return array Array containing count and search queries
 */
function get_search_user_queries(
    $sLimit, $eLimit, $searchField = NULL, $searchValue = NULL,
    $searchStatus = NULL
)
{
    $sLimit = intval($sLimit);
    $eLimit = intval($eLimit);
    $where = '';

    if ($_SESSION['user_type'] == 'reseller') {
        $where .= 'WHERE t2.created_by = ' . intval($_SESSION['user_id']);
    }

    if ($searchStatus !== NULL && $searchStatus != 'anything') {
        $where .= (($where == '') ? 'WHERE ' : ' AND ') . 't1.domain_status' . (
            ($searchStatus == 'ok' || $searchStatus == 'disabled')
                ? ' = ' . quoteValue($searchStatus)
                : " NOT IN (
                        'ok', 'disabled', 'toadd', 'tochange', 'toenable',
                        'torestore', 'todisable', 'todelete'
                    )
                "
            );
    }

    if ($searchField !== NULL && $searchField != 'anything') {
        if ($searchField == 'domain_name') {
            $where .= (($where == '') ? 'WHERE ' : ' AND ') . 't1.domain_name';
        } elseif ($_SESSION['user_type'] == 'admin'
            && $searchField == 'reseller_name'
        ) {
            $where .= (($where == '') ? 'WHERE ' : ' AND ') . 't3.admin_name';
        } elseif (in_array(
            $searchField,
            ['fname', 'lname', 'firm', 'city', 'state', 'country'], true
        )) {
            $where .= ($where == '' ? 'WHERE ' : ' AND ') . "t2.$searchField";
        } else {
            showBadRequestErrorPage();
        }

        $searchValue = str_replace(
            ['!', '_', '%'], ['!!!', '!_', '!%'], $searchValue
        );
        $where .= ' LIKE ' . quoteValue(
                '%' . ($searchField == 'domain_name'
                    ? encode_idna($searchValue) : $searchValue) . '%'
            ) . " ESCAPE '!'";
    }

    return [
        "
            SELECT COUNT(t1.domain_id)
            FROM domain AS t1
            JOIN admin AS t2 ON(t2.admin_id = t1.domain_admin_id)
            JOIN admin AS t3 ON(t3.admin_id = t2.created_by)
            $where
        ",
        "
            SELECT t1.domain_id, t1.domain_name, t1.domain_created,
                t1.domain_status, t1.domain_disk_limit, t1.domain_disk_usage,
                t2.admin_id, t2.admin_status, t3.admin_name AS reseller_name
            FROM domain AS t1
            JOIN admin AS t2 ON(t2.admin_id = t1.domain_admin_id)
            JOIN admin AS t3 ON(t3.admin_id = t2.created_by)
            $where
            ORDER BY t1.domain_name ASC
            LIMIT $sLimit, $eLimit
        "
    ];
}

/**
 * Generate user search fields
 *
 * @param TemplateEngine $tpl
 * @param string|null $searchField Field to search
 * @param string|null $searchValue Value to search
 * @param string|null $searchStatus Status to search
 * @return void
 */
function gen_search_user_fields(
    TemplateEngine $tpl, $searchField = NULL, $searchValue = NULL,
    $searchStatus = NULL
)
{
    $none = $domain = $customerId = $firstname = $lastname = $company = $city =
    $state = $country = $resellerName = $anything = $ok = $suspended =
    $error = '';

    if ($searchField === NULL
        && $searchValue === NULL
        && $searchStatus === NULL
    ) {
        $none = $anything = ' selected';
        $tpl->assign('SEARCH_VALUE', '');
    } else {
        if ($searchField == NULL || $searchField == 'anything') {
            $none = ' selected';
        } elseif ($searchField == 'domain_name') {
            $domain = ' selected';
        } elseif ($searchField == 'fname') {
            $firstname = ' selected';
        } elseif ($searchField == 'lname') {
            $lastname = ' selected';
        } elseif ($searchField == 'firm') {
            $company = ' selected';
        } elseif ($searchField == 'city') {
            $city = ' selected';
        } elseif ($searchField == 'state') {
            $state = ' selected';
        } elseif ($searchField == 'country') {
            $country = ' selected';
        } elseif ($_SESSION['user_type'] == 'admin'
            && $searchField == 'reseller_name'
        ) {
            $resellerName = ' selected';
        } else {
            showBadRequestErrorPage();
        }

        if ($searchStatus === NULL || $searchStatus == 'anything') {
            $anything = 'selected ';
        } elseif ($searchStatus == 'ok') {
            $ok = ' selected';
        } elseif ($searchStatus == 'disabled') {
            $suspended = ' selected';
        } elseif (($searchStatus == 'error')) {
            $error = ' selected';
        } else {
            showBadRequestErrorPage();
        }

        $tpl->assign(
            'SEARCH_VALUE',
            $searchValue !== NULL ? tohtml($searchValue, 'htmlAttr') : ''
        );
    }

    $tpl->assign([
        # search_field select
        'CLIENT_NONE_SELECTED'          => $none,
        'CLIENT_DOMAIN_NAME_SELECTED'   => $domain,
        'CLIENT_FIRST_NAME_SELECTED'    => $firstname,
        'CLIENT_LAST_NAME_SELECTED'     => $lastname,
        'CLIENT_COMPANY_SELECTED'       => $company,
        'CLIENT_CITY_SELECTED'          => $city,
        'CLIENT_STATE_SELECTED'         => $state,
        'CLIENT_COUNTRY_SELECTED'       => $country,
        'CLIENT_RESELLER_NAME_SELECTED' => $resellerName,
        # search_status select
        'CLIENT_ANYTHING_SELECTED'      => $anything,
        'CLIENT_OK_SELECTED'            => $ok,
        'CLIENT_DISABLED_SELECTED'      => $suspended,
        'CLIENT_ERROR_SELECTED'         => $error
    ]);
}

/**
 * Generates user domain_aliases_list
 *
 * @param TemplateEngine $tpl
 * @param int $domainId Domain unique identifier
 * @return void
 */
function gen_user_domain_aliases_list(TemplateEngine $tpl, $domainId)
{
    $tpl->assign('CLIENT_DOMAIN_ALIAS_BLK', '');

    if (!isset($_SESSION['client_domain_aliases_switch'])
        || $_SESSION['client_domain_aliases_switch'] != 'show'
    ) {
        return;
    }

    $stmt = exec_query(
        '
            SELECT alias_name
            FROM domain_aliasses
            WHERE domain_id = ?
            ORDER BY alias_name ASC
        ',
        [$domainId]
    );

    if (!$stmt->rowCount()) {
        return;
    }

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        $tpl->assign([
            'CLIENT_DOMAIN_ALIAS_URL' => tohtml($row['alias_name'], 'htmlAttr'),
            'CLIENT_DOMAIN_ALIAS'     => tohtml(decode_idna($row['alias_name']))
        ]);
        $tpl->parse('CLIENT_DOMAIN_ALIAS_BLK', '.client_domain_alias_blk');
    }
}

/**
 * Generate user list
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function gen_user_list(TemplateEngine $tpl)
{
    $cfg = Registry::get('config');

    if (!empty($_POST)) {
        if (!isset($_POST['search_status'])
            || !isset($_POST['search_field'])
            || !isset($_POST['client_domain_aliases_switch'])
            || !in_array(
                $_POST['client_domain_aliases_switch'], ['show', 'hide']
            )
        ) {
            showBadRequestErrorPage();
        }

        $_SESSION['client_domain_aliases_switch'] = clean_input(
            $_POST['client_domain_aliases_switch']
        );
        $_SESSION['search_field'] = clean_input($_POST['search_field']);
        $_SESSION['search_value'] = isset($_POST['search_value'])
            ? clean_input($_POST['search_value']) : '';
        $_SESSION['search_status'] = clean_input($_POST['search_status']);
    } elseif (!isset($_GET['psi'])) {
        unset(
            $_SESSION['search_field'], $_SESSION['search_value'],
            $_SESSION['search_status']
        );
    }

    $sLimit = isset($_GET['psi']) ? intval($_GET['psi']) : 0;
    $eLimit = intval($cfg['DOMAIN_ROWS_PER_PAGE']);

    if (!empty($_POST)) {
        list($cQuery, $sQuery) = get_search_user_queries(
            $sLimit, $eLimit, $_SESSION['search_field'],
            $_SESSION['search_value'], $_SESSION['search_status']
        );
        gen_search_user_fields(
            $tpl, $_SESSION['search_field'], $_SESSION['search_value'],
            $_SESSION['search_status']
        );
    } else {
        list($cQuery, $sQuery) = get_search_user_queries($sLimit, $eLimit);
        gen_search_user_fields($tpl);
    }

    if (isset($_SESSION['client_domain_aliases_switch'])) {
        $tpl->assign([
            'CLIENT_DOMAIN_ALIASES_SWITCH_VALUE'                              =>
                $_SESSION['client_domain_aliases_switch'],
            $_SESSION['client_domain_aliases_switch'] == 'show'
                ? 'CLIENT_DOMAIN_ALIASES_SHOW' : 'CLIENT_DOMAIN_ALIASES_HIDE' =>
                ''
        ]);
    } else {
        $tpl->assign([
            'CLIENT_DOMAIN_ALIASES_SWITCH_VALUE' => 'hide',
            'CLIENT_DOMAIN_ALIASES_HIDE'         => ''
        ]);
    }

    $rowCount = execute_query($cQuery)->fetchRow(PDO::FETCH_COLUMN);

    if ($rowCount == 0) {
        if (!empty($_POST)) {
            $tpl->assign([
                'CLIENT_DOMAIN_ALIASES_SWITCH' => '',
                'CLIENT_LIST'                  => '',
            ]);
        } else {
            $tpl->assign([
                'CLIENT_SEARCH_FORM' => '',
                'CLIENT_LIST'        => ''
            ]);
        }
        return;
    }

    if ($sLimit == 0) {
        $tpl->assign('CLIENT_SCROLL_PREV', '');
    } else {
        $prevSi = $sLimit - $eLimit;
        $tpl->assign([
            'CLIENT_SCROLL_PREV_GRAY' => '',
            'CLIENT_PREV_PSI'         => $prevSi > 0 ? $prevSi : 0
        ]);
    }

    $nextSi = $sLimit + $eLimit;

    if ($nextSi + 1 > $rowCount) {
        $tpl->assign('CLIENT_SCROLL_NEXT', '');
    } else {
        $tpl->assign([
            'CLIENT_SCROLL_NEXT_GRAY' => '',
            'CLIENT_NEXT_PSI'         => $nextSi
        ]);
    }

    $tpl->assign('CLIENT_MESSAGE', '');
    $stmt = execute_query($sQuery);

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        $statusOk = true;
        $statusTxt = $statusTooltip = translate_dmn_status(
            $row['admin_status'] != 'ok'
                ? $row['admin_status'] : $row['domain_status']
        );

        if ($row['admin_status'] == 'ok' && $row['domain_status'] == 'ok') {
            $class = 'i_ok';
            $statusTooltip = tr('Click to suspend this customer account.');
        } elseif ($row['domain_status'] == 'disabled') {
            $class = 'i_disabled';
            $statusTooltip = tr('Click to unsuspend this customer account.');
        } elseif (in_array($row['admin_status'], ['tochange', 'tochangepw'])
            || in_array(
                $row['domain_status'],
                [
                    'toadd', 'tochange', 'torestore', 'toenable', 'todisable',
                    'todelete'
                ]
            )
        ) {
            $class = 'i_reload';
            $statusOk = false;
        } else {
            $class = 'i_error';
            $statusTooltip = tr('An unexpected error occurred.');
            $statusOk = false;
        }

        $tpl->assign([
            'CLIENT_STATUS_CLASS'      => $class,
            'TR_CLIENT_STATUS_TOOLTIP' => $statusTooltip,
            'TR_CLIENT_STATUS'         => $statusTxt,
            'CLIENT_USERNAME'          => tohtml(
                decode_idna($row['domain_name']), 'htmlAttr'
            ),
            'CLIENT_DOMAIN_ID'         => $row['domain_id'],
            'CLIENT_ID'                => $row['admin_id'],
            'CLIENT_CREATED_ON'        => tohtml(
                $row['domain_created'] == 0
                    ? tr('N/A')
                    : date($cfg['DATE_FORMAT'], $row['domain_created'])
            ),
            'CLIENT_CREATED_BY'        => tohtml($row['reseller_name'])
        ]);

        if ($statusOk) {
            $tpl->assign([
                'CLIENT_DOMAIN_STATUS_NOT_OK' => '',
                'CLIENT_DOMAIN_URL'           => tohtml(
                    $row['domain_name'], 'htmlAttr'
                )
            ]);
            $tpl->parse('CLIENT_DOMAIN_STATUS_OK', 'client_domain_status_ok');
            $tpl->parse('CLIENT_RESTRICTED_LINKS', 'client_restricted_links');
        } else {
            $tpl->assign([
                'CLIENT_DOMAIN_STATUS_OK' => '',
                'CLIENT_RESTRICTED_LINKS' => ''
            ]);
            $tpl->parse('CLIENT_DOMAIN_STATUS_NOT_OK', 'client_domain_status_not_ok');
        }

        gen_user_domain_aliases_list($tpl, $row['domain_id']);
        $tpl->parse('CLIENT_ITEM', '.client_item');
    }
}

/**
 * Generate manage users page
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function get_admin_manage_users(TemplateEngine $tpl)
{
    gen_admin_list($tpl);
    gen_reseller_list($tpl);
    gen_user_list($tpl);
}

// Reseller

/**
 * Returns reseller Ip list
 *
 * @param TemplateEngine $tpl
 * @param int $resellerId Reseller unique identifier
 * @param int $domainIp Identifier of the selected domain IP
 * @return void
 */
function reseller_generate_ip_list(TemplateEngine $tpl, $resellerId, $domainIp)
{
    $stmt = exec_query(
        'SELECT reseller_ips FROM reseller_props WHERE reseller_id = ?',
        [$resellerId]
    );
    $row = $stmt->fetchRow();
    $resellerIps = explode(';', rtrim($row['reseller_ips'], ';'));

    $stmt = execute_query('SELECT * FROM server_ips');
    while ($row = $stmt->fetchRow()) {
        if (!in_array($row['ip_id'], $resellerIps)) {
            continue;
        }

        $tpl->assign([
            'IP_NUM'      => tohtml(($row['ip_number'] == '0.0.0.0')
                ? tr('Any') : $row['ip_number'], 'htmlAttr'),
            'IP_VALUE'    => $row['ip_id'],
            'IP_SELECTED' => $domainIp === $row['ip_id'] ? ' selected' : ''
        ]);
        $tpl->parse('IP_ENTRY', '.ip_entry');
    }
}

/**
 * Returns translation for jQuery DataTables plugin.
 *
 * @param bool $json Does the data must be encoded to JSON?
 * @param array $override Allow to override or add plugin translation
 * @return string|array
 */
function getDataTablesPluginTranslations($json = true, array $override = [])
{
    $tr = [
        'sLengthMenu'  => tr(
            'Show %s records per page',
            '
                <select>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="20">20</option>
                <option value="50">50</option>
                <option value="100">100</option>
                </select>
            '
        ),
        //'sLengthMenu' => tr('Show %s records per page', '_MENU_'),
        'zeroRecords'  => tr('Nothing found - sorry'),
        'info'         => tr(
            'Showing %s to %s of %s records', '_START_', '_END_', '_TOTAL_'
        ),
        'infoEmpty'    => tr('Showing 0 to 0 of 0 records'),
        'infoFiltered' => tr('(filtered from %s total records)', '_MAX_'),
        'search'       => tr('Search'),
        'paginate'     => ['previous' => tr('Previous'), 'next' => tr('Next')],
        'processing'   => tr('Loading data...')
    ];

    if (!empty($override)) {
        $tr = array_merge($tr, $override);
    }

    return ($json) ? json_encode($tr) : $tr;
}

/**
 * Show the given error page
 *
 * @param int $code Code of error page to show (400, 403 or 404)
 * @return void
 */
function showErrorPage($code)
{
    switch ($code) {
        case 400:
            $message = 'Bad Request';
            break;
        case 403:
            $message = 'Forbidden';
            break;
        case 404:
            $message = 'Not Found';
            break;
        case 405:
            $message = 'Method Not Allowed';
            break;
        default:
            throw new Exception(500, 'Unknown error page');
    }

    header("Status: $code $message");

    if (isset($_SERVER['HTTP_ACCEPT'])) {
        if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header("Content-type: application/json");
            exit(json_encode(['code' => $code, 'message' => $message]));
        }

        if (strpos($_SERVER['HTTP_ACCEPT'], 'application/xmls') !== false) {
            header("Content-type: text/xml;charset=utf-8");
            exit(<<<EOF
<?xml version="1.0" encoding="utf-8"?>
<response>
    <code>$code</code>
    <message>$message</message>
</response>
EOF
            );
        }
    }

    if (!is_xhr()) {
        /** @noinspection PhpIncludeInspection */
        include(Registry::get('config')['GUI_ROOT_DIR'] . "/public/errordocs/$code.html");
    }

    exit;
}

/**
 * Show 400 error page
 *
 * @return void
 */
function showBadRequestErrorPage()
{
    showErrorPage(400);
}

/**
 * Show 403 error page
 *
 * @return void
 */
function showForbiddenErrorPage()
{
    showErrorPage(403);
}

/**
 * Show 404 error page
 *
 * @return void
 */
function showNotFoundErrorPage()
{
    showErrorPage(404);
}

/**
 * Show 405 error page
 *
 * @return void
 */
function showMethodNotAllowedErrorPage()
{
    showErrorPage(405);
}
