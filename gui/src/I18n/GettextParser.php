<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

declare(strict_types=1);

namespace iMSCP\I18n;

/**
 * Class GettextParser
 * @package iMSCP\I18n
 */
class GettextParser
{
    /**
     * @var int Headers
     */
    const HEADERS = 1;

    /**
     * @var int Translation table
     */
    const TRANSLATION_TABLE = 2;

    /**
     * @var resource File handle
     */
    protected $fh;

    /**
     * @var string Path to the gettext file
     */
    protected $filePath;

    /**
     * @var string Headers from gettext file
     */
    protected $headers = '';

    /**
     * @var array Translation table
     */
    protected $translationTable = [];

    /**
     * @var string Whether the current file is little endian
     */
    protected $littleEndian;

    /**
     * @var int Number of strings in the file.
     */
    protected $nbStrings;

    /**
     * @var array Index table of original strings (msgid).
     */
    protected $msgidIndexTable;

    /**
     * @var array Index table of translated strings (msgstr)
     */
    protected $msgstrIndexTable;

    /**
     * @var bool Does the mo file is loaded?
     */
    protected $isLoaded;

    /**
     * Constructor
     *
     * @param string $filePath Path to gettext file
     * @throws ParserException When file is not readable
     */
    public function __construct($filePath)
    {
        $filePath = (string)$filePath;

        if (!is_readable($filePath)) {
            throw new ParserException(sprintf('%s is not readable', $filePath));
        }

        $this->filePath = $filePath;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if (is_resource($this->fh)) {
            fclose($this->fh);
        }
    }

    /**
     * Returns translation table
     *
     * @return array An array of pairs key/value where the keys are the original
     *               strings (msgid) and the values, the translated strings
     *               (msgstr)
     * @throws ParserException
     */
    public function getTranslationTable()
    {
        if (!$this->translationTable) {
            $this->translationTable = $this->_parse(self::TRANSLATION_TABLE);
        }

        return $this->translationTable;
    }

    /**
     * Parse a machine object file
     *
     * @param int $part self::HEADERS|self::TRANSLATION_TABLE
     * @return array|string An array of pairs key/value where the keys are the
     *                      original strings (msgid) and the values, the
     *                      translated strings (msgstr) or a string that
     *                      contains headers, each of them separated by EOL.
     * @throws ParserException on failure
     */
    protected function _parse($part)
    {
        if ($this->fh === NULL) {
            if (!($this->fh = fopen($this->filePath, 'rb'))) {
                throw new ParserException('Unable to open ' . $this->filePath);
            }
        }

        if ($this->isLoaded === NULL) {
            // Magic number
            $magic = fread($this->fh, 4);

            if ($magic == "\x95\x04\x12\xde") {
                $this->littleEndian = false;
            } elseif ($magic == "\xde\x12\x04\x95") {
                $this->littleEndian = true;
            } else {
                fclose($this->fh);
                throw new ParserException(sprintf(
                    '%s is not a valid gettext file', $this->filePath
                ));
            }

            // Verify major revision (only 0 and 1 supported)
            $majorRevision = ($this->readInteger() >> 16);

            if ($majorRevision !== 0 && $majorRevision !== 1) {
                fclose($this->fh);
                throw new ParserException(sprintf(
                    '%s has an unknown major revision', $this->filePath
                ));
            }

            // Number of strings
            $this->nbStrings = $this->readInteger();
            // Offset of table with original strings
            $msgidTableOffset = $this->readInteger();
            // Offset of table with translation strings
            $msgstrTableOffset = $this->readInteger();

            // Getting index of original strings
            fseek($this->fh, $msgidTableOffset);
            $this->msgidIndexTable = $this->readIntegerList(
                2 * $this->nbStrings
            );

            // Getting index of translated strings
            fseek($this->fh, $msgstrTableOffset);
            $this->msgstrIndexTable = $this->readIntegerList(
                2 * $this->nbStrings
            );

            $this->isLoaded = true;
        }

        switch ((int)$part) {
            case self::HEADERS:
                fseek($this->fh, $this->msgstrIndexTable[2]);
                return fread($this->fh, $this->msgstrIndexTable[1]);
                break;
            case self::TRANSLATION_TABLE:
                $nbString = $this->nbStrings;
                $parseResult = [];

                for ($index = 1; $index < $nbString; $index++) {
                    // Getting msgid
                    fseek($this->fh, $this->msgidIndexTable[$index * 2 + 2]);
                    $msgid = fread(
                        $this->fh, $this->msgidIndexTable[$index * 2 + 1]
                    );

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
                throw new ParserException('Unknown part type to parse');
        }
    }

    /**
     * Read a single integer from the current file
     *
     * @return int
     */
    protected function readInteger()
    {
        if ($this->littleEndian) {
            $result = unpack('Vint', fread($this->fh, 4));
        } else {
            $result = unpack('Nint', fread($this->fh, 4));
        }

        return $result['int'];
    }

    /**
     * Read an integer from the current file
     *
     * @param int $num
     * @return array
     */
    protected function readIntegerList($num)
    {
        if ($this->littleEndian) {
            return unpack('V' . $num, fread($this->fh, 4 * $num));
        }

        return unpack('N' . $num, fread($this->fh, 4 * $num));
    }

    /**
     * Retruns project id version header value
     *
     * @return string Project id version header value
     * @throws ParserException
     */
    public function getProjectIdVersion()
    {
        return $this->_getHeaderValue('Project-Id-Version:');
    }

    /**
     * Returns given header value
     *
     * @param string $header header name
     * @return string header value
     * @throws ParserException
     */
    protected function _getHeaderValue($header)
    {
        $headers = $this->getHeaders();
        $header = str_replace(
            chr(13), '', substr($headers, strpos($headers, $header))
        );
        $header = substr(
            $header, ($start = strpos($header, ':') + 2),
            (strpos($header, chr(10)) - $start)
        );

        return (!empty($header)) ? $header : '';
    }

    /**
     * Returns headers
     *
     * @return string A string that contains gettext file headers, each
     *                separated by EOL
     * @throws ParserException
     */
    public function getHeaders()
    {
        if (!$this->headers) {
            $this->headers = $this->_parse(self::HEADERS);
        }

        return $this->headers;
    }

    /**
     * Returns report msgid bugs value header value
     *
     * @return string R eport msgid bugs header value
     * @throws ParserException
     */
    public function getReportMsgidBugs()
    {
        return $this->_getHeaderValue('Report-Msgid-Bugs-To:');
    }

    /**
     * Returns pot creation date header value
     *
     * @return string POT creation date header value
     * @throws ParserException
     */
    public function getPotCreationDate()
    {
        return $this->_getHeaderValue('POT-Creation-Date:');
    }

    /**
     * Returns po creation date header value
     *
     * @return string PO creation date header value
     * @throws ParserException
     */
    public function getPoRevisionDate()
    {
        return $this->_getHeaderValue('PO-Revision-Date:');
    }

    /**
     * Returns last translator header value
     *
     * @return string Last translator header value
     * @throws ParserException
     */
    public function getLastTranslator()
    {
        return $this->_getHeaderValue('Last-Translator:');
    }

    /**
     * Returns language team header value
     *
     * @return string language team header value
     * @throws ParserException
     */
    public function getLanguageTeam()
    {
        return $this->_getHeaderValue('Language-Team:');
    }

    /**
     * Returns mime version header value
     *
     * @return string Mime version header value
     * @throws ParserException
     */
    public function getMimeVersion()
    {
        return $this->_getHeaderValue('MIME-Version:');
    }

    /**
     * Returns content type header value
     *
     * @return string Content type header value
     * @throws ParserException
     */
    public function getContentType()
    {
        return $this->_getHeaderValue('Content-Type:');
    }

    /**
     * Returns content transfer encoding header value
     *
     * @return string Content transfer encoding header value
     * @throws ParserException
     */
    public function getContentTransferEncoding()
    {
        return $this->_getHeaderValue('Content-Transfer-Encoding:');
    }

    /**
     * Returns language header value
     *
     * @return string Language header value
     * @throws ParserException
     */
    public function getLanguage()
    {
        return $this->_getHeaderValue('Language:');
    }

    /**
     * Returns plural forms header value
     *
     * @return string Plural forms header value
     * @throws ParserException
     */
    public function getPluralForms()
    {
        return $this->_getHeaderValue('Plural-Forms:');
    }

    /**
     * Returns number of stranslated strings
     *
     * @return int Number of translated strings
     * @throws ParserException
     */
    public function getNumberOfTranslatedStrings()
    {
        if (NULL === $this->nbStrings) {
            $this->getHeaders();
        }

        return $this->nbStrings;
    }
}
