<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 */

/**
 * Helper function to generates domain details.
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $domainId Domain unique identifier
 * @return void
 */
function gen_domain_details($tpl, $domainId)
{
	$tpl->assign('USER_DETAILS', '');

	if (isset($_SESSION['details']) && $_SESSION['details'] == 'hide') {
		$tpl->assign(
			array(
				'TR_VIEW_DETAILS' => tr('View aliases'),
				'SHOW_DETAILS' => 'show'
			)
		);

		return;
	} else if (isset($_SESSION['details']) && $_SESSION['details'] == 'show') {
		$tpl->assign(
			array(
				'TR_VIEW_DETAILS' => tr('Hide aliases'),
				'SHOW_DETAILS' => 'hide'
			)
		);

		$aliasQuery = '
			SELECT `alias_id`, `alias_name` FROM `domain_aliasses` WHERE `domain_id` = ? ORDER BY `alias_id` DESC
		';
		$aliasStmt = exec_query($aliasQuery, $domainId);

		if (!$aliasStmt->rowCount()) {
			$tpl->assign('USER_DETAILS', '');
		} else {
			while (!$aliasStmt->EOF) {
				$aliasName = $aliasStmt->fields['alias_name'];

				$tpl->assign('ALIAS_DOMAIN', tohtml(decode_idna($aliasName)));
				$tpl->parse('USER_DETAILS', '.user_details');

				$aliasStmt->moveNext();
			}
		}
	} else {
		$tpl->assign(
			array(
				'TR_VIEW_DETAILS' => tr('View aliases'),
				'SHOW_DETAILS' => 'show'
			)
		);

		return;
	}
}

/**
 * Helper function to generate logged from block.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function generateLoggedFrom($tpl)
{
	$tpl->define_dynamic('logged_from', 'layout');

	if (isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])) {
		$tpl->assign(
			array(
				'YOU_ARE_LOGGED_AS' => tr(
					'%1$s you are now logged as %2$s', $_SESSION['logged_from'], decode_idna($_SESSION['user_logged'])
				),
				'TR_GO_BACK' => tr('Back')));

		$tpl->parse('LOGGED_FROM', 'logged_from');
	} else {
		$tpl->assign('LOGGED_FROM', '');
	}
}

/**
 * Helper function to generates an html list of available languages.
 *
 * This method generate a HTML list of available languages. The language used by the user is pre-selected.
 * If no language is found, a specific message is shown.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  string $userDefinedLanguage User defined language
 * @return void
 */
function gen_def_language($tpl, $userDefinedLanguage)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$htmlSelected = $cfg->HTML_SELECTED;
	$availableLanguages = i18n_getAvailableLanguages();

	if (!empty($availableLanguages)) {
		foreach ($availableLanguages as $language) {
			$tpl->assign(
				array(
				'LANG_VALUE' => $language['locale'],
				'LANG_SELECTED' => ($language['locale'] == $userDefinedLanguage)
					? $htmlSelected : '',
				'LANG_NAME' => tohtml($language['language'])
				)
			);

			$tpl->parse('DEF_LANGUAGE', '.def_language');
		}
	} else {
		$tpl->assign('LANGUAGES_AVAILABLE', '');
		set_page_message(tr('No languages found.'), 'warning');
	}
}

/**
 * Helper function to generate HTML list of months and years
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @param  int $fromMonth
 * @param  int $fromYear
 * @param  int $numberYears
 * @return void
 */
function generateSelectListForMonthsAndYears($tpl, $fromMonth = null, $fromYear = null, $numberYears = 3)
{
	$fromMonth = intval($fromMonth);
	$fromYear = intval($fromYear);

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (!$fromMonth || $fromMonth > 12) {
		$fromMonth = date('m');
	}

	if ($fromYear) {
		$fromYearTwoDigit = date('y', mktime(0, 0, 0, 1, 1, $fromYear));
	} else {
		$fromYearTwoDigit = date('y');
	}

	foreach (range(1, 12) as $month) {
		$tpl->assign(
			array(
				'OPTION_SELECTED' => ($month == $fromMonth) ? $cfg->HTML_SELECTED : '',
				'MONTH_VALUE' => $month
			)
		);

		$tpl->parse('MONTH_LIST', '.month_list');
	}

	$currentYear = date('y');

	foreach (range($currentYear - ($numberYears - 1), $currentYear) as $year) {
		$tpl->assign(
			array(
				'OPTION_SELECTED' => ($fromYearTwoDigit == $year) ? $cfg->HTML_SELECTED : '',
				'VALUE' => $year,
				'HUMAN_VALUE' => date('Y', mktime(0, 0, 0, 1, 1, $year))
			)
		);

		$tpl->parse('YEAR_LIST', '.year_list');
	}
}

/**
 * Helper function to generate navigation.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @throws iMSCP_Exception_Production
 * @param iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function generateNavigation($tpl)
{
	iMSCP_Events_Aggregator::getInstance()->dispatch(
		iMSCP_Events::onBeforeGenerateNavigation, array('templateEngine' => $tpl)
	);

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$tpl->define_dynamic(
		array(
			'main_menu' => 'layout',
			'main_menu_block' => 'main_menu',
			'menu' => 'layout',
			'left_menu_block' => 'menu',
			'breadcrumbs' => 'layout',
			'breadcrumb_block' => 'breadcrumbs'
		)
	);

	generateLoggedFrom($tpl);

	// Dynamic links (only at customer level)
	if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'user') {

		$domainProperties = get_domain_default_props($_SESSION['user_id']);

		$tpl->assign(
			array(
				'FILEMANAGER_PATH' => $cfg->FILEMANAGER_PATH,
				'FILEMANAGER_TARGET' => $cfg->FILEMANAGER_TARGET,
				'PMA_PATH' => $cfg->PMA_PATH,
				'PMA_TARGET' => $cfg->PMA_TARGET,
				'WEBMAIL_PATH' => $cfg->WEBMAIL_PATH,
				'WEBMAIL_TARGET' => $cfg->WEBMAIL_TARGET,
				'WEBSTATS_RPATH' => 'http://' . decode_idna($domainProperties['domain_name']) . '/' . $cfg->WEBSTATS_RPATH,
				'WEBSTATS_TARGET' => $cfg->WEBSTATS_TARGET
			)
		);
	}

	// Dynamic links (All levels)
	$tpl->assign(
		array(
			'SUPPORT_SYSTEM_PATH' => $cfg->IMSCP_SUPPORT_SYSTEM_PATH,
			'SUPPORT_SYSTEM_TARGET' => $cfg->IMSCP_SUPPORT_SYSTEM_TARGET
		)
	);

	/** @var $navigation Zend_Navigation */
	$navigation = iMSCP_Registry::get('navigation');

	// Remove support system page if feature is globally disabled
	if (!$cfg->IMSCP_SUPPORT_SYSTEM) {
		$navigation->removePage($navigation->findOneBy('class', 'support'));
	}

	if ($_SESSION['user_type'] != 'user') {
		if ($cfg->HOSTING_PLANS_LEVEL != $_SESSION['user_type']) {
			if ($_SESSION['user_type'] === 'admin') {
				$navigation->findOneBy('class', 'hosting_plans')->setVisible(false);
			} else {
				$navigation->findOneBy('class', 'hosting_plan_add')->setVisible(false);
			}
		}
	}

	// Custom menus
	if (null != ($customMenus = getCustomMenus($_SESSION['user_type']))) {
		foreach ($customMenus as $customMenu) {
			$navigation->addPage(
				array(
					'order' => $customMenu['menu_order'],
					'label' => tohtml($customMenu['menu_name']),
					'uri' => get_menu_vars($customMenu['menu_link']),
					'target' => (!empty($customMenu['menu_target']) ? tohtml($customMenu['menu_target']) : '_self'),
					'class' => 'custom_link'));
		}
	}

	/** @var $activePage Zend_Navigation_Page_Uri */
	foreach ($navigation->findAllBy('uri', $_SERVER['SCRIPT_NAME']) as $activePage) {
		$activePage->setActive();
	}

	if (!empty($_GET)) {
		$query = '?' . http_build_query($_GET);
	} else {
		$query = '';
	}

	/** @var $page Zend_Navigation_Page */
	foreach ($navigation as $page) {
		if (null !== ($callbacks = $page->get('privilege_callback'))) {
			$callbacks = (isset($callbacks['name'])) ? array($callbacks) : $callbacks;

			foreach ($callbacks as $callback) {
				if (is_callable($callback['name'])) {
					if (
						! call_user_func_array(
							$callback['name'], isset($callback['param']) ? (array) $callback['param'] : array()
						)
					) {
						continue 2;
					}
				} else {
					$name = (is_array($callback['name'])) ? $callback['name'][1] : $callback['name'];
					throw new iMSCP_Exception_Production(
						sprintf('Privileges callback is not callable: %s', $name)
					);
				}
			}
		}

		if ($page->isVisible()) {
			$tpl->assign(
				array(
					'HREF' => $page->getHref(),
					'CLASS' => $page->getClass() . (($_SESSION['show_main_menu_labels']) ? ' show_labels' : ''),
					'IS_ACTIVE_CLASS' => ($page->isActive(true)) ? 'active' : 'dummy',
					'LABEL' => tr($page->getLabel()),
					'TARGET' => ($page->getTarget()) ? $page->getTarget() : '_self',
					'LINK_LABEL' => ($_SESSION['show_main_menu_labels']) ? tr($page->getLabel()) : '')
			);

			// Add page to main menu
			$tpl->parse('MAIN_MENU_BLOCK', '.main_menu_block');

			if ($page->isActive(true)) {
				$tpl->assign(
					array(
						'TR_SECTION_TITLE' => tr($page->getLabel()),
						'SECTION_TITLE_CLASS' => $page->getClass()));

				// Add page to breadcrumb
				$tpl->parse('BREADCRUMB_BLOCK', '.breadcrumb_block');

				if ($page->hasPages()) {
					$iterator = new RecursiveIteratorIterator($page, RecursiveIteratorIterator::SELF_FIRST);

					/** @var $subpage Zend_Navigation_Page_Uri */
					foreach ($iterator as $subpage) {
						if (null !== ($callbacks = $subpage->get('privilege_callback'))) {
							$callbacks = (isset($callbacks['name'])) ? array($callbacks) : $callbacks;

							foreach ($callbacks AS $callback) {
								if (is_callable($callback['name'])) {
									if (
										! call_user_func_array(
											$callback['name'],
											isset($callback['param']) ? (array) $callback['param'] : array()
										)
									) {
										continue 2;
									}
								} else {
									$name = (is_array($callback['name'])) ? $callback['name'][1] : $callback['name'];
									throw new iMSCP_Exception_Production(
										sprintf('Privileges callback is not callable: %s', $name)
									);
								}
							}
						}

						$tpl->assign(
							array(
								'HREF' => $subpage->getHref(),
								'IS_ACTIVE_CLASS' => ($subpage->isActive(true)) ? 'active' : 'dummy',
								'LABEL' => tr($subpage->getLabel()),
								'TARGET' => ($subpage->getTarget()) ? $subpage->getTarget() : '_self'
							)
						);

						if ($subpage->isVisible()) {
							// Add subpage to left menu
							$tpl->parse('LEFT_MENU_BLOCK', '.left_menu_block');
						}

						if ($subpage->isActive(true)) {
							$tpl->assign(
								array(
									'TR_TITLE' => ($subpage->get('dynamic_title'))
										? $subpage->get('dynamic_title') : tr($subpage->getLabel()),
									'TITLE_CLASS' => $subpage->get('title_class')
								)
							);

							if (!$subpage->hasPages()) {
								$tpl->assign('HREF', $subpage->getHref() . "$query");
							}

							// ad subpage to breadcrumbs
							if (null != ($label = $subpage->get('dynamic_title'))) {
								$tpl->assign('LABEL', $label);
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
	$tpl->assign(
		array(
			'TR_MENU_LOGOUT' => 'Logout',
			'VERSION' => $cfg->Version,
			'BUILDDATE' => $cfg->BuildDate,
			'CODENAME' => $cfg->CodeName
		)
	);

	iMSCP_Events_Aggregator::getInstance()->dispatch(
		iMSCP_Events::onAfterGenerateNavigation, array('templateEngine' => $tpl)
	);
}

/**
 * Returns custom menus for given user.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @throws iMSCP_Exception
 * @param string $userLevel User type (admin, reseller or user)
 * @return null|array Array containing custom menus definitions or NULL in case no custom menu is found
 */
function getCustomMenus($userLevel)
{
	if ($userLevel == 'admin') {
		$params = 'A';
	} elseif ($userLevel == 'reseller') {
		$params = 'R';
	} elseif ($userLevel == 'user') {
		$params = 'C';
	} else {
		throw new iMSCP_Exception("Unknown user level '$userLevel' for getCustomMenus() function.");
	}

	$query = "SELECT * FROM `custom_menus` WHERE `menu_level` LIKE ?";
	$stmt = exec_query($query, "%$params%");

	if ($stmt->rowCount()) {
		return $stmt->fetchAll();
	} else {
		return null;
	}
}
