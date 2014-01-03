<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage	I18n
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/** @see Zend_Translate_Adapter */
require_once 'Zend/Translate/Adapter.php';

/**
 * Transitional adapter class for Zend.
 *
 * This adapter is coded in a dirty style. It provides an ugly way to translate
 * validation messages from Zend_validator by using the i-MSCP translation system.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	I18n
 * @copyright	2010-2014 by i-MSCP team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_I18n_Adapter_Zend extends Zend_Translate_Adapter
{
	/**
	 * Constructor.
	 *
	 * We do no want use Zend translation feature. This constructor is only intended
	 * to discard the Zend_Translate_Adapter::_constructor() call.
	 */
	public function __construct()
	{

	}

	/**
	 * Pure compatibility issue - Always return FALSE.
	 *
	 * @param $messageId
	 * @param bool $original
	 * @param null $locale
	 * @return bool
	 */
	public function isTranslated($messageId, $original = false, $locale = null)
	{
		return false;
	}

	/**
	 * Translates the given string by using i-MSCP translation system.
	 *
	 * @param $messageId $messageId Translation string
	 * @param null $locale UNUSED HERE
	 * @return string
	 */
	public function translate($messageId, $locale = null)
	{
		return tr($messageId);
	}

	/**
	 * Returns the adapter name
	 *
	 * @return string
	 */
	public function toString()
	{
		// TODO: Implement toString() method.
	}

	/**
	 * Load translation data
	 *
	 * @param  mixed			  $data
	 * @param  string|Zend_Locale $locale
	 * @param  array			  $options (optional)
	 * @return array
	 */
	protected function _loadTranslationData($data, $locale, array $options = array())
	{
		// TODO: Implement _loadTranslationData() method.
	}
}
