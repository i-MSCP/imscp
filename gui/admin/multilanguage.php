<?php
/**
 *  ispCP (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 **/

require '../include/ispcp-lib.php';

check_login(__FILE__);

$tpl = new pTemplate();
$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/multilanguage.tpl');
$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('lang_row', 'page');
$tpl -> define_dynamic('lang_delete_link', 'lang_row');
$tpl -> define_dynamic('lang_delete_show', 'lang_row');
$tpl -> define_dynamic('lang_radio', 'lang_row');
$tpl -> define_dynamic('lang_def', 'lang_row');

$theme = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
	array(
			'TR_ADMIN_I18N_PAGE_TITLE' => tr('ISPCP - Admin/Internationalization'),
			'THEME_COLOR_PATH' => "../themes/$theme",
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => get_logo($_SESSION['user_id']),
			'ISPCP_LICENSE' => $cfg['ISPCP_LICENSE']
			)
	);

function update_def_lang() {
	global $sql, $theme;

	if (isset($_POST['uaction']) && $_POST['uaction'] === 'change_language') {
		if (isset($_POST['default_language']) && !empty($_POST['default_language'])) {
			$user_id = $_SESSION['user_id'];
			$user_lang = $_POST['default_language'];

			$query = <<<SQL_QUERY
				select
					*
				from
					user_gui_props
				where
					user_id = ?
SQL_QUERY;

			$rs = exec_query($sql, $query, array($user_id));

			if ($rs->RecordCount() == 0)	{
				$query = <<<SQL_QUERY
						insert into
							user_gui_props
							(
								user_id,
								lang,
								layout
							)
						values
							(
								?,
								?,
								?
							)
SQL_QUERY;

			$rs = exec_query($sql, $query, array($user_id, $user_lang, $theme));
			}
			else {
				$query = <<<SQL_QUERY
					update
						user_gui_props
					set
						lang = ?
					where
						user_id = ?
SQL_QUERY;

				$rs = exec_query($sql, $query, array($user_lang, $user_id));
			}

			$_SESSION['user_def_lang'] = $user_lang;

			set_page_message(tr('Default language changed!'));
		}
	}
}


function install_lang() {
	global $sql;

	if (isset($_POST['uaction']) AND $_POST['uaction'] === 'upload_language') {

		// add lang pack now !
		$file_type = $_FILES['lang_file']['type'];

		if (empty($_FILES['lang_file']['name'])) {
			set_page_message(tr('Upload file error!'));
			return;
		}

		if (!($file_type === "text/plain") AND !($file_type === "application/octet-stream")){
			set_page_message(tr('You can upload only text files!'));
			return;
		}
		else {
			$file = $_FILES['lang_file']['tmp_name'];
			$fd = fopen($file, "r");

			if (!$fd) {
				set_page_message(tr('Can not read ispcp language file!'));
				return;
			}
			$table = fgets($fd, 4096);
			$table = explode(" = ", trim($table));
			if ($table[0] != "ispcp_table") {
				set_page_message(tr('Can not read ispcp language file!'));
				return;
			}
			$lang_table = 'lang_'.$table[1];

			$tables = $sql->MetaTables();
			$nlang = count($tables);
			$lang_update = false;

			$i = 0;
			do {
				$data = $tables[$i];
				if ($data == $lang_table) {
					$lang_update = true;
				}
				$i++;
			} while ($lang_update === false AND $i < $nlang);

			reset($tables);
			if (file_exists($file)) {
				$fd = fopen($file, "r");

				if (!$fd) {
					set_page_message(tr('Can not read ispcp language file!'));
					return;
				}

				if ($lang_update === true) {
					// clean up table if this is language update
					$sql->Execute("DROP TABLE IF EXISTS `$lang_table`;");
				}

				$sql->Execute("CREATE TABLE `$lang_table` (
								id int(10) unsigned NOT NULL auto_increment,
								msgid text,
								msgstr text,
								UNIQUE id (id)
								) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;"
							);

				while(!feof($fd)) {
					$buffer_id    = fgets($fd, 4096);
					$buffer_id    = explode(" = ", trim($buffer_id));
					$orig_string  = trim(@$buffer_id[0]);
					$trans_string = trim(@$buffer_id[1]);

					$query = "INSERT INTO `$lang_table` (msgid,msgstr) VALUES (?, ?)";
					exec_query($sql, $query, array($orig_string, $trans_string));
				}
				fclose($fd);
			}

			$user_logged = $_SESSION['user_logged'];

			if ($lang_update == 0) {
				write_log("$user_logged: add new language: $lang_table!");
				set_page_message(tr('New language installed!'));
			}
			else {
				write_log("$user_logged: update language: $lang_table!");
				set_page_message(tr('Update language installed!'));
			}
		}
	}
}

function show_lang(&$tpl, &$sql) {
	$tables = $sql->MetaTables();

	$nlang = count($tables);

	global $cfg;

	$row = 1;

	list($user_def_lang, $user_def_layout) = get_user_gui_props($sql, $_SESSION['user_id']);

	$usr_def_lng  = explode('_',$user_def_lang);

	for($i=0;$i<$nlang;$i++){

		$data= $tables[$i];
		$pos = strpos($data, "lang_");
		if ($pos === false) {
			/* not found... ... next :) */
		}
		else {
			$dat  = explode('_',$data);

			$query = <<<SQL_QUERY
				select
					count(msgid) as cnt
				from
					$tables[$i]
SQL_QUERY;
			$rs = exec_query($sql, $query, array());

			$query = <<<SQL_QUERY
				select
					msgstr
				from
					$tables[$i]
				where
					msgid = 'ispcp_language'
SQL_QUERY;
			$res = exec_query($sql, $query, array());

			if ($res -> RecordCount() == 0) {
			  $language_name = tr('Unknown');
			} else {
			  $language_name = $res->fields['msgstr'];
			}

			if ($row++ % 2 == 0) {
				$tpl -> assign('LANG_CLASS', 'content2');
			}
			else{
				$tpl -> assign('LANG_CLASS', 'content');
			}

			if ($usr_def_lng[1] == $dat[1]) {
				$tpl -> assign(
					array(
						'DEFAULT' => tr('yes'),
						'LANG_RADIO' =>'',
						)
					);
				$tpl->parse('LANG_DEF', 'lang_def');
			}
			else {
				$tpl -> assign(
					array(
						'LANG_DEF' =>'',
						'LANG_VALUE' =>'lang_'.$dat[1],
						)
					);
				$tpl->parse('LANG_RADIO', 'lang_radio');
			}

			if ($cfg['USER_INITIAL_LANG'] == 'lang_'.$dat[1] || $usr_def_lng[1] == $dat[1]) {
				$tpl -> assign(
					array(
						'TR_UNINSTALL' => tr('uninstall'),
						'LANG_DELETE_LINK' =>'',
						)
					);
				$tpl->parse('LANG_DELETE_SHOW', 'lang_delete_show');
			}
			else {
				$tpl -> assign(
					array(
						'TR_UNINSTALL' => tr('uninstall'),
						'URL_DELETE' => 'delete_lang.php?delete_lang=lang_'.$dat[1],
						'LANG_DELETE_SHOW' =>'',
						)
					);
				$tpl->parse('LANG_DELETE_LINK', 'lang_delete_link');
			}
			//'LANGUAGE' => $dat[1],
			//$res

			$tpl -> assign(
				array(
				'LANGUAGE' => $language_name,
				'MESSAGES' => $rs->fields['cnt']." ".tr('Messages translated'),
				'URL_EXPORT' => 'multilanguage_export.php?export_lang=lang_'.$dat[1],
				)
			);

			$tpl->parse('LANG_ROW','.lang_row');
		}
	}
}



/*
 *
 * static page messages.
 *
 */

update_def_lang();

gen_admin_mainmenu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/main_menu_settings.tpl');
gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_settings.tpl');

install_lang();

show_lang($tpl, $sql);

$tpl -> assign(
	array(
		'TR_MULTILANGUAGE' => tr('Multilanguage'),
		'TR_INSTALLED_LANGUAGES' => tr('Installed languages'),
		'TR_LANGUAGE' => tr('Language'),
		'TR_MESSAGES' => tr('Messages'),
		'TR_DEFAULT' => tr('Default'),
		'TR_ACTION' => tr('Action'),
		'TR_SAVE' => tr('Save'),
		'TR_INSTALL_NEW_LANGUAGE' => tr('Install new language'),
		'TR_LANGUAGE_FILE' => tr('Language file'),
		'ISP_LOGO' => get_logo($_SESSION['user_id']),
		'TR_INSTALL' => tr('Install'),
		'TR_EXPORT' => tr('Export'),
		'TR_MESSAGE_DELETE' => tr('Are you sure you want to delete', true),
		)
	);

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if ($cfg['DUMP_GUI_DEBUG']) dump_gui_debug();

unset_messages();
?>