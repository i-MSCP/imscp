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

namespace iMSCP\Plugin;

use iMSCP_Events as Events;
use iMSCP_Events_Manager as BaseEventsManager;
use iMSCP_Events_Manager_Interface as EventsManagerInterface;

/**
 * Class EventsManager
 * @package iMSCP\Plugin
 */
class EventsManager extends BaseEventsManager
{
    /**
     * @var EventsManagerInterface
     */
    protected $eventsManager;

    /**
     * @var array Events triggered by this object
     */
    protected $events = [
        Events::onBeforeUpdatePluginList,
        Events::onAfterUpdatePluginList,
        Events::onBeforeInstallPlugin,
        Events::onAfterInstallPlugin,
        Events::onBeforeUpdatePlugin,
        Events::onAfterUpdatePlugin,
        Events::onBeforeEnablePlugin,
        Events::onAfterEnablePlugin,
        Events::onBeforeDisablePlugin,
        Events::onAfterDisablePlugin,
        Events::onBeforeUninstallPlugin,
        Events::onAfterUninstallPlugin,
        Events::onBeforeDeletePlugin,
        Events::onAfterDeletePlugin,
        Events::onBeforeLockPlugin,
        Events::onAfterLockPlugin,
        Events::onBeforeUnlockPlugin,
        Events::onAfterUnlockPlugin
    ];

    /**
     * EventsManager constructor.
     *
     * @param EventsManagerInterface $em
     */
    public function __construct(EventsManagerInterface $em)
    {
        $this->eventsManager = $em;
    }

    /**
     * @inheritdoc
     */
    public function registerListener($event, $listener, $priority = 1)
    {
        if (is_array($event)) {
            $listeners = [];

            foreach ($event as $name) {
                $listeners[] = $this->registerListener($name, $listener, $priority);
            }

            return $listeners;
        }

        if (in_array($event, $this->events)) {
            return parent::registerListener($event, $listener, $priority);
        }

        return $this->eventsManager->registerListener($event, $listener, $priority);
    }
}
