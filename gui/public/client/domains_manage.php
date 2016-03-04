<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 by i-MSCP Team
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
 * Generates domains list
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function generateDomainsList($tpl)
{
    $cfg = iMSCP_Registry::get('config');
    $stmt = exec_query(
        "
            SELECT t1.domain_id, t1.domain_name, t1.domain_created, t1.domain_expires, t1.domain_status,
                t2.status as ssl_status
            FROM domain AS t1
            LEFT JOIN ssl_certs AS t2 ON(t2.domain_id = t1.domain_id AND t2.domain_type = 'dmn')
            WHERE domain_admin_id = ? ORDER BY domain_name
        ",
        $_SESSION['user_id']
    );

    while ($row = $stmt->fetchRow()) {
        $domainName = decode_idna($row['domain_name']);

        if ($row['domain_status'] == 'ok') {
            $tpl->assign(array(
                'DOMAIN_NAME' => tohtml($domainName),
                'DOMAIN_STATUS_RELOAD_FALSE' => ''
            ));
            $tpl->parse('DOMAIN_STATUS_RELOAD_TRUE', 'domain_status_reload_true');
        } else {
            $tpl->assign(array(
                'DOMAIN_NAME' => tohtml($domainName),
                'DOMAIN_STATUS_RELOAD_TRUE' => ''
            ));
            $tpl->parse('DOMAIN_STATUS_RELOAD_FALSE', 'domain_status_reload_false');
        }

        $tpl->assign(array(
            'DOMAIN_NAME' => tohtml($domainName),
            'DOMAIN_CREATE_DATE' => tohtml(date($cfg['DATE_FORMAT'], $row['domain_created'])),
            'DOMAIN_EXPIRE_DATE' => $row['domain_expires'] != 0 ? tohtml(date($cfg['DATE_FORMAT'], $row['domain_expires'])) : tr('Never'),
            'DOMAIN_STATUS' => translate_dmn_status($row['domain_status']),
            'DOMAIN_SSL_STATUS' => is_null($row['ssl_status'])
                ? tr('Disabled')
                : (
                    in_array($row['ssl_status'], array('toadd', 'tochange', 'todelete', 'ok'))
                        ? translate_dmn_status($row['ssl_status'])
                        : '<span style="color: red;font-weight: bold">' . tr('Invalid SSL certificate') . "</span>"
                ),
            'CERT_SCRIPT' => tohtml('cert_view.php?domain_id=' . $row['domain_id'] . '&domain_type=dmn', 'htmlAttr'),
            'VIEW_CERT' => customerHasFeature('ssl') ? tr('Manage SSL certificate') : tr('View SSL certificate'),
        ));
        $tpl->parse('DOMAIN_ITEM', '.domain_item');
    }
}

/**
 * Generates domain aliases list
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function generateDomainAliasesList($tpl)
{
    if (!customerHasFeature('domain_aliases')) {
        $tpl->assign('DOMAIN_ALIASES_BLOCK', '');
        return;
    }

    $domainId = get_user_domain_id($_SESSION['user_id']);
    $stmt = exec_query(
        "
            SELECT t1.alias_id, t1.alias_name, t1.alias_status, t1.alias_mount, t1.alias_ip_id, t1.url_forward,
                t2.status AS ssl_status
            FROM domain_aliasses AS t1
            LEFT JOIN ssl_certs AS t2 ON(t1.alias_id = t2.domain_id AND t2.domain_type = 'als')
            WHERE t1.domain_id = ? ORDER BY t1.alias_mount, t1.alias_name
        ",
        $domainId
    );

    if (!$stmt->rowCount()) {
        $tpl->assign(array(
            'ALS_MSG' => tr('You do not have domain aliases.'),
            'ALS_LIST' => ''
        ));
        return;
    }

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        $alsId = $row['alias_id'];
        $alsName = $row['alias_name'];
        $alsStatus = $row['alias_status'];
        $alsForwardUrl = $row['url_forward'];
        $alsMountPoint = $row['alias_mount'];

        list($action, $actionScript, $isStatusOk, $certText, $certScript) = generateDomainAliasAction($alsId, $alsStatus);
        list($redirectUrl, $editLink, $edit) = generateDomainAliasRedirect($alsId, $alsStatus, $alsForwardUrl);

        $alsName = decode_idna($alsName);
        $redirectUrl = decode_idna($redirectUrl);

        if ($isStatusOk) {
            $tpl->assign(array(
                'ALS_NAME' => tohtml($alsName),
                'ALS_STATUS_RELOAD_FALSE' => ''
            ));
            $tpl->parse('ALS_STATUS_RELOAD_TRUE', 'als_status_reload_true');
        } else {
            $tpl->assign(array(
                'ALS_NAME' => tohtml($alsName),
                'ALS_STATUS_RELOAD_TRUE' => ''
            ));
            $tpl->parse('ALS_STATUS_RELOAD_FALSE', 'als_status_reload_false');
        }

        $tpl->assign(array(
            'ALS_NAME' => tohtml($alsName),
            'ALS_MOUNT' => tohtml($alsMountPoint),
            'ALS_STATUS' => translate_dmn_status($alsStatus),
            'ALS_SSL_STATUS' => is_null($row['ssl_status'])
                ? tr('Disabled')
                : (
                    in_array($row['ssl_status'], array('toadd', 'tochange', 'todelete', 'ok'))
                        ? translate_dmn_status($row['ssl_status'])
                        : '<span style="color: red;font-weight: bold">' . tr('Invalid SSL certificate') . "</span>"
                ),
            'ALS_REDIRECT' => tohtml($redirectUrl),
            'ALS_EDIT_LINK' => $editLink,
            'ALS_EDIT' => $edit,
            'ALS_ACTION' => $action,
            'CERT_SCRIPT' => $certScript,
            'VIEW_CERT' => $certText,
            'ALS_ACTION_SCRIPT' => $actionScript
        ));
        $tpl->parse('ALS_ITEM', '.als_item');
    }

    $tpl->assign('ALS_MESSAGE', '');

}

/**
 * Generates domain alias action
 *
 * @access private
 * @param int $id Alias unique identifier
 * @param string $status Alias status
 * @return array
 */
function generateDomainAliasAction($id, $status)
{
    if ($status == 'ok') {
        return array(
            tr('Delete'),
            tohtml("alias_delete.php?id=$id", 'htmlAttr'),
            true,
            customerHasFeature('ssl') ? tr('Manage SSL certificate') : tr('View SSL certificate'),
            tohtml("cert_view.php?domain_id=$id&domain_type=als", 'htmlAttr')
        );
    }

    if ($status == 'ordered') {
        return array(tr('Delete order'), tohtml("alias_order_delete.php?del_id=$id"), false, '-', '#');
    }

    return array(tr('N/A'), '#', false, tr('N/A'), '#');
}

/**
 * Generates domain alias redirect
 *
 * @access private
 * @param int $id Alias unique identifier
 * @param string $status Alias status
 * @param string $redirectUrl Target URL for redirect request
 * @return array
 */
function generateDomainAliasRedirect($id, $status, $redirectUrl)
{
    if ($redirectUrl == 'no') {
        if ($status == 'ok') {
            return array('-', tohtml("alias_edit.php?id=$id", 'htmlAttr'), tr('Edit'));
        }

        if ($status == 'ordered') {
            return array('-', '#', tr('N/A'));
        }

        return array(tr('N/A'), '#', tr('N/A'));
    }

    if ($status == 'ok') {
        return array($redirectUrl, tohtml("alias_edit.php?id=$id", 'htmlAttr'), tr('Edit'));
    }

    if ($status == 'ordered') {
        return array($redirectUrl, '#', tr('N/A'));
    }

    return array(tr('N/A'), '#', tr('N/A'));
}

/**
 * Generates subdomains list
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function generateSubdomainsList($tpl)
{
    if (!customerHasFeature('subdomains')) {
        $tpl->assign('SUBDOMAINS_BLOCK', '');
        return;
    }

    $domainId = get_user_domain_id($_SESSION['user_id']);

    // Subdomains
    $stmt1 = exec_query(
        "
            SELECT t1.subdomain_id, t1.subdomain_name, t1.subdomain_mount, t1.subdomain_status, t1.subdomain_url_forward,
                t2.domain_name, t3.status AS ssl_status
            FROM subdomain AS t1 JOIN domain AS t2 USING(domain_id)
            LEFT JOIN ssl_certs AS t3 ON(t1.subdomain_id = t3.domain_id AND t3.domain_type = 'sub')
            WHERE t1.domain_id = ? ORDER BY t1.subdomain_name
        ",
        $domainId
    );

    // Domain aliases subdomains
    $stmt2 = exec_query(
        "
            SELECT t1.subdomain_alias_id, t1.subdomain_alias_name, t1.subdomain_alias_mount,
                t1.subdomain_alias_url_forward, t1.subdomain_alias_status, t2.alias_name, t3.status AS ssl_status
            FROM subdomain_alias AS t1 JOIN domain_aliasses AS t2 USING(alias_id)
            LEFT JOIN ssl_certs AS t3 ON(t1.subdomain_alias_id = t3.domain_id AND t3.domain_type = 'alssub')
            WHERE t2.domain_id = ?
            ORDER BY t1.subdomain_alias_name
        ",
        $domainId
    );

    if (!$stmt1->rowCount() && !$stmt2->rowCount()) {
        $tpl->assign(array(
            'SUB_MSG' => tr('You do not have subdomains.'),
            'SUB_LIST' => ''
        ));
        return;
    }

    while ($row = $stmt1->fetchRow()) {
        $domainName = $row['domain_name'];
        $subId = $row['subdomain_id'];
        $subName = $row['subdomain_name'];
        $subStatus = $row['subdomain_status'];
        $subUrlForward = $row['subdomain_url_forward'];
        $subMountPoint = $row['subdomain_mount'];

        list($action, $actionScript, $isStatusOk, $certText, $certScript) = generateSubdomainAction($subId, $subStatus);
        list($redirectUrl, $editLink, $edit) = generateSubdomainRedirect($subId, $subStatus, $subUrlForward, 'dmn');

        $domainName = decode_idna($domainName);
        $subName = decode_idna($subName);
        $redirectUrl = decode_idna($redirectUrl);

        if ($isStatusOk) {
            $tpl->assign(array(
                'SUB_NAME' => tohtml($subName),
                'SUB_ALIAS_NAME' => tohtml($domainName),
                'SUB_STATUS_RELOAD_FALSE' => ''
            ));
            $tpl->parse('SUB_STATUS_RELOAD_TRUE', 'sub_status_reload_true');
        } else {
            $tpl->assign(array(
                'SUB_NAME' => tohtml($subName),
                'SUB_ALIAS_NAME' => tohtml($domainName),
                'SUB_STATUS_RELOAD_TRUE' => ''
            ));
            $tpl->parse('SUB_STATUS_RELOAD_FALSE', 'sub_status_reload_false');
        }

        $tpl->assign(array(
            'SUB_MOUNT' => tohtml($subMountPoint),
            'SUB_REDIRECT' => $redirectUrl,
            'SUB_STATUS' => translate_dmn_status($subStatus),
            'SUB_SSL_STATUS' => is_null($row['ssl_status'])
                ? tr('Disabled')
                : (
                    in_array($row['ssl_status'], array('toadd', 'tochange', 'todelete', 'ok'))
                        ? translate_dmn_status($row['ssl_status'])
                        : '<span style="color: red;font-weight: bold">' . tr('Invalid SSL certificate') . "</span>"
                ),
            'SUB_EDIT_LINK' => $editLink,
            'SUB_EDIT' => $edit,
            'CERT_SCRIPT' => $certScript,
            'VIEW_CERT' => $certText,
            'SUB_ACTION' => $action,
            'SUB_ACTION_SCRIPT' => $actionScript
        ));
        $tpl->parse('SUB_ITEM', '.sub_item');
    }

    while ($row = $stmt2->fetchRow(PDO::FETCH_ASSOC)) {
        $alsName = $row['alias_name'];
        $alssubId = $row['subdomain_alias_id'];
        $alssubName = $row['subdomain_alias_name'];
        $alssubStatus = $row['subdomain_alias_status'];
        $alssubMountPoint = $row['subdomain_alias_mount'];
        $alssubUrlForward = $row['subdomain_alias_url_forward'];

        list($action, $actionScript, $isStatusOk, $certText, $certScript) = generateSubdomainAliasAction($alssubId, $alssubStatus);
        list($redirectUrl, $editLink, $edit) = generateSubdomainRedirect($alssubId, $alssubStatus, $alssubUrlForward, 'als');

        $alsName = decode_idna($alsName);
        $name = decode_idna($alssubName);
        $redirectUrl = decode_idna($redirectUrl);

        if ($isStatusOk) {
            $tpl->assign(array(
                'SUB_NAME' => tohtml($name),
                'SUB_ALIAS_NAME' => tohtml($alsName),
                'SUB_STATUS_RELOAD_FALSE' => ''
            ));
            $tpl->parse('SUB_STATUS_RELOAD_TRUE', 'sub_status_reload_true');
        } else {
            $tpl->assign(array(
                'SUB_NAME' => tohtml($name),
                'SUB_ALIAS_NAME' => tohtml($alsName),
                'SUB_STATUS_RELOAD_TRUE' => ''
            ));
            $tpl->parse('SUB_STATUS_RELOAD_FALSE', 'sub_status_reload_false');
        }

        $tpl->assign(array(
            'SUB_NAME' => tohtml($name),
            'SUB_MOUNT' => tohtml($alssubMountPoint),
            'SUB_REDIRECT' => $redirectUrl,
            'SUB_STATUS' => translate_dmn_status($alssubStatus),
            'SUB_SSL_STATUS' => is_null($row['ssl_status'])
                ? tr('Disabled')
                : (
                    in_array($row['ssl_status'], array('toadd', 'tochange', 'todelete', 'ok'))
                        ? translate_dmn_status($row['ssl_status'])
                        : '<span style="color: red;font-weight: bold">' . tr('Invalid SSL certificate') . "</span>"
                ),
            'SUB_EDIT_LINK' => $editLink,
            'SUB_EDIT' => $edit,
            'CERT_SCRIPT' => $certScript,
            'VIEW_CERT' => $certText,
            'SUB_ACTION' => $action,
            'SUB_ACTION_SCRIPT' => $actionScript
        ));

        $tpl->parse('SUB_ITEM', '.sub_item');
    }

    $tpl->assign('SUB_MESSAGE', '');
}

/**
 * Generates subdomain redirect
 *
 * @access private
 * @param int $id Subdomain unique identifier
 * @param string $status Subdomain status
 * @param string $redirectUrl Target URL for redirect request
 * @param string $entityType Subdomain type (dmn|als)
 * @return array
 */
function generateSubdomainRedirect($id, $status, $redirectUrl, $entityType)
{
    if ($status == 'ok') {
        return array(
            $redirectUrl == 'no' ? '-' : $redirectUrl,
            tohtml("subdomain_edit.php?id=$id&type=$entityType", 'htmlAttr'),
            tr('Edit')
        );
    }

    if ($status == 'ordered') {
        return array($redirectUrl == 'no' ? '-' : $redirectUrl, '#', tr('N/A'));
    }

    return array(tr('N/A'), '#', tr('N/A'));
}

/**
 * Generates subdomain action
 *
 * @access private
 * @param int $id Subdomain unique identifier
 * @param string $status Subdomain status
 * @return array
 */
function generateSubdomainAction($id, $status)
{
    if ($status == 'ok') {
        return array(
            tr('Delete'), tohtml("subdomain_delete.php?id=$id", 'htmlAttr'),
            true,
            customerHasFeature('ssl') ? tr('Manage SSL certificate') : tr('View SSL certificate'),
            tohtml("cert_view.php?domain_id=$id&domain_type=sub", 'htmlAttr'),
        );
    }

    return array(tr('N/A'), '#', false, tr('N/A'), '#');
}

/**
 * Generates subdomain aliases action
 *
 * @access private
 * @param int $id Subdomain Alias unique identifier
 * @param string $status Subdomain alias Status
 * @return array
 */
function generateSubdomainAliasAction($id, $status)
{
    if ($status == 'ok') {
        return array(
            tr('Delete'),
            tohtml("alssub_delete.php?id=$id", 'htmlAttr'),
            true,
            customerHasFeature('ssl') ? tr('Manage SSL certificate') : tr('View SSL certificate'),
            tohtml("cert_view.php?domain_id=$id&domain_type=alssub", 'htmlAttr'),
        );
    }

    return array(tr('N/A'), '#', false, tr('N/A'), '#');
}

/**
 * Generates custom DNS records list
 *
 * @param iMSCP_pTemplate $tpl Template engine
 * @return void
 */
function generateCustomDnsRecordsList($tpl)
{
    $filterCond = '';
    if (!customerHasFeature('custom_dns_records')) {
        $filterCond = "AND owned_by <> 'custom_dns_feature'";
    }

    $stmt = exec_query(
        "
            SELECT t1.*, IFNULL(t3.alias_name, t2.domain_name) zone_name
            FROM domain_dns AS t1 LEFT JOIN domain AS t2 USING (domain_id)
            LEFT JOIN domain_aliasses AS t3 USING (alias_id)
            WHERE t1.domain_id = ? $filterCond ORDER BY t1.domain_id, t1.alias_id, t1.domain_dns, t1.domain_type
        ",
        get_user_domain_id($_SESSION['user_id'])
    );

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetchRow()) {
            list($actionEdit, $actionScriptEdit) = generateCustomDnsRecordAction(
                'edit',
                $row['owned_by'] === 'custom_dns_feature' ? $row['domain_dns_id'] : ($row['owned_by'] === 'ext_mail_feature' ? ($row['alias_id'] ? $row['alias_id'] . ';alias' : $row['domain_id'] . ';normal' ) : null),
                $row['domain_dns_status'],
                $row['owned_by']
            );

            if ($row['owned_by'] !== 'custom_dns_feature') {
                $tpl->assign('DNS_DELETE_LINK', '');
            } else {
                list($actionDelete, $actionScriptDelete) = generateCustomDnsRecordAction('Delete', $row['domain_dns_id'], $row['domain_dns_status']);
                $tpl->assign(array(
                    'DNS_ACTION_SCRIPT_DELETE' => $actionScriptDelete,
                    'DNS_ACTION_DELETE' => $actionDelete,
                    'DNS_TYPE_RECORD' => tr("%s record", $row['domain_type'])
                ));
                $tpl->parse('DNS_DELETE_LINK', '.dns_delete_link');
            }

            // Remove TTL part if any
            # FIXME TTL must be in dedicated column
            if (strpos($row['domain_dns'], ' ') !== false) {
                $dnsName = explode(' ', $row['domain_dns']);
                $dnsName = $dnsName[0];
            } else {
                $dnsName = $row['domain_dns'];
            }

            $tpl->assign(array(
                'DNS_DOMAIN' => tohtml(decode_idna($row['zone_name'])),
                'DNS_NAME' => tohtml(decode_idna($dnsName)),
                'DNS_CLASS' => tohtml($row['domain_class']),
                'DNS_TYPE' => tohtml($row['domain_type']),
                'LONG_DNS_DATA' => tohtml(wordwrap(decode_idna($row['domain_text']), 80, "\n", true)),
                'SHORT_DNS_DATA' => decode_idna((strlen($row['domain_text']) > 20) ? substr($row['domain_text'], 0, 17) . '...' : $row['domain_text']),
                'DNS_ACTION_SCRIPT_EDIT' => $actionScriptEdit,
                'DNS_ACTION_EDIT' => $actionEdit
            ));

            $tpl->parse('DNS_ITEM', '.dns_item');
            $tpl->assign('DNS_DELETE_LINK', '');
        }

        $tpl->parse('DNS_LIST', 'dns_list');
        $tpl->assign('DNS_MESSAGE', '');
    } else {
        if (customerHasFeature('custom_dns_records')) {
            $tpl->assign(array(
                'DNS_MSG' => tr('You do not have DNS resource records.'),
                'DNS_LIST' => ''
            ));
        } else {
            $tpl->assign('CUSTOM_DNS_RECORDS_BLOCK', '');
        }
    }
}

/**
 * Generates custom DNS record action
 *
 * @access private
 * @param string $action Action
 * @param string|null $id Custom DNS record unique identifier
 * @param string $status Custom DNS record status
 * @param string $ownedBy Owner of the DNS record
 * @return array
 */
function generateCustomDnsRecordAction($action, $id, $status, $ownedBy = 'custom_dns_feature')
{
    if ($status == 'ok') {
        if ($action == 'edit') {
            if ($ownedBy === 'custom_dns_feature') {
                return array(tr('Edit'), tohtml("dns_edit.php?id=$id", 'htmlAttr'));
            }

            if ($ownedBy === 'ext_mail_feature') {
                return array(tr('Edit'), tohtml("mail_external_edit.php?item=$id", 'htmlAttr'));
            }
        } elseif ($ownedBy === 'custom_dns_feature') {
            return array(tr('Delete'), tohtml("dns_delete.php?id=$id", 'htmlAttr'));
        }
    }

    return array(tr('N/A'), '#');
}

/***********************************************************************************************************************
 * Main
 */

require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);
check_login('user');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(array(
    'layout' => 'shared/layouts/ui.tpl',
    'page' => 'client/domains_manage.tpl',
    'page_message' => 'layout',
    'domain_list' => 'page',
    'domain_item' => 'domain_list',
    'domain_status_reload_true' => 'domain_item',
    'domain_status_reload_false' => 'domain_item',
    'domain_aliases_block' => 'page',
    'als_message' => 'domain_aliases_block',
    'als_list' => 'domain_aliases_block',
    'als_item' => 'als_list',
    'als_status_reload_true' => 'als_item',
    'als_status_reload_false' => 'als_item',
    'subdomains_block' => 'page',
    'sub_message' => 'subdomains_block',
    'sub_list' => 'subdomains_block',
    'sub_item' => 'sub_list',
    'sub_status_reload_true' => 'sub_item',
    'sub_status_reload_false' => 'sub_item',
    'custom_dns_records_block' => 'page',
    'dns_message' => 'custom_dns_records_block',
    'dns_list' => 'custom_dns_records_block',
    'dns_item' => 'dns_list',
    'dns_edit_link' => 'dns_item',
    'dns_delete_link' => 'dns_item'
));

$tpl->assign(array(
    'TR_PAGE_TITLE' => tr('Client / Domains'),
    'TR_DOMAINS' => tr('Domains'),
    'TR_CREATE_DATE' => tr('Creation date'),
    'TR_EXPIRE_DATE' => tr('Expire date'),
    'TR_DOMAIN_ALIASES' => tr('Domain aliases'),
    'TR_SUBDOMAINS' => tr('Subdomains'),
    'TR_NAME' => tr('Name'),
    'TR_MOUNT' => tr('Mount point'),
    'TR_REDIRECT' => tr('Redirect'),
    'TR_STATUS' => tr('Status'),
    'TR_SSL_STATUS' => tr('SSL status'),
    'TR_ACTIONS' => tr('Actions'),
    'TR_DNS' => tr('DNS resource records'),
    'TR_DNS_NAME' => tr('Name'),
    'TR_DNS_CLASS' => tr('Class'),
    'TR_DNS_TYPE' => tr('Type'),
    'TR_DNS_ACTION' => tr('Actions'),
    'TR_DNS_DATA' => tr('Record data'),
    'TR_DOMAIN_NAME' => tr('Domain')
));

iMSCP_Events_Aggregator::getInstance()->registerListener('onGetJsTranslations', function ($e) {
    /** @var $e \iMSCP_Events_Event */
    $translations = $e->getParam('translations');
    $translations['core']['als_delete_alert'] = tr('Are you sure you want to delete this domain alias?');
    $translations['core']['sub_delete_alert'] = tr('Are you sure you want to delete this subdomain?');
    $translations['core']['dns_delete_alert'] = tr('Are you sure you want to delete this DNS record?');
});

generateNavigation($tpl);
generateDomainsList($tpl);
generateSubdomainsList($tpl);
generateDomainAliasesList($tpl);
generateCustomDnsRecordsList($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();

unsetMessages();
