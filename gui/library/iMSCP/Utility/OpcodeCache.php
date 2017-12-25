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
 */

/**
 * Class OpcodeCacheUtility
 *
 * Code borrowed to the TYPO3 CMS project.
 */
class iMSCP_Utility_OpcodeCache
{
    /**
     * @var array|null All supported cache types
     */
    static protected $supportedCaches;

    /**
     * @var array|null Holds all currently active caches
     */
    static protected $activeCaches;

    /**
     * Clears a file from an opcache, if one exists
     *
     * @param string|NULL $fileAbsPath The file as absolute path to be cleared
     *                                 or NULL to clear completely
     * @return void
     */
    static public function clearAllActive($fileAbsPath = NULL)
    {
        foreach (static::getAllActive() as $properties) {
            $callback = $properties['clearCallback'];
            $callback($fileAbsPath);
        }
    }

    /**
     * Returns all supported and active opcaches
     *
     * @return array Array filled with supported and active opcaches
     */
    static public function getAllActive()
    {
        if (static::$activeCaches === NULL) {
            static::initialize();
        }

        return static::$activeCaches;
    }

    /**
     * Initialize the cache properties
     *
     * @return void
     */
    static protected function initialize()
    {
        $apcVersion = phpversion('apc');
        $xcVersion = phpversion('xcache');

        static::$supportedCaches = [
            // The ZendOpcache aka OPcache since PHP 5.5
            // http://php.net/manual/de/book.opcache.php
            'OPcache'           => [
                'active'        => extension_loaded('Zend OPcache') && ini_get('opcache.enable') === '1',
                'version'       => phpversion('Zend OPcache'),
                'canReset'      => true, // opcache_reset() ... it seems that it doesn't reset for current run.
                // From documentation this function exists since first version (7.0.0) but from Changelog
                // this function exists since 7.0.2
                // http://pecl.php.net/package-changelog.php?package=ZendOpcache&release=7.0.2
                'canInvalidate' => function_exists('opcache_invalidate'),
                'error'         => false,
                'clearCallback' => function ($fileAbsPath) {
                    if ($fileAbsPath !== NULL
                        && function_exists('opcache_invalidate')
                    ) {
                        opcache_invalidate($fileAbsPath, true);
                    } else {
                        opcache_reset();
                    }
                }
            ],

            // The Alternative PHP Cache aka APC
            // http://www.php.net/manual/de/book.apc.php
            'APC'               => [
                // Currently APCu identifies itself both as "apcu" and "apc"
                // (for compatibility) although it doesn't provide the
                // APC-opcache functionality
                //'active' => extension_loaded('apc') && !extension_loaded('apcu') && ini_get('apc.enabled') === '1',
                'active'        => extension_loaded('apc')
                    && !extension_loaded('apcu') && ini_get('apc.enabled') === '1',
                'version'       => $apcVersion,
                // apc_clear_cache() since APC 2.0.0 so default yes. In cli it do not clear the http cache.
                'canReset'      => true,
                'canInvalidate' => self::canApcInvalidate(),
                // Versions lower then 3.1.7 are known as malfunction
                'error'         => $apcVersion && version_compare($apcVersion, '3.1.7', '<'),
                'clearCallback' => function ($fileAbsPath) {
                    if ($fileAbsPath !== NULL && iMSCP_Utility_OpcodeCache::getCanInvalidate('APC')) {
                        // This may output a warning like: PHP Warning: apc_delete_file(): Could not stat file
                        // This warning isn't true, this means that apc was unable to generate the cache key
                        // which depends on the configuration of APC.
                        @apc_delete_file($fileAbsPath);
                    } else {
                        apc_clear_cache('opcode');
                    }
                }
            ],

            // http://www.php.net/manual/de/book.wincache.php
            /*'WinCache' => array(
                'active' => extension_loaded('wincache') && ini_get('wincache.ocenabled') === '1',
                'version' => phpversion('wincache'),
                'canReset' => false,
                'canInvalidate' => true, // wincache_refresh_if_changed()
                'error' => false,
                'clearCallback' => function ($fileAbsPath) {
                    if($fileAbsPath !== null) {
                        wincache_refresh_if_changed(array($fileAbsPath));
                    } else {
                        // No argument means refreshing all.
                        wincache_refresh_if_changed();
                    }
                }
            ),
            */

            // http://xcache.lighttpd.net/
            'XCache'            => [
                'active'        => extension_loaded('xcache'),
                'version'       => $xcVersion,
                'canReset'      => true, // xcache_clear_cache()
                'canInvalidate' => false,
                'error'         => false,
                // API changed with XCache 3.0.0
                // http://xcache.lighttpd.net/wiki/XcacheApi?action=diff&version=23&old_version=22
                'clearCallback' => (
                $xcVersion && version_compare($xcVersion, '3.0.0', '<')
                    ?
                    function () {
                        if (!ini_get('xcache.admin.enable_auth') && defined('XC_TYPE_PHP')) {
                            xcache_clear_cache(XC_TYPE_PHP, 0);
                        }
                    }
                    :
                    function () {
                        if (!ini_get('xcache.admin.enable_auth') && defined('XC_TYPE_PHP')) {
                            xcache_clear_cache(XC_TYPE_PHP);
                        }
                    }
                )
            ],

            // https://github.com/eaccelerator/eaccelerator
            //
            // @see https://github.com/eaccelerator/eaccelerator/blob/master/doc/php/info.php
            // Only possible if we are in eaccelerator.admin_allowed_path and we can only remove data
            // "that isn't used in the current requests"
            'eAccelerator'      => [
                'active'        => extension_loaded('eAccelerator'),
                'version'       => phpversion('eaccelerator'),
                'canReset'      => false,
                'canInvalidate' => false,
                'error'         => false,
                'clearCallback' => function () {
                    /** @noinspection PhpUndefinedFunctionInspection */
                    eaccelerator_clear();
                }
            ],

            // https://github.com/zendtech/ZendOptimizerPlus
            // http://files.zend.com/help/Zend-Server/zend-server.htm#zendoptimizerplus.html
            'ZendOptimizerPlus' => [
                'active'        => extension_loaded('Zend Optimizer+') && ini_get('zend_optimizerplus.enable') === '1',
                'version'       => phpversion('Zend Optimizer+'),
                'canReset'      => true, // accelerator_reset()
                'canInvalidate' => false,
                'error'         => false,
                'clearCallback' => function () {
                    /** @noinspection PhpUndefinedFunctionInspection */
                    accelerator_reset();
                }
            ],
        ];

        static::$activeCaches = [];

        // Cache the active ones
        foreach (static::$supportedCaches as $opcodeCache => $properties) {
            if ($properties['active']) {
                static::$activeCaches[$opcodeCache] = $properties;
            }
        }
    }

    /**
     * Checks if the APC configuration is useable to clear cache of one file ( https://bugs.php.net/bug.php?id=66819 )
     *
     * @return bool Returns TRUE if file can be invalidated and FALSE if complete cache needs to be removed
     */
    static public function canApcInvalidate()
    {
        // apc_delete_file() should exists since APC 3.1.1 but you never know so default is no
        $canInvalidate = false;

        if (function_exists('apc_delete_file')) {
            // Deleting files from cache depends on generating the cache key.
            // This cache key generation depends on unnecessary configuration options
            // http://git.php.net/?p=pecl/caching/apc.git;a=blob;f=apc_cache.c;h=d15cf8c1b4b9d09b9bac75b16c062c8b40458dda;hb=HEAD#l931

            // If stat=0 then canonicalized path may be used
            $stat = (int)ini_get('apc.stat');

            // If canonicalize (default = 1) then file_update_protection isn't checked
            $canonicalize = (int)ini_get('apc.canonicalize');

            // If file file_update_protection is checked, then we will fail, 'cause we generated the file and then try to
            // remove it. But the file is not older than file_update_protection and therefore hash generation will stop with error.
            $protection = (int)ini_get('apc.file_update_protection');

            if ($protection === 0 || ($stat === 0 && $canonicalize === 1)) {
                $canInvalidate = true;
            }
        }

        return $canInvalidate;
    }

    /**
     * Gets the state of canInvalidate for given cache system
     *
     * @param string $system The cache system to test (APC, ...)
     * @return boolean The calculated value from array or FALSE if cache system not exists
     */
    static public function getCanInvalidate($system)
    {
        return isset(static::$supportedCaches[$system])
            ? static::$supportedCaches[$system]['canInvalidate'] : false;
    }
}
