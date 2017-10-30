<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
 */

namespace iMSCP\Loader;

use Zend_Loader_SplAutoloader as SplAutoloader;

require_once 'Zend/Loader/SplAutoloader.php';

/**
 * PSR-0/PSR-4 compliant autoloader
 *
 * Allows autoloading both namespaced and vendor-prefixed classes. Class
 * lookups are performed on the filesystem. If a class file for the referenced
 * class is not found, a PHP warning will be raised by include().
 *
 * @package iMSCP
 */
class StandardAutoloader implements SplAutoloader
{
    const NS_SEPARATOR = '\\';
    const PREFIX_SEPARATOR = '_';
    const LOAD_NS = 'namespaces';
    const LOAD_PREFIX = 'prefixes';
    const ACT_AS_FALLBACK = 'fallback_autoloader';

    /**
     * @var array Namespace/directory pairs to search
     */
    protected $namespaces = [];

    /**
     * @var array Prefix/directory pairs to search
     */
    protected $prefixes = [];

    /**
     * @var bool Whether or not the autoloader should also act as a fallback autoloader
     */
    protected $fallbackAutoloaderFlag = false;

    /**
     * @inheritdoc
     */
    public function __construct($options = NULL)
    {
        if (NULL !== $options) {
            $this->setOptions($options);
        }
    }

    /**
     * Configure autoloader
     *
     * Allows specifying both "namespace" and "prefix" pairs, using the
     * following structure:
     * <code>
     * [
     *     'namespaces' => [
     *         'iMSCP\\'     => '/path/to/iMSCP/library',
     *         'Doctrine\\' => '/path/to/Doctrine/library',
     *     ],
     *     'prefixes' => [
     *         'Zend_'     => '/path/to/Zend/library',
     *     ],
     *     'fallback_autoloader' => true,
     * ]
     * </code>
     *
     * @throws \InvalidArgumentException
     * @param  array|\Traversable $options
     * @return StandardAutoloader
     */
    public function setOptions($options)
    {
        if (!is_array($options)
            && !($options instanceof \Traversable)
        ) {
            throw new \InvalidArgumentException('Options must be either an array or Traversable');
        }

        foreach ($options as $type => $pairs) {
            switch ($type) {
                case self::LOAD_NS:
                    if (is_array($pairs) || $pairs instanceof \Traversable) {
                        $this->registerNamespaces($pairs);
                    }
                    break;
                case self::LOAD_PREFIX:
                    if (is_array($pairs) || $pairs instanceof \Traversable) {
                        $this->registerPrefixes($pairs);
                    }
                    break;
                case self::ACT_AS_FALLBACK:
                    $this->setFallbackAutoloader($pairs);
                    break;
                default:
                    // ignore
            }
        }

        return $this;
    }

    /**
     * Set flag indicating fallback autoloader status
     *
     * @param  bool $flag
     * @return StandardAutoloader
     */
    public function setFallbackAutoloader($flag)
    {
        $this->fallbackAutoloaderFlag = (bool)$flag;
        return $this;
    }

    /**
     * Is this autoloader acting as a fallback autoloader?
     *
     * @return bool
     */
    public function isFallbackAutoloader()
    {
        return $this->fallbackAutoloaderFlag;
    }

    /**
     * Register a namespace/directory pair
     *
     * @param string $namespace
     * @param string $directory
     * @return StandardAutoloader
     */
    public function registerNamespace($namespace, $directory)
    {
        $namespace = rtrim($namespace, self::NS_SEPARATOR) . self::NS_SEPARATOR;
        $this->namespaces[$namespace] = $this->normalizeDirectory($directory);
        return $this;
    }

    /**
     * Register many namespace/directory pairs at once
     *
     * @throws \Zend_Loader_Exception_InvalidArgumentException
     * @param array $namespaces
     * @return StandardAutoloader
     */
    public function registerNamespaces($namespaces)
    {
        if (!is_array($namespaces)
            && !$namespaces instanceof \Traversable
        ) {
            throw new \InvalidArgumentException('Namespace pairs must be either an array or Traversable');
        }

        foreach ($namespaces as $namespace => $directory) {
            $this->registerNamespace($namespace, $directory);
        }

        return $this;
    }

    /**
     * Register a prefix/directory pair
     *
     * @param  string $prefix
     * @param  string $directory
     * @return StandardAutoloader
     */
    public function registerPrefix($prefix, $directory)
    {
        $prefix = rtrim($prefix, self::PREFIX_SEPARATOR) . self::PREFIX_SEPARATOR;
        $this->prefixes[$prefix] = $this->normalizeDirectory($directory);
        return $this;
    }

    /**
     * Register many namespace/directory pairs at once
     *
     * @param  array $prefixes
     * @return StandardAutoloader
     */
    public function registerPrefixes($prefixes)
    {
        if (!is_array($prefixes)
            && !$prefixes instanceof \Traversable
        ) {
            throw new \InvalidArgumentException('Prefix pairs must be either an array or Traversable');
        }

        foreach ($prefixes as $prefix => $directory) {
            $this->registerPrefix($prefix, $directory);
        }

        return $this;
    }

    /**
     * Defined by Autoloadable; autoload a class
     *
     * @param  string $class
     * @return false|string
     */
    public function autoload($class)
    {
        $isFallback = $this->isFallbackAutoloader();

        if (false !== strpos($class, self::NS_SEPARATOR)) {
            if ($this->loadClass($class, self::LOAD_NS)) {
                return $class;
            }

            if ($isFallback) {
                return $this->loadClass($class, self::ACT_AS_FALLBACK);
            }

            return false;
        }

        if (false !== strpos($class, self::PREFIX_SEPARATOR)) {
            if ($this->loadClass($class, self::LOAD_PREFIX)) {
                return $class;
            }

            if ($isFallback) {
                return $this->loadClass($class, self::ACT_AS_FALLBACK);
            }

            return false;
        }

        if ($isFallback) {
            return $this->loadClass($class, self::ACT_AS_FALLBACK);
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function register()
    {
        spl_autoload_register([$this, 'autoload']);
    }

    /**
     * Load a class, based on its type (namespaced or prefixed)
     *
     * @param  string $class
     * @param  string $type
     * @return bool
     */
    protected function loadClass($class, $type)
    {
        // Fallback autoloading
        if ($type === self::ACT_AS_FALLBACK) {
            $resolvedName = stream_resolve_include_path($this->transformClassNameToFilename($class, ''));
            if ($resolvedName !== false) {
                return include $resolvedName;
            }

            return false;
        }

        // PSR-4 autoloading
        if ($type == self::LOAD_NS) {
            $filename = $this->findFile($class);
            if (NULL !== $filename) {
                return include $filename;
            }
        }

        // PSR-0 and/or prefix autoloading
        foreach ($this->$type as $leader => $path) {
            if (0 === strpos($class, $leader)) {
                $filename = $this->transformClassNameToFilename(substr($class, strlen($leader)), $path);
                if (file_exists($filename)) {
                    return include $filename;
                }

                return false;
            }
        }

        return false;
    }

    /**
     * Find a file
     *
     * @param string $class
     * @return string|null
     */
    protected function findFile($class)
    {
        $class = ltrim($class, self::NS_SEPARATOR);

        foreach ($this->namespaces as $currentNamespace => $currentBaseDir) {
            if (0 === strpos($class, $currentNamespace)) {
                $classWithoutPrefix = substr($class, strlen($currentNamespace));
                $file = $currentBaseDir
                    . str_replace(self::NS_SEPARATOR, DIRECTORY_SEPARATOR, $classWithoutPrefix)
                    . '.php';

                if (file_exists($file)) {
                    return $file;
                }
            }
        }

        return NULL;
    }

    /**
     * Transform the class name to a filename
     *
     * @param  string $class
     * @param  string $directory
     * @return string
     */
    protected function transformClassNameToFilename($class, $directory)
    {
        // $class may contain a namespace portion, in which case we need
        // to preserve any underscores in that portion.
        $matches = [];
        preg_match('/(?P<namespace>.+\\\)?(?P<class>[^\\\]+$)/', $class, $matches);

        $class = (isset($matches['class'])) ? $matches['class'] : '';
        $namespace = (isset($matches['namespace'])) ? $matches['namespace'] : '';

        return $directory
            . str_replace(self::NS_SEPARATOR, '/', $namespace)
            . str_replace(self::PREFIX_SEPARATOR, '/', $class)
            . '.php';
    }

    /**
     * Normalize the directory to include a trailing directory separator
     *
     * @param  string $directory
     * @return string
     */
    protected function normalizeDirectory($directory)
    {
        $last = $directory[strlen($directory) - 1];
        if (in_array($last, ['/', '\\'])) {
            $directory[strlen($directory) - 1] = DIRECTORY_SEPARATOR;
            return $directory;
        }

        return $directory . DIRECTORY_SEPARATOR;
    }
}
