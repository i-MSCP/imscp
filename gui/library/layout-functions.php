<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2011 by i-MSCP | http://i-mscp.net
 * @version     SVN: $Id$
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 *
 * @license
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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2011 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

if (isset($_SESSION['user_id']) && !isset($_SESSION['logged_from']) &&
    !isset($_SESSION['logged_from_id'])
) {
    list($user_def_lang, $user_def_layout) = get_user_gui_props($_SESSION['user_id']);
    $_SESSION['user_theme'] = $user_def_layout;
    $_SESSION['user_def_lang'] = $user_def_lang;
}

/**
 * Must be documented.
 *
 * @param  $user_id
 * @return array
 */
function get_user_gui_props($user_id)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $query = "
		SELECT
			`lang`, `layout`
		FROM
			`user_gui_props`
		WHERE
			`user_id` = ?
	";
    $stmt = exec_query($query, $user_id);

    if ($stmt->recordCount() == 0 ||
        (empty($stmt->fields['lang']) && empty($stmt->fields['layout']))
    ) {
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
 * Generate page message (info, warning, error, success).
 *
 * Note: The default message type is set to 'info'.
 * See the set_page_message() function for more information.
 *
 * @param  iMSCP_pTemplate $tpl iMSCP_pTemplate instance
 * @return void
 */
function generatePageMessage($tpl)
{
    if (!isset($_SESSION['user_page_message'])) {
        $tpl->assign('PAGE_MESSAGE', '');
    } else {
        $tpl->assign(array(
                          'MESSAGE_CLS' => $_SESSION['user_page_message_cls'],
                          'MESSAGE' => $_SESSION['user_page_message']));

        unset($_SESSION['user_page_message'], $_SESSION['user_page_message_cls']);
    }
}

/**
 * Sets a message to raise.
 *
 * @param string $message $message Message to raise
 * @param string $level Message level
 * @return void
 */
function set_page_message($message, $level = 'info')
{
    if ($level != 'info' && $level != 'warning' && $level != 'error' &&
        $level != 'success'
    ) {
        throw new iMSCP_Exception('Wrong level for page message.');
    }

    if (isset($_SESSION['user_page_message'])) {
        $_SESSION['user_page_message'] .= "\n<br />$message";
    } else {
        $_SESSION['user_page_message'] = $message;
    }

    $_SESSION['user_page_message_cls'] = $level;
}

/**
 * Converts a Array of Strings to a single String.
 *
 * @param  array $messages Stack of messages to be concatenated
 * @return string Concatenated messages
 */
function format_message($messages)
{
    $string = '';

    foreach ((array)$messages as $message) {
        $string .= $message . "<br />\n";
    }

    return $string;
}

/**
 * Gets menu variables.
 *
 * @param  string $menu_link Menu link
 * @return mixed
 */
function get_menu_vars($menu_link)
{
    $query = "
		SELECT
			`customer_id`, `fname`, `lname`, `firm`, `zip`, `city`, `state`,
			`country`, `email`, `phone`, `fax`, `street1`, `street2`
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

    $query = "
		SELECT
			`domain_name`, `domain_admin_id`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
	";
    $stmt = exec_query($query, $_SESSION['user_id']);

    $search [] = '{domain_name}';
    $replace[] = $stmt->fields['domain_name'];

    return str_replace($search, $replace, $menu_link);
}

/**
 * Must be documented.
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @param  $user_def_layout
 * @return void
 * @todo currently not being used because there's only one layout/theme
 */
function gen_def_layout($tpl, $user_def_layout)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $layouts = array('blue', 'green', 'red', 'yellow');

    foreach ($layouts as $layout) {
        $selected = ($layout === $user_def_layout) ? $cfg->HTML_SELECTED : '';

        $tpl->assign(array(
                          'LAYOUT_VALUE' => $layout,
                          'LAYOUT_SELECTED' => $selected,
                          'LAYOUT_NAME' => $layout));

        $tpl->parse('DEF_LAYOUT', '.def_layout');
    }
}

/**
 * Get user logo path.
 *
 * Note: Only administrators and resellers can have their own logo. Search is done in
 * the following order: user logo -> user's creator logo -> theme logo --> isp logo.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @since i-MSCP 1.0.1.4
 * @param bool $searchForCreator Tell whether or not search must be done for user's
 *                               creator in case no logo is found for user
 * @param bool $returnDefault    Tell whether or not default logo must be returned
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

    // No  user logo found
    if (empty($stmt->fields['logo'])) {
        if (!$returnDefault) {
            return '';
        } elseif (file_exists($cfg->GUI_ROOT_DIR . '/public/themes/' .
                              $_SESSION['user_theme'] . '/images/imscp_logo.png')
        ) {
            return '../themes/' . $_SESSION['user_theme'] . '/images/imscp_logo.png';
        } else {
            // no logo available, we are using default
            return $cfg->ISP_LOGO_PATH . '/' . 'isp_logo.gif';
        }
    }

    return $cfg->ISP_LOGO_PATH . '/' . $stmt->fields['logo'];
}

/**
 * Updates user logo.
 *
 * Note: Only administrators and resellers can have their own logo.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @since i-MSCP 1.0.1.4
 * @return bool TRUE on success, FALSE otherwise
 */
function layout_updateUserLogo()
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    // closure that is run before move_uploaded_file() function - See the
    // Utils_UploadFile() function for further information about implementation
    // details
    $beforeMove = function($cfg)
    {
        $tmpFilePath = $_FILES['logoFile']['tmp_name'];

        // Checking file mime type
        if (!($fileMimeType = checkMimeType($tmpFilePath, array('image/gif',
                                                               'image/jpeg',
                                                               'image/pjpeg',
                                                               'image/png')))
        ) {
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
        $fileName = sha1(rand(10, 10) . '-' . $_SESSION['user_id']) .
                    '.' . $fileExtension;

        // Return destination file path
        return $cfg->GUI_ROOT_DIR . '/data/ispLogos/' . $fileName;
    };

    if (($logoPath = utils_uploadFile('logoFile', array($beforeMove, $cfg)))) {
        if ($_SESSION['user_type'] == 'admin') {
            $userId = 1;
        } else {
            $userId = $_SESSION['user_id'];
        }

        // We must catch old logo before update
        $oldLogoFile = layout_getUserLogo(false, false);

        $query = "UPDATE `user_gui_props` SET `logo` = ? WHERE `user_id` = ?";
        exec_query($query, array(basename($logoPath), $userId));

        // Delete old logo
        layout_deleteUserLogo($oldLogoFile, true);
    } else {
        return false;
    }

    return true;
}


/**
 * Deletes user logo.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @since i-MSCP 1.0.1.4
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

    if (strpos($logoFilePath, $cfg->ISP_LOGO_PATH) !== false) {
        $logoFilePath = $cfg->GUI_ROOT_DIR . '/data/ispLogos/' . basename($logoFilePath);

        if (file_exists($logoFilePath)) { // Make this function safe
            if (@unlink($logoFilePath)) {
                if (!$onlyFile) {
                    $query = "UPDATE `user_gui_props` SET `logo` = ? WHERE `user_id` = ?";
                    exec_query($query, array(0, $userId));
                }

                return true;
            } else {
                write_log(tr("System is unable to remove '%s' user logo.", $logoFilePath), E_USER_WARNING);
                return false;
            }
        }
    }

    return true;
}

/**
 * Is user logo?
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @since i-MSCP 1.0.1.4
 * @param $logoPath Logo path to match against
 * @return bool TRUE if $logoPath is an user's logo, FALSE otherwise
 */
function layout_isUserLogo($logoPath)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    if ($logoPath == '../themes/' . $_SESSION['user_theme'] . '/images/imscp_logo.png'
        || $logoPath == $cfg->ISP_LOGO_PATH . '/' . 'isp_logo.gif'
    ) {
        return false;
    }

    return true;
}
