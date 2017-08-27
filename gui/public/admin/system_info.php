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

/***********************************************************************************************************************
 * Main
 */

require 'imscp-lib.php';

check_login('admin');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => 'admin/system_info.tpl',
    'page_message' => 'layout',
    'device_block' => 'page'
]);

$sysinfo = new iMSCP_SystemInfo();

$tpl->assign([
    'CPU_MODEL'       => tohtml($sysinfo->cpu['model']),
    'CPU_CORES'       => tohtml($sysinfo->cpu['cpus']),
    'CPU_CLOCK_SPEED' => tohtml($sysinfo->cpu['cpuspeed']),
    'CPU_CACHE'       => tohtml($sysinfo->cpu['cache']),
    'CPU_BOGOMIPS'    => tohtml($sysinfo->cpu['bogomips']),
    'UPTIME'          => tohtml($sysinfo->uptime),
    'KERNEL'          => tohtml($sysinfo->kernel),
    'LOAD'            => sprintf('%s %s %s', $sysinfo->load[0], $sysinfo->load[1], $sysinfo->load[2]),
    'RAM_TOTAL'       => bytesHuman($sysinfo->ram['total'] * 1024),
    'RAM_USED'        => bytesHuman($sysinfo->ram['used'] * 1024),
    'RAM_FREE'        => bytesHuman($sysinfo->ram['free'] * 1024),
    'SWAP_TOTAL'      => bytesHuman($sysinfo->swap['total'] * 1024),
    'SWAP_USED'       => bytesHuman($sysinfo->swap['used'] * 1024),
    'SWAP_FREE'       => bytesHuman($sysinfo->swap['free'] * 1024)
]);

$devices = $sysinfo->filesystem;

foreach ($devices as $device) {
    $tpl->assign([
        'MOUNT'     => tohtml($device['mount']),
        'TYPE'      => tohtml($device['fstype']),
        'PARTITION' => tohtml($device['disk']),
        'PERCENT'   => $device['percent'],
        'FREE'      => bytesHuman($device['free'] * 1024),
        'USED'      => bytesHuman($device['used'] * 1024),
        'SIZE'      => bytesHuman($device['size'] * 1024)
    ]);
    $tpl->parse('DEVICE_BLOCK', '.device_block');
}

$tpl->assign([
    'TR_PAGE_TITLE'       => tr('Admin / System Tools / System Information'),
    'TR_SYSTEM_INFO'      => tr('System data'),
    'TR_KERNEL'           => tr('Kernel Version'),
    'TR_UPTIME'           => tr('Uptime'),
    'TR_LOAD'             => tr('Load (1 Min, 5 Min, 15 Min)'),
    'TR_CPU_INFO'         => tr('CPU Information'),
    'TR_CPU'              => tr('Processor data'),
    'TR_CPU_MODEL'        => tr('Model'),
    'TR_CPU_CORES'        => tr('Cores'),
    'TR_CPU_CLOCK_SPEED'  => tr('Clock speed (MHz)'),
    'TR_CPU_CACHE'        => tr('Cache'),
    'TR_CPU_BOGOMIPS'     => tr('Bogomips'),
    'TR_MEMORY_INFO'      => tr('Memory information'),
    'TR_RAM'              => tr('RAM'),
    'TR_TOTAL'            => tr('Total'),
    'TR_USED'             => tr('Used'),
    'TR_FREE'             => tr('Free'),
    'TR_SWAP'             => tr('Swap'),
    'TR_FILE_SYSTEM_INFO' => tr('Filesystem system Info'),
    'TR_MOUNT'            => tr('Mount'),
    'TR_TYPE'             => tr('Type'),
    'TR_PARTITION'        => tr('Partition'),
    'TR_PERCENT'          => tr('Percent'),
    'TR_SIZE'             => tr('Size')
]);

iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
    /* @var $e iMSCP_Events_Event */
    $e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations();
});

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
