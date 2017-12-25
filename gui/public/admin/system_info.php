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

use iMSCP\TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('admin');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAdminScriptStart);

$tpl = new TemplateEngine();
$tpl->define([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'admin/system_info.phtml',
    'page_message' => 'layout',
    'device_block' => 'page'
]);

$tpl->config = Registry::get('config');

$sysinfo = new iMSCP_SystemInfo();

$tpl->assign([
    'CPU_MODEL'       => tohtml($sysinfo->cpu['model']),
    'CPU_CORES'       => tohtml($sysinfo->cpu['cpus']),
    'CPU_CLOCK_SPEED' => tohtml($sysinfo->cpu['cpuspeed']),
    'CPU_CACHE'       => tohtml($sysinfo->cpu['cache']),
    'CPU_BOGOMIPS'    => tohtml($sysinfo->cpu['bogomips']),
    'UPTIME'          => tohtml($sysinfo->uptime),
    'KERNEL'          => tohtml($sysinfo->kernel),
    'LOAD'            => tohtml(sprintf('%s %s %s', $sysinfo->load[0], $sysinfo->load[1], $sysinfo->load[2])),
    'RAM_TOTAL'       => tohtml(bytesHuman($sysinfo->ram['total'] * 1024)),
    'RAM_USED'        => tohtml(bytesHuman($sysinfo->ram['used'] * 1024)),
    'RAM_FREE'        => tohtml(bytesHuman($sysinfo->ram['free'] * 1024)),
    'SWAP_TOTAL'      => tohtml(bytesHuman($sysinfo->swap['total'] * 1024)),
    'SWAP_USED'       => tohtml(bytesHuman($sysinfo->swap['used'] * 1024)),
    'SWAP_FREE'       => tohtml(bytesHuman($sysinfo->swap['free'] * 1024))
]);

$devices = $sysinfo->filesystem;

foreach ($devices as $device) {
    $tpl->assign([
        'MOUNT'     => tohtml($device['mount']),
        'TYPE'      => tohtml($device['fstype']),
        'PARTITION' => tohtml($device['disk']),
        'PERCENT'   => tohtml($device['percent']),
        'FREE'      => tohtml(bytesHuman($device['free'] * 1024)),
        'USED'      => tohtml(bytesHuman($device['used'] * 1024)),
        'SIZE'      => tohtml(bytesHuman($device['size'] * 1024))
    ]);
    $tpl->parse('DEVICE_BLOCK', '.device_block');
}

$tpl->assign([
    'TR_PAGE_TITLE'               => tohtml(tr('Admin / System Tools / System Information')),
    'TR_DISTRIBUTION_INFO'        => tohtml(tr('Distribution')),
    'TR_DISTRIBUTION_ID'          => tohtml(tr('ID')),
    'TR_DISTRIBUTION_RELEASE'     => tohtml(tr('Release')),
    'TR_DISTRIBUTION_CODENAME'    => tohtml(tr('Codename')),
    'TR_iMSCP_INFO'               => tohtml(tr('i-MSCP Info')),
    'TR_IMSCP_RELEASE'            => tohtml(tr('Release')),
    'TR_IMSCP_CODENAME'           => tohtml(tr('Codename')),
    'TR_IMSCP_BUILD'              => tohtml(tr('Build')),
    'TR_IMSCP_PLUGIN_API_VERSION' => tohtml(tr('Plugin API version')),
    'TR_IMSCP_HTTPD_SERVER'       => tohtml(tr('HTTPD')),
    'TR_IMSCP_FTPD_SERVER'        => tohtml(tr('FTPD')),
    'TR_IMSCP_MTA_SERVER'         => tohtml(tr('MTA')),
    'TR_IMSCP_PHP_SERVER'         => tohtml(tr('PHP (for customers)')),
    'TR_IMSCP_PO_SERVER'          => tohtml(tr('IMAP/POP')),
    'TR_IMSCP_SQL_SERVER'         => tohtml(tr('SQL')),
    'TR_SYSTEM_INFO'              => tohtml(tr('System')),
    'TR_KERNEL'                   => tohtml(tr('Kernel Version')),
    'TR_UPTIME'                   => tohtml(tr('Uptime')),
    'TR_LOAD'                     => tohtml(tr('Load (1 Min, 5 Min, 15 Min)')),
    'TR_CPU_INFO'                 => tohtml(tr('Processor Info')),
    'TR_CPU'                      => tohtml(tr('Processor')),
    'TR_CPU_MODEL'                => tohtml(tr('Model')),
    'TR_CPU_CORES'                => tohtml(tr('Cores')),
    'TR_CPU_CLOCK_SPEED'          => tohtml(tr('Clock speed (MHz)')),
    'TR_CPU_CACHE'                => tohtml(tr('Cache')),
    'TR_CPU_BOGOMIPS'             => tohtml(tr('Bogomips')),
    'TR_MEMORY_INFO'              => tohtml(tr('Memory Info')),
    'TR_RAM'                      => tohtml(tr('Memory data')),
    'TR_TOTAL'                    => tohtml(tr('Total')),
    'TR_USED'                     => tohtml(tr('Used')),
    'TR_FREE'                     => tohtml(tr('Free')),
    'TR_SWAP'                     => tohtml(tr('Swap data')),
    'TR_FILE_SYSTEM_INFO'         => tohtml(tr('Filesystem Info')),
    'TR_MOUNT'                    => tohtml(tr('Mount point')),
    'TR_TYPE'                     => tohtml(tr('Type')),
    'TR_PARTITION'                => tohtml(tr('Partition')),
    'TR_PERCENT'                  => tohtml(tr('Percent')),
    'TR_SIZE'                     => tohtml(tr('Size'))
]);

Registry::get('iMSCP_Application')->getEventsManager()->registerListener('onGetJsTranslations', function (iMSCP_Events_Event $e) {
    $e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations();
});

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
Registry::get('iMSCP_Application')->getEventsManager()->dispatch(iMSCP_Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
