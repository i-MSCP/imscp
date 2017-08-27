<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2017 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

require 'imscp-lib.php';

check_login('admin');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

systemHasAntiRootkits() or showBadRequestErrorPage();

$config = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'           => 'shared/layouts/ui.tpl',
    'page'             => 'admin/rootkit_log.tpl',
    'page_message'     => 'layout',
    'antirootkits_log' => 'page'
]);
$tpl->assign('TR_PAGE_TITLE', tr('Admin / System Tools / Anti-Rootkits Logs'));

$antiRootkits = [];

if (isset($config['ANTI_ROOTKITS_PACKAGES'])) {
    $antiRootkits = explode(',', $config['ANTI_ROOTKITS_PACKAGES']);
}

$antiRootkits[] = 'Other';
$antiRootkitLogFiles = [
    'Chkrootkit' => 'CHKROOTKIT_LOG',
    'Rkhunter'   => 'RKHUNTER_LOG',
    'Other'      => 'OTHER_ROOTKIT_LOG'
];

foreach ($antiRootkitLogFiles as $antiRootkit => $logVar) {
    if (!in_array($antiRootkit, $antiRootkits) || !isset($config[$logVar]) || $config[$logVar] == '') {
        unset($antiRootkitLogFiles[$antiRootkit]);
    }
}

if (!empty($antiRootkitLogFiles)) {
    /** @var Zend_Cache_Core $cache */
    $cache = iMSCP_Registry::get('iMSCP_Application')->getCache();

    foreach ($antiRootkitLogFiles AS $antiRootkit => $logVar) {
        $logFile = $config[$logVar];
        $cacheId = 'iMSCP_Rootkit_'. pathinfo($logFile, PATHINFO_FILENAME);

        if(!($content = $cache->load($cacheId))) {
            if (@is_readable($logFile) && @filesize($logFile) > 0) {
                $handle = fopen($logFile, 'r');
                $log = fread($handle, filesize($logFile));
                fclose($handle);
                $content = nl2br(tohtml($log));
                $content = '<div>' . $content . '</div>';
                $search = [];
                $replace = [];

                // rkhunter-like log colouring
                if ($antiRootkit == 'Rkhunter') {
                    $search [] = '/[^\-]WARNING/i';
                    $replace[] = '<strong style="color:orange">$0</strong>';
                    $search [] = '/([^a-z])(OK)([^a-z])/i';
                    $replace[] = '$1<span style="color:green">$2</span>$3';
                    $search [] = '/[ \t]+clean[ \t]+/i';
                    $replace[] = '<span style="color:green">$0</span>';
                    $search [] = '/Not found/i';
                    $replace[] = '<span style="color:blue">$0</span>';
                    $search [] = '/None found/i';
                    $replace[] = '<span style="color:magenta">$0</span>';
                    $search [] = '/Skipped/i';
                    $replace[] = '<span style="color:blue">$0</span>';
                    $search [] = '/unknown[^)]/i';
                    $replace[] = '<strong style="color:#bf55bf">$0</strong>';
                    $search [] = '/Unsafe/i';
                    $replace[] = '<strong style="color:#cfcf00">$0</strong>';
                    $search [] = '/[1-9][0-9]*[ \t]+vulnerable/i';
                    $replace[] = '<strong style="color:red">$0</strong>';
                    $search [] = '/0[ \t]+vulnerable/i';
                    $replace[] = '<span style="color:green">$0</span>';
                } elseif ($antiRootkit == 'Chkrootkit') {
                    // chkrootkit-like log colouring
                    $search [] = '/([^a-z][ \t]+)(INFECTED)/i';
                    $replace[] = '$1<strong style="color:red">$2</strong>';
                    $search [] = '/Nothing found/i';
                    $replace[] = '<span style="color:green">$0</span>';
                    $search [] = '/Nothing detected/i';
                    $replace[] = '<span style="color:green">$0</span>';
                    $search [] = '/Not infected/i';
                    $replace[] = '<span style="color:green">$0</span>';
                    $search [] = '/no packet sniffer/i';
                    $replace[] = '<span style="color:green">$0</span>';
                    $search [] = '/(: )(PACKET SNIFFER)/i';
                    $replace[] = '$1<span style="color:orange">$2</span>';
                    $search [] = '/not promisc/i';
                    $replace[] = '<span style="color:green">$0</span>';
                    $search [] = '/no suspect file(s|)/i';
                    $replace[] = '<span style="color:green">$0</span>';
                    $search [] = '/([0-9]+) process(|es) hidden/i';
                    $replace[] = '<span style="color:#cfcf00">$0</span>';
                }

                $content = preg_replace($search, $replace, $content);
            } else {
                $content = '<strong style="color:red">' . tr("%s doesn't exist or is empty.", $logFile) . '</strong>';
            }

            $cache->save($content, $cacheId, [], 86400);
        }

        $tpl->assign([
            'LOG'      => $content,
            'FILENAME' => $logFile
        ]);
        $tpl->parse('ANTIROOTKITS_LOG', '.antirootkits_log');
    }

    $tpl->assign('NB_LOG', sizeof($antiRootkitLogFiles));
} else {
    $tpl->assign('ANTIROOTKITS_LOG', '');
    set_page_message(tr('No anti-rootkits logs'), 'static_info');
}

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
