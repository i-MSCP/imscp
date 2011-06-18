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

    list($userDefinedLanguage) = get_user_gui_props($_SESSION['user_id']);
    $userDefinedLanguage = explode('_', $userDefinedLanguage);

    $index = 0;

    foreach($db->metaTables() as $tableName) {
        // Is language database table ?
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
         if ($stmt[0]->recordCount() != 0) {
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


        if ($cfg->USER_INITIAL_LANG == $tableName || $userDefinedLanguage == $tableName) {
            $tpl->assign(array('TR_UNINSTALL' => tr('N/A'),
                              'LANG_DELETE_LINK' => '',
                              'LANGUAGE' => tohtml($languageName),
                              'LANGUAGE_REVISION' => $languageRevision,
                              'LANG_VALUE_CHECKED' => $cfg->HTML_CHECKED,
                              'LANG_VALUE' => $tableName));

            $tpl->parse('LANG_DELETE_SHOW', 'lang_delete_show');
        } else {
            $tpl->assign(array('TR_UNINSTALL' => tr('Uninstall'),
                              'URL_DELETE' => "language_delete.php?delete_lang=$tableName",
                              'LANG_DELETE_SHOW' => '',
                              'LANGUAGE' => tohtml($languageName),
                              'LANGUAGE_REVISION' => $languageRevision,
                              'LANG_VALUE_CHECKED' => '',
                              'LANG_VALUE' => $tableName));

            $tpl->parse('LANG_DELETE_LINK', 'lang_delete_link');
        }

        // Retrieving number of translated messages
        $query = "SELECT COUNT(`msgid`) AS `cnt` FROM `$tableName`;";
        $stmt = exec_query($query);

        $tpl->assign(array('MESSAGES' => tr('%d messages translated', $stmt->fields['cnt'] - 5),
                          'URL_EXPORT' => "multilanguage_export.php?export_lang=$tableName",
                          'INDEX' => $index,
                          'TR_GZIPPED' => tr('Gzipped')));

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

    if (empty($_FILES['languageFile']['name']) || !is_readable($filePath)) {
        set_page_message(tr('Upload file error!'), 'error');
        return;
    } else {
        $fileName = $_FILES['languageFile']['name'];
    }

    $mimeType = checkMimeType($filePath, array('text/plain', 'text/x-po'));

    if(false == $mimeType) {
        set_page_message(tr('You can upload only portable object files.'), 'error');
        return;
    } else {

        if ($mimeType == 'text/x-po') {
            $parseResult = _parseGettextFile($filePath, $fileName);
        } else {
            set_page_message(tr('Importing a language from a text file is no longer supported.<br /> You must now import the languages from portable object files (*.po).'), 'warning');
            return;
        }

        if (is_int($parseResult)) {
            if ($parseResult == 1) {
                set_page_message(tr('Could not read language file.'), 'error');
                return;
            } elseif ($parseResult == 2) {
                set_page_message(tr('Uploaded file is not a valid language file.'), 'error');
                return;
            }
        }

        if (empty($parseResult['imscp_languageSetlocaleValue']) ||
            empty($parseResult['imscp_table']) ||
            empty($parseResult['imscp_language']) ||
            !preg_match('/^[a-z]{2}(_[A-Z]{2}){0,1}$/Di', $parseResult['imscp_languageSetlocaleValue'])
            || !preg_match('/^[a-z0-9()]+$/Di', $parseResult['imscp_table'])
        ) {
            set_page_message(tr('The file does not contain all the necessary information.'), 'error');
            return;
        }

        /** @var $db iMSCP_Database */
        $db = iMSCP_Registry::get('db');

        $languageTable = 'lang_' . $parseResult['imscp_table'];
        $isUpdate = false;

        if(in_array($languageTable, $db->metaTables())) {
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

        foreach($parseResult as $msgid => $msgstr){
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
}

/**
 * Imports all translation string from a portable object (po) file.
 *
 * @param  string $file Absolute path to the uploaded file
 * @param  string $filename Filename
 * @return mixed array An array that contains all translation string or an integer on
 *                     failure
 */
function _parseGettextFile($file, $filename)
{
    $content = file_get_contents($file);

    if (empty($content)) {
        return 1;
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
        return 2;
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

        // Retrieves the name of language team
        if (isset($headers['Language-Team'])) {
            $languageTeam = $headers['Language-Team'];
            if (($np = strpos($languageTeam, '<')) !== false) {

                // Normalizes the name of the language team and uses it as database
                // table name
                $parseResult['imscp_table'] = str_replace(
                    array(' ', '(', ')'), '', mb_substr($languageTeam, 0, $np));
            }
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

        // Retrieves the locale from language filename
        $parseResult['imscp_languageSetlocaleValue'] = basename($filename, '.po');

        unset($parseResult['headers']);
    } else {
        return 2;
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
$tpl->define_dynamic('lang_delete_link', 'lang_row');
$tpl->define_dynamic('lang_delete_show', 'lang_row');
$tpl->define_dynamic('lang_radio', 'lang_row');
$tpl->define_dynamic('lang_def', 'lang_row');

$tpl->assign(array(
                  'TR_ADMIN_I18N_PAGE_TITLE' => tr('i-MSCP - Admin/Internationalisation'),
                  'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
                  'THEME_CHARSET' => tr('encoding'),
                  'ISP_LOGO' => get_logo($_SESSION['user_id']),
                  'TR_MULTILANGUAGE' => tr('Internationalisation'),
                  'TR_INSTALLED_LANGUAGES' => tr('Installed languages'),
                  'TR_LANGUAGE' => tr('Language'),
                  'TR_MESSAGES' => tr('Messages'),
                  'TR_LANG_REV' => tr('Date'),
                  'TR_DEFAULT' => tr('Panel default language'),
                  'TR_ACTION' => tr('Action'),
                  'TR_SAVE' => tr('Save'),
                  'TR_INSTALL_NEW_LANGUAGE' => tr('Install new language'),
                  'TR_LANGUAGE_FILE' => tr('Language file'),
                  'ISP_LOGO' => get_logo($_SESSION['user_id']),
                  'TR_INSTALL' => tr('Install'),
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
