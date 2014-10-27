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
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2014 by i-MSCP | http://i-mscp.net
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 */

/**
 * Translates the given message
 *
 * @param string|array $msgid tring|array  Translation string, or Array for plural translations
 * @param mixed $substitution,... If second parameter is bool (true), prevent the returned string from being replaced
 *                                with html entities. If not considere the parameter(s) as simple substitution value(s)
 * @return string Translated or original message
 */
function tr($msgid, $substitution = false)
{
	static $translator = null;

	if(null == $translator) {
		/** @var Zend_Translate_Adapter $translator */
		$translator = iMSCP_Registry::get('translator');
	}

	$msgstr = $translator->translate($msgid);

	// Detect whether $substitution is really a substitution or just a value to
	// be replaced in $msgstr
	if (!is_bool($substitution)) {
		$substitution = false;
	}

	// Process included parameter
	if (func_num_args() > 1) {
		$argv = func_get_args();
		unset($argv[0]);

		if (is_bool($argv[1])) {
			unset($argv[1]);
		}

		$msgstr = vsprintf($msgstr, $argv);
	}

	if (!$substitution) {
		$msgstr = replace_html(tohtml($msgstr));
	}

	return $msgstr;
}

/**
 * Replaces special encoded strings back to their original signs
 *
 * @param string $string String to replace chars
 * @return String with replaced chars
 */
function replace_html($string)
{
	$pattern = array(
		'#&lt;[ ]*b[ ]*&gt;#i', '#&lt;[ ]*/[ ]*b[ ]*&gt;#i',
		'#&lt;[ ]*strong[ ]*&gt;#i', '#&lt;[ ]*/[ ]*strong[ ]*&gt;#i',
		'#&lt;[ ]*em[ ]*&gt;#i', '#&lt;[ ]*/[ ]*em[ ]*&gt;#i',
		'#&lt;[ ]*i[ ]*&gt;#i', '#&lt;[ ]*/[ ]*i[ ]*&gt;#i',
		'#&lt;[ ]*small[ ]*&gt;#i', '#&lt;[ ]*/[ ]*small[ ]*&gt;#i',
		'#&lt;[ ]*br[ ]*(/|)[ ]*&gt;#i');

	$replacement = array(
		'<b>', '</b>', '<strong>', '</strong>', '<em>', '</em>', '<i>', '</i>', '<small>', '</small>', '<br />'
	);

	$string = preg_replace($pattern, $replacement, $string);

	return $string;
}

/**
 * Build languages index from machine object files.
 *
 * @return void
 */
function i18n_buildLanguageIndex()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// Flush translation cache
	Zend_Translate::clearCache('iMSCP');

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($cfg->GUI_ROOT_DIR . '/i18n/locales/', FilesystemIterator::SKIP_DOTS)
	);

	$availableLanguages = array();

	/** @var $item SplFileInfo */
	foreach ($iterator as $item) {
		if (strlen($basename = $item->getBasename()) > 8) {
			continue;
		}

		if ($item->isReadable()) {
			$parser = new iMSCP_I18n_Parser_Mo($item->getPathname());
			$translationTable = $parser->getTranslationTable();

			if(!empty($translationTable)) {
				$poRevisionDate = DateTime::createFromFormat('Y-m-d H:i O', $parser->getPotCreationDate());

				$availableLanguages[$basename] = array(
					'locale' => $parser->getLanguage(),
					'revision' => $poRevisionDate->format('Y-m-d H:i'),
					'translatedStrings' => $parser->getNumberOfTranslatedStrings(),
					'lastTranslator' => $parser->getLastTranslator()
				);

				// Getting localized language name
				if(!isset($translationTable['_: Localised language'])) {
					$availableLanguages[$basename]['language'] = tr('Unknown');
				} else {
					$availableLanguages[$basename]['language'] = $translationTable['_: Localised language'];
				}
			} else {
				set_page_message(
					tr(
						'The %s translation file has been ignored: Translation table is empty.',
						"<strong>$basename</strong>"
					),
					'warning'
				);
			}
		}
	}

	/** @var $dbConfig iMSCP_Config_Handler_Db */
	$dbConfig = iMSCP_Registry::get('dbConfig');

	sort($availableLanguages);
	$serializedData = serialize($availableLanguages);
	$dbConfig['AVAILABLE_LANGUAGES'] = $serializedData;
	$cfg['AVAILABLE_LANGUAGES'] = $serializedData;
}

/**
 * Returns list of available languages with some informations
 *
 * Note: For safe reasons, only the files that are readable will be indexed.
 *
 * @return array Array that contains information about available languages
 */
function i18n_getAvailableLanguages()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (!isset($cfg['AVAILABLE_LANGUAGES']) || !isSerialized($cfg['AVAILABLE_LANGUAGES'])) {
		i18n_buildLanguageIndex();
	}

	return unserialize($cfg['AVAILABLE_LANGUAGES']);
}

/**
 * Import Machine object file in languages directory
 *
 * @return bool TRUE on success, FALSE otherwise
 */
function i18n_importMachineObjectFile()
{
	// closure that is run before move_uploaded_file() function - See the Utils_UploadFile() function for further
	// information about implementation details
	$beforeMove = function () {
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');
		$localesDirectory = $cfg->GUI_ROOT_DIR . '/i18n/locales';

		$filePath = $_FILES['languageFile']['tmp_name'];

		if (!is_readable($filePath)) {
			set_page_message(tr('File is not readable.'), 'error');
			return false;
		}

		try {
			$parser = new iMSCP_I18n_Parser_Mo($filePath);
			$encoding = $parser->getContentType();
			$locale = $parser->getLanguage();
			$revision = $parser->getPoRevisionDate();
			$lastTranslator = $parser->getLastTranslator();
			$translationTable = $parser->getTranslationTable();
		} catch (iMSCP_Exception $e) {
			set_page_message(tr('Only gettext Machine Object files (MO files) are accepted.'), 'error');
			return false;
		}

		if (isset($translationTable['_: Localised language'])) {
			$language = $translationTable['_: Localised language'];
		} else {
			$language = '';
		}

		if (empty($encoding) || empty($locale) || empty($revision) || empty($lastTranslator) || empty($language)) {
			set_page_message(
				tr("%s is not a valid i-MSCP language file.", tohtml($_FILES['languageFile']['name'])), 'error'
			);
			return false;
		}

		if (!is_dir("$localesDirectory/$locale")) {
			if (!@mkdir("$localesDirectory/$locale", 0700)) {
				set_page_message(tr("Unable to create '%s' directory for language file.", tohtml($locale)), 'error');
				return false;
			}
		}

		if (!is_dir("$localesDirectory/$locale/LC_MESSAGES")) {
			if (!@mkdir("$localesDirectory/$locale/LC_MESSAGES", 0700)) {
				set_page_message(tr("Unable to create 'LC_MESSAGES' directory for language file."), 'error');
				return false;
			}
		}

		// Return destination file path
		return "$localesDirectory/$locale/LC_MESSAGES/$locale.mo";
	};

	if (utils_uploadFile('languageFile', array($beforeMove)) === false) {
		return false;
	}

	// Rebuild language index
	i18n_buildLanguageIndex();
	return true;
}

/**
 * Change panel default language
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @return bool TRUE if language name is valid, FALSE otherwise
 */
function i18n_changeDefaultLanguage()
{
	if (isset($_POST['defaultLanguage'])) {
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$defaultLanguage = clean_input($_POST['defaultLanguage']);
		$availableLanguages = i18n_getAvailableLanguages();

		// Check for language availability
		$isValidLanguage = false;
		foreach ($availableLanguages as $languageDefinition) {
			if ($languageDefinition['locale'] == $defaultLanguage) {
				$isValidLanguage = true;
			}
		}

		if (!$isValidLanguage) return false;

		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');
		$dbConfig->USER_INITIAL_LANG = $defaultLanguage;
		$cfg->USER_INITIAL_LANG = $defaultLanguage;

		// Ensures language change on next load for current user in case he has not yet his gui properties explicitly
		// set (eg. for the first admin user when i-MSCP was just installed
		$stmt = exec_query('SELECT lang FROM user_gui_props WHERE user_id = ?', $_SESSION['user_id']);

		if ($stmt->fields['lang'] == null) {
			unset($_SESSION['user_def_lang']);
		}
	} else {
		return false;
	}

	return true;
}

/**
 * Add translations
 *
 * This function allow any plugin to add its translations.
 *
 * @param string $dirpath Absolute path to the translations root directory
 * @param string $type Translations type (array, Csv, Gettext, Ini, Qt, Tbx, Tmx, Xliff, XmlTm)
 * @param string $tag Tag which is used for caching (eg. plugin name)
 * @return void
 */
function l10n_addTranslations($dirpath, $type = 'Array', $tag = 'iMSCP')
{
	$locale = iMSCP_Registry::get('user_def_lang');

	$translator = new Zend_Translate(
		array(
			'adapter' => $type,
			'content' => $dirpath,
			'scan' => Zend_Translate::LOCALE_DIRECTORY,
			'locale' => $locale,
			'disableNotices' => true,
			'tag' => $tag
		)
	);

	if($translator->getAdapter()->isAvailable($locale)) {
		iMSCP_Registry::get('translator')->addTranslation(array('content' => $translator));
	}
}

// Dirty hack to make gettext add this entry to the .pot file
if (false) {
	tr('_: Localised language');
}
