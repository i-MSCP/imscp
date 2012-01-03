<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2012 by i-MSCP Team.
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
 * @package		iMSCP
 * @package		iMSCP_Debug
 * @subpackage	Bar_Plugin
 * @copyright	2010-2012 by i-MSCP team
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * Interface for i-MSCP Debug Bar component's plugins.
 *
 * @package		iMSCP
 * @package		iMSCP_Debug
 * @subpackage	Bar_Plugin
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.1
 */
interface iMSCP_Debug_Bar_Plugin_Interface
{
	/**
	 * Returns plugin unique identifier.
	 *
	 * @abstract
	 * @return string
	 */
	public function getIdentifier();

	/**
	 * Returns plugin tab.
	 *
	 * @abstract
	 * @return string
	 */
	public function getTab();

	/**
	 * Returns the plugin panel.
	 *
	 * @abstract
	 * @return string
	 */
	public function getPanel();

	/**
	 * Returns plugin icon.
	 *
	 * @abstract
	 * @return string
	 */
	public function getIcon();
}
