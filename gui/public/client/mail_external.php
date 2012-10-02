<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Client
 * @copyright   2010-2012 by i-MSCP team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @author      Sascha Bay <worst.case@gmx.de>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate an external mail server item
 *
 * @access private
 * @param iMSCP_pTemplate $tpl Template instance
 * @param string $action Action
 * @param int $domainId Domain id
 * @param string $domainName Domain name
 * @param string $status Item status
 * @param string $type Domain type (normal for domain or alias for domain alias)
 * @return void
 */
function _client_generateItem($tpl, $action, $domainId, $domainName, $status, $type)
{
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');
    $idnDomainName = decode_idna($domainName);
    $statusOk = $cfg->ITEM_OK_STATUS;

    if ($action == 'create') {
        $tpl->assign(
            array(
                'DOMAIN' => tohtml($idnDomainName),
                'STATUS' => ($status == $statusOk) ? tr('Deactivated') : translate_dmn_status($status),
                'CREATE_ACTION_URL' => "mail_external_add.php?id=$domainId;$type",
                'TR_CREATE' => tr('Create'),
                'EDIT_LINK' => '',
                'DELETE_LINK' => ''
            )
        );

        $tpl->parse('CREATE_LINK', 'create_link');
    } else {
        $tpl->assign(
            array(
                'DOMAIN' => tohtml($idnDomainName),
                'STATUS' => ($status == $statusOk) ? tr('Activated') : translate_dmn_status($status),
                'CREATE_LINK' => '',
                'TR_EDIT' => ($status == $statusOk) ? tr('Edit') : tr('N/A'),
                'EDIT_ACTION_URL' => ($status == $statusOk) ? "mail_external_edit.php?id=$domainId;$type" : '#',
                'TR_DELETE' => ($status == $statusOk) ? tr('Delete') : tr('N/A'),
                'DELETE_ACTION_URL' => ($status == $statusOk) ? "mail_external_delete.php?id=$domainId;$type" : '#'
            )
        );

        $tpl->parse('EDIT_LINK', 'edit_link');
        $tpl->parse('DELETE_LINK', 'delete_link');
    }
}

/**
 * Generate external mail server item list
 *
 * @access private
 * @param iMSCP_pTemplate $tpl Template engine
 * @param int $domainId Domain id
 * @param string $domainName
 * @return void
 */
function _client_generateItemList($tpl, $domainId, $domainName)
{
    $query = 'SELECT `domain_status`, `external_mail` FROM `domain` WHERE `domain_id` = ?';
    $stmt = exec_query($query, $domainId);
    $mode = $stmt->fields['external_mail'] == 'off' ? 'create' : 'normal';
    _client_generateItem($tpl, $mode, $domainId, $domainName, $stmt->fields['domain_status'], 'normal');
    $tpl->parse('ITEM', '.item');

    $query = 'SELECT * FROM `domain_aliasses` WHERE `domain_id` = ?';
    $stmt = exec_query($query, array($domainId));

    while (!$stmt->EOF) {
        $aliasId = $stmt->fields['alias_id'];
        $aliasName = $stmt->fields['alias_name'];
        $mode = $stmt->fields['external_mail'] == 'off' ? 'create' : 'normal';
        _client_generateItem($tpl, $mode, $aliasId, $aliasName, $stmt->fields['alias_status'], 'alias');
        $tpl->parse('ITEM', '.item');
        $stmt->moveNext();
    }
}

/**
 * Generates view
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function client_generateView($tpl)
{
    list($domainId, $domainName) = get_domain_default_props($_SESSION['user_id']);
    _client_generateItemList($tpl, $domainId, $domainName);
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('external_mail') || !customerHasFeature('mail')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
    array(
        'layout' => 'shared/layouts/ui.tpl',
        'page' => 'client/mail_external.tpl',
        'page_message' => 'layout',
        'item' => 'page',
        'create_link' => 'item',
        'edit_link' => 'item',
        'delete_link' => 'item'
    )
);

$tpl->assign(
    array(
        'TR_PAGE_TITLE' => tr('i-MSCP - Client / Mail Accounts / External mail servers'),
        'THEME_CHARSET' => tr('encoding'),
        'ISP_LOGO' => layout_getUserLogo(),
        'TR_TITLE_RELAY_MAIL_USERS' => tr('External mail servers'),
        'TR_DOMAIN' => tr('Domain'),
        'TR_STATUS' => tr('Status'),
        'TR_ACTION' => tr('Action'),
        'TR_DELETE_MESSAGE' => tr("Are you sure you want to delete the external mail server entries for the '%s' domain?", true, '%s')
    )
);

generateNavigation($tpl);
generatePageMessage($tpl);
client_generateView($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
