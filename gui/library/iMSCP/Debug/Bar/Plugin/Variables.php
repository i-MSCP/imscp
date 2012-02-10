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

/** @See iMSCP_Debug_Bar_Plugin */
require_once 'iMSCP/Debug/Bar/Plugin.php';

/**
 * Variables plugin for the i-MSCP Debug Bar component.
 *
 * Provides debug information about variables such as $_GET, $_POST...
 *
 * @package		iMSCP
 * @package		iMSCP_Debug
 * @subpackage	Bar_Plugin
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.2
 */
class iMSCP_Debug_Bar_Plugin_Variables extends iMSCP_Debug_Bar_Plugin
{
	/**
	 * @var string Plugin unique identifier
	 */
	const IDENTIFIER = 'Variables';

	/**
	 * Returns plugin unique identifier.
	 *
	 * @return string Plugin unique identifier.
	 */
	public function getIdentifier()
	{
		return self::IDENTIFIER;
	}

	/**
	 * Returns list of events that this plugin listens on.
	 *
	 * @return array
	 */
	public function getListenedEvents()
	{
		return array();
	}

	/**
	 * Returns plugin tab.
	 *
	 * @return string
	 */
	public function getTab()
	{
		return $this->getIdentifier();
	}

	/**
	 * Returns the plugin panel.
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$vars = '<h4>Variables</h4>';

		$vars .= '<h4>$_GET:</h4>'
			. '<div id="iMSCPdebug_get">' . $this->_humanize($_GET) . '</div>';

		$vars .= '<h4>$_POST:</h4>'
			. '<div id="iMSCPdebug_post">' . $this->_humanize($_POST) . '</div>';

		$vars .= '<h4>$_COOKIE:</h4>'
			. '<div id="iMSCPdebug_cookie">' . $this->_humanize($_COOKIE) . '</div>';

		$vars .= '<h4>$_FILES:</h4>'
			. '<div id="iMSCPdebug_file">' . $this->_humanize($_FILES) . '</div>';

		$vars .= '<h4>$_SESSION:</h4>'
			. '<div id="iMSCPdebug_session">' . $this->_humanize($_SESSION) . '</div>';

		$vars .= '<h4>$_SERVER:</h4>'
			. '<div id="iMSCPdebug_server">' . $this->_humanize($_SERVER) . '</div>';

		$vars .= '<h4>$_ENV:</h4>'
			. '<div id="iMSCPdebug_env">' . $this->_humanize($_ENV) . '</div>';

		return $vars;
	}

	/**
	 * Returns plugin icon.
	 *
	 * @return string
	 */
	public function getIcon()
	{
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAFWSURBVBgZBcE/SFQBAAfg792dppJeEhjZn80MChpqdQ2iscmlscGi1nBPaGkviKKhONSpvSGHcCrBiDDjEhOC0I68sjvf+/V9RQCsLHRu7k0yvtN8MTMPICJieaLVS5IkafVeTkZEFLGy0JndO6vWNGVafPJVh2p8q/lqZl60DpIkaWcpa1nLYtpJkqR1EPVLz+pX4rj47FDbD2NKJ1U+6jTeTRdL/YuNrkLdhhuAZVP6ukqbh7V0TzmtadSEDZXKhhMG7ekZl24jGDLgtwEd6+jbdWAAEY0gKsPO+KPy01+jGgqlUjTK4ZroK/UVKoeOgJ5CpRyq5e2qjhF1laAS8c+Ymk1ZrVXXt2+9+fJBYUwDpZ4RR7Wtf9u9m2tF8Hwi9zJ3/tg5pW2FHVv7eZJHd75TBPD0QuYze7n4Zdv+ch7cfg8UAcDjq7mfwTycew1AEQAAAMB/0x+5JQ3zQMYAAAAASUVORK5CYII=';
	}
}
