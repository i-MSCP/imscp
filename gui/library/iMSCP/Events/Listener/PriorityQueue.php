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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Events_Listener
 * @copyright   2010-2014 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@i-mscp.net>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/** @see iMSCP_Events_Listener_SplPriorityQueue */
require_once 'iMSCP/Events/Listener/SplPriorityQueue.php';

/** @see iMSCP_Events_Manager_Interface */
require_once 'iMSCP/Events/Listener.php';

/**
 * Class iMSCP_Listener_PriorityQueue
 */
class iMSCP_Listener_PriorityQueue implements Countable, IteratorAggregate
{
	/**
	 * Actual items aggregated in the priority queue. Each item is an array with keys "listener" and "priority"
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * @var iMSCP_Events_Listener_SplPriorityQueue Inner queue object
	 */
	protected $queue;

	/**
	 * Constructor
	 *
	 * @return iMSCP_Listener_PriorityQueue
	 */
	public function __construct()
	{
		$this->queue = new iMSCP_Events_Listener_SplPriorityQueue();
	}

	/**
	 * Add the given listener into the queue
	 *
	 * Priority defaults to 1 (low priority) if none provided.
	 *
	 * @param iMSCP_Listener $listener Listener
	 * @param int $priority Listener priority
	 * @return iMSCP_Listener_PriorityQueue
	 */
	public function addListener(iMSCP_Listener $listener, $priority = 1)
	{
		$priority = (int) $priority;
		$this->items[] = array('listener' => $listener, 'priority' => $priority);
		$this->queue->insert($listener, $priority);

		return $this;
	}

	/**
	 * Remove the given listener from the queue
	 *
	 * Note: This removes the first listener matching the provided listener found. If the same listener item has been
	 * added multiple times, it will not remove other instances.
	 *
	 * @param iMSCP_Listener $listener Listener to remove from the queue
	 * @return bool FALSE if the item was not found, TRUE otherwise.
	 */
	public function removeListener(iMSCP_Listener $listener)
	{
		$key = false;

		foreach ($this->items as $key => $item) {
			if ($item['listener'] === $listener) {
				break;
			}
		}

		if ($key) {
			unset($this->items[$key]);
				$this->queue = new iMSCP_Events_Listener_SplPriorityQueue();

				foreach ($this->items as $item) {
					$this->queue->insert($item['listener'], $item['priority']);
				}

			return true;
		}

		return false;
	}

	/**
	 * Is the queue empty?
	 *
	 * @return bool TRUE if the queue is empty, FALSE otherwise
	 */
	public function isEmpty()
	{
		return (0 === $this->count());
	}

	/**
	 * How many items are in the queue?
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->items);
	}

	/**
	 * Retrieve the inner iterator
	 *
	 * SplPriorityQueue acts as a heap, which typically implies that as items are iterated, they are also removed. This
	 * method retrieves the inner queue object, and clones it for purposes of iteration.
	 *
	 * @return iMSCP_Events_Listener_SplPriorityQueue
	 */
	public function getIterator()
	{
		return clone $this->queue;
	}

	/**
	 * Does the queue have a listener with the given priority?
	 *
	 * @param  int $priority
	 * @return bool
	 */
	public function hasPriority($priority)
	{
		foreach ($this->items as $item) {
			if ($item['priority'] === $priority) {
				return true;
			}
		}

		return false;
	}
}
