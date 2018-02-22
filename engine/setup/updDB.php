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

use iMSCP\Update\UpdateDatabase;

define('IMSCP_SETUP', true);

function upddb_process()
{
    chdir(dirname(__FILE__));
    require_once '../../gui/library/imscp-lib.php';

    $dbUpdater = new UpdateDatabase();

    if ($dbUpdater->getLastAppliedUpdate() > $dbUpdater->getLastUpdate()) {
        throw new iMSCP_Exception('An i-MSCP downgrade attempt has been detected. Downgrade is not supported.');
    }

    if (!$dbUpdater->applyUpdates()) {
        fwrite(STDERR, sprintf("[ERROR] %s\n", $dbUpdater->getError()));
        exit(1);
    }

    i18n_buildLanguageIndex();
}

try {
    if (version_compare(PHP_VERSION, '7', '<')) {
        upddb_process();
    } else {
        try {
            upddb_process();
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
