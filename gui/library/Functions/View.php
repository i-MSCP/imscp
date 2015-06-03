<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by i-MSCP Team
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
 * Helper function to generates domain details
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $domainId Domain unique identifier
 * @return void
 */
function gen_domain_details($tpl, $domainId)
{
	$tpl->assign('USER_DETAILS', '');

	if(isset($_SESSION['details']) && $_SESSION['details'] == 'hide') {
		$tpl->assign(
			array(
				'TR_VIEW_DETAILS' => tr('View aliases'),
				'SHOW_DETAILS' => 'show'
			)
		);

		return;
	} else if(isset($_SESSION['details']) && $_SESSION['details'] == 'show') {
		$tpl->assign(
			array(
				'TR_VIEW_DETAILS' => tr('Hide aliases'),
				'SHOW_DETAILS' => 'hide'
			)
		);

		$stmt = exec_query(
			'SELECT alias_id, alias_name FROM domain_aliasses WHERE domain_id = ? ORDER BY alias_id DESC', $domainId
		);

		if(!$stmt->rowCount()) {
			$tpl->assign('USER_DETAILS', '');
		} else {
			while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$tpl->assign('ALIAS_DOMAIN', tohtml(decode_idna($row['alias_name'])));
				$tpl->parse('USER_DETAILS', '.user_details');
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

	if(isset($_SESSION['logged_from']) && isset($_SESSION['logged_from_id'])) {
		$tpl->assign(
			array(
				'YOU_ARE_LOGGED_AS' => tr(
					'%1$s you are now logged as %2$s', $_SESSION['logged_from'], decode_idna($_SESSION['user_logged'])
				),
				'TR_GO_BACK' => tr('Back')
			)
		);

		$tpl->parse('LOGGED_FROM', 'logged_from');
	} else {
		$tpl->assign('LOGGED_FROM', '');
	}
}

/**
 * Helper function to generates an html list of available languages
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

	$htmlSelected = $cfg['HTML_SELECTED'];
	$availableLanguages = i18n_getAvailableLanguages();

	array_unshift(
		$availableLanguages,
		array(
			'locale' => 'auto',
			'language' => tr('Auto (Browser language)')
		)
	);

	if(!empty($availableLanguages)) {
		foreach($availableLanguages as $language) {
			$tpl->assign(
				array(
					'LANG_VALUE' => $language['locale'],
					'LANG_SELECTED' => ($language['locale'] == $userDefinedLanguage) ? $htmlSelected : '',
					'LANG_NAME' => tohtml($language['language'])
				)
			);

			$tpl->parse('DEF_LANGUAGE', '.def_language');
		}
	} else {
		$tpl->assign('LANGUAGES_AVAILABLE', '');
		set_page_message(tr('No languages found.'), 'static_warning');
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
function generateMonthsAndYearsHtmlList($tpl, $fromMonth = null, $fromYear = null, $numberYears = 3)
{
	$fromMonth = intval($fromMonth);
	$fromYear = intval($fromYear);

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if(!$fromMonth || $fromMonth > 12) {
		$fromMonth = date('m');
	}

	if($fromYear) {
		$fromYearTwoDigit = date('y', mktime(0, 0, 0, 1, 1, $fromYear));
	} else {
		$fromYearTwoDigit = date('y');
	}

	foreach(range(1, 12) as $month) {
		$tpl->assign(
			array(
				'OPTION_SELECTED' => ($month == $fromMonth) ? $cfg['HTML_SELECTED'] : '',
				'MONTH_VALUE' => tohtml($month)
			)
		);

		$tpl->parse('MONTH_LIST', '.month_list');
	}

	$currentYear = date('y');

	foreach(range($currentYear - ($numberYears - 1), $currentYear) as $year) {
		$tpl->assign(
			array(
				'OPTION_SELECTED' => ($fromYearTwoDigit == $year) ? $cfg['HTML_SELECTED'] : '',
				'VALUE' => tohtml($year, 'htmlAttr'),
				'HUMAN_VALUE' => tohtml(date('Y', mktime(0, 0, 0, 1, 1, $year)))
			)
		);

		$tpl->parse('YEAR_LIST', '.year_list');
	}
}

/**
 * Helper function to generate navigation
 *
 * @throws iMSCP_Exception
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

	/** @var $navigation Zend_Navigation */
	$navigation = iMSCP_Registry::get('navigation');

	// Dynamic links (only at customer level)
	if(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'user') {
		$domainProperties = get_domain_default_props($_SESSION['user_id']);

		$tpl->assign('WEBSTATS_PATH', 'http://' . decode_idna($domainProperties['domain_name']) . '/stats');

		if(customerHasFeature('mail')) {
			$webmails = getWebmailList();

			if(!empty($webmails)) {
				$page1 = $navigation->findOneBy('class', 'email');
				$page2 = $navigation->findOneBy('class', 'webtools');

				foreach($webmails as $webmail) {
					$page =array(
						'label' => tr('%s webmail', $webmail),
						'uri' => '/' . (($webmail == 'Roundcube') ? 'webmail' : strtolower($webmail)),
						'target' => '_blank',
					);

					$page1->addPage($page);
					$page2->addPage($page);
				}
			}
		}
	} else {
		if($cfg['HOSTING_PLANS_LEVEL'] != $_SESSION['user_type']) {
			if($_SESSION['user_type'] === 'admin') {
				$navigation->findOneBy('class', 'hosting_plans')->setVisible(false);
			} else {
				$navigation->findOneBy('class', 'hosting_plan_add')->setVisible(false);
			}
		}
	}

	// Dynamic links (All levels)
	$tpl->assign(
		array(
			'SUPPORT_SYSTEM_PATH' => 'ticket_system.php',
			'SUPPORT_SYSTEM_TARGET' => '_self'
		)
	);

	// Remove support system page if feature is globally disabled
	if(!$cfg['IMSCP_SUPPORT_SYSTEM']) {
		$navigation->removePage($navigation->findOneBy('class', 'support'));
	}

	// Custom menus
	if(null != ($customMenus = getCustomMenus($_SESSION['user_type']))) {
		foreach($customMenus as $customMenu) {
			$navigation->addPage(
				array(
					'order' => $customMenu['menu_order'],
					'label' => tohtml($customMenu['menu_name']),
					'uri' => get_menu_vars($customMenu['menu_link']),
					'target' => (!empty($customMenu['menu_target']) ? tohtml($customMenu['menu_target']) : '_self'),
					'class' => 'custom_link'
				)
			);
		}
	}

	/** @var $activePage Zend_Navigation_Page_Uri */
	foreach($navigation->findAllBy('uri', $_SERVER['SCRIPT_NAME']) as $activePage) {
		$activePage->setActive();
	}

	if(!empty($_GET)) {
		$query = '?' . http_build_query($_GET);
	} else {
		$query = '';
	}

	/** @var $page Zend_Navigation_Page */
	foreach($navigation as $page) {
		if(null !== ($callbacks = $page->get('privilege_callback'))) {
			$callbacks = (isset($callbacks['name'])) ? array($callbacks) : $callbacks;

			foreach($callbacks as $callback) {
				if(is_callable($callback['name'])) {
					if(!call_user_func_array($callback['name'], isset($callback['param']) ? (array)$callback['param'] : array())) {
						continue 2;
					}
				} else {
					$name = (is_array($callback['name'])) ? $callback['name'][1] : $callback['name'];
					throw new iMSCP_Exception(sprintf('Privileges callback is not callable: %s', $name));
				}
			}
		}

		if($page->isVisible()) {
			$tpl->assign(
				array(
					'HREF' => $page->getHref(),
					'CLASS' => $page->getClass() . (($_SESSION['show_main_menu_labels']) ? ' show_labels' : ''),
					'IS_ACTIVE_CLASS' => ($page->isActive(true)) ? 'active' : 'dummy',
					'LABEL' => tr($page->getLabel()),
					'TARGET' => ($page->getTarget()) ? $page->getTarget() : '_self',
					'LINK_LABEL' => ($_SESSION['show_main_menu_labels']) ? tr($page->getLabel()) : ''
				)
			);

			// Add page to main menu
			$tpl->parse('MAIN_MENU_BLOCK', '.main_menu_block');

			if($page->isActive(true)) {
				$tpl->assign(
					array(
						'TR_SECTION_TITLE' => tr($page->getLabel()),
						'SECTION_TITLE_CLASS' => $page->getClass()));

				// Add page to breadcrumb
				$tpl->parse('BREADCRUMB_BLOCK', '.breadcrumb_block');

				if($page->hasPages()) {
					$iterator = new RecursiveIteratorIterator($page, RecursiveIteratorIterator::SELF_FIRST);

					/** @var $subpage Zend_Navigation_Page_Uri */
					foreach($iterator as $subpage) {
						if(null !== ($callbacks = $subpage->get('privilege_callback'))) {
							$callbacks = (isset($callbacks['name'])) ? array($callbacks) : $callbacks;

							foreach($callbacks AS $callback) {
								if(is_callable($callback['name'])) {
									if(!call_user_func_array(
										$callback['name'],
										isset($callback['param']) ? (array)$callback['param'] : array())
									) {
										continue 2;
									}
								} else {
									$name = (is_array($callback['name'])) ? $callback['name'][1] : $callback['name'];
									throw new iMSCP_Exception(sprintf('Privileges callback is not callable: %s', $name));
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

						if($subpage->isVisible()) {
							// Add subpage to left menu
							$tpl->parse('LEFT_MENU_BLOCK', '.left_menu_block');
						}

						if($subpage->isActive(true)) {
							$tpl->assign(
								array(
									'TR_TITLE' => ($subpage->get('dynamic_title'))
										? $subpage->get('dynamic_title') : tr($subpage->getLabel()),
									'TITLE_CLASS' => $subpage->get('title_class')
								)
							);

							if(!$subpage->hasPages()) {
								$tpl->assign('HREF', $subpage->getHref() . "$query");
							}

							// ad subpage to breadcrumbs
							if(null != ($label = $subpage->get('dynamic_title'))) {
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
			'TR_MENU_LOGOUT' => tr('Logout'),
			'VERSION' => (isset($cfg['Version']) && $cfg['Version'] != '') ? $cfg['Version'] : tr('Unknown'),
			'BUILDDATE' => (isset($cfg['BuildDate']) && $cfg['BuildDate'] != '') ? $cfg['BuildDate'] : tr('Unavailable'),
			'CODENAME' => (isset($cfg['CodeName']) && $cfg['CodeName'] != '') ? $cfg['CodeName'] : tr('Unknown')
		)
	);

	iMSCP_Events_Aggregator::getInstance()->dispatch(
		iMSCP_Events::onAfterGenerateNavigation, array('templateEngine' => $tpl)
	);
}

/**
 * Returns custom menus for given user.
 *
 * @throws iMSCP_Exception
 * @param string $userLevel User type (admin, reseller or user)
 * @return null|array Array containing custom menus definitions or NULL in case no custom menu is found
 */
function getCustomMenus($userLevel)
{
	if($userLevel == 'admin') {
		$param = 'A';
	} elseif($userLevel == 'reseller') {
		$param = 'R';
	} elseif($userLevel == 'user') {
		$param = 'C';
	} else {
		throw new iMSCP_Exception("Unknown user level '$userLevel' for getCustomMenus() function.");
	}

	$stmt = exec_query('SELECT * FROM custom_menus WHERE menu_level LIKE ?', "%$param%");

	if($stmt->rowCount()) {
		return $stmt->fetchAll();
	} else {
		return null;
	}
}

// Admin

/**
 * Returns admin Ip list.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function admin_generate_ip_list($tpl)
{
	global $domainIp;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = execute_query('SELECT * FROM server_ips');

	while($row = $stmt->fetchRow()) {
		$ipId = $row['ip_id'];

		$selected = ($domainIp === $ipId) ? $cfg['HTML_SELECTED'] : '';

		$tpl->assign(
			array(
				'IP_NUM' => $row['ip_number'],
				'IP_VALUE' => $ipId,
				'IP_SELECTED' => $selected
			)
		);

		$tpl->parse('IP_ENTRY', '.ip_entry');
	}
}

/**
 * Helper function to generate admin list template part
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function gen_admin_list($tpl)
{
	/** @var $cfg  iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = execute_query(
		"
			SELECT
				t1.admin_id, t1.admin_name, t1.domain_created, IFNULL(t2.admin_name, '') AS created_by
			FROM
				admin AS t1
			LEFT JOIN
				admin AS t2 ON (t1.created_by = t2.admin_id)
			WHERE
				t1.admin_type = 'admin'
			ORDER BY
				t1.admin_name ASC
		"
	);

	if(!$stmt->rowCount()) {
		$tpl->assign(
			array(
				'ADMIN_MESSAGE' => tr('No administrator accounts found.'),
				'ADMIN_LIST' => ''
			)
		);

		$tpl->parse('ADMIN_MESSAGE', 'admin_message');
	} else {
		$tpl->assign(
			array(
				'TR_ADMIN_USERNAME' => tr('Username'),
				'TR_ADMIN_CREATED_ON' => tr('Creation date'),
				'TR_ADMIN_CREATED_BY' => tr('Created by'),
				'TR_ADMIN_ACTIONS' => tr('Actions')
			)
		);

		while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			$adminCreated = $row['domain_created'];

			if($adminCreated == 0) {
				$adminCreated = tr('N/A');
			} else {
				$dateFormat = $cfg['DATE_FORMAT'];
				$adminCreated = date($dateFormat, $adminCreated);
			}

			if($row['created_by'] == '' || $row['admin_id'] == $_SESSION['user_id']) {

				$tpl->assign('ADMIN_DELETE_LINK', '');
				$tpl->parse('ADMIN_DELETE_SHOW', 'admin_delete_show');
			} else {
				$tpl->assign(
					array(
						'ADMIN_DELETE_SHOW' => '',
						'TR_DELETE' => tr('Delete'),
						'URL_DELETE_ADMIN' => 'user_delete.php?delete_id=' . $row['admin_id'],
						'ADMIN_USERNAME' => tohtml($row['admin_name'])
					)
				);

				$tpl->parse('ADMIN_DELETE_LINK', 'admin_delete_link');
			}

			$tpl->assign(
				array(
					'ADMIN_USERNAME' => tohtml($row['admin_name']),
					'ADMIN_CREATED_ON' => tohtml($adminCreated),
					'ADMIN_CREATED_BY' => ($row['created_by'] != '') ? tohtml($row['created_by']) : tr('System'),
					'URL_EDIT_ADMIN' => 'admin_edit.php?edit_id=' . $row['admin_id']
				)
			);

			$tpl->parse('ADMIN_ITEM', '.admin_item');
		}

		$tpl->parse('ADMIN_LIST', 'admin_list');
		$tpl->assign('ADMIN_MESSAGE', '');
	}
}

/**
 * Helper function to generate reseller list template part
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function gen_reseller_list($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = execute_query(
		"
			SELECT
				t1.admin_id, t1.admin_name, t1.domain_created, IFNULL(t2.admin_name, '') AS created_by
			FROM
				admin AS t1
			LEFT JOIN
				admin AS t2 ON (t1.created_by = t2.admin_id)
			WHERE
				t1.admin_type = 'reseller'
			ORDER BY
				t1.admin_name ASC
		"
	);

	if(!$stmt->rowCount()) {
		$tpl->assign(
			array(
				'RSL_MESSAGE' => tr('No reseller accounts found.'),
				'RSL_LIST' => ''
			)
		);

		$tpl->parse('RSL_MESSAGE', 'rsl_message');
	} else {
		$tpl->assign(
			array(
				'TR_RSL_USERNAME' => tr('Username'),
				'TR_RSL_CREATED_BY' => tr('Created by'),
				'TR_RSL_ACTIONS' => tr('Actions')
			)
		);

		while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			if($row['created_by'] == '') {
				$tpl->assign(
					array(
						'TR_DELETE' => tr('Delete'),
						'RSL_DELETE_LINK' => ''
					)
				);

				$tpl->parse('RSL_DELETE_SHOW', 'rsl_delete_show');
			} else {
				$tpl->assign(
					array(
						'RSL_DELETE_SHOW' => '',
						'TR_DELETE' => tr('Delete'),
						'URL_DELETE_RSL' => 'user_delete.php?delete_id=' . $row['admin_id'],
						'TR_CHANGE_USER_INTERFACE' => tr('Switch to user interface'),
						'GO_TO_USER_INTERFACE' => tr('Switch'),
						'URL_CHANGE_INTERFACE' => 'change_user_interface.php?to_id=' . $row['admin_id']
					)
				);

				$tpl->parse('RSL_DELETE_LINK', 'rsl_delete_link');
			}

			$resellerCreated = $row['domain_created'];

			if($resellerCreated == 0) {
				$resellerCreated = tr('N/A');
			} else {
				$date_formt = $cfg['DATE_FORMAT'];
				$resellerCreated = date($date_formt, $resellerCreated);
			}

			$tpl->assign(
				array(
					'RSL_USERNAME' => tohtml($row['admin_name']),
					'RESELLER_CREATED_ON' => tohtml($resellerCreated),
					'RSL_CREATED_BY' => ($row['created_by'] != '') ? tohtml($row['created_by']) : tr('Unknown'),
					'URL_EDIT_RSL' => 'reseller_edit.php?edit_id=' . $row['admin_id']
				)
			);

			$tpl->parse('RSL_ITEM', '.rsl_item');
		}

		$tpl->parse('RSL_LIST', 'rsl_list');
		$tpl->assign('RSL_MESSAGE', '');
	}
}

/**
 * Helper function to generate a user list
 *
 * @param iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function gen_user_list($tpl)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$startIndex = 0;
	$rowsPerPage = $cfg['DOMAIN_ROWS_PER_PAGE'];

	if(isset($_GET['psi']) && $_GET['psi'] == 'last') {
		if(isset($_SESSION['search_page'])) {
			$_GET['psi'] = $_SESSION['search_page'];
		} else {
			unset($_GET['psi']);
		}
	}

	if(isset($_GET['psi'])) {
		$startIndex = $_GET['psi'];
	}

	// Search request generated ?
	if(isset($_POST['uaction']) && !empty($_POST['uaction'])) {
		$_SESSION['search_for'] = clean_input($_POST['search_for']);
		$_SESSION['search_common'] = $_POST['search_common'];
		$_SESSION['search_status'] = $_POST['search_status'];
		$startIndex = 0;
	} elseif(isset($_SESSION['search_for']) && !isset($_GET['psi'])) {
		// He have not got scroll through patient records
		unset($_SESSION['search_for']);
		unset($_SESSION['search_common']);
		unset($_SESSION['search_status']);
	}

	$searchQuery = $countQuery = '';

	if(isset($_SESSION['search_for'])) {
		gen_admin_domain_query(
			$searchQuery, $countQuery, $startIndex, $rowsPerPage, $_SESSION['search_for'], $_SESSION['search_common'],
			$_SESSION['search_status']
		);

		gen_admin_domain_search_options(
			$tpl, $_SESSION['search_for'], $_SESSION['search_common'], $_SESSION['search_status']
		);

		$stmt = exec_query($countQuery);
	} else {
		gen_admin_domain_query($searchQuery, $countQuery, $startIndex, $rowsPerPage, 'n/a', 'n/a', 'n/a');
		gen_admin_domain_search_options($tpl, 'n/a', 'n/a', 'n/a');
		$stmt = exec_query($countQuery);
	}

	$recordCount = $stmt->fields['cnt'];
	$stmt = execute_query($searchQuery);

	if(!$stmt->rowCount()) {
		if(isset($_SESSION['search_for'])) {
			$tpl->assign(
				array(
					'USR_MESSAGE' => tr('No records found matching the search criteria.'),
					'USR_LIST' => '',
					'SCROLL_PREV' => '',
					'SCROLL_NEXT' => '',
					'TR_VIEW_DETAILS' => tr('view aliases'),
					'SHOW_DETAILS' => 'show'
				)
			);

			unset($_SESSION['search_for']);
			unset($_SESSION['search_common']);
			unset($_SESSION['search_status']);
		} else {
			$tpl->assign(
				array(
					'SEARCH_FORM' => '',
					'USR_MESSAGE' => tr('No customer accounts found.'),
					'USR_LIST' => '',
					'SCROLL_PREV' => '',
					'SCROLL_NEXT' => '',
					'TR_VIEW_DETAILS' => tr('view aliases'),
					'SHOW_DETAILS' => 'show'
				)
			);
		}

		$tpl->parse('USR_MESSAGE', 'usr_message');
	} else {
		$prevSi = $startIndex - $rowsPerPage;

		if($startIndex == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_PREV_GRAY' => '',
					'PREV_PSI' => $prevSi
				)
			);
		}

		$nextSi = $startIndex + $rowsPerPage;

		if($nextSi + 1 > $recordCount) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_NEXT_GRAY' => '',
					'NEXT_PSI' => $nextSi
				)
			);
		}

		$tpl->assign(
			array(
				'TR_USR_USERNAME' => tr('Username'),
				'TR_USR_CREATED_BY' => tr('Created by'),
				'TR_USR_ACTIONS' => tr('Actions'),
				'TR_USER_STATUS' => tr('Status'),
				'TR_DETAILS' => tr('Details')
			)
		);

		while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			// user status icon
			$domainCreatedBy = $row['created_by'];

			$stmt2 = exec_query('SELECT admin_name, admin_status FROM admin WHERE admin_id = ?', $domainCreatedBy);

			if(!isset($stmt2->fields['admin_name'])) {
				$createdByName = tr('N/A');
			} else {
				$createdByName = $stmt2->fields['admin_name'];
			}

			$tpl->assign(
				array(
					'USR_DELETE_SHOW' => '',
					'USER_ID' => $row['admin_id'],
					'DOMAIN_ID' => $row['domain_id'],
					'TR_DELETE' => tr('Delete'),
					'URL_DELETE_USR' => 'user_delete.php?domain_id=' . $row['domain_id'],
					'TR_CHANGE_USER_INTERFACE' => tr('Switch to user interface'),
					'GO_TO_USER_INTERFACE' => tr('Switch'),
					'URL_CHANGE_INTERFACE' => 'change_user_interface.php?to_id=' . $row['domain_admin_id'],
					'USR_USERNAME' => tohtml($row['domain_name']),
					'TR_EDIT_DOMAIN' => tr('Edit domain'),
					'TR_EDIT_USR' => tr('Edit user')
				)
			);

			$tpl->parse('USR_DELETE_LINK', 'usr_delete_link');

			if($row['admin_status'] == 'ok' && $row['domain_status'] == 'ok') {
				$status = 'ok';
				$statusTooltip = tr('Click to deactivate');
				$statusTxt = translate_dmn_status($row['domain_status']);
				$statusBool = true;
				$canChange = true;
			} elseif($row['domain_status'] == 'disabled') {
				$status = 'disabled';
				$statusTooltip = tr('Click to activate');
				$statusTxt = translate_dmn_status($row['domain_status']);
				$statusBool = false;
				$canChange = true;
			} elseif(
				$row['domain_status'] == 'toadd' || $row['domain_status'] == 'torestore' ||
				$row['domain_status'] == 'tochange' || $row['domain_status'] == 'toenable' ||
				$row['domain_status'] == 'todisable' ||  $row['domain_status'] == 'todelete'
			) {
				$status = 'reload';
				$statusTxt = $statusTooltip = translate_dmn_status(
					($row['admin_status'] != 'ok') ? $row['admin_status'] : $row['domain_status']
				);
				$statusBool = false;
				$canChange = false;
			} else {
				$status = 'error';
				$statusTooltip = tr('An unexpected error occurred. Go to the debugger interface for more details.');
				$statusTxt = translate_dmn_status(
					($row['admin_status'] != 'ok') ? $row['admin_status'] : $row['domain_status']
				);
				$statusBool = false;
				$canChange = false;
			}

			$tpl->assign(
				array(
					'STATUS' => $status,
					'STATUS_TOOLTIP' => $statusTooltip,
					'TR_STATUS' => $statusTxt,
				)
			);

			if($canChange) {
				$tpl->assign('DOMAIN_STATUS_NOCHANGE', '');
				$tpl->parse('DOMAIN_STATUS_CHANGE', 'domain_status_change');
			} else {
				$tpl->assign('DOMAIN_STATUS_CHANGE', '');
				$tpl->parse('DOMAIN_STATUS_NOCHANGE', 'domain_status_nochange');
			}

			$adminName = decode_idna($row['domain_name']);
			$domainCreated = $row['domain_created'];

			if($domainCreated == 0) {
				$domainCreated = tr('N/A');
			} else {
				$date_formt = $cfg['DATE_FORMAT'];
				$domainCreated = date($date_formt, $domainCreated);
			}

			$domainExpires = $row['domain_expires'];

			if($domainExpires == 0) {
				$domainExpires = tr('Not Set');
			} else {
				$date_formt = $cfg['DATE_FORMAT'];
				$domainExpires = date($date_formt, $domainExpires);
			}

			if($statusBool == false) { // reload
				$tpl->assign('USR_STATUS_RELOAD_TRUE', '');
				$tpl->assign('USR_USERNAME', tohtml($adminName));
				$tpl->parse('USR_STATUS_RELOAD_FALSE', 'usr_status_reload_false');
			} else {
				$tpl->assign('USR_STATUS_RELOAD_FALSE', '');
				$tpl->assign('USR_USERNAME', tohtml($adminName));
				$tpl->parse('USR_STATUS_RELOAD_TRUE', 'usr_status_reload_true');
			}

			$tpl->assign(
				array(
					'USER_CREATED_ON' => tohtml($domainCreated),
					'USER_EXPIRES_ON' => $domainExpires,
					'USR_CREATED_BY' => tohtml($createdByName),
					'USR_OPTIONS' => '',
					'URL_EDIT_USR' => 'admin_edit.php?edit_id=' . $row['domain_admin_id'],
					'TR_MESSAGE_DELETE' => tojs(tr('Are you sure you want to delete %s?', '%s'))
				)
			);

			gen_domain_details($tpl, $row['domain_id']);

			$tpl->parse('USR_ITEM', '.usr_item');
		}

		$tpl->parse('USR_LIST', 'usr_list');
		$tpl->assign('USR_MESSAGE', '');
	}
}

/**
 * Helper function to generate manage users template part.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function get_admin_manage_users($tpl)
{
	$tpl->assign(
		array(
			'TR_MANAGE_USERS' => tr('Manage users'),
			'TR_ADMINISTRATORS' => tr('Administrators'),
			'TR_RESELLERS' => tr('Resellers'),
			'TR_CUSTOMERS' => tr('Customers'),
			'TR_SEARCH' => tr('Search'),
			'TR_CREATED_ON' => tr('Creation date'),
			'TR_EXPIRES_ON' => tr('Expire date'),
			'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete %s?', '%s'),
			'TR_EDIT' => tr('Edit')
		)
	);

	gen_admin_list($tpl);
	gen_reseller_list($tpl);
	gen_user_list($tpl);
}

/**
 * Helper function to generate domain search form template part.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @param  string $searchFor Object to search for
 * @param  string $searchCommon Common object to search for
 * @param  string $searchStatus Object status to search for
 * @return void
 */
function gen_admin_domain_search_options($tpl, $searchFor, $searchCommon, $searchStatus)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$htmlSelected = $cfg['HTML_SELECTED'];

	$domainSelected = $customerIdSelected = $lastnameSelected = $companySelected = $citySelected = $stateSelected =
	$countrySelected = $allSelected = $okSelected = $suspendedSelected = '';

	if($searchFor == 'n/a' && $searchCommon == 'n/a' && $searchStatus == 'n/a') {
		// we have no search and let's generate search fields empty
		$domainSelected = $htmlSelected;
		$allSelected = $htmlSelected;
	}

	if($searchCommon == 'domain_name') {
		$domainSelected = $htmlSelected;
	} elseif($searchCommon == 'customer_id') {
		$customerIdSelected = $htmlSelected;
	} elseif($searchCommon == 'lname') {
		$lastnameSelected = $htmlSelected;
	} elseif($searchCommon === 'firm') {
		$companySelected = $htmlSelected;
	} elseif($searchCommon == 'city') {
		$citySelected = $htmlSelected;
	} elseif($searchCommon == 'state') {
		$stateSelected = $htmlSelected;
	} elseif($searchCommon == 'country') {
		$countrySelected = $htmlSelected;
	}

	if($searchStatus == 'all') {
		$allSelected = $htmlSelected;
	} elseif($searchStatus == 'ok') {
		$okSelected = $htmlSelected;
	} elseif($searchStatus == 'disabled') {
		$suspendedSelected = $htmlSelected;
	}

	if($searchFor == 'n/a' || $searchFor == '') {
		$tpl->assign(array('SEARCH_FOR' => ''));
	} else {
		$tpl->assign(array('SEARCH_FOR' => $searchFor));
	}

	$tpl->assign(
		array(
			'M_DOMAIN_NAME' => tr('Domain name'),
			'M_CUSTOMER_ID' => tr('Customer ID'),
			'M_LAST_NAME' => tr('Last name'),
			'M_COMPANY' => tr('Company'),
			'M_CITY' => tr('City'),
			'M_STATE' => tr('State/Province'),
			'M_COUNTRY' => tr('Country'),
			'M_ALL' => tr('All'),
			'M_OK' => tr('OK'),
			'M_SUSPENDED' => tr('Suspended'),
			'M_ERROR' => tr('Error'),
			'M_DOMAIN_NAME_SELECTED' => $domainSelected,
			'M_CUSTOMER_ID_SELECTED' => $customerIdSelected,
			'M_LAST_NAME_SELECTED' => $lastnameSelected,
			'M_COMPANY_SELECTED' => $companySelected,
			'M_CITY_SELECTED' => $citySelected,
			'M_STATE_SELECTED' => $stateSelected,
			'M_COUNTRY_SELECTED' => $countrySelected,
			'M_ALL_SELECTED' => $allSelected,
			'M_OK_SELECTED' => $okSelected,
			'M_SUSPENDED_SELECTED' => $suspendedSelected
		)
	);
}

// Reseller

/**
 * Returns reseller Ip list
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  int $resellerId Reseller unique identifier
 * @return void
 */
function reseller_generate_ip_list($tpl, $resellerId)
{
	global $domainIp;

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$htmlSelected = $cfg['HTML_SELECTED'];

	$stmt = exec_query('SELECT reseller_ips FROM reseller_props WHERE reseller_id = ?', $resellerId);

	$row = $stmt->fetchRow();

	$resellerIps = $row['reseller_ips'];

	$stmt = execute_query('SELECT * FROM server_ips');

	while($row = $stmt->fetchRow()) {
		$ipId = $row['ip_id'];

		if(preg_match("/$ipId;/", $resellerIps) == 1) {
			$selected = ($domainIp === $ipId) ? $htmlSelected : '';

			$tpl->assign(
				array(
					'IP_NUM' => $row['ip_number'],
					'IP_VALUE' => $ipId,
					'IP_SELECTED' => $selected
				)
			);

			$tpl->parse('IP_ENTRY', '.ip_entry');
		}
	}
}

/**
 * Generate reseller domain search form
 *
 * @param iMSCP_pTemplate $tpl
 * @param string $searchFor
 * @param string $searchCommon
 * @param string $searchStatus
 * @return void
 */
function gen_manage_domain_search_options($tpl, $searchFor, $searchCommon, $searchStatus)
{

	$cfg = iMSCP_Registry::get('config');

	$htmlSelected = $cfg['HTML_SELECTED'];

	if($searchFor === 'n/a' && $searchCommon === 'n/a' && $searchStatus === 'n/a') {
		// we have no search and let's genarate search fields empty
		$domainSelected = $htmlSelected;
		$customerIdSelected = '';
		$lastnameSelected = '';
		$companySelected = '';
		$citySelected = '';
		$stateSelected = '';
		$countrySelected = '';

		$allSelected = $htmlSelected;
		$okSelected = '';
		$suspendedSelected = '';
	} else {
		if($searchCommon === 'domain_name') {
			$domainSelected = $htmlSelected;
			$customerIdSelected = '';
			$lastnameSelected = '';
			$companySelected = '';
			$citySelected = '';
			$stateSelected = '';
			$countrySelected = '';
		} elseif($searchCommon === 'customer_id') {
			$domainSelected = '';
			$customerIdSelected = $htmlSelected;
			$lastnameSelected = '';
			$companySelected = '';
			$citySelected = '';
			$stateSelected = '';
			$countrySelected = '';
		} elseif($searchCommon === 'lname') {
			$domainSelected = '';
			$customerIdSelected = '';
			$lastnameSelected = $htmlSelected;
			$companySelected = '';
			$citySelected = '';
			$stateSelected = '';
			$countrySelected = '';
		} elseif($searchCommon === 'firm') {
			$domainSelected = '';
			$customerIdSelected = '';
			$lastnameSelected = '';
			$companySelected = $htmlSelected;
			$citySelected = '';
			$stateSelected = '';
			$countrySelected = '';
		} elseif($searchCommon === 'city') {
			$domainSelected = '';
			$customerIdSelected = '';
			$lastnameSelected = '';
			$companySelected = '';
			$citySelected = $htmlSelected;
			$stateSelected = '';
			$countrySelected = '';
		} elseif($searchCommon === 'state') {
			$domainSelected = '';
			$customerIdSelected = '';
			$lastnameSelected = '';
			$companySelected = '';
			$citySelected = '';
			$stateSelected = $htmlSelected;
			$countrySelected = '';
		} elseif($searchCommon === 'country') {
			$domainSelected = '';
			$customerIdSelected = '';
			$lastnameSelected = '';
			$companySelected = '';
			$citySelected = '';
			$stateSelected = '';
			$countrySelected = $htmlSelected;
		} else {
			showBadRequestErrorPage();
			exit;
		}

		if($searchStatus === 'all') {
			$allSelected = $htmlSelected;
			$okSelected = '';
			$suspendedSelected = '';
		} else if($searchStatus === 'ok') {
			$allSelected = '';
			$okSelected = $htmlSelected;
			$suspendedSelected = '';
		} else if($searchStatus === 'disabled') {
			$allSelected = '';
			$okSelected = '';
			$suspendedSelected = $htmlSelected;
		} else {
			showBadRequestErrorPage();
			exit;
		}
	}

	if($searchFor === 'n/a' || $searchFor === '') {
		$tpl->assign('SEARCH_FOR', '');
	} else {
		$tpl->assign('SEARCH_FOR', tohtml($searchFor));
	}

	$tpl->assign(
		array(
			'M_DOMAIN_NAME' => tr('Domain name'),
			'M_CUSTOMER_ID' => tr('Customer ID'),
			'M_LAST_NAME' => tr('Last name'),
			'M_COMPANY' => tr('Company'),
			'M_CITY' => tr('City'),
			'M_STATE' => tr('State/Province'),
			'M_COUNTRY' => tr('Country'),
			'M_ALL' => tr('All'),
			'M_OK' => tr('OK'),
			'M_SUSPENDED' => tr('Suspended'),
			'M_ERROR' => tr('Error'),
			'M_DOMAIN_NAME_SELECTED' => $domainSelected,
			'M_CUSTOMER_ID_SELECTED' => $customerIdSelected,
			'M_LAST_NAME_SELECTED' => $lastnameSelected,
			'M_COMPANY_SELECTED' => $companySelected,
			'M_CITY_SELECTED' => $citySelected,
			'M_STATE_SELECTED' => $stateSelected,
			'M_COUNTRY_SELECTED' => $countrySelected,
			'M_ALL_SELECTED' => $allSelected,
			'M_OK_SELECTED' => $okSelected,
			'M_SUSPENDED_SELECTED' => $suspendedSelected,
		)
	);
}
