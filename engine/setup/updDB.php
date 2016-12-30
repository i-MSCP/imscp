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

use iMSCP_Update_Database as DbUpdater;

define('IMSCP_SETUP', true);

try {
    chdir(dirname(__FILE__));
    require_once '../../gui/library/imscp-lib.php';

    $dbUpdater = DbUpdater::getInstance();

    if($dbUpdater->getLastAppliedUpdate() > $dbUpdater->getLastUpdate()) {
        throw new iMSCP_Exception('An i-MSCP downgrade attempt has been detected. Downgrade is not supported.');
    }

    if (!$dbUpdater->applyUpdates()) {
        fwrite(STDERR, sprintf("[ERROR] %s\n", $dbUpdater->getError()));
        exit(1);
    }

    i18n_buildLanguageIndex();
} catch (Exception $e) {
    fwrite(STDERR, sprintf("[ERROR] %s \n\nStack trace:\n\n%s\n", $e->getMessage(), $e->getTraceAsString()));
    exit(1);
}
