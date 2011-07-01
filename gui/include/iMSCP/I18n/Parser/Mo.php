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
 * Class to parse gettext machine object files.
 *
 * @see http://www.gnu.org/software/gettext/manual/gettext.html#MO-Files
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @version 0.0.1
 */
class iMSCP_I18n_Parser_Mo extends iMSCP_I18n_Parser
{
	/**
	 * Byte ordering.
	 *
	 * @var string
	 */
	protected $_order;

	/**
	 * Number of strings in the file.
	 *
	 * @var int
	 */
	protected $_nbString;

	/**
	 * Index table of original strings (msgid)
	 *
	 * @var array
	 */
	protected $_msgidIndexTable;

	/**
	 * Index table of translated strings (msgstr)
	 *
	 * @var array
	 */
	protected $_msgstrIndexTable;

	/**
	 * Returns number of stranslated strings.
	 *
	 * @return int Number of translated strings
	 */
	public function getNumberOfTranslatedStrings()
	{
		if(null === $this->_nbString) {
			$this->getHeaders();
		}

		return $this->_nbString - 1;
	}

	/**
	 * Parse a machine object file.
	 *
	 * @throws iMSCP_i18n_Parser_Exception When file cannot be opened
	 * @throws iMSCP_i18n_Parser_Exception When file have bad magic number
	 * @throws iMSCP_i18n_Parser_Exception When file part to parse is unknow
	 * @param int $part Part to parse - Can be either iMSCP_I18n_Parser::HEADERS or
	 *                                  iMSCP_I18n_Parser::TRANSLATION_TABLE
	 * @return Array|string An array that represent a translation table or a string
	 *                      that represent the headers
	 */
	protected function _parse($part)
	{
		if ($this->_fh === null) {
			if (!($this->_fh = fopen($this->_filePath, 'rb'))) {
				require_once 'iMSCP/I18n/Parser/Exception.php';
				throw new iMSCP_i18n_Parser_Exception(
						'Unable to open ' . $this->_filePath);
			}
		}

		if ($this->_order === null) {
			// Magic number (byte 0 to 4)
			$value = unpack('V', fread($this->_fh, 4));
			$magicNumber = array_shift($value);

			if ($magicNumber == (int)0x0950412de ||
				dechex($magicNumber) == 'ffffffff950412de'
			) {
				$this->_order = 'V'; // low endian
			} elseif($magicNumber == (int)0x0de120495) {
				$this->_order = 'N'; // big endian
			} else {
				require_once 'iMSCP/I18n/Parser/Exception.php';
				throw new iMSCP_i18n_Parser_Exception(
						'Bad magic number in ' . $this->_filePath);
			}

			// Skipping the revision number (byte 4 to 8)
			fseek($this->_fh, 4, SEEK_CUR);

			// number of strings (byte 8 to 12)
			$value = unpack($this->_order, fread($this->_fh, 4));
			$this->_nbString = array_shift($value);

			// offset of table with original strings (byte 12 to 16)
			$value = unpack($this->_order, fread($this->_fh, 4));
			$msgidtableOffset = array_shift($value);

			// offset of table with translation strings (byte 16 to 20)
			$value = unpack($this->_order, fread($this->_fh, 4));
			$msgstrTableOffset = array_shift($value);

			// two integers per string (offset and size)
			$count = $this->_nbString * 2;

			// index of original strings
			fseek($this->_fh, $msgidtableOffset);
			$this->_msgidIndexTable = unpack(
				$this->_order . $count, fread($this->_fh, ($count * 4)));

			// index of translated strings
			fseek($this->_fh, $msgstrTableOffset);
			$this->_msgstrIndexTable = unpack(
				$this->_order . $count, fread($this->_fh, ($count * 4)));

		}

		switch ((int)$part) {
			case self::HEADERS:
				$nbString = 1;
				$index = 0;
				break;
			case self::TRANSLATION_TABLE:
				$nbString = $this->_nbString;
				$index = 1;
				break;
			default:
				require_once 'iMSCP/I18n/Parser/Exception.php';
				throw new iMSCP_i18n_Parser_Exception('Unknown part type to parse');
		}

		$parseResult = null;

		for ($index; $index < $nbString; $index++) {
			fseek($this->_fh, $this->_msgidIndexTable[$index * 2 + 2]);

			if (!($length = $this->_msgidIndexTable[$index * 2 + 1])) {
				$msgid = '__headers__';
			} else {
				$msgid = fread($this->_fh, $length);
			}

			fseek($this->_fh, $this->_msgstrIndexTable[$index * 2 + 2]);

			if (!$length = $this->_msgstrIndexTable[$index * 2 + 1])
				$msgstr = '';
			else
				$msgstr = fread($this->_fh, $length);

			if ($msgid == '__headers__') {
				$parseResult = $msgstr;
			} else {
				$parseResult[$msgid] = $msgstr;
			}
		}

		return $parseResult;
	}
}
