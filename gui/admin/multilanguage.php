<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl->define_dynamic('page', Config::get('ADMIN_TEMPLATE_PATH') . '/multilanguage.tpl');
$tpl->define_dynamic('page_message', 'page');
$tpl->define_dynamic('lang_row', 'page');
$tpl->define_dynamic('lang_delete_link', 'lang_row');
$tpl->define_dynamic('lang_delete_show', 'lang_row');
$tpl->define_dynamic('lang_radio', 'lang_row');
$tpl->define_dynamic('lang_def', 'lang_row');

$theme = Config::get('USER_INITIAL_THEME');

$tpl->assign(
	array(
		'TR_ADMIN_I18N_PAGE_TITLE'	=> tr('ispCP - Admin/Internationalisation'),
		'THEME_COLOR_PATH'			=> "../themes/$theme",
		'THEME_CHARSET'				=> tr('encoding'),
		'ISP_LOGO'					=> get_logo($_SESSION['user_id'])
	)
);

function update_def_lang() {
	$sql = Database::getInstance();
	global $theme;

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'change_language') {
		if (isset($_POST['default_language']) && !empty($_POST['default_language'])) {
			$user_id = $_SESSION['user_id'];
			$user_lang = $_POST['default_language'];

			$query = "SELECT * FROM `user_gui_props` WHERE `user_id` = ?";
			$rs = exec_query($sql, $query, array($user_id));

			if ($rs->RecordCount() == 0) {
				$query = "INSERT INTO `user_gui_props` (`user_id`, `lang`, `layout`) VALUES (?, ?, ?)";
				$rs = exec_query($sql, $query, array($user_id, $user_lang, $theme));
			} else {
				$query = "UPDATE `user_gui_props` SET `lang` = ? WHERE `user_id` = ?";
				$rs = exec_query($sql, $query, array($user_lang, $user_id));
			}

			$_SESSION['user_def_lang'] = $user_lang;
			set_page_message(tr('Default language changed!'));
		}
	}
}

function install_lang() {
	$sql = Database::getInstance();

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'upload_language') {
		// add lang pack now !
		$file_type = $_FILES['lang_file']['type'];
		$file = $_FILES['lang_file']['tmp_name'];

		if (empty($_FILES['lang_file']['name']) || !file_exists($file) || !is_readable($file)) {
			set_page_message(tr('Upload file error!'));
			return;
		}

		if ($file_type !== "text/plain" && $file_type !== "application/octet-stream") {
			set_page_message(tr('You can upload only text files!'));
			return;
		} else {
			$fp = fopen($file, 'r');

			if (!$fp) {
				set_page_message(tr('Could not read language file!'));
				return;
			}

			$t = '';
			$ab = array('ispcp_languageSetlocaleValue' => '', 'ispcp_table' => '', 'ispcp_language' => '');
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
				set_page_message(tr('Uploaded file is not a valid language file!'));
				return;
			}

			if (empty($ab['ispcp_languageSetlocaleValue'])
				|| empty($ab['ispcp_table'])
				|| empty($ab['ispcp_language'])
				|| !preg_match('/^[a-z]{2}(_[A-Z]{2}){0,1}$/Di', $ab['ispcp_languageSetlocaleValue'])
				|| !preg_match('/^[a-z0-9]+$/Di', $ab['ispcp_table'])) {
				set_page_message(tr('Uploaded file does not contain the language information!'));
				return;
			}

			$lang_table = 'lang_' . $ab['ispcp_table'];

			$lang_update = false;

			for ($i = 0, $tables = $sql->MetaTables(), $nlang = count($tables); $i < $nlang; $i++) {
				if ($lang_table == $tables[$i]) {
					$lang_update = true;
					break;
				}
			}

			if ($lang_update) {
				$sql->Execute("DROP TABLE IF EXISTS `$lang_table`;");
			}

			$sql->Execute("CREATE TABLE `$lang_table` (
							msgid text collate utf8_unicode_ci,
							msgstr text collate utf8_unicode_ci,
							KEY msgid (msgid(25))
							) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;"
			);

			foreach ($ab as $msgid => $msgstr) {
				$query = "INSERT INTO `$lang_table` (`msgid`, `msgstr`) VALUES (?, ?)";
				exec_query($sql, $query, str_replace("\\n", "\n", array($msgid, $msgstr)));
			}

			if (!$lang_update) {
				write_log(sprintf("%s added new language: %s", $_SESSION['user_logged'], $ab['ispcp_language']));
				set_page_message(tr('New language installed!'));
			} else {
				write_log(sprintf("%s updated language: %s", $_SESSION['user_logged'], $ab['ispcp_language']));
				set_page_message(tr('Language was updated!'));
			}
		}
	}
}

function show_lang(&$tpl, &$sql) {
	$tables = $sql->MetaTables();

	$nlang = count($tables);

	$row = 1;

	list($user_def_lang, $user_def_layout) = get_user_gui_props($sql, $_SESSION['user_id']);

	$usr_def_lng = explode('_', $user_def_lang);

	for ($i = 0;$i < $nlang;$i++) {
		$data = $tables[$i];
		$pos = strpos($data, "lang_");
		if ($pos === false) {
			/* not found... ... next :) */
			continue;
		}
		$dat = explode('_', $data);

		$query = "SELECT COUNT(`msgid`) as cnt FROM $tables[$i]";
		$rs = exec_query($sql, $query, array());

		$query = "SELECT `msgstr` FROM $tables[$i] WHERE `msgid` = 'ispcp_language'";
		$res = exec_query($sql, $query, array());

		$language_name = ($res->RecordCount() == 0) ? tr('Unknown') : $res->fields['msgstr'];

		$tpl->assign('LANG_CLASS', ($row++ % 2 == 0) ? 'content2' : 'content');

		if ($usr_def_lng[1] == $dat[1]) {
			$tpl->assign(
				array(
					'DEFAULT'		=> tr('yes'),
					'LANG_RADIO'	=> '',
				)
			);
			$tpl->parse('LANG_DEF', 'lang_def');
		} else {
			$tpl->assign(
				array(
					'LANG_DEF'		=> '',
					'LANG_VALUE'	=> 'lang_' . $dat[1],
				)
			);
			$tpl->parse('LANG_RADIO', 'lang_radio');
		}

		if (Config::get('USER_INITIAL_LANG') == 'lang_' . $dat[1] || $usr_def_lng[1] == $dat[1]) {
			$tpl->assign(
				array(
					'TR_UNINSTALL'		=> tr('uninstall'),
					'LANG_DELETE_LINK'	=> '',
					'LANGUAGE'			=> $language_name,
				)
			);
			$tpl->parse('LANG_DELETE_SHOW', 'lang_delete_show');
		} else {
			$tpl->assign(
				array(
					'TR_UNINSTALL'		=> tr('uninstall'),
					'URL_DELETE'		=> 'language_delete.php?delete_lang=lang_' . $dat[1],
					'LANG_DELETE_SHOW'	=> '',
					'LANGUAGE'			=> $language_name,
				)
			);
			$tpl->parse('LANG_DELETE_LINK', 'lang_delete_link');
		}
		// 'LANGUAGE' => $dat[1],
		// $res
		$tpl->assign(
			array(
				'MESSAGES'		=> tr('%d messages translated', $rs->fields['cnt']),
				'URL_EXPORT'	=> 'multilanguage_export.php?export_lang=lang_' . $dat[1],
			)
		);

		$tpl->parse('LANG_ROW', '.lang_row');
	}
}

/*
 *
 * static page messages.
 *
 */

update_def_lang();

gen_admin_mainmenu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/main_menu_settings.tpl');
gen_admin_menu($tpl, Config::get('ADMIN_TEMPLATE_PATH') . '/menu_settings.tpl');

install_lang();

show_lang($tpl, $sql);

$tpl->assign(
	array(
		'TR_MULTILANGUAGE'			=> tr('Internationalisation'),
		'TR_INSTALLED_LANGUAGES'	=> tr('Installed languages'),
		'TR_LANGUAGE'				=> tr('Language'),
		'TR_MESSAGES'				=> tr('Messages'),
		'TR_DEFAULT'				=> tr('Default'),
		'TR_ACTION'					=> tr('Action'),
		'TR_SAVE'					=> tr('Save'),
		'TR_INSTALL_NEW_LANGUAGE'	=> tr('Install new language'),
		'TR_LANGUAGE_FILE'			=> tr('Language file'),
		'ISP_LOGO'					=> get_logo($_SESSION['user_id']),
		'TR_INSTALL'				=> tr('Install'),
		'TR_EXPORT'					=> tr('Export'),
		'TR_MESSAGE_DELETE'			=> tr('Are you sure you want to delete %s?', true, '%s'),
	)
);

gen_page_message($tpl);

$tpl->parse('PAGE', 'page');
$tpl->prnt();

if (Config::get('DUMP_GUI_DEBUG'))
	dump_gui_debug();

unset_messages();

?>
