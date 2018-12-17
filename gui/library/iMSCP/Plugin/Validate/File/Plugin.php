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
 *
 * @noinspection PhpUnhandledExceptionInspection PhpDocMissingThrowsInspection
 */

/**
 * Class iMSCP_Validate_File_Plugin
 *
 * Validate an i-MSCP plugin archive
 *
 */
class iMSCP_Plugin_Validate_File_Plugin extends Zend_Validate_Abstract
{
    /**
     * @const string Error constants
     */
    const NOT_PLUGIN = 'fileNotPlugin';
    const NOT_READABLE = 'fileNotReadable';
    const NOT_COMPATIBLE = 'pluginNotCompatible';
    const NOT_ALLOWED_PROTECTED = 'pluginIsProtected';
    const NOT_ALLOWED_PENDING = 'pluginHasPendingTask';
    const NOT_DOWNGRADABLE = 'pluginNotDowngradable';
    const MISSING_PLUGIN_INFO = 'pluginMissingInfo';
    const INVALID_PLUGIN_INFO = 'invalidPluginInfo';

    /**
     * @var array Error message templates
     */
    protected $_messageTemplates = [
        self::NOT_PLUGIN            => "File '%value%' doesn't look like an i-MSCP plugin archive.",
        self::NOT_READABLE          => "File '%value%' is not readable or does not exist.",
        self::NOT_COMPATIBLE        => "Plugin '%value%' is not compatible with this i-MSCP version.",
        self::NOT_ALLOWED_PROTECTED => "Plugin '%value%' cannot be updated because it is protected.",
        self::NOT_ALLOWED_PENDING   => "Plugin '%value%' cannot be updated due to pending task.",
        self::NOT_DOWNGRADABLE      => "Plugin '%value%' cannot be downgraded.",
        self::MISSING_PLUGIN_INFO   => "Plugin '%value%' info field is missing.",
        self::INVALID_PLUGIN_INFO   => "Plugin '%value%' info field is invalid.",
    ];

    /**
     * @var array Validator options
     */
    protected $_options = [
        'plugin_manager' => NULL,
    ];

    /**
     * iMSCP_Plugin_Validate_File_Plugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } else if (!is_array($options)) {
            $options = func_get_args();
            $temp['plugin_manager'] = array_shift($options);
            $options = $temp;
        }

        $options += $this->_options;
        $this->setOptions($options);
    }

    /**
     * Returns all set options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Sets the options for this validator
     *
     * @param array $options
     * @return iMSCP_Plugin_Validate_File_Plugin
     */
    public function setOptions($options)
    {
        if (array_key_exists('plugin_manager', $options)) {
            $this->setPluginManager($options['plugin_manager']);
        }

        return $this;
    }

    /**
     * Returns the set plugin_manager
     *
     * @return iMSCP_Plugin_Manager
     */
    public function getPluginManager()
    {
        return $this->_options['plugin_manager'];
    }

    /**
     * Set the plugin manager
     *
     * @param iMSCP_Plugin_Manager|NULL $pluginManager
     * @return iMSCP_Plugin_Validate_File_Plugin
     */
    public function setPluginManager(iMSCP_Plugin_Manager $pluginManager = NULL)
    {
        if ($pluginManager === NULL) {
            $pluginManager = new iMSCP_Plugin_Manager();
        }

        $this->_options['plugin_manager'] = $pluginManager;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isValid($value, $file = NULL)
    {
        if (!Zend_Loader::isReadable($value)) {
            return $this->_throw($file, self::NOT_READABLE);
        }

        $name = explode('.', $file['name'])[0];

        if (!in_array($file['type'], ['application/zip', 'application/x-gzip', 'application/x-bzip2'])) {
            return $this->_throw($file, self::NOT_PLUGIN);
        }

        if ($file['type'] === 'application/zip') {
            if (!extension_loaded('zip')) {
                throw new Zend_Validate_Exception('This validator needs the zip and bz2 PHP extensions');
            }

            $arch = new ZipArchive();
            $ret = $arch->open($value);

            if ($ret !== true) {
                throw new Zend_Validate_Exception($this->_zipErrorString($ret));
            }

            if (false == $arch->locateName("$name/$name.php") || false == ($info = $arch->getFromName("$name/info.php"))) {
                return $this->_throw($file, self::NOT_PLUGIN);
            }

            $arch->close();
        } else {
            try {
                Zend_Loader::loadClass('Archive_Tar');
            } catch (Zend_Exception $e) {
                throw new Zend_Validate_Exception('This filter needs PEARs Archive_Tar', 0, $e);
            }

            if (!extension_loaded($file['type'] == 'application/x-gzip' ? 'zlib' : 'bz2')) {
                throw new Zend_Validate_Exception(sprintf(
                    'This validator needs the %s PHP extension', $file['type'] == 'application/x-gzip' ? 'zlib' : 'bz2'
                ));
            }

            $arch = new Archive_Tar($value);
            if (false === $arch->extractInString("$name/$name.php") || false == ($info = $arch->extractInString("$name/info.php"))) {
                return $this->_throw($file, self::NOT_PLUGIN);
            }
        }

        return $this->_checkInfo(eval('?>' . $info), $name);
    }

    /**
     * Check plugin info file
     * @param array $info
     * @param string $name
     * @return bool|false
     */
    protected function _checkInfo($info, $name)
    {
        if (!is_array($info)) {
            return $this->_throw(['name' => $name], self::NOT_PLUGIN);
        }

        $pm = $this->getPluginManager();

        foreach (['name', 'desc', 'version', 'build', 'require_api'] as $key) {
            if (!isset($info[$key])) {
                return $this->_throw(['name' => $key], self::MISSING_PLUGIN_INFO);
            }

            switch ($key) {
                case 'name':
                    if (!is_string($info[$key]) || $info['name'] !== $name) {
                        return $this->_throw(['name' => 'name'], self::INVALID_PLUGIN_INFO);
                    }
                    break;
                case 'version':
                    if (!is_string($info[$key]) || !preg_match('/\d+\.\d+\.\d+/', $info['version'])) {
                        return $this->_throw(['name' => 'version'], self::INVALID_PLUGIN_INFO);
                    }
                    break;
                case 'build':
                    if (!is_string($info[$key]) || !preg_match('/^\d{10}$/', $info['build'])) {
                        return $this->_throw(['name' => 'build'], self::INVALID_PLUGIN_INFO);
                    }
                    break;
                case 'require_api':
                    if (!is_string($info[$key]) || !preg_match('/\d+\.\d+\.\d+/', $info['require_api'])) {
                        return $this->_throw(['name' => 'require_api'], self::INVALID_PLUGIN_INFO);
                    }

                    if (version_compare($info['require_api'], $pm->pluginGetApiVersion(), '>')) {
                        return $this->_throw(['name' => 'require_api'], self::NOT_COMPATIBLE);
                    }
            }
        }

        if ($pm->pluginIsKnown($name)) {
            $pluginInfo = $pm->pluginGetInfo($info['name']);
            if (version_compare($info['version'] . '.' . $info['build'], $pluginInfo['version'] . '.' . $pluginInfo['build'], '<')) {
                return $this->_throw(['name' => $name], self::NOT_DOWNGRADABLE);
            }

            if ($pm->pluginIsProtected($info['name'])) {
                return $this->_throw(['name' => $name], self::NOT_ALLOWED_PROTECTED);
            }

            if (!in_array($pm->pluginGetStatus($info['name']), ['uninstalled', 'disabled', 'enabled'])) {
                return $this->_throw(['name' => $name], self::NOT_ALLOWED_PENDING);
            }
        }

        return true;
    }

    /**
     * Throws an error of the given type
     *
     * @param  array $file
     * @param  string $errorType
     * @return false
     */
    protected function _throw($file, $errorType)
    {
        if ($file !== NULL) {
            $this->_value = $file['name'];
        }

        $this->_error($errorType);
        return false;
    }

    /**
     * Returns the proper string based on the given error constant
     *
     * @param string $error
     * @return string
     */
    protected function _zipErrorString($error)
    {
        switch ($error) {
            case ZipArchive::ER_MULTIDISK :
                return 'Multidisk ZIP Archives not supported';
            case ZipArchive::ER_RENAME :
                return 'Failed to rename the temporary file for ZIP';
            case ZipArchive::ER_CLOSE :
                return 'Failed to close the ZIP Archive';
            case ZipArchive::ER_SEEK :
                return 'Failure while seeking the ZIP Archive';
            case ZipArchive::ER_READ :
                return 'Failure while reading the ZIP Archive';
            case ZipArchive::ER_WRITE :
                return 'Failure while writing the ZIP Archive';
            case ZipArchive::ER_CRC :
                return 'CRC failure within the ZIP Archive';
            case ZipArchive::ER_ZIPCLOSED :
                return 'ZIP Archive already closed';
            case ZipArchive::ER_NOENT :
                return 'No such file within the ZIP Archive';
            case ZipArchive::ER_EXISTS :
                return 'ZIP Archive already exists';
            case ZipArchive::ER_OPEN :
                return 'Can not open ZIP Archive';
            case ZipArchive::ER_TMPOPEN :
                return 'Failure creating temporary ZIP Archive';
            case ZipArchive::ER_ZLIB :
                return 'ZLib Problem';
            case ZipArchive::ER_MEMORY :
                return 'Memory allocation problem while working on a ZIP Archive';
            case ZipArchive::ER_CHANGED :
                return 'ZIP Entry has been changed';
            case ZipArchive::ER_COMPNOTSUPP :
                return 'Compression method not supported within ZLib';
            case ZipArchive::ER_EOF :
                return 'Premature EOF within ZIP Archive';
            case ZipArchive::ER_INVAL :
                return 'Invalid argument for ZLIB';
            case ZipArchive::ER_NOZIP :
                return 'Given file is no zip archive';
            case ZipArchive::ER_INTERNAL :
                return 'Internal error while working on a ZIP Archive';
            case ZipArchive::ER_INCONS :
                return 'Inconsistent ZIP archive';
            case ZipArchive::ER_REMOVE :
                return 'Can not remove ZIP Archive';
            case ZipArchive::ER_DELETED :
                return 'ZIP Entry has been deleted';
            default :
                return 'Unknown error within ZIP Archive';
        }
    }
}
