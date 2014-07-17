<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
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
 * @category    iMSCP
 * @package     iMSCP_Loader
 * @copyright   2010-2014 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace iMSCP\Loader;

/**
 * AutoloaderFactory class
 *
 * Class allowing to create/retrieve autoloader class implementing the iMSCP\Loader\ISplAutoloader interface. This class
 * also acts as an autoloader registry.
 *
 * @package     iMSCP_Loader
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
class AutoloaderFactory
{
    /**
     * @const string Default loader
     */
    const DEFAULT_LOADER = 'iMSCP\\Loader\\UniversalLoader';

    /**
     * @var ISplAutoloader[] All loaders registered using the factory
     */
    protected static $loaders = array();

    /**
     * @var string Default loader instance
     */
    protected static $defaultLoader = null;

    /**
     * Factory for autoloaders
     *
     * Options should be an array of the following structure:
     * <code>
     * array(
     *     '<autoloader class name>' => $autoloaderOptions,
     * )
     * </code>
     *
     * The factory will then loop through and instantiate each autoloader with the specified options, and register each
     * with the spl_autoloader. If no options is passed in, the default autoloader will be registered. Also if an
     * autoloader is already instantiated, options will be added to it.
     *
     * Note that the class names must be resolvable on the include_path or via the Zend library, using PSR-0 rules
     * (unless the class has already been loaded).
     *
     * @throws \InvalidArgumentException
     * @param array $options
     * @return void
     */
    public static function factory(array $options = null)
    {
        if (null !== $options) {
            foreach ($options as $class => $autoloaderOptions) {
                if (!isset(static::$loaders[$class])) { // Autoloader not already instantiated
                    $loader = static::getDefaultLoader();

                    // Trying to load the given autoloader with default autoloader
                    if (!class_exists($class) && !$loader->autoload($class)) {
                        throw new \InvalidArgumentException(
                            sprintf('%s(): Unable to load the Autoloader class "%s"', __METHOD__, $class)
                        );
                    }

                    if ($class === static::DEFAULT_LOADER) {
                        $loader->setOptions($autoloaderOptions);
                    } else {
                        $loader = new $class($autoloaderOptions);

                        if (!$loader instanceof ISplAutoloader) {
                            throw new \InvalidArgumentException(
                                sprintf(
                                    '%s(): autoloader class %s must implement the iMSCP\Loader\ISplAutoloader interface',
                                    __METHOD__,
                                    $class
                                )
                            );
                        }
                    }

                    $loader->register(); // Register the loader on the spl autoloader registry
                    static::$loaders[$class] = $loader;
                } else { // Autoloader instance already there, we are so symply add options for it
                    static::$loaders[$class]->setOptions($autoloaderOptions);
                }
            }
        } elseif (!isset(static::$loaders[static::DEFAULT_LOADER])) {
            // No options passed in, we so create default autoloader instance
            $loader = static::getDefaultLoader();
            $loader->register();
            static::$loaders[static::DEFAULT_LOADER] = $loader;
        }
    }

    /**
     * Return instance of given autoloader or false if not found
     *
     * @param string $classname Autoloader classname
     * @return bool|ISplAutoloader
     */
    public static function getAutoloader($classname)
    {
        if(isset(static::$loaders[$classname])) {
            return static::$loaders[$classname];
        }

        return false;
    }

    /**
     * Create and returns instance of default loader
     *
     * @return ISplAutoloader
     */
    protected static function getDefaultLoader()
    {
        if (null === static::$defaultLoader) {
           $className = static::DEFAULT_LOADER;

            if (!class_exists($className)) {
                // Retrieves filename from the classname
                $fileName = substr(strrchr($className, '\\'), 1);
                require_once __DIR__ . "/$fileName.php";
            }

            static::$defaultLoader = new $className();
        }

        return static::$defaultLoader;
    }
}
