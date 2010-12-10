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

// Client main menu
return array(
    array(
        'label' => 'General Information',
	    'module' => 'client',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'general'
    ),
    array(
        'label' => 'Manage Domains',
	    'module' => 'client',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'domains',
	    'visible' => 1
    ),
    array(
        'label' => 'Email Accounts',
        'controller' => 'index',
	    'module' => 'client',
        'action' => 'index',
	    'class' => 'email',
	    'visible' => 1
    ),
    array(
        'label' => 'Ftp Accounts',
	    'module' => 'client',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'ftp',
	    'visible' => 1
    ),
    array(
        'label' => 'Databases',
	    'module' => 'client',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'database',
	    'visible' => 1
    ),
    array(
        'label' => 'Webtools',
	    'module' => 'client',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'webtools',
	    'visible' => 1
    ),
    array(
        'label' => 'Statistics',
	    'module' => 'client',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'statistics',
	    'visible' => 1
    ),
    array(
        'label' => 'Support System',
	    'module' => 'client',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'support',
	    'visible' => 1
    ),
    array(
        'label' => 'Custom Menus',
	    'module' => 'client',
        'controller' => 'index',
        'action' => 'index',
	    'class' => 'custom_link',
	    'visible' => 1
    )
);