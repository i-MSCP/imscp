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

// Common

/**
 * Generate logged from block
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function generateLoggedFrom($tpl)
{
    $tpl->define_dynamic('logged_from', 'layout');

    if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])) {
        $tpl->assign([
            'YOU_ARE_LOGGED_AS' => tr(
                '%1$s you are now logged as %2$s',
                $_SESSION['logged_from'],
                decode_idna($_SESSION['user_logged'])
            ),
            'TR_GO_BACK'        => tr('Back')
        ]);
        $tpl->parse('LOGGED_FROM', 'logged_from');
        return;
    }

    $tpl->assign('LOGGED_FROM', '');
}

/**
 * Generates list of available languages
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  string $selectedLanguage Selected language
 * @return void
 */
function gen_def_language($tpl, $selectedLanguage)
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
 * Generate list of months and years
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @param  int $fromMonth
 * @param  int $fromYear
 * @param  int $numberYears
 * @return void
 */
function generateMonthsAndYearsHtmlList($tpl, $fromMonth = NULL, $fromYear = NULL, $numberYears = 3)
{
    $fromMonth = filter_digits($fromMonth);
    $fromYear = filter_digits($fromYear);

    if (!$fromMonth || $fromMonth > 12) {
        $fromMonth = date('m');
    }

    $fromYearTwoDigit = ($fromYear)
        ? date('y', mktime(0, 0, 0, 1, 1, $fromYear))
        : date('y');

    foreach (range(1, 12) as $month) {
        $tpl->assign([
            'OPTION_SELECTED' => ($month == $fromMonth) ? ' selected' : '',
            'MONTH_VALUE'     => tohtml($month)
        ]);
        $tpl->parse('MONTH_LIST', '.month_list');
    }

    $currentYear = date('y');
    foreach (range($currentYear - ($numberYears - 1), $currentYear) as $year) {
        $tpl->assign([
            'OPTION_SELECTED' => ($fromYearTwoDigit == $year) ? ' selected' : '',
            'VALUE'           => tohtml($year, 'htmlAttr'),
            'HUMAN_VALUE'     => tohtml(date('Y', mktime(0, 0, 0, 1, 1, $year)))
        ]);
        $tpl->parse('YEAR_LIST', '.year_list');
    }
}

/**
 * Generate navigation
 *
 * @throws iMSCP_Exception
 * @param iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function generateNavigation($tpl)
{
    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onBeforeGenerateNavigation, [
        'templateEngine' => $tpl
    ]);

    $cfg = iMSCP_Registry::get('config');
    $tpl->define_dynamic([
        'main_menu'        => 'layout',
        'main_menu_block'  => 'main_menu',
        'menu'             => 'layout',
        'left_menu_block'  => 'menu',
        'breadcrumbs'      => 'layout',
        'breadcrumb_block' => 'breadcrumbs'
    ]);

    generateLoggedFrom($tpl);

    /** @var $navigation Zend_Navigation */
    $navigation = iMSCP_Registry::get('navigation');

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
                    throw new iMSCP_Exception(sprintf('Privileges callback is not callable: %s', $name));
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
                                    throw new iMSCP_Exception(
                                        sprintf('Privileges callback is not callable: %s', $name)
                                    );
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
                                    ? $subpage->get('dynamic_title') : tohtml($subpage->getLabel()),
                                'TITLE_CLASS' => $subpage->get('title_class')
                            ]);

                            if (!$subpage->hasPages()) {
                                $tpl->assign('HREF', $subpage->getHref() . "$query");
                            }

                            // ad subpage to breadcrumbs
                            if (NULL != ($label = $subpage->get('dynamic_title'))) {
                                $tpl->assign('MENU_LABEL_TOOLTIP', tohtml($label));
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
        'BUILDDATE'      => (isset($cfg['BuildDate']) && $cfg['BuildDate'] != '')
            ? $cfg['BuildDate'] : tohtml(tr('Unavailable')),
        'CODENAME'       => (isset($cfg['CodeName']) && $cfg['CodeName'] != '')
            ? $cfg['CodeName'] : tohtml(tr('Unknown'))
    ]);

    iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAfterGenerateNavigation, [
        'templateEngine' => $tpl
    ]);
}

/**
 * Get custom menus for the given user
 *
 * @throws iMSCP_Exception
 * @param string $userLevel User type (admin, reseller or user)
 * @return null|[] Array containing custom menus definitions or NULL in case no custom menu is found
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
        throw new iMSCP_Exception("Unknown user level '$userLevel' for getCustomMenus() function.");
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
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function gen_admin_list($tpl)
{
    $cfg = iMSCP_Registry::get('config');
    $stmt = execute_query(
        "
          SELECT t1.admin_id, t1.admin_name, t1.domain_created, IFNULL(t2.admin_name, '') AS created_by
          FROM admin AS t1
          LEFT JOIN admin AS t2 ON (t1.created_by = t2.admin_id)
          WHERE t1.admin_type = 'admin'
          ORDER BY t1.admin_name ASC
        "
    );

    if (!$stmt->rowCount()) {
        $tpl->assign('ADMINISTRATOR_LIST', '');
        $tpl->parse('ADMINISTRATOR_MESSAGE', 'administrator_message');
        return;
    }

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        $adminCreated = $row['domain_created'];

        if ($adminCreated == 0) {
            $adminCreated = tr('N/A');
        } else {
            $dateFormat = $cfg['DATE_FORMAT'];
            $adminCreated = date($dateFormat, $adminCreated);
        }

        if (empty($row['created_by']) || $row['admin_id'] == $_SESSION['user_id']) {
            $tpl->assign('ADMINISTRATOR_DELETE_LINK', '');
        } else {
            $tpl->assign([
                'ADMINISTRATOR_USERNAME' => tohtml($row['admin_name']),
                'ADMINISTRATOR_EDIT_URL' => 'user_delete.php?delete_id=' . $row['admin_id'],

            ]);
            $tpl->parse('ADMIN_DELETE_LINK', 'admin_delete_link');
        }

        $tpl->assign([
            'ADMINISTRATOR_USERNAME'   => tohtml($row['admin_name']),
            'ADMINISTRATOR_CREATED_ON' => tohtml($adminCreated),
            'ADMINISTRATPR_CREATED_BY' => ($row['created_by'] != '') ? tohtml($row['created_by']) : tr('System'),
            'ADMINISTRATOR_EDIT_URL'   => 'user_edit.php?edit_id=' . $row['admin_id']
        ]);
        $tpl->parse('ADMINISTRATOR_ITEM', '.administrator_item');
    }

    $tpl->parse('ADMINISRATOR_LIST', 'administrator_list');
    $tpl->assign('ADMINISTRATOR_MESSAGE', '');
}

/**
 * Generate reseller list
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function gen_reseller_list($tpl)
{
    $cfg = iMSCP_Registry::get('config');

    $stmt = execute_query(
        "
          SELECT t1.admin_id, t1.admin_name, t1.domain_created, IFNULL(t2.admin_name, '') AS created_by
          FROM admin AS t1
          LEFT JOIN admin AS t2 ON (t1.created_by = t2.admin_id)
          WHERE t1.admin_type = 'reseller'
          ORDER BY t1.admin_name ASC
        "
    );

    if (!$stmt->rowCount()) {
        $tpl->assign('RESELLER_LIST', '');
        $tpl->parse('RESELLER_MESSAGE', 'reseller_message');
        return;
    }

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        $tpl->assign([
            'RESELLER_SWITCH_INTERFACE_URL' => 'change_user_interface.php?to_id=' . $row['admin_id'],
            'RESELLER_DELETE_URL'           => 'user_delete.php?delete_id=' . $row['admin_id']
        ]);

        $resellerCreated = $row['domain_created'];

        if ($resellerCreated == 0) {
            $resellerCreated = tr('N/A');
        } else {
            $resellerCreated = date($cfg['DATE_FORMAT'], $resellerCreated);
        }

        $tpl->assign([
            'RESELLER_NAME'       => tohtml($row['admin_name']),
            'RESELLER_CREATED_ON' => tohtml($resellerCreated),
            'RESELLER_CREATED_BY' => ($row['created_by'] != '') ? tohtml($row['created_by']) : tr('Unknown'),
            'RESELLER_EDIT_URL'   => 'reseller_edit.php?edit_id=' . $row['admin_id']
        ]);
        $tpl->parse('RESELER_ITEM', '.reseller_item');
    }

    $tpl->parse('RESELLER_LIST', 'reseller_list');
    $tpl->assign('RESELLER_MESSAGE', '');
}

/**
 * Generates user domain_aliases_list
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $domainId Domain unique identifier
 * @return void
 */
function gen_user_domain_aliases_list($tpl, $domainId)
{
    if (!isset($_SESSION['client_domain_aliases_switch']) || $_SESSION['client_domain_aliases_switch'] != 'show') {
        $tpl->assign('CLIENT_DOMAIN_ALIAS_BLK', '');
        return;
    }

    $stmt = exec_query(
        'SELECT alias_name FROM domain_aliasses WHERE domain_id = ? ORDER BY alias_id DESC', $domainId
    );

    if (!$stmt->rowCount()) {
        $tpl->assign('CLIENT_DOMAIN_ALIAS_BLK', '');
        return;
    }

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        $tpl->assign([
            'CLIENT_DOMAIN_ALIAS_URL' => tohtml($row['alias_name'], 'htmlAttr'),
            'CLIENT_DOMAIN_ALIAS', tohtml(decode_idna($row['alias_name']))
        ]);
        $tpl->parse('CLIENT_DOMAIN_ALIAS_BLK', '.client_domain_alias_blk');
    }
}

/**
 * Generate user list
 *
 * @param iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function gen_user_list($tpl)
{
    $cfg = iMSCP_Registry::get('config');

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

        $tpl->parse('CLIENT_MESSAGE', 'client_message');
        return;
    } elseif (isset($_SESSION['client_domain_aliases_switch'])) {
        $tpl->assign([
            'CLIENT_DOMAIN_ALIASES_SWITCH_VALUE' => $_SESSION['client_domain_aliases_switch'],
            ($_SESSION['client_domain_aliases_switch'] == 'show')
                ? 'CLIENT_DOMAIN_ALIASES_SHOW'
                : 'CLIENT_DOMAIN_ALIASES_HIDE'   => ''
        ]);
    } else {
        $tpl->assign([
            'CLIENT_DOMAIN_ALIASES_SWITCH_VALUE' => 'hide',
            'CLIENT_DOMAIN_ALIASES_HIDE'         => ''
        ]);
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
                'CLIENT_RESTRICTED_LINKS' => '',
            ]);
            $tpl->parse('CLIENT_DOMAIN_STATUS_NOT_OK', 'client_domain_status_not_ok');
        }

        gen_user_domain_aliases_list($tpl, $row['domain_id']);
        $tpl->parse('CLIENT_ITEM', '.client_item');
    }

    $tpl->parse('CLIENT_LIST', 'client_list');
    $tpl->assign('CLIENT_MESSAGE', '');
}

/**
 * Generate manage users page
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function get_admin_manage_users($tpl)
{
    gen_admin_list($tpl);
    gen_reseller_list($tpl);
    gen_user_list($tpl);
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
function get_search_user_queries($sLimit, $eLimit, $searchField = NULL, $searchValue = NULL, $searchStatus = NULL)
{
    $sLimit = intval($sLimit);
    $eLimit = intval($eLimit);
    $where = '';

    if ($_SESSION['user_type'] == 'reseller') {
        $where .= 'WHERE t2.created_by = ' . intval($_SESSION['user_id']);
    }

    if ($searchStatus !== NULL && $searchStatus != 'anything') {
        $where .= (($where == '') ? 'WHERE ' : ' ') . 't1.domain_status' . (
            ($searchStatus == 'ok' || $searchStatus == 'disabled')
                ? ' = ' . quoteValue($searchStatus)
                : " NOT IN ('ok', 'toadd', 'tochange', 'toenable', 'torestore', 'todisable', 'todelete')"
            );
    }

    if ($searchField !== NULL && $searchField != 'anything') {
        if ($searchField == 'domain_name') {
            $where .= (($where == '') ? 'WHERE ' : ' AND ') . 't1.domain_name';
        } elseif ($_SESSION['user_type'] == 'admin' && $searchField == 'reseller_name') {
            $where .= (($where == '') ? 'WHERE ' : ' AND ') . 't3.admin_name';
        } elseif (in_array(
            $searchField, ['customer_id', 'fname', 'lname', 'firm', 'city', 'state', 'country'], true
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
 * @param iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @param string|null $searchField Field to search
 * @param string|null $searchValue Value to search
 * @param string|null $searchStatus Status to search
 * @return void
 */
function gen_search_user_fields($tpl, $searchField = NULL, $searchValue = NULL, $searchStatus = NULL)
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
        } elseif ($searchField == 'customer_id') {
            $customerId = ' selected';
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

        $tpl->assign('SEARCH_VALUE', ($searchValue !== NULL) ? tohtml($searchValue) : '');
    }

    $tpl->assign([
        # search_field select
        'CLIENT_NONE_SELECTED'          => $none,
        'CLIENT_DOMAIN_NAME_SELECTED'   => $domain,
        'CLIENT_CUSTOMER_ID_SELECTED'   => $customerId,
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

// Reseller

/**
 * Returns reseller Ip list
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $resellerId Reseller unique identifier
 * @param int $domainIp Identifier of the selected domain IP
 * @return void
 */
function reseller_generate_ip_list($tpl, $resellerId, $domainIp)
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
