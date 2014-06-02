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
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Layout
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 */

/**
 * Must be documented
 *
 * @param  $user_id
 * @return array
 * @todo must be removed
 */
function get_user_gui_props($user_id)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "SELECT `lang`, `layout` FROM `user_gui_props` WHERE `user_id` = ?";
	$stmt = exec_query($query, $user_id);

	if ($stmt->recordCount() == 0 || (empty($stmt->fields['lang']) && empty($stmt->fields['layout']))) {
		// values for user id, some default stuff
		return array($cfg->USER_INITIAL_LANG, $cfg->USER_INITIAL_THEME);
	} elseif (empty($stmt->fields['lang'])) {
		return array($cfg->USER_INITIAL_LANG, $stmt->fields['layout']);
	} elseif (empty($stmt->fields['layout'])) {
		return array($stmt->fields['lang'], $cfg->USER_INITIAL_THEME);
	} else {
		return array($stmt->fields['lang'], $stmt->fields['layout']);
	}
}

/**
 * Generates the page messages to display on client browser
 *
 * Note: The default level for message is sets to 'info'.
 * See the {@link set_page_message()} function for more information.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function generatePageMessage($tpl)
{
	$namespace = new Zend_Session_Namespace('pageMessages');

	if (Zend_Session::namespaceIsset('pageMessages')) {
		foreach (array('success', 'error', 'warning', 'info') as $level) {
			if (isset($namespace->{$level})) {
				$tpl->assign(
					array(
						'MESSAGE_CLS' => $level .
						(($level == 'success') ? ' timeout' : ''),
						'MESSAGE' => $namespace->{$level}
					)
				);

				$tpl->parse('PAGE_MESSAGE', '.page_message');
			}
		}

		Zend_Session::namespaceUnset('pageMessages');
	} else {
		$tpl->assign('PAGE_MESSAGE', '');
	}
}

/**
 * Sets a page message to display on client browser
 *
 * @throws iMSCP_Exception
 * @param string $message $message Message to display
 * @param string $level Message level (INFO, WARNING, ERROR, SUCCESS)
 * @return void
 */
function set_page_message($message, $level = 'info')
{
	$level = strtolower($level);

	if (!is_string($message)) {
		throw new iMSCP_Exception('set_page_message() expects a string for $message');
	} elseif (!in_array($level, array('info', 'warning', 'error', 'success'))) {
		throw new iMSCP_Exception(sprintf('Wrong level %s for page message.', $level));
	}

	static $namespace = null;

	if (null === $namespace) {
		$namespace = new Zend_Session_Namespace('pageMessages');
	}

	if (isset($namespace->{$level})) {
		$namespace->{$level} .= "\n<br />$message";
	} else {
		$namespace->{$level} = $message;
	}
}

/**
 * format message(s) to be displayed on client browser as page message.
 *
 * @throws iMSCP_Exception
 * @param  string|array $messages Message or stack of messages to be concatenated
 * @return string Concatenated messages
 */
function format_message($messages)
{
	$string = '';

	if (is_array($messages)) {
		foreach ($messages as $message) {
			$string .= $message . "<br />\n";
		}
	} elseif (is_string($messages)) {
		$string = $messages;
	} else {
		throw new iMSCP_Exception('set_page_message() expects a string or an array for $messages.');
	}

	return $string;
}

/**
 * Gets menu variables
 *
 * @param  string $menuLink Menu link
 * @return mixed
 */
function get_menu_vars($menuLink)
{
	if (strpos($menuLink, '}') === false || strpos($menuLink, '}') === false) {
		return $menuLink;
	}

	$query = "
		SELECT
			`customer_id`, `fname`, `lname`, `firm`, `zip`, `city`, `state`, `country`, `email`, `phone`, `fax`,
			`street1`, `street2`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
	";
	$stmt = exec_query($query, $_SESSION['user_id']);

	$search = array();
	$replace = array();

	$search [] = '{uid}';
	$replace[] = $_SESSION['user_id'];
	$search [] = '{uname}';
	$replace[] = tohtml($_SESSION['user_logged']);
	$search [] = '{cid}';
	$replace[] = tohtml($stmt->fields['customer_id']);
	$search [] = '{fname}';
	$replace[] = tohtml($stmt->fields['fname']);
	$search [] = '{lname}';
	$replace[] = tohtml($stmt->fields['lname']);
	$search [] = '{company}';
	$replace[] = tohtml($stmt->fields['firm']);
	$search [] = '{zip}';
	$replace[] = tohtml($stmt->fields['zip']);
	$search [] = '{city}';
	$replace[] = tohtml($stmt->fields['city']);
	$search [] = '{state}';
	$replace[] = $stmt->fields['state'];
	$search [] = '{country}';
	$replace[] = tohtml($stmt->fields['country']);
	$search [] = '{email}';
	$replace[] = tohtml($stmt->fields['email']);
	$search [] = '{phone}';
	$replace[] = tohtml($stmt->fields['phone']);
	$search [] = '{fax}';
	$replace[] = tohtml($stmt->fields['fax']);
	$search [] = '{street1}';
	$replace[] = tohtml($stmt->fields['street1']);
	$search [] = '{street2}';
	$replace[] = tohtml($stmt->fields['street2']);

	$query = 'SELECT `domain_name`, `domain_admin_id` FROM `domain` WHERE `domain_admin_id` = ?';
	$stmt = exec_query($query, $_SESSION['user_id']);

	$search [] = '{domain_name}';
	$replace[] = $stmt->fields['domain_name'];

	return str_replace($search, $replace, $menuLink);
}

/**
 * Returns available color set for current layout
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @return array
 */
function layout_getAvailableColorSet()
{
	static $colorSet = null;

	if (null === $colorSet) {
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		if (file_exists($cfg->ROOT_TEMPLATE_PATH . '/info.php')) {
			$themeInfo = include_once($cfg->ROOT_TEMPLATE_PATH . '/info.php');

			if (is_array($themeInfo)) {
				$colorSet = (array)$themeInfo['theme_color_set'];
			} else {
				ini_set('display_errors', 1);
				trigger_error(
					sprintf(
						"The 'theme_color'_set parameter is missing in the %s file",
						$cfg->ROOT_TEMPLATE_PATH . '/info.php'
					),
					E_USER_ERROR
				);
			}
		} else {
			trigger_error(
				sprintf("File %s is missing or not readable", $cfg->ROOT_TEMPLATE_PATH . '/info.php'), E_USER_ERROR
			);
		}
	}

	return $colorSet;
}

/**
 * Returns layout color for given user
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param int $userId user unique identifier
 * @return string User layout color
 */
function layout_getUserLayoutColor($userId)
{
	static $color = null;

	if (null === $color) {
		if (isset($_SESSION['user_theme_color'])) {
			$color = $_SESSION['user_theme_color'];
		} else {
			$allowedColors = layout_getAvailableColorSet();

			$query = 'SELECT `layout_color` FROM `user_gui_props` WHERE `user_id` = ?';
			$stmt = exec_query($query, (int)$userId);

			if ($stmt->rowCount()) {
				$color = $stmt->fields['layout_color'];

				if (!$color || !in_array($color, $allowedColors)) {
					$color = array_shift($allowedColors);
				}
			} else {
				$color = array_shift($allowedColors);
			}
		}
	}

	return $color;
}

/**
 * Init layout
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param iMSCP_Events_Event $event
 * @return void
 * @todo Use cookies to store user UI properties (Remember me implementation?)
 */
function layout_init($event)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($cfg->DEBUG) {
		$themesAssetsVersion = time();
	} else {
		$themesAssetsVersion = $cfg->THEME_ASSETS_VERSION;
	}

	$encoding = tr('encoding');

	ini_set('default_charset', ($encoding != 'encoding') ? $encoding : 'UTF-8');

	if (isset($_SESSION['user_theme_color'])) {
		$color = $_SESSION['user_theme_color'];
	} elseif (isset($_SESSION['user_id'])) {
		$userId = isset($_SESSION['logged_from_id']) ? $_SESSION['logged_from_id'] : $_SESSION['user_id'];
		$color = layout_getUserLayoutColor($userId);
		$_SESSION['user_theme_color'] = $color;
	} else {
		$colors = layout_getAvailableColorSet();
		$color = array_shift($colors);
	}

	/** @var $tpl iMSCP_pTemplate */
	$tpl = $event->getParam('templateEngine');

	$tpl->assign(
		array(
			'THEME_CHARSET' => ($encoding != 'encoding') ? $encoding : 'UTF-8',
			'THEME_ASSETS_PATH' => '/themes/' . $cfg->USER_INITIAL_THEME . '/assets',
			'THEME_ASSETS_VERSION' => $themesAssetsVersion,
			'THEME_COLOR' => $color
		)
	);

	$tpl->parse('LAYOUT', 'layout');
}

/**
 * Sets given layout color for given user
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param int $userId User unique identifier
 * @param string $color Layout color
 * @return bool TRUE on success false otherwise
 */
function layout_setUserLayoutColor($userId, $color)
{
	if (in_array($color, layout_getAvailableColorSet())) {
		$query = 'UPDATE `user_gui_props` SET `layout_color` = ? WHERE `user_id` = ?';
		exec_query($query, array($color, (int)$userId));

		// Dealing with sessions across multiple browsers for same user identifier - Begin

		$sessionId = session_id();

		$query = "SELECT `session_id` FROM `login` WHERE `user_name` = ?  AND `session_id` <> ?";
		$stmt = exec_query($query, array($_SESSION['user_logged'], $sessionId));

		if ($stmt->rowCount()) {
			foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $otherSessionId) {
				session_write_close();
				session_id($otherSessionId);
				session_start();
				$_SESSION['user_theme_color'] = $color; // Update user layout color
			}

			// Return back to the previous session
			session_write_close();
			session_id($sessionId);
			session_start();
		}

		// Dealing with data across multiple sessions - End

		return true;
	}

	return false;
}

/**
 * Get user logo path
 *
 * Note: Only administrators and resellers can have their own logo. Search is done in the following order:
 * user logo -> user's creator logo -> theme logo --> isp logo.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param bool $searchForCreator Tell whether or not search must be done for user's creator in case no logo is found for user
 * @param bool $returnDefault Tell whether or not default logo must be returned
 * @return string User logo path.
 * @todo cache issues
 */
function layout_getUserLogo($searchForCreator = true, $returnDefault = true)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// On switched level, we want show logo from logged user
	if (isset($_SESSION['logged_from_id']) && $searchForCreator) {
		$userId = $_SESSION['logged_from_id'];
		// Customers inherit the logo of their reseller
	} elseif ($_SESSION['user_type'] == 'user') {
		$userId = $_SESSION['user_created_by'];
	} else {
		$userId = $_SESSION['user_id'];
	}

	$query = 'SELECT `logo` FROM `user_gui_props` WHERE `user_id`= ?';
	$stmt = exec_query($query, $userId);

	// No logo is found for the user, let see for it creator
	if ($searchForCreator && $userId != 1 && empty($stmt->fields['logo'])) {
		$query = '
			SELECT
				`b`.`logo`
			FROM
				`admin` `a`
			LEFT JOIN
				`user_gui_props` `b` ON (`b`.`user_id` = `a`.`created_by`)
			WHERE
				`a`.`admin_id`= ?
        ';
		$stmt = exec_query($query, $userId);
	}

	// No user logo found
	if (
		empty($stmt->fields['logo']) ||
		!file_exists($cfg->GUI_ROOT_DIR . '/data/persistent/ispLogos/' . $stmt->fields['logo'])
	) {
		if (!$returnDefault) {
			return '';
		} elseif (file_exists($cfg->ROOT_TEMPLATE_PATH . '/assets/images/imscp_logo.png')) {
			return '/themes/' . $_SESSION['user_theme'] . '/assets/images/imscp_logo.png';
		} else {
			// no logo available, we are using default
			return $cfg->ISP_LOGO_PATH . '/' . 'isp_logo.gif';
		}
	}

	return $cfg->ISP_LOGO_PATH . '/' . $stmt->fields['logo'];
}

/**
 * Updates user logo
 *
 * Note: Only administrators and resellers can have their own logo.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @return bool TRUE on success, FALSE otherwise
 */
function layout_updateUserLogo()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// closure that is run before move_uploaded_file() function - See the
	// Utils_UploadFile() function for further information about implementation
	// details
	$beforeMove = function ($cfg) {
		$tmpFilePath = $_FILES['logoFile']['tmp_name'];

		// Checking file mime type
		if (!($fileMimeType = checkMimeType($tmpFilePath, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png')))) {
			set_page_message(tr('You can only upload images.'), 'error');
			return false;
		}

		// Retrieving file extension (gif|jpeg|png)
		if ($fileMimeType == 'image/pjpeg' || $fileMimeType == 'image/jpeg') {
			$fileExtension = 'jpeg';
		} else {
			$fileExtension = substr($fileMimeType, -3);
		}

		// Getting the image size
		list($imageWidth, $imageHeigth) = getimagesize($tmpFilePath);

		// Checking image size
		if ($imageWidth > 500 || $imageHeigth > 90) {
			set_page_message(tr('Images have to be smaller than 500 x 90 pixels.'), 'error');
			return false;
		}

		// Building an unique file name
		$fileName = sha1(utils_randomString(15) . '-' . $_SESSION['user_id']) . '.' . $fileExtension;

		// Return destination file path
		return $cfg->GUI_ROOT_DIR . '/data/persistent/ispLogos/' . $fileName;
	};

	if (($logoPath = utils_uploadFile('logoFile', array($beforeMove, $cfg))) === false) {
		return false;
	} else {
		if ($_SESSION['user_type'] == 'admin') {
			$userId = 1;
		} else {
			$userId = $_SESSION['user_id'];
		}

		// We must catch old logo before update
		$oldLogoFile = layout_getUserLogo(false, false);

		exec_query('UPDATE `user_gui_props` SET `logo` = ? WHERE `user_id` = ?', array(basename($logoPath), $userId));

		// Deleting old logo (we are safe here) - We don't return FALSE on failure.
		// The administrator will be warned through logs.
		layout_deleteUserLogo($oldLogoFile, true);
	}

	return true;
}

/**
 * Deletes user logo
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param string $logoFilePath OPTIONAL Logo file path
 * @param bool $onlyFile OPTIONAL Tell whether or not only logo file must be deleted
 * @return bool TRUE on success, FALSE otherwise
 */
function layout_deleteUserLogo($logoFilePath = null, $onlyFile = false)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (null === $logoFilePath) {
		if ($_SESSION['user_type'] == 'admin') {
			$logoFilePath = layout_getUserLogo(true);
		} else {
			$logoFilePath = layout_getUserLogo(false);
		}
	}

	if ($_SESSION['user_type'] == 'admin') {
		$userId = 1;
	} else {
		$userId = $_SESSION['user_id'];
	}

	if (!$onlyFile) {
		exec_query('UPDATE `user_gui_props` SET `logo` = ? WHERE `user_id` = ?', array(null, $userId));
	}

	if (strpos($logoFilePath, $cfg->ISP_LOGO_PATH) !== false) {
		$logoFilePath = $cfg->GUI_ROOT_DIR . '/data/persistent/ispLogos/' . basename($logoFilePath);

		if (file_exists($logoFilePath) && @unlink($logoFilePath)) {
			return true;
		} else {
			write_log(tr("System is unable to remove '%s' user logo.", $logoFilePath), E_USER_WARNING);
			return false;
		}
	}

	return true;
}

/**
 * Is user logo?
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param string $logoPath Logo path to match against
 * @return bool TRUE if $logoPath is a user's logo, FALSE otherwise
 */
function layout_isUserLogo($logoPath)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (
		$logoPath == '/themes/' . $_SESSION['user_theme'] . '/assets/images/imscp_logo.png'
		|| $logoPath == $cfg->ISP_LOGO_PATH . '/' . 'isp_logo.gif'
	) {
		return false;
	}

	return true;
}

/**
 * Load navigation file for current UI level
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @param iMSCP_Events_Event $event
 * @return void
 */
function layout_LoadNavigation($event)
{
	if (isset($_SESSION['user_type'])) {
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		switch ($_SESSION['user_type']) {
			case 'admin':
				$menuPath = "{$cfg->ROOT_TEMPLATE_PATH}/admin/navigation.xml";
				break;
			case 'reseller':
				$menuPath = "{$cfg->ROOT_TEMPLATE_PATH}/reseller/navigation.xml";
				break;
			default:
				$menuPath = "{$cfg->ROOT_TEMPLATE_PATH}/client/navigation.xml";
		}

		iMSCP_Registry::set('navigation', new Zend_Navigation(new Zend_Config_Xml($menuPath, 'navigation')));

		// Set main menu labels visibility for the current environment
		iMSCP_Events_Aggregator::getInstance()->registerListener(
			iMSCP_Events::onBeforeGenerateNavigation, 'layout_setMainMenuLabelsVisibilityEvt'
		);
	}
}

/**
 * Tells whether or not main menu labels are visible for the given user.
 *
 * @param int $userId User unique identifier
 * @return bool
 */
function layout_isMainMenuLabelsVisible($userId)
{
	$stmt = exec_query('SELECT `show_main_menu_labels` FROM `user_gui_props` WHERE `user_id` = ?', $userId);

	if ($stmt->rowCount()) {
		return (bool)$stmt->fields['show_main_menu_labels'];
	}

	return true;
}

/**
 * Sets main menu label visibility for the given user
 *
 * @param int $userId User unique identifier
 * @param int $visibility (0|1)
 * @return void
 */
function layout_setMainMenuLabelsVisibility($userId, $visibility)
{
	$visibility = (int)$visibility;

	$query = 'UPDATE `user_gui_props` SET `show_main_menu_labels` = ? WHERE `user_id` = ?';
	exec_query($query, array($visibility, (int)$userId));

	if (!isset($_SESSION['logged_from_id'])) {
		$_SESSION['show_main_menu_labels'] = $visibility;
	}
}

/**
 * Sets main menu visibility for current environment
 *
 * @param $event iMSCP_Events_Event
 * @return void
 */
function layout_setMainMenuLabelsVisibilityEvt($event)
{
	if (!isset($_SESSION['show_main_menu_labels']) && isset($_SESSION['user_type'])) {
		$userId = isset($_SESSION['logged_from_id']) ? $_SESSION['logged_from_id'] : $_SESSION['user_id'];
		$_SESSION['show_main_menu_labels'] = layout_isMainMenuLabelsVisible($userId);
	}
}
