<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
 * @package     iMSCP_Utilities
 * @subpackage  String
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Utility class to generate random strings
 *
 * @category    iMSCP
 * @package     iMSCP_Utilities
 * @subpackage  String
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @since       1.0.0
 * @version     1.0.0
 */
class iMSCP_Utilities_String_Random
{
	/**
	 * @var array ASCII alpha characters (LOWERCASE)
	 */
	protected static $alpha = array("\x61", "\x7A");

	/**
	 * @var array ASCII digital characters
	 */
	protected static $digit = array("\x30", "\x39");

	/**
	 * @var array ASCII alpha characters (UPPERCASE)
	 */
	protected static $alphaUpper = array("\x41", "\x5A");

	/**
	 * @var array ASCII special characters (PRINTABLE|NO BLANK)
	 */
	protected static $specialChar = array(
		array("\x21", "\x2F"),
		array("\x3A", "\x40"),
		array("\x5b", "\x60"),
		array("\x7B", "\x7E")
	);

	/**
	 * Generate random ASCII alpha string
	 *
	 * @static
	 * @param  $stringLength Random string length
	 * @param string $case Random string case (lower|upper|mixed)
	 * @param string $specialCharacters Tells whether or not the generated string can contain special ASCII characters
	 * @return string Random ASCII alpha string
	 */
	public static function alpha($stringLength, $case = 'lower') {

		$pool = '';

		switch($case)
		{
			case 'upper':
				$pool = range(self::$alphaUpper[0], self::$alphaUpper[1]);
			break;
			case 'mixed':
				$pool = range(self::$alphaUpper[0], self::$alphaUpper[1]);
			default:
				$pool = array_merge((array) $pool, range(self::$alpha[0], self::$alpha[1]));
		}

		return self::_generateRandomString($pool, (int) $stringLength);
	}

	/**
	 * Generate random ASCII digital string
	 * 
	 * @static
	 * @param  $stringLength Random string length
	 * @return string Random ASCII Digital string
	 */
	public static function digit($stringLength)
	{
		return self::_generateRandomString(range(self::$digit[0], self::$digit[1]), (int) $stringLength);
	}

	/**
	 * Generate random ASCII alphanumeric string
	 *
	 * @static
	 * @param  $stringLength Random string length
	 * @param string $case Random string case (lower|upper|mixed)
	 * @return string Random ASCII Alphanumeric string
	 */
	public static function alnum($stringLength, $case = 'mixed')
	{
		$pool = '';

		switch($case)
		{
			case 'upper':
				$pool = range(self::$alphaUpper[0], self::$alphaUpper[1]);
			break;
			case 'mixed':
				$pool = range(self::$alphaUpper[0], self::$alphaUpper[1]);
			default:
				$pool = array_merge((array) $pool, range(self::$alpha[0], self::$alpha[1]));
		}

		$pool = array_merge($pool, range(self::$digit[0], self::$digit[1]));

		return self::_generateRandomString($pool, (int) $stringLength);
	}

	/**
	 * Generate random ASCII string
	 * 
	 * @static
	 * @param  $stringLength Random string length
	 * @param string $case Random string case (lower|upper|mixed)
	 * @return string Random ASCII string
	 */
	public static function ascii($stringLength, $case = 'mixed') {

		$pool = array_merge(
			($case == 'upper' || $case == 'mixed') ? range(self::$alphaUpper[0], self::$alphaUpper[1]) : array(),
			($case == 'lower' || $case == 'mixed') ? range(self::$alpha[0], self::$alpha[1]) : array(),
			range(self::$digit[0], self::$digit[1]),
			self::_buildSpecialCharRange()
		);

		return self::_generateRandomString($pool, (int) $stringLength);
	}

	/**
	 * Generate random string
	 * 
	 * @static
	 * @param  $pool Pool of characters to be used for generate the random string
	 * @param  $stringLength Random string length
	 * @return string Random string
	 */
	protected static function _generateRandomString($pool, $stringLength) {

		shuffle($pool);
		$max = sizeof($pool);
		$randomString = '';

		for($i=0; $i < $stringLength; $i++) {
			$randomString .= $pool[rand(0, $max--)];
		}

		return $randomString;
	}

	/**
	 * Builds a pool of ASCII special characters
	 *
	 * @static
	 * @return array Array that contain ASCII special characters
	 */
	protected static function _buildSpecialCharRange() {

		$pool = array();

		foreach(self::$specialChar as $specialCharRange) {
			$pool = array_merge($pool, range($specialCharRange[0], $specialCharRange[1]));
		}

		return $pool;
	}
}
