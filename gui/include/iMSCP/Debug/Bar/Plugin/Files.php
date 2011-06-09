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

/** @See iMSCP_Debug_Bar_Plugin */
require_once 'iMSCP/Debug/Bar/Plugin.php';

/**
 * Files plugin for the i-MSCP Debug Bar component.
 *
 * Provide debug information about all included files.
 *
 * @package     iMSCP
 * @package     iMSCP_Debug
 * @subpackage  Bar_Plugin
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @version     0.0.1
 */
class iMSCP_Debug_Bar_Plugin_Files extends iMSCP_Debug_Bar_Plugin
{
    /**
     * Plugin unique identifier.
     *
     * @var string
     */
    const IDENTIFIER = 'Files';

    /**
     * Stores included files
     *
     * @var
     */
    protected $_includedFiles = null;

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
     * Returns list of events that this plugin listens on.
     *
     * @abstract
     * @return array
     */
    public function getListenedEvents()
    {
        return array();
    }

    /**
     * Returns plugin tab.
     *
     * @return string
     */
    public function getTab()
    {
        return count($this->_getIncludedFiles()) . ' ' . $this->getIdentifier();
    }

    /**
     * Returns the plugin panel.
     *
     * @return string
     */
    public function getPanel()
    {
        $included = $this->_getIncludedFiles();
        $xhtml = '<h4>File Information</h4>';
        $xhtml .= count($included) . ' Files Included<br />';
        $size = 0;

        foreach ($included as $file) {
            $size += filesize($file);
        }

        $xhtml .= 'Total Size: ' . round($size / 1024, 1) . 'K<br />';
        $xhtml .= '<h4>Application Files</h4>';

        foreach ($included as $file) {
            $file = str_replace($this->_basePath, '', $file);
            $inUserLib = false;

            if (!$inUserLib) {
                $xhtml .= $file . '<br />';
            }
        }

        return $xhtml;
    }

    /**
     * Returns plugin icon.
     *
     * @return string
     */
    public function getIcon()
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAADPSURBVCjPdZFNCsIwEEZHPYdSz1DaHsMzuPM6RRcewSO4caPQ3sBDKCK02p+08DmZtGkKlQ+GhHm8MBmiFQUU2ng0B7khClTdQqdBiX1Ma1qMgbDlxh0XnJHiit2JNq5HgAo3KEx7BFAM/PMI0CDB2KNvh1gjHZBi8OR448GnAkeNDEDvKZDh2Xl4cBcwtcKXkZdYLJBYwCCFPDRpMEjNyKcDPC4RbXuPiWKkNABPOuNhItegz0pGFkD+y3p0s48DDB43dU7+eLWes3gdn5Y/LD9Y6skuWXcAAAAASUVORK5CYII=';
    }

    /**
     * Returns list of all included files.
     *
     * @return array
     */
    protected function _getIncludedFiles()
    {
        if (null !== $this->_includedFiles) {
            return $this->_includedFiles;
        }

        $this->_includedFiles = get_included_files();
        sort($this->_includedFiles);

        return $this->_includedFiles;
    }
}
