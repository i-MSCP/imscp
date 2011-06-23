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


/*******************************************************************************
 * Script functions
 */

/**
 * Generate page
 *
 * @param  iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function generatePage($tpl)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $default_language = $cfg->USER_INITIAL_LANG;

    //$index = 0;

    foreach($db->metaTables() as $tableName) {
        // Is not a language database table ?
        if (strpos($tableName, 'lang_') === false) {
            continue;
        }

        $stmt = array();

        foreach (array('imscp_language', 'imscp_languageSetlocaleValue',
            'imscp_languageRevision') as $msgstr
        ) {
            $stmt[] = exec_query(
                "SELECT `msgstr` FROM `$tableName` WHERE `msgid` = '$msgstr'");
        }

         // User better language name if available
         if ($stmt[0]->recordCount() !== 0) {
            $languageName = $stmt[0]->fields['msgstr'];
         } else {
             $languageName = substr($tableName, strrpos($tableName, '_') + 1);
         }


        if ($stmt[2]->recordCount() !== 0 && $stmt[2]->fields['msgstr'] != '' &&
            class_exists('DateTime')
        ) {
            $datetime = new DateTime($stmt[2]->fields['msgstr']);
            $languageRevision = $datetime->format('Y-m-d H:i');
        } else {
            $languageRevision = tr('Unknown');
        }

        $stmt = exec_query(
            "SELECT COUNT(`user_id`) `cnt` FROM `user_gui_props` WHERE `lang` =  ?",
            $tableName
        );

        if ($stmt->fields['cnt'] > 0) {
            $tpl->assign(array('TR_UNINSTALL' => tr('N/A'),
                              'LANG_DELETE_LINK' => '',
                              'LANGUAGE' => tohtml($languageName),
                              'LANGUAGE_REVISION' => $languageRevision,
                              'LANG_VALUE_CHECKED' => $default_language == $tableName
                                  ? $cfg->HTML_CHECKED : '',
                              'LANG_VALUE' => $tableName,
                              'LANG_DELETE_LINK' => ''));

            $tpl->parse('LANG_SHOW', 'lang_show');
        } else {
            $tpl->assign(array('TR_UNINSTALL' => tr('Uninstall'),
                              'URL_DELETE' => "language_delete.php?delete_lang=$tableName",
                              'LANG_DELETE_SHOW' => '',
                              'LANGUAGE' => tohtml($languageName),
                              'LANGUAGE_REVISION' => $languageRevision,
                              'LANG_VALUE_CHECKED' => $default_language == $tableName
                                  ? $cfg->HTML_CHECKED : '',
                              'LANG_VALUE' => $tableName,
                              'LANG_SHOW' => ''));

            $tpl->parse('LANG_DELETE_LINK', 'lang_delete_link');
        }

        // Retrieving number of translated messages
        $stmt = exec_query("SELECT COUNT(`msgid`) AS `cnt` FROM `$tableName`");

        $tpl->assign('MESSAGES', tr('%d messages translated', $stmt->fields['cnt'] - 4));
        /*
        $tpl->assign(array('MESSAGES' => tr('%d messages translated', $stmt->fields['cnt'] - 4),
                          'URL_EXPORT' => "multilanguage_export.php?export_lang=$tableName",
                          'INDEX' => $index,
                          'TR_GZIPPED' => tr('Gzipped')));
        */

        $tpl->parse('LANG_ROW', '.lang_row');
    }
}

/**
 * Imports the translation strings from a language file.
 *
 * @return void
 */
function importLanguageFile()
{
    $filePath = $_FILES['languageFile']['tmp_name'];

    if (!is_readable($filePath)) {
        set_page_message(tr('Upload fileno readable error.'), 'error');
        return;
    }

    if(($parseResult = _parseGettextFile($filePath)) === false) {
        return;
    }

    if (empty($parseResult['imscp_table']) || empty($parseResult['imscp_language'])
        || !preg_match('/^[a-z0-9_]+$/Di', $parseResult['imscp_table'])
    ) {
        set_page_message(tr('The file does not contain all the necessary information.'), 'error');
        return;
    }

    /** @var $db iMSCP_Database */
    $db = iMSCP_Registry::get('db');

    $languageTable = 'lang_' . $parseResult['imscp_table'];
    $isUpdate = false;

    if (in_array($languageTable, $db->metaTables())) {
        execute_query("DROP TABLE IF EXISTS `$languageTable`;");
        $isUpdate = true;
    }

    $query = "
			CREATE TABLE
			    `$languageTable` (
				    `msgid` text collate utf8_unicode_ci,
				    `msgstr` text collate utf8_unicode_ci,
				    KEY `msgid` (msgid(25))
			    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
			;
		";
    execute_query($query);

    $parameters = array();

    foreach ($parseResult as $msgid => $msgstr) {
        $parameters[] = $msgid;
        $parameters[] = $msgstr;
    }

    $queryPart = str_repeat('(?,?), ', count($parseResult));
    $query = "INSERT INTO `$languageTable`  (`msgid`, `msgstr`) VALUES " .
             substr_replace($queryPart, '', strrpos($queryPart, ','));

    exec_query($query, $parameters);

    if (!$isUpdate) {
        write_log(tr('%s added new language: %s', $_SESSION['user_logged'],
                     $parseResult['imscp_language']));

        set_page_message(tr('Language was successfully installed.'), 'success');
    } else {
        write_log(tr('%s updated language: %s', $_SESSION['user_logged'],
                     $parseResult['imscp_language']));

        set_page_message(tr('Language was successfully updated.'), 'success');
    }
}

/**
 * Imports all translation string from a portable object (po) file.
 *
 * @param  string $file Absolute path to the uploaded file
 * @return mixed array An array that contains all translation string or FALSE on
 *                     failure
 */
function _parseGettextFile($file)
{
    if(($content = @file_get_contents($file)) == false) {
        set_page_message(tr("Unable to read the language file."), 'error');
        return false;
    }

    if (empty($content)) {
        set_page_message(tr("The file doesn't contains any content"), 'error');
        return false;
    }

    $parseResult = array();

    // Parses all messages
    $countMessages = preg_match_all(
        '/(msgid\s+("([^"]|\\\\")*?"\s*)+)\s+(msgstr\s+("([^"]|\\\\")*?"\s*)+)/',
        $content, $messagesStack
    );

    // Normalize all messages id and messages strings
    for ($i = 0; $i < $countMessages; $i++) {
        $msgid = preg_replace('/\s*msgid\s*"(.*)"\s*/s', '\\1', $messagesStack[1][$i]);
        $msgstr = preg_replace('/\s*msgstr\s*"(.*)"\s*/s', '\\1', $messagesStack[4][$i]);

        if (!empty($msgstr)) {
            $parseResult[_decodePoFileString((!empty($msgid) ? $msgid : 'headers'))] =
                _decodePoFileString($msgstr);
        }
    }

    // Sets the language
    if (isset($parseResult['_: Localised language'])) {
        $parseResult['imscp_language'] = $parseResult['_: Localised language'];
        unset($parseResult['_: Localised language']);
    } else {
        return false;
    }

    // Parses some relevant header information
    if (isset($parseResult['headers'])) {
        $headers = array();

        foreach (explode("\n", $parseResult['headers']) as $header) {
            if (($np = strpos($header, ':')) !== false) {
                $key = substr($header, 0, $np);
                $headers[$key] = trim(substr($header, $np + 1));
            }
        }

        // Use the Language header as database language table name
        if(isset($headers['Language'])) {
            if(!empty($headers['Language'])) {
                $parseResult['imscp_table'] = trim($headers['Language']);
            } else {
                set_page_message(tr("The 'Language' header doesn't contains the required value"), 'error');
                return false;
            }
        } else {
            set_page_message(tr("The language file doesn't contains the required 'Language' header."), 'error');
            return false;
        }

        // Retrieves the i-MSCP_language Revision from the PO-Revision-Date header
        if (isset($headers['PO-Revision-Date'])) {
            // trim timezone
            if (($np = strpos($headers['PO-Revision-Date'], '+')) !== false) {
                $headers['PO-Revision-Date'] = substr($headers['PO-Revision-Date'], 0, $np);
            }

            // currently some problems with hour/minute parsing?!
            $time = strptime($headers['PO-Revision-Date'], '%Y-%m-%d %H:%I');

            $parseResult['imscp_languageRevision'] = sprintf(
                '%04d%02d%02d%02d%02d%02d', $time['tm_year'] + 1900, $time['tm_mon'] + 1,
                $time['tm_mday'], $time['tm_hour'], $time['tm_min'], $time['tm_sec']);
        } else {
            $parseResult['imscp_languageRevision'] = strftime('%Y%m%d%H%I%S');
        }

        unset($parseResult['headers']);
    } else {
        set_page_message(tr("No headers found in language file."), 'error');
        return false;
    }

    // set default encoding to UTF-8 if not present
    if (!isset($parseResult['encoding'])) {
        $parseResult['encoding'] = 'UTF-8';
    }

    return $parseResult;
}


/**
 * Remove leading and trailing quotes, un-escape linefeed, cr, tab and quotes
 *
 * @param string $s
 * @return string Normalized string
 */
function _decodePoFileString($s)
{
    return str_replace(
        array('\\n', '\\r', '\\t', '\"'),
        array("\n", "\r", "\t", '"'),
        preg_replace('/"\s+"/', '', $s));
}

/**
 * Change panel default language.
 *
 * @return void
 */
function changeDefaultLanguage()
{
    if (isset($_POST['default_language'])) {
        /** @var $cfg iMSCP_Config_Handler_File */
        $cfg = iMSCP_Registry::get('config');

        /** @var $dbConfig iMSCP_Config_Handler_Db */
        $defaultLanguage = clean_input($_POST['default_language']);

        /** @var $dbConfig iMSCP_Config_Handler_Db */
        $dbConfig = iMSCP_Registry::get('dbConfig');
        $dbConfig->USER_INITIAL_LANG = $defaultLanguage;
        $cfg->USER_INITIAL_LANG = $defaultLanguage;

        // Ensures language change on next load for current user in case he has not yet
        // his gui properties explicitly set (eg. for the first admin user when i-MSCP
        // was just installed

        $stmt = exec_query(
            "SELECT
                lang
            FROM
                `user_gui_props`
            WHERE
                `user_id` = {$_SESSION['user_id']};
            ;
        ");

        if ($stmt->fields['lang'] == null) {
            unset($_SESSION['user_def_lang']);
        }
    } else {
        return;
    }
}

/*******************************************************************************
 * Main script
 */

// Include needed libraries
require '../include/imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

// Check for login
check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

// Dispatches the request
if (isset($_POST['uaction'])) {
    if($_POST['uaction'] == 'uploadLanguage') {
        importLanguageFile();
    } elseif($_POST['uaction'] == 'changeLanguage') {
        changeDefaultLanguage();
    }
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/multilanguage.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('lang_row', 'page');
$tpl->define_dynamic('lang_show', 'lang_row');
$tpl->define_dynamic('lang_delete_link', 'lang_row');
$tpl->define_dynamic('lang_def', 'lang_row');

$tpl->assign(array(
                  'TR_ADMIN_I18N_PAGE_TITLE' => tr('i-MSCP - Admin/Internationalisation'),
                  'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
                  'THEME_CHARSET' => tr('encoding'),
                  'ISP_LOGO' => get_logo($_SESSION['user_id']),
                  'TR_MULTILANGUAGE' => tr('Internationalization'),
                  'TR_INSTALLED_LANGUAGES' => tr('Installed languages'),
                  'TR_LANGUAGE' => tr('Language'),
                  'TR_MESSAGES' => tr('Messages'),
                  'TR_LANG_REV' => tr('Date'),
                  'TR_DEFAULT' => tr('Panel default language'),
                  'TR_ACTION' => tr('Action'),
                  'TR_SAVE' => tr('Save'),
                  'TR_INSTALL_NEW_LANGUAGE' => tr('Install / Update language'),
                  'TR_LANGUAGE_FILE' => tr('Language file'),
                  'ISP_LOGO' => get_logo($_SESSION['user_id']),
                  'TR_INSTALL' => tr('Install / Update'),
                  'TR_EXPORT' => tr('Export'),
                  'TR_MESSAGE_DELETE' =>
                  tr('Are you sure you want to delete %s?', true, '%s')));

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_settings.tpl');
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
