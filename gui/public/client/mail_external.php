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
 * Functions
 */

/**
 * Activate or deactivate external mail feature for the given domain
 *
 * @param string $action Action to be done (activate|deactivate)
 * @param int $domainId Domain unique identifier
 * @param string $domainType Domain type
 * @throws Zend_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function updateExternalMailFeature($action, $domainId, $domainType)
{
    switch ($domainType) {
        case 'dmn';
            $query = "
                UPDATE domain SET domain_status = 'tochange', external_mail = ?
                WHERE domain_id = ? AND domain_admin_id = ?
            ";
            break;
        case 'als';
            $query = "
                UPDATE domain_aliasses AS t1
                JOIN domain AS t2 USING(domain_id)
                SET t1.alias_status = 'tochange', t1.external_mail = ?
                WHERE t1.alias_id = ? AND t2.domain_admin_id = ?
            ";
            break;
        default:
            showBadRequestErrorPage();
            return;
    }

    $stmt = exec_query($query, [$action == 'activate' ? 'on' : 'off', $domainId, $_SESSION['user_id']]);

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    if ($action == 'activate') {
        write_log(sprintf('External mail feature has been activated by %s', $_SESSION['user_logged']));
        set_page_message(tr('External mail server feature scheduled for activation.'), 'success');
        return;
    }

    write_log(sprintf('External mail feature has been deactivated by %s', $_SESSION['user_logged']));
    set_page_message(tr('External mail server feature scheduled for deactivation.'), 'success');
}

/**
 * Generate an external mail server item
 *
 * @access private
 * @param iMSCP_pTemplate $tpl Template instance
 * @param string $externalMail Status of external mail for the domain
 * @param int $domainId Domain id
 * @param string $domainName Domain name
 * @param string $status Item status
 * @param string $type Domain type (normal for domain or alias for domain alias)
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 */
function generateItem($tpl, $externalMail, $domainId, $domainName, $status, $type)
{
    if ($status == 'ok') {
        if ($externalMail == 'off') {
            $tpl->assign([
                'DOMAIN'          => decode_idna($domainName),
                'STATUS'          => ($status == 'ok') ? tr('Deactivated') : translate_dmn_status($status),
                'DOMAIN_TYPE'     => $type,
                'DOMAIN_ID'       => $domainId,
                'TR_ACTIVATE'     => ($status == 'ok') ? tr('Activate') : tr('N/A'),
                'DEACTIVATE_LINK' => ''
            ]);
            $tpl->parse('ACTIVATE_LINK', 'activate_link');
            return;
        }

        $tpl->assign([
            'DOMAIN'        => decode_idna($domainName),
            'STATUS'        => ($status == 'ok') ? tr('Activated') : translate_dmn_status($status),
            'DOMAIN_TYPE'   => $type,
            'DOMAIN_ID'     => $domainId,
            'ACTIVATE_LINK' => '',
            'TR_DEACTIVATE' => ($status == 'ok') ? tr('Deactivate') : tr('N/A'),
        ]);
        $tpl->parse('DEACTIVATE_LINK', 'deactivate_link');
        return;
    }

    $tpl->assign([
        'DOMAIN'          => decode_idna($domainName),
        'STATUS'          => translate_dmn_status($status),
        'ACTIVATE_LINK'   => '',
        'DEACTIVATE_LINK' => ''
    ]);
}

/**
 * Generate external mail server item list
 *
 * @access private
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $domainId Domain id
 * @param string $domainName Domain name
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function generateItemList($tpl, $domainId, $domainName)
{
    $stmt = exec_query('SELECT domain_status, external_mail FROM domain WHERE domain_id = ?', $domainId);
    $data = $stmt->fetchRow();

    generateItem($tpl, $data['external_mail'], $domainId, $domainName, $data['domain_status'], 'dmn');

    $tpl->parse('ITEM', '.item');

    $stmt = exec_query(
        'SELECT alias_id, alias_name, alias_status, external_mail FROM domain_aliasses WHERE domain_id = ?', $domainId
    );

    if (!$stmt->rowCount()) {
        return;
    }

    while ($data = $stmt->fetchRow()) {
        generateItem($tpl, $data['external_mail'], $data['alias_id'], $data['alias_name'], $data['alias_status'], 'als');
        $tpl->parse('ITEM', '.item');
    }
}

/**
 * Generates page
 *
 * @param iMSCP_ptemplate $tpl
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function generatePage($tpl)
{
    iMSCP_Events_Aggregator::getInstance()->registerListener(iMSCP_Events::onGetJsTranslations, function ($e) {
        /** @var iMSCP_Events_Description $e */
        $translations = $e->getParam('translations');
        $translations['core']['datatable'] = getDataTablesPluginTranslations(false);
    });

    $tpl->assign([
        'TR_PAGE_TITLE' => tr('Client / Mail / External Mail Feature'),
        'TR_INTRO'      => tr('Below, you can activate the external mail feature for one or many of your domains. Note that activating the external mail feature configures our server to relay your mail through your own mail server, but that no DNS record is created for it.'),
        'TR_DOMAIN'     => tr('Domain'),
        'TR_STATUS'     => tr('Status'),
        'TR_ACTION'     => tr('Action'),
        'TR_DEACTIVATE' => tr('Deactivate'),
        'TR_CANCEL'     => tr('Cancel')
    ]);

    $domainProps = get_domain_default_props($_SESSION['user_id']);
    $domainId = $domainProps['domain_id'];
    $domainName = $domainProps['domain_name'];
    generateItemList($tpl, $domainId, $domainName);
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

check_login('user');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

if (!customerHasFeature('external_mail')) {
    showBadRequestErrorPage();
}

if (isset($_GET['action'])
    && isset($_GET['domain_id'])
    && isset($_GET['domain_type'])
) {
    $action = clean_input($_GET['action']);
    $domainId = intval($_GET['domain_id']);
    $domainType = clean_input($_GET['domain_type']);

    switch ($action) {
        case 'activate':
        case 'deactivate':
            updateExternalMailFeature($action, $domainId, $domainType);
            send_request();
            break;
        default:
            showBadRequestErrorPage();
    }

    redirectTo('mail_external.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'          => 'shared/layouts/ui.tpl',
    'page'            => 'client/mail_external.tpl',
    'page_message'    => 'layout',
    'item'            => 'page',
    'activate_link'   => 'item',
    'deactivate_link' => 'item'
]);

generateNavigation($tpl);
generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
