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
 * Class to parse gettext portable object files.
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 * @version 0.0.1
 */
class iMSCP_I18n_Parser_Po extends iMSCP_I18n_Parser
{
	/**
	 * Returns number of translated strings.
	 *
	 * @throws iMSCP_I18n_Parser_Exception
	 * @return void
	 */
	public function getNumberOfTranslatedStrings()
	{
		require_once 'iMSCP/I18n/Parser/Exception.php';
		throw new iMSCP_I18n_Parser_Exception('Not Yet Implemented');
	}

	/**
	 * Parse a portable object file.
	 *
	 * @throws iMSCP_I18n_Parser_Exception
	 * @param int $part Part to parse (default to iMSCP_I18n_Parser::ALL)
	 * @return array|string An array of pairs key/value where the keys are the
	 *                      original strings (msgid) and the values, the translated
	 *                      strings (msgstr) or a string that contains headers, each
	 * 						of them separated by EOL.
	 */
	protected function _parse($part)
	{
		require_once 'iMSCP/I18n/Parser/Exception.php';
		throw new iMSCP_i18n_Exception('Not Yet Implemented');
	}
}
