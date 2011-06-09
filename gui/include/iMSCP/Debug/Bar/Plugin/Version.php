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
                 '<p>©2010-2011 <a href="http://www.i-mscp.net">i-MSCP Team</a></p>' .
                 'Includes images from the <a href="http://www.famfamfam.com/lab/icons/silk/">Silk Icon set</a> by Mark James</p>';
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
        return 'data:image/gif;base64,R0lGODlhEAAQAPcAAPb7/ef2+VepAGKzAIC8SavSiYS9Stvt0uTx4fX6+ur1632+QMLgrGOuApDIZO738drs0Ofz5t7v2MfjtPP6+t7v12SzAcvnyX2+PaPRhH2+Qmy3H3K5LPP6+cXkwIHAR2+4JHi7NePz8YC/Rc3ozfH49XK5KXq9OrzdpNzu1YrEUqrVkdzw5uTw4d/v2dDow5zOeO3279Hq0m+4JqrUhpnMbeHw3N3w6Mflwm22HmazBODy7tfu3un06r7gsuXy4sTisIzGXvH59ny9PdPr1rXZpMzlu36/Q5bLb+Pw3tDnxNHr1Lfbm+b199/x62q1Fp3NcdjszqTPh/L599vt04/GWmazCPb7/LHZnW63I3W6MXa7MmGuAt/y7Gq1E2m0Eb7cp9frzZLJaO/489bu3HW3N7rerN/v2q7WjIjEVuLx343FVrDXj9nt0cTjvW2zIoPBSNjv4OT09IXDUpvLeeHw3dPqyNLpxs/nwHe8OIvFWrPaoGe0C5zMb83mvHm8Oen06a3Xl9XqyoC/Qr/htWe0DofDU4nFWbPYk7ndqZ/PfYPBTMPhrqHRgoLBSujz55PKadHpxfX6+6LNeqPQfNXt2pPIYH2+O7vcoHi4OOf2+PL5+NTs2N3u1mi1E7XZl4zEVJjLaZHGauby5KTShmSzBO/38s/oz3i7MtbrzMHiuYTCT4fDTtXqye327uDv3JDHXu328JnMcu738LLanvD49ZTJYpPKauX19tvv44jBWo7GWpfKZ+Dv27XcpcrluXu8ONTs16zXleT08qfUjKzUlc7pzm63HaTRfZXKZuj06HG4KavViGe0EcDfqcjmxaDQgZrNdOHz77/ep4/HYL3esnW6LobCS3S5K57OctDp0JXKbez17N7x6cbkwLTZlbXXmLrcnrvdodHr06PQe8jkt5jIa93v13m8OI7CW3O6L3a7Nb7gs6nUjmu2GqjTgZjKaKLQeZnMc4LAReL08rTbopbLbuTx4KDOdtbry7DYmrvfrrPaoXK5K5zOegAAACH5BAEAAAAALAAAAAAQABAAAAhMAAEIHEiwoMGDCBMOlCKgoUMuHghInEiggEOHAC5eJNhQ4UAuAjwIJLCR4AEBDQS2uHiAYLGOHjNqlCmgYAONApQ0jBGzp8+fQH8GBAA7';
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
