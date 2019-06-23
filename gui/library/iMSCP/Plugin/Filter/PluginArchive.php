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
 * Class PluginFile
 *
 * Filter that extract uploaded plugin archive into plugins directory
 *
 * @package iMSCP\Plugin\Filter
 */
class iMSCP_Plugin_Filter_PluginArchive implements Zend_Filter_Interface
{
    /**
     * @var array Validator options
     */
    protected $_options = [
        'destination' => NULL,
        'magic_file'  => NULL
    ];

    /**
     * iMSCP_Plugin_Validate_File_Plugin constructor.
     *
     * @param array $options
     * @return void
     */
    public function __construct(array $options = [])
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } elseif (!is_array($options)) {
            $options = func_get_args();
            $temp['destination'] = array_shift($options);

            if (!empty($options)) {
                $temp['magic_file'] = array_shift($options);
            }

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
     * Sets the options for this filter
     *
     * @param array $options
     * @return iMSCP_Plugin_Filter_PluginArchive
     */
    public function setOptions(
        array $options
    ): iMSCP_Plugin_Filter_PluginArchive
    {
        if (array_key_exists('destination', $options)) {
            $this->setDestination($options['destination']);
        }

        if (array_key_exists('magic_file', $options)) {
            $this->setDestination($options['magic_file']);
        }

        return $this;
    }

    /**
     * Returns the 'destination' option
     *
     * @return string
     */
    public function getDestination(): string
    {
        return $this->_options['destination'];
    }

    /**
     * Set the 'destination' option
     *
     * @param string $destination
     * @return iMSCP_Plugin_Filter_PluginArchive
     */
    public function setDestination(
        string $destination
    ): iMSCP_Plugin_Filter_PluginArchive
    {
        if (NULL === $destination) {
            $destination = GUI_ROOT_DIR . DIRECTORY_SEPARATOR . 'plugins';
        }

        $destination = (string)$destination;

        if (!is_dir($destination) || !is_writable($destination)) {
            throw new Zend_Filter_Exception(sprintf(
                "Invalid 'destination' option: '%s' is not a directory or is not writable.",
                $destination
            ));
        }

        $this->_options['destination'] = utils_normalizePath($destination);

        return $this;
    }

    /**
     * Returns 'magic_file' option
     *
     * @return string|null
     */
    public function getMagicFile(): ?string
    {
        return $this->_options['magic_file'];
    }

    /**
     * Set the 'magic_file' option
     *
     * @param null|string $magicFile
     * @return iMSCP_Plugin_Filter_PluginArchive
     */
    public function setMagicFile(
        ?string $magicFile
    ): iMSCP_Plugin_Filter_PluginArchive
    {
        if (NULL === $magicFile) {
            $this->_options['magic_file'] = NULL;
        }

        $magicFile = (string)$magicFile;

        if (!is_file($magicFile) || !is_readable($magicFile)) {
            throw new Zend_Filter_Exception(sprintf(
                "Invalid 'magic_file' option: '%s' is not a file or is not readable.",
                $magicFile
            ));
        }

        $this->_options['magic_file'] = utils_normalizePath($magicFile);

        return $this;
    }

    /**
     * @inheritdoc
     * @return string
     */
    public function filter($value): string
    {
        umask(027);
        $archName = explode('.', basename($value))[0];
        $destination = $this->getDestination();

        try {
            $mimeType = $this->_detectMimeType($value);

            if (!in_array($mimeType, [
                'application/zip', 'application/x-gzip', 'application/x-bzip2'
            ])) {
                throw new Zend_Validate_Exception(sprintf(
                    'Unsupported plugin archive type. Only zip, tar.gz and tar.bz2 archive type are supported.',
                    ));
            }

            if ($mimeType == 'application/zip') {
                if (!extension_loaded('zip')) {
                    throw new Zend_Validate_Exception(sprintf(
                        'Missing %s PHP extension.', 'zip'
                    ));
                }

                $arch = new ZipArchive();

                if (true !== $arch->open($value)) {
                    throw new Zend_Filter_Exception(sprintf(
                        'Error while opening the %s plugin archive.', $archName
                    ));
                }

                if (!$this->_backupPluginDir(
                    $destination . DIRECTORY_SEPARATOR . $archName
                )) {
                    throw new Zend_Filter_Exception(sprintf(
                        "Couldn't backup the current %s plugin directory.",
                        $archName
                    ));
                }

                if (false === @$arch->extractTo($destination)) {
                    throw new Zend_Filter_Exception(sprintf(
                        'Error while extracting the %s plugin archive.',
                        $archName
                    ));
                }

                $arch->close();
            } else {
                try {
                    Zend_Loader::loadClass('Archive_Tar');
                } catch (Zend_Exception $e) {
                    throw new Zend_Validate_Exception(
                        'Missing PEARs Archive_Tar.'
                    );
                }

                if (!extension_loaded(
                    $mimeType == 'application/x-gzip' ? 'zlib' : 'bz2'
                )) {
                    throw new Zend_Validate_Exception(sprintf(
                        'Missing %s PHP extension.',
                        $mimeType == 'application/x-gzip' ? 'zlib' : 'bz2'
                    ));
                }

                if (!$this->_backupPluginDir(
                    $destination . DIRECTORY_SEPARATOR . $archName
                )) {
                    throw new Zend_Filter_Exception(sprintf(
                        "Couldn't backup the current %s plugin directory.",
                        $archName
                    ));
                }

                $arch = new Archive_Tar(
                    $value, $mimeType == 'application/x-gzip' ? 'gz' : 'bz2'
                );

                if (false === $arch->extract($destination)) {
                    throw new Zend_Filter_Exception(sprintf(
                        'Error while extracting the %s plugin archive.',
                        $archName
                    ));
                }
            }
        } catch (Exception $e) {
            $this->_restorePluginDir(
                $destination . DIRECTORY_SEPARATOR . $archName
            );
            @unlink($value);
            throw $e;
        }

        @unlink($value);
        @utils_removeDir(
            $destination . DIRECTORY_SEPARATOR . $archName . '-old'
        );
        iMSCP_Utility_OpcodeCache::clearAllActive();

        return $destination . DIRECTORY_SEPARATOR . $archName;
    }

    /**
     * Internal method to detect the mime type of a file
     *
     * @param string $file File
     * @return string Mime-type of given file
     */
    protected function _detectMimeType(string $file): string
    {
        if (class_exists('finfo', false)) {
            $const = defined('FILEINFO_MIME_TYPE')
                ? FILEINFO_MIME_TYPE
                : FILEINFO_MIME;

            if (!empty($this->getMagicFile())) {
                $mime = @finfo_open($const, $this->getMagicFile());
            }

            if (empty($mime)) {
                $mime = @finfo_open($const);
            }

            if (!empty($mime)) {
                $result = finfo_file($mime, $file);
            }

            unset($mime);
        }

        if (empty($result)
            && (function_exists('mime_content_type')
                && ini_get('mime_magic.magicfile')
            )
        ) {
            $result = mime_content_type($file);
        }

        if (empty($result)) {
            $result = 'application/octet-stream';
        }

        return $result;
    }

    /**
     * Internal method to backup old backup directory
     *
     * @param string $pluginDir
     * @return bool
     */
    protected function _backupPluginDir(string $pluginDir): bool
    {
        if (!@utils_removeDir($pluginDir . '-old')) {
            return false;
        }

        if (!is_dir($pluginDir)) {
            return true;
        }

        return @rename($pluginDir, $pluginDir . '-old');
    }

    /**
     * Internal method to restore old plugin directory on failure
     *
     * @param string $pluginDir Plugin directory
     * @return void
     */
    protected function _restorePluginDir(string $pluginDir): void
    {
        @utils_removeDir($pluginDir);

        if (!is_dir($pluginDir . '-old')) {
            return;
        }

        @rename($pluginDir . '-old', $pluginDir);
    }
}
