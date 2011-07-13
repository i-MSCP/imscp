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
