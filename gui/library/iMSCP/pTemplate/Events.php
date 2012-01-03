<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2012 by i-MSCP team
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
 * @package     iMSCP
 * @package     iMSCP_pTemplates
 * @subpackage  Events
 * @copyright   2010-2012 by i-MSCP team
 * @author      Laurent Declercq <ldeclercq@l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Contains all events thrown in the pTemplate component
 *
 * @package     iMSCP
 * @package     iMSCP_pTemplates
 * @subpackage  Events
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
final class iMSCP_pTemplate_Events
{
	/**
	 * The onBeforeAssembleTemplateFiles event is fired before the first parent template is loaded.
	 *
	 * The event listeners methods receives the path of the template that will be loaded
	 */
	const onBeforeAssembleTemplateFiles = 'onBeforeAssembleTemplateFiles';

	/**
	 * The onAfterAssembleTemplateFiles event is fired after the first parent template is loaded.
	 *
	 * The event listener method receives the template content as a string
	 */
	const onAfterAssembleTemplateFiles = 'onBeforeAssembleTemplateFiles';

	/**
	 * The onBeforeLoadTemplateFile event is fired before a template is loaded.
	 *
	 * The event listeners methods receives the path of the template that will be loaded
	 */
	const onBeforeLoadTemplateFile = 'onBeforeLoadTemplateFile';

	/**
	 * The onAfterLoadTemplateFile event is fired after the loading of a template file.
	 *
	 * The event listener method receives the template content as a string
	 */
	const onAfterLoadTemplateFile = 'onAfterLoadTemplateFile';
}
