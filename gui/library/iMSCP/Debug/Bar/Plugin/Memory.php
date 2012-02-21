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

/** @see iMSCP_Events_Listeners_Interface */
require_once 'iMSCP/Events/Listeners/Interface.php';

/**
 * Memory plugin for the i-MSCP Debug Bar component.
 *
 * Provides debug information about memory consumption.
 *
 * @package		iMSCP
 * @package		iMSCP_Debug
 * @subpackage	Bar_Plugin
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.3
 */
class iMSCP_Debug_Bar_Plugin_Memory extends iMSCP_Debug_Bar_Plugin implements iMSCP_Events_Listeners_Interface
{
	/**
	 * @var string Plugin unique identifier
	 */
	const IDENTIFIER = 'Memory';

	/**
	 * @var array Listened events
	 */
	protected $_listenedEvents = array(
		iMSCP_Events::onLoginScriptStart,
		iMSCP_Events::onLoginScriptEnd,
		iMSCP_Events::onLostPasswordScriptStart,
		iMSCP_Events::onLostPasswordScriptEnd,
		iMSCP_Events::onAdminScriptStart,
		iMSCP_Events::onAdminScriptEnd,
		iMSCP_Events::onResellerScriptStart,
		iMSCP_Events::onResellerScriptEnd,
		iMSCP_Events::onClientScriptStart,
		iMSCP_Events::onClientScriptEnd,
		iMSCP_Events::onOrderPanelScriptStart,
		iMSCP_Events::onOrderPanelScriptEnd,
		iMSCP_Events::onExceptionToBrowserStart,
		iMSCP_Events::onExceptionToBrowserEnd
	);

	/**
	 * @var array memory peak usage
	 */
	protected $_memory = array();

	/**
	 * Catchs all listener methods to avoid to declarare all of them.
	 *
	 * @throws iMSCP_Debug_Bar_Exception on an unknown listener method
	 * @param  string $listenerMethod Listener method name
	 * @param  array $arguments Enumerated array containing listener method arguments (always an iMSCP_Events_Description object)
	 * @return void
	 */
	public function __call($listenerMethod, $arguments)
	{
		if (!in_array($listenerMethod, $this->_listenedEvents)) {
			throw new iMSCP_Debug_Bar_Exception('Unknown listener method.');
		} else {
			switch ($listenerMethod) {
				case iMSCP_Events::onLoginScriptStart:
				case iMSCP_Events::onLostPasswordScriptStart:
				case iMSCP_Events::onAdminScriptStart:
				case iMSCP_Events::onResellerScriptStart:
				case iMSCP_Events::onClientScriptStart:
				case iMSCP_Events::onOrderPanelScriptStart:
				case iMSCP_Events::onExceptionToBrowserStart:
					$this->startComputeMemory();
					break;
				default:
					$this->stopComputeMemory();
			}
		}
	}

	/**
	 * Sets a memory mark identified with $name
	 *
	 * @param string $name
	 */
	public function mark($name)
	{
		if (!function_exists('memory_get_peak_usage')) {
			return;
		}

		if (isset($this->_memory['user'][$name])) {
			$this->_memory['user'][$name] = memory_get_peak_usage() - $this->_memory['user'][$name];
		} else {
			$this->_memory['user'][$name] = memory_get_peak_usage();
		}
	}

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
	 * @abstract
	 * @return array
	 */
	public function getListenedEvents()
	{
		return $this->_listenedEvents;
	}

	/**
	 * Returns plugin tab.
	 *
	 * @return string
	 */
	public function getTab()
	{
		return bytesHuman($this->_memory['whole'] = memory_get_peak_usage()) . ' of ' .
			str_replace('M', ' MiB', ini_get("memory_limit"));
	}

	/**
	 * Returns the plugin panel.
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$panel = '<h4>Memory Usage</h4>';
		$panel .= "<pre>\t<strong>Script:</strong> " . bytesHuman($this->_memory['endScript'] - $this->_memory['startScript']) . PHP_EOL;
		$panel .= "\t<strong>Whole Application:</strong> " . bytesHuman($this->_memory['whole']) . PHP_EOL . "</pre>";

		if (isset($this->_memory['user']) && count($this->_memory['user'])) {
			$panel . "<pre>";
			foreach ($this->_memory['user'] as $key => $value) {
				$panel .= "\t<strong>" . $key . ':</strong> ' . bytesHuman($value) . PHP_EOL;
			}
			$panel . '</pre>';
		}

		return $panel;
	}

	/**
	 * Returns plugin icon.
	 *
	 * @return string
	 */
	public function getIcon()
	{
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAGvSURBVDjLpZO7alZREEbXiSdqJJDKYJNCkPBXYq12prHwBezSCpaidnY+graCYO0DpLRTQcR3EFLl8p+9525xgkRIJJApB2bN+gZmqCouU+NZzVef9isyUYeIRD0RTz482xouBBBNHi5u4JlkgUfx+evhxQ2aJRrJ/oFjUWysXeG45cUBy+aoJ90Sj0LGFY6anw2o1y/mK2ZS5pQ50+2XiBbdCvPk+mpw2OM/Bo92IJMhgiGCox+JeNEksIC11eLwvAhlzuAO37+BG9y9x3FTuiWTzhH61QFvdg5AdAZIB3Mw50AKsaRJYlGsX0tymTzf2y1TR9WwbogYY3ZhxR26gBmocrxMuhZNE435FtmSx1tP8QgiHEvj45d3jNlONouAKrjjzWaDv4CkmmNu/Pz9CzVh++Yd2rIz5tTnwdZmAzNymXT9F5AtMFeaTogJYkJfdsaaGpyO4E62pJ0yUCtKQFxo0hAT1JU2CWNOJ5vvP4AIcKeao17c2ljFE8SKEkVdWWxu42GYK9KE4c3O20pzSpyyoCx4v/6ECkCTCqccKorNxR5uSXgQnmQkw2Xf+Q+0iqQ9Ap64TwAAAABJRU5ErkJggg==';
	}

	/**
	 * Start to compute memory.
	 *
	 * @return void
	 */
	protected function startComputeMemory()
	{
		if (function_exists('memory_get_peak_usage')) {
			$this->_memory['startScript'] = memory_get_peak_usage();
		}
	}

	/**
	 * Stop to compute memory.
	 *
	 * @return void
	 */
	protected function stopComputeMemory()
	{
		if (function_exists('memory_get_peak_usage')) {
			$this->_memory['endScript'] = memory_get_peak_usage();
		}
	}
}
