<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP Team
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
 * @copyright   2010-2011 i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Base class to parse gettex files (*.po, *.mo)
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @version 0.0.1
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
	protected $_fh;

	/**
	 * Path to the gettext file.
	 *
	 * @var string
	 */
	protected $_filePath;

	/**
	 * Headers from gettext file.
	 *
	 * @var string
	 */
	protected $_headers = '';

	/**
	 * Translation table.
	 *
	 * @var array
	 */
	protected $_translationTable = array();

	/**
	 * Constructor.
	 *
	 * @throws iMSCP_i18n_Exception When file is not readable
	 * @param $filePath Path to gettext file
	 */
	public function __construct($filePath)
	{
		$filePath = (string)$filePath;

		if (!is_readable($filePath)) {
			require_once 'iMSCP/I18n/Parser/Exception.php';
			throw new iMSCP_i18n_Parser_Exception("$filePath is not readable");
		}

		$this->_filePath = $filePath;
	}

	/**
	 * Returns headers.
	 *
	 * @return string A string that contains headers
	 */
	public function getHeaders()
	{
		if (empty($this->_headers)) {
			$this->_headers = $this->_parse(self::HEADERS);
		}

		return $this->_headers;
	}

	/**
	 * Returns translation table.
	 *
	 * @return array
	 */
	public function getTranslationTable()
	{
		if (empty($this->_translationTable)) {
			$this->_translationTable = $this->_parse(self::TRANSLATION_TABLE);
		}

		return $this->_translationTable;
	}

	/**
	 * Retruns project id version.
	 *
	 * @return string Header value
	 */
	public function getProjectIdVersion()
	{
		return $this->_getHeaderValue('Project-Id-Version:');
	}

	/**
	 * Returns report msgid bugs value.
	 *
	 * @return string Header value
	 */
	public function getReportMsgidBugs()
	{
		return $this->_getHeaderValue('Report-Msgid-Bugs-To:');
	}

	/**
	 * Returns pot creation date.
	 *
	 * @return string Header value
	 */
	public function getPotCreationDate()
	{
		return $this->_getHeaderValue('POT-Creation-Date:');
	}

	/**
	 * Returns po creation date.
	 *
	 * @return string Header value
	 */
	public function getPoRevisionDate()
	{
		return $this->_getHeaderValue('PO-Revision-Date:');
	}

	/**
	 * Returns last translator.
	 *
	 * @return string Header value
	 */
	public function getLastTranslator()
	{
		return $this->_getHeaderValue('Last-Translator:');
	}

	/**
	 * Returns language team.
	 *
	 * @return string Header value
	 */
	public function getLanguageTeam()
	{
		return $this->_getHeaderValue('Language-Team:');
	}

	/**
	 * Returns mime version.
	 *
	 * @return string Header value
	 */
	public function getMimeVersion()
	{
		return $this->_getHeaderValue('MIME-Version:');
	}

	/**
	 * Returns content type.
	 *
	 * @return string Header value
	 */
	public function getContentType()
	{
		return $this->_getHeaderValue('Content-Type:');
	}

	/**
	 * Returns content transfer encoding.
	 *
	 * @return string Header value
	 */
	public function getContentTransferEncoding()
	{
		return $this->_getHeaderValue('Content-Transfer-Encoding:');
	}

	/**
	 * Returns language.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->_getHeaderValue('Language:');
	}

	/**
	 * Returns Plural Forms value.
	 *
	 * @return string
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
	 * @param $part part file to parse (ALL|HEADER|TRANSLATION_TABLE
	 * @return void
	 */
	abstract protected function _parse($part);

	/**
	 * Returns given header value.
	 *
	 * @param string $header header name
	 * @return string header
	 */
	protected function _getHeaderValue($header)
	{
		$headers = $this->getHeaders();
		$header = substr($headers, strpos($headers, $header));

		return substr($header, ($start = strpos($header, ':') + 2),
			(strpos($header, chr(10)) - $start));
	}

	/**
	 * Destructor.
	 *
	 */
	public function __destruct()
	{
		fclose($this->_fh);
	}
}
