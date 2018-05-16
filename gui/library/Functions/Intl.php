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
use iMSCP_I18n_Parser_Gettext as GettextParser;
use iMSCP_Registry as Registry;
use iMSCP_Utility_OpcodeCache as OpcodeCacheManager;
use Zend_Locale as Locale;
use Zend_Translate as Translator;

/**
 * Translates the given string
 *
 * @param string $messageId Translation string, or Array for plural translations
 * @param mixed $substitution,... Substitution value(s)
 * @return string
 * @throws Zend_Exception
 */
function tr($messageId, $substitution = NULL)
{
    static $translator = NULL;

    if (NULL == $translator) {
        if (Registry::isRegistered('translator')) {
            /** @var Zend_Translate_Adapter $translator */
            $translator = Registry::get('Zend_Translate');
        } else {
            $message = $messageId;

            if ($substitution !== NULL) {
                $argv = func_get_args();
                unset($argv[0]);
                $message = vsprintf($message, $argv);
            }

            return $message;
        }
    }

    $message = $translator->translate($messageId);

    // Process included parameter
    if ($substitution !== NULL) {
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
 * @return string
 * @throws Zend_Exception
 */
function ntr($singular, $plural, $number)
{
    static $translator = NULL;

    if (NULL == $translator) {
        /** @var Zend_Translate_Adapter $translator */
        $translator = Registry::get('Zend_Translate');
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
    $pattern = [
        '#&lt;[ ]*b[ ]*&gt;#i', '#&lt;[ ]*/[ ]*b[ ]*&gt;#i',
        '#&lt;[ ]*strong[ ]*&gt;#i', '#&lt;[ ]*/[ ]*strong[ ]*&gt;#i',
        '#&lt;[ ]*em[ ]*&gt;#i', '#&lt;[ ]*/[ ]*em[ ]*&gt;#i',
        '#&lt;[ ]*i[ ]*&gt;#i', '#&lt;[ ]*/[ ]*i[ ]*&gt;#i',
        '#&lt;[ ]*small[ ]*&gt;#i', '#&lt;[ ]*/[ ]*small[ ]*&gt;#i',
        '#&lt;[ ]*br[ ]*(/|)[ ]*&gt;#i'];

    $replacement = [
        '<b>', '</b>', '<strong>', '</strong>', '<em>', '</em>', '<i>', '</i>', '<small>', '</small>', '<br>'
    ];

    return preg_replace($pattern, $replacement, $string);
}

/**
 * Build languages index from machine object files
 *
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_i18n_Exception
 */
function i18n_buildLanguageIndex()
{
    $cfg = Registry::get('config');

    // Clear translation cache
    /** @var Zend_Translate $translator */
    $translator = Registry::get('Zend_Translate');

    if ($translator->hasCache()) {
        $translator->clearCache();
    }

    # Clear opcode cache if any
    OpcodeCacheManager::clearAllActive();

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cfg['GUI_ROOT_DIR'] . '/i18n/locales/', FilesystemIterator::SKIP_DOTS)
    );

    $availableLanguages = [];

    /** @var $item SplFileInfo */
    foreach ($iterator as $item) {
        if (!$item->isReadable()) {
            continue;
        }

        $basename = $item->getBasename();
        $parser = new GettextParser($item->getPathname());
        $translationTable = $parser->getTranslationTable();

        if (!empty($translationTable)) {
            $poCreationDate = DateTime::createFromFormat('Y-m-d H:i O', $parser->getPotCreationDate());
            $availableLanguages[$basename] = [
                'locale'            => $parser->getLanguage(),
                'creation'          => $poCreationDate->format('Y-m-d H:i'),
                'translatedStrings' => $parser->getNumberOfTranslatedStrings(),
                'lastTranslator'    => $parser->getLastTranslator()
            ];

            if (isset($translationTable['_: Localised language'])) {
                $availableLanguages[$basename]['language'] = $translationTable['_: Localised language'];
            } else {
                $availableLanguages[$basename]['language'] = tr('Unknown');
            }

            continue;
        }

        if (PHP_SAPI != 'cli') {
            set_page_message(
                tr('The %s translation file has been ignored: Translation table is empty.', $basename), 'warning'
            );
        }
    }

    $dbConfig = Registry::get('dbConfig');
    sort($availableLanguages);
    $serializedData = serialize($availableLanguages);
    $dbConfig['AVAILABLE_LANGUAGES'] = $serializedData;
    $cfg['AVAILABLE_LANGUAGES'] = $serializedData;
}

/**
 * Returns list of available languages
 *
 * @param bool $localesOnly Flag indicating whether or not only list of locales must be returned
 * @return array Array that contains information about available languages
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 */
function i18n_getAvailableLanguages($localesOnly = false)
{
    $cfg = Registry::get('config');

    if (!isset($cfg['AVAILABLE_LANGUAGES'])
        || !isSerialized($cfg['AVAILABLE_LANGUAGES'])
    ) {
        i18n_buildLanguageIndex();
    }

    $languages = unserialize($cfg['AVAILABLE_LANGUAGES']);

    if ($localesOnly) {
        $locales = [Locale::BROWSER];

        foreach ($languages as $language) {
            $locales[] = $language['locale'];
        }

        return $locales;
    }

    array_unshift($languages, [
        'locale'            => Locale::BROWSER,
        'creation'          => tr('N/A'),
        'translatedStrings' => tr('N/A'),
        'lastTranslator'    => tr('N/A'),
        'language'          => tr('Auto (Browser language)')
    ]);

    return $languages;
}

/**
 * Import Machine object file in languages directory
 *
 * @return bool TRUE on success, FALSE otherwise
 * @throws Zend_Exception
 * @throws iMSCP_i18n_Exception
 */
function i18n_importMachineObjectFile()
{
    // closure that is run before move_uploaded_file() function - See the Utils_UploadFile() function for further
    // information about implementation details
    $beforeMove = function () {
        $localesDirectory = Registry::get('config')['GUI_ROOT_DIR'] . '/i18n/locales';
        $filePath = $_FILES['languageFile']['tmp_name'];

        if (!is_readable($filePath)) {
            set_page_message(tr('File is not readable.'), 'error');
            return false;
        }

        try {
            $parser = new GettextParser($filePath);
            $encoding = $parser->getContentType();
            $locale = $parser->getLanguage();
            $creation = $parser->getPotCreationDate();
            $translationTable = $parser->getTranslationTable();
        } catch (iMSCPException $e) {
            set_page_message(tr('Only gettext Machine Object files (MO files) are accepted.'), 'error');
            return false;
        }

        $language = isset($translationTable['_: Localised language']) ? $translationTable['_: Localised language'] : '';

        if (empty($encoding)
            || empty($locale)
            || empty($creation)
            || empty($lastTranslator)
            || empty($language)
        ) {
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

    if (utils_uploadFile('languageFile', [$beforeMove]) === false) {
        return false;
    }

    // Rebuild language index
    i18n_buildLanguageIndex();
    return true;
}

/**
 * Change panel default language
 *
 * @return bool TRUE if language name is valid, FALSE otherwise
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function i18n_changeDefaultLanguage()
{
    if (!isset($_POST['defaultLanguage'])) {
        return false;
    }

    $defaultLanguage = clean_input($_POST['defaultLanguage']);
    $availableLanguages = i18n_getAvailableLanguages();

    // Check for language availability
    $isValidLanguage = false;
    foreach ($availableLanguages as $languageDefinition) {
        if ($languageDefinition['locale'] == $defaultLanguage) {
            $isValidLanguage = true;
        }
    }

    if (!$isValidLanguage) {
        return false;
    }

    $dbConfig = Registry::get('dbConfig');
    $dbConfig['USER_INITIAL_LANG'] = $defaultLanguage;
    Registry::get('config')['USER_INITIAL_LANG'] = $defaultLanguage;

    // Ensures language change on next load for current user in case he has not yet his gui properties explicitly
    // set (eg. for the first admin user when i-MSCP was just installed
    $stmt = exec_query('SELECT lang FROM user_gui_props WHERE user_id = ?', $_SESSION['user_id']);
    if ($stmt->fetchRow(PDO::FETCH_COLUMN) == NULL) {
        unset($_SESSION['user_def_lang']);
    }

    return true;
}

/**
 * Add translations
 *
 * This function allow any plugin to add its translations.
 *
 * @param string $dirPath Absolute path to the translations root directory
 * @param string $type Translations type (array, Csv, Gettext, Ini, Qt, Tbx, Tmx, Xliff, XmlTm)
 * @param string $tag Tag which is used for caching (eg. plugin name)
 * @param string|null $scan If set to NULL, no scanning of the directory structure will be done. If set to
 *                          Translator::LOCALE_DIRECTORY the locale will be detected within the directory.
 *                          If set to Translator::LOCALE_FILENAME the locale will be detected within the filename.
 * @return void
 * @throws Zend_Exception
 * @throws Zend_Translate_Exception
 */
function l10n_addTranslations($dirPath, $type = 'Array', $tag = 'iMSCP_Translate', $scan = Translator::LOCALE_FILENAME)
{
    /** @var Zend_Translate_Adapter $primaryTranslator */
    $primaryTranslator = Registry::get('Zend_Translate')->getAdapter();
    $locale = $primaryTranslator->getLocale();

    $pluginTranslator = new Translator([
        'adapter'        => $type,
        'content'        => $dirPath,
        'scan'           => $scan,
        'locale'         => $locale,
        'disableNotices' => true,
        'tag'            => $tag
    ]);

    if ($pluginTranslator->getAdapter()->isAvailable($locale)) {
        $primaryTranslator->addTranslation([
            'content' => $pluginTranslator
        ]);
    }
}

/**
 * Get JS translations strings
 *
 * Note: Plugins can register their own JS translation strings by listening on
 * the onGetJsTranslations event, and add them to the translations ArrayObject
 * which is a parameter of that event.
 *
 * For instance:
 *
 * EventsManager::getInstance()->registerListener('onGetJsTranslations', function($e) {
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
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 */
function i18n_getJsTranslations()
{
    $translations = new ArrayObject([
        // Core translation strings
        'core' => [
            'ok'                      => tr('Ok'),
            'warning'                 => tr('Warning!'),
            'yes'                     => tr('Yes'),
            'no'                      => tr('No'),
            'confirmation_required'   => tr('Confirmation required'),
            'close'                   => tr('Close'),
            'generate'                => tr('Generate'),
            'show'                    => tr('Show'),
            'your_new_password'       => tr('Your new password'),
            'password_generate_alert' => tr('You must first generate a password by clicking on the generate button.'),
            'password_length'         => Registry::get('config')['PASSWD_CHARS']
        ]],
        ArrayObject::ARRAY_AS_PROPS
    );

    EventsManager::getInstance()->dispatch(Events::onGetJsTranslations, ['translations' => $translations]);
    return json_encode($translations, JSON_FORCE_OBJECT);
}
