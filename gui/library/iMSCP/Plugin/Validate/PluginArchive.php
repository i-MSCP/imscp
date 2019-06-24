<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

declare(strict_types=1);

namespace iMSCP\Plugin\Validate;

use Archive_Tar;
use iMSCP_Plugin_Manager;
use ParseError;
use Zend_Config;
use Zend_Exception;
use Zend_Loader;
use Zend_Uri;
use Zend_Validate_Abstract;
use Zend_Validate_EmailAddress;
use Zend_Validate_Exception;
use ZipArchive;

/**
 * Class PluginArchive
 *
 * Validator that validate a plugin archive
 *
 * @package iMSCP\Plugin\Validate
 */
class PluginArchive extends Zend_Validate_Abstract
{
    /**
     * @const string Error constants
     */
    const NO_PLUGIN_INFO = 'pluginInfoMissing';
    const NO_PLUGIN_ENTRY_POINT = 'pluginEntryPointMissing';
    const NOT_PLUGIN = 'fileNotPlugin';
    const NOT_READABLE = 'fileNotReadable';
    const NOT_COMPATIBLE = 'pluginNotCompatible';
    const NOT_ALLOWED_PROTECTED = 'pluginIsProtected';
    const NOT_ALLOWED_PENDING = 'pluginHasPendingTask';
    const INVALID_PLUGIN_INFO_FILE = 'invalidPluginInfoFile';
    const INVALID_PLUGIN_INFO_FIELD = 'invalidPluginInfo';
    const MISSING_PLUGIN_INFO_FIELD = 'pluginMissingInfoField';

    /**
     * @var array Error message templates
     */
    protected $_messageTemplates = [
        self::NO_PLUGIN_INFO            => "Invalid plugin archive: The plugin info.php file is missing inside the plugin archive.",
        self::NO_PLUGIN_ENTRY_POINT     => "Invalid plugin archive: The plugin entry point (class) is missing inside the plugin archive.",
        self::NOT_PLUGIN                => "Invalid plugin archive: The '%value%' file doesn't look like an i-MSCP plugin archive.",
        self::NOT_READABLE              => "The '%value%' file is not readable or does not exist.",
        self::NOT_COMPATIBLE            => "Plugin '%value%' is not compatible with this i-MSCP version.",
        self::NOT_ALLOWED_PROTECTED     => "The '%value%' plugin cannot be updated because it is protected.",
        self::NOT_ALLOWED_PENDING       => "The '%value%' plugin cannot be updated due to pending task.",
        self::INVALID_PLUGIN_INFO_FILE  => "Invalid plugin archive: The %value%' plugin info file is not valid.",
        self::INVALID_PLUGIN_INFO_FIELD => "Invalid plugin archive: The plugin '%value%' info field is not valid.",
        self::MISSING_PLUGIN_INFO_FIELD => "Invalid plugin archive: The '%value%' plugin info field is missing.",
    ];

    /**
     * @var array Validator options
     */
    protected $_options = [
        'plugin_manager' => NULL
    ];

    /**
     * PluginArchive constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
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
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     * Sets the options for this validator
     *
     * @param array $options
     * @return PluginArchive
     */
    public function setOptions(
        array $options
    ): PluginArchive
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
    public function getPluginManager(): iMSCP_Plugin_Manager
    {
        return $this->_options['plugin_manager'];
    }

    /**
     * Set the plugin manager
     *
     * @param iMSCP_Plugin_Manager|NULL $pm
     * @return PluginArchive
     */
    public function setPluginManager(
        iMSCP_Plugin_Manager $pm = NULL
    ): PluginArchive
    {
        if ($pm === NULL) {
            $pm = new iMSCP_Plugin_Manager();
        }

        $this->_options['plugin_manager'] = $pm;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isValid($value, $file = NULL): bool
    {
        if (!Zend_Loader::isReadable($value)) {
            return $this->_throw($file['name'], self::NOT_READABLE);
        }

        // Only tar.gz, tar.bz2 and zip archives are supported
        if (!in_array($file['type'], [
            'application/zip', 'application/x-gzip', 'application/x-bzip2'
        ])) {
            return $this->_throw($file['name'], self::NOT_PLUGIN);
        }

        if ($file['type'] == 'application/zip') {
            return $this->_isValidZipArchive($value, $file);
        }

        return $this->_isValidTarArchive($value, $file);
    }

    /**
     * Internal method to validate a plugin Zip Archive
     *
     * @param string $value
     * @param array $file
     * @return bool TRUE if the Zip archive is valid, FALSE otherwise
     */
    protected function _isValidZipArchive($value, $file)
    {
        if (!extension_loaded('zip')) {
            throw new Zend_Validate_Exception(sprintf(
                'Missing %s PHP extension.', 'zip'
            ));
        }

        $arch = new ZipArchive();
        $name = explode('.', $file['name'])[0];

        if (true !== $arch->open($value)) {
            throw new Zend_Validate_Exception(sprintf(
                'Error while opening the %s plugin archive.', $name
            ));
        }

        $infoAsString = @$arch->getFromName("$name/info.php");

        if (false === $infoAsString) {
            return $this->_throw($file['name'], self::NO_PLUGIN_INFO);
        }

        $info = $this->_isValidPlugin($infoAsString);
        if (false !== $info) {
            $entryPointFile = $info['name'] . '.php';

            if (false === @$arch->locateName("$name/$entryPointFile")) {
                return $this->_throw(
                    $file['name'], self::NO_PLUGIN_ENTRY_POINT
                );
            }

            $arch->close();
            return true;
        }

        $arch->close();
        return false;
    }

    /**
     * Internal method to validate a Tar Archive
     *
     * @param string $value
     * @param array $file
     * @return bool TRUE if the Zip archive is valid, FALSE otherwise
     */
    protected function _isValidTarArchive($value, $file)
    {
        try {
            Zend_Loader::loadClass('Archive_Tar');
        } catch (Zend_Exception $e) {
            throw new Zend_Validate_Exception(tr('Missing PEAR Archive_Tar.'));
        }

        if (!extension_loaded(
            $file['type'] == 'application/x-gzip' ? 'zlib' : 'bz2'
        )) {
            throw new Zend_Validate_Exception(sprintf(
                'Missing %s PHP extension.',
                $file['type'] == 'application/x-gzip' ? 'zlib' : 'bz2'
            ));
        }

        $arch = new Archive_Tar(
            $value, $file['type'] == 'application/x-gzip' ? 'gz' : 'bz2'
        );
        $name = explode('.', $file['name'])[0];

        /** @var string $infoAsString */
        $infoAsString = @$arch->extractInString("$name/info.php");

        if (false === $infoAsString) {
            return $this->_throw($file['name'], self::NO_PLUGIN_INFO);
        }

        $info = $this->_isValidPlugin($infoAsString);
        if (false !== $info) {
            $entryPointFile = $info['name'] . '.php';

            if (false === @$arch->extractInString("$name/$entryPointFile")) {
                return $this->_throw(
                    $file['name'], self::NO_PLUGIN_ENTRY_POINT
                );
            }

            return true;
        }

        return false;
    }

    /**
     * Internal method to check plugin validity
     *
     * @param string $infoAsString Plugin info as string
     * @return array|false array containing plugin info if the plugin archive is
     *                    valid, NULL otherwise
     */
    protected function _isValidPlugin(string $infoAsString)
    {
        $info = (static function (string $infoAsString) {
            try {
                $info = eval('?>' . $infoAsString);
            } catch (ParseError $e) {
                $info = NULL;
            }

            return $info;
        })($infoAsString);

        if (NULL === $info || !is_array($info)) {
            return $this->_throw('info.php', self::INVALID_PLUGIN_INFO_FILE);
        }

        // Check for plugin info fields

        // Required fields
        foreach (
            ['name', 'desc', 'version', 'build', 'require_api'] as $field
        ) {
            if (!isset($info[$field])) {
                return $this->_throw($field, self::MISSING_PLUGIN_INFO_FIELD);
            }

            switch ($field) {
                case 'name':
                    if (!is_string($info[$field])
                        || !preg_match('/^[a-z]+$/i', $info[$field])
                    ) {
                        return $this->_throw(
                            $field, self::INVALID_PLUGIN_INFO_FIELD
                        );
                    }
                    break;
                case 'desc':
                    if (!is_string($info[$field]) || $info[$field] === '') {
                        return $this->_throw(
                            $field, self::INVALID_PLUGIN_INFO_FIELD
                        );
                    }
                    break;
                case 'version':
                    if (!is_string($info[$field])
                        || !preg_match('/^\d+\.\d+\.\d+$/', $info[$field])
                    ) {
                        return $this->_throw(
                            $field, self::INVALID_PLUGIN_INFO_FIELD
                        );
                    }
                    break;
                case 'build':
                    if (!(is_string($info[$field])
                            || is_int($info[$field]))
                        || !preg_match('/^\d{10}$/', $info[$field])
                    ) {
                        return $this->_throw(
                            $field, self::INVALID_PLUGIN_INFO_FIELD
                        );
                    }
                    break;
                case 'require_api':
                    if (!is_string($info[$field])
                        || !preg_match('/^\d+\.\d+\.\d+$/', $info[$field])
                    ) {
                        return $this->_throw(
                            $field, self::INVALID_PLUGIN_INFO_FIELD
                        );
                    }
            }
        }

        // Optional fields

        if (isset($info['author'])) {
            foreach ((array)$info['author'] as $author) {
                if (!is_string($author) || $author === '') {
                    return $this->_throw(
                        'author', self::INVALID_PLUGIN_INFO_FIELD
                    );
                }
            }
        }

        if (isset($info['email'])
            && !(is_string($info['email'])
                || !(new Zend_Validate_EmailAddress)->isValid($info['email'])
            )
        ) {
            return $this->_throw('email', self::INVALID_PLUGIN_INFO_FIELD);
        }

        if (isset($info['url']) &&
            !(is_string($info['url']) && Zend_Uri::check($info['url']))
        ) {
            return $this->_throw('url', self::INVALID_PLUGIN_INFO_FIELD);
        }

        if (isset($info['priority'])
            && (!(is_string($info['priority']) || is_int($info['priority']))
                || !preg_match('/^\d+$/', $info['priority']))
        ) {
            return $this->_throw('priority', self::INVALID_PLUGIN_INFO_FIELD);
        }

        $pm = $this->getPluginManager();

        // Check for plugin compatibility with current plugin API.
        // Check that the plugin is not being downgraded.
        $pm->pluginCheckCompat($info['name'], $info);

        if ($pm->pluginIsKnown($info['name'])) {
            // If the plugin is protected, update is forbidden
            if ($pm->pluginIsProtected($info['name'])) {
                return $this->_throw(
                    $info['name'], self::NOT_ALLOWED_PROTECTED
                );
            }

            // If there is a pending task for the plugin, update is forbidden
            if (!in_array(
                $pm->pluginGetStatus($info['name']),
                ['uninstalled', 'disabled', 'enabled']
            )) {
                return $this->_throw($info['name'], self::NOT_ALLOWED_PENDING);
            }
        }

        return $info;
    }

    /**
     * Internal method to throws an error of the given type
     *
     * @param string $value
     * @param string $errorType
     * @return false
     */
    protected function _throw(string $value, string $errorType): bool
    {
        $this->_value = (string)$value;
        $this->_error($errorType);
        return false;
    }
}
