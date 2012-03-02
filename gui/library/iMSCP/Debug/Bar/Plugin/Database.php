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
 * Database plugin for the i-MSCP Debug Bar component.
 *
 * Provide debug information about all queries made during script execution and their
 * execution time.
 *
 * @package		iMSCP
 * @package		iMSCP_Debug
 * @subpackage	Bar_Plugin
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @version		0.0.3
 * @Todo Replace the markers to see the parameters in queries strings
 */
class iMSCP_Debug_Bar_Plugin_Database extends iMSCP_Debug_Bar_Plugin implements iMSCP_Events_Listeners_Interface
{
	/**
	 * @var string Plugin unique identifier
	 */
	const IDENTIFIER = 'Database';

	/**
	 * @var array Listened events
	 */
	protected $_listenedEvents = array(
		iMSCP_Events::onBeforeDatabaseConnection,
		iMSCP_Events::onAfterDatabaseConnection,
		iMSCP_Events::onBeforeQueryExecute,
		iMSCP_Events::onAfterQueryExecute
	);

	/**
	 * @var int Total time elapsed
	 */
	protected $_totalTimeElapsed = 0;

	/**
	 * @var array queries and their execution time
	 */
	protected $_queries = array();

	/**
	 * @var int Query index
	 */
	protected $_queryIndex = 0;

	/**
	 * Start to compute time for database connection.
	 *
	 * @param  iMSCP_Database_Events_Database $event
	 * @return void
	 */
	public function onBeforeDatabaseConnection($event)
	{
		$this->_queries[$this->_queryIndex]['time'] = microtime(true);
		$this->_queries[$this->_queryIndex]['queryString'] = 'connection';
	}

	/**
	 * Stop to compute time for database connection.
	 *
	 * @param  iMSCP_Database_Events_Database $event
	 * @return void
	 */
	public function onAfterDatabaseConnection($event)
	{
		$time = microtime(true) - $this->_queries[$this->_queryIndex]['time'];
		$this->_queries[$this->_queryIndex]['time'] = $time;
		$this->_totalTimeElapsed += $time;
		$this->_queryIndex++;
	}

	/**
	 * @param  iMSCP_Database_Events_Database $event
	 * @return void
	 */
	public function onBeforeQueryExecute($event)
	{
		$this->_queries[$this->_queryIndex]['time'] = microtime(true);
		$this->_queries[$this->_queryIndex]['queryString'] = $event->getQueryString();
	}

	/**
	 * @param  iMSCP_Database_Events_Database $event
	 * @return void
	 */
	public function onAfterQueryExecute($event)
	{
		$this->_queries[$this->_queryIndex]['time'] = ((microtime(true)) - $this->_queries[$this->_queryIndex]['time']);
		$this->_totalTimeElapsed += $this->_queries[$this->_queryIndex]['time'];
		$this->_queryIndex++;
	}

	/**
	 * Returns plugin unique identifier.
	 *
	 * @return string
	 */
	public function getIdentifier()
	{
		return self::IDENTIFIER;
	}

	/**
	 * Returns listened events.
	 *
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
		return (count($this->_queries)) . ' queries in ' . round($this->_totalTimeElapsed * 1000, 2) . ' ms';
	}

	/**
	 * Returns the plugin panel.
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$xhtml = '<h4>Database queries and their execution time</h4><ol>';

		foreach ($this->_queries as $query) {
			$xhtml .= '<li><strong>[' . round($query['time'] * 1000, 2) . ' ms]</strong> '
				. htmlspecialchars($query['queryString']) . '</li>';
		}

		return $xhtml . '</ol>';
	}

	/**
	 * Returns plugin icon.
	 *
	 * @return string
	 */
	public function getIcon()
	{
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAEYSURBVBgZBcHPio5hGAfg6/2+R980k6wmJgsJ5U/ZOAqbSc2GnXOwUg7BESgLUeIQ1GSjLFnMwsKGGg1qxJRmPM97/1zXFAAAAEADdlfZzr26miup2svnelq7d2aYgt3rebl585wN6+K3I1/9fJe7O/uIePP2SypJkiRJ0vMhr55FLCA3zgIAOK9uQ4MS361ZOSX+OrTvkgINSjS/HIvhjxNNFGgQsbSmabohKDNoUGLohsls6BaiQIMSs2FYmnXdUsygQYmumy3Nhi6igwalDEOJEjPKP7CA2aFNK8Bkyy3fdNCg7r9/fW3jgpVJbDmy5+PB2IYp4MXFelQ7izPrhkPHB+P5/PjhD5gCgCenx+VR/dODEwD+A3T7nqbxwf1HAAAAAElFTkSuQmCC';
	}
}
