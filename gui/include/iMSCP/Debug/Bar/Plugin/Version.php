<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP Team.
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
 * @package     iMSCP_Debug
 * @subpackage  Bar_Plugin
 * @copyright   2010-2011 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/**
 * Version plugin for the i-MSCP Debug Bar component.
 *
 * Provides version information about i-MSCP and also information about all PHP
 * extensions loaded.
 *
 * @package     iMSCP
 * @package     iMSCP_Debug
 * @subpackage  Bar_Plugin
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class iMSCP_Debug_Bar_Plugin_Version extends iMSCP_Debug_Bar_Plugin
{
    /**
     * Plugin unique identifier.
     *
     * @var string
     */
    const IDENTIFIER = 'Version';

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
     * Gets menu tab for the Debugbar.
     *
     * @return string
     */
    public function getTab()
    {
        $version = iMSCP_Registry::get('config')->Version;
        return ' ' . $version . '/'.phpversion();
    }

    /**
     * Gets content panel for the Debugbar.
     *
     * @return string
     */
    public function getPanel()
    {
        $version = iMSCP_Registry::get('config')->Version;
        $panel = '<h4>i-MSCP DebugBar v0.0.1</h4>' .
                 '<p>©2010-2011 <a href="http://www.i-mscp.net">i-MSCP Team</a><br />' .
                 'Includes images from the <a href="http://www.famfamfam.com/lab/icons/silk/">Silk Icon set</a> by Mark James<br />
                 Based upon project hosted at <a href="http://code.google.com/p/zfdebug">ZFDebug</a></p>';
        $panel .= '<h4>i-MSCP '.$version.' / PHP '.phpversion().' with extensions:</h4>';
        $extensions = get_loaded_extensions();
        natcasesort($extensions);
        $panel .= implode('<br>', $extensions);
        return $panel;
    }

    /**
     * Returns plugin icon.
     *
     * @return string
     */
    public function getIcon()
    {
        return '/themes/default/images/icons/copyright.png';
    }

    /**
     * Returns list of events that the plugin listens on.
     *
     * @return array
     */
    public function getListenedEvents()
    {
        return array();
    }
}
