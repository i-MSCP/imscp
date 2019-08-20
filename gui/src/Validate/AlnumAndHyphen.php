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

namespace iMSCP\Validate;

use iMSCP\Filter\AlnumAndHyphenFilter;
use Zend_Validate_Alnum;

/**
 * Class AlnumAndHyphen
 * @package iMSCP\Validate
 */
class AlnumAndHyphen extends Zend_Validate_Alnum
{
    /**
     * Alphanumeric and hyphen filter used for validation
     *
     * @var AlnumAndHyphenFilter
     */
    protected static $_filter = NULL;

    /**
     * Defined by Zend_Validate_Interface.
     *
     * Returns true if and only if $value contains only alphabetic and digit
     * characters.
     *
     * @param string $value
     * @return boolean
     */
    public function isValid($value)
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            $this->_error(self::INVALID);
            return false;
        }

        $this->_setValue($value);

        if ('' === $value) {
            $this->_error(self::STRING_EMPTY);
            return false;
        }

        if (NULL === self::$_filter) {
            self::$_filter = new AlnumAndHyphenFilter();
        }

        /** @noinspection PhpDeprecationInspection */
        self::$_filter->allowWhiteSpace = $this->allowWhiteSpace;

        if ($value != self::$_filter->filter($value)) {
            $this->_error(self::NOT_ALNUM);
            return false;
        }

        return true;
    }
}
