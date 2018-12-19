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
        self::MISSING_PLUGIN_INFO   => "Plugin '%value%' info field is missing.",
        self::INVALID_PLUGIN_INFO   => "Plugin '%value%' info field is invalid.",
    ];

    /**
     * @var array Validator options
     */
    protected $_options = [
        'plugin_manager' => NULL
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
        } elseif (!is_array($options)) {
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
            return $this->_throw($file['name'], self::NOT_READABLE);
        }

        if (!in_array($file['type'], ['application/zip', 'application/x-gzip', 'application/x-bzip2'])) {
            return $this->_throw($file['name'], self::NOT_PLUGIN);
        }

        $name = explode('.', $file['name'])[0];

        if ($file['type'] == 'application/zip') {
            if (!extension_loaded('zip')) {
                throw new Zend_Validate_Exception(tr('Missing %s PHP extension.', 'zip'));
            }

            $arch = new ZipArchive();

            if (true !== $arch->open($value)) {
                throw new Zend_Validate_Exception(tr('Error while opening the %s plugin archive.', $name));
            }

            if (false === @$arch->locateName("$name/$name.php") || false == ($info = @$arch->getFromName("$name/info.php"))) {
                return $this->_throw($file['name'], self::NOT_PLUGIN);
            }

            $arch->close();
        } else {
            try {
                Zend_Loader::loadClass('Archive_Tar');
            } catch (Zend_Exception $e) {
                throw new Zend_Validate_Exception(tr('Missing PEAR Archive_Tar.'));
            }

            if (!extension_loaded($file['type'] == 'application/x-gzip' ? 'zlib' : 'bz2')) {
                throw new Zend_Validate_Exception(tr('Missing %s PHP extension.', $file['type'] == 'application/x-gzip' ? 'zlib' : 'bz2'));
            }

            $arch = new Archive_Tar($value, $file['type'] == 'application/x-gzip' ? 'gz' : 'bz2');
            if (false === $arch->extractInString("$name/$name.php") || false == ($info = $arch->extractInString("$name/info.php"))) {
                return $this->_throw($file['name'], self::NOT_PLUGIN);
            }
        }

        return $this->_checkPlugin($name, eval('?>' . $info));
    }

    /**
     * Internal method to check plugin
     *
     * @param string $pluginName Plugin name
     * @param array $info Plugin info
     * @return boolean
     */
    protected function _checkPlugin($pluginName, $info)
    {
        if (!is_array($info)) {
            return $this->_throw($pluginName, self::NOT_PLUGIN);
        }

        $pm = $this->getPluginManager();

        foreach (['name', 'desc', 'version', 'build', 'require_api'] as $field) {
            if (!isset($info[$field])) {
                return $this->_throw($field, self::MISSING_PLUGIN_INFO);
            }

            switch ($field) {
                case 'name':
                    if (!is_string($info[$field]) || $info[$field] !== $pluginName) {
                        return $this->_throw($field, self::INVALID_PLUGIN_INFO);
                    }
                    break;
                case 'desc':
                    if (!is_string($info[$field])) {
                        return $this->_throw($field, self::INVALID_PLUGIN_INFO);
                    }
                    break;
                case 'version':
                    if (!is_string($info[$field]) || !preg_match('/^\d+\.\d+\.\d+$/', $info[$field])) {
                        return $this->_throw($field, self::INVALID_PLUGIN_INFO);
                    }
                    break;
                case 'build':
                    if (!is_string($info[$field]) || !preg_match('/^\d{10}$/', $info[$field])) {
                        return $this->_throw($field, self::INVALID_PLUGIN_INFO);
                    }
                    break;
                case 'require_api':
                    if (!is_string($info[$field]) || !preg_match('/^\d+\.\d+\.\d+$/', $info[$field])) {
                        return $this->_throw($field, self::INVALID_PLUGIN_INFO);
                    }
            }
        }

        $pm->pluginCheckCompat($pluginName, $info);

        if (!$pm->pluginIsKnown($pluginName)) {
            return true;
        }

        if ($pm->pluginIsProtected($info['name'])) {
            return $this->_throw($pluginName, self::NOT_ALLOWED_PROTECTED);
        }

        if (!in_array($pm->pluginGetStatus($info['name']), ['uninstalled', 'disabled', 'enabled'])) {
            return $this->_throw($pluginName, self::NOT_ALLOWED_PENDING);
        }

        return true;
    }

    /**
     * Internal method to throws an error of the given type
     *
     * @param  string $value
     * @param  string $errorType
     * @return false
     */
    protected function _throw($value, $errorType)
    {
        $this->_value = (string)$value;
        $this->_error($errorType);
        return false;
    }
}
