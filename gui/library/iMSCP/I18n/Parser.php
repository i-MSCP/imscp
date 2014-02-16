<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
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
 *
 * @category    iMSCP
 * @package     iMSCP_I18n
 * @subpackage  Parser
 * @copyright   2010-2014 i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Class iMSCP_I18n_Parser
 *
 * Base class for Gettext Portable Object and Machine Object file parsers
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 */
abstract class iMSCP_I18n_Parser
{
	/**
	 * Headers.
	 *
	 * @var int
	 */
	const HEADERS = 1;

	/**
	 * Translation table.
	 *
	 * @var int
	 */
	const TRANSLATION_TABLE = 2;

	/**
	 * File handle.
	 *
	 * @var resource
	 */
	protected $fh;

	/**
	 * Path to the gettext file.
	 *
	 * @var string
	 */
	protected $filePath;

	/**
	 * Headers from gettext file.
	 *
	 * A string that contains headers, each of them separated by EOL.
	 *
	 * @var string
	 */
	protected $headers = '';

	/**
	 * Translation table.
	 *
	 * An array of pairs key/value where the keys are the original strings (msgid)
	 * and the values, the translated strings (msgstr).
	 *
	 * @var array
	 */
	protected $translationTable = array();

	/**
	 * Constructor.
	 *
	 * @throws iMSCP_i18n_Exception When file is not readable
	 * @param string $filePath Path to gettext file
	 */
	public function __construct($filePath)
	{
		$filePath = (string)$filePath;

		if (!is_readable($filePath)) {
			require_once 'iMSCP/I18n/Parser/Exception.php';
			throw new iMSCP_i18n_Parser_Exception("$filePath is not readable");
		}

		$this->filePath = $filePath;
	}

	/**
	 * Returns headers.
	 *
	 * @return string A string that contains gettext file headers, each separed by EOL
	 */
	public function getHeaders()
	{
		if (empty($this->headers)) {
			$this->headers = $this->_parse(self::HEADERS);
		}

		return $this->headers;
	}

	/**
	 * Returns translation table.
	 *
	 * @return array An array of pairs key/value where the keys are the original strings (msgid) and the values, the
	 *               translated strings (msgstr)
	 */
	public function getTranslationTable()
	{
		if (empty($this->translationTable)) {
			$this->translationTable = $this->_parse(self::TRANSLATION_TABLE);
		}

		return $this->translationTable;
	}

	/**
	 * Retruns project id version header value.
	 *
	 * @return string Project id version header value
	 */
	public function getProjectIdVersion()
	{
		return $this->_getHeaderValue('Project-Id-Version:');
	}

	/**
	 * Returns report msgid bugs value header value.
	 *
	 * @return string R eport msgid bugs header value
	 */
	public function getReportMsgidBugs()
	{
		return $this->_getHeaderValue('Report-Msgid-Bugs-To:');
	}

	/**
	 * Returns pot creation date header value.
	 *
	 * @return string POT creation date header value
	 */
	public function getPotCreationDate()
	{
		return $this->_getHeaderValue('POT-Creation-Date:');
	}

	/**
	 * Returns po creation date header value.
	 *
	 * @return string PO creation date header value
	 */
	public function getPoRevisionDate()
	{
		return $this->_getHeaderValue('PO-Revision-Date:');
	}

	/**
	 * Returns last translator header value.
	 *
	 * @return string Last translator header value
	 */
	public function getLastTranslator()
	{
		return $this->_getHeaderValue('Last-Translator:');
	}

	/**
	 * Returns language team header value.
	 *
	 * @return string language team header value
	 */
	public function getLanguageTeam()
	{
		return $this->_getHeaderValue('Language-Team:');
	}

	/**
	 * Returns mime version header value.
	 *
	 * @return string Mime version header value
	 */
	public function getMimeVersion()
	{
		return $this->_getHeaderValue('MIME-Version:');
	}

	/**
	 * Returns content type header value.
	 *
	 * @return string Content type header value
	 */
	public function getContentType()
	{
		return $this->_getHeaderValue('Content-Type:');
	}

	/**
	 * Returns content transfer encoding header value.
	 *
	 * @return string Content transfer encoding header value
	 */
	public function getContentTransferEncoding()
	{
		return $this->_getHeaderValue('Content-Transfer-Encoding:');
	}

	/**
	 * Returns language header value.
	 *
	 * @return string Language header value
	 */
	public function getLanguage()
	{
		return $this->_getHeaderValue('Language:');
	}

	/**
	 * Returns plural forms header value.
	 *
	 * @return string Plural forms header value
	 */
	public function getPluralForms()
	{
		return $this->_getHeaderValue('Plural-Forms:');
	}

	/**
	 * Returns number of translated strings.
	 *
	 * @abstract
	 * @return int Number of translated strings
	 */
	abstract public function getNumberOfTranslatedStrings();

	/**
	 * Parse file.
	 *
	 * @abstract
	 * @param int $part Part file to parse {@link self::HEADER} or {@link self::TRANSLATION_TABLE}
	 * @return array|string An array of pairs key/value where the keys are the original strings (msgid) and the values,
	 *                      the translated strings (msgstr) or a string that contains headers, each of them separated by
	 *                      EOL.
	 */
	abstract protected function _parse($part);

	/**
	 * Returns given header value.
	 *
	 * @param string $header header name
	 * @return string header value
	 */
	protected function _getHeaderValue($header)
	{
		$headers = $this->getHeaders();
		$header = str_replace(chr(13), '',substr($headers, strpos($headers, $header)));

		$header =  substr($header, ($start = strpos($header, ':') + 2), (strpos($header, chr(10)) - $start));

		return (!empty($header)) ? $header : '';
	}

	/**
	 * Destructor.
	 */
	public function __destruct()
	{
		if($this->fh !== null) {
			fclose($this->fh);
		}
	}
}
