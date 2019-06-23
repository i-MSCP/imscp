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

/**
 * Class iMSCP_Validate_File_Plugin
 *
 * Validator for plugin archives
 *
 */
class iMSCP_Plugin_Validate_PluginArchive extends Zend_Validate_Abstract
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
        self::NO_PLUGIN_INFO            => "Plugin info.php file is missing inside the plugin archive.",
        self::NO_PLUGIN_ENTRY_POINT     => "Plugin entry point (class) is missing inside the plugin archive.",
        self::NOT_PLUGIN                => "File '%value%' doesn't look like an i-MSCP plugin archive.",
        self::NOT_READABLE              => "File '%value%' is not readable or does not exist.",
        self::NOT_COMPATIBLE            => "Plugin '%value%' is not compatible with this i-MSCP version.",
        self::NOT_ALLOWED_PROTECTED     => "Plugin '%value%' cannot be updated because it is protected.",
        self::NOT_ALLOWED_PENDING       => "Plugin '%value%' cannot be updated due to pending task.",
        self::INVALID_PLUGIN_INFO_FILE  => "Plugin '%value%' file is invalid.",
        self::INVALID_PLUGIN_INFO_FIELD => "Plugin '%value%' info field is invalid.",
        self::MISSING_PLUGIN_INFO_FIELD => "Plugin '%value%' info field is missing.",
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
     * @return iMSCP_Plugin_Validate_PluginArchive
     */
    public function setOptions(
        array $options
    ): iMSCP_Plugin_Validate_PluginArchive
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
     * @return iMSCP_Plugin_Validate_PluginArchive
     */
    public function setPluginManager(
        iMSCP_Plugin_Manager $pm = NULL
    ): iMSCP_Plugin_Validate_PluginArchive
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

        // Only tar.gz, tar.bz2 and zip archives are accepted
        if (!in_array($file['type'], [
            'application/zip', 'application/x-gzip', 'application/x-bzip2'
        ])) {
            return $this->_throw($file['name'], self::NOT_PLUGIN);
        }

        // Retrieve archive name
        $archName = explode('.', $file['name'])[0];

        if ($file['type'] == 'application/zip') {
            if (!extension_loaded('zip')) {
                throw new Zend_Validate_Exception(sprintf(
                    'Missing %s PHP extension.', 'zip'
                ));
            }

            $arch = new ZipArchive();

            if (true !== $arch->open($value)) {
                throw new Zend_Validate_Exception(sprintf(
                    'Error while opening the %s plugin archive.', $archName
                ));
            }

            $infoAsString = @$arch->getFromName("$archName/info.php");

            if (false === $infoAsString) {
                return $this->_throw($file['name'], self::NO_PLUGIN_INFO);
            }

            if (NULL !== ($info = $this->_isValidPlugin($infoAsString))) {
                $name = $info['name'];

                // Checks that plugin archive contains the <pluginName>.php
                // class (plugin entry point)
                if (false === @$arch->locateName("$archName/$name.php")) {
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

        /** @var string $infoAsString */
        $infoAsString = @$arch->extractInString("$archName/info.php");

        if (false === $infoAsString) {
            return $this->_throw($file['name'], self::NO_PLUGIN_INFO);
        }

        if (NULL !== ($info = $this->_isValidPlugin($infoAsString))) {
            $name = $info['name'];

            // Checks that plugin archive contains the <pluginName>.php
            // class (plugin entry point)
            if (false === @$arch->extractInString("$archName/$name.php")) {
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
     * @return array|null array containing plugin info if the plugin is valid,
     *               NULL otherwise
     */
    protected function _isValidPlugin(string $infoAsString): ?array
    {
        // This is a bit unsafe but that validator is only involved in admin UI
        $info = eval('?>' . $infoAsString);
        if (false === $info || !is_array($info)) {
            $this->_throw('info.php', self::INVALID_PLUGIN_INFO_FILE);
            return NULL;
        }

        // Check for plugin info fields

        // Required fields
        foreach (
            ['name', 'desc', 'version', 'build', 'require_api'] as $field
        ) {
            if (!isset($info[$field])) {
                $this->_throw($field, self::MISSING_PLUGIN_INFO_FIELD);
                return NULL;
            }

            switch ($field) {
                case 'name':
                    if (!is_string($info[$field])
                        || !preg_match('/^[a-z]+$/i', $info[$field])
                    ) {
                        $this->_throw($field, self::INVALID_PLUGIN_INFO_FIELD);
                        return NULL;
                    }
                    break;
                case 'desc':
                    if (!is_string($info[$field]) || $info[$field] === '') {
                        $this->_throw($field, self::INVALID_PLUGIN_INFO_FIELD);
                        return NULL;
                    }
                    break;
                case 'version':
                    if (!is_string($info[$field])
                        || !preg_match('/^\d+\.\d+\.\d+$/', $info[$field])
                    ) {
                        $this->_throw($field, self::INVALID_PLUGIN_INFO_FIELD);
                        return NULL;
                    }
                    break;
                case 'build':
                    if (!(is_string($info[$field])
                            || is_int($info[$field]))
                        || !preg_match('/^\d{10}$/', $info[$field])
                    ) {
                        $this->_throw($field, self::INVALID_PLUGIN_INFO_FIELD);
                        return NULL;
                    }
                    break;
                case 'require_api':
                    if (!is_string($info[$field])
                        || !preg_match('/^\d+\.\d+\.\d+$/', $info[$field])
                    ) {
                        $this->_throw($field, self::INVALID_PLUGIN_INFO_FIELD);
                        return NULL;
                    }
            }
        }

        // Optional fields

        if (isset($info['author'])) {
            if (is_string($info['author']) && $info['author'] === '') {
                $this->_throw('author', self::INVALID_PLUGIN_INFO_FIELD);
                return NULL;
            } elseif (is_array($info['author'])) {
                foreach ($info['author'] as $author) {
                    if (!is_string($author) || $author === '') {
                        $this->_throw('author', self::INVALID_PLUGIN_INFO_FIELD);
                        return NULL;
                    }
                }
            } else {
                $this->_throw('author', self::INVALID_PLUGIN_INFO_FIELD);
                return NULL;
            }

        }

        if (isset($info['email'])
            && !(is_string($info['email'] && $info['email'] !== ''))
        ) {
            $this->_throw('email', self::INVALID_PLUGIN_INFO_FIELD);
            return NULL;
        }

        if (isset($info['url']) &&
            !(is_string($info['url']) && Zend_Uri::check($info['url']))
        ) {
            $this->_throw('url', self::INVALID_PLUGIN_INFO_FIELD);
            return NULL;
        }

        if (isset($info['priority'])
            && (!(is_string($info['priority']) || is_int($info['priority']))
                || !preg_match('/^\d+$/', $info['priority']))
        ) {
            $this->_throw('priority', self::INVALID_PLUGIN_INFO_FIELD);
            return NULL;
        }

        $pm = $this->getPluginManager();

        // Check for plugin compatibility with current plugin API.
        // Check that the plugin is not being downgraded.
        $pm->pluginCheckCompat($info['name'], $info);

        if ($pm->pluginIsKnown($info['name'])) {
            // If the plugin is protected, update is forbidden
            if ($pm->pluginIsProtected($info['name'])) {
                $this->_throw($info['name'], self::NOT_ALLOWED_PROTECTED);
                return NULL;
            }

            // If there is a pending task for the plugin, update is forbidden
            if (!in_array(
                $pm->pluginGetStatus($info['name']),
                ['uninstalled', 'disabled', 'enabled']
            )) {
                $this->_throw($info['name'], self::NOT_ALLOWED_PENDING);
                return NULL;
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
