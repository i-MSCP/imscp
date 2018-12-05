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

use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsManager;
use iMSCP_Exception as iMSCPException;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Registry as Registry;
use Zend_Navigation as Navigation;

// Common

/**
 * Generate logged from block
 *
 * @param  TemplateEngine $tpl
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 */
function generateLoggedFrom(TemplateEngine $tpl)
{
    $tpl->define_dynamic('logged_from', 'layout');

    if (!isset($_SESSION['logged_from'])
        || !isset($_SESSION['logged_from_id'])
    ) {
        $tpl->assign('LOGGED_FROM', '');
        return;
    }

    $tpl->assign([
        'YOU_ARE_LOGGED_AS' => tr('%1$s you are now logged as %2$s', $_SESSION['logged_from'], $_SESSION['user_logged']),
        'TR_GO_BACK'        => tr('Back')
    ]);
    $tpl->parse('LOGGED_FROM', 'logged_from');
}

/**
 * Generates list of available languages
 *
 * @param  TemplateEngine $tpl
 * @param  string $selectedLanguage Selected language
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 */
function generateLanguagesList(TemplateEngine $tpl, $selectedLanguage)
{
    foreach (i18n_getAvailableLanguages() as $language) {
        $tpl->assign([
            'LANG_VALUE'    => tohtml($language['locale'], 'htmlAttr'),
            'LANG_SELECTED' => ($language['locale'] == $selectedLanguage) ? ' selected' : '',
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
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
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
 * @throws Zend_Exception
 * @throws Zend_Navigation_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function generateNavigation(TemplateEngine $tpl)
{
    EventsManager::getInstance()->dispatch(Events::onBeforeGenerateNavigation, ['templateEngine' => $tpl]);

    $cfg = Registry::get('config');
    $tpl->define_dynamic([
        'main_menu'        => 'layout',
        'main_menu_block'  => 'main_menu',
        'menu'             => 'layout',
        'left_menu_block'  => 'menu',
        'breadcrumbs'      => 'layout',
        'breadcrumb_block' => 'breadcrumbs'
    ]);

    generateLoggedFrom($tpl);

    /** @var $navigation Navigation */
    $navigation = Registry::get('navigation');

    // Dynamic links (only at customer level)
    if ($_SESSION['user_type'] == 'user') {
        $domainProperties = get_domain_default_props($_SESSION['user_id']);
        $tpl->assign('WEBSTATS_PATH', 'http://' . decode_idna($domainProperties['domain_name']) . '/stats/');

        if (customerHasFeature('mail')) {
            $webmails = getWebmailList();

            if (!empty($webmails)) {
                $page1 = $navigation->findOneBy('class', 'email');
                $page2 = $navigation->findOneBy('class', 'webtools');

                foreach ($webmails as $webmail) {
                    $page = [
                        'label'  => tr('%s webmail', $webmail),
                        'uri'    => '/' . (($webmail == 'Roundcube') ? 'webmail' : strtolower($webmail)) . '/',
                        'target' => '_blank',
                    ];
                    $page1->addPage($page);
                    $page2->addPage($page);
                }
            }
        }
    }

    // Dynamic links (All levels)
    $tpl->assign([
        'SUPPORT_SYSTEM_PATH'   => 'ticket_system.php',
        'SUPPORT_SYSTEM_TARGET' => '_self'
    ]);

    // Remove support system page if feature is globally disabled
    if (!$cfg['IMSCP_SUPPORT_SYSTEM']) {
        $navigation->removePage($navigation->findOneBy('class', 'support'));
    }

    // Custom menus
    if (NULL != ($customMenus = getCustomMenus($_SESSION['user_type']))) {
        foreach ($customMenus as $customMenu) {
            $navigation->addPage([
                'order'  => $customMenu['menu_order'],
                'label'  => tohtml($customMenu['menu_name']),
                'uri'    => get_menu_vars($customMenu['menu_link']),
                'target' => (!empty($customMenu['menu_target']) ? tohtml($customMenu['menu_target']) : '_self'),
                'class'  => 'custom_link'
            ]);
        }
    }

    /** @var $activePage Zend_Navigation_Page_Uri */
    foreach ($navigation->findAllBy('uri', $_SERVER['SCRIPT_NAME']) as $activePage) {
        $activePage->setActive();
    }

    $query = (!empty($_GET)) ? '?' . http_build_query($_GET) : '';

    /** @var $page Zend_Navigation_Page */
    foreach ($navigation as $page) {
        if (NULL !== ($callbacks = $page->get('privilege_callback'))) {
            $callbacks = (isset($callbacks['name'])) ? [$callbacks] : $callbacks;

            foreach ($callbacks as $callback) {
                if (is_callable($callback['name'])) {
                    if (!call_user_func_array(
                        $callback['name'], isset($callback['param']) ? (array)$callback['param'] : []
                    )
                    ) {
                        continue 2;
                    }
                } else {
                    $name = (is_array($callback['name'])) ? $callback['name'][1] : $callback['name'];
                    throw new iMSCPException(sprintf('Privileges callback is not callable: %s', $name));
                }
            }
        }

        if ($page->isVisible()) {
            $tpl->assign([
                'HREF'                    => $page->getHref(),
                'CLASS'                   => $page->getClass()
                    . (($_SESSION['show_main_menu_labels']) ? ' show_labels' : ''),
                'IS_ACTIVE_CLASS'         => ($page->isActive(true)) ? 'active' : 'dummy',
                'TARGET'                  => ($page->getTarget()) ? tohtml($page->getTarget()) : '_self',
                'MAIN_MENU_LABEL_TOOLTIP' => tohtml($page->getLabel(), 'htmlAttr'),
                'MAIN_MENU_LABEL'         => ($_SESSION['show_main_menu_labels']) ? tohtml($page->getLabel()) : ''
            ]);

            // Add page to main menu
            $tpl->parse('MAIN_MENU_BLOCK', '.main_menu_block');

            if ($page->isActive(true)) {
                $tpl->assign([
                    'TR_SECTION_TITLE'    => tohtml($page->getLabel()),
                    'SECTION_TITLE_CLASS' => $page->getClass()
                ]);

                // Add page to breadcrumb
                $tpl->assign('BREADCRUMB_LABEL', tohtml($page->getLabel()));
                $tpl->parse('BREADCRUMB_BLOCK', '.breadcrumb_block');

                if ($page->hasPages()) {
                    $iterator = new RecursiveIteratorIterator($page, RecursiveIteratorIterator::SELF_FIRST);

                    /** @var $subpage Zend_Navigation_Page_Uri */
                    foreach ($iterator as $subpage) {
                        if (NULL !== ($callbacks = $subpage->get('privilege_callback'))) {
                            $callbacks = (isset($callbacks['name'])) ? [$callbacks] : $callbacks;

                            foreach ($callbacks AS $callback) {
                                if (is_callable($callback['name'])) {
                                    if (!call_user_func_array(
                                        $callback['name'],
                                        isset($callback['param']) ? (array)$callback['param'] : [])
                                    ) {
                                        continue 2;
                                    }
                                } else {
                                    $name = (is_array($callback['name'])) ? $callback['name'][1] : $callback['name'];
                                    throw new iMSCPException(sprintf('Privileges callback is not callable: %s', $name));
                                }
                            }
                        }

                        $tpl->assign([
                            'HREF'            => $subpage->getHref(),
                            'IS_ACTIVE_CLASS' => ($subpage->isActive(true)) ? 'active' : 'dummy',
                            'LEFT_MENU_LABEL' => tohtml($subpage->getLabel()),
                            'TARGET'          => ($subpage->getTarget()) ? $subpage->getTarget() : '_self'
                        ]);

                        if ($subpage->isVisible()) {
                            // Add subpage to left menu
                            $tpl->parse('LEFT_MENU_BLOCK', '.left_menu_block');
                        }

                        if ($subpage->isActive(true)) {
                            $tpl->assign([
                                'TR_TITLE'    => ($subpage->get('dynamic_title'))
                                    ? $subpage->get('dynamic_title')
                                    : tohtml($subpage->getLabel()),
                                'TITLE_CLASS' => $subpage->get('title_class')
                            ]);

                            if (!$subpage->hasPages()) {
                                $tpl->assign('HREF', $subpage->getHref() . "$query");
                            }

                            // add subpage to breadcrumbs
                            if (NULL != ($label = $subpage->get('dynamic_title'))) {
                                $tpl->assign('BREADCRUMB_LABEL', $label);
                            } else {
                                $tpl->assign('BREADCRUMB_LABEL', tohtml($subpage->getLabel()));
                            }

                            $tpl->parse('BREADCRUMB_BLOCK', '.breadcrumb_block');
                        }
                    }

                    $tpl->parse('MENU', 'menu');
                } else {
                    $tpl->assign('MENU', '');
                }
            }
        }
    }

    $tpl->parse('MAIN_MENU', 'main_menu');
    $tpl->parse('BREADCRUMBS', 'breadcrumbs');
    $tpl->parse('MENU', 'menu');

    // Static variables
    $tpl->assign([
        'TR_MENU_LOGOUT' => tr('Logout'),
        'VERSION'        => (isset($cfg['Version']) && $cfg['Version'] != '')
            ? $cfg['Version'] : tohtml(tr('Unknown')),
        'BUILDDATE'      => (isset($cfg['Build']) && $cfg['Build'] != '')
            ? $cfg['Build'] : tohtml(tr('Unavailable')),
        'CODENAME'       => (isset($cfg['CodeName']) && $cfg['CodeName'] != '')
            ? $cfg['CodeName'] : tohtml(tr('Unknown'))
    ]);

    EventsManager::getInstance()->dispatch(Events::onAfterGenerateNavigation, ['templateEngine' => $tpl]);
}

/**
 * Get custom menus for the given user
 *
 * @throws iMSCPException
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
        throw new iMSCPException("Unknown user level '$userLevel' for getCustomMenus() function.");
    }

    $stmt = exec_query('SELECT * FROM custom_menus WHERE menu_level LIKE ?', "%$param%");
    if ($stmt->rowCount()) {
        return $stmt->fetchAll();
    }

    return NULL;
}

// Admin

/**
 * Generate administrator list
 *
 * @param  TemplateEngine $tpl
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function gen_admin_list(TemplateEngine $tpl)
{
    $stmt = execute_query(
        "
          SELECT t1.admin_id, t1.admin_name, t1.domain_created, t2.admin_name AS created_by
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
            'ADMINISTRATPR_CREATED_BY' => tohtml(is_null($row['created_by']) ? tr('System') : $row['created_by']),
            'ADMINISTRATOR_ID'         => $row['admin_id']
        ]);

        if (is_null($row['created_by']) || $row['admin_id'] == $_SESSION['user_id']) {
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
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function gen_reseller_list(TemplateEngine $tpl)
{
    $stmt = execute_query(
        "
          SELECT t1.admin_id, t1.admin_name, t1.domain_created, t2.admin_name AS created_by
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
            'RESELLER_CREATED_BY' => tohtml(is_null($row['created_by']) ? tr('Unknown') : $row['created_by']),
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
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function get_search_user_queries($sLimit, $eLimit, $searchField = NULL, $searchValue = NULL, $searchStatus = NULL)
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
                : " NOT IN ('ok', 'disabled', 'toadd', 'tochange', 'toenable', 'torestore', 'todisable', 'todelete')"
            );
    }

    if ($searchField !== NULL && $searchField != 'anything') {
        if ($searchField == 'domain_name') {
            $where .= (($where == '') ? 'WHERE ' : ' AND ') . 't1.domain_name';
        } elseif ($_SESSION['user_type'] == 'admin' && $searchField == 'reseller_name') {
            $where .= (($where == '') ? 'WHERE ' : ' AND ') . 't3.admin_name';
        } elseif (in_array(
            $searchField, ['fname', 'lname', 'firm', 'city', 'state', 'country'], true
        )) {
            $where .= (($where == '') ? 'WHERE ' : ' AND ') . "t2.$searchField";
        } else {
            showBadRequestErrorPage();
        }

        $searchValue = str_replace(['!', '_', '%'], ['!!!', '!_', '!%'], $searchValue);
        $where .= ' LIKE ' . quoteValue(
                '%' . (($searchField == 'domain_name') ? encode_idna($searchValue) : $searchValue) . '%'
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
            SELECT t1.domain_id, t1.domain_name, t1.domain_created, t1.domain_status, t1.domain_disk_limit,
                t1.domain_disk_usage, t2.admin_id, t2.admin_status, t3.admin_name AS reseller_name
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
 * @throws iMSCP_Exception
 * @throws Zend_Exception
 */
function gen_search_user_fields(TemplateEngine $tpl, $searchField = NULL, $searchValue = NULL, $searchStatus = NULL)
{
    $none = $domain = $customerId = $firstname = $lastname = $company = $city = $state = $country = $resellerName =
    $anything = $ok = $suspended = $error = '';

    if ($searchField === NULL && $searchValue === NULL && $searchStatus === NULL) {
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
        } elseif ($_SESSION['user_type'] == 'admin' && $searchField == 'reseller_name') {
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

        $tpl->assign('SEARCH_VALUE', ($searchValue !== NULL) ? tohtml($searchValue, 'htmlAttr') : '');
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
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function gen_user_domain_aliases_list(TemplateEngine $tpl, $domainId)
{
    $tpl->assign('CLIENT_DOMAIN_ALIAS_BLK', '');

    if (!isset($_SESSION['client_domain_aliases_switch'])
        || $_SESSION['client_domain_aliases_switch'] != 'show'
    ) {
        return;
    }

    $stmt = exec_query('SELECT alias_name FROM domain_aliasses WHERE domain_id = ? ORDER BY alias_name ASC', $domainId);

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
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function gen_user_list(TemplateEngine $tpl)
{
    $cfg = Registry::get('config');

    if (!empty($_POST)) {
        if (!isset($_POST['search_status'])
            || !isset($_POST['search_field'])
            || !isset($_POST['client_domain_aliases_switch'])
            || !in_array($_POST['client_domain_aliases_switch'], ['show', 'hide'])
        ) {
            showBadRequestErrorPage();
        }

        $_SESSION['client_domain_aliases_switch'] = clean_input($_POST['client_domain_aliases_switch']);
        $_SESSION['search_field'] = clean_input($_POST['search_field']);
        $_SESSION['search_value'] = isset($_POST['search_value']) ? clean_input($_POST['search_value']) : '';
        $_SESSION['search_status'] = clean_input($_POST['search_status']);
    } elseif (!isset($_GET['psi'])) {
        unset($_SESSION['search_field'], $_SESSION['search_value'], $_SESSION['search_status']);
    }

    $sLimit = isset($_GET['psi']) ? intval($_GET['psi']) : 0;
    $eLimit = intval($cfg['DOMAIN_ROWS_PER_PAGE']);

    if (!empty($_POST)) {
        list($cQuery, $sQuery) = get_search_user_queries(
            $sLimit, $eLimit, $_SESSION['search_field'], $_SESSION['search_value'], $_SESSION['search_status']
        );
        gen_search_user_fields($tpl, $_SESSION['search_field'], $_SESSION['search_value'], $_SESSION['search_status']);
    } else {
        list($cQuery, $sQuery) = get_search_user_queries($sLimit, $eLimit);
        gen_search_user_fields($tpl);
    }

    if (isset($_SESSION['client_domain_aliases_switch'])) {
        $tpl->assign([
            'CLIENT_DOMAIN_ALIASES_SWITCH_VALUE'                              => $_SESSION['client_domain_aliases_switch'],
            ($_SESSION['client_domain_aliases_switch'] == 'show')
                ? 'CLIENT_DOMAIN_ALIASES_SHOW' : 'CLIENT_DOMAIN_ALIASES_HIDE' => ''
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
            ($row['admin_status'] != 'ok') ? $row['admin_status'] : $row['domain_status']
        );

        if ($row['admin_status'] == 'ok' && $row['domain_status'] == 'ok') {
            $class = 'i_ok';
            $statusTooltip = tr('Click to suspend this customer account.');
        } elseif ($row['domain_status'] == 'disabled') {
            $class = 'i_disabled';
            $statusTooltip = tr('Click to unsuspend this customer account.');
        } elseif (in_array($row['admin_status'], ['tochange', 'tochangepw'])
            || in_array($row['domain_status'], ['toadd', 'tochange', 'torestore', 'toenable', 'todisable', 'todelete'])
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
            'CLIENT_USERNAME'          => tohtml(decode_idna($row['domain_name']), 'htmlAttr'),
            'CLIENT_DOMAIN_ID'         => $row['domain_id'],
            'CLIENT_ID'                => $row['admin_id'],
            'CLIENT_CREATED_ON'        => tohtml(
                ($row['domain_created'] == 0) ? tr('N/A') : date($cfg['DATE_FORMAT'], $row['domain_created'])
            ),
            'CLIENT_CREATED_BY'        => tohtml($row['reseller_name'])
        ]);

        if ($statusOk) {
            $tpl->assign([
                'CLIENT_DOMAIN_STATUS_NOT_OK' => '',
                'CLIENT_DOMAIN_URL'           => tohtml($row['domain_name'], 'htmlAttr')
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
 * @param  TemplateEngine $tpl
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
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
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function reseller_generate_ip_list(TemplateEngine $tpl, $resellerId, $domainIp)
{
    $stmt = exec_query('SELECT reseller_ips FROM reseller_props WHERE reseller_id = ?', $resellerId);
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
 * @throws Zend_Exception
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
        'info'         => tr('Showing %s to %s of %s records', '_START_', '_END_', '_TOTAL_'),
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
 * @throws iMSCPException
 * @return void
 * @throws Zend_Exception
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
        default:
            throw new iMSCPException(500, 'Unknown error page');
    }

    header("Status: $code $message");

    if (isset($_SERVER['HTTP_ACCEPT'])) {
        if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header("Content-type: application/json");
            exit(json_encode(['code' => 404, 'message' => $message]));
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
        include(Registry::get('config')['GUI_ROOT_DIR'] . "/public/errordocs/$code.html");
    }

    exit;
}

/**
 * Show 400 error page
 *
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function showBadRequestErrorPage()
{
    showErrorPage(400);
}

/**
 * Show 404 error page
 *
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function showNotFoundErrorPage()
{
    showErrorPage(404);
}

/**
 * Show 404 error page
 *
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function showForbiddenErrorPage()
{
    showErrorPage(403);
}
