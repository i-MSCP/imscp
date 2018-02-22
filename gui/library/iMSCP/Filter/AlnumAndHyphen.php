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

/**
 * @see Zend_Filter_Alnum
 */
require_once 'Zend/Filter/Alnum.php';

/**
 * Class iMSCP_Filter_AlnumAndHyphen
 */
class iMSCP_Filter_AlnumAndHyphen extends Zend_Filter_Alnum
{
    /**
     * Defined by Zend_Filter_Interface
     *
     * Returns the string $value, removing all but alphabetic, digit and hyphen characters
     *
     * @param  string $value
     * @return string
     */
    public function filter($value)
    {
        $whiteSpace = $this->allowWhiteSpace ? '\s' : '';
        if (!self::$_unicodeEnabled) {
            // POSIX named classes are not supported, use alternative a-zA-Z0-9 match
            $pattern = '/[^a-zA-Z0-9' . $whiteSpace . '-]/';
        } else if (self::$_meansEnglishAlphabet) {
            //The Alphabet means english alphabet.
            $pattern = '/[^a-zA-Z0-9' . $whiteSpace . '-]/u';
        } else {
            //The Alphabet means each language's alphabet.
            $pattern = '/[^\p{L}\p{N}' . $whiteSpace . '-]/u';
        }

        return preg_replace($pattern, '', (string)$value);
    }
}
