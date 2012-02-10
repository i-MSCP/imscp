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
 * Timer plugin for the i-MSCP Debug Bar component.
 *
 * Provides timing information of current request, time spent in level scripts and
 * custom timers.
 *
 * @package		iMSCP
 * @package		iMSCP_Debug
 * @subpackage	Bar_Plugin
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.3
 */
class iMSCP_Debug_Bar_Plugin_Timer extends iMSCP_Debug_Bar_Plugin implements iMSCP_Events_Listeners_Interface
{
	/**
	 * @var string Plugin unique identifier
	 */
	const IDENTIFIER = 'Timer';

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
	 * @var float Times
	 */
	protected $_times = array();

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
					$this->startComputeTime();
					break;
				default:
					$this->stopComputeTime();
			}
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
	 * @return array
	 */
	public function getListenedEvents()
	{
		return $this->_listenedEvents;
	}

	/**
	 * Returns menu tab for the Debugbar.
	 *
	 * The content for tab is the time elapsed since the request begin
	 *
	 * @return string
	 */
	public function getTab()
	{
		return round(($this->_times['endScript'] - ($_SERVER['REQUEST_TIME'])) * 1000, 2) . ' ms';
	}

	/**
	 * Returns content panel for the Debugbar.
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$xhtml = '<h4>Custom Timers</h4>';
		$xhtml .= "Current script (Initialization steps excluded): " .
			round(($this->_times['endScript'] - $this->_times['startScript']) * 1000, 2) . ' ms<br />';

		if (isset($this->_times['custom']) && count($this->_times['custom'])) {
			foreach ($this->_times['custom'] as $name => $time) {
				$xhtml .= '' . $name . ': ' . round($time, 2) . ' ms<br>';
			}
		}

		if (isset($_SESSION['user_type'])) {
			switch ($_SESSION['user_type']) {
				case 'user':
					$currentScriptLevel = 'client';
					break;
				default:
					$currentScriptLevel = $_SESSION['user_type'];
			}
		} else {
			$currentScriptLevel = 'noLevel';
		}

		$currentScriptName = basename($_SERVER['SCRIPT_FILENAME']);

		// Getting the overall time elapsed in millisecondes for the current script
		// (included bootstrap, initialization time...)
		$_SESSION['iMSCPdebug_Time'][$currentScriptLevel][$currentScriptName]['times'][] =
			($this->_times['endScript'] - $_SERVER['REQUEST_TIME']) * 1000;

		$xhtml .= '<h4>Overall Timers</h4>';

		foreach ($_SESSION['iMSCPdebug_Time'] as $scriptLevel => $scriptNames) {

			// Current script level hightlighting
			if ($scriptLevel == $currentScriptLevel) {
				$scriptLevel = '<strong>' . $scriptLevel . '</strong>';
			}

			$xhtml .= $scriptLevel . '<br />';
			$xhtml .= '<div class="pre">';

			foreach ($scriptNames as $scriptName => $times) {

				// Current script name hightlighting
				if ($scriptName == $currentScriptName) {
					$scriptName = '<strong>' . $scriptName . '</strong>';
				}

				$xhtml .= '    ' . $scriptName . '<br />';
				$xhtml .= '<div class="pre">';

				foreach ($times as $time) {
					$xhtml .= '            Avg: ' . $this->_computeAverageTime($time) . ' ms / ' .
						count($time) . ' requests<br />';
					$xhtml .= '            Min: ' . round(min($time), 2) . ' ms<br />';
					$xhtml .= '            Max: ' . round(max($time), 2) . ' ms<br />';
				}

				$xhtml .= '</div>';
			}

			$xhtml .= '</div>';
		}

		return $xhtml;
	}

	/**
	 * Returns plugin icon.
	 *
	 * @return string
	 */
	public function getIcon()
	{
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAKrSURBVDjLpdPbT9IBAMXx/qR6qNbWUy89WS5rmVtutbZalwcNgyRLLMyuoomaZpRQCt5yNRELL0TkBSXUTBT5hZSXQPwBAvor/fZGazlb6+G8nIfP0znbgG3/kz+Knsbb+xxNV63DLxVLHzqV0vCrfMluzFmw1OW8ePEwf8+WgM1UXDnapVgLePr5Nj9DJBJGFEN8+TzKqL2RzkenV4yl5ws2BXob1WVeZxXhoB+PP0xzt0Bly0fKTePozV5GphYQPA46as+gU5/K+w2w6Ev2Ol/KpNCigM01R2uPgDcQIRSJEYys4JmNoO/y0tbnY9JlxnA9M15bfHZHCnjzVN4x7TLz6fMSJqsPgLAoMvV1niSQBGIbUP3Ki93t57XhItVXjulTQHf9hfk5/xgGyzQTgQjx7xvE4nG0j3UsiiLR1VVaLN3YpkTuNLgZGzRSq8wQUoD16flkOPSF28/cLCYkwqvrrAGXC1UYWtuRX1PR5RhgTJTI1Q4wKwzwWHk4kQI6a04nQ99mUOlczMYkFhPrBMQoN+7eQ35Nhc01SvA7OEMSFzTv8c/0UXc54xfQcj/bNzNmRmNy0zctMpeEQFSio/cdvqUICz9AiEPb+DLK2gE+2MrR5qXPpoAn6mxdr1GBwz1FiclDcAPCEkTXIboByz8guA75eg8WxxDtFZloZIdNKaDu5rnt9UVHE5POep6Zh7llmsQlLBNLSMTiEm5hGXXDJ6qb3zJiLaIiJy1Zpjy587ch1ahOKJ6XHGGiv5KeQSfFun4ulb/josZOYY0di/0tw9YCquX7KZVnFW46Ze2V4wU1ivRYe1UWI1Y1vgkDvo9PGLIoabp7kIrctJXSS8eKtjyTtuDErrK8jIYHuQf8VbK0RJUsLfEg94BfIztkLMvP3v3XN/5rfgIYvAvmgKE6GAAAAABJRU5ErkJggg==';
	}

	/**
	 * Sets a time mark identified with given name.
	 *
	 *
	 * @param string $name Time mark unique identifier
	 * @return void
	 */
	public function mark($name)
	{
		if (isset($this->_times['custom'][$name])) {
			$this->_times['custom'][$name] =
				(microtime(true) - $_SERVER['REQUEST_TIME']) * 1000 - $this->_times['custom'][$name];
		} else {
			$this->_times['custom'][$name] = (microtime(true) - $_SERVER['REQUEST_TIME']) * 1000;
		}
	}

	/**
	 * Starts to compute time.
	 *
	 * Computes time elapsed between the begin of the request and the begin of the
	 * script. Stores the result in milliseconds. Also reset the timer if asked by
	 * user.
	 *
	 * @return void
	 */
	protected function startComputeTime()
	{
		if (isset($_REQUEST['iMSCPdebug_Reset'])) {
			unset($_SESSION['iMSCPdebug_Time']);
		}

		$this->_times['startScript'] = microtime(true);
	}

	/**
	 * Stops to compute time.
	 *
	 * Computes time elapsed between the begin of the request and the end of the
	 * script. Stores the result in milliseconds.
	 *
	 * @return void
	 */
	protected function stopComputeTime()
	{
		$this->_times['endScript'] = microtime(true);
	}

	/**
	 * Computes average time for a set of requests.
	 *
	 * @param array $array
	 * @param int $precision
	 * @return float
	 */
	protected function _computeAverageTime(array $array, $precision = 2)
	{
		if (!is_array($array)) {
			return 'ERROR in method _computeAverageTime(): this is a not array.';
		}

		foreach ($array as $value) {
			if (!is_numeric($value)) {
				return 'ERROR in method _computeAverageTime(): the array contains one or more non-numeric values.';
			}
		}

		$cuantos = count($array);
		return round(array_sum($array) / $cuantos, $precision);
	}
}
