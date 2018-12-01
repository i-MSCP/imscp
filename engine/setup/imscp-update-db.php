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
use iMSCP_Update_Database as DbUpdater;

define('IMSCP_SETUP', true);

/**
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 * @throws iMSCP_i18n_Exception
 */
function update()
{
    chdir(dirname(__FILE__));
    require_once '../../gui/library/imscp-lib.php';

    $dbUpdater = DbUpdater::getInstance();

    if ($dbUpdater->getLastAppliedUpdate() > $dbUpdater->getLastUpdate()) {
        throw new iMSCP_Exception('An i-MSCP downgrade attempt has been detected. Downgrade is not supported.');
    }

    if (!$dbUpdater->applyUpdates()) {
        fwrite(STDERR, sprintf("[ERROR] %s\n", $dbUpdater->getError()));
        exit(1);
    }

    // Optimize the database unless last optimization date is less than 24 hours
    $lastOptimization = intval(Registry::get('config')['DATABASE_LAST_OPTIMIZATION']);
    if (time() >= $lastOptimization + 86400) {
        $dbUpdater->optimizeTables();
    }

    // FIXME: Not really the right place... Should be done in dedicated script...
    i18n_buildLanguageIndex();
}

try {
    if (version_compare(PHP_VERSION, '7', '<')) {
        update();
    } else {
        try {
            update();
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);

        }
    }
} catch (Exception $e) {
    $prevException = $e->getPrevious();

    fwrite(
        STDERR,
        sprintf(
            "[ERROR] %s \n\nStack trace:\n\n%s\n",
            $e->getMessage(),
            ($prevException) ? $prevException->getTraceAsString() : $e->getTraceAsString()
        )
    );
    exit(1);
}
