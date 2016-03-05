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
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/**
 * Translates the given string
 *
 * @param string $messageId Translation string, or Array for plural translations
 * @param mixed $substitution,... Substitution value(s)
 * @return string
 */
function tr($messageId, $substitution = null)
{
	static $translator = null;

	if(null == $translator) {
		if(iMSCP_Registry::isRegistered('translator')) {
			/** @var Zend_Translate_Adapter $translator */
			$translator = iMSCP_Registry::get('translator');
		} else {
			$message = $messageId;

			if ($substitution !== null) {
				$argv = func_get_args();
				unset($argv[0]);
				$message = vsprintf($message, $argv);
			}

			return $message;
		}
	}

	$message = $translator->translate($messageId);

	// Process included parameter
	if ($substitution !== null) {
		$argv = func_get_args();
		unset($argv[0]);
		$message = vsprintf($message, $argv);
	}

	return $message;
}

/**
 * Translates the given string using plural notations
 *
 * @param string $singular Singular translation string
 * @param string $plural Plural translation string
 * @param integer $number Number for detecting the correct plural
 * @param mixed $substitution... Substitution value(s)
 * @return string
 */
function ntr($singular, $plural, $number)
{
	static $translator = null;

	if(null == $translator) {
		/** @var Zend_Translate_Adapter $translator */
		$translator = iMSCP_Registry::get('translator');
	}

	$message = $translator->plural($singular, $plural, $number);
	$argv = func_get_args();

	return vsprintf($message, array_splice($argv, 2));
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
		'<b>', '</b>', '<strong>', '</strong>', '<em>', '</em>', '<i>', '</i>', '<small>', '</small>', '<br>'
	);

	$string = preg_replace($pattern, $replacement, $string);

	return $string;
}

/**
 * Build languages index from machine object files.
 *
 * @throws iMSCP_Exception
 * @return void
 */
function i18n_buildLanguageIndex()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	// Clear translation cache
	/** @var Zend_Translate $translator */
	$translator = iMSCP_Registry::get('translator');
	if($translator->hasCache()) {
		$translator->clearCache('iMSCP');
	}

	# Remove all cached navigation translation files
	if(@is_dir(CACHE_PATH . '/translations/navigation')) {
		if(!utils_removeDir(CACHE_PATH . '/translations/navigation')) {
			throw new iMSCP_Exception('Unable to remove directory for cached navigation translation files');
		}
	}

	# Clear opcode cache if any
	iMSCP_Utility_OpcodeCache::clearAllActive();

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
			$parser = new iMSCP_I18n_Parser_Gettext($item->getPathname());
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
					tr('The %s translation file has been ignored: Translation table is empty.', $basename), 'warning'
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
			$parser = new iMSCP_I18n_Parser_Gettext($filePath);
			$encoding = $parser->getContentType();
			$locale = $parser->getLanguage();
			$revision = $parser->getPoRevisionDate();
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
 * @param string|null $scan If set to NULL, no scanning of the directory structure will be done. If set to
 *                         Zend_Translate::LOCALE_DIRECTORY the locale will be detected within the directory.
 *                         If set to Zend_Translate::LOCALE_FILENAME the locale will be detected within the filename.
 * @throws iMSCP_Exception
 * @return void
 */
function l10n_addTranslations($dirpath, $type = 'Array', $tag = 'iMSCP', $scan = Zend_Translate::LOCALE_FILENAME)
{
	/** @var Zend_Translate_Adapter $primaryTranslator */
	$primaryTranslator = iMSCP_Registry::get('translator')->getAdapter();

	$locale = $primaryTranslator->getLocale();

	$pluginTranslator = new Zend_Translate(array(
		'adapter' => $type,
		'content' => $dirpath,
		'scan' => $scan,
		'locale' => $locale,
		'disableNotices' => true,
		'tag' => $tag
	));

	if($pluginTranslator->getAdapter()->isAvailable($locale)) {
		$primaryTranslator->addTranslation(array('content' => $pluginTranslator));
	}
}

/**
 * Get JS translations strings
 *
 * Note: Plugins can register their own JS translation strings by listening on the onGetJsTranslations event, and add
 * them to the translations ArrayObject which is a parameter of that event.
 *
 * For instance:
 *
 * iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function($e) {
 *    $e->getParam('translations')->my_namespace = array(
 *        'first_translation_string_identifier' => tr('my first translation string'),
 *        'second_translation_string_identifier' => tr('my second translation string')
 *    );
 * });
 *
 * Then, in your JS script, you can access your translation strings as follow:
 *
 * imscp_i18n.my_namespace.first_translation_string_identifier
 * imscp_i18n.my_namespace.second_translation_string_identifier
 * ...
 *
 * @return string JS object as string
 * TODO ASSETIC management
 */
function i18n_getJsTranslations()
{
	$cfg = iMSCP_Registry::get('config');

	$translations = new ArrayObject(array(
		// Core translation strings
		'core' => array(
			'close' => tr('Close'),
			'generate' => tr('Generate'),
			'show' => tr('Show'),
			'your_new_password' => tr('Your new password'),
			'password_generate_alert' => tr('You must first generate a password by clicking on the generate button.'),
			'password_length' => $cfg['PASSWD_CHARS']
		)),
		ArrayObject::ARRAY_AS_PROPS
	);

	iMSCP_Events_Aggregator::getInstance()->dispatch('onGetJsTranslations', array('translations' => $translations));

	return json_encode($translations, JSON_FORCE_OBJECT);
}
