<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2011 i-MSCP Team
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
 * @package     iMSCP_URI
 * @subpackage  parser
 * @copyright   2011 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-mscp Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/** @see iMSCP_Uri_Parser_ResultMixin */
require_once 'iMSCP/Uri/Parser/ResultMixin.php';

/**
 * Parser Split Result class.
 * 
 * @category    iMSCP
 * @package     iMSCP_URI
 * @subpackage  parser
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class iMSCP_Uri_Parser_SplitResult extends iMSCP_Uri_Parser_ResultMixin
{
	/**
	 * Constructor.
	 *
	 * @param string $scheme Scheme component
	 * @param string $authority Authority component
	 * @param string $path Path component
	 * @param string $query Query component
	 * @param string $fragment Fragment component
	 */
	public function __construct($scheme, $authority, $path, $query, $fragment)
	{
		parent::__construct(array($scheme, $authority, $path, $query, $fragment));
	}

	/**
	 * Return string representation of this object.
	 *
	 * @return string
	 */
	public function getUri()
	{
		return iMSCP_Uri_Parser::getInstance()->unsplitUri($this);
	}
}
