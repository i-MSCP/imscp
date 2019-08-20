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
 */

/** @noinspection PhpIncludeInspection */

define('IMSCP_SETUP', true);

use iMSCP\Registry;
use iMSCP\Update\DatabaseUpdate;
use iMSCP\Update\UpdateException;

try {
    chdir(dirname(__FILE__));
    require_once '../../gui/include/imscp-lib.php';

    // Update database schema

    $dbUpdater = DatabaseUpdate::getInstance();

    if ($dbUpdater->getLastAppliedUpdate() > $dbUpdater->getLastUpdate()) {
        throw new UpdateException("i-MSCP downgrade isn't supported.");
    }

    if (!$dbUpdater->applyUpdates()) {
        throw new UpdateException($dbUpdater->getError());
    }

    // Optimize the database unless last optimization date is less than 24 hours

    $lastOptimization = intval(
        Registry::get('config')['DATABASE_LAST_OPTIMIZATION']
    );

    if (time() > $lastOptimization + 604800) {
        $dbUpdater->optimizeTables();
    }

    // Update language index in database

    i18n_buildLanguageIndex();
} catch (Throwable $e) {
    fwrite(
        STDERR,
        sprintf(
            "[ERROR] %s \n\nStack trace:\n\n%s\n",
            $e->getMessage(),
            $e->getTraceAsString()
        )
    );
    exit(1);
}
