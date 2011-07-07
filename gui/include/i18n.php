<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010 by i-MSCP | http://i-mscp.net
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

/**
 * Translates a given string into the selected language, if exists.
 *
 * @author Laurent Declercq (nuxwin) <l.declercq@nuxwin.com>
 * @author Raphael Geissert (2007)
 * @param string $msgid string to translate
 * @param mixed $substitution Prevent the returned string from being replaced
 *                            with html entities
 * @return Translated or original string
 */
function tr($msgid, $substitution = false)
{
	$msgstr = T_($msgid);

	// Detect whether $substitution is really a substitution or just a value to
	// be replaced in $msgstr
	if (!is_bool($substitution)) {
		$substitution = false;
	}

	// Detect comments and strip them if $msgid == $msgstr
	// e.g. tr('_: This is just a comment\nReal message to translate here')
	if (substr($msgid, 0, 3) == '_: ' && $msgid == $msgstr &&
		count($l = explode("\n", $msgid)) > 1
	) {
		unset($l[0]);
		$msgstr = implode("\n", $l);
	}

	// Replace values
	if (func_num_args() > 1) {
		$argv = func_get_args();
		unset($argv[0]);

		if (is_bool($argv[1])) {
			unset($argv[1]);
		}

		$msgstr = vsprintf($msgstr, $argv);
	}

	if (!$substitution) {
		$msgstr = replace_html(htmlentities($msgstr, ENT_COMPAT, 'UTF-8'));
	}

	return $msgstr;
}

/**
 * Replaces special encoded strings back to their original signs
 *
 * @author Benedikt Heintel <benedikt.heintel@ispcp.net>
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
		'<b>', '</b>', '<strong>', '</strong>', '<em>', '</em>', '<i>', '</i>',
		'<small>', '</small>', '<br />');

	$string = preg_replace($pattern, $replacement, $string);

	return $string;
}

// Dirty hack to make gettext add this entry to the .pot file
if (false) {
	tr('_: Localised language');
}

/**
 * Build languages index from machine object files.
 *
 * Note: Only the files that are readable will be processed.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @since i-MSCP 1.0.1.4
 * @return void
 */
function i18n_buildLanguageIndex()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
			$cfg->GUI_ROOT_DIR . '/i18n/locales/', FilesystemIterator::SKIP_DOTS));

	$availableLanguages = array();

	/** @var $item SplFileInfo */
	foreach ($iterator as $item) {
		$basename = $item->getBasename();

		if ($item->isReadable()) {
			$parser = new iMSCP_I18n_Parser_Mo($item->getPathname());
			$poRevisionDate = DateTime::createFromFormat(
				'Y-m-d H:i O', $parser->getPotCreationDate());

			$availableLanguages[$basename] = array(
				'locale' => $parser->getLanguage(),
				'revision' => $poRevisionDate->format('Y-m-d H:i'),
				'translatedStrings' => $parser->getNumberOfTranslatedStrings(),
				'lastTranslator' => $parser->getLastTranslator());

			// Getting localized language name
			$translationTable = $parser->getTranslationTable();
			$availableLanguages[$basename]['language'] =
				$translationTable['_: Localised language'];
		}
	}

	/** @var $dbConfig iMSCP_Config_Handler_Db */
	$dbConfig = iMSCP_Registry::get('dbConfig');

	sort($availableLanguages);
	$dbConfig->AVAILABLE_LANGUAGES = serialize($availableLanguages);
	$cfg->replaceWith($dbConfig);
}

/**
 * Returns list of available languages with some informations.
 *
 * Note: For safe reasons, only the files that are readable will be indexed.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @since i-MSCP 1.0.1.4
 * @return array Array that contains information about available languages
 */
function i18n_getAvailableLanguages()
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (!isset($cfg->AVAILABLE_LANGUAGES) || !is_serialized($cfg->AVAILABLE_LANGUAGES)
	) {
		i18n_buildLanguageIndex();
	}

	return unserialize(($cfg->AVAILABLE_LANGUAGES));
}

/**
 * Return name of domain being used.
 *
 * Note: See #130 for further explaination.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @since i-MSCP 1.0.1.4
 * @throws iMSCP_Exception
 * @param string $upstreamDomain Upstream domain name
 * @return string Domain being used
 */
function i18n_getDomain($upstreamDomain)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$domainDirectory = $cfg->GUI_ROOT_DIR . "/i18n/locales/$upstreamDomain/LC_MESSAGES";
	$upstreamFileModificationTime = filemtime($domainDirectory . "/$upstreamDomain.mo");
	$domain = $upstreamDomain . '_' . $upstreamFileModificationTime;

	if(!file_exists($domainDirectory . "/$domain.mo")) {
		if(!@copy($domainDirectory . "/$upstreamDomain.mo", $domainDirectory . "/$domain.mo")) {
			write_log("i18n: Unable to create $domainDirectory/$domain.mo domain file for production", E_USER_ERROR);
		} else {
			write_log("i18n: Created new machine object file $domainDirectory/$domain.mo for production.", E_USER_NOTICE);
			i18n_domainsGarbageCollector($domainDirectory,  $domain . '.mo');
		}
	}

	return $domain;
}

/**
 * Garbage collector for domains translation files.
 *
 * Note: See #130 for further explaination.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @since i-MSCP 1.0.1.4
 * @param $domainDirectory Current domain directory path
 * @param $skipDomain Domain that must not be removed
 * @return void
 */
function i18n_domainsGarbageCollector($domainDirectory, $skipDomain)
{
	$currentDomainFilepath = $domainDirectory . '/' . $skipDomain;
	$domainsFiles = glob($domainDirectory . '/*_*_*.mo');

	foreach($domainsFiles as $file) {
		if($file !=  $currentDomainFilepath) {
			if(@unlink($file)) {
				write_log("i18n: Removed $file machine object production file.", E_USER_NOTICE);
			} else {
				write_log("i18n: Unable to removed $file machine object production file.", E_USER_ERROR);
			}
		}
	}
}
