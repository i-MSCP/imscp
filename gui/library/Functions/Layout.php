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
use Zend_Controller_Action_Helper_FlashMessenger as FlashMessenger;
use Zend_Navigation as Navigation;

/**
 * Must be documented
 *
 * @param  int $userId User unique identifier
 * @return array
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function get_user_gui_props($userId)
{
    $cfg = Registry::get('config');

    $stmt = exec_query('SELECT lang, layout FROM user_gui_props WHERE user_id = ?', $userId);

    if (!$stmt->rowCount()) {
        return [$cfg['USER_INITIAL_LANG'], $cfg['USER_INITIAL_THEME']];
    }

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

    if (empty($row['lang'])
        && empty($row['layout'])
    ) {
        return [$cfg['USER_INITIAL_LANG'], $cfg['USER_INITIAL_THEME']];
    }

    if (empty($row['lang'])) {
        return [$cfg['USER_INITIAL_LANG'], $row['layout']];
    }

    if (empty($row['layout'])) {
        return [$row['lang'], $cfg['USER_INITIAL_THEME']];
    }

    return [$row['lang'], $row['layout']];
}

/**
 * Sets a page message to display on client browser
 *
 * @param string $message $message Message to display
 * @param string $level Message level (static_)?(info|warning|error|success)
 * @return void
 * @throws Zend_Exception
 */
function set_page_message($message, $level = 'info')
{
    $level = strtolower($level);

    if (Registry::isRegistered('flashMessenger')) {
        $flashMessenger = Registry::get('flashMessenger');
    } else {
        $flashMessenger = new FlashMessenger();
        Registry::set('flashMessenger', $flashMessenger);
    }

    $flashMessenger->addMessage($message, $level);
}

/**
 * Generates page messages
 *
 * @param TemplateEngine $tpl
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 */
function generatePageMessage(TemplateEngine $tpl)
{
    $flashMessenger = Registry::isRegistered('flashMessenger') ? Registry::get('flashMessenger') : new FlashMessenger();

    EventsManager::getInstance()->dispatch(Events::onGeneratePageMessages, ['flashMessenger' => $flashMessenger]);

    $tpl->assign('PAGE_MESSAGE', '');

    foreach (
        [
            'success', 'error', 'warning', 'info', 'static_success', 'static_error', 'static_warning', 'static_info'
        ] as $level
    ) {
        if (!$flashMessenger->hasCurrentMessages($level)) {
            continue;
        }

        $tpl->assign([
            'MESSAGE_CLS' => $level,
            'MESSAGE'     => implode("<br>\n", $flashMessenger->getCurrentMessages($level))
        ]);
        $tpl->parse('PAGE_MESSAGE', '.page_message');

        $flashMessenger->clearCurrentMessages($level);
    }

    foreach (
        [
            'success', 'error', 'warning', 'info', 'static_success', 'static_error', 'static_warning', 'static_info'
        ] as $level
    ) {
        if (!$flashMessenger->hasMessages($level)) {
            continue;
        }

        $tpl->assign([
            'MESSAGE_CLS' => $level,
            'MESSAGE'     => implode("<br>\n", $flashMessenger->getMessages($level))
        ]);
        $tpl->parse('PAGE_MESSAGE', '.page_message');
    }
}

/**
 * format message(s) to be displayed on client browser as page message
 *
 * @throws iMSCPException
 * @param  string|array $messages Message or stack of messages to be concatenated
 * @return string Concatenated messages
 */
function format_message($messages)
{
    $string = '';

    if (is_array($messages)) {
        foreach ($messages as $message) {
            $string .= $message . "<br>\n";
        }
    } elseif (is_string($messages)) {
        $string = $messages;
    } else {
        throw new iMSCPException('set_page_message() expects a string or an array for $messages.');
    }

    return $string;
}

/**
 * Gets menu variables
 *
 * @param  string $menuLink Menu link
 * @return mixed
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function get_menu_vars($menuLink)
{
    if (strpos($menuLink, '}') === false || strpos($menuLink, '}') === false) {
        return $menuLink;
    }

    $row = exec_query(
        '
            SELECT fname, lname, firm, zip, city, state, country, email, phone, fax, street1, street2
            FROM admin
            WHERE admin_id = ?
        ',
        $_SESSION['user_id']
    )->fetchRow(PDO::FETCH_ASSOC);

    $search = [];
    $replace = [];

    $search [] = '{uid}';
    $replace[] = $_SESSION['user_id'];
    $search [] = '{uname}';
    $replace[] = tohtml($_SESSION['user_logged']);
    $search [] = '{fname}';
    $replace[] = tohtml($row['fname']);
    $search [] = '{lname}';
    $replace[] = tohtml($row['lname']);
    $search [] = '{company}';
    $replace[] = tohtml($row['firm']);
    $search [] = '{zip}';
    $replace[] = tohtml($row['zip']);
    $search [] = '{city}';
    $replace[] = tohtml($row['city']);
    $search [] = '{state}';
    $replace[] = tohtml($row['state']);
    $search [] = '{country}';
    $replace[] = tohtml($row['country']);
    $search [] = '{email}';
    $replace[] = tohtml($row['email']);
    $search [] = '{phone}';
    $replace[] = tohtml($row['phone']);
    $search [] = '{fax}';
    $replace[] = tohtml($row['fax']);
    $search [] = '{street1}';
    $replace[] = tohtml($row['street1']);
    $search [] = '{street2}';
    $replace[] = tohtml($row['street2']);

    $row = exec_query(
        'SELECT domain_name, domain_admin_id FROM domain WHERE domain_admin_id = ?', $_SESSION['user_id']
    )->fetchRow(PDO::FETCH_ASSOC);

    $search [] = '{domain_name}';
    $replace[] = $row['domain_name'];

    return str_replace($search, $replace, $menuLink);
}

/**
 * Returns available color set for current layout
 *
 * @return array
 * @throws Zend_Exception
 */
function layout_getAvailableColorSet()
{
    static $colorSet = NULL;

    if (NULL !== $colorSet) {
        return $colorSet;
    }

    $cfg = Registry::get('config');

    if (file_exists($cfg['ROOT_TEMPLATE_PATH'] . '/info.php')) {
        $themeInfo = include_once($cfg['ROOT_TEMPLATE_PATH'] . '/info.php');

        if (is_array($themeInfo)) {
            $colorSet = (array)$themeInfo['theme_color_set'];
        } else {
            ini_set('display_errors', 1);
            trigger_error(sprintf("The 'theme_color'_set parameter is missing in the %s file", $cfg['ROOT_TEMPLATE_PATH'] . '/info.php'), E_USER_ERROR);
        }
    } else {
        trigger_error(sprintf("File %s is missing or not readable", $cfg['ROOT_TEMPLATE_PATH'] . '/info.php'), E_USER_ERROR);
    }

    return $colorSet;
}

/**
 * Returns layout color for given user
 *
 * @param int $userId user unique identifier
 * @return string User layout color
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function layout_getUserLayoutColor($userId)
{
    static $layoutColor = NULL;

    if (NULL !== $layoutColor) {
        return $layoutColor;
    }

    if (isset($_SESSION['user_theme_color'])) {
        $layoutColor = $_SESSION['user_theme_color'];
        return $layoutColor;
    }

    $allowedColors = layout_getAvailableColorSet();
    $layoutColor = exec_query(
        'SELECT layout_color FROM user_gui_props WHERE user_id = ?', $userId
    )->fetchRow(PDO::FETCH_COLUMN);

    if (!$layoutColor || !in_array($layoutColor, $allowedColors)) {
        $layoutColor = array_shift($allowedColors);
    }

    return $layoutColor;
}

/**
 * Init layout
 *
 * @param iMSCP_Events_Event $event
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @todo Use cookies to store user UI properties (Remember me implementation?)
 */
function layout_init($event)
{
    $cfg = Registry::get('config');

    if ($cfg['DEBUG']) {
        $themesAssetsVersion = time();
    } else {
        $themesAssetsVersion = $cfg['THEME_ASSETS_VERSION'];
    }

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

    /** @var $tpl TemplateEngine */
    $tpl = $event->getParam('templateEngine');
    $tpl->assign([
        'THEME_CHARSET'        => 'UTF-8',
        'THEME_ASSETS_PATH'    => '/themes/' . $cfg->USER_INITIAL_THEME . '/assets',
        'THEME_ASSETS_VERSION' => $themesAssetsVersion,
        'THEME_COLOR'          => $color,
        'ISP_LOGO'             => (isset($_SESSION['user_id'])) ? layout_getUserLogo() : '',
        'JS_TRANSLATIONS'      => i18n_getJsTranslations()
    ]);
    $tpl->parse('LAYOUT', 'layout');
}

/**
 * Sets given layout color for given user
 *
 * @param int $userId User unique identifier
 * @param string $color Layout color
 * @return bool TRUE on success false otherwise
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function layout_setUserLayoutColor($userId, $color)
{
    if (!in_array($color, layout_getAvailableColorSet())) {
        return false;
    }

    exec_query('UPDATE user_gui_props SET layout_color = ? WHERE user_id = ?', [$color, $userId]);

    // Dealing with sessions across multiple browsers for same user identifier - Begin

    $sessionId = session_id();
    $stmt = exec_query(
        'SELECT session_id FROM login WHERE user_name = ? AND session_id <> ?', [
            encode_idna($_SESSION['user_logged']), $sessionId
        ]
    );

    if (!$stmt->rowCount()) {
        return true;
    }

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

    // Dealing with data across multiple sessions - End
    return true;
}

/**
 * Get user logo path
 *
 * Only administrators and resellers can have their own logo.
 *
 * Search is done in the following order:
 *  user logo -> user's creator logo -> theme logo --> isp logo
 *
 * @param bool $searchForCreator Tell whether or not search must be done for
 *                               user's creator in case no logo is found for
 *                               user
 * @param bool $returnDefault Tell whether or not default logo must be returned
 * @return string User logo path.
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 * @todo cache issues
 */
function layout_getUserLogo($searchForCreator = true, $returnDefault = true)
{
    $cfg = Registry::get('config');

    // On switched level, we want show logo from logged user
    if (isset($_SESSION['logged_from_id']) && $searchForCreator) {
        $userId = $_SESSION['logged_from_id'];
        // Customers inherit the logo of their reseller
    } elseif ($_SESSION['user_type'] == 'user') {
        $userId = $_SESSION['user_created_by'];
    } else {
        $userId = $_SESSION['user_id'];
    }


    $stmt = exec_query('SELECT logo FROM user_gui_props WHERE user_id= ?', $userId);

    // No logo is found for the user, let see for it creator
    if ($searchForCreator && $userId != 1 && empty($stmt->fields['logo'])) {
        $stmt = exec_query(
            '
                SELECT b.logo
                FROM admin a
                LEFT JOIN user_gui_props b ON (b.user_id = a.created_by)
                WHERE a.admin_id= ?
            ',
            $userId
        );
    }

    // No user logo found
    if (empty($stmt->fields['logo'])
        || !file_exists($cfg['GUI_ROOT_DIR'] . '/data/persistent/ispLogos/' . $stmt->fields['logo'])
    ) {
        if (!$returnDefault) {
            return '';
        }

        if (file_exists($cfg['ROOT_TEMPLATE_PATH'] . '/assets/images/imscp_logo.png')) {
            return '/themes/' . $_SESSION['user_theme'] . '/assets/images/imscp_logo.png';
        }

        // no logo available, we are using default
        return $cfg['ISP_LOGO_PATH'] . '/' . 'isp_logo.gif';
    }

    return $cfg['ISP_LOGO_PATH'] . '/' . $stmt->fields['logo'];
}

/**
 * Updates user logo
 *
 * Note: Only administrators and resellers can have their own logo.
 *
 * @return bool TRUE on success, FALSE otherwise
 * @throws Zend_Exception
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Exception
 */
function layout_updateUserLogo()
{
    $cfg = Registry::get('config');

    // closure that is run before move_uploaded_file() function - See the
    // Utils_UploadFile() function for further information about implementation
    // details
    $beforeMove = function ($cfg) {
        $tmpFilePath = $_FILES['logoFile']['tmp_name'];

        // Checking file mime type
        if (!($fileMimeType = checkMimeType($tmpFilePath, ['image/gif', 'image/jpeg', 'image/pjpeg', 'image/png']))) {
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
        list($imageWidth, $imageHeight) = getimagesize($tmpFilePath);

        // Checking image size
        if ($imageWidth > 500 || $imageHeight > 90) {
            set_page_message(tr('Images have to be smaller than 500 x 90 pixels.'), 'error');
            return false;
        }

        // Building an unique file name
        $fileName = sha1(\iMSCP\Crypt::randomStr(15) . '-' . $_SESSION['user_id']) . '.' . $fileExtension;

        // Return destination file path
        return $cfg['GUI_ROOT_DIR'] . '/data/persistent/ispLogos/' . $fileName;
    };

    if (($logoPath = utils_uploadFile('logoFile', [$beforeMove, $cfg])) === false) {
        return false;
    }

    if ($_SESSION['user_type'] == 'admin') {
        $userId = 1;
    } else {
        $userId = $_SESSION['user_id'];
    }

    // We must catch old logo before update
    $oldLogoFile = layout_getUserLogo(false, false);

    exec_query('UPDATE user_gui_props SET logo = ? WHERE user_id = ?', [basename($logoPath), $userId]);

    // Deleting old logo (we are safe here) - We don't return FALSE on failure.
    // The administrator will be warned through logs.
    layout_deleteUserLogo($oldLogoFile, true);
    return true;
}

/**
 * Deletes user logo
 *
 * @param string $logoFilePath OPTIONAL Logo file path
 * @param bool $onlyFile OPTIONAL Tell whether or not only logo file must be
 *                       deleted
 * @return bool TRUE on success, FALSE otherwise
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function layout_deleteUserLogo($logoFilePath = NULL, $onlyFile = false)
{
    $cfg = Registry::get('config');

    if (NULL === $logoFilePath) {
        if ($_SESSION['user_type'] == 'admin') {
            $logoFilePath = layout_getUserLogo(true);
        } else {
            $logoFilePath = layout_getUserLogo(false);
        }
    }

    $userId = ($_SESSION['user_type'] == 'admin') ? 1 : $_SESSION['user_id'];

    if (!$onlyFile) {
        exec_query('UPDATE user_gui_props SET logo = ? WHERE user_id = ?', [NULL, $userId]);
    }

    if (strpos($logoFilePath, $cfg['ISP_LOGO_PATH']) === false) {
        return true;
    }

    $logoFilePath = $cfg['GUI_ROOT_DIR'] . '/data/persistent/ispLogos/' . basename($logoFilePath);

    if (file_exists($logoFilePath) && !@unlink($logoFilePath)) {
        write_log(sprintf("Couldn't remove the %s file.", $logoFilePath), E_USER_WARNING);
        return false;
    }

    return true;
}

/**
 * Is user logo?
 *
 * @param string $logoPath Logo path to match against
 * @return bool TRUE if $logoPath is a user's logo, FALSE otherwise
 * @throws Zend_Exception
 */
function layout_isUserLogo($logoPath)
{
    if ($logoPath == '/themes/' . $_SESSION['user_theme'] . '/assets/images/imscp_logo.png'
        || $logoPath == Registry::get('config')['ISP_LOGO_PATH'] . '/' . 'isp_logo.gif'
    ) {
        return false;
    }

    return true;
}

/**
 * Load navigation file for current UI level
 *
 * @return void
 * @throws Zend_Exception
 * @throws Zend_Navigation_Exception
 */
function layout_LoadNavigation()
{
    if (!isset($_SESSION['user_type'])) {
        return;
    }

    switch ($_SESSION['user_type']) {
        case 'admin':
            $userLevel = 'admin';
            break;
        case 'reseller':
            $userLevel = 'reseller';
            break;
        default:
            $userLevel = 'client';
    }

    Registry::set('navigation', new Navigation(
        include(Registry::get('config')['ROOT_TEMPLATE_PATH'] . "/$userLevel/navigation.php")
    ));

    // Set main menu labels visibility for the current environment
    EventsManager::getInstance()->registerListener(
        Events::onBeforeGenerateNavigation, 'layout_setMainMenuLabelsVisibilityEvt'
    );
}

/**
 * Tells whether or not main menu labels are visible for the given user
 *
 * @param int $userId User unique identifier
 * @return bool
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function layout_isMainMenuLabelsVisible($userId)
{
    $stmt = exec_query('SELECT show_main_menu_labels FROM user_gui_props WHERE user_id = ?', $userId);

    if ($stmt->rowCount()) {
        return (bool)$stmt->fetchRow(PDO::FETCH_COLUMN);
    }

    return true;
}

/**
 * Sets main menu label visibility for the given user
 *
 * @param int $userId User unique identifier
 * @param int $visibility (0|1)
 * @return void
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Exception_Database
 */
function layout_setMainMenuLabelsVisibility($userId, $visibility)
{
    $visibility = ($visibility) ? 1 : 0;

    exec_query('UPDATE user_gui_props SET show_main_menu_labels = ? WHERE user_id = ?', [$visibility, $userId]);

    if (!isset($_SESSION['logged_from_id'])) {
        $_SESSION['show_main_menu_labels'] = $visibility;
    }
}

/**
 * Sets main menu visibility for current environment
 *
 * @return void
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_Events_Exception
 */
function layout_setMainMenuLabelsVisibilityEvt()
{
    if (!isset($_SESSION['show_main_menu_labels']) && isset($_SESSION['user_type'])) {
        $userId = isset($_SESSION['logged_from_id']) ? $_SESSION['logged_from_id'] : $_SESSION['user_id'];
        $_SESSION['show_main_menu_labels'] = layout_isMainMenuLabelsVisible($userId);
    }
}
