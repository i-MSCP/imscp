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
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Events_Manager
 * @copyright	2010-2012 by i-MSCP team
 * @author		Laurent Declercq <l.declercq@i-mscp.net>
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license		http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * Allows to modify the response for a request.
 *
 * Formally, this event doesn't represent a reponse but that will change in future.
 * For now, this event allow to operate some modifications on the template generated
 * by the iMSCP_pTemplate template engine. A listener method that receives this event
 * can doing some changes on the generated template before the final result is sent
 * to the client browser.
 *
 * @category	iMSCP
 * @package		iMSCP_Core
 * @subpackage	Events_Manager
 * @author		Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version		0.0.1
 */
class iMSCP_Events_Response extends iMSCP_Events_Event
{
	/**
	 * @var iMSCP_pTemplate
	 */
	protected $_templateEngine;

	/**
	 * Constructor.
	 *
	 * @param iMSCP_pTemplate $templateEngine Template engine
	 */
	public function __construct(iMSCP_pTemplate $templateEngine)
	{
		$this->_templateEngine = $templateEngine;
	}

	/**
	 * Returns iMSCP_pTemplate instance.
	 *
	 * @return iMSCP_pTemplate
	 */
	public function getTemplateEngine()
	{
		return $this->_templateEngine;
	}
}
