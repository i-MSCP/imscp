<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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
 */

namespace iMSCP\Database;

use iMSCP\Database\Events\Statement as StatementEvent;
use iMSCP_Events as Events;
use iMSCP_Events_Manager_Interface as EventsManagerInterface;
use PDO;
use PDOStatement;

/**
 * Class ResultSet
 * @package iMSCP\Database
 */
class ResultSet extends PDOStatement
{
    /**
     * @var EventsManagerInterface
     */
    protected $em;

    /**
     * @var StatementEvent
     */
    protected $event;

    /**
     * ResultSet constructor.
     *
     * @param EventsManagerInterface $em
     */
    protected function __construct(EventsManagerInterface $em)
    {
        $this->em = $em;
        $this->event = new StatementEvent($this->queryString);
    }

    /**
     * @inheritdoc
     */
    public function execute($parameters = NULL)
    {
        // BC reasons -- Will be removed in a later release
        if (NULL !== $parameters) {
            $parameters = (array)$parameters;
        }

        $this->event->setName(Events::onBeforeQueryExecute);
        $this->em->dispatch($this->event);
        $ret = parent::execute($parameters);
        $this->event->setName(Events::onAfterQueryExecute);
        $this->em->dispatch($this->event);
        return $ret;
    }

    /**
     * Fetches the next row from a result set
     *
     * @see PDOStatement::fetch()
     * @param null $fetchStyle
     * @param int $cursorOriantation
     * @param int $cursorOffset
     * @return mixed
     * @deprecated Will be removed in a later version
     */
    public function fetchRow($fetchStyle = NULL, $cursorOriantation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        return $this->fetch($fetchStyle, $cursorOriantation, $cursorOffset);
    }
}
