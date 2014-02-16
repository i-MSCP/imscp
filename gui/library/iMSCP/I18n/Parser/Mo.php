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
 * Class iMSCP_I18n_Parser_Mo
 *
 * Gettext Machine Object (MO) file parser.
 *
 * @see http://www.gnu.org/software/gettext/manual/gettext.html#MO-Files
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_I18n_Parser_Mo extends iMSCP_I18n_Parser
{
	/**
	 * Byte ordering.
	 *
	 * @var string
	 */
	protected $order;

	/**
	 * Number of strings in the file.
	 *
	 * @var int
	 */
	protected $nbStrings;

	/**
	 * Index table of original strings (msgid).
	 *
	 * @var array
	 */
	protected $msgidIndexTable;

	/**
	 * Index table of translated strings (msgstr).
	 *
	 * @var array
	 */
	protected $msgstrIndexTable;

	/**
	 * Returns number of stranslated strings.
	 *
	 * @return int Number of translated strings
	 */
	public function getNumberOfTranslatedStrings()
	{
		if(null === $this->nbStrings) {
			$this->getHeaders();
		}

		return $this->nbStrings - 1;
	}

	/**
	 * Parse a machine object file.
	 *
	 * @throws iMSCP_i18n_Parser_Exception When file cannot be opened
	 * @throws iMSCP_i18n_Parser_Exception When file have bad magic number
	 * @throws iMSCP_i18n_Parser_Exception When file part to parse is unknow
	 * @param int $part Part to parse - Can be either iMSCP_I18n_Parser::HEADERS or iMSCP_I18n_Parser::TRANSLATION_TABLE
	 * @return array|string An array of pairs key/value where the keys are the original strings (msgid) and the values,
	 *                      the translated strings (msgstr) or a string that contains headers, eachof them separated by
	 *                      EOL.
	 */
	protected function _parse($part)
	{
		if ($this->fh === null) {
			if (!($this->fh = fopen($this->filePath, 'rb'))) {
				require_once 'iMSCP/I18n/Parser/Exception.php';
				throw new iMSCP_i18n_Parser_Exception('Unable to open ' . $this->filePath);
			}
		}

		if ($this->order === null) {
			// Magic number
			$value = unpack('V', fread($this->fh, 4));
			$magicNumber = array_shift($value);

			if ($magicNumber == (int)0x0950412de || dechex($magicNumber) == 'ffffffff950412de') {
				$this->order = 'V'; // Little Endian
			} elseif($magicNumber == (int)0x0de120495) {
				$this->order = 'N'; // Big endian
			} else {
				require_once 'iMSCP/I18n/Parser/Exception.php';
				throw new iMSCP_i18n_Parser_Exception('Bad magic number in ' . $this->filePath);
			}

			// Skipping the revision number
			fseek($this->fh, 4, SEEK_CUR);

			// number of strings 											N
			$value = unpack($this->order, fread($this->fh, 4));
			$this->nbStrings = array_shift($value);

			// offset of table with original strings						O
			$value = unpack($this->order, fread($this->fh, 4));
			$msgidtableOffset = array_shift($value);

			// offset of table with translation strings						T
			$value = unpack($this->order, fread($this->fh, 4));
			$msgstrTableOffset = array_shift($value);

			// each string descriptor uses two 32 bits integers, one for the string
			// length, another for the offset of the string
			$count = $this->nbStrings * 2;

			// getting index of original strings
			fseek($this->fh, $msgidtableOffset);
			$this->msgidIndexTable = unpack($this->order . $count, fread($this->fh, ($count * 4)));

			// getting index of translated strings
			fseek($this->fh, $msgstrTableOffset);
			$this->msgstrIndexTable = unpack($this->order . $count, fread($this->fh, ($count * 4)));

		}

		switch ((int)$part) {
			case self::HEADERS:
				fseek($this->fh, $this->msgstrIndexTable[2]);
				return fread($this->fh, $this->msgstrIndexTable[1]);
				break;
			case self::TRANSLATION_TABLE:
				$nbString = $this->nbStrings;
				$parseResult = array();

				for ($index = 1; $index < $nbString; $index++) {
					// Getting msgid
					fseek($this->fh, $this->msgidIndexTable[$index * 2 + 2]);
					$msgid = fread($this->fh, $this->msgidIndexTable[$index * 2 + 1]);

					// Getting msgstr
					fseek($this->fh, $this->msgstrIndexTable[$index * 2 + 2]);

					if (!$length = $this->msgstrIndexTable[$index * 2 + 1]) {
						$msgstr = '';
					} else {
						$msgstr = fread($this->fh, $length);
					}

					$parseResult[$msgid] = $msgstr;
				}

				return $parseResult;
				break;
			default:
				require_once 'iMSCP/I18n/Parser/Exception.php';
				throw new iMSCP_i18n_Parser_Exception('Unknown part type to parse');
		}
	}
}
