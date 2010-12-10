<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * Copyright (C) 2010 by internet Multi Server Control Panel - http://i-mscp.net
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 * The Original Code is "i-MSCP - internet Multi Server Control Panel".
 *
 * The Initial Developer of the Original Code is i-MSCP Team.
 * Portions created by Initial Developer are Copyright (C) 2010 by
 * internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category    i-MSCP
 * @copyright   2010 by i-MSCP | http://i-mscp.net
 * @author      Zend Tools
 * @author      i-MSCP Team
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/ GPL v2
 */

// Admin main menu
return array(
    array(
        'label' => 'funk',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'general'
    ),
    array(
        'label' => 'Manage hosting plans',
        'controller' => 'index',
        'action' => 'index',
	    'visible' => 0,
	    'class' => 'hosting_plans'
    ),
    array(
        'label' => 'System tools',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'webtools'
    ),
    array(
        'label' => 'Manage users',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'manage_users'
    ),
    array(
        'label' => 'Statistics',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'general'
    ),
    array(
        'label' => 'Support system',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'support',
    ),
    array(
        'label' => 'Support system',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'general',
    ),
    array(
        'label' => 'Settings',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'settings'
    ),
);