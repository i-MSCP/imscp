<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by i-MSCP Team
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

require 'imscp-lib.php';

check_login('admin');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

isset($_GET['id']) or showBadRequestErrorPage();

$softwareId = intval($_GET['id']);
$stmt = exec_query(
    'SELECT reseller_id, software_archive, software_depot FROM web_software WHERE software_id = ?', $softwareId
);

if (!$stmt->rowCount()) {
    showBadRequestErrorPage();
}

$row = $stmt->fetch();

$cfg = iMSCP_Registry::get('config');
if ($row['software_depot'] == 'yes') {
    $filename = $cfg['GUI_APS_DEPOT_DIR'] . '/' . $row['software_archive'] . '-' . $softwareId . '.tar.gz';
} else {
    $filename = $cfg['GUI_APS_DIR'] . '/' . $row['reseller_id'] . '/' . $row['software_archive'] . '-' . $softwareId . '.tar.gz';
}

if (!file_exists($filename)) {
    set_page_message(tr('File does not exist. %1$s.tar.gz', $row['software_archive']), 'error');
    redirectTo('software_manage.php');
}

header("Cache-Control: public, must-revalidate");
header("Pragma: hack");
header("Content-Type: application/octet-stream");
header("Content-Length: " . (string)(filesize($filename)));
header('Content-Disposition: attachment; filename="' . $row['software_archive'] . '.tar.gz"');
header("Content-Transfer-Encoding: binary\n");

$fp = fopen($filename, 'rb');
$buffer = fread($fp, filesize($filename));
fclose($fp);
print $buffer;
