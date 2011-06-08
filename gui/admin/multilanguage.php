<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010 by i-MSCP | http://i-mscp.net
 * @version 	SVN: $Id$
 * @link 		http://i-mscp.net
 * @author 		ispCP Team
 * @author 		i-MSCP Team
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

// Include needed libraries
require '../include/imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);


/*******************************************************************************
 * View functions
 */

/**
 * Prepares page data to show available languages
 *
 * @param  iMSCP_pTemplate $tpl An iMSCP_pTemplate instance
 * @return void
 */
function showLang($tpl) {

	/**
	 * @var $cfg iMSCP_Config_Handler_File
	 */
	$cfg = iMSCP_Registry::get('config');


	/** @var $db iMSCP_Database */
	$db = iMSCP_Registry::get('db');

	$tables = $db->metaTables();

	$nlang = count($tables);

	$row = 1;

	list($user_def_lang) = get_user_gui_props($_SESSION['user_id']);

	$usr_def_lng = explode('_', $user_def_lang);

	for ($i = 0; $i < $nlang; $i++) {
		$data = $tables[$i];
		$pos = strpos($data, 'lang_');

		if ($pos === false) {
			// not found... ... next :)
			continue;
		}

		$dat = explode('_', $data);

		/**
		 * @var $stmt iMSCP_Database_ResultSet
		 */
		$stmt = array();

		foreach(array(
			'imscp_language', 'imscp_languageSetlocaleValue',
			'imscp_languageRevision') as $msgstr) {

			$stmt[] = exec_query(
				"SELECT `msgstr` FROM `{$tables[$i]}` WHERE `msgid` = '$msgstr'
			");
		}

		if ($stmt[0]->recordCount() == 0 || $stmt[1]->recordCount() == 0) {
			$language_name = tr('Unknown');
		} else {
			$tr_langcode = tr($stmt[1]->fields['msgstr']);

			if ($stmt[1]->fields['msgstr'] == $tr_langcode) {
				// no translation found
				$language_name = $stmt[0]->fields['msgstr'];
			} else {
				$language_name = $tr_langcode;
			}
		}

		if ($stmt[2]->recordCount() !== 0 && $stmt[2]->fields['msgstr'] != '' &&
			class_exists('DateTime')) {

			$tmp_lang = new DateTime($stmt[2]->fields['msgstr']);
			$language_revision = $tmp_lang->format('Y-m-d H:i');

			unset($tmp_lang);
		} else {
			$language_revision = tr('Unknown');
		}

		$tpl->assign('LANG_CLASS', ($row++ % 2 == 0) ? 'content2' : 'content4');

		if ($cfg->USER_INITIAL_LANG == "lang_{$dat[1]}" ||
			$usr_def_lng[1] == $dat[1]) {

			$tpl->assign(
				array(
					'TR_UNINSTALL' => tr('N/A'),
					'LANG_DELETE_LINK' => '',
					'LANGUAGE' => tohtml($language_name),
					'LANGUAGE_REVISION' => $language_revision,
					'LANG_VALUE_CHECKED' => $cfg->HTML_CHECKED,
					'LANG_VALUE' => $tr_langcode
				)
			);

			$tpl->parse('LANG_DELETE_SHOW', 'lang_delete_show');
		} else {
			$tpl->assign(
				array(
					'TR_UNINSTALL' => tr('Uninstall'),
					'URL_DELETE' =>
						"language_delete.php?delete_lang=lang_{$dat[1]}",
					'LANG_DELETE_SHOW' => '',
					'LANGUAGE' => tohtml($language_name),
					'LANGUAGE_REVISION' => $language_revision,
					'LANG_VALUE_CHECKED' => '',
					'LANG_VALUE' => $tr_langcode
				)
			);

			$tpl->parse('LANG_DELETE_LINK', 'lang_delete_link');
		}

		// Retrieving number of translated messages
		$query = "SELECT COUNT(`msgid`) AS `cnt` FROM `{$tables[$i]}`;";

		$stmt = exec_query($query);

		$tpl->assign(
			array(
				'MESSAGES' =>
					tr('%d messages translated', $stmt->fields['cnt'] - 5),
				'URL_EXPORT' =>
					"multilanguage_export.php?export_lang=lang_{$dat[1]}",
				'INDEX' => $i,
				'TR_GZIPPED' => tr('Gzipped')
			)
		);

		$tpl->parse('LANG_ROW', '.lang_row');
	}
} // end showLang()

/*******************************************************************************
 * Importation functions
 */

/**
 * Import all translation string from a language file
 *
 */
function importLanguageFile() {

	// Add new language
	$file_type = $_FILES['lang_file']['type'];
	$file = $_FILES['lang_file']['tmp_name'];

	if (empty($_FILES['lang_file']['name']) || !is_readable($file)) {
		set_page_message(tr('Upload file error!'), 'error');
		return;
	}

	if ($file_type != 'text/plain' && $file_type != 'application/octet-stream'
		&& $file_type != 'text/x-gettext-translation') {

		set_page_message(tr
                ('You can upload only text files!'),
                'error'
        );
		return;
	} else {
		if ($file_type == 'text/x-gettext-translation') {
			$ab = _importGettextFile($file, $_FILES['lang_file']['name']);
		} else {
			$ab = _importTextFile($file);
		}

		if (is_int($ab)) {
			if ($ab == 1) {
				set_page_message(tr('Could not read language file!'), 'error');
				return;
			} elseif ($ab == 2) {
				set_page_message(tr('Uploaded file is not a valid language file!'), 'error');
				return;
			}
		}
            //funk this Language Error! Language: upload bug removed, not copied by Benedict.
        if (empty($ab['imscp_languageSetlocaleValue']) ||
                 empty($ab['imscp_table'])
                || empty($ab['imscp_language'])
                || !preg_match('/^[a-z]{2}(_[A-Z]{2}){0,1}$/Di',$ab['imscp_languageSetlocaleValue'])
                || !preg_match('/^[a-z0-9()]+$/Di', $ab['imscp_table']))
                        {
                        set_page_message(
                                tr('Uploaded file does not contain language information!'),
                                'error'
                                );
                                return;
                        }


        /** @var $sql iMSCP_Database */
		$db = iMSCP_Registry::get('db');

		$lang_table = 'lang_' . $ab['imscp_table'];
		$lang_update = false;

		for ($i = 0, $tables = $db->metaTables(), $nlang = count($tables) ;
			$i < $nlang; $i++) {

			if ($lang_table == $tables[$i]) {
				$lang_update = true;
				break;
			}
		}

		if ($lang_update) {
			execute_query("DROP TABLE IF EXISTS `$lang_table`;");
		}

		$query = "
			CREATE TABLE `$lang_table` (
				`msgid`     text collate utf8_unicode_ci,
				`msgstr`    text collate utf8_unicode_ci,
				 KEY        `msgid` (msgid(25)
				 )
			) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
		";

		execute_query($query);

		foreach ($ab as $msgid => $msgstr) {
			$query = "
				INSERT INTO `$lang_table` (
					`msgid`, `msgstr`
				) VALUES (?, ?);
			";

			exec_query($query, str_replace("\\n", "\n", array($msgid, $msgstr)));
		}

		if (!$lang_update) {
			write_log(
				tr(
					'%s added new language: %s', $_SESSION['user_logged'],
					$ab['imscp_language']
				)
			);

			set_page_message(tr('New language installed!'), 'success');
		} else {
			write_log(
				tr(
					'%s updated language: %s', $_SESSION['user_logged'],
					$ab['imscp_language']
				)
			);

			set_page_message(tr('Language was updated!'), 'success');
		}
	}
}

/**
 * Import traditional i-MSCP  translation file format
 *
 * @param string $file translation file
 * @return array|int
 */
function _importTextFile($file) {

    if(!($fp= fopen($file, 'r'))) return 1;

    $ab = array(
        'imscp_languageRevision'        => '',
        'imscp_languageSetlocaleValue'  => '',
        'imscp_table'                   => '',
        'imscp_language'                => ''
    );

    $errors = 0;

    while (!feof($fp) && $errors <= 3) {
        $t = fgets($fp);

        $msgid = '';
        $msgstr = '';

        @list($msgid, $msgstr) = $t = explode(' = ', $t);

        if (count($t) != 1) {
            $ab[$msgid] = rtrim($msgstr);
        } else {
            $errors++;
        }
    }

    fclose($fp);

    if ($errors > 3) {
        return 2;
    }

    return $ab;
}

/**
 * Import all translation string from a PO file
 *
 * @param  string $file
 * @param  string $filename
 * @return mixed Array that contain all translation string or int on failure
 */
function _importGettextFile($file, $filename) {

    $content = file_get_contents($file);

    if (empty($content)) return 1;

    $ab = array(
        'imscp_languageRevision'        => '',
        'imscp_languageSetlocaleValue'  => '',
        'imscp_table'                   => '',
        'imscp_language'                => ''
    );

    // Parse all messages
    $n = preg_match_all(
        '/(msgid\s+("([^"]|\\\\")*?"\s*)+)\s+(msgstr\s+("([^"]|\\\\")*?"\s*)+)/',
        $content, $matches
    );

    for ($i = 0; $i < $n; $i++) {
        $id = preg_replace('/\s*msgid\s*"(.*)"\s*/s', '\\1', $matches[1][$i]);
        $str = preg_replace('/\s*msgstr\s*"(.*)"\s*/s', '\\1', $matches[4][$i]);

        if (!empty($str)) {
            $ab[_decodePoFileString($id)] = _decodePoFileString($str);
        }
    }

    // set language
    if (isset($ab['_: Localised language'])) {
        $ab['imscp_language'] = $ab['_: Localised language'];
        unset($ab['_: Localised language']);
    } else {
        return 2;
    }

    // Parse some relevant header information
	if (isset($ab[''])) {
		$ameta = array();

		$header = explode("\n", $ab['']);

		foreach ($header as $hline) {
            $n = strpos($hline, ':');
            if ($n !== false) {
                $key = substr($hline, 0, $n);
                $ameta[$key] = trim(substr($hline, $n+1));
            }
        }

		# Retrieving language translation team
        if (isset($ameta['Language-Team'])) {
            $s = $ameta['Language-Team'];
            $n = strpos($s, '<');

            if ($n !== false) {
                $ab['imscp_table'] = str_replace(' ', '', mb_substr($s, 0, $n));
            }
        }

        // Getting i-MSCP_language Revision by PO-Revision-Date
        if (isset($ameta['PO-Revision-Date'])) {
            // trim timezone
            $n = strpos($ameta['PO-Revision-Date'], '+');
            if ($n !== false) {
                $ameta['PO-Revision-Date'] = substr($ameta['PO-Revision-Date'], 0, $n);
            }

            // currently some problems with hour/minute parsing?!
            $time = strptime($ameta['PO-Revision-Date'], '%Y-%m-%d %H:%I');

            $ab['imscp_languageRevision'] = sprintf(
                '%04d%02d%02d%02d%02d%02d',
                $time['tm_year']+1900,
                $time['tm_mon']+1,
                $time['tm_mday'],
                $time['tm_hour'],
                $time['tm_min'],
                $time['tm_sec']
            );
        } else {
            $ab['imscp_languageRevision'] = strftime('%Y%m%d%H%I%S');
        }

        // get locale from file name
        $ab['imscp_languageSetlocaleValue'] = basename($filename, '.po');

        unset($ab['']);
    } else {
        return 2;
    }

    // set default encoding to UTF-8 if not present
    if (!isset($ab['encoding'])) {
        $ab['encoding'] = 'UTF-8';
    }

    return $ab;
}


/**
 * Remove leading and trailing quotes, un-escape linefeed, cr, tab and quotes
 *
 * @param string $s
 * @return string Normalized string
 */
function _decodePoFileString($s) {
    return str_replace(
        array('\\n', '\\r', '\\t', '\"'), array("\n", "\r", "\t", '"'),
	    preg_replace('/"\s+"/', '', $s)
    );
}

/*******************************************************************************
 * Main script
 */

// Check for login
check_login(__FILE__);

/**
 * @var $cfg iMSCP_Config_Handler_File
 */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/multilanguage.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('lang_row', 'page');
$tpl->define_dynamic('lang_delete_link', 'lang_row');
$tpl->define_dynamic('lang_delete_show', 'lang_row');
$tpl->define_dynamic('lang_radio', 'lang_row');
$tpl->define_dynamic('lang_def', 'lang_row');

$tpl->assign(
	array(
		'TR_ADMIN_I18N_PAGE_TITLE'  => tr('i-MSCP - Admin/Internationalisation'),
		'THEME_COLOR_PATH'          => "../themes/{$cfg->USER_INITIAL_THEME}",
		'THEME_CHARSET'             => tr('encoding'),
		'ISP_LOGO'                  => get_logo($_SESSION['user_id'])
	)
);

if (isset($_POST['uaction']) && $_POST['uaction'] == 'upload_language') {
	importLanguageFile();
}

showLang($tpl);

/**
 * static page messages
 */

gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_settings.tpl');

$tpl->assign(
	array(
		'TR_MULTILANGUAGE'          => tr('Internationalisation'),
		'TR_INSTALLED_LANGUAGES'    => tr('Installed languages'),
		'TR_LANGUAGE'               => tr('Language'),
		'TR_MESSAGES'               => tr('Messages'),
		'TR_LANG_REV'               => tr('Date'),
		'TR_DEFAULT'                => tr('Panel default language'),
		'TR_ACTION'                 => tr('Action'),
		'TR_SAVE'                   => tr('Save'),
		'TR_INSTALL_NEW_LANGUAGE'   => tr('Install new language'),
		'TR_LANGUAGE_FILE'          => tr('Language file'),
		'ISP_LOGO'                  => get_logo($_SESSION['user_id']),
		'TR_INSTALL'                => tr('Install'),
		'TR_EXPORT'                 => tr('Export'),
		'TR_MESSAGE_DELETE'         =>
			tr('Are you sure you want to delete %s?', true, '%s'),
	)
);

generatePageMessage($tpl);

$tpl->parse('PAGE', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(
    iMSCP_Events::onAdminScriptEnd, new iMSCP_Events_Response($tpl));

$tpl->prnt();

unsetMessages();
