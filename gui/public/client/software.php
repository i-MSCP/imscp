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
 *  Script functions
 */

/**
 * client_generatePageLists.
 *
 * @param iMSCP_pTemplate $tpl Template engine instance
 * @return void
 * @throws Zend_Exception
 * @throws iMSCP_Events_Exception
 * @throws iMSCP_Events_Manager_Exception
 * @throws iMSCP_Exception
 * @throws iMSCP_Exception_Database
 */
function client_generatePageLists($tpl)
{
    $domainProperties = get_domain_default_props($_SESSION['user_id']);
    $stmt = exec_query('SELECT created_by FROM admin WHERE admin_id = ?', $_SESSION['user_id']);
    $software_poss = gen_software_list($tpl, $domainProperties['domain_id'], $stmt->fields['created_by']);
    $tpl->assign('TOTAL_SOFTWARE_AVAILABLE', $software_poss);
}

/***********************************************************************************************************************
 * Main script
 */

require_once 'imscp-lib.php';

check_login('user');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
customerHasFeature('aps') or showBadRequestErrorPage();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic([
    'layout'                  => 'shared/layouts/ui.tpl',
    'page'                    => 'client/software.tpl',
    'page_message'            => 'layout',
    'software_message'        => 'page',
    'software_item'           => 'page',
    'software_action_delete'  => 'page',
    'software_action_install' => 'page',
    'software_total'          => 'page',
    'no_software'             => 'page',
    'no_software_support'     => 'page',
    'software_list'           => 'page',
    'del_software_support'    => 'software_list',
    'del_software_item'       => 'software_list',
    't_software_support'      => 'software_list'
]);
$tpl->assign([
    'TR_PAGE_TITLE'         => tr('Client / Webtools / Software'),
    'TR_SOFTWARE'           => tr('Software'),
    'TR_VERSION'            => tr('Version'),
    'TR_LANGUAGE'           => tr('Language'),
    'TR_TYPE'               => tr('Type'),
    'TR_NEED_DATABASE'      => tr('Database'),
    'TR_STATUS'             => tr('Status'),
    'TR_ACTION'             => tr('Action'),
    'TR_SOFTWARE_AVAILABLE' => tr('Available software')
]);

iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
    /** @var $e \iMSCP_Events_Event */
    $e->getParam('translations')->core['dataTable'] = getDataTablesPluginTranslations(false);
});

generateNavigation($tpl);
client_generatePageLists($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();

unsetMessages();
