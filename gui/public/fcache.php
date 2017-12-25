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

use iMSCP_Registry as Registry;
use Zend_Cache_Core as Cache;

require_once 'imscp-lib.php';

if (strtolower($_SERVER['REQUEST_METHOD']) !== 'get') {
    showBadRequestErrorPage();
}

$cacheIds = explode(';', isset($_GET['ids']) ? clean_input((string)$_GET['ids']) : []);
if (empty($cacheIds)) {
    showBadRequestErrorPage();
}

/** @var Cache $cache */
$cache = Registry::get('iMSCP_Application')->getCache();

foreach ($cacheIds as $cacheId) {
    if ($cacheId === 'opcache') {
        iMSCP_Utility_OpcodeCache::clearAllActive();
        write_log('OPcache has been flushed.', E_USER_NOTICE);
    } elseif ($cacheId === 'userland') {
        if (!$cache->clean()) {
            showInternalServerError();
        }

        write_log('APCu userland cache has been flushed.', E_USER_NOTICE);
    } elseif ($cache->test($cacheId)) {
        if (!$cache->remove($cacheId)) {
            showInternalServerError();
        }

        write_log(sprintf('APCu userland cache with ID `%s` has been flushed.', $cacheId), E_USER_NOTICE);
    }
}

exit('success');
