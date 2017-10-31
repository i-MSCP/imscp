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

/**
 * Class AutoloaderFactory
 * @package iMSCP\Loader
 */
abstract class AutoloaderFactory
{
    const STANDARD_AUTOLOADER = 'iMSCP\\Loader\\StandardAutoloader';
    const ZEND_STANDAD_AUTOLOADER = 'Zend_Loader_StandardAutoloader';
    const CLASS_MAP_AUTOLOADER = 'Zend_Loader_ClassMapAutoloader';

    /**
     * @var array All autoloaders registered using the factory
     */
    protected static $loaders = [];

    /**
     * @var StandardAutoloader StandardAutoloader instance for resolving
     * autoloader classes via the include_path
     */
    protected static $standardAutoloader;

    /**
     * Factory for autoloaders
     *
     * Options should be an array or Traversable object of the following structure:
     * <code>
     * [
     *     '<autoloader class name>' => $autoloaderOptions,
     * ]
     * </code>
     *
     * The factory will then loop through and instantiate each autoloader with
     * the specified options, and register each with the spl_autoloader.
     *
     * You may retrieve the concrete autoloader instances later using
     * {@link getRegisteredAutoloaders()}.
     *
     * Note that the class names must be resolvable on the include_path or via
     * the Zend library, using PSR-0 rules (unless the class has already been
     * loaded).
     *
     * @param  array|\Traversable $options (optional) options to use. Defaults to Zend_Loader_StandardAutoloader
     * @return void
     * @throws \Zend_Loader_Exception_InvalidArgumentException for invalid options
     * @throws \Zend_Loader_Exception_InvalidArgumentException for unloadable autoloader classes
     */
    public static function factory($options = NULL)
    {
        if (NULL === $options) {
            if (!isset(self::$loaders[self::STANDARD_AUTOLOADER])) {
                $autoloader = self::getStandardAutoloader();
                $autoloader->register();
                self::$loaders[self::STANDARD_AUTOLOADER] = $autoloader;
                // BC reasons. will be removed in a later release
                self::$loaders[self::ZEND_STANDAD_AUTOLOADER] = $autoloader;
            }

            // Return so we don't hit the next check's exception (we're done here anyway)
            return;
        }

        if (!is_array($options)
            && !($options instanceof \Traversable)
        ) {
            throw new \InvalidArgumentException('Options provided must be an array or Traversable');
        }

        foreach ($options as $class => $loaderOptions) {
            if (!isset(self::$loaders[$class])) {
                // Check class map autoloader
                if ($class == self::CLASS_MAP_AUTOLOADER) {
                    if (!class_exists(self::CLASS_MAP_AUTOLOADER)) {
                        // Extract the filename from the classname
                        $classMapLoader = substr(
                            strrchr(self::CLASS_MAP_AUTOLOADER, '_'), 1
                        );

                        require_once __DIR__ . "/$classMapLoader.php";
                    }
                }

                // Autoload with standard autoloader
                $autoloader = self::getStandardAutoloader();
                if (!class_exists($class)
                    && !$autoloader->autoload($class)
                ) {
                    throw new \InvalidArgumentException(sprintf('Autoloader class "%s" not loaded', $class));
                }

                if (!is_subclass_of($class, 'iMSCP\\Loader\\SplAutoloaderInterface')) {
                    throw new \InvalidArgumentException(sprintf(
                        'Autoloader class %s must implement iMSCP\\Loader\\SplAutoloaderInterface', $class
                    ));
                }

                if ($class === self::STANDARD_AUTOLOADER) {
                    $autoloader->setOptions($loaderOptions);
                } else {
                    $autoloader = new $class($loaderOptions);
                }

                $autoloader->register();
                self::$loaders[$class] = $autoloader;
                if ($class === self::STANDARD_AUTOLOADER) {
                    // BC reasons. will be removed in a later release
                    self::$loaders[self::ZEND_STANDAD_AUTOLOADER] = $autoloader;
                }
            } else {
                self::$loaders[$class]->setOptions($loaderOptions);
            }
        }
    }

    /**
     * Get an instance of the standard autoloader
     *
     * Used to attempt to resolve autoloader classes, using the
     * StandardAutoloader. The instance is marked as a fallback autoloader, to
     * allow resolving autoloaders not under the "Zend" or "Zend" namespaces.
     *
     * @return StandardAutoloader
     */
    protected static function getStandardAutoloader()
    {
        if (NULL !== self::$standardAutoloader) {
            return self::$standardAutoloader;
        }

        // Extract the filename from the classname
        $stdAutoloader = substr(strrchr(self::STANDARD_AUTOLOADER, '\\'), 1);

        if (!class_exists(self::STANDARD_AUTOLOADER)) {
            require_once __DIR__ . "/$stdAutoloader.php";
        }

        self::$standardAutoloader = new StandardAutoloader();
        return self::$standardAutoloader;
    }

    /**
     * Retrieves an autoloader by class name
     *
     * @param string $class
     * @return \Zend_Loader_SplAutoloader
     * @throws \Zend_Loader_Exception_InvalidArgumentException for non-registered class
     */
    public static function getRegisteredAutoloader($class)
    {
        if (!isset(self::$loaders[$class])) {
            throw new \InvalidArgumentException(sprintf('Autoloader class "%s" not loaded', $class));
        }

        return self::$loaders[$class];
    }

    /**
     * Unregisters all autoloaders that have been registered via the factory.
     * This will NOT unregister autoloaders registered outside of the fctory.
     *
     * @return void
     */
    public static function unregisterAutoloaders()
    {
        foreach (self::getRegisteredAutoloaders() as $class => $autoloader) {
            spl_autoload_unregister([$autoloader, 'autoload']);

            // BC reasons. will be removed in a later release
            if ($class == self::STANDARD_AUTOLOADER
                || $class == self::ZEND_STANDAD_AUTOLOADER
            ) {
                unset(self::$loaders[self::STANDARD_AUTOLOADER]);
                unset(self::$loaders[self::ZEND_STANDAD_AUTOLOADER]);
            }

            unset(self::$loaders[$class]);
        }
    }

    /**
     * Get an list of all autoloaders registered with the factory
     *
     * Returns an array of autoloader instances.
     *
     * @return array
     */
    public static function getRegisteredAutoloaders()
    {
        return self::$loaders;
    }

    /**
     * Unregister a single autoloader by class name
     *
     * @param  string $autoloaderClass
     * @return bool
     */
    public static function unregisterAutoloader($autoloaderClass)
    {
        if (!isset(self::$loaders[$autoloaderClass])) {
            return false;
        }

        $autoloader = self::$loaders[$autoloaderClass];
        spl_autoload_unregister([$autoloader, 'autoload']);

        // BC reasons. will be removed in a later release
        if ($autoloaderClass == self::STANDARD_AUTOLOADER
            || $autoloaderClass == self::ZEND_STANDAD_AUTOLOADER
        ) {
            unset(self::$loaders[self::STANDARD_AUTOLOADER]);
            unset(self::$loaders[self::ZEND_STANDAD_AUTOLOADER]);
        }

        unset(self::$loaders[$autoloaderClass]);
        return true;
    }
}
