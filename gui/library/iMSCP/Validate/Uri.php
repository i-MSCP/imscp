<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2011-2011 by i-MSCP Team
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
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Validate
 * @copyright	2010-2014 by by i-MSCP team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.1
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

 /** @see Zend_Validate_Abstract */
require_once 'Zend/Validate/Abstract.php';

/** @See Zend_Uri */
require_once 'Zend/Uri.php';

/**
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Validate
 * @author		Laurent Declercq <l.declercq@i-mscp.net>
 * @version		0.0.1
 */
class iMSCP_Validate_Uri extends Zend_Validate_Abstract
{
	const INVALID_URI = 'invalidURI';

	protected $_messageTemplates = array(
		self::INVALID_URI => "'%value%' is not a valid URI.",
	);

	/**
	 * Returns true if the $uri is valid
	 *
	 * If $uri is not a valid URI, then this method returns false, and
	 * getMessages() will return an array of messages that explain why the
	 * validation failed.
	 *
	 * @throws Zend_Validate_Exception If validation of $value is impossible
	 * @param  string $uri URI to be validated
	 * @return boolean
	 */
	public function isValid($uri)
	{
		$uri = (string) $uri;
		$this->_setValue($uri);

		try {
			Zend_Uri::factory($uri, 'iMSCP_Uri_Redirect');
		} catch(Exception $e) {
			$this->_error(self::INVALID_URI);
			return false;
		}

		return true;
	}
}
