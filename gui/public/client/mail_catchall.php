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

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate action
 *
 * @param int $mailId
 * @param string $mailStatus Mail account status
 * @return array|null
 */
function client_generateAction($mailId, $mailStatus)
{
    if ($mailStatus == 'toadd') {
        return [tr('N/A'), '#'];
    }

    if ($mailStatus == 'ok') {
        return [tr('Delete CatchAll'), "mail_catchall_delete.php?id=$mailId"];
    }

    if ($mailStatus == 'tochange') {
        return [tr('N/A'), '#'];
    }

    if ($mailStatus == 'todelete') {
        return [tr('N/A'), '#'];
    }

    return NULL;
}

/**
 * Generate catchall item
 *
 * @param iMSCP_pTemplate $tpl
 * @param string $action Action
 * @param int $dmnId Domain unique identifier
 * @param string $dmnName Domain name
 * @param int $mailId Mail unique identifier
 * @param string $mailAcc Mail account
 * @param string $mailStatus Mail account status
 * @param string $catchallType Catchall type
 * @return void
 */
function client_generateCatchallItem($tpl, $action, $dmnId, $dmnName, $mailId, $mailAcc, $mailStatus, $catchallType)
{
    $showDmnName = decode_idna($dmnName);

    if ($action == 'create') {
        $tpl->assign([
            'CATCHALL_DOMAIN'        => tohtml($showDmnName),
            'CATCHALL_ACC'           => tr('None'),
            'TR_CATCHALL_STATUS'     => tr('N/A'),
            'TR_CATCHALL_ACTION'     => tr('Create catch all'),
            'CATCHALL_ACTION'        => $action,
            'CATCHALL_ACTION_SCRIPT' => "mail_catchall_add.php?id=$dmnId;$catchallType",
            'DEL_ICON'               => ''
        ]);
        return;
    }

    list($catchallAction, $catchallActionScript) = client_generateAction($mailId, $mailStatus);

    $showDmnName = decode_idna($dmnName);
    $showMailAcc = decode_idna($mailAcc);
    $tpl->assign([
        'CATCHALL_DOMAIN'        => tohtml($showDmnName),
        'CATCHALL_ACC'           => tohtml($showMailAcc),
        'TR_CATCHALL_STATUS'     => translate_dmn_status($mailStatus),
        'TR_CATCHALL_ACTION'     => $catchallAction,
        'CATCHALL_ACTION'        => $catchallAction,
        'CATCHALL_ACTION_SCRIPT' => $catchallActionScript
    ]);

    if ($catchallActionScript == '#') {
        $tpl->assign('DEL_ICON', '');
    }
}

/**
 * Generate catchall list
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $dmnId Domain unique identifier
 * @param string $dmnName Domain Name
 */
function client_generateCatchallList($tpl, $dmnId, $dmnName)
{
    $serverHostname = iMSCP_Registry::get('config')->{'SERVER_HOSTNAME'};
    $statusOk = 'ok';

    if ($dmnName != $serverHostname) {
        $stmt = exec_query(
            'SELECT mail_id, mail_acc, status FROM mail_users WHERE domain_id = ? AND sub_id = ? AND mail_type = ?',
            [$dmnId, 0, 'normal_catchall']
        );

        if (!$stmt->rowCount()) {
            client_generateCatchallItem($tpl, 'create', $dmnId, $dmnName, '', '', '', 'normal');
        } else {
            $row = $stmt->fetchRow();
            client_generateCatchallItem($tpl, 'delete', $dmnId, $dmnName, $row['mail_id'], $row['mail_acc'], $row['status'], 'normal');
        }

        $tpl->parse('CATCHALL_ITEM', 'catchall_item');
    }

    $stmt = exec_query(
        'SELECT alias_id, alias_name FROM domain_aliasses WHERE domain_id = ? AND alias_status = ?',
        [$dmnId, $statusOk]
    );

    while ($data = $stmt->fetchRow()) {
        $id = $data['alias_id'];
        $name = $data['alias_name'];
        $stmt2 = exec_query(
            'SELECT mail_id, mail_acc, status FROM mail_users WHERE domain_id = ? AND sub_id = ? AND mail_type = ?',
            [$dmnId, $id, 'alias_catchall']
        );

        if (!$stmt2->rowCount()) {
            client_generateCatchallItem($tpl, 'create', $id, $name, '', '', '', 'alias');
        } else {
            $row = $stmt2->fetchRow();
            client_generateCatchallItem($tpl, 'delete', $id, $name, $row['mail_id'], $row['mail_acc'], $row['status'], 'alias');
        }

        $tpl->parse('CATCHALL_ITEM', '.catchall_item');
    }

    $stmt = exec_query(
        "
            SELECT t1.subdomain_alias_id, CONCAT(t1.subdomain_alias_name, '.', t2.alias_name) AS subdomain_name
            FROM subdomain_alias AS t1, domain_aliasses AS t2
            WHERE t2.alias_id IN (SELECT alias_id FROM domain_aliasses WHERE domain_id = ?)
            AND t1.alias_id = t2.alias_id
            AND t1.subdomain_alias_status = ?
        ",
        [$dmnId, $statusOk]
    );

    while ($data = $stmt->fetchRow()) {
        $id = $data['subdomain_alias_id'];
        $name = $data['subdomain_name'];
        $stmt2 = exec_query(
            'SELECT mail_id, mail_acc, status FROM mail_users WHERE domain_id = ? AND sub_id = ? AND mail_type = ?',
            [$dmnId, $id, 'alssub_catchall']
        );

        if (!$stmt2->rowCount()) {
            client_generateCatchallItem($tpl, 'create', $id, $name, '', '', '', 'alssub');
        } else {
            $row = $stmt2->fetchRow();
            client_generateCatchallItem($tpl, 'delete', $id, $name, $row['mail_id'], $row['mail_acc'], $row['status'], 'alssub');
        }

        $tpl->parse('CATCHALL_ITEM', '.catchall_item');
    }

    $stmt = exec_query(
        "
            SELECT t1.subdomain_id, CONCAT(t1.subdomain_name, '.', t2.domain_name) AS subdomain_name
            FROM subdomain AS t1, domain AS t2
            WHERE t1.domain_id = ?
            AND t1.domain_id = t2.domain_id
            AND t1.subdomain_status = ?
        ",
        [$dmnId, $statusOk]
    );

    while ($data = $stmt->fetchRow()) {
        $id = $data['subdomain_id'];
        $name = $data['subdomain_name'];
        $stmt2 = exec_query(
            'SELECT mail_id, mail_acc, status FROM mail_users WHERE domain_id = ? AND sub_id = ? AND mail_type = ?',
            [$dmnId, $id, 'subdom_catchall']
        );

        if (!$stmt2->rowCount()) {
            client_generateCatchallItem($tpl, 'create', $id, $name, '', '', '', 'subdom');
        } else {
            $row = $stmt2->fetchRow();
            client_generateCatchallItem($tpl, 'delete', $id, $name, $row['mail_id'], $row['mail_acc'], $row['status'], 'subdom');
        }

        $tpl->parse('CATCHALL_ITEM', '.catchall_item');
    }
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 */
function client_generatePage($tpl)
{
    $domainProps = get_domain_default_props($_SESSION['user_id']);
    $dmnId = $domainProps['domain_id'];
    $dmnName = $domainProps['domain_name'];

    client_generateCatchallList($tpl, $dmnId, $dmnName);
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');
customerHasFeature('mail') or showBadRequestErrorPage();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'        => 'shared/layouts/ui.tpl',
    'page'          => 'client/mail_catchall.tpl',
    'page_message'  => 'layout',
    'catchall_item' => 'page',
    'del_icon'      => 'catchall_item'
]);

$tpl->assign([
    'TR_PAGE_TITLE'                => tr('Client / Email / Catchall'),
    'TR_STATUS'                    => tr('Status'),
    'TR_ACTION'                    => tr('Action'),
    'TR_TITLE_CATCHALL_MAIL_USERS' => tr('Catch all'),
    'TR_DOMAIN'                    => tr('Domain'),
    'TR_CATCHALL'                  => tr('Catch all'),
    'TR_MESSAGE_DELETE'            => tr('Are you sure you want to delete the %s catch all?', '%s'),
    'TR_CANCEL'                    => tr('Cancel')
]);

iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
    /** @var $e \iMSCP_Events_Event */
    $e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
});

client_generatePage($tpl);
generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

