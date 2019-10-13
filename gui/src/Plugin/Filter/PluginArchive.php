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

namespace iMSCP\Plugin\Filter;

use Archive_Tar;
use Exception;
use iMSCP\Utility\OpcodeCache;
use Zend_Config;
use Zend_Exception;
use Zend_Filter_Exception;
use Zend_Filter_Interface;
use Zend_Loader;
use ZipArchive;

/**
 * Class PluginArchive
 *
 * Filter that extract uploaded plugin archive into the plugins directory
 *
 * @package iMSCP\Plugin\Filter
 */
class PluginArchive implements Zend_Filter_Interface
{
    /**
     * @var array Validator options
     */
    protected $_options = [
        'destination' => NULL,
        'magic_file'  => NULL
    ];

    /**
     * PluginArchive constructor.
     *
     * @param mixed $options
     * @return void
     */
    public function __construct($options)
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
     * Returns all set options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->_options;
    }

    /**
     * Sets the options for this filter.
     *
     * @param array $options
     * @return PluginArchive
     */
    public function setOptions(
        array $options
    ): PluginArchive
    {
        if (array_key_exists('destination', $options)) {
            $this->setDestination($options['destination']);
        }

        if (array_key_exists('magic_file', $options)) {
            $this->setMagicFile($options['magic_file']);
        }

        return $this;
    }

    /**
     * Set the 'destination' option.
     *
     * @param string|null $destination
     * @return PluginArchive
     */
    public function setDestination(
        ?string $destination
    ): PluginArchive
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
     * Set the 'magic_file' option.
     *
     * @param null|string $magicFile
     * @return PluginArchive
     */
    public function setMagicFile(
        ?string $magicFile
    ): PluginArchive
    {
        if (NULL === $magicFile) {
            $this->_options['magic_file'] = NULL;
            return $this;
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
     */
    public function filter($value): string
    {
        umask(027);
        $name = explode('.', basename($value))[0];
        $destination = $this->getDestination();

        try {
            $mimeType = $this->_detectMimeType($value);

            if (!in_array($mimeType, [
                'application/zip', 'application/x-gzip', 'application/x-bzip2',
                'application/x-xz'
            ])) {
                throw new Zend_Filter_Exception(sprintf(
                    'Unsupported plugin archive type. Only tar.gz, tar.bz2, tar.xz and zip archive types are supported.'
                ));
            }

            if ($mimeType == 'application/zip') {
                $this->_filterZipArchive($value);
            } else {
                $this->_filterTarArchive($value, $mimeType);

            }
        } catch (Exception $e) {
            $this->_restorePluginDir(
                $destination . DIRECTORY_SEPARATOR . $name
            );
            @unlink($value);
            throw $e;
        }

        @unlink($value);
        @utils_removeDir(
            $destination . DIRECTORY_SEPARATOR . $name . '-old'
        );
        OpcodeCache::clearAllActive();

        return $destination . DIRECTORY_SEPARATOR . $name;
    }

    /**
     * Returns the 'destination' option.
     *
     * @return string
     */
    public function getDestination(): string
    {
        return $this->_options['destination'];
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
     * Returns 'magic_file' option.
     *
     * @return string|null
     */
    public function getMagicFile(): ?string
    {
        return $this->_options['magic_file'];
    }

    /**
     * Internal method to filter a zip archive.
     *
     * @param $value
     * @return void
     * @throws Zend_Filter_Exception
     */
    protected function _filterZipArchive($value): void
    {
        if (!extension_loaded('zip')) {
            throw new Zend_Filter_Exception(sprintf(
                'Missing %s PHP extension.', 'zip'
            ));
        }

        $arch = new ZipArchive();
        $name = explode('.', basename($value))[0];

        if (true !== $arch->open($value)) {
            throw new Zend_Filter_Exception(sprintf(
                'Error while opening the %s plugin archive.', $name
            ));
        }

        $destination = $this->getDestination();

        if (!$this->_backupPluginDir(
            $destination . DIRECTORY_SEPARATOR . $name
        )) {
            throw new Zend_Filter_Exception(sprintf(
                "Couldn't backup the current %s plugin directory.",
                $name
            ));
        }

        umask(027);

        if (false === @$arch->extractTo($destination)) {
            throw new Zend_Filter_Exception(sprintf(
                'Error while extracting the %s plugin archive.',
                $name
            ));
        }

        $arch->close();
    }

    /**
     * Internal method to backup old backup directory.
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
     * Internal method to filter a tar archive.
     *
     * @param $value
     * @param $type
     * @return void
     * @throws Zend_Filter_Exception
     */
    protected function _filterTarArchive($value, $type): void
    {
        try {
            Zend_Loader::loadClass('Archive_Tar');
        } catch (Zend_Exception $e) {
            throw new Zend_Filter_Exception(
                'Missing PEARs Archive_Tar.'
            );
        }

        $extName = ($type == 'application/x-gzip')
            ? 'zlib' : ($type == 'application/x-bzip2' ? 'bz2' : 'xz');

        if (!extension_loaded($extName)) {
            throw new Zend_Filter_Exception(sprintf(
                'Missing %s PHP extension.', $extName
            ));
        }

        $name = explode('.', basename($value))[0];
        $destination = $this->getDestination();

        if (!$this->_backupPluginDir(
            $destination . DIRECTORY_SEPARATOR . $name
        )) {
            throw new Zend_Filter_Exception(sprintf(
                "Couldn't backup the current %s plugin directory.",
                $name
            ));
        }

        umask(027);

        $arch = new Archive_Tar(
            $value,
            $extName == 'zlib' ? 'gz' : (($extName == 'xz') ? 'lzma2' : 'bz2')
        );

        if (false === $arch->extract($destination)) {
            throw new Zend_Filter_Exception(sprintf(
                'Error while extracting the %s plugin archive.',
                $name
            ));
        }
    }

    /**
     * Internal method to restore old plugin directory on failure.
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
